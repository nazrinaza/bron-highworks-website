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

final class ProvisioningInputException extends RuntimeException {}

$stage = 'reading the private JSON file';
try {
    if (! is_readable($argv[2])) {
        throw new ProvisioningInputException('The private admin JSON file is not readable by PHP. Check its ownership and permissions.');
    }
    $json = file_get_contents($argv[2]);
    if ($json === false) {
        throw new ProvisioningInputException('The private admin JSON file could not be read.');
    }
    // Some text editors add a UTF-8 BOM; accept it without changing the credentials.
    if (str_starts_with($json, "\xEF\xBB\xBF")) {
        $json = substr($json, 3);
    }
    $credentials = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    if (! is_array($credentials) || array_is_list($credentials)) {
        throw new ProvisioningInputException('The admin JSON must be an object with name, email and password fields.');
    }
    $stage = 'validating administrator fields';
    $validator = Validator::make($credentials, [
        'email' => ['required', 'email', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'password' => ['required', 'string', Password::min(12)->letters()->numbers()],
    ]);
    if ($validator->fails()) {
        $requirements = [
            'name' => 'name must be a non-empty text value of at most 255 characters',
            'email' => 'email must be a valid email address of at most 255 characters',
            'password' => 'password must be text with at least 12 characters, including letters and numbers',
        ];
        $messages = [];
        foreach ($requirements as $field => $requirement) {
            if ($validator->errors()->has($field)) {
                $messages[] = $requirement;
            }
        }
        throw new ProvisioningInputException('Invalid admin JSON: '.implode('; ', $messages).'.');
    }
    $email = strtolower($credentials['email']);
    $stage = 'saving the administrator in the database';
    DB::transaction(function () use ($credentials, $email): void {
        if (User::where('is_admin', true)->exists()) {
            throw new ProvisioningInputException('An administrator already exists. If provisioning previously succeeded, remove bron-admin.json and redeploy; existing passwords will not be changed.');
        }
        if (User::where('email', $email)->exists()) {
            throw new ProvisioningInputException('A user with that email already exists. Use the interactive bron:admin command through hosting support to promote or reset that account.');
        }
        $user = new User;
        $user->name = $credentials['name'];
        $user->email = $email;
        $user->password = $credentials['password'];
        $user->is_admin = true;
        $user->save();
    });
    if (! unlink($argv[2])) {
        throw new ProvisioningInputException('The administrator was saved, but bron-admin.json could not be removed. Delete that file in File Manager and redeploy.');
    }
    fwrite(STDOUT, "Initial administrator created; private provisioning file removed.\n");
} catch (JsonException $exception) {
    fwrite(STDERR, "BRON ADMIN ERROR: invalid JSON. Use double quotes, escape quotes/backslashes inside values, and remove trailing commas. Do not share the file's contents.\n");
    exit(1);
} catch (ProvisioningInputException $exception) {
    fwrite(STDERR, 'BRON ADMIN ERROR: '.$exception->getMessage()."\n");
    exit(1);
} catch (Throwable $exception) {
    // Never print JSON input, passwords, SQL bindings or exception details to deployment logs.
    fwrite(STDERR, "BRON ADMIN ERROR: failed while $stage. Error type: ".get_class($exception).". No credentials have been printed.\n");
    exit(1);
}
