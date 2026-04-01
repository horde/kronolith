<?php

declare(strict_types=1);

/**
 * A null driver for Kronolith's Geo location data.
 *
 * This driver satisfies the Geo interface requirements but performs no
 * actual storage or retrieval. Used when geolocation features are not
 * configured or not needed.
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @package  Kronolith
 */
class Kronolith_Geo_Null extends Kronolith_Geo_Base
{
    /**
     * Constructor.
     *
     * The null driver doesn't require a database adapter, but accepts one
     * to satisfy the parent constructor signature.
     *
     * @param Horde_Db_Adapter|null $adapter  The Horde_Db adapter (ignored)
     */
    public function __construct($adapter = null)
    {
        // Null driver doesn't need database access
        if ($adapter !== null) {
            parent::__construct($adapter);
        }
    }

    /**
     * Save location of event to storage.
     *
     * This is a no-op in the null driver.
     *
     * @param string $event_id  The event id
     * @param array  $point     Hash containing 'lat' and 'lon' coordinates
     */
    public function setLocation($event_id, $point): void
    {
        // No-op: geolocation storage disabled
    }

    /**
     * Retrieve the location of the specified event.
     *
     * Always returns null as no locations are stored.
     *
     * @param string $event_id  The event id
     *
     * @return array|null  Always returns null
     */
    public function getLocation($event_id): ?array
    {
        return null;
    }

    /**
     * Removes the event's location from storage.
     *
     * This is a no-op in the null driver.
     *
     * @param string $event_id  The event id
     */
    public function deleteLocation($event_id): void
    {
        // No-op: nothing to delete
    }

    /**
     * Search for events close to a given point.
     *
     * Always returns an empty array as no locations are stored.
     *
     * @param array $criteria  Search criteria (ignored)
     *
     * @return array  Always returns an empty array
     */
    public function search($criteria): array
    {
        return [];
    }
}
