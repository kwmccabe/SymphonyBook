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


.PHONY: tests
tests:
	symfony console doctrine:database:drop --force --env=test || true
	symfony console doctrine:database:create --env=test
	symfony console doctrine:migrations:migrate -n --env=test
	symfony console doctrine:fixtures:load -n --env=test
	symfony php bin/phpunit $(MAKECMDGOALS)


.PHONY: spa-start spa-stop spa-build spa-status
spa-start:
	cd spa; symfony server:start -d --passthru=index.html

spa-stop:
	cd spa; symfony server:stop

spa-build:
	cd spa; API_ENDPOINT=`symfony var:export SYMFONY_PROJECT_DEFAULT_ROUTE_URL --dir=..` ./node_modules/.bin/encore dev

spa-status:
	cd spa; symfony server:status


.PHONY: doit
doit:
	@echo $(PATH)
	@ls -la ~/.docker/run/docker.sock


