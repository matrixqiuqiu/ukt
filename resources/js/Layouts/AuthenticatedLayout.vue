<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Toast from '@/Components/Toast.vue';
import { useToast } from '@/composables/useToast';

const page = usePage();
const user = computed(() => page.props.auth.user);
const mahasiswa = computed(() => page.props.auth.mahasiswa);
const displayName = computed(() => mahasiswa.value?.nama_lengkap || user.value?.name || 'User');
const isAdmin = computed(() => user.value?.role === 'admin');
const pendingCount = computed(() => page.props.pendingVerification || 0);
const belumBayarCount = computed(() => page.props.belumBayarCount || 0);
const theme = computed(() => page.props.theme);

const themeStyle = computed(() => {
    if (!theme.value) return {};
    return {
        '--primary': theme.value.primary_color,
        '--primary-dark': theme.value.primary_color,
        '--content-bg': theme.value.content_bg || '#f8fafc',
        '--content-text': theme.value.content_text || '#1e293b',
        '--card-bg': theme.value.card_bg || '#ffffff',
        '--card-border': theme.value.card_border || '#e2e8f0',
    };
});

const sidebarStyle = computed(() => {
    if (!theme.value) return {};
    return {
        '--sidebar-bg': theme.value.sidebar_bg,
        '--sidebar-text': theme.value.sidebar_text,
        '--sidebar-icon': theme.value.sidebar_icon,
        '--sidebar-active-text': theme.value.sidebar_active_text,
        '--sidebar-active-bg': theme.value.sidebar_active_bg,
        '--sidebar-hover-bg': theme.value.sidebar_hover_bg,
        background: theme.value.sidebar_bg,
        color: theme.value.sidebar_text,
    };
});

const navbarStyle = computed(() => {
    if (!theme.value) return {};
    return {
        '--navbar-bg': theme.value.navbar_bg,
        '--navbar-text': theme.value.navbar_text,
        '--navbar-border': theme.value.navbar_border,
        '--logo-text': theme.value.logo_text,
        background: theme.value.navbar_bg,
        color: theme.value.navbar_text,
        borderBottomColor: theme.value.navbar_border,
    };
});

const toast = useToast();

let lastFlashSuccess = null;
watch(() => page.props.flash?.success, (msg) => {
    if (msg && msg !== lastFlashSuccess) {
        lastFlashSuccess = msg;
        toast.success(msg);
        setTimeout(() => { lastFlashSuccess = null; }, 6000);
    }
});

let lastFlashError = null;
watch(() => page.props.flash?.error, (msg) => {
    if (msg && msg !== lastFlashError) {
        lastFlashError = msg;
        toast.error(msg);
        setTimeout(() => { lastFlashError = null; }, 6000);
    }
});

watch(() => page.props.flash?.sync_result, (result) => {
    if (!result) return;
    if (result.success) {
        toast.success(`Sinkronisasi berhasil! Baru: ${result.created}, Diperbarui: ${result.updated}`);
        if (result.errors?.length) {
            toast.warning(`${result.errors.length} data gagal diproses.`);
        }
    } else {
        toast.error(result.message || 'Sinkronisasi gagal.');
    }
}, { immediate: true });

const adminLinks = [
    { label: 'Dashboard', route: 'admin.dashboard', icon: '<path fill="currentColor" d="M2 6.5c0-2.121 0-3.182.659-3.841S4.379 2 6.5 2s3.182 0 3.841.659S11 4.379 11 6.5s0 3.182-.659 3.841S8.621 11 6.5 11s-3.182 0-3.841-.659S2 8.621 2 6.5m11 11c0-2.121 0-3.182.659-3.841S15.379 13 17.5 13s3.182 0 3.841.659S22 15.379 22 17.5s0 3.182-.659 3.841S19.621 22 17.5 22s-3.182 0-3.841-.659S13 19.621 13 17.5" opacity=".5"/><path fill="currentColor" d="M2 17.5c0-2.121 0-3.182.659-3.841S4.379 13 6.5 13s3.182 0 3.841.659S11 15.379 11 17.5s0 3.182-.659 3.841S8.621 22 6.5 22s-3.182 0-3.841-.659S2 19.621 2 17.5m11-11c0-2.121 0-3.182.659-3.841S15.379 2 17.5 2s3.182 0 3.841.659S22 4.379 22 6.5s0 3.182-.659 3.841S19.621 11 17.5 11s-3.182 0-3.841-.659S13 8.621 13 6.5"/>', group: 'Dashboard' },
    { label: 'Data Mahasiswa', route: 'admin.mahasiswa.index', icon: '<circle cx="15" cy="6" r="3" fill="currentColor" opacity=".4"/><ellipse cx="16" cy="17" fill="currentColor" opacity=".4" rx="5" ry="3"/><circle cx="9.001" cy="6" r="4" fill="currentColor"/><ellipse cx="9.001" cy="17.001" fill="currentColor" rx="7" ry="4"/>', group: 'Manajemen' },
    { label: 'Tagihan UKT', route: 'admin.tagihan.index', icon: '<path fill="currentColor" d="M14 22h-4c-3.771 0-5.657 0-6.828-1.172S2 17.771 2 14v-4c0-3.771 0-5.657 1.172-6.828S6.239 2 10.03 2c.606 0 1.091 0 1.5.017q-.02.12-.02.244l-.01 2.834c0 1.097 0 2.067.105 2.848c.114.847.375 1.694 1.067 2.386c.69.69 1.538.952 2.385 1.066c.781.105 1.751.105 2.848.105h4.052c.043.534.043 1.19.043 2.063V14c0 3.771 0 5.657-1.172 6.828S17.771 22 14 22" opacity=".5"/><path fill="currentColor" d="m11.51 2.26l-.01 2.835c0 1.097 0 2.066.105 2.848c.114.847.375 1.694 1.067 2.385c.69.691 1.538.953 2.385 1.067c.781.105 1.751.105 2.848.105h4.052q.02.232.028.5H22c0-.268 0-.402-.01-.56a5.3 5.3 0 0 0-.958-2.641c-.094-.128-.158-.204-.285-.357C19.954 7.494 18.91 6.312 18 5.5c-.81-.724-1.921-1.515-2.89-2.161c-.832-.556-1.248-.834-1.819-1.04a6 6 0 0 0-.506-.154c-.384-.095-.758-.128-1.285-.14z"/>', group: 'Manajemen' },
    { label: 'Pembayaran', route: 'admin.pembayaran.index', icon: '<path fill="currentColor" d="M1.289 2.763a.75.75 0 0 1 .948-.475l.265.089l.04.013c.626.209 1.155.385 1.572.579c.442.206.826.46 1.117.865c.291.403.412.848.467 1.333c.052.456.052 1.014.052 1.674V9.5c0 1.435.002 2.437.103 3.192c.099.734.28 1.122.556 1.399c.277.277.666.457 1.4.556c.755.101 1.756.103 3.191.103h7a.75.75 0 1 1 0 1.5h-7.055c-1.367 0-2.47 0-3.337-.117c-.9-.12-1.658-.38-2.26-.981c-.601-.602-.86-1.36-.981-2.26c-.117-.867-.117-1.97-.117-3.337V6.883c0-.713 0-1.185-.042-1.546c-.04-.342-.107-.507-.194-.626c-.086-.12-.221-.237-.533-.382c-.33-.153-.777-.304-1.453-.53l-.265-.088a.75.75 0 0 1-.474-.948" clip-rule="evenodd"/><path fill="currentColor" d="M5.745 6q.006.39.005.841V9.5c0 1.435.002 2.437.103 3.192q.023.165.05.308h10.12c.959 0 1.438 0 1.814-.248s.565-.688.942-1.57l.43-1c.809-1.89 1.213-2.833.769-3.508S18.506 6 16.45 6z" opacity=".5"/><path fill="currentColor" d="M7.5 18a1.5 1.5 0 1 1 0 3a1.5 1.5 0 0 1 0-3M18 19.5a1.5 1.5 0 1 0-3 0a1.5 1.5 0 0 0 3 0"/>', group: 'Manajemen' },
    { label: 'Verifikasi', route: 'admin.verifikasi.index', icon: '<path fill="currentColor" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2S2 6.477 2 12c0 1.6.376 3.112 1.043 4.453c.178.356.237.763.134 1.148l-.595 2.226a1.3 1.3 0 0 0 1.591 1.592l2.226-.596a1.63 1.63 0 0 1 1.149.133A9.96 9.96 0 0 0 12 22Z" opacity=".5"/><path fill="currentColor" d="M16.807 19.011A8.46 8.46 0 0 1 12 20.5a8.46 8.46 0 0 1-4.807-1.489c-.604-.415-.862-1.205-.51-1.848C7.41 15.83 8.91 15 12 15s4.59.83 5.318 2.163c.35.643.093 1.433-.511 1.848M12 12a3 3 0 1 0 0-6a3 3 0 0 0 0 6"/>', group: 'Manajemen', badge: () => pendingCount.value },
    { label: 'Dispensasi', route: 'admin.dispensasi.index', icon: '<path fill="currentColor" d="M6 2a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h9a3 3 0 0 0 3-3V9.5a.5.5 0 0 0-.146-.354l-5-5A.5.5 0 0 0 12.5 4H6a1 1 0 0 1 0-2h8a1 1 0 0 1 1 1v5.5a.5.5 0 0 0 .5.5H21a1 1 0 0 1 1 1V19a3 3 0 0 1-3 3h-1a1 1 0 1 1 0-2h1a1 1 0 0 0 1-1V10.5h-4.5A2.5 2.5 0 0 1 13 8V4.586L8.914 6.5A2.5 2.5 0 0 1 8.5 6.62V8a1 1 0 1 1-2 0V6.62a3 3 0 0 0-1.188.368A2.5 2.5 0 0 0 4 9.354V19a1 1 0 0 0 1 1h1a1 1 0 1 1 0 2H6Z" opacity=".5"/><path fill="currentColor" d="M8.5 11a1 1 0 0 1 1 1v5a1 1 0 1 1-2 0v-5a1 1 0 0 1 1-1M11 14a1 1 0 0 1 1 1v2a1 1 0 1 1-2 0v-2a1 1 0 0 1 1-1M14 13a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0v-3a1 1 0 0 1 1-1"/>', group: 'Manajemen' },
    { label: 'Data Bank', route: 'admin.bank.index', icon: '<path fill="currentColor" d="M2 7.5A5.5 5.5 0 0 1 7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5zm5.5-4a4 4 0 0 0-4 4V9h16V7.5a4 4 0 0 0-4-4zm8.5 8H4v4.5a4 4 0 0 0 4 4h4a4 4 0 0 0 4-4z"/><path fill="currentColor" d="M4 11h16v2H4zm0 4h7v2H4z" opacity=".5"/>', group: 'Master Data' },
    { label: 'Data Program Studi', route: 'admin.jurusan.index', icon: '<path fill="currentColor" d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>', group: 'Master Data' },
    { label: 'Data Fakultas', route: 'admin.fakultas.index', icon: '<path fill="currentColor" d="M4 10v7h3v-7H4zm6 0v7h3v-7h-3zM2 22h19v-3H2v3zm14-12v7h3v-7h-3zm-4.5-9L2 6v2h19V6l-9.5-5z"/>', group: 'Master Data' },
    { label: 'Tarif & Komponen Biaya', route: 'admin.biaya.index', icon: '<path fill="currentColor" d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>', group: 'Master Data' },
    { label: 'Jenis Beasiswa', route: 'admin.jenis-beasiswa.index', icon: '<path fill="currentColor" d="M7 7h10v2H7zM7 11h10v2H7zM7 15h6v2H7z" opacity=".5"/><path fill="currentColor" d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5zM3 5v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2z"/>', group: 'Master Data' },
    { label: 'Beasiswa', route: 'admin.beasiswa.index', icon: '<path fill="currentColor" d="M12 2L2 7l5 2v2.5c0 2.94 2.14 5.69 5 6.5c2.86-.81 5-3.56 5-6.5V9l5-2z" opacity=".4"/><path fill="currentColor" d="M12 12.5c-2.86-.81-5-3.56-5-6.5V7l5 2.5V12.5z"/><path fill="currentColor" d="M12 13l5-2V7l-5 2z"/>', group: 'Master Data' },
    { label: 'Periode & Tahun Akademik', route: 'admin.tahun-akademik.index', icon: '<path fill="currentColor" d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/><path fill="currentColor" d="M9 10H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/>', group: 'Settings' },
    { label: 'Pengaturan User', route: 'admin.user.index', icon: '<path fill="currentColor" d="M9 6a3 3 0 1 1-6 0a3 3 0 0 1 6 0" opacity=".4"/><path fill="currentColor" d="M17 6a3 3 0 1 1-6 0a3 3 0 0 1 6 0M12 11a5 5 0 0 1 5 5v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a5 5 0 0 1 5-5z" opacity=".4"/><path fill="currentColor" d="M23 20a3 3 0 0 0-3-3h-4.1a6 6 0 0 0-2.1-1.9c.7.2 1.4.4 2.2.4a5 5 0 0 1 5 5v.5A1.5 1.5 0 0 1 19.5 22H17v-2h6zM9 11a5 5 0 0 1 5 5v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3v2h6v-2a3 3 0 0 0-3-3"/>', group: 'Settings' },
    { label: 'Pengaturan Aplikasi', route: 'admin.pengaturan.index', icon: '<path fill="currentColor" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10" opacity=".4"/><path fill="currentColor" d="M13.765 2.152C13.398 2 12.932 2 12 2s-1.398 0-1.765.152a2 2 0 0 0-1.083 1.083c-.092.223-.129.484-.143.863a1.62 1.62 0 0 1-.79 1.353a1.62 1.62 0 0 1-1.567.008c-.336-.178-.579-.276-.82-.308a2 2 0 0 0-1.478.396C4.04 5.79 3.806 6.193 3.34 7s-.7 1.21-.751 1.605a2 2 0 0 0 .396 1.479c.148.192.355.353.676.555c.473.297.777.803.777 1.361s-.304 1.064-.777 1.36c-.321.203-.529.364-.676.556a2 2 0 0 0-.396 1.479c.052.394.285.798.75 1.605c.467.807.7 1.21 1.015 1.453a2 2 0 0 0 1.479.396c.24-.032.483-.13.819-.308a1.62 1.62 0 0 1 1.567.008c.483.28.77.795.79 1.353c.014.38.05.64.143.863a2 2 0 0 0 1.083 1.083C10.602 22 11.068 22 12 22s1.398 0 1.765-.152a2 2 0 0 0 1.083-1.083c.092-.223.129-.483.143-.863c.02-.558.307-1.074.79-1.353a1.62 1.62 0 0 1 1.567-.008c.336.178.579.276.819.308a2 2 0 0 0 1.479-.396c.315-.242.548-.646 1.014-1.453s.7-1.21.751-1.605a2 2 0 0 0-.396-1.479c-.148-.192-.355-.353-.676-.555A1.62 1.62 0 0 1 19.562 12c0-.558.304-1.064.777-1.36c.321-.203.529-.364.676-.556a2 2 0 0 0 .396-1.479c-.052-.394-.285-.798-.75-1.605c-.467-.807-.7-1.21-1.015-1.453a2 2 0 0 0-1.479-.396c-.24.032-.483.13-.82.308a1.62 1.62 0 0 1-1.566-.008a1.62 1.62 0 0 1-.79-1.353c-.014-.38-.05-.64-.143-.863a2 2 0 0 0-1.083-1.083Z"/>', group: 'Settings' },
    { label: 'Payment Gateway', route: 'admin.operations.index', icon: '<path fill="currentColor" d="M5 5h14c1.1 0 2 .9 2 2v10c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2V7c0-1.1.9-2 2-2" opacity=".4"/><path fill="currentColor" d="M3 7h18v2H3zm0 4h18v2H3zm0 4h18v2H3z" opacity=".3"/><path fill="currentColor" d="M6 11h2v2H6zm0 4h2v2H6zm4-4h2v2h-2zm0 4h2v2h-2zm4-4h2v2h-2zm0 4h2v2h-2z"/>', group: 'Operations' },
    { label: 'Maintenance', route: 'admin.system.maintenance', icon: '<path fill="currentColor" d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9c-2-2-5-2.4-7.4-1.2L8 5l-1.4 1.4L2 2v2l3.6 3.6l1.4-1.4l3.3 3.3l-1.4 1.4L12.5 14.5c-1.2 2.4-.8 5.4 1.2 7.4c1.9 1.9 4.6 2.4 6.9 1.5l-1.1-1.1c-1.4.7-3.2.4-4.4-.9c-1.2-1.2-1.5-3-.9-4.4z" opacity=".5"/><path fill="currentColor" d="M12.5 14.5l1.4-1.4l4.6 4.6l-1.4 1.4z"/>', group: 'System' },
    { label: 'Backup Data', route: 'admin.system.backup', icon: '<path fill="currentColor" d="M12 3C6 3 1 6.58 1 11s5 8 11 8s11-3.58 11-8S18 3 12 3" opacity=".4"/><path fill="currentColor" d="M12 7a5 5 0 0 0-5 5a5 5 0 0 0 5 5a5 5 0 0 0 5-5a5 5 0 0 0-5-5m0 8a3 3 0 1 1 0-6a3 3 0 0 1 0 6M4 16c0 2.5 3.58 4.5 8 4.5s8-2 8-4.5v-2c0 2.5-3.58 4.5-8 4.5S4 16.5 4 14z"/>', group: 'System' },
];

const mahasiswaLinks = [
    { label: 'Dashboard', route: 'mahasiswa.dashboard', icon: '<path fill="currentColor" d="M2 6.5c0-2.121 0-3.182.659-3.841S4.379 2 6.5 2s3.182 0 3.841.659S11 4.379 11 6.5s0 3.182-.659 3.841S8.621 11 6.5 11s-3.182 0-3.841-.659S2 8.621 2 6.5m11 11c0-2.121 0-3.182.659-3.841S15.379 13 17.5 13s3.182 0 3.841.659S22 15.379 22 17.5s0 3.182-.659 3.841S19.621 22 17.5 22s-3.182 0-3.841-.659S13 19.621 13 17.5" opacity=".5"/><path fill="currentColor" d="M2 17.5c0-2.121 0-3.182.659-3.841S4.379 13 6.5 13s3.182 0 3.841.659S11 15.379 11 17.5s0 3.182-.659 3.841S8.621 22 6.5 22s-3.182 0-3.841-.659S2 19.621 2 17.5m11-11c0-2.121 0-3.182.659-3.841S15.379 2 17.5 2s3.182 0 3.841.659S22 4.379 22 6.5s0 3.182-.659 3.841S19.621 11 17.5 11s-3.182 0-3.841-.659S13 8.621 13 6.5"/>', group: 'Menu Utama' },
    { label: 'Tagihan UKT', route: 'mahasiswa.tagihan.index', icon: '<path fill="currentColor" fill-rule="evenodd" d="M14 22h-4c-3.771 0-5.657 0-6.828-1.172S2 17.771 2 14v-4c0-3.771 0-5.657 1.172-6.828S6.239 2 10.03 2c.606 0 1.091 0 1.5.017q-.02.12-.02.244l-.01 2.834c0 1.097 0 2.067.105 2.848c.114.847.375 1.694 1.067 2.386c.69.69 1.538.952 2.385 1.066c.781.105 1.751.105 2.848.105h4.052c.043.534.043 1.19.043 2.063V14c0 3.771 0 5.657-1.172 6.828S17.771 22 14 22" clip-rule="evenodd" opacity=".5"/><path fill="currentColor" d="m11.51 2.26l-.01 2.835c0 1.097 0 2.066.105 2.848c.114.847.375 1.694 1.067 2.385c.69.691 1.538.953 2.385 1.067c.781.105 1.751.105 2.848.105h4.052q.02.232.028.5H22c0-.268 0-.402-.01-.56a5.3 5.3 0 0 0-.958-2.641c-.094-.128-.158-.204-.285-.357C19.954 7.494 18.91 6.312 18 5.5c-.81-.724-1.921-1.515-2.89-2.161c-.832-.556-1.248-.834-1.819-1.04a6 6 0 0 0-.506-.154c-.384-.095-.758-.128-1.285-.14z"/>', group: 'Pembayaran' },
    { label: 'Riwayat Transaksi', route: 'mahasiswa.riwayat.index', icon: '<path fill="currentColor" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2S2 6.477 2 12c0 1.6.376 3.112 1.043 4.453c.178.356.237.763.134 1.148l-.595 2.226a1.3 1.3 0 0 0 1.591 1.592l2.226-.596a1.63 1.63 0 0 1 1.149.133A9.96 9.96 0 0 0 12 22Z" opacity=".5"/><path fill="currentColor" d="M16.807 19.011A8.46 8.46 0 0 1 12 20.5a8.46 8.46 0 0 1-4.807-1.489c-.604-.415-.862-1.205-.51-1.848C7.41 15.83 8.91 15 12 15s4.59.83 5.318 2.163c.35.643.093 1.433-.511 1.848M12 12a3 3 0 1 0 0-6a3 3 0 0 0 0 6"/>', group: 'Pembayaran' },
    { label: 'Dispensasi', route: 'mahasiswa.dispensasi.index', icon: '<path fill="currentColor" d="M6 2a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h9a3 3 0 0 0 3-3V9.5a.5.5 0 0 0-.146-.354l-5-5A.5.5 0 0 0 12.5 4H6a1 1 0 0 1 0-2h8a1 1 0 0 1 1 1v5.5a.5.5 0 0 0 .5.5H21a1 1 0 0 1 1 1V19a3 3 0 0 1-3 3h-1a1 1 0 1 1 0-2h1a1 1 0 0 0 1-1V10.5h-4.5A2.5 2.5 0 0 1 13 8V4.586L8.914 6.5A2.5 2.5 0 0 1 8.5 6.62V8a1 1 0 1 1-2 0V6.62a3 3 0 0 0-1.188.368A2.5 2.5 0 0 0 4 9.354V19a1 1 0 0 0 1 1h1a1 1 0 1 1 0 2H6Z" opacity=".5"/><path fill="currentColor" d="M8.5 11a1 1 0 0 1 1 1v5a1 1 0 1 1-2 0v-5a1 1 0 0 1 1-1M11 14a1 1 0 0 1 1 1v2a1 1 0 1 1-2 0v-2a1 1 0 0 1 1-1M14 13a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0v-3a1 1 0 0 1 1-1"/>', group: 'Pembayaran' },
    { label: 'Profil', route: 'profile.edit', icon: '<g fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="6" r="4"/><path d="M20 17.5c0 2.485 0 4.5-8 4.5s-8-2.015-8-4.5S7.582 13 12 13s8 2.015 8 4.5Z"/></g>', group: 'Akun' },
];

const currentLinks = computed(() => isAdmin.value ? adminLinks : mahasiswaLinks);
const groupedLinks = computed(() => {
    const groups = {};
    currentLinks.value.forEach(link => {
        if (!groups[link.group]) groups[link.group] = [];
        groups[link.group].push(link);
    });
    return groups;
});

// Expand state untuk semua group kecuali Dashboard
const groupOpen = ref({});
const isLinkActive = (linkRoute) => {
    return route().current(linkRoute) || route().current(linkRoute.replace('.index', '.*'));
};
const isGroupActive = (group, items) => items.some(it => isLinkActive(it.route));

watch(() => groupedLinks.value, (groups) => {
    Object.entries(groups).forEach(([g, items]) => {
        if (g === 'Dashboard') return;
        if (groupOpen.value[g] === undefined) {
            groupOpen.value[g] = isGroupActive(g, items);
        }
    });
}, { immediate: true, deep: true });

watch(() => page.url, () => {
    Object.entries(groupedLinks.value).forEach(([g, items]) => {
        if (g !== 'Dashboard' && isGroupActive(g, items)) groupOpen.value[g] = true;
    });
});

const topbarOpen = ref(false);
const topbarMenuRef = ref(null);
const toggleTopbar = () => topbarOpen.value = !topbarOpen.value;
const closeTopbar = () => topbarOpen.value = false;
const onTopbarClickOutside = (e) => {
    if (!topbarMenuRef.value) return;
    if (!topbarMenuRef.value.contains(e.target)) closeTopbar();
};
const onTopbarEsc = (e) => { if (e.key === 'Escape') closeTopbar(); };
onMounted(() => {
    document.addEventListener('click', onTopbarClickOutside);
    document.addEventListener('keydown', onTopbarEsc);
});
onUnmounted(() => {
    document.removeEventListener('click', onTopbarClickOutside);
    document.removeEventListener('keydown', onTopbarEsc);
});

const logout = () => {
    window.axios.post(route('logout')).then(() => {
        window.location.href = '/';
    });
};
</script>

<template>
    <div class="app-shell" data-stisla-app-shell data-stisla-app-shell-auto-collapse="true" :style="themeStyle">
        <!-- SIDEBAR -->
        <aside class="sidebar sidebar--lg sidebar--app" data-stisla-sidebar :style="sidebarStyle">
            <!-- 1. Header & Brand -->
            <header class="sidebar__header">
                <Link class="sidebar__brand" href="/">
                    <div class="sidebar__brand-logo-wrap" :class="{ 'has-img': theme?.logo_url || theme?.invoice_logo }">
                        <img v-if="theme?.logo_url || theme?.invoice_logo" :src="theme.logo_url || theme.invoice_logo" alt="Logo" class="sidebar__brand-logo" />
                        <div v-else class="sidebar__brand-logo-fallback">
                            <svg xmlns="http://www.w3.org/2000/svg" width="1.25em" height="1.25em" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 1.5l3.4 7.1 7.1 3.4-7.1 3.4-3.4 7.1-3.4-7.1L1.5 12l7.1-3.4z" opacity=".45"/>
                                <path d="M12 1.5l3.4 7.1L12 12 8.6 8.6z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="sidebar__brand-info">
                        <span class="sidebar__brand-title">{{ theme?.website_short_name || (isAdmin ? 'UKT Admin' : 'UKT Portal') }}</span>
                        <span class="sidebar__brand-sub">{{ theme?.invoice_institution_name || 'Universitas Bumigora' }}</span>
                    </div>
                </Link>
            </header>

            <!-- 2. Mini User Identity Badge in Sidebar -->
            <div class="sidebar__user-badge">
                <div class="sidebar__user-avatar-wrap">
                    <img class="sidebar__user-avatar" :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(displayName) + '&background=' + (isAdmin ? '2563eb' : '4f46e5') + '&color=fff&bold=true'" alt="User Avatar" />
                    <span class="sidebar__user-status-dot"></span>
                </div>
                <div class="sidebar__user-info">
                    <div class="sidebar__user-name" :title="displayName">{{ displayName }}</div>
                    <div class="sidebar__user-role">
                        <span class="role-pill" :class="isAdmin ? 'role-pill--admin' : 'role-pill--student'">
                            <span class="role-dot"></span>
                            {{ isAdmin ? 'Administrator' : (mahasiswa?.nim ? 'NIM: ' + mahasiswa.nim : 'Mahasiswa') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 3. Sidebar Navigation Menu -->
            <div class="sidebar__content">
                <nav class="sidebar__menu">
                    <template v-for="(items, group) in groupedLinks" :key="group">
                        <!-- Group collapsible -->
                        <div v-if="group !== 'Dashboard' && group !== 'Menu Utama'" class="sidebar__group sidebar__group--collapsible" :class="{ 'is-expanded': !!groupOpen[group] }">
                            <button class="sidebar__group-title sidebar__group-toggle" @click="groupOpen[group] = !groupOpen[group]" :aria-expanded="!!groupOpen[group]">
                                <span class="group-title-left">
                                    <span class="group-icon-pill">
                                        <svg v-if="group==='Manajemen'" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 13h2v8H3zM7 9h2v12H7zM11 5h2v16h-2zM15 9h2v12h-2zM19 13h2v8h-2z" opacity=".5"/><path fill="currentColor" d="M3 3h18v2H3z"/></svg>
                                        <svg v-else-if="group==='Master Data'" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3C7 3 2 5.5 2 8.5S7 14 12 14s10-2.5 10-5.5S17 3 12 3" opacity=".4"/><path fill="currentColor" d="M2 12c0 3 5 5.5 10 5.5S22 15 22 12v-3.5c0 3-5 5.5-10 5.5S2 15 2 12z"/><path fill="currentColor" d="M2 16.5c0 3 5 5.5 10 5.5s10-2.5 10-5.5V12c0 3-5 5.5-10 5.5S2 15 2 16.5z" opacity=".5"/></svg>
                                        <svg v-else-if="group==='System'" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19.5 12a7.5 7.5 0 0 1-1.1 4l1.5 1.5a9.5 9.5 0 0 0 0-11L18.4 8A7.48 7.48 0 0 1 19.5 12"/><path fill="currentColor" d="M12 8a4 4 0 1 0 0 8a4 4 0 0 0 0-8m0 6a2 2 0 1 1 0-4a2 2 0 0 1 0 4" opacity=".5"/><path fill="currentColor" d="M12 2a10 10 0 0 0-3.2.5l1.7 1.7A7.5 7.5 0 0 1 12 4a7.5 7.5 0 0 1 5.3 2.2l1.7-1.7A9.95 9.95 0 0 0 12 2"/></svg>
                                        <svg v-else-if="group==='Settings'" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"/><path d="M13.765 2.152C13.398 2 12.932 2 12 2s-1.398 0-1.765.152a2 2 0 0 0-1.083 1.083c-.092.223-.129.484-.143.863a1.62 1.62 0 0 1-.79 1.353a1.62 1.62 0 0 1-1.567.008c-.336-.178-.579-.276-.82-.308a2 2 0 0 0-1.478.396C4.04 5.79 3.806 6.193 3.34 7s-.7 1.21-.751 1.605a2 2 0 0 0 .396 1.479c.148.192.355.353.676.555c.473.297.777.803.777 1.361s-.304 1.064-.777 1.36c-.321.203-.529.364-.676.556a2 2 0 0 0-.396 1.479c.052.394.285.798.75 1.605c.467.807.7 1.21 1.015 1.453a2 2 0 0 0 1.479.396c.24-.032.483-.13.819-.308a1.62 1.62 0 0 1 1.567.008c.483.28.77.795.79 1.353c.014.38.05.64.143.863a2 2 0 0 0 1.083 1.083C10.602 22 11.068 22 12 22s1.398 0 1.765-.152a2 2 0 0 0 1.083-1.083c.092-.223.129-.483.143-.863c.02-.558.307-1.074.79-1.353a1.62 1.62 0 0 1 1.567-.008c.336.178.579.276.819.308a2 2 0 0 0 1.479-.396c.315-.242.548-.646 1.014-1.453s.7-1.21.751-1.605a2 2 0 0 0-.396-1.479c-.148-.192-.355-.353-.676-.555A1.62 1.62 0 0 1 19.562 12c0-.558.304-1.064.777-1.36c.321-.203.529-.364.676-.556a2 2 0 0 0 .396-1.479c-.052-.394-.285-.798-.75-1.605c-.467-.807-.7-1.21-1.015-1.453a2 2 0 0 0-1.479-.396c-.24.032-.483.13-.82.308a1.62 1.62 0 0 1-1.566-.008a1.62 1.62 0 0 1-.79-1.353c-.014-.38-.05-.64-.143-.863a2 2 0 0 0-1.083-1.083Z"/></g></svg>
                                        <svg v-else-if="group==='Operations'" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M5 5h14c1.1 0 2 .9 2 2v10c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2V7c0-1.1.9-2 2-2" opacity=".4"/><path fill="currentColor" d="M3 7h18v2H3zm0 4h18v2H3zm0 4h18v2H3z" opacity=".3"/><path fill="currentColor" d="M6 11h2v2H6zm0 4h2v2H6zm4-4h2v2h-2zm0 4h2v2h-2zm4-4h2v2h-2zm0 4h2v2h-2z"/></svg>
                                        <svg v-else-if="group==='Pembayaran'" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 22h-4c-3.771 0-5.657 0-6.828-1.172S2 17.771 2 14v-4c0-3.771 0-5.657 1.172-6.828S6.239 2 10.03 2c.606 0 1.091 0 1.5.017q-.02.12-.02.244l-.01 2.834c0 1.097 0 2.067.105 2.848c.114.847.375 1.694 1.067 2.386c.69.69 1.538.952 2.385 1.066c.781.105 1.751.105 2.848.105h4.052c.043.534.043 1.19.043 2.063V14c0 3.771 0 5.657-1.172 6.828S17.771 22 14 22" opacity=".5"/><path fill="currentColor" d="m11.51 2.26l-.01 2.835c0 1.097 0 2.066.105 2.848c.114.847.375 1.694 1.067 2.385c.69.691 1.538.953 2.385 1.067c.781.105 1.751.105 2.848.105h4.052q.02.232.028.5H22c0-.268 0-.402-.01-.56a5.3 5.3 0 0 0-.958-2.641c-.094-.128-.158-.204-.285-.357C19.954 7.494 18.91 6.312 18 5.5c-.81-.724-1.921-1.515-2.89-2.161c-.832-.556-1.248-.834-1.819-1.04a6 6 0 0 0-.506-.154c-.384-.095-.758-.128-1.285-.14z"/></svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2L2 7l2 2h16l2-2z" opacity=".5"/><path fill="currentColor" d="M12 9a3 3 0 1 0 0 6a3 3 0 0 0 0-6"/></svg>
                                    </span>
                                    <span class="group-title-text">{{ group }}</span>
                                </span>
                                <svg class="group-toggle-arrow" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true" :style="{ transform: groupOpen[group] ? 'rotate(180deg)' : 'rotate(0deg)', transition:'transform 0.25s cubic-bezier(0.16, 1, 0.3, 1)' }"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9l6 6l6-6"/></svg>
                            </button>
                            <ul v-show="groupOpen[group]" class="sidebar__list">
                                <li v-for="link in items" :key="link.route" class="sidebar__item">
                                    <Link class="sidebar__button" :href="route(link.route)" :class="{ 'is-active': isLinkActive(link.route) }">
                                        <span class="sidebar__icon-box">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="1.1em" height="1.1em" viewBox="0 0 24 24" aria-hidden="true" v-html="link.icon"></svg>
                                        </span>
                                        <span class="sidebar__label-text">{{ link.label }}</span>
                                        <span v-if="link.badge && link.badge() > 0" class="sidebar-badge">{{ link.badge() }}</span>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                        <div v-else class="sidebar__group">
                            <span class="sidebar__group-title group-title--static">{{ group }}</span>
                            <ul class="sidebar__list">
                                <li v-for="link in items" :key="link.route" class="sidebar__item">
                                    <Link class="sidebar__button" :href="route(link.route)" :class="{ 'is-active': isLinkActive(link.route) }">
                                        <span class="sidebar__icon-box">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="1.1em" height="1.1em" viewBox="0 0 24 24" aria-hidden="true" v-html="link.icon"></svg>
                                        </span>
                                        <span class="sidebar__label-text">{{ link.label }}</span>
                                        <span v-if="link.badge && link.badge() > 0" class="sidebar-badge">{{ link.badge() }}</span>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </template>
                </nav>
            </div>

            <!-- 4. Sidebar Footer -->
            <footer class="sidebar__footer">
                <div class="sidebar__footer-nav">
                    <a class="sidebar__button btn-logout" href="#" @click.prevent="logout" title="Keluar dari akun">
                        <span class="sidebar__icon-box text-rose">
                            <svg xmlns="http://www.w3.org/2000/svg" width="1.1em" height="1.1em" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="currentColor" d="M16 2h-1c-2.829 0-4.242 0-5.121.879S9 5.172 9 8v8c0 2.829 0 4.243.879 5.122c.878.878 2.292.878 5.119.878H16c2.828 0 4.242 0 5.121-.879C22 20.243 22 18.828 22 16V8c0-2.828 0-4.243-.879-5.121S18.828 2 16 2" opacity=".5"/>
                                <path fill="currentColor" fill-rule="evenodd" d="M15.75 12a.75.75 0 0 0-.75-.75H4.027l1.961-1.68a.75.75 0 1 0-.976-1.14l-3.5 3a.75.75 0 0 0 0 1.14l3.5 3a.75.75 0 1 0 .976-1.14l-1.96-1.68H15a.75.75 0 0 0 .75-.75" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <span class="sidebar__label-text font-semibold">Keluar Sistem</span>
                    </a>
                </div>
                <div class="sidebar__footer-meta">
                    <span>UKT Portal v2.4</span>
                    <span class="meta-dot">·</span>
                    <span>UBG Mataram</span>
                </div>
            </footer>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="app-shell__main">
            <!-- NAVBAR -->
            <header class="navbar" :style="navbarStyle">
                <div class="navbar-left-wrap">
                    <button type="button" class="button button--ghost button--neutral button--icon-only button--flush-start" data-stisla-app-shell-toggle="auto" aria-label="Toggle sidebar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="1.25em" height="1.25em" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.5" d="M20 7H4m16 5H4m16 5H4"/>
                        </svg>
                    </button>
                    <div class="navbar-context-pill">
                        <span class="nav-role-dot"></span>
                        <span class="nav-role-text">{{ isAdmin ? 'Panel Administrator' : 'Portal Mahasiswa' }}</span>
                    </div>
                </div>

                <div class="ms-auto">
                    <div class="flex gap-1">
                        <div class="menu" ref="topbarMenuRef">
                            <button type="button" class="button button--ghost button--neutral flex items-center gap-2 user-menu-btn" @click="toggleTopbar" aria-haspopup="menu" :aria-expanded="String(topbarOpen)" aria-controls="topbarUser">
                                <span class="hidden sm:inline font-medium user-display-name">{{ displayName }}</span>
                                <span class="avatar avatar--sm avatar--circle" data-stisla-avatar>
                                    <img class="avatar__image" :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(displayName) + '&background=' + (isAdmin ? '2563eb' : '4f46e5') + '&color=fff'" alt="" />
                                    <span class="avatar__fallback">{{ displayName?.charAt(0) || 'U' }}</span>
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true" :style="{ transform: topbarOpen ? 'rotate(180deg)' : 'rotate(0deg)', transition: 'transform 0.2s' }">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m19 9l-7 6l-7-6"/>
                                </svg>
                            </button>
                            <div class="menu__popup w-48" id="topbarUser" role="menu" :data-state="topbarOpen ? 'open' : 'closed'" v-show="topbarOpen" @click="closeTopbar">
                                <div class="menu__group" role="group" aria-labelledby="topbarUserHead">
                                    <h3 class="menu__group-label" id="topbarUserHead">{{ isAdmin ? 'Administrator' : displayName }}</h3>
                                    <Link :href="route('profile.edit')" class="menu__item" role="menuitem">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                                            <g fill="none" stroke="currentColor" stroke-width="1.5">
                                                <circle cx="12" cy="6" r="4"/>
                                                <path d="M20 17.5c0 2.485 0 4.5-8 4.5s-8-2.015-8-4.5S7.582 13 12 13s8 2.015 8 4.5Z"/>
                                            </g>
                                        </svg>
                                        Profil Akun
                                    </Link>
                                    <Link v-if="isAdmin" :href="route('admin.pengaturan.index')" class="menu__item" role="menuitem">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                                            <g fill="none" stroke="currentColor" stroke-width="1.5">
                                                <circle cx="12" cy="12" r="3"/>
                                                <path d="M13.765 2.152C13.398 2 12.932 2 12 2s-1.398 0-1.765.152a2 2 0 0 0-1.083 1.083c-.092.223-.129.484-.143.863a1.62 1.62 0 0 1-.79 1.353a1.62 1.62 0 0 1-1.567.008c-.336-.178-.579-.276-.82-.308a2 2 0 0 0-1.478.396C4.04 5.79 3.806 6.193 3.34 7s-.7 1.21-.751 1.605a2 2 0 0 0 .396 1.479c.148.192.355.353.676.555c.473.297.777.803.777 1.361s-.304 1.064-.777 1.36c-.321.203-.529.364-.676.556a2 2 0 0 0-.396 1.479c.052.394.285.798.75 1.605c.467.807.7 1.21 1.015 1.453a2 2 0 0 0 1.479.396c.24-.032.483-.13.819-.308a1.62 1.62 0 0 1 1.567.008c.483.28.77.795.79 1.353c.014.38.05.64.143.863a2 2 0 0 0 1.083 1.083C10.602 22 11.068 22 12 22s1.398 0 1.765-.152a2 2 0 0 0 1.083-1.083c.092-.223.129-.483.143-.863c.02-.558.307-1.074.79-1.353a1.62 1.62 0 0 1 1.567-.008c.336.178.579.276.819.308a2 2 0 0 0 1.479-.396c.315-.242.548-.646 1.014-1.453s.7-1.21.751-1.605a2 2 0 0 0-.396-1.479c-.148-.192-.355-.353-.676-.555A1.62 1.62 0 0 1 19.562 12c0-.558.304-1.064.777-1.36c.321-.203.529-.364.676-.556a2 2 0 0 0 .396-1.479c-.052-.394-.285-.798-.75-1.605c-.467-.807-.7-1.21-1.015-1.453a2 2 0 0 0-1.479-.396c-.24.032-.483.13-.82.308a1.62 1.62 0 0 1-1.566-.008a1.62 1.62 0 0 1-.79-1.353c-.014-.38-.05-.64-.143-.863a2 2 0 0 0-1.083-1.083Z"/>
                                            </g>
                                        </svg>
                                        Pengaturan Sistem
                                    </Link>
                                </div>
                                <hr class="menu__separator" role="separator" />
                                <a href="#" class="menu__item" role="menuitem" @click.prevent="logout">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                                        <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.5">
                                            <path d="M9.002 7c.012-2.175.109-3.353.877-4.121C10.758 2 12.172 2 15 2h1c2.829 0 4.243 0 5.122.879C22 3.757 22 5.172 22 8v8c0 2.828 0 4.243-.878 5.121C20.242 22 18.829 22 16 22h-1c-2.828 0-4.242 0-5.121-.879c-.768-.768-.865-1.946-.877-4.121"/>
                                            <path stroke-linejoin="round" d="M15 12H2m0 0l3.5-3M2 12l3.5 3"/>
                                        </g>
                                    </svg>
                                    Keluar (Logout)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- PAGE CONTENT -->
            <div class="content">
                <div v-if="$slots.header" class="content__header">
                    <slot name="header" />
                </div>
                <slot />
            </div>
        </main>
    </div>

    <!-- BOTTOM NAV (Mahasiswa Mobile) -->
    <nav v-if="!isAdmin" class="bottom-nav">
        <Link :href="route('mahasiswa.dashboard')" class="bottom-nav-item" :class="{ active: route().current('mahasiswa.dashboard') }">
            <div class="bottom-nav-icon"><i class="fas fa-home"></i></div>
            <div class="bottom-nav-label">Dashboard</div>
        </Link>
        <Link :href="route('mahasiswa.tagihan.index')" class="bottom-nav-item" :class="{ active: route().current('mahasiswa.tagihan.*') }">
            <div class="bottom-nav-icon"><i class="fas fa-receipt"></i></div>
            <div class="bottom-nav-label">Tagihan</div>
        </Link>
        <Link :href="route('mahasiswa.riwayat.index')" class="bottom-nav-item" :class="{ active: route().current('mahasiswa.riwayat.*') }">
            <div class="bottom-nav-icon">
                <i class="fas fa-history"></i>
                <span v-if="belumBayarCount > 0" class="bottom-nav-badge">{{ belumBayarCount }}</span>
            </div>
            <div class="bottom-nav-label">Riwayat</div>
        </Link>
        <Link :href="route('mahasiswa.dispensasi.index')" class="bottom-nav-item" :class="{ active: route().current('mahasiswa.dispensasi.*') }">
            <div class="bottom-nav-icon"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="bottom-nav-label">Dispensasi</div>
        </Link>
        <Link :href="route('profile.edit')" class="bottom-nav-item" :class="{ active: route().current('profile.edit') }">
            <div class="bottom-nav-icon"><i class="fas fa-user"></i></div>
            <div class="bottom-nav-label">Profil</div>
        </Link>
    </nav>

    <Toast />
</template>

<style>
/* ==========================================================================
   SIDEBAR & MODERN APP SHELL STYLES
   ========================================================================== */

/* --- Custom Slim Scrollbar for Sidebar --- */
.sidebar__content::-webkit-scrollbar {
    width: 4px;
}
.sidebar__content::-webkit-scrollbar-track {
    background: transparent;
}
.sidebar__content::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.12);
    border-radius: 9999px;
}
.sidebar__content::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.25);
}

/* --- Base Sidebar Shell --- */
.sidebar[data-stisla-sidebar] {
    background: var(--sidebar-bg, #0f172a) !important;
    color: var(--sidebar-text, #94a3b8) !important;
    border-right: 1px solid rgba(255, 255, 255, 0.07);
    display: flex !important;
    flex-direction: column !important;
    transition: width 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

/* --- 1. Header & Brand --- */
.sidebar__header {
    background: transparent !important;
    padding: 1.15rem 1.15rem 0.65rem !important;
    border-bottom: none !important;
}

.sidebar__brand {
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
    text-decoration: none !important;
    padding: 0.35rem 0.25rem !important;
    min-width: 0 !important;
    border-radius: 0.75rem;
    transition: opacity 0.15s ease;
}

.sidebar__brand:hover {
    opacity: 0.92;
}

.sidebar__brand-logo-wrap {
    width: 38px;
    height: 38px;
    border-radius: 0.65rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    padding: 0.25rem;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.sidebar__brand-logo-wrap.has-img {
    background: #ffffff;
    border-color: rgba(255, 255, 255, 0.3);
}

.sidebar__brand-logo {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.sidebar__brand-logo-fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #38bdf8;
    font-size: 1.1rem;
}

.sidebar__brand-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
    overflow: hidden;
}

.sidebar__brand-title {
    font-size: 0.9375rem !important;
    font-weight: 800 !important;
    color: var(--logo-text, #ffffff) !important;
    line-height: 1.25 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    letter-spacing: -0.015em !important;
}

.sidebar__brand-sub {
    font-size: 0.6875rem !important;
    font-weight: 500 !important;
    color: var(--sidebar-text, #94a3b8) !important;
    opacity: 0.75 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    line-height: 1.3 !important;
}

/* --- 2. User Identity Badge in Sidebar --- */
.sidebar__user-badge {
    margin: 0.35rem 0.85rem 0.75rem;
    padding: 0.6rem 0.75rem;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    backdrop-filter: blur(8px);
}

.sidebar__user-avatar-wrap {
    position: relative;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
}

.sidebar__user-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 1.5px solid rgba(255, 255, 255, 0.2);
}

.sidebar__user-status-dot {
    position: absolute;
    bottom: -1px;
    right: -1px;
    width: 8px;
    height: 8px;
    background: #10b981;
    border: 1.5px solid var(--sidebar-bg, #0f172a);
    border-radius: 50%;
}

.sidebar__user-info {
    flex: 1;
    min-width: 0;
}

.sidebar__user-name {
    font-size: 0.78125rem;
    font-weight: 700;
    color: var(--sidebar-active-text, #ffffff);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.25;
}

.sidebar__user-role {
    margin-top: 0.15rem;
    line-height: 1;
}

.role-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.625rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    padding: 0.1rem 0.4rem;
    border-radius: 0.35rem;
}

.role-pill .role-dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
}

.role-pill--admin {
    background: rgba(59, 130, 246, 0.18);
    color: #93c5fd;
    border: 1px solid rgba(59, 130, 246, 0.3);
}
.role-pill--admin .role-dot {
    background: #60a5fa;
}

.role-pill--student {
    background: rgba(99, 102, 241, 0.18);
    color: #c7d2fe;
    border: 1px solid rgba(99, 102, 241, 0.3);
}
.role-pill--student .role-dot {
    background: #818cf8;
}

/* --- 3. Sidebar Content & Navigation Menu --- */
.sidebar__content {
    background: transparent !important;
    padding: 0 0.85rem 1rem !important;
    flex: 1;
    overflow-y: auto;
}

.sidebar__menu {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.sidebar__group {
    margin-bottom: 0.35rem;
}

.sidebar__group-title {
    color: var(--sidebar-text, #94a3b8) !important;
    opacity: 0.85;
}

.sidebar__group-title.group-title--static {
    display: block;
    font-size: 0.65625rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.65rem 0.6rem 0.25rem;
    color: var(--sidebar-text, #94a3b8);
}

.sidebar__group-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: none;
    border: none;
    cursor: pointer;
    font: inherit;
    padding: 0.45rem 0.55rem;
    border-radius: 0.5rem;
    color: var(--sidebar-text, #94a3b8);
    transition: all 0.15s ease-in-out;
}

.sidebar__group-toggle:hover {
    color: var(--sidebar-active-text, #ffffff);
    background: var(--sidebar-hover-bg, rgba(255, 255, 255, 0.05));
}

.group-title-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.group-icon-pill {
    width: 20px;
    height: 20px;
    border-radius: 0.35rem;
    background: rgba(255, 255, 255, 0.06);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    color: var(--sidebar-icon, #94a3b8);
}

.group-title-text {
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.group-toggle-arrow {
    font-size: 0.75rem;
    opacity: 0.6;
}

.sidebar__list {
    list-style: none;
    padding: 0.15rem 0 0.25rem;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.sidebar__item {
    margin: 0;
    padding: 0;
}

/* --- Modern Sidebar Nav Buttons --- */
.sidebar__button {
    display: flex !important;
    align-items: center !important;
    gap: 0.65rem !important;
    padding: 0.55rem 0.75rem !important;
    border-radius: 0.625rem !important;
    color: var(--sidebar-text, #94a3b8) !important;
    text-decoration: none !important;
    font-size: 0.8125rem !important;
    font-weight: 600 !important;
    line-height: 1.3 !important;
    transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1) !important;
    position: relative;
    border: 1px solid transparent;
}

.sidebar__button:hover {
    background: var(--sidebar-hover-bg, rgba(255, 255, 255, 0.06)) !important;
    color: var(--sidebar-active-text, #ffffff) !important;
    transform: translateX(3px);
}

.sidebar__button.is-active {
    background: var(--sidebar-active-bg, #2563eb) !important;
    color: var(--sidebar-active-text, #ffffff) !important;
    font-weight: 700 !important;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.28);
    border-color: rgba(255, 255, 255, 0.15);
}

.sidebar__button.is-active::before {
    content: '';
    position: absolute;
    left: -0.85rem;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 18px;
    border-radius: 0 4px 4px 0;
    background: #60a5fa;
    box-shadow: 0 0 8px rgba(96, 165, 250, 0.8);
}

.sidebar__icon-box {
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: var(--sidebar-icon, currentColor);
    transition: color 0.15s ease;
}

.sidebar__button:hover .sidebar__icon-box,
.sidebar__button.is-active .sidebar__icon-box {
    color: var(--sidebar-active-text, #ffffff) !important;
}

.sidebar__label-text {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 0.375rem;
    margin-left: auto;
    background: #ef4444;
    color: #ffffff;
    font-size: 0.65625rem;
    font-weight: 800;
    border-radius: 9999px;
    line-height: 1;
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
}

/* --- 4. Sidebar Footer --- */
.sidebar__footer {
    background: transparent !important;
    padding: 0.65rem 0.85rem 0.85rem !important;
    border-top: 1px solid rgba(255, 255, 255, 0.07);
}

.sidebar__footer-nav {
    display: flex;
    flex-direction: column;
}

.btn-logout {
    color: #cbd5e1 !important;
}

.btn-logout:hover {
    background: rgba(239, 68, 68, 0.12) !important;
    color: #f87171 !important;
    border-color: rgba(239, 68, 68, 0.2);
}

.sidebar__footer-meta {
    margin-top: 0.65rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    font-size: 0.65625rem;
    font-weight: 600;
    color: var(--sidebar-text, #64748b);
    opacity: 0.6;
}

.meta-dot {
    opacity: 0.4;
}

.text-rose {
    color: #fb7185 !important;
}

/* ==========================================================================
   NAVBAR & TOPBAR STYLES
   ========================================================================== */
.navbar {
    background: var(--navbar-bg, #ffffff) !important;
    border-bottom: 1px solid var(--navbar-border, #e2e8f0) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 0.75rem 1.5rem !important;
    min-height: 64px;
}

.navbar-left-wrap {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.navbar-context-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.3rem 0.75rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9999px;
    font-size: 0.71875rem;
    font-weight: 700;
    color: var(--navbar-text, #0f172a);
}

.nav-role-dot {
    width: 6px;
    height: 6px;
    background: #2563eb;
    border-radius: 50%;
}

.user-menu-btn {
    border-radius: 9999px !important;
    padding: 0.25rem 0.65rem !important;
    border: 1px solid #e2e8f0 !important;
    background: #ffffff !important;
    transition: all 0.15s ease;
}

.user-menu-btn:hover {
    background: #f8fafc !important;
    border-color: #cbd5e1 !important;
}

.user-display-name {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1e293b;
}

.navbar .button--ghost {
    color: var(--navbar-text, #1e293b) !important;
}

/* ==========================================================================
   CONTENT LAYOUT & TOPBAR MENU
   ========================================================================== */
.app-shell__main {
    padding-top: 0;
}

.page.content {
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
    background: var(--content-bg, #f8fafc);
    color: var(--content-text, #1e293b);
}

.content__header {
    padding: 0 1.5rem 1rem;
}

.menu { position: relative; }
.menu__popup {
    position: absolute !important;
    top: calc(100% + 0.5rem) !important;
    right: 0 !important;
    left: auto !important;
    z-index: 50 !important;
    min-width: 13.5rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    padding: 0.5rem 0;
}
.menu__popup[data-state="closed"] { display: none !important; }
.menu__popup[data-state="open"] { display: block !important; }

.menu__item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.55rem 1.15rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #334155;
    text-decoration: none;
    transition: background 0.15s, color 0.15s;
}

.menu__item:hover {
    background: #f1f5f9;
    color: #0f172a;
}

/* ==========================================================================
   MOBILE BOTTOM NAVIGATION (MAHASISWA)
   ========================================================================== */
.bottom-nav {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: calc(60px + env(safe-area-inset-bottom, 0px));
    padding-bottom: env(safe-area-inset-bottom, 0px);
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-top: 1px solid rgba(226, 232, 240, 0.85);
    z-index: 99;
    box-shadow: 0 -4px 20px rgba(15, 23, 42, 0.05);
    justify-content: space-around;
    align-items: center;
}

@media (max-width: 768px) {
    .bottom-nav {
        display: flex;
    }
    .app-shell__main {
        padding-bottom: calc(76px + env(safe-area-inset-bottom, 0px)) !important;
    }
    .page.content {
        padding-top: 1rem;
        padding-bottom: 1.25rem;
    }
    .content__header {
        padding: 0 1rem 0.75rem;
    }
    .menu__popup {
        right: 0.5rem !important;
        max-width: calc(100vw - 1rem);
    }
}

.bottom-nav-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.2rem;
    text-decoration: none;
    color: #64748b;
    padding: 0.35rem 0.15rem;
    position: relative;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.bottom-nav-icon {
    position: relative;
    font-size: 1.15rem;
    line-height: 1;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.bottom-nav-label {
    font-size: 0.625rem;
    font-weight: 600;
    line-height: 1;
    transition: color 0.15s ease;
}

.bottom-nav-item:hover,
.bottom-nav-item:active {
    color: #334155;
}

.bottom-nav-item.active {
    color: #4f46e5;
}

.bottom-nav-item.active .bottom-nav-icon {
    transform: translateY(-2px);
    color: #4f46e5;
}

.bottom-nav-item.active .bottom-nav-label {
    font-weight: 700;
    color: #4f46e5;
}

.bottom-nav-badge {
    position: absolute;
    top: -4px;
    right: -8px;
    min-width: 14px;
    height: 14px;
    padding: 0 3px;
    border-radius: 9999px;
    background: #ef4444;
    color: #ffffff;
    font-size: 0.5625rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(239, 68, 68, 0.4);
}
</style>
