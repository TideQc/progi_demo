<?php
namespace App;

use PDO;

/**
 * VehicleRepository
 *
 * Handles queries related to vehicles.
 */
class VehicleRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all vehicles with their type name
     * @return array List of vehicles: id, name, price, type
     */
    public function getAllVehicles(): array {
        $stmt = $this->pdo->prepare('SELECT v.id, v.name, v.price, vt.name AS type FROM vehicles v JOIN vehicle_types vt ON v.vehicle_type_id = vt.id ORDER BY v.id');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get vehicle by id
     * @param int $id
     * @return array|null
     */
    public function getVehicleById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT v.id, v.name, v.price, vt.name AS type FROM vehicles v JOIN vehicle_types vt ON v.vehicle_type_id = vt.id WHERE v.id = :id LIMIT 1');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
