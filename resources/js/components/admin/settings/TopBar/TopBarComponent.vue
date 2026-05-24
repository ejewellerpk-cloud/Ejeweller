<template>
    <LoadingComponent :props="loading" />

    <div id="top-bar" class="db-card db-tab-div active">
        <div class="db-card-header">
            <h3 class="db-card-title">Top Bar Settings</h3>
        </div>
        <div class="db-card-body">
            <form @submit.prevent="save" class="w-full d-block">
                <div class="form-row">
                    
                    <div class="form-col-12 sm:form-col-6">
                        <label for="top_bar_status" class="db-field-title">Status</label>
                        <div class="db-field-radio-group">
                            <div class="db-field-radio">
                                <div class="custom-radio">
                                    <input type="radio" v-model="form.top_bar_status" id="active" value="active" class="custom-radio-field">
                                    <span class="custom-radio-span"></span>
                                </div>
                                <label for="active" class="db-field-label">Active</label>
                            </div>
                            <div class="db-field-radio">
                                <div class="custom-radio">
                                    <input type="radio" v-model="form.top_bar_status" id="inactive" value="inactive" class="custom-radio-field">
                                    <span class="custom-radio-span"></span>
                                </div>
                                <label for="inactive" class="db-field-label">Inactive</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-col-12">
                        <div class="flex items-center justify-between mb-2">
                            <label class="db-field-title mb-0">Promotional Texts / Slider Titles</label>
                            <button type="button" @click="addTitle" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-primary/10 text-primary border border-primary/20 hover:bg-primary/20 transition-all flex items-center gap-1">
                                <i class="fa-solid fa-plus text-[10px]"></i> Add Title
                            </button>
                        </div>
                        <div class="flex flex-col gap-3">
                            <div v-for="(title, index) in titles" :key="index" class="flex items-center gap-2">
                                <input v-model="titles[index]" type="text" class="db-field-control" :placeholder="'E.g. Promotional Title ' + (index + 1)">
                                <button v-if="titles.length > 1" type="button" @click="removeTitle(index)" class="flex-shrink-0 w-10 h-10 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 flex items-center justify-center transition-colors">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label for="top_bar_link" class="db-field-title">Link (Optional)</label>
                        <input v-model="form.top_bar_link" id="top_bar_link" type="text" class="db-field-control" placeholder="E.g. /promotions">
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label for="top_bar_bg_color" class="db-field-title">Background Color</label>
                        <input v-model="form.top_bar_bg_color" id="top_bar_bg_color" type="color" class="db-field-control cursor-pointer h-10 p-1">
                    </div>

                    <div class="form-col-12 sm:form-col-6">
                        <label for="top_bar_text_color" class="db-field-title">Text Color</label>
                        <input v-model="form.top_bar_text_color" id="top_bar_text_color" type="color" class="db-field-control cursor-pointer h-10 p-1">
                    </div>

                    <div class="form-col-12 mt-4">
                        <button type="submit" class="db-btn text-white bg-primary">
                            <i class="lab lab-fill-save"></i>
                            <span>{{ $t("button.save") }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import LoadingComponent from "../../components/LoadingComponent";
import alertService from "../../../../services/alertService";

export default {
    name: "TopBarComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: {
                isActive: false,
            },
            form: {
                top_bar_status: "inactive",
                top_bar_text: "",
                top_bar_link: "",
                top_bar_bg_color: "#ff5c00",
                top_bar_text_color: "#ffffff",
            },
            titles: [""],
            errors: {},
        };
    },
    mounted() {
        this.list();
    },
    methods: {
        list: function () {
            this.loading.isActive = true;
            this.$store
                .dispatch("topBar/lists")
                .then((res) => {
                    const data = res.data.data;
                    if(data.top_bar_status) this.form.top_bar_status = data.top_bar_status;
                    if(data.top_bar_text) {
                        this.form.top_bar_text = data.top_bar_text;
                        try {
                            const parsed = JSON.parse(data.top_bar_text);
                            if (Array.isArray(parsed)) {
                                this.titles = parsed;
                            } else {
                                this.titles = [data.top_bar_text];
                            }
                        } catch (e) {
                            this.titles = [data.top_bar_text];
                        }
                    } else {
                        this.titles = [""];
                    }
                    if(data.top_bar_link) this.form.top_bar_link = data.top_bar_link;
                    if(data.top_bar_bg_color) this.form.top_bar_bg_color = data.top_bar_bg_color;
                    if(data.top_bar_text_color) this.form.top_bar_text_color = data.top_bar_text_color;
                    this.loading.isActive = false;
                })
                .catch((err) => {
                    this.loading.isActive = false;
                });
        },
        save: function () {
            try {
                this.loading.isActive = true;
                
                // Filter out empty titles
                const filteredTitles = this.titles.filter(t => t && t.trim() !== '');
                if (filteredTitles.length === 0) {
                    filteredTitles.push('Welcome to our store!');
                }
                this.form.top_bar_text = JSON.stringify(filteredTitles);

                this.$store
                    .dispatch("topBar/save", this.form)
                    .then((res) => {
                        this.loading.isActive = false;
                        alertService.successFlip(1, "Top Bar Settings");
                        this.errors = {};
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
        addTitle: function () {
            this.titles.push('');
        },
        removeTitle: function (index) {
            this.titles.splice(index, 1);
        },
    },
};
</script>
