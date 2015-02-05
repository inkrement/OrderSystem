composer = composer

all: install

install: load_deps generate_files

load_deps:
	# hotfix to load deps
	mkdir domain
	$(composer) install

generate_files:
	vendor/bin/propel config:convert --output-dir=config
	vendor/bin/propel model:build --output-dir=domain
	vendor/bin/propel sql:build --output-dir=sql
	vendor/bin/propel sql:insert --sql-dir=sql
	$(composer) dump-autoload

clean:
	rm -rf ./domain
	rm -rf ./log/*
	rm -rf ./sql
	rm -rf ./shop.db
	rm -rf ./vendor
	rm -rf ./composer.lock
	rm -rf ./config/config.php

.PHONY: install generate_files load_deps install
