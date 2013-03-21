<?php

/**
 * Test: Nette\Application\UI\LinkGenerator::createLink() without component context.
 *
 * @author     Jan Smitka
 * @package    Nette\Application\UI
 */

use Nette\Application,
	Nette\Http;
use Tester\Assert;



require __DIR__ . '/../bootstrap.php';


class TestPresenter extends Application\UI\Presenter
{
	public function renderFoo($id, $val) {}
}


$container = id(new Nette\Config\Configurator)->setTempDirectory(TEMP_DIR)->createContainer();

$url = new Http\UrlScript('http://localhost/index.php');
$url->setScriptPath('/index.php');

$router = new Application\Routers\SimpleRouter();

$linkGenerator = new Application\UI\LinkGenerator($router, $url, $container->getService('nette.requestFactory'));

// createLink()
Assert::same('/index.php?action=foo&presenter=Test', $linkGenerator->createLink('Test:foo', array()));
Assert::same('/index.php?action=foo&presenter=Test', $linkGenerator->createLink(':Test:foo', array()));
Assert::same('/index.php?id=123&action=foo&presenter=Test', $linkGenerator->createLink('Test:foo', array(123)));
Assert::same('/index.php?id=123&val=abc&action=foo&presenter=Test', $linkGenerator->createLink('Test:foo', array(123, 'abc')));

// link()
Assert::same('/index.php?action=foo&presenter=Test', $linkGenerator->link('Test:foo'));
Assert::same('/index.php?action=foo&presenter=Test', $linkGenerator->link(':Test:foo'));
Assert::same('/index.php?id=123&action=foo&presenter=Test', $linkGenerator->link('Test:foo', 123));
Assert::same('/index.php?id=123&val=abc&action=foo&presenter=Test', $linkGenerator->link('Test:foo', 123, 'abc'));

// errors
Assert::exception(function () use ($linkGenerator) {
	$linkGenerator->createLink('this', array());
}, 'Nette\Application\UI\InvalidLinkException');

Assert::exception(function () use ($linkGenerator) {
	$linkGenerator->createLink('signal!', array());
}, 'Nette\Application\UI\InvalidLinkException');
