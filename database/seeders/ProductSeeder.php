<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    private const CATALOG = [
        'Hand Painted Wear' => [
            ['Alpona Cotton Saree', 'soft cotton', 3850, 4], ['Shimul Floral Khadi Kurti', 'handwoven khadi', 2150, 5], ['Rongdhonu Cotton T-Shirt', '100% cotton jersey', 950, 8], ['Kashful Hand Painted Dupatta', 'cotton voile', 1250, 6], ['Mrittika Brushstroke Panjabi', 'cotton slub', 2850, 3],
        ],
        'Crochet Wear' => [
            ['Sunset Granny Square Top', 'soft cotton yarn', 1850, 3], ['Cloud Puff Stitch Cardigan', 'acrylic-wool blend yarn', 3250, 2], ['Moonlight Shell Stitch Shawl', 'cotton-viscose yarn', 2450, 3], ['Forest Green Crochet Sweater', 'soft acrylic yarn', 3100, 2], ['Daisy Mesh Summer Top', 'mercerised cotton yarn', 2050, 3],
        ],
        'Bags & Pouches' => [
            ['Krishnochura Painted Canvas Tote', 'washed cotton canvas', 850, 10], ['Shapla Hand Painted Market Tote', 'heavy cotton canvas', 950, 8], ['Lilac Granny Square Shoulder Bag', 'cotton yarn', 1450, 5], ['Sage Bucket Crochet Bag', 'recycled cotton yarn', 1350, 6], ['Alpona Cotton Travel Pouch', 'cotton canvas', 420, 12],
        ],
        'Home Decor' => [
            ['Marigold Flower Coaster Set', 'cotton yarn', 450, 15], ['Sunrise Arch Wall Hanging', 'cotton cord and wooden dowel', 1150, 5], ['Lotus Lace Table Runner', 'cotton thread', 1650, 3], ['Sage Filet Crochet Table Runner', 'mercerised cotton yarn', 1850, 2], ['Nokshi Leaf Cushion Cover', 'cotton yarn', 950, 6],
        ],
        'Accessories & Gifts' => [
            ['Daisy Crochet Hair Clip Pair', 'cotton yarn on snap clips', 280, 18], ['Mini Mushroom Keychain', 'cotton yarn with steel ring', 260, 15], ['Rainbow Heart Scrunchie', 'cotton yarn and elastic', 320, 14], ['Sleepy Fox Amigurumi', 'cotton yarn with hypoallergenic filling', 850, 5], ['Momo the Crochet Bear', 'soft chenille yarn with safety eyes', 950, 4],
        ],
    ];

    public function run(): void
    {
        foreach (self::CATALOG as $categoryName => $products) {
            $category = Category::where('name', $categoryName)->firstOrFail();

            foreach ($products as [$name, $material, $price, $stock]) {
                Product::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'description' => $this->description($name, $material),
                        'image_path' => null,
                        'price' => $price,
                        'stock' => $stock,
                    ],
                );
            }
        }
    }

    private function description(string $name, string $material): string
    {
        $painted = str_contains($name, 'Painted') || str_contains($name, 'Alpona') || str_contains($name, 'Brush') || str_contains($name, 'Floral') || str_contains($name, 'Krishnochura') || str_contains($name, 'Shapla') || str_contains($name, 'Nokshi');
        $craft = $painted ? 'hand painted' : 'handmade';

        return "{$name} is a {$craft} piece made with {$material}. It is created in small batches and may be made to order, so slight variations are part of its handmade character. Hand wash in cold water with mild detergent, do not bleach, and dry flat or in the shade. Please allow 5–7 working days for made-to-order preparation.";
    }
}
