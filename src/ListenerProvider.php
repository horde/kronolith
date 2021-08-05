<?php
declare(strict_types=1);
namespace Horde\Kronolith;
use Horde\EventDispatcher\SimpleListenerProvider;

/**
 * An EventDispatcher ListenerProvider
 * 
 * Has all Kronolith Event Listeners
 */
class ListenerProvider extends SimpleListenerProvider
{
    public function __construct(array $listeners = [])
    {
        foreach ($listeners as $listener) {
            $this->addListener($listener);
        }
    }
}