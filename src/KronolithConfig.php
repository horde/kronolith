<?php

declare(strict_types=1);
/**
 * Kronolith configuration class
 *
 * Provides access to the Kronolith configuration settings.
 *
 * Old pattern: globals $conf; $somethingDetail = $conf['something']['detail']; *
 * New pattern: $config = $injector->get(KronolithConfig::class); $somethingDetail = $config->get('something.detail');
 *
 * Prefer DI over instantiating $config in your code.
 */

namespace Horde\Kronolith;

use Horde\Core\Config\State;
use Horde\Injector\Attribute\Factory;

#[Factory(factory: KronolithConfigFactory::class, method: 'create')]
class KronolithConfig extends State {}
