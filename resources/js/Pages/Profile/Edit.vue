<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const user = computed(() => usePage().props.auth.user);
const mahasiswa = computed(() => usePage().props.auth.mahasiswa);
const isAdmin = computed(() => user.value?.role === 'admin');
</script>

<template>
    <Head title="Profil" />
    <AuthenticatedLayout>
        <div class="profile-page">
            <!-- Profile Cover Card -->
            <div class="profile-cover-card">
                <div class="profile-cover-img"></div>
                <div class="profile-cover-bottom">
                    <div class="profile-avatar">
                        <img :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(mahasiswa?.nama_lengkap || user?.name || 'U') + '&background=fff&color=4f46e5&size=192&bold=true'" alt="" />
                    </div>
                    <div class="profile-info">
                        <h1 class="profile-name">{{ mahasiswa?.nama_lengkap || user?.name }}</h1>
                        <p class="profile-role">
                            {{ isAdmin ? 'Administrator' : 'Mahasiswa' }}
                            <span v-if="mahasiswa"> &middot; {{ mahasiswa.jurusan || '-' }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="profile-content">
                <div class="container-xl">
                    <div class="profile-grid">
                        <!-- Left: About -->
                        <div class="profile-main">
                            <div class="profile-card">
                                <h3 class="profile-card-title">Tentang</h3>
                                <div class="profile-about">
                                    <div v-if="mahasiswa" class="profile-about-item">
                                        <span class="profile-about-label">NIM</span>
                                        <span class="profile-about-value">{{ mahasiswa.nim }}</span>
                                    </div>
                                    <div v-if="mahasiswa" class="profile-about-item">
                                        <span class="profile-about-label">Nama Lengkap</span>
                                        <span class="profile-about-value">{{ mahasiswa.nama_lengkap }}</span>
                                    </div>
                                    <div v-if="mahasiswa" class="profile-about-item">
                                        <span class="profile-about-label">Program Studi</span>
                                        <span class="profile-about-value">{{ mahasiswa.jurusan }}</span>
                                    </div>
                                    <div v-if="mahasiswa" class="profile-about-item">
                                        <span class="profile-about-label">Angkatan</span>
                                        <span class="profile-about-value">{{ mahasiswa.angkatan }}</span>
                                    </div>
                                    <div v-if="mahasiswa" class="profile-about-item">
                                        <span class="profile-about-label">Semester</span>
                                        <span class="profile-about-value">{{ mahasiswa.semester }}</span>
                                    </div>
                                    <div class="profile-about-item">
                                        <span class="profile-about-label">Email</span>
                                        <span class="profile-about-value">{{ user?.email }}</span>
                                    </div>
                                    <div class="profile-about-item">
                                        <span class="profile-about-label">Status</span>
                                        <span class="profile-about-value">
                                            <span v-if="mahasiswa?.status_aktif" class="badge-success">Aktif</span>
                                            <span v-else class="badge-danger">Non-aktif</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Quick Info -->
                        <div class="profile-sidebar">
                            <div class="profile-card">
                                <h3 class="profile-card-title">Informasi Akun</h3>
                                <div class="profile-card-body">
                                    <div class="profile-sidebar-item">
                                        <div class="profile-sidebar-icon" style="background:#ede9fe;color:#7c3aed;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <div class="profile-sidebar-label">Nama</div>
                                            <div class="profile-sidebar-value">{{ mahasiswa?.nama_lengkap || user?.name }}</div>
                                        </div>
                                    </div>
                                    <div class="profile-sidebar-item">
                                        <div class="profile-sidebar-icon" style="background:#dbeafe;color:#2563eb;">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div>
                                            <div class="profile-sidebar-label">Email</div>
                                            <div class="profile-sidebar-value">{{ user?.email }}</div>
                                        </div>
                                    </div>
                                    <div v-if="mahasiswa" class="profile-sidebar-item">
                                        <div class="profile-sidebar-icon" style="background:#d1fae5;color:#059669;">
                                            <i class="fas fa-id-card"></i>
                                        </div>
                                        <div>
                                            <div class="profile-sidebar-label">NIM</div>
                                            <div class="profile-sidebar-value">{{ mahasiswa.nim }}</div>
                                        </div>
                                    </div>
                                    <div v-if="mahasiswa" class="profile-sidebar-item">
                                        <div class="profile-sidebar-icon" style="background:#fef3c7;color:#d97706;">
                                            <i class="fas fa-graduation-cap"></i>
                                        </div>
                                        <div>
                                            <div class="profile-sidebar-label">Semester</div>
                                            <div class="profile-sidebar-value">Semester {{ mahasiswa.semester }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.profile-cover-card {
    background: white;
    border-radius: 1rem;
    margin: 1.5rem 1.5rem 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid var(--gray-200);
    overflow: hidden;
}

.profile-cover-img {
    height: 180px;
    background:
        url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1200&h=400&fit=crop&crop=bottom') center/cover no-repeat;
    position: relative;
}

.profile-cover-img::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 50%, rgba(0,0,0,0.05) 100%);
}

.profile-cover-bottom {
    display: flex;
    align-items: flex-end;
    gap: 1.25rem;
    padding: 0 1.5rem 1.5rem;
    margin-top: -25px;
    position: relative;
    z-index: 1;
}

.profile-avatar {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    border: 4px solid white;
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    background: white;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-info {
    flex: 1;
    padding-bottom: 0.25rem;
    min-width: 0;
}

.profile-name {
    font-size: 1.375rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
    line-height: 1.2;
}

.profile-role {
    font-size: 0.875rem;
    color: var(--gray-500);
    margin: 0.25rem 0 0;
}

.profile-content {
    padding: 1.5rem;
    min-height: calc(100vh - 200px);
}

.profile-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 1.5rem;
    align-items: start;
}

.profile-card {
    background: white;
    border-radius: 1rem;
    border: 1px solid var(--gray-200);
    overflow: hidden;
}

.profile-card-title {
    font-size: 0.9375rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--gray-100);
}

.profile-card-body {
    padding: 1.25rem;
}

.profile-about {
    padding: 0.5rem 1.25rem;
}

.profile-about-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.625rem 0;
    border-bottom: 1px solid var(--gray-100);
}

.profile-about-item:last-child {
    border-bottom: none;
}

.profile-about-label {
    font-size: 0.8125rem;
    color: var(--gray-500);
    font-weight: 500;
}

.profile-about-value {
    font-size: 0.875rem;
    color: var(--gray-900);
    font-weight: 600;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
    padding: 0.25rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-danger {
    background: #fee2e2;
    color: #991b1b;
    padding: 0.25rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.profile-sidebar-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--gray-100);
}

.profile-sidebar-item:last-child {
    border-bottom: none;
}

.profile-sidebar-icon {
    width: 40px;
    height: 40px;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.profile-sidebar-label {
    font-size: 0.6875rem;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    margin-bottom: 0.125rem;
}

.profile-sidebar-value {
    font-size: 0.875rem;
    color: var(--gray-900);
    font-weight: 600;
}

@media (max-width: 992px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
    .profile-sidebar {
        order: -1;
    }
}

@media (max-width: 768px) {
    .profile-cover-card {
        margin: 1rem;
    }
    .profile-cover-img {
        height: 140px;
    }
    .profile-cover-bottom {
        flex-direction: column;
        align-items: flex-start;
        margin-top: -36px;
        padding: 0 1rem 1rem;
    }
    .profile-avatar {
        width: 72px;
        height: 72px;
    }
    .profile-name {
        font-size: 1.125rem;
    }
    .profile-content {
        padding: 1rem;
    }
}
</style>
