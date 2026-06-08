/** Product detail page — main gallery + lightbox swipers (mobile swipe friendly). */

export const productGalleryMainSwiperProps = {
    followFinger: true,
    touchRatio: 1,
    touchAngle: 40,
    threshold: 5,
    touchStartPreventDefault: false,
    passiveListeners: true,
    touchReleaseOnEdges: true,
    resistanceRatio: 0.85,
    longSwipes: true,
    longSwipesMs: 240,
    shortSwipes: true,
    preventInteractionOnTransition: false,
    simulateTouch: true,
    allowTouchMove: true,
    grabCursor: true,
    watchOverflow: true,
    slideToClickedSlide: false,
};

export const productGalleryLightboxSwiperProps = {
    ...productGalleryMainSwiperProps,
    nested: true,
};

export function connectGalleryThumbs(mainSwiper, thumbsSwiper) {
    if (!mainSwiper?.thumbs || !thumbsSwiper || thumbsSwiper.destroyed || !thumbsSwiper.el) {
        return;
    }
    try {
        mainSwiper.thumbs.swiper = thumbsSwiper;
        mainSwiper.thumbs.init();
        mainSwiper.thumbs.update();
    } catch (e) {}
}

export function getGalleryClickedIndex(swiper) {
    if (!swiper) {
        return -1;
    }

    if (swiper.clickedSlide) {
        const loopIndex = swiper.clickedSlide.getAttribute('data-swiper-slide-index');
        if (loopIndex !== null && loopIndex !== '') {
            const parsed = parseInt(loopIndex, 10);
            if (Number.isFinite(parsed)) {
                return parsed;
            }
        }
    }

    if (typeof swiper.clickedIndex === 'number' && swiper.clickedIndex >= 0) {
        return swiper.clickedIndex;
    }

    if (typeof swiper.realIndex === 'number') {
        return swiper.realIndex;
    }

    return -1;
}
