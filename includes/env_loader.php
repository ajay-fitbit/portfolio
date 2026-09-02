<?php
/**
 * Simple .env file loader without external dependencies
 */
class EnvLoader {
    private static $env = [];
    
    /**
     * Load environment variables from .env file
     */
    public static function load($filePath = '.env') {
        if (!file_exists($filePath)) {
            throw new Exception(".env file not found at: " . $filePath);
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                $value = trim($value, '"\'');
                
                // Store in static array and set as environment variable
                self::$env[$key] = $value;
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
    
    /**
     * Get environment variable
     */
    public static function get($key, $default = null) {
        return self::$env[$key] ?? getenv($key) ?? $default;
    }
    
    /**
     * Check if environment variable exists
     */
    public static function has($key) {
        return isset(self::$env[$key]) || getenv($key) !== false;
    }
}
?>
