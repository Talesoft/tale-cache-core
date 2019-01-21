<?php declare(strict_types=1);

namespace Tale\Test\Cache;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tale\Cache\InvalidArgumentException;
use Tale\Cache\Item;

/**
 * @coversDefaultClass \Tale\Cache\Item
 */
final class ItemTest extends TestCase
{

    /**
     * @covers ::__construct
     * @covers ::filterKey
     * @dataProvider provideInvalidKeys
     * @expectedException InvalidArgumentException
     * @param $key
     */
    public function testConstructThrowsExceptionOnInvalidKey($key): void
    {
        $item = new Item($key);
    }

    public function provideInvalidKeys(): array
    {
        return [
            [12],
            [new class {
            }],
            [[23]],
            [stream_context_create()],
            [354.53],
            ['some key'],
            ['some-key'],
            ['some%key'],
            [str_repeat('abcdefgh', 8).'a'], //65 chars,
            ['']
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getKey
     * @covers ::filterKey
     */
    public function testGetKey(): void
    {
        $item = new Item('some.key');
        self::assertSame('some.key', $item->getKey());
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::filterKey
     */
    public function testGet(): void
    {
        $item = new Item('some.key');
        self::assertNull($item->get());

        $item = new Item('some.key', 'some value');
        self::assertSame('some value', $item->get());
    }

    /**
     * @covers ::__construct
     * @covers ::isHit
     * @covers ::filterKey
     */
    public function testIsHit(): void
    {
        $item = new Item('some.key');
        self::assertTrue($item->isHit());

        $item = new Item('some.key', null, null, false);
        self::assertFalse($item->isHit());
    }

    /**
     * @covers ::__construct
     * @covers ::set
     * @covers ::filterKey
     */
    public function testSet(): void
    {
        $item = Item::createMiss('some.key');
        self::assertFalse($item->isHit());
        self::assertSame($item, $item->set('some value'));
        self::assertTrue($item->isHit());
    }

    /**
     * @covers ::__construct
     * @covers ::getExpirationTime
     * @covers ::filterKey
     * @throws \Exception
     */
    public function testGetExpirationTime(): void
    {
        $time = new DateTimeImmutable('+2 weeks');
        $item = new Item('some.key', null, $time);
        self::assertEquals($time, $item->getExpirationTime());
    }

    /**
     * @covers ::__construct
     * @covers ::expiresAt
     * @covers ::filterExpirationTime
     * @covers ::filterKey
     * @throws \Exception
     */
    public function testExpiresAt(): void
    {
        $item = new Item('some.key');
        $expirationTime = new DateTimeImmutable('+5 weeks');
        self::assertSame($item, $item->expiresAt($expirationTime));
        self::assertEquals($expirationTime, $item->getExpirationTime());
        self::assertInstanceOf(DateTimeImmutable::class, $item->getExpirationTime());

        $expirationTime = new \DateTime('+5 weeks');
        self::assertSame($item, $item->expiresAt($expirationTime));
        self::assertEquals($expirationTime, $item->getExpirationTime());
        self::assertInstanceOf(DateTimeImmutable::class, $item->getExpirationTime());
    }

    /**
     * @covers ::__construct
     * @covers ::expiresAt
     * @covers ::filterExpirationTime
     * @covers ::filterKey
     * @expectedException InvalidArgumentException
     * @dataProvider provideExpiresAtTimes
     * @param $arg
     */
    public function testExpiresAtThrowsExceptionOnInvalidArgument($arg): void
    {
        $item = new Item('some.key');
        $item->expiresAt($arg);
    }

    public function provideExpiresAtTimes(): array
    {
        return [
            [false],
            [1],
            [5.4],
            [[1]],
            ['test'],
            [new class {
            }],
            [stream_context_create()]
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::expiresAfter
     * @covers ::filterKey
     * @throws \Exception
     */
    public function testExpiresAfter(): void
    {
        $item = new Item('some.key');
        $interval = new \DateInterval('P2D');
        $now = new DateTimeImmutable();
        self::assertSame($item, $item->expiresAfter($interval));
        self::assertSame($interval->format('%d'), $item->getExpirationTime()->diff($now)->format('%d'));
        $now = new DateTimeImmutable();
        self::assertSame($item, $item->expiresAfter(3600));
        self::assertSame(3600, $item->getExpirationTime()->getTimestamp() - $now->getTimestamp());
    }

    /**
     * @covers ::__construct
     * @covers ::expiresAfter
     * @covers ::filterKey
     * @expectedException InvalidArgumentException
     * @dataProvider provideExpiresAfterTimeIntervals
     * @param $arg
     * @throws \Exception
     */
    public function testExpiresAfterThrowsExceptionOnInvalidArgument($arg): void
    {
        $item = new Item('some.key');
        $item->expiresAfter($arg);
    }

    public function provideExpiresAfterTimeIntervals(): array
    {
        return [
            [false],
            [5.4],
            [[1]],
            ['test'],
            [new class {
            }],
            [stream_context_create()]
        ];
    }

    /**
     * @covers ::createHit
     * @throws \Exception
     */
    public function testCreateHit(): void
    {
        $expirationTime = new DateTimeImmutable('+4 weeks');
        $item = Item::createHit('some.key', 'some value', $expirationTime);
        self::assertTrue($item->isHit());
        self::assertSame('some.key', $item->getKey());
        self::assertSame('some value', $item->get());
        self::assertSame($expirationTime, $item->getExpirationTime());
    }

    /**
     * @covers ::createMiss
     * @throws \Exception
     */
    public function testCreateMiss(): void
    {
        $item = Item::createMiss('some.key');
        self::assertFalse($item->isHit());
        self::assertSame('some.key', $item->getKey());
        self::assertNull($item->get());
        self::assertNull($item->getExpirationTime());
    }
}
