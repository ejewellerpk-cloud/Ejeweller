/**
 * Homepage Swiper touch configuration.
 * Vertical page scroll stays native; horizontal swipes move carousels smoothly on mobile.
 */

export const HOMEPAGE_ROW_SWIPER_SPEED = 550;
export const HOMEPAGE_HERO_SWIPER_SPEED = 600;

const scrollFriendlyTouch = {
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

/** Categories, brands, outlets, promotions */
export const homepageRowSwiperProps = {
    ...scrollFriendlyTouch,
};

/** Product rows that contain nested card image swipers */
export const homepageProductRowSwiperProps = {
    ...scrollFriendlyTouch,
    threshold: 12,
    touchAngle: 45,
};

/** Hero banner slider */
export const homepageHeroSwiperProps = {
    ...scrollFriendlyTouch,
    touchAngle: 55,
    threshold: 10,
};

/** Reviews continuous marquee slider */
export const homepageReviewsSwiperProps = {
    ...scrollFriendlyTouch,
    grabCursor: true,
    threshold: 10,
};
