<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

onMounted(() => {
    window.location.href = route('admin.tahun-akademik.index');
});
</script>

<template>
    <Head title="Pengaturan Semester" />
    <AuthenticatedLayout>
        <div style="padding: 3rem 1.5rem; text-align: center;">
            <p style="color: #64748b; font-size: 0.9375rem;">Mengalihkan ke Pusat Kontrol Periode & Tahun Akademik...</p>
            <Link :href="route('admin.tahun-akademik.index')" class="m-btn m-btn-primary" style="margin-top: 1rem;">
                Buka Periode & Tahun Akademik
            </Link>
        </div>
    </AuthenticatedLayout>
</template>
