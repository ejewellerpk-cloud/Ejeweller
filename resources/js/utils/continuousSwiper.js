/** Shared helpers for marquee-style Swiper sliders (autoplay loop + manual swipe). */

export const CONTINUOUS_SWIPER_SPEED = 4500;
export const MANUAL_SWIPER_SPEED = 380;

export const continuousAutoplayConfig = {
    delay: 0,
    disableOnInteraction: false,
    pauseOnMouseEnter: false,
    stopOnLastSlide: false,
    waitForTransition: false,
};

/** Smooth related-products carousel (not infinite marquee). */
export const RELATED_PRODUCTS_SWIPER_SPEED = 650;
export const RELATED_PRODUCTS_AUTOPLAY_DELAY = 3800;

export const relatedProductsAutoplayConfig = {
    delay: RELATED_PRODUCTS_AUTOPLAY_DELAY,
    disableOnInteraction: false,
    pauseOnMouseEnter: true,
    stopOnLastSlide: false,
    waitForTransition: true,
};

/** Lets the page scroll vertically; only clearly horizontal swipes move the slider. */
export const touchFriendlySwiperProps = {
    followFinger: true,
    touchRatio: 1,
    touchAngle: 50,
    threshold: 8,
    touchStartPreventDefault: false,
    passiveListeners: true,
    touchReleaseOnEdges: true,
    resistanceRatio: 0.82,
    longSwipes: true,
    longSwipesMs: 250,
    shortSwipes: true,
    preventInteractionOnTransition: false,
    simulateTouch: true,
    allowTouchMove: true,
};

export function pauseContinuousSwiper(swiper) {
    if (!swiper) {
        return;
    }
    swiper.autoplay?.stop();
    swiper.params.speed = MANUAL_SWIPER_SPEED;
    if (swiper.wrapperEl) {
        swiper.wrapperEl.style.transitionTimingFunction = 'ease-out';
    }
}

export function resumeContinuousSwiper(swiper) {
    if (!swiper) {
        return;
    }
    swiper.params.speed = CONTINUOUS_SWIPER_SPEED;
    if (swiper.wrapperEl) {
        swiper.wrapperEl.style.transitionTimingFunction = 'linear';
    }
    const autoplay = swiper.autoplay;
    if (autoplay) {
        autoplay.stop();
        autoplay.start();
    }
}

export function pauseRelatedProductsSwiper(swiper) {
    if (!swiper) {
        return;
    }
    if (swiper._relatedResumeTimer) {
        clearTimeout(swiper._relatedResumeTimer);
        swiper._relatedResumeTimer = null;
    }
    swiper.autoplay?.stop();
}

export function resumeRelatedProductsSwiper(swiper, delayMs = 2800) {
    if (!swiper?.autoplay) {
        return;
    }
    if (swiper._relatedResumeTimer) {
        clearTimeout(swiper._relatedResumeTimer);
    }
    swiper._relatedResumeTimer = setTimeout(() => {
        swiper.autoplay?.start();
        swiper._relatedResumeTimer = null;
    }, delayMs);
}
