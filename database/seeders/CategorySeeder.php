<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public const NAMES = [
        'Hand Painted Wear',
        'Crochet Wear',
        'Bags & Pouches',
        'Home Decor',
        'Accessories & Gifts',
    ];

    public function run(): void
    {
        foreach (self::NAMES as $name) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }
    }
}
