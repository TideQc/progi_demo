<?php
use PHPUnit\Framework\TestCase;
use App\VehicleRepository;

class VehicleRepositoryTest extends TestCase {
    private \PDO $pdo;

    protected function setUp(): void {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create tables
        $this->pdo->exec("CREATE TABLE vehicle_types (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE);");
        $this->pdo->exec("CREATE TABLE vehicles (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, price DECIMAL(10,2), vehicle_type_id INTEGER);");

        // Seed types and vehicles
        $this->pdo->exec("INSERT INTO vehicle_types (name) VALUES ('common'), ('luxury');");
        $this->pdo->exec("INSERT INTO vehicles (name, price, vehicle_type_id) VALUES ('Vehicle A', 100.00, 1), ('Vehicle B', 200.00, 1);");
    }

    public function testGetAllVehicles() {
        $repo = new VehicleRepository($this->pdo);
        $list = $repo->getAllVehicles();
        $this->assertCount(2, $list);
        $this->assertEquals('Vehicle A', $list[0]['name']);
        $this->assertEquals('common', $list[0]['type']);
    }

    public function testGetVehicleById() {
        $repo = new VehicleRepository($this->pdo);
        $v = $repo->getVehicleById(2);
        $this->assertNotNull($v);
        $this->assertEquals('Vehicle B', $v['name']);
    }
}
