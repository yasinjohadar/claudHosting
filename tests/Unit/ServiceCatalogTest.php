<?php

namespace Tests\Unit;

use App\Models\OfferedService;
use App\Models\ServiceType;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    public function test_offered_service_formatted_price(): void
    {
        $service = new OfferedService([
            'price' => 1500.5,
            'currency' => 'SAR',
        ]);

        $this->assertStringContainsString('1,500.50', $service->formatted_price);
        $this->assertStringContainsString('ر.س', $service->formatted_price);
    }

    public function test_service_type_has_offered_services_relation(): void
    {
        $type = new ServiceType;
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $type->offeredServices());
    }

    public function test_offered_service_belongs_to_service_type(): void
    {
        $service = new OfferedService;
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $service->serviceType());
    }

    public function test_negative_price_fails_validation_rules(): void
    {
        $rules = [
            'price' => 'required|numeric|min:0',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make(['price' => -1], $rules);

        $this->assertTrue($validator->fails());
    }
}
