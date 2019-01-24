<?php declare(strict_types=1);

namespace Tale\Cache;

use Psr\Cache\CacheItemInterface;

abstract class AbstractPool implements PoolInterface
{
    /** @var ItemInterface[]  */
    private $deferredItems = [];

    public function getItems(array $keys = [])
    {
        foreach ($keys as $key) {
            yield $this->getItem($key);
        }
    }

    public function hasItem($key): bool
    {
        return $this->getItem($key)->isHit();
    }

    public function deleteItems(array $keys): bool
    {
        return array_reduce($keys, function ($success, $key) {
            return $success && $this->deleteItem($key);
        }, true);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->filterItem($item);
        if (\in_array($item, $this->deferredItems, true)) {
            return false;
        }
        $this->deferredItems[] = $item;
        return true;
    }

    public function commit(): bool
    {
        $success = array_reduce($this->deferredItems, function ($success, $item) {
            return $success && $this->save($item);
        }, true);
        $this->deferredItems = [];
        return $success;
    }

    protected function filterItem(CacheItemInterface $item): ItemInterface
    {
        if (!($item instanceof ItemInterface)) {
            throw new \InvalidArgumentException(sprintf(
                'Passed cache item needs to be instance of %s, you passed a PSR Cache Item. PSR Cache items '.
                'are fixed to their own pool, Tale Cache items are not.',
                ItemInterface::class
            ));
        }
        return $item;
    }
}
