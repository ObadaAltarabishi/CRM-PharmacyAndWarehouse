<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\ExpenseInvoice;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Pharmacy;
use App\Models\PharmacyProduct;
use App\Models\Product;
use App\Models\Region;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Models\WarehouseRating;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    private const PASSWORD = 'password123';

    public function run(): void
    {
        $regions = Region::query()->get();
        $products = Product::query()->take(150)->get();

        if ($regions->isEmpty() || $products->count() < 150) {
            throw new \RuntimeException('DemoDataSeeder requires regions and at least 150 products from openFDA.');
        }

        $admins = $this->seedAdmins($regions);
        $pharmacies = $this->seedPharmacies($regions, $admins);
        $warehouses = $this->seedWarehouses($regions, $admins);

        $this->seedWarehouseInventory($warehouses, $products);
        $this->seedPharmacyInventory($pharmacies, $products);
        $this->seedOrders($pharmacies, $warehouses, $products);
        $this->seedRatings($pharmacies, $warehouses);
        $this->seedExpenses($pharmacies, $warehouses);
        $this->seedSalesInvoices($pharmacies, $products);
        $this->seedFeedbacks($pharmacies, $warehouses);
    }

    private function seedAdmins($regions)
    {
        $admins = collect();

        for ($i = 1; $i <= 25; $i++) {
            $admins->push(Admin::create([
                'name' => $i === 1 ? 'Muhammad Hamzah Al Mzasri' : "Demo Admin $i",
                'email' => $i === 1 ? 'muhammad.hamzah.almzasri@gmail.com' : "admin$i@gmail.com",
                'phone' => '093000' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'password' => self::PASSWORD,
                'role' => $i === 1 ? 'super_admin' : 'admin',
                'region_id' => $regions[($i - 1) % $regions->count()]->id,
            ]));
        }

        return $admins;
    }

    private function seedPharmacies($regions, $admins)
    {
        $pharmacies = collect();

        for ($i = 1; $i <= 25; $i++) {
            $pharmacies->push(Pharmacy::create([
                'pharmacy_name' => "Demo Pharmacy $i",
                'doctor_name' => "Doctor $i",
                'doctor_phone' => '094000' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'doctor_email' => $i === 1 ? 'clashsy0@gmail.com' : "pharmacy$i@gmail.com",
                'password' => self::PASSWORD,
                'activated_at' => now()->subDays(60 - ($i % 30)),
                'region_id' => $regions[($i - 1) % $regions->count()]->id,
                'admin_id' => $admins[($i - 1) % $admins->count()]->id,
            ]));
        }

        return $pharmacies;
    }

    private function seedWarehouses($regions, $admins)
    {
        $warehouses = collect();

        for ($i = 1; $i <= 25; $i++) {
            $warehouses->push(Warehouse::create([
                'warehouse_name' => "Demo Warehouse $i",
                'owner_name' => "Warehouse Owner $i",
                'owner_phone' => '095000' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'owner_email' => $i === 1 ? 'obadaaltarabishi@gmail.com' : "warehouse$i@gmail.com",
                'password' => self::PASSWORD,
                'activated_at' => now()->subDays(55 - ($i % 25)),
                'region_id' => $regions[($i - 1) % $regions->count()]->id,
                'admin_id' => $admins[($i - 1) % $admins->count()]->id,
            ]));
        }

        return $warehouses;
    }

    private function seedWarehouseInventory($warehouses, $products): void
    {
        foreach ($warehouses as $warehouseIndex => $warehouse) {
            $selected = $products->slice(($warehouseIndex * 7) % 80, 70)->values();

            foreach ($selected as $productIndex => $product) {
                $cost = 8 + (($warehouseIndex + $productIndex) % 45);

                WarehouseProduct::create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'quantity' => 40 + (($warehouseIndex * 5 + $productIndex * 3) % 160),
                    'reserved_quantity' => ($productIndex % 9 === 0) ? 3 : 0,
                    'cost_price' => $cost,
                    'sell_price_to_pharmacy' => round($cost * 1.25, 4),
                ]);
            }
        }
    }

    private function seedPharmacyInventory($pharmacies, $products): void
    {
        foreach ($pharmacies as $pharmacyIndex => $pharmacy) {
            $selected = $products->slice(($pharmacyIndex * 5) % 100, 35)->values();

            foreach ($selected as $productIndex => $product) {
                $quantity = match ($productIndex % 10) {
                    0, 1 => 0,
                    2, 3, 4 => ($productIndex % 4) + 1,
                    default => 8 + (($pharmacyIndex + $productIndex) % 35),
                };

                $cost = 10 + (($pharmacyIndex + $productIndex) % 35);

                PharmacyProduct::create([
                    'pharmacy_id' => $pharmacy->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'cost_price' => $cost,
                    'default_sell_price' => round($cost * 1.35, 4),
                ]);
            }
        }
    }

    private function seedOrders($pharmacies, $warehouses, $products): void
    {
        $statuses = [
            Order::STATUS_PENDING,
            Order::STATUS_APPROVED,
            Order::STATUS_REJECTED,
            Order::STATUS_RECEIVED,
            Order::STATUS_ISSUE,
        ];

        foreach ($pharmacies as $pharmacyIndex => $pharmacy) {
            for ($i = 1; $i <= 10; $i++) {
                $warehouse = $warehouses[($pharmacyIndex + $i) % $warehouses->count()];
                $status = $statuses[($pharmacyIndex + $i) % count($statuses)];
                $items = $products->slice((($pharmacyIndex * 6) + $i) % 120, 3)->values();
                $total = 0;

                $order = Order::create([
                    'pharmacy_id' => $pharmacy->id,
                    'warehouse_id' => $warehouse->id,
                    'status' => $status,
                    'total_cost' => 0,
                    'approved_at' => in_array($status, [Order::STATUS_APPROVED, Order::STATUS_RECEIVED, Order::STATUS_ISSUE], true) ? now()->subDays($i + 3) : null,
                    'rejected_at' => $status === Order::STATUS_REJECTED ? now()->subDays($i + 2) : null,
                    'received_at' => $status === Order::STATUS_RECEIVED ? now()->subDays($i) : null,
                    'issue_at' => $status === Order::STATUS_ISSUE ? now()->subDays($i) : null,
                    'issue_note' => $status === Order::STATUS_ISSUE ? 'Some items need warehouse review.' : null,
                    'created_at' => now()->subDays($i + 10),
                    'updated_at' => now()->subDays($i),
                ]);

                foreach ($items as $itemIndex => $product) {
                    $unitCost = 12 + (($pharmacyIndex + $i + $itemIndex) % 30);
                    $quantity = 3 + (($i + $itemIndex) % 8);
                    $lineTotal = $unitCost * $quantity;
                    $total += $lineTotal;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                        'line_total' => $lineTotal,
                    ]);
                }

                $order->total_cost = $total;
                $order->save();
            }
        }
    }

    private function seedRatings($pharmacies, $warehouses): void
    {
        foreach ($warehouses->take(20) as $warehouseIndex => $warehouse) {
            for ($i = 0; $i < 4; $i++) {
                $pharmacy = $pharmacies[($warehouseIndex + $i) % $pharmacies->count()];

                if (!Order::query()
                    ->where('pharmacy_id', $pharmacy->id)
                    ->where('warehouse_id', $warehouse->id)
                    ->where('status', Order::STATUS_RECEIVED)
                    ->exists()) {
                    Order::create([
                        'pharmacy_id' => $pharmacy->id,
                        'warehouse_id' => $warehouse->id,
                        'status' => Order::STATUS_RECEIVED,
                        'total_cost' => 100,
                        'approved_at' => now()->subDays(8),
                        'received_at' => now()->subDays(5),
                    ]);
                }

                WarehouseRating::updateOrCreate(
                    [
                        'pharmacy_id' => $pharmacy->id,
                        'warehouse_id' => $warehouse->id,
                    ],
                    ['rating' => 3 + (($warehouseIndex + $i) % 3)]
                );
            }
        }
    }

    private function seedExpenses($pharmacies, $warehouses): void
    {
        $descriptions = ['Rent payment', 'Electricity bill', 'Maintenance cost', 'Delivery expense', 'Office supplies'];

        foreach ($pharmacies as $pharmacyIndex => $pharmacy) {
            for ($i = 1; $i <= 15; $i++) {
                ExpenseInvoice::create([
                    'pharmacy_id' => $pharmacy->id,
                    'amount' => 25 + (($pharmacyIndex + $i) * 7),
                    'created_by_name' => $pharmacy->doctor_name,
                    'description' => $descriptions[$i % count($descriptions)],
                    'created_at' => now()->subDays($i),
                    'updated_at' => now()->subDays($i),
                ]);
            }
        }

        foreach ($warehouses as $warehouseIndex => $warehouse) {
            for ($i = 1; $i <= 15; $i++) {
                ExpenseInvoice::create([
                    'warehouse_id' => $warehouse->id,
                    'amount' => 40 + (($warehouseIndex + $i) * 9),
                    'created_by_name' => $warehouse->owner_name,
                    'description' => $descriptions[($i + 1) % count($descriptions)],
                    'created_at' => now()->subDays($i),
                    'updated_at' => now()->subDays($i),
                ]);
            }
        }
    }

    private function seedSalesInvoices($pharmacies, $products): void
    {
        foreach ($pharmacies as $pharmacyIndex => $pharmacy) {
            for ($i = 1; $i <= 15; $i++) {
                $invoice = SalesInvoice::create([
                    'pharmacy_id' => $pharmacy->id,
                    'total_price' => 0,
                    'paid_total' => 0,
                    'discount_percent' => 0,
                    'feedback' => $i % 6 === 0 ? 'Customer requested a follow-up note.' : null,
                    'created_at' => now()->subDays($i),
                    'updated_at' => now()->subDays($i),
                ]);

                $total = 0;
                $items = $products->slice((($pharmacyIndex * 4) + $i) % 130, 2 + ($i % 3))->values();

                foreach ($items as $itemIndex => $product) {
                    $quantity = 1 + (($i + $itemIndex) % 4);
                    $unitPrice = 15 + (($pharmacyIndex + $i + $itemIndex) % 40);
                    $lineTotal = $quantity * $unitPrice;
                    $total += $lineTotal;

                    SalesInvoiceItem::create([
                        'sales_invoice_id' => $invoice->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ]);
                }

                $discountPercent = $i % 5 === 0 ? 22 : (($i % 4) * 3);
                $paidTotal = round($total * (1 - ($discountPercent / 100)), 4);

                $invoice->total_price = $total;
                $invoice->paid_total = $paidTotal;
                $invoice->discount_percent = $discountPercent;
                $invoice->save();
            }
        }
    }

    private function seedFeedbacks($pharmacies, $warehouses): void
    {
        $contents = [
            'Order delivery was smooth and on time.',
            'Some products need clearer expiry information.',
            'Warehouse response time was good.',
            'There was a quantity mismatch in one order.',
            'System workflow is easy to use.',
        ];

        foreach ($pharmacies->take(15) as $index => $pharmacy) {
            Feedback::create([
                'content' => $contents[$index % count($contents)],
                'pharmacy_id' => $pharmacy->id,
                'warehouse_id' => $warehouses[$index % $warehouses->count()]->id,
                'order_id' => Order::query()->where('pharmacy_id', $pharmacy->id)->value('id'),
            ]);
        }

        foreach ($warehouses->take(10) as $index => $warehouse) {
            Feedback::create([
                'content' => $contents[($index + 2) % count($contents)],
                'warehouse_id' => $warehouse->id,
            ]);
        }
    }
}
