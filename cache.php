<?php
// ============================================================
// cache.php — Simple file-based cache
// Include this after db.php on pages that need caching
// ============================================================

class Cache {

    private static $dir = __DIR__ . '/cache/';
    private static $enabled = true;

    // Auto-create the cache directory if it doesn't exist
    private static function ensureDir(): void {
        if(!is_dir(self::$dir)){
            mkdir(self::$dir, 0755, true);
        }
    }

    // Store a value in cache
    // $key     = unique name e.g. 'timeslots', 'schedule_ICT11A'
    // $data    = anything (array, string, int)
    // $seconds = how long to keep it (default 60 seconds)
    public static function set(string $key, $data, int $seconds = 60): void {
        if(!self::$enabled) return;
        self::ensureDir();
        $file    = self::$dir . self::filename($key);
        $payload = serialize(['expires' => time() + $seconds, 'data' => $data]);
        file_put_contents($file, $payload, LOCK_EX);
    }

    // Get a value from cache — returns null if missing or expired
    public static function get(string $key) {
        if(!self::$enabled) return null;
        $file = self::$dir . self::filename($key);
        if(!file_exists($file)) return null;
        $payload = unserialize(file_get_contents($file));
        if(!$payload || time() > $payload['expires']){
            @unlink($file);
            return null;
        }
        return $payload['data'];
    }

    // Delete a specific cache entry (call this when data changes)
    public static function delete(string $key): void {
        $file = self::$dir . self::filename($key);
        if(file_exists($file)) @unlink($file);
    }

    // Delete all cache entries that start with a prefix
    // e.g. Cache::flush('schedule_') clears all section schedules
    public static function flush(string $prefix = ''): void {
        foreach(glob(self::$dir . '*.cache') as $file){
            if($prefix === '' || strpos(basename($file), md5($prefix)) !== false){
                @unlink($file);
            }
        }
        // Simpler: just delete all if no prefix
        if($prefix === ''){
            array_map('unlink', glob(self::$dir . '*.cache'));
        }
    }

    // Delete all expired cache files
    // Call this occasionally to clean up stale files that were never read again
    // Returns the number of files deleted
    public static function gc(): int {
        $deleted = 0;
        foreach(glob(self::$dir . '*.cache') as $file){
            $payload = @unserialize(@file_get_contents($file));
            if(!$payload || time() > $payload['expires']){
                @unlink($file);
                $deleted++;
            }
        }
        return $deleted;
    }

    // Returns how many cache files exist and their total size in KB
    public static function stats(): array {
        $files = glob(self::$dir . '*.cache') ?: [];
        $size  = array_sum(array_map('filesize', $files));
        return [
            'count' => count($files),
            'size_kb' => round($size / 1024, 1),
        ];
    }

    private static function filename(string $key): string {
        return md5($key) . '.cache';
    }
}