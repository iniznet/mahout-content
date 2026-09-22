<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

/**
 * The stable machine codes of the package's REST errors.
 *
 * A code is never translated and never changes meaning within a major version;
 * a client branches on it. The message beside it is the translated, human part.
 */
enum RestErrorCode: string
{
    /** A resource that does not exist. */
    case NotFound = 'mahout_content_not_found';

    /** A request parameter that failed validation. */
    case InvalidParameter = 'mahout_content_invalid_parameter';

    /** A request the route's permission refused. */
    case Forbidden = 'mahout_content_forbidden';
}
