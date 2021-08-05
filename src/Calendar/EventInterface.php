<?php
declare(strict_types=1);

namespace Horde\Kronolith;

/**
 * Public interface of a kronolith event
 */
interface EventInterface
{
    public function getAttendees();
    public function getExceptions();
}