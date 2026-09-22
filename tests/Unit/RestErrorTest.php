<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Tests\Unit;

use Iniznet\Mahout\Content\RestError;
use Iniznet\Mahout\Content\RestErrorCode;
use Iniznet\Mahout\Content\Tests\TestCase;

/**
 * The one error shape: a stable machine code, a translated message, and a
 * valid HTTP status in the error data.
 *
 * @internal
 */
final class RestErrorTest extends TestCase
{
    public function testNotFoundCarriesAStableCodeATranslatedMessageAndAValidStatus(): void
    {
        $error = RestError::notFound();

        self::assertSame(RestErrorCode::NotFound->value, $error->get_error_code());
        self::assertNotSame('', $error->get_error_message());
        self::assertSame(404, $this->status($error));
    }

    public function testInvalidParameterCarriesTheParameterAndAValidStatus(): void
    {
        $error = RestError::invalidParameter('slug');

        self::assertSame(RestErrorCode::InvalidParameter->value, $error->get_error_code());
        self::assertNotSame('', $error->get_error_message());
        self::assertSame(400, $this->status($error));
        self::assertSame(['status' => 400, 'param' => 'slug'], $error->get_error_data());
    }

    public function testForbiddenUsesCoreAuthorizationCodeWhenNobodyIsLoggedIn(): void
    {
        \wp_set_current_user(0);

        $error = RestError::forbidden();

        self::assertSame(RestErrorCode::Forbidden->value, $error->get_error_code());
        self::assertSame(401, $this->status($error));
    }

    public function testForbiddenUsesCoreAuthorizationCodeWhenAUserIsLoggedIn(): void
    {
        \wp_set_current_user(self::factory()->user->create(['role' => 'subscriber']));

        $error = RestError::forbidden();

        self::assertSame(403, $this->status($error));
    }

    public function testEveryStatusIsValidHttp(): void
    {
        foreach ([RestError::notFound(), RestError::invalidParameter('x'), RestError::forbidden()] as $error) {
            $status = $this->status($error);

            self::assertIsInt($status);
            self::assertGreaterThanOrEqual(400, $status);
            self::assertLessThanOrEqual(599, $status);
        }
    }

    private function status(\WP_Error $error): int
    {
        $data = $error->get_error_data();
        self::assertIsArray($data);

        return (int) ($data['status'] ?? 0);
    }
}
