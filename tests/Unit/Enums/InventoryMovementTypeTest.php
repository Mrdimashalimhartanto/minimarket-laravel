<?php

namespace Tests\Unit\Enums;

use Tests\TestCase;
use App\Enums\InventoryMovementType;

class InventoryMovementTypeTest extends TestCase
{
    // #[Test]
    public function it_has_expected_enum_values(): void
    {
        // ✅ HARUS uppercase, sesuai enum asli
        $this->assertSame('IN', InventoryMovementType::IN->value);
        $this->assertSame('OUT', InventoryMovementType::OUT->value);
        $this->assertSame('ADJUST', InventoryMovementType::ADJUST->value);
    }

    // #[Test]
    public function it_returns_correct_labels(): void
    {
        $this->assertSame('Stock In', InventoryMovementType::IN->label());
        $this->assertSame('Stock Out', InventoryMovementType::OUT->label());
        $this->assertSame('Stock Adjustment', InventoryMovementType::ADJUST->label());
    }

    // #[Test]
    public function it_can_be_created_from_value(): void
    {
        $this->assertTrue(InventoryMovementType::tryFrom('IN') === InventoryMovementType::IN);
        $this->assertTrue(InventoryMovementType::tryFrom('OUT') === InventoryMovementType::OUT);
        $this->assertTrue(InventoryMovementType::tryFrom('ADJUST') === InventoryMovementType::ADJUST);
    }
}
