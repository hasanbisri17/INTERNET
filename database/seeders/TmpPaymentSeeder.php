<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\PaymentMethod;
use App\Models\Payment;

class TmpPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $customer = Customer::firstOrCreate(
            ['name' => 'Test Customer'],
            [
                'email' => null,
                'phone' => '0800000000',
                'address' => 'Test Address',
            ]
        );

        $package = InternetPackage::firstOrCreate(
            ['name' => 'Basic 10Mbps'],
            [
                'price' => 100000,
                'speed' => '10Mbps',
                'description' => 'Basic internet package for testing',
                'is_active' => true,
            ]
        );

        $method = PaymentMethod::firstOrCreate(
            ['code' => 'gateway'],
            [
                'name' => 'Gateway',
                'type' => 'e_wallet',
                'provider' => 'Test',
                'is_active' => true,
            ]
        );

        Payment::firstOrCreate(
            ['invoice_number' => 'INV-TEST-0001'],
            [
                'customer_id' => $customer->id,
                'internet_package_id' => $package->id,
                'amount' => 150000,
                'due_date' => now(),
                'status' => 'pending',
                'payment_method_id' => $method->id,
            ]
        );
    }
}