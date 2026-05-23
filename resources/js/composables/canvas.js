import { ref } from "vue";

export function useCanvas() { 
    const activeClass = ref('canvas-active')
    const hiddenClass = ref('overflow-hidden')

    function openCanvas(targetID) {
        console.log("canvas.js: openCanvas called for targetID:", targetID);
        const targetElement = document.querySelector(`#${targetID}`);
        console.log("canvas.js: targetElement found:", targetElement);
        if (targetElement) {
            targetElement.classList.add(activeClass.value);
            document.body.classList.add(hiddenClass.value);
            console.log("canvas.js: Classes added successfully.");
        } else {
            console.error("canvas.js: targetElement NOT FOUND!");
        }
    }

    function closeCanvas(targetID) {
        console.log("canvas.js: closeCanvas called for targetID:", targetID);
        const targetElement = document.querySelector(`#${targetID}`);
        if (targetElement) {
            targetElement.classList.remove(activeClass.value);
            document.body.classList.remove(hiddenClass.value);
            console.log("canvas.js: Classes removed successfully.");
        }
    }

    function closeBackdrop(event) {
        const containerElement = event.currentTarget.firstElementChild
        const isWrapperElement = event.target.contains(containerElement)

        if(isWrapperElement) {
            event.currentTarget.classList.remove(activeClass.value)
            document.body.classList.remove(hiddenClass.value)
        }
    }

    return { openCanvas, closeCanvas, closeBackdrop }
}