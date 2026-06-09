/**
 * Marquee-style Swiper helpers — continuous linear motion, direction control,
 * touch momentum, and desktop hover pause.
 */

export const CONTINUOUS_SWIPER_SPEED = 4500;
export const MANUAL_SWIPER_SPEED = 380;
export const RELATED_PRODUCTS_DEFAULT_VELOCITY = 3800;

export const MIN_RELATED_MARQUEE_PRODUCTS = 3;
export const MIN_RELATED_MARQUEE_SLIDES = 12;

export const continuousAutoplayConfig = {
    delay: 0,
    disableOnInteraction: false,
    pauseOnMouseEnter: false,
    stopOnLastSlide: false,
    waitForTransition: false,
};

/** Related products premium marquee autoplay (delay: 0 = never stops between slides). */
export const relatedMarqueeAutoplayConfig = {
    delay: 0,
    disableOnInteraction: false,
    pauseOnMouseEnter: false,
    stopOnLastSlide: false,
    waitForTransition: false,
    reverseDirection: false,
};

/** @deprecated Use relatedMarqueeAutoplayConfig */
export const relatedProductsAutoplayConfig = relatedMarqueeAutoplayConfig;

/** @deprecated Use CONTINUOUS_SWIPER_SPEED */
export const RELATED_PRODUCTS_SWIPER_SPEED = CONTINUOUS_SWIPER_SPEED;

/** @deprecated Use RELATED_PRODUCTS_DEFAULT_VELOCITY */
export const RELATED_PRODUCTS_AUTOPLAY_DELAY = RELATED_PRODUCTS_DEFAULT_VELOCITY;

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

export function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

/**
 * Maps admin velocity (2000–10000 ms) to Swiper transition duration.
 * Higher admin value = slower movement. Responsive per breakpoint.
 */
export function resolveRelatedMarqueeSpeed(adminVelocityMs, viewportWidth = 1024) {
    const base = clamp(
        Number(adminVelocityMs) || RELATED_PRODUCTS_DEFAULT_VELOCITY,
        2000,
        10000,
    );

    if (viewportWidth >= 1024) {
        return Math.round(base * 1.35);
    }
    if (viewportWidth >= 768) {
        return Math.round(base * 1.05);
    }
    return Math.round(base * 0.92);
}

/**
 * Duplicate slides for a seamless infinite loop without visible resets.
 */
export function buildRelatedMarqueeSlides(products, minSlides = MIN_RELATED_MARQUEE_SLIDES) {
    const items = products || [];
    if (!items.length) {
        return [];
    }
    if (items.length >= minSlides) {
        return items;
    }
    const target = Math.max(minSlides, items.length * 3);
    let out = [];
    while (out.length < target) {
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

/** Home reviews marquee helpers */
export function pauseContinuousSwiper(swiper) {
    pauseRelatedMarqueeTouch(swiper);
}

export function resumeContinuousSwiper(swiper) {
    resumeRelatedMarqueeTouch(swiper, { speed: CONTINUOUS_SWIPER_SPEED, delayMs: 450 });
}

/** @deprecated */
export function pauseRelatedProductsSwiper(swiper) {
    pauseRelatedMarqueeTouch(swiper);
}

/** @deprecated */
export function resumeRelatedProductsSwiper(swiper, delayMs = 420) {
    resumeRelatedMarqueeTouch(swiper, { delayMs });
}

export function supportsHoverPause() {
    if (typeof window === 'undefined' || !window.matchMedia) {
        return false;
    }
    return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
}
