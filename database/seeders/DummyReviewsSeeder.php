<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\User;
use App\Models\ProductReview;
use Illuminate\Support\Facades\DB;

class DummyReviewsSeeder extends Seeder
{
    public function run()
    {
        $products = Product::inRandomOrder()->limit(10)->get();
        $user = User::first() ?? User::factory()->create();

        $reviews = [
            'Absolutely amazing product! Totally worth the price.',
            'Quality is surprisingly good, fast delivery as well.',
            'Loved the packaging. The product matches the description perfectly.',
            'Highly recommended! Will definitely buy again.',
            'Good value for money. Looks exactly like the pictures.',
            'Decent product for this price range. Satisfied with the purchase.',
        ];

        // Dummy images for reviews
        $dummyImages = [
            'https://plus.unsplash.com/premium_photo-1675896084254-dcb626387e1e?q=80&w=3535&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=3540&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=3499&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?q=80&w=3540&auto=format&fit=crop',
        ];

        foreach ($products as $product) {
            $numReviews = rand(3, 8);
            for ($i = 0; $i < $numReviews; $i++) {
                $review = ProductReview::create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'star' => rand(4, 5),
                    'review' => $reviews[array_rand($reviews)],
                ]);

                // Add 1 or 2 images to some reviews
                if (rand(0, 1) === 1) {
                    $numImages = rand(1, 2);
                    for ($j = 0; $j < $numImages; $j++) {
                        $localFile = public_path('images/default/product/cover.png');
                        if (file_exists($localFile)) {
                            $review->addMedia($localFile)
                                ->preservingOriginal()
                                ->toMediaCollection('product-review');
                        }
                    }
                }
            }
        }
    }
}
