/** Debounce window for touchend + synthetic click on iOS Safari. */
const DUPLICATE_MS = 450;

function markTap(target) {
    if (target?.dataset) {
        target.dataset.instantTapAt = String(Date.now());
    }
}

function shouldSkipClick(target) {
    const last = Number(target?.dataset?.instantTapAt || 0);
    return last > 0 && Date.now() - last < DUPLICATE_MS;
}

/** Router navigation — touchend fires immediately on iOS; click is fallback for desktop. */
export function onInstantNavigate(event, navigate) {
    if (!event?.currentTarget || typeof navigate !== 'function') {
        return;
    }

    if (event.type === 'touchend') {
        markTap(event.currentTarget);
        // Call navigate() without the event — guardEvent aborts when defaultPrevented is true.
        navigate();
        if (event.cancelable) {
            event.preventDefault();
        }
        return;
    }

    if (event.type === 'click') {
        if (shouldSkipClick(event.currentTarget)) {
            event.preventDefault();
            return;
        }
        navigate(event);
    }
}

/** Buttons / canvas toggles — same instant response on iOS. */
export function onInstantAction(event, action) {
    if (!event?.currentTarget || typeof action !== 'function') {
        return;
    }

    if (event.type === 'touchend') {
        event.preventDefault();
        markTap(event.currentTarget);
        action(event);
        return;
    }

    if (event.type === 'click') {
        if (shouldSkipClick(event.currentTarget)) {
            event.preventDefault();
            return;
        }
        action(event);
    }
}
