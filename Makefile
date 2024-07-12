SHELL := /bin/bash

start:
	docker compose up -d
	symfony server:start -d
.PHONY: start

stop:
	symfony server:stop
	docker compose down
.PHONY: stop

migration:
	./bin/console make:migration
.PHONY: migration

migrate:
	./bin/console doctrine:migrations:migrate
.PHONY: migrate

tests:
	symfony console doctrine:database:drop --force --env=test || true
	symfony console doctrine:database:create --env=test
	symfony console doctrine:migrations:migrate -n --env=test
	symfony console doctrine:fixtures:load -n --env=test
	symfony php bin/phpunit $(MAKECMDGOALS)
.PHONY: tests

