# Ecommerce Analytics & Customer Intelligence Platform

Production-grade, self-hosted analytics module for Shopperzz (Laravel 12 + Vue 3).

## Where dashboard data comes from

| UI section | API | Database / source |
|------------|-----|-------------------|
| Site dropdown | `GET /api/admin/intelligence/sites` | `analytics_sites` |
| Live visitors, page views today (top row) | `GET .../realtime` (poll 5s) | Redis/Cache counters via `AnalyticsRealtimeService` |
| Visitors, sessions, page views, orders, revenue | `GET .../overview?site_id=&from=&to=` | `analytics_visitors`, `analytics_sessions`, `analytics_events` |
| Funnel | `GET .../funnel` | `analytics_events` by `event_name` |
| Traffic sources | `GET .../sources` | `analytics_sessions.source` |
| Top products | `GET .../products` | `analytics_events` (`product_viewed`) |

**Flow:** `tracker.js` → `POST /api/analytics/v1/collect` → DB tables → admin APIs → Vue `/admin/intelligence`.

Log lines `Broadcasting AnalyticsRealtimeUpdated` are **not** what fills the dashboard UI (websocket not wired yet). The dashboard uses the HTTP APIs above.

### Debug console (admin Intelligence page)

Open `/admin/intelligence` → browser **Console** → filter `[Intelligence]`.

Logs show: auth token present, each API request/response, Vuex mutations, and final UI state.

Disable after debugging:

```js
localStorage.setItem('intelligence_debug', '0');
location.reload();
```

## Architecture

```
tracker.js → POST /api/analytics/v1/collect
    → Redis buffer (per site)
    → Queue: analytics
    → ProcessAnalyticsIngestJob
    → Batch insert analytics_events
    → Redis realtime counters
    → Broadcast AnalyticsRealtimeUpdated (Reverb-ready)

Admin: GET /api/admin/intelligence/*
    → Vue dashboard (/admin/intelligence)
```

### Design principles

- **No synchronous DB writes** on ingest (Redis + queue only)
- **Batch inserts** (500 rows/chunk)
- **Event deduplication** (Redis SET NX + DB unique `event_uuid`)
- **Bot filtering** (user-agent heuristics)
- **Multi-site** workspaces + sites + member roles
- **Partition-ready** `event_date` column on `analytics_events`
- **Future**: ClickHouse (`config/analytics.php` → `future.clickhouse_enabled`), replay (`analytics_replay_chunks`), heatmaps (`analytics_heatmap_points`)

## Installation

### 1. Migrate

```bash
php artisan migrate
```

### 2. Configure environment

```env
ANALYTICS_ENABLED=true
ANALYTICS_PUBLIC_KEY=pk_xxx   # from install command
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
ANALYTICS_INGEST_QUEUE=analytics
BROADCAST_DRIVER=redis   # or reverb when added
```

### 3. Create site + keys (choose one)

**Option A — Admin UI (recommended)**  
Go to **Settings → Intelligence Keys** (`/admin/settings/intelligence-analytics`).  
On first visit keys are auto-generated and stored in `analytics_sites` (database).

On **local** dev, `.env` is **not** rewritten when you save/regenerate keys (avoids Vite restarting with “server connection lost”). The storefront reads the public key from the database. On **production**, set `ANALYTICS_SYNC_ENV=true` if you also want `.env` updated.

**Option B — CLI**

```bash
php artisan analytics:install-site "eJeweller" "ejeweller.pk" --user=1 --origin=https://ejeweller.pk
```

Copy **Public Key** into `.env` as `ANALYTICS_PUBLIC_KEY` (or save from admin UI).

### 4. Run workers

```bash
php artisan queue:work redis --queue=analytics,default
```

### 5. Schedule (optional cron)

```bash
php artisan schedule:run
# Runs analytics:aggregate-daily at 01:15
```

### 6. Admin dashboard

Open **Admin → Intelligence** or `/admin/intelligence`.

## Tracker installation

### On Shopperzz (built-in)

Set `ANALYTICS_PUBLIC_KEY` in `.env`. Tracker loads from `master.blade.php` automatically.

### External store / CDN

```html
<script>
  window.__ANALYTICS__ = {
    siteKey: 'pk_your_public_key',
    endpoint: 'https://your-store.com/api/analytics/v1/collect',
    userId: null
  };
</script>
<script async src="https://analytics.ejeweller.pk/analytics/tracker.js"></script>
```

### SPA / Vue Router

Tracker hooks `history.pushState`, `replaceState`, and `popstate` for automatic page views.

### Ecommerce events (Vue)

```js
import { trackAddToCart, trackOrderPlaced } from '@/services/analyticsEcommerceBridge';

trackAddToCart(product, qty);
trackOrderPlaced({ id: order.id, total: order.total, currency_code: 'PKR' });
```

Or globally:

```js
Analytics.ecommerce('add_to_cart', { product_id: 1, quantity: 2 });
Analytics.identify(userId);
```

## API reference

### Ingest (public)

`POST /api/analytics/v1/collect`

Headers: `X-Analytics-Key: pk_...` (or `site_key` in JSON body for sendBeacon)

```json
{
  "session_id": "uuid",
  "visitor_id": "uuid",
  "user_id": 123,
  "events": [
    {
      "event_uuid": "uuid",
      "event_name": "product_viewed",
      "event_category": "ecommerce",
      "product_id": 5,
      "occurred_at": "2026-05-25T12:00:00Z"
    }
  ],
  "context": {
    "page_url": "https://...",
    "utm_source": "facebook",
    "utm_medium": "cpc",
    "user_agent": "..."
  }
}
```

### Admin (Sanctum)

| Endpoint | Description |
|----------|-------------|
| `GET /api/admin/intelligence/sites` | Sites for current user |
| `GET /api/admin/intelligence/overview?site_id=&from=&to=` | KPIs + realtime snapshot |
| `GET /api/admin/intelligence/realtime?site_id=` | Live counters |
| `GET /api/admin/intelligence/funnel?site_id=` | Funnel steps |
| `GET /api/admin/intelligence/sources?site_id=` | Source breakdown |
| `GET /api/admin/intelligence/products?site_id=` | Top viewed products |

Admin intelligence routes are registered in **`routes/api.php`** (inside the `admin` group) so they work even when production has an older `routes/analytics.php` without those paths. Ingest stays in `routes/analytics.php` (`POST /api/analytics/v1/collect`).

## Realtime WebSockets (optional)

1. Install Laravel Reverb + laravel-echo
2. Set `BROADCAST_DRIVER=reverb`
3. Subscribe: `analytics.site.{siteId}` event `AnalyticsRealtimeUpdated`
4. Until then, dashboard polls `/realtime` every 5s

## Security

- Per-site public/secret keys
- CORS `allowed_origins` on site
- Rate limit: `ANALYTICS_INGEST_RATE_LIMIT` per minute
- Bot UA filtering
- Duplicate `event_uuid` rejection
- IP stored as SHA-256 hash only

## cPanel / shared hosting (ejeweller.pk)

When cPanel shows **Redis enabled** and a socket path like:

`/home/ejewelle/.redis/redis.sock`

use the **socket**, not `127.0.0.1:6379`, in production `.env`:

```env
REDIS_CLIENT=phpredis
REDIS_UNIX_SOCKET=/home/ejewelle/.redis/redis.sock
REDIS_PASSWORD=null

# Separate DB indexes (cPanel warns about key conflicts)
REDIS_DB=2
REDIS_CACHE_DB=1

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
ANALYTICS_INGEST_QUEUE=analytics
```

After deploy:

```bash
php artisan config:clear
php artisan config:cache
```

**Queue worker on cPanel** — add a cron job every minute (adjust path):

```cron
* * * * * cd /home/ejewelle/public_html && php artisan queue:work redis --queue=analytics,default --stop-when-empty --max-time=55
```

Or run `queue:work` under Supervisor if your plan allows it.

**PHP extension:** cPanel “Redis enabled” is the server; Laravel still needs the **phpredis** (or Predis) PHP extension enabled for that PHP version in **Select PHP Version → Extensions**.

**Verify connection** (SSH or Terminal in cPanel):

```bash
php artisan tinker --execute="echo Illuminate\Support\Facades\Redis::connection()->ping();"
```

Should print `PONG`. Then `collect` uses the Redis buffer + queue; without a worker, events stay buffered until cron runs.

## Deployment checklist

- [ ] Upload `routes/api.php` (intelligence routes) + full `app/Analytics/` + `public/build` after `npm run build`
- [ ] On server: `php artisan route:clear` (and `config:clear` if you changed `.env`)
- [ ] Verify `GET /api/admin/intelligence/sites` returns JSON `{ "success": true, ... }` (not HTML)
- [ ] Redis running (TCP or cPanel Unix socket)
- [ ] `QUEUE_CONNECTION=redis`
- [ ] Supervisor: `queue:work --queue=analytics`
- [ ] Cron: `schedule:run`
- [ ] `ANALYTICS_PUBLIC_KEY` set
- [ ] CORS origins configured on site
- [ ] Optional: separate subdomain serving `public/analytics/tracker.js`

## Folder structure

```
app/Analytics/
  Contracts/
  DTOs/
  Enums/
  Events/
  Http/
  Jobs/
  Models/
  Repositories/
  Services/
  Console/Commands/
config/analytics.php
routes/analytics.php
public/analytics/tracker.js
resources/js/components/admin/intelligence/
docs/ANALYTICS_PLATFORM.md
```

## Local development without Redis

If the PHP Redis extension or Redis server is not installed, ingestion still works:

- `POST /api/analytics/v1/collect` processes events **synchronously** (HTTP 202).
- Dedup and realtime counters use Laravel **Cache** (file/database driver) instead of Redis.
- Run `php artisan queue:work` only when `QUEUE_CONNECTION=redis` and Redis are available.

For production throughput, use Redis + `analytics` queue worker as in the checklist below.

## Troubleshooting (local dev)

| Symptom | Cause | Fix |
|--------|--------|-----|
| `ERR_CONNECTION_RESET` on save/load keys | `php artisan serve` crashed (often after `.env` was rewritten) | Stop serve (Ctrl+C), run `php artisan config:clear`, start `php artisan serve` again |
| `[vite] server connection lost` | Vite restarted when `.env` changed | Restart `npm run dev`; keys are in DB — no need to sync `.env` locally |
| Save works but page errors on reload | Old admin JS bundle still calls `load()` after save | Hard refresh admin (Ctrl+Shift+R) or keep `npm run dev` running |
| `collect` **401 Unauthorized** | DB has placeholder key `pk_...` from Save, stale offline queue, or key mismatch | Open **Intelligence Keys** → **Regenerate** → **Save** (auto-heals bad keys). Hard refresh storefront. In DevTools → Application → Local Storage delete `analytics_offline` if needed. Console: `window.__ANALYTICS__.siteKey` must match admin public key (35+ chars, not `pk_...`) |
| Dashboard “Analytics site could not be loaded” | Admin user not linked as site member / workspace owner | Open Intelligence Keys and **Save** once (links your user). Refresh dashboard |
| Logs show `AnalyticsRealtimeUpdated` but dashboard empty | Dashboard uses HTTP API (not broadcast logs); old `npm run build`, API errors, or date/site mismatch | Run `npm run build` on server, deploy `public/build`. Open `/admin/intelligence`, check Network → `overview` returns data. Set date range to today (server timezone). Hard refresh admin |

**Recommended local `.env`:**

```env
APP_ENV=local
APP_DEBUG=true
ANALYTICS_SYNC_ENV=false
```

Do not use `APP_ENV=production` on `127.0.0.1` — it disables debug and can enable production-only behaviour.

## Next phases

- ClickHouse exporter job
- Session replay chunk processor
- Heatmap aggregation service
- AI insights / attribution models
- Report CSV export API
