<?php
// C:\laragon\www\film_studio\fix_paths.php

$targetDir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';

if (!is_dir($targetDir)) {
    die("❌ Error: Cannot find the 'templates' directory at $targetDir\n");
}

echo "Starting global path refactoring sweep inside: $targetDir...\n\n";

// Recursively iterate through all files in the templates folder
$directory = new RecursiveDirectoryIterator($targetDir);
$iterator = new RecursiveIteratorIterator($directory);

$updatedCount = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filePath = $file->getRealPath();
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // Pattern 1: Handle __DIR__ . '/../config/db.php' or variations
        $content = preg_replace(
            '/__DIR__\s*\.\s*[\'"]\/\.\.\/config\/db\.php[\'"]/',
            "ROOT_PATH . 'config/db.php'",
            $content
        );

        // Pattern 2: Handle raw loose strings like '../config/db.php'
        $content = preg_replace(
            '/[\'"]\.\.\/config\/db\.php[\'"]/',
            "ROOT_PATH . 'config/db.php'",
            $content
        );
        
        // Pattern 3: Handle raw loose strings like '../src/Auth/Auth_check.php'
        $content = preg_replace(
            '/[\'"]\.\.\/src\/Auth\/Auth_check\.php[\'"]/',
            "ROOT_PATH . 'src/Auth/Auth_check.php'",
            $content
        );

        // If changes were made, save the file back down
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "✅ Fixed paths in: " . str_replace(__DIR__, '', $filePath) . "\n";
            $updatedCount++;
        }
    }
}

echo "\n🎉 Sweep complete! Successfully updated $updatedCount template files.\n";