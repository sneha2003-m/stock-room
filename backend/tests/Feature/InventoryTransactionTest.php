<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventoryTransactionTest extends TestCase
{
    use DatabaseTransactions;

    private $item;
    private $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Test category']);
        $this->supplier = Supplier::create(['name' => 'Test supplier']);
        $this->item = Item::create([
            'category_id' => $category->id,
            'name' => 'Test item',
            'price' => '12.50',
            'quantity' => 4,
        ]);
    }

    public function test_purchase_increases_stock_and_calculates_total()
    {
        $response = $this->postJson('/api/purchases', [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => '2026-09-27',
            'items' => [[
                'item_id' => $this->item->id,
                'quantity' => 3,
                'purchase_price' => '8.25',
            ]],
        ]);

        $response->assertCreated()->assertJsonPath('total_amount', '24.75');
        $this->assertSame(7, $this->item->fresh()->quantity);
    }

    public function test_sale_decreases_stock_and_calculates_total()
    {
        $response = $this->postJson('/api/sales', [
            'sale_date' => '2026-09-27',
            'items' => [[
                'item_id' => $this->item->id,
                'quantity' => 2,
                'selling_price' => '15.50',
            ]],
        ]);

        $response->assertCreated()->assertJsonPath('total_amount', '31.00');
        $this->assertSame(2, $this->item->fresh()->quantity);
    }

    public function test_sale_cannot_consume_more_than_available_stock()
    {
        $saleCount = Sale::count();
        $response = $this->postJson('/api/sales', [
            'sale_date' => '2026-09-27',
            'items' => [[
                'item_id' => $this->item->id,
                'quantity' => 5,
                'selling_price' => '15.50',
            ]],
        ]);

        $response->assertUnprocessable()->assertJsonFragment(['message' => 'Not enough stock for Test item. Available: 4.']);
        $this->assertSame(4, $this->item->fresh()->quantity);
        $this->assertSame($saleCount, Sale::count());
    }
}