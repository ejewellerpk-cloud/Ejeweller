import { createStore } from "vuex";
import createPersistedState from "vuex-persistedstate";

import { auth } from "./modules/auth";
import { socialLogin } from "./modules/socialLogin";
import { frontendAddress } from "./modules/frontend/frontendAddress";
import { frontendBenefit } from "./modules/frontend/frontendBenefit";
import { frontendCart } from "./modules/frontend/frontendCart";
import { frontendCountryCode } from "./modules/frontend/frontendCountryCode";
import { frontendCountryStateCity } from "./modules/frontend/frontendCountryStateCity";
import { frontendCoupon } from "./modules/frontend/frontendCoupon";
import { frontendEditProfile } from "./modules/frontend/frontendEditProfile";
import { frontendLanguage } from "./modules/frontend/frontendLanguage";
import { frontendOrder } from "./modules/frontend/frontendOrder";
import { frontendOrderArea } from "./modules/frontend/frontendOrderArea";
import { frontendOutlet } from "./modules/frontend/frontendOutlet";
import { frontendOverview } from "./modules/frontend/frontendOverview";
import { frontendPage } from "./modules/frontend/frontendPage";
import { frontendPaymentGateway } from "./modules/frontend/frontendPaymentGateway";
import { frontendProduct } from "./modules/frontend/frontendProduct";
import { frontendProductBrand } from "./modules/frontend/frontendProductBrand";
import { frontendProductCategory } from "./modules/frontend/frontendProductCategory";
import { frontendProductReview } from "./modules/frontend/frontendProductReview";
import { frontendProductSection } from "./modules/frontend/frontendProductSection";
import { frontendProductVariation } from "./modules/frontend/frontendProductVariation";
import { frontendPromotion } from "./modules/frontend/frontendPromotion";
import { frontendReturnAndRefund } from "./modules/frontend/frontendReturnAndRefund";
import { frontendReturnReason } from "./modules/frontend/frontendReturnReason";
import { frontendSocialLogin } from "./modules/frontend/frontendSocialLogin";
import { frontendSetting } from "./modules/frontend/frontendSetting";
import { frontendSignup } from "./modules/frontend/frontendSignup";
import { frontendSlider } from "./modules/frontend/frontendSlider";
import { frontendWishlist } from "./modules/frontend/frontendWishlist";
import { globalState } from "./modules/frontend/globalState";

/**
 * Storefront-only Vuex store — excludes ~80 admin/POS modules so the customer
 * bundle does not parse/register them on every page load.
 */
export default createStore({
    state: {},
    mutations: {},
    actions: {},
    modules: {
        auth,
        socialLogin,
        globalState,
        frontendSetting,
        frontendLanguage,
        frontendEditProfile,
        frontendCountryCode,
        frontendPage,
        frontendSlider,
        frontendProductCategory,
        frontendProduct,
        frontendBenefit,
        frontendPromotion,
        frontendProductSection,
        frontendWishlist,
        frontendProductVariation,
        frontendAddress,
        frontendSignup,
        frontendCart,
        frontendCoupon,
        frontendSocialLogin,
        frontendPaymentGateway,
        frontendOrder,
        frontendOrderArea,
        frontendReturnAndRefund,
        frontendReturnReason,
        frontendOverview,
        frontendProductReview,
        frontendProductBrand,
        frontendOutlet,
        frontendCountryStateCity,
    },
    plugins: [
        createPersistedState({
            paths: ["auth", "globalState", "frontendCart"],
        }),
    ],
});
