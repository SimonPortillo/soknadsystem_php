<?php

namespace app\utils;
/**
 * Cache Utility
 * 
 * This utility provides simple file-based caching functionality,
 * allowing storage and retrieval of cached data with expiration.
 */
class CacheUtil {

    protected string $cacheDir;

    // creates a cache directory if it doesn't exist
    public function __construct(string $cacheDir = __DIR__ . '/../../cache/application')
    {
        $this->cacheDir = $cacheDir;
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0775, true);
        }
    }

    /**
     * Get a cached value by key.
     *
     * @param string $key The cache key.
     * @return mixed The cached value, or null if not found or expired.
     */
    public function get(string $key): mixed
    {
        $file = $this->cacheDir . '/' . md5($key) . '.cache';

        if (!file_exists($file)) {
            return null;
        }

        $data = unserialize(file_get_contents($file)); 

        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }
        return $data['value'];
    }

    /**
     * Set a cached value by key with a time-to-live (TTL).
     *
     * @param string $key The cache key.
     * @param mixed $value The value to cache.
     * @param int $ttl Time-to-live in seconds.
     * @return void
     */
    public function set(string $key, mixed $value, int $ttl): void {

        // ensure unique filename for each key by hashing
        $file = $this->cacheDir . '/' . md5($key) . '.cache';

        $data = [
            'expires' => time() + $ttl,
            'value' => $value
        ];

        file_put_contents($file, serialize($data));
    }
}