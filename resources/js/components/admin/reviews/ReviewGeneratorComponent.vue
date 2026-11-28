<template>
    <LoadingComponent :props="loading" />

    <div class="col-12">
        <div class="rg-page">
            <div class="rg-page__header">
                <div class="min-w-0">
                    <h1 class="rg-page__title">{{ $t('label.reviews_generator') }}</h1>
                    <p class="rg-page__subtitle">{{ $t('message.reviews_generator_hint') }}</p>
                </div>
                <router-link :to="{ name: 'admin.review.list' }" class="rg-btn rg-btn--muted">
                    <i class="lab lab-line-undo lab-font-size-16"></i>
                    <span>{{ $t('button.back') }}</span>
                </router-link>
            </div>

            <form class="rg-card" @submit.prevent="generate">
                <div class="rg-section">
                    <label class="rg-label required" for="rg_product_id">{{ $t('label.product') }}</label>
                    <vue-select
                        id="rg_product_id"
                        class="db-field-control f-b-custom-select"
                        v-model="form.product_id"
                        :options="products"
                        label-by="name"
                        value-by="id"
                        :closeOnSelect="true"
                        :searchable="true"
                        :clearOnClose="true"
                        placeholder="--"
                        search-placeholder="--"
                        :class="errors.product_id ? 'invalid' : ''"
                    />
                    <small class="db-field-alert" v-if="errors.product_id">{{ errors.product_id[0] }}</small>

                    <div v-if="selectedProduct" class="rg-product">
                        <img
                            class="rg-product__thumb"
                            :src="selectedProduct.cover || selectedProduct.thumb"
                            :alt="selectedProduct.name"
                        />
                        <div class="min-w-0">
                            <p class="rg-product__name">{{ selectedProduct.name }}</p>
                            <p class="rg-product__meta" v-if="selectedProduct.sku">
                                SKU: {{ selectedProduct.sku }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rg-section">
                    <label class="rg-label required" for="rg_quantity">{{ $t('label.quantity') }}</label>
                    <input
                        id="rg_quantity"
                        v-model.number="form.quantity"
                        type="number"
                        min="1"
                        max="50"
                        class="db-field-control rg-qty"
                        :class="errors.quantity ? 'invalid' : ''"
                    />
                    <small class="rg-help">{{ $t('message.reviews_generator_quantity_hint') }}</small>
                    <small class="db-field-alert" v-if="errors.quantity">{{ errors.quantity[0] }}</small>
                </div>

                <div class="rg-section rg-section--wide">
                    <div class="rg-images-head">
                        <div>
                            <label class="rg-label">{{ $t('label.upload_images') }}</label>
                            <p class="rg-help">{{ $t('message.reviews_generator_images_hint') }}</p>
                        </div>
                        <button type="button" class="rg-btn rg-btn--primary" @click="openImageDialog">
                            <i class="lab lab-fill-image"></i>
                            <span>{{ $t('button.select_images') }}</span>
                        </button>
                    </div>

                    <div v-if="imageItems.length" class="rg-images">
                        <div class="relative" v-for="(item, index) in imageItems" :key="item.id">
                            <button
                                @click="removeImage(index)"
                                type="button"
                                class="lab-fill-close text-xs w-5 h-5 leading-5 rounded-full shadow absolute -top-1 -right-1 text-danger bg-white z-10"
                            ></button>
                            <img :src="item.url" alt="review" class="rg-images__preview" />
                        </div>
                    </div>
                    <p v-else class="rg-empty-images">{{ $t('message.reviews_generator_no_images') }}</p>
                    <small class="db-field-alert" v-if="errors['images.0']">{{ errors['images.0'][0] }}</small>
                    <p v-if="imageItems.length" class="rg-help mt-2">
                        {{ $t('message.reviews_generator_images_selected', { count: imageItems.length }) }}
                    </p>
                </div>

                <div class="rg-actions">
                    <button type="submit" class="rg-btn rg-btn--primary" :disabled="loading.isActive">
                        <i class="lab lab-line-star"></i>
                        <span>{{ $t('button.generate_reviews') }}</span>
                    </button>
                    <router-link :to="{ name: 'admin.review.list' }" class="rg-btn rg-btn--muted">
                        <i class="lab lab-fill-close-circle"></i>
                        <span>{{ $t('button.cancel') }}</span>
                    </router-link>
                </div>
            </form>
        </div>
    </div>

    <!-- Image picker dialog -->
    <div
        v-if="showImageDialog"
        class="rg-dialog"
        @keydown.esc.prevent="closeImageDialog"
    >
        <div class="rg-dialog__backdrop" @click="closeImageDialog"></div>
        <div
            class="rg-dialog__panel"
            role="dialog"
            aria-modal="true"
            :aria-label="$t('button.select_images')"
            tabindex="-1"
            ref="imageDialog"
        >
            <div class="rg-dialog__header">
                <h2 class="rg-dialog__title">{{ $t('button.select_images') }}</h2>
                <button type="button" class="rg-dialog__close" @click="closeImageDialog" aria-label="Close">
                    <i class="lab lab-line-cross"></i>
                </button>
            </div>

            <div
                class="rg-dropzone"
                :class="{ 'is-dragging': isDragging }"
                @click="openFileBrowser"
                @dragenter.prevent="onDragEnter"
                @dragover.prevent="onDragOver"
                @dragleave.prevent="onDragLeave"
                @drop.prevent="onDrop"
            >
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/jpg,image/gif"
                    multiple
                    class="sr-only"
                    @change="onFileInputChange"
                />
                <i class="lab lab-fill-image rg-dropzone__icon"></i>
                <p class="rg-dropzone__title">{{ $t('message.reviews_generator_dropzone_title') }}</p>
                <p class="rg-dropzone__hint">{{ $t('message.reviews_generator_dropzone_hint') }}</p>
            </div>

            <div v-if="imageItems.length" class="rg-dialog__previews">
                <div class="relative" v-for="(item, index) in imageItems" :key="item.id">
                    <button
                        @click.stop="removeImage(index)"
                        type="button"
                        class="lab-fill-close text-xs w-5 h-5 leading-5 rounded-full shadow absolute -top-1 -right-1 text-danger bg-white z-10"
                    ></button>
                    <img :src="item.url" alt="review" class="rg-images__preview" />
                </div>
            </div>

            <div class="rg-dialog__footer">
                <span class="rg-dialog__count">
                    {{ $t('message.reviews_generator_images_selected', { count: imageItems.length }) }}
                </span>
                <div class="rg-dialog__footer-actions">
                    <button type="button" class="rg-btn rg-btn--ghost" @click="clearImages" :disabled="!imageItems.length">
                        {{ $t('button.clear') }}
                    </button>
                    <button type="button" class="rg-btn rg-btn--primary" @click="closeImageDialog">
                        {{ $t('button.done') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LoadingComponent from "../components/LoadingComponent";
import alertService from "../../../services/alertService";

let imageSeq = 0;

export default {
    name: "ReviewGeneratorComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: {
                isActive: false,
            },
            imageItems: [],
            showImageDialog: false,
            isDragging: false,
            dragDepth: 0,
            form: {
                product_id: null,
                quantity: 10,
            },
            errors: {},
            productSearch: {
                paginate: 0,
                page: 1,
                order_column: "id",
            },
        };
    },
    mounted() {
        this.loading.isActive = true;
        this.$store
            .dispatch("product/getSimpleProduct", this.productSearch)
            .finally(() => {
                this.loading.isActive = false;
            });
        window.addEventListener("paste", this.onPaste);
    },
    beforeUnmount() {
        window.removeEventListener("paste", this.onPaste);
        this.revokeAllUrls();
    },
    computed: {
        products() {
            return this.$store.getters["product/simpleList"] || [];
        },
        selectedProduct() {
            if (!this.form.product_id) {
                return null;
            }
            return this.products.find((p) => Number(p.id) === Number(this.form.product_id)) || null;
        },
    },
    methods: {
        openImageDialog() {
            this.showImageDialog = true;
            this.$nextTick(() => {
                this.$refs.imageDialog?.focus?.();
            });
        },
        closeImageDialog() {
            this.showImageDialog = false;
            this.isDragging = false;
            this.dragDepth = 0;
        },
        openFileBrowser() {
            this.$refs.fileInput?.click();
        },
        onFileInputChange(event) {
            const files = event.target?.files;
            if (files?.length) {
                this.addFiles(Array.from(files));
            }
            if (event.target) {
                event.target.value = "";
            }
        },
        onDragEnter() {
            this.dragDepth += 1;
            this.isDragging = true;
        },
        onDragOver() {
            this.isDragging = true;
        },
        onDragLeave() {
            this.dragDepth = Math.max(0, this.dragDepth - 1);
            if (this.dragDepth === 0) {
                this.isDragging = false;
            }
        },
        onDrop(event) {
            this.isDragging = false;
            this.dragDepth = 0;
            const files = Array.from(event.dataTransfer?.files || []);
            this.addFiles(files);
        },
        onPaste(event) {
            if (!this.showImageDialog) {
                return;
            }
            const items = Array.from(event.clipboardData?.items || []);
            const files = [];
            items.forEach((item) => {
                if (item.type && item.type.startsWith("image/")) {
                    const file = item.getAsFile();
                    if (file) {
                        files.push(file);
                    }
                }
            });
            if (files.length) {
                event.preventDefault();
                this.addFiles(files);
            }
        },
        isAcceptedImage(file) {
            if (!file || !file.type) {
                return false;
            }
            return /image\/(jpeg|jpg|png|webp|gif)/i.test(file.type);
        },
        addFiles(files) {
            const accepted = files.filter((file) => this.isAcceptedImage(file));
            if (!accepted.length) {
                alertService.error(this.$t("message.reviews_generator_invalid_image"));
                return;
            }
            accepted.forEach((file) => {
                this.imageItems.push({
                    id: `img-${Date.now()}-${imageSeq++}`,
                    file,
                    url: URL.createObjectURL(file),
                });
            });
        },
        removeImage(index) {
            const item = this.imageItems[index];
            if (item?.url) {
                URL.revokeObjectURL(item.url);
            }
            this.imageItems.splice(index, 1);
        },
        clearImages() {
            this.revokeAllUrls();
            this.imageItems = [];
        },
        revokeAllUrls() {
            this.imageItems.forEach((item) => {
                if (item?.url) {
                    URL.revokeObjectURL(item.url);
                }
            });
        },
        generate() {
            this.errors = {};

            if (!this.form.product_id) {
                this.errors.product_id = [this.$t("message.reviews_generator_product_required")];
                return;
            }

            const qty = Number(this.form.quantity);
            if (!qty || qty < 1 || qty > 50) {
                this.errors.quantity = [this.$t("message.reviews_generator_quantity_hint")];
                return;
            }

            const fd = new FormData();
            fd.append("product_id", this.form.product_id);
            fd.append("quantity", qty);

            this.imageItems.forEach((item) => {
                if (item?.file) {
                    fd.append("images[]", item.file);
                }
            });

            this.loading.isActive = true;
            this.$store
                .dispatch("review/generate", { form: fd })
                .then((res) => {
                    const count =
                        res.data?.meta?.generated ??
                        (Array.isArray(res.data?.data) ? res.data.data.length : qty);
                    alertService.success(
                        this.$t("message.reviews_generator_success", { count })
                    );
                    this.$router.push({
                        name: "admin.review.list",
                        query: { product_id: String(this.form.product_id) },
                    });
                })
                .catch((err) => {
                    this.errors = err.response?.data?.errors || {};
                    if (err.response?.data?.message && !Object.keys(this.errors).length) {
                        alertService.error(err.response.data.message);
                    }
                })
                .finally(() => {
                    this.loading.isActive = false;
                });
        },
    },
};
</script>

<style scoped>
.rg-page {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.rg-page__header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.rg-page__title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a2e;
    letter-spacing: -0.02em;
}

.rg-page__subtitle {
    margin: 0.35rem 0 0;
    font-size: 0.875rem;
    color: #6b7280;
    max-width: 36rem;
    line-height: 1.45;
}

.rg-card {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 1rem;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
}

.rg-section {
    max-width: 40rem;
}

.rg-section--wide {
    max-width: 52rem;
}

.rg-label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
}

.rg-label.required::after {
    content: " *";
    color: #ef4444;
}

.rg-help {
    display: block;
    margin-top: 0.4rem;
    font-size: 0.75rem;
    color: #9ca3af;
    line-height: 1.4;
}

.rg-qty {
    max-width: 8rem;
}

.rg-product {
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.75rem;
    background: #f8f9fc;
    border: 1px solid #eef0f5;
    border-radius: 0.75rem;
}

.rg-product__thumb {
    width: 4.5rem;
    height: 4.5rem;
    object-fit: cover;
    border-radius: 0.625rem;
    border: 1px solid #e5e7eb;
    background: #fff;
    flex-shrink: 0;
}

.rg-product__name {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #111827;
    line-height: 1.35;
}

.rg-product__meta {
    margin: 0.25rem 0 0;
    font-size: 0.75rem;
    color: #6b7280;
}

.rg-images-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
}

.rg-images {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.rg-images__preview {
    width: 5.5rem;
    height: 5.5rem;
    object-fit: cover;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    display: block;
}

.rg-empty-images {
    margin: 0;
    padding: 1rem;
    border: 1px dashed #d1d5db;
    border-radius: 0.75rem;
    color: #9ca3af;
    font-size: 0.8125rem;
    background: #fafafa;
}

.rg-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding-top: 0.25rem;
}

.rg-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1rem;
    border-radius: 0.625rem;
    font-size: 0.875rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    text-decoration: none;
    line-height: 1.2;
}

.rg-btn--primary {
    background: #4f46e5;
    color: #fff;
}

.rg-btn--primary:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.rg-btn--muted {
    background: #4b5563;
    color: #fff;
}

.rg-btn--ghost {
    background: #eff0f6;
    color: #374151;
}

.rg-btn--ghost:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.rg-dialog {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.rg-dialog__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
}

.rg-dialog__panel {
    position: relative;
    width: min(40rem, 100%);
    max-height: min(90vh, 44rem);
    overflow: auto;
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
    outline: none;
    display: flex;
    flex-direction: column;
}

.rg-dialog__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.15rem;
    border-bottom: 1px solid #eef0f5;
}

.rg-dialog__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #111827;
}

.rg-dialog__close {
    width: 2rem;
    height: 2rem;
    border: none;
    border-radius: 9999px;
    background: #f3f4f6;
    color: #4b5563;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.rg-dropzone {
    margin: 1rem 1.15rem 0;
    padding: 1.75rem 1rem;
    border: 2px dashed #c7cbd6;
    border-radius: 0.9rem;
    background: #f8f9fc;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.15s ease, background-color 0.15s ease;
}

.rg-dropzone:hover,
.rg-dropzone.is-dragging {
    border-color: #4f46e5;
    background: #eef2ff;
}

.rg-dropzone__icon {
    font-size: 1.75rem;
    color: #6b7280;
}

.rg-dropzone__title {
    margin: 0.65rem 0 0.25rem;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #111827;
}

.rg-dropzone__hint {
    margin: 0;
    font-size: 0.75rem;
    color: #6b7280;
    line-height: 1.45;
}

.rg-dialog__previews {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding: 1rem 1.15rem 0;
}

.rg-dialog__footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 1rem 1.15rem 1.15rem;
    margin-top: 0.5rem;
    border-top: 1px solid #eef0f5;
}

.rg-dialog__count {
    font-size: 0.8125rem;
    color: #6b7280;
    font-weight: 600;
}

.rg-dialog__footer-actions {
    display: flex;
    gap: 0.5rem;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>
