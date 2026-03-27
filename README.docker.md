# Docker local setup

Minimalny setup uruchamia Symfony za prostym Traefikiem lokalnie.

Adresy:
- aplikacja: `http://smartdesk.localhost`
- dashboard Traefika: `http://localhost:8080`

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
```

Uwagi:
- setup używa wbudowanego serwera PHP, więc jest dobry na lokalny start, ale nie jako docelowy runtime
- Traefik nasłuchuje na porcie `80`, więc jeśli port jest zajęty, trzeba go zwolnić albo zmienić mapowanie w `compose.yaml`
