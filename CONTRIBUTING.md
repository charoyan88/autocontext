# Contributing

## Scope

Auto-Context is still an MVP. Prefer small, focused pull requests that improve correctness, documentation, test coverage, or developer experience without broad refactors.

## Local setup

```bash
./scripts/start_mvp.sh
```

If you need a manual flow, use:

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec vite npm install
docker compose exec vite npm run build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan demo:seed
```

## Before opening a PR

- Run `docker compose exec app php artisan test`
- Keep changes scoped to the task
- Update `README.md` when startup, ports, credentials, or feature status changes
- Document optional or incomplete subsystems clearly instead of presenting them as stable

## Notes

- ClickHouse and Kafka-related pieces are currently optional and should be treated as in-progress unless the change explicitly finishes and documents them
- Do not commit real secrets or environment-specific hostnames
