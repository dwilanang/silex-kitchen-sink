<?php

use Igorw\Silex\ConfigServiceProvider;
use Nassau\Silex\Provider\TranslationLoaderProvider;
use Nassau\Silex\ScopedApplication;
use Nassau\SilexWhoops\WhoopsServiceProvider;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Propel;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

include dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/config/propel-connection.php';

$app = new ScopedApplication([
	'path.app' => dirname(__DIR__) . '/app',
	'path.config' => dirname(__DIR__) . '/app/config',
	'path.cache' => getenv('DEBUG') ? dirname(__DIR__) . '/cache' : (getenv('TMPDIR') ?: sys_get_temp_dir()),
	'path.src' => dirname(__DIR__) . '/src',
	'path.views' => dirname(__DIR__) . '/app/views',
	'path.translations' => dirname(__DIR__) . '/app/translations',
	'path.assets' => dirname(__DIR__) . '/web/assets',

	'sentry.dsn' => getenv('SENTRY_DSN'),

	'redis.host' => getenv('REDIS_HOST'),
	'memcached.host' => getenv('MEMCACHED_HOST'),


	'debug' => (bool) getenv('DEBUG'),

]);

call_user_func(function ($queriesPath) use ($app)
{
	if (file_exists($queriesPath))
	{
		foreach (require $queriesPath as $key => $value)
		{
			$app[$key] = $app->publish($app->factory($value));
		}
	}
}, __DIR__ . '/propel/config/query-di.php');

$app->register(new ConfigServiceProvider($app['path.config'] . '/application.yaml'));
$app->register(new TranslationServiceProvider);
$app->register(new TranslationLoaderProvider, [
	'translator.loader.path' => $app['path.translations'],
]);

$app->register(new TwigServiceProvider, [
	'twig.path' => [
		$app['path.views'],
		$app['path.views'] . '/pages',
		$app['path.views'] . '/layouts',
		$app['path.views'] . '/blocks',
		$app['path.assets']
	],
	'twig.options' => [
		'cache' => $app['path.cache'] . '/twig',
		'debug' => $app['debug'],
		'strict_variables' => true,
	],
]);

$app->register(new ServiceControllerServiceProvider);
$app->register(new SessionServiceProvider, [
	'session.storage.handler' => function ()
	{
		/** @var ConnectionWrapper $connectionInterface */
		$connectionInterface = Propel::getConnection();
		return new PdoSessionHandler($connectionInterface->getWrappedConnection(), [
			'db_table' => 'session',
			'db_id_col' => 'id',
			'db_data_col' => 'data',
			'db_time_col' => 'created_at',
		]);
	},
]);

$app['raven'] = function () use ($app)
{
	return new Raven_Client($app['sentry.dsn']);
};
$app['raven.error_handler'] = function () use ($app)
{
	return new Raven_ErrorHandler($app['raven']);
};
$app['raven.subscribe'] = $app->publish(function () use ($app)
{
	/** @var Raven_ErrorHandler $errorHandler */
	$errorHandler = $app['raven.error_handler'];
	/** @var Raven_Client $raven */
	$raven = $app['raven'];

	return function () use ($errorHandler, $raven)
	{
		$errorHandler->registerExceptionHandler();
		$errorHandler->registerErrorHandler();
		$errorHandler->registerShutdownFunction();

		// return error handling function for silex:
		return function (\Exception $e) use ($raven)
		{
			$raven->captureException($e);

			return new Response("", Response::HTTP_INTERNAL_SERVER_ERROR);
		};
	};
});

if ($app['debug'])
{
	// register debug handler for web, skip it for cli, since it’s crappy:
	'cli' !== PHP_SAPI && $app->register(new WhoopsServiceProvider);
}
else
{
	$app->error($app['raven.subscribe']());
}

return call_user_func(function () use ($app)
{
	if (false === isset($app['event-dispatcher']))
	{
		return $app;
	}

	$subscriberKeyPrefix = 'subscriber.';
	foreach ($app->keys() as $key)
	{
		if ($subscriberKeyPrefix === substr($key, 0, strlen($subscriberKeyPrefix)))
		{
			$app['event-dispatcher'] = $app->extend('event-dispatcher', function (EventDispatcherInterface $dispatcher) use ($app, $key)
			{
				$dispatcher->addSubscriber($app[$key]);

				return $dispatcher;
			});
		}
	}

	return $app;
});
