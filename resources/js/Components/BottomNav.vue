<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const navItems = [
    {
        label: 'Dashboard',
        route: 'mahasiswa.dashboard',
        icon: '<path fill="currentColor" d="M2 6.5c0-2.121 0-3.182.659-3.841S4.379 2 6.5 2s3.182 0 3.841.659S11 4.379 11 6.5s0 3.182-.659 3.841S8.621 11 6.5 11s-3.182 0-3.841-.659S2 8.621 2 6.5m11 11c0-2.121 0-3.182.659-3.841S15.379 13 17.5 13s3.182 0 3.841.659S22 15.379 22 17.5s0 3.182-.659 3.841S19.621 22 17.5 22s-3.182 0-3.841-.659S13 19.621 13 17.5" opacity=".5"/><path fill="currentColor" d="M2 17.5c0-2.121 0-3.182.659-3.841S4.379 13 6.5 13s3.182 0 3.841.659S11 15.379 11 17.5s0 3.182-.659 3.841S8.621 22 6.5 22s-3.182 0-3.841-.659S2 19.621 2 17.5m11-11c0-2.121 0-3.182.659-3.841S15.379 2 17.5 2s3.182 0 3.841.659S22 4.379 22 6.5s0 3.182-.659 3.841S19.621 11 17.5 11s-3.182 0-3.841-.659S13 8.621 13 6.5"/>',
    },
    {
        label: 'Tagihan',
        route: 'mahasiswa.tagihan.index',
        icon: '<path fill="currentColor" fill-rule="evenodd" d="M14 22h-4c-3.771 0-5.657 0-6.828-1.172S2 17.771 2 14v-4c0-3.771 0-5.657 1.172-6.828S6.239 2 10.03 2c.606 0 1.091 0 1.5.017q-.02.12-.02.244l-.01 2.834c0 1.097 0 2.067.105 2.848c.114.847.375 1.694 1.067 2.386c.69.69 1.538.952 2.385 1.066c.781.105 1.751.105 2.848.105h4.052c.043.534.043 1.19.043 2.063V14c0 3.771 0 5.657-1.172 6.828S17.771 22 14 22" clip-rule="evenodd" opacity=".5"/><path fill="currentColor" d="m11.51 2.26l-.01 2.835c0 1.097 0 2.066.105 2.848c.114.847.375 1.694 1.067 2.385c.69.691 1.538.953 2.385 1.067c.781.105 1.751.105 2.848.105h4.052q.02.232.028.5H22c0-.268 0-.402-.01-.56a5.3 5.3 0 0 0-.958-2.641c-.094-.128-.158-.204-.285-.357C19.954 7.494 18.91 6.312 18 5.5c-.81-.724-1.921-1.515-2.89-2.161c-.832-.556-1.248-.834-1.819-1.04a6 6 0 0 0-.506-.154c-.384-.095-.758-.128-1.285-.14z"/>',
    },
    {
        label: 'Riwayat',
        route: 'mahasiswa.riwayat.index',
        icon: '<path fill="currentColor" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2S2 6.477 2 12c0 1.6.376 3.112 1.043 4.453c.178.356.237.763.134 1.148l-.595 2.226a1.3 1.3 0 0 0 1.591 1.592l2.226-.596a1.63 1.63 0 0 1 1.149.133A9.96 9.96 0 0 0 12 22Z" opacity=".5"/><path fill="currentColor" d="M16.807 19.011A8.46 8.46 0 0 1 12 20.5a8.46 8.46 0 0 1-4.807-1.489c-.604-.415-.862-1.205-.51-1.848C7.41 15.83 8.91 15 12 15s4.59.83 5.318 2.163c.35.643.093 1.433-.511 1.848M12 12a3 3 0 1 0 0-6a3 3 0 0 0 0 6"/>',
    },
];
</script>

<template>
    <nav class="bottom-nav">
        <Link
            v-for="item in navItems"
            :key="item.route"
            :href="route(item.route)"
            class="bottom-nav__item"
            :class="{ 'is-active': route().current(item.route) }"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="1.25em" height="1.25em" viewBox="0 0 24 24" v-html="item.icon"></svg>
            <span class="bottom-nav__label">{{ item.label }}</span>
        </Link>
    </nav>
</template>

<style scoped>
.bottom-nav {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-top: 1px solid var(--gray-200, #e5e7eb);
    z-index: 999;
    padding: 0.5rem 0;
    box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.08);
}

@media (max-width: 991px) {
    .bottom-nav {
        display: flex;
        justify-content: space-around;
        align-items: center;
    }
}

.bottom-nav__item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 1rem;
    text-decoration: none;
    color: var(--gray-500, #6b7280);
    font-size: 0.6875rem;
    font-weight: 500;
    transition: color 0.2s;
    border-radius: 0.5rem;
}

.bottom-nav__item:hover {
    color: var(--primary-color, #4f46e5);
}

.bottom-nav__item.is-active {
    color: var(--primary-color, #4f46e5);
    font-weight: 700;
}

.bottom-nav__item.is-active svg {
    color: var(--primary-color, #4f46e5);
}

.bottom-nav__label {
    line-height: 1;
}
</style>
