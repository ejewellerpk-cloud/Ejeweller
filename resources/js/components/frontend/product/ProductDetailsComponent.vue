<template>
    <LoadingComponent v-if="loading.isActive" :props="loading" skeleton="product-detail" />
    <section v-else class="mb-12">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <CategoryBreadcrumbComponent :categories="categories" />
                </div>

                <div v-if="combinedMedia.length" class="col-12 sm:col-6 lg:col-5 gallery-swiper-container relative">
                    <!-- Heart Screen Overlay Animation -->
                    <div v-if="animatingWishlist" class="absolute inset-0 flex items-center justify-center bg-black/10 z-30 pointer-events-none rounded-2xl animate-fade-overlay">
                        <div class="w-20 h-20 rounded-full bg-white/95 flex items-center justify-center shadow-2xl animate-heart-burst">
                            <i class="lab-fill-heart text-primary text-4xl animate-heart-pulse"></i>
                        </div>
                    </div>

                    <!-- SAVE % tag overlaid on top-left of image/slider -->
                    <span v-if="detailPrices.onSale" 
                        class="absolute top-4 left-4 z-20 bg-primary text-white text-[11px] sm:text-xs font-extrabold px-3 py-1.5 rounded-full shadow-[0_4px_12px_rgba(255,92,0,0.25)] flex items-center gap-1 animate-pulse">
                        <i class="fa-solid fa-tags text-[10px]"></i>
                        SAVE {{ detailPrices.percent }}%
                    </span>

                    <!-- Wishlist Button Overlay -->
                    <button type="button" @click="wishlist()"
                        class="w-10 h-10 rounded-full shadow-lg absolute top-4 right-16 z-20 bg-white hover:scale-105 active:scale-90 transition-all duration-300 flex items-center justify-center border border-gray-100">
                        <i :class="isWishlisted(product) ? 'lab-fill-heart text-primary animate-heart-pulse' : 'lab-line-heart text-secondary'" class="text-xl mt-0.5"></i>
                    </button>

                    <!-- Share Button Overlay -->
                    <button type="button" @click="shareProduct"
                        class="w-10 h-10 rounded-full shadow-lg absolute top-4 right-4 z-20 bg-white text-secondary hover:text-primary hover:scale-105 active:scale-95 transition-all duration-300 flex items-center justify-center border border-gray-100">
                        <i class="fa-solid fa-share-nodes text-base"></i>
                    </button>

                    <Swiper dir="ltr"
                        :key="'gallery-main-' + props.search.slug"
                        v-bind="gallerySwiperProps"
                        :spaceBetween="10"
                        :navigation="true"
                        :pagination="galleryPaginationConfig"
                        :thumbs="galleryThumbsConfig"
                        :modules="modules"
                        :loop="combinedMedia.length > 2"
                        class="gallery-swiper mb-4"
                        @swiper="setMainSwiper"
                        @slideChange="onMainGallerySlideChange"
                        @sliderFirstMove="onGallerySliderDrag"
                        @touchEnd="onGallerySliderTouchEnd"
                        @click="onGallerySwiperClick">
                        <SwiperSlide v-for="(media, index) in combinedMedia" :key="'media-' + index" class="w-full flex items-center justify-center bg-black rounded-2xl overflow-hidden aspect-square" style="aspect-ratio: 1/1;">
                            <template v-if="media.type === 'image'">
                                <div class="w-full h-full relative overflow-hidden flex items-center justify-center select-none cursor-pointer product-gallery-slide">
                                    <img :src="media.url" alt="product"
                                        :loading="index === 0 ? 'eager' : 'lazy'"
                                        :fetchpriority="index === 0 ? 'high' : 'auto'"
                                        decoding="async"
                                        draggable="false"
                                        @error="$event.target.src=$store.getters['frontendSetting/lists'].theme_logo; $event.target.classList.remove('object-cover'); $event.target.classList.add('object-contain', 'bg-white', 'p-8')"
                                        class="w-full h-full object-cover transition-transform duration-300 ease-out origin-center pointer-events-none" />
                                </div>
                            </template>
                            <template v-else-if="media.type === 'video'">
                                <iframe v-if="isEmbedVideo(media)"
                                    :src="formatVideoLink(media.data)" class="w-full h-full pointer-events-none" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                                <div v-else class="relative w-full h-full bg-black">
                                    <img
                                        :src="getVideoPoster(media)"
                                        alt="video preview"
                                        class="w-full h-full object-cover absolute inset-0"
                                        :class="mainSwiperActiveIndex === index ? 'opacity-0 pointer-events-none' : 'opacity-100'"
                                    />
                                    <video
                                        v-if="mainSwiperActiveIndex === index"
                                        :src="media.data.link"
                                        :poster="getVideoPoster(media)"
                                        autoplay
                                        muted
                                        loop
                                        playsinline
                                        webkit-playsinline
                                        preload="auto"
                                        class="w-full h-full object-cover relative z-[1] pointer-events-none"
                                    ></video>
                                </div>
                            </template>
                        </SwiperSlide>
                    </Swiper>

                    <Swiper v-if="combinedMedia.length > 1"
                        :key="'gallery-thumbs-' + props.search.slug"
                        dir="ltr"
                        @swiper="setThumbsSwiper"
                        :spaceBetween="12"
                        :slidesPerView="4"
                        :freeMode="true"
                        :watchSlidesProgress="true" :modules="modules" class="thumb-swiper hidden sm:block">
                        <SwiperSlide v-for="(media, index) in combinedMedia" :key="'thumb-media-' + index"
                            @mouseover="thumbsSwiper ? thumbsSwiper.slideTo(index) : null"
                            class="w-full cursor-pointer rounded-lg border border-gray-200 transition-all duration-500 bg-black flex items-center justify-center aspect-square relative" style="aspect-ratio: 1/1;">
                            <template v-if="media.type === 'image'">
                                <img class="w-full h-full rounded-lg border-2 border-gray-200 transition-all duration-500 object-cover" loading="lazy"
                                    @error="$event.target.src=$store.getters['frontendSetting/lists'].theme_logo; $event.target.classList.remove('object-cover'); $event.target.classList.add('object-contain', 'bg-white', 'p-2')"
                                    :src="media.url" alt="gallery" />
                            </template>
                            <template v-else-if="media.type === 'video'">
                                <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-45 rounded-lg z-10">
                                    <i class="fa-solid fa-play text-white text-base"></i>
                                </div>
                                <img class="w-full h-full rounded-lg border-2 border-gray-200 object-cover" loading="lazy"
                                    @error="onVideoPosterImageError($event)"
                                    :src="getVideoPoster(media)" alt="video thumbnail" />
                            </template>
                        </SwiperSlide>
                    </Swiper>
                </div>

                <div v-else class="col-12 sm:col-6 lg:col-5 relative">
                    <!-- Heart Screen Overlay Animation -->
                    <div v-if="animatingWishlist" class="absolute inset-0 flex items-center justify-center bg-black/10 z-30 pointer-events-none rounded-2xl animate-fade-overlay">
                        <div class="w-20 h-20 rounded-full bg-white/95 flex items-center justify-center shadow-2xl animate-heart-burst">
                            <i class="lab-fill-heart text-primary text-4xl animate-heart-pulse"></i>
                        </div>
                    </div>

                    <!-- SAVE % tag overlaid on top-left of image/slider -->
                    <span v-if="detailPrices.onSale" 
                        class="absolute top-4 left-4 z-20 bg-primary text-white text-[11px] sm:text-xs font-extrabold px-3 py-1.5 rounded-full shadow-[0_4px_12px_rgba(255,92,0,0.25)] flex items-center gap-1 animate-pulse">
                        <i class="fa-solid fa-tags text-[10px]"></i>
                        SAVE {{ detailPrices.percent }}%
                    </span>

                    <!-- Wishlist Button Overlay -->
                    <button type="button" @click="wishlist(product.wishlist = !product.wishlist)"
                        class="w-10 h-10 rounded-full shadow-lg absolute top-4 right-16 z-20 bg-white hover:scale-105 active:scale-90 transition-all duration-300 flex items-center justify-center border border-gray-100">
                        <i :class="product.wishlist ? 'lab-fill-heart text-primary animate-heart-pulse' : 'lab-line-heart text-secondary'" class="text-xl mt-0.5"></i>
                    </button>

                    <!-- Share Button Overlay -->
                    <button type="button" @click="shareProduct"
                        class="w-10 h-10 rounded-full shadow-lg absolute top-4 right-4 z-20 bg-white text-secondary hover:text-primary hover:scale-105 active:scale-95 transition-all duration-300 flex items-center justify-center border border-gray-100">
                        <i class="fa-solid fa-share-nodes text-base"></i>
                    </button>
                    <div @touchend="onFallbackImageTap($event)"
                        @click="onFallbackImageTap($event)"
                        class="w-full h-full relative overflow-hidden flex items-center justify-center select-none cursor-pointer product-gallery-slide rounded-2xl">
                        <img :src="product.image" alt="products" loading="eager" fetchpriority="high" decoding="async"
                            @error="$event.target.src=$store.getters['frontendSetting/lists'].theme_logo; $event.target.classList.remove('object-cover'); $event.target.classList.add('object-contain', 'bg-white', 'p-8')"
                            class="w-full h-full object-cover transition-transform duration-300 ease-out origin-center rounded-2xl" />
                    </div>
                </div>

                <div class="col-12 sm:col-6 lg:col-7 lg:pl-10">
                    <!-- Premium Interactive Price & Offer Row (Container styling removed as requested) -->
                    <div class="mb-2">
                        <div class="flex flex-nowrap items-center justify-between gap-2 sm:gap-4 w-full">
                            <!-- Left: Price and Discount Pill -->
                            <div class="flex flex-nowrap items-baseline gap-2 sm:gap-3 shrink-0">
                                <span class="text-4xl min-[360px]:text-5xl sm:text-6xl font-black text-primary tracking-tight whitespace-nowrap shrink-0">
                                    {{ detailPrices.salePrice }}
                                </span>
                                <div class="flex flex-nowrap items-baseline gap-1.5 sm:gap-2 shrink-0" v-if="detailPrices.onSale">
                                    <del class="text-base min-[360px]:text-lg sm:text-xl font-medium text-gray-400 line-through whitespace-nowrap shrink-0">
                                        {{ detailPrices.originalPrice }}
                                    </del>
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs min-[360px]:text-sm sm:text-sm font-black bg-red-100 text-red-600 animate-pulse whitespace-nowrap shrink-0">
                                        {{ detailPrices.percent }}% OFF
                                    </span>
                                </div>
                            </div>

                            <!-- Right: Dynamic Ticker Stock & Sales Conveyor Badge -->
                            <div class="h-[44px] overflow-hidden flex items-center relative select-none shrink-0">
                                <Transition name="badge-fade">
                                    <div :key="currentActiveBadge ? currentActiveBadge.type : 'empty'" v-if="currentActiveBadge"
                                        :class="currentActiveBadge.bgClass"
                                        class="inline-flex items-center px-3 py-2 sm:px-5 sm:py-2.5 rounded-full border text-xs min-[360px]:text-sm sm:text-base font-black shadow-sm transition-all duration-300 whitespace-nowrap shrink-0">
                                        
                                        <!-- Low Stock Pulsing Indicator -->
                                        <span v-if="currentActiveBadge.type === 'stock-low'" class="relative flex h-2.5 w-2.5 mr-2 sm:h-3 sm:w-3 sm:mr-2.5 shrink-0">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                                        </span>
                                        
                                        <!-- In Stock / Sold Count Icons -->
                                        <i v-else-if="currentActiveBadge.icon" :class="currentActiveBadge.icon" class="mr-2 sm:mr-2.5 text-xs min-[360px]:text-sm sm:text-base"></i>
                                        
                                        <span>{{ currentActiveBadge.text }}</span>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>

                    <p v-if="product.bought_last_24_hours > 0 || product.in_baskets > 0" class="text-red-500 font-bold text-sm mb-2 flex items-center gap-1.5 animate-pulse">
                        <i class="fa-solid fa-fire text-red-500 text-xs"></i>
                        <span>{{ socialProofText(product.in_baskets, product.bought_last_24_hours) }}</span>
                    </p>

                    <!-- Flash Sale Countdown Timer -->
                    <div v-if="product.flash_sale && flashSaleTimeLeft" class="mb-6 p-4 rounded-xl bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-lg flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-bolt text-2xl animate-pulse text-yellow-300"></i>
                            <div>
                                <h3 class="font-black text-lg sm:text-xl leading-none tracking-tight">Flash Sale Ends In</h3>
                                <p class="text-xs sm:text-sm font-medium text-red-100 mt-1">Don't miss out on this offer!</p>
                            </div>
                        </div>
                        <div class="flex gap-2 text-center">
                            <div class="bg-white/20 rounded-lg p-2 min-w-[50px] backdrop-blur-sm border border-white/30">
                                <span class="block text-xl font-black leading-none">{{ flashSaleTimeLeft.days }}</span>
                                <span class="text-[10px] uppercase font-bold tracking-wider mt-1 block opacity-80">Days</span>
                            </div>
                            <div class="bg-white/20 rounded-lg p-2 min-w-[50px] backdrop-blur-sm border border-white/30">
                                <span class="block text-xl font-black leading-none">{{ flashSaleTimeLeft.hours }}</span>
                                <span class="text-[10px] uppercase font-bold tracking-wider mt-1 block opacity-80">Hrs</span>
                            </div>
                            <div class="bg-white/20 rounded-lg p-2 min-w-[50px] backdrop-blur-sm border border-white/30">
                                <span class="block text-xl font-black leading-none">{{ flashSaleTimeLeft.minutes }}</span>
                                <span class="text-[10px] uppercase font-bold tracking-wider mt-1 block opacity-80">Min</span>
                            </div>
                            <div class="bg-white/20 rounded-lg p-2 min-w-[50px] backdrop-blur-sm border border-white/30">
                                <span class="block text-xl font-black leading-none">{{ flashSaleTimeLeft.seconds }}</span>
                                <span class="text-[10px] uppercase font-bold tracking-wider mt-1 block opacity-80">Sec</span>
                            </div>
                        </div>
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-bold capitalize text-heading mb-3">{{ product.name }}</h2>

                    <!-- Etsy-Style Shipping, Delivery, Rating & Fees Row -->
                    <div class="grid grid-cols-3 gap-1 py-2 my-2 text-center text-xs sm:text-sm">
                        <!-- 1. Star Ratings Column -->
                        <div @click="scrollToReviews" class="flex flex-col items-center justify-center px-1 cursor-pointer hover:opacity-85 transition-opacity">
                            <div class="flex items-center gap-1 mb-1">
                                <span class="text-sm font-black text-gray-900">{{ product.rating_star_count > 0 ? (product.rating_star / product.rating_star_count).toFixed(1) : '5.0' }}</span>
                                <i class="fa-solid fa-star text-[#FFBC1F] text-xs"></i>
                            </div>
                            <span class="text-[11px] text-gray-500 hover:text-primary cursor-pointer font-bold whitespace-nowrap">
                                ({{ product.rating_star_count }} {{ product.rating_star_count > 1 ? $t('label.reviews') : $t('label.review') }})
                            </span>
                        </div>
                        
                        <!-- 2. Dynamic Estimated Delivery Column -->
                        <div class="flex flex-col items-center justify-center px-1">
                            <div class="flex items-center gap-1.5 mb-1 text-primary">
                                <i class="fa-solid fa-truck-fast text-xs"></i>
                                <span class="text-xs font-black text-gray-900">Arrives Soon</span>
                            </div>
                            <span class="text-[11px] font-bold text-green-600 whitespace-nowrap">
                                {{ getEstimatedDeliveryDate() }}
                            </span>
                        </div>
                        
                        <!-- 3. Shipping Fee Column -->
                        <div class="flex flex-col items-center justify-center px-1">
                            <div class="flex items-center gap-1 mb-1 text-green-600">
                                <i class="fa-solid fa-box text-xs"></i>
                                <span class="text-xs font-black text-green-600">Shipping</span>
                            </div>
                            <span class="text-[11px] font-bold text-gray-900 whitespace-nowrap">
                                {{ getShippingFee() }}
                            </span>
                        </div>
                    </div>



                    <VariationComponent
                        v-if="showVariationComponent && product.slug"
                        :product-slug="product.slug"
                        :variation-tree-data="allVariationTree"
                        :initial-variant-id="initialVariantIdFromRoute"
                        :fallback-image="product.image"
                        :method="selectedVariationMethod"
                        :variations="initialVariations"
                    />

                    <dl class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-3">
                        <dt class="capitalize text-lg font-semibold">{{ $t('label.quantity') }}:</dt>
                        <dd class="flex items-center gap-6">
                            <div class="flex items-center gap-1 w-20 p-1 rounded-full bg-[#F7F7FC]">
                                <button @click.prevent="quantityDecrement" type="button"
                                    :class="temp.quantity === 1 ? 'cursor-not-allowed' : ''"
                                    class="lab-fill-circle-minus text-lg leading-none transition-all duration-300 hover:text-primary"></button>
                                <input type="number" v-model="temp.quantity" v-on:keypress="onlyNumber($event)"
                                    v-on:keyup="quantityUp" class="text-center w-full h-5 text-sm font-medium">
                                <button @click.prevent="quantityIncrement" type="button"
                                    :class="temp.stock === temp.quantity ? 'cursor-not-allowed' : temp.quantity === temp.maximum_purchase_quantity ? 'cursor-not-allowed' : ''"
                                    class="lab-fill-circle-plus text-lg leading-none transition-all duration-300 hover:text-primary"></button>
                            </div>
                            <div v-if="!initialVariations.length || selectedVariation != null">
                                <p v-if="temp.stock > 0" class="capitalize">
                                    {{ $t('label.available') }}:
                                    <b>({{ temp.stock }}) </b>
                                    {{ product.unit }}
                                </p>
                                <p v-else class="capitalize text-danger">
                                    {{ $t('label.stock_out') }}
                                </p>
                            </div>
                        </dd>
                    </dl>

                    <dl v-if="temp.quantity > 1" class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-3">
                        <dt class="capitalize text-lg font-semibold">{{ $t('label.total_price') }}:</dt>
                        <dd class="flex items-center gap-6 text-green-500 font-semibold text-lg">
                            {{
                                currencyFormat(temp.totalPrice, setting.site_digit_after_decimal_point,
                                    setting.site_default_currency_symbol, setting.site_currency_position)
                            }}
                        </dd>
                    </dl>

                    <div class="flex flex-row items-center gap-2 mb-2">
                        <button @click.prevent="addToCart" type="button"
                            class="flex-1 sm:flex-none h-12 px-5 sm:px-8 rounded-full text-white font-bold flex items-center justify-center gap-2 transition-all duration-300 active:scale-[0.98] shadow-btn-primary !bg-primary">
                            <i class="lab-line-bag text-lg"></i>
                            <span class="whitespace-nowrap text-xs sm:text-sm">{{ $t("button.add_to_cart") }}</span>
                        </button>
                        <button @click.prevent="buyNow" type="button"
                            class="flex-1 sm:flex-none h-12 px-5 sm:px-10 rounded-full text-white font-extrabold flex items-center justify-center gap-2 transition-all duration-300 active:scale-[0.98] shadow-[0_4px_15px_rgba(220,38,38,0.3)] bg-red-600 hover:bg-red-700 hover:scale-[1.02]">
                            <i class="fa-solid fa-bolt text-lg text-yellow-300 animate-pulse"></i>
                            <span class="whitespace-nowrap text-xs sm:text-sm">{{ $t("button.buy_now") || 'Buy Now' }}</span>
                        </button>
                    </div>

                    <button v-if="setting.whatsapp_status === activityEnum.ENABLE && setting.whatsapp_product_status === activityEnum.ENABLE" @click.prevent="orderOnWhatsApp" type="button"
                        class="whatsapp-sparkle-btn w-full h-12 rounded-full bg-[#25D366] hover:bg-[#1ebd5a] text-white font-extrabold flex items-center justify-center gap-2 transition-all duration-300 active:scale-[0.98] mb-3 relative overflow-hidden">
                        <i class="fa-brands fa-whatsapp text-xl relative z-10"></i>
                        <span class="whitespace-nowrap text-sm sm:text-base relative z-10">Order on WhatsApp</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-24">
        <div class="container">
            <div class="row">
                <div class="col-12 flex flex-col gap-3">
                    <!-- Details Section -->
                    <div class="rounded-[32px] border border-[#D9DBE9] bg-white p-4 sm:p-6">
                        <h3 class="capitalize text-2xl sm:text-3xl font-bold mb-3 flex items-center gap-2 text-heading">
                            <i class="lab-line-document text-primary text-2xl sm:text-3xl"></i>
                            {{ $t('label.product_details') }}
                        </h3>
                        <div class="text-description text-base text-gray-700 leading-relaxed" v-html="product.details"></div>
                    </div>



                    <!-- Reviews Section -->
                    <div id="product-reviews-section" class="rounded-[32px] border border-[#D9DBE9] bg-white p-4 sm:p-6 scroll-mt-24 sm:scroll-mt-28">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="capitalize text-2xl sm:text-3xl font-bold flex items-center gap-3 text-heading">
                                <i class="lab-line-star text-primary text-2xl sm:text-3xl"></i>
                                {{ $t('label.product_reviews') }}
                            </h3>
                            <button v-if="product.rating_star_count > reviews.length" @click.prevent="readMore"
                                type="button" class="text-primary font-bold hover:underline transition-all duration-300 text-sm sm:text-base whitespace-nowrap">
                                View All
                            </button>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-3 mb-4 pb-2">
                            <starRating border-color="#FFBC1F" :rounded-corners="true" :padding="2.5"
                                :border-width="2.5" :star-size="14" class="-mt-0.5" inactive-color="#FFFFFF"
                                active-color="#FFBC1F" :round-start-rating="false" :show-rating="false"
                                :read-only="true" :max-rating="5"
                                :rating="(product.rating_star / product.rating_star_count)" />
                            <div v-if="product.rating_star_count > 0" class="flex items-center gap-1.5">
                                <span class="text-lg font-bold text-heading">
                                    {{ (product.rating_star / product.rating_star_count).toFixed(1) }}
                                </span>
                                <span class="text-base font-medium text-gray-500">
                                    ({{ product.rating_star_count }} {{ product.rating_star_count > 1 ? $t('label.reviews') : $t('label.review') }})
                                </span>
                            </div>
                        </div>

                        <div v-if="reviews && reviews.length" class="space-y-6">
                            <div v-for="(review, index) in reviews?.slice()?.reverse()" :key="index" class="border-b border-gray-100 last:border-b-0 pb-6 last:pb-0">
                                <div class="flex items-center justify-between gap-4 mb-2">
                                    <h4 class="text-lg font-bold text-heading capitalize">{{ review.name }}</h4>
                                    <span class="text-sm text-gray-400 font-medium">{{ review.date }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 mb-3">
                                    <starRating border-color="#FFBC1F" inactive-color="#FFFFFF" active-color="#FFBC1F"
                                        :rounded-corners="true" :padding="2" :border-width="2" :star-size="11"
                                        class="-mt-0.5" :round-start-rating="false" :show-rating="false"
                                        :read-only="true" :max-rating="5" :rating="review.star" />
                                </div>
                                <p class="text-base text-gray-600 leading-relaxed mb-4">{{ review.review }}</p>

                                <div class="flex flex-wrap gap-3" v-if="review.images && review.images.length > 0">
                                    <img v-for="(reviewImage, imgIndex) in review.images" :key="imgIndex" :src="reviewImage" alt="review image" loading="lazy"
                                        class="w-20 h-20 object-cover rounded-xl cursor-pointer hover:opacity-85 transition-all duration-300 border border-gray-200" 
                                        @click="previewImage(review.images, imgIndex, review)" data-modal="imagePreviewModal">
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Shipping and Return Section -->
                    <div class="rounded-[32px] border border-[#D9DBE9] bg-white p-4 sm:p-6">
                        <h3 class="capitalize text-2xl sm:text-3xl font-bold mb-3 flex items-center gap-2 text-heading">
                            <i class="lab-line-truck text-primary text-2xl sm:text-3xl"></i>
                            {{ $t('label.product_shipping_and_return') }}
                        </h3>
                        <div class="text-description text-base text-gray-700 leading-relaxed" v-html="product.shipping_and_return"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <RelatedProductsSection v-if="product.slug" :product-slug="product.slug" />

    <section v-if="recentlyViewedLoading || recentlyViewedProducts.length > 0" class="mb-12 sm:mb-16">
        <div class="container">
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2.5">
                Recently Viewed
            </h4>

            <RecentlyViewedStripSkeleton v-if="recentlyViewedLoading" />

            <div v-else class="flex gap-2.5 overflow-x-auto pb-2 recently-viewed-scroll">
                <div v-for="product in recentlyViewedProducts" :key="product.id"
                    @click.prevent="goToRecentlyViewedProduct(product.slug)"
                    class="flex-shrink-0 w-[100px] cursor-pointer group">
                    <div class="w-[100px] h-[100px] rounded-lg overflow-hidden bg-gray-50 mb-1.5 relative">
                        <div v-if="!product.cover || product.cover.includes('default/product')"
                            class="absolute inset-0 flex items-center justify-center bg-gray-50/50 z-10">
                            <img :src="setting.theme_logo" alt="logo" loading="lazy"
                                class="w-1/2 h-1/2 object-contain opacity-40 group-hover:scale-105 group-hover:opacity-70 transition-all duration-300" />
                        </div>
                        <template v-else>
                            <div class="absolute inset-0 flex items-center justify-center z-0"
                                v-if="!recentlyViewedLoadedImages[product.id]">
                                <div class="flex gap-1">
                                    <div class="w-1.5 h-1.5 bg-primary/40 rounded-full animate-bounce"></div>
                                    <div class="w-1.5 h-1.5 bg-primary/40 rounded-full animate-bounce" style="animation-delay: 0.15s"></div>
                                    <div class="w-1.5 h-1.5 bg-primary/40 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
                                </div>
                            </div>
                            <img :src="product.cover" :alt="product.name" loading="lazy"
                                @load="onRecentlyViewedImageLoad(product.id)"
                                @error="onRecentlyViewedImageError($event, product.id)"
                                :class="recentlyViewedLoadedImages[product.id] ? 'opacity-100' : 'opacity-0'"
                                class="w-full h-full object-cover group-hover:scale-105 transition-all duration-300 relative z-10" />
                        </template>
                    </div>
                    <h5 class="text-[11px] font-medium text-gray-700 leading-tight line-clamp-2 group-hover:text-primary transition-colors duration-200">
                        {{ product.name }}
                    </h5>
                    <div class="flex items-center gap-1 mt-0.5">
                        <span class="text-[11px] font-bold text-primary font-sans">
                            {{ product.is_offer && product.discounted_price ? product.discounted_price : product.currency_price }}
                        </span>
                        <del v-if="product.is_offer && product.old_currency_price" class="text-[9px] text-gray-400 font-sans">
                            {{ product.old_currency_price }}
                        </del>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Product Gallery Fullscreen Lightbox -->
    <div v-if="showMediaLightbox" class="fixed inset-0 z-[9998] bg-black flex flex-col product-media-lightbox"
        @click.self="closeMediaLightbox"
        @gesturestart.prevent
        @gesturechange.prevent
        @gestureend.prevent>
        <div v-if="animatingWishlist" class="fixed inset-0 z-[10000] flex items-center justify-center pointer-events-none">
            <div class="w-24 h-24 rounded-full bg-white/95 flex items-center justify-center shadow-2xl animate-heart-burst">
                <i class="lab-fill-heart text-primary text-5xl animate-heart-pulse"></i>
            </div>
        </div>
        <div class="absolute top-0 left-0 w-full p-4 flex items-center justify-between z-20 bg-gradient-to-b from-black/80 to-transparent">
            <button @click="closeMediaLightbox" type="button" class="text-white hover:text-gray-300 p-2">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </button>
            <span class="text-white text-sm font-medium" v-if="combinedMedia.length > 0">
                {{ mediaLightboxIndex + 1 }} / {{ combinedMedia.length }}
            </span>
            <div class="w-10"></div>
        </div>
        <div class="flex-1 flex items-center justify-center w-full min-h-0 pb-24 sm:pb-28">
            <Swiper
                :initialSlide="mediaLightboxIndex"
                v-bind="galleryLightboxSwiperProps"
                :loop="lightboxLoopEnabled"
                :navigation="true"
                :modules="modules"
                :allowTouchMove="!isLightboxImageZoomed"
                @slideChange="handleMediaLightboxSlideChange"
                @sliderFirstMove="onLightboxSlideDragStart"
                @slideChangeTransitionEnd="onLightboxSlideTransitionEnd"
                class="w-full h-full product-gallery-lightbox">
                <SwiperSlide v-for="(media, index) in combinedMedia" :key="'lightbox-' + index" class="flex items-center justify-center">
                    <div v-if="media.type === 'image'"
                        class="w-full h-full flex items-center justify-center p-4 overflow-hidden lightbox-image-zoom-wrap"
                        @click="onLightboxImageTap($event)"
                        @touchstart="onLightboxPinchStart($event, index)"
                        @touchmove="onLightboxPinchMove($event, index)"
                        @touchend="onLightboxPinchEnd"
                        @touchcancel="onLightboxPinchEnd"
                        @wheel="onLightboxWheel($event, index)">
                        <img :src="media.url" alt="product"
                            :style="getLightboxImageStyle(index)"
                            class="max-w-full max-h-[78vh] object-contain transition-transform duration-150 ease-out origin-center select-none pointer-events-none" />
                    </div>
                    <div v-else-if="media.type === 'video'" class="w-full h-full flex items-center justify-center bg-black max-h-[85vh]">
                        <iframe v-if="media.data.video_provider === 5 || media.data.video_provider === 10 || media.data.video_provider === 15"
                            :src="mediaLightboxPlaybackIndex === index && !lightboxSwiperDragging ? formatVideoLink(media.data) : ''"
                            class="w-full h-full max-h-[85vh] pointer-events-none"
                            frameborder="0"
                            allow="autoplay; encrypted-media; playsinline"></iframe>
                        <div v-else class="relative w-full h-full max-h-[85vh]">
                            <img
                                :src="getVideoPoster(media)"
                                alt="video preview"
                                class="w-full h-full max-h-[85vh] object-cover absolute inset-0 pointer-events-none"
                                :class="mediaLightboxPlaybackIndex === index && !lightboxSwiperDragging ? 'opacity-0' : 'opacity-100'"
                            />
                            <video
                                v-if="mediaLightboxPlaybackIndex === index && !lightboxSwiperDragging"
                                :src="media.data.link"
                                :poster="getVideoPoster(media)"
                                autoplay
                                muted
                                loop
                                playsinline
                                webkit-playsinline
                                preload="auto"
                                class="w-full h-full max-h-[85vh] object-cover relative z-[1] pointer-events-none"
                                @canplay="onLightboxVideoCanPlay($event, index)"
                            ></video>
                        </div>
                    </div>
                </SwiperSlide>
            </Swiper>
        </div>
        <div class="absolute bottom-0 left-0 w-full z-20 bg-gradient-to-t from-black via-black/85 to-transparent pt-10 pb-4 px-4 sm:pb-6 sm:px-6">
            <div class="w-full max-w-4xl mx-auto flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <span class="block text-[10px] sm:text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">Total Price</span>
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-lg sm:text-2xl font-black text-white leading-none drop-shadow-md whitespace-nowrap">
                            {{ currencyFormat(temp.totalPrice, setting.site_digit_after_decimal_point, setting.site_default_currency_symbol, setting.site_currency_position) }}
                        </span>
                        <span v-if="detailPrices.onSale"
                            class="hidden sm:inline-flex bg-primary/20 text-primary text-[10px] font-bold px-1.5 py-0.5 rounded-md leading-none shadow-sm border border-primary/30">
                            -{{ discountPercentageDetail() }}%
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <button @click.prevent.stop="addToCart" type="button"
                        class="h-10 sm:h-11 px-4 sm:px-5 rounded-full text-white font-extrabold flex items-center justify-center gap-1.5 transition-all duration-300 active:scale-[0.98] bg-primary shadow-btn-primary hover:-translate-y-0.5">
                        <i class="lab-line-bag text-sm sm:text-base"></i>
                        <span class="text-xs sm:text-sm whitespace-nowrap">Add to cart</span>
                    </button>
                    <button @click.prevent.stop="buyNow" type="button"
                        class="h-10 sm:h-11 px-4 sm:px-5 rounded-full text-white font-extrabold flex items-center justify-center gap-1.5 transition-all duration-300 active:scale-[0.98] bg-red-600 hover:bg-red-700 shadow-[0_4px_15px_rgba(220,38,38,0.35)] hover:-translate-y-0.5">
                        <i class="fa-solid fa-bolt text-yellow-300 text-sm sm:text-base"></i>
                        <span class="text-xs sm:text-sm whitespace-nowrap">Buy Now</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

     <!-- Full Screen Review Image Viewer Modal (Temu Style) -->
     <div id="imagePreviewModal" class="modal fixed !inset-0 z-[9999] bg-black !left-0 !translate-x-0 flex items-center justify-center product-image-preview-modal"
        @click.self="hidePreviewImage"
        @gesturestart.prevent
        @gesturechange.prevent
        @gestureend.prevent>
        <div v-if="animatingWishlist" class="fixed inset-0 z-[10000] flex items-center justify-center pointer-events-none">
            <div class="w-24 h-24 rounded-full bg-white/95 flex items-center justify-center shadow-2xl animate-heart-burst">
                <i class="lab-fill-heart text-primary text-5xl animate-heart-pulse"></i>
            </div>
        </div>
        <!-- Header: Close & Pagination -->
        <div class="absolute top-0 left-0 w-full p-4 sm:p-6 flex items-center justify-between z-20 pointer-events-none bg-gradient-to-b from-black/80 to-transparent">
            <button @click="hidePreviewImage" class="text-white hover:text-gray-300 transition-colors pointer-events-auto p-2">
                <i class="fa-solid fa-xmark text-xl sm:text-2xl"></i>
            </button>
            <div class="text-white text-sm sm:text-base font-medium tracking-wide drop-shadow-md">
                <span v-if="previewImages && previewImages.length > 0">
                    {{ previewIndex + 1 }} / {{ previewImages.length }}
                </span>
            </div>
            <!-- Empty div for flex balance -->
            <div class="w-10"></div>
        </div>

        <!-- Swiper Container (Full Screen) -->
        <div class="absolute inset-0 w-full h-full flex items-center justify-center" v-if="previewImages && previewImages.length > 0">
            <Swiper :initialSlide="previewIndex"
                v-bind="galleryLightboxSwiperProps"
                :allowTouchMove="!isPreviewImageZoomed"
                @slideChange="handlePreviewSlideChange"
                :modules="modules"
                class="w-full h-full product-image-preview-swiper">
                <SwiperSlide v-for="(img, idx) in previewImages" :key="idx" class="w-full h-full" @click.self="hidePreviewImage">
                    <div class="w-full h-full flex items-center justify-center p-4 overflow-hidden lightbox-image-zoom-wrap"
                        @click="onPreviewImageTap($event)"
                        @click.self="hidePreviewImage"
                        @touchstart="onPreviewPinchStart($event, idx)"
                        @touchmove="onPreviewPinchMove($event, idx)"
                        @touchend="onPreviewPinchEnd"
                        @touchcancel="onPreviewPinchEnd"
                        @wheel="onPreviewWheel($event, idx)">
                        <img :src="img" alt="review"
                            :style="getPreviewImageStyle(idx)"
                            class="max-w-full max-h-[85vh] object-contain transition-transform duration-150 ease-out origin-center select-none pointer-events-none"
                            loading="lazy" />
                    </div>
                </SwiperSlide>
            </Swiper>
        </div>

        <!-- Review Info & Action Area (Floating at Bottom) -->
        <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-black via-black/80 to-transparent pt-12 pb-4 sm:pb-6 px-4 sm:px-6 pointer-events-none z-10 flex flex-col justify-end">
            
            <div class="w-full max-w-4xl mx-auto pointer-events-auto flex flex-col gap-4">
                <!-- Review Details (Name, Date, Stars, Text) -->
                <div v-if="previewReview" class="text-white flex flex-col gap-2">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-white/10 flex items-center justify-center border border-white/20 text-white flex-shrink-0">
                            <i class="lab-line-user text-base sm:text-lg"></i>
                        </div>
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-sm sm:text-base capitalize">{{ previewReview.name }}</span>
                                <span class="text-xs text-gray-400">on {{ previewReview.date }}</span>
                            </div>
                            <div class="flex items-center gap-1 mt-0.5">
                                <starRating border-color="#FFBC1F" inactive-color="rgba(255,255,255,0.2)" active-color="#FFBC1F"
                                    :rounded-corners="true" :padding="2" :border-width="0" :star-size="10"
                                    :round-start-rating="false" :show-rating="false"
                                    :read-only="true" :max-rating="5" :rating="previewReview.star" />
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-sm sm:text-base text-gray-200 leading-snug drop-shadow-md mt-1">{{ previewReview.review }}</p>
                </div>

                <!-- Product Info & Actions Line -->
                <div class="flex items-center justify-between border-t border-white/10 pt-4 mt-1 gap-4">
                    <!-- Price Snippet -->
                    <div class="flex flex-col">
                        <span class="text-[10px] sm:text-xs text-gray-400 font-bold uppercase tracking-wider mb-0.5">Total Price</span>
                        <div class="flex items-center gap-2">
                            <span class="text-lg sm:text-xl font-black text-white leading-none drop-shadow-md">
                                {{ currencyFormat(temp.totalPrice, setting.site_digit_after_decimal_point, setting.site_default_currency_symbol, setting.site_currency_position) }}
                            </span>
                            <span v-if="detailPrices.onSale" class="bg-primary/20 text-primary text-[10px] font-bold px-1.5 py-0.5 rounded-md leading-none shadow-sm border border-primary/30">
                                -{{ detailPrices.percent }}%
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button @click.prevent="addToCart" type="button"
                            class="px-5 h-10 sm:h-11 rounded-full text-white font-extrabold flex items-center justify-center transition-all duration-300 bg-[#FF8A00] hover:bg-[#ff9d2e] shadow-[0_4px_15px_rgba(255,138,0,0.4)] hover:-translate-y-0.5">
                            <span class="text-sm">Add to cart</span>
                        </button>
                        <button @click.prevent="buyNow" type="button"
                            class="px-5 h-10 sm:h-11 rounded-full text-white font-extrabold flex items-center justify-center transition-all duration-300 bg-[#FF3B30] hover:bg-[#ff4e45] shadow-[0_4px_15px_rgba(255,59,48,0.4)] hover:-translate-y-0.5">
                            <span class="text-sm">Buy Now</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pdp-mobile-sticky-bar fixed left-4 right-4 z-20 p-3 bg-white rounded-2xl border border-gray-100 shadow-[0_4px_20px_rgba(0,0,0,0.08)] sm:hidden flex items-center justify-between gap-3">
        <div class="flex flex-col text-left flex-shrink-0">
            <span class="text-[9px] font-bold text-text uppercase tracking-widest">{{ $t('label.total_price') }}</span>
            <span class="text-base font-extrabold text-heading">
                {{
                    currencyFormat(temp.totalPrice, setting.site_digit_after_decimal_point,
                        setting.site_default_currency_symbol, setting.site_currency_position)
                }}
            </span>
        </div>
        <div class="flex items-center gap-2 flex-grow justify-end max-w-[65%] sm:max-w-[70%]">
            <button @click.prevent="addToCart" type="button"
                class="flex-1 h-11 px-1.5 rounded-full text-white font-bold flex items-center justify-center gap-1 active:scale-[0.98] transition-all duration-300 text-[10px] min-[375px]:text-xs whitespace-nowrap bg-primary shadow-btn-primary">
                <i class="lab-line-bag text-sm font-bold"></i>
                <span>{{ $t("button.add_to_cart") }}</span>
            </button>
            <button @click.prevent="buyNow" type="button"
                class="flex-1 h-11 px-1 rounded-full text-white font-extrabold flex items-center justify-center gap-1 active:scale-[0.98] transition-all duration-300 whitespace-nowrap animate-flash-buy">
                <i class="fa-solid fa-bolt text-yellow-300 animate-bolt-strike text-xs min-[375px]:text-sm"></i>
                <div class="flex flex-col items-center justify-center leading-none">
                    <span class="text-[10px] min-[375px]:text-[12px] font-black uppercase tracking-wider block">{{ $t("button.buy_now") || 'Buy Now' }}</span>
                    <span class="text-[8px] min-[375px]:text-[9px] font-medium opacity-90 block animate-buy-text-fade mt-0.5" :key="currentBuyNowText" v-if="discountPercentageDetail() > 0">
                        {{ currentBuyNowText }}
                    </span>
                </div>
            </button>
        </div>
    </div>

    <!-- Stunning Premium Social Sharing Modal -->
    <div id="shareModal" class="modal flex items-center">
            <div class="max-w-md w-full mx-auto relative p-6 bg-white rounded-2xl shadow-card">
                <button 
                    @click="hideShareModal" 
                    class="absolute top-4 right-4 text-secondary hover:text-primary w-8 h-8 rounded-full border border-gray-100 flex items-center justify-center transition-all duration-300"
                >✕</button>
                <h3 class="capitalize text-xl font-extrabold text-heading mb-2 text-center">{{ $t('label.share_this_product') || 'Share This Product' }}</h3>
                <p class="text-sm text-text mb-6 text-center line-clamp-2">{{ product.name }}</p>
                
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <a :href="'https://api.whatsapp.com/send?text=' + encodeURIComponent('Look what I found on Ejweller: ' + shareUrl)" target="_blank"
                       class="flex flex-col items-center justify-center p-3 rounded-2xl bg-emerald-50 hover:bg-emerald-100 text-emerald-600 transition-colors duration-300">
                        <i class="lab-fill-whatsapp text-3xl mb-1"></i>
                        <span class="text-xs font-bold">WhatsApp</span>
                    </a>
                    <a :href="'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl)" target="_blank"
                       class="flex flex-col items-center justify-center p-3 rounded-2xl bg-blue-50 hover:bg-blue-100 text-blue-600 transition-colors duration-300">
                        <i class="lab-fill-facebook text-3xl mb-1"></i>
                        <span class="text-xs font-bold">Facebook</span>
                    </a>
                    <a :href="'https://twitter.com/intent/tweet?text=' + encodeURIComponent('Look what I found on Ejweller: ') + '&url=' + encodeURIComponent(shareUrl)" target="_blank"
                       class="flex flex-col items-center justify-center p-3 rounded-2xl bg-sky-50 hover:bg-sky-100 text-sky-600 transition-colors duration-300">
                        <i class="lab-fill-x text-3xl mb-1"></i>
                        <span class="text-xs font-bold">Twitter</span>
                    </a>
                    <a :href="'mailto:?subject=' + encodeURIComponent(product.name) + '&body=' + encodeURIComponent('Look what I found on Ejweller: ' + shareUrl)"
                       class="flex flex-col items-center justify-center p-3 rounded-2xl bg-gray-50 hover:bg-gray-100 text-gray-600 transition-colors duration-300">
                        <i class="lab-fill-mail text-3xl mb-1"></i>
                        <span class="text-xs font-bold">Email</span>
                    </a>
                </div>

                <div class="flex items-center gap-2 p-1 border border-gray-100 rounded-full bg-slate-50">
                    <input type="text" readonly :value="shareUrl" class="w-full text-xs text-text bg-transparent pl-4 pr-2 outline-none select-all" />
                    <button @click="copyShareLink" type="button" class="flex-shrink-0 py-2 px-5 rounded-full bg-primary text-white text-xs font-bold transition-all duration-300 active:scale-[0.97]">
                        {{ copyText }}
                    </button>
                </div>
            </div>
    </div>
</template>

<script>
import { ref, computed, defineAsyncComponent, nextTick } from "vue";
import { Swiper, SwiperSlide } from 'swiper/vue';
import { FreeMode, Navigation, Thumbs, Pagination, Autoplay } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/thumbs';
import LoadingComponent from "../components/LoadingComponent";
import starRating from "vue-star-rating";
import targetService from "../../../services/targetService";
import router from "../../../router";
import CategoryBreadcrumbComponent from "../components/CategoryBreadcrumbComponent";
import RecentlyViewedStripSkeleton from "../components/skeleton/RecentlyViewedStripSkeleton.vue";
import appService from "../../../services/appService";
import alertService from "../../../services/alertService";
import { useHead } from '@vueuse/head';
import { pixelService } from "../../../services/pixelService";
import 'vue-inner-image-zoom/lib/vue-inner-image-zoom.css';

import InnerImageZoom from 'vue-inner-image-zoom';
import activityEnum from "../../../enums/modules/activityEnum";
import axios from "axios";
import { discountPercentage, getDetailPrices, parseAmount, withCartLinePricing } from "../../../utils/productOffer";
import { trackProductViewed, trackWishlistToggle } from "../../../services/analyticsEcommerceBridge";
import { captureVideoThumbnail, isSelfHostedVideo } from "../../../utils/videoThumbnail";
import {
    productGalleryMainSwiperProps,
    productGalleryLightboxSwiperProps,
    connectGalleryThumbs,
    getGalleryClickedIndex,
} from "../../../utils/productGallerySwiper";

export default {
    name: "ProductDetailsComponent",
    components: {
        VariationComponent: defineAsyncComponent(() => import("../components/VariationComponent")),
        RecentlyViewedStripSkeleton,
        RelatedProductsSection: defineAsyncComponent(() => import("./RelatedProductsSection.vue")),
        CategoryBreadcrumbComponent,
        starRating,
        Swiper,
        SwiperSlide,
        LoadingComponent,
        'inner-image-zoom': InnerImageZoom
    },
    setup() {
        const thumbsSwiper = ref(null);
        const mainSwiper = ref(null);

        const resetGallerySwipers = () => {
            if (mainSwiper.value && !mainSwiper.value.destroyed) {
                try {
                    mainSwiper.value.destroy(true, true);
                } catch (e) {}
            }
            if (thumbsSwiper.value && !thumbsSwiper.value.destroyed) {
                try {
                    thumbsSwiper.value.destroy(true, true);
                } catch (e) {}
            }
            mainSwiper.value = null;
            thumbsSwiper.value = null;
        };

        const setThumbsSwiper = (swiper) => {
            thumbsSwiper.value = swiper;
            nextTick(() => connectGalleryThumbs(mainSwiper.value, swiper));
        };
        const setMainSwiper = (swiper) => {
            mainSwiper.value = swiper;
            nextTick(() => connectGalleryThumbs(swiper, thumbsSwiper.value));
        };

        const galleryThumbsConfig = computed(() => {
            if (!thumbsSwiper.value || thumbsSwiper.value.destroyed) {
                return undefined;
            }
            return { swiper: thumbsSwiper.value };
        });

        return {
            thumbsSwiper,
            mainSwiper,
            setThumbsSwiper,
            setMainSwiper,
            resetGallerySwipers,
            galleryThumbsConfig,
            modules: [FreeMode, Navigation, Thumbs, Pagination, Autoplay],
            gallerySwiperProps: productGalleryMainSwiperProps,
            galleryLightboxSwiperProps: productGalleryLightboxSwiperProps,
        }
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            props: {
                search: {
                    slug: null,
                    review_limit: 3
                }
            },
            activityEnum: activityEnum,
            enableAddToCardButton: false,
            selectedVariation: null,
            productArray: {},
            showVariationComponent: false,
            initProduct: {
                isVariation: false,
                variationId: null,
                sku: null,
                stock: 0,
                quantity: 1,
                discount: 0,
                price: 0,
                oldPrice: 0,
                totalPrice: 0,
                maximum_purchase_quantity: 0
            },
            temp: {
                name: "",
                image: "",
                isVariation: false,
                variationId: null,
                productId: 0,
                sku: null,
                stock: 0,
                taxes: {},
                shipping: {},
                quantity: 1,
                discount: 0,
                price: 0,
                oldPrice: 0,
                totalPrice: 0,
                maximum_purchase_quantity: 0
            },
            previewImages: [],
            previewIndex: 0,
            previewReview: null,
            previewHistoryActive: false,
            _onPreviewPopState: null,
            shareUrl: "",
            copyText: "Copy",
            showMediaLightbox: false,
            mediaLightboxIndex: 0,
            mediaLightboxPlaybackIndex: 0,
            lightboxSwiperDragging: false,
            lightboxHistoryActive: false,
            lightboxPinch: { scale: 1, x: 0, y: 0, startDist: 0, startScale: 1, active: false, panning: false, panStartX: 0, panStartY: 0, panOriginX: 0, panOriginY: 0 },
            previewPinch: { scale: 1, x: 0, y: 0, startDist: 0, startScale: 1, active: false, panning: false, panStartX: 0, panStartY: 0, panOriginX: 0, panOriginY: 0 },
            _onLightboxPopState: null,
            animatingWishlist: false,
            tickerIndex: 0,
            tickerInterval: null,
            soldCount: 0,
            flashSaleTimeLeft: null,
            flashSaleInterval: null,
            badgeIndex: 0,
            badgeInterval: null,
            localWishlist: JSON.parse(localStorage.getItem('local_wishlist') || '[]'),
            activeViewers: 0,
            viewersInterval: null,
            recentlyViewedProducts: [],
            recentlyViewedLoading: false,
            recentlyViewedLoadedImages: {},
            videoPosterMap: {},
            mainSwiperActiveIndex: 0,
            gallerySliderDragged: false,
            loadToken: 0,
        }
    },
    computed: {
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
        categories: function () {
            return this.$store.getters["frontendProductCategory/ancestorsAndSelf"];
        },
        initialVariations: function () {
            return this.$store.getters["frontendProductVariation/initialVariation"];
        },
        allVariationTree: function () {
            return this.$store.getters["frontendProductVariation/allVariation"];
        },
        product: function () {
            return this.$store.getters["frontendProduct/show"];
        },
        images: function () {
            return this.$store.getters["frontendProduct/showImages"];
        },
        videos: function () {
            return this.$store.getters["frontendProduct/showVideos"];
        },
        combinedMedia: function () {
            const list = [];
            const imgs = this.images || [];
            const vids = this.videos || [];
            
            if (imgs.length > 0) {
                list.push({ type: 'image', url: imgs[0], originalIndex: 0 });
                if (vids.length > 0) {
                    list.push({ type: 'video', data: vids[0], originalIndex: 0 });
                }
                for (let i = 1; i < imgs.length; i++) {
                    list.push({ type: 'image', url: imgs[i], originalIndex: i });
                }
                for (let i = 1; i < vids.length; i++) {
                    list.push({ type: 'video', data: vids[i], originalIndex: i });
                }
            } else {
                vids.forEach((vid, i) => {
                    list.push({ type: 'video', data: vid, originalIndex: i });
                });
            }
            return list;
        },
        reviews: function () {
            return this.$store.getters["frontendProduct/showReviews"];
        },
        animatedBuyNowTexts: function () {
            const texts = [];
            const discount = this.discountPercentageDetail();
            if (discount > 0) {
                texts.push(`${discount}% OFF`);
            }
            texts.push('Offer Ends Soon');
            texts.push('Hurry!');
            return texts;
        },
        currentBuyNowText: function () {
            const texts = this.animatedBuyNowTexts;
            return texts[this.tickerIndex % texts.length];
        },
        activeBadges: function () {
            const list = [];
            
            // Item 1: Stock Status
            if (this.temp.stock > 0 && this.temp.stock <= 12) {
                list.push({
                    type: 'stock-low',
                    text: `Only ${this.temp.stock} left!`,
                    icon: null,
                    bgClass: 'bg-red-50 border border-red-100 text-red-600'
                });
            } else if (this.temp.stock > 0) {
                list.push({
                    type: 'stock-ok',
                    text: 'In Stock',
                    icon: 'fa-solid fa-circle-check text-blue-500 mr-2',
                    bgClass: 'bg-blue-50 border border-blue-100 text-blue-600'
                });
            }
            
            // Item 2: Sold Status
            if (this.shouldShowSoldCount()) {
                list.push({
                    type: 'sold',
                    text: `${this.getProductSoldCount()} Sold`,
                    icon: 'fa-solid fa-fire text-amber-500 animate-bounce mr-2',
                    bgClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700'
                });
            }
            
            return list;
        },
        currentActiveBadge: function () {
            const list = this.activeBadges;
            if (list.length === 0) return null;
            return list[this.badgeIndex % list.length];
        },
        initialVariantIdFromRoute: function () {
            const variant = this.$route?.query?.variant;
            if (variant == null || variant === '') {
                return null;
            }
            const id = parseInt(variant, 10);
            return isNaN(id) ? null : id;
        },
        variationPriceProduct: function () {
            if (this.selectedVariation && this.selectedVariation.sku) {
                const v = this.selectedVariation;
                return {
                    is_offer: !!v.is_offer,
                    price: v.price,
                    old_price: v.old_price,
                    currency_price: v.currency_price,
                    old_currency_price: v.old_currency_price,
                    discount_percentage: v.discount_percentage,
                    discount: v.discount,
                };
            }
            return this.product;
        },
        detailPrices: function () {
            return getDetailPrices(this.variationPriceProduct);
        },
        isLightboxImageZoomed: function () {
            return this.lightboxPinch.active || this.lightboxPinch.panning || this.lightboxPinch.scale > 1.05;
        },
        isPreviewImageZoomed: function () {
            return this.previewPinch.active || this.previewPinch.panning || this.previewPinch.scale > 1.05;
        },
        galleryPaginationConfig: function () {
            return this.getPaginationConfig();
        },
        lightboxLoopEnabled: function () {
            const media = this.combinedMedia || [];
            if (media.some((item) => item.type === 'video')) {
                return false;
            }
            return media.length > 2;
        },
    },
    mounted() {
        this.show();
        this.scheduleEngagementWidgets();
    },
    beforeUnmount() {
        if (this.tickerInterval) {
            clearInterval(this.tickerInterval);
        }
        if (this.badgeInterval) {
            clearInterval(this.badgeInterval);
        }
        if (this.viewersInterval) {
            clearInterval(this.viewersInterval);
        }
        if (this._onLightboxPopState) {
            window.removeEventListener('popstate', this._onLightboxPopState);
        }
        if (this._onPreviewPopState) {
            window.removeEventListener('popstate', this._onPreviewPopState);
        }
        document.body.style.overflow = '';
        document.body.classList.remove('media-lightbox-open');
        document.body.classList.remove('image-preview-open');
        this.resetGallerySwipers();
    },
    methods: {
        scheduleEngagementWidgets: function () {
            const start = () => {
                this.tickerInterval = setInterval(() => {
                    if (this.discountPercentageDetail() > 0) {
                        this.tickerIndex++;
                    }
                }, 2200);
                this.badgeInterval = setInterval(() => {
                    this.badgeIndex++;
                }, 3000);
                this.activeViewers = Math.floor(Math.random() * (45 - 15 + 1)) + 15;
                this.viewersInterval = setInterval(() => {
                    const change = Math.floor(Math.random() * 5) - 2;
                    let newViewers = this.activeViewers + change;
                    if (newViewers < 12) newViewers = 12 + Math.floor(Math.random() * 5);
                    if (newViewers > 75) newViewers = 75 - Math.floor(Math.random() * 5);
                    this.activeViewers = newViewers;
                }, 5000);
            };

            if (typeof requestIdleCallback === 'function') {
                requestIdleCallback(start, { timeout: 3000 });
            } else {
                setTimeout(start, 500);
            }
        },
        productCacheKey: function (slug) {
            return 'product_show_v1_' + slug;
        },
        readProductCache: function (slug) {
            try {
                const raw = sessionStorage.getItem(this.productCacheKey(slug));
                if (!raw) {
                    return null;
                }
                const parsed = JSON.parse(raw);
                if (!parsed?.data || Date.now() - parsed.t > 180000) {
                    return null;
                }
                return parsed.data;
            } catch (e) {
                return null;
            }
        },
        writeProductCache: function (slug, data) {
            try {
                sessionStorage.setItem(this.productCacheKey(slug), JSON.stringify({
                    t: Date.now(),
                    data: data,
                }));
            } catch (e) {}
        },
        preloadHeroImage: function (data) {
            const hero = data?.image || (Array.isArray(data?.images) && data.images.length ? data.images[0] : null);
            if (!hero) {
                return;
            }
            const img = new Image();
            img.decoding = 'async';
            img.src = hero;
        },
        getProductSoldCount: function () {
            if (this.product && (parseInt(this.product.use_random_sale) === 10 || parseInt(this.product.use_random_sale) === 0)) {
                return this.product.actual_sales || 0;
            }
            if (this.soldCount > 0) {
                return this.soldCount;
            }
            if (this.product && this.product.id) {
                return (this.product.id * 53) % 450 + 138;
            }
            return 138;
        },
        stopFlashSaleTimer() {
            if (this.flashSaleInterval) {
                clearInterval(this.flashSaleInterval);
                this.flashSaleInterval = null;
            }
        },
        startFlashSaleTimer(endDate) {
            this.stopFlashSaleTimer();
            const end = new Date(endDate).getTime();
            
            const updateTimer = () => {
                const now = new Date().getTime();
                const distance = end - now;
                
                if (distance < 0) {
                    this.flashSaleTimeLeft = null;
                    this.stopFlashSaleTimer();
                    return;
                }
                
                this.flashSaleTimeLeft = {
                    days: Math.floor(distance / (1000 * 60 * 60 * 24)).toString().padStart(2, '0'),
                    hours: Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)).toString().padStart(2, '0'),
                    minutes: Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)).toString().padStart(2, '0'),
                    seconds: Math.floor((distance % (1000 * 60)) / 1000).toString().padStart(2, '0')
                };
            };
            
            updateTimer();
            this.flashSaleInterval = setInterval(updateTimer, 1000);
        },
        discountPercentageDetail: function () {
            return discountPercentage(this.product);
        },
        shouldShowSoldCount: function () {
            if (!this.product) return false;
            const isRandomSaleOff = parseInt(this.product.use_random_sale) === 10 || parseInt(this.product.use_random_sale) === 0;
            if (isRandomSaleOff && (!this.product.actual_sales || parseInt(this.product.actual_sales) === 0)) {
                return false;
            }
            return true;
        },
        scrollToReviews: function () {
            const element = document.getElementById('product-reviews-section');
            if (!element) {
                return;
            }
            const header = document.querySelector('header');
            const headerOffset = header
                ? Math.ceil(header.getBoundingClientRect().height) + 16
                : 96;
            const top = element.getBoundingClientRect().top + window.scrollY - headerOffset;
            window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
        },
        isEmbedVideo: function (media) {
            if (!media?.data) {
                return false;
            }
            const provider = Number(media.data.video_provider);
            return provider === 5 || provider === 10 || provider === 15;
        },
        getVideoPoster: function (media) {
            if (!media?.data?.link) {
                return this.fallbackVideoPoster();
            }
            if (media.data.thumbnail) {
                return media.data.thumbnail;
            }
            if (this.isEmbedVideo(media)) {
                return this.getEmbedVideoThumbnail(media);
            }
            if (this.videoPosterMap[media.data.link]) {
                return this.videoPosterMap[media.data.link];
            }
            return this.fallbackVideoPoster();
        },
        fallbackVideoPoster: function () {
            return this.product?.image || this.setting?.theme_logo || '';
        },
        getEmbedVideoThumbnail: function (media) {
            const link = media.data.link;
            if (Number(media.data.video_provider) === 5) {
                const ytId = this.getYouTubeId(link);
                if (ytId) {
                    return `https://img.youtube.com/vi/${ytId}/hqdefault.jpg`;
                }
            }
            return this.fallbackVideoPoster();
        },
        onVideoPosterImageError: function (event) {
            if (!event?.target) {
                return;
            }
            event.target.src = this.fallbackVideoPoster();
            event.target.classList.remove('object-cover');
            event.target.classList.add('object-contain', 'bg-white', 'p-2');
        },
        generateVideoPosters: function () {
            const videos = this.videos || [];
            videos.forEach((video) => {
                if (!isSelfHostedVideo(video) || !video.link || this.videoPosterMap[video.link]) {
                    return;
                }
                captureVideoThumbnail(video.link).then((dataUrl) => {
                    this.videoPosterMap = {
                        ...this.videoPosterMap,
                        [video.link]: dataUrl,
                    };
                }).catch(() => {});
            });
        },
        onMainGallerySlideChange: function (swiper) {
            this.mainSwiperActiveIndex = swiper?.realIndex ?? 0;
        },
        validatePurchaseBeforeAction: function () {
            if (this.showVariationComponent && (!this.selectedVariation || !this.selectedVariation.sku)) {
                alertService.error(
                    this.$t('message.select_all_options')
                    || this.$t('message.please_select_a_variation')
                    || 'Please select all options first!'
                );
                return false;
            }
            if ((this.temp.stock || 0) <= 0) {
                alertService.error(this.$t('message.out_of_stock') || 'This product is out of stock!');
                return false;
            }
            return true;
        },
        socialProofText: function (inBaskets, boughtLast24) {
            const baskets = parseInt(inBaskets, 10) || 0;
            const bought = parseInt(boughtLast24, 10) || 0;
            const parts = [];
            if (baskets > 0) {
                parts.push(`In ${baskets} Bastek`);
            }
            if (bought > 0) {
                parts.push(`${bought} bought in last 24 hours`);
            }
            if (parts.length === 2) {
                return parts[0] + ' & ' + parts[1];
            }
            return parts.join('');
        },
        refreshProductSocialProof: function () {
            if (!this.product?.id) {
                return Promise.resolve();
            }
            const productId = this.product.id;
            return axios.post('frontend/cart-track/stats', {
                product_ids: [productId],
            }).then((res) => {
                const stats = res.data?.data?.[String(productId)] || res.data?.data?.[productId];
                if (stats) {
                    this.$store.commit('frontendProduct/updateSocialProof', {
                        product_id: productId,
                        in_baskets: stats.in_baskets,
                        bought_last_24_hours: stats.bought_last_24_hours,
                    });
                }
            }).catch(() => {});
        },
        openMediaLightbox: function (index) {
            this.mediaLightboxIndex = index;
            this.mediaLightboxPlaybackIndex = index;
            this.lightboxSwiperDragging = false;
            this.resetLightboxPinch();
            this.showMediaLightbox = true;
            document.body.style.overflow = 'hidden';
            document.body.classList.add('media-lightbox-open');
            if (!this.lightboxHistoryActive) {
                history.pushState({ productGallery: 1 }, '');
                this.lightboxHistoryActive = true;
                this._onLightboxPopState = () => {
                    if (this.showMediaLightbox) {
                        this.closeMediaLightbox(true);
                    }
                };
                window.addEventListener('popstate', this._onLightboxPopState);
            }
        },
        closeMediaLightbox: function (fromPopState = false) {
            if (!this.showMediaLightbox) {
                return;
            }
            this.showMediaLightbox = false;
            this.lightboxSwiperDragging = false;
            this.pauseLightboxVideos();
            this.resetLightboxPinch();
            document.body.style.overflow = '';
            document.body.classList.remove('media-lightbox-open');
            if (fromPopState) {
                if (this._onLightboxPopState) {
                    window.removeEventListener('popstate', this._onLightboxPopState);
                    this._onLightboxPopState = null;
                }
                this.lightboxHistoryActive = false;
            } else if (this.lightboxHistoryActive) {
                this.lightboxHistoryActive = false;
                if (this._onLightboxPopState) {
                    window.removeEventListener('popstate', this._onLightboxPopState);
                    this._onLightboxPopState = null;
                }
                history.back();
            }
        },
        resetLightboxPinch: function () {
            this.lightboxPinch = { scale: 1, x: 0, y: 0, startDist: 0, startScale: 1, active: false, panning: false, panStartX: 0, panStartY: 0, panOriginX: 0, panOriginY: 0 };
        },
        resetPreviewPinch: function () {
            this.previewPinch = { scale: 1, x: 0, y: 0, startDist: 0, startScale: 1, active: false, panning: false, panStartX: 0, panStartY: 0, panOriginX: 0, panOriginY: 0 };
        },
        getPinchImageStyle: function (pinchState, isActiveSlide) {
            if (!isActiveSlide) {
                return {};
            }
            const scale = pinchState.scale;
            return {
                transform: `translate(${pinchState.x}px, ${pinchState.y}px) scale(${scale})`,
                cursor: pinchState.panning ? 'grabbing' : (scale > 1 ? 'grab' : 'zoom-in'),
                transition: pinchState.panning || pinchState.active ? 'none' : undefined,
            };
        },
        onPinchTouchStart: function (e, index, activeIndex, pinchState) {
            if (activeIndex !== index) {
                return;
            }
            if (e.touches.length === 2) {
                e.preventDefault();
                pinchState.panning = false;
                pinchState.active = true;
                pinchState.startDist = this.getTouchPinchDistance(e.touches);
                pinchState.startScale = pinchState.scale > 1 ? pinchState.scale : 1;
                return;
            }
            if (e.touches.length === 1 && pinchState.scale > 1.05) {
                e.preventDefault();
                pinchState.active = false;
                pinchState.panning = true;
                pinchState.panStartX = e.touches[0].clientX;
                pinchState.panStartY = e.touches[0].clientY;
                pinchState.panOriginX = pinchState.x;
                pinchState.panOriginY = pinchState.y;
            }
        },
        onPinchTouchMove: function (e, index, activeIndex, pinchState) {
            if (activeIndex !== index) {
                return;
            }
            if (pinchState.panning && e.touches.length === 1) {
                e.preventDefault();
                pinchState.x = pinchState.panOriginX + (e.touches[0].clientX - pinchState.panStartX);
                pinchState.y = pinchState.panOriginY + (e.touches[0].clientY - pinchState.panStartY);
                return;
            }
            if (!pinchState.active || e.touches.length !== 2) {
                return;
            }
            e.preventDefault();
            this.applyPinchZoom(pinchState, this.getTouchPinchDistance(e.touches));
        },
        onPinchTouchEnd: function (pinchState, resetFn) {
            pinchState.panning = false;
            if (pinchState.scale <= 1.05) {
                resetFn.call(this);
            } else {
                pinchState.active = false;
            }
        },
        getLightboxImageStyle: function (index) {
            return this.getPinchImageStyle(this.lightboxPinch, this.mediaLightboxIndex === index);
        },
        getPreviewImageStyle: function (index) {
            return this.getPinchImageStyle(this.previewPinch, this.previewIndex === index);
        },
        getTouchPinchDistance: function (touches) {
            return Math.hypot(
                touches[0].clientX - touches[1].clientX,
                touches[0].clientY - touches[1].clientY
            );
        },
        applyPinchZoom: function (pinchState, dist) {
            const next = (dist / pinchState.startDist) * pinchState.startScale;
            pinchState.scale = Math.min(4, Math.max(1, next));
        },
        onLightboxPinchStart: function (e, index) {
            this.onPinchTouchStart(e, index, this.mediaLightboxIndex, this.lightboxPinch);
        },
        onLightboxPinchMove: function (e, index) {
            this.onPinchTouchMove(e, index, this.mediaLightboxIndex, this.lightboxPinch);
        },
        onLightboxPinchEnd: function () {
            this.onPinchTouchEnd(this.lightboxPinch, this.resetLightboxPinch);
        },
        onLightboxWheel: function (e, index) {
            if (this.mediaLightboxIndex !== index || !e.ctrlKey) {
                return;
            }
            e.preventDefault();
            const delta = e.deltaY < 0 ? 0.12 : -0.12;
            const next = Math.min(4, Math.max(1, this.lightboxPinch.scale + delta));
            this.lightboxPinch.scale = next;
            if (next <= 1.05) {
                this.resetLightboxPinch();
            }
        },
        onPreviewPinchStart: function (e, index) {
            this.onPinchTouchStart(e, index, this.previewIndex, this.previewPinch);
        },
        onPreviewPinchMove: function (e, index) {
            this.onPinchTouchMove(e, index, this.previewIndex, this.previewPinch);
        },
        onPreviewPinchEnd: function () {
            this.onPinchTouchEnd(this.previewPinch, this.resetPreviewPinch);
        },
        onPreviewWheel: function (e, index) {
            if (this.previewIndex !== index || !e.ctrlKey) {
                return;
            }
            e.preventDefault();
            const delta = e.deltaY < 0 ? 0.12 : -0.12;
            const next = Math.min(4, Math.max(1, this.previewPinch.scale + delta));
            this.previewPinch.scale = next;
            if (next <= 1.05) {
                this.resetPreviewPinch();
            }
        },
        handlePreviewSlideChange: function (swiper) {
            this.previewIndex = swiper.activeIndex;
            this.resetPreviewPinch();
        },
        handleMediaLightboxSlideChange: function (swiper) {
            this.mediaLightboxIndex = swiper.realIndex;
        },
        onLightboxSlideDragStart: function () {
            this.lightboxSwiperDragging = true;
            this.pauseLightboxVideos();
        },
        onLightboxSlideTransitionEnd: function (swiper) {
            this.lightboxSwiperDragging = false;
            this.mediaLightboxPlaybackIndex = swiper.realIndex;
            this.mediaLightboxIndex = swiper.realIndex;
            this.resetLightboxPinch();
            this.$nextTick(() => this.playActiveLightboxVideo());
        },
        pauseLightboxVideos: function () {
            document.querySelectorAll('.product-gallery-lightbox video').forEach((video) => {
                try {
                    video.pause();
                } catch (e) {}
            });
        },
        playActiveLightboxVideo: function () {
            const media = this.combinedMedia[this.mediaLightboxPlaybackIndex];
            if (!media || media.type !== 'video' || this.isEmbedVideo(media) || this.lightboxSwiperDragging) {
                return;
            }
            const root = document.querySelector('.product-gallery-lightbox');
            if (!root) {
                return;
            }
            root.querySelectorAll('video').forEach((video) => {
                video.muted = true;
                const playPromise = video.play();
                if (playPromise?.catch) {
                    playPromise.catch(() => {});
                }
            });
        },
        onLightboxVideoCanPlay: function (event, index) {
            if (this.mediaLightboxPlaybackIndex !== index || this.lightboxSwiperDragging) {
                return;
            }
            const video = event.target;
            if (!video || !video.paused) {
                return;
            }
            video.muted = true;
            const playPromise = video.play();
            if (playPromise?.catch) {
                playPromise.catch(() => {});
            }
        },
        handleImageClick: function (index) {
            this.openMediaLightbox(index === 999 ? 0 : index);
        },
        getTapCoords: function (event) {
            const t = event.changedTouches?.[0] || event.touches?.[0];
            return {
                x: t?.clientX ?? event.clientX ?? 0,
                y: t?.clientY ?? event.clientY ?? 0,
            };
        },
        isDoubleTap: function (event, stateKey) {
            const now = Date.now();
            const { x, y } = this.getTapCoords(event);
            const last = this[stateKey];
            if (last && now - last.time < 320 && Math.hypot(x - last.x, y - last.y) < 48) {
                if (last.timer) {
                    clearTimeout(last.timer);
                }
                this[stateKey] = null;
                return true;
            }
            if (last?.timer) {
                clearTimeout(last.timer);
            }
            this[stateKey] = { time: now, x, y, timer: null };
            return false;
        },
        wishlistFromDoubleTap: function () {
            if (!this.product?.id) {
                return;
            }
            if (this.isWishlisted(this.product)) {
                this.animatingWishlist = true;
                setTimeout(() => {
                    this.animatingWishlist = false;
                }, 800);
                return;
            }
            this.wishlist();
        },
        onGallerySliderDrag: function () {
            this.gallerySliderDragged = true;
        },
        onGallerySliderTouchEnd: function () {
            if (this.gallerySliderDragged) {
                window.setTimeout(() => {
                    this.gallerySliderDragged = false;
                }, 320);
            }
        },
        onGallerySwiperClick: function (swiper, event) {
            if (this.gallerySliderDragged || swiper?.allowClick === false) {
                return;
            }
            const index = getGalleryClickedIndex(swiper);
            if (index < 0) {
                return;
            }
            this.onGalleryImageTap(index, event);
        },
        onFallbackImageTap: function (event) {
            this.onGalleryImageTap(999, event);
        },
        onGalleryImageTap: function (index, event) {
            if (event?.type === 'click' && event?.detail === 0) {
                return;
            }
            if (this.isDoubleTap(event, '_galleryImageTapState')) {
                event.preventDefault?.();
                event.stopPropagation?.();
                this.wishlistFromDoubleTap();
                return;
            }
            const state = this._galleryImageTapState;
            if (!state) {
                return;
            }
            if (state.timer) {
                clearTimeout(state.timer);
            }
            state.timer = setTimeout(() => {
                this._galleryImageTapState = null;
                this.handleImageClick(index);
            }, 280);
        },
        onLightboxImageTap: function (event) {
            if (this.isDoubleTap(event, '_lightboxImageTapState')) {
                event.preventDefault();
                event.stopPropagation();
                this.wishlistFromDoubleTap();
                return;
            }
            const state = this._lightboxImageTapState;
            if (state) {
                state.timer = setTimeout(() => {
                    this._lightboxImageTapState = null;
                }, 350);
            }
        },
        onPreviewImageTap: function (event) {
            if (this.isDoubleTap(event, '_previewImageTapState')) {
                event.preventDefault();
                event.stopPropagation();
                this.wishlistFromDoubleTap();
                return;
            }
            const state = this._previewImageTapState;
            if (state) {
                state.timer = setTimeout(() => {
                    this._previewImageTapState = null;
                }, 350);
            }
        },
        onlyNumber: function (e) {
            return appService.onlyNumber(e);
        },
        currencyFormat: function (amount, decimal, currency, position) {
            return appService.currencyFormat(amount, decimal, currency, position);
        },
        multiTargets: function (event, commonBtnClass, commonDivClass, targetID) {
            targetService.multiTargets(event, commonBtnClass, commonDivClass, targetID)
        },
        isWishlisted(product) {
            if (!product) return false;
            if (this.$store.getters.authStatus) {
                return product.wishlist;
            }
            return this.localWishlist.includes(product.id);
        },
        wishlist: function () {
            const currentStatus = this.isWishlisted(this.product);
            const nextStatus = !currentStatus;
            
            if (this.$store.getters.authStatus) {
                this.$store.dispatch("frontendWishlist/toggle", {
                    product_id: this.product.id,
                    toggle: nextStatus
                 }).then((res) => {
                    if (nextStatus) {
                        this.animatingWishlist = true;
                        setTimeout(() => {
                            this.animatingWishlist = false;
                        }, 800);
                    }
                    this.product.wishlist = nextStatus;
                 }).catch((err) => {
                    if (err.response && err.response.status === 401) {
                        this.product.wishlist = false;
                        localStorage.setItem('pending_wishlist_product_id', this.product.id);
                        router.push({ name: "auth.login" });
                    }
                });
            } else {
                // Guest logic!
                let localWish = JSON.parse(localStorage.getItem('local_wishlist') || '[]');
                const prodId = this.product.id;
                if (localWish.includes(prodId)) {
                    localWish = localWish.filter(id => id !== prodId);
                } else {
                    localWish.push(prodId);
                    this.animatingWishlist = true;
                    setTimeout(() => {
                        this.animatingWishlist = false;
                    }, 800);
                }
                localStorage.setItem('local_wishlist', JSON.stringify(localWish));
                this.localWishlist = localWish;
                trackWishlistToggle(
                    { id: this.product.id, product_id: this.product.id, sku: this.product.sku },
                    localWish.includes(prodId)
                );
            }
        },
        readMore: function () {
            this.props.search.review_limit = this.product.rating_star_count;
            this.show();
        },
        previewImage: function (images, index, reviewObj = null) {
            this.previewImages = images || [];
            this.previewIndex = index || 0;
            this.previewReview = reviewObj;
            document.body.classList.add('image-preview-open');
            appService.modalShow('#imagePreviewModal');
            if (!this.previewHistoryActive) {
                history.pushState({ productReviewPreview: 1 }, '');
                this.previewHistoryActive = true;
                this._onPreviewPopState = () => {
                    if (this.previewImages.length > 0) {
                        this.hidePreviewImage(true);
                    }
                };
                window.addEventListener('popstate', this._onPreviewPopState);
            }
        },
        hidePreviewImage: function (fromPopState = false) {
            appService.modalHide('#imagePreviewModal');
            document.body.classList.remove('image-preview-open');
            this.resetPreviewPinch();
            this.previewImages = [];
            this.previewReview = null;
            if (fromPopState) {
                if (this._onPreviewPopState) {
                    window.removeEventListener('popstate', this._onPreviewPopState);
                    this._onPreviewPopState = null;
                }
                this.previewHistoryActive = false;
            } else if (this.previewHistoryActive) {
                this.previewHistoryActive = false;
                if (this._onPreviewPopState) {
                    window.removeEventListener('popstate', this._onPreviewPopState);
                    this._onPreviewPopState = null;
                }
                history.back();
            }
        },
        shareProduct: function () {
            this.shareUrl = window.location.origin + window.location.pathname;
            const shareData = {
                title: this.product.name,
                text: this.product.name,
                url: this.shareUrl
            };
            
            if (navigator.share) {
                navigator.share(shareData)
                    .then(() => {})
                    .catch((err) => {});
            } else {
                appService.modalShow('#shareModal');
            }
        },
        copyShareLink: function () {
            navigator.clipboard.writeText(this.shareUrl).then(() => {
                this.copyText = "Copied!";
                alertService.success("Product link copied to clipboard!");
                setTimeout(() => {
                    this.copyText = "Copy";
                }, 2000);
            }).catch(() => {
                alertService.error("Failed to copy link.");
            });
        },
        hideShareModal: function () {
            appService.modalHide('#shareModal');
        },
        orderOnWhatsApp: function () {
            let phone = this.setting.company_calling_code + this.setting.company_phone;
            if(phone) {
                phone = phone.replace(/[^0-9]/g, '');
            } else {
                phone = '';
            }
            const url = window.location.origin + window.location.pathname;
            const companyName = this.setting.company_name || 'Jadeno.pk';
            const text = `Hi, I want to order : ${this.product.name} | ${companyName} URL: ${url}`;
            window.open(`https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(text)}`, '_blank');
        },
        scheduleDeferredDetailWork: function (productData) {
            const run = () => {
                pixelService.trackViewContent(productData);
                trackProductViewed(productData);
                this.fetchRecentlyViewed();
                this.$nextTick(() => this.generateVideoPosters());
            };
            if (typeof requestIdleCallback === 'function') {
                requestIdleCallback(run, { timeout: 2500 });
            } else {
                setTimeout(run, 0);
            }
        },
        applyProductFromShowResponse: function (data) {
            this.initProduct = {
                isVariation: false,
                variationId: null,
                sku: data.sku,
                stock: data.stock,
                quantity: 1,
                discount: 0,
                price: parseAmount(data.price),
                oldPrice: parseAmount(data.old_price),
                totalPrice: parseAmount(data.price),
                maximum_purchase_quantity: data.maximum_purchase_quantity
            };
            this.temp = {
                name: data.name,
                image: data.image,
                isVariation: false,
                variationId: null,
                productId: data.id,
                sku: data.sku,
                stock: data.stock,
                taxes: data.taxes,
                shipping: data.shipping,
                quantity: 1,
                discount: 0,
                price: parseAmount(data.price),
                oldPrice: parseAmount(data.old_price),
                totalPrice: parseAmount(data.price),
                maximum_purchase_quantity: data.maximum_purchase_quantity
            };

            const randomSaleValue = parseInt(data.use_random_sale);
            const isRandomSaleOff = randomSaleValue === 10 || randomSaleValue === 0;

            if (isRandomSaleOff) {
                this.soldCount = data.actual_sales || 0;
            } else {
                let startingPoint = randomSaleValue === 5 ? ((data.id * 53) % 450 + 138) : randomSaleValue;
                const storageKey = 'sold_count_' + data.id;
                let localCount = localStorage.getItem(storageKey);
                if (!localCount || parseInt(localCount, 10) < startingPoint) {
                    localCount = startingPoint + (data.actual_sales || 0);
                    localStorage.setItem(storageKey, localCount);
                }
                this.soldCount = parseInt(localCount, 10);
            }

            let localViewed = JSON.parse(localStorage.getItem('recently_viewed_products') || '[]');
            localViewed = localViewed.filter((id) => id !== data.id);
            localViewed.unshift(data.id);
            if (localViewed.length > 10) {
                localViewed.pop();
            }
            localStorage.setItem('recently_viewed_products', JSON.stringify(localViewed));

            if (data.flash_sale && data.offer_end_date) {
                this.startFlashSaleTimer(data.offer_end_date);
            }

            this.preloadHeroImage(data);
        },
        applyProductSeo: function (data) {
            if (!data.seo || !data.seo.title || !data.seo.description) {
                return;
            }
            const metaData = [
                { name: 'title', content: data.seo.title },
                { name: 'description', content: data.seo.description },
            ];
            if (data.seo.thumb && data.seo.cover) {
                metaData.push({ content: data.seo.thumb });
                metaData.push({ content: data.seo.cover });
            }
            useHead({
                title: this.setting.company_name + ' - ' + data.seo.title,
                meta: metaData
            });
        },
        loadSecondaryProductData: function (data, token) {
            const run = () => {
                const tasks = [];

                if (data.category_slug) {
                    tasks.push(
                        this.$store.dispatch('frontendProductCategory/ancestorsAndSelf', data.category_slug).catch(() => {})
                    );
                }

                const productSlug = data.slug;
                const productId = data.id;

                tasks.push(
                    this.$store.dispatch('frontendProductVariation/allVariation', productSlug)
                        .then((allVarRes) => {
                            if (token !== this.loadToken) {
                                return;
                            }
                            const hasTree = (allVarRes.data.data || []).length > 0;
                            if (hasTree) {
                                this.showVariationComponent = true;
                            }
                        })
                        .catch(() => {})
                );

                tasks.push(
                    this.$store.dispatch('frontendProductVariation/initialVariation', productId)
                        .then((initVariationRes) => {
                            if (token !== this.loadToken) {
                                return;
                            }
                            if (initVariationRes.data.data.length > 0) {
                                this.showVariationComponent = true;
                            }
                            if (!this.showVariationComponent && data.stock > 0) {
                                this.enableAddToCardButton = false;
                            }
                        })
                        .catch(() => {})
                );

                return Promise.allSettled(tasks);
            };

            if (typeof requestIdleCallback === 'function') {
                requestIdleCallback(run, { timeout: 1500 });
            } else {
                setTimeout(run, 150);
            }
        },
        commitShowPayload: function (data) {
            this.$store.commit('frontendProduct/show', data);
            let images = data.images || [];
            if (images.length === 0 && data.image) {
                images = [data.image];
            }
            this.$store.commit('frontendProduct/showImages', images);
            this.$store.commit('frontendProduct/showReviews', data.reviews);
            this.$store.commit('frontendProduct/showVideos', data.videos);
            this.$store.commit('frontendProduct/showSeo', data.seo);
        },
        handleShowSuccess: function (res, token) {
            if (token !== this.loadToken) {
                return;
            }

            const data = res.data.data;
            this.applyProductFromShowResponse(data);
            this.applyProductSeo(data);
            this.loading.isActive = false;

            this.scheduleDeferredDetailWork(data);
            this.loadSecondaryProductData(data, token);
        },
        requestProductShow: function (token, useTrashed) {
            const action = useTrashed ? 'frontendProduct/showWithTrashed' : 'frontendProduct/show';
            const slug = this.props.search.slug;

            if (!useTrashed) {
                const cached = this.readProductCache(slug);
                if (cached) {
                    this.commitShowPayload(cached);
                    this.handleShowSuccess({ data: { data: cached } }, token);
                }
            }

            this.$store.dispatch(action, this.props.search).then((res) => {
                if (useTrashed) {
                    this.commitShowPayload(res.data.data);
                } else {
                    this.writeProductCache(slug, res.data.data);
                }
                this.handleShowSuccess(res, token);
            }).catch((err) => {
                if (token !== this.loadToken) {
                    return;
                }
                if (!useTrashed && err.response?.status === 404) {
                    this.requestProductShow(token, true);
                    return;
                }
                this.loading.isActive = false;
            });
        },
        show: function () {
            if (typeof this.$route.params.slug === 'undefined') {
                return;
            }

            const token = ++this.loadToken;
            this.resetGallerySwipers();
            this.loading.isActive = true;
            this.selectedVariation = null;
            this.showVariationComponent = false;
            this.enableAddToCardButton = false;
            this.videoPosterMap = {};
            this.mainSwiperActiveIndex = 0;
            this.$store.commit('frontendProductVariation/initialVariation', []);
            this.$store.commit('frontendProductVariation/allVariation', []);
            this.props.search.slug = this.$route.params.slug;

            this.requestProductShow(token, false);
        },
        fetchRecentlyViewed: function () {
            let localViewed = JSON.parse(localStorage.getItem('recently_viewed_products') || '[]');
            // exclude current product
            localViewed = localViewed.filter(id => id !== this.product.id);
            if (localViewed.length > 0) {
                this.recentlyViewedLoading = true;
                this.recentlyViewedLoadedImages = {};
                this.$store.dispatch("frontendProduct/lists", {
                    ids: localViewed.join(','),
                    paginate: 0
                }).then((res) => {
                    this.recentlyViewedProducts = res.data.data || [];
                    this.recentlyViewedLoading = false;
                }).catch((err) => {
                    this.recentlyViewedLoading = false;
                });
            }
        },
        onRecentlyViewedImageLoad: function (productId) {
            this.recentlyViewedLoadedImages[productId] = true;
        },
        onRecentlyViewedImageError: function (event, productId) {
            this.recentlyViewedLoadedImages[productId] = true;
            event.target.src = this.setting.theme_logo;
            event.target.classList.remove('object-cover');
            event.target.classList.add('object-contain', 'p-3', 'opacity-40');
        },
        goToRecentlyViewedProduct: function (slug) {
            if (!slug) {
                return;
            }
            router.push({ name: 'frontend.product.details', params: { slug: slug } });
        },
        selectedVariationMethod: function (variation) {
            this.enableAddToCardButton = true;
            this.selectedVariation = null;

            this.temp.isVariation = this.initProduct.isVariation;
            this.temp.variationId = this.initProduct.variationId;
            this.temp.sku = this.initProduct.sku;
            this.temp.stock = this.initProduct.stock;
            this.temp.quantity = this.initProduct.quantity;
            this.temp.discount = this.initProduct.discount;
            this.temp.price = this.initProduct.price;
            this.temp.oldPrice = this.initProduct.oldPrice;
            this.temp.totalPrice = this.initProduct.price;
            this.temp.maximum_purchase_quantity = this.initProduct.maximum_purchase_quantity;

            if (variation && variation.sku) {
                this.selectedVariation = variation;

                this.temp.isVariation = true;
                this.temp.variationId = variation.id;
                this.temp.sku = variation.sku;
                this.temp.stock = variation.stock;
                this.temp.quantity = 1;
                this.temp.discount = 0;
                this.temp.price = parseAmount(variation.price);
                this.temp.oldPrice = parseAmount(variation.old_price);
                this.temp.totalPrice = parseAmount(variation.price);
                this.temp.maximum_purchase_quantity = variation.maximum_purchase_quantity;

                if (variation.stock > 0) {
                    this.enableAddToCardButton = false;
                }

                if (variation.image) {
                    const imageIndex = this.combinedMedia.findIndex(media => media.url === variation.image);
                    if (imageIndex !== -1 && this.mainSwiper) {
                        this.mainSwiper.slideToLoop(imageIndex);
                    }
                }

                this.totalPriceSetup();
                this.updateVariantInUrl(variation.id);
            } else {
                this.updateVariantInUrl(null);
            }
        },
        updateVariantInUrl: function (variationId) {
            if (typeof window === 'undefined' || !window.history?.replaceState) {
                return;
            }
            try {
                const url = new URL(window.location.href);
                if (variationId) {
                    url.searchParams.set('variant', String(variationId));
                } else {
                    url.searchParams.delete('variant');
                }
                window.history.replaceState({}, '', url.toString());
            } catch (e) {
                // ignore URL update errors
            }
        },
        quantityUp: function () {
            if (this.temp.quantity === 0) {
                this.temp.quantity = 1;
            }
            if (this.temp.quantity > this.temp.stock) {
                this.temp.quantity = this.temp.stock
            }

            if (this.temp.quantity > this.temp.maximum_purchase_quantity) {
                alertService.error(this.$t('message.purchase_limit_exceeded'));
                this.temp.quantity = this.temp.maximum_purchase_quantity
            }
            this.totalPriceSetup();
        },
        quantityIncrement: function () {
            this.temp.quantity++;
            if (this.temp.quantity <= 0) {
                this.temp.quantity = 1;
            }

            if (this.temp.quantity > this.temp.stock) {
                this.temp.quantity--;
            }
            if (this.temp.quantity > this.temp.maximum_purchase_quantity) {
                alertService.error(this.$t('message.purchase_limit_exceeded'));
                this.temp.quantity--;
            }
            this.totalPriceSetup();
        },
        quantityDecrement: function () {
            this.temp.quantity--;
            if (this.temp.quantity <= 0) {
                this.temp.quantity = 1;
            }
            this.totalPriceSetup();
        },
        totalPriceSetup: function () {
            this.temp.totalPrice = (this.temp.price * this.temp.quantity);
        },
        cartPricingSource: function () {
            if (this.selectedVariation && this.selectedVariation.sku) {
                return this.selectedVariation;
            }
            return {
                price: this.temp.price,
                old_price: this.temp.oldPrice,
                is_offer: this.product?.is_offer,
                discount: this.product?.discount,
                discount_percentage: this.product?.discount_percentage,
            };
        },
        buildProductArrayForCart: function (extraFields = {}) {
            const base = {
                name: this.temp.name,
                product_id: this.temp.productId,
                image: this.temp.image,
                variation_names: '',
                variation_id: this.temp.variationId ?? null,
                sku: this.temp.sku,
                stock: this.temp.stock,
                taxes: this.temp.taxes,
                shipping: this.temp.shipping,
                quantity: this.temp.quantity,
                maximum_purchase_quantity: this.temp.maximum_purchase_quantity,
                in_baskets: this.product.in_baskets || 0,
                bought_last_24_hours: this.product.bought_last_24_hours || 0,
                ...extraFields,
            };
            return withCartLinePricing(base, this.cartPricingSource());
        },
        addToCart: function () {
            if (!this.validatePurchaseBeforeAction()) {
                return;
            }

            // Increment social proof sold count
            if (this.temp.productId) {
                const storageKey = 'sold_count_' + this.temp.productId;
                this.soldCount++;
                localStorage.setItem(storageKey, this.soldCount);
            }
            this.enableAddToCardButton = true;
            this.productArray = this.buildProductArrayForCart();

            if (this.selectedVariation) {
                this.$store.dispatch("frontendProductVariation/ancestorsToString", this.selectedVariation.id).then((res) => {
                    this.productArray.variation_names = res.data.data;
                    this.showVariationComponent = false;
                    this.$store.dispatch("frontendCart/lists", this.productArray).then((res) => {
                        this.refreshProductSocialProof();
                        this.showVariationComponent = true;
                        this.productArray = {};
                        this.selectedVariation = null;
                        this.temp.isVariation = this.initProduct.isVariation;
                        this.temp.variationId = this.initProduct.variationId;
                        this.temp.sku = this.initProduct.sku;
                        this.temp.stock = this.initProduct.stock;
                        this.temp.quantity = this.initProduct.quantity;
                        this.temp.discount = this.initProduct.discount;
                        this.temp.price = this.initProduct.price;
                        this.temp.oldPrice = this.initProduct.oldPrice;
                        this.temp.totalPrice = this.initProduct.price;
                        this.temp.maximum_purchase_quantity = this.initProduct.maximum_purchase_quantity;
                    }).catch((err) => {
                        if (err && err.message === "stockOut") {
                            alertService.error(this.$t('message.out_of_stock') || "This product is out of stock!");
                        } else {
                            alertService.error(this.$t('message.maximum_quantity') || "Maximum purchase quantity reached!");
                        }
                        this.showVariationComponent = true;
                        this.selectedVariation = null;
                        this.temp.stock = this.initProduct.stock;
                        this.temp.quantity = this.initProduct.quantity;
                    });
                }).catch((err) => {
                });
            } else {
                this.$store.dispatch("frontendCart/lists", this.productArray).then((res) => {
                    this.refreshProductSocialProof();
                    this.enableAddToCardButton = false;
                    this.productArray = {};
                    this.selectedVariation = null;
                    this.temp.isVariation = this.initProduct.isVariation;
                    this.temp.variationId = this.initProduct.variationId;
                    this.temp.sku = this.initProduct.sku;
                    this.temp.stock = this.initProduct.stock;
                    this.temp.quantity = this.initProduct.quantity;
                    this.temp.discount = this.initProduct.discount;
                    this.temp.price = this.initProduct.price;
                    this.temp.oldPrice = this.initProduct.oldPrice;
                    this.temp.totalPrice = this.initProduct.price;
                    this.temp.maximum_purchase_quantity = this.initProduct.maximum_purchase_quantity;
                }).catch((err) => {
                    if (err && err.message === "stockOut") {
                        alertService.error(this.$t('message.out_of_stock') || "This product is out of stock!");
                    } else {
                        alertService.error(this.$t('message.maximum_quantity') || "Maximum purchase quantity reached!");
                    }
                    this.enableAddToCardButton = false;
                    this.selectedVariation = null;
                    this.temp.stock = this.initProduct.stock;
                    this.temp.quantity = this.initProduct.quantity;
                });
            }
        },
        buyNow: function () {
            if (!this.validatePurchaseBeforeAction()) {
                return;
            }

            // Increment social proof sold count
            if (this.temp.productId) {
                const storageKey = 'sold_count_' + this.temp.productId;
                this.soldCount++;
                localStorage.setItem(storageKey, this.soldCount);
            }
            this.enableAddToCardButton = true;
            this.productArray = this.buildProductArrayForCart({ skipCartDrawer: true });

            if (this.selectedVariation) {
                this.$store.dispatch("frontendProductVariation/ancestorsToString", this.selectedVariation.id).then((res) => {
                    this.productArray.variation_names = res.data.data;
                    this.showVariationComponent = false;
                    this.$store.dispatch("frontendCart/lists", this.productArray).then((res) => {
                        this.showVariationComponent = true;
                        this.productArray = {};
                        this.selectedVariation = null;
                        this.temp.isVariation = this.initProduct.isVariation;
                        this.temp.variationId = this.initProduct.variationId;
                        this.temp.sku = this.initProduct.sku;
                        this.temp.stock = this.initProduct.stock;
                        this.temp.quantity = this.initProduct.quantity;
                        this.temp.discount = this.initProduct.discount;
                        this.temp.price = this.initProduct.price;
                        this.temp.oldPrice = this.initProduct.oldPrice;
                        this.temp.totalPrice = this.initProduct.price;
                        this.temp.maximum_purchase_quantity = this.initProduct.maximum_purchase_quantity;
                        router.push({ name: "frontend.checkout.checkout" });
                    }).catch((err) => {
                        if (err && err.message === "stockOut") {
                            alertService.error(this.$t('message.out_of_stock') || "This product is out of stock!");
                        } else {
                            alertService.error(this.$t('message.maximum_quantity') || "Maximum purchase quantity reached!");
                        }
                        this.showVariationComponent = true;
                        this.selectedVariation = null;
                        this.temp.stock = this.initProduct.stock;
                        this.temp.quantity = this.initProduct.quantity;
                    });
                }).catch((err) => {
                });
            } else {
                this.$store.dispatch("frontendCart/lists", this.productArray).then((res) => {
                    this.refreshProductSocialProof();
                    this.enableAddToCardButton = false;
                    this.productArray = {};
                    this.selectedVariation = null;
                    this.temp.isVariation = this.initProduct.isVariation;
                    this.temp.variationId = this.initProduct.variationId;
                    this.temp.sku = this.initProduct.sku;
                    this.temp.stock = this.initProduct.stock;
                    this.temp.quantity = this.initProduct.quantity;
                    this.temp.discount = this.initProduct.discount;
                    this.temp.price = this.initProduct.price;
                    this.temp.oldPrice = this.initProduct.oldPrice;
                    this.temp.totalPrice = this.initProduct.price;
                    this.temp.maximum_purchase_quantity = this.initProduct.maximum_purchase_quantity;
                    router.push({ name: "frontend.checkout.checkout" });
                }).catch((err) => {
                    if (err && err.message === "stockOut") {
                        alertService.error(this.$t('message.out_of_stock') || "This product is out of stock!");
                    } else {
                        alertService.error(this.$t('message.maximum_quantity') || "Maximum purchase quantity reached!");
                    }
                    this.enableAddToCardButton = false;
                    this.selectedVariation = null;
                    this.temp.stock = this.initProduct.stock;
                    this.temp.quantity = this.initProduct.quantity;
                });
            }
        },
        getYouTubeId(url) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        },
        formatVideoLink(video) {
            if (!video || !video.link) return '';
            let link = video.link;
            
            // YouTube
            if (video.video_provider === 5) {
                const ytId = this.getYouTubeId(link);
                if (ytId) {
                    if (!link.includes('/embed/')) {
                        link = 'https://www.youtube.com/embed/' + ytId;
                    }
                    return link + '?autoplay=1&mute=1&loop=1&playlist=' + ytId + '&controls=0&showinfo=0&modestbranding=1&enablejsapi=1&playsinline=1';
                }
            }
            // Vimeo
            else if (video.video_provider === 10) {
                return link + (link.includes('?') ? '&' : '?') + 'autoplay=1&loop=1&muted=1&background=1&controls=0&playsinline=1';
            }
            // Dailymotion
            else if (video.video_provider === 15) {
                return link + (link.includes('?') ? '&' : '?') + 'autoplay=1&mute=1&loop=1&controls=0&playsinline=1';
            }
            return link;
        },
        getEstimatedDeliveryDate() {
            const today = new Date();
            const minDeliveryDate = new Date(today);
            minDeliveryDate.setDate(today.getDate() + 2); // 2 days from now
            
            const maxDeliveryDate = new Date(today);
            maxDeliveryDate.setDate(today.getDate() + 4); // 4 days from now
            
            const options = { month: 'short', day: 'numeric' };
            const minFormatted = minDeliveryDate.toLocaleDateString('en-US', options);
            const maxFormatted = maxDeliveryDate.toLocaleDateString('en-US', options);
            
            return `${minFormatted} - ${maxFormatted}`;
        },
        getShippingFee() {
            if (!this.setting) return 'Calculated at checkout';
            
            // 1. PRODUCT_WISE (5)
            if (parseInt(this.setting.shipping_setup_method) === 5) {
                if (this.product && this.product.shipping && parseFloat(this.product.shipping.shipping_cost) > 0) {
                    return this.currencyFormat(
                        this.product.shipping.shipping_cost,
                        this.setting.site_digit_after_decimal_point,
                        this.setting.site_default_currency_symbol,
                        this.setting.site_currency_position
                    );
                }
                return 'FREE Delivery';
            }
            
            // 2. FLAT_RATE_WISE (10)
            else if (parseInt(this.setting.shipping_setup_method) === 10) {
                if (parseFloat(this.setting.shipping_setup_flat_rate_wise_cost) > 0) {
                    return this.currencyFormat(
                        this.setting.shipping_setup_flat_rate_wise_cost,
                        this.setting.site_digit_after_decimal_point,
                        this.setting.site_default_currency_symbol,
                        this.setting.site_currency_position
                    );
                }
                return 'FREE Delivery';
            }
            
            // 3. AREA_WISE (15)
            else if (parseInt(this.setting.shipping_setup_method) === 15) {
                if (parseFloat(this.setting.shipping_setup_area_wise_default_cost) > 0) {
                    return this.currencyFormat(
                        this.setting.shipping_setup_area_wise_default_cost,
                        this.setting.site_digit_after_decimal_point,
                        this.setting.site_default_currency_symbol,
                        this.setting.site_currency_position
                    );
                }
                return 'FREE Delivery';
            }
            
            
            return 'FREE Delivery';
        },
        getPaginationConfig: function () {
            const videoIndices = [];
            if (this.combinedMedia && this.combinedMedia.length > 0) {
                this.combinedMedia.forEach((media, index) => {
                    if (media.type === 'video') {
                        videoIndices.push(index);
                    }
                });
            }
            if (videoIndices.length === 0) {
                return { clickable: true };
            }
            return {
                clickable: true,
                renderBullet: function (index, className) {
                    if (videoIndices.includes(index)) {
                        return `<span class="${className} video-dot"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M7 6v12l10-6z"/></svg></span>`;
                    }
                    return `<span class="${className}"></span>`;
                }
            };
        }
    },
    watch: {
        '$route.params.slug'(newSlug, oldSlug) {
            if (newSlug && newSlug !== oldSlug) {
                this.show();
            }
        },
        videos: {
            handler() {
                this.$nextTick(() => this.generateVideoPosters());
            },
            deep: true,
        },
    }
}
</script>

<style scoped>
.gallery-swiper-container,
.gallery-swiper,
.gallery-swiper :deep(.swiper-wrapper),
.gallery-swiper :deep(.swiper-slide) {
    touch-action: pan-y pinch-zoom;
    -webkit-tap-highlight-color: transparent;
}

.product-gallery-slide,
.product-gallery-slide img {
    touch-action: pan-y pinch-zoom;
    -webkit-user-drag: none;
    user-select: none;
}

.product-gallery-lightbox,
.product-gallery-lightbox :deep(.swiper-wrapper),
.product-gallery-lightbox :deep(.swiper-slide) {
    touch-action: pan-y pinch-zoom;
    -webkit-tap-highlight-color: transparent;
}

.product-image-preview-swiper,
.product-image-preview-swiper :deep(.swiper-wrapper),
.product-image-preview-swiper :deep(.swiper-slide) {
    touch-action: pan-y pinch-zoom;
    -webkit-tap-highlight-color: transparent;
}

.lightbox-image-zoom-wrap {
    touch-action: none;
}

.gallery-swiper :deep(.swiper-pagination) {
    bottom: 10px !important;
}
.gallery-swiper :deep(.swiper-pagination-bullet) {
    background: #ff5c00 !important;
    opacity: 0.65;
    width: 8px;
    height: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
    margin: 0 5px !important;
}
.gallery-swiper :deep(.swiper-pagination-bullet-active) {
    opacity: 1;
    background: #ff5c00 !important;
    width: 18px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}
.gallery-swiper :deep(.swiper-button-next),
.gallery-swiper :deep(.swiper-button-prev) {
    color: #ff5c00 !important;
    background: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.gallery-swiper :deep(.swiper-button-next):after,
.gallery-swiper :deep(.swiper-button-prev):after {
    font-size: 16px;
    font-weight: bold;
}

.animate-heart-pulse {
    animation: heartPulse 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}

.animate-fade-overlay {
    animation: fadeOverlay 0.8s ease-in-out forwards;
}

.animate-heart-burst {
    animation: heartBurst 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

@keyframes fadeOverlay {
    0% { opacity: 0; }
    15% { opacity: 1; }
    75% { opacity: 1; }
    100% { opacity: 0; }
}

@keyframes heartBurst {
    0% { transform: scale(0.3); opacity: 0; }
    25% { transform: scale(1.1); opacity: 1; }
    35% { transform: scale(0.95); }
    75% { transform: scale(1); opacity: 1; }
    100% { transform: scale(0.8); opacity: 0; }
}

@keyframes heartPulse {
    0% {
        transform: scale(1);
    }
    35% {
        transform: scale(1.45);
    }
    70% {
        transform: scale(0.85);
    }
    100% {
        transform: scale(1);
    }
}

.animate-flash-buy {
    animation: premiumFlashGlow 3.5s infinite ease-in-out;
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 40%, #ffffff 50%, #ef4444 60%, #dc2626 100%) !important;
    background-size: 250% 100% !important;
}

@keyframes premiumFlashGlow {
    0% {
        background-position: 100% 0;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        transform: scale(1);
    }
    40% {
        background-position: 100% 0;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        transform: scale(1);
    }
    50% {
        background-position: 0 0;
        box-shadow: 0 6px 20px rgba(220, 38, 38, 0.6), 0 0 0 4px rgba(220, 38, 38, 0.15);
        transform: scale(1.04);
    }
    60% {
        background-position: 0 0;
        box-shadow: 0 6px 20px rgba(220, 38, 38, 0.6), 0 0 0 4px rgba(220, 38, 38, 0.15);
        transform: scale(1.04);
    }
    100% {
        background-position: -100% 0;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        transform: scale(1);
    }
}

.animate-buy-text-fade {
    animation: buyTextFade 0.4s ease-out;
}

@keyframes buyTextFade {
    0% {
        opacity: 0;
        transform: translateY(8px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-bolt-strike {
    animation: boltStrike 0.8s infinite ease-in-out;
    display: inline-block;
}

@keyframes boltStrike {
    0%, 100% {
        transform: scale(1) rotate(0deg);
        filter: drop-shadow(0 0 2px rgba(253, 224, 71, 0.5));
    }
    50% {
        transform: scale(1.3) rotate(15deg);
        filter: drop-shadow(0 0 8px rgba(253, 224, 71, 0.95));
    }
}

/* Super-premium simultaneous cross-fade with zero blank space */
.badge-fade-enter-active,
.badge-fade-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.badge-fade-leave-active {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    transform: translateY(-50%) scale(0.95);
    pointer-events: none;
}
.badge-fade-enter-from {
    opacity: 0;
    transform: scale(0.95);
}
.badge-fade-leave-to {
    opacity: 0;
    transform: translateY(-50%) scale(0.9);
}

.whatsapp-sparkle-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(105deg, transparent 35%, rgba(255, 255, 255, 0.45) 50%, transparent 65%);
    background-size: 200% 100%;
    animation: whatsappShimmer 2.8s ease-in-out infinite;
    pointer-events: none;
}

.whatsapp-sparkle-btn::after {
    content: '';
    position: absolute;
    top: 0;
    right: 15%;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #fff;
    box-shadow:
        -28px 8px 0 1px rgba(255, 255, 255, 0.7),
        32px 18px 0 0 rgba(255, 255, 255, 0.5),
        -12px 22px 0 1px rgba(255, 255, 255, 0.6);
    animation: whatsappSparkleDots 2s ease-in-out infinite;
    pointer-events: none;
}

@keyframes whatsappShimmer {
    0%, 100% { background-position: 200% 0; opacity: 0.3; }
    50% { background-position: -200% 0; opacity: 1; }
}

@keyframes whatsappSparkleDots {
    0%, 100% { opacity: 0.4; transform: scale(0.8); }
    50% { opacity: 1; transform: scale(1.2); }
}

.product-gallery-lightbox :deep(.swiper-button-next),
.product-gallery-lightbox :deep(.swiper-button-prev) {
    color: #fff;
}

.recently-viewed-scroll::-webkit-scrollbar {
    height: 3px;
}
.recently-viewed-scroll::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.recently-viewed-scroll::-webkit-scrollbar-track {
    background: transparent;
}
</style>

<style>
body.media-lightbox-open,
body.image-preview-open {
    overflow: hidden;
    overscroll-behavior: none;
    touch-action: manipulation;
}

body.media-lightbox-open .product-media-lightbox,
body.image-preview-open .product-image-preview-modal {
    touch-action: manipulation;
}

@media (max-width: 640px) {
    .whatsapp-btn {
        bottom: 168px !important;
    }
}
</style>