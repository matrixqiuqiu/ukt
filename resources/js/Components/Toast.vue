<script setup>
import { useToast } from '@/composables/useToast';

const { toasts, removeToast } = useToast();

const iconMap = {
    success: 'fas fa-check-circle',
    error: 'fas fa-times-circle',
    warning: 'fas fa-exclamation-triangle',
    info: 'fas fa-info-circle',
};

const colorMap = {
    success: { bg: '#d1fae5', border: '#10b981', text: '#065f46' },
    error: { bg: '#fee2e2', border: '#ef4444', text: '#991b1b' },
    warning: { bg: '#fef3c7', border: '#f59e0b', text: '#92400e' },
    info: { bg: '#dbeafe', border: '#3b82f6', text: '#1e40af' },
};
</script>

<template>
    <div class="toast-container">
        <TransitionGroup name="toast" tag="div">
            <div
                v-for="t in toasts"
                :key="t.id"
                class="toast-item"
                :style="{
                    background: colorMap[t.type]?.bg,
                    borderLeft: `4px solid ${colorMap[t.type]?.border}`,
                    color: colorMap[t.type]?.text,
                }"
            >
                <i :class="iconMap[t.type]" class="toast-icon"></i>
                <span class="toast-message">{{ t.message }}</span>
                <button class="toast-close" @click.stop="removeToast(t.id)">
                    <i class="fas fa-times"></i>
                </button>
                <div
                    class="toast-progress"
                    :style="{
                        background: colorMap[t.type]?.border,
                        animationDuration: (t.duration || 4000) + 'ms',
                    }"
                ></div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-container {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-width: 400px;
    width: 100%;
    pointer-events: none;
}

.toast-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    border-radius: 0.75rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    pointer-events: all;
    font-size: 0.875rem;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.toast-icon {
    font-size: 1.125rem;
    flex-shrink: 0;
}

.toast-message {
    flex: 1;
}

.toast-close {
    background: none;
    border: none;
    color: inherit;
    cursor: pointer;
    padding: 0.25rem;
    opacity: 0.6;
    transition: opacity 0.2s;
    flex-shrink: 0;
    z-index: 1;
}

.toast-close:hover {
    opacity: 1;
}

.toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    width: 100%;
    animation: toast-progress linear forwards;
    opacity: 0.5;
}

@keyframes toast-progress {
    from { width: 100%; }
    to { width: 0%; }
}

.toast-enter-active {
    animation: toast-in 0.3s ease;
}

.toast-leave-active {
    animation: toast-out 0.3s ease forwards;
}

@keyframes toast-in {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes toast-out {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}
</style>
