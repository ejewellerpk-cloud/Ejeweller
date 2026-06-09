<template>
    <LoadingComponent :props="loading" />
    <div class="col-12">
        <div class="db-card">
            <div class="db-card-header border-none">
                <h3 class="db-card-title">{{ isEditing ? $t('label.edit_review') : $t('button.add_review') }}</h3>
                <router-link :to="{ name: 'admin.review.list' }" class="db-btn py-2 text-white bg-gray-600">
                    <i class="lab lab-line-undo lab-font-size-16"></i>
                    <span>{{ $t('button.cancel') }}</span>
                </router-link>
            </div>

            <div class="db-card-body">
                <form @submit.prevent="save" class="w-full">
                    <div class="row">
                        <div class="col-12 sm:col-6 xl:col-4 mb-4">
                            <label for="product_id" class="db-field-title required">{{ $t('label.product') }}</label>
                            <vue-select class="db-field-control f-b-custom-select" id="product_id"
                                v-model="form.product_id"
                                :options="products"
                                label-by="name"
                                value-by="id"
                                :closeOnSelect="true"
                                :searchable="true"
                                :clearOnClose="true"
                                placeholder="--"
                                search-placeholder="--"
                                :class="errors.product_id ? 'invalid' : ''" />
                            <small class="db-field-alert" v-if="errors.product_id">{{ errors.product_id[0] }}</small>
                        </div>

                        <div class="col-12 sm:col-6 xl:col-4 mb-4">
                            <label for="user_id" class="db-field-title required">{{ $t('label.customer') }}</label>
                            <vue-select class="db-field-control f-b-custom-select" id="user_id"
                                v-model="form.user_id"
                                :options="customers"
                                label-by="name"
                                value-by="id"
                                :closeOnSelect="true"
                                :searchable="true"
                                :clearOnClose="true"
                                placeholder="--"
                                search-placeholder="--"
                                :class="errors.user_id ? 'invalid' : ''" />
                            <small class="db-field-alert" v-if="errors.user_id">{{ errors.user_id[0] }}</small>
                            <small class="text-xs text-gray-500 mt-1 block">{{ $t('message.review_customer_hint') }}</small>
                        </div>

                        <div class="col-12 mb-4">
                            <label class="db-field-title required">{{ $t('label.rating') }} ({{ activeRate }})</label>
                            <nav class="flex items-center gap-2 mt-2">
                                <button v-for="rate in 5" :key="rate" type="button" @click="activeRate = rate"
                                    :class="{ '!text-[#F6A609]': activeRate >= rate }"
                                    class="lab-fill-star text-3xl text-[#D9DBE9]"></button>
                            </nav>
                            <small class="db-field-alert" v-if="errors.star">{{ errors.star[0] }}</small>
                        </div>

                        <div class="col-12 mb-4">
                            <label for="review" class="db-field-title required">{{ $t('label.review_details') }}</label>
                            <textarea id="review" v-model="form.review" rows="5"
                                class="db-field-control"
                                :class="errors.review ? 'invalid' : ''"
                                :placeholder="$t('label.your_review')"></textarea>
                            <small class="db-field-alert" v-if="errors.review">{{ errors.review[0] }}</small>
                        </div>

                        <div class="col-12 mb-6">
                            <label class="db-field-title">{{ $t('label.upload_images') }}</label>
                            <p class="text-xs text-gray-500 mb-3">{{ $t('message.review_images_hint') }}</p>
                            <div class="flex flex-wrap gap-3">
                                <div class="relative" v-for="(_, index) in 5" :key="index">
                                    <button v-if="imageUrl[index]" @click="removeImage(index)" type="button"
                                        class="lab-fill-close text-xs w-5 h-5 leading-5 rounded-full shadow absolute -top-1 -right-1 text-danger bg-white z-10"></button>
                                    <img v-if="imageUrl[index]" :src="imageUrl[index]" alt="review"
                                        class="rounded-lg w-24 h-24 object-cover border border-gray-200" />
                                    <label v-else
                                        class="relative rounded-lg w-24 h-24 flex flex-col items-center justify-center gap-1 cursor-pointer bg-[#EFF0F6] border border-dashed border-gray-300">
                                        <input @change="handleImageUpload($event, index)" type="file" accept="image/*"
                                            class="absolute inset-0 opacity-0 cursor-pointer">
                                        <i class="lab-fill-image text-xl text-text"></i>
                                        <span class="text-[10px] font-medium capitalize text-text">{{ $t('label.add_image') }}</span>
                                    </label>
                                </div>
                            </div>
                            <small class="db-field-alert" v-if="errors['images.0']">{{ errors['images.0'][0] }}</small>
                        </div>

                        <div class="col-12">
                            <div class="flex flex-wrap gap-3">
                                <button type="submit" class="db-btn py-2 text-white bg-primary">
                                    <i class="lab lab-fill-save"></i>
                                    <span>{{ $t('button.save') }}</span>
                                </button>
                                <router-link :to="{ name: 'admin.review.list' }" class="db-btn py-2 text-white bg-gray-600">
                                    <i class="lab lab-fill-close-circle"></i>
                                    <span>{{ $t('button.cancel') }}</span>
                                </router-link>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import LoadingComponent from "../components/LoadingComponent";
import alertService from "../../../services/alertService";
import statusEnum from "../../../enums/modules/statusEnum";

export default {
    name: "ReviewCreateComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: {
                isActive: false,
            },
            activeRate: 5,
            imageUrl: Array(5).fill(null),
            images: {},
            form: {
                product_id: null,
                user_id: null,
                review: '',
            },
            errors: {},
            reviewId: null,
            productSearch: {
                paginate: 0,
                page: 1,
                order_column: 'id',
            },
        };
    },
    mounted() {
        this.loading.isActive = true;
        const tasks = [
            this.$store.dispatch('product/getSimpleProduct', this.productSearch),
            this.$store.dispatch('user/lists', {
                order_column: 'id',
                order_type: 'asc',
                status: statusEnum.ACTIVE,
            }),
        ];

        if (this.isEditing) {
            this.reviewId = this.$route.params.id;
            tasks.push(this.$store.dispatch('review/show', this.reviewId));
        }

        Promise.all(tasks).then((results) => {
            if (this.isEditing) {
                const review = results[results.length - 1]?.data?.data || this.$store.getters['review/show'];
                this.form.product_id = review.product_id;
                this.form.user_id = review.user_id;
                this.form.review = review.review;
                this.activeRate = Number(review.star) || 5;
                (review.images || []).forEach((image, index) => {
                    if (index < 5) {
                        this.imageUrl[index] = image;
                    }
                });
            }
        }).finally(() => {
            this.loading.isActive = false;
        });
    },
    computed: {
        isEditing() {
            return this.$route.name === 'admin.review.edit';
        },
        products() {
            return this.$store.getters['product/simpleList'];
        },
        customers() {
            return this.$store.getters['user/lists'];
        },
    },
    methods: {
        handleImageUpload(event, index) {
            const input = event.target;
            if (!input.files || !input.files[0]) {
                return;
            }
            const file = input.files[0];
            this.imageUrl[index] = URL.createObjectURL(file);
            this.images[index] = file;
        },
        removeImage(index) {
            if (this.imageUrl[index]) {
                URL.revokeObjectURL(this.imageUrl[index]);
            }
            this.imageUrl[index] = null;
            this.images[index] = null;
        },
        save() {
            const fd = new FormData();
            fd.append('product_id', this.form.product_id ?? '');
            fd.append('user_id', this.form.user_id ?? '');
            fd.append('star', this.activeRate);
            fd.append('review', this.form.review);

            Object.keys(this.images).forEach((key) => {
                if (this.images[key]) {
                    fd.append('images[]', this.images[key]);
                }
            });

            this.loading.isActive = true;
            const action = this.isEditing
                ? this.$store.dispatch('review/update', { id: this.reviewId, form: fd })
                : this.$store.dispatch('review/save', { form: fd });

            action.then((res) => {
                this.loading.isActive = false;
                alertService.successFlip(this.isEditing ? 1 : 0, this.$t('menu.reviews'));
                this.$router.push({
                    name: 'admin.review.show',
                    params: { id: res.data.data.id },
                });
            }).catch((err) => {
                this.loading.isActive = false;
                this.errors = err.response?.data?.errors || {};
                if (err.response?.data?.message && !Object.keys(this.errors).length) {
                    alertService.error(err.response.data.message);
                }
            });
        },
    },
    beforeUnmount() {
        this.imageUrl.forEach((url) => {
            if (url) {
                URL.revokeObjectURL(url);
            }
        });
    },
};
</script>
