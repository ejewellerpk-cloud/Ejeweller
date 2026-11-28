<template>
    <LoadingComponent :props="loading" />

    <div class="col-12">
        <div class="reviews-page">
            <!-- Header -->
            <div class="reviews-page__header">
                <div class="min-w-0">
                    <h1 class="reviews-page__title">{{ $t('menu.reviews') }}</h1>
                    <p class="reviews-page__subtitle">Moderate customer feedback before it goes live</p>
                </div>
                <div class="reviews-page__toolbar">
                    <router-link
                        v-if="permissionChecker('reviews')"
                        :to="{ name: 'admin.review.generate' }"
                        class="reviews-btn reviews-btn--ghost"
                    >
                        <i class="lab lab-line-star"></i>
                        <span>{{ $t('button.generate_reviews') }}</span>
                    </router-link>
                    <router-link
                        v-if="permissionChecker('reviews')"
                        :to="{ name: 'admin.review.create' }"
                        class="reviews-btn reviews-btn--primary"
                    >
                        <i class="lab lab-line-add-circle"></i>
                        <span>{{ $t('button.add_review') }}</span>
                    </router-link>
                    <TableLimitComponent :method="list" :search="props.search" :page="paginationPage" />
                    <button type="button" class="reviews-btn reviews-btn--ghost" @click="showFilter = !showFilter">
                        <i class="lab lab-line-filter lab-font-size-14"></i>
                        <span>{{ $t('button.filter') }}</span>
                    </button>
                    <div class="dropdown-group">
                        <ExportComponent />
                        <div class="dropdown-list db-card-filter-dropdown-list">
                            <PrintComponent :props="printObj" />
                            <ExcelComponent :method="xls" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status summary -->
            <div class="reviews-stats">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.key"
                    type="button"
                    class="reviews-stats__card"
                    :class="{ 'is-active': activeStatusTab === tab.key }"
                    @click="setStatusTab(tab.key)"
                >
                    <span class="reviews-stats__label">{{ tab.label }}</span>
                    <span class="reviews-stats__value" :class="tab.tone">{{ tab.count }}</span>
                </button>
            </div>

            <!-- Filters -->
            <div v-show="showFilter" class="reviews-filters">
                <form @submit.prevent="search">
                    <div class="reviews-filters__grid">
                        <div>
                            <label class="reviews-field-label" for="product_id">{{ $t('label.product') }}</label>
                            <vue-select
                                class="db-field-control f-b-custom-select"
                                id="product_id"
                                v-model="props.search.product_id"
                                :options="products"
                                label-by="name"
                                value-by="id"
                                :closeOnSelect="true"
                                :searchable="true"
                                :clearOnClose="true"
                                placeholder="--"
                                search-placeholder="--"
                            />
                        </div>
                        <div>
                            <label class="reviews-field-label" for="user_id">{{ $t('label.customer') }}</label>
                            <vue-select
                                class="db-field-control f-b-custom-select"
                                id="user_id"
                                v-model="props.search.user_id"
                                :options="customers"
                                label-by="name"
                                value-by="id"
                                :closeOnSelect="true"
                                :searchable="true"
                                :clearOnClose="true"
                                placeholder="--"
                                search-placeholder="--"
                            />
                        </div>
                        <div class="reviews-filters__actions">
                            <button type="submit" class="reviews-btn reviews-btn--primary">
                                <i class="lab lab-line-search lab-font-size-16"></i>
                                <span>{{ $t('button.search') }}</span>
                            </button>
                            <button type="button" class="reviews-btn reviews-btn--muted" @click="clear">
                                <i class="lab lab-line-cross lab-font-size-22"></i>
                                <span>{{ $t('button.clear') }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Review feed -->
            <div id="print" class="reviews-feed">
                <template v-if="reviews.length > 0">
                    <article
                        v-for="review in reviews"
                        :key="review.id"
                        class="review-card"
                        :class="statusAccentClass(review.status)"
                    >
                        <div class="review-card__top">
                            <div class="review-card__identity">
                                <div class="review-card__avatar" aria-hidden="true">
                                    {{ initials(review.user_name) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="review-card__name-row">
                                        <h3 class="review-card__name">{{ review.user_name || 'Guest' }}</h3>
                                        <span
                                            v-if="Number(review.is_verified_purchase) === askEnum.YES"
                                            class="review-badge review-badge--verified"
                                        >Verified purchase</span>
                                    </div>
                                    <p class="review-card__meta">
                                        <span>{{ review.date || '—' }}</span>
                                        <span class="review-card__dot">·</span>
                                        <span :class="reviewStatusTextClass(review.status)">
                                            {{ reviewStatusLabel(review.status) }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="review-card__rating">
                                <starRating
                                    border-color="#E8A317"
                                    inactive-color="#F3F0E8"
                                    active-color="#E8A317"
                                    :rounded-corners="true"
                                    :padding="2"
                                    :border-width="1.5"
                                    :star-size="16"
                                    :round-start-rating="false"
                                    :show-rating="false"
                                    :read-only="true"
                                    :max-rating="5"
                                    :rating="review.star"
                                />
                                <span class="review-card__score">{{ review.star }}/5</span>
                            </div>
                        </div>

                        <div class="review-card__product">
                            <img
                                v-if="review.product_thumb"
                                :src="review.product_thumb"
                                :alt="review.product_name"
                                class="review-card__product-img"
                                loading="lazy"
                            />
                            <div v-else class="review-card__product-fallback" aria-hidden="true">
                                <i class="lab lab-line-box"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="review-card__product-label">{{ $t('label.product') }}</p>
                                <p class="review-card__product-name">{{ review.product_name || '—' }}</p>
                            </div>
                        </div>

                        <p class="review-card__body">{{ plainReview(review.review) }}</p>

                        <div v-if="review.images && review.images.length" class="review-card__photos">
                            <img
                                v-for="(img, imgIndex) in review.images"
                                :key="imgIndex"
                                :src="img"
                                alt="Review photo"
                                class="review-card__photo"
                                loading="lazy"
                            />
                        </div>

                        <div v-if="permissionChecker('reviews')" class="review-card__actions hidden-print">
                            <button
                                v-if="Number(review.status) !== reviewStatusEnum.APPROVED"
                                type="button"
                                class="reviews-btn reviews-btn--approve"
                                @click="changeStatus(review.id, reviewStatusEnum.APPROVED)"
                            >Approve</button>
                            <button
                                v-if="Number(review.status) !== reviewStatusEnum.REJECTED"
                                type="button"
                                class="reviews-btn reviews-btn--reject"
                                @click="changeStatus(review.id, reviewStatusEnum.REJECTED)"
                            >Reject</button>
                            <div class="review-card__icon-actions">
                                <SmIconViewComponent :link="'admin.review.show'" :id="review.id" />
                                <SmIconSidebarModalEditComponent @click="edit(review)" />
                                <SmIconDeleteComponent @click="destroy(review.id)" />
                            </div>
                        </div>
                    </article>
                </template>

                <div v-else class="reviews-empty">
                    <img
                        class="reviews-empty__img"
                        :src="ENV.API_URL + '/images/default/not-found/not_found.png'"
                        alt="Not Found"
                    />
                    <p class="reviews-empty__text">{{ $t('message.no_data_found') }}</p>
                </div>
            </div>

            <div class="reviews-pagination" v-if="reviews.length > 0">
                <PaginationSMBox :pagination="pagination" :method="list" />
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <PaginationTextComponent :props="{ page: paginationPage }" />
                    <PaginationBox :pagination="pagination" :method="list" />
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="js">
import starRating from "vue-star-rating";
import ENV from "../../../config/env";
import statusEnum from "../../../enums/modules/statusEnum";
import reviewStatusEnum from "../../../enums/modules/reviewStatusEnum";
import askEnum from "../../../enums/modules/askEnum";
import alertService from "../../../services/alertService";
import appService from "../../../services/appService";
import LoadingComponent from "../components/LoadingComponent";
import TableLimitComponent from "../components/TableLimitComponent";
import SmIconSidebarModalEditComponent from "../components/buttons/SmIconSidebarModalEditComponent";
import SmIconDeleteComponent from "../components/buttons/SmIconDeleteComponent";
import SmIconViewComponent from "../components/buttons/SmIconViewComponent";
import ExcelComponent from "../components/buttons/export/ExcelComponent";
import ExportComponent from "../components/buttons/export/ExportComponent";
import PrintComponent from "../components/buttons/export/PrintComponent";
import PaginationBox from "../components/pagination/PaginationBox";
import PaginationSMBox from "../components/pagination/PaginationSMBox";
import PaginationTextComponent from "../components/pagination/PaginationTextComponent";

export default {
    name: 'ReviewListComponent',
    components: {
        PaginationBox,
        PaginationSMBox,
        PaginationTextComponent,
        TableLimitComponent,
        PrintComponent,
        ExcelComponent,
        ExportComponent,
        SmIconViewComponent,
        LoadingComponent,
        SmIconSidebarModalEditComponent,
        SmIconDeleteComponent,
        starRating
    },
    data() {
        return {
            loading: {
                isActive: false
            },
            showFilter: false,
            activeStatusTab: 'all',
            printObj: {
                id: "print",
                popTitle: this.$t('menu.reviews')
            },
            props: {
                search: {
                    paginate: 1,
                    page: 1,
                    per_page: 10,
                    order_column: 'id',
                    order_type: 'desc',
                    user_id: null,
                    product_id: null,
                    status: null,
                }
            },
            productSearch: {
                paginate: 0,
                page: 1,
                order_column: 'id',
            },
            ENV: ENV,
            reviewStatusEnum: reviewStatusEnum,
            askEnum: askEnum,
        }
    },
    mounted() {
        const queryProductId = this.$route.query.product_id;
        if (queryProductId) {
            this.props.search.product_id = Number(queryProductId) || queryProductId;
            this.showFilter = true;
        }
        this.list();
        this.loadStats();
        this.loading.isActive = true;
        this.$store.dispatch('product/getSimpleProduct', this.productSearch).then(() => {
            this.loading.isActive = false;
        }).catch(() => {
            this.loading.isActive = false;
        });
        this.$store.dispatch('user/lists', {
            order_column: 'id',
            order_type: 'asc',
            status: statusEnum.ACTIVE
        });
    },
    computed: {
        reviews: function () {
            return this.$store.getters['review/lists'];
        },
        pagination: function () {
            return this.$store.getters['review/pagination'];
        },
        paginationPage: function () {
            return this.$store.getters['review/page'];
        },
        products: function () {
            return this.$store.getters['product/simpleList'];
        },
        customers: function () {
            return this.$store.getters['user/lists'];
        },
        stats: function () {
            return this.$store.getters['review/stats'] || {};
        },
        statusTabs: function () {
            return [
                { key: 'all', label: 'All', count: this.stats.all || 0, tone: '' },
                { key: 'pending', label: 'Pending', count: this.stats.pending || 0, tone: 'is-pending' },
                { key: 'approved', label: 'Approved', count: this.stats.approved || 0, tone: 'is-approved' },
                { key: 'rejected', label: 'Rejected', count: this.stats.rejected || 0, tone: 'is-rejected' },
            ];
        },
    },
    methods: {
        plainReview: function (text) {
            return appService.htmlTagRemover(text || '');
        },
        initials: function (name) {
            const parts = String(name || 'G').trim().split(/\s+/).filter(Boolean);
            if (!parts.length) return 'G';
            if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
            return (parts[0][0] + parts[1][0]).toUpperCase();
        },
        search: function () {
            this.list();
        },
        permissionChecker(e) {
            return appService.permissionChecker(e);
        },
        loadStats: function () {
            this.$store.dispatch('review/stats').catch(() => {});
        },
        setStatusTab: function (key) {
            this.activeStatusTab = key;
            const map = {
                all: null,
                pending: reviewStatusEnum.PENDING,
                approved: reviewStatusEnum.APPROVED,
                rejected: reviewStatusEnum.REJECTED,
            };
            this.props.search.status = map[key];
            this.list(1);
        },
        list: function (page = 1) {
            this.loading.isActive = true;
            this.props.search.page = page;
            this.$store.dispatch('review/lists', this.props.search)
                .then(() => {
                    this.loading.isActive = false;
                })
                .catch(() => {
                    this.loading.isActive = false;
                })
        },
        clear: function () {
            this.activeStatusTab = 'all';
            this.props.search = {
                paginate: 1,
                page: 1,
                per_page: 10,
                order_column: 'id',
                order_type: 'desc',
                user_id: null,
                product_id: null,
                status: null,
            };
            this.list();
        },
        reviewStatusLabel: function (status) {
            const map = {
                [reviewStatusEnum.PENDING]: 'Pending',
                [reviewStatusEnum.APPROVED]: 'Approved',
                [reviewStatusEnum.REJECTED]: 'Rejected',
            };
            return map[Number(status)] || 'Pending';
        },
        reviewStatusTextClass: function (status) {
            const value = Number(status);
            if (value === reviewStatusEnum.APPROVED) return 'text-approved';
            if (value === reviewStatusEnum.REJECTED) return 'text-rejected';
            return 'text-pending';
        },
        statusAccentClass: function (status) {
            const value = Number(status);
            if (value === reviewStatusEnum.APPROVED) return 'is-approved';
            if (value === reviewStatusEnum.REJECTED) return 'is-rejected';
            return 'is-pending';
        },
        changeStatus: function (id, status) {
            this.loading.isActive = true;
            this.$store.dispatch('review/changeStatus', { id, status }).then(() => {
                this.loading.isActive = false;
                alertService.success(status === reviewStatusEnum.APPROVED ? 'Review approved' : 'Review rejected');
                this.loadStats();
                this.list(this.props.search.page);
            }).catch((err) => {
                this.loading.isActive = false;
                alertService.error(err.response?.data?.message || 'Unable to update review status');
            });
        },
        xls: function () {
            this.loading.isActive = true;
            this.$store.dispatch('review/export', this.props.search).then(res => {
                this.loading.isActive = false;
                const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = this.$t("menu.reviews");
                link.click();
                URL.revokeObjectURL(link.href);
            }).catch((err) => {
                this.loading.isActive = false;
                alertService.error(err.response?.data?.message);
            });
        },
        edit: function (review) {
            this.$router.push({
                name: 'admin.review.edit',
                params: { id: review.id },
            });
        },
        destroy: function (id) {
            appService.destroyConfirmation().then(() => {
                this.loading.isActive = true;
                this.$store.dispatch('review/destroy', {
                    id: id,
                    search: this.props.search,
                }).then(() => {
                    this.loading.isActive = false;
                    this.loadStats();
                    alertService.successFlip(null, this.$t('menu.reviews'));
                }).catch((err) => {
                    this.loading.isActive = false;
                    alertService.error(err.response?.data?.message);
                });
            }).catch(() => {});
        },
    }
}
</script>

<style scoped>
.reviews-page {
    --rp-ink: #1a1c1e;
    --rp-muted: #6b7280;
    --rp-line: #e8e6e1;
    --rp-surface: #ffffff;
    --rp-canvas: #f7f6f3;
    --rp-amber: #e8a317;
    --rp-approve: #0f7a3f;
    --rp-reject: #b42318;
    --rp-pending: #b54708;
    background: var(--rp-canvas);
    border: 1px solid var(--rp-line);
    border-radius: 1rem;
    padding: 1.25rem;
}

@media (min-width: 640px) {
    .reviews-page {
        padding: 1.5rem;
    }
}

.reviews-page__header {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

@media (min-width: 768px) {
    .reviews-page__header {
        flex-direction: row;
        align-items: flex-start;
        justify-content: space-between;
    }
}

.reviews-page__title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--rp-ink);
    line-height: 1.2;
}

.reviews-page__subtitle {
    margin: 0.35rem 0 0;
    font-size: 0.875rem;
    color: var(--rp-muted);
}

.reviews-page__toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
}

.reviews-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    min-height: 2.35rem;
    padding: 0.45rem 0.9rem;
    border-radius: 9999px;
    font-size: 0.8125rem;
    font-weight: 700;
    line-height: 1;
    border: 1px solid transparent;
    transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    cursor: pointer;
    text-decoration: none;
    white-space: nowrap;
}

.reviews-btn--primary {
    background: #1a1c1e;
    color: #fff;
}

.reviews-btn--primary:hover {
    background: #2d3034;
}

.reviews-btn--ghost {
    background: var(--rp-surface);
    border-color: var(--rp-line);
    color: var(--rp-ink);
}

.reviews-btn--muted {
    background: #eceae4;
    color: var(--rp-ink);
}

.reviews-btn--approve {
    background: #0f7a3f;
    color: #fff;
}

.reviews-btn--reject {
    background: #fff;
    color: var(--rp-reject);
    border-color: #f3c4bf;
}

.reviews-btn--reject:hover {
    background: #fef3f2;
}

.reviews-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.65rem;
    margin-bottom: 1.25rem;
}

@media (min-width: 768px) {
    .reviews-stats {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

.reviews-stats__card {
    text-align: left;
    background: var(--rp-surface);
    border: 1px solid var(--rp-line);
    border-radius: 0.9rem;
    padding: 0.9rem 1rem;
    cursor: pointer;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.reviews-stats__card.is-active {
    border-color: #1a1c1e;
    box-shadow: 0 0 0 1px #1a1c1e;
}

.reviews-stats__label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--rp-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.reviews-stats__value {
    display: block;
    margin-top: 0.35rem;
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--rp-ink);
    letter-spacing: -0.03em;
    line-height: 1;
}

.reviews-stats__value.is-pending { color: var(--rp-pending); }
.reviews-stats__value.is-approved { color: var(--rp-approve); }
.reviews-stats__value.is-rejected { color: var(--rp-reject); }

.reviews-filters {
    background: var(--rp-surface);
    border: 1px solid var(--rp-line);
    border-radius: 0.9rem;
    padding: 1rem;
    margin-bottom: 1.25rem;
}

.reviews-filters__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.85rem;
}

@media (min-width: 768px) {
    .reviews-filters__grid {
        grid-template-columns: 1fr 1fr auto;
        align-items: end;
    }
}

.reviews-field-label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--rp-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.reviews-filters__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.reviews-feed {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.review-card {
    background: var(--rp-surface);
    border: 1px solid var(--rp-line);
    border-radius: 1rem;
    padding: 1rem 1.1rem;
    border-left-width: 4px;
}

.review-card.is-pending { border-left-color: #f5b84c; }
.review-card.is-approved { border-left-color: #34a853; }
.review-card.is-rejected { border-left-color: #d92d20; }

.review-card__top {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 0.9rem;
}

@media (min-width: 640px) {
    .review-card__top {
        flex-direction: row;
        align-items: flex-start;
        justify-content: space-between;
    }
}

.review-card__identity {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    min-width: 0;
}

.review-card__avatar {
    width: 2.6rem;
    height: 2.6rem;
    border-radius: 9999px;
    background: #eceae4;
    color: var(--rp-ink);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 800;
    flex-shrink: 0;
}

.review-card__name-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
}

.review-card__name {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
    color: var(--rp-ink);
    text-transform: capitalize;
}

.review-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 9999px;
    padding: 0.15rem 0.5rem;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.review-badge--verified {
    background: #e8f6ee;
    color: var(--rp-approve);
}

.review-card__meta {
    margin: 0.2rem 0 0;
    font-size: 0.8rem;
    color: var(--rp-muted);
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
}

.review-card__dot {
    opacity: 0.5;
}

.text-pending { color: var(--rp-pending); font-weight: 700; }
.text-approved { color: var(--rp-approve); font-weight: 700; }
.text-rejected { color: var(--rp-reject); font-weight: 700; }

.review-card__rating {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.review-card__score {
    font-size: 0.8rem;
    font-weight: 800;
    color: var(--rp-ink);
}

.review-card__product {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 0.75rem;
    border-radius: 0.75rem;
    background: var(--rp-canvas);
    margin-bottom: 0.85rem;
}

.review-card__product-img,
.review-card__product-fallback {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.55rem;
    object-fit: cover;
    flex-shrink: 0;
    background: #fff;
    border: 1px solid var(--rp-line);
}

.review-card__product-fallback {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--rp-muted);
}

.review-card__product-label {
    margin: 0;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--rp-muted);
}

.review-card__product-name {
    margin: 0.1rem 0 0;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--rp-ink);
    text-transform: capitalize;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.review-card__body {
    margin: 0 0 0.85rem;
    font-size: 0.95rem;
    line-height: 1.55;
    color: #374151;
    white-space: pre-wrap;
    word-break: break-word;
}

.review-card__photos {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.9rem;
}

.review-card__photo {
    width: 4.25rem;
    height: 4.25rem;
    object-fit: cover;
    border-radius: 0.6rem;
    border: 1px solid var(--rp-line);
}

.review-card__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    padding-top: 0.85rem;
    border-top: 1px solid var(--rp-line);
}

.review-card__icon-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-left: auto;
}

.reviews-empty {
    text-align: center;
    background: var(--rp-surface);
    border: 1px dashed var(--rp-line);
    border-radius: 1rem;
    padding: 2.5rem 1rem;
}

.reviews-empty__img {
    width: min(100%, 220px);
    margin: 0 auto;
}

.reviews-empty__text {
    margin: 0.75rem 0 0;
    font-size: 1rem;
    color: var(--rp-muted);
}

.reviews-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--rp-line);
}

@media print {
    .hidden-print {
        display: none !important;
    }
}
</style>
