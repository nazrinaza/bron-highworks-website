<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

// CLI-only, one-time provisioning from a private file created in cPanel File Manager.
if (PHP_SAPI !== 'cli' || $argc !== 3) {
    exit(1);
}

require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    $credentials = json_decode(file_get_contents($argv[2]), true, flags: JSON_THROW_ON_ERROR);
    $validator = Validator::make($credentials, [
        'email' => ['required', 'email', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'password' => ['required', Password::min(12)->letters()->numbers()],
    ]);
    if ($validator->fails()) {
        throw new RuntimeException('Invalid administrator details.');
    }
    $email = strtolower($credentials['email']);
    DB::transaction(function () use ($credentials, $email): void {
        if (User::where('is_admin', true)->exists() || User::where('email', $email)->exists()) {
            throw new RuntimeException('An administrator or matching user already exists.');
        }
        $user = new User;
        $user->name = $credentials['name'];
        $user->email = $email;
        $user->password = $credentials['password'];
        $user->is_admin = true;
        $user->save();
    });
    if (! unlink($argv[2])) {
        throw new RuntimeException('Remove the private provisioning file in File Manager.');
    }
    fwrite(STDOUT, "Initial administrator created; private provisioning file removed.\n");
} catch (Throwable $exception) {
    // Never print JSON input, passwords, SQL bindings or exception details to deployment logs.
    fwrite(STDERR, "Admin provisioning failed. Check the private JSON file and whether an admin already exists. Remove that file if provisioning already succeeded.\n");
    exit(1);
}
