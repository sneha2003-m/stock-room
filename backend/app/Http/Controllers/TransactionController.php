<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'item_count' => Item::count(),
            'category_count' => DB::table('categories')->count(),
            'supplier_count' => DB::table('suppliers')->count(),
            'stock_units' => Item::sum('quantity'),
            'low_stock' => Item::with('category')->where('quantity', '<=', 5)->orderBy('quantity')->limit(8)->get(),
            'recent_sales' => Sale::with('items.item')->latest('sale_date')->latest('id')->limit(6)->get(),
        ]);
    }

    public function purchases()
    {
        return Purchase::with('supplier', 'items.item')->latest('purchase_date')->latest('id')->get();
    }

    public function sales()
    {
        return Sale::with('items.item')->latest('sale_date')->latest('id')->get();
    }

    public function storePurchase(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|distinct|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1|max:4294967295',
            'items.*.purchase_price' => 'required|numeric|min:0|max:9999999999.99',
        ]);

        $purchase = DB::transaction(function () use ($data) {
            $totalCents = $this->calculateTotalCents($data['items'], 'purchase_price');

            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'total_amount' => $this->formatCents($totalCents),
            ]);

            foreach ($data['items'] as $line) {
                $item = Item::whereKey($line['item_id'])->lockForUpdate()->firstOrFail();
                $item->increment('quantity', $line['quantity']);
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'item_id' => $item->id,
                    'quantity' => $line['quantity'],
                    'purchase_price' => $line['purchase_price'],
                ]);
            }

            return $purchase;
        });

        return response()->json($purchase->load('supplier', 'items.item'), 201);
    }

    public function storeSale(Request $request)
    {
        $data = $request->validate([
            'sale_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|distinct|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selling_price' => 'required|numeric|min:0|max:9999999999.99',
        ]);

        $sale = DB::transaction(function () use ($data) {
            $totalCents = $this->calculateTotalCents($data['items'], 'selling_price');

            $sale = Sale::create([
                'sale_date' => $data['sale_date'],
                'total_amount' => $this->formatCents($totalCents),
            ]);

            foreach ($data['items'] as $line) {
                $item = Item::whereKey($line['item_id'])->lockForUpdate()->firstOrFail();
                if ($item->quantity < $line['quantity']) {
                    abort(422, 'Not enough stock for ' . $item->name . '. Available: ' . $item->quantity . '.');
                }

                $item->decrement('quantity', $line['quantity']);
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'item_id' => $item->id,
                    'quantity' => $line['quantity'],
                    'selling_price' => $line['selling_price'],
                ]);
            }

            return $sale;
        });

        return response()->json($sale->load('items.item'), 201);
    }

    private function formatCents($cents)
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    private function calculateTotalCents($lines, $priceField)
    {
        $totalCents = 0;
        $maximumCents = 99999999999999;

        foreach ($lines as $line) {
            $unitCents = (int) round((float) $line[$priceField] * 100);
            $quantity = (int) $line['quantity'];
            if ($unitCents > intdiv($maximumCents - $totalCents, $quantity)) {
                abort(422, 'Transaction total exceeds the supported maximum.');
            }
            $totalCents += $unitCents * $quantity;
        }

        return $totalCents;
    }
}