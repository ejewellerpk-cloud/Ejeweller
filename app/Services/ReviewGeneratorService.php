<?php

namespace App\Services;

use Exception;
use App\Enums\Ask;
use App\Enums\Role as EnumRole;
use App\Enums\ReviewStatus;
use App\Enums\Status;
use App\Http\Requests\AdminGenerateReviewsRequest;
use App\Libraries\QueryExceptionLibrary;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReviewGeneratorService
{
    private const MALE_NAMES = [
        'Ali Raza', 'Hassan Ahmed', 'Usman Khan', 'Bilal Hussain', 'Hamza Sheikh',
        'Ahmad Farooq', 'Saad Malik', 'Zain Ali', 'Omar Siddiqui', 'Fahad Iqbal',
        'Imran Shah', 'Tariq Mehmood', 'Noman Ashraf', 'Waqas Javed', 'Adeel Anwar',
        'Danish Qureshi', 'Kamran Baig', 'Shahzaib Nawaz', 'Haris Butt', 'Asad Rehman',
        'Muhammad Umer', 'Abdul Rehman', 'Syed Ali Hassan', 'M. Abdullah', 'Ghulam Mustafa',
    ];

    private const FEMALE_NAMES = [
        'Fatima Khan', 'Ayesha Bibi', 'Sana Malik', 'Hira Ahmed', 'Maryam Sheikh',
        'Noor Fatima', 'Iqra Hussain', 'Laiba Raza', 'Mahnoor Ali', 'Zara Siddiqui',
        'Amna Farooq', 'Saba Qureshi', 'Mehwish Anwar', 'Rabia Javed', 'Kinza Shah',
        'Areeba Nawaz', 'Bushra Iqbal', 'Nimra Baig', 'Sadia Mehmood', 'Hafsa Butt',
        'Saima Parveen', 'Shazia Naz', 'Rubina Kousar', 'Nadia Akhtar', 'Farah Naz',
    ];

    /** Gender-neutral English comments. */
    private const COMMENTS_EN = [
        'Really good product. Quality matched the pictures and packing was neat.',
        'Worth the money. Fabric/feel is better than I expected for this price.',
        'Received on time. Looks exactly like the listing. Happy with it.',
        'Nice quality overall. Slight color difference in light but still good.',
        'Recommended. My family liked it and I will order again.',
        'Decent product for the price. Delivery was quick too.',
        'Very satisfied. Size was accurate and finishing looks clean.',
        'Good purchase. Packaging was careful and product is as described.',
        'Amazing quality! Totally worth buying. Will recommend to friends.',
        'Product is fine. Nothing fancy but does the job well.',
        'Better than local market options. Happy with the order.',
        'Looks premium. Soft material and stitching seems strong.',
        'Ordered for gift and they loved it. Thanks Shopperzz.',
        'Simple and useful. Exactly what I needed.',
        'Quality is okay for daily use. Value for money.',
    ];

    private const COMMENTS_UR_MALE = [
        'بہت اچھی کوالٹی ہے، تصاویر جیسی ہی ملی۔ خوش ہوں۔',
        'قیمت کے حساب سے زبردست چیز ہے۔ دوبارہ ضرور لوں گا۔',
        'پیکنگ اچھی تھی اور ڈیلیوری بھی وقت پر ہو گئی۔',
        'بالکل ویسا ہی ہے جیسا سائٹ پر دکھایا گیا تھا۔ شکریہ۔',
        'فیملی کو پسند آیا۔ کوالٹی اچھی اور فٹنگ درست ہے۔',
        'اچھا پروڈکٹ ہے، معمولی فرق رنگ میں تھا مگر ٹھیک ہے۔',
        'پیسے کے قابل ہے۔ سلائی اور فنشنگ صاف ہے۔',
        'آرڈر کرنے کے قابل ہے۔ دوستوں کو ضرور بتائوں گا۔',
        'سمپل مگر کام کی چیز ہے۔ مطمئن ہوں۔',
        'مقامی مارکیٹ سے بہتر ملا۔ تجویز کرتا ہوں۔',
    ];

    private const COMMENTS_UR_FEMALE = [
        'بہت اچھی کوالٹی ہے، تصاویر جیسی ہی ملی۔ خوش ہوں۔',
        'قیمت کے حساب سے زبردست چیز ہے۔ دوبارہ ضرور لوں گی۔',
        'پیکنگ اچھی تھی اور ڈیلیوری بھی وقت پر ہو گئی۔',
        'بالکل ویسا ہی ہے جیسا سائٹ پر دکھایا گیا تھا۔ شکریہ۔',
        'فیملی کو پسند آیا۔ کوالٹی اچھی اور فٹنگ درست ہے۔',
        'اچھا پروڈکٹ ہے، معمولی فرق رنگ میں تھا مگر ٹھیک ہے۔',
        'پیسے کے قابل ہے۔ سلائی اور فنشنگ صاف ہے۔',
        'آرڈر کرنے کے قابل ہے۔ دوستوں کو ضرور بتائوں گی۔',
        'سمپل مگر کام کی چیز ہے۔ مطمئن ہوں۔',
        'مقامی مارکیٹ سے بہتر ملی۔ تجویز کرتی ہوں۔',
    ];

    private const COMMENTS_MIXED_MALE = [
        'Bohot achi quality hai, bilkul pictures jaisi. Worth it!',
        'Packing zabardast thi aur product bhi soft nikla. Happy with order.',
        'Size perfect tha. Delivery bhi fast hui. Recommended.',
        'Color thora light mein different laga magar overall acha hai.',
        'Value for money. Ghar walon ko pasand aa gaya.',
        'Material soft hai aur finishing clean. Dobara lunga.',
        'Same as shown. Local market se better quality mili.',
        'Nice product. Gift ke liye liya tha, unko bhi pasand aaya.',
        'Theek thaak hai daily use ke liye. Paisay wasool.',
        'Zabardast! Photo jaisi quality, no complaint.',
        'Delivery on time thi. Product solid feel deta hai.',
        'Bahut khush hun is purchase se. Will order again inshaAllah.',
        'Stitching strong hai. Size chart follow kiya to perfect fit.',
        'Overall satisfied. Thori si delay delivery mein thi warna 5 star.',
        'Achha deal mila. Recommend karta hun.',
    ];

    private const COMMENTS_MIXED_FEMALE = [
        'Bohot achi quality hai, bilkul pictures jaisi. Worth it!',
        'Packing zabardast thi aur product bhi soft nikla. Happy with order.',
        'Size perfect tha. Delivery bhi fast hui. Recommended.',
        'Color thora light mein different laga magar overall acha hai.',
        'Value for money. Ghar walon ko pasand aa gaya.',
        'Material soft hai aur finishing clean. Dobara lungi.',
        'Same as shown. Local market se better quality mili.',
        'Nice product. Gift ke liye li thi, unko bhi pasand aaya.',
        'Theek thaak hai daily use ke liye. Paisay wasool.',
        'Zabardast! Photo jaisi quality, no complaint.',
        'Delivery on time thi. Product solid feel deta hai.',
        'Bahut khush hun is purchase se. Will order again inshaAllah.',
        'Stitching strong hai. Size chart follow ki to perfect fit.',
        'Overall satisfied. Thori si delay delivery mein thi warna 5 star.',
        'Achha deal mila. Recommend karti hun.',
    ];

    /**
     * @throws Exception
     */
    public function generate(AdminGenerateReviewsRequest $request): array
    {
        try {
            $product = Product::findOrFail((int) $request->product_id);
            $quantity = (int) $request->quantity;
            $uploaded = $request->file('images', []) ?: [];
            $uploaded = array_values(array_filter($uploaded, fn ($file) => $file instanceof UploadedFile));

            $tempPaths = [];
            foreach ($uploaded as $file) {
                $stored = $file->store('temp/review-generator');
                if ($stored) {
                    $tempPaths[] = Storage::path($stored);
                }
            }

            $created = [];

            DB::transaction(function () use ($product, $quantity, $tempPaths, &$created) {
                $reviewers = $this->buildReviewerQueue($quantity);
                $usedComments = [];

                foreach ($reviewers as $i => $reviewer) {
                    $user = $this->resolveReviewer($reviewer['name'], $i);
                    $comment = $this->pickComment($usedComments, $reviewer['gender']);
                    $star = $this->pickStar();

                    $review = ProductReview::create([
                        'user_id'              => $user->id,
                        'product_id'           => $product->id,
                        'star'                 => $star,
                        'review'               => $comment,
                        'status'               => ReviewStatus::APPROVED,
                        'is_verified_purchase' => Ask::NO,
                    ]);

                    $this->attachRandomImages($review, $tempPaths);

                    $created[] = $review->load(['product', 'user']);
                }
            });

            foreach ($tempPaths as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            return $created;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * Alternate male/female reviewers so gender grammar stays consistent.
     *
     * @return array<int, array{name: string, gender: string}>
     */
    private function buildReviewerQueue(int $quantity): array
    {
        $males = self::MALE_NAMES;
        $females = self::FEMALE_NAMES;
        shuffle($males);
        shuffle($females);

        $queue = [];
        for ($i = 0; $i < $quantity; $i++) {
            $gender = ($i % 2 === 0) ? 'male' : 'female';
            $pool = $gender === 'male' ? $males : $females;
            $nameIndex = intdiv($i, 2) % count($pool);
            $name = $pool[$nameIndex];

            if (intdiv($i, 2) >= count($pool)) {
                $name .= ' ' . chr(65 + ($i % 26));
            }

            $queue[] = [
                'name'   => $name,
                'gender' => $gender,
            ];
        }

        shuffle($queue);

        return $queue;
    }

    private function resolveReviewer(string $name, int $index): User
    {
        $slug = Str::slug($name, '.');
        $email = 'reviewgen.' . $slug . '.' . $index . '@shopperzz.internal';

        $user = User::withTrashed()->where('email', $email)->first();

        if ($user) {
            if (method_exists($user, 'trashed') && $user->trashed()) {
                $user->restore();
            }
            if ($user->name !== $name) {
                $user->name = $name;
                $user->save();
            }
            if ((int) $user->status !== Status::ACTIVE) {
                $user->status = Status::ACTIVE;
                $user->save();
            }
            if (!$user->hasRole(EnumRole::CUSTOMER)) {
                $user->assignRole(EnumRole::CUSTOMER);
            }

            return $user;
        }

        // Prefer stable email without index when name is unique in pool
        $stableEmail = 'reviewgen.' . $slug . '@shopperzz.internal';
        $user = User::withTrashed()->where('email', $stableEmail)->first();
        if ($user && $user->name === $name) {
            if (method_exists($user, 'trashed') && $user->trashed()) {
                $user->restore();
            }
            if (!$user->hasRole(EnumRole::CUSTOMER)) {
                $user->assignRole(EnumRole::CUSTOMER);
            }

            return $user;
        }

        $emailToUse = $user ? $email : $stableEmail;

        $phoneBase = '3' . str_pad((string) (random_int(100000000, 999999999)), 9, '0', STR_PAD_LEFT);
        while (User::withTrashed()->where('phone', $phoneBase)->exists()) {
            $phoneBase = '3' . str_pad((string) (random_int(100000000, 999999999)), 9, '0', STR_PAD_LEFT);
        }

        $user = User::create([
            'name'              => $name,
            'email'             => $emailToUse,
            'phone'             => $phoneBase,
            'username'          => 'rg_' . Str::lower(Str::random(10)),
            'password'          => bcrypt(Str::random(16)),
            'email_verified_at' => now(),
            'status'            => Status::ACTIVE,
            'country_code'      => '+92',
            'is_guest'          => Ask::YES,
        ]);

        $user->assignRole(EnumRole::CUSTOMER);

        return $user;
    }

    private function pickComment(array &$used, string $gender): string
    {
        $bucketRoll = random_int(1, 100);
        if ($bucketRoll <= 40) {
            $pool = self::COMMENTS_EN;
        } elseif ($bucketRoll <= 70) {
            $pool = $gender === 'female' ? self::COMMENTS_MIXED_FEMALE : self::COMMENTS_MIXED_MALE;
        } else {
            $pool = $gender === 'female' ? self::COMMENTS_UR_FEMALE : self::COMMENTS_UR_MALE;
        }

        $available = array_values(array_diff($pool, $used));
        if (empty($available)) {
            $available = $pool;
            $used = array_values(array_diff($used, $pool));
        }

        $comment = $available[array_rand($available)];
        $used[] = $comment;

        if ($pool === self::COMMENTS_EN && random_int(0, 4) === 0) {
            $suffixes = ['', '', ' 👍', ' Highly recommended.', ' JazakAllah.', ''];
            $comment .= $suffixes[array_rand($suffixes)];
        }

        return trim($comment);
    }

    private function pickStar(): int
    {
        $roll = random_int(1, 100);
        if ($roll <= 55) {
            return 5;
        }
        if ($roll <= 85) {
            return 4;
        }
        if ($roll <= 95) {
            return 3;
        }

        return 2;
    }

    private function attachRandomImages(ProductReview $review, array $tempPaths): void
    {
        if (empty($tempPaths)) {
            return;
        }

        $roll = random_int(1, 100);
        if ($roll <= 15) {
            $count = 0;
        } elseif ($roll <= 75) {
            $count = 1;
        } else {
            $count = min(2, count($tempPaths));
        }

        if ($count === 0) {
            return;
        }

        $paths = $tempPaths;
        shuffle($paths);
        $selected = array_slice($paths, 0, $count);

        foreach ($selected as $path) {
            if (is_file($path)) {
                $review->addMedia($path)
                    ->preservingOriginal()
                    ->toMediaCollection('product-review');
            }
        }
    }
}
