/**
 * Product card touch + swiper behaviour.
 *
 * Tap      → open product detail immediately
 * Swipe    → change image / video slide (only when slide actually changes or clear horizontal drag)
 * Scroll   → page scrolls vertically (no navigation)
 */

export const TAP_SLOP_PX = 14;
export const SCROLL_SLOP_PX = 24;
export const SWIPE_SLOP_PX = 18;
export const TOUCH_NAV_DEDUPE_MS = 400;

/** Nested swiper inside homepage product rows — vertical scroll stays native. */
export const productCardSwiperProps = {
    nested: true,
    followFinger: true,
    touchRatio: 1,
    touchAngle: 35,
    threshold: 12,
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
    slideToClickedSlide: false,
};

export function isFinePointerDevice() {
    return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
}

export function isInteractiveCardTarget(target) {
    return Boolean(target?.closest?.('button, a, .swiper-pagination, .product-card__quick-view'));
}

export function readTouchPoint(event) {
    const touch = event.changedTouches?.[0] || event.touches?.[0];
    if (!touch) {
        return null;
    }
    return { x: touch.clientX, y: touch.clientY };
}

export function createTouchSession(startPoint, startSlideIndex = null) {
    return {
        startX: startPoint.x,
        startY: startPoint.y,
        sliderDragged: false,
        startSlideIndex,
        endSlideIndex: null,
    };
}

export function markSliderDragged(session) {
    if (session) {
        session.sliderDragged = true;
    }
}

/** Capture final slide index; do not block navigation on micro-drags alone. */
export function noteSliderTouchEnd(session, endSlideIndex = null) {
    if (!session) {
        return;
    }
    if (endSlideIndex != null) {
        session.endSlideIndex = endSlideIndex;
    }
}

export function classifyTouchIntent(session, endPoint) {
    if (!session || !endPoint) {
        return 'unknown';
    }

    const dx = endPoint.x - session.startX;
    const dy = endPoint.y - session.startY;
    const absX = Math.abs(dx);
    const absY = Math.abs(dy);

    const slideChanged =
        session.startSlideIndex != null
        && session.endSlideIndex != null
        && session.startSlideIndex !== session.endSlideIndex;

    if (slideChanged) {
        return 'swipe';
    }

    if (absY >= SCROLL_SLOP_PX && absY > absX) {
        return 'scroll';
    }

    if (absX >= SWIPE_SLOP_PX && absX > absY) {
        return 'swipe';
    }

    // Tiny jitter / Swiper first-move without a real slide change = tap
    return 'tap';
}

export function shouldOpenProductFromTouch(session, endPoint) {
    return classifyTouchIntent(session, endPoint) === 'tap';
}

export function shouldSkipDuplicateClick(productId, navTimestamps) {
    const lastTouchNav = navTimestamps[productId];
    return lastTouchNav && Date.now() - lastTouchNav < TOUCH_NAV_DEDUPE_MS;
}

export function recordTouchNavigation(productId, navTimestamps) {
    navTimestamps[productId] = Date.now();
}
