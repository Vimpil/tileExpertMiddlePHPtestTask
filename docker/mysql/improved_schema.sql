SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET collation_connection = 'utf8mb4_unicode_ci';

-- Clients table for normalized client data
CREATE TABLE clients (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         name VARCHAR(255) NOT NULL,
                         surname VARCHAR(255) DEFAULT NULL,
                         company_name VARCHAR(255) DEFAULT NULL,
                         email VARCHAR(100) DEFAULT NULL,
                         sex ENUM('male', 'female', 'other') DEFAULT NULL,
                         created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                         updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Delivery addresses table for normalized address data
CREATE TABLE delivery_addresses (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    country_id INT DEFAULT NULL,
                                    region VARCHAR(100) DEFAULT NULL,
                                    city VARCHAR(200) DEFAULT NULL,
                                    address VARCHAR(500) NOT NULL,
                                    building VARCHAR(200) DEFAULT NULL,
                                    apartment_office VARCHAR(50) DEFAULT NULL,
                                    postal_index VARCHAR(20) DEFAULT NULL,
                                    phone_code VARCHAR(20) DEFAULT NULL,
                                    phone VARCHAR(20) DEFAULT NULL,
                                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Managers table
CREATE TABLE managers (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          name VARCHAR(255) NOT NULL,
                          email VARCHAR(100) NOT NULL,
                          phone VARCHAR(20) DEFAULT NULL,
                          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode   COLLATE=utf8mb4_unicode_ci;

-- Carriers table
CREATE TABLE carriers (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          name VARCHAR(255) NOT NULL,
                          contact_data TEXT DEFAULT NULL,
                          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Warehouses table
CREATE TABLE warehouses (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(255) NOT NULL,
                            address VARCHAR(500) NOT NULL,
                            working_hours VARCHAR(100) DEFAULT NULL,
                            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bank details table
CREATE TABLE bank_details (
                              id INT AUTO_INCREMENT PRIMARY KEY,
                              details TEXT NOT NULL,
                              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                              updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Delivery times table
CREATE TABLE delivery_times (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                order_id INT NOT NULL,
                                type ENUM('min', 'max', 'confirm_min', 'confirm_max', 'fast_pay_min', 'fast_pay_max', 'old_min', 'old_max') NOT NULL,
                                date DATE NOT NULL,
                                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                                FOREIGN KEY (order_id) REFERENCES `order`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_order_id ON delivery_times (order_id);

-- Order table
CREATE TABLE `order` (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         hash VARCHAR(32) NOT NULL,
                         user_id INT DEFAULT NULL,
                         token VARCHAR(64) NOT NULL COMMENT 'Unique user hash',
                         number VARCHAR(20) DEFAULT NULL COMMENT 'Order number',
                         status ENUM('pending', 'processing', 'shipped', 'cancelled', 'delivered') NOT NULL DEFAULT 'pending',
                         client_id INT DEFAULT NULL,
                         delivery_address_id INT DEFAULT NULL,
                         manager_id INT DEFAULT NULL,
                         carrier_id INT DEFAULT NULL,
                         warehouse_id INT DEFAULT NULL,
                         bank_details_id INT DEFAULT NULL,
                         vat_type ENUM('individual', 'vat_payer') NOT NULL DEFAULT 'individual',
                         vat_number VARCHAR(100) DEFAULT NULL,
                         tax_number VARCHAR(50) DEFAULT NULL,
                         discount SMALLINT DEFAULT NULL,
                         delivery_cost DECIMAL(10,2) DEFAULT NULL,
                         delivery_type ENUM('client_address', 'warehouse') DEFAULT NULL,
                         delivery_price_euro DECIMAL(10,2) DEFAULT NULL,
                         pay_type ENUM('bank_transfer', 'credit_card', 'paypal') NOT NULL,
                         pay_date_execution DATETIME DEFAULT NULL,
                         offset_date DATETIME DEFAULT NULL,
                         offset_reason ENUM('factory_holiday', 'production_delay', 'other') DEFAULT NULL,
                         proposed_date DATETIME DEFAULT NULL,
                         ship_date DATETIME DEFAULT NULL,
                         tracking_number VARCHAR(50) DEFAULT NULL,
                         locale VARCHAR(5) NOT NULL,
                         cur_rate DECIMAL(10,4) DEFAULT 1.0000,
                         currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
                         measure VARCHAR(3) NOT NULL DEFAULT 'm',
                         name VARCHAR(255) NOT NULL,
                         description TEXT DEFAULT NULL,
                         create_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                         update_date DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                         cancel_date DATETIME DEFAULT NULL,
                         weight_gross DECIMAL(10,2) DEFAULT NULL,
                         payment_euro BOOLEAN DEFAULT FALSE,
                         spec_price BOOLEAN DEFAULT FALSE,
                         delivery_calculate_type ENUM('manual', 'automatic') DEFAULT 'manual',
                         full_payment_date DATE DEFAULT NULL,
                         sending_date DATETIME DEFAULT NULL,
                         fact_date DATETIME DEFAULT NULL,
                         FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
                         FOREIGN KEY (delivery_address_id) REFERENCES delivery_addresses(id) ON DELETE SET NULL,
                         FOREIGN KEY (manager_id) REFERENCES managers(id) ON DELETE SET NULL,
                         FOREIGN KEY (carrier_id) REFERENCES carriers(id) ON DELETE SET NULL,
                         FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,
                         FOREIGN KEY (bank_details_id) REFERENCES bank_details(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_hash ON `order` (hash);
CREATE INDEX idx_user_id ON `order` (user_id);
CREATE INDEX idx_create_date ON `order` (create_date);
CREATE INDEX idx_status_create_date ON `order` (status, create_date);
CREATE INDEX idx_manager_id ON `order` (manager_id);
CREATE INDEX idx_carrier_id ON `order` (carrier_id);
CREATE INDEX idx_warehouse_id ON `order` (warehouse_id);
CREATE INDEX idx_bank_details_id ON `order` (bank_details_id);
CREATE INDEX idx_client_id ON `order` (client_id);
CREATE INDEX idx_delivery_address_id ON `order` (delivery_address_id);

-- Order article table
CREATE TABLE order_article (
                               id INT AUTO_INCREMENT PRIMARY KEY,
                               order_id INT NOT NULL,
                               article_id INT DEFAULT NULL COMMENT 'ID of the article/collection',
                               amount DECIMAL(10,2) NOT NULL,
                               price DECIMAL(10,2) NOT NULL,
                               price_eur DECIMAL(10,2) DEFAULT NULL,
                               currency VARCHAR(3) DEFAULT NULL,
                               measure VARCHAR(2) DEFAULT NULL,
                               weight DECIMAL(10,2) NOT NULL,
                               multiple_pallet ENUM('packaging', 'pallet', 'min_pallet') DEFAULT NULL,
                               packaging_count DECIMAL(10,2) NOT NULL,
                               pallet DECIMAL(10,2) NOT NULL,
                               packaging DECIMAL(10,2) NOT NULL,
                               swimming_pool BOOLEAN NOT NULL DEFAULT FALSE,
                               created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                               updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                               FOREIGN KEY (order_id) REFERENCES `order`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_order_id ON order_article (order_id);
CREATE INDEX idx_article_id ON order_article (article_id);