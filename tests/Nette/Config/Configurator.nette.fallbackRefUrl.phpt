<?php

/**
 * Test: Nette\Config\Configurator: reference URL fallback.
 *
 * @author     Jan Smitka
 * @package    Nette\Config
 */

use Nette\Config\Configurator;



require __DIR__ . '/../bootstrap.php';



$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$container = $configurator->addConfig('files/config.nette.fallbackRefUrl.neon')
	->createContainer();

Assert::same('http://example.org/sub/directory/index.php', (string) $container->createNette__refUrl());
