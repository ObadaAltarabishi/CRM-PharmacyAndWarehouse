<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class ProductsSeeder extends Seeder
{
    public function run(): void
    {
        $targets = [
            'acetaminophen',
            'ibuprofen',
            'amoxicillin',
            'azithromycin',
            'metformin',
            'atorvastatin',
            'simvastatin',
            'omeprazole',
            'pantoprazole',
            'esomeprazole',
            'famotidine',
            'cetirizine',
            'loratadine',
            'fexofenadine',
            'montelukast',
            'albuterol',
            'prednisone',
            'dexamethasone',
            'diclofenac',
            'naproxen',
            'clopidogrel',
            'aspirin',
            'amlodipine',
            'losartan',
            'valsartan',
            'lisinopril',
            'metoprolol',
            'carvedilol',
            'furosemide',
            'spironolactone',
            'hydrochlorothiazide',
            'levothyroxine',
            'glimepiride',
            'sitagliptin',
            'ondansetron',
        ];

        $inserted = 0;
        $max = 100;

        foreach ($targets as $term) {
            if ($inserted >= $max) {
                break;
            }

            $response = Http::get('https://api.fda.gov/drug/ndc.json', [
                'search' => 'generic_name:"' . $term . '" AND finished:true',
                'limit' => 5,
            ]);

            if (!$response->ok()) {
                continue;
            }

            $results = $response->json('results') ?? [];

            foreach ($results as $row) {
                if ($inserted >= $max) {
                    break;
                }

                $barcode = $row['product_ndc'] ?? null;
                if (!$barcode) {
                    continue;
                }

                $active = $row['active_ingredients'][0] ?? null;
                $strength = $active['strength'] ?? null;

                $name = $row['brand_name'] ?? $row['generic_name'] ?? null;
                $company = $row['labeler_name'] ?? null;
                $form = $row['dosage_form'] ?? null;

                if (!$name || !$strength || !$company) {
                    continue;
                }

                $created = Product::query()->firstOrCreate(
                    ['barcode' => $barcode],
                    [
                        'name' => $name,
                        'strength' => $strength,
                        'company_name' => $company,
                        'form' => $form,
                    ]
                );

                if ($created->wasRecentlyCreated) {
                    $inserted++;
                }
            }
        }
    }
}
