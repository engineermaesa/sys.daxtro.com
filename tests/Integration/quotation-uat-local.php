<?php

// Local automated UAT only. No application bootstrap, migration, or database connection in this runner.
require dirname(__DIR__, 2).'/vendor/autoload.php';

use Symfony\Component\Process\Process;

if (count($argv) !== 1) {
    fwrite(STDERR, "Usage: php tests/Integration/quotation-uat-local.php\n");
    exit(2);
}
$project = dirname(__DIR__, 2);
$started = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
$artifacts = sys_get_temp_dir().'/quotation-uat-local-'.$started->format('Ymd-His').'-'.bin2hex(random_bytes(4));
if (!mkdir($artifacts, 0700)) throw new RuntimeException('Cannot create isolated UAT artifact directory.');
$environment = [
    'APP_ENV' => 'testing', 'APP_URL' => 'http://127.0.0.1',
    'APP_CONFIG_CACHE' => $artifacts.'/unused-config.php',
    'APP_ROUTES_CACHE' => $artifacts.'/unused-routes.php',
    'APP_EVENTS_CACHE' => $artifacts.'/unused-events.php',
    'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:',
    'DB_URL' => '', 'DATABASE_URL' => '', 'DB_HOST' => '127.0.0.1',
    'DB_USERNAME' => 'local_quotation_uat', 'DB_PASSWORD' => '',
    'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync', 'LOG_CHANNEL' => 'single',
    'BROADCAST_CONNECTION' => 'log', 'BROADCAST_DRIVER' => 'log',
    'QUOTATION_UAT_ARTIFACT_DIR' => $artifacts,
];
$head = new Process(['git', 'rev-parse', 'HEAD'], $project);
$head->run();
$manifest = [
    'started_at' => $started->format(DateTimeInterface::ATOM),
    'git_head' => $head->isSuccessful() ? trim($head->getOutput()) : null,
    'php_version' => PHP_VERSION,
    'context' => [
        'application_environment' => 'testing', 'application_url' => 'http://127.0.0.1',
        'database' => 'Named SQLite fixture connections in memory or temporary files',
        'notifications' => 'Faked by test fixtures; broadcast transport forced to log',
        'production_access' => false, 'application_database_migrated' => false,
        'mysql_verified' => false, 'browser_verified' => false,
    ],
    'artifact_directory' => $artifacts,
    'runs' => [],
];
$failed = false;
echo 'UAT_ARTIFACT_DIR='.$artifacts."\n";
foreach (['quotation-uat' => 'uat.xml', 'quotation-revision' => 'regression.xml'] as $group => $file) {
    $process = new Process([
        PHP_BINARY, $project.'/vendor/phpunit/phpunit/phpunit', '--group', $group, '--log-junit', $artifacts.'/'.$file,
    ], $project, $environment);
    $process->setTimeout(300);
    echo 'Running '.$group."\n";
    try {
        $process->run(function ($type, $buffer) { echo $buffer; });
        $exitCode = $process->getExitCode();
    } catch (Throwable $exception) {
        $process->stop();
        $exitCode = 1;
        file_put_contents($artifacts.'/'.$group.'-exception.txt', get_class($exception).': '.$exception->getMessage());
    }
    file_put_contents($artifacts.'/'.$group.'.txt', $process->getOutput().$process->getErrorOutput());
    $result = ['group' => $group, 'exit_code' => $exitCode, 'junit' => $file, 'tests' => 0, 'assertions' => 0, 'failures' => 0, 'errors' => 0, 'skipped' => 0];
    if (is_file($artifacts.'/'.$file)) {
        $xml = new DOMDocument;
        $xml->load($artifacts.'/'.$file, LIBXML_NONET);
        foreach ($xml->getElementsByTagName('testcase') as $case) {
            $result['tests']++;
            $result['assertions'] += (int) $case->getAttribute('assertions');
            foreach (['failure' => 'failures', 'error' => 'errors', 'skipped' => 'skipped'] as $element => $key) {
                $result[$key] += $case->getElementsByTagName($element)->length;
            }
        }
    } else {
        $result['errors']++;
    }
    $failed = $failed || $exitCode !== 0 || !$result['tests'] || $result['failures'] || $result['errors'] || $result['skipped'];
    $manifest['runs'][] = $result;
    file_put_contents($artifacts.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
}
$manifest['finished_at'] = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format(DateTimeInterface::ATOM);
$manifest['result'] = $failed ? 'Failed or Not Verified' : 'Passed automated local checks';
file_put_contents($artifacts.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
echo 'UAT_RESULT='.$manifest['result']."\n";
echo 'UAT_ARTIFACT_DIR='.$artifacts."\n";
exit($failed ? 1 : 0);
