#!make
include .env
include .env.local

SHELL := /bin/bash


.PHONY: start status stop

start: start-docker start-server start-messenger

status:
	symfony console messenger:failed:show
	docker compose ps
	symfony server:status

stop:
	symfony server:stop
	docker compose down


.PHONY: start-docker start-server start-messenger

start-docker:
	docker compose up -d

start-server:
	symfony server:start -d

# foreground: symfony console messenger:consume async -vv
start-messenger:
	symfony run -d --watch=config,src,templates,vendor/composer/installed.json symfony console messenger:consume async



.PHONY: db-connect db-reload

# fails: symfony run psql
# works: symfony run psql app app -h 0.0.0.0 -p 5432    (!ChangeMe!)
db-connect:
	docker compose exec database psql $(POSTGRES_DB) $(POSTGRES_USER)

db-reload:
	symfony console doctrine:database:drop --force --env=dev || true
	symfony console doctrine:database:create --env=dev
	symfony console doctrine:migrations:migrate -n --env=dev
	symfony console doctrine:fixtures:load -n --env=dev

.PHONY: migration migrate

migration:
	./bin/console make:migration

migrate:
	./bin/console doctrine:migrations:migrate


.PHONY: tests

tests:
	symfony console doctrine:database:drop --force --env=test || true
	symfony console doctrine:database:create --env=test
	symfony console doctrine:migrations:migrate -n --env=test
	symfony console doctrine:fixtures:load -n --env=test
	symfony php bin/phpunit $(MAKECMDGOALS)


.PHONY: test
test:
	@echo $(MAKECMDGOALS)

