# enriquecimentocadastralpj

## API de consulta de empresas

A API consulta a [BrasilAPI](https://brasilapi.com.br/) e mantém uma cópia dos
dados cadastrais por equipe. As rotas exigem uma sessão autenticada e verificada
da aplicação; cada usuário só pode acessar equipes das quais participa.

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