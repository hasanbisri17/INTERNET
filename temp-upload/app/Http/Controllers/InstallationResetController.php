<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class InstallationController extends Controller
{
    // ... existing code ...

    public function reset()
    {
        try {
            // Remove .env file
            if (File::exists(base_path('.env'))) {
                File::delete(base_path('.env'));
            }

            // Remove installed marker
            if (File::exists(storage_path('installed'))) {
                File::delete(storage_path('installed'));
            }

            // Clear all caches
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => 'Installation reset successfully!'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reset failed: ' . $e->getMessage()
            ]);
        }
    }
}
