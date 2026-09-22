<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

/**
 * One rewrite slug claimed by more than one declared definition.
 *
 * The doctor's detail renders the slug and every declaration that claims it, so
 * the report names the conflict instead of asserting that one exists.
 */
final readonly class RewriteCollision
{
    /**
     * @param list<string> $declaredBy the "<kind> <key>" strings claiming the slug
     */
    public function __construct(
        public string $slug,
        public array $declaredBy,
    ) {
    }
}
