<template>
    <LoadingComponent :props="loading" />
    <section class="mb-12">
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
                    <span v-if="product.is_offer && discountPercentageDetail() > 0" 
                        class="absolute top-4 left-4 z-20 bg-primary text-white text-[11px] sm:text-xs font-extrabold px-3 py-1.5 rounded-full shadow-[0_4px_12px_rgba(255,92,0,0.25)] flex items-center gap-1 animate-pulse">
                        <i class="fa-solid fa-tags text-[10px]"></i>
                        SAVE {{ discountPercentageDetail() }}%
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

                    <Swiper dir="ltr" :spaceBetween="10" :navigation="true" :pagination="{ clickable: true }" :thumbs="{ swiper: thumbsSwiper }"
                        :modules="modules" :loop="true" class="gallery-swiper mb-4">
                        <SwiperSlide v-for="(media, index) in combinedMedia" :key="'media-' + index" class="w-full flex items-center justify-center bg-black rounded-2xl overflow-hidden aspect-square" style="aspect-ratio: 1/1;">
                            <template v-if="media.type === 'image'">
                                <div @click="handleImageClick(index)" @dblclick="toggleZoom(index)"
                                    style="touch-action: manipulation;"
                                    class="w-full h-full relative overflow-hidden flex items-center justify-center select-none cursor-pointer">
                                    <img :src="media.url" alt="product" 
                                        :class="zoomedIndex === index ? 'scale-[2.2] cursor-zoom-out z-30' : 'scale-100 cursor-zoom-in'"
                                        class="w-full h-full object-cover transition-transform duration-300 ease-out origin-center" />
                                </div>
                            </template>
                            <template v-else-if="media.type === 'video'">
                                <iframe v-if="media.data.video_provider === 5 || media.data.video_provider === 10 || media.data.video_provider === 15" 
                                    :src="formatVideoLink(media.data)" class="w-full h-full pointer-events-none" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                                <video v-else :src="media.data.link" autoplay="true" muted="true" loop="true" playsinline="true" webkit-playsinline="true" class="w-full h-full object-cover"></video>
                            </template>
                        </SwiperSlide>
                    </Swiper>

                    <Swiper v-if="combinedMedia.length > 1" dir="ltr" @swiper="setThumbsSwiper" :spaceBetween="12" :slidesPerView="4" :freeMode="true"
                        :watchSlidesProgress="true" :modules="modules" class="thumb-swiper hidden sm:block">
                        <SwiperSlide v-for="(media, index) in combinedMedia" :key="'thumb-media-' + index"
                            @mouseover="thumbsSwiper ? thumbsSwiper.slideTo(index) : null"
                            class="w-full cursor-pointer rounded-lg border border-gray-200 transition-all duration-500 bg-black flex items-center justify-center aspect-square relative" style="aspect-ratio: 1/1;">
                            <template v-if="media.type === 'image'">
                                <img class="w-full h-full rounded-lg border-2 border-gray-200 transition-all duration-500 object-cover"
                                    :src="media.url" alt="gallery" />
                            </template>
                            <template v-else-if="media.type === 'video'">
                                <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-45 rounded-lg z-10">
                                    <i class="fa-solid fa-play text-white text-base"></i>
                                </div>
                                <!-- YouTube Video Thumbnail -->
                                <template v-if="media.data.video_provider === 5">
                                    <img class="w-full h-full rounded-lg border-2 border-gray-200 object-cover"
                                        :src="getVideoThumbnail(media)" alt="video thumbnail" />
                                </template>
                                <!-- Self-hosted Video (renders first frame natively) -->
                                <template v-else>
                                    <video :src="media.data.link" preload="metadata" class="w-full h-full rounded-lg border-2 border-gray-200 object-cover pointer-events-none"></video>
                                </template>
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
                    <span v-if="product.is_offer && discountPercentageDetail() > 0" 
                        class="absolute top-4 left-4 z-20 bg-primary text-white text-[11px] sm:text-xs font-extrabold px-3 py-1.5 rounded-full shadow-[0_4px_12px_rgba(255,92,0,0.25)] flex items-center gap-1 animate-pulse">
                        <i class="fa-solid fa-tags text-[10px]"></i>
                        SAVE {{ discountPercentageDetail() }}%
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
                    <div @click="handleImageClick(999)" @dblclick="toggleZoom(999)"
                        style="touch-action: manipulation;"
                        class="w-full h-full relative overflow-hidden flex items-center justify-center select-none cursor-pointer rounded-2xl">
                        <img :src="product.image" alt="products" 
                            :class="zoomedIndex === 999 ? 'scale-[2.2] cursor-zoom-out z-30' : 'scale-100 cursor-zoom-in'"
                            class="w-full h-full object-cover transition-transform duration-300 ease-out origin-center rounded-2xl" />
                    </div>
                </div>

                <div class="col-12 sm:col-6 lg:col-7 lg:pl-10">
                    <!-- Premium Interactive Price & Offer Row (Container styling removed as requested) -->
                    <div class="mb-6">
                        <div class="flex flex-nowrap items-center justify-between gap-2 sm:gap-4 w-full">
                            <!-- Left: Price and Discount Pill -->
                            <div class="flex flex-nowrap items-baseline gap-2 sm:gap-3 shrink-0">
                                <span class="text-4xl min-[360px]:text-5xl sm:text-6xl font-black text-primary tracking-tight whitespace-nowrap shrink-0">
                                    {{
                                        currencyFormat(temp.price, setting.site_digit_after_decimal_point,
                                            setting.site_default_currency_symbol, setting.site_currency_position)
                                    }}
                                </span>
                                <div class="flex flex-nowrap items-baseline gap-1.5 sm:gap-2 shrink-0" v-if="product.is_offer">
                                    <del class="text-base min-[360px]:text-lg sm:text-xl font-medium text-gray-400 line-through whitespace-nowrap shrink-0">
                                        {{
                                            currencyFormat(temp.oldPrice, setting.site_digit_after_decimal_point,
                                                setting.site_default_currency_symbol, setting.site_currency_position)
                                        }}
                                    </del>
                                    <span v-if="discountPercentageDetail() > 0" 
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs min-[360px]:text-sm sm:text-sm font-black bg-red-100 text-red-600 animate-pulse whitespace-nowrap shrink-0">
                                        {{ discountPercentageDetail() }}% OFF
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

                    <h2 class="text-2xl sm:text-3xl font-bold capitalize text-heading mb-6">{{ product.name }}</h2>

                    <!-- Etsy-Style Shipping, Delivery, Rating & Fees Row -->
                    <div class="grid grid-cols-3 gap-2 border-y border-gray-100 py-4 my-6 text-center text-xs sm:text-sm">
                        <!-- 1. Star Ratings Column -->
                        <div @click="scrollToReviews" class="flex flex-col items-center justify-center border-r border-gray-100 px-1 cursor-pointer hover:opacity-85 transition-opacity">
                            <div class="flex items-center gap-1 mb-1">
                                <span class="text-sm font-black text-gray-900">{{ product.rating_star_count > 0 ? (product.rating_star / product.rating_star_count).toFixed(1) : '5.0' }}</span>
                                <i class="fa-solid fa-star text-[#FFBC1F] text-xs"></i>
                            </div>
                            <span class="text-[11px] text-gray-500 hover:text-primary cursor-pointer font-bold whitespace-nowrap">
                                ({{ product.rating_star_count }} {{ product.rating_star_count > 1 ? $t('label.reviews') : $t('label.review') }})
                            </span>
                        </div>
                        
                        <!-- 2. Dynamic Estimated Delivery Column -->
                        <div class="flex flex-col items-center justify-center border-r border-gray-100 px-1">
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



                    <VariationComponent v-if="initialVariations.length > 0 && variationComponent"
                        :method="selectedVariationMethod" :variations="initialVariations" />

                    <dl class="flex flex-wrap items-center gap-x-6 gap-y-3 mb-8">
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

                    <dl v-if="temp.quantity > 1" class="flex flex-wrap items-center gap-x-6 gap-y-3 mb-8">
                        <dt class="capitalize text-lg font-semibold">{{ $t('label.total_price') }}:</dt>
                        <dd class="flex items-center gap-6 text-green-500 font-semibold text-lg">
                            {{
                                currencyFormat(temp.totalPrice, setting.site_digit_after_decimal_point,
                                    setting.site_default_currency_symbol, setting.site_currency_position)
                            }}
                        </dd>
                    </dl>

                    <div class="flex flex-row items-center gap-4 mb-10">
                        <button @click.prevent="addToCart" :disabled="enableAddToCardButton" type="button"
                            :class="enableAddToCardButton === false ? 'shadow-btn-primary !bg-primary' : 'bg-slate-400'"
                            class="flex-1 sm:flex-none h-12 px-5 sm:px-8 rounded-full text-white font-bold flex items-center justify-center gap-2.5 transition-all duration-300 active:scale-[0.98]">
                            <i class="lab-line-bag text-lg"></i>
                            <span class="whitespace-nowrap text-xs sm:text-sm">{{ $t("button.add_to_cart") }}</span>
                        </button>
                        <button @click.prevent="buyNow" :disabled="enableAddToCardButton" type="button"
                            :class="enableAddToCardButton === false ? 'shadow-[0_4px_15px_rgba(220,38,38,0.3)] bg-red-600 hover:bg-red-700 hover:scale-[1.02]' : 'bg-slate-400'"
                            class="flex-1 sm:flex-none h-12 px-5 sm:px-10 rounded-full text-white font-extrabold flex items-center justify-center gap-2.5 transition-all duration-300 active:scale-[0.98]">
                            <i class="fa-solid fa-bolt text-lg text-yellow-300 animate-pulse"></i>
                            <span class="whitespace-nowrap text-xs sm:text-sm">{{ $t("button.buy_now") || 'Buy Now' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section :class="relatedProducts.length > 0 ? 'mb-12' : 'mb-24'">
        <div class="container">
            <div class="row">
                <div class="col-12 flex flex-col gap-8">
                    <!-- Details Section -->
                    <div class="rounded-[32px] border border-[#D9DBE9] bg-white p-6 sm:p-8">
                        <h3 class="capitalize text-2xl sm:text-3xl font-bold mb-6 flex items-center gap-3 text-heading border-b border-gray-100 pb-4">
                            <i class="lab-line-document text-primary text-2xl sm:text-3xl"></i>
                            {{ $t('label.product_details') }}
                        </h3>
                        <div class="text-description text-base text-gray-700 leading-relaxed" v-html="product.details"></div>
                    </div>



                    <!-- Reviews Section -->
                    <div id="product-reviews-section" class="rounded-[32px] border border-[#D9DBE9] bg-white p-6 sm:p-8">
                        <h3 class="capitalize text-2xl sm:text-3xl font-bold mb-6 flex items-center gap-3 text-heading border-b border-gray-100 pb-4">
                            <i class="lab-line-star text-primary text-2xl sm:text-3xl"></i>
                            {{ $t('label.product_reviews') }}
                        </h3>
                        
                        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 mb-8 pb-6">
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
                                    <img v-for="(reviewImage, imgIndex) in review.images" :key="imgIndex" :src="reviewImage" alt="review image"
                                        class="w-20 h-20 object-cover rounded-xl cursor-pointer hover:opacity-85 transition-all duration-300 border border-gray-200" 
                                        @click="previewImage(reviewImage)">
                                </div>
                            </div>

                            <button v-if="product.rating_star_count > reviews.length" @click.prevent="readMore"
                                type="button" class="flex items-center justify-center gap-2 w-fit mx-auto mt-8 py-2.5 px-6 rounded-full border border-primary text-primary font-bold hover:bg-primary/5 transition-all duration-300">
                                <span class="capitalize text-base">{{ $t('label.read_more') }}</span>
                                <i class="lab-line-down-arrow text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Shipping and Return Section -->
                    <div class="rounded-[32px] border border-[#D9DBE9] bg-white p-6 sm:p-8">
                        <h3 class="capitalize text-2xl sm:text-3xl font-bold mb-6 flex items-center gap-3 text-heading border-b border-gray-100 pb-4">
                            <i class="lab-line-truck text-primary text-2xl sm:text-3xl"></i>
                            {{ $t('label.product_shipping_and_return') }}
                        </h3>
                        <div class="text-description text-base text-gray-700 leading-relaxed" v-html="product.shipping_and_return"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section v-if="relatedProducts.length > 0" class="mb-24 sm:mb-20">
        <div class="container">
            <div class="flex items-center justify-between gap-4 mb-5 sm:mb-7">
                <h2 class="text-2xl sm:text-4xl font-bold capitalize">
                    {{ $t('label.related_products') }}
                </h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-1.5 sm:gap-6">
                <ProductListComponent v-if="relatedProducts.length > 0" :products="relatedProducts" />
            </div>
        </div>
    </section>

     <div id="imagePreviewModal" class="modal flex items-center"  @click="hidePreviewImage">
            <div class="max-w-lg w-full mx-auto relative ">
                        <button 
                            @click="hidePreviewImage" 
                            class="absolute top-2 right-2 text-white bg-black bg-opacity-50 rounded-full w-8 h-8 hover:bg-opacity-75"
                        >  ✕</button>
                <img data-modal="imagePreviewModal" :src="previewImg" alt="return" class="w-full h-full rounded-lg object-cover" />
            </div>
    </div>

    <div class="fixed bottom-[78px] left-4 right-4 z-20 p-3 bg-white rounded-2xl border border-gray-100 shadow-[0_4px_20px_rgba(0,0,0,0.08)] sm:hidden flex items-center justify-between gap-3">
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
            <button @click.prevent="addToCart" :disabled="enableAddToCardButton" type="button"
                :class="enableAddToCardButton === false ? 'bg-primary shadow-btn-primary' : 'bg-slate-400'"
                class="flex-1 h-11 px-1.5 rounded-full text-white font-bold flex items-center justify-center gap-1 active:scale-[0.98] transition-all duration-300 text-[10px] min-[375px]:text-xs whitespace-nowrap">
                <i class="lab-line-bag text-sm font-bold"></i>
                <span>{{ $t("button.add_to_cart") }}</span>
            </button>
            <button @click.prevent="buyNow" :disabled="enableAddToCardButton" type="button"
                :class="enableAddToCardButton === false ? 'animate-flash-buy' : 'bg-slate-400'"
                class="flex-1 h-11 px-1 rounded-full text-white font-extrabold flex items-center justify-center gap-1 active:scale-[0.98] transition-all duration-300 whitespace-nowrap">
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
import { ref } from "vue";
import { Swiper, SwiperSlide } from 'swiper/vue';
import { FreeMode, Navigation, Thumbs, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/thumbs';
import LoadingComponent from "../components/LoadingComponent";
import starRating from "vue-star-rating";
import targetService from "../../../services/targetService";
import router from "../../../router";
import CategoryBreadcrumbComponent from "../components/CategoryBreadcrumbComponent";
import ProductListComponent from "../components/ProductListComponent";
import VariationComponent from "../components/VariationComponent";
import appService from "../../../services/appService";
import alertService from "../../../services/alertService";
import { useHead } from '@vueuse/head';
import { pixelService } from "../../../services/pixelService";
import 'vue-inner-image-zoom/lib/vue-inner-image-zoom.css';

import InnerImageZoom from 'vue-inner-image-zoom';

export default {
    name: "ProductDetailsComponent",
    components: {
        VariationComponent,
        ProductListComponent,
        CategoryBreadcrumbComponent,
        starRating,
        Swiper,
        SwiperSlide,
        LoadingComponent,
        'inner-image-zoom': InnerImageZoom
    },
    setup() {
        const thumbsSwiper = ref(null);
        const setThumbsSwiper = (swiper) => {
            thumbsSwiper.value = swiper;
        };
        return {
            thumbsSwiper,
            setThumbsSwiper,
            modules: [FreeMode, Navigation, Thumbs, Pagination],
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
            enableAddToCardButton: false,
            selectedVariation: null,
            productArray: {},
            variationComponent: false,
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
            previewImg:null,
            shareUrl: "",
            copyText: "Copy",
            zoomedIndex: null,
            lastTap: 0,
            animatingWishlist: false,
            tickerIndex: 0,
            tickerInterval: null,
            soldCount: 0,
            badgeIndex: 0,
            badgeInterval: null,
            localWishlist: JSON.parse(localStorage.getItem('local_wishlist') || '[]')
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
        relatedProducts: function () {
            return this.$store.getters["frontendProduct/relatedProducts"];
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
    },
    mounted() {
        this.show();
        this.showRelatedProduct();
        this.tickerInterval = setInterval(() => {
            if (this.discountPercentageDetail() > 0) {
                this.tickerIndex++;
            }
        }, 2200);
        this.badgeInterval = setInterval(() => {
            this.badgeIndex++;
        }, 3000);
    },
    beforeUnmount() {
        if (this.tickerInterval) {
            clearInterval(this.tickerInterval);
        }
        if (this.badgeInterval) {
            clearInterval(this.badgeInterval);
        }
    },
    methods: {
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
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },
        getVideoThumbnail: function (media) {
            if (!media || !media.data || !media.data.link) {
                return this.product.image;
            }
            const link = media.data.link;
            if (media.data.video_provider === 5) {
                const ytId = this.getYouTubeId(link);
                if (ytId) {
                    return `https://img.youtube.com/vi/${ytId}/hqdefault.jpg`;
                }
            }
            return this.product.image;
        },
        toggleZoom: function (index) {
            if (this.zoomedIndex === index) {
                this.zoomedIndex = null;
            } else {
                this.zoomedIndex = index;
            }
        },
        handleImageClick: function (index) {
            const now = new Date().getTime();
            const timespan = now - this.lastTap;
            if (timespan < 300 && timespan > 0) {
                this.toggleZoom(index);
                this.lastTap = 0;
            } else {
                this.lastTap = now;
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
                this.localWishlist = localWish; // triggers reactivity
            }
        },
        readMore: function () {
            this.props.search.review_limit += 1;
            this.show();
        },
        previewImage: function (img) {
            this.previewImg = img;
            appService.modalShow('#imagePreviewModal');
        },
        hidePreviewImage: function () {
             appService.modalHide('#imagePreviewModal');
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
        show: function () {
            if (typeof this.$route.params.slug !== "undefined") {
                this.loading.isActive = true;
                this.props.search.slug = this.$route.params.slug;
                this.$store.dispatch("frontendProduct/show", this.props.search).then((res) => {
                    this.initProduct = {
                        isVariation: false,
                        variationId: null,
                        sku: res.data.data.sku,
                        stock: res.data.data.stock,
                        quantity: 1,
                        discount: 0,
                        price: res.data.data.price,
                        oldPrice: res.data.data.old_price,
                        totalPrice: res.data.data.price,
                        maximum_purchase_quantity: res.data.data.maximum_purchase_quantity
                    };
                    this.temp = {
                        name: res.data.data.name,
                        image: res.data.data.image,
                        isVariation: false,
                        variationId: null,
                        productId: res.data.data.id,
                        sku: res.data.data.sku,
                        stock: res.data.data.stock,
                        taxes: res.data.data.taxes,
                        shipping: res.data.data.shipping,
                        quantity: 1,
                        discount: 0,
                        price: res.data.data.price,
                        oldPrice: res.data.data.old_price,
                        totalPrice: res.data.data.price,
                        maximum_purchase_quantity: res.data.data.maximum_purchase_quantity
                    };

                    // Social Proof sold count initialization
                    const storageKey = 'sold_count_' + res.data.data.id;
                    let localCount = localStorage.getItem(storageKey);
                    if (!localCount) {
                        localCount = (res.data.data.id * 53) % 450 + 138;
                        localStorage.setItem(storageKey, localCount);
                    }
                    this.soldCount = parseInt(localCount);
                    pixelService.trackViewContent(res.data.data);

                    this.$store.dispatch("frontendProductCategory/ancestorsAndSelf", res.data.data.category_slug).then((categoryRes) => {
                        this.loading.isActive = false;
                    }).catch((err) => {
                        this.loading.isActive = false;
                    });

                    this.$store.dispatch("frontendProductVariation/initialVariation", res.data.data.id).then((initVariationRes) => {
                        if (initVariationRes.data.data.length > 0) {
                            this.variationComponent = true;
                        }

                        if (!initVariationRes.data.data.length && res.data.data.stock > 0) {
                            this.enableAddToCardButton = false;
                        }
                        this.loading.isActive = false;
                    }).catch((err) => {
                        this.loading.isActive = false;
                    });

                    if (Object.keys(res.data.data.seo) && res.data.data.seo.title && res.data.data.seo.description) {
                        let metaData = [
                            { name: 'title', content: res.data.data.seo.title },
                            { name: 'description', content: res.data.data.seo.description },
                        ];

                        if (res.data.data.seo.thumb && res.data.data.seo.cover) {
                            metaData.push({ content: res.data.data.seo.thumb });
                            metaData.push({ content: res.data.data.seo.cover });
                        }

                        useHead({
                            title: this.setting.company_name + ' - ' + res.data.data.seo.title,
                            meta: metaData
                        });
                    }
                }).catch((err) => {
                    this.loading.isActive = false;
                });
            }
        },
        showRelatedProduct: function () {
            if (typeof this.$route.params.slug !== "undefined") {
                this.loading.isActive = true;
                this.props.search.slug = this.$route.params.slug;
                this.$store.dispatch("frontendProduct/relatedProducts", {
                    slug: this.$route.params.slug,
                    rand: 8
                }).then((res) => {
                    this.loading.isActive = false;
                }).catch((err) => {
                    this.loading.isActive = false;
                });
            }
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

            if (variation) {
                this.selectedVariation = variation;

                this.temp.isVariation = true;
                this.temp.variationId = variation.id;
                this.temp.sku = variation.sku;
                this.temp.stock = variation.stock;
                this.temp.quantity = 1;
                this.temp.discount = 0;
                this.temp.price = variation.price;
                this.temp.oldPrice = variation.old_price;
                this.temp.totalPrice = variation.price;
                this.temp.maximum_purchase_quantity = variation.maximum_purchase_quantity;

                if (variation.stock > 0) {
                    this.enableAddToCardButton = false;
                }
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
        addToCart: function () {
            // Increment social proof sold count
            if (this.temp.productId) {
                const storageKey = 'sold_count_' + this.temp.productId;
                this.soldCount++;
                localStorage.setItem(storageKey, this.soldCount);
            }
            this.enableAddToCardButton = true;
            this.productArray = {
                name: this.temp.name,
                product_id: this.temp.productId,
                image: this.temp.image,
                variation_names: '',
                variation_id: this.temp.variationId,
                sku: this.temp.sku,
                stock: this.temp.stock,
                taxes: this.temp.taxes,
                shipping: this.temp.shipping,
                quantity: this.temp.quantity,
                discount: this.temp.discount,
                price: this.temp.price,
                old_price: this.temp.oldPrice,
                total_price: this.temp.totalPrice,
                maximum_purchase_quantity: this.temp.maximum_purchase_quantity
            }

            if (this.selectedVariation) {
                this.$store.dispatch("frontendProductVariation/ancestorsToString", this.selectedVariation.id).then((res) => {
                    this.productArray.variation_names = res.data.data;
                    this.variationComponent = false;
                    this.$store.dispatch("frontendCart/lists", this.productArray).then((res) => {
                        this.variationComponent = true;
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
                        this.variationComponent = true;
                        this.selectedVariation = null;
                        this.temp.stock = this.initProduct.stock;
                        this.temp.quantity = this.initProduct.quantity;
                    });
                }).catch((err) => {
                });
            } else {
                this.$store.dispatch("frontendCart/lists", this.productArray).then((res) => {
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
            // Increment social proof sold count
            if (this.temp.productId) {
                const storageKey = 'sold_count_' + this.temp.productId;
                this.soldCount++;
                localStorage.setItem(storageKey, this.soldCount);
            }
            this.enableAddToCardButton = true;
            this.productArray = {
                name: this.temp.name,
                product_id: this.temp.productId,
                image: this.temp.image,
                variation_names: '',
                variation_id: this.temp.variationId,
                sku: this.temp.sku,
                stock: this.temp.stock,
                taxes: this.temp.taxes,
                shipping: this.temp.shipping,
                quantity: this.temp.quantity,
                discount: this.temp.discount,
                price: this.temp.price,
                old_price: this.temp.oldPrice,
                total_price: this.temp.totalPrice,
                maximum_purchase_quantity: this.temp.maximum_purchase_quantity,
                skipCartDrawer: true
            }

            if (this.selectedVariation) {
                this.$store.dispatch("frontendProductVariation/ancestorsToString", this.selectedVariation.id).then((res) => {
                    this.productArray.variation_names = res.data.data;
                    this.variationComponent = false;
                    this.$store.dispatch("frontendCart/lists", this.productArray).then((res) => {
                        this.variationComponent = true;
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
                        this.variationComponent = true;
                        this.selectedVariation = null;
                        this.temp.stock = this.initProduct.stock;
                        this.temp.quantity = this.initProduct.quantity;
                    });
                }).catch((err) => {
                });
            } else {
                this.$store.dispatch("frontendCart/lists", this.productArray).then((res) => {
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
        discountPercentageDetail() {
            if (this.temp.oldPrice && this.temp.price && this.temp.oldPrice > this.temp.price) {
                return Math.round(((this.temp.oldPrice - this.temp.price) / this.temp.oldPrice) * 100);
            }
            return 0;
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
        }
    },
    watch: {
        $route() {
            this.show();
            this.showRelatedProduct();
        }
    }
}
</script>

<style scoped>
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
</style>

<style>
@media (max-width: 640px) {
    .whatsapp-btn {
        bottom: 168px !important;
    }
}
</style>