<?php

use Nassau\SilexWhoops\WhoopsServiceProvider;
use Symfony\Component\HttpFoundation\Request;

if (php_sapi_name() == 'cli-server' && is_file($_SERVER['REQUEST_URI']))
{
	return false;
}

/** @var \Silex\Application $app */
$app = require dirname(__DIR__) . '/app/app.php';

if ($app['debug'])
{
	$app->register(new WhoopsServiceProvider);
	$app->before(function () use ($app) {
		/** @var Twig_Environment $twig */
		$twig = $app['twig'];
		$twig->addExtension(new Twig_Extension_Debug);
	});
}



Request::setTrustedProxies(array('192.0.0.1', '10.0.0.0/8'));

$app->run();