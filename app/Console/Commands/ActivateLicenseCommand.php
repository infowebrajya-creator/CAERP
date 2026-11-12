<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class ActivateLicenseCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'license:activate {key? : The cryptographically signed license key}';

    /**
     * The console command description.
     */
    protected $description = 'Activate WebRajya ERP on this machine with a license key';

    /**
     * Execute the console command.
     */
    public function handle(LicenseService $licenseService): int
    {
        $key = $this->argument('key') ?: $this->ask('Enter your WebRajya license key');
        if (empty($key)) {
            $this->error('License key is required.');
            return self::FAILURE;
        }

        $result = $licenseService->activate(trim($key));

        if (! $result['success']) {
            $this->error('Activation Failed: ' . $result['message']);
            return self::FAILURE;
        }

        $this->info($result['message']);
        $this->line("Machine ID: {$licenseService->getMachineFingerprint()}");

        return self::SUCCESS;
    }
}
