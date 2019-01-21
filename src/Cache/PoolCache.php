<?php declare(strict_types=1);

namespace Tale\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Tale\CacheInterface;

final class PoolCache implements CacheInterface
{
    /** @var CacheItemPoolInterface */
    private $pool;

    public function __construct(CacheItemPoolInterface $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @param string $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function has($key): bool
    {
        return $this->pool->hasItem($key);
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function get($key, $default = null)
    {
        $item = $this->pool->getItem($key);
        if ($item->isHit()) {
            return $item->get();
        }
        return $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param \DateTimeInterface|int|null $ttl
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function set($key, $value, $ttl = null): bool
    {
        $item = $this->pool->getItem($key);
        $item
            ->expiresAfter($ttl)
            ->set($value);
        return $this->pool->save($item);
    }

    /**
     * @param string $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function delete($key): bool
    {
        return $this->pool->deleteItem($key);
    }

    public function clear(): bool
    {
        return $this->pool->clear();
    }

    /**
     * @param iterable $keys
     * @param null $default
     * @return \Generator|iterable
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMultiple($keys, $default = null)
    {
        $this->validateIterable($keys);
        /** @var ItemInterface[] $items */
        $items = $this->pool->getItems($this->reduceIterator($keys));
        foreach ($items as $item) {
            yield $item->isHit() ? $item->get() : $default;
        }
    }

    /**
     * @param iterable $values
     * @param null $ttl
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function setMultiple($values, $ttl = null): bool
    {
        $this->validateIterable($values);
        foreach ($values as $key => $value) {
            $item = $this->pool->getItem($key);
            $item->expiresAfter($ttl)
                ->set($value);
            $this->pool->saveDeferred($item);
        }
        $this->pool->commit();
        return true;
    }

    /**
     * @param iterable $keys
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function deleteMultiple($keys): bool
    {
        return $this->pool->deleteItems($this->reduceIterator($keys));
    }

    private function validateIterable($keys): void
    {
        if (!is_iterable($keys)) {
            throw new InvalidArgumentException('Argument needs to be iterable');
        }
    }

    private function reduceIterator(iterable $keys): array
    {
        return $keys instanceof \Traversable ? iterator_to_array($keys) : (array)$keys;
    }
}
