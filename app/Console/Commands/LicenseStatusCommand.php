<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class LicenseStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'license:status';

    /**
     * The console command description.
     */
    protected $description = 'Display the current machine fingerprint and license status of WebRajya ERP';

    /**
     * Execute the console command.
     */
    public function handle(LicenseService $licenseService): int
    {
        $info = $licenseService->getLicenseInfo();

        $this->info('====================================================');
        $this->info('            WebRajya ERP — License Status           ');
        $this->info('====================================================');
        $this->line("<comment>Machine ID:</comment>      {$info['machine_fingerprint']}");
        $this->line("<comment>Status:</comment>          " . ($info['is_activated'] ? '<info>ACTIVATED (Valid)</info>' : '<error>UNLICENSED / INACTIVE</error>'));
        $this->line("<comment>Licensed To:</comment>      " . ($info['licensed_to'] ?: 'N/A'));
        $this->line("<comment>Edition:</comment>          {$info['edition']}");
        $this->line("<comment>Key:</comment>              " . ($info['license_key'] ?: 'None'));
        $this->line("<comment>AMC Expiry:</comment>       " . ($info['amc_expires_at'] ?: 'N/A') . " ({$info['remaining_amc_days']} days remaining)");
        $this->line("<comment>Staff User Limit:</comment> " . ($info['max_staff_users'] ?? 'N/A'));
        $this->info('====================================================');

        return self::SUCCESS;
    }
}
