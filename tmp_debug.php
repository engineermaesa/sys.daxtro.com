<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'lead_claim_count=' . App\Models\Leads\LeadClaim::count() . PHP_EOL;
