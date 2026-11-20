<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Hand Painted Wear',
            'Crochet Wear',
            'Bags & Pouches',
            'Home Decor',
            'Accessories & Gifts',
        ]);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
        ];
    }
}
