<?php
use PHPUnit\Framework\TestCase;
use App\VehicleRepository;
use App\FeeRepository;
use App\BidCalculator;

class ApiVehiclesTest extends TestCase {
    private \PDO $pdo;

    protected function setUp(): void {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create tables
        $this->pdo->exec("CREATE TABLE vehicle_types (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE);");
        $this->pdo->exec("CREATE TABLE vehicles (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, price DECIMAL(10,2), vehicle_type_id INTEGER);");
        $this->pdo->exec("CREATE TABLE fee_configurations (id INTEGER PRIMARY KEY AUTOINCREMENT, vehicle_type_id INTEGER, fee_type TEXT, percentage DECIMAL(5,2), fixed_amount DECIMAL(10,2), min_amount DECIMAL(10,2), max_amount DECIMAL(10,2), price_range_min DECIMAL(10,2), price_range_max DECIMAL(10,2));");

        // Seed types
        $this->pdo->exec("INSERT INTO vehicle_types (name) VALUES ('common'), ('luxury');");

        // Seed fees similar to production
        // basic_buyer_fee common: 10% min 10 max 50
        $this->pdo->exec("INSERT INTO fee_configurations (vehicle_type_id, fee_type, percentage, min_amount, max_amount) VALUES (1, 'basic_buyer_fee', 10.00, 10.00, 50.00);");
        // seller_special_fee common 2%
        $this->pdo->exec("INSERT INTO fee_configurations (vehicle_type_id, fee_type, percentage) VALUES (1, 'seller_special_fee', 2.00);");
        // association ranges
        $this->pdo->exec("INSERT INTO fee_configurations (vehicle_type_id, fee_type, fixed_amount, price_range_min, price_range_max) VALUES (1, 'association_fee', 5.00, 1.00, 500.00);");
        $this->pdo->exec("INSERT INTO fee_configurations (vehicle_type_id, fee_type, fixed_amount, price_range_min, price_range_max) VALUES (1, 'association_fee', 10.00, 500.01, 1000.00);");
        // storage fee
        $this->pdo->exec("INSERT INTO fee_configurations (vehicle_type_id, fee_type, fixed_amount) VALUES (1, 'storage_fee', 100.00);");

        // Seed a vehicle with price 501.00 (Vehicle 2 from your list)
        $this->pdo->exec("INSERT INTO vehicles (name, price, vehicle_type_id) VALUES ('Vehicle 2', 501.00, 1);");
    }

    public function testCalculationForVehicle2() {
        $vehicleRepo = new VehicleRepository($this->pdo);
        $feeRepo = new FeeRepository($this->pdo);
        $calc = new BidCalculator($feeRepo);

        $vehicles = $vehicleRepo->getAllVehicles();
        $this->assertCount(1, $vehicles);

        $v = $vehicles[0];
        $result = $calc->calculate((float)$v['price'], $v['type']);

        // Expected: price 501, basic_buyer_fee capped at 50, special 2% = 10.02, association 10, storage 100
        $this->assertEquals(501.00, $result['price']);
        $this->assertEquals(50.00, $result['fees']['basic_buyer_fee']);
        $this->assertEqualsWithDelta(10.02, $result['fees']['seller_special_fee'], 0.001);
        $this->assertEquals(10.00, $result['fees']['association_fee']);
        $this->assertEquals(100.00, $result['fees']['storage_fee']);
        $this->assertEqualsWithDelta(671.02, $result['total'], 0.01);
    }
}
