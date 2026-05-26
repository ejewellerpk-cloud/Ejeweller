import HeroSliderSkeleton from './HeroSliderSkeleton.vue';
import CategoryCarouselSkeleton from './CategoryCarouselSkeleton.vue';
import ProductGridSkeleton from './ProductGridSkeleton.vue';
import PromotionBannerSkeleton from './PromotionBannerSkeleton.vue';
import BrandStripSkeleton from './BrandStripSkeleton.vue';
import ReviewsSectionSkeleton from './ReviewsSectionSkeleton.vue';
import BenefitsStripSkeleton from './BenefitsStripSkeleton.vue';
import StripSkeleton from './StripSkeleton.vue';
import ProductDetailSkeleton from './ProductDetailSkeleton.vue';
import AccountPageSkeleton from './AccountPageSkeleton.vue';
import CheckoutPageSkeleton from './CheckoutPageSkeleton.vue';
import FormPageSkeleton from './FormPageSkeleton.vue';
import OrderListSkeleton from './OrderListSkeleton.vue';
import PageContentSkeleton from './PageContentSkeleton.vue';
import AppShellSkeleton from './AppShellSkeleton.vue';
import ProductCardSkeleton from './ProductCardSkeleton.vue';

/** Maps LoadingComponent skeleton prop → layout component */
export const SKELETON_REGISTRY = {
    hero: HeroSliderSkeleton,
    categories: CategoryCarouselSkeleton,
    products: ProductGridSkeleton,
    'product-grid': ProductGridSkeleton,
    promotions: PromotionBannerSkeleton,
    brands: BrandStripSkeleton,
    reviews: ReviewsSectionSkeleton,
    benefits: BenefitsStripSkeleton,
    strip: StripSkeleton,
    'product-detail': ProductDetailSkeleton,
    account: AccountPageSkeleton,
    checkout: CheckoutPageSkeleton,
    form: FormPageSkeleton,
    'order-list': OrderListSkeleton,
    page: PageContentSkeleton,
    app: AppShellSkeleton,
    card: ProductCardSkeleton,
};

export const DEFAULT_INLINE_SKELETON = 'page';
export const DEFAULT_FULLSCREEN_SKELETON = 'app';
