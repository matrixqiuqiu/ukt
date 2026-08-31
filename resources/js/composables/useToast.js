import { reactive } from 'vue';

const state = reactive({
    toasts: [],
});

let counter = 0;

export function useToast() {
    function addToast(message, type = 'success', duration = 4000) {
        const id = ++counter;
        state.toasts.push({ id, message, type, duration });

        if (duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, duration);
        }
    }

    function removeToast(id) {
        const index = state.toasts.findIndex(t => t.id === id);
        if (index !== -1) {
            state.toasts.splice(index, 1);
        }
    }

    function success(message, duration = 4000) {
        addToast(message, 'success', duration);
    }

    function error(message, duration = 5000) {
        addToast(message, 'error', duration);
    }

    function warning(message, duration = 4500) {
        addToast(message, 'warning', duration);
    }

    function info(message, duration = 4000) {
        addToast(message, 'info', duration);
    }

    return {
        toasts: state.toasts,
        addToast,
        removeToast,
        success,
        error,
        warning,
        info,
    };
}
