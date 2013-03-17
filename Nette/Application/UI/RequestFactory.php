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
	Nette\ComponentModel\IComponent;


/**
 * RequestFactory handles creation of internal application requests for visual components.
 *
 * @author     Jan Smitka
 */
class RequestFactory extends Nette\Object
{
	const PRESENTER_CLASS = 'Nette\Application\UI\Presenter';

	/** @var Application\IPresenterFactory */
	private $presenterFactory;


	public function __construct(Application\IPresenterFactory $presenterFactory)
	{
		$this->presenterFactory = $presenterFactory;
	}


	/**
	 * Create application request.
	 *
	 * @param string $destination
	 * @param array $args
	 * @param PresenterComponent $context Component relative to which the request will be created. Affects handling of the $destination param.
	 * @param string $mode link|forward|redirect
	 * @return Application\Request Created application requests.
	 * @throws InvalidLinkException
	 */
	public function createRequest($destination, array $args, PresenterComponent $context = NULL, $mode = 'link')
	{
		if ($context !== NULL) {
			$contextPresenter = $context->getPresenter();
		} else {
			$contextPresenter = NULL;
		}

		// PARSE DESTINATION
		// ?query syntax
		$a = strpos($destination, '?');
		if ($a !== FALSE) {
			parse_str(substr($destination, $a + 1), $args); // requires disabled magic quotes
			$destination = substr($destination, 0, $a);
		}

		// signal or empty
		if (!$context instanceof Presenter || substr($destination, -1) === '!') {
			if ($context === NULL) {
				throw new InvalidLinkException('Context of request must be specified when using signals.');
			}

			$signal = rtrim($destination, '!');
			$a = strrpos($signal, ':');
			if ($a !== FALSE) {
				$context = $context->getComponent(strtr(substr($signal, 0, $a), ':', '-'));
				$signal = (string) substr($signal, $a + 1);
			}
			if ($signal == NULL) {  // intentionally ==
				throw new InvalidLinkException("Signal must be non-empty string.");
			}
			$destination = 'this';
		}

		if ($destination == NULL) {  // intentionally ==
			throw new InvalidLinkException("Destination must be non-empty string.");
		}

		// presenter: action
		$current = FALSE;
		$a = strrpos($destination, ':');
		if ($a === FALSE) {
			if ($contextPresenter === NULL) {
				throw new InvalidLinkException('Context of must be specified when using relative destination.');
			}

			$action = $destination === 'this' ? $contextPresenter->action : $destination;
			$presenter = $contextPresenter->getName();
			$presenterClass = get_class($contextPresenter);
		} else {
			$action = (string) substr($destination, $a + 1);
			if ($destination[0] === ':') { // absolute
				if ($a < 2) {
					throw new InvalidLinkException("Missing presenter name in '$destination'.");
				}
				$presenter = substr($destination, 1, $a - 1);
			} elseif ($contextPresenter === NULL) { // absolute without leading :
				$presenter = substr($destination, 0, $a);
			} else { // relative
				$presenter = $contextPresenter->getName();
				$b = strrpos($presenter, ':');
				if ($b === FALSE) { // no module
					$presenter = substr($destination, 0, $a);
				} else { // with module
					$presenter = substr($presenter, 0, $b + 1) . substr($destination, 0, $a);
				}
			}
			try {
				$presenterClass = $this->presenterFactory->getPresenterClass($presenter);
			} catch (Application\InvalidPresenterException $e) {
				throw new InvalidLinkException($e->getMessage(), NULL, $e);
			}
		}

		// PROCESS SIGNAL ARGUMENTS
		if (isset($signal)) { // $component must be IStatePersistent, no $presenterContext checking required
			$reflection = new PresenterComponentReflection(get_class($context));
			if ($signal === 'this') { // means "no signal"
				$signal = '';
				if (array_key_exists(0, $args)) {
					throw new InvalidLinkException("Unable to pass parameters to 'this!' signal.");
				}

			} elseif (strpos($signal, IComponent::NAME_SEPARATOR) === FALSE) { // TODO: AppForm exception
				// counterpart of signalReceived() & tryCall()
				$method = $context->formatSignalMethod($signal);
				if (!$reflection->hasCallableMethod($method)) {
					throw new InvalidLinkException("Unknown signal '$signal', missing handler {$reflection->name}::$method()");
				}
				if ($args) { // convert indexed parameters to named
					self::argsToParams(get_class($context), $method, $args);
				}
			}

			// counterpart of IStatePersistent
			if ($args && array_intersect_key($args, $reflection->getPersistentParams())) {
				$context->saveState($args);
			}

			if ($args && $context !== $contextPresenter) {
				$prefix = $context->getUniqueId() . IComponent::NAME_SEPARATOR;
				foreach ($args as $key => $val) {
					unset($args[$key]);
					$args[$prefix . $key] = $val;
				}
			}
		}


		// PROCESS ARGUMENTS
		if (is_subclass_of($presenterClass, self::PRESENTER_CLASS)) {
			if ($action === '') {
				$action = Presenter::DEFAULT_ACTION;
			}

			$current = $contextPresenter !== NULL && ($action === '*' || strcasecmp($action, $contextPresenter->action) === 0)
				&& $presenterClass === get_class($contextPresenter); // TODO

			$reflection = new PresenterComponentReflection($presenterClass);
			if ($args || $destination === 'this') {
				// counterpart of run() & tryCall()
				/**/$method = $presenterClass::formatActionMethod($action);/**/
				/*5.2* $method = call_user_func(array($presenterClass, 'formatActionMethod'), $action);*/
				if (!$reflection->hasCallableMethod($method)) {
					/**/$method = $presenterClass::formatRenderMethod($action);/**/
					/*5.2* $method = call_user_func(array($presenterClass, 'formatRenderMethod'), $action);*/
					if (!$reflection->hasCallableMethod($method)) {
						$method = NULL;
					}
				}

				// convert indexed parameters to named
				if ($method === NULL) {
					if (array_key_exists(0, $args)) {
						throw new InvalidLinkException("Unable to pass parameters to action '$presenter:$action', missing corresponding method.");
					}

				} elseif ($destination === 'this') {
					self::argsToParams($presenterClass, $method, $args, $this->params);

				} else {
					self::argsToParams($presenterClass, $method, $args);
				}
			}

			// state can be persisted only when called in context of an active component
			if ($contextPresenter !== NULL) {
				// counterpart of IStatePersistent
				if ($args && array_intersect_key($args, $reflection->getPersistentParams())) {
					$contextPresenter->saveState($args, $reflection);
				}

				if ($mode === 'redirect') {
					$contextPresenter->saveGlobalState();
				}

				$globalState = $contextPresenter->getGlobalState($destination === 'this' ? NULL : $presenterClass);
				if ($current && $args) {
					$tmp = $globalState + $contextPresenter->getParameters();
					foreach ($args as $key => $val) {
						if (http_build_query(array($val)) !== (isset($tmp[$key]) ? http_build_query(array($tmp[$key])) : '')) {
							$current = FALSE;
							break;
						}
					}
				}
				$args += $globalState;
			}
		}

		// ADD ACTION
		$args[Presenter::ACTION_KEY] = $action;

		// ADD SIGNAL & FLASH
		if ($contextPresenter !== NULL) {
			if (!empty($signal)) {
				$args[Presenter::SIGNAL_KEY] = $context->getParameterId($signal);
				$current = $current && $args[Presenter::SIGNAL_KEY] === $contextPresenter->getParameter(Presenter::SIGNAL_KEY);
			}
			if (($mode === 'redirect' || $mode === 'forward') && $contextPresenter->hasFlashSession()) {
				$args[Presenter::FLASH_KEY] = $contextPresenter->getParameter(Presenter::FLASH_KEY);
			}
		}

		$request = new Application\Request(
			$presenter,
			Application\Request::FORWARD,
			$args,
			array(),
			array()
		);
		$request->setCurrent($current);

		return $request;
	}



	/**
	 * Converts list of arguments to named parameters.
	 * @param  string  class name
	 * @param  string  method name
	 * @param  array   arguments
	 * @param  array   supplemental arguments
	 * @return void
	 * @throws InvalidLinkException
	 */
	private static function argsToParams($class, $method, & $args, $supplemental = array())
	{
		$i = 0;
		$rm = new \ReflectionMethod($class, $method);
		foreach ($rm->getParameters() as $param) {
			$name = $param->getName();
			if (array_key_exists($i, $args)) {
				$args[$name] = $args[$i];
				unset($args[$i]);
				$i++;

			} elseif (array_key_exists($name, $args)) {
				// continue with process

			} elseif (array_key_exists($name, $supplemental)) {
				$args[$name] = $supplemental[$name];

			} else {
				continue;
			}

			if ($args[$name] === NULL) {
				continue;
			}

			$def = $param->isDefaultValueAvailable() && $param->isOptional() ? $param->getDefaultValue() : NULL; // see PHP bug #62988
			$type = $param->isArray() ? 'array' : gettype($def);
			if (!PresenterComponentReflection::convertType($args[$name], $type)) {
				throw new InvalidLinkException("Invalid value for parameter '$name' in method $class::$method(), expected " . ($type === 'NULL' ? 'scalar' : $type) . ".");
			}

			if ($args[$name] === $def || ($def === NULL && is_scalar($args[$name]) && (string) $args[$name] === '')) {
				$args[$name] = NULL; // value transmit is unnecessary
			}
		}

		if (array_key_exists($i, $args)) {
			$method = $rm->getName();
			throw new InvalidLinkException("Passed more parameters than method $class::$method() expects.");
		}
	}
}
