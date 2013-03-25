<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Application;



/**
 * Generic interface for objects with ability to generate links.
 *
 * @author     Jan Smitka
 */
interface ILinkGenerator
{
	/**
	 * Creates the link URL from the given arguments. This function must also accept
	 * variable number of arguments.
	 *
	 * @param string $destination Destination string.
	 * @param array|mixed $args Link arguments.
	 * @return string Generated URL.
	 */
	public function link($destination, $args = array());
}
