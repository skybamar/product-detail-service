# Product Detail Service

Solution of the Logio PHP assignment: an endpoint that returns product data as JSON,
backed by a switchable data source (ElasticSearch / MySQL), a cache and a request
counter — each replaceable purely through configuration.

## Requirements

Docker with Compose. Nothing else — PHP and Composer run inside the container.

## Quick start

```bash
docker compose build
docker compose run --rm app composer install
docker compose up -d

curl http://localhost:8088/product/abc-123
```

The port is `8088` to avoid the common `8080` collisions.

### Switching the data source

```bash
PRODUCT_SOURCE=mysql docker compose up -d
curl http://localhost:8088/product/some-new-id
```

The in-memory driver stand-ins include a `source` field in the returned data so the
switch is visible in the response. Note that previously cached products are still
served from the cache — the assignment defines the cache as infinite and never
invalidated.

### Running tests and static analysis

```bash
docker compose run --rm app vendor/bin/phpunit
docker compose run --rm app vendor/bin/phpstan analyse
```

Both also run in CI (GitHub Actions) on every push to `main` and on every pull request.

## Configuration

Defaults live in `.env`; override them with real environment variables or `.env.local`.

| Variable | Default | Meaning |
|---|---|---|
| `PRODUCT_SOURCE` | `elasticsearch` | Data source: `elasticsearch` or `mysql` |
| `PRODUCT_CACHE_DIR` | `var/storage/product-cache` | Directory for cached products (one JSON file per product) |
| `PRODUCT_COUNTER_FILE` | `var/storage/product-request-counts.txt` | Plain-text file with `id → request count` pairs |

## Architecture

```
ProductController  (HTTP/JSON only)
        │
ProductDetailService  (the assignment workflow)
        │
        ├── ProductCache          ── FileProductCache
        ├── ProductRepository     ── ElasticSearchProductRepository ── IElasticSearchDriver
        │       (via factory)     └─ MySqlProductRepository         ── IMySQLDriver
        └── ProductRequestCounter ── FileProductRequestCounter
```

The core in `src/Product/` has no dependency on Symfony. The framework layer is
deliberately thin: routing to the controller, the DI container wiring in
`config/services.yaml`, and `JsonResponse`.

**Workflow** (as specified): try the cache → on miss ask the repository and cache the
result → increment the request counter (every request, including cache hits) → return
JSON.

### Design decisions

- **Own narrow interfaces** (`ProductRepository`, `ProductCache`, `ProductRequestCounter`)
  instead of generic ones: each declares exactly what the service needs (ISP). New
  technology = new implementation + a one-line configuration change (OCP); the service
  never changes.
- **Adapters** unify the two incompatible driver contracts (`findById` vs
  `findProduct`) behind `ProductRepository`. The driver interfaces are kept verbatim
  from the assignment — adding native types to them would break the existing drivers
  (return types are covariant).
- **`FileProductCache`**: cache keys are hashed (a request id cannot escape the cache
  directory), writes are atomic (temp file + `rename`), corrupted entries degrade to a
  cache miss and heal on the next request.
- **`FileProductRequestCounter`**: the read-modify-write cycle holds an exclusive
  `flock`, so concurrent requests cannot lose increments; ids are urlencoded so
  delimiter characters cannot corrupt the format. Rewriting the whole file is O(n) —
  consciously accepted as the "plain text is enough for now" stage, and so is the
  fact that the exclusive lock serializes all requests across all products. A process
  killed between truncate and write can still empty the file. All three limits
  disappear with the Redis/SQL counter below.
- **Failures of the cache (reads and writes) or the counter are logged and never
  break the customer response** — losing a marketing metric is cheaper than losing a pageview.
- **Product data are passed through as `array<string, mixed>`**: the assignment does
  not define the product structure and the service never inspects it. With a known
  schema I would introduce a `Product` value object at the adapter boundary.
- **Tests use small hand-written fakes/spies** instead of a mocking framework — with
  one-method interfaces they are shorter and more readable.

### What I would do next

- Back `ProductCache` with `symfony/cache` (Redis adapter) via a `Psr16ProductCache`
  implementation — gaining stampede protection and battle-tested edge cases; the
  interface and service stay untouched.
- Move the counter to Redis (`INCR`) or SQL (`INSERT … ON DUPLICATE KEY UPDATE
  count = count + 1`) — both are atomic and O(1) per request, unlike the file.
- A `Product` value object (denormalized with symfony/serializer or cuyz/valinor) once
  the product schema is known.
- A functional test of the endpoint (`WebTestCase`), a production image (multi-stage
  build, php-fpm, opcache) and a health check.

## AI-assisted workflow

The project was built with Claude Code under a strict review process: every step was
proposed, explained (including rejected alternatives) and approved before being
committed, commit by commit. All design decisions above were made or explicitly
signed off by me — and I can defend any of them.