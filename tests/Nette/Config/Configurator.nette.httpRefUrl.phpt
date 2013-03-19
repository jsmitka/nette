<?php

/**
 * Test: Nette\Config\Configurator: HTTP request without reference URL fallback.
 *
 * @author     Jan Smitka
 * @package    Nette\Config
 */

use Nette\Config\Configurator;



require __DIR__ . '/../bootstrap.php';


$_SERVER['HTTP_HOST'] = 'example.com';

$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$container = $configurator->addConfig('files/config.nette.fallbackRefUrl.neon')
	->createContainer();

Assert::same('http://example.com/', (string) $container->createNette__refUrl());
