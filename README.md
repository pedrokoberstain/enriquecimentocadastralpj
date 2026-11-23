# enriquecimentocadastralpj

## Executar com Docker

É necessário ter o Docker Desktop instalado. No Prompt de Comando, na pasta do
projeto, copie a configuração e inicie os containers:

```cmd
copy .env.docker.example .env.docker
docker compose --env-file .env.docker up --build
```

O comando inicia a aplicação PHP, gera os assets, cria as tabelas no MySQL e
mantém os dados em volumes Docker. Acesse `http://localhost:8000`. Para parar,
use `Ctrl+C` ou execute `docker compose --env-file .env.docker down`.

Para criar uma equipe local para consultar a API, execute uma vez:

```cmd
docker compose --env-file .env.docker exec app php artisan db:seed
```

As senhas de `.env.docker.example` são apenas para desenvolvimento local. O
serviço MySQL não publica portas no computador e a aplicação fica limitada ao
acesso local.

## API de consulta de empresas

A API consulta a [BrasilAPI](https://brasilapi.com.br/) e mantém uma cópia dos
dados cadastrais por equipe. Fora do ambiente local, as rotas exigem uma sessão
autenticada e verificada; cada usuário só acessa equipes das quais participa.
No ambiente local, a autenticação é desativada para facilitar os testes. Não
use `APP_ENV=local` em produção.

Configure `BRASILAPI_URL` no ambiente (por padrão:
`https://brasilapi.com.br/api`), execute `php artisan migrate` e use o slug da
equipe nas rotas:

| Método | Rota | Ação |
| --- | --- | --- |
| `GET` | `/api/teams/{team}/companies` | Lista os registros (15 por página) |
| `POST` | `/api/teams/{team}/companies` | Consulta e salva/atualiza um CNPJ |
| `GET` | `/api/teams/{team}/companies/{cnpj}` | Exibe o último dado salvo |
| `POST` | `/api/teams/{team}/companies/{cnpj}/refresh` | Atualiza os dados na BrasilAPI |
| `DELETE` | `/api/teams/{team}/companies/{cnpj}` | Remove o registro da equipe |

Para consultar ou atualizar um cadastro:

```http
POST /api/teams/minha-equipe/companies
Content-Type: application/json

{"cnpj":"11.222.333/0001-81"}
```

O CNPJ deve passar pela validação dos dígitos verificadores. A resposta inclui
os campos principais do cadastro e o payload original da BrasilAPI em
`dados_cadastrais`, além do horário da última consulta em `atualizado_em`.
Consultas sem sucesso retornam erro HTTP e não substituem o último cadastro
válido salvo. Nos parâmetros `{cnpj}` da URL, informe os 14 dígitos sem
pontuação.

A listagem aceita os filtros opcionais `uf`, `municipio` e
`situacao_cadastral`, além de `page` e `per_page` (padrão 15, máximo 100).
Exemplo:

```http
GET /api/teams/minha-equipe/companies?uf=SP&municipio=São%20Paulo&situacao_cadastral=ATIVA&page=1&per_page=20
```

O resultado é JSON paginado, com os registros em `data` e os controles de
paginação em `links` e `meta`.