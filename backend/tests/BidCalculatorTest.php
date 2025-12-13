<?php
use PHPUnit\Framework\TestCase;
use App\BidCalculator;
use App\FeeRepository;

final class BidCalculatorTest extends TestCase
{
    private $mockRepository;

    protected function setUp(): void
    {
        $this->mockRepository = $this->createMock(FeeRepository::class);
    }

    public function testCommonVehicleExample()
    {
        // Setup: Mock repository returns configurations for common vehicle
        $this->mockRepository->method('getVehicleTypes')
            ->willReturn(['common', 'luxury']);

        $this->mockRepository->method('getFeeConfig')
            ->willReturnCallback(function($type, $feeType, $price = null) {
                if ($type === 'common' && $feeType === 'basic_buyer_fee') {
                    return ['percentage' => 10, 'min_amount' => 10, 'max_amount' => 50];
                }
                if ($type === 'common' && $feeType === 'seller_special_fee') {
                    return ['percentage' => 2];
                }
                if ($type === 'common' && $feeType === 'association_fee' && $price > 500 && $price <= 1000) {
                    return ['fixed_amount' => 10];
                }
                if ($type === 'common' && $feeType === 'storage_fee') {
                    return ['fixed_amount' => 100];
                }
                return null;
            });

        $calc = new BidCalculator($this->mockRepository);
        $result = $calc->calculate(1000, 'common');

        // Verify the calculation matches expected values
        $this->assertEquals(1000.00, $result['price']);
        $this->assertEquals('common', $result['type']);
        $this->assertEquals(50.00, $result['fees']['basic_buyer_fee']);
        $this->assertEquals(20.00, $result['fees']['seller_special_fee']);
        $this->assertEquals(10.00, $result['fees']['association_fee']);
        $this->assertEquals(100.00, $result['fees']['storage_fee']);
        $this->assertEquals(1180.00, $result['total']);
    }

    public function testLuxuryVehicleMinimumBasicFee()
    {
        // Setup: Mock repository returns configurations for luxury vehicle
        $this->mockRepository->method('getVehicleTypes')
            ->willReturn(['common', 'luxury']);

        $this->mockRepository->method('getFeeConfig')
            ->willReturnCallback(function($type, $feeType, $price = null) {
                if ($type === 'luxury' && $feeType === 'basic_buyer_fee') {
                    return ['percentage' => 10, 'min_amount' => 25, 'max_amount' => 200];
                }
                if ($type === 'luxury' && $feeType === 'seller_special_fee') {
                    return ['percentage' => 4];
                }
                if ($type === 'luxury' && $feeType === 'association_fee') {
                    return ['fixed_amount' => 5];
                }
                if ($type === 'luxury' && $feeType === 'storage_fee') {
                    return ['fixed_amount' => 100];
                }
                return null;
            });

        $calc = new BidCalculator($this->mockRepository);
        // 10% of 100 = 10, but minimum is 25
        $result = $calc->calculate(100, 'luxury');

        $this->assertEquals(25.00, $result['fees']['basic_buyer_fee']);
    }

    public function testInvalidVehicleType()
    {
        $this->mockRepository->method('getVehicleTypes')
            ->willReturn(['common', 'luxury']);

        $this->mockRepository->method('getFeeConfig')
            ->willReturn(null);

        $calc = new BidCalculator($this->mockRepository);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid vehicle type');
        $calc->calculate(1000, 'invalid');
    }
}
