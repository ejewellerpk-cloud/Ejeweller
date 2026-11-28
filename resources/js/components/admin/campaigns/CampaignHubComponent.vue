<template>
    <LoadingComponent :props="loading" />

    <div class="col-12">
        <div class="ch-hub">
            <!-- Hero header -->
            <header class="ch-hero">
                <div class="ch-hero__copy min-w-0">
                    <p class="ch-kicker">Growth workspace</p>
                    <h1 class="ch-title">{{ $t('menu.campaigns') || 'Campaigns' }}</h1>
                    <p class="ch-sub">
                        Launch Meta ads, track ROAS, recover carts, sync audiences & catalog — one place.
                    </p>
                </div>

                <div class="ch-hero__actions">
                    <div
                        class="ch-meta-pill"
                        :class="metaStatus.ok ? 'is-ok' : metaStatus.checked ? 'is-bad' : 'is-idle'"
                        @click="checkMetaStatus"
                        role="button"
                        tabindex="0"
                    >
                        <span class="ch-meta-pill__dot"></span>
                        <span class="ch-meta-pill__text">
                            <template v-if="!metaStatus.checked">Check Meta</template>
                            <template v-else-if="metaStatus.ok">Meta connected</template>
                            <template v-else>Meta issue</template>
                        </span>
                    </div>

                    <div class="ch-date-range">
                        <label class="ch-date-range__label">From</label>
                        <input type="date" v-model="from" class="ch-date-range__input" @change="refreshActive" />
                        <label class="ch-date-range__label">To</label>
                        <input type="date" v-model="to" class="ch-date-range__input" @change="refreshActive" />
                    </div>

                    <button
                        v-if="activeTab === 'campaigns'"
                        type="button"
                        class="ch-btn ch-btn--primary"
                        @click="openCreate"
                    >
                        <i class="lab lab-line-plus"></i>
                        <span>New campaign</span>
                    </button>
                </div>
            </header>

            <!-- Tabs -->
            <nav class="ch-tabs" aria-label="Campaign sections">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    class="ch-tab"
                    :class="{ 'is-active': activeTab === tab.id }"
                    @click="switchTab(tab.id)"
                >
                    <i :class="tab.icon"></i>
                    <span>{{ tab.label }}</span>
                </button>
            </nav>

            <!-- CAMPAIGNS -->
            <section v-show="activeTab === 'campaigns'" class="ch-panel">
                <div class="ch-panel__head">
                    <div>
                        <h2 class="ch-panel__title">Active & draft campaigns</h2>
                        <p class="ch-panel__hint">{{ campaigns.length }} campaign{{ campaigns.length === 1 ? '' : 's' }}</p>
                    </div>
                    <button type="button" class="ch-btn ch-btn--ghost sm:hidden" @click="openCreate">
                        <i class="lab lab-line-plus"></i>
                        <span>New</span>
                    </button>
                </div>

                <!-- Mobile cards -->
                <div class="ch-cards lg:hidden" v-if="campaigns.length">
                    <article v-for="c in campaigns" :key="'m-' + c.id" class="ch-card">
                        <div class="ch-card__top">
                            <div class="min-w-0">
                                <h3 class="ch-card__name">{{ c.name }}</h3>
                                <p class="ch-card__meta">
                                    <span class="ch-channel" :class="'ch-channel--' + c.channel">{{ channelLabel(c.channel) }}</span>
                                    <span class="ch-status" :class="statusClass(c.status)">{{ c.status }}</span>
                                </p>
                            </div>
                            <div class="ch-card__spend">
                                <span class="ch-card__spend-label">Spend</span>
                                <strong>{{ formatMoney(c.total_spend) }}</strong>
                            </div>
                        </div>

                        <div class="ch-card__row">
                            <span>UTM</span>
                            <code>{{ c.utm_campaign || '—' }}</code>
                        </div>
                        <div class="ch-card__row" v-if="c.external_campaign_id">
                            <span>Meta ID</span>
                            <code class="truncate">{{ c.external_campaign_id }}</code>
                        </div>
                        <div class="ch-card__products" v-if="(c.products || []).length">
                            <div
                                v-for="p in (c.products || []).slice(0, 4)"
                                :key="'mp-' + p.id"
                                class="ch-prod-chip"
                                :title="p.name"
                            >
                                <img :src="p.thumb || p.cover" :alt="p.name" />
                                <span v-if="(p.videos || []).length" class="ch-prod-chip__vid">▶</span>
                            </div>
                            <span v-if="(c.products || []).length > 4" class="ch-prod-more">
                                +{{ c.products.length - 4 }}
                            </span>
                        </div>

                        <div class="ch-card__actions">
                            <button type="button" class="ch-btn ch-btn--soft" @click="copyUrl(c.tracking_url)">Copy link</button>
                            <button
                                v-if="isMetaChannel(c.channel)"
                                type="button"
                                class="ch-btn ch-btn--primary"
                                @click="pushToMeta(c)"
                            >
                                {{ c.external_campaign_id ? 'Linked' : 'Push Meta' }}
                            </button>
                            <button type="button" class="ch-btn ch-btn--soft" @click="openSpend(c)">Spend</button>
                            <button type="button" class="ch-btn ch-btn--soft" @click="openEdit(c)">Edit</button>
                            <button type="button" class="ch-btn ch-btn--danger" @click="destroy(c)">Delete</button>
                        </div>
                    </article>
                </div>

                <!-- Desktop table -->
                <div class="ch-table-wrap hidden lg:block" v-if="campaigns.length">
                    <table class="ch-table">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Channel</th>
                                <th>Status</th>
                                <th>UTM</th>
                                <th>Tracking</th>
                                <th class="text-right">Spend</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in campaigns" :key="c.id">
                                <td>
                                    <div class="ch-td-title">{{ c.name }}</div>
                                    <div v-if="c.external_campaign_id" class="ch-td-sub">Meta · {{ c.external_campaign_id }}</div>
                                    <div class="ch-card__products ch-card__products--inline" v-if="(c.products || []).length">
                                        <div
                                            v-for="p in (c.products || []).slice(0, 5)"
                                            :key="'tp-' + p.id"
                                            class="ch-prod-chip"
                                            :title="p.name"
                                        >
                                            <img :src="p.thumb || p.cover" :alt="p.name" />
                                            <span v-if="(p.videos || []).length" class="ch-prod-chip__vid">▶</span>
                                        </div>
                                        <span v-if="(c.products || []).length > 5" class="ch-prod-more">
                                            +{{ c.products.length - 5 }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="ch-channel" :class="'ch-channel--' + c.channel">{{ channelLabel(c.channel) }}</span>
                                </td>
                                <td>
                                    <span class="ch-status" :class="statusClass(c.status)">{{ c.status }}</span>
                                </td>
                                <td><code class="ch-code">{{ c.utm_campaign }}</code></td>
                                <td>
                                    <button type="button" class="ch-link" @click="copyUrl(c.tracking_url)">Copy link</button>
                                </td>
                                <td class="text-right tabular-nums font-semibold">{{ formatMoney(c.total_spend) }}</td>
                                <td>
                                    <div class="ch-row-actions justify-end">
                                        <button
                                            v-if="isMetaChannel(c.channel)"
                                            type="button"
                                            class="ch-btn ch-btn--primary ch-btn--sm"
                                            @click="pushToMeta(c)"
                                        >
                                            {{ c.external_campaign_id ? 'Linked' : 'Push Meta' }}
                                        </button>
                                        <button type="button" class="ch-btn ch-btn--soft ch-btn--sm" @click="openSpend(c)">Spend</button>
                                        <button type="button" class="ch-btn ch-btn--soft ch-btn--sm" @click="openEdit(c)">Edit</button>
                                        <button type="button" class="ch-btn ch-btn--danger ch-btn--sm" @click="destroy(c)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!campaigns.length" class="ch-empty">
                    <div class="ch-empty__icon"><i class="lab lab-line-promotion"></i></div>
                    <h3>No campaigns yet</h3>
                    <p>Create a Meta campaign, push it to Ads Manager, then track spend & ROAS here.</p>
                    <button type="button" class="ch-btn ch-btn--primary" @click="openCreate">Create first campaign</button>
                </div>
            </section>

            <!-- PERFORMANCE -->
            <section v-show="activeTab === 'performance'" class="ch-panel">
                <div class="ch-kpi-grid">
                    <div v-for="kpi in performanceKpis" :key="kpi.label" class="ch-kpi">
                        <span class="ch-kpi__label">{{ kpi.label }}</span>
                        <strong class="ch-kpi__value">{{ kpi.value }}</strong>
                    </div>
                </div>

                <div class="ch-split">
                    <div class="ch-block">
                        <h2 class="ch-panel__title">Campaign performance</h2>
                        <div class="ch-table-wrap">
                            <table class="ch-table">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th class="text-right">Spend</th>
                                        <th class="text-right hidden sm:table-cell">Sessions</th>
                                        <th class="text-right">Orders</th>
                                        <th class="text-right hidden md:table-cell">Revenue</th>
                                        <th class="text-right">ROAS</th>
                                        <th class="text-right hidden lg:table-cell">CPA</th>
                                    </tr>
                                </thead>
                                <tbody v-if="performance.campaigns?.length">
                                    <tr v-for="row in performance.campaigns" :key="row.id">
                                        <td class="font-semibold">{{ row.name }}</td>
                                        <td class="text-right tabular-nums">{{ formatMoney(row.spend) }}</td>
                                        <td class="text-right tabular-nums hidden sm:table-cell">{{ row.sessions }}</td>
                                        <td class="text-right tabular-nums">{{ row.orders }}</td>
                                        <td class="text-right tabular-nums hidden md:table-cell">{{ formatMoney(row.revenue) }}</td>
                                        <td class="text-right font-bold" :class="row.roas >= 1 ? 'text-emerald-700' : ''">
                                            {{ row.roas != null ? row.roas + 'x' : '—' }}
                                        </td>
                                        <td class="text-right tabular-nums hidden lg:table-cell">
                                            {{ row.cpa != null ? formatMoney(row.cpa) : '—' }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr>
                                        <td colspan="7" class="ch-empty-cell">
                                            Add spend and run ads with campaign UTM links to see ROAS.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="ch-block">
                        <h2 class="ch-panel__title">Channel attribution</h2>
                        <div class="ch-table-wrap">
                            <table class="ch-table">
                                <thead>
                                    <tr>
                                        <th>Source</th>
                                        <th class="text-right">Orders</th>
                                        <th class="text-right">Revenue</th>
                                        <th class="text-right hidden sm:table-cell">Spend</th>
                                        <th class="text-right">ROAS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(ch, i) in attribution.channels || []" :key="i">
                                        <td class="capitalize font-semibold">{{ ch.source }}</td>
                                        <td class="text-right tabular-nums">{{ ch.orders }}</td>
                                        <td class="text-right tabular-nums">{{ formatMoney(ch.revenue) }}</td>
                                        <td class="text-right tabular-nums hidden sm:table-cell">{{ formatMoney(ch.spend) }}</td>
                                        <td class="text-right font-bold">{{ ch.roas != null ? ch.roas + 'x' : '—' }}</td>
                                    </tr>
                                    <tr v-if="!(attribution.channels || []).length">
                                        <td colspan="5" class="ch-empty-cell">No attribution data in this range.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- RECOVERY -->
            <section v-show="activeTab === 'recovery'" class="ch-panel">
                <div class="ch-kpi-grid">
                    <div class="ch-kpi">
                        <span class="ch-kpi__label">Cart abandon rate</span>
                        <strong class="ch-kpi__value">{{ recovery.kpis?.cart_abandonment_rate ?? 0 }}%</strong>
                    </div>
                    <div class="ch-kpi">
                        <span class="ch-kpi__label">Queue pending</span>
                        <strong class="ch-kpi__value">{{ recovery.queue?.pending ?? 0 }}</strong>
                    </div>
                    <div class="ch-kpi">
                        <span class="ch-kpi__label">Sent</span>
                        <strong class="ch-kpi__value">{{ recovery.queue?.sent ?? 0 }}</strong>
                    </div>
                    <div class="ch-kpi">
                        <span class="ch-kpi__label">Recoverable est.</span>
                        <strong class="ch-kpi__value">{{ formatMoney(recovery.kpis?.recoverable_value_estimate) }}</strong>
                    </div>
                </div>

                <div class="ch-toolbar">
                    <button type="button" class="ch-btn ch-btn--primary" @click="enqueueRecovery">Enqueue abandons</button>
                    <button type="button" class="ch-btn ch-btn--dark" @click="processRecovery">Send due emails now</button>
                </div>

                <div class="ch-table-wrap">
                    <table class="ch-table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>When</th>
                                <th class="text-right">Value</th>
                                <th class="hidden sm:table-cell">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(a, i) in recovery.recent_abandons || []" :key="i">
                                <td class="font-semibold">{{ a.event }}</td>
                                <td class="text-xs text-[var(--ch-muted)]">{{ a.occurred_at }}</td>
                                <td class="text-right tabular-nums">{{ formatMoney(a.value) }}</td>
                                <td class="hidden sm:table-cell truncate max-w-[12rem]">{{ a.email || '—' }}</td>
                            </tr>
                            <tr v-if="!(recovery.recent_abandons || []).length">
                                <td colspan="4" class="ch-empty-cell">No recent abandons in this range.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- AUDIENCES -->
            <section v-show="activeTab === 'audiences'" class="ch-panel">
                <div class="ch-feature">
                    <div class="ch-feature__copy">
                        <h2 class="ch-panel__title">Customer audiences</h2>
                        <p class="ch-panel__hint">
                            Export hashed customer lists or sync a Custom Audience straight to Meta.
                        </p>
                    </div>
                    <div class="ch-feature__controls">
                        <div class="ch-field">
                            <label>Segment</label>
                            <select v-model="audienceSegment" class="ch-select" @change="loadAudiences">
                                <option value="all">All customers</option>
                                <option value="vip">VIP</option>
                                <option value="loyal">Loyal</option>
                                <option value="at_risk">At risk</option>
                                <option value="hibernating">Hibernating</option>
                                <option value="new">New</option>
                            </select>
                        </div>
                        <button type="button" class="ch-btn ch-btn--primary" @click="exportAudience">Download CSV</button>
                        <button type="button" class="ch-btn ch-btn--dark" @click="syncAudience">Sync to Meta</button>
                    </div>
                </div>

                <div class="ch-stat-banner">
                    <strong>{{ audience.count || 0 }}</strong>
                    <span>contacts ready for “{{ audienceSegment }}”</span>
                </div>
                <p class="ch-panel__hint mt-3">
                    {{ audience.note || 'CSV works without Marketing API. Sync needs Meta token + ad account.' }}
                </p>
            </section>

            <!-- CATALOG -->
            <section v-show="activeTab === 'catalog'" class="ch-panel">
                <div class="ch-panel__head">
                    <div>
                        <h2 class="ch-panel__title">Meta catalog sync</h2>
                        <p class="ch-panel__hint">
                            Products sync from your database into the connected Meta Catalog — no manual re-entry.
                            <span v-if="!catalogSync.configured" class="ch-warn-inline"> Set Catalog ID in Facebook CAPI settings.</span>
                        </p>
                    </div>
                    <div class="ch-toolbar">
                        <button type="button" class="ch-btn ch-btn--soft" @click="loadCatalogSync">Refresh</button>
                        <button type="button" class="ch-btn ch-btn--primary" @click="syncSelectedToMeta" :disabled="!selectedProducts.length">
                            Sync selected ({{ selectedProducts.length || 0 }})
                        </button>
                        <button type="button" class="ch-btn ch-btn--dark" @click="retryFailedCatalog">Retry failed</button>
                    </div>
                </div>

                <div class="ch-kpi-grid">
                    <div class="ch-kpi"><span class="ch-kpi__label">Tracked</span><strong class="ch-kpi__value">{{ catalogTotals.tracked || 0 }}</strong></div>
                    <div class="ch-kpi"><span class="ch-kpi__label">Synced</span><strong class="ch-kpi__value">{{ catalogTotals.synced || 0 }}</strong></div>
                    <div class="ch-kpi"><span class="ch-kpi__label">Pending</span><strong class="ch-kpi__value">{{ catalogTotals.pending || 0 }}</strong></div>
                    <div class="ch-kpi"><span class="ch-kpi__label">Failed</span><strong class="ch-kpi__value">{{ catalogTotals.failed || 0 }}</strong></div>
                    <div class="ch-kpi"><span class="ch-kpi__label">Needs attention</span><strong class="ch-kpi__value">{{ catalogTotals.needs_attention || 0 }}</strong></div>
                    <div class="ch-kpi"><span class="ch-kpi__label">Missing images</span><strong class="ch-kpi__value">{{ catalogTotals.missing_images || 0 }}</strong></div>
                </div>

                <div class="ch-feature ch-feature--catalog mt-4">
                    <div class="ch-feature__copy">
                        <h3 class="ch-panel__title" style="font-size:1rem">CSV feed (fallback)</h3>
                        <p class="ch-panel__hint">
                            {{ catalog.count || 0 }} active products. Optional: paste feed URL into Commerce Manager → Data Sources.
                        </p>
                        <code class="ch-feed-url">{{ catalogSync.csv_url || catalog.csv_url || feedUrl }}</code>
                        <div class="ch-toolbar mt-3">
                            <button type="button" class="ch-btn ch-btn--soft" @click="copyUrl(catalogSync.csv_url || catalog.csv_url || feedUrl)">Copy feed URL</button>
                            <a :href="catalogSync.csv_url || catalog.csv_url || feedUrl" target="_blank" class="ch-btn ch-btn--soft">Open CSV</a>
                        </div>
                    </div>
                </div>

                <div class="ch-table-wrap mt-4">
                    <table class="ch-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Status</th>
                                <th class="hidden sm:table-cell">Last sync</th>
                                <th>Issues</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in (catalogSync.recent || [])" :key="'cs-' + row.product_id">
                                <td>
                                    <div class="ch-td-title">{{ row.name || ('#' + row.product_id) }}</div>
                                    <div class="ch-td-sub">Retailer · {{ row.retailer_id }}</div>
                                </td>
                                <td><span class="ch-sync-badge" :class="'is-' + row.sync_status">{{ row.sync_status }}</span></td>
                                <td class="hidden sm:table-cell text-xs text-[var(--ch-muted)]">{{ row.last_synced_at || '—' }}</td>
                                <td class="text-xs text-[var(--ch-muted)]">
                                    <span v-if="row.last_error">{{ row.last_error }}</span>
                                    <span v-else-if="(row.errors || []).length">{{ row.errors[0].message }}</span>
                                    <span v-else>—</span>
                                </td>
                                <td>
                                    <div class="ch-row-actions justify-end">
                                        <button type="button" class="ch-btn ch-btn--soft ch-btn--sm" @click="prepareProductById(row.product_id)">Preview</button>
                                        <button type="button" class="ch-btn ch-btn--primary ch-btn--sm" @click="syncProductById(row.product_id)">Sync</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!(catalogSync.recent || []).length">
                                <td colspan="5" class="ch-empty-cell">No synced products yet. Select products in a campaign and click Sync to Meta.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <!-- Create / Edit full-page modal -->
    <div v-if="drawerOpen" class="ch-overlay ch-overlay--page" @click.self="closeDrawer">
        <div class="ch-page-modal" role="dialog" aria-modal="true" aria-labelledby="campaign-modal-title">
            <div class="ch-page-modal__head">
                <div class="min-w-0">
                    <p class="ch-kicker">Campaign</p>
                    <h3 id="campaign-modal-title" class="ch-drawer__title">
                        {{ form.id ? 'Edit campaign' : 'New campaign' }}
                    </h3>
                    <p class="ch-panel__hint">Fill details, then save. Meta push is available from the campaigns list.</p>
                </div>
                <button type="button" class="ch-icon-btn" aria-label="Close" @click="closeDrawer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form class="ch-page-modal__body" @submit.prevent="saveCampaign">
                <div class="ch-form-sections">
                    <section class="ch-form-section">
                        <h4 class="ch-form-section__title">Basics</h4>
                        <div class="ch-form-grid ch-form-grid--3">
                            <div class="ch-field ch-field--span-2">
                                <label for="campaign_name">Name</label>
                                <input id="campaign_name" v-model="form.name" required />
                            </div>
                            <div class="ch-field">
                                <label for="campaign_status">Status</label>
                                <select id="campaign_status" v-model="form.status">
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="paused">Paused</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <div class="ch-field">
                                <label for="campaign_channel">Channel</label>
                                <select id="campaign_channel" v-model="form.channel">
                                    <option value="meta">Meta (Facebook/IG)</option>
                                    <option value="google">Google Ads</option>
                                    <option value="tiktok">TikTok</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="ch-field">
                                <label for="campaign_objective">Objective</label>
                                <select id="campaign_objective" v-model="form.objective">
                                    <option value="conversions">Conversions</option>
                                    <option value="traffic">Traffic</option>
                                    <option value="awareness">Awareness</option>
                                    <option value="catalog">Catalog sales</option>
                                </select>
                            </div>
                            <div class="ch-field">
                                <label for="campaign_landing">Landing path</label>
                                <input id="campaign_landing" v-model="form.landing_path" placeholder="/" />
                            </div>
                        </div>
                    </section>

                    <section class="ch-form-section">
                        <h4 class="ch-form-section__title">Tracking & UTM</h4>
                        <div class="ch-form-grid ch-form-grid--3">
                            <div class="ch-field">
                                <label for="campaign_utm_source">utm_source</label>
                                <input id="campaign_utm_source" v-model="form.utm_source" />
                            </div>
                            <div class="ch-field">
                                <label for="campaign_utm_medium">utm_medium</label>
                                <input id="campaign_utm_medium" v-model="form.utm_medium" />
                            </div>
                            <div class="ch-field">
                                <label for="campaign_utm_campaign">utm_campaign</label>
                                <input id="campaign_utm_campaign" v-model="form.utm_campaign" placeholder="Auto if empty" />
                            </div>
                            <div class="ch-field">
                                <label for="campaign_utm_content">utm_content</label>
                                <input id="campaign_utm_content" v-model="form.utm_content" />
                            </div>
                            <div class="ch-field">
                                <label for="campaign_utm_term">utm_term</label>
                                <input id="campaign_utm_term" v-model="form.utm_term" />
                            </div>
                        </div>
                    </section>

                    <section class="ch-form-section">
                        <h4 class="ch-form-section__title">Budget & schedule</h4>
                        <div class="ch-form-grid ch-form-grid--4">
                            <div class="ch-field">
                                <label for="campaign_daily_budget">Daily budget</label>
                                <input id="campaign_daily_budget" v-model="form.daily_budget" type="number" min="0" step="0.01" />
                            </div>
                            <div class="ch-field">
                                <label for="campaign_total_budget">Total budget</label>
                                <input id="campaign_total_budget" v-model="form.total_budget" type="number" min="0" step="0.01" />
                            </div>
                            <div class="ch-field">
                                <label for="campaign_starts_at">Starts</label>
                                <input id="campaign_starts_at" v-model="form.starts_at" type="date" />
                            </div>
                            <div class="ch-field">
                                <label for="campaign_ends_at">Ends</label>
                                <input id="campaign_ends_at" v-model="form.ends_at" type="date" />
                            </div>
                            <div class="ch-field ch-field--full">
                                <label for="campaign_notes">Notes</label>
                                <textarea id="campaign_notes" v-model="form.notes" rows="3"></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="ch-form-section">
                        <div class="ch-form-section__head">
                            <div>
                                <h4 class="ch-form-section__title">Products & Meta catalog</h4>
                                <p class="ch-panel__hint">
                                    Select a product — details, images, price, and landing URL load from your database. Sync to Meta Catalog before or after saving the campaign.
                                </p>
                            </div>
                            <div class="ch-toolbar">
                                <span class="ch-pill-count" v-if="selectedProducts.length">{{ selectedProducts.length }} selected</span>
                                <button
                                    v-if="selectedProducts.length"
                                    type="button"
                                    class="ch-btn ch-btn--primary ch-btn--sm"
                                    @click="syncSelectedToMeta"
                                >
                                    Sync selected to Meta
                                </button>
                            </div>
                        </div>

                        <div class="ch-selected-products" v-if="selectedProducts.length">
                            <article
                                v-for="p in selectedProducts"
                                :key="'sel-' + p.id"
                                class="ch-selected-product"
                                :class="{ 'is-active': productPreview?.product_id === p.id }"
                                @click="prepareProductById(p.id)"
                            >
                                <img :src="p.cover || p.thumb" :alt="p.name" />
                                <div class="min-w-0">
                                    <strong class="truncate block">{{ p.name }}</strong>
                                    <span class="ch-selected-product__meta">
                                        {{ (p.images || []).length || 1 }} img
                                        <template v-if="(p.videos || []).length"> · {{ p.videos.length }} vid</template>
                                        <template v-if="p.meta_sync?.sync_status"> · {{ p.meta_sync.sync_status }}</template>
                                    </span>
                                </div>
                                <button type="button" class="ch-icon-btn" aria-label="Remove" @click.stop="removeSelectedProduct(p.id)">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </article>
                        </div>

                        <div v-if="productPreview" class="ch-meta-preview">
                            <div class="ch-meta-preview__media">
                                <img :src="productPreview.preview?.image || productPreview.payload?.image_link" alt="" />
                            </div>
                            <div class="ch-meta-preview__body">
                                <div class="ch-form-section__head" style="margin-bottom:0.5rem">
                                    <div>
                                        <h5 class="ch-meta-preview__title">{{ productPreview.preview?.title }}</h5>
                                        <p class="ch-panel__hint">{{ productPreview.preview?.url }}</p>
                                    </div>
                                    <span class="ch-sync-badge" :class="'is-' + (productPreview.sync_status || productPreview.meta?.sync_status || 'pending')">
                                        {{ productPreview.sync_status || productPreview.meta?.sync_status || 'pending' }}
                                    </span>
                                </div>
                                <p class="ch-meta-preview__desc">{{ productPreview.preview?.description }}</p>
                                <div class="ch-meta-preview__facts">
                                    <span>Price {{ productPreview.preview?.price }}</span>
                                    <span v-if="productPreview.preview?.sale_price">Sale {{ productPreview.preview.sale_price }}</span>
                                    <span>{{ productPreview.preview?.availability }}</span>
                                    <span v-if="productPreview.preview?.brand">{{ productPreview.preview.brand }}</span>
                                    <span v-if="productPreview.preview?.category">{{ productPreview.preview.category }}</span>
                                </div>
                                <ul v-if="(productPreview.errors || []).length" class="ch-meta-list is-error">
                                    <li v-for="(e, i) in productPreview.errors" :key="'pe-' + i">{{ e.message || e }}</li>
                                </ul>
                                <ul v-if="(productPreview.warnings || []).length" class="ch-meta-list is-warn">
                                    <li v-for="(w, i) in productPreview.warnings" :key="'pw-' + i">{{ w.message || w }}</li>
                                </ul>
                                <div class="ch-toolbar mt-3">
                                    <button type="button" class="ch-btn ch-btn--soft ch-btn--sm" @click="validateProductById(productPreview.product_id)">Validate</button>
                                    <button type="button" class="ch-btn ch-btn--primary ch-btn--sm" @click="syncProductById(productPreview.product_id)">Sync to Meta</button>
                                    <a v-if="productPreview.preview?.url" :href="productPreview.preview.url" target="_blank" class="ch-btn ch-btn--soft ch-btn--sm">Open landing</a>
                                </div>
                            </div>
                        </div>

                        <div class="ch-product-search">
                            <input
                                v-model="productSearch"
                                type="search"
                                placeholder="Search products by name or SKU…"
                                @input="onProductSearch"
                            />
                        </div>

                        <div class="ch-product-grid" v-if="productResults.length">
                            <button
                                v-for="p in productResults"
                                :key="'pick-' + p.id"
                                type="button"
                                class="ch-product-tile"
                                :class="{ 'is-selected': isProductSelected(p.id) }"
                                @click="toggleProduct(p)"
                            >
                                <div class="ch-product-tile__media">
                                    <img :src="p.thumb || p.cover" :alt="p.name" />
                                    <span v-if="(p.videos || []).length" class="ch-product-tile__badge">Video</span>
                                    <span v-if="p.meta_sync?.sync_status" class="ch-product-tile__sync" :class="'is-' + p.meta_sync.sync_status">
                                        {{ p.meta_sync.sync_status }}
                                    </span>
                                    <span v-if="isProductSelected(p.id)" class="ch-product-tile__check">
                                        <i class="fa-solid fa-check"></i>
                                    </span>
                                </div>
                                <div class="ch-product-tile__body">
                                    <strong class="truncate">{{ p.name }}</strong>
                                    <span>
                                        <template v-if="p.sale_price">{{ formatMoney(p.sale_price) }} · </template>
                                        {{ formatMoney(p.selling_price) }}
                                    </span>
                                </div>
                            </button>
                        </div>
                        <p v-else-if="productSearchLoading" class="ch-panel__hint">Loading products…</p>
                        <p v-else class="ch-panel__hint">No products found. Try another search.</p>
                    </section>
                </div>

                <div class="ch-page-modal__foot">
                    <button type="button" class="ch-btn ch-btn--soft" @click="closeDrawer">Cancel</button>
                    <button type="submit" class="ch-btn ch-btn--primary">Save campaign</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Spend modal -->
    <div v-if="spendOpen" class="ch-overlay ch-overlay--center" @click.self="spendOpen = false">
        <div class="ch-modal">
            <div class="ch-drawer__head">
                <div>
                    <p class="ch-kicker">Spend</p>
                    <h3 class="ch-drawer__title">{{ spendCampaign?.name }}</h3>
                </div>
                <button type="button" class="ch-icon-btn" aria-label="Close" @click="spendOpen = false">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form class="ch-drawer__body" @submit.prevent="saveSpend">
                <div class="ch-form-grid">
                    <div class="ch-field ch-field--full">
                        <label for="spend_date">Date</label>
                        <input id="spend_date" v-model="spendForm.spend_date" type="date" required />
                    </div>
                    <div class="ch-field ch-field--full">
                        <label for="spend_amount">Spend amount</label>
                        <input id="spend_amount" v-model="spendForm.spend" type="number" min="0" step="0.01" required />
                    </div>
                    <div class="ch-field">
                        <label for="spend_impressions">Impressions</label>
                        <input id="spend_impressions" v-model="spendForm.impressions" type="number" min="0" />
                    </div>
                    <div class="ch-field">
                        <label for="spend_clicks">Clicks</label>
                        <input id="spend_clicks" v-model="spendForm.clicks" type="number" min="0" />
                    </div>
                </div>
                <div class="ch-drawer__foot">
                    <button type="button" class="ch-btn ch-btn--soft" @click="spendOpen = false">Cancel</button>
                    <button type="submit" class="ch-btn ch-btn--primary">Save spend</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import LoadingComponent from '../components/LoadingComponent.vue';
import alertService from '../../../services/alertService';

const emptyForm = () => ({
    id: null,
    name: '',
    channel: 'meta',
    objective: 'conversions',
    status: 'draft',
    utm_source: 'facebook',
    utm_medium: 'cpc',
    utm_campaign: '',
    utm_content: '',
    utm_term: '',
    landing_path: '/',
    daily_budget: null,
    total_budget: null,
    starts_at: '',
    ends_at: '',
    notes: '',
    product_ids: [],
});

export default {
    name: 'CampaignHubComponent',
    components: { LoadingComponent },
    data() {
        const to = new Date();
        const from = new Date();
        from.setDate(from.getDate() - 29);
        return {
            loading: { isActive: false },
            activeTab: 'campaigns',
            tabs: [
                { id: 'campaigns', label: 'Campaigns', icon: 'lab lab-line-promotion' },
                { id: 'performance', label: 'Performance', icon: 'fa-solid fa-chart-line' },
                { id: 'recovery', label: 'Recovery', icon: 'lab lab-line-refresh' },
                { id: 'audiences', label: 'Audiences', icon: 'fa-solid fa-users' },
                { id: 'catalog', label: 'Catalog', icon: 'lab lab-line-box' },
            ],
            from: from.toISOString().slice(0, 10),
            to: to.toISOString().slice(0, 10),
            campaigns: [],
            performance: { campaigns: [], totals: {} },
            attribution: { channels: [], totals: {} },
            recovery: { kpis: {}, queue: {}, recent_abandons: [] },
            audience: {},
            audienceSegment: 'all',
            catalog: {},
            catalogSync: { totals: {}, recent: [], configured: false },
            feedUrl: `${window.location.origin}/feeds/meta-catalog.csv`,
            drawerOpen: false,
            form: emptyForm(),
            selectedProducts: [],
            productResults: [],
            productSearch: '',
            productSearchLoading: false,
            productSearchTimer: null,
            productPreview: null,
            spendOpen: false,
            spendCampaign: null,
            spendForm: { spend_date: to.toISOString().slice(0, 10), spend: '', impressions: '', clicks: '' },
            metaStatus: { checked: false, ok: false, message: '' },
        };
    },
    computed: {
        performanceKpis() {
            const t = this.performance.totals || {};
            return [
                { label: 'Spend', value: this.formatMoney(t.spend) },
                { label: 'Revenue', value: this.formatMoney(t.revenue) },
                { label: 'Orders', value: t.orders ?? 0 },
                { label: 'ROAS', value: t.roas != null ? `${t.roas}x` : '—' },
            ];
        },
        catalogTotals() {
            return this.catalogSync.totals || {};
        },
    },
    mounted() {
        this.loadCampaigns();
        this.checkMetaStatus(true);
    },
    methods: {
        switchTab(id) {
            this.activeTab = id;
            this.refreshActive();
        },
        refreshActive() {
            if (this.activeTab === 'campaigns') this.loadCampaigns();
            if (this.activeTab === 'performance') this.loadPerformance();
            if (this.activeTab === 'recovery') this.loadRecovery();
            if (this.activeTab === 'audiences') this.loadAudiences();
            if (this.activeTab === 'catalog') {
                this.loadCatalog();
                this.loadCatalogSync();
            }
        },
        formatMoney(v) {
            const n = Number(v || 0);
            return n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        },
        isMetaChannel(channel) {
            return ['meta', 'facebook', 'instagram'].includes(channel);
        },
        channelLabel(channel) {
            return {
                meta: 'Meta',
                facebook: 'Facebook',
                instagram: 'Instagram',
                google: 'Google',
                tiktok: 'TikTok',
                other: 'Other',
            }[channel] || channel;
        },
        statusClass(status) {
            return {
                draft: 'is-draft',
                active: 'is-active',
                paused: 'is-paused',
                completed: 'is-done',
            }[status] || 'is-draft';
        },
        async checkMetaStatus(silent = false) {
            try {
                const res = await axios.get('admin/campaigns/meta/status');
                const data = res.data?.data || {};
                this.metaStatus = {
                    checked: true,
                    ok: !!data.ok,
                    message: data.message || '',
                };
                if (!silent) {
                    if (data.ok) alertService.success(data.message || 'Meta connected');
                    else alertService.error(data.message || 'Meta not connected');
                }
            } catch (e) {
                this.metaStatus = {
                    checked: true,
                    ok: false,
                    message: e.response?.data?.data?.message || e.response?.data?.message || e.message,
                };
                if (!silent) alertService.error(this.metaStatus.message);
            }
        },
        async loadCampaigns() {
            this.loading.isActive = true;
            try {
                const res = await axios.get('admin/campaigns', { params: { paginate: 0, order_column: 'id', order_type: 'desc' } });
                this.campaigns = res.data?.data || res.data || [];
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        async loadPerformance() {
            this.loading.isActive = true;
            try {
                const [perf, attr] = await Promise.all([
                    axios.get('admin/campaigns/performance', { params: { from: this.from, to: this.to } }),
                    axios.get('admin/campaigns/attribution', { params: { from: this.from, to: this.to } }),
                ]);
                this.performance = perf.data?.data || {};
                this.attribution = attr.data?.data || {};
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        async loadRecovery() {
            this.loading.isActive = true;
            try {
                const res = await axios.get('admin/campaigns/recovery', { params: { from: this.from, to: this.to } });
                this.recovery = res.data?.data || {};
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        async loadAudiences() {
            this.loading.isActive = true;
            try {
                const res = await axios.get('admin/campaigns/audiences', { params: { segment: this.audienceSegment } });
                this.audience = res.data?.data || {};
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        async loadCatalog() {
            this.loading.isActive = true;
            try {
                const res = await axios.get('admin/campaigns/catalog');
                this.catalog = res.data?.data || {};
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        async loadCatalogSync() {
            try {
                const res = await axios.get('admin/campaigns/catalog/sync-status');
                this.catalogSync = res.data?.data || { totals: {}, recent: [] };
            } catch (e) {
                this.catalogSync = { totals: {}, recent: [], configured: false };
            }
        },
        openCreate() {
            this.form = emptyForm();
            this.selectedProducts = [];
            this.productPreview = null;
            this.productSearch = '';
            this.drawerOpen = true;
            this.loadProductPicker();
        },
        openEdit(c) {
            const products = c.products || [];
            this.form = {
                ...emptyForm(),
                ...c,
                starts_at: c.starts_at || '',
                ends_at: c.ends_at || '',
                product_ids: products.map((p) => p.id),
            };
            this.selectedProducts = [...products];
            this.productPreview = null;
            this.productSearch = '';
            this.drawerOpen = true;
            this.loadProductPicker();
            if (products[0]) this.prepareProductById(products[0].id);
        },
        closeDrawer() {
            this.drawerOpen = false;
            this.productPreview = null;
        },
        isProductSelected(id) {
            return this.form.product_ids.includes(id);
        },
        toggleProduct(product) {
            if (this.isProductSelected(product.id)) {
                this.removeSelectedProduct(product.id);
                return;
            }
            this.selectedProducts = [...this.selectedProducts, product];
            this.form.product_ids = this.selectedProducts.map((p) => p.id);
            this.prepareProductById(product.id);
        },
        removeSelectedProduct(id) {
            this.selectedProducts = this.selectedProducts.filter((p) => p.id !== id);
            this.form.product_ids = this.selectedProducts.map((p) => p.id);
            if (this.productPreview?.product_id === id) this.productPreview = null;
        },
        onProductSearch() {
            clearTimeout(this.productSearchTimer);
            this.productSearchTimer = setTimeout(() => this.loadProductPicker(), 280);
        },
        async loadProductPicker() {
            this.productSearchLoading = true;
            try {
                const res = await axios.get('admin/campaigns/products', {
                    params: { search: this.productSearch || undefined, limit: 40 },
                });
                this.productResults = res.data?.data || [];
            } catch (e) {
                this.productResults = [];
            } finally {
                this.productSearchLoading = false;
            }
        },
        async prepareProductById(productId) {
            if (!productId) return;
            try {
                const res = await axios.get(`admin/campaigns/products/${productId}/prepare`);
                this.productPreview = res.data?.data || null;
                this.applyMetaSyncToSelected(productId, this.productPreview?.meta);
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            }
        },
        async validateProductById(productId) {
            try {
                const res = await axios.post(`admin/campaigns/products/${productId}/validate`);
                this.productPreview = res.data?.data || null;
                if (res.data?.status) alertService.success('Product validated for Meta');
                else alertService.error('Validation issues found — check preview');
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            }
        },
        async syncProductById(productId) {
            this.loading.isActive = true;
            try {
                const res = await axios.post(`admin/campaigns/products/${productId}/sync-meta`, { force: true });
                const d = res.data?.data || {};
                if (res.data?.status || d.ok) {
                    alertService.success(res.data?.message || d.message || 'Synced to Meta Catalog');
                    this.productPreview = d.data || d;
                    this.applyMetaSyncToSelected(productId, this.productPreview?.meta || this.productPreview);
                    this.loadCatalogSync();
                    this.loadProductPicker();
                } else {
                    alertService.error(res.data?.message || d.message || 'Sync failed');
                    if (d.data) this.productPreview = d.data;
                }
            } catch (e) {
                const d = e.response?.data?.data;
                if (d?.data) this.productPreview = d.data;
                else if (d) this.productPreview = d;
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        async syncSelectedToMeta() {
            const ids = this.selectedProducts.map((p) => p.id);
            if (!ids.length) {
                alertService.error('Select products first');
                return;
            }
            if (!confirm(`Sync ${ids.length} product(s) to Meta Catalog?`)) return;
            this.loading.isActive = true;
            try {
                const res = await axios.post('admin/campaigns/products/sync-meta-bulk', {
                    product_ids: ids,
                    force: true,
                });
                const d = res.data?.data || {};
                alertService.success(`Synced ok:${d.ok || 0} failed:${d.failed || 0} skipped:${d.skipped || 0}`);
                this.loadCatalogSync();
                this.loadProductPicker();
                if (this.productPreview?.product_id) this.prepareProductById(this.productPreview.product_id);
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        async retryFailedCatalog() {
            this.loading.isActive = true;
            try {
                const res = await axios.post('admin/campaigns/catalog/retry-failed');
                const d = res.data?.data || {};
                alertService.success(`Retry ok:${d.ok || 0} failed:${d.failed || 0}`);
                this.loadCatalogSync();
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        applyMetaSyncToSelected(productId, meta) {
            if (!meta) return;
            this.selectedProducts = this.selectedProducts.map((p) => (
                p.id === productId ? { ...p, meta_sync: meta } : p
            ));
            this.productResults = this.productResults.map((p) => (
                p.id === productId ? { ...p, meta_sync: meta } : p
            ));
        },
        async saveCampaign() {
            this.loading.isActive = true;
            try {
                const payload = {
                    ...this.form,
                    product_ids: this.selectedProducts.map((p) => p.id),
                };
                if (this.form.id) {
                    await axios.post(`admin/campaigns/${this.form.id}`, payload);
                } else {
                    await axios.post('admin/campaigns', payload);
                }
                alertService.successFlip(1, 'Campaign');
                this.closeDrawer();
                this.loadCampaigns();
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        async destroy(c) {
            if (!confirm(`Delete campaign "${c.name}"?`)) return;
            try {
                await axios.delete(`admin/campaigns/${c.id}`);
                this.loadCampaigns();
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            }
        },
        openSpend(c) {
            this.spendCampaign = c;
            this.spendForm = {
                spend_date: new Date().toISOString().slice(0, 10),
                spend: '',
                impressions: '',
                clicks: '',
            };
            this.spendOpen = true;
        },
        async saveSpend() {
            try {
                await axios.post(`admin/campaigns/${this.spendCampaign.id}/spends`, this.spendForm);
                this.spendOpen = false;
                alertService.successFlip(1, 'Spend');
                this.loadCampaigns();
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            }
        },
        async enqueueRecovery() {
            try {
                const res = await axios.post('admin/campaigns/recovery/enqueue');
                alertService.success(res.data?.data?.message || 'Enqueued');
                this.loadRecovery();
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            }
        },
        async processRecovery() {
            try {
                const res = await axios.post('admin/campaigns/recovery/process');
                const d = res.data?.data || {};
                alertService.success(`Sent ${d.sent || 0}, failed ${d.failed || 0}`);
                this.loadRecovery();
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            }
        },
        async exportAudience() {
            try {
                const res = await axios.get('admin/campaigns/audiences/export', {
                    params: { segment: this.audienceSegment },
                    responseType: 'blob',
                });
                const blob = new Blob([res.data], { type: 'text/csv' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `meta-audience-${this.audienceSegment}.csv`;
                link.click();
                URL.revokeObjectURL(link.href);
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            }
        },
        async syncAudience() {
            try {
                const res = await axios.post('admin/campaigns/audiences/sync', { segment: this.audienceSegment });
                const d = res.data?.data || {};
                if (d.synced) alertService.success(d.message);
                else alertService.error(d.message || 'Sync failed');
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            }
        },
        async pushToMeta(c) {
            if (c.external_campaign_id) {
                alertService.success('Already linked to Meta: ' + c.external_campaign_id);
                return;
            }
            const productCount = (c.products || []).length;
            const msg = productCount
                ? `Create PAUSED Meta campaign for "${c.name}" with ${productCount} product ad(s) from store images?`
                : `Create PAUSED Meta campaign for "${c.name}"? (No products linked — creatives will be empty until you attach products.)`;
            if (!confirm(msg)) return;
            this.loading.isActive = true;
            try {
                const res = await axios.post(`admin/campaigns/${c.id}/push-meta`);
                const d = res.data?.data || {};
                if (d.ok) {
                    alertService.success(d.message || 'Pushed to Meta');
                    this.loadCampaigns();
                } else {
                    alertService.error(d.message || res.data?.message || 'Push failed');
                }
            } catch (e) {
                alertService.error(e.response?.data?.data?.message || e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        async copyUrl(url) {
            try {
                await navigator.clipboard.writeText(url);
                alertService.success('Copied');
            } catch {
                prompt('Copy URL', url);
            }
        },
    },
};
</script>

<style scoped>
.ch-hub {
    --ch-ink: #1c1917;
    --ch-muted: #78716c;
    --ch-line: #e7e5e4;
    --ch-soft: #f5f5f4;
    --ch-card: #ffffff;
    --ch-accent: #0f766e;
    --ch-accent-2: #b45309;
    --ch-danger: #b91c1c;
    --ch-radius: 16px;
    --ch-shadow: 0 10px 30px rgba(28, 25, 23, 0.06);
    color: var(--ch-ink);
}

.ch-hero {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1.25rem 1.25rem 1rem;
    border-radius: var(--ch-radius);
    background:
        radial-gradient(1200px 280px at 0% 0%, rgba(15, 118, 110, 0.10), transparent 55%),
        radial-gradient(900px 240px at 100% 0%, rgba(180, 83, 9, 0.08), transparent 50%),
        linear-gradient(180deg, #fff 0%, #fafaf9 100%);
    border: 1px solid var(--ch-line);
    box-shadow: var(--ch-shadow);
}

@media (min-width: 1024px) {
    .ch-hero {
        flex-direction: row;
        align-items: flex-end;
        justify-content: space-between;
        padding: 1.5rem 1.75rem;
    }
}

.ch-kicker {
    margin: 0;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--ch-accent);
}

.ch-title {
    margin: 0.35rem 0 0;
    font-size: clamp(1.5rem, 3vw, 2rem);
    line-height: 1.15;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.ch-sub {
    margin: 0.45rem 0 0;
    max-width: 38rem;
    color: var(--ch-muted);
    font-size: 0.925rem;
    line-height: 1.5;
}

.ch-hero__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    align-items: center;
    width: 100%;
}

@media (min-width: 1024px) {
    .ch-hero__actions {
        width: auto;
        justify-content: flex-end;
    }
}

.ch-meta-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.55rem 0.8rem;
    border-radius: 999px;
    border: 1px solid var(--ch-line);
    background: #fff;
    cursor: pointer;
    user-select: none;
    font-size: 0.8rem;
    font-weight: 700;
}

.ch-meta-pill__dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 999px;
    background: #a8a29e;
}

.ch-meta-pill.is-ok {
    border-color: rgba(15, 118, 110, 0.25);
    background: rgba(15, 118, 110, 0.08);
    color: #0f766e;
}
.ch-meta-pill.is-ok .ch-meta-pill__dot { background: #0f766e; }

.ch-meta-pill.is-bad {
    border-color: rgba(185, 28, 28, 0.25);
    background: rgba(185, 28, 28, 0.06);
    color: #b91c1c;
}
.ch-meta-pill.is-bad .ch-meta-pill__dot { background: #b91c1c; }

.ch-date-range {
    display: grid;
    grid-template-columns: auto 1fr auto 1fr;
    gap: 0.4rem;
    align-items: center;
    padding: 0.4rem 0.55rem;
    border: 1px solid var(--ch-line);
    border-radius: 12px;
    background: #fff;
    width: 100%;
}

@media (min-width: 640px) {
    .ch-date-range { width: auto; }
}

.ch-date-range__label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--ch-muted);
}

.ch-date-range__input {
    border: 0;
    background: transparent;
    font-size: 0.85rem;
    color: var(--ch-ink);
    min-width: 0;
}

.ch-tabs {
    display: flex;
    gap: 0.4rem;
    margin-top: 1rem;
    padding: 0.35rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    border: 1px solid var(--ch-line);
    border-radius: 14px;
    background: #fff;
}

.ch-tabs::-webkit-scrollbar { display: none; }

.ch-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    white-space: nowrap;
    padding: 0.7rem 0.95rem;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--ch-muted);
    transition: background 0.2s ease, color 0.2s ease;
}

.ch-tab i { font-size: 0.95rem; }

.ch-tab.is-active {
    background: var(--ch-ink);
    color: #fff;
}

.ch-panel {
    margin-top: 1rem;
    padding: 1rem;
    border: 1px solid var(--ch-line);
    border-radius: var(--ch-radius);
    background: var(--ch-card);
    box-shadow: var(--ch-shadow);
}

@media (min-width: 768px) {
    .ch-panel { padding: 1.25rem 1.35rem; }
}

.ch-panel__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.ch-panel__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.ch-panel__hint {
    margin: 0.25rem 0 0;
    color: var(--ch-muted);
    font-size: 0.875rem;
}

.ch-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    min-height: 2.5rem;
    padding: 0.55rem 0.95rem;
    border-radius: 10px;
    border: 1px solid transparent;
    font-size: 0.82rem;
    font-weight: 700;
    transition: transform 0.15s ease, background 0.15s ease, border-color 0.15s ease;
}

.ch-btn:active { transform: scale(0.98); }
.ch-btn--sm { min-height: 2rem; padding: 0.35rem 0.65rem; font-size: 0.75rem; }
.ch-btn--primary { background: var(--ch-accent); color: #fff; }
.ch-btn--primary:hover { background: #0d9488; }
.ch-btn--dark { background: var(--ch-ink); color: #fff; }
.ch-btn--soft { background: var(--ch-soft); color: var(--ch-ink); border-color: var(--ch-line); }
.ch-btn--ghost { background: #fff; border-color: var(--ch-line); }
.ch-btn--danger { background: #fef2f2; color: var(--ch-danger); border-color: #fecaca; }

.ch-cards {
    display: grid;
    gap: 0.85rem;
}

.ch-card {
    border: 1px solid var(--ch-line);
    border-radius: 14px;
    padding: 1rem;
    background: linear-gradient(180deg, #fff, #fafaf9);
}

.ch-card__top {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
}

.ch-card__name {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.ch-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin: 0.4rem 0 0;
}

.ch-card__spend {
    text-align: right;
    flex-shrink: 0;
}

.ch-card__spend-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--ch-muted);
}

.ch-card__spend strong {
    font-size: 1.05rem;
    font-variant-numeric: tabular-nums;
}

.ch-card__row {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.45rem 0;
    border-top: 1px dashed var(--ch-line);
    font-size: 0.8rem;
}

.ch-card__row span { color: var(--ch-muted); font-weight: 600; }
.ch-card__row code {
    font-size: 0.75rem;
    max-width: 60%;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ch-card__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    margin-top: 0.85rem;
}

.ch-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border: 1px solid var(--ch-line);
    border-radius: 12px;
}

.ch-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 640px;
}

.ch-table th,
.ch-table td {
    padding: 0.85rem 1rem;
    text-align: left;
    border-bottom: 1px solid var(--ch-line);
    vertical-align: middle;
}

.ch-table th {
    font-size: 0.7rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--ch-muted);
    background: var(--ch-soft);
    font-weight: 800;
}

.ch-table tbody tr:last-child td { border-bottom: 0; }
.ch-table tbody tr:hover td { background: rgba(15, 118, 110, 0.03); }

.ch-td-title { font-weight: 800; }
.ch-td-sub {
    margin-top: 0.2rem;
    font-size: 0.7rem;
    color: var(--ch-muted);
    max-width: 16rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ch-code {
    font-size: 0.75rem;
    background: var(--ch-soft);
    padding: 0.2rem 0.4rem;
    border-radius: 6px;
}

.ch-link {
    color: var(--ch-accent);
    font-size: 0.8rem;
    font-weight: 800;
}

.ch-row-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.ch-channel,
.ch-status {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: capitalize;
}

.ch-channel { background: #ecfeff; color: #0e7490; }
.ch-channel--google { background: #eff6ff; color: #1d4ed8; }
.ch-channel--tiktok { background: #f5f3ff; color: #5b21b6; }
.ch-channel--other { background: #f5f5f4; color: #57534e; }

.ch-status.is-draft { background: #f5f5f4; color: #57534e; }
.ch-status.is-active { background: #ecfdf5; color: #047857; }
.ch-status.is-paused { background: #fffbeb; color: #b45309; }
.ch-status.is-done { background: #f1f5f9; color: #334155; }

.ch-kpi-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

@media (min-width: 768px) {
    .ch-kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}

.ch-kpi {
    padding: 1rem;
    border-radius: 14px;
    border: 1px solid var(--ch-line);
    background: linear-gradient(180deg, #fff, #fafaf9);
}

.ch-kpi__label {
    display: block;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--ch-muted);
}

.ch-kpi__value {
    display: block;
    margin-top: 0.4rem;
    font-size: clamp(1.15rem, 2.5vw, 1.45rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    font-variant-numeric: tabular-nums;
}

.ch-split {
    display: grid;
    gap: 1rem;
}

@media (min-width: 1280px) {
    .ch-split { grid-template-columns: 1.4fr 1fr; }
}

.ch-block { min-width: 0; }
.ch-block + .ch-block { margin-top: 0.25rem; }
.ch-block .ch-panel__title { margin-bottom: 0.75rem; }

.ch-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
    margin-bottom: 1rem;
}

.ch-feature {
    display: grid;
    gap: 1rem;
}

@media (min-width: 900px) {
    .ch-feature {
        grid-template-columns: 1.2fr 1fr;
        align-items: end;
    }
}

.ch-feature__controls {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
    align-items: end;
}

.ch-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-width: 0;
}

.ch-field label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--ch-muted);
}

.ch-field input,
.ch-field select,
.ch-field textarea,
.ch-select {
    width: 100%;
    min-height: 2.6rem;
    border: 1px solid var(--ch-line);
    border-radius: 10px;
    padding: 0.55rem 0.75rem;
    background: #fff;
    color: var(--ch-ink);
    font-size: 0.9rem;
}

.ch-field textarea { min-height: 5rem; resize: vertical; }

.ch-stat-banner {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.5rem;
    margin-top: 1rem;
    padding: 1rem 1.1rem;
    border-radius: 14px;
    background: rgba(15, 118, 110, 0.08);
    border: 1px solid rgba(15, 118, 110, 0.15);
}

.ch-stat-banner strong {
    font-size: 1.6rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.ch-feature--catalog {
    grid-template-columns: 1fr;
    padding: 0.25rem;
}

.ch-feed-url {
    display: block;
    margin-top: 0.85rem;
    padding: 0.85rem 1rem;
    border-radius: 12px;
    background: var(--ch-soft);
    border: 1px solid var(--ch-line);
    font-size: 0.78rem;
    word-break: break-all;
}

.ch-empty {
    text-align: center;
    padding: 2.5rem 1rem;
}

.ch-empty__icon {
    width: 3.25rem;
    height: 3.25rem;
    margin: 0 auto 0.85rem;
    display: grid;
    place-items: center;
    border-radius: 999px;
    background: rgba(15, 118, 110, 0.1);
    color: var(--ch-accent);
    font-size: 1.25rem;
}

.ch-empty h3 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 800;
}

.ch-empty p {
    margin: 0.4rem auto 1rem;
    max-width: 26rem;
    color: var(--ch-muted);
    font-size: 0.9rem;
}

.ch-empty-cell {
    text-align: center !important;
    color: var(--ch-muted);
    padding: 1.75rem 1rem !important;
}

.ch-overlay {
    position: fixed;
    inset: 0;
    z-index: 50;
    background: rgba(28, 25, 23, 0.45);
    display: flex;
}

.ch-overlay--page {
    align-items: stretch;
    justify-content: stretch;
    padding: 0;
}

@media (min-width: 768px) {
    .ch-overlay--page {
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
    }
}

.ch-overlay--center {
    align-items: flex-end;
    justify-content: center;
    padding: 0;
}

@media (min-width: 640px) {
    .ch-overlay--center {
        align-items: center;
        padding: 1rem;
    }
}

.ch-page-modal {
    width: 100%;
    height: 100dvh;
    max-height: 100dvh;
    background: #fff;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

@media (min-width: 768px) {
    .ch-page-modal {
        width: min(100%, 72rem);
        height: min(94dvh, 920px);
        max-height: 94dvh;
        border-radius: 20px;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.22);
    }
}

.ch-page-modal__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.15rem 1.25rem;
    border-bottom: 1px solid var(--ch-line);
    background:
        radial-gradient(700px 180px at 0% 0%, rgba(15, 118, 110, 0.08), transparent 60%),
        #fff;
    flex-shrink: 0;
}

@media (min-width: 768px) {
    .ch-page-modal__head { padding: 1.35rem 1.75rem; }
}

.ch-page-modal__body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 1.15rem 1.25rem 1.4rem;
    -webkit-overflow-scrolling: touch;
}

@media (min-width: 768px) {
    .ch-page-modal__body { padding: 1.35rem 1.75rem 1.6rem; }
}

.ch-page-modal__foot {
    display: flex;
    flex-direction: column-reverse;
    gap: 0.55rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--ch-line);
    position: sticky;
    bottom: 0;
    background: #fff;
}

@media (min-width: 640px) {
    .ch-page-modal__foot {
        flex-direction: row;
        justify-content: flex-end;
    }
}

.ch-form-sections {
    display: grid;
    gap: 1rem;
}

.ch-form-section {
    border: 1px solid var(--ch-line);
    border-radius: 14px;
    padding: 1rem;
    background: linear-gradient(180deg, #fff, #fafaf9);
}

@media (min-width: 768px) {
    .ch-form-section { padding: 1.15rem 1.25rem; }
}

.ch-form-section__title {
    margin: 0 0 0.85rem;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--ch-muted);
}

.ch-form-section__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
}

.ch-form-section__head .ch-form-section__title { margin: 0; }

.ch-pill-count {
    flex-shrink: 0;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 0.35rem 0.6rem;
    border-radius: 999px;
    background: rgba(15, 118, 110, 0.12);
    color: var(--ch-accent);
}

.ch-card__products {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
    margin-top: 0.65rem;
}

.ch-card__products--inline {
    margin-top: 0.45rem;
}

.ch-prod-chip {
    position: relative;
    width: 2rem;
    height: 2rem;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--ch-line);
    background: var(--ch-soft);
    flex-shrink: 0;
}

.ch-prod-chip img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.ch-prod-chip__vid {
    position: absolute;
    right: 1px;
    bottom: 1px;
    font-size: 7px;
    line-height: 1;
    padding: 1px 2px;
    border-radius: 3px;
    background: rgba(0, 0, 0, 0.7);
    color: #fff;
}

.ch-prod-more {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--ch-muted);
}

.ch-selected-products {
    display: grid;
    gap: 0.55rem;
    margin-bottom: 0.85rem;
}

.ch-selected-product {
    display: grid;
    grid-template-columns: 3rem 1fr auto;
    gap: 0.75rem;
    align-items: center;
    padding: 0.55rem 0.65rem;
    border: 1px solid var(--ch-line);
    border-radius: 12px;
    background: #fff;
}

.ch-selected-product img {
    width: 3rem;
    height: 3rem;
    border-radius: 8px;
    object-fit: cover;
}

.ch-selected-product__meta {
    display: block;
    margin-top: 0.15rem;
    font-size: 0.75rem;
    color: var(--ch-muted);
}

.ch-product-search input {
    width: 100%;
    min-height: 2.6rem;
    border: 1px solid var(--ch-line);
    border-radius: 10px;
    padding: 0.55rem 0.85rem;
    background: #fff;
    margin-bottom: 0.85rem;
}

.ch-product-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.65rem;
}

@media (min-width: 640px) {
    .ch-product-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (min-width: 900px) {
    .ch-product-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}

.ch-product-tile {
    text-align: left;
    border: 1px solid var(--ch-line);
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    padding: 0;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.ch-product-tile.is-selected {
    border-color: var(--ch-accent);
    box-shadow: 0 0 0 2px rgba(15, 118, 110, 0.18);
}

.ch-product-tile__media {
    position: relative;
    aspect-ratio: 1;
    background: var(--ch-soft);
}

.ch-product-tile__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.ch-product-tile__badge {
    position: absolute;
    left: 0.4rem;
    top: 0.4rem;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 0.2rem 0.4rem;
    border-radius: 6px;
    background: rgba(28, 25, 23, 0.78);
    color: #fff;
}

.ch-product-tile__check {
    position: absolute;
    right: 0.4rem;
    top: 0.4rem;
    width: 1.4rem;
    height: 1.4rem;
    border-radius: 999px;
    display: grid;
    place-items: center;
    background: var(--ch-accent);
    color: #fff;
    font-size: 0.7rem;
}

.ch-product-tile__body {
    display: grid;
    gap: 0.15rem;
    padding: 0.55rem 0.65rem 0.7rem;
    font-size: 0.78rem;
}

.ch-product-tile__body strong {
    font-size: 0.82rem;
}

.ch-product-tile__body span {
    color: var(--ch-muted);
}

.ch-product-tile__sync {
    position: absolute;
    left: 0.4rem;
    bottom: 0.4rem;
    font-size: 0.6rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 0.15rem 0.35rem;
    border-radius: 6px;
    background: rgba(28, 25, 23, 0.75);
    color: #fff;
}

.ch-sync-badge {
    display: inline-flex;
    align-items: center;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 0.25rem 0.5rem;
    border-radius: 999px;
    background: var(--ch-soft);
    color: var(--ch-muted);
}

.ch-sync-badge.is-synced { background: rgba(15, 118, 110, 0.12); color: var(--ch-accent); }
.ch-sync-badge.is-pending,
.ch-sync-badge.is-stale { background: rgba(180, 83, 9, 0.12); color: var(--ch-accent-2); }
.ch-sync-badge.is-failed,
.ch-sync-badge.is-needs_attention { background: rgba(185, 28, 28, 0.1); color: var(--ch-danger); }

.ch-selected-product.is-active {
    border-color: var(--ch-accent);
    box-shadow: 0 0 0 2px rgba(15, 118, 110, 0.12);
}

.ch-selected-product { cursor: pointer; }

.ch-meta-preview {
    display: grid;
    gap: 0.85rem;
    margin-bottom: 1rem;
    padding: 0.85rem;
    border: 1px solid var(--ch-line);
    border-radius: 14px;
    background: #fff;
}

@media (min-width: 768px) {
    .ch-meta-preview {
        grid-template-columns: 8.5rem 1fr;
        align-items: start;
    }
}

.ch-meta-preview__media {
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    background: var(--ch-soft);
}

.ch-meta-preview__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.ch-meta-preview__title {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
}

.ch-meta-preview__desc {
    margin: 0.35rem 0 0.55rem;
    color: var(--ch-muted);
    font-size: 0.85rem;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ch-meta-preview__facts {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.ch-meta-preview__facts span {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.25rem 0.5rem;
    border-radius: 999px;
    background: var(--ch-soft);
    color: var(--ch-ink);
}

.ch-meta-list {
    margin: 0.55rem 0 0;
    padding-left: 1.1rem;
    font-size: 0.78rem;
}

.ch-meta-list.is-error { color: var(--ch-danger); }
.ch-meta-list.is-warn { color: var(--ch-accent-2); }

.ch-warn-inline { color: var(--ch-accent-2); font-weight: 700; }

.ch-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}

.ch-modal {
    width: min(100%, 28rem);
    height: auto;
    max-height: 92dvh;
    background: #fff;
    display: flex;
    flex-direction: column;
    border-radius: 18px 18px 0 0;
    overflow: hidden;
}

@media (min-width: 640px) {
    .ch-modal { border-radius: 18px; }
}

.ch-drawer__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.1rem 1.2rem;
    border-bottom: 1px solid var(--ch-line);
    background: #fff;
    flex-shrink: 0;
}

.ch-drawer__title {
    margin: 0.2rem 0 0;
    font-size: 1.25rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.ch-icon-btn {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 999px;
    border: 1px solid var(--ch-line);
    display: grid;
    place-items: center;
    background: #fff;
    flex-shrink: 0;
}

.ch-drawer__body {
    padding: 1.1rem 1.2rem 1.4rem;
    overflow-y: auto;
    flex: 1;
}

.ch-form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.85rem;
}

.ch-form-grid--3,
.ch-form-grid--4 {
    grid-template-columns: 1fr;
}

@media (min-width: 640px) {
    .ch-form-grid { grid-template-columns: 1fr 1fr; }
    .ch-form-grid--3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ch-form-grid--4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ch-field--full { grid-column: 1 / -1; }
}

@media (min-width: 900px) {
    .ch-form-grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .ch-form-grid--4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .ch-field--span-2 { grid-column: span 2; }
}

.ch-drawer__foot {
    display: flex;
    flex-direction: column-reverse;
    gap: 0.55rem;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--ch-line);
}

@media (min-width: 640px) {
    .ch-drawer__foot {
        flex-direction: row;
        justify-content: flex-end;
    }
}
</style>
