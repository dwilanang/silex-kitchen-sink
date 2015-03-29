<?php

return call_user_func(function()
{
	$env = getenv('ENV') ?: 'dev';
	$projectName = getenv('PROJECT_NAME') ?: basename(dirname(dirname(dirname(__DIR__))), '.dev');
	$schemaName = 'main';

	$dbname = getenv('DB_NAME') ?: "{$projectName}_{$env}";
	$dbhost = getenv('DB_HOST') ?: 'localhost';
	$dbuser = getenv('DB_USER') ?: $projectName;
	$dbpass = getenv('DB_PASS') ?: $projectName;

	return [
		'propel' => [
			'general' => [
				'project' => $schemaName
			],
			'paths' => [
				'schemaDir' => 'app/propel/config',
				'outputDir' => 'src',
				'phpDir' => 'src',
				'phpConfDir' => 'app/propel/config',
				'sqlDir' => 'app/propel/sql',
			],
			'database' => [
				'connections' => [
					$schemaName => [
						'adapter'    => 'mysql',
						'dsn'        => sprintf('mysql:host=%s;user=%s;password=%s;dbname=%s', $dbhost, $dbuser, $dbpass, $dbname),
						'user'       => $dbuser,
						'password'   => $dbpass,
						'attributes' => [],
						'settings' => [
							'charset' => 'utf8',
							'queries' => [
								'utf8' => 'SET NAMES utf8',
							]
						]
					]
				]
			],
			'runtime' => [
				'defaultConnection' => $schemaName,
				'connections' => [$schemaName]
			],
			'generator' => [
				'defaultConnection' => $schemaName,
				'connections' => [$schemaName]
			]
		]
	];
});
