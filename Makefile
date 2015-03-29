model:
	vendor/bin/propel build --config-dir=app/propel/config/ --schema-dir=app/propel/config/ --platform=mysql --output-dir=src/
	php app/propel/bin/generate-container.php

sql:
	vendor/bin/propel sql:build --config-dir=app/propel/config/ --schema-dir=app/propel/config/ --platform=mysql --output-dir=app/propel/sql --overwrite

migrate:
	vendor/bin/propel migrate --config-dir=app/propel/config/

diff:
	vendor/bin/propel diff --config-dir=app/propel/config/

config:
	vendor/bin/propel config:convert --config-dir=app/propel/config/ --output-dir=app/config --output-file=propel-connection.php
