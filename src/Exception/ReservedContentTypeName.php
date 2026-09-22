<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Exception;

/**
 * A post type or taxonomy key is reserved by WordPress core.
 *
 * Core owns these names. Registering one is never the intent, and core's own
 * behaviour when it happens is a silent, order-dependent override.
 */
final class ReservedContentTypeName extends \InvalidArgumentException implements MahoutException
{
    private function __construct(
        string $message,
        private readonly string $kind,
        private readonly string $key,
    ) {
        parent::__construct($message);
    }

    public static function forKey(string $kind, string $key): self
    {
        return new self(sprintf('The %s name "%s" is reserved by WordPress core.', $kind, $key), $kind, $key);
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
