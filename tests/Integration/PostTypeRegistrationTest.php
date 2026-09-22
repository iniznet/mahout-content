<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Tests\Integration;

use Iniznet\Mahout\Content\Exception\ContentTypeCollision;
use Iniznet\Mahout\Content\Exception\InvalidContentTypeKey;
use Iniznet\Mahout\Content\Exception\ReservedContentTypeName;
use Iniznet\Mahout\Content\PostType;
use Iniznet\Mahout\Content\Tests\TestCase;

/**
 * Registration, collision and naming, proved against core's own registry.
 *
 * The negative claims are the point: a collision is a loud boot failure where
 * core itself would silently overwrite, and a reserved name is refused before
 * the collision check can even be order-dependent.
 *
 * @internal
 */
final class PostTypeRegistrationTest extends TestCase
{
    public function testADeclarativeDefinitionRegistersThePostTypeWithTheDeclaredArgs(): void
    {
        $this->trackPostType('fixture_series');

        $this->registrar()->registerPostType(new PostType(
            key: 'fixture_series',
            args: [
                'labels' => ['name' => 'Series'],
                'public' => true,
                'has_archive' => true,
                'show_in_rest' => true,
                'supports' => ['title', 'editor'],
                'rewrite' => ['slug' => 'series'],
            ],
        ));

        self::assertTrue(\post_type_exists('fixture_series'));

        $object = \get_post_type_object('fixture_series');

        self::assertNotNull($object);
        self::assertTrue($object->public);
        self::assertTrue($object->has_archive);
        self::assertSame('series', $object->rewrite['slug'] ?? null);
        self::assertTrue(\post_type_supports('fixture_series', 'title'));
        self::assertTrue(\post_type_supports('fixture_series', 'editor'));
    }

    public function testACollidingPostTypeKeyThrowsContentTypeCollision(): void
    {
        $this->trackPostType('fixture_series');
        $registrar = $this->registrar();
        $registrar->registerPostType(new PostType(key: 'fixture_series', args: ['public' => true]));

        $this->expectException(ContentTypeCollision::class);
        $registrar->registerPostType(new PostType(key: 'fixture_series', args: ['public' => false]));
    }

    public function testTheCollisionNamesItsKindAndKey(): void
    {
        $this->trackPostType('fixture_series');
        $registrar = $this->registrar();
        $registrar->registerPostType(new PostType(key: 'fixture_series', args: ['public' => true]));

        try {
            $registrar->registerPostType(new PostType(key: 'fixture_series'));
            self::fail('A colliding registration must throw.');
        } catch (ContentTypeCollision $collision) {
            self::assertSame('post type', $collision->kind());
            self::assertSame('fixture_series', $collision->key());
        }
    }

    public function testACoreReservedPostTypeNameIsRefused(): void
    {
        $this->expectException(ReservedContentTypeName::class);
        $this->registrar()->registerPostType(new PostType(key: 'post'));
    }

    public function testASecondCoreReservationIsRefusedToo(): void
    {
        $this->expectException(ReservedContentTypeName::class);
        $this->registrar()->registerPostType(new PostType(key: 'wp_font_family'));
    }

    public function testAKeyWithoutANamespaceFailsRegistration(): void
    {
        $this->expectException(InvalidContentTypeKey::class);
        $this->registrar()->registerPostType(new PostType(key: 'series'));
    }

    public function testAMixedCaseKeyFailsBeforeCoreCanLowercaseIt(): void
    {
        // register_post_type() applies sanitize_key(), which would silently
        // register 'howdah_series' for the key 'Howdah_Series'. The naming rule
        // refuses it first, because a mangled key is a different key.
        $this->expectException(InvalidContentTypeKey::class);
        $this->registrar()->registerPostType(new PostType(key: 'Howdah_Series'));
    }

    public function testAKeyOverTheTwentyCharacterCapFailsRegistration(): void
    {
        $this->expectException(InvalidContentTypeKey::class);
        $this->registrar()->registerPostType(new PostType(key: 'fixture_series_over_twenty_chars'));
    }

    public function testTheOverLengthRejectionNamesTheMaximum(): void
    {
        try {
            $this->registrar()->registerPostType(new PostType(key: 'fixture_series_over_twenty'));
            self::fail('An over-length key must be refused.');
        } catch (InvalidContentTypeKey $exception) {
            self::assertSame('post type', $exception->kind());
            self::assertSame('fixture_series_over_twenty', $exception->key());
            self::assertStringContainsString('20', $exception->getMessage());
        }
    }
}
