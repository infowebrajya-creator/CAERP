<?php

namespace App\Console\Commands;

use App\Services\UpdateService;
use Illuminate\Console\Command;

class CheckUpdateCommand extends Command
{
    protected $signature = 'update:check';

    protected $description = 'Check for software updates and release notes for WebRajya ERP';

    public function handle(UpdateService $updateService): int
    {
        $this->info('Checking for WebRajya ERP updates...');

        $result = $updateService->checkForUpdates(forceRefresh: true);

        $this->info('====================================================');
        $this->info('            WebRajya ERP — Software Updates         ');
        $this->info('====================================================');
        $this->line("<comment>Current Version:</comment> {$result['current_version']}");
        $this->line("<comment>Latest Version:</comment>  {$result['latest_version']}");
        $this->line("<comment>AMC Status:</comment>      " . ($result['is_amc_valid'] ? '<info>Active (Eligible for updates)</info>' : '<error>Expired (AMC Renewal Required)</error>'));

        if (! empty($result['has_update'])) {
            $this->newLine();
            $this->info("🌟 NEW VERSION AVAILABLE: {$result['latest_version']} (Released: {$result['release_date']})");
            if (! empty($result['changelog'])) {
                $this->line('<comment>What\'s New:</comment>');
                foreach ($result['changelog'] as $item) {
                    $this->line(" - {$item}");
                }
            }
            if (! empty($result['download_url'])) {
                $this->line("<comment>Download URL:</comment> {$result['download_url']}");
            }
        } else {
            $this->info('Your WebRajya ERP system is up to date.');
        }
        $this->info('====================================================');

        return self::SUCCESS;
    }
}
