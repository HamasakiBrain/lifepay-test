<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Booking;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        Booking::create([
            'name' => 'Test User',
            'status' => 1,
            'amount' => 100,
            'description' => 'Test description',
            'method' => 'sbp',
            'customer_phone' => '+79999999999',
            'customer_email' => 'email@a.com',
            'orderId' => 1,
        ]);
    }
}
