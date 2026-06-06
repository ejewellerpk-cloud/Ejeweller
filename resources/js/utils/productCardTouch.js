/**
 * Product card touch + swiper behaviour.
 *
 * Tap      → open product detail
 * Swipe    → change image / video slide (handled by Swiper)
 * Scroll   → page scrolls vertically (no navigation)
 */

export const TAP_SLOP_PX = 12;
export const SCROLL_SLOP_PX = 20;
export const SWIPE_SLOP_PX = 14;
export const TOUCH_NAV_DEDUPE_MS = 400;

/** Nested swiper inside homepage product rows — vertical scroll stays native. */
export const productCardSwiperProps = {
    nested: true,
    followFinger: true,
    touchRatio: 1,
    touchAngle: 30,
    threshold: 6,
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
    return Boolean(target?.closest?.('button, .swiper-pagination'));
}

export function readTouchPoint(event) {
    const touch = event.changedTouches?.[0] || event.touches?.[0];
    if (!touch) {
        return null;
    }
    return { x: touch.clientX, y: touch.clientY };
}

export function createTouchSession(startPoint) {
    return {
        startX: startPoint.x,
        startY: startPoint.y,
        sliderDragged: false,
    };
}

export function markSliderDragged(session) {
    if (session) {
        session.sliderDragged = true;
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

    if (session.sliderDragged || (absX >= SWIPE_SLOP_PX && absX > absY)) {
        return 'swipe';
    }

    if (absY >= SCROLL_SLOP_PX && absY > absX) {
        return 'scroll';
    }

    if (absX <= TAP_SLOP_PX && absY <= TAP_SLOP_PX) {
        return 'tap';
    }

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
