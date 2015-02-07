all: install

composer.phar:
	curl -sS https://getcomposer.org/installer | php

install: load_deps generate_files

load_deps: composer.phar
	# hotfix to load deps
	mkdir src/domain
	php composer.phar install

generate_files: reset composer.phar
	vendor/bin/propel config:convert --output-dir=config --config-dir=config
	vendor/bin/propel model:build --output-dir=src/domain --config-dir=config
	vendor/bin/propel sql:build --output-dir=src/sql --config-dir=config
	vendor/bin/propel sql:insert --sql-dir=src/sql --config-dir=config
	php composer.phar dump-autoload

reset:
	rm -rf ./src/sql
	rm -rf ./shop.db
	rm -rf ./src/domain
	rm -rf ./config/config.php
	rm -rf ./log/*

clean: reset
	rm -rf ./vendor
	rm -rf ./composer.lock
	rm -rf ./composer.phar

.PHONY: install generate_files load_deps install reset
