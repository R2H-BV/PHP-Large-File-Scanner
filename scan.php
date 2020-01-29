<?php
declare(strict_types = 1);
/**
 * Scan a directory and find large files.
 * @author      Michael Snoeren <michael@r2h.nl>
 * @license     GNU/GPLv3
 * @copyright   R2H Marketing & Internet Solutions
 */

set_time_limit(300);
error_reporting(-1);
ini_set('display_errors', 'On');

// Prepare the input.
if (php_sapi_name() === 'cli') {
    for ($c = 1; $c < $argc; $c++) {
        $param = explode('=', $argv[$c], 2);
        $_GET[str_replace('--', '', $param[0])] = $param[1];
    }
}

// Check if a directory is given. If not, use the current one.
$dir = isset($_GET['dir']) && !empty($_GET['dir'])
    ? $_GET['dir']
    : getcwd();

// Setup the minimal size a file should have to be reported.
$size = isset($_GET['size']) && !empty($_GET['size'])
    ? (int) $_GET['size'] * 1024 * 1024
    : 1024 * 1024;

// Check if the directory exists.
if (!is_dir($dir)) {
    // Try a relative directory.
    $dir = getcwd() . $dir;
}
if (!is_dir($dir)) {
    echo json_encode(['error' => true, 'message' => sprintf('Directory "%s" not a directory.', $dir)]);
    exit(1);
}

/**
 * Function to make the given path a relative one.
 * @param   string $path The full path.
 * @access  public
 * @return  string
 */
function makeRelativePath(string $path): string
{
    return DIRECTORY_SEPARATOR .
        ltrim(
            str_replace(
                getcwd(),
                '',
                str_replace(
                    ['\\', '/'],
                    DIRECTORY_SEPARATOR,
                    $path
                )
            ),
            DIRECTORY_SEPARATOR
        );
}

// Scan for files.
$largeFiles = [];
$dirIterator = new RecursiveDirectoryIterator(
    $dir,
    RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS
);
$iterator = new RecursiveIteratorIterator(
    $dirIterator,
    RecursiveIteratorIterator::SELF_FIRST,
    RecursiveIteratorIterator::CATCH_GET_CHILD
);

foreach ($iterator as $file) {
    try {
        $fileSize = $file->getSize();
        if ($fileSize >= $size) {
            $largeFiles[makeRelativePath($file->getRealPath())] = number_format($fileSize / 1024 / 1024, 2) . 'mb';
        }
    } catch (\Exception $e) {
    }
}

echo json_encode(['error' => false, 'largeFiles' => $largeFiles]);
