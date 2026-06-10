<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Http\Client\ConnectionException;
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
            'doxycycline',
            'ciprofloxacin',
            'cephalexin',
            'cefuroxime',
            'clindamycin',
            'fluconazole',
            'acyclovir',
            'oseltamivir',
            'insulin',
            'empagliflozin',
            'dapagliflozin',
            'gliclazide',
            'rosuvastatin',
            'bisoprolol',
            'atenolol',
            'diltiazem',
            'verapamil',
            'enalapril',
            'candesartan',
            'telmisartan',
            'warfarin',
            'rivaroxaban',
            'apixaban',
            'nitroglycerin',
            'isosorbide',
            'salbutamol',
            'budesonide',
            'fluticasone',
            'tiotropium',
            'ipratropium',
            'mometasone',
            'hydrocortisone',
            'methylprednisolone',
            'ketorolac',
            'meloxicam',
            'celecoxib',
            'tramadol',
            'gabapentin',
            'pregabalin',
            'sertraline',
            'fluoxetine',
            'escitalopram',
            'amitriptyline',
            'diazepam',
            'lorazepam',
            'alprazolam',
            'zolpidem',
            'phenytoin',
            'carbamazepine',
            'valproate',
            'lamotrigine',
            'levetiracetam',
            'haloperidol',
            'risperidone',
            'quetiapine',
            'olanzapine',
            'metoclopramide',
            'domperidone',
            'loperamide',
            'bisacodyl',
            'lactulose',
            'senna',
            'ferrous',
            'folic acid',
            'cyanocobalamin',
            'cholecalciferol',
            'calcium',
            'magnesium',
            'zinc',
            'potassium',
            'sodium chloride',
            'chlorpheniramine',
            'diphenhydramine',
            'hydroxyzine',
            'ranitidine',
            'sucralfate',
            'simethicone',
            'mebendazole',
            'albendazole',
            'permethrin',
            'mupirocin',
            'clotrimazole',
            'ketoconazole',
            'terbinafine',
            'lidocaine',
            'benzocaine',
            'timolol',
            'latanoprost',
            'brimonidine',
            'ciprofloxacin ophthalmic',
            'ofloxacin',
            'tobramycin',
            'levocetirizine',
            'desloratadine',
            'pseudoephedrine',
            'guaifenesin',
            'dextromethorphan',
            'nystatin',
            'metronidazole',
            'tinidazole',
            'nitrofurantoin',
            'tamsulosin',
            'finasteride',
            'sildenafil',
            'ethinyl estradiol',
            'levonorgestrel',
            'progesterone',
            'oxytocin',
        ];

        $inserted = 0;
        $max = 150;
        $apiKey = config('services.openfda.key');

        foreach ($targets as $term) {
            if ($inserted >= $max) {
                break;
            }

            $query = [
                'search' => 'generic_name:"' . $term . '" AND finished:true',
                'limit' => 5,
            ];

            if ($apiKey) {
                $query['api_key'] = $apiKey;
            }

            try {
                $response = Http::timeout(15)
                    ->retry(2, 1000, throw: false)
                    ->get('https://api.fda.gov/drug/ndc.json', $query);
            } catch (ConnectionException) {
                continue;
            }

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

            usleep(250000);
        }
    }
}
