<?php

/**
 * Test: Nette\Latte\Engine: {link ...}, {plink ...} using LinkGenerator
 *
 * @author     Jan Smitka
 * @package    Nette\Latte
 */

use Nette\Latte,
	Nette\Application;



require __DIR__ . '/../bootstrap.php';



class MockLinkGenerator extends Application\UI\LinkGenerator
{
	public function __construct() {}

	public function link($destination, $args = array())
	{
		if (!is_array($args)) {
			$args = func_get_args();
			array_shift($args);
		}
		array_unshift($args, $destination);
		return 'GENLINK(' . implode(', ', $args) . ')';
	}

}



$template = new Nette\Templating\Template;
$template->registerFilter(new Latte\Engine);

$template->_linkGenerator = new MockLinkGenerator();

$template->action = 'login';
$template->arr = array('link' => 'login', 'param' => 123);

Assert::match(<<<EOD
GENLINK(Homepage:)

GENLINK(Homepage:)

GENLINK(Homepage:action)

GENLINK(Homepage:action)

GENLINK(Homepage:action, 10, 20, {one}&amp;two)

GENLINK(:, 10)

GENLINK(default, 10, 20, 30)

GENLINK(login)

GENLINK(login, 123)

GENLINK(default, 10, 20, 30)
EOD

, (string) $template->setSource(<<<EOD
{plink Homepage:}

{plink  Homepage: }

{plink Homepage:action }

{plink 'Homepage:action' }

{plink Homepage:action 10, 20, '{one}&two'}

{plink : 10 }

{plink default 10, 'a' => 20, 'b' => 30}

{link  \$action}

{plink \$arr['link'], \$arr['param']}

{link default 10, 'a' => 20, 'b' => 30}
EOD
));
