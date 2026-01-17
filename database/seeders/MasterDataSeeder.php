<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeliveryMethod;
use App\Models\DeliveryTerm;
use App\Models\Currency;
use App\Models\PaymentTerm;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $siteId = session('site_id') ?? 1; 
        $userId = auth()->check() ? auth()->id() : 1; 

        // --- Delivery Methods (common options) ---
        $deliveryMethods = [
            ['name' => 'Courier', 'description' => 'Delivered via courier service'],
            ['name' => 'Pickup', 'description' => 'Customer pickup from site'],
            ['name' => 'Freight', 'description' => 'Delivered via freight company'],
            ['name' => 'Postal Service', 'description' => 'Delivered via postal service'],
            ['name' => 'Air Cargo', 'description' => 'Delivered via air cargo service'],
            ['name' => 'Sea Freight', 'description' => 'Delivered via shipping line'],
            ['name' => 'Truck', 'description' => 'Delivered via trucking service'],
            ['name' => 'Hand Delivery', 'description' => 'Delivered by hand locally'],
        ];

        foreach ($deliveryMethods as $method) {
            DeliveryMethod::create(array_merge($method, [
                'site_id' => $siteId,
                'created_by' => $userId,
                'updated_by' => $userId
            ]));
        }

        // --- Delivery Terms (common trade terms) ---
        $deliveryTerms = [
            ['name' => 'EXW', 'description' => 'Ex Works (seller delivers at own premises)'],
            ['name' => 'FCA', 'description' => 'Free Carrier (delivery to carrier)'],
            ['name' => 'CPT', 'description' => 'Carriage Paid To (seller pays freight)'],
            ['name' => 'CIP', 'description' => 'Carriage and Insurance Paid To'],
            ['name' => 'DAP', 'description' => 'Delivered At Place (buyer receives at location)'],
            ['name' => 'DPU', 'description' => 'Delivered at Place Unloaded'],
            ['name' => 'DDP', 'description' => 'Delivered Duty Paid (seller responsible for duties)'],
            ['name' => 'FOB', 'description' => 'Free On Board (goods loaded on ship)'],
            ['name' => 'CFR', 'description' => 'Cost and Freight (seller pays shipping)'],
            ['name' => 'CIF', 'description' => 'Cost, Insurance, Freight (insurance included)'],
        ];

        foreach ($deliveryTerms as $term) {
            DeliveryTerm::create(array_merge($term, [
                'site_id' => $siteId,
                'created_by' => $userId,
                'updated_by' => $userId
            ]));
        }

        // --- Payment Terms (common business terms) ---
        $paymentTerms = [
            ['name' => 'Advance', 'description' => 'Full payment before delivery'],
            ['name' => 'Cash on Delivery', 'description' => 'Payment upon delivery'],
            ['name' => 'Net 7', 'description' => 'Payment due in 7 days'],
            ['name' => 'Net 15', 'description' => 'Payment due in 15 days'],
            ['name' => 'Net 30', 'description' => 'Payment due in 30 days'],
            ['name' => 'Net 45', 'description' => 'Payment due in 45 days'],
            ['name' => 'Net 60', 'description' => 'Payment due in 60 days'],
            ['name' => 'Net 90', 'description' => 'Payment due in 90 days'],
            ['name' => 'Letter of Credit', 'description' => 'Payment through LC issued by bank'],
            ['name' => 'Partial Payment', 'description' => 'Payment made in installments'],
        ];

        foreach ($paymentTerms as $term) {
            PaymentTerm::create(array_merge($term, [
                'site_id' => $siteId,
                'created_by' => $userId,
                'updated_by' => $userId
            ]));
        }

        // --- Currencies (most commonly used ISO 4217 codes) ---
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'is_active' => true],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_active' => true],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'is_active' => true],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'is_active' => true],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'is_active' => true],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$', 'is_active' => true],
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF', 'is_active' => true],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'is_active' => true],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'is_active' => true],
            ['code' => 'LKR', 'name' => 'Sri Lankan Rupee', 'symbol' => 'Rs', 'is_active' => true],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'is_active' => true],
            ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'is_active' => true],
        ];

        foreach ($currencies as $currency) {
            Currency::create(array_merge($currency, [
                'site_id' => $siteId,
                'created_by' => $userId,
                'updated_by' => $userId
            ]));
        }
    }
}
