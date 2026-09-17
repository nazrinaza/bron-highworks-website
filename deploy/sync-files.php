<?php

// CLI deployment copier. Never follow destination symlinks or alter protected runtime paths.
if (PHP_SAPI !== 'cli' || $argc !== 4) {
    fwrite(STDERR, "Usage: php sync-files.php SOURCE DESTINATION app|public|storage\n");
    exit(1);
}

function entries(string $directory): array
{
    $names = scandir($directory);
    if ($names === false) {
        throw new RuntimeException('Unable to list deployment directory.');
    }

    return array_values(array_diff($names, ['.', '..']));
}

function removeEntry(string $path): void
{
    if (is_link($path) || ! is_dir($path)) {
        if (! unlink($path)) {
            throw new RuntimeException('Unable to remove stale deployment file.');
        }

        return;
    }
    foreach (entries($path) as $name) {
        removeEntry($path.'/'.$name);
    }
    if (! rmdir($path)) {
        throw new RuntimeException('Unable to remove stale deployment directory.');
    }
}

function syncDirectory(string $source, string $destination, array $protected, bool $onlyMissing): void
{
    if (is_link($destination) || (file_exists($destination) && ! is_dir($destination))) {
        if ($onlyMissing) {
            throw new RuntimeException('Runtime directory is not a regular directory.');
        }
        removeEntry($destination);
    }
    $existing = is_dir($destination);
    if (! $existing && ! mkdir($destination, 0755, true)) {
        throw new RuntimeException('Unable to create deployment directory.');
    }
    if ((! $onlyMissing || ! $existing) && ! chmod($destination, 0755)) {
        throw new RuntimeException('Unable to set deployment directory permissions.');
    }
    $names = entries($source);
    if (! $onlyMissing) {
        foreach (entries($destination) as $name) {
            if (! in_array($name, $protected, true) && ! in_array($name, $names, true)) {
                removeEntry($destination.'/'.$name);
            }
        }
    }
    foreach ($names as $name) {
        if (in_array($name, $protected, true)) {
            continue;
        }
        $from = $source.'/'.$name;
        $to = $destination.'/'.$name;
        if (is_dir($from)) {
            syncDirectory($from, $to, [], $onlyMissing);
        } elseif (! $onlyMissing || (! file_exists($to) && ! is_link($to))) {
            if (is_link($to) || is_dir($to)) {
                removeEntry($to);
            }
            if (! copy($from, $to) || ! chmod($to, 0644)) {
                throw new RuntimeException('Unable to copy deployment file.');
            }
        }
    }
}

try {
    $source = realpath($argv[1]);
    $destination = realpath($argv[2]);
    $mode = $argv[3];
    if (! $source || ! $destination || ! is_dir($source) || ! is_dir($destination)
        || is_link($argv[1]) || is_link($argv[2]) || ! in_array($mode, ['app', 'public', 'storage'], true)
        || $source === $destination || str_starts_with($destination.'/', $source.'/')
        || str_starts_with($source.'/', $destination.'/')) {
        throw new RuntimeException('Invalid or overlapping deployment directories.');
    }
    // Reject source symlinks before touching the destination.
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $entry) {
        if ($entry->isLink()) {
            throw new RuntimeException('Deployment source contains an unexpected symlink.');
        }
    }
    $protected = match ($mode) {
        'app' => ['.env', 'storage'],
        'public' => ['.htaccess', '.well-known', 'storage'],
        'storage' => [],
    };
    syncDirectory($source, $destination, $protected, $mode === 'storage');
    fwrite(STDOUT, "BRON: PHP file synchronization completed ($mode).\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'BRON ERROR: '.$exception->getMessage()."\n");
    exit(1);
}
