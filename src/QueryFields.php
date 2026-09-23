<?php

/**
 * The shape a content query is answered with: ids only, or whole rows.
 *
 * `fields => 'ids'` is the hardened value. It is what keeps a listing cheap:
 * one column crosses the wire, the rows are primed in one statement, and the
 * objects the mapper reads come from the cache rather than from a second query
 * per post. A consumer that genuinely needs whole rows from the query itself
 * declares it, and nothing else about the query changes.
 *
 * The empty string is not a missing value: it is core's own name for the full
 * row set, the `case ''` arm of the fields switch in `WP_Query::get_posts()`.
 */

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

enum QueryFields
{
    /** Only the post ids: the hardened default, one column per row. */
    case Ids;

    /** The whole rows, for a consumer that reads the objects off the query itself. */
    case Rows;

    /** The value the `fields` query var carries. */
    public function toQueryVar(): string
    {
        return match ($this) {
            self::Ids => 'ids',
            self::Rows => '',
        };
    }
}
