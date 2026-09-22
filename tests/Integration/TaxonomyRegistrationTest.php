<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Tests\Integration;

use Iniznet\Mahout\Content\Exception\ContentTypeCollision;
use Iniznet\Mahout\Content\Exception\InvalidContentTypeKey;
use Iniznet\Mahout\Content\Exception\ReservedContentTypeName;
use Iniznet\Mahout\Content\PostType;
use Iniznet\Mahout\Content\Taxonomy;
use Iniznet\Mahout\Content\Tests\TestCase;

/**
 * The taxonomy half of the registration contract: the declared args, the
 * attachment to the declared object types, and the three refusals.
 *
 * @internal
 */
final class TaxonomyRegistrationTest extends TestCase
{
    public function testADeclarativeDefinitionRegistersTheTaxonomyWithTheDeclaredArgs(): void
    {
        $this->trackPostType('fixture_series');
        $this->trackTaxonomy('fixture_genre');
        $registrar = $this->registrar();
        $registrar->registerPostType(new PostType(key: 'fixture_series', args: ['public' => true]));
        $registrar->registerTaxonomy(new Taxonomy(
            key: 'fixture_genre',
            objectTypes: ['fixture_series'],
            args: ['public' => true, 'hierarchical' => false, 'show_in_rest' => true, 'rewrite' => ['slug' => 'genre']],
        ));

        self::assertTrue(\taxonomy_exists('fixture_genre'));

        $taxonomy = \get_taxonomy('fixture_genre');

        self::assertNotNull($taxonomy);
        self::assertFalse($taxonomy->hierarchical);
        self::assertContains('fixture_series', $taxonomy->object_type);
        self::assertContains('fixture_genre', \get_object_taxonomies('fixture_series'));
    }

    public function testACollidingTaxonomyKeyThrowsContentTypeCollision(): void
    {
        $this->trackTaxonomy('fixture_genre');
        $registrar = $this->registrar();
        $registrar->registerTaxonomy(new Taxonomy(key: 'fixture_genre', objectTypes: ['post']));

        $this->expectException(ContentTypeCollision::class);
        $registrar->registerTaxonomy(new Taxonomy(key: 'fixture_genre', objectTypes: ['post']));
    }

    public function testACoreReservedTaxonomyNameIsRefused(): void
    {
        $this->expectException(ReservedContentTypeName::class);
        $this->registrar()->registerTaxonomy(new Taxonomy(key: 'category', objectTypes: ['post']));
    }

    public function testAKeyWithoutANamespaceFailsRegistration(): void
    {
        $this->expectException(InvalidContentTypeKey::class);
        $this->registrar()->registerTaxonomy(new Taxonomy(key: 'genre', objectTypes: ['post']));
    }

    public function testAKeyOverTheThirtyTwoCharacterCapFailsRegistration(): void
    {
        $this->expectException(InvalidContentTypeKey::class);
        $this->registrar()->registerTaxonomy(new Taxonomy(
            key: 'fixture_'.str_repeat('genre', 8).'_taxonomy',
            objectTypes: ['post'],
        ));
    }
}
