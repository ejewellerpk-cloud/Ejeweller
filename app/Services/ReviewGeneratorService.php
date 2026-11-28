<?php

namespace App\Services;

use Exception;
use App\Enums\Ask;
use App\Enums\ReviewStatus;
use App\Http\Requests\AdminGenerateReviewsRequest;
use App\Libraries\QueryExceptionLibrary;
use App\Models\Product;
use App\Models\ProductReview;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReviewGeneratorService
{
    private const MALE_FIRST = [
        'Ali', 'Hassan', 'Usman', 'Bilal', 'Hamza', 'Ahmad', 'Saad', 'Zain', 'Omar', 'Fahad',
        'Imran', 'Tariq', 'Noman', 'Waqas', 'Adeel', 'Danish', 'Kamran', 'Shahzaib', 'Haris', 'Asad',
        'Umer', 'Rehman', 'Abdullah', 'Mustafa', 'Kashif', 'Junaid', 'Faisal', 'Naveed', 'Salman', 'Rizwan',
        'Arslan', 'Shahid', 'Yasir', 'Amir', 'Sohail', 'Irfan', 'Babar', 'Talha', 'Hammad', 'Moiz',
        'Huzaifa', 'Rayyan', 'Ayan', 'Ibrahim', 'Yousuf', 'Basit', 'Sameer', 'Farhan', 'Owais', 'Zubair',
    ];

    private const FEMALE_FIRST = [
        'Fatima', 'Ayesha', 'Sana', 'Hira', 'Maryam', 'Noor', 'Iqra', 'Laiba', 'Mahnoor', 'Zara',
        'Amna', 'Saba', 'Mehwish', 'Rabia', 'Kinza', 'Areeba', 'Bushra', 'Nimra', 'Sadia', 'Hafsa',
        'Saima', 'Shazia', 'Rubina', 'Nadia', 'Farah', 'Khadija', 'Hina', 'Maham', 'Anam', 'Aima',
        'Eman', 'Alina', 'Momina', 'Dua', 'Rida', 'Minahil', 'Aiza', 'Hoorain', 'Esha', 'Aleena',
        'Maira', 'Tuba', 'Komal', 'Sundas', 'Fiza', 'Zoya', 'Iman', 'Aiman', 'Sehrish', 'Nida',
    ];

    private const LAST_NAMES = [
        'Khan', 'Ahmed', 'Hussain', 'Sheikh', 'Malik', 'Ali', 'Siddiqui', 'Iqbal', 'Shah', 'Mehmood',
        'Ashraf', 'Javed', 'Anwar', 'Qureshi', 'Baig', 'Nawaz', 'Butt', 'Rehman', 'Farooq', 'Raza',
        'Chaudhry', 'Mirza', 'Hashmi', 'Abbasi', 'Gillani', 'Bhatti', 'Dar', 'Waseem', 'Akhtar', 'Naz',
        'Parveen', 'Kousar', 'Yousaf', 'Awan', 'Rauf', 'Saleem', 'Nadeem', 'Zia', 'Arif', 'Basheer',
    ];

    private const NAME_STYLES = [
        'first_last',
        'first_last',
        'first_last',
        'first_initial_last',
        'm_first_last',
        'syed_first_last',
        'first_bibi',
        'first_only_last',
    ];

    /**
     * @return array{generated:int, media_token:?string}
     * @throws Exception
     */
    public function generate(AdminGenerateReviewsRequest $request): array
    {
        try {
            $product = Product::findOrFail((int) $request->product_id);
            $quantity = (int) $request->quantity;
            $uploaded = $request->file('images', []) ?: [];
            $uploaded = array_values(array_filter($uploaded, fn ($file) => $file instanceof UploadedFile));

            [$mediaToken, $tempPaths] = $this->resolveMediaPool($request->input('media_token'), $uploaded);

            $createdIds = [];
            $postedAtById = [];

            DB::transaction(function () use ($product, $quantity, &$createdIds, &$postedAtById) {
                $existingTexts = ProductReview::query()
                    ->where('product_id', $product->id)
                    ->whereNotNull('review')
                    ->pluck('review')
                    ->map(fn ($text) => $this->normalizeText((string) $text))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                // Names live on reviews only — do not create User rows.
                $existingNames = ProductReview::query()
                    ->where('product_id', $product->id)
                    ->whereNotNull('reviewer_name')
                    ->pluck('reviewer_name')
                    ->map(fn ($name) => $this->normalizeText((string) $name))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $reviewers = $this->buildReviewerQueue($quantity, $existingNames);
                $timestamps = $this->buildReviewTimestamps($product, $quantity);
                $usedComments = $existingTexts;
                $hasReviewerName = Schema::hasColumn('product_reviews', 'reviewer_name');

                foreach ($reviewers as $i => $reviewer) {
                    $postedAt = $timestamps[$i];
                    $row = [
                        'user_id'              => null,
                        'product_id'           => $product->id,
                        'star'                 => $this->pickStar(),
                        'review'               => $this->composeUniqueComment($usedComments, $reviewer['gender']),
                        'status'               => ReviewStatus::APPROVED,
                        'is_verified_purchase' => random_int(1, 100) <= 55 ? Ask::YES : Ask::NO,
                        'created_at'           => $postedAt->format('Y-m-d H:i:s'),
                        'updated_at'           => $postedAt->format('Y-m-d H:i:s'),
                    ];

                    if ($hasReviewerName) {
                        $row['reviewer_name'] = $reviewer['name'];
                    }

                    $id = (int) DB::table('product_reviews')->insertGetId($row);
                    $createdIds[] = $id;
                    $postedAtById[$id] = $postedAt->copy();
                }
            });

            // Keep media work outside the DB transaction. Skip image conversions for speed.
            if (!empty($tempPaths) && !empty($createdIds)) {
                $reviews = ProductReview::query()->whereIn('id', $createdIds)->get();
                ProductReview::$skipConversions = true;
                try {
                    $this->distributeUniqueImages($reviews, $tempPaths, $postedAtById);
                } finally {
                    ProductReview::$skipConversions = false;
                }
            }

            if ($request->boolean('cleanup_media') && $mediaToken) {
                Storage::deleteDirectory('temp/review-generator/' . $mediaToken);
                $mediaToken = null;
            }

            return [
                'generated'   => count($createdIds),
                'media_token' => $mediaToken,
            ];
        } catch (\Throwable $exception) {
            Log::error('Review generator failed', [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
            ]);
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * Upload once and reuse across progress batches via media_token.
     *
     * @param  array<int, UploadedFile>  $uploaded
     * @return array{0:?string, 1:array<int, string>}
     */
    private function resolveMediaPool(?string $token, array $uploaded): array
    {
        if (!empty($uploaded)) {
            $token = (string) Str::uuid();
            foreach ($uploaded as $index => $file) {
                $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
                $name = 'img_' . $index . '_' . Str::lower(Str::random(8)) . '.' . $ext;
                $stored = $file->storeAs('temp/review-generator/' . $token, $name);
                if (!$stored) {
                    throw new Exception('Unable to store review generator image.', 422);
                }
            }
        }

        if (!$token || !Str::isUuid($token)) {
            return [null, []];
        }

        $directory = 'temp/review-generator/' . $token;
        if (!Storage::exists($directory)) {
            return [$token, []];
        }

        $paths = [];
        foreach (Storage::files($directory) as $relative) {
            $absolute = Storage::path($relative);
            if (is_file($absolute)) {
                $paths[] = $absolute;
            }
        }

        return [$token, $paths];
    }

    /**
     * @return array<int, Carbon>
     */
    private function buildReviewTimestamps(Product $product, int $quantity): array
    {
        $now = Carbon::now();
        $listedAt = $product->created_at
            ? Carbon::parse($product->created_at)
            : $now->copy()->subMonths(3);

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
            $position = random_int(1, 100) <= 65 ? ($u * $u) : $u;
            $base = Carbon::createFromTimestamp((int) round($startTs + ($span * $position)));

            $hourRoll = random_int(1, 100);
            if ($hourRoll <= 48) {
                $hour = random_int(19, 23);
            } elseif ($hourRoll <= 72) {
                $hour = random_int(11, 14);
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
     * @param  array<int, string>  $blockedNames
     * @return array<int, array{name: string, gender: string}>
     */
    private function buildReviewerQueue(int $quantity, array $blockedNames = []): array
    {
        $used = array_fill_keys($blockedNames, true);
        $queue = [];

        for ($i = 0; $i < $quantity; $i++) {
            $gender = ($i % 2 === 0) ? 'male' : 'female';
            $name = $this->makeUniqueName($gender, $used, $i);
            $used[$this->normalizeText($name)] = true;

            $queue[] = [
                'name'   => $name,
                'gender' => $gender,
            ];
        }

        shuffle($queue);

        return $queue;
    }

    /**
     * @param  array<string, bool>  $used
     */
    private function makeUniqueName(string $gender, array &$used, int $seed): string
    {
        $firstPool = $gender === 'female' ? self::FEMALE_FIRST : self::MALE_FIRST;
        $lastPool = self::LAST_NAMES;

        for ($attempt = 0; $attempt < 400; $attempt++) {
            $first = $firstPool[array_rand($firstPool)];
            $last = $lastPool[array_rand($lastPool)];
            $style = self::NAME_STYLES[array_rand(self::NAME_STYLES)];

            $name = match ($style) {
                'first_initial_last' => $first[0] . '. ' . $last,
                'm_first_last' => $gender === 'male' && random_int(0, 1) === 1
                    ? 'M. ' . $first . ' ' . $last
                    : $first . ' ' . $last,
                'syed_first_last' => $gender === 'male' && random_int(0, 2) === 0
                    ? 'Syed ' . $first . ' ' . $last
                    : $first . ' ' . $last,
                'first_bibi' => $gender === 'female' && random_int(0, 3) === 0
                    ? $first . ' Bibi'
                    : $first . ' ' . $last,
                'first_only_last' => random_int(0, 5) === 0
                    ? $first . ' ' . $last[0] . '.'
                    : $first . ' ' . $last,
                default => $first . ' ' . $last,
            };

            $key = $this->normalizeText($name);
            if (!isset($used[$key])) {
                return $name;
            }
        }

        // Extremely rare fallback: force uniqueness with soft middle mark
        $first = $firstPool[$seed % count($firstPool)];
        $last = $lastPool[($seed * 7) % count($lastPool)];
        $suffix = chr(65 + ($seed % 26));

        return $first . ' ' . $last . ' ' . $suffix;
    }

    /**
     * @param  array<int, string>  $usedNormalized
     */
    private function composeUniqueComment(array &$usedNormalized, string $gender): string
    {
        for ($attempt = 0; $attempt < 80; $attempt++) {
            $style = $this->pickCommentStyle();
            $comment = match ($style) {
                'en' => $this->composeEnglish($gender),
                'mixed' => $this->composeMixed($gender),
                default => $this->composeUrdu($gender),
            };

            $comment = trim(preg_replace('/\s+/u', ' ', $comment) ?? $comment);
            $key = $this->normalizeText($comment);

            if ($key !== '' && !in_array($key, $usedNormalized, true)) {
                $usedNormalized[] = $key;

                return $comment;
            }
        }

        $fallback = $this->composeMixed($gender) . ' #' . Str::lower(Str::random(3));
        $usedNormalized[] = $this->normalizeText($fallback);

        return $fallback;
    }

    private function pickCommentStyle(): string
    {
        $roll = random_int(1, 100);
        if ($roll <= 38) {
            return 'en';
        }
        if ($roll <= 72) {
            return 'mixed';
        }

        return 'ur';
    }

    private function composeEnglish(string $gender): string
    {
        $openings = [
            'Really liked this.',
            'Solid buy.',
            'Happy with the purchase.',
            'Better than expected.',
            'Nice find.',
            'Good experience overall.',
            'Worth ordering.',
            'Came as shown.',
            'Pleasantly surprised.',
            'No regrets on this one.',
        ];

        $quality = [
            'Quality feels neat and tidy.',
            'Material/feel is better than the price suggests.',
            'Finishing looks clean.',
            'Looks premium in hand.',
            'Stitching and edges are neat.',
            'Soft feel and decent build.',
            'Color is close to the photos.',
            'Slight shade difference in light, still fine.',
            'Durable enough for daily use.',
            'Packing protected it well.',
        ];

        $delivery = [
            'Delivery was quick.',
            'Arrived on time.',
            'Packaging was careful.',
            'Courier experience was smooth.',
            'Got it sooner than expected.',
            '',
            '',
        ];

        $closingsMale = [
            'I will order again.',
            'Recommended.',
            'Family liked it too.',
            'Value for money.',
            'Thanks.',
            'Will recommend to friends.',
            'Exactly what I needed.',
        ];

        $closingsFemale = [
            'I will order again.',
            'Recommended.',
            'Family liked it too.',
            'Value for money.',
            'Thanks.',
            'Will recommend to friends.',
            'Exactly what I needed.',
        ];

        $parts = [
            $openings[array_rand($openings)],
            $quality[array_rand($quality)],
        ];

        $deliveryLine = $delivery[array_rand($delivery)];
        if ($deliveryLine !== '') {
            $parts[] = $deliveryLine;
        }

        $closings = $gender === 'female' ? $closingsFemale : $closingsMale;
        if (random_int(0, 1) === 1) {
            $parts[] = $closings[array_rand($closings)];
        }

        $extra = ['', '', ' 👍', ' Highly recommended.', ' JazakAllah.'];

        return implode(' ', $parts) . $extra[array_rand($extra)];
    }

    private function composeMixed(string $gender): string
    {
        $openings = [
            'Bohot acha experience raha.',
            'Product solid nikla.',
            'Worth it bilkul.',
            'Same as shown.',
            'Overall satisfied.',
            'Zabardast deal mili.',
            'Theek thaak aur kaam ki cheez.',
            'Happy with order.',
            'Photo jaisi quality.',
            'Mujhe pasand aa gaya.',
        ];

        $quality = [
            'Quality achi hai.',
            'Material soft hai.',
            'Finishing clean hai.',
            'Stitching strong lagi.',
            'Color almost exact hai.',
            'Packing zabardast thi.',
            'Feel premium hai.',
            'Daily use ke liye perfect.',
            'Local market se better mila.',
            'Size chart follow kiya to fit theek aa gaya.',
        ];

        $delivery = [
            'Delivery bhi fast hui.',
            'Time pe aa gaya.',
            'Courier smooth tha.',
            'Thori delay thi warna perfect.',
            '',
            '',
        ];

        $closingsMale = [
            'Dobara lunga.',
            'Recommend karta hun.',
            'Ghar walon ko pasand aa gaya.',
            'Paisay wasool.',
            'Will order again inshaAllah.',
            'No complaint.',
        ];

        $closingsFemale = [
            'Dobara lungi.',
            'Recommend karti hun.',
            'Ghar walon ko pasand aa gaya.',
            'Paisay wasool.',
            'Will order again inshaAllah.',
            'No complaint.',
        ];

        $parts = [
            $openings[array_rand($openings)],
            $quality[array_rand($quality)],
        ];

        $deliveryLine = $delivery[array_rand($delivery)];
        if ($deliveryLine !== '') {
            $parts[] = $deliveryLine;
        }

        $closings = $gender === 'female' ? $closingsFemale : $closingsMale;
        $parts[] = $closings[array_rand($closings)];

        if ($deliveryLine !== '' && random_int(0, 1) === 1) {
            $parts[] = $deliveryLine;
        }
        $parts[] = $closings[array_rand($closings)];

        return implode(' ', $parts);
    }

    private function composeUrdu(string $gender): string
    {
        $openings = [
            'بہت اچھا تجربہ رہا۔',
            'پروڈکٹ زبردست نکلا۔',
            'تصاویر جیسا ہی ملا۔',
            'مجھے پسند آیا۔',
            'اچھی خریداری ہے۔',
            'بالکل ویسا ہی ہے جیسا دکھایا گیا تھا۔',
            'قیمت کے حساب سے بہتر ہے۔',
            'سمپل مگر کام کی چیز ہے۔',
        ];

        $quality = [
            'کوالٹی صاف اور اچھی ہے۔',
            'مٹیریل نرم اور فنشنگ درست ہے۔',
            'سلائی مضبوط لگتی ہے۔',
            'رنگ تقریباً ویسا ہی ہے۔',
            'پیکنگ محفوظ تھی۔',
            'روزانہ استعمال کے لیے مناسب ہے۔',
            'مقامی مارکیٹ سے بہتر ملا۔',
            'فٹنگ درست ہے۔',
        ];

        $delivery = [
            'ڈیلیوری وقت پر ہو گئی۔',
            'کورئیر کا تجربہ اچھا رہا۔',
            'جلد موصول ہو گیا۔',
            '',
            '',
        ];

        $closingsMale = [
            'دوبارہ لوں گا۔',
            'تجویز کرتا ہوں۔',
            'فیملی کو بھی پسند آیا۔',
            'پیسے کے قابل ہے۔',
            'مطمئن ہوں۔',
            'شکریہ۔',
        ];

        $closingsFemale = [
            'دوبارہ لوں گی۔',
            'تجویز کرتی ہوں۔',
            'فیملی کو بھی پسند آیا۔',
            'پیسے کے قابل ہے۔',
            'مطمئن ہوں۔',
            'شکریہ۔',
        ];

        $parts = [
            $openings[array_rand($openings)],
            $quality[array_rand($quality)],
        ];

        $deliveryLine = $delivery[array_rand($delivery)];
        if ($deliveryLine !== '') {
            $parts[] = $deliveryLine;
        }

        $closings = $gender === 'female' ? $closingsFemale : $closingsMale;
        $parts[] = $closings[array_rand($closings)];

        return implode(' ', $parts);
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $text) ?? $text;

        return trim($text);
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

    /**
     * Attach each pool image at most once across reviews.
     * Used files are deleted from the media_token folder so later batches cannot reuse them.
     *
     * @param  \Illuminate\Support\Collection<int, ProductReview>  $reviews
     * @param  array<int, string>  $tempPaths
     * @param  array<int, Carbon>  $postedAtById
     */
    private function distributeUniqueImages($reviews, array $tempPaths, array $postedAtById): void
    {
        $pool = array_values(array_filter($tempPaths, static fn ($path) => is_string($path) && is_file($path)));
        if (empty($pool) || $reviews->isEmpty()) {
            return;
        }

        shuffle($pool);
        $reviewList = $reviews->values()->all();
        shuffle($reviewList);

        // Pass 1: one unique image per review until the pool runs out.
        foreach ($reviewList as $review) {
            if (empty($pool)) {
                break;
            }

            $path = array_shift($pool);
            $this->attachOneUniqueImage($review, $path);

            if (isset($postedAtById[$review->id])) {
                $this->backdateReviewMedia($review, $postedAtById[$review->id]);
            }
        }

        // Pass 2: optional second unique image for some reviews that already have one.
        if (!empty($pool)) {
            shuffle($reviewList);
            foreach ($reviewList as $review) {
                if (empty($pool)) {
                    break;
                }

                if ($review->getMedia('product-review')->count() < 1) {
                    continue;
                }

                if (random_int(1, 100) > 35) {
                    continue;
                }

                $path = array_shift($pool);
                $this->attachOneUniqueImage($review, $path);

                if (isset($postedAtById[$review->id])) {
                    $this->backdateReviewMedia($review, $postedAtById[$review->id]);
                }
            }
        }
    }

    private function attachOneUniqueImage(ProductReview $review, ?string $path): void
    {
        if (!$path || !is_file($path)) {
            return;
        }

        // Move (not copy) into media library — faster, and removes file from the shared pool.
        $review->addMedia($path)->toMediaCollection('product-review');
    }
}
