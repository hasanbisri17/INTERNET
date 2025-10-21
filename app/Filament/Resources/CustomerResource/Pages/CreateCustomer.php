<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate credentials based on connection type
        if ($data['connection_type'] === 'pppoe') {
            // Generate PPPOE username and password
            $data['pppoe_username'] = $this->generatePPPOEUsername();
            $data['pppoe_password'] = $this->generatePPPOEPassword();
            $data['customer_id'] = null; // Clear customer_id for PPPOE
        } elseif ($data['connection_type'] === 'static') {
            // Generate customer ID for STATIC
            $data['customer_id'] = $this->generateCustomerID();
            $data['pppoe_username'] = null; // Clear PPPOE credentials for STATIC
            $data['pppoe_password'] = null;
        }

        return $data;
    }

    /**
     * Generate unique PPPOE username
     */
    protected function generatePPPOEUsername(): string
    {
        do {
            // Format: pppoe_XXXXXXXX (8 random characters)
            $username = 'pppoe_' . strtolower(Str::random(8));
        } while (Customer::where('pppoe_username', $username)->exists());

        return $username;
    }

    /**
     * Generate secure PPPOE password
     */
    protected function generatePPPOEPassword(): string
    {
        // Generate 12 character password with mixed case, numbers, and special chars
        return Str::password(12);
    }

    /**
     * Generate unique customer ID for STATIC connections
     */
    protected function generateCustomerID(): string
    {
        do {
            // Format: CID-XXXXXX (6 digit number)
            $customerId = 'CID-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Customer::where('customer_id', $customerId)->exists());

        return $customerId;
    }
}
