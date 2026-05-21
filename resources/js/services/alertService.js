import {useToast} from "vue-toastification";
/*
 * Position
 * --------------
 * top-right
 * top-center
 * top-left
 * bottom-right
 * bottom-center
 * bottom-left
 * */
export default {
    default: function (message = "Default", position = "top-right") {
        const toast = useToast();
        toast(message, {
            position: position,
            onClick: () => { navigator.clipboard.writeText(message); }
        });
    },

    success: function (message = "Success", position = "top-right") {
        const toast = useToast();
        toast.success(message, {
            position: position,
            onClick: () => { navigator.clipboard.writeText(message); }
        });
    },

    info: function (message = "Info", position = "top-right") {
        const toast = useToast();
        toast.info(message, {
            position: position,
            onClick: () => { navigator.clipboard.writeText(message); }
        });
    },

    warning: function (message = "Warning", position = "top-right") {
        const toast = useToast();
        toast.warning(message, {
            position: position,
            onClick: () => { navigator.clipboard.writeText(message); }
        });
    },

    error: function (message = "Error", position = "top-right") {
        const toast = useToast();
        toast.error(message, {
            position: position,
            onClick: () => { navigator.clipboard.writeText(message); }
        });
    },

    successFlip: function (status = null, message = "", position = "top-right") {
        const toast = useToast();
        if (status != null) {
            if (status) {
                message = message + " Updated Successfully.";
            } else {
                message = message + " Created Successfully.";
            }
        } else {
            message = message + " Deleted Successfully.";
        }

        toast.success(message, {
            position: position,
            onClick: () => { navigator.clipboard.writeText(message); }
        });
    },

    successInfo: function (status = null, message = "", position = "top-right") {
        const toast = useToast();
        toast.success(message, {
            position: position,
            onClick: () => { navigator.clipboard.writeText(message); }
        });
    },
};
