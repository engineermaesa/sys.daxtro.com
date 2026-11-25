<?php

// Uses a newly initialized private MySQL instance. Never reads .env or bootstraps Laravel.
require dirname(__DIR__, 2).'/vendor/autoload.php';

use Symfony\Component\Process\Process;

$binary = $argv[1] ?? 'C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysqld.exe';
if (!is_file($binary) || !extension_loaded('pdo_mysql')) {
    throw new RuntimeException('Requires local mysqld binary and pdo_mysql. Pass binary path as the only optional argument.');
}
$directory = sys_get_temp_dir().'/quotation-sql-local-'.bin2hex(random_bytes(6));
mkdir($directory, 0700);
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if (!$socket) throw new RuntimeException($error);
$port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
fclose($socket);
$initialize = new Process([$binary, '--no-defaults', '--initialize-insecure', '--datadir='.$directory.'/data', '--log-error='.$directory.'/initialize.log']);
$initialize->setTimeout(120);
$initialize->mustRun();
$server = new Process([$binary, '--no-defaults', '--datadir='.$directory.'/data', '--bind-address=127.0.0.1', '--port='.$port, '--mysqlx=0', '--log-error='.$directory.'/server.log']);
$server->setTimeout(null);
$server->start();
$pdo = null;
$checks = 0;
$assert = function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
try {
    $deadline = microtime(true) + 45;
    while (microtime(true) < $deadline && $server->isRunning()) {
        try {
            $pdo = new PDO('mysql:host=127.0.0.1;port='.$port, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            break;
        } catch (PDOException $exception) {
            usleep(100000);
        }
    }
    if (!$pdo) throw new RuntimeException('Private MySQL did not become ready. Inspect '.$directory);
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    $script = file_get_contents(dirname(__DIR__, 2).'/database/sql/2026_11_22_quotation_revision_flow.sql');
    $run = function () use ($pdo, $script): void {
        $statement = $pdo->query($script);
        do {
            if ($statement->columnCount()) $statement->fetchAll();
        } while ($statement->nextRowset());
        $statement->closeCursor();
    };
    $fixture = function (string $name) use ($pdo): void {
        $pdo->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `'.$name.'`');
        $ddl = [
            "CREATE TABLE quotations (id BIGINT UNSIGNED PRIMARY KEY, quotation_no VARCHAR(255) NULL, status ENUM('draft','review','pending_finance','published','rejected','expired') NULL DEFAULT 'draft', deleted_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)",
            "CREATE TABLE temp_quotations (id BIGINT UNSIGNED PRIMARY KEY, status ENUM('draft','submitted') NOT NULL DEFAULT 'draft', deleted_at TIMESTAMP NULL)",
            'CREATE TABLE temp_quotation_items (id BIGINT PRIMARY KEY)',
            'CREATE TABLE temp_quotation_payment_terms (id BIGINT PRIMARY KEY)',
            'CREATE TABLE quotation_reviews (id BIGINT PRIMARY KEY, notes TEXT)',
            'CREATE TABLE quotation_logs (id BIGINT PRIMARY KEY, quotation_id BIGINT UNSIGNED NULL, temp_quotation_id BIGINT UNSIGNED NULL)',
            "CREATE TABLE document_counters (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, type ENUM('ticket','work_order') NOT NULL, date DATE NOT NULL, last_number INT UNSIGNED NOT NULL DEFAULT 0, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, UNIQUE KEY document_counters_type_date_unique(type,date))",
            'CREATE TABLE migrations (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(255) NOT NULL, batch INT NOT NULL)',
        ];
        foreach ($ddl as $query) $pdo->exec($query.' ENGINE=InnoDB');
        $pdo->exec("INSERT INTO migrations (migration,batch) VALUES ('existing_migration',9)");
        $pdo->exec("INSERT INTO document_counters(type,date,last_number) VALUES ('ticket','2026-11-22',8),('work_order','2026-11-22',12)");
        $pdo->exec("INSERT INTO quotation_reviews VALUES (1,'Keep existing review')");
        $pdo->exec("INSERT INTO temp_quotations(id,status) VALUES (1,'draft'),(2,'submitted')");
        $pdo->exec("INSERT INTO quotations(id,quotation_no,status,deleted_at) VALUES (1,'QT_DEAL_0007','published',NULL),(2,'QT_DEAL_0042','rejected','2026-11-21 00:00:00'),(3,'QT001','expired',NULL),(4,NULL,'draft',NULL),(5,'','draft',NULL),(6,'qt_deal_900','review',NULL),(7,CONCAT('QT_DEAL_600',CHAR(10)),'review',NULL)");
    };
    $fixture('quotation_sql_valid');
    $run();
    $assert((int) $pdo->query('SELECT @qr_ready')->fetchColumn() === 1, 'Valid fixture was blocked');
    $assert((int) $pdo->query("SELECT last_number FROM document_counters WHERE type='quotation'")->fetchColumn() === 42, 'Seed must include deleted records and exclude malformed numbers');
    $assert($pdo->query('SELECT revision_code FROM quotations WHERE id=1')->fetchColumn() === 'QT_DEAL_0007_R0', 'R0 backfill failed');
    $assert($pdo->query('SELECT revision_code FROM quotations WHERE id=4')->fetchColumn() === null, 'Unnumbered quotation was backfilled');
    $assert($pdo->query('SELECT status FROM quotations WHERE id=1')->fetchColumn() === 'published', 'Existing status changed');
    $assert($pdo->query('SELECT updated_at FROM quotations WHERE id=1')->fetchColumn() === null, 'Backfill changed updated_at');
    $assert((int) $pdo->query("SELECT COUNT(*) FROM migrations WHERE migration LIKE '2026_11_20_%'")->fetchColumn() === 6, 'Missing migration records');
    $assert((int) $pdo->query('SELECT MAX(batch) FROM migrations')->fetchColumn() === 10, 'Wrong migration batch');
    $assert((int) $pdo->query('SELECT SUM(lock_version) FROM temp_quotations')->fetchColumn() === 0, 'Draft lock version default incorrect');
    $assert($pdo->query('SELECT notes FROM quotation_reviews WHERE id=1')->fetchColumn() === 'Keep existing review', 'Existing reviews changed');
    $assert((int) $pdo->query("SELECT SUM(last_number) FROM document_counters WHERE type<>'quotation'")->fetchColumn() === 20, 'Other counters changed');
    $pdo->exec("UPDATE quotations SET revision_no=2, revision_code='QT_DEAL_0007_R2' WHERE id=1");
    $pdo->exec("UPDATE document_counters SET last_number=100 WHERE type='quotation'");
    $before = $pdo->query('SELECT * FROM migrations ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $run();
    $assert($pdo->query('SELECT * FROM migrations ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) === $before, 'Rerun changed migration records');
    $assert((int) $pdo->query("SELECT last_number FROM document_counters WHERE type='quotation'")->fetchColumn() === 100, 'Rerun lowered counter');
    $assert($pdo->query('SELECT revision_code FROM quotations WHERE id=1')->fetchColumn() === 'QT_DEAL_0007_R2', 'Rerun overwrote current revision');
    $pdo->exec("INSERT INTO quotations(id,quotation_no,status) VALUES (8,'QT_DEAL_00500','published')");
    $run();
    $assert((int) $pdo->query("SELECT last_number FROM document_counters WHERE type='quotation'")->fetchColumn() === 500, 'Rerun failed to raise counter');
    $pdo->exec("UPDATE quotations SET status='approved_by_bm' WHERE id=8");
    $assert($pdo->query('SELECT status FROM quotations WHERE id=8')->fetchColumn() === 'approved_by_bm', 'New status unavailable');

    $fixture('quotation_sql_missing');
    $pdo->exec('ALTER TABLE quotation_logs DROP COLUMN temp_quotation_id');
    $run();
    $assert((int) $pdo->query('SELECT @qr_ready')->fetchColumn() === 0, 'Missing prerequisite must block');
    $assert((int) $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND column_name='revision_no'")->fetchColumn() === 0, 'Blocked import mutated schema');
    $assert((int) $pdo->query('SELECT COUNT(*) FROM migrations')->fetchColumn() === 1, 'Blocked import registered migrations');

    $fixture('quotation_sql_overflow');
    $pdo->exec("INSERT INTO quotations(id,quotation_no) VALUES(8,'QT_DEAL_4294967296')");
    $run();
    $assert((int) $pdo->query('SELECT @qr_ready')->fetchColumn() === 0, 'Overflow must block');
    $assert((int) $pdo->query('SELECT COUNT(*) FROM migrations')->fetchColumn() === 1, 'Overflow registered migrations');
    echo json_encode(['result' => 'Passed', 'checks' => $checks, 'mysql_version' => $version, 'artifacts' => $directory, 'production_access' => false], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
} finally {
    if ($pdo) {
        try { $pdo->exec('SHUTDOWN'); } catch (Throwable $exception) { /* Server may already have stopped. */ }
        $pdo = null;
    }
    $server->stop(10);
}
