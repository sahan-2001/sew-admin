<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Site;

class SiteSeeder extends Seeder
{
    public function run()
    {
        Site::create(['name' => 'Head Office', 'code' => 'HO']);
        Site::create(['name' => 'Branch 01', 'code' => 'B01']);
        Site::create(['name' => 'Branch 02', 'code' => 'B02']);
    }
}