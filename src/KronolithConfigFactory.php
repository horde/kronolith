<?php

declare(strict_types=1);
/**
 * Kronolith configuration class factory
 *
 * Creates instances of the KronolithConfig class.
 *
 * Old pattern: globals $conf; $somethingDetail = $conf['something']['detail']; *
 * New pattern: $config = $injector->get(KronolithConfig::class); $somethingDetail = $config->get('something.detail');
 *
 * Prefer DI over instantiating $config in your code.
 */

namespace Horde\Kronolith;

use Horde\Core\Config\ConfigLoader;
use Horde\Injector\Injector;

class KronolithConfigFactory
{
    public function __construct(private Injector $injector) {}

    public function create(): KronolithConfig
    {
        $state = $this->injector->get(ConfigLoader::class)->load('kronolith');
        return new KronolithConfig($state->toArray());
    }
}
