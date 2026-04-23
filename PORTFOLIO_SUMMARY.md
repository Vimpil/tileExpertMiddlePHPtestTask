# MLH Portfolio Submission — Tile Expert Order Management

## Summary

I stabilized the test suite for the Tile Expert Order Management application (Symfony 7.2), a multi-endpoint API for managing tile orders, pricing, and full-text search via Manticore Search.

**Status:** ✅ All 10 PHPUnit tests passing (56 assertions) in Docker PHP 8.3
**Tests:** `tests/Controller/OrderControllerTest.php` + `tests/Controller/PriceControllerTest.php`  
**Commits:** 5 clean, atomic changes (see below)

---

## What I Fixed

### 1. **Controller Test Isolation** (Commit `eae9bb1`)
- Converted WebTestCase (kernel-dependent) tests to pure PHPUnit TestCase with direct controller instantiation.
- Mocked all external services (Doctrine, SearchService, Connection, ValidatorInterface).
- Tests no longer require a running Manticore instance or populated database.
- Added negative-path coverage: invalid XML content-type, malformed XML, invalid query parameters.

**Impact:** Tests run in <4s, deterministic, no external dependency coupling.

### 2. **Container Response Decoupling** (Commit `eae9bb1`)
- Removed `AbstractController::json()` call in `OrderController::createOrder()`.
- Replaced with direct `JsonResponse` instantiation.
- Controllers now work outside Symfony kernel context, enabling simpler unit testing.

### 3. **Request Validation Hardening** (Commit `bf1fcad`)
- `PriceController::getPrice()` now explicitly casts query params to strings with empty-string defaults.
- `PriceRequest` DTO converted from old-style DocBlock constraints to PHP 8 attributes.
- Validation fails early with user-facing error messages (tested in `testGetPriceRejectsInvalidParameters`).

### 4. **Entity Mapping Alignment** (Commit `bf1fcad`)
- `OrdersArticle` entity now properly imports `OrdersArticleRepository` class.
- Ensures Doctrine mapping stays in sync with test fixtures.

### 5. **Environment & Documentation** (Commits `c885c28`, `b98930b`)
- `.env` and `.env.test` aligned with Docker runtime (MySQL 8 ready, Manticore ports, test DB URL).
- `README.md` now clearly documents deterministic test strategy, actual file paths, and Docker-based verification workflow.

---

## Commit History

```
a26b416 Add GitHub Actions workflow for automated PHPUnit test runs
bf1fcad Harden price request validation and align OrdersArticle mapping
b98930b Polish README with deterministic test strategy and path fixes
c885c28 Align environment configuration for Docker-based test runtime
eae9bb1 Stabilize controller tests with isolated unit coverage
```

---

## How to Verify

### Local Docker (Recommended)

```bash
docker compose run --rm --no-deps php php vendor/bin/phpunit --testdox
```

**Expected output:**
```
PHPUnit 12.1.5 by Sebastian Bergmann

Runtime:       PHP 8.3.30
Configuration: /var/www/html/phpunit.dist.xml

..........                                                        10 / 10 (100%)

Time: ~4s, Memory: 10.00 MB

Order Controller (App\Tests\Controller\OrderController)
 ✔ Check privileges
 ✔ Get one order
 ✔ Get order stats
 ✔ Get order stats rejects invalid group by
 ✔ Create order
 ✔ Create order rejects invalid content type
 ✔ Create order rejects malformed xml
 ✔ Search orders

Price Controller (App\Tests\Controller\PriceController)
 ✔ Get price
 ✔ Get price rejects invalid parameters

OK (10 tests, 56 assertions)
```

### CI/CD (GitHub Actions)

Tests automatically run on every push via `.github/workflows/tests.yml`. View results in the GitHub Actions tab.

---

## Why This Matters

**Before:**
- Tests required live Manticore, populated MySQL, and kernel bootstrapping.
- Doctrine deprecation warnings (MySQL < 8) cluttered output.
- Tests leaked external dependencies; hard to run offline or in CI.

**After:**
- Tests are **deterministic and isolated**.
- All external services mocked; tests run in <4s offline.
- Controller logic verified without infrastructure coupling.
- Negative paths covered (validation failures, malformed input).
- Clear documentation for future contributors and recruiters.

---

## Technical Highlights

### Key Changes

1. **Direct controller instantiation** (no WebTestCase kernel boot)
   ```php
   $controller = new OrderController($mockedRegistry);
   $response = $controller->getOrder(42);
   ```

2. **Service mocking** (Doctrine, Manticore, validators)
   ```php
   $registry = $this->createMock(ManagerRegistry::class);
   $registry->method('getRepository')->willReturn($mockRepository);
   ```

3. **Request/response testing** (Symfony Request/Response objects, not HTTP)
   ```php
   $request = Request::create('/orders/stats', 'GET', ['group_by' => 'month']);
   $response = $controller->getOrderStats($request);
   ```

4. **Validation testing** (negative paths for bad input)
   ```php
   // Ensure validator mock returns errors for blank factory
   $validator->method('validate')->willReturn(new ConstraintViolationList([...]));
   $response = $controller->getPrice($request, $validator);
   $this->assertSame(400, $response->getStatusCode());
   ```

### Files Modified

- `src/Controller/OrderController.php` — Use `JsonResponse` directly
- `src/Controller/PriceController.php` — Explicit casting, default empty strings
- `tests/Controller/OrderControllerTest.php` — 269 lines of deterministic tests (was 165, now with negative paths)
- `tests/Controller/PriceControllerTest.php` — Added validation failure test
- `src/DTO/PriceRequest.php` — Modernized to PHP 8 attributes
- `src/Entity/OrdersArticle.php` — Fixed Doctrine mapping import
- `config/packages/doctrine.yaml`, `.env`, `.env.test`, `docker-compose.yml` — Runtime alignment
- `README.md` — Corrected paths, added test strategy section
- `.github/workflows/tests.yml` — CI/CD automation (new)

---

## Future Enhancements (Optional)

1. **Integration test suite** (optional, separate from default run):
   - Spin up real MySQL + Manticore for end-to-end validation
   - Run only on CI, not locally

2. **Code coverage reporting**:
   - Add `--coverage-html` to CI workflow
   - Track coverage trends over time

3. **Additional validation**:
   - PHP linting (phpstan, phpcs)
   - Type checking for DTO/entity properties

---

## Questions?

See `README.md` for full setup, endpoint documentation, and troubleshooting.

