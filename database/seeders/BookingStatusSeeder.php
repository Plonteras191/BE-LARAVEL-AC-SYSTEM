<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BookingStatus;

class BookingStatusSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            'Pending',
            'Accepted',
            'Completed',
            'Rejected'
        ];

        foreach ($statuses as $status) {
            BookingStatus::firstOrCreate([
                'status_name' => $status
            ]);
        }
    }
}
