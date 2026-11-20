<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Realistic product names per category.
     * Keys must match the names in CategoryFactory exactly.
     */
    private const CATALOG = [
        'Hand Painted Wear' => ['Cotton Saree', 'Khadi Kurti', 'Painted T-Shirt', 'Cotton Dupatta', 'Khadi Panjabi'],
        'Crochet Wear' => ['Crochet Top', 'Crochet Cardigan', 'Crochet Shawl', 'Crochet Sweater'],
        'Bags & Pouches' => ['Painted Tote Bag', 'Crochet Shoulder Bag', 'Canvas Pouch', 'Crochet Market Bag'],
        'Home Decor' => ['Crochet Coaster Set', 'Wall Hanging', 'Table Runner', 'Cushion Cover'],
        'Accessories & Gifts' => ['Crochet Hair Clips', 'Yarn Keychain', 'Crochet Scrunchie', 'Amigurumi Toy'],
    ];

    private const VARIANTS = ['', '', 'Pro', 'Mini', 'Plus', 'Lite'];

    /**
     * Default: attach to a random existing category (or create one),
     * with a name that matches it.
     */
    public function definition(): array
    {
        $category = Category::inRandomOrder()->first() ?? Category::factory()->create();

        return array_merge($this->attributesFor($category), [
            'price' => fake()->numberBetween(10, 15000),
            'stock' => 3,
        ]);
    }

    /**
     * Use this when seeding so the product belongs to a specific category.
     */
    public function forCategory(Category $category): static
    {
        return $this->state(fn () => $this->attributesFor($category));
    }

    private function attributesFor(Category $category): array
    {
        $names = self::CATALOG[$category->name] ?? null;

        // Fallback for custom categories that aren't in the catalog above
        $base = $names ? fake()->randomElement($names) : ucfirst(fake()->words(2, true));
        $name = trim($base . ' ' . fake()->randomElement(self::VARIANTS));

        return [
            'category_id' => $category->id,
            'name' => $name,
            // random suffix keeps the unique slug from colliding on repeated names
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(5)),
            'description' => "A reliable {$name} from our {$category->name} collection. Great value and built to last.",
        ];
    }
}
