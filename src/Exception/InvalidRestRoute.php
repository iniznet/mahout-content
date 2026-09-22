<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Exception;

/**
 * A REST route definition violates the REST contract.
 *
 * The namespace carries the version and the segments are lowercase kebab-case,
 * so a route that does not say its version is refused rather than served under
 * an unversioned path. The route's Permission is the one permission
 * declaration, so a definition that also carries a permission_callback in its
 * args is a second way to declare one permission, and is refused too.
 */
final class InvalidRestRoute extends \InvalidArgumentException implements MahoutException
{
    private function __construct(string $message, private readonly string $value)
    {
        parent::__construct($message);
    }

    public static function badNamespace(string $namespace): self
    {
        return new self(
            sprintf('The REST namespace "%s" must be <slug>/v<n> in lowercase.', $namespace),
            $namespace,
        );
    }

    public static function badSegment(string $route): self
    {
        return new self(
            sprintf('The REST route "%s" must be lowercase kebab-case segments, without a leading slash.', $route),
            $route,
        );
    }

    public static function permissionCallbackRedeclared(string $route): self
    {
        return new self(
            sprintf('The REST route "%s" declares a permission through its Permission; the args may not carry permission_callback.', $route),
            $route,
        );
    }

    public function value(): string
    {
        return $this->value;
    }
}
