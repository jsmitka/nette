<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Templating;

use Nette,
	Nette\Application;


/**
 * Template helpers for link generation.
 *
 * @author     Jan Smitka
 */
class LinkHelper extends Nette\Object
{
	/** @var Application\ILinkGenerator */
	private $linkGenerator;

	/** @var Application\ILinkGenerator */
	private $presenter;


	/**
	 * @param $linkGenerator
	 */
	public function __construct(Application\ILinkGenerator $linkGenerator, Application\ILinkGenerator $presenter = NULL)
	{
		$this->linkGenerator = $linkGenerator;
		if ($presenter === NULL && $linkGenerator instanceof Application\UI\PresenterComponent) {
			$this->presenter = $linkGenerator->getPresenter();
		} else {
			$this->presenter = $presenter;
		}
	}


	/**
	 * @param ITemplate|FileTemplate $template
	 */
	public function register(ITemplate $template)
	{
		$template->registerHelper('_link', array($this, 'linkHelper'));
		$template->registerHelper('_plink', array($this, 'plinkHelper'));
	}


	public function linkHelper($destination, $args = array())
	{
		return $this->linkGenerator->link($destination, $args);
	}

	public function plinkHelper($destination, $args = array())
	{
		if ($this->presenter === NULL) {
			return $this->linkHelper($destination, $args);
		} else {
			return $this->presenter->link($destination, $args);
		}
	}
}
