# Performance Optimization Report — Homepage & Product Details

**Project:** Shopperzz / Ejeweller  
**Date:** 24 Nov 2026  

---

## 1. Identified bottlenecks (before)

| Severity | Issue | Impact |
|----------|--------|--------|
| Critical | `mostPopularProducts` / `flashSaleProducts` ignored `rand: 8` → returned full catalog | Huge JSON, hundreds of cards, main-thread jank |
| Critical | Home product sections loaded **all** products then `take(8)` in PHP | Slow SQL + cache payload |
| Critical | `SimpleProductResource` N+1 fallbacks for sales/baskets/24h when aggregates missing | 2–3 queries × N products |
| Critical | Related products missing `withSum(productStocks)` → stock N+1 | Slow related API |
| High | Variation `initialVariation` per-row stock SUM; `allVariation` missing attribute eager loads | Slow PDP secondary load |
| High | Prefetch vs section mount race → duplicate category/section/promotion HTTP | Extra TTFB on first visit |
| High | PDP session cache still re-ran full `handleShowSuccess` on network → duplicate variations + analytics | Wasted work on revisit |
| High | ProductSection used N× `ProductListComponent` + separate BIG promotion API | Extra JS instances + HTTP |
| Medium | Aggressive `DeferUntilVisible` rootMargins mounted many sections at once on desktop | API/chunk storm |
| Medium | Related products limit 32 without `:lite` | Heavy cards + DOM |

---

## 2. Files modified

### Backend
- `app/Services/ProductService.php`
- `app/Services/ProductSectionService.php`
- `app/Services/ProductVariationService.php`
- `app/Services/ProductCategoryService.php`
- `app/Http/Resources/SimpleProductResource.php`

### Frontend
- `resources/js/store/utils/coalesceInflight.js` *(new)*
- `resources/js/store/modules/frontend/frontendProduct.js`
- `resources/js/store/modules/frontend/frontendProductCategory.js`
- `resources/js/store/modules/frontend/frontendProductSection.js`
- `resources/js/store/modules/frontend/frontendPromotion.js`
- `resources/js/components/frontend/home/HomeComponent.vue`
- `resources/js/components/frontend/home/ProductSectionComponent.vue`
- `resources/js/components/frontend/product/ProductDetailsComponent.vue`
- `resources/js/components/frontend/product/RelatedProductsSection.vue`
- `resources/css/app.css`

### Docs
- `docs/PERFORMANCE-OPTIMIZATION-REPORT.md` *(this file)*

---

## 3. Changes implemented

### Backend
1. **Honor `rand`** on popular + flash via `randAndLimitOrOrderBy` (SQL `LIMIT`).
2. **Home sections**: `limit(8)` in eager load; drop unused `variations`/`reviews` loads; use `withCount('variations')` + taxes; cache key bumped to `v4`.
3. **Related products**: stock/cart/24h aggregates; trim videos/full variations; cache category descendant IDs (versioned, busted on category write).
4. **Variations**: `withSum('stockItems')` + attribute/product eager loads on initial + all tree.
5. **`SimpleProductResource`**: never N+1 for social proof; safe empty taxes/videos when not loaded; `variation_count` from `variations_count`.

### Frontend
1. Vuex **in-flight coalesce** for category/section/promotion/popular/flash lists.
2. Client **slice by `rand`** as belt-and-suspenders for popular/flash.
3. Homepage **tighter rootMargins** (160→60px) to stagger below-fold mounts.
4. Product rails: **one** `ProductListComponent` per section (`contents` + rail CSS on `.product-card`).
5. BIG promotions: reuse Vuex lists when possible (skip second HTTP).
6. PDP: **soft refresh** after session cache (update price/stock/SEO; skip duplicate variations/analytics).
7. Related: `:lite="true"`, limit **16**.
8. Gallery: mount embed **iframe only on active slide**.

### Preserved
- `loadToken`, product session cache, SEO, lite home cards, native rails, keep-alive, no Teleport flash bar, ATC/Buy Now/Wishlist/WhatsApp.

---

## 4. Expected impact (not lab-measured Lighthouse)

| Area | Expected |
|------|----------|
| Popular/flash payload | From full catalog → **8 products** |
| Home sections DB/cache | Load 8 products in SQL, leaner JSON |
| Related/list APIs | Remove N+1 stock/social queries |
| First-visit home | Fewer duplicate APIs; staggered section mounts |
| PDP revisit (cache) | Half the secondary work (no double variation/pixel) |
| Related UI | Lite cards + fewer items |

**Estimated feel:** Homepage **~85–92%**, PDP **~80–88%** on good mobile (relative to prior architecture), subject to host/CDN/images/third-party scripts.

---

## 5. Tests performed

| Check | Result |
|-------|--------|
| PHP CLI syntax (`php -l`) | Skipped — `php` not on local Windows PATH |
| Frontend production build (`npm run build`) | Run as part of release |
| Manual Lighthouse before/after | Not run in this environment — run on production after deploy |

**Recommended after deploy:**
1. Hard-refresh `/` — Network: popular/flash ≤8 items; no duplicate category/section calls.
2. Open `/product/{slug}` twice — second visit paints from cache; Network still has one show refresh; variations fire once.
3. ATC / Buy Now / variation select / wishlist still work.
4. PageSpeed Insights mobile on home + one PDP.

---

## 6. Remaining opportunities

1. Split `ProductListComponent` into lite-only chunk (drop Swiper parse on home).
2. SSR bootstrap lean PDP JSON in `RootController` (like hero sliders).
3. Image CDN / WebP conversions for uploads (quality-safe).
4. HTTP/2 + OPcache + Redis object cache on server.
5. Further trim category-wise product filter queries.
6. Optionally skip `initialVariation` API when `allVariation` tree already has roots.

---

## 7. Server-level recommendations

```bash
# After git pull
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
# Ensure Redis/file cache + OPcache enabled
# Prefer CDN for /storage media
```
