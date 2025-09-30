
# API Restful

This repository contains a production-ready RESTful API built with modern PHP, following clean architecture, SOLID principles, and best practices for security, performance, and maintainability.


## Key features

- JWT Authentication (stateless)
- Rate Limiting (Redis-based)
- PostgreSQL Database (with PDO)
- Redis Caching
- Dockerized Environment
- GitHub Actions CI
- Unit & Integration Tests (PHPUnit)
- PSR Standards (PSR-4, PSR-7, PSR-11, PSR-15)
- Security Headers (CSP, HSTS, XSS Protection)
- Validation & Error Handling

## Technologies & Tools

**Backend:** PHP 8.3, Laminas (Diactoros, HttpHandlerRunner)

**Database:** PostgreSQL

**Caching:** Redis (Predis)

**Auth:** JWT

**Testing:** PHPUnit, Guzzle (for API tests)

**CI/CD:** Github Actions, Docker

**Security:** Rate Limiting, CSRF Protection, Input Validation

**Dependency Management:** Composer, DI (PHP-DI)

**Code Standards:** PSR-4, PSR-7, PSR-11, PSR-15

**Observability:** Grafana, Prometheus



## 📖 Documentation

- [`/documentation.php`](./documentation.php) → Gateway API 

Endpoints:  

```http
GET   /api/v1/token
GET    /api/v1/customers
GET    /api/v1/customers/{id}
POST   /api/v1/customers
PUT    /api/v1/customers/{id}
DELETE /api/v1/customers/{id}
```

## Running Locally

Clone the project

```bash
  git clone https://github.com/thmachado/gateway-api.git
```

Enter in the project directory

```bash
  cd gateway-api
```

Create a local `.env` from the example and put your secrets:

```bash
cp .env.example .env
```

Running with Docker

```bash
  docker compose up -d --build
```

Running tests

```bash
  docker compose exec server ./vendor/bin/phpunit tests
```

## Licença

[MIT](https://choosealicense.com/licenses/mit/)
