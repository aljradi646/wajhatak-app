<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateLuxAdmin extends Command
{
    protected $signature = 'lux:create-admin {--name=} {--email=} {--password=}';

    protected $description = 'Create or promote an active administrator without storing credentials in source code.';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('الاسم');
        $email = $this->option('email') ?: $this->ask('البريد الإلكتروني');
        $password = $this->option('password') ?: $this->secret('كلمة المرور');

        if (! $name || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $password || mb_strlen($password) < 10) {
            $this->error('أدخل اسمًا وبريدًا صحيحًا وكلمة مرور لا تقل عن 10 أحرف.');

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => mb_strtolower($email)]);
        $user->fill(['name' => $name, 'password' => Hash::make($password), 'is_active' => true, 'locale' => 'ar']);
        $user->save();
        Role::findOrCreate('admin');
        $user->syncRoles(['admin']);

        $this->info("تم تجهيز حساب المدير {$user->email}.");

        return self::SUCCESS;
    }
}
