<?php
namespace App;

/**
 * BidCalculator Class
 *
 * Calculates fees and totals for vehicle bids using dynamic configurations from database.
 * All fee rules are loaded from the database, not hardcoded.
 */
class BidCalculator {
    private FeeRepository $feeRepository;

    /**
     * Constructor
     * @param FeeRepository $feeRepository Database repository for fee configurations
     */
    public function __construct(FeeRepository $feeRepository) {
        $this->feeRepository = $feeRepository;
    }

    /**
     * Calculate fees and total
     * @param float $price Vehicle base price
     * @param string $type 'common' or 'luxury' (must exist in database)
     * @return array breakdown: fees and total
     * @throws \Exception if vehicle type not found in database
     */
    public function calculate(float $price, string $type): array {
        $price = max(0, $price);
        $type = strtolower($type);

        // Verify vehicle type exists in database
        $validTypes = $this->feeRepository->getVehicleTypes();
        if (!in_array($type, $validTypes)) {
            throw new \Exception("Invalid vehicle type: {$type}. Valid types: " . implode(', ', $validTypes));
        }

        // Calculate each fee based on database configuration
        $basicConfig = $this->feeRepository->getFeeConfig($type, 'basic_buyer_fee');
        $basic = $this->calculateBasicBuyerFee($price, $basicConfig);

        $specialConfig = $this->feeRepository->getFeeConfig($type, 'seller_special_fee');
        $special = $this->calculateSellerSpecialFee($price, $specialConfig);

        $associationConfig = $this->feeRepository->getFeeConfig($type, 'association_fee', $price);
        $association = $associationConfig ? (float)$associationConfig['fixed_amount'] : 0;

        $storageConfig = $this->feeRepository->getFeeConfig($type, 'storage_fee');
        $storage = $storageConfig ? (float)$storageConfig['fixed_amount'] : 0;

        $total = $price + $basic + $special + $association + $storage;

        return [
            'price' => round($price, 2),
            'type' => $type,
            'fees' => [
                'basic_buyer_fee' => round($basic, 2),
                'seller_special_fee' => round($special, 2),
                'association_fee' => round($association, 2),
                'storage_fee' => round($storage, 2)
            ],
            'total' => round($total, 2)
        ];
    }

    /**
     * Calculate basic buyer fee: percentage of price, bounded by min/max
     * @param float $price Vehicle base price
     * @param array $config Fee configuration from database
     * @return float Calculated fee
     */
    private function calculateBasicBuyerFee(float $price, ?array $config): float {
        if (!$config) {
            return 0;
        }

        $percentage = (float)($config['percentage'] ?? 0);
        $minAmount = (float)($config['min_amount'] ?? 0);
        $maxAmount = (float)($config['max_amount'] ?? PHP_FLOAT_MAX);

        $fee = $price * ($percentage / 100);
        return max($minAmount, min($maxAmount, $fee));
    }

    /**
     * Calculate seller's special fee: percentage of price
     * @param float $price Vehicle base price
     * @param array $config Fee configuration from database
     * @return float Calculated fee
     */
    private function calculateSellerSpecialFee(float $price, ?array $config): float {
        if (!$config) {
            return 0;
        }

        $percentage = (float)($config['percentage'] ?? 0);
        return $price * ($percentage / 100);
    }
}
