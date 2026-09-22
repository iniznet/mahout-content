<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Exception;

/**
 * A post type or taxonomy key is already registered.
 *
 * A collision is a boot failure, not a silent merge: the site would otherwise
 * serve whichever registration won, which is a config order accident.
 */
final class ContentTypeCollision extends \RuntimeException implements MahoutException
{
    private function __construct(
        string $message,
        private readonly string $kind,
        private readonly string $key,
    ) {
        parent::__construct($message);
    }

    public static function alreadyRegistered(string $kind, string $key): self
    {
        return new self(sprintf('The %s "%s" is already registered.', $kind, $key), $kind, $key);
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
