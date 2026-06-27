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
        $regions = Region::query()->get()->values();
        $products = Product::query()->take(150)->get()->values();

        if ($regions->isEmpty() || $products->count() < 150) {
            throw new \RuntimeException('DemoDataSeeder requires regions and at least 150 products from openFDA.');
        }

        $admins = $this->seedAdmins($regions);
        $pharmacies = $this->seedPharmacies($regions, $admins);
        $warehouses = $this->seedWarehouses($regions, $admins);

        $this->seedWarehouseInventory($warehouses, $products);
        $this->seedPharmacyInventory($pharmacies, $products);
        $this->seedOrders($pharmacies, $warehouses);
        $this->seedRatings($pharmacies, $warehouses);
        $this->seedExpenses($pharmacies, $warehouses);
        $this->seedSalesInvoices($pharmacies);
        $this->seedFeedbacks($pharmacies, $warehouses);
    }

    private function seedAdmins($regions)
    {
        $adminProfiles = [
            ['Muhammad Hamzah Al Mzasri', 'muhammad.hamzah.almzasri@gmail.com', '0991000001'],
            ['Omar Al Sabbagh', 'omar.alsabbagh.admin@gmail.com', '0991000002'],
            ['Rama Darwish', 'rama.darwish.admin@gmail.com', '0991000003'],
            ['Laith Al Hakeem', 'laith.alhakeem.admin@gmail.com', '0991000004'],
            ['Nour Qabbani', 'nour.qabbani.admin@gmail.com', '0991000005'],
            ['Yazan Khoury', 'yazan.khoury.admin@gmail.com', '0991000006'],
            ['Maya Barakat', 'maya.barakat.admin@gmail.com', '0991000007'],
            ['Khaled Al Masri', 'khaled.almasri.admin@gmail.com', '0991000008'],
            ['Hiba Mansour', 'hiba.mansour.admin@gmail.com', '0991000009'],
            ['Fadi Shami', 'fadi.shami.admin@gmail.com', '0991000010'],
            ['Samar Haddad', 'samar.haddad.admin@gmail.com', '0991000011'],
            ['Tarek Al Khatib', 'tarek.alkhatib.admin@gmail.com', '0991000012'],
            ['Lina Al Ahmad', 'lina.alahmad.admin@gmail.com', '0991000013'],
            ['Basel Ibrahim', 'basel.ibrahim.admin@gmail.com', '0991000014'],
            ['Dima Najjar', 'dima.najjar.admin@gmail.com', '0991000015'],
            ['Anas Saleh', 'anas.saleh.admin@gmail.com', '0991000016'],
            ['Reem Al Ali', 'reem.alali.admin@gmail.com', '0991000017'],
            ['Samer Darzi', 'samer.darzi.admin@gmail.com', '0991000018'],
            ['Aya Al Omar', 'aya.alomar.admin@gmail.com', '0991000019'],
            ['Majd Al Halabi', 'majd.alhalabi.admin@gmail.com', '0991000020'],
            ['Bayan Al Kurdi', 'bayan.alkurdi.admin@gmail.com', '0991000021'],
            ['Wassim Nassar', 'wassim.nassar.admin@gmail.com', '0991000022'],
            ['Farah Rahal', 'farah.rahal.admin@gmail.com', '0991000023'],
            ['Hussein Al Jundi', 'hussein.aljundi.admin@gmail.com', '0991000024'],
            ['Dana Ismail', 'dana.ismail.admin@gmail.com', '0991000025'],
        ];

        return collect($adminProfiles)->map(function (array $profile, int $index) use ($regions) {
            return Admin::create([
                'name' => $profile[0],
                'email' => $profile[1],
                'phone' => $profile[2],
                'password' => self::PASSWORD,
                'role' => $index === 0 ? 'super_admin' : 'admin',
                'region_id' => $regions[$index % $regions->count()]->id,
            ]);
        });
    }

    private function seedPharmacies($regions, $admins)
    {
        $pharmacyProfiles = [
            ['Al Shifaa Pharmacy', 'Dr. Omar Al Zain', 'clashsy0@gmail.com', '0944000001'],
            ['Al Hayat Pharmacy', 'Dr. Leen Haddad', 'leen.haddad.pharmacy@gmail.com', '0944000002'],
            ['Al Amal Pharmacy', 'Dr. Tarek Nasser', 'tarek.nasser.pharmacy@gmail.com', '0944000003'],
            ['Al Nour Pharmacy', 'Dr. Sara Al Khatib', 'sara.alkhatib.pharmacy@gmail.com', '0944000004'],
            ['Al Salam Pharmacy', 'Dr. Karim Darwish', 'karim.darwish.pharmacy@gmail.com', '0944000005'],
            ['Damascus Care Pharmacy', 'Dr. Hala Mansour', 'hala.mansour.pharmacy@gmail.com', '0944000006'],
            ['Al Rawda Pharmacy', 'Dr. Firas Barakat', 'firas.barakat.pharmacy@gmail.com', '0944000007'],
            ['Al Quds Pharmacy', 'Dr. Rania Ibrahim', 'rania.ibrahim.pharmacy@gmail.com', '0944000008'],
            ['Al Andalus Pharmacy', 'Dr. Sami Khoury', 'sami.khoury.pharmacy@gmail.com', '0944000009'],
            ['Al Yasmeen Pharmacy', 'Dr. Dalia Saleh', 'dalia.saleh.pharmacy@gmail.com', '0944000010'],
            ['Al Farah Pharmacy', 'Dr. Basel Najjar', 'basel.najjar.pharmacy@gmail.com', '0944000011'],
            ['Al Razi Pharmacy', 'Dr. Nour Darzi', 'nour.darzi.pharmacy@gmail.com', '0944000012'],
            ['Ibn Sina Pharmacy', 'Dr. Anas Al Ali', 'anas.alali.pharmacy@gmail.com', '0944000013'],
            ['Al Hikma Pharmacy', 'Dr. Maya Rahal', 'maya.rahal.pharmacy@gmail.com', '0944000014'],
            ['Al Wafa Pharmacy', 'Dr. Youssef Shami', 'youssef.shami.pharmacy@gmail.com', '0944000015'],
            ['Al Basma Pharmacy', 'Dr. Reem Qabbani', 'reem.qabbani.pharmacy@gmail.com', '0944000016'],
            ['Al Safa Pharmacy', 'Dr. Majd Al Halabi', 'majd.halabi.pharmacy@gmail.com', '0944000017'],
            ['Al Mahaba Pharmacy', 'Dr. Hiba Al Omar', 'hiba.alomar.pharmacy@gmail.com', '0944000018'],
            ['Al Kindi Pharmacy', 'Dr. Wassim Nassar', 'wassim.nassar.pharmacy@gmail.com', '0944000019'],
            ['Al Tawfiq Pharmacy', 'Dr. Aya Ismail', 'aya.ismail.pharmacy@gmail.com', '0944000020'],
            ['Al Rahma Pharmacy', 'Dr. Fadi Mansour', 'fadi.mansour.pharmacy@gmail.com', '0944000021'],
            ['Al Bayan Pharmacy', 'Dr. Dana Al Kurdi', 'dana.kurdi.pharmacy@gmail.com', '0944000022'],
            ['Al Madina Pharmacy', 'Dr. Khaled Al Jundi', 'khaled.jundi.pharmacy@gmail.com', '0944000023'],
            ['Al Nahda Pharmacy', 'Dr. Farah Darwish', 'farah.darwish.pharmacy@gmail.com', '0944000024'],
            ['Al Amal Modern Pharmacy', 'Dr. Samer Al Ahmad', 'samer.alahmad.pharmacy@gmail.com', '0944000025'],
        ];

        $pharmacyCoordinates = [
            [33.513805, 36.276528],
            [33.502145, 36.298912],
            [33.519422, 36.308741],
            [33.486921, 36.293455],
            [33.529834, 36.289112],
            [33.507621, 36.319845],
            [33.489732, 36.272681],
            [33.535118, 36.305667],
            [33.498406, 36.334219],
            [33.521775, 36.254803],
            [33.474912, 36.306184],
            [33.544203, 36.276904],
            [33.515267, 36.345719],
            [33.493618, 36.246557],
            [33.527491, 36.326033],
            [33.482746, 36.323681],
            [33.538921, 36.293746],
            [33.506884, 36.263591],
            [33.469335, 36.286904],
            [33.550214, 36.314277],
            [33.517458, 36.235896],
            [33.496327, 36.357412],
            [33.462851, 36.318503],
            [33.556731, 36.269388],
            [33.525604, 36.360145],
        ];

        return collect($pharmacyProfiles)->map(function (array $profile, int $index) use ($regions, $admins, $pharmacyCoordinates) {
            return Pharmacy::create([
                'pharmacy_name' => $profile[0],
                'doctor_name' => $profile[1],
                'doctor_email' => $profile[2],
                'doctor_phone' => $profile[3],
                'password' => self::PASSWORD,
                'activated_at' => now()->subDays(90 - ($index % 45)),
                'region_id' => $regions[$index % $regions->count()]->id,
                'latitude' => $pharmacyCoordinates[$index][0],
                'longitude' => $pharmacyCoordinates[$index][1],
                'admin_id' => $admins[$index % $admins->count()]->id,
            ]);
        });
    }

    private function seedWarehouses($regions, $admins)
    {
        $warehouseProfiles = [
            ['Al Baraka Medical Warehouse', 'Obada Al Tarabishi', 'obadaaltarabishi@gmail.com', '0955000001'],
            ['Levant Pharma Distribution', 'Nizar Haddad', 'nizar.haddad.warehouse@gmail.com', '0955000002'],
            ['Cham Medical Supplies', 'Maher Al Khatib', 'maher.khatib.warehouse@gmail.com', '0955000003'],
            ['Al Razi Drug Store', 'Mazen Darwish', 'mazen.darwish.warehouse@gmail.com', '0955000004'],
            ['Ibn Sina Medical Warehouse', 'Ammar Mansour', 'ammar.mansour.warehouse@gmail.com', '0955000005'],
            ['Al Safa Pharma Store', 'Rami Khoury', 'rami.khoury.warehouse@gmail.com', '0955000006'],
            ['Nour Al Sham Medical', 'Hani Barakat', 'hani.barakat.warehouse@gmail.com', '0955000007'],
            ['Al Hayat Pharma Supply', 'Bilal Ibrahim', 'bilal.ibrahim.warehouse@gmail.com', '0955000008'],
            ['Al Qalamoun Drug Store', 'Salah Saleh', 'salah.saleh.warehouse@gmail.com', '0955000009'],
            ['Al Amal Medical Depot', 'Tamer Najjar', 'tamer.najjar.warehouse@gmail.com', '0955000010'],
            ['Al Watan Pharma', 'Karam Darzi', 'karam.darzi.warehouse@gmail.com', '0955000011'],
            ['Al Andalus Medical Store', 'Loai Al Ali', 'loai.alali.warehouse@gmail.com', '0955000012'],
            ['Al Yasmeen Pharma Depot', 'Jad Rahal', 'jad.rahal.warehouse@gmail.com', '0955000013'],
            ['Al Fouad Medical Warehouse', 'Murad Shami', 'murad.shami.warehouse@gmail.com', '0955000014'],
            ['Al Kindi Drug Distribution', 'Amer Qabbani', 'amer.qabbani.warehouse@gmail.com', '0955000015'],
            ['Al Hikma Pharma Supply', 'Hassan Al Halabi', 'hassan.halabi.warehouse@gmail.com', '0955000016'],
            ['Al Rahma Medical Depot', 'Wael Al Omar', 'wael.alomar.warehouse@gmail.com', '0955000017'],
            ['Al Bayan Pharma Store', 'Samir Nassar', 'samir.nassar.warehouse@gmail.com', '0955000018'],
            ['Al Madina Medical Supplies', 'Ibrahim Ismail', 'ibrahim.ismail.warehouse@gmail.com', '0955000019'],
            ['Al Nahda Drug Store', 'Ghassan Al Kurdi', 'ghassan.kurdi.warehouse@gmail.com', '0955000020'],
            ['Al Shifa Pharma Distribution', 'Taha Al Jundi', 'taha.jundi.warehouse@gmail.com', '0955000021'],
            ['Al Tawfiq Medical Warehouse', 'Kinan Darwish', 'kinan.darwish.warehouse@gmail.com', '0955000022'],
            ['Al Fajr Pharma Depot', 'Adel Al Ahmad', 'adel.alahmad.warehouse@gmail.com', '0955000023'],
            ['Al Omran Medical Store', 'Mounir Mansour', 'mounir.mansour.warehouse@gmail.com', '0955000024'],
            ['Al Rawabi Pharma Supply', 'Eyad Barakat', 'eyad.barakat.warehouse@gmail.com', '0955000025'],
        ];

        return collect($warehouseProfiles)->map(function (array $profile, int $index) use ($regions, $admins) {
            return Warehouse::create([
                'warehouse_name' => $profile[0],
                'owner_name' => $profile[1],
                'owner_email' => $profile[2],
                'owner_phone' => $profile[3],
                'password' => self::PASSWORD,
                'activated_at' => now()->subDays(85 - ($index % 40)),
                'region_id' => $regions[$index % $regions->count()]->id,
                'admin_id' => $admins[($index + 5) % $admins->count()]->id,
            ]);
        });
    }

    private function seedWarehouseInventory($warehouses, $products): void
    {
        foreach ($warehouses as $warehouseIndex => $warehouse) {
            for ($i = 0; $i < 70; $i++) {
                $product = $products[(($warehouseIndex * 11) + $i) % $products->count()];
                $cost = 6 + (($warehouseIndex * 3 + $i * 2) % 55);

                WarehouseProduct::create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'quantity' => 90 + (($warehouseIndex * 17 + $i * 9) % 260),
                    'reserved_quantity' => 0,
                    'cost_price' => $cost,
                    'sell_price_to_pharmacy' => round($cost * (1.18 + (($i % 5) * 0.02)), 4),
                ]);
            }
        }
    }

    private function seedPharmacyInventory($pharmacies, $products): void
    {
        foreach ($pharmacies as $pharmacyIndex => $pharmacy) {
            for ($i = 0; $i < 45; $i++) {
                $product = $products[(($pharmacyIndex * 9) + $i) % $products->count()];
                $quantity = match ($i % 12) {
                    0, 1 => 0,
                    2, 3, 4 => 1 + (($pharmacyIndex + $i) % 4),
                    default => 12 + (($pharmacyIndex * 4 + $i * 3) % 55),
                };
                $cost = 8 + (($pharmacyIndex * 2 + $i) % 45);

                PharmacyProduct::create([
                    'pharmacy_id' => $pharmacy->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'cost_price' => $cost,
                    'default_sell_price' => round($cost * (1.32 + (($i % 4) * 0.03)), 4),
                ]);
            }
        }
    }

    private function seedOrders($pharmacies, $warehouses): void
    {
        $statuses = [
            Order::STATUS_PENDING,
            Order::STATUS_APPROVED,
            Order::STATUS_REJECTED,
            Order::STATUS_RECEIVED,
            Order::STATUS_ISSUE,
        ];

        foreach ($pharmacies as $pharmacyIndex => $pharmacy) {
            for ($i = 0; $i < 10; $i++) {
                $warehouse = $warehouses[(($pharmacyIndex * 3) + $i) % $warehouses->count()];
                $status = $statuses[($pharmacyIndex + $i) % count($statuses)];

                $this->createOrderWithItems(
                    $pharmacy,
                    $warehouse,
                    $status,
                    18 + $i + ($pharmacyIndex % 7),
                    3 + ($i % 3),
                    ($pharmacyIndex * 5) + $i
                );
            }
        }
    }

    private function seedRatings($pharmacies, $warehouses): void
    {
        foreach ($warehouses->take(20) as $warehouseIndex => $warehouse) {
            for ($i = 0; $i < 4; $i++) {
                $pharmacy = $pharmacies[(($warehouseIndex * 2) + $i) % $pharmacies->count()];

                if (!Order::query()
                    ->where('pharmacy_id', $pharmacy->id)
                    ->where('warehouse_id', $warehouse->id)
                    ->where('status', Order::STATUS_RECEIVED)
                    ->exists()) {
                    $this->createOrderWithItems(
                        $pharmacy,
                        $warehouse,
                        Order::STATUS_RECEIVED,
                        7 + $i,
                        2 + ($i % 2),
                        ($warehouseIndex * 4) + $i
                    );
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
        $pharmacyDescriptions = [
            'Monthly pharmacy rent payment',
            'Electricity and generator subscription',
            'Refrigerator maintenance for temperature-sensitive medicines',
            'POS paper rolls and packaging bags',
            'Cleaning supplies and sanitizers',
            'Internet and pharmacy software subscription',
            'Local delivery service fees',
            'Staff meal and overtime allowance',
            'Medical shelves repair and labeling',
            'Municipal license renewal fees',
            'Water bill and utility service',
            'Small equipment replacement',
            'Expired medicine disposal service',
            'Pharmacy signboard maintenance',
            'Accounting and invoice archiving service',
        ];

        $warehouseDescriptions = [
            'Warehouse rent and storage space payment',
            'Cold-chain room electricity bill',
            'Delivery truck fuel and maintenance',
            'Loading workers daily allowance',
            'Inventory barcode labels and cartons',
            'Temperature monitoring device service',
            'Forklift maintenance cost',
            'Warehouse cleaning and pest control',
            'Internet and logistics software subscription',
            'Security system maintenance',
            'Product return handling fees',
            'Packaging materials purchase',
            'Shelving repair and storage pallets',
            'Vehicle insurance installment',
            'Administrative documents and permits',
        ];

        foreach ($pharmacies as $pharmacyIndex => $pharmacy) {
            foreach ($pharmacyDescriptions as $i => $description) {
                ExpenseInvoice::create([
                    'pharmacy_id' => $pharmacy->id,
                    'amount' => 18 + (($pharmacyIndex + 2) * ($i + 4)) + (($i % 4) * 11),
                    'created_by_name' => $pharmacy->doctor_name,
                    'description' => $description,
                    'created_at' => Carbon::now()->subDays($i + 1 + ($pharmacyIndex % 5)),
                    'updated_at' => Carbon::now()->subDays($i + 1 + ($pharmacyIndex % 5)),
                ]);
            }
        }

        foreach ($warehouses as $warehouseIndex => $warehouse) {
            foreach ($warehouseDescriptions as $i => $description) {
                ExpenseInvoice::create([
                    'warehouse_id' => $warehouse->id,
                    'amount' => 35 + (($warehouseIndex + 3) * ($i + 5)) + (($i % 5) * 17),
                    'created_by_name' => $warehouse->owner_name,
                    'description' => $description,
                    'created_at' => Carbon::now()->subDays($i + 2 + ($warehouseIndex % 6)),
                    'updated_at' => Carbon::now()->subDays($i + 2 + ($warehouseIndex % 6)),
                ]);
            }
        }
    }

    private function seedSalesInvoices($pharmacies): void
    {
        $feedbacks = [
            null,
            'Customer asked for a cheaper alternative next time.',
            null,
            'Prescription included chronic medication refill.',
            null,
            'Customer requested delivery to home.',
        ];

        foreach ($pharmacies as $pharmacyIndex => $pharmacy) {
            for ($i = 0; $i < 15; $i++) {
                $invoice = SalesInvoice::create([
                    'pharmacy_id' => $pharmacy->id,
                    'total_price' => 0,
                    'paid_total' => 0,
                    'discount_percent' => 0,
                    'feedback' => $feedbacks[($pharmacyIndex + $i) % count($feedbacks)],
                    'created_at' => Carbon::now()->subDays($i + 1 + ($pharmacyIndex % 4)),
                    'updated_at' => Carbon::now()->subDays($i + 1 + ($pharmacyIndex % 4)),
                ]);

                $inventory = PharmacyProduct::query()
                    ->where('pharmacy_id', $pharmacy->id)
                    ->where('quantity', '>', 3)
                    ->orderBy('id')
                    ->get()
                    ->values();

                $total = 0;
                $itemCount = 2 + ($i % 3);

                for ($j = 0; $j < $itemCount; $j++) {
                    $pharmacyProduct = $inventory[(($i * 3) + $j) % $inventory->count()];
                    $quantity = min(1 + (($i + $j) % 3), max(1, $pharmacyProduct->quantity - 2));
                    $unitPrice = $pharmacyProduct->default_sell_price;
                    $lineTotal = round($quantity * $unitPrice, 4);
                    $total += $lineTotal;

                    SalesInvoiceItem::create([
                        'sales_invoice_id' => $invoice->id,
                        'product_id' => $pharmacyProduct->product_id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ]);

                    $pharmacyProduct->decrement('quantity', $quantity);
                }

                $discountPercent = match ($i % 6) {
                    0 => 0,
                    1 => 3,
                    2 => 5,
                    3 => 8,
                    4 => 12,
                    default => 18,
                };

                $invoice->total_price = round($total, 4);
                $invoice->paid_total = round($total * (1 - ($discountPercent / 100)), 4);
                $invoice->discount_percent = $discountPercent;
                $invoice->save();
            }
        }
    }

    private function seedFeedbacks($pharmacies, $warehouses): void
    {
        $contents = [
            'The order arrived on time and product packaging was clean.',
            'One item had a close expiry date and needs review before next shipment.',
            'Warehouse support responded quickly to the pharmacy issue.',
            'The received quantity matched the invoice exactly.',
            'Prices were acceptable but delivery could be faster.',
            'The system made it easy to track order status.',
            'A replacement product was suggested and accepted by the pharmacy.',
            'The warehouse should add clearer batch information.',
            'Sales invoice workflow was smooth during peak hours.',
            'The pharmacy requested better notification timing for approved orders.',
        ];

        foreach ($pharmacies as $index => $pharmacy) {
            $order = Order::query()
                ->where('pharmacy_id', $pharmacy->id)
                ->whereIn('status', [Order::STATUS_RECEIVED, Order::STATUS_ISSUE])
                ->orderBy('id')
                ->first();

            Feedback::create([
                'content' => $contents[$index % count($contents)],
                'pharmacy_id' => $pharmacy->id,
                'warehouse_id' => $order?->warehouse_id ?? $warehouses[$index % $warehouses->count()]->id,
                'order_id' => $order?->id,
            ]);
        }

        foreach ($warehouses as $index => $warehouse) {
            $order = Order::query()
                ->where('warehouse_id', $warehouse->id)
                ->whereIn('status', [Order::STATUS_RECEIVED, Order::STATUS_ISSUE])
                ->orderBy('id')
                ->first();

            Feedback::create([
                'content' => $contents[($index + 4) % count($contents)],
                'pharmacy_id' => $order?->pharmacy_id,
                'warehouse_id' => $warehouse->id,
                'order_id' => $order?->id,
            ]);
        }
    }

    private function createOrderWithItems(Pharmacy $pharmacy, Warehouse $warehouse, string $status, int $daysAgo, int $itemCount, int $offset): Order
    {
        $createdAt = Carbon::now()->subDays($daysAgo);
        $order = Order::create([
            'pharmacy_id' => $pharmacy->id,
            'warehouse_id' => $warehouse->id,
            'status' => $status,
            'total_cost' => 0,
            'approved_at' => in_array($status, [Order::STATUS_APPROVED, Order::STATUS_RECEIVED, Order::STATUS_ISSUE], true) ? $createdAt->copy()->addDay() : null,
            'rejected_at' => $status === Order::STATUS_REJECTED ? $createdAt->copy()->addDays(2) : null,
            'received_at' => $status === Order::STATUS_RECEIVED ? $createdAt->copy()->addDays(4) : null,
            'issue_at' => $status === Order::STATUS_ISSUE ? $createdAt->copy()->addDays(4) : null,
            'issue_note' => $status === Order::STATUS_ISSUE ? 'Quantity or expiry date needs warehouse confirmation.' : null,
            'created_at' => $createdAt,
            'updated_at' => Carbon::now()->subDays(max(1, $daysAgo - 4)),
        ]);

        $inventory = WarehouseProduct::query()
            ->where('warehouse_id', $warehouse->id)
            ->whereColumn('quantity', '>', 'reserved_quantity')
            ->orderBy('id')
            ->get()
            ->values();

        $total = 0;

        for ($i = 0; $i < $itemCount; $i++) {
            $warehouseProduct = $inventory[($offset + $i) % $inventory->count()];
            $available = max(1, $warehouseProduct->quantity - $warehouseProduct->reserved_quantity);
            $quantity = min(2 + (($offset + $i) % 7), $available);
            $unitCost = $warehouseProduct->sell_price_to_pharmacy;
            $lineTotal = round($quantity * $unitCost, 4);
            $total += $lineTotal;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $warehouseProduct->product_id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
            ]);

            if (in_array($status, [Order::STATUS_PENDING, Order::STATUS_APPROVED, Order::STATUS_ISSUE], true)) {
                $warehouseProduct->increment('reserved_quantity', $quantity);
            }

            if ($status === Order::STATUS_RECEIVED) {
                $warehouseProduct->decrement('quantity', $quantity);
                $this->increasePharmacyStock($pharmacy, $warehouseProduct, $quantity);
            }
        }

        $order->total_cost = round($total, 4);
        $order->save();

        return $order;
    }

    private function increasePharmacyStock(Pharmacy $pharmacy, WarehouseProduct $warehouseProduct, int $quantity): void
    {
        $pharmacyProduct = PharmacyProduct::query()->firstOrCreate(
            [
                'pharmacy_id' => $pharmacy->id,
                'product_id' => $warehouseProduct->product_id,
            ],
            [
                'quantity' => 0,
                'cost_price' => $warehouseProduct->sell_price_to_pharmacy,
                'default_sell_price' => round($warehouseProduct->sell_price_to_pharmacy * 1.35, 4),
            ]
        );

        $pharmacyProduct->increment('quantity', $quantity);
        $pharmacyProduct->cost_price = $warehouseProduct->sell_price_to_pharmacy;
        $pharmacyProduct->default_sell_price = round($warehouseProduct->sell_price_to_pharmacy * 1.35, 4);
        $pharmacyProduct->save();
    }
}
