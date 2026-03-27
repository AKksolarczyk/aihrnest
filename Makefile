.PHONY: db-create db-drop db-migrate db-fixtures db-reset

db-create:
	docker compose exec app php bin/console doctrine:database:create --if-not-exists

db-drop:
	docker compose exec app php bin/console doctrine:database:drop --if-exists --force

db-migrate:
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

db-fixtures:
	docker compose exec app php bin/console doctrine:fixtures:load --no-interaction

db-reset: db-drop db-create db-migrate db-fixtures
