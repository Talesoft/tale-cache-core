<?php declare(strict_types=1);

namespace Tale\Test\Cache;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Tale\Cache\InvalidArgumentException;
use Tale\Cache\Item;
use Tale\Cache\Pool\NullPool;
use Tale\Cache\Pool\RuntimePool;

/**
 * @coversDefaultClass \Tale\Cache\Pool\RuntimePool
 */
final class RuntimePoolTest extends TestCase
{
    /**
     * @covers ::getItem
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testGetItem(): void
    {
        $pool = new RuntimePool();
        $item = $pool->getItem('some.key');
        self::assertEquals(Item::createMiss('some.key'), $item);
        $item->set('some value');
        $pool->save($item);
        $item = $pool->getItem('some.key');
        self::assertEquals(Item::createHit('some.key', 'some value'), $pool->getItem('some.key'));
    }

    /**
     * @covers ::clear
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testClear(): void
    {
        $pool = new RuntimePool();
        $item = $pool->getItem('some.key');
        $item->set('some value');
        $pool->save($item);
        self::assertTrue($pool->hasItem('some.key'));
        self::assertTrue($pool->clear());
        self::assertFalse($pool->hasItem('some.key'));
    }

    /**
     * @covers ::deleteItem
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testDeleteItem(): void
    {
        $pool = new RuntimePool();
        $item = $pool->getItem('some.key');
        $item->set('some value');
        $pool->save($item);
        self::assertTrue($pool->hasItem('some.key'));
        self::assertTrue($pool->deleteItem('some.key'));
        self::assertFalse($pool->deleteItem('some.other.key'));
        self::assertFalse($pool->hasItem('some.key'));
    }

    /**
     * @covers ::save()
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testSave(): void
    {
        $pool = new RuntimePool();
        self::assertFalse($pool->hasItem('some.key'));
        $item = $pool->getItem('some.key');
        $item->set('some value');
        $pool->save($item);
        self::assertTrue($pool->hasItem('some.key'));
        self::assertEquals($item, $pool->getItem('some.key'));
    }
}
