.PHONY: build

BOX_BIN := $(shell composer global config bin-dir --absolute 2>/dev/null)/box

build:
	composer install --prefer-dist --optimize-autoloader --no-interaction
	composer global require humbug/box --no-interaction
	rm -rf vendor
	composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
	$(BOX_BIN) compile
