<?php

namespace App\Service;

use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class CacheService
 *
 * A simple service to handle caching with Symfony Cache.
 *
 * This service provides methods to:
 * - Retrieve or store cached data
 * - Invalidate specific cache keys
 * - Clear the entire cache
 *
 * Usage:
 * ```php
 * $data = $cacheService->get('some_key', function () {
 *     // Generate or fetch data here
 *     return $result;
 * }, 3600);
 * ```
 *
 * @package App\Service
 */
class CacheService
{
    private CacheInterface $cache;

    /**
     * CacheService constructor.
     *
     * @param CacheInterface $cache The Symfony CacheInterface instance (e.g., cache.app)
     *
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Retrieves data from cache or generates it using a callback if not present.
     *
     * This method automatically stores the result in cache with the given TTL.
     *
     * @param string $key A unique cache key
     * @param callable $callback Function to generate the data if not cached
     * @param int $ttl Time-to-live in seconds. Defaults to 1 hour (3600s)
     *
     * @return mixed Cached data or result from the callback
     * @throws InvalidArgumentException
     */
    public function get(string $key, callable $callback, int $ttl = 3600): mixed
    {
        return $this->cache->get($key, function (ItemInterface $item) use ($callback, $ttl) {
            $item->expiresAfter($ttl);
            return $callback();
        });
    }

    /**
     * Deletes a specific cache key.
     *
     * @param string $key The cache key to remove
     * @throws InvalidArgumentException
     */
    public function invalidate(string $key): void
    {
        $this->cache->delete($key);
    }

    /**
     * Clears all cache entries.
     */
    public function clear(): void
    {
        $this->cache->clear();
    }
}
