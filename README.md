# URL Shortener - Laravel 12 & Vue.js

Sistema de encurtamento de URLs focado em performance e escalabilidade.

## Stack / Tech Stack

* **Backend:** Laravel 12, Sanctum/JWT, PostgreSQL ou SQLite.
* **Frontend:** Vue 3 (SPA) + Vue Router + Pinia, integrado ao Blade via Vite.
* **Estilos:** Tailwind CSS v4.
* **Cache/Filas:** Redis (produção) ou drivers locais (desenvolvimento).

## Estrutura do Projeto / Project Structure

* [Backend (API)](./docs/api.md): Laravel 12, autenticação, jobs em segundo plano e documentação Swagger.
* **Frontend** (`resources/js/`): SPA Vue montada em `resources/views/app.blade.php`.
* [Docker](./docker-compose.yml): stack completa (Nginx, php-fpm, fila, PostgreSQL e Redis) + [Dockerfile](./Dockerfile) multi-stage com build do frontend.

---

## PT-BR

### Desenvolvimento local (sem Docker)

#### Pré-requisitos
* PHP **8.2+** com as extensões `bcmath` (ou `gmp`), `pdo_sqlite`, `mbstring`, `openssl`.
* Composer e Node.js (com npm) instalados.

#### Como iniciar
```bash
# Banco local (SQLite)
cp .env.example .env
# edite o .env:
#   DB_CONNECTION=sqlite
#   DB_DATABASE=/caminho/absoluto/do/projeto/database/database.sqlite
#   CACHE_STORE=file
#   QUEUE_CONNECTION=database
#   SESSION_DRIVER=file

# Backend
composer install
php artisan key:generate
php artisan migrate

# Dependências do frontend
npm install

# Terminal 1 - API (Laravel)
php artisan serve

# Terminal 2 - worker de filas (contagem de visitas)
php artisan queue:work --sleep=1 --tries=3

# Terminal 3 (opcional) - Vite em modo desenvolvimento
npm run dev
```

> A extensão `bcmath` é obrigatória (o Hashids depende dela). No Ubuntu/PHP ondrej:
> `sudo apt-get install -y php8.4-bcmath`

#### Produção de assets
Para servir com os assets compilados (sem `npm run dev`), rode `npm run build`.

#### Acessos
* **Frontend (SPA)**: `http://localhost:8000` (o Vue Router cuida de `/login`, `/register`, `/dashboard`, `/profile`)
* **API**: `http://localhost:8000/api`
* **Swagger UI**: `http://localhost:8000/api/documentation`

### Rodando com Docker
Tudo já está empacotado em um `docker-compose.yml`: a SPA Vue é compilada no build da imagem (multi-stage) e o Laravel roda em um único container com **Nginx + php-fpm via Supervisor**. Fila e cache usam Redis. As migrações rodam automaticamente na primeira subida.

#### Pré-requisitos
* Docker e Docker Compose instalados.

#### Como iniciar
```bash
git clone <repo-url>
cd url-shortener-laravel-vue
docker compose up -d --build
```

Não é necessário copiar `.env`, gerar `APP_KEY` ou rodar `migrate` manualmente — o entrypoint faz isso (`APP_KEY` é gerado apenas se estiver ausente; migrações rodam somente no container `app`).

#### Serviços disponíveis
* **Frontend (SPA) + API (Nginx + php-fpm)**: `http://localhost:8000`
* **Swagger UI**: `http://localhost:8000/api/documentation`
* **Worker de filas**: sem porta exposta
* **PostgreSQL 16**: porta interna `5432`
* **Redis 7**: porta interna `6379`

#### Variáveis de ambiente (opcionais)
| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `APP_PORT` | `8000` | Porta publicada no host |
| `DOCKER_APP_URL` | `http://localhost:8000` | URL base da aplicação |
| `DOCKER_DB_PASSWORD` | `laravel` | Senha do PostgreSQL |
| `DOCKER_JWT_SECRET` | `change-me` | Secret do JWT (se `AUTH_DRIVER=jwt`) |
| `DOCKER_HASHIDS_SALT` | `change-me` | Salt do Hashids (códigos curtos) |

> O Docker Compose lê o `.env` do projeto para interpolação, e esse arquivo é o `.env` de desenvolvimento local do Laravel. Por isso as variáveis do Docker usam o prefixo `DOCKER_`, evitando conflito com `APP_URL`, `DB_PASSWORD` etc.

#### Comandos úteis
```bash
docker compose ps                          # status dos serviços
docker compose logs -f app                 # logs da aplicação
docker compose logs -f queue               # logs do worker de fila
docker compose exec app php artisan migrate # rodar migrações manualmente
docker compose exec app php artisan tinker  # shell interativo
docker compose down                        # parar (mantém volumes)
docker compose down -v                     # parar e apagar dados (db/redis/app)
```

Os dados persistem em volumes nomeados (`db_data`, `redis_data`). O primeiro usuário pode ser criado pela tela `/register` ou via `POST /api/auth/register`.

### Principais funcionalidades
* **Performance**: Redirecionamento sub-milissegundo com cache em Redis.
* **Resiliência**: Contagem de acessos e métricas processadas via filas (Jobs).
* **Segurança**: Autenticação via Sanctum/JWT, verificação de e-mail e recuperação de senha.
* **Gestão**: Controle de expiração de links, painel e gestão de perfil.

---

## EN

### Local development (without Docker)

#### Prerequisites
* PHP **8.2+** with `bcmath` (or `gmp`), `pdo_sqlite`, `mbstring`, `openssl` extensions.
* Composer and Node.js (with npm) installed.

#### Getting started
```bash
# Backend
composer install
php artisan key:generate

# Local database (SQLite)
cp .env.example .env
# edit .env:
#   DB_CONNECTION=sqlite
#   DB_DATABASE=/absolute/path/to/project/database/database.sqlite

php artisan migrate

# Frontend dependencies
npm install

# Terminal 1 - API (Laravel)
php artisan serve

# Terminal 2 - queue worker (visit counting)
php artisan queue:work --sleep=1 --tries=3

# Terminal 3 (optional) - Vite dev server
npm run dev
```

> The `bcmath` extension is required (Hashids depends on it). On Ubuntu with the ondrej PPA:
> `sudo apt-get install -y php8.4-bcmath`

#### Serving built assets
To serve pre-compiled assets (no `npm run dev`), run `npm run build`.

#### Access points
* **Frontend (SPA)**: `http://localhost:8000` (Vue Router handles `/login`, `/register`, `/dashboard`, `/profile`)
* **API**: `http://localhost:8000/api`
* **Swagger UI**: `http://localhost:8000/api/documentation`

### Running with Docker
Everything ships in a single `docker-compose.yml`: the Vue SPA is compiled at image build time (multi-stage) and Laravel runs in a single container with **Nginx + php-fpm via Supervisor**. Queues and cache use Redis. Migrations run automatically on first boot.

#### Prerequisites
* Docker and Docker Compose installed.

#### How to start
```bash
git clone <repo-url>
cd url-shortener-laravel-vue
docker compose up -d --build
```

No need to copy `.env`, generate an `APP_KEY`, or run `migrate` manually — the entrypoint handles it (`APP_KEY` is generated only when missing; migrations run only on the `app` service).

#### Available Services
* **Frontend (SPA) + API (Nginx + php-fpm)**: `http://localhost:8000`
* **Swagger UI**: `http://localhost:8000/api/documentation`
* **Queue worker**: no exposed port
* **PostgreSQL 16**: internal port `5432`
* **Redis 7**: internal port `6379`

#### Environment variables (optional)
| Variable | Default | Description |
|----------|---------|-------------|
| `APP_PORT` | `8000` | Host port to publish |
| `DOCKER_APP_URL` | `http://localhost:8000` | Application base URL |
| `DOCKER_DB_PASSWORD` | `laravel` | PostgreSQL password |
| `DOCKER_JWT_SECRET` | `change-me` | JWT secret (when `AUTH_DRIVER=jwt`) |
| `DOCKER_HASHIDS_SALT` | `change-me` | Hashids salt (short codes) |

> Docker Compose reads the project `.env` for interpolation, and that file is Laravel's local development `.env`. The Docker variables are therefore prefixed with `DOCKER_` to avoid clashing with `APP_URL`, `DB_PASSWORD`, etc.

#### Useful Commands
```bash
docker compose ps                           # service status
docker compose logs -f app                  # application logs
docker compose logs -f queue                # queue worker logs
docker compose exec app php artisan migrate # run migrations manually
docker compose exec app php artisan tinker  # interactive shell
docker compose down                         # stop (keeps volumes)
docker compose down -v                      # stop and wipe data (db/redis/app)
```

Data is persisted in named volumes (`db_data`, `redis_data`). The first user can be created from `/register` or via `POST /api/auth/register`.

### Key Features
* **Performance**: Sub-millisecond redirection using Redis caching.
* **Resilience**: Visit counting and metrics processed via background jobs.
* **Security**: Sanctum/JWT authentication, email verification, and password recovery.
* **Management**: Link expiration control, dashboard, and profile management.