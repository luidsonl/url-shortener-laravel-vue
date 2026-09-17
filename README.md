# URL Shortener - Laravel 12 & Vue.js

Sistema de encurtamento de URLs focado em performance e escalabilidade.

## Estrutura do Projeto / Project Structure

* [Backend (API)](./docs/api.md): Laravel 12, Redis, PostgreSQL.

---

## PT-BR

### Rodando com Docker
O projeto está totalmente Dockerizado para facilitar o desenvolvimento e deploy.

#### Pré-requisitos
* Docker e Docker Compose instalados.

#### Como iniciar
```bash
# Clone o repositório
git clone <repo-url>
cd url-shortener-laravel-vue

# Suba os containers (build inicial pode levar alguns minutos)
docker compose up -d --build
```

#### Serviços disponíveis
* **Frontend**: `http://localhost:5173`
* **API (Nginx)**: `http://localhost:8000`
* **Swagger UI**: `http://localhost:8000/api/documentation`
* **PostgreSQL**: Porta `5432`
* **Redis**: Porta `6379`

#### Comandos úteis
```bash
# Ver logs do backend
docker compose logs -f app

# Executar comandos artisan
docker compose exec app php artisan migrate

# Ver logs do worker de filas
docker compose logs -f queue
```

### Principais funcionalidades
* **Performance**: Redirecionamento sub-milissegundo com cache em Redis.
* **Resiliência**: Processamento de métricas e contagem de acessos via Jobs em segundo plano.
* **Segurança**: Autenticação via Sanctum/JWT, verificação de e-mail e recuperação de senha.
* **Gestão**: Controle de expiração de links e painel administrativo completo.

---

## EN

### Running with Docker
The project is fully Dockerized for ease of development and deployment.

#### Prerequisites
* Docker and Docker Compose installed.

#### How to start
```bash
# Clone the repository
git clone <repo-url>
cd url-shortener-laravel-vue

# Start containers (initial build may take a few minutes)
docker compose up -d --build
```

#### Available Services
* **Frontend**: `http://localhost:5173`
* **API (Nginx)**: `http://localhost:8000`
* **Swagger UI**: `http://localhost:8000/api/documentation`
* **PostgreSQL**: Port `5432`
* **Redis**: Port `6379`

#### Useful Commands
```bash
# View backend logs
docker compose logs -f app

# Run artisan commands
docker compose exec app php artisan migrate

# View queue worker logs
docker compose logs -f queue
```

### Key Features
* **Performance**: Sub-millisecond redirection using Redis caching.
* **Resilience**: Metrics and access counting processed via background Jobs.
* **Security**: Sanctum/JWT authentication, email verification, and password recovery.
* **Management**: Link expiration control and comprehensive admin dashboard.