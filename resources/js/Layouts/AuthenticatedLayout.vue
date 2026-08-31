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
    { label: 'Jenis Komponen Biaya', route: 'admin.komponen-biaya.index', icon: '<path fill="currentColor" d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>', group: 'Master Data' },
    { label: 'Pengaturan Biaya', route: 'admin.biaya.index', icon: '<path fill="currentColor" d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>', group: 'Master Data' },
    { label: 'Jenis Beasiswa', route: 'admin.jenis-beasiswa.index', icon: '<path fill="currentColor" d="M7 7h10v2H7zM7 11h10v2H7zM7 15h6v2H7z" opacity=".5"/><path fill="currentColor" d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5zM3 5v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2z"/>', group: 'Master Data' },
    { label: 'Beasiswa', route: 'admin.beasiswa.index', icon: '<path fill="currentColor" d="M12 2L2 7l5 2v2.5c0 2.94 2.14 5.69 5 6.5c2.86-.81 5-3.56 5-6.5V9l5-2z" opacity=".4"/><path fill="currentColor" d="M12 12.5c-2.86-.81-5-3.56-5-6.5V7l5 2.5V12.5z"/><path fill="currentColor" d="M12 13l5-2V7l-5 2z"/>', group: 'Master Data' },
    { label: 'Pengaturan Tahun Akademik', route: 'admin.tahun-akademik.index', icon: '<path fill="currentColor" d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/><path fill="currentColor" d="M9 10H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/>', group: 'Settings' },
    { label: 'Pengaturan Semester', route: 'admin.semester-aktif.index', icon: '<path fill="currentColor" d="M7 11h2v2H7zm0 4h2v2H7zm4-4h2v2h-2zm0 4h2v2h-2zm4-4h2v2h-2zm0 4h2v2h-2z" opacity=".5"/><path fill="currentColor" d="M5 22h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2zM5 4h14v14H5z"/><path fill="currentColor" d="M12 17.5l-1.41-1.41L13.17 13.5H7v-1.5h6.17l-2.58-2.59L12 8l4 4z"/>', group: 'Settings' },
    { label: 'Pengaturan User', route: 'admin.user.index', icon: '<path fill="currentColor" d="M9 6a3 3 0 1 1-6 0a3 3 0 0 1 6 0" opacity=".4"/><path fill="currentColor" d="M17 6a3 3 0 1 1-6 0a3 3 0 0 1 6 0M12 11a5 5 0 0 1 5 5v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a5 5 0 0 1 5-5z" opacity=".4"/><path fill="currentColor" d="M23 20a3 3 0 0 0-3-3h-4.1a6 6 0 0 0-2.1-1.9c.7.2 1.4.4 2.2.4a5 5 0 0 1 5 5v.5A1.5 1.5 0 0 1 19.5 22H17v-2h6zM9 11a5 5 0 0 1 5 5v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3v2h6v-2a3 3 0 0 0-3-3"/>', group: 'Settings' },
    { label: 'Profil Website', route: 'admin.profil-website.index', icon: '<path fill="currentColor" d="M4 7a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3z" opacity=".4"/><path fill="currentColor" d="M7 5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h1V5zm7.5 3.5a1.5 1.5 0 1 1 0 3a1.5 1.5 0 0 1 0-3M12 18a4 4 0 0 1 4-4v4z"/>', group: 'Settings' },
    { label: 'Pengaturan Tema', route: 'admin.pengaturan.index', icon: '<path fill="currentColor" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10" opacity=".4"/><path fill="currentColor" d="M13.765 2.152C13.398 2 12.932 2 12 2s-1.398 0-1.765.152a2 2 0 0 0-1.083 1.083c-.092.223-.129.484-.143.863a1.62 1.62 0 0 1-.79 1.353 1.62 1.62 0 0 1-1.567.008c-.336-.178-.579-.276-.82-.308a2 2 0 0 0-1.478.396C4.04 5.79 3.806 6.193 3.34 7s-.7 1.21-.751 1.605a2 2 0 0 0 .396 1.479c.148.192.355.353.676.555c.473.297.777.803.777 1.361s-.304 1.064-.777 1.36c-.321.203-.529.364-.676.556a2 2 0 0 0-.396 1.479c.052.394.285.798.75 1.605c.467.807.7 1.21 1.015 1.453a2 2 0 0 0 1.479.396c.24-.032.483-.13.819-.308a1.62 1.62 0 0 1 1.567.008c.483.28.77.795.79 1.353c.014.38.05.64.143.863a2 2 0 0 0 1.083 1.083C10.602 22 11.068 22 12 22s1.398 0 1.765-.152a2 2 0 0 0 1.083-1.083c.092-.223.129-.483.143-.863c.02-.558.307-1.074.79-1.353a1.62 1.62 0 0 1 1.567-.008c.336.178.579.276.819.308a2 2 0 0 0 1.479-.396c.315-.242.548-.646 1.014-1.453s.7-1.21.751-1.605a2 2 0 0 0-.396-1.479c-.148-.192-.355-.353-.676-.555A1.62 1.62 0 0 1 19.562 12c0-.558.304-1.064.777-1.36c.321-.203.529-.364.676-.556a2 2 0 0 0 .396-1.479c-.052-.394-.285-.798-.75-1.605c-.467-.807-.7-1.21-1.015-1.453a2 2 0 0 0-1.479-.396c-.24.032-.483.13-.82.308a1.62 1.62 0 0 1-1.566-.008a1.62 1.62 0 0 1-.79-1.353c-.014-.38-.05-.64-.143-.863a2 2 0 0 0-1.083-1.083Z"/>', group: 'Settings' },
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
const isGroupActive = (group, items) => items.some(it => route().current(it.route));
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
            <header class="sidebar__header">
                <a class="sidebar__brand" href="/">
                    <img v-if="theme?.invoice_logo" :src="theme.invoice_logo" alt="Logo" class="sidebar__brand-logo" />
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 1.5l3.4 7.1 7.1 3.4-7.1 3.4-3.4 7.1-3.4-7.1L1.5 12l7.1-3.4z" opacity=".45"/>
                        <path d="M12 1.5l3.4 7.1L12 12 8.6 8.6z"/>
                    </svg>
                    <span>{{ isAdmin ? (theme?.website_name || 'UKT System') : (theme?.website_short_name || theme?.website_name || 'UKT Portal') }}</span>
                </a>
            </header>

            <div class="sidebar__content">
                <nav class="sidebar__menu">
                    <template v-for="(items, group) in groupedLinks" :key="group">
                        <!-- Semua group kecuali Dashboard jadi collapsible -->
                        <div v-if="group !== 'Dashboard'" class="sidebar__group sidebar__group--collapsible">
                            <button class="sidebar__group-title sidebar__group-toggle" @click="groupOpen[group] = !groupOpen[group]" :aria-expanded="!!groupOpen[group]">
                                <span style="display:flex;align-items:center;gap:0.5rem;">
                                    <svg v-if="group==='Manajemen'" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 13h2v8H3zM7 9h2v12H7zM11 5h2v16h-2zM15 9h2v12h-2zM19 13h2v8h-2z" opacity=".5"/><path fill="currentColor" d="M3 3h18v2H3z"/></svg>
                                    <svg v-else-if="group==='Master Data'" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3C7 3 2 5.5 2 8.5S7 14 12 14s10-2.5 10-5.5S17 3 12 3" opacity=".4"/><path fill="currentColor" d="M2 12c0 3 5 5.5 10 5.5S22 15 22 12v-3.5c0 3-5 5.5-10 5.5S2 15 2 12z"/><path fill="currentColor" d="M2 16.5c0 3 5 5.5 10 5.5s10-2.5 10-5.5V12c0 3-5 5.5-10 5.5S2 15 2 16.5z" opacity=".5"/></svg>
                                    <svg v-else-if="group==='System'" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19.5 12a7.5 7.5 0 0 1-1.1 4l1.5 1.5a9.5 9.5 0 0 0 0-11L18.4 8A7.48 7.48 0 0 1 19.5 12"/><path fill="currentColor" d="M12 8a4 4 0 1 0 0 8a4 4 0 0 0 0-8m0 6a2 2 0 1 1 0-4a2 2 0 0 1 0 4" opacity=".5"/><path fill="currentColor" d="M12 2a10 10 0 0 0-3.2.5l1.7 1.7A7.5 7.5 0 0 1 12 4a7.5 7.5 0 0 1 5.3 2.2l1.7-1.7A9.95 9.95 0 0 0 12 2"/></svg>
                                    <svg v-else-if="group==='Settings'" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"/><path d="M13.765 2.152C13.398 2 12.932 2 12 2s-1.398 0-1.765.152a2 2 0 0 0-1.083 1.083c-.092.223-.129.484-.143.863a1.62 1.62 0 0 1-.79 1.353a1.62 1.62 0 0 1-1.567.008c-.336-.178-.579-.276-.82-.308a2 2 0 0 0-1.478.396C4.04 5.79 3.806 6.193 3.34 7s-.7 1.21-.751 1.605a2 2 0 0 0 .396 1.479c.148.192.355.353.676.555c.473.297.777.803.777 1.361s-.304 1.064-.777 1.36c-.321.203-.529.364-.676.556a2 2 0 0 0-.396 1.479c.052.394.285.798.75 1.605c.467.807.7 1.21 1.015 1.453a2 2 0 0 0 1.479.396c.24-.032.483-.13.819-.308a1.62 1.62 0 0 1 1.567.008c.483.28.77.795.79 1.353c.014.38.05.64.143.863a2 2 0 0 0 1.083 1.083C10.602 22 11.068 22 12 22s1.398 0 1.765-.152a2 2 0 0 0 1.083-1.083c.092-.223.129-.483.143-.863c.02-.558.307-1.074.79-1.353a1.62 1.62 0 0 1 1.567-.008c.336.178.579.276.819.308a2 2 0 0 0 1.479-.396c.315-.242.548-.646 1.014-1.453s.7-1.21.751-1.605a2 2 0 0 0-.396-1.479c-.148-.192-.355-.353-.676-.555A1.62 1.62 0 0 1 19.562 12c0-.558.304-1.064.777-1.36c.321-.203.529-.364.676-.556a2 2 0 0 0 .396-1.479c-.052-.394-.285-.798-.75-1.605c-.467-.807-.7-1.21-1.015-1.453a2 2 0 0 0-1.479-.396c-.24.032-.483.13-.82.308a1.62 1.62 0 0 1-1.566-.008a1.62 1.62 0 0 1-.79-1.353c-.014-.38-.05-.64-.143-.863a2 2 0 0 0-1.083-1.083Z"/></g></svg>
                                    <svg v-else-if="group==='Operations'" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M5 5h14c1.1 0 2 .9 2 2v10c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2V7c0-1.1.9-2 2-2" opacity=".4"/><path fill="currentColor" d="M3 7h18v2H3zm0 4h18v2H3zm0 4h18v2H3z" opacity=".3"/><path fill="currentColor" d="M6 11h2v2H6zm0 4h2v2H6zm4-4h2v2h-2zm0 4h2v2h-2zm4-4h2v2h-2zm0 4h2v2h-2z"/></svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2L2 7l2 2h16l2-2z" opacity=".5"/><path fill="currentColor" d="M12 9a3 3 0 1 0 0 6a3 3 0 0 0 0-6"/></svg>
                                    {{ group }}
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true" :style="{ transform: groupOpen[group] ? 'rotate(180deg)' : 'rotate(0deg)', transition:'transform 0.2s' }"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m6 9l6 6l6-6"/></svg>
                            </button>
                            <ul v-show="groupOpen[group]" class="sidebar__list">
                                <li v-for="link in items" :key="link.route" class="sidebar__item">
                                    <Link class="sidebar__button" :href="route(link.route)" :class="{ 'is-active': route().current(link.route) }">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true" v-html="link.icon"></svg>
                                        <span>{{ link.label }}</span>
                                        <span v-if="link.badge && link.badge() > 0" class="sidebar-badge">{{ link.badge() }}</span>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                        <div v-else class="sidebar__group">
                            <span class="sidebar__group-title">{{ group }}</span>
                            <ul class="sidebar__list">
                                <li v-for="link in items" :key="link.route" class="sidebar__item">
                                    <Link class="sidebar__button" :href="route(link.route)" :class="{ 'is-active': route().current(link.route) }">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true" v-html="link.icon"></svg>
                                        <span>{{ link.label }}</span>
                                        <span v-if="link.badge && link.badge() > 0" class="sidebar-badge">{{ link.badge() }}</span>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </template>
                </nav>
            </div>

            <footer class="sidebar__footer">
                <ul class="sidebar__list">
                    <li class="sidebar__item">
                        <a class="sidebar__button" href="#" @click.prevent="logout">
                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="currentColor" d="M16 2h-1c-2.829 0-4.242 0-5.121.879S9 5.172 9 8v8c0 2.829 0 4.243.879 5.122c.878.878 2.292.878 5.119.878H16c2.828 0 4.242 0 5.121-.879C22 20.243 22 18.828 22 16V8c0-2.828 0-4.243-.879-5.121S18.828 2 16 2" opacity=".5"/>
                                <path fill="currentColor" fill-rule="evenodd" d="M15.75 12a.75.75 0 0 0-.75-.75H4.027l1.961-1.68a.75.75 0 1 0-.976-1.14l-3.5 3a.75.75 0 0 0 0 1.14l3.5 3a.75.75 0 1 0 .976-1.14l-1.96-1.68H15a.75.75 0 0 0 .75-.75" clip-rule="evenodd"/>
                            </svg>
                            <span>Log out</span>
                        </a>
                    </li>
                </ul>
            </footer>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="app-shell__main">
            <!-- NAVBAR -->
            <header class="navbar" :style="navbarStyle">
                <button type="button" class="button button--ghost button--neutral button--icon-only button--flush-start" data-stisla-app-shell-toggle="auto" aria-label="Toggle sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.5" d="M20 7H4m16 5H4m16 5H4"/>
                    </svg>
                </button>



                <div class="ms-auto">
                    <div class="flex gap-1">
                        <div class="menu" ref="topbarMenuRef">
                            <button type="button" class="button button--ghost button--neutral flex items-center gap-2" @click="toggleTopbar" aria-haspopup="menu" :aria-expanded="String(topbarOpen)" aria-controls="topbarUser">
                                <span class="hidden sm:inline font-medium">{{ displayName }}</span>
                                <span class="avatar avatar--sm avatar--circle" data-stisla-avatar>
                                    <img class="avatar__image" :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(displayName) + '&background=' + (isAdmin ? '6777ef' : '4f46e5') + '&color=fff'" alt="" />
                                    <span class="avatar__fallback">{{ displayName?.charAt(0) || 'U' }}</span>
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true" :style="{ transform: topbarOpen ? 'rotate(180deg)' : 'rotate(0deg)', transition: 'transform 0.2s' }">
                                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m19 9l-7 6l-7-6"/>
                                </svg>
                            </button>
                            <div class="menu__popup w-48" id="topbarUser" role="menu" :data-state="topbarOpen ? 'open' : 'closed'" v-show="topbarOpen" @click="closeTopbar">
                                <div class="menu__group" role="group" aria-labelledby="topbarUserHead">
                                        <h3 class="menu__group-label" id="topbarUserHead">{{ isAdmin ? 'Administrator' : displayName }}</h3>
                                    <a :href="route('profile.edit')" class="menu__item" role="menuitem">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                                            <g fill="none" stroke="currentColor" stroke-width="1.5">
                                                <circle cx="12" cy="6" r="4"/>
                                                <path d="M20 17.5c0 2.485 0 4.5-8 4.5s-8-2.015-8-4.5S7.582 13 12 13s8 2.015 8 4.5Z"/>
                                            </g>
                                        </svg>
                                        Profile
                                    </a>
                                    <a href="#" class="menu__item" role="menuitem">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                                                 <g fill="none" stroke="currentColor" stroke-width="1.5">
                                                 <circle cx="12" cy="12" r="3"/>
                                                 <path d="M13.765 2.152C13.398 2 12.932 2 12 2s-1.398 0-1.765.152a2 2 0 0 0-1.083 1.083c-.092.223-.129.484-.143.863a1.62 1.62 0 0 1-.79 1.353a1.62 1.62 0 0 1-1.567.008c-.336-.178-.579-.276-.82-.308a2 2 0 0 0-1.478.396C4.04 5.79 3.806 6.193 3.34 7s-.7 1.21-.751 1.605a2 2 0 0 0 .396 1.479c.148.192.355.353.676.555c.473.297.777.803.777 1.361s-.304 1.064-.777 1.36c-.321.203-.529.364-.676.556a2 2 0 0 0-.396 1.479c.052.394.285.798.75 1.605c.467.807.7 1.21 1.015 1.453a2 2 0 0 0 1.479.396c.24-.032.483-.13.819-.308a1.62 1.62 0 0 1 1.567.008c.483.28.77.795.79 1.353c.014.38.05.64.143.863a2 2 0 0 0 1.083 1.083C10.602 22 11.068 22 12 22s1.398 0 1.765-.152a2 2 0 0 0 1.083-1.083c.092-.223.129-.483.143-.863c.02-.558.307-1.074.79-1.353a1.62 1.62 0 0 1 1.567-.008c.336.178.579.276.819.308a2 2 0 0 0 1.479-.396c.315-.242.548-.646 1.014-1.453s.7-1.21.751-1.605a2 2 0 0 0-.396-1.479c-.148-.192-.355-.353-.676-.555A1.62 1.62 0 0 1 19.562 12c0-.558.304-1.064.777-1.36c.321-.203.529-.364.676-.556a2 2 0 0 0 .396-1.479c-.052-.394-.285-.798-.75-1.605c-.467-.807-.7-1.21-1.015-1.453a2 2 0 0 0-1.479-.396c-.24.032-.483.13-.82.308a1.62 1.62 0 0 1-1.566-.008a1.62 1.62 0 0 1-.79-1.353c-.014-.38-.05-.64-.143-.863a2 2 0 0 0-1.083-1.083Z"/>
                                                 </g>
                                         </svg>
                                        Settings
                                    </a>
                                </div>
                                <hr class="menu__separator" role="separator" />
                                <a href="#" class="menu__item" role="menuitem" @click.prevent="logout">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true">
                                        <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.5">
                                            <path d="M9.002 7c.012-2.175.109-3.353.877-4.121C10.758 2 12.172 2 15 2h1c2.829 0 4.243 0 5.122.879C22 3.757 22 5.172 22 8v8c0 2.828 0 4.243-.878 5.121C20.242 22 18.829 22 16 22h-1c-2.828 0-4.242 0-5.121-.879c-.768-.768-.865-1.946-.877-4.121"/>
                                            <path stroke-linejoin="round" d="M15 12H2m0 0l3.5-3M2 12l3.5 3"/>
                                        </g>
                                    </svg>
                                    Log out
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
        <Link :href="route('profile.edit')" class="bottom-nav-item" :class="{ active: route().current('profile.edit') }">
            <div class="bottom-nav-icon"><i class="fas fa-user"></i></div>
            <div class="bottom-nav-label">Profil</div>
        </Link>
    </nav>

    <Toast />
</template>

<style>
.sidebar__group-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: none;
    border: none;
    cursor: pointer;
    font: inherit;
    padding: 0.25rem 0;
    color: var(--sidebar-text, #94a3b8);
}
.sidebar__group-toggle:hover { color: var(--sidebar-active-text, #fff); }
.sidebar-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 0.375rem;
    margin-left: 0.5rem;
    background: #ef4444;
    color: white;
    font-size: 0.6875rem;
    font-weight: 700;
    border-radius: 9999px;
    line-height: 1;
}

/* Theme: Sidebar */
.sidebar[data-stisla-sidebar] {
    background: var(--sidebar-bg, #1e293b) !important;
}
.sidebar__header {
    background: var(--sidebar-bg, #1e293b) !important;
}
.sidebar__brand span {
    color: var(--logo-text, #ffffff) !important;
}
.sidebar__brand svg {
    color: var(--logo-text, #ffffff) !important;
}

.sidebar__brand-logo {
    width: 34px;
    height: 34px;
    object-fit: contain;
    border-radius: 0.375rem;
    background: #ffffff;
    padding: 2px;
}
.sidebar__content {
    background: var(--sidebar-bg, #1e293b) !important;
}
.sidebar__footer {
    background: var(--sidebar-bg, #1e293b) !important;
}
.sidebar__group-title {
    color: var(--sidebar-text, #94a3b8) !important;
}
.sidebar__button {
    color: var(--sidebar-text, #94a3b8) !important;
}
.sidebar__button:hover {
    background: var(--sidebar-hover-bg, #334155) !important;
    color: var(--sidebar-active-text, #ffffff) !important;
}
.sidebar__button.is-active {
    background: var(--sidebar-active-bg, #4f46e5) !important;
    color: var(--sidebar-active-text, #ffffff) !important;
}
.sidebar__search .input {
    background: var(--sidebar-hover-bg, #334155) !important;
    border-color: transparent !important;
    color: var(--sidebar-text, #94a3b8) !important;
}
.sidebar__search .input::placeholder {
    color: var(--sidebar-text, #94a3b8) !important;
    opacity: 0.7;
}
.sidebar__search .input-group__text {
    color: var(--sidebar-text, #94a3b8) !important;
}

/* Theme: Navbar */
.navbar {
    background: var(--navbar-bg, #ffffff) !important;
    border-bottom: 1px solid var(--navbar-border, #e2e8f0) !important;
}
.navbar .button--ghost {
    color: var(--navbar-text, #1e293b) !important;
}
.navbar .input {
    border-color: var(--navbar-border, #e2e8f0) !important;
}

/* Fix: content spacing */
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

/* Topbar user menu — fix posisi di bawah tombol, tidak di pojok */
.menu { position: relative; }
.menu__popup {
    position: absolute !important;
    top: calc(100% + 0.5rem) !important;
    right: 0 !important;
    left: auto !important;
    z-index: 50 !important;
    min-width: 12rem;
    background: #fff;
    border: 1px solid var(--gray-200, #e5e7eb);
    border-radius: 0.75rem;
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
    padding: 0.5rem 0;
}
.menu__popup[data-state="closed"] { display: none !important; }
.menu__popup[data-state="open"] { display: block !important; }
</style>
