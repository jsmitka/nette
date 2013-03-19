<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Application\UI;

use Nette,
	Nette\Application,
	Nette\Http;

class LinkGenerator extends Nette\Object
{
	/** @var Application\IRouter */
	private $router;

	/** @var Http\Url */
	private $refUrl;

	/** @var RequestFactory */
	private $requestFactory;

	/** @var Application\Request */
	private $lastCreatedRequest;


	public function __construct(Application\IRouter $router, Http\Url $refUrl, RequestFactory $requestFactory) {
		$this->router = $router;
		$this->requestFactory = $requestFactory;
		$this->refUrl = $refUrl;
	}

	/**
	 * @return Application\Request
	 */
	public function getLastCreatedRequest()
	{
		return $this->lastCreatedRequest;
	}


	public function createLink($destination, array $args, PresenterComponent $context = NULL, $mode = 'link', $absoluteUrl = FALSE)
	{
		$this->lastCreatedRequest = NULL;

		// fragment
		$a = strpos($destination, '#');
		if ($a === FALSE) {
			$fragment = '';
		} else {
			$fragment = substr($destination, $a);
			$destination = substr($destination, 0, $a);
		}

		// URL scheme
		$a = strpos($destination, '//');
		if ($a === FALSE) {
			$scheme = FALSE;
		} else {
			$scheme = substr($destination, 0, $a);
			$destination = substr($destination, $a + 2);
		}

		$this->lastCreatedRequest = $this->requestFactory->createRequest($destination, $args, $context, $mode);

		if ($mode === 'forward' || $mode === 'test') {
			return NULL;
		}

		// CONSTRUCT URL
		$url = $this->router->constructUrl($this->lastCreatedRequest, $this->refUrl);
		if ($url === NULL) {
			unset($args[Presenter::ACTION_KEY]);
			$params = urldecode(http_build_query($args, NULL, ', '));
			$presenter = $this->lastCreatedRequest->getPresenterName();
			$requestArgs = $this->lastCreatedRequest->getParameters();
			$action = $requestArgs[Presenter::ACTION_KEY];
			throw new InvalidLinkException("No route for $presenter:$action($params)");
		}

		// make URL relative if possible
		if ($mode === 'link' && $scheme === FALSE && !$absoluteUrl) {
			$hostUrl = $this->refUrl->getHostUrl();
			if (strncmp($url, $hostUrl, strlen($hostUrl)) === 0) {
				$url = substr($url, strlen($hostUrl));
			}
		}

		return $url . $fragment;
	}
}
