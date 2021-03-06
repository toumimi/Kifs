<?php
/*
 * This is where all the glue code goes.
 *
 * TODO view helpers
 * TODO improve config files?
 */

error_reporting(E_ALL);

/*
 * Feel free to rename the app directory as you like
 */
$appDir = realpath(__DIR__.'/../app');

// Init autoloader
set_include_path($appDir.PATH_SEPARATOR.
		realpath(__DIR__.'/../vendors').PATH_SEPARATOR.
		get_include_path());

require 'Kifs/Autoload/Autoloader.php';
Kifs\Autoload\Autoloader::register();

// Init application scope
$appScope = new Kifs\Application\Scope($_POST, $_GET, $_COOKIE, $_SERVER);
$appScope->setAppDir($appDir);
$appScope->setTemplateDir($appDir.'/Template');

// Set env
$env = ucfirst(getenv('APP_ENV') ?: 'prod');
$appScope->setEnv($env);

// Init injectors
$appInjector = new Kifs\Injector\Application($appScope);
$controllerInjector = new Injector\Controller($appScope);

// Init error handler
$errorHandler = $appInjector->injectErrorHanlder();
$errorHandler->start();

// Init database connections
$appScope->loadConfig('Db');
$appScope->addDbConnection('master', $appInjector->injectMysqlDbConnection(
	$appScope->getConfig('Db', 'host'),
	$appScope->getConfig('Db', 'login'),
	$appScope->getConfig('Db', 'pass'),
	$appScope->getConfig('Db', 'db')
));

// Init Router
$router = $appInjector->injectRouter();
require $appScope->getAppDir().'/Config/Routes.php';

// Init app
$app = $appInjector->injectControllerApplication($router, $controllerInjector);

// Run app
$app->dispatch($appInjector->injectRequest());
