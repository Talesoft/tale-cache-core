<?php declare(strict_types=1);

namespace Tale;

use Psr\Cache\CacheItemPoolInterface;
use Tale\Cache\Item;
use Tale\Cache\ItemInterface;
use Tale\Cache\Pool\NullPool;
use Tale\Cache\Pool\RuntimePool;
use Tale\Cache\PoolCache;

function cache_pool_cache(CacheItemPoolInterface $pool): PoolCache
{
    return new PoolCache($pool);
}

function cache_pool_null(): CacheItemPoolInterface
{
    return new NullPool();
}

function cache_pool_runtime(): CacheItemPoolInterface
{
    return new RuntimePool();
}

function cache_item($key, $value = null, ?\DateTimeInterface $expirationTime = null, bool $hit = true): ItemInterface
{
    return new Item($key, $value, $expirationTime, $hit);
}

function cache_item_miss($key): ItemInterface
{
    return Item::createMiss($key);
}

function cache_item_hit($key, $value = null, ?\DateTimeInterface $expirationTime = null): ItemInterface
{
    return Item::createHit($key, $value, $expirationTime);
}
