<?php declare(strict_types=1);

namespace Tale\Cache\Pool;

use Psr\Cache\CacheItemInterface;
use Tale\Cache\Item;
use Tale\Cache\ItemInterface;

final class NullPool extends AbstractPool
{
    public function getItem($key): ItemInterface
    {
        return Item::createMiss($key);
    }

    public function clear(): bool
    {
        return true;
    }

    public function deleteItem($key): bool
    {
        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        $this->filterItem($item);
        return true;
    }
}
