<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatRupiah } from '@/utils';

const props = defineProps({
    mahasiswa: Object,
    tagihans: Array,
});
</script>
<template>
    <Head :title="mahasiswa.nama_lengkap" />
    <AuthenticatedLayout>
        <template #header>
            <div class="page-heading">
                <Link :href="route('admin.mahasiswa.index')" class="btn btn-secondary btn-sm">&larr; Kembali</Link>
                <span>{{ mahasiswa.nama_lengkap }}</span>
            </div>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="custom-card">
                            <div class="card-header">
                                <h3 class="card-title">Data Diri</h3>
                            </div>
                            <div class="card-body">
                                <div class="stat-card">
                                    <div class="stat-card__label">NIM</div>
                                    <div class="stat-card__value">{{ mahasiswa.nim }}</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-card__label">Jurusan</div>
                                    <div class="stat-card__value">{{ mahasiswa.jurusan }}</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-card__label">Angkatan</div>
                                    <div class="stat-card__value">{{ mahasiswa.angkatan }}</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-card__label">Semester</div>
                                    <div class="stat-card__value">{{ mahasiswa.semester }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="custom-card">
                            <div class="card-header">
                                <h3 class="card-title">Tagihan UKT</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Semester</th>
                                                <th>Tahun Akademik</th>
                                                <th>Nominal</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="t in tagihans" :key="t.id">
                                                <td>{{ t.semester }}</td>
                                                <td>{{ t.tahun_akademik }}</td>
                                                <td>{{ formatRupiah(t.nominal) }}</td>
                                                <td>
                                                    <span :class="{
                                                        'badge-custom': true,
                                                        'badge-success': t.status === 'sudah_dibayar',
                                                        'badge-danger': t.status === 'belum_dibayar',
                                                        'badge-warning': t.status === 'terlambat'
                                                    }">{{ t.status.replace('_', ' ') }}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
