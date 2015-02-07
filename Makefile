composer = composer

all: install

install: load_deps generate_files

load_deps:
	# hotfix to load deps
	mkdir src/domain
	$(composer) install

generate_files:
	vendor/bin/propel config:convert --output-dir=config
	vendor/bin/propel model:build --output-dir=src/domain
	vendor/bin/propel sql:build --output-dir=src/sql
	vendor/bin/propel sql:insert --sql-dir=src/sql
	$(composer) dump-autoload

reset:
	rm -rf ./shop.db
	rm -rf ./log/*

clean: reset
	rm -rf ./src/domain
	rm -rf ./src/sql
	rm -rf ./vendor
	rm -rf ./composer.lock
	rm -rf ./config/config.php

.PHONY: install generate_files load_deps install reset
