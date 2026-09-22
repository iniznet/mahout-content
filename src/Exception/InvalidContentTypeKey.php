<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Exception;

/**
 * A post type or taxonomy key violates the content-model naming convention.
 *
 * The convention is <slug>_<name>, lowercase, with a bounded length. A bare
 * name collides across features and a mixed-case name is a different key to
 * every subsystem that compares strings.
 */
final class InvalidContentTypeKey extends \InvalidArgumentException implements MahoutException
{
    private function __construct(
        string $message,
        private readonly string $kind,
        private readonly string $key,
    ) {
        parent::__construct($message);
    }

    public static function notNamespaced(string $kind, string $key): self
    {
        return new self(
            sprintf('The %s key "%s" must match <slug>_<name> in lowercase.', $kind, $key),
            $kind,
            $key,
        );
    }

    public static function tooLong(string $kind, string $key, int $maximum): self
    {
        return new self(
            sprintf('The %s key "%s" is longer than the %d-character maximum.', $kind, $key, $maximum),
            $kind,
            $key,
        );
    }

    /**
     * WordPress itself refused the registration. The naming rule and the length
     * cap are enforced before core is called, so this names a core refusal the
     * package's own checks could not see coming.
     */
    public static function refusedByCore(string $kind, string $key, string $reason): self
    {
        return new self(
            sprintf('The %s "%s" was refused by WordPress: %s', $kind, $key, $reason),
            $kind,
            $key,
        );
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function key(): string
    {
        return $this->key;
    }
}
