<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

/**
 * The rewrite slugs a declared content model claims, and the collisions among
 * them.
 *
 * A rewrite slug collision is silent in WordPress: the second registration's
 * rules lose or win by order, and no notice fires. This class computes the
 * collision set over the declared definitions so the doctor can report it. It
 * resolves no collaborator and touches no WordPress function; it is a value
 * computation over the definitions it is handed.
 */
final readonly class RewriteSlugs
{
    /**
     * @param list<PostType|Taxonomy> $definitions the declared definitions
     */
    public function __construct(private readonly array $definitions)
    {
    }

    /**
     * Every slug claimed by two or more definitions, ordered by slug.
     *
     * @return list<RewriteCollision>
     */
    public function collisions(): array
    {
        /** @var array<string, list<string>> $claims */
        $claims = [];

        foreach ($this->definitions as $definition) {
            $slug = $this->slug($definition);

            if (is_string($slug)) {
                $claims[$slug][] = $this->declares($definition);
            }
        }

        $collisions = [];

        foreach ($claims as $slug => $declaredBy) {
            if (count($declaredBy) > 1) {
                $collisions[] = new RewriteCollision((string) $slug, $declaredBy);
            }
        }

        usort($collisions, static fn (RewriteCollision $a, RewriteCollision $b): int => $a->slug <=> $b->slug);

        return $collisions;
    }

    /**
     * The effective rewrite slug, exactly as WordPress resolves it: false
     * disables the rewrite, an array may name a slug, and anything else falls
     * back to the definition's own key.
     */
    private function slug(PostType|Taxonomy $definition): ?string
    {
        $rewrite = $definition->args['rewrite'] ?? null;

        if (false === $rewrite) {
            return null;
        }

        if (is_array($rewrite) && is_string($rewrite['slug'] ?? null) && '' !== $rewrite['slug']) {
            return (string) $rewrite['slug'];
        }

        return $definition->key;
    }

    private function declares(PostType|Taxonomy $definition): string
    {
        $kind = $definition instanceof PostType ? 'post type' : 'taxonomy';

        return $kind.' '.$definition->key;
    }
}
