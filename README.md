
[![Packagist](https://img.shields.io/packagist/v/talesoft/tale-cache-core.svg?style=for-the-badge)](https://packagist.org/packages/talesoft/tale-cache-core)
[![License](https://img.shields.io/github/license/Talesoft/tale-cache-core.svg?style=for-the-badge)](https://github.com/Talesoft/tale-cache-core/blob/master/LICENSE.md)
[![CI](https://img.shields.io/travis/Talesoft/tale-cache-core.svg?style=for-the-badge)](https://travis-ci.org/Talesoft/tale-cache-core)
[![Coverage](https://img.shields.io/codeclimate/coverage/Talesoft/tale-cache-core.svg?style=for-the-badge)](https://codeclimate.com/github/Talesoft/tale-cache-core)

Tale Cache Core
===============

What is Tale Cache Core?
------------------------

Tale Cache Core is a basic extension of the PSR-6 and PSR-16 caching
standards combined into a single library.

It acts as a base for libraries to be compatible to PSR-6 and PSR-16
caches without relying on heavy dependencies and also acts as a base
for the Tale Cache library.

Furthermore, it tries to fix a single problem with the standard
PSR cache specifications.

Installation
------------

```bash
composer require talesoft/tale-cache-core
```

Usage
-----

### PSR-6 to PSR-16 adapter

Easily use PSR-6 cache pools in your applications or libraries
preferring PSR-16 by using `Tale\Cache\PoolCache`

```php
use Tale\Cache\PoolCache;

$pool = new RedisCachePool(); //Create some PSR-6 CacheItemPool

$cache = new PoolCache($pool);
//$cache is now a PSR-16 cache

$data = $cache->get('some.key');
if ($data === null) {

    $data = ...; //Generate $data somehow
    $cache->set('some.key', $data);
}

//$data is now a cached value
```

### Null Cache and Runtime Cache for library authors

Sometimes library authors want to make their libraries caching
compatible, but don't want to implement a whole caching
implementation with it. While interfaces work really well for that,
they can only be represented as optional dependencies, when sometimes
what you really want is a required dependency on a cache or test
against a mocked implementation. A real implementation also avoids
needing to make your properties nullable or do null-checks
on optional dependencies all the time, so you avoid a lot of
defensive programming with null checks

Tale Cache Core provides two lightweight, simple implementations
of a PSR-6 cache pool that can be used as default values and work
like normal caches, just that they don't really do anything

Imagine a service that looks like this:
```php
use Psr\Cache\CacheItemPoolInterface;

final class MyService
{
    /** @var CacheItemPoolInterface */
    private $cachePool;
    
    public function __construct(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }
    
    public function getCachePool(): CacheItemPoolInterface
    {
        return $this->cachePool;
    }
    
    public function doStuff(): void
    {
        $metadataItem = $this->cachePool->getItem('metadata');
        if (!$metadataItem->isHit()) {
            $metadataItem
                ->expiresAfter(new \DateInterval('P2D'))
                ->set($this->generateHeavyMetadata());
                
            $this->cachePool->save($metadataItem);
        }
        
        $metadata = $metadataItem->get();
        //Do something with $metadata
    }
}
```

If you might want to test against this or instantiate it somewhere,
you can either use the `NullPool`

```php
use Tale\Cache\Pool\NullPool;

$myService = new MyService(new NullPool());

$myService->doStuff();
```

This will basically just work like a completely disabled cache.

If you want to have some runtime caching so that cache items
are not generated over and over again, you can also use the
`RuntimePool`, which caches values as long as the process is there

```php
use Tale\Cache\Pool\RuntimePool;

$myService = new MyService(new RuntimePool());

$myService->doStuff();
$myService->doStuff(); //This will be faster, as values are cached during runtime
```

If you still want optional dependencies, but you want to avoid
defensive null-checks all over your library code, you can
just default the value with a null-coalesce operator

```php
public function __construct(CacheItemPoolInterface $cachePool = null)
{
    $this->cachePool = $cachePool ?? new NullPool();
}
```

### Easy custom Cache Pool implementation

Tale Cache Core takes some of the work you require when wanting
to write PSR-6 compatible cache pools. As an example we will
implement an own file cache with Tale Cache Core:

```php
use Tale\Cache\Pool\AbstractPool;
use Tale\Cache\Item;

final class FilePool extends AbstractPool
{
    /** @var string */
    private $directory;
    
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }
    
    public function getItem($key): ItemInterface
    {
        $path = $this->getPathFromKey($key);
        if (file_exists($path)) {
        
            //Unserialize data from file
            [$ttl, $value] = unserialize(file_get_contents($path));
            
            //Check TTL
            if (time() < filemtime() + $ttl) {
            
                $expirationTime = new \DateTimeImmutable();
                $expirationTime->setTimestamp($expirationTime->getTimestamp() + $ttl);
                
                //Return a hit item
                return Item::createHit($key, $value, $expirationTime);
            }
        }
        //Create a miss
        return Item::createMiss($key);
    }

    public function clear(): bool
    {
        $files = glob("{$this->directory}/*.cache");
        $success = true;
        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }
        return $success;
    }

    public function deleteItem($key): bool
    {
        $path = $this->getPathFromKey($key);
        return unlink($path);
    }

    public function save(CacheItemInterface $item): bool
    {
        //Make sure that it's an (interopable) Tale\Cache\ItemInterface instance
        //including items from this instance (which makes it downwards PSR-6 compatible)
        $this->filterItem($item);
        
        $path = $this->getPathFromKey($item->getKey());
        
        //This ->getExpireTime() call here is what enables interopability
        $ttl = time() - $item->getExpireTime()->getTimestamp();
        
        return file_put_contents($path, serialize([$ttl, $item->get()])) !== false;
    }
    
    private function getPathFromKey($key): string
    {
        $hash = md5($key);
        return "{$this->directory}/{$hash}.cache";
    }
}
```

now you have a fully valid PSR-6 cache working with files

```php
$pool = new FilePool('/my/cache');

$item = $pool->getItem('my.data');
if (!$item->isHit()) {
    //Generate $data somehow
    $data = ...;
    
    $item
        ->expiresAfter(new \DateInterval('P2D'))
        ->set($data);
    $pool->saveDeferred($item);
}

$cachedData = $item->get();

//At end of execution
$pool->commit();
```

### Interoperable Cache Items

Tale Cache Core extends the normal PSR-6/16 interfaces and adds
a single method that provides the ability to have a single
CacheItem implementation for all possible CachePools

New interfaces to code against (All are PSR-6/16 compatible):

    Psr\SimpleCache\CacheInterface   => Tale\CacheInterface
        | No new methods
        
    Psr\Cache\CacheItemPoolInterface => Tale\Cache\PoolInterface
        | getItem($key): Tale\Cache\ItemInterface (type narrowing)
        
    Psr\Cache\CacheItemInterface     => Tale\Cache\ItemInterface
        | getExpirationTime(): ?DateTimeInterface

As you can see, the `Tale\Cache\ItemInterface` provides a single **new**
method to the interfaces, which allows us to **retrieve** the specified
expiration time of a cache item. This allows the `Tale\Cache\ItemInterface` 
to be absolutely interoperable between different Item Pool implementations.

**You can move items from one pool to another**:

```php
$poolA = new SomeCachePool();
$poolB = new SomeOtherCachePool();

$item = $poolA->getItem('some.item');
if ($item->isHit()) {
    $poolA->deleteItem($item);
    $poolB->save($item); //Cache Item has been moved to poolB
}
```

This is possible because the Cache Item can finally be a normal DTO
and doesn't need its pool to set its expiration time, the cached
value is stored inside the item along with its key and TTL, so it
can always be moved or copied to other item pools.

