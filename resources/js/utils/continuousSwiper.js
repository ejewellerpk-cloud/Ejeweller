/**
 * Marquee-style Swiper helpers — continuous linear motion, direction control,
 * touch momentum, and desktop hover pause.
 */

export const MANUAL_SWIPER_SPEED = 380;
export const MARQUEE_RESUME_DELAY_MS = 3000;
export const MARQUEE_MAX_VISIBLE_SLIDES = 4;

export const continuousAutoplayConfig = {
    delay: 0,
    disableOnInteraction: true,
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

/** Minimum slide count for a seamless Swiper loop at the widest breakpoint. */
export function marqueeMinSlideCount(itemCount, maxVisible = MARQUEE_MAX_VISIBLE_SLIDES) {
    const safeCount = Math.max(Number(itemCount) || 0, 1);
    return Math.max(safeCount * 4, maxVisible * 5);
}

/** Repeat items until the carousel has enough slides for a seamless marquee loop. */
export function duplicateMarqueeSlides(items, minSlides = 16) {
    if (!Array.isArray(items) || !items.length) {
        return [];
    }
    let out = [...items];
    while (out.length < minSlides) {
        out = out.concat(items);
    }
    return out;
}

export function canRunMarqueeAutoplay(swiper) {
    return !!swiper
        && !swiper.destroyed
        && !swiper._marqueeTouchActive
        && !swiper._marqueeHoverPaused
        && !swiper._marqueeVisibilityPaused;
}

/** Keep marquee moving when Swiper loop fixes or transitions briefly stop autoplay. */
export function ensureMarqueeAutoplayRunning(swiper) {
    if (!canRunMarqueeAutoplay(swiper) || !swiper.autoplay) {
        return;
    }
    applyMarqueeLinearMotion(swiper);
    if (!swiper.autoplay.running) {
        swiper.autoplay.start();
    }
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

/** Pause marquee while the user is interacting — stays stopped until resume timer fires. */
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

/** Resume continuous motion after user inactivity with preserved / updated direction. */
export function resumeRelatedMarqueeTouch(swiper, { speed, direction, delayMs = MARQUEE_RESUME_DELAY_MS } = {}) {
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
        ensureMarqueeAutoplayRunning(swiper);
        swiper._marqueeResumeTimer = null;
    }, delayMs);
}

/** Pause when the section leaves the viewport. */
export function pauseRelatedMarqueeVisibility(swiper) {
    if (!swiper) {
        return;
    }
    swiper._marqueeVisibilityPaused = true;
    clearRelatedMarqueeTimers(swiper);
    swiper.autoplay?.stop();
}

/** Resume after the section re-enters the viewport. */
export function resumeRelatedMarqueeVisibility(swiper, { speed, direction } = {}) {
    if (!swiper || swiper.destroyed) {
        return;
    }
    swiper._marqueeVisibilityPaused = false;
    if (speed) {
        swiper.params.speed = speed;
    }
    applyMarqueeLinearMotion(swiper);
    if (direction) {
        applyMarqueeDirection(swiper, direction);
    }
    ensureMarqueeAutoplayRunning(swiper);
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
    ensureMarqueeAutoplayRunning(swiper);
}

export function destroyRelatedMarqueeSwiper(swiper) {
    clearRelatedMarqueeTimers(swiper);
    if (swiper) {
        swiper._marqueeHoverPaused = false;
        swiper._marqueeTouchActive = false;
        swiper._marqueeVisibilityPaused = false;
    }
}

export function supportsHoverPause() {
    if (typeof window === 'undefined' || !window.matchMedia) {
        return false;
    }
    return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
}
