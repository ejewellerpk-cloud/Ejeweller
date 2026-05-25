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
