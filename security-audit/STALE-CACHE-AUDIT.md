# Stale Cache Audit — Shopperzz

Audit date: 2026-10-03  
Issue class: admin saves new value, storefront keeps showing old value for up to ~1 hour (same class as shipping flat rate).

## Primary storefront settings cache

| Item | Detail |
|------|--------|
| Key | `global_settings_v2` |
| TTL | **3600 seconds (1 hour)** |
| Reader | `SettingService::list()` → `GET /api/frontend/setting` |
| Clear helper | `SettingService::clearCache()` |

Cached groups: `company`, `site`, `shipping_setup`, `theme`, `otp`, `social_media`, `notification`, `whatsapp`, `top_bar`, `product_page`, `cookies`.

### Writers that did NOT clear (FIXED this pass)

| Service | Setting group | Symptom if stale |
|---------|---------------|------------------|
| `ThemeService` | theme | logo/colors/footer theme lag |
| `OtpService` | otp | OTP digit/type/expiry lag |
| `CookiesService` | cookies | cookie banner text/status lag |
| `NotificationService` | notification | FCM / notification settings lag |
| `WhatsappService` | whatsapp | only cleared old key `global_settings`, not `v2` |
| `SiteService` | site | currency, COD, sold-out toggle, etc. (had `optimize:clear` but now also explicit clear) |
| `CompanyService` | company | company name/contact lag |
| `TopBarService` | top_bar | top bar content lag |
| `PaymentGatewayService::enableOnlinePaymentsSetting` | site | online payment flag lag |

### Writers already OK before this pass

| Service | Notes |
|---------|--------|
| `ShippingSetupService` | Fixed earlier (shipping flat rate) |
| `SocialMediaService` | calls `clearCache()` |
| `ProductPageService` | calls `clearCache()` + `optimize:clear` |
| `PostExSettingsService` | forgets both settings keys (postex not in v2 merge) |

---

## Other app caches

| Cache | TTL | Cleared when | Risk |
|-------|-----|--------------|------|
| Home product sections (`ProductSectionService`) | 1 hour | product CRUD; purchase received; **now also COD order + paid order stock activate** | Sold-out / stock / product cards lag |
| Category tree (`ProductCategoryService`) | 1 hour | category store/update/destroy | Low (already cleared) |
| Swich PayIn token (`SwichPayinClient`) | ~50 min | intentional auth token cache | OK |
| Analytics maps / realtime | various | analytics only | OK / not storefront catalog |
| Active users middleware | 10 min | rolling | OK |

---

## Frontend / browser persistence (not Laravel cache)

| Store | What | Effect |
|-------|------|--------|
| Vuex persisted state | `frontendCart` (incl. `shippingCharge`) | Old cart shipping until recalculate + settings refresh |
| `sessionStorage` product show | 3 minutes | Product details page can briefly show prior stock/price |
| `localStorage` sold counts / wishlist | client-only | Not admin settings |

---

## Fix applied

1. All settings group writers above now call `SettingService::clearCache()` after save.
2. WhatsApp uses full clear (includes `global_settings_v2`).
3. Home section cache busts when order stock becomes active (COD + payment capture).

After deploy: `php artisan optimize:clear` once to flush any leftover stale keys.
