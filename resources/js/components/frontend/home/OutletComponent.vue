<template>
    <div ref="lazySection" class="relative min-h-[50px]">
        <LoadingComponent v-if="loading.isActive" :props="loading" :isFullScreen="false" />
        <section v-if="outlets.length > 0" class="mb-10 sm:mb-20">
            <div class="container">
                <h2 class="capitalize text-2xl sm:text-4xl font-bold mb-8">
                    {{ $t('label.our_outlets') }}
                </h2>
                <Swiper dir="ltr" :speed="1000" :loop="true" :navigation="true" :modules="modules" class="navigate-swiper" :breakpoints="breakpoints">
                    <SwiperSlide v-for="outlet in outlets" :key="outlet.id">
                        <div class="w-full h-full p-6 rounded-2xl shadow-sm border border-slate-100 bg-white group hover:border-primary transition-all duration-300">
                            <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-primary transition-all duration-300">
                                <i class="fa-solid fa-store text-primary group-hover:text-white text-xl transition-all duration-300"></i>
                            </div>
                            <h3 class="text-lg font-bold text-heading mb-3">{{ outlet.name }}</h3>
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-location-dot text-slate-400 mt-1 text-sm"></i>
                                    <p class="text-sm text-slate-500 leading-relaxed">{{ outlet.address }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid fa-phone text-slate-400 text-sm"></i>
                                    <p class="text-sm font-medium text-slate-600">{{ outlet.phone }}</p>
                                </div>
                                <div v-if="outlet.email" class="flex items-center gap-3">
                                    <i class="fa-solid fa-envelope text-slate-400 text-sm"></i>
                                    <p class="text-sm text-slate-500 truncate">{{ outlet.email }}</p>
                                </div>
                            </div>
                        </div>
                    </SwiperSlide>
                </Swiper>
            </div>
        </section>
    </div>
</template>

<script>
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import statusEnum from "../../../enums/modules/statusEnum";
import LoadingComponent from "../components/LoadingComponent";

export default {
    name: "OutletComponent",
    components: {
        Swiper, SwiperSlide,
        LoadingComponent
    },
    setup() {
        return {
            modules: [Navigation, Pagination, Autoplay],
        }
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            breakpoints: {
                0: { slidesPerView: 1.1, spaceBetween: 16 },
                640: { slidesPerView: 2, spaceBetween: 24 },
                768: { slidesPerView: 3, spaceBetween: 24 },
                1024: { slidesPerView: 3, spaceBetween: 24 }
            },
        }
    },
    computed: {
        outlets: function () {
            return this.$store.getters["frontendOutlet/lists"];
        },
    },
    mounted() {
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                this.fetchData();
                observer.disconnect();
            }
        }, { rootMargin: '300px' });
        
        if (this.$refs.lazySection) {
            observer.observe(this.$refs.lazySection);
        } else {
            this.fetchData();
        }
    },
    methods: {
        fetchData() {
            if (this.outlets.length > 0) return;

            this.loading.isActive = true;
            this.$store.dispatch("frontendOutlet/lists", {
                paginate: 0,
                order_column: "id",
                order_type: "asc",
                status: statusEnum.ACTIVE,
            }).then(res => {
                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
            });
        }
    }
}
</script>
