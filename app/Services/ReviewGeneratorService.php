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
use Carbon\Carbon;
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
                $timestamps = $this->buildReviewTimestamps($product, $quantity);
                $usedComments = [];

                foreach ($reviewers as $i => $reviewer) {
                    $user = $this->resolveReviewer($reviewer['name'], $i);
                    $comment = $this->pickComment($usedComments, $reviewer['gender']);
                    $star = $this->pickStar();
                    $postedAt = $timestamps[$i];

                    $review = new ProductReview([
                        'user_id'              => $user->id,
                        'product_id'           => $product->id,
                        'star'                 => $star,
                        'review'               => $comment,
                        'status'               => ReviewStatus::APPROVED,
                        'is_verified_purchase' => random_int(1, 100) <= 55 ? Ask::YES : Ask::NO,
                    ]);
                    $review->created_at = $postedAt;
                    $review->updated_at = $postedAt;
                    $review->save();

                    $this->attachRandomImages($review, $tempPaths);
                    $this->backdateReviewMedia($review, $postedAt);

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
     * Marketplace-style posting times:
     * - Always after product created_at (+ delivery lag)
     * - Never in the future
     * - Spread over the listing lifetime with mild recency bias
     * - Prefer Pakistan evening / lunch posting hours
     * - Avoid identical timestamps
     *
     * @return array<int, Carbon>
     */
    private function buildReviewTimestamps(Product $product, int $quantity): array
    {
        $now = Carbon::now();
        $listedAt = $product->created_at
            ? Carbon::parse($product->created_at)
            : $now->copy()->subMonths(3);

        // Real buyers usually review after delivery (a few days), not instantly at listing.
        $earliest = $listedAt->copy()->addDays(random_int(2, 8))->addHours(random_int(1, 10));
        if ($earliest->greaterThanOrEqualTo($now)) {
            $earliest = $listedAt->copy()->addHours(random_int(4, 48));
        }
        if ($earliest->greaterThanOrEqualTo($now)) {
            $earliest = $listedAt->copy()->addMinutes(random_int(15, 180));
        }
        if ($earliest->greaterThanOrEqualTo($now)) {
            $earliest = $now->copy()->subMinutes(max(5, $quantity * 3));
        }

        $latest = $now->copy()->subMinutes(random_int(20, 360));
        if ($latest->lte($earliest)) {
            $latest = $now->copy()->subMinutes(5);
        }

        $startTs = $earliest->getTimestamp();
        $endTs = max($startTs + 60, $latest->getTimestamp());
        $span = max(1, $endTs - $startTs);

        $timestamps = [];
        for ($i = 0; $i < $quantity; $i++) {
            $u = mt_rand() / mt_getrandmax();
            // ~65% recent-weighted (like live stores), ~35% flatter older trail
            $position = random_int(1, 100) <= 65 ? ($u * $u) : $u;
            $base = Carbon::createFromTimestamp((int) round($startTs + ($span * $position)));

            $hourRoll = random_int(1, 100);
            if ($hourRoll <= 48) {
                $hour = random_int(19, 23); // evening peak
            } elseif ($hourRoll <= 72) {
                $hour = random_int(11, 14); // lunch / afternoon
            } elseif ($hourRoll <= 90) {
                $hour = random_int(15, 18);
            } else {
                $hour = random_int(8, 10);
            }

            $base->setTime($hour, random_int(0, 59), random_int(0, 59));

            if ($base->lt($earliest)) {
                $base = $earliest->copy()->addMinutes(random_int(5, 120));
            }
            if ($base->gt($now)) {
                $base = $now->copy()->subMinutes(random_int(5, 90));
            }

            $timestamps[] = $base;
        }

        usort($timestamps, static fn (Carbon $a, Carbon $b) => $a->getTimestamp() <=> $b->getTimestamp());

        for ($i = 1; $i < count($timestamps); $i++) {
            if ($timestamps[$i]->lte($timestamps[$i - 1])) {
                $timestamps[$i] = $timestamps[$i - 1]->copy()->addMinutes(random_int(12, 140))->addSeconds(random_int(5, 50));
            }
            if ($timestamps[$i]->gt($now)) {
                $timestamps[$i] = $now->copy()->subSeconds(($quantity - $i) * random_int(20, 55));
                if ($timestamps[$i]->lte($timestamps[$i - 1])) {
                    $timestamps[$i] = $timestamps[$i - 1]->copy()->addSeconds(random_int(15, 45));
                }
            }
        }

        return $timestamps;
    }

    private function backdateReviewMedia(ProductReview $review, Carbon $postedAt): void
    {
        foreach ($review->getMedia('product-review') as $media) {
            $media->created_at = $postedAt;
            $media->updated_at = $postedAt;
            $media->save();
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
