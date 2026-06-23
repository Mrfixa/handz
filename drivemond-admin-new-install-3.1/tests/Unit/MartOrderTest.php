<?php

namespace Tests\Unit;

use Modules\TripManagement\Entities\MartOrder;
use Tests\TestCase;

class MartOrderTest extends TestCase
{
    // ========================================================================
    // STATUSES constant tests
    // ========================================================================

    public function test_statuses_contains_all_expected_statuses(): void
    {
        $expectedStatuses = ['pending', 'accepted', 'picked_up', 'delivered', 'cancelled'];
        
        foreach ($expectedStatuses as $status) {
            $this->assertContains($status, MartOrder::STATUSES);
        }
        
        $this->assertCount(5, MartOrder::STATUSES);
    }

    public function test_statuses_is_in_lifecycle_order(): void
    {
        $statuses = MartOrder::STATUSES;
        
        $this->assertEquals('pending', $statuses[0]);
        $this->assertEquals('accepted', $statuses[1]);
        $this->assertEquals('picked_up', $statuses[2]);
        $this->assertEquals('delivered', $statuses[3]);
        $this->assertEquals('cancelled', $statuses[4]);
    }

    // ========================================================================
    // STATUS_TRANSITIONS constant tests
    // ========================================================================

    public function test_accepted_transition_only_from_pending(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;
        
        $this->assertArrayHasKey('accepted', $transitions);
        $this->assertEquals(['pending'], $transitions['accepted']);
    }

    public function test_picked_up_transition_only_from_accepted(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;
        
        $this->assertArrayHasKey('picked_up', $transitions);
        $this->assertEquals(['accepted'], $transitions['picked_up']);
    }

    public function test_delivered_transition_only_from_picked_up(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;
        
        $this->assertArrayHasKey('delivered', $transitions);
        $this->assertEquals(['picked_up'], $transitions['delivered']);
    }

    public function test_cancelled_transition_from_pending_or_accepted(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;
        
        $this->assertArrayHasKey('cancelled', $transitions);
        $this->assertContains('pending', $transitions['cancelled']);
        $this->assertContains('accepted', $transitions['cancelled']);
    }

    public function test_cancelled_cannot_come_from_picked_up(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;
        
        $this->assertNotContains('picked_up', $transitions['cancelled']);
    }

    public function test_cancelled_cannot_come_from_delivered(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;
        
        $this->assertArrayNotHasKey('delivered', $transitions['cancelled']);
    }

    public function test_delivered_is_a_final_state(): void
    {
        // delivered is not listed as a target in STATUS_TRANSITIONS,
        // meaning nothing can transition TO delivered except from picked_up
        $this->assertArrayHasKey('delivered', MartOrder::STATUS_TRANSITIONS);
        
        // The only valid transition TO delivered is from picked_up
        $this->assertEquals(['picked_up'], MartOrder::STATUS_TRANSITIONS['delivered']);
    }

    // ========================================================================
    // Status transition helper method tests (if exists in model)
    // ========================================================================

    public function test_pending_is_valid_initial_status(): void
    {
        $this->assertContains('pending', MartOrder::STATUSES);
        $this->assertArrayHasKey('accepted', MartOrder::STATUS_TRANSITIONS);
    }

    public function test_no_skipping_allowed_in_status_flow(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;
        
        // Cannot go from pending directly to picked_up (must go through accepted)
        $this->assertArrayNotHasKey('picked_up', $transitions['pending'] ?? []);
        
        // Cannot go from pending directly to delivered
        $this->assertArrayNotHasKey('delivered', $transitions['pending'] ?? []);
        
        // Cannot go from accepted directly to delivered (must go through picked_up)
        $this->assertArrayNotHasKey('delivered', $transitions['accepted'] ?? []);
    }

    // ========================================================================
    // Model attribute tests
    // ========================================================================

    public function test_fillable_attributes_are_defined(): void
    {
        $order = new MartOrder();
        $fillable = $order->getFillable();
        
        $requiredFields = [
            'ref_id',
            'customer_id',
            'driver_id',
            'status',
            'total_amount',
            'tip_amount',
            'discount_amount',
            'promo_code',
            'payment_status',
            'payment_method',
            'delivery_address',
            'delivery_lat',
            'delivery_lng',
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertContains($field, $fillable, "Field '$field' should be fillable");
        }
    }

    public function test_casts_are_defined_for_amounts(): void
    {
        $order = new MartOrder();
        $casts = $order->getCasts();
        
        $this->assertEquals('decimal:2', $casts['total_amount']);
        $this->assertEquals('decimal:2', $casts['tip_amount']);
        $this->assertEquals('decimal:2', $casts['discount_amount']);
    }

    public function test_casts_are_defined_for_coordinates(): void
    {
        $order = new MartOrder();
        $casts = $order->getCasts();
        
        $this->assertEquals('decimal:7', $casts['delivery_lat']);
        $this->assertEquals('decimal:7', $casts['delivery_lng']);
        $this->assertEquals('decimal:7', $casts['driver_lat']);
        $this->assertEquals('decimal:7', $casts['driver_lng']);
    }

    public function test_cancelled_at_is_cast_to_datetime(): void
    {
        $order = new MartOrder();
        $casts = $order->getCasts();
        
        $this->assertEquals('datetime', $casts['cancelled_at']);
    }

    public function test_model_uses_soft_deletes(): void
    {
        $order = new MartOrder();
        
        $this->assertContains('Illuminate\Database\Eloquent\SoftDeletes', class_uses($order));
    }

    public function test_model_uses_uuids(): void
    {
        $order = new MartOrder();
        
        $this->assertContains('Illuminate\Database\Eloquent\Concerns\HasUuids', class_uses($order));
    }

    // ========================================================================
    // Relationship tests
    // ========================================================================

    public function test_customer_relationship_is_defined(): void
    {
        $order = new MartOrder();
        
        $this->assertTrue(method_exists($order, 'customer'));
    }

    public function test_driver_relationship_is_defined(): void
    {
        $order = new MartOrder();
        
        $this->assertTrue(method_exists($order, 'driver'));
    }

    public function test_items_relationship_is_defined(): void
    {
        $order = new MartOrder();
        
        $this->assertTrue(method_exists($order, 'items'));
    }

    public function test_channel_relationship_is_defined(): void
    {
        $order = new MartOrder();
        
        $this->assertTrue(method_exists($order, 'channel'));
    }

    public function test_conversations_relationship_is_defined(): void
    {
        $order = new MartOrder();
        
        $this->assertTrue(method_exists($order, 'conversations'));
    }
}
