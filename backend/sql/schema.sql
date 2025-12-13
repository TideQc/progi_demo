-- Vehicle Types Table
CREATE TABLE IF NOT EXISTS vehicle_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fee Configuration Table
CREATE TABLE IF NOT EXISTS fee_configurations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_type_id INT NOT NULL,
    fee_type VARCHAR(100) NOT NULL,
    percentage DECIMAL(5, 2),
    fixed_amount DECIMAL(10, 2),
    min_amount DECIMAL(10, 2),
    max_amount DECIMAL(10, 2),
    price_range_min DECIMAL(10, 2),
    price_range_max DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id)
);

-- Insert vehicle types
INSERT INTO vehicle_types (name) VALUES ('common'), ('luxury')
ON DUPLICATE KEY UPDATE name=name;

-- Basic buyer fee: Common (10%, min $10, max $50)
INSERT INTO fee_configurations (vehicle_type_id, fee_type, percentage, min_amount, max_amount)
SELECT id, 'basic_buyer_fee', 10.00, 10.00, 50.00 FROM vehicle_types WHERE name = 'common'
ON DUPLICATE KEY UPDATE percentage=10.00;

-- Basic buyer fee: Luxury (10%, min $25, max $200)
INSERT INTO fee_configurations (vehicle_type_id, fee_type, percentage, min_amount, max_amount)
SELECT id, 'basic_buyer_fee', 10.00, 25.00, 200.00 FROM vehicle_types WHERE name = 'luxury'
ON DUPLICATE KEY UPDATE percentage=10.00;

-- Seller's special fee: Common (2%)
INSERT INTO fee_configurations (vehicle_type_id, fee_type, percentage)
SELECT id, 'seller_special_fee', 2.00 FROM vehicle_types WHERE name = 'common'
ON DUPLICATE KEY UPDATE percentage=2.00;

-- Seller's special fee: Luxury (4%)
INSERT INTO fee_configurations (vehicle_type_id, fee_type, percentage)
SELECT id, 'seller_special_fee', 4.00 FROM vehicle_types WHERE name = 'luxury'
ON DUPLICATE KEY UPDATE percentage=4.00;

-- Association fees by price range (applies to both types)
-- $1 - $500: $5
INSERT INTO fee_configurations (vehicle_type_id, fee_type, fixed_amount, price_range_min, price_range_max)
SELECT id, 'association_fee', 5.00, 1.00, 500.00 FROM vehicle_types WHERE name = 'common'
ON DUPLICATE KEY UPDATE fixed_amount=5.00;

-- $500.01 - $1000: $10
INSERT INTO fee_configurations (vehicle_type_id, fee_type, fixed_amount, price_range_min, price_range_max)
SELECT id, 'association_fee', 10.00, 500.01, 1000.00 FROM vehicle_types WHERE name = 'common'
ON DUPLICATE KEY UPDATE fixed_amount=10.00;

-- $1000.01 - $3000: $15
INSERT INTO fee_configurations (vehicle_type_id, fee_type, fixed_amount, price_range_min, price_range_max)
SELECT id, 'association_fee', 15.00, 1000.01, 3000.00 FROM vehicle_types WHERE name = 'common'
ON DUPLICATE KEY UPDATE fixed_amount=15.00;

-- $3000.01+: $20
INSERT INTO fee_configurations (vehicle_type_id, fee_type, fixed_amount, price_range_min, price_range_max)
SELECT id, 'association_fee', 20.00, 3000.01, 9999999.99 FROM vehicle_types WHERE name = 'common'
ON DUPLICATE KEY UPDATE fixed_amount=20.00;

-- Storage fee: Fixed $100 for all types
INSERT INTO fee_configurations (vehicle_type_id, fee_type, fixed_amount)
SELECT id, 'storage_fee', 100.00 FROM vehicle_types
ON DUPLICATE KEY UPDATE fixed_amount=100.00;

-- Vehicles table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    vehicle_type_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id)
);

-- Seed vehicles (use SELECT to map type names to ids)
INSERT INTO vehicles (name, price, vehicle_type_id)
SELECT 'Vehicle 1', 398.00, id FROM vehicle_types WHERE name = 'common'
ON DUPLICATE KEY UPDATE name = name;

INSERT INTO vehicles (name, price, vehicle_type_id)
SELECT 'Vehicle 2', 501.00, id FROM vehicle_types WHERE name = 'common'
ON DUPLICATE KEY UPDATE name = name;

INSERT INTO vehicles (name, price, vehicle_type_id)
SELECT 'Vehicle 3', 57.00, id FROM vehicle_types WHERE name = 'common'
ON DUPLICATE KEY UPDATE name = name;

INSERT INTO vehicles (name, price, vehicle_type_id)
SELECT 'Vehicle 4', 1800.00, id FROM vehicle_types WHERE name = 'luxury'
ON DUPLICATE KEY UPDATE name = name;

INSERT INTO vehicles (name, price, vehicle_type_id)
SELECT 'Vehicle 5', 1100.00, id FROM vehicle_types WHERE name = 'common'
ON DUPLICATE KEY UPDATE name = name;

INSERT INTO vehicles (name, price, vehicle_type_id)
SELECT 'Vehicle 6', 1000000.00, id FROM vehicle_types WHERE name = 'luxury'
ON DUPLICATE KEY UPDATE name = name;
