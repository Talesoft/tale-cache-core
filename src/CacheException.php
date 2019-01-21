<?php declare(strict_types=1);

namespace Tale;

use Psr\Cache\CacheException as PsrCacheException;
use Psr\SimpleCache\CacheException as PsrSimpleCacheException;

final class CacheException extends \RuntimeException implements PsrCacheException, PsrSimpleCacheException
{
}
