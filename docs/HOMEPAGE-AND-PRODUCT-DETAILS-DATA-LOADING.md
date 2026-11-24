# Homepage & Product Details — Data Loading Report

**Project:** Shopperzz / Ejeweller (Laravel + Vue 3 storefront)  
**Purpose:** Detailed technical brief for ChatGPT / AI assistants about how homepage and product details pages load data.  
**Date context:** November 2026  

---

## 1. Stack overview

| Layer | Tech |
|--------|------|
| Backend | Laravel (API under `/api/...`) |
| Storefront | Vue 3 + Vuex + Vue Router |
| Entry | `resources/js/storefront.js` → `StorefrontShell` |
| HTML shell | `resources/views/master.blade.php` via `RootController` |
| API auth header | `x-api-key` = `VITE_API_KEY` / `window.APP_KEY` |

All frontend API calls use Axios `baseURL = APP_URL + "/api"`.

---

## 2. First HTML paint (server)

**Controller:** `app/Http/Controllers/Frontend/RootController.php`  
**View:** `resources/views/master.blade.php`

### Homepage (`/` or `/home`)

1. Loads active **hero sliders** from DB (`Slider` + media).
2. Injects into page:
   - `<link rel="preload" as="image" href="…">` for first hero (mobile image preferred) — **LCP**.
   - Inline boot hero image `#boot-lcp-hero`.
   - `window.__HOME_HERO_SLIDERS__ = […]` JSON.
3. Vue boot (`createShopApp`) commits sliders into Vuex:

```js
store.commit("frontendSlider/lists", window.__HOME_HERO_SLIDERS__);
```

So the hero can render **without waiting** for a second slider API round-trip if bootstrap succeeded.

### Product details (`/product/{slug}`)

1. Server looks up product by slug for **SEO meta** (title, description, OG image).
2. No full product JSON is SSR’d — product body still loads via Vue API after mount.
3. Tracker / analytics scripts may load asynchronously from `master.blade.php`.

### Global on every page

- `window.APP_URL`, `window.APP_KEY`, Facebook pixel vars.
- Vite bundle: `resources/js/storefront.js`.
- Optional analytics: `window.__ANALYTICS__` + `/analytics/tracker.js`.

---

## 3. Storefront shell (every route)

**File:** `resources/js/components/StorefrontShell.vue`

Mounts:

- `FrontendNavBarComponent` (settings, languages, cart, category mega-menu trees)
- `router-view` with **`keep-alive` for `HomeComponent` only**
- Mobile nav / cart canvas / footer / floating WhatsApp (hidden on PDP)

Navbar (non-blocking extras):

- `frontendSetting/lists` → `GET frontend/setting`
- Deferred: `frontendProductCategory/trees` → `GET frontend/product-category/tree` (idle / timeout so home hero APIs win first)

---

## 4. Homepage data loading

### Route

| Path | Name | Component | Meta |
|------|------|-----------|------|
| `/` | `frontend.home` | `HomeComponent` (lazy chunk) | `keepAlive: true` |

**File:** `resources/js/components/frontend/home/HomeComponent.vue`

### Section order (top → bottom)

| # | Section | Mount strategy | Component |
|---|---------|----------------|-----------|
| 1 | Hero slider | **Eager** sync import | `SliderComponent` |
| 2 | Categories row | **Eager** sync import | `CategoryComponent` |
| 3 | Promotions (ads strip) | `DeferUntilVisible` + async chunk | `PromotionComponent` |
| 4 | Product sections + big promos | Deferred + async | `ProductSectionComponent` |
| 5 | Most popular | Deferred + async | `MostPopularComponent` |
| 6 | Flash sale | Deferred + async | `FlashSaleComponent` |
| 7 | Brands | Deferred + async | `ProductBrandComponent` |
| 8 | Outlets | Deferred + async | `OutletComponent` |
| 9 | Featured reviews | Deferred + async | `HomeReviewsComponent` |
| 10 | Benefits | Deferred + async | `BenefitComponent` |

### `DeferUntilVisible`

**File:** `resources/js/components/frontend/components/DeferUntilVisible.vue`

- Uses `IntersectionObserver` with configurable `rootMargin` (homepage uses ~80–320px).
- Until visible: placeholder `min-height` only (no API).
- When intersecting: mounts child → child’s `mounted` / mixin runs `fetchData()`.

### Homepage prefetch (parallel, in `created()`)

Fired **before** deferred sections mount (so cache is warm):

| Vuex action | HTTP | Purpose |
|-------------|------|---------|
| `frontendProductCategory/lists` | `GET frontend/product-category?paginate=0&parent_id=&status=5&…` | Flat category list for Category row |
| `frontendProductSection/lists` | `GET frontend/product-section` | Home product rails (cached server-side ~1h, max 8 products/section) |
| `frontendPromotion/lists` | `GET frontend/promotion?paginate=0&status=5&…` | Promotion cards |

Also **warms JS chunks** via `requestIdleCallback`: Promotion, ProductSection, MostPopular.

### Per-section API map

| Component | When fetch runs | Vuex / call | Endpoint |
|-----------|-----------------|-------------|----------|
| Slider | `created` if store empty | `frontendSlider/lists` | `GET frontend/slider` |
| Category | `mounted` if empty | `frontendProductCategory/lists` | `GET frontend/product-category` |
| Promotion | mixin `mounted` → `fetchData` | `frontendPromotion/lists` | `GET frontend/promotion` |
| ProductSection | `fetchData` | `frontendProductSection/lists` + BIG promotions `vuex:false` | `GET frontend/product-section`, `GET frontend/promotion?type=BIG` |
| MostPopular | `fetchData` | `frontendProduct/popularProducts` | `GET frontend/product/popular-products?rand=8` |
| FlashSale | `fetchData` | `frontendProduct/flashSaleProducts` | `GET frontend/product/flash-sale-products?rand=8` |
| Brand | `fetchData` | `frontendProductBrand/lists` | `GET frontend/product-brand` |
| Outlet | `fetchData` | `frontendOutlet/lists` | `GET frontend/outlet` |
| HomeReviews | `fetchData` | direct axios | `GET frontend/featured-reviews` (keeps 4–5★) |
| Benefit | `fetchData` | `frontendBenefit/lists` | `GET frontend/benefit` |

**Mixin:** `resources/js/mixins/frontendSectionFetch.js` — on `mounted`, calls `fetchData()` if defined.

### Homepage product UI performance notes

- Product rails use **native CSS scroll-snap** (`.homepage-product-rail`), not nested Swipers.
- Cards on home use `ProductListComponent` with **`lite` prop** → cover image only (no per-card media Swiper/video).
- CSS: `content-visibility: auto` on deferred / section blocks.
- Theme class: `homepage-theme--{site_homepage_theme}` from settings.

### Homepage loading sequence (ideal)

```
HTML + hero preload image
  → Vue mount StorefrontShell + Navbar (settings API)
  → HomeComponent created: prefetch categories + sections + promotions
  → Slider (often from __HOME_HERO_SLIDERS__) + Category mount/fetch
  → User scrolls → DeferUntilVisible mounts next chunks
  → Each section fetchData (skipped if Vuex already filled by prefetch)
```

### Keep-alive

Returning to `/` restores `HomeComponent` instance; sections that already fetched usually **do not re-hit** APIs if Vuex lists are non-empty.

---

## 5. Product details page (PDP) data loading

### Route

| Path | Name | Component |
|------|------|-----------|
| `/product/:slug` | `frontend.product.details` | `ProductDetailsComponent` (lazy) |

**File:** `resources/js/components/frontend/product/ProductDetailsComponent.vue`

### Mount lifecycle

```
mounted()
  → show()
  → scheduleEngagementWidgets()   // fake viewers / ticker intervals (UI only)
```

### Primary product load — `show()` → `requestProductShow()`

1. Reads `this.$route.params.slug`.
2. Increments `loadToken` (cancels stale responses on fast navigation).
3. Resets variation UI / gallery state.
4. **Session/local product cache** (`readProductCache(slug)`):
   - If hit → `commitShowPayload` + `handleShowSuccess` immediately (optimistic paint).
5. Network: Vuex `frontendProduct/show`  
   - **`GET /api/frontend/product/show/{slug}`**
6. On success:
   - Write cache.
   - `handleShowSuccess` → apply product, SEO, stop loading spinner.
7. On **404**: retry `frontendProduct/showWithTrashed`  
   - **`GET /api/frontend/product/show-with-trashed/{slug}`**

### Vuex commits on show (`commitShowPayload`)

| Commit | Data |
|--------|------|
| `frontendProduct/show` | Full product object |
| `frontendProduct/showImages` | Gallery images |
| `frontendProduct/showReviews` | Reviews on product |
| `frontendProduct/showVideos` | Videos |
| `frontendProduct/showSeo` | SEO block |

### After primary success

#### A. `applyProductFromShowResponse(data)`

- Builds `temp` pricing / qty state for Add to Cart / Buy Now.
- Sold-count logic (`use_random_sale` vs actual sales + `localStorage`).
- Updates `recently_viewed_products` in `localStorage` (max 10 IDs).
- Starts flash-sale countdown → shared `flashSaleTopBar` service (navbar blink; **no Teleport**).
- Preloads hero image.

#### B. `applyProductSeo(data)`

- `useHead` title/meta from `data.seo` when present (complements server SEO).

#### C. `scheduleDeferredDetailWork` (`requestIdleCallback`)

- Facebook / pixel `ViewContent`
- Analytics `trackProductViewed`
- `fetchRecentlyViewed()` → `GET frontend/product?ids=…&paginate=0`
- Video poster generation

#### D. `loadSecondaryProductData` (idle / short timeout)

Parallel:

| Action | Endpoint |
|--------|----------|
| `frontendProductVariation/allVariation(slug)` | `GET frontend/product/all-variation/{slug}` |
| `frontendProductVariation/initialVariation(id)` | `GET frontend/product/initial-variation/{id}` |

Sets `showVariationComponent` / enable ATC rules from stock + variations.

### Related products (below fold)

**Component:** `RelatedProductsSection.vue` (async)

- Controlled by settings: `product_page_related_status`, autoscroll, touch, speed, direction.
- Fetch: `frontendProduct/relatedProducts`  
  - **`GET frontend/product/related-products/{slug}`**

### Cart / WhatsApp / wishlist (user actions, not initial load)

- Add to cart / Buy now → `frontendCart/lists` (+ variation ancestors string if needed).
- Wishlist toggle → `frontendWishlist/toggle`.
- WhatsApp order → builds message from product + URL; floating FAB on mobile (outside sticky bar).

### Sticky UI (mobile)

- Bottom sticky: total + Add to cart + Buy now.
- WhatsApp: right-edge mid-screen FAB (`pdp-whatsapp-fab`), not inside sticky bar.

### Analytics / ads note

- PDP is the correct **ad landing URL** (`/product/{slug}`).
- Server SEO + client `ViewContent` / ecommerce bridge support Meta ads.

### PDP loading sequence

```
RootController SEO meta in HTML
  → Vue PDP mount
  → show(): cache paint (optional) + GET product/show/{slug}
  → apply product + SEO + flash timer
  → idle: pixel, analytics, recently viewed, video posters
  → idle: variations (all + initial)
  → RelatedProductsSection mounts → related-products API
```

---

## 6. Key file index

### Homepage

| Path | Role |
|------|------|
| `resources/js/components/frontend/home/HomeComponent.vue` | Section orchestration, prefetch |
| `resources/js/components/frontend/components/DeferUntilVisible.vue` | Scroll-gated mount |
| `resources/js/mixins/frontendSectionFetch.js` | `mounted` → `fetchData` |
| `resources/js/components/frontend/home/*Component.vue` | Each home block |
| `app/Http/Controllers/Frontend/RootController.php` | Hero SSR bootstrap |
| `app/Services/ProductSectionService.php` | Home sections cache + 8 products |

### Product details

| Path | Role |
|------|------|
| `resources/js/components/frontend/product/ProductDetailsComponent.vue` | PDP core |
| `resources/js/components/frontend/product/RelatedProductsSection.vue` | Related rail |
| `resources/js/store/modules/frontend/frontendProduct.js` | show / lists / related / popular / flash |
| `resources/js/store/modules/frontend/frontendProductVariation.js` | Variation APIs |
| `resources/js/services/analyticsEcommerceBridge.js` | `product_viewed`, cart events |
| `resources/js/services/flashSaleTopBar.js` | Shared flash countdown for navbar |

### Routing / boot

| Path | Role |
|------|------|
| `resources/js/router/modules/frontendRoutes.js` | Routes |
| `resources/js/storefront.js` | SPA entry |
| `resources/js/bootstrap/createShopApp.js` | Axios, hero commit, plugins |
| `resources/views/master.blade.php` | Shell + preload + APP_KEY |

---

## 7. Status enum reminder

Active entities typically use **`status = 5`** (`statusEnum.ACTIVE`). Inactive = `10`.

---

## 8. ChatGPT usage tips

When asking ChatGPT to change homepage or PDP behavior, paste this file and specify:

1. **Which section** (e.g. MostPopular, ProductSection, PDP variations).
2. **Whether the change is** first paint / API / Vuex cache / deferral / SEO.
3. **Do not** reintroduce Teleport for flash topbar (caused Vue `nextSibling` / `textContent` crashes).
4. Homepage product cards should stay **`lite`** unless a full media slider is explicitly required.
5. PDP must keep `loadToken` guards so rapid slug changes don’t apply stale responses.

---

## 9. Quick API cheat sheet

### Homepage

```
GET /api/frontend/slider
GET /api/frontend/product-category
GET /api/frontend/product-category/tree          (navbar mega)
GET /api/frontend/product-section
GET /api/frontend/promotion
GET /api/frontend/product/popular-products
GET /api/frontend/product/flash-sale-products
GET /api/frontend/product-brand
GET /api/frontend/outlet
GET /api/frontend/featured-reviews
GET /api/frontend/benefit
GET /api/frontend/setting                         (navbar / shell)
```

### Product details

```
GET /api/frontend/product/show/{slug}
GET /api/frontend/product/show-with-trashed/{slug}   (404 fallback)
GET /api/frontend/product/all-variation/{slug}
GET /api/frontend/product/initial-variation/{id}
GET /api/frontend/product/related-products/{slug}
GET /api/frontend/product?ids=1,2,3&paginate=0       (recently viewed)
```

---

*End of report.*
