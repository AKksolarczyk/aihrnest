# Docker local setup

Minimalny setup uruchamia Symfony za prostym Traefikiem lokalnie.

Adresy:
- aplikacja: `http://smartdesk.localhost`
- dashboard Traefika: `http://localhost:8080`
- Mailpit: `http://mailpit.localhost` albo `http://localhost:8025`
- PostgreSQL: `localhost:5432`

Start:

```bash
docker compose up --build
```

Zatrzymanie:

```bash
docker compose down
```

Przydatne komendy:

```bash
docker compose exec app php bin/console about
docker compose exec app composer install
docker compose exec app php bin/console doctrine:database:create --if-not-exists
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

Domyslne konta z fixtures:

```text
anna.kowalska@example.com / password123
piotr.nowak@example.com / password123
```

Uwagi:
- setup używa wbudowanego serwera PHP, więc jest dobry na lokalny start, ale nie jako docelowy runtime
- Traefik nasłuchuje na porcie `80`, więc jeśli port jest zajęty, trzeba go zwolnić albo zmienić mapowanie w `compose.yaml`
- kontener `app` ma ustawiony `DATABASE_URL` wskazujacy na serwis `postgres`
- rejestracja wysyla mail aktywacyjny przez Mailpit; bez klikniecia linku konto nie zaloguje sie do aplikacji
