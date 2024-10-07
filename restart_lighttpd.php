<?php

// Define constants
define('LIGHTTPD_SERVICE', 'lighttpd'); // Name of the Lighttpd service
define('BACKUP_DIR', __DIR__ . '/baks');
define('LIGHTTPD_CONF_DIR', '/etc/lighttpd');
define('LIGHTTPD_CONF_FILE', LIGHTTPD_CONF_DIR . '/lighttpd.conf');
define('VHOSTS_CONF_DIR', LIGHTTPD_CONF_DIR . '/vhosts.d');

// Function to execute a shell command and return the output
function executeCommand($command) {
    $output = [];
    $returnVar = 0;
    exec($command, $output, $returnVar);
    return [$output, $returnVar];
}

// Function to check Lighttpd configuration
function checkLighttpdConfig() {
    // Check main Lighttpd config
    list($output, $returnVar) = executeCommand("lighttpd -t -f " . escapeshellarg(LIGHTTPD_CONF_FILE));
    
    if ($returnVar !== 0 || !in_array("Syntax OK", $output)) {
        echo "Error in Lighttpd main configuration file.\n";
        echo implode("\n", $output);
        return false;
    }
    
    // Check virtual hosts configurations
    $vhostFiles = glob(VHOSTS_CONF_DIR . '/*.conf');
    foreach ($vhostFiles as $file) {
        list($output, $returnVar) = executeCommand("lighttpd -t -f " . escapeshellarg($file));
        if ($returnVar !== 0 || !in_array("Syntax OK", $output)) {
            echo "Error in Lighttpd virtual host configuration file: $file\n";
            echo implode("\n", $output);
            return false;
        }
    }
    
    return true;
}

// Function to create a backup of the current Lighttpd config directory
function createBackup() {
    $timestamp = date('YmdHis');
    $backupPath = BACKUP_DIR . "/lighttpd_backup_$timestamp/";
    
    if (!file_exists(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0755, true);
    }
    
    exec("cp -r " . escapeshellarg(LIGHTTPD_CONF_DIR) . " " . escapeshellarg($backupPath), $output, $returnVar);
    
    if ($returnVar !== 0) {
        echo "Failed to create backup. Aborting restart.\n";
        return false;
    }
    return $backupPath;
}

// Function to clean up old backups, keeping only the latest 7
function cleanupOldBackups() {
    $backupFiles = glob(BACKUP_DIR . '/lighttpd_backup_*');
    usort($backupFiles, 'filemtime');
    
    while (count($backupFiles) > 7) {
        $oldestBackup = array_shift($backupFiles);
        exec("rm -rf " . escapeshellarg($oldestBackup));
    }
}

// Function to revert Lighttpd to the last successful backup
function revertToLastBackup() {
    $backupFiles = glob(BACKUP_DIR . '/lighttpd_backup_*');
    
    if (empty($backupFiles)) {
        echo "No backups available to revert to.\n";
        return false;
    }
    
    usort($backupFiles, 'filemtime');
    $lastBackup = array_pop($backupFiles);
    
    exec("rm -rf " . escapeshellarg(LIGHTTPD_CONF_DIR));
    exec("cp -r " . escapeshellarg($lastBackup) . " " . escapeshellarg(LIGHTTPD_CONF_DIR));
}

// Function to restart Lighttpd
function restartLighttpd() {
    list($output, $returnVar) = executeCommand("systemctl restart " . LIGHTTPD_SERVICE);
    return $returnVar;
}

// Checking if the script is run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line.");
}

// Main script execution
if (!checkLighttpdConfig()) {
    // Abort if the configuration is not valid
    exit(1);
}

$backupPath = createBackup();

if (!$backupPath) {
    exit(1); // Do not proceed with restart if backup creation failed
}

cleanupOldBackups();

echo "Restarting Lighttpd...\n";
$returnVar = restartLighttpd();

if ($returnVar === 0) {
    echo "Lighttpd restarted successfully.\n";
} else {
    echo "Error restarting Lighttpd. Reverting to last backup...\n";
    revertToLastBackup();
    echo "Reverted to the last backup.\n";
}

// Checking if Lighttpd is alive after restart
sleep(2);
$listOutput = [];
exec("systemctl is-active " . LIGHTTPD_SERVICE, $listOutput, $returnVar);

if ($returnVar !== 0) {
    echo "Lighttpd has died shortly after restart. Reverting to last backup...\n";
    revertToLastBackup();
    echo "Reverted to the last backup.\n";
}

?>
