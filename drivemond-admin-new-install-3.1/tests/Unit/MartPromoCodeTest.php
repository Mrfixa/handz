<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Modules\TripManagement\Entities\MartPromoCode;
use Tests\TestCase;

class MartPromoCodeTest extends TestCase
{
    // ========================================================================
    // isValid() tests
    // ========================================================================

    public function test_is_valid_returns_true_for_active_code_with_no_expiry(): void
    {
        $promo = new MartPromoCode([
            'code' => 'VALIDCODE',
            'is_active' => true,
            'expires_at' => null,
            'usage_limit' => null,
            'used_count' => 0,
        ]);

        $this->assertTrue($promo->isValid());
    }

    public function test_is_valid_returns_false_when_inactive(): void
    {
        $promo = new MartPromoCode([
            'code' => 'INACTIVECODE',
            'is_active' => false,
            'expires_at' => null,
            'usage_limit' => null,
            'used_count' => 0,
        ]);

        $this->assertFalse($promo->isValid());
    }

    public function test_is_valid_returns_false_when_expired(): void
    {
        $promo = new MartPromoCode([
            'code' => 'EXPIREDCODE',
            'is_active' => true,
            'expires_at' => Carbon::now()->subDay(),
            'usage_limit' => null,
            'used_count' => 0,
        ]);

        $this->assertFalse($promo->isValid());
    }

    public function test_is_valid_returns_true_when_expiry_is_in_future(): void
    {
        $promo = new MartPromoCode([
            'code' => 'FUTURECODE',
            'is_active' => true,
            'expires_at' => Carbon::now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
        ]);

        $this->assertTrue($promo->isValid());
    }

    public function test_is_valid_returns_false_when_usage_limit_reached(): void
    {
        $promo = new MartPromoCode([
            'code' => 'MAXEDCODE',
            'is_active' => true,
            'expires_at' => null,
            'usage_limit' => 10,
            'used_count' => 10,
        ]);

        $this->assertFalse($promo->isValid());
    }

    public function test_is_valid_returns_true_when_usage_limit_not_reached(): void
    {
        $promo = new MartPromoCode([
            'code' => 'LIMITEDCODE',
            'is_active' => true,
            'expires_at' => null,
            'usage_limit' => 10,
            'used_count' => 5,
        ]);

        $this->assertTrue($promo->isValid());
    }

    public function test_is_valid_returns_true_when_usage_limit_is_null(): void
    {
        $promo = new MartPromoCode([
            'code' => 'UNLIMITEDCODE',
            'is_active' => true,
            'expires_at' => null,
            'usage_limit' => null,
            'used_count' => 999,
        ]);

        $this->assertTrue($promo->isValid());
    }

    // ========================================================================
    // computeDiscount() tests
    // ========================================================================

    public function test_compute_discount_returns_zero_when_subtotal_below_minimum(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'fixed',
            'discount_value' => 10.00,
            'min_order_amount' => 50.00,
            'max_discount' => null,
        ]);

        $discount = $promo->computeDiscount(30.00);
        $this->assertEquals(0.0, $discount);
    }

    public function test_compute_discount_returns_zero_when_discount_value_is_zero(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'min_order_amount' => 0,
            'max_discount' => null,
        ]);

        $discount = $promo->computeDiscount(100.00);
        $this->assertEquals(0.0, $discount);
    }

    public function test_compute_discount_returns_zero_when_discount_value_is_negative(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'fixed',
            'discount_value' => -5.00,
            'min_order_amount' => 0,
            'max_discount' => null,
        ]);

        $discount = $promo->computeDiscount(100.00);
        $this->assertEquals(0.0, $discount);
    }

    public function test_compute_discount_fixed_type_applies_direct_value(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'fixed',
            'discount_value' => 15.00,
            'min_order_amount' => 0,
            'max_discount' => null,
        ]);

        $discount = $promo->computeDiscount(100.00);
        $this->assertEquals(15.00, $discount);
    }

    public function test_compute_discount_fixed_type_cannot_exceed_subtotal(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'fixed',
            'discount_value' => 50.00,
            'min_order_amount' => 0,
            'max_discount' => null,
        ]);

        $discount = $promo->computeDiscount(30.00);
        $this->assertEquals(30.00, $discount);
    }

    public function test_compute_discount_percent_type_applies_percentage(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'percent',
            'discount_value' => 20.00,
            'min_order_amount' => 0,
            'max_discount' => null,
        ]);

        $discount = $promo->computeDiscount(100.00);
        $this->assertEquals(20.00, $discount);
    }

    public function test_compute_discount_percent_type_respects_max_discount(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'percent',
            'discount_value' => 50.00,
            'min_order_amount' => 0,
            'max_discount' => 10.00,
        ]);

        $discount = $promo->computeDiscount(100.00);
        // 50% of 100 = 50, but capped at 10
        $this->assertEquals(10.00, $discount);
    }

    public function test_compute_discount_fixed_type_respects_max_discount(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'fixed',
            'discount_value' => 50.00,
            'min_order_amount' => 0,
            'max_discount' => 25.00,
        ]);

        $discount = $promo->computeDiscount(100.00);
        $this->assertEquals(25.00, $discount);
    }

    public function test_compute_discount_rounds_to_two_decimals(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'percent',
            'discount_value' => 33.33,
            'min_order_amount' => 0,
            'max_discount' => null,
        ]);

        $discount = $promo->computeDiscount(100.00);
        $this->assertEquals(33.33, $discount);
    }

    public function test_compute_discount_returns_exact_when_at_min_order_amount(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'fixed',
            'discount_value' => 10.00,
            'min_order_amount' => 50.00,
            'max_discount' => null,
        ]);

        $discount = $promo->computeDiscount(50.00);
        $this->assertEquals(10.00, $discount);
    }

    public function test_compute_discount_percent_with_small_subtotal(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'percent',
            'discount_value' => 10.00,
            'min_order_amount' => 0,
            'max_discount' => null,
        ]);

        $discount = $promo->computeDiscount(5.00);
        $this->assertEquals(0.50, $discount);
    }

    public function test_compute_discount_max_discount_null_means_no_cap(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'percent',
            'discount_value' => 100.00,
            'min_order_amount' => 0,
            'max_discount' => null,
        ]);

        $discount = $promo->computeDiscount(75.00);
        $this->assertEquals(75.00, $discount);
    }
}
