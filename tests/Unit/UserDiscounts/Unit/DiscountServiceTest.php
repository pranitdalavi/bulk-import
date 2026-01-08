<?php

namespace PranitDalavi\UserDiscounts\Tests\Unit;

use Orchestra\Testbench\TestCase;
use PranitDalavi\UserDiscounts\Services\DiscountService;
use PranitDalavi\UserDiscounts\Models\Discount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DiscountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return ['PranitDalavi\UserDiscounts\UserDiscountsServiceProvider'];
    }

    // Rely on application's migrations and Testbench's RefreshDatabase

    /** @test */
    public function usage_cap_is_enforced()
    {
        $userId = 1;

        $discount = Discount::create([
            'name' => 'Limited Discount',
            'type' => 'percentage',
            'value' => 10,
            'usage_limit' => 2,
            'active' => true,
        ]);

        $service = new DiscountService();

        $service->assign($userId, $discount->id);

        $amount = 100;

        $amountAfter1 = $service->apply($userId, $amount);
        $this->assertEquals(90, $amountAfter1);

        $amountAfter2 = $service->apply($userId, $amountAfter1);
        $this->assertEquals(81, $amountAfter2); // 10% again

        $amountAfter3 = $service->apply($userId, $amountAfter2);
        $this->assertEquals(81, $amountAfter3); // cap reached, no further discount
    }
}
