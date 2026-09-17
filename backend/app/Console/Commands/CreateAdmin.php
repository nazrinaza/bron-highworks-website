<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'bron:admin {email} {--name=BRON Admin}';

    protected $description = 'Create an administrator, or reset an existing administrator password interactively';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $password = $this->secret('Password (at least 12 characters)');
        $validator = Validator::make(['email' => $email, 'password' => $password], ['email' => ['required', 'email'], 'password' => ['required', Password::min(12)->letters()->numbers()]]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        if ($password !== $this->secret('Confirm password')) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }
        $user = User::firstOrNew(['email' => $email]);
        $user->name = $this->option('name');
        $user->password = $password;
        $user->is_admin = true;
        $user->save();
        $this->info('Administrator saved.');

        return self::SUCCESS;
    }
}
