#!make
include .env
include .env.local

SHELL := /bin/bash


.PHONY: start stop status vars open
start: start-docker start-server start-messenger

stop:
	symfony server:stop
	docker compose down

status:
	symfony console messenger:failed:show
	docker compose ps
	symfony server:status

vars:
	symfony var:export --debug

open:
	symfony open:local


.PHONY: start-docker start-server start-messenger npm-watch
start-docker:
	docker compose up -d

start-server:
	symfony server:start -d

# symfony console messenger:consume async -vv
# symfony console messenger:failed:show
# symfony console messenger:failed:retry

start-messenger:
	symfony run -d --watch=config,src,templates,vendor/composer/installed.json symfony console messenger:consume async


.PHONY: npm-build npm-watch
npm-build:
	symfony run npm run dev

npm-watch:
	symfony run -d npm run watch


.PHONY: cache-clear cache-purge
cache-clear:
	rm -rf var/cache/dev/http_cache/

cache-purge:
	curl -s -I -X PURGE -u admin:admin `symfony var:export SYMFONY_PROJECT_DEFAULT_ROUTE_URL`admin/http-cache/
	curl -s -I -X PURGE -u admin:admin `symfony var:export SYMFONY_PROJECT_DEFAULT_ROUTE_URL`admin/http-cache/conference_header


.PHONY: start-docker-socket stop-docker-socket
start-docker-socket:
	sudo ln -s ~/.docker/run/docker.sock /var/run/docker.sock

stop-docker-socket:
	sudo rm /var/run/docker.sock


.PHONY: db-connect db-reload
# fails: symfony run psql
# works: symfony run psql app app -h 127.0.0.1 -p 5432    (!ChangeMe!)
# works: symfony run psql app app -h 0.0.0.0 -p 5432      (!ChangeMe!)
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

.PHONY: translation
translation:
	symfony console translation:extract es --force --domain=messages
	symfony console translation:extract en --force --domain=messages


.PHONY: tests
tests:
	symfony console doctrine:database:drop --force --env=test || true
	symfony console doctrine:database:create --env=test
	symfony console doctrine:migrations:migrate -n --env=test
	symfony console doctrine:fixtures:load -n --env=test
	symfony php bin/phpunit $(MAKECMDGOALS)


.PHONY: spa-start spa-stop spa-status spa-build spa-open
spa-start:
	cd spa; symfony server:start -d --passthru=index.html

spa-stop:
	cd spa; symfony server:stop

spa-status:
	cd spa; symfony server:status

spa-build:
	cd spa; API_ENDPOINT=`symfony var:export SYMFONY_PROJECT_DEFAULT_ROUTE_URL --dir=..` ./node_modules/.bin/encore dev

spa-open:
	cd spa; symfony open:local


.PHONY: doit
doit:
	@echo $(PATH)
	@ls -la ~/.docker/run/docker.sock


