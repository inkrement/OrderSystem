composer = composer

all: install

install: load_deps generate_files

load_deps:
	# hotfix to load deps
	mkdir src/domain
	$(composer) install

generate_files: reset
	vendor/bin/propel config:convert --output-dir=config --config-dir=config
	vendor/bin/propel model:build --output-dir=src/domain --config-dir=config
	vendor/bin/propel sql:build --output-dir=src/sql --config-dir=config
	vendor/bin/propel sql:insert --sql-dir=src/sql --config-dir=config
	$(composer) dump-autoload

reset:
	rm -rf ./src/sql
	rm -rf ./shop.db
	rm -rf ./src/domain
	rm -rf ./config/config.php
	rm -rf ./log/*

clean: reset
	rm -rf ./vendor
	rm -rf ./composer.lock

.PHONY: install generate_files load_deps install reset
