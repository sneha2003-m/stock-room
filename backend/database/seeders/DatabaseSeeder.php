<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            'Electronics',
            'Office Supplies',
            'Furniture',
            'Home Goods',
            'Accessories',
            'Consumables',
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category]);
        }

        $catalog = [
            ['Electronics', 'Wireless Mouse', 18.99, 32],
            ['Electronics', 'USB-C Hub', 42.50, 18],
            ['Electronics', 'Bluetooth Speaker', 58.00, 14],
            ['Electronics', 'Mechanical Keyboard', 75.00, 11],
            ['Electronics', 'Laptop Stand', 39.99, 20],
            ['Electronics', '4K Monitor', 280.00, 7],
            ['Electronics', 'Webcam HD', 95.00, 9],
            ['Electronics', 'SSD 1TB', 120.00, 13],
            ['Electronics', 'Wireless Charger', 24.50, 21],
            ['Electronics', 'Noise Cancelling Headphones', 149.99, 6],
            ['Office Supplies', 'Notebook Set', 12.25, 40],
            ['Office Supplies', 'Ballpoint Pen Pack', 8.90, 54],
            ['Office Supplies', 'Sticky Notes', 6.75, 48],
            ['Office Supplies', 'Desk Organizer', 31.00, 16],
            ['Office Supplies', 'Printer Paper Ream', 14.40, 28],
            ['Office Supplies', 'File Cabinet', 120.00, 8],
            ['Office Supplies', 'Laser Printer', 220.00, 5],
            ['Office Supplies', 'Whiteboard Marker', 4.30, 60],
            ['Office Supplies', 'A4 Binder', 9.60, 35],
            ['Office Supplies', 'Desk Lamp', 27.90, 22],
            ['Furniture', 'Ergonomic Chair', 220.00, 9],
            ['Furniture', 'Standing Desk', 420.00, 4],
            ['Furniture', 'Filing Shelf', 180.00, 6],
            ['Furniture', 'Storage Cabinet', 155.00, 7],
            ['Furniture', 'Office Table', 310.00, 5],
            ['Furniture', 'Rolling Cart', 96.00, 12],
            ['Furniture', 'Bookshelf', 170.00, 8],
            ['Furniture', 'Conference Chair', 140.00, 10],
            ['Furniture', 'Lounge Stool', 65.00, 15],
            ['Furniture', 'Monitor Arm', 58.50, 18],
            ['Home Goods', 'Ceramic Vase', 26.00, 17],
            ['Home Goods', 'Throw Blanket', 34.75, 13],
            ['Home Goods', 'Wall Clock', 19.90, 24],
            ['Home Goods', 'Table Runner', 22.50, 20],
            ['Home Goods', 'Floor Lamp', 49.99, 10],
            ['Home Goods', 'Bedding Set', 62.00, 9],
            ['Home Goods', 'Pillow Set', 28.25, 19],
            ['Home Goods', 'Plant Pot', 18.20, 25],
            ['Home Goods', 'Storage Basket', 16.50, 30],
            ['Home Goods', 'Scented Candle', 11.80, 41],
            ['Accessories', 'Leather Wallet', 36.00, 16],
            ['Accessories', 'Travel Backpack', 52.00, 12],
            ['Accessories', 'Sunglasses', 29.99, 22],
            ['Accessories', 'Watch Strap', 21.50, 31],
            ['Accessories', 'Key Holder', 14.25, 27],
            ['Accessories', 'Phone Case', 18.40, 38],
            ['Accessories', 'Laptop Sleeve', 25.80, 20],
            ['Accessories', 'Smart Wallet', 48.00, 11],
            ['Accessories', 'Reading Glasses', 32.50, 14],
            ['Accessories', 'Packable Tote', 23.00, 26],
            ['Consumables', 'Coffee Beans', 15.00, 46],
            ['Consumables', 'Hand Soap', 9.75, 58],
            ['Consumables', 'Cleaning Cloth Pack', 11.20, 52],
            ['Consumables', 'Tissue Box', 7.80, 62],
            ['Consumables', 'Desk Wipes', 8.60, 44],
            ['Consumables', 'Paper Towels', 13.40, 39],
            ['Consumables', 'Toilet Cleaner', 10.90, 33],
            ['Consumables', 'Dish Soap', 6.45, 51],
            ['Consumables', 'Air Freshener', 12.80, 26],
            ['Consumables', 'Laundry Detergent', 19.90, 24],
        ];

        foreach ($catalog as [$categoryName, $name, $price, $quantity]) {
            $category = Category::where('name', $categoryName)->first();

            if ($category) {
                Item::firstOrCreate(
                    ['category_id' => $category->id, 'name' => $name],
                    ['price' => $price, 'quantity' => $quantity]
                );
            }
        }

        $itemsByName = Item::with('category')->get()->keyBy(function ($item) {
            return $item->category->name . '|' . $item->name;
        });
        $items = [];

        foreach ($catalog as [$categoryName, $name]) {
            $items[] = $itemsByName->get($categoryName . '|' . $name);
        }

        $suppliers = $this->seedSuppliers();
        $this->seedPurchases($suppliers, $items);
        $this->seedSales($items);
    }

    private function seedSuppliers()
    {
        $suppliers = [
            ['Northstar Office Supply', '555-0101', 'orders@northstar.example.test'],
            ['Meridian Tech Distribution', '555-0102', 'sales@meridian.example.test'],
            ['Harborline Furniture', '555-0103', 'orders@harborline.example.test'],
            ['Summit Wholesale Goods', '555-0104', 'hello@summit.example.test'],
            ['Cedar Grove Essentials', '555-0105', 'sales@cedargrove.example.test'],
            ['BluePeak Electronics', '555-0106', 'orders@bluepeak.example.test'],
            ['Atlas Workspace Co.', '555-0107', 'contact@atlasworkspace.example.test'],
            ['Riverbend Paper & Packaging', '555-0108', 'sales@riverbend.example.test'],
            ['BrightPath Home Goods', '555-0109', 'orders@brightpath.example.test'],
            ['Urban Trail Accessories', '555-0110', 'hello@urbantrail.example.test'],
            ['Everwell Consumables', '555-0111', 'orders@everwell.example.test'],
            ['Keystone Business Supply', '555-0112', 'sales@keystone.example.test'],
        ];

        $records = [];

        foreach ($suppliers as [$name, $phone, $email]) {
            $records[] = Supplier::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'phone' => $phone]
            );
        }

        return $records;
    }

    private function seedPurchases($suppliers, $items)
    {
        foreach (range(0, 24) as $purchaseIndex) {
            $lines = [];
            $totalCents = 0;

            foreach (range(0, 1) as $lineIndex) {
                $item = $items[($purchaseIndex * 2 + $lineIndex * 29) % count($items)];
                $priceCents = (int) round(
                    (float) $item->price * 100 * (65 + (($purchaseIndex + $lineIndex) % 10)) / 100
                );
                $quantity = 2;
                $lines[] = [
                    'item' => $item,
                    'quantity' => $quantity,
                    'purchase_price' => $this->formatCents($priceCents),
                ];
                $totalCents += $priceCents * $quantity;
            }

            $purchaseDate = date('Y-m-d', strtotime('2026-07-01 +' . $purchaseIndex . ' days'));

            DB::transaction(function () use ($purchaseIndex, $purchaseDate, $suppliers, $lines, $totalCents) {
                $purchase = Purchase::firstOrCreate([
                    'supplier_id' => $suppliers[$purchaseIndex % count($suppliers)]->id,
                    'purchase_date' => $purchaseDate,
                    'total_amount' => $this->formatCents($totalCents),
                ]);

                if (!$purchase->wasRecentlyCreated) {
                    return;
                }

                foreach ($lines as $line) {
                    $line['item']->increment('quantity', $line['quantity']);
                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'item_id' => $line['item']->id,
                        'quantity' => $line['quantity'],
                        'purchase_price' => $line['purchase_price'],
                    ]);
                }
            });
        }
    }

    private function seedSales($items)
    {
        foreach (range(0, 49) as $saleIndex) {
            $lines = [];
            $totalCents = 0;

            foreach (range(0, 1) as $lineIndex) {
                $item = $items[($saleIndex * 2 + $lineIndex * 31) % count($items)];
                $priceCents = (int) round(
                    (float) $item->price * 100 * (100 + (($saleIndex + $lineIndex) % 11)) / 100
                );
                $quantity = 1;
                $lines[] = [
                    'item' => $item,
                    'quantity' => $quantity,
                    'selling_price' => $this->formatCents($priceCents),
                ];
                $totalCents += $priceCents * $quantity;
            }

            $saleDate = date('Y-m-d', strtotime('2026-08-01 +' . $saleIndex . ' days'));

            DB::transaction(function () use ($saleDate, $lines, $totalCents) {
                $sale = Sale::firstOrCreate([
                    'sale_date' => $saleDate,
                    'total_amount' => $this->formatCents($totalCents),
                ]);

                if (!$sale->wasRecentlyCreated) {
                    return;
                }

                foreach ($lines as $line) {
                    $item = Item::whereKey($line['item']->id)->lockForUpdate()->firstOrFail();

                    if ($item->quantity < $line['quantity']) {
                        throw new \RuntimeException('Not enough stock to seed sale for ' . $item->name . '.');
                    }

                    $item->decrement('quantity', $line['quantity']);
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'item_id' => $item->id,
                        'quantity' => $line['quantity'],
                        'selling_price' => $line['selling_price'],
                    ]);
                }
            });
        }
    }

    private function formatCents($cents)
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
