<?php declare(strict_types=1);

namespace Tale\Cache\Pool;

use Psr\Cache\CacheItemInterface;
use Tale\Cache\Item;
use Tale\Cache\ItemInterface;

final class RuntimePool extends AbstractPool
{
    /** @var ItemInterface[] */
    private $items = [];

    public function getItem($key): ItemInterface
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }
        return Item::createMiss($key);
    }

    public function clear(): bool
    {
        $this->items = [];
        return true;
    }

    public function deleteItem($key): bool
    {
        $exists = array_key_exists($key, $this->items);
        if ($exists) {
            unset($this->items[$key]);
            return true;
        }
        return false;
    }

    public function save(CacheItemInterface $item): bool
    {
        $this->filterItem($item);
        $this->items[$item->getKey()] = $item;
        return true;
    }
}
