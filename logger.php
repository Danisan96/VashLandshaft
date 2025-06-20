<?php
// logger.php

class Logger {
    private static $logDir = __DIR__ . '/logs';
    private static $maxLogSize = 10485760; // 10MB
    
    public static function init() {
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0777, true);
        }
    }
    
    public static function log($message, $type = 'app') {
        self::init();
        $date = date('Y-m-d');
        $datetime = date('Y-m-d H:i:s');
        
        $logFile = self::$logDir . "/{$type}_{$date}.log";
        
        // Ротация логов
        if (file_exists($logFile)) { 
            $fileSize = filesize($logFile);
            if ($fileSize > self::$maxLogSize) {
                $archiveFile = self::$logDir . "/{$type}_{$date}_" . time() . ".log";
                rename($logFile, $archiveFile);
            }
        }
        
        $message = "[$datetime] $message" . PHP_EOL;
        file_put_contents($logFile, $message, FILE_APPEND);
    }
    
    public static function logError($message) {
        self::log("ERROR: $message", 'errors');
    }
    
    public static function logDb($message) {
        self::log("DB: $message", 'database');
    }
    
    public static function logRequest() {
        $request = [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? ''
        ];
        
        self::log("Request: " . json_encode($request), 'requests');
    }
    
    public static function getClientIP() {
        $keys = [
            'HTTP_CLIENT_IP', 
            'HTTP_X_FORWARDED_FOR', 
            'HTTP_X_FORWARDED', 
            'HTTP_X_CLUSTER_CLIENT_IP', 
            'HTTP_FORWARDED_FOR', 
            'HTTP_FORWARDED', 
            'REMOTE_ADDR'
        ];
        
        foreach ($keys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        
        return 'unknown';
    }
}
?>