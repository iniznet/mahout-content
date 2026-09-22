<?php

/**
 * A content model whose two definitions claim the same rewrite slug: the
 * negative fixture for the content-model doctor.
 */

declare(strict_types=1);

use Iniznet\Mahout\Content\PostType;
use Iniznet\Mahout\Content\Taxonomy;

return [
    new PostType(key: 'fixture_series', args: [
        'label' => 'Series',
        'public' => true,
        'rewrite' => ['slug' => 'series'],
    ]),
    new Taxonomy(key: 'fixture_genre', objectTypes: ['fixture_series'], args: [
        'public' => true,
        'rewrite' => ['slug' => 'series'],
    ]),
];
