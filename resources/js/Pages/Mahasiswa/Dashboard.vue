<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { formatRupiah } from '@/utils';
import { computed } from 'vue';

const props = defineProps({
    stats: Object,
    tagihans: Array,
});
const page = usePage();

const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Dashboard Mahasiswa" />
    <AuthenticatedLayout>
        <div class="student-portal">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1 class="welcome-title">Selamat Datang, {{ $page.props.auth.user?.name }}! 👋</h1>
                <p class="welcome-subtitle">Semoga hari Anda menyenangkan</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value">{{ stats.totalTagihan }}</div>
                            <div class="stat-label">Total Tagihan UKT</div>
                        </div>
                        <div class="stat-icon primary"><i class="fas fa-file-invoice-dollar"></i></div>
                    </div>
                    <div class="stat-change" :class="stats.belumBayar > 0 ? 'negative' : 'positive'">
                        <i :class="stats.belumBayar > 0 ? 'fas fa-arrow-down' : 'fas fa-check'"></i>
                        {{ stats.belumBayar > 0 ? stats.belumBayar + ' tagihan belum dibayar' : 'Semua tagihan lunas' }}
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value">{{ stats.sudahBayar }}</div>
                            <div class="stat-label">Sudah Dibayar</div>
                        </div>
                        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-check"></i> Pembayaran lunas
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value">{{ stats.belumBayar }}</div>
                            <div class="stat-label">Belum Dibayar</div>
                        </div>
                        <div class="stat-icon warning"><i class="fas fa-exclamation-triangle"></i></div>
                    </div>
                    <div class="stat-change" :class="stats.belumBayar > 0 ? 'negative' : 'positive'">
                        {{ stats.belumBayar > 0 ? 'Perlu segera dibayar' : 'Tidak ada tagihan' }}
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value">Aktif</div>
                            <div class="stat-label">Status Akademik</div>
                        </div>
                        <div class="stat-icon info"><i class="fas fa-graduation-cap"></i></div>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-check"></i> Dapat mengikuti kuliah
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Quick Actions -->
                <div class="m-card">
                    <div class="m-card-header">
                        <h3 class="m-card-title">Aksi Cepat</h3>
                        <Link :href="route('mahasiswa.tagihan.index')" class="m-btn m-btn-secondary m-btn-sm">Lihat Semua</Link>
                    </div>
                    <div class="m-card-body">
                        <div class="quick-actions">
                            <Link :href="route('mahasiswa.tagihan.index')" class="quick-action-btn">
                                <div class="quick-action-icon"><i class="fas fa-credit-card"></i></div>
                                <div>
                                    <div class="quick-action-label">Bayar UKT</div>
                                    <div class="quick-action-desc">Lakukan pembayaran sekarang</div>
                                </div>
                            </Link>
                            <Link :href="route('mahasiswa.riwayat.index')" class="quick-action-btn">
                                <div class="quick-action-icon"><i class="fas fa-history"></i></div>
                                <div>
                                    <div class="quick-action-label">Riwayat</div>
                                    <div class="quick-action-desc">Lihat riwayat pembayaran</div>
                                </div>
                            </Link>
                            <a href="#" class="quick-action-btn">
                                <div class="quick-action-icon"><i class="fas fa-download"></i></div>
                                <div>
                                    <div class="quick-action-label">Unduh Kwitansi</div>
                                    <div class="quick-action-desc">Download bukti pembayaran</div>
                                </div>
                            </a>
                            <a href="#" class="quick-action-btn">
                                <div class="quick-action-icon"><i class="fas fa-info-circle"></i></div>
                                <div>
                                    <div class="quick-action-label">Info UKT</div>
                                    <div class="quick-action-desc">Detail golongan UKT</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Payment Status -->
                <div class="m-card">
                    <div class="m-card-header">
                        <h3 class="m-card-title">Status Pembayaran</h3>
                    </div>
                    <div class="m-card-body">
                        <div class="payment-status">
                            <div v-for="t in tagihans" :key="t.id" class="status-item" :class="t.status === 'sudah_dibayar' ? 'success' : t.status === 'dispen' ? 'dispen' : 'warning'">
                                <div class="status-label">{{ t.status === 'sudah_dibayar' ? 'Lunas' : t.status === 'dispen' ? 'Dispen' : t.pending_pembayaran_id ? 'Menunggu Pembayaran' : 'Belum Dibayar' }}</div>
                                <div class="status-value">UKT Semester {{ t.semester }} {{ t.tahun_akademik }}</div>
                                <div v-if="t.beasiswa" class="m-badge m-badge-success" style="margin-top:0.35rem;display:inline-flex;gap:0.35rem;"><i class="fas fa-graduation-cap"></i> {{ t.beasiswa.nama }} ({{ t.beasiswa.kode }}) · Potongan Rp {{ Number(t.beasiswa.diskon).toLocaleString('id-ID') }}</div>
                                <div class="status-amount">{{ formatRupiah(t.nominal) }}</div>
                                <Link v-if="t.status !== 'sudah_dibayar' && t.pending_pembayaran_id" :href="route('mahasiswa.pembayaran.show', t.pending_pembayaran_id)" class="m-btn m-btn-secondary" style="margin-top: 1rem; width: 100%;">
                                    <i class="fas fa-hourglass-half"></i> Lihat Pembayaran
                                </Link>
                                <Link v-else-if="t.status !== 'sudah_dibayar' && t.status !== 'dispen'" :href="route('mahasiswa.tagihan.show', t.id)" class="m-btn m-btn-primary" style="margin-top: 1rem; width: 100%;">
                                    <i class="fas fa-credit-card"></i> Bayar Sekarang
                                </Link>
                                <div v-else-if="t.status !== 'sudah_dibayar' && t.status === 'dispen'" style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--gray-600);">
                                    <i class="fas fa-file-signature"></i> Dispensasi disetujui, menunggu pembayaran
                                </div>
                                <div v-else style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--gray-600);">
                                    <i class="fas fa-check-circle"></i> Pembayaran lunas
                                </div>
                            </div>
                            <div v-if="!tagihans || tagihans.length === 0" class="text-center py-4" style="color: var(--gray-600);">
                                Belum ada tagihan UKT
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="m-card">
                <div class="m-card-header">
                    <h3 class="m-card-title">Daftar Tagihan UKT</h3>
                    <Link :href="route('mahasiswa.tagihan.index')" class="m-btn m-btn-secondary m-btn-sm">Lihat Semua</Link>
                </div>
                <div class="m-card-body">
                    <div class="table-container">
                        <table class="m-data-table">
                            <thead>
                                <tr>
                                    <th>Semester</th>
                                    <th>Tahun Akademik</th>
                                    <th>Nominal</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="t in tagihans" :key="t.id">
                                    <td>{{ t.semester }}</td>
                                    <td>{{ t.tahun_akademik }}</td>
                                    <td style="font-weight: 600;">{{ formatRupiah(t.nominal) }}<div v-if="t.beasiswa" style="font-size:0.65rem;color:#059669;"><i class="fas fa-graduation-cap"></i> {{ t.beasiswa.kode }} potongan Rp {{ Number(t.beasiswa.diskon).toLocaleString('id-ID') }}</div></td>
                                    <td>{{ t.jatuh_tempo }}</td>
                                    <td>
                                        <span class="m-badge" :class="{
                                            'm-badge-success': t.status === 'sudah_dibayar',
                                            'm-badge-dispen': t.status === 'dispen',
                                            'm-badge-warning': t.status === 'belum_dibayar',
                                            'm-badge-danger': t.status === 'terlambat'
                                        }">{{ t.status === 'sudah_dibayar' ? 'Lunas' : t.status === 'dispen' ? 'Dispen' : t.status === 'belum_dibayar' ? (t.pending_pembayaran_id ? 'Menunggu Pembayaran' : 'Belum Lunas') : 'Terlambat' }}</span>
                                        <div v-if="t.beasiswa" class="m-badge m-badge-success" style="margin-top:0.25rem;font-size:0.6rem;">{{ t.beasiswa.nama }}</div>
                                    </td>
                                    <td>
                                        <Link v-if="t.status !== 'sudah_dibayar' && t.pending_pembayaran_id" :href="route('mahasiswa.pembayaran.show', t.pending_pembayaran_id)" class="m-btn m-btn-secondary m-btn-sm">
                                            <i class="fas fa-hourglass-half"></i> Lihat
                                        </Link>
                                        <Link v-else-if="t.status !== 'sudah_dibayar' && t.status !== 'dispen'" :href="route('mahasiswa.tagihan.show', t.id)" class="m-btn m-btn-primary m-btn-sm">
                                            <i class="fas fa-credit-card"></i> Bayar
                                        </Link>
                                        <span v-else-if="t.status === 'dispen'" class="m-badge m-badge-dispen">Dispen</span>
                                        <Link v-else-if="t.last_pembayaran_id" :href="route('mahasiswa.pembayaran.show', t.last_pembayaran_id)" class="m-btn m-btn-secondary m-btn-sm">
                                            <i class="fas fa-download"></i> Unduh
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!tagihans || tagihans.length === 0">
                                    <td colspan="6" class="text-center py-4" style="color: var(--gray-600);">Belum ada tagihan UKT</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
