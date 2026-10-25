import { reactive } from "vue";

/** Shared flash-sale topbar state (product page → navbar). */
export const flashSaleTopBar = reactive({
    visible: false,
    days: "00",
    hours: "00",
    minutes: "00",
    seconds: "00",
});

export function setFlashSaleTopBar(timeLeft) {
    if (!timeLeft) {
        clearFlashSaleTopBar();
        return;
    }
    flashSaleTopBar.visible = true;
    flashSaleTopBar.days = timeLeft.days;
    flashSaleTopBar.hours = timeLeft.hours;
    flashSaleTopBar.minutes = timeLeft.minutes;
    flashSaleTopBar.seconds = timeLeft.seconds;
}

export function clearFlashSaleTopBar() {
    flashSaleTopBar.visible = false;
    flashSaleTopBar.days = "00";
    flashSaleTopBar.hours = "00";
    flashSaleTopBar.minutes = "00";
    flashSaleTopBar.seconds = "00";
}
