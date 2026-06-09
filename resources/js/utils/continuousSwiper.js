/**
 * Marquee-style Swiper helpers — continuous linear motion, direction control,
 * touch momentum, and desktop hover pause.
 */

export const MANUAL_SWIPER_SPEED = 380;

export const continuousAutoplayConfig = {
    delay: 0,
    disableOnInteraction: false,
    pauseOnMouseEnter: false,
    stopOnLastSlide: false,
    waitForTransition: false,
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

/** Repeat items until the carousel has enough slides for a seamless marquee loop. */
export function duplicateMarqueeSlides(items, minSlides = 8) {
    if (!Array.isArray(items) || !items.length) {
        return [];
    }
    if (items.length >= minSlides) {
        return items;
    }
    let out = [];
    while (out.length < minSlides) {
        out = out.concat(items);
    }
    return out;
}

export function applyMarqueeLinearMotion(swiper) {
    if (swiper?.wrapperEl) {
        swiper.wrapperEl.style.transitionTimingFunction = 'linear';
    }
}

export function applyMarqueeDirection(swiper, direction = 'forward') {
    if (!swiper?.params?.autoplay) {
        return;
    }
    const reverse = direction === 'reverse';
    if (swiper.params.autoplay.reverseDirection === reverse) {
        return;
    }
    swiper.params.autoplay.reverseDirection = reverse;
    if (swiper.autoplay?.running) {
        swiper.autoplay.stop();
        swiper.autoplay.start();
    }
}

/**
 * Infer marquee direction from the user's last horizontal swipe.
 * forward  = content flows right → left (default)
 * reverse  = content flows left → right
 */
export function detectMarqueeDirectionFromTouch(swiper) {
    const diff = swiper?.touches?.diff ?? 0;
    if (Math.abs(diff) < 6) {
        return null;
    }
    return diff > 0 ? 'reverse' : 'forward';
}

export function clearRelatedMarqueeTimers(swiper) {
    if (swiper?._marqueeResumeTimer) {
        clearTimeout(swiper._marqueeResumeTimer);
        swiper._marqueeResumeTimer = null;
    }
}

export function configureRelatedMarqueeSwiper(swiper, { speed, direction = 'forward' } = {}) {
    if (!swiper) {
        return;
    }
    if (speed) {
        swiper.params.speed = speed;
    }
    applyMarqueeLinearMotion(swiper);
    applyMarqueeDirection(swiper, direction);
}

/** Brief slowdown while the finger is on screen — keeps tracking smooth. */
export function pauseRelatedMarqueeTouch(swiper) {
    if (!swiper) {
        return;
    }
    clearRelatedMarqueeTimers(swiper);
    swiper._marqueeTouchActive = true;
    swiper.params.speed = MANUAL_SWIPER_SPEED;
    applyMarqueeLinearMotion(swiper);
    swiper.autoplay?.stop();
}

/** Resume continuous motion quickly after touch with preserved / updated direction. */
export function resumeRelatedMarqueeTouch(swiper, { speed, direction, delayMs = 420 } = {}) {
    if (!swiper?.autoplay) {
        return;
    }
    clearRelatedMarqueeTimers(swiper);
    swiper._marqueeResumeTimer = setTimeout(() => {
        if (!swiper || swiper.destroyed) {
            return;
        }
        swiper._marqueeTouchActive = false;
        if (speed) {
            swiper.params.speed = speed;
        }
        applyMarqueeLinearMotion(swiper);
        if (direction) {
            applyMarqueeDirection(swiper, direction);
        }
        if (!swiper._marqueeHoverPaused) {
            swiper.autoplay.start();
        }
        swiper._marqueeResumeTimer = null;
    }, delayMs);
}

/** Desktop hover — pause at exact position. */
export function pauseRelatedMarqueeHover(swiper) {
    if (!swiper || swiper._marqueeTouchActive) {
        return;
    }
    swiper._marqueeHoverPaused = true;
    swiper.autoplay?.stop();
}

/** Desktop hover leave — resume from exact position. */
export function resumeRelatedMarqueeHover(swiper) {
    if (!swiper || !swiper._marqueeHoverPaused || swiper._marqueeTouchActive) {
        return;
    }
    swiper._marqueeHoverPaused = false;
    if (swiper.autoplay && !swiper.autoplay.running) {
        swiper.autoplay.start();
    }
}

export function destroyRelatedMarqueeSwiper(swiper) {
    clearRelatedMarqueeTimers(swiper);
    if (swiper) {
        swiper._marqueeHoverPaused = false;
        swiper._marqueeTouchActive = false;
    }
}

export function supportsHoverPause() {
    if (typeof window === 'undefined' || !window.matchMedia) {
        return false;
    }
    return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
}
