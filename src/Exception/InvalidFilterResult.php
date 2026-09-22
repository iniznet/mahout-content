<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Exception;

/**
 * A content filter returned a value of the wrong type.
 *
 * A filter that returns a scalar where an argument array is required would be
 * passed to WordPress as an argument array anyway; refusing it here names the
 * hook instead of producing a downstream failure that looks unrelated.
 */
final class InvalidFilterResult extends \UnexpectedValueException implements MahoutException
{
    private function __construct(string $message, private readonly string $hook)
    {
        parent::__construct($message);
    }

    public static function notAnArray(string $hook): self
    {
        return new self(sprintf('The "%s" filter must return an array.', $hook), $hook);
    }

    /**
     * A registration argument array is a name-to-value map. A filter that
     * returns a list would hand core positional keys and every named argument
     * would be lost.
     */
    public static function notAnArgumentMap(string $hook): self
    {
        return new self(sprintf('The "%s" filter must return an argument map, not a list.', $hook), $hook);
    }

    public function hook(): string
    {
        return $this->hook;
    }
}
