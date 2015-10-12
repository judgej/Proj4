<?php namespace Academe\Proj\Point;

/**
 * Define a projected point.
 * A projected point has a projection and a coordinate in whatever
 * units define it.
 */

use Academe\Proj\ProjectionInterface;

class Projection
{
    /**
     * The $projectino will contain metadata to tell us what params are
     * supported and how they should be validated.
     */
    public function __construct(array $params, ProjectionInterface $projection)
    {
    }
}
