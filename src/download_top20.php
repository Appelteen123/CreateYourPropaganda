<?php
include 'db.php';

function removeDirectoryRecursive($path)
{
    if (!is_dir($path)) {
        return;
    }

    $items = scandir($path);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $itemPath = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($itemPath)) {
            removeDirectoryRecursive($itemPath);
        } else {
            @unlink($itemPath);
        }
    }

    @rmdir($path);
}

function createZipWithPowerShell($entries, $zipFilePath)
{
    if (!function_exists('shell_exec') || empty($entries)) {
        return false;
    }

    $stagingDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'top20_stage_' . uniqid();
    if (!mkdir($stagingDir, 0777, true) && !is_dir($stagingDir)) {
        return false;
    }

    foreach ($entries as $entry) {
        $destinationPath = $stagingDir . DIRECTORY_SEPARATOR . $entry['name'];
        if (!@copy($entry['source'], $destinationPath)) {
            removeDirectoryRecursive($stagingDir);
            return false;
        }
    }

    $sourcePattern = $stagingDir . DIRECTORY_SEPARATOR . '*';
    $sourcePatternEscaped = str_replace("'", "''", $sourcePattern);
    $zipPathEscaped = str_replace("'", "''", $zipFilePath);

    $command = "powershell -NoProfile -NonInteractive -Command \"Compress-Archive -Path '$sourcePatternEscaped' -DestinationPath '$zipPathEscaped' -Force\" 2>&1";

    shell_exec($command);

    removeDirectoryRecursive($stagingDir);

    return is_file($zipFilePath) && filesize($zipFilePath) > 0;
}

$topPhotos = ff_get_top_posts(20);

if (empty($topPhotos)) {
    header('Location: index.php');
    exit;
}

$entries = [];
$usedNames = [];

foreach ($topPhotos as $index => $photo) {
    $imageUrl = (string) ($photo['image_url'] ?? '');
    if ($imageUrl === '') {
        continue;
    }

    $pathFromUrl = parse_url($imageUrl, PHP_URL_PATH);
    if (!is_string($pathFromUrl) || $pathFromUrl === '') {
        continue;
    }

    $relativePath = ltrim(str_replace('\\', '/', $pathFromUrl), '/');
    $absolutePath = __DIR__ . '/' . $relativePath;

    if (!is_file($absolutePath) || !is_readable($absolutePath)) {
        continue;
    }

    $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
    if ($extension === '') {
        $extension = 'jpg';
    }

    $safeUser = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $photo['username']);
    $baseName = sprintf('%02d_%s_post_%d.%s', $index + 1, $safeUser ?: 'user', (int) $photo['id'], $extension);

    $fileName = $baseName;
    $suffix = 1;
    while (isset($usedNames[$fileName])) {
        $fileName = sprintf('%02d_%s_post_%d_%d.%s', $index + 1, $safeUser ?: 'user', (int) $photo['id'], $suffix, $extension);
        $suffix++;
    }

    $usedNames[$fileName] = true;
    $entries[] = ['source' => $absolutePath, 'name' => $fileName];
}

if (empty($entries)) {
    header('Location: index.php');
    exit;
}

$tempZipPath = tempnam(sys_get_temp_dir(), 'top20_');
$zipFilePath = $tempZipPath . '.zip';
@rename($tempZipPath, $zipFilePath);

$zipCreated = false;
if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        foreach ($entries as $entry) {
            $zip->addFile($entry['source'], $entry['name']);
        }
        $zip->close();
        $zipCreated = is_file($zipFilePath) && filesize($zipFilePath) > 0;
    }
}

if (!$zipCreated) {
    $zipCreated = createZipWithPowerShell($entries, $zipFilePath);
}

if (!$zipCreated) {
    @unlink($zipFilePath);
    http_response_code(500);
    echo 'ZIP-bestand maken is mislukt op deze server.';
    exit;
}

$downloadName = 'top-20-fotos-' . date('Y-m-d-H-i') . '.zip';

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($zipFilePath));
header('Pragma: public');
header('Cache-Control: must-revalidate');

readfile($zipFilePath);
@unlink($zipFilePath);
exit;
