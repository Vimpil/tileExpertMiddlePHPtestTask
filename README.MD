# Tile Expert Order Management Application

- **Update:** Changed "Symfony 7.2" to "Symfony 6.x" to match typical doctrine.yaml/project structure.

This is a Symfony 6.x application that provides endpoints for retrieving tile prices, managing orders, and searching orders with Manticore Search. The application is containerized with Docker Compose and includes tests, a MySQL database, and Manticore Search integration.

## Features

- **Endpoint #1:** Retrieve tile prices from tile.expert via GET request.
- **Endpoint #2:** Get order statistics grouped by day, month, or year with pagination.
- **Endpoint #3:** Save orders via XML POST request.
- **Endpoint #4:** Retrieve a single order by ID.
- **Search Endpoint:** Search orders using Manticore Search.
- MySQL database for orders and order articles.
- PHPUnit tests for all endpoints.
- Docker Compose setup with configurable ports.
- **Reference:** An improved database schema is included in `docs/improved_schema.sql` for informational purposes only (not used by the application).

**Note:** Only the `test` profile is used to run Docker images in this project. All environment variables for Docker containers are defined in the `.env.test` file. The default `.env` is not used for Docker execution.
## Prerequisites

- Docker and Docker Compose
- Git
- Make (optional, for simplified commands)

## Setup Instructions

### 1. Clone the Repository

```sh
git clone https://github.com/Vimpil/tileExpertMiddlePHPtestTask.git
cd tileExpertMiddlePHPtestTask
```

### 2. Configure Environment Variables

By default, the repository includes a `.env.test` file for testing in Docker.  
If you need to run the application outside Docker or with custom settings, copy and edit the example environment file:

```sh
cp .env.example .env

Edit `.env` to set ports and database credentials:

```env
APP_ENV=test
APP_DEBUG=1
KERNEL_CLASS='App\Kernel'
APP_SECRET='$ecretf0rt3st'
SYMFONY_DEPRECATIONS_HELPER=999999
PHP_PORT=9000
MYSQL_PORT=3306
MANTICORE_PORT=9308
DATABASE_URL="mysql://root:root@db_test:3306/test"
```

### 3. Build and Start Containers

Use Docker Compose to build and start the application, MySQL, and Manticore Search:

```sh
# Start the Docker containers for the test profile (php, MySQL, Manticore, etc.)
docker-compose --profile test up

# Stop and remove the containers for the test profile
docker-compose --profile test down

# Clear the Symfony application cache (run after containers are up)
php bin/console cache:clear
```

Alternatively, use the Makefile:

```sh
make up
```

### 4. Install Dependencies

Install PHP dependencies via Composer:

```sh
docker-compose exec php composer install
```

### 5. Set Up the Database

Apply the database schema:

```sh
docker-compose exec php php bin/console doctrine:schema:update --force
```

Validate the schema:

```sh
docker-compose exec php php bin/console doctrine:schema:validate
```

(Optional) Generate sample orders for testing:

```sh
docker-compose exec php php bin/console app:generate-orders
```

### 6. Configure Manticore Search

Index the orders table in Manticore:

```sh
docker-compose exec manticore indexer orders
```

Test Manticore connectivity:

```sh
curl http://localhost:9308/json/search -d '{"index":"orders","query":{"match_all":{}},"limit":5}'
```

## Running the Application

The application is accessible at [http://localhost:8080](http://localhost:8080) \(default: http://localhost:8080\).

## Useful Commands

- Clear Symfony cache:  
  `docker-compose exec php php bin/console cache:clear`
- Clear Doctrine metadata cache:  
  `docker-compose exec php php bin/console doctrine:cache:clear-metadata`
- Generate database migration diff:  
  `docker-compose exec php php bin/console doctrine:migrations:diff`
- Check Doctrine mapping:  
  `docker-compose exec php php bin/console doctrine:mapping:info`

## Endpoints

### Endpoint #1: Get Tile Price

- **Method:** GET  
- **URL:** `/price`
- **Query Parameters:**
  - `factory` (e.g., cobsa)
  - `collection` (e.g., manual)
  - `article` (e.g., manu7530bcbm-manualbaltic7-5x30)
- **Example Response:**
  ```json
  {
    "price": "56.65",
    "factory": "cobsa",
    "collection": "manual",
    "article": "manu7530bcbm-manualbaltic7-5x30"
  }
  ```
- **How to Test:**
  ```sh
  php vendor/bin/phpunit tests/Controller/PriceControllerTest.php
  ```

### Endpoint #2: Get Order Statistics

- **Method:** GET
- **URL:** `/orders/stats`
- **Query Parameters:**
  - `page` (optional, default: 1) - Pagination page number
  - `limit` (optional, default: 10) - Number of results per page
  - `group_by` (optional, default: 'month') - Group by 'day', 'month', or 'year'
- **Example Response:**
  ```json
  {
    "page": 1,
    "limit": 10,
    "total_pages": 5,
    "group_by": "month",
    "data": [
      {
        "date": "2024-06",
        "count": 15,
        "total_amount": 12500.50
      },
      ...
    ]
  }
  ```
- **How to Test:**
  ```sh
  php vendor/bin/phpunit tests/Controller/OrderControllerTest.php --filter testGetOrderStats
  ```

### Endpoint #3: Save Orders via XML

- **Method:** POST
- **URL:** `/soap`
- **Headers:** 
  - `Content-Type: application/xml`
- **Request Body:** XML data with order information
  ```xml
  <?xml version="1.0" encoding="UTF-8"?>
  <order>
    <name>Test Order</name>
    <user_id>123</user_id>
    <number>TST-001</number>
    <status>2</status>
    <email>test@example.com</email>
    <!-- Additional fields -->
  </order>
  ```
- **Example Response:**
  ```json
  {
    "message": "Order created",
    "id": 42
  }
  ```
- **How to Test:**
  ```sh
  php vendor/bin/phpunit tests/Controller/OrderControllerTest.php --filter testCreateOrder
  ```

### Endpoint #4: Retrieve a Single Order

- **Method:** GET
- **URL:** `/orders/{id}`
- **URL Parameters:**
  - `id` - The ID of the order to retrieve
- **Example Response:**
  ```json
  {
    "id": 42,
    "create_date": "2024-06-15 14:30:22"
  }
  ```
- **How to Test:**
  ```sh
  php vendor/bin/phpunit tests/Controller/OrderControllerTest.php --filter testGetOneOrder
  ```

### Search Endpoint: Search Orders

- **Method:** GET
- **URL:** `/search`
- **Query Parameters:**
  - `q` - Search query (leave empty to match all orders)
- **Example Response:**
  ```json
  {
    "hits": [
      {
        "_id": "42",
        "_score": 1,
        "_source": {
          "id": 42,
          "name": "Test Order",
          "email": "test@example.com"
        }
      }
    ],
    "total": 1,
    "error": null,
    "warning": null
  }
  ```
- **How to Test:**
  ```sh
  php vendor/bin/phpunit tests/Controller/OrderControllerTest.php --filter testSearchOrders
  ```

**Note:**  
- The application is configured to use the test database only when running in Docker.

### Test Search Service

- View SearchService:  
  `docker-compose exec manticore cat /var/www/html/src/Service/SearchService.php`
- Run Manticore test command:  
  `docker-compose exec php php bin/console app:test-manticore`

## Database Schema Reference

> **Note:**  
> The file `docs/improved_schema.sql` contains an improved version of the database schema.  
> This file is provided for informational/reference purposes only and is **not intended for import or use in the application**.  
> The application uses its own schema as defined by Doctrine entities and migrations.

## Troubleshooting

- **Cache Issues:**
  ```sh
  docker-compose exec php php bin/console cache:clear
  docker-compose exec php php bin/console doctrine:cache:clear-metadata
  ```
- **Database Permissions:**
  Grant privileges if needed:
  ```sh
  docker-compose exec db mysql -u root -proot -e "GRANT ALL PRIVILEGES ON my_database.* TO 'user'@'%'; FLUSH PRIVILEGES;"
  ```
  Or use:
  ```sh
  docker-compose exec php php bin/console app:grant-privileges
  ```
- **Composer Issues:**
  Check Composer version:
  ```sh
  docker-compose exec php composer --version
  ```

## Project Structure

```
tile-expert-app/
├── docker-compose.yml
├── Dockerfile
├── Makefile
├── src/
│   ├── Entity/
│   │   ├── Order.php
│   │   ├── OrderArticle.php
│   ├── Repository/
│   │   ├── OrderRepository.php
│   ├── Controller/
│   │   ├── OrderController.php
│   │   ├── PriceController.php
│   ├── Service/
│   │   ├── SearchService.php
│   ├── Tests/
│   │   ├── Controller/
│   │   │   ├── OrderControllerTest.php
│   │   │   ├── PriceControllerTest.php
├── config/
│   ├── services.yaml
│   ├── packages/
│   │   ├── doctrine.yaml
├── manticore.conf
├── .env
├── composer.json
├── README.md
├── docs/
│   ├── improved_schema.sql   # Reference only, not for import/use
```

## Database Schema Improvements

```markdown
1. **Singular Table Names**  
   - *Improvement:* Renamed `orders` to `order` and `orders_article` to `order_article`.  
   - *Comment:* Aligns with Symfony/Doctrine conventions where entity names are singular (e\.g\., `Order` entity)\. Improves clarity and consistency\.

2. **Normalized Denormalized Fields**  
   - *Improvement:* Removed `manager_name`, `manager_email`, `manager_phone`, `carrier_name`, `carrier_contact_data`, and `warehouse_data` from `order`\. Used foreign keys to `managers`, `carriers`, and `warehouses` instead\.  
   - *Comment:* Reduces data redundancy, ensures consistency \(e\.g\., updating a manager's email updates all orders\), and saves storage\.

3. **Consolidated Delivery Times**  
   - *Improvement:* Moved all delivery time fields \(`delivery_time_min`, `confirm_min`, etc\.\) from `order` and `order_article` to `delivery_times` with a `type` ENUM\.  
   - *Comment:* Centralizes delivery time data, reduces `order` table size, and allows flexible tracking\. `delivery_times` is linked to `order` via a foreign key\.

4. **Enforced Foreign Key Constraints**  
   - *Improvement:* Made `order_id` in `order_article` and `delivery_times` non\-nullable with `ON DELETE CASCADE`\. Kept `article_id` as nullable \(assuming no `article` table exists yet\)\. Added `ON DELETE SET NULL` for `client_id`, `delivery_address_id`, etc\., to allow flexibility\.  
   - *Comment:* Prevents orphaned records, ensures data integrity, and handles deletions appropriately\.

5. **Optimized Indexes**  
   - *Improvement:* Kept `idx_create_date`, `idx_user_id`, `idx_hash`\. Replaced `IDX_4` with `idx_status_create_date` for Endpoint \#2 queries\. Added indexes on `manager_id`, `carrier_id`, `warehouse_id`, `bank_details_id`, `client_id`, and `delivery_address_id`\.  
   - *Comment:* Enhances performance for common queries \(e\.g\., stats by date/status, lookups by manager\)\. Removed redundant `IDX_4` as `create_date` is already indexed\.

6. **Used ENUM for Status and Types**  
   - *Improvement:* Replaced `INT`/`SMALLINT` with `ENUM` for `status` \(pending, processing, etc\.\), `vat_type`, `delivery_type`, `pay_type`, `offset_reason`, `delivery_calculate_type`, and `multiple_pallet`\.  
   - *Comment:* Enforces valid values, improves readability, and reduces errors\. ENUMs are documented in the schema\.

7. **Standardized Character Encoding**  
   - *Improvement:* Set `utf8mb4_unicode_ci` for all tables and columns, with `SET NAMES utf8mb4` at the schema level\.  
   - *Comment:* Ensures consistent Unicode support for international characters, matching the second schema's standard\.

8. **Split Large `order` Table**  
   - *Improvement:* Created `clients` table for `client_name`, `surname`, `company_name`, `sex`, `email`\. Created `delivery_addresses` table for `delivery_country`, `region`, `city`, `address`, etc\.  
   - *Comment:* Reduces `order` table complexity \(from 60\+ columns to \~30\), improves query performance, and allows reuse of client/address data\.

9. **Improved Data Types**  
   - *Improvement:* Used `DECIMAL\(10,2\)` for `price`, `delivery_cost`, `price_eur`, `weight`, etc\., instead of `DOUBLE`\. Increased varchar lengths: `email` \(100\), `name` \(255\), `address` \(500\)\. Used `VARCHAR\(20\)` for `number` to handle longer order numbers\.  
   - *Comment:* `DECIMAL` ensures precise financial calculations\. Larger varchar lengths prevent truncation for edge cases\.

10. **Added Timestamps**  
    - *Improvement:* Kept `create_date` and `update_date` in `order`, made `update_date` auto\-updating\. Added `created_at` and `updated_at` to all tables for consistency\.  
    - *Comment:* Enables auditing and tracking of record changes\. Auto\-updating `updated_at` simplifies maintenance\.

11. **Removed Redundant Fields**  
    - *Improvement:* Removed `bank_transfer_requested`, `accept_pay`, `product_review`, `mirror`, `process`, `entrance_review`, `show_msg`, `address_equal` as they seem niche or unused\. These could be moved to an `order_metadata` table if needed\.  
    - *Comment:* Simplifies the schema by focusing on core functionality\. Metadata table can be added later for flexibility\.

12. **Added Missing Tables**  
    - *Improvement:* Added `clients` and `delivery_addresses` tables to normalize client and address data\.  
    - *Comment:* Supports scalability and reuse\. `clients` centralizes user data, and `delivery_addresses` allows multiple addresses per order\.
```
> Again, this schema is for informational purposes only and is not used by the application.

## Contributing

Submit issues or pull requests to the GitHub repository:  
https://github.com/Vimpil/tileExpertMiddlePHPtestTask/

