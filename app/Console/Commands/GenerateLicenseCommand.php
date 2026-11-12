<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateLicenseCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'license:generate
                            {--to= : Licensed firm or CA name (e.g. "Sharma & Associates CA")}
                            {--email= : CA or firm email address}
                            {--edition=Lifetime Edition : Edition name}
                            {--staff=25 : Maximum staff users allowed}
                            {--amc-years=1 : Number of years of free updates/AMC included}
                            {--hw= : Optional machine hardware fingerprint (lock to specific machine)}';

    /**
     * The console command description.
     */
    protected $description = 'Generate a cryptographically signed license key for WebRajya ERP';

    /**
     * Execute the console command.
     */
    public function handle(LicenseService $licenseService): int
    {
        $licensedTo = $this->option('to') ?: $this->ask('Enter CA Firm or Licensee Name');
        if (empty($licensedTo)) {
            $this->error('Licensee name is required.');
            return self::FAILURE;
        }

        $email = $this->option('email') ?: $this->ask('Enter Licensee Email (optional)');
        $edition = $this->option('edition');
        $staff = (int) $this->option('staff');
        $amcYears = (int) $this->option('amc-years');
        $amcDate = Carbon::now()->addYears($amcYears)->format('Y-m-d');
        $hw = $this->option('hw');

        $key = $licenseService->generateLicenseKey(
            licensedTo: $licensedTo,
            email: $email,
            edition: $edition,
            maxStaff: $staff,
            amcExpiresAt: $amcDate,
            boundHardwareId: $hw
        );

        $this->info('====================================================');
        $this->info('       WebRajya ERP — License Key Generated        ');
        $this->info('====================================================');
        $this->line("<comment>Licensed To:</comment>   {$licensedTo}");
        if ($email) {
            $this->line("<comment>Email:</comment>         {$email}");
        }
        $this->line("<comment>Edition:</comment>       {$edition}");
        $this->line("<comment>Staff Limit:</comment>   {$staff} users");
        $this->line("<comment>AMC Expiry:</comment>    {$amcDate} ({$amcYears} year(s))");
        $this->line("<comment>Hardware Lock:</comment> " . ($hw ?: 'Any (Locks to first activated machine)'));
        $this->newLine();
        $this->line('<info>LICENSE KEY:</info>');
        $this->line($key);
        $this->newLine();
        $this->comment('Deliver this key to the CA. They can enter it at /license/activate');
        $this->comment('or run: php artisan license:activate "' . $key . '"');
        $this->info('====================================================');

        return self::SUCCESS;
    }
}
