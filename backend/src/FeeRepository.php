<?php
namespace App;

use PDO;

/**
 * FeeRepository
 *
 * Handles all database queries for fee configurations and vehicle types.
 */
class FeeRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all vehicle types
     * @return array List of vehicle type names
     */
    public function getVehicleTypes(): array {
        $stmt = $this->pdo->query('SELECT name FROM vehicle_types ORDER BY name');
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
    }

    /**
     * Get fee configuration for a vehicle type and fee type
     * @param string $vehicleType 'common' or 'luxury'
     * @param string $feeType 'basic_buyer_fee', 'seller_special_fee', 'association_fee', 'storage_fee'
     * @param float|null $price Price (used for association fee range lookup)
     * @return array Configuration with keys: percentage, fixed_amount, min_amount, max_amount, price_range_min, price_range_max
     */
    public function getFeeConfig(string $vehicleType, string $feeType, ?float $price = null): ?array {
        $stmt = $this->pdo->prepare('
            SELECT 
                fc.percentage, fc.fixed_amount, fc.min_amount, fc.max_amount, 
                fc.price_range_min, fc.price_range_max
            FROM 
                fee_configurations AS fc
            JOIN 
                vehicle_types AS vt ON fc.vehicle_type_id = vt.id
            WHERE 
                vt.name = :type 
            AND 
                fc.fee_type = :feeType
        ');

        // For association_fee, find the matching price range
        if ($feeType === 'association_fee' && $price !== null) {
            $stmt = $this->pdo->prepare('
                SELECT 
                    fc.percentage, fc.fixed_amount, fc.min_amount, fc.max_amount, 
                    fc.price_range_min, fc.price_range_max
                FROM 
                    fee_configurations AS fc
                JOIN 
                    vehicle_types AS vt ON fc.vehicle_type_id = vt.id
                WHERE 
                    vt.name = :type 
                AND 
                    fc.fee_type = :feeType
                AND 
                    :price >= fc.price_range_min 
                AND 
                    :price <= fc.price_range_max
                LIMIT 1
            ');
            $stmt->bindParam(':price', $price, PDO::PARAM_STR);
        }

        $stmt->bindParam(':type', $vehicleType, PDO::PARAM_STR);
        $stmt->bindParam(':feeType', $feeType, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get all fee configurations for a vehicle type
     * @param string $vehicleType 'common' or 'luxury'
     * @return array Grouped by fee_type
     */
    public function getConfigsByType(string $vehicleType): array {
        $stmt = $this->pdo->prepare('
            SELECT 
                fc.fee_type, fc.percentage, fc.fixed_amount, fc.min_amount, fc.max_amount, 
                fc.price_range_min, fc.price_range_max
            FROM 
                fee_configurations AS fc
            JOIN 
                vehicle_types AS vt ON fc.vehicle_type_id = vt.id
            WHERE 
                vt.name = :type
            ORDER BY 
                fc.fee_type
        ');
        $stmt->bindParam(':type', $vehicleType, PDO::PARAM_STR);
        $stmt->execute();

        $configs = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $feeType = $row['fee_type'];
            if (!isset($configs[$feeType])) {
                $configs[$feeType] = [];
            }
            $configs[$feeType][] = $row;
        }

        return $configs;
    }
}
