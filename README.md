# Enriquecimento Cadastral PJ

## Executar com Docker

Pré-requisito: Docker Desktop instalado e aberto.

No terminal, na pasta do projeto:

```cmd
copy .env.docker.example .env.docker
docker compose --env-file .env.docker up --build
```

Acesse http://localhost:8000.

Para parar os containers, pressione `Ctrl+C` ou execute:

```cmd
docker compose --env-file .env.docker down
```
