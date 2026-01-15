<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Site;

class SiteSeeder extends Seeder
{
    public function run()
    {
        Site::create(['name' => 'Head Office', 'code' => 'HO', 'created_by' => 1, 'updated_by' => 1]);
        Site::create(['name' => 'Branch 01', 'code' => 'B01', 'created_by' => 1, 'updated_by' => 1]);
        Site::create(['name' => 'Branch 02', 'code' => 'B02', 'created_by' => 1, 'updated_by' => 1]);
    }
}