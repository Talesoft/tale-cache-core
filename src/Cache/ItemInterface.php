<?php declare(strict_types=1);

namespace Tale\Cache;

use Psr\Cache\CacheItemInterface;

interface ItemInterface extends CacheItemInterface
{
    public function getExpirationTime(): ?\DateTimeInterface;
}
