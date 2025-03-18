<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            ['city' => 'New York', 'state' => 'NY', 'country' => 'USA'],
            ['city' => 'San Francisco', 'state' => 'CA', 'country' => 'USA'],
            ['city' => 'Los Angeles', 'state' => 'CA', 'country' => 'USA'],
            ['city' => 'Chicago', 'state' => 'IL', 'country' => 'USA'],
            ['city' => 'Austin', 'state' => 'TX', 'country' => 'USA'],
            ['city' => 'Seattle', 'state' => 'WA', 'country' => 'USA'],
            ['city' => 'Boston', 'state' => 'MA', 'country' => 'USA'],
            ['city' => 'London', 'state' => null, 'country' => 'UK'],
            ['city' => 'Berlin', 'state' => null, 'country' => 'Germany'],
            ['city' => 'Paris', 'state' => null, 'country' => 'France'],
            ['city' => 'Toronto', 'state' => 'ON', 'country' => 'Canada'],
            ['city' => 'Sydney', 'state' => 'NSW', 'country' => 'Australia'],
            ['city' => 'Singapore', 'state' => null, 'country' => 'Singapore'],
            ['city' => 'Tokyo', 'state' => null, 'country' => 'Japan'],
            ['city' => 'Bangalore', 'state' => 'Karnataka', 'country' => 'India'],
        ];

        foreach ($locations as $location) {
            Location::updateOrCreate(
                [
                    'city' => $location['city'],
                    'state' => $location['state'],
                    'country' => $location['country']
                ]
            );
        }
    }
}
