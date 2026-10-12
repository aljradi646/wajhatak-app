<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Security\TotpService;
use Illuminate\Console\Command;

class EnableAdminTwoFactor extends Command
{
    protected $signature = 'admin:2fa-enable {email : Email address of an existing administrator}';

    protected $description = 'Enable TOTP-based two-factor authentication for an administrator (secret printed once).';

    public function handle(TotpService $totp): int
    {
        $user = User::query()->where('email', mb_strtolower(trim((string) $this->argument('email'))))->first();

        if (! $user || ! $user->hasRole('admin') || ! $user->is_active) {
            $this->error('An active administrator with that email was not found.');

            return self::FAILURE;
        }

        $secret = $totp->generateSecret();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => now(),
        ])->save();

        $this->newLine();
        $this->info('TOTP is enabled. Add this account to an authenticator app now; the secret is shown only once.');
        $this->line('Secret: '.$secret);
        $this->line('Provisioning URI: '.$totp->provisioningUri($user->email, $secret));
        $this->warn('Store the secret securely. Re-run this command only when intentionally rotating the secret.');

        return self::SUCCESS;
    }
}
