.PHONY: build

PHP_BIN ?= php
BOX_PHP ?= $(PHP_BIN)
COMPOSER_BIN ?= $(shell command -v composer)
BOX_VERSION ?= 4.6.2
BOX_COMPOSER_HOME := $(if $(strip $(COMPOSER_HOME)),$(COMPOSER_HOME),$(shell mktemp -d))
BOX_BIN := $(shell COMPOSER_HOME="$(BOX_COMPOSER_HOME)" $(COMPOSER_BIN) global config bin-dir --absolute 2>/dev/null)/box

ifeq ($(UPDATE_LOCK),true)
COMPOSER_ACTION := update
COMPOSER_ACTION_FLAGS := --with-all-dependencies
else
COMPOSER_ACTION := install
COMPOSER_ACTION_FLAGS :=
endif

build:
	rm -rf vendor
	$(PHP_BIN) $(COMPOSER_BIN) $(COMPOSER_ACTION) --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress $(COMPOSER_ACTION_FLAGS)
	COMPOSER_HOME="$(BOX_COMPOSER_HOME)" $(BOX_PHP) $(COMPOSER_BIN) global require humbug/box:$(BOX_VERSION) --with-all-dependencies --no-interaction
	$(BOX_PHP) $(BOX_BIN) compile

pint:
	$(PHP_BIN) $(COMPOSER_BIN) global require laravel/pint --with-all-dependencies --no-interaction
	$(PHP_BIN) $(COMPOSER_BIN) global exec pint -- --test
