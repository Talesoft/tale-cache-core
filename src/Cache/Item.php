<?php declare(strict_types=1);

namespace Tale\Cache;

final class Item implements ItemInterface
{
    /** @var string */
    private $key;

    /** @var mixed */
    private $value;

    /** @var \DateTimeInterface */
    private $expirationTime;

    /** @var bool */
    private $hit;

    /**
     * Item constructor.
     * @param string $key
     * @param null $value
     * @param bool $hit
     * @param \DateTimeInterface|null $expirationTime
     */
    public function __construct($key, $value = null, ?\DateTimeInterface $expirationTime = null, bool $hit = true)
    {
        $this->key = $this->filterKey($key);
        $this->value = $value;
        $this->expirationTime = $this->filterExpirationTime($expirationTime);
        $this->hit = $hit;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get()
    {
        return $this->value;
    }

    public function set($value)
    {
        $this->value = $value;
        $this->hit = true;
        return $this;
    }

    public function getExpirationTime(): ?\DateTimeInterface
    {
        return $this->expirationTime;
    }

    /**
     * @param \DateTimeInterface|null $expiration
     * @return $this|ItemInterface
     * @throws InvalidArgumentException
     */
    public function expiresAt($expiration)
    {
        if ($expiration !== null && !($expiration instanceof \DateTimeInterface)) {
            throw new InvalidArgumentException(
                'Argument 1 passed to expiresAt time needs to be null or DateTimeInterface instance'
            );
        }
        $this->expirationTime = $this->filterExpirationTime($expiration);
        return $this;
    }

    /**
     * @param \DateInterval|int|null $time
     * @return $this|ItemInterface
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    public function expiresAfter($time)
    {
        if ($time !== null && !is_int($time) && !($time instanceof \DateInterval)) {
            throw new InvalidArgumentException(
                'Argument 1 passed to expiresAt time needs to be null, int or DateInterval'
            );
        }
        $this->expirationTime = $time !== null ? new \DateTimeImmutable() : null;
        if (is_int($time)) {
            $this->expirationTime = $this->expirationTime->setTimestamp(
                $this->expirationTime->getTimestamp() + $time
            );
        } elseif ($time instanceof \DateInterval) {
            $this->expirationTime = $this->expirationTime->add($time);
        }
        return $this;
    }

    public function isHit(): bool
    {
        return $this->hit;
    }

    private function filterExpirationTime(?\DateTimeInterface $dateTime): ?\DateTimeInterface
    {
        if ($dateTime === null) {
            return null;
        }
        return $dateTime instanceof \DateTime
            ? \DateTimeImmutable::createFromMutable($dateTime)
            : $dateTime;
    }

    private function filterKey($key): string
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('Cache key needs to be string');
        }

        if ($key === '') {
            throw new InvalidArgumentException('Cache key can\'t be empty');
        }

        if (strlen($key) > 64) {
            throw new InvalidArgumentException('Cache key can only have up to 64 characters');
        }

        if (!preg_match('/^[a-zA-Z0-9._]+$/', $key)) {
            throw new InvalidArgumentException('Cache key can consist of a-z, A-Z, 0-9, . and _ only');
        }
        return $key;
    }

    public static function createHit($key, $value, \DateTimeInterface $expirationTime = null): self
    {
        return new self($key, $value, $expirationTime);
    }

    public static function createMiss($key): self
    {
        return new self($key, null, null, false);
    }
}
