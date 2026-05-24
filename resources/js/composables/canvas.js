import { ref } from "vue";

export function useCanvas() { 
    const activeClass = ref('canvas-active')
    const hiddenClass = ref('overflow-hidden')

    function openCanvas(targetID) {
        console.log("canvas.js: openCanvas called for targetID:", targetID);
        const targetElements = document.querySelectorAll(`#${targetID}`);
        if (targetElements.length > 0) {
            targetElements.forEach(el => el.classList.add(activeClass.value));
            document.body.classList.add(hiddenClass.value);
            if (targetID === 'cart-canvas') {
                document.body.classList.add('cart-canvas-open');
            }
            console.log("canvas.js: Classes added successfully.");
        } else {
            console.error("canvas.js: targetElement NOT FOUND!");
        }
    }

    function closeCanvas(targetID) {
        console.log("canvas.js: closeCanvas called for targetID:", targetID);
        const targetElements = document.querySelectorAll(`#${targetID}`);
        if (targetElements.length > 0) {
            targetElements.forEach(el => el.classList.remove(activeClass.value));
            document.body.classList.remove(hiddenClass.value);
            if (targetID === 'cart-canvas') {
                document.body.classList.remove('cart-canvas-open');
            }
            console.log("canvas.js: Classes removed successfully.");
        }
    }

    function closeBackdrop(event) {
        const containerElement = event.currentTarget.firstElementChild
        const isWrapperElement = event.target.contains(containerElement)

        if(isWrapperElement) {
            event.currentTarget.classList.remove(activeClass.value)
            document.body.classList.remove(hiddenClass.value)
            if (event.currentTarget.id === 'cart-canvas') {
                document.body.classList.remove('cart-canvas-open');
            }
        }
    }

    return { openCanvas, closeCanvas, closeBackdrop }
}