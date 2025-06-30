# Ambiente Docker per Site War

Questo ambiente Docker fornisce un'installazione pronta all'uso dell'applicazione Site War.

## Requisiti
- Docker
- Docker Compose

## Avvio dell'Ambiente

Per avviare l'ambiente di sviluppo, seguire questi passi:

1. Assicurarsi di avere Docker e Docker Compose installati
2. Aprire un terminale nella directory principale del progetto (dove si trova il file `docker-compose.yml`)
3. Eseguire il comando:

```bash
docker-compose up -d
```

4. L'applicazione sarà disponibile all'indirizzo http://localhost:8080

## Arresto dell'Ambiente

Per fermare l'ambiente, eseguire:

```bash
docker-compose down
```

## Logs

I log di Apache saranno disponibili nella directory `docker/logs`.

## Modifica delle API Keys

Le API keys sono configurate nel file `/server/config/api_keys.php`. Questo file è mappato come volume nel container, quindi tutte le modifiche fatte al file locale si rifletteranno automaticamente nel container.

## Troubleshooting

### Permessi Directory Cache

Se si verificano problemi di permessi con le directory di cache, eseguire:

```bash
docker-compose exec site-war chmod -R 777 /var/www/html/server/cache
```

### Verifica delle API Keys

Per verificare che le API keys siano caricate correttamente:

```bash
docker-compose exec site-war php -r "print_r(require('/var/www/html/server/config/api_keys.php'));"
```

### Pulizia della Cache

Per pulire la cache:

```bash
docker-compose exec site-war rm -rf /var/www/html/server/cache/data/*
docker-compose exec site-war rm -rf /var/www/html/server/cache/ratelimit/*
```