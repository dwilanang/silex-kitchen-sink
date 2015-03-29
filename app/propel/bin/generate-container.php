<?php

require __DIR__ . '/../../../vendor/autoload.php';

file_put_contents(__DIR__ . '/../config/query-di.php', sprintf(
	"<?php /* THIS FILE IS GENERATED AND WILL BE OVERWRITTEN */ return [\n\t%s\n];",
	implode(",\n\t", array_map(
function ($table)
{
	$before = get_declared_classes();
	/** @noinspection PhpIncludeInspection */
	require $table;
	list ($mapName) = array_values(array_filter(array_diff(get_declared_classes(), $before), function ($className)
	{
		return 'Propel\\' !== substr($className, 0, strlen('Propel\\'));
	}));


	return sprintf(
		'"query.%s.%s" => function () { return new %s; }',
		constant("$mapName::DATABASE_NAME"),
		constant("$mapName::TABLE_NAME"),
		constant("$mapName::OM_CLASS") . 'Query'
	);

}, glob(__DIR__ . '/../../../src/*/Model/*/Map/*.php')))));
