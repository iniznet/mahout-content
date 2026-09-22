<?php

/**
 * A content model with distinct rewrite slugs: the positive fixture for the
 * content-model doctor.
 */

declare(strict_types=1);

use Iniznet\Mahout\Content\PostType;
use Iniznet\Mahout\Content\Taxonomy;

return [
    new PostType(key: 'fixture_series', args: [
        'public' => true,
        'rewrite' => ['slug' => 'series'],
    ]),
    new Taxonomy(key: 'fixture_genre', objectTypes: ['fixture_series'], args: [
        'rewrite' => ['slug' => 'genre'],
    ]),
];
