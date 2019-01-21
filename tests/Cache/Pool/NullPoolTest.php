<?php declare(strict_types=1);

namespace Tale\Test\Cache;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Tale\Cache\InvalidArgumentException;
use Tale\Cache\Item;
use Tale\Cache\Pool\NullPool;

/**
 * @coversDefaultClass \Tale\Cache\Pool\NullPool
 */
final class NullPoolTest extends TestCase
{
    /**
     * @covers ::getItem
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testGetItem(): void
    {
        $pool = new NullPool();
        self::assertEquals(Item::createMiss('some.key'), $pool->getItem('some.key'));
    }

    /**
     * @covers ::getItems
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testGetItems(): void
    {
        $pool = new NullPool();
        $items = $pool->getItems(['first.key', 'second.key']);
        self::assertEquals([Item::createMiss('first.key'), Item::createMiss('second.key')], iterator_to_array($items));
    }

    /**
     * @covers ::hasItem
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testHasItem(): void
    {
        $pool = new NullPool();
        self::assertFalse($pool->hasItem('some.key'));
    }

    /**
     * @covers ::clear
     */
    public function testClear(): void
    {
        $pool = new NullPool();
        self::assertTrue($pool->clear());
    }

    /**
     * @covers ::deleteItem
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testDeleteItem(): void
    {
        $pool = new NullPool();
        self::assertTrue($pool->deleteItem('some.key'));
    }

    /**
     * @covers ::deleteItems
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testDeleteItems(): void
    {
        $pool = new NullPool();
        self::assertTrue($pool->deleteItems(['first.key', 'second.key']));
    }

    /**
     * @covers ::save
     * @covers ::getItem
     * @covers ::filterItem
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testSave(): void
    {
        $pool = new NullPool();
        self::assertTrue($pool->save($pool->getItem('some.key')));
    }

    /**
     * @covers ::saveDeferred
     * @covers ::getItem
     * @covers ::filterItem
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testSaveDeferred(): void
    {
        $pool = new NullPool();
        $item = $pool->getItem('some.key');
        self::assertTrue($pool->saveDeferred($item));
        self::assertFalse($pool->saveDeferred($item));
    }

    /**
     * @covers ::saveDeferred
     * @covers ::getItem
     * @covers ::filterItem
     * @expectedException InvalidArgumentException
     */
    public function testSaveDeferredThrowsExceptionOnInvalidItem(): void
    {
        $pool = new NullPool();
        self::assertTrue($pool->saveDeferred(new class implements CacheItemInterface
        {
            public function getKey()
            {
            }
            public function get()
            {
            }
            public function isHit()
            {
            }
            public function set($value)
            {
            }
            public function expiresAt($expiration)
            {
            }
            public function expiresAfter($time)
            {
            }
        }));
    }

    /**
     * @covers ::commit
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testCommit(): void
    {
        $pool = new NullPool();
        self::assertTrue($pool->commit());
        self::assertTrue($pool->saveDeferred($pool->getItem('some.key')));
        self::assertTrue($pool->commit());
    }
}
