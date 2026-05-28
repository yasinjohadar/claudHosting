<?php

namespace Tests\Unit;

use App\Models\CustomerService;
use Tests\TestCase;

class CustomerServiceModelTest extends TestCase
{
    public function test_status_options_cover_all_constants(): void
    {
        $options = CustomerService::statusOptions();

        $this->assertArrayHasKey(CustomerService::STATUS_PENDING, $options);
        $this->assertArrayHasKey(CustomerService::STATUS_ACTIVE, $options);
        $this->assertArrayHasKey(CustomerService::STATUS_COMPLETED, $options);
        $this->assertArrayHasKey(CustomerService::STATUS_CANCELLED, $options);
        $this->assertArrayHasKey(CustomerService::STATUS_OVERDUE, $options);
    }

    public function test_formatted_price_uses_sar_label(): void
    {
        $record = new CustomerService([
            'price' => 2500,
            'currency' => 'SAR',
        ]);

        $this->assertStringContainsString('2,500.00', $record->formatted_price);
        $this->assertStringContainsString('ر.س', $record->formatted_price);
    }

    public function test_status_label_and_color_for_active(): void
    {
        $record = new CustomerService(['status' => CustomerService::STATUS_ACTIVE]);

        $this->assertSame('نشطة', $record->status_label);
        $this->assertSame('success', $record->status_color);
    }

    public function test_belongs_to_customer_relation(): void
    {
        $record = new CustomerService;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $record->customer());
    }
}
