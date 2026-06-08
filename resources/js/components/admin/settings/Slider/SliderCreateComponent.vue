<template>
    <LoadingComponent :props="loading" />
    <SmModalCreateComponent :props="addButton" />

    <div id="modal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">{{ $t("menu.sliders") }}</h3>
                <button class="modal-close fa-solid fa-xmark text-xl text-slate-400 hover:text-red-500"
                    @click="reset"></button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="save">
                    <div class="form-row">
                        <div class="form-col-12">
                            <label for="name" class="db-field-title required">{{
                                $t("label.title")
                                }}</label>
                            <input v-model="props.form.title" v-bind:class="errors.title ? 'invalid' : ''" type="text"
                                id="name" class="db-field-control" />
                            <small class="db-field-alert" v-if="errors.title">{{
                                errors.title[0]
                                }}</small>
                        </div>

                        <!-- Link Type Selection -->
                        <div class="form-col-12 sm:form-col-4">
                            <label class="db-field-title">{{ $t("label.link_type") }}</label>
                            <vue-select class="db-field-control f-b-custom-select" v-model="linkType" 
                                :options="linkTypeOptions" label-by="name" value-by="id"
                                :closeOnSelect="true" :searchable="false" :clearOnClose="false" />
                        </div>

                        <!-- Dynamic Dropdown based on Link Type -->
                        <div class="form-col-12 sm:form-col-8" v-if="linkType !== 'custom'">
                            <label class="db-field-title">Select {{ linkType.charAt(0).toUpperCase() + linkType.slice(1) }}</label>
                            
                            <!-- Product Dropdown -->
                            <vue-select v-if="linkType === 'product'" class="db-field-control f-b-custom-select"
                                v-model="selectedId" :options="products" label-by="name" value-by="id"
                                :closeOnSelect="true" :searchable="true" placeholder="Select Product"
                                @update:modelValue="handleSelectionChange" />

                            <!-- Category Dropdown -->
                            <vue-select v-if="linkType === 'category'" class="db-field-control f-b-custom-select"
                                v-model="selectedId" :options="categories" label-by="name" value-by="id"
                                :closeOnSelect="true" :searchable="true" placeholder="Select Category"
                                @update:modelValue="handleSelectionChange" />

                            <!-- Brand Dropdown -->
                            <vue-select v-if="linkType === 'brand'" class="db-field-control f-b-custom-select"
                                v-model="selectedId" :options="brands" label-by="name" value-by="id"
                                :closeOnSelect="true" :searchable="true" placeholder="Select Brand"
                                @update:modelValue="handleSelectionChange" />
                        </div>

                        <div class="form-col-12" :class="linkType === 'custom' ? 'sm:form-col-8' : 'sm:form-col-12'">
                            <label for="link" class="db-field-title">{{ $t("label.link") }}</label>
                            <input v-model="props.form.link" v-bind:class="errors.link ? 'invalid' : ''" type="text"
                                id="link" class="db-field-control" placeholder="URL will be generated here" />
                            <small class="db-field-alert" v-if="errors.link">{{ errors.link[0] }}</small>
                        </div>


                        <div class="form-col-12 sm:form-col-12">
                            <label for="image" class="db-field-title required">
                                {{ $t("label.image") }} (1689px,600px)
                            </label>
                            <div class="flex items-center gap-4 mb-3">
                                <img :src="imagePreview || '/images/default/no-image.png'" 
                                     class="w-32 h-16 object-cover rounded border border-slate-200 bg-slate-50" />
                                <div class="flex-1 flex items-center gap-2">
                                    <input @change="changeImage" v-bind:class="errors.image ? 'invalid' : ''" id="image"
                                        type="file" class="db-field-control flex-1" ref="imageProperty"
                                        accept="image/png, image/jpeg, image/jpg">
                                    
                                    <ImageUploadSourceButtons @selected="handleMediaSelected" @loading="loading.isActive = $event" />
                                </div>
                            </div>
                            <small class="db-field-alert" v-if="errors.image">{{ errors.image[0] }}</small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label class="db-field-title required" for="active">{{ $t("label.status") }}</label>
                            <div class="db-field-radio-group">
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input :value="enums.statusEnum.ACTIVE" v-model="props.form.status" id="active"
                                            type="radio" class="custom-radio-field" />
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="active" class="db-field-label">{{ $t("label.active") }}</label>
                                </div>
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input :value="enums.statusEnum.INACTIVE" v-model="props.form.status"
                                            type="radio" id="inactive" class="custom-radio-field" />
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="inactive" class="db-field-label">{{ $t("label.inactive") }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-col-12">
                            <label for="description" class="db-field-title">{{
                                $t("label.description")
                                }}</label>
                            <textarea v-model="props.form.description" v-bind:class="errors.description ? 'invalid' : ''
                                " id="description" class="db-field-control"></textarea>
                            <small class="db-field-alert" v-if="errors.description">{{ errors.description[0] }}</small>
                        </div>

                        <div class="form-col-12">
                            <div class="modal-btns">
                                <button type="button" class="modal-btn-outline modal-close" @click="reset">
                                    <i class="lab lab-fill-close-circle"></i>
                                    <span>{{ $t("button.close") }}</span>
                                </button>

                                <button type="submit" class="db-btn py-2 text-white bg-primary">
                                    <i class="lab lab-fill-save"></i>
                                    <span>{{ $t("button.save") }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<script>
import SmModalCreateComponent from "../../components/buttons/SmModalCreateComponent";
import LoadingComponent from "../../components/LoadingComponent";
import ImageUploadSourceButtons from "../../media/ImageUploadSourceButtons";
import statusEnum from "../../../../enums/modules/statusEnum";
import alertService from "../../../../services/alertService";
import appService from "../../../../services/appService";
import { assetToFile } from "../../../../services/imageUploadService";

export default {
    name: "SliderCreateComponent",
    components: { SmModalCreateComponent, LoadingComponent, ImageUploadSourceButtons },
    props: ["props"],
    data() {
        return {
            loading: {
                isActive: false,
            },
            enums: {
                statusEnum: statusEnum,
                statusEnumArray: {
                    [statusEnum.ACTIVE]: this.$t("label.active"),
                    [statusEnum.INACTIVE]: this.$t("label.inactive"),
                },
            },
            image: "",
            imagePreview: "",
            errors: {},
            products: [],
            categories: [],
            brands: [],
            linkType: 'custom',
            selectedId: null,
            linkTypeOptions: [
                { id: 'custom', name: 'Custom URL' },
                { id: 'product', name: 'Product' },
                { id: 'category', name: 'Category' },
                { id: 'brand', name: 'Brand' }
            ]
        };
    },
    computed: {
        addButton: function () {
            return { title: this.$t("button.add_slider") }
        }
    },
    watch: {
        'props.form.link': function(newLink) {
            if (!this.selectedId) {
                this.detectLinkType(newLink);
            }
        }
    },
    mounted() {
        this.fetchAllData();
    },
    methods: {
        async fetchAllData() {
            this.loading.isActive = true;
            try {
                const [prodRes, catRes, brandRes] = await Promise.all([
                    this.$store.dispatch('product/lists', { order_column: 'id', order_type: 'desc' }),
                    this.$store.dispatch('productCategory/lists', { order_column: 'id', order_type: 'desc' }),
                    this.$store.dispatch('productBrand/lists', { order_column: 'id', order_type: 'desc' })
                ]);
                this.products = prodRes.data.data;
                this.categories = catRes.data.data;
                this.brands = brandRes.data.data;
                
                // Initial detect for edit mode
                if (this.props.form.link) {
                    this.detectLinkType(this.props.form.link);
                }
            } finally {
                this.loading.isActive = false;
            }
        },
        detectLinkType(link) {
            if (!link) return;
            if (link.startsWith('/product/')) {
                this.linkType = 'product';
                const slug = link.replace('/product/', '');
                const item = this.products.find(p => p.slug === slug);
                if (item) this.selectedId = item.id;
            } else if (link.includes('?category=')) {
                this.linkType = 'category';
                const slug = link.split('?category=')[1];
                const item = this.categories.find(p => p.slug === slug);
                if (item) this.selectedId = item.id;
            } else if (link.includes('?brand=')) {
                this.linkType = 'brand';
                const slug = link.split('?brand=')[1];
                const item = this.brands.find(p => p.slug === slug);
                if (item) this.selectedId = item.id;
            } else {
                this.linkType = 'custom';
            }
        },
        handleSelectionChange(id) {
            if (!id) {
                this.props.form.link = "";
                return;
            }
            let item = null;
            if (this.linkType === 'product') {
                item = this.products.find(p => p.id === id);
                if (item) this.props.form.link = `/product/${item.slug}`;
            } else if (this.linkType === 'category') {
                item = this.categories.find(p => p.id === id);
                if (item) this.props.form.link = `/product?category=${item.slug}`;
            } else if (this.linkType === 'brand') {
                item = this.brands.find(p => p.id === id);
                if (item) this.props.form.link = `/product?brand=${item.slug}`;
            }
        },
        changeImage: function (e) {
            this.image = e.target.files[0];
            if (this.image) {
                if (this.image.size > 2 * 1024 * 1024) {
                    alertService.error(this.$t('message.image_size_too_large') + " (Max 2MB)");
                    this.image = "";
                    this.imagePreview = "";
                    this.$refs.imageProperty.value = null;
                } else {
                    this.imagePreview = URL.createObjectURL(this.image);
                }
            }
        },
        async handleMediaSelected(asset) {
            const items = Array.isArray(asset) ? asset : [asset];
            if (!items.length) {
                return;
            }

            try {
                this.loading.isActive = true;
                const file = await assetToFile(items[0]);
                this.image = file;
                this.imagePreview = items[0].url;
                if (this.$refs.imageProperty) {
                    this.$refs.imageProperty.value = null;
                }
            } catch (error) {
                alertService.error("Failed to load image");
            } finally {
                this.loading.isActive = false;
            }
        },
        reset: function () {
            appService.modalHide();
            this.$store.dispatch("slider/reset").then().catch();
            this.errors = {};
            this.selectedId = null;
            this.linkType = 'custom';
            this.$props.props.form = {
                title: "",
                link: "",
                description: "",
                status: statusEnum.ACTIVE,
            };
            if (this.image) {
                this.image = "";
                this.imagePreview = "";
                this.$refs.imageProperty.value = null;
            }
        },
        save: function () {
            try {
                const fd = new FormData();
                fd.append("title", this.props.form.title);
                fd.append("link", this.props.form.link);
                fd.append("status", this.props.form.status);
                fd.append("description", this.props.form.description);
                if (this.image) {
                    fd.append("image", this.image);
                }
                const tempId = this.$store.getters["slider/temp"].temp_id;
                this.loading.isActive = true;
                this.$store.dispatch("slider/save", { form: fd, search: this.props.search })
                    .then((res) => {
                        appService.modalHide();
                        this.loading.isActive = false;
                        alertService.successFlip(tempId === null ? 0 : 1, this.$t("menu.sliders"));
                        this.reset();
                    })
                    .catch((err) => {
                        this.loading.isActive = false;
                        this.errors = err.response.data.errors;
                    });
            } catch (err) {
                this.loading.isActive = false;
                alertService.error(err);
            }
        },
    },
};
</script>