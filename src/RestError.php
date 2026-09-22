<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

/**
 * The package's one REST error shape.
 *
 * A stable machine code, a translated message, and a valid HTTP status in the
 * error data. No exception text, no path, no SQL, and no internal identifier
 * ever reaches the payload. The constructors are value constructors: they hold
 * no state and resolve no collaborator.
 */
final class RestError
{
    public static function notFound(): \WP_Error
    {
        return new \WP_Error(
            RestErrorCode::NotFound->value,
            \__('The requested resource was not found.', 'mahout-content'),
            ['status' => 404],
        );
    }

    public static function invalidParameter(string $parameter): \WP_Error
    {
        return new \WP_Error(
            RestErrorCode::InvalidParameter->value,
            \__('A request parameter is not valid.', 'mahout-content'),
            ['status' => 400, 'param' => $parameter],
        );
    }

    public static function forbidden(): \WP_Error
    {
        return new \WP_Error(
            RestErrorCode::Forbidden->value,
            \__('You are not allowed to perform this request.', 'mahout-content'),
            ['status' => \rest_authorization_required_code()],
        );
    }
}
