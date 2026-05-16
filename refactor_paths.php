<?php
/**
 * Front-Controller Path Refactor Tool
 * Automatically migrates relative includes to ROOT_PATH absolute constants.
 */

declare(strict_types=1);

$rootPath = __DIR__;
$templatesDir = $rootPath . DIRECTORY_SEPARATOR . 'templates';

if (!is_dir($templatesDir)) {
    die("Error: 'templates/' directory not found in $rootPath\n");
}

// Mapping of legacy relative segments to new absolute paths
// Note: Using 'src/Auth/Auth_check.php' as per your new architecture
$mapping = [
    'config/db.php'                => 'config/db.php',
    'config/db.php'          => 'config/db.php',
    'includes/auth_check.php'      => 'src/Auth/Auth_check.php',
    'src/Auth/Auth_check.php'      => 'src/Auth/Auth_check.php',
    'includes/header.php'          => 'templates/includes/header.php',
    'includes/footer.php'          => 'templates/includes/footer.php',
    'includes/functions.php'       => 'templates/includes/functions.php',
    'includes/settings_loader.php' => 'templates/includes/settings_loader.php',
];

// Regex to find: require/include (with or without _once), optional __DIR__, and relative ../ paths
$pattern = '/(require|include)(_once)?\s*\(?\s*(?:__DIR__\s*\.\s*)?\s*([\'"])(?:\.\.\/)+(.+?)\3\s*\)?\s*;/i';

$updatedFiles = 0;
$totalFiles = 0;

echo "🚀 Starting path refactor in $templatesDir...\n";

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($templatesDir));

foreach ($iterator as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') {
        continue;
    }

    $filePath = $file->getRealPath();
    $content = file_get_contents($filePath);
    $totalFiles++;

    $newContent = preg_replace_callback($pattern, function ($matches) use ($mapping) {
        $statement = $matches[1] . ($matches[2] ?? ''); // e.g., require_once
        $capturedPath = $matches[4];                   // e.g., includes/header.php

        // Look for the match in our mapping
        if (isset($mapping[$capturedPath])) {
            return "{$statement} ROOT_PATH . '{$mapping[$capturedPath]}';";
        }

        // Fallback: If not in map, just swap dots for ROOT_PATH generically
        return "{$statement} ROOT_PATH . '{$capturedPath}';";
    }, $content, -1, $count);

    if ($count > 0) {
        file_put_contents($filePath, $newContent);
        echo "✅ Updated: " . str_replace($rootPath, '', $filePath) . " ($count changes)\n";
        $updatedFiles++;
    }
}

echo "\n--------------------------------------------------\n";
echo "✨ Refactor Complete!\n";
echo "📂 Total PHP files scanned: $totalFiles\n";
echo "🛠️  Files modified: $updatedFiles\n";
echo "--------------------------------------------------\n";
if ($updatedFiles > 0) {
    echo "Tip: Run a search for 'require' in your editor to verify unusual edge cases.\n";
}
?>