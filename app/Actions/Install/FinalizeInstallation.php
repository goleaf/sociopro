<?php

namespace App\Actions\Install;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FinalizeInstallation
{
    public function handle(array $data, ?string $purchaseCode = null): User
    {
        return DB::transaction(function () use ($data, $purchaseCode): User {
            $settings = [
                'system_name' => $data['system_name'],
            ];

            if ($purchaseCode) {
                $settings['purchase_code'] = $purchaseCode;
            }

            foreach ($settings as $type => $description) {
                Setting::where('type', $type)->update([
                    'description' => $description,
                ]);
            }

            return User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'user_role' => 'admin',
                'friends' => json_encode([]),
                'gender' => 'male',
                'address' => $data['admin_address'],
                'phone' => $data['admin_phone'],
                'date_of_birth' => time(),
                'timezone' => $data['timezone'],
                'email_verified_at' => date('Y-m-d H:i:s', time()),
            ]);
        });
    }
}
