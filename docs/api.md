# PT-BR
---
# URL Shortener API

API para encurtamento de links desenvolvida com Laravel 12, projetada para alta carga através de processamento assíncrono e cache.

## Funcionalidades Técnicas

* Gerenciamento de Usuários e Autenticação:
    * Sistema de autenticação flexivel (Sanctum ou JWT).
    * Fluxo de verificação de e-mail para novos usuários.
    * Recuperação de senha com envio de tokens por e-mail.
    * Níveis de acesso para administradores e usuários comuns.

* Gerenciamento de Shortlinks:
    * Criação, edição e exclusão de links encurtados.
    * Possibilidade de definir data de expiração para os links.
    * Ações em lote para exclusão de múltiplos registros.

* Performance e Escalabilidade:
    * Cache com Redis: Os links são armazenados em cache para evitar consultas repetitivas ao banco de dados.
    * Background Jobs: A contagem de acessos e analytics são processados via filas, garantindo que o redirecionamento seja instantâneo para o usuário.

* Comunicação e Documentação:
    * Envio de e-mails automáticos (Boas-vindas e Verificação).
    * Documentação interativa via Swagger UI.
    * Suíte de testes automatizados com PHPUnit.


# EN
---
# URL Shortener API

Robust link-shortening API built with Laravel 12, designed for high traffic using asynchronous processing and caching.

## Technical Features

* User Management and Authentication:
    * Flexible authentication system (Sanctum or JWT).
    * Email verification flow for new accounts.
    * Password recovery system with automated emails.
    * Role-based access control (Admin and Regular Users).

* Shortlink Management:
    * Full CRUD for shortened links.
    * Link expiration support.
    * Bulk actions for efficient record management.

* Performance and Scalability:
    * Redis Caching: Links are cached to bypass database overhead during resolution.
    * Background Jobs: Access counting and analytics are handled via queues to ensure non-blocking redirects.

* Communication and Documentation:
    * Automated email system (Welcome and Verification).
    * Interactive API documentation via Swagger UI.
    * Comprehensive test suite using PHPUnit.

# Tech Stack
- Laravel 12
- Redis (Cache & Queues)
- PHPUnit
- Swagger (L5-Swagger)

---
# API Endpoints

## Public
-   `GET /{code}` - Redirect to the original URL.
-   `GET /health` - Check system status.
-   `GET /api/documentation` - Swagger.

## Authentication
-   `POST /api/auth/register` - Register a new user.
-   `POST /api/auth/login` - Authenticate and receive a token.
-   `POST /api/auth/logout` - Revoke current session.
-   `GET /api/auth/user` - Get authenticated user details.
-   `POST /api/auth/validate-token` - Check token validity.
-   `POST /api/forgot-password` - Request reset link.
-   `POST /api/reset-password` - Execute password reset.

## User Profile
-   `GET /api/profile` - View your profile.
-   `PUT /api/profile` - Update profile data.
-   `DELETE /api/profile` - Delete your account.

## Short Links (Authenticated)
-   `GET /api/short-links` - List your links.
-   `POST /api/short-links` - Create a new short link.
-   `GET /api/short-links/{id}` - View link details.
-   `PUT /api/short-links/{id}` - Update a link.
-   `DELETE /api/short-links/{id}` - Delete a link.
-   `POST /api/short-links/bulk-delete` - Delete multiple links.

## Admin Tools
-   `GET /api/users` - List all users.
-   `POST /api/users` - Create a new user.
-   `GET /api/users/{id}` - View user details.
-   `PUT /api/users/{id}` - Modify user data.
-   `DELETE /api/users/{id}` - Remove a user.

---

## License

The project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
