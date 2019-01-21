<?php declare(strict_types=1);

namespace Tale\Cache;

use Psr\Cache\InvalidArgumentException as PsrCacheInvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrSimpleCacheInvalidArgumentException;

final class InvalidArgumentException extends \InvalidArgumentException implements
    PsrCacheInvalidArgumentException,
    PsrSimpleCacheInvalidArgumentException
{
}
