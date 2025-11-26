<?php
$singbox_bin = '/usr/bin/sing-box';
$log = '/etc/neko/tmp/log.txt';

function _getSingboxPID() {
    global $singbox_bin;
    $command = "pgrep -f " . escapeshellarg($singbox_bin);
    exec($command, $output);
    return isset($output[0]) ? $output[0] : null;
}

function _writeToLog($message) {
    global $log;
    $dateTime = new DateTime();  
    $time = $dateTime->format('H:i:s'); 
    $logMessage = "[ $time ] $message\n";
    if (file_put_contents($log, $logMessage, FILE_APPEND) === false) {
        error_log("Failed to write to log file: $log");
    }
}

$pid = null;
$maxTry = 10;
$delay = 2;
$try = 0;

while ($try < $maxTry) {
    $pid = _getSingboxPID();
    if ($pid !== null) {
        _writeToLog("Sing-box đang chạy trên PID: $pid");
        break;
    }
    $try++;
    if ($try < $maxTry) {
        sleep($delay);
    }
}

if ($pid !== null) {
    shell_exec("/etc/init.d/firewall restart");
    _writeToLog("Đã khởi động lại Firewall.");
} else {
    _writeToLog("Không lấy được PID sing-box sau $maxTry lần, không restart Firewall.");
}