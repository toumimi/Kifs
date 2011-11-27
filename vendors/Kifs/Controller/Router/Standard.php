<?php
namespace Kifs\Controller\Router;

class Standard
{
	/**
	 *Array of routes
	 *
	 *	array(
	 *		uri => /plop/foo/:page/bar
	 *		controllerName => Foo\Bar
	 * 		params => array(':paramname' => 'int')
	 * 	)
	 *
	 * @var array
	 */
	private $routes;


	/**
	 * @param array $config array of Route
	 */
	public function __construct($config)
	{
		$this->_loadRoutesFromConfig($config);
	}

	/**
	 * Add a route to a controller
	 *
	 * Expected arguments :
	 * 		uri : /plop/foo/:page/bar
	 *		controllerName : Foo\Bar
	 * 		params : array(':paramname' => 'int')
	 *
	 * @param string $uri Pattern of uri
	 * @param string $controllerName fully qualified name without the 'Controller' namespace
	 * @param array $params Types of the uri parameters if any
	 * @return void
	 */
	public function addRoute($uri, $controllerName, $params = array())
	{
		$this->_routes[] = array(
			'uri' => $uri,
			'controller' => $controllerName,
			'params' => $params
		);
	}

	/**
	 * @param \Kifs\Controller\Request\Http $request
	 * @return bool
	 */
	public function route($request)
	{
		if ($this->_requestAlreadyRouted($request))
			return false;

		$uri = $request->getServer('REQUEST_URI');
		$uri = substr($uri, 1);

		if (!empty($this->_routes)) {
			$this->_createUriPatterns($uri);

			foreach ($this->_routes as $route) {
				if (preg_match($route['uriPattern'], $uri)) {
					$request->setControllerName($route['controller']);
					return true;
				}
			}
		}

		// Use default route if no user defined routes matched the URI
		$request->setControllerName($this->_getDefaultRoute($uri));
		return true;
	}

	private function _loadRoutesFromConfig($config)
	{
		foreach ($config as $route) {
			/* @var $route \Kifs\Controller\Router\Route */
			$this->addRoute(
				$route->getUri(),
				$route->getControllerName(),
				$route->getParams()
			);
		}
	}

	private function _requestAlreadyRouted($request)
	{
		$controllerName = $request->getControllerName();
		if (empty($controllerName))
			return false;

		return true;
	}

	private function _createUriPatterns()
	{
		foreach ($this->_routes as &$route) {
			$patterns = array();
			$route['uriPattern'] = '#^';

			if (!empty($route['params'])) {
				foreach ($route['params'] as $name => $type) {
					switch ($type) {
						case 'int':
							$patterns[] = '#[0-9]+#';
							break;
						case 'string':
							$patterns[] = '#[^/]+#';
							break;
						default:
							throw Exception('Unknow parameter type');
					}
				}

				$route['uriPattern'] .= preg_replace($patterns, array_keys($route['params']), $route['uri']);
			} else {
				$route['uriPattern'] .= $route['uri'];
			}
			$route['uriPattern'] .= '#';
		}
	}

	private function _getDefaultRoute($uri)
	{
		if (empty($uri))
			return 'Index';

		return str_replace('/', '\\', $uri);
	}

}
