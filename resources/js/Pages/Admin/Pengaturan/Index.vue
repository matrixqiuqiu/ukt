<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useToast } from '@/composables/useToast';

const { success, error: toastError } = useToast();

const props = defineProps({
  theme: Object,
});

const form = useForm({
  // Identitas Website
  website_name: props.theme?.website_name || 'Sistem Informasi UKT',
  website_short_name: props.theme?.website_short_name || 'UKT UBG',
  website_tagline: props.theme?.website_tagline || 'Sistem Pengelolaan & Pembayaran UKT Online Mahasiswa',
  website_footer_text: props.theme?.website_footer_text || '© 2026 Universitas Bumigora. All rights reserved.',

  // Warna Sidebar
  sidebar_bg: props.theme?.sidebar_bg || '#1e293b',
  sidebar_text: props.theme?.sidebar_text || '#94a3b8',
  sidebar_icon: props.theme?.sidebar_icon || '#94a3b8',
  sidebar_active_text: props.theme?.sidebar_active_text || '#ffffff',
  sidebar_active_bg: props.theme?.sidebar_active_bg || '#4f46e5',
  sidebar_hover_bg: props.theme?.sidebar_hover_bg || '#334155',
  logo_text: props.theme?.logo_text || '#ffffff',

  // Warna Navbar & Primary
  navbar_bg: props.theme?.navbar_bg || '#ffffff',
  navbar_text: props.theme?.navbar_text || '#1e293b',
  navbar_border: props.theme?.navbar_border || '#e2e8f0',
  primary_color: props.theme?.primary_color || '#4f46e5',

  // Warna Content & Card
  content_bg: props.theme?.content_bg || '#f8fafc',
  content_text: props.theme?.content_text || '#1e293b',
  card_bg: props.theme?.card_bg || '#ffffff',
  card_border: props.theme?.card_border || '#e2e8f0',

  // Kop Surat & Invoice PDF
  invoice_institution_name: props.theme?.invoice_institution_name || 'UNIVERSITAS BUMIGORA',
  invoice_institution_address: props.theme?.invoice_institution_address || 'Jl. Ismail Marzuki No.22, Cilinaya, Kec. Cakranegara, Kota Mataram, Nusa Tenggara Barat 83127',
  invoice_institution_phone: props.theme?.invoice_institution_phone || '(0370) 634498',
  invoice_institution_email: props.theme?.invoice_institution_email || 'info@universitasbumigora.ac.id',
  invoice_institution_website: props.theme?.invoice_institution_website || 'https://universitasbumigora.ac.id',
  invoice_logo: props.theme?.invoice_logo || '',
  invoice_header_image: props.theme?.invoice_header_image || '',
});

const activeTab = ref('identitas');
const logoPreview = ref(props.theme?.logo_url || props.theme?.invoice_logo || '');
const headerPreview = ref(props.theme?.header_image_url || props.theme?.invoice_header_image || '');
const isUploadingLogo = ref(false);
const isUploadingHeader = ref(false);
const logoInput = ref(null);
const headerInput = ref(null);
const showResetModal = ref(false);

// Preset Palet Warna
const presets = [
  {
    name: 'Default Indigo',
    desc: 'Warna standar modern',
    sidebar_bg: '#1e293b', sidebar_text: '#94a3b8', sidebar_icon: '#94a3b8', sidebar_active_text: '#ffffff',
    sidebar_active_bg: '#4f46e5', sidebar_hover_bg: '#334155',
    navbar_bg: '#ffffff', navbar_text: '#1e293b', navbar_border: '#e2e8f0',
    primary_color: '#4f46e5', logo_text: '#ffffff',
    content_bg: '#f8fafc', content_text: '#1e293b', card_bg: '#ffffff', card_border: '#e2e8f0',
  },
  {
    name: 'Biru Kampus UBG',
    desc: 'Nuansa biru korporat akademik',
    sidebar_bg: '#0f172a', sidebar_text: '#93c5fd', sidebar_icon: '#93c5fd', sidebar_active_text: '#ffffff',
    sidebar_active_bg: '#2563eb', sidebar_hover_bg: '#1e3a5f',
    navbar_bg: '#ffffff', navbar_text: '#0f172a', navbar_border: '#bfdbfe',
    primary_color: '#2563eb', logo_text: '#ffffff',
    content_bg: '#f8fafc', content_text: '#0f172a', card_bg: '#ffffff', card_border: '#e2e8f0',
  },
  {
    name: 'Hijau Emerald',
    desc: 'Nuansa segar & terpercaya',
    sidebar_bg: '#064e3b', sidebar_text: '#a7f3d0', sidebar_icon: '#a7f3d0', sidebar_active_text: '#ffffff',
    sidebar_active_bg: '#10b981', sidebar_hover_bg: '#047857',
    navbar_bg: '#ffffff', navbar_text: '#064e3b', navbar_border: '#a7f3d0',
    primary_color: '#059669', logo_text: '#ffffff',
    content_bg: '#f8fafc', content_text: '#0f172a', card_bg: '#ffffff', card_border: '#e2e8f0',
  },
  {
    name: 'Merah Marun',
    desc: 'Elegan, tegas, dan resmi',
    sidebar_bg: '#1e0505', sidebar_text: '#fecaca', sidebar_icon: '#fecaca', sidebar_active_text: '#ffffff',
    sidebar_active_bg: '#dc2626', sidebar_hover_bg: '#450a0a',
    navbar_bg: '#ffffff', navbar_text: '#1c0a00', navbar_border: '#fecaca',
    primary_color: '#dc2626', logo_text: '#ffffff',
    content_bg: '#f8fafc', content_text: '#1e293b', card_bg: '#ffffff', card_border: '#e2e8f0',
  },
  {
    name: 'Ungu Akademik',
    desc: 'Modern, kreatif, & berkelas',
    sidebar_bg: '#180828', sidebar_text: '#ddd6fe', sidebar_icon: '#ddd6fe', sidebar_active_text: '#ffffff',
    sidebar_active_bg: '#7c3aed', sidebar_hover_bg: '#3b0764',
    navbar_bg: '#ffffff', navbar_text: '#180828', navbar_border: '#ddd6fe',
    primary_color: '#7c3aed', logo_text: '#ffffff',
    content_bg: '#f8fafc', content_text: '#1e293b', card_bg: '#ffffff', card_border: '#e2e8f0',
  },
  {
    name: 'Emas Elegan',
    desc: 'Nuansa eksklusif & prestisius',
    sidebar_bg: '#1a1400', sidebar_text: '#fde68a', sidebar_icon: '#fde68a', sidebar_active_text: '#ffffff',
    sidebar_active_bg: '#d97706', sidebar_hover_bg: '#451a03',
    navbar_bg: '#ffffff', navbar_text: '#1a1400', navbar_border: '#fde68a',
    primary_color: '#d97706', logo_text: '#ffffff',
    content_bg: '#f8fafc', content_text: '#1e293b', card_bg: '#ffffff', card_border: '#e2e8f0',
  },
  {
    name: 'Dark Slate Modern',
    desc: 'Minimalis & modern tech',
    sidebar_bg: '#0f172a', sidebar_text: '#94a3b8', sidebar_icon: '#94a3b8', sidebar_active_text: '#ffffff',
    sidebar_active_bg: '#3b82f6', sidebar_hover_bg: '#1e293b',
    navbar_bg: '#1e293b', navbar_text: '#f8fafc', navbar_border: '#334155',
    primary_color: '#3b82f6', logo_text: '#ffffff',
    content_bg: '#f1f5f9', content_text: '#0f172a', card_bg: '#ffffff', card_border: '#cbd5e1',
  },
];

const applyPreset = (preset) => {
  Object.keys(preset).forEach(key => {
    if (key !== 'name' && key !== 'desc' && form[key] !== undefined) {
      form[key] = preset[key];
    }
  });
};

const submit = () => {
  form.put(route('admin.pengaturan.update'), {
    preserveScroll: true,
    onSuccess: () => success('Pengaturan aplikasi & tampilan berhasil disimpan.'),
    onError: () => toastError('Gagal menyimpan. Periksa kembali isian form Anda.'),
  });
};

const executeResetTheme = () => {
  showResetModal.value = false;
  router.post(route('admin.pengaturan.reset'), {}, {
    preserveScroll: true,
    onSuccess: () => success('Tema berhasil direset ke konfigurasi awal bawaan.'),
    onError: () => toastError('Gagal mereset tema.'),
  });
};

// Upload Logo
const uploadLogo = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  isUploadingLogo.value = true;
  const formData = new FormData();
  formData.append('logo', file);

  try {
    const response = await fetch(route('admin.pengaturan.upload-logo'), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: formData,
    });
    const data = await response.json();
    if (data.success) {
      logoPreview.value = data.url;
      form.invoice_logo = data.url;
      success('Logo berhasil diunggah.');
    } else {
      toastError('Gagal mengunggah logo.');
    }
  } catch (error) {
    toastError('Gagal menghubungi server untuk upload logo.');
  } finally {
    isUploadingLogo.value = false;
  }
};

const removeLogo = async () => {
  try {
    await router.post(route('admin.pengaturan.remove-logo'), {}, { preserveScroll: true });
    logoPreview.value = '';
    form.invoice_logo = '';
    success('Logo berhasil dihapus.');
  } catch (e) {
    toastError('Gagal menghapus logo.');
  }
};

// Upload Kop Surat
const uploadHeader = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  isUploadingHeader.value = true;
  const formData = new FormData();
  formData.append('header_image', file);

  try {
    const response = await fetch(route('admin.pengaturan.upload-header'), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: formData,
    });
    const data = await response.json();
    if (data.success) {
      headerPreview.value = data.url;
      form.invoice_header_image = data.url;
      success('Header kop surat berhasil diunggah.');
    } else {
      toastError('Gagal mengunggah header kop surat.');
    }
  } catch (error) {
    toastError('Gagal menghubungi server untuk upload kop surat.');
  } finally {
    isUploadingHeader.value = false;
  }
};

const removeHeader = async () => {
  try {
    await router.post(route('admin.pengaturan.remove-header'), {}, { preserveScroll: true });
    headerPreview.value = '';
    form.invoice_header_image = '';
    success('Header kop surat berhasil dihapus.');
  } catch (e) {
    toastError('Gagal menghapus header kop surat.');
  }
};
</script>

<template>
  <Head title="Pengaturan Aplikasi & Tampilan" />

  <AuthenticatedLayout>
    <div class="settings-page-wrapper">
      <!-- Header Page -->
      <div class="settings-header">
        <div class="header-left">
          <div class="header-badge">
            <span class="badge-pill">
              <i class="fas fa-sliders-h"></i> Control Center
            </span>
            <span class="badge-pill pill-neutral">
              <i class="fas fa-desktop"></i> {{ form.website_short_name || 'UKT System' }}
            </span>
          </div>
          <h1 class="settings-title">Pengaturan Aplikasi & Tampilan</h1>
          <p class="settings-subtitle">Kelola identitas sistem, tema warna antarmuka, dan konfigurasi cetak kop surat invoice</p>
        </div>
        <div class="header-actions">
          <button type="button" class="btn-solid btn-outline" @click="showResetModal = true">
            <i class="fas fa-undo"></i> Reset Default
          </button>
          <button type="button" class="btn-solid btn-primary" @click="submit" :disabled="form.processing">
            <i class="fas" :class="form.processing ? 'fa-spinner fa-pulse' : 'fa-save'"></i>
            {{ form.processing ? 'Menyimpan...' : 'Simpan Semua' }}
          </button>
        </div>
      </div>

      <!-- Segmented Navigation Tabs -->
      <div class="settings-nav-tabs">
        <button
          type="button"
          class="nav-tab-btn"
          :class="{ active: activeTab === 'identitas' }"
          @click="activeTab = 'identitas'"
        >
          <i class="fas fa-building"></i>
          <span>Identitas & Branding Kampus</span>
        </button>
        <button
          type="button"
          class="nav-tab-btn"
          :class="{ active: activeTab === 'tema' }"
          @click="activeTab = 'tema'"
        >
          <i class="fas fa-palette"></i>
          <span>Tema & Palet Warna UI</span>
        </button>
        <button
          type="button"
          class="nav-tab-btn"
          :class="{ active: activeTab === 'invoice' }"
          @click="activeTab = 'invoice'"
        >
          <i class="fas fa-file-invoice-dollar"></i>
          <span>Kop Surat & Template Invoice PDF</span>
        </button>
      </div>

      <form @submit.prevent="submit">
        <!-- ================= TAB 1: IDENTITAS & BRANDING ================= -->
        <div v-show="activeTab === 'identitas'" class="tab-panel">
          <div class="grid-2-col">
            <!-- Left: Identitas Form -->
            <div class="solid-card">
              <div class="card-title-bar">
                <h3><i class="fas fa-info-circle text-primary"></i> Identitas Sistem & Website</h3>
                <span class="card-subtitle">Nama aplikasi yang muncul di browser title, sidebar, dan header mahasiswa</span>
              </div>
              <div class="card-content">
                <div class="form-group-solid">
                  <label>Nama Lengkap Sistem / Website <span class="required">*</span></label>
                  <input
                    type="text"
                    v-model="form.website_name"
                    class="input-solid"
                    placeholder="Contoh: Sistem Informasi UKT Universitas Bumigora"
                    required
                  />
                  <div class="form-hint">Ditampilkan pada judul halaman browser dan header portal.</div>
                </div>

                <div class="form-group-solid">
                  <label>Nama Singkat / Brand Text <span class="required">*</span></label>
                  <input
                    type="text"
                    v-model="form.website_short_name"
                    class="input-solid"
                    placeholder="Contoh: UKT UBG"
                    required
                  />
                  <div class="form-hint">Nama pendek di sidebar samping logo dan badge navigasi.</div>
                </div>

                <div class="form-group-solid">
                  <label>Tagline / Deskripsi Singkat</label>
                  <input
                    type="text"
                    v-model="form.website_tagline"
                    class="input-solid"
                    placeholder="Contoh: Sistem Pengelolaan & Pembayaran UKT Mahasiswa"
                  />
                  <div class="form-hint">Deskripsi pendukung di halaman login atau dashboard.</div>
                </div>

                <div class="form-group-solid">
                  <label>Teks Hak Cipta (Footer)</label>
                  <input
                    type="text"
                    v-model="form.website_footer_text"
                    class="input-solid"
                    placeholder="Contoh: © 2026 Universitas Bumigora. All rights reserved."
                  />
                  <div class="form-hint">Teks hak cipta yang muncul di bagian paling bawah halaman portal.</div>
                </div>
              </div>
            </div>

            <!-- Right: Logo & Visual Asset -->
            <div class="solid-card">
              <div class="card-title-bar">
                <h3><i class="fas fa-image text-primary"></i> Logo Utama Institusi</h3>
                <span class="card-subtitle">Logo resmi yang digunakan di sidebar, favicon, dan lembar invoice</span>
              </div>
              <div class="card-content">
                <div class="logo-upload-zone">
                  <div class="logo-preview-box">
                    <img
                      v-if="logoPreview"
                      :src="logoPreview"
                      alt="Logo Kampus"
                      class="img-logo-preview"
                    />
                    <div v-else class="empty-logo-placeholder">
                      <i class="fas fa-university"></i>
                      <span>Belum ada logo terunggah</span>
                    </div>
                  </div>

                  <div class="logo-upload-controls">
                    <input
                      type="file"
                      accept="image/png,image/jpeg,image/webp,image/gif"
                      @change="uploadLogo"
                      :disabled="isUploadingLogo"
                      style="display:none;"
                      ref="logoInput"
                    />
                    <div class="upload-btn-group">
                      <button
                        type="button"
                        class="btn-solid btn-primary btn-sm"
                        @click="logoInput?.click()"
                        :disabled="isUploadingLogo"
                      >
                        <i class="fas" :class="isUploadingLogo ? 'fa-spinner fa-pulse' : 'fa-upload'"></i>
                        {{ isUploadingLogo ? 'Mengunggah...' : (logoPreview ? 'Ganti Logo' : 'Unggah Logo') }}
                      </button>
                      <button
                        v-if="logoPreview"
                        type="button"
                        class="btn-solid btn-danger btn-sm"
                        @click="removeLogo"
                      >
                        <i class="fas fa-trash-alt"></i> Hapus
                      </button>
                    </div>
                    <div class="upload-guide-box">
                      <div class="guide-item"><i class="fas fa-check-circle"></i> Format yang didukung: PNG, JPG, WEBP, GIF</div>
                      <div class="guide-item"><i class="fas fa-check-circle"></i> Ukuran maksimal: 5 MB (disarankan PNG transparan)</div>
                      <div class="guide-item"><i class="fas fa-check-circle"></i> Otomatis dioptimalkan untuk PDF dan resolusi web</div>
                    </div>
                  </div>
                </div>

                <!-- Live Header Branding Preview -->
                <div class="sidebar-brand-preview-box">
                  <span class="preview-label"><i class="fas fa-eye"></i> Preview Tampilan Header & Brand:</span>
                  <div class="brand-mockup" :style="{ background: form.sidebar_bg }">
                    <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="mockup-logo-img" />
                    <i v-else class="fas fa-graduation-cap mockup-logo-icon" :style="{ color: form.sidebar_active_bg }"></i>
                    <div class="mockup-brand-info">
                      <span class="mockup-brand-title" :style="{ color: form.logo_text }">{{ form.website_short_name || 'UKT UBG' }}</span>
                      <span class="mockup-brand-sub" :style="{ color: form.sidebar_text }">{{ form.website_name || 'Portal Pembayaran UKT' }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ================= TAB 2: TEMA & PALET WARNA ================= -->
        <div v-show="activeTab === 'tema'" class="tab-panel">
          <!-- Preset Templates -->
          <div class="solid-card" style="margin-bottom:1.5rem;">
            <div class="card-title-bar">
              <div style="display:flex;align-items:center;justify-content:space-between;width:100%;">
                <div>
                  <h3><i class="fas fa-swatchbook text-primary"></i> Pilihan Preset Tema Cepat</h3>
                  <span class="card-subtitle">Pilih palet warna siap pakai yang dirancang harmonis dan solid</span>
                </div>
                <button type="button" class="btn-solid btn-outline btn-sm" @click="showResetModal = true">
                  <i class="fas fa-undo"></i> Reset Default
                </button>
              </div>
            </div>
            <div class="card-content">
              <div class="presets-grid">
                <div
                  v-for="preset in presets"
                  :key="preset.name"
                  class="preset-card-item"
                  :class="{ active: form.sidebar_bg === preset.sidebar_bg && form.sidebar_active_bg === preset.sidebar_active_bg && form.primary_color === preset.primary_color }"
                  @click="applyPreset(preset)"
                >
                  <div class="preset-color-strip">
                    <div class="strip-item" :style="{ background: preset.sidebar_bg }" title="Sidebar"></div>
                    <div class="strip-item" :style="{ background: preset.sidebar_active_bg }" title="Active"></div>
                    <div class="strip-item" :style="{ background: preset.navbar_bg, border: '1px solid #e2e8f0' }" title="Navbar"></div>
                    <div class="strip-item" :style="{ background: preset.primary_color }" title="Primary"></div>
                  </div>
                  <div class="preset-info">
                    <div class="preset-name">{{ preset.name }}</div>
                    <div class="preset-desc">{{ preset.desc }}</div>
                  </div>
                  <div class="preset-check" v-if="form.sidebar_bg === preset.sidebar_bg && form.sidebar_active_bg === preset.sidebar_active_bg && form.primary_color === preset.primary_color">
                    <i class="fas fa-check-circle"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Color Customizer & Interactive Live Mockup -->
          <div class="grid-2-col">
            <!-- Left: Granular Color Pickers -->
            <div class="solid-card">
              <div class="card-title-bar">
                <h3><i class="fas fa-sliders-h text-primary"></i> Kustomisasi Palet Detail</h3>
                <span class="card-subtitle">Sesuaikan kode warna heksadesimal sesuai panduan brand kampus</span>
              </div>
              <div class="card-content">
                <!-- Sidebar Section -->
                <div class="color-section-group">
                  <div class="color-section-title"><i class="fas fa-columns"></i> Navigasi Sidebar</div>
                  <div class="color-fields-grid">
                    <div class="color-field-row">
                      <label>Background Sidebar</label>
                      <div class="color-input-wrap">
                        <input type="color" v-model="form.sidebar_bg" class="native-color" />
                        <input type="text" v-model="form.sidebar_bg" class="hex-text" />
                      </div>
                    </div>
                    <div class="color-field-row">
                      <label>Teks Menu Biasa</label>
                      <div class="color-input-wrap">
                        <input type="color" v-model="form.sidebar_text" class="native-color" />
                        <input type="text" v-model="form.sidebar_text" class="hex-text" />
                      </div>
                    </div>
                    <div class="color-field-row">
                      <label>Ikon Menu</label>
                      <div class="color-input-wrap">
                        <input type="color" v-model="form.sidebar_icon" class="native-color" />
                        <input type="text" v-model="form.sidebar_icon" class="hex-text" />
                      </div>
                    </div>
                    <div class="color-field-row">
                      <label>Background Menu Aktif</label>
                      <div class="color-input-wrap">
                        <input type="color" v-model="form.sidebar_active_bg" class="native-color" />
                        <input type="text" v-model="form.sidebar_active_bg" class="hex-text" />
                      </div>
                    </div>
                    <div class="color-field-row">
                      <label>Teks Menu Aktif</label>
                      <div class="color-input-wrap">
                        <input type="color" v-model="form.sidebar_active_text" class="native-color" />
                        <input type="text" v-model="form.sidebar_active_text" class="hex-text" />
                      </div>
                    </div>
                    <div class="color-field-row">
                      <label>Background Hover</label>
                      <div class="color-input-wrap">
                        <input type="color" v-model="form.sidebar_hover_bg" class="native-color" />
                        <input type="text" v-model="form.sidebar_hover_bg" class="hex-text" />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Navbar & Primary Section -->
                <div class="color-section-group" style="margin-top:1.25rem;">
                  <div class="color-section-title"><i class="fas fa-window-maximize"></i> Top Navbar & Aksen Primer</div>
                  <div class="color-fields-grid">
                    <div class="color-field-row">
                      <label>Warna Primer Tombol</label>
                      <div class="color-input-wrap">
                        <input type="color" v-model="form.primary_color" class="native-color" />
                        <input type="text" v-model="form.primary_color" class="hex-text" />
                      </div>
                    </div>
                    <div class="color-field-row">
                      <label>Background Navbar</label>
                      <div class="color-input-wrap">
                        <input type="color" v-model="form.navbar_bg" class="native-color" />
                        <input type="text" v-model="form.navbar_bg" class="hex-text" />
                      </div>
                    </div>
                    <div class="color-field-row">
                      <label>Teks Top Navbar</label>
                      <div class="color-input-wrap">
                        <input type="color" v-model="form.navbar_text" class="native-color" />
                        <input type="text" v-model="form.navbar_text" class="hex-text" />
                      </div>
                    </div>
                    <div class="color-field-row">
                      <label>Border Bottom Navbar</label>
                      <div class="color-input-wrap">
                        <input type="color" v-model="form.navbar_border" class="native-color" />
                        <input type="text" v-model="form.navbar_border" class="hex-text" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right: Interactive Live Admin Mockup Preview -->
            <div class="solid-card">
              <div class="card-title-bar">
                <h3><i class="fas fa-desktop text-primary"></i> Live Mockup Antarmuka Admin</h3>
                <span class="card-subtitle">Pratinjau visual langsung sebelum perubahan disimpan</span>
              </div>
              <div class="card-content">
                <div class="ui-mockup-frame">
                  <!-- Mockup Topbar -->
                  <div class="mockup-topbar" :style="{ background: form.navbar_bg, borderBottomColor: form.navbar_border }">
                    <div class="mockup-topbar-left">
                      <span class="mockup-btn-icon"><i class="fas fa-bars" :style="{ color: form.navbar_text }"></i></span>
                      <span class="mockup-page-indicator" :style="{ color: form.navbar_text }">Dashboard Admin</span>
                    </div>
                    <div class="mockup-topbar-right">
                      <span class="mockup-user-pill" :style="{ color: form.navbar_text, borderColor: form.navbar_border }">
                        <i class="fas fa-user-circle"></i> Administrator
                      </span>
                    </div>
                  </div>

                  <!-- Mockup Layout Body -->
                  <div class="mockup-layout-body">
                    <!-- Mockup Sidebar -->
                    <div class="mockup-sidebar" :style="{ background: form.sidebar_bg }">
                      <div class="mockup-sidebar-brand" :style="{ color: form.logo_text }">
                        <i class="fas fa-graduation-cap" :style="{ color: form.sidebar_active_bg }"></i>
                        <span>{{ form.website_short_name || 'UKT UBG' }}</span>
                      </div>
                      <div class="mockup-menu-list">
                        <div class="mockup-menu-item active" :style="{ background: form.sidebar_active_bg, color: form.sidebar_active_text }">
                          <i class="fas fa-tachometer-alt"></i> Dashboard
                        </div>
                        <div class="mockup-menu-item" :style="{ color: form.sidebar_text }">
                          <i class="fas fa-users" :style="{ color: form.sidebar_icon }"></i> Data Mahasiswa
                        </div>
                        <div class="mockup-menu-item" :style="{ color: form.sidebar_text }">
                          <i class="fas fa-file-invoice" :style="{ color: form.sidebar_icon }"></i> Tagihan UKT
                        </div>
                        <div class="mockup-menu-item hover-preview" :style="{ background: form.sidebar_hover_bg, color: form.sidebar_text }">
                          <i class="fas fa-money-check-alt" :style="{ color: form.sidebar_icon }"></i> Pembayaran (Hover)
                        </div>
                        <div class="mockup-menu-item" :style="{ color: form.sidebar_text }">
                          <i class="fas fa-cog" :style="{ color: form.sidebar_icon }"></i> Pengaturan
                        </div>
                      </div>
                    </div>

                    <!-- Mockup Content Area -->
                    <div class="mockup-content-pane" :style="{ background: form.content_bg, color: form.content_text }">
                      <!-- Mockup Stats Cards -->
                      <div class="mockup-stats-row">
                        <div class="mockup-stat-card" :style="{ background: form.card_bg, borderColor: form.card_border }">
                          <div class="mockup-stat-title">Total Tagihan</div>
                          <div class="mockup-stat-num" :style="{ color: form.primary_color }">Rp 1.45 M</div>
                          <div class="mockup-stat-sub">340 Mahasiswa</div>
                        </div>
                        <div class="mockup-stat-card" :style="{ background: form.card_bg, borderColor: form.card_border }">
                          <div class="mockup-stat-title">Terverifikasi</div>
                          <div class="mockup-stat-num" style="color:#16a34a;">88.5%</div>
                          <div class="mockup-stat-sub">301 Pembayaran</div>
                        </div>
                      </div>

                      <!-- Mockup Action Card -->
                      <div class="mockup-action-card" :style="{ background: form.card_bg, borderColor: form.card_border }">
                        <div class="mockup-card-header" :style="{ borderBottomColor: form.card_border }">
                          <strong>Tabel Tagihan UKT Semester Aktif</strong>
                          <button type="button" class="mockup-btn" :style="{ background: form.primary_color, color: '#ffffff' }">
                            <i class="fas fa-plus"></i> Tambah Tagihan
                          </button>
                        </div>
                        <div class="mockup-card-body">
                          <div class="mockup-table-skeleton">
                            <div class="skeleton-row header">
                              <span style="width:30%;">NIM / Mahasiswa</span>
                              <span style="width:30%;">Program Studi</span>
                              <span style="width:20%;">Nominal</span>
                              <span style="width:20%;">Status</span>
                            </div>
                            <div class="skeleton-row">
                              <span style="width:30%;">25080110013 - Ryan Pratama</span>
                              <span style="width:30%;">S1 Rekayasa Sistem Komputer</span>
                              <span style="width:20%;">Rp 3.500.000</span>
                              <span style="width:20%;"><span class="mockup-badge-success">LUNAS</span></span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ================= TAB 3: KOP SURAT & INVOICE PDF ================= -->
        <div v-show="activeTab === 'invoice'" class="tab-panel">
          <div class="grid-2-col">
            <!-- Left: Identitas Institusi & Header Upload -->
            <div class="solid-card">
              <div class="card-title-bar">
                <h3><i class="fas fa-university text-primary"></i> Data Resmi Institusi Kampus</h3>
                <span class="card-subtitle">Informasi legal institusi yang tercantum pada kop surat cetak tagihan & PDF invoice</span>
              </div>
              <div class="card-content">
                <div class="form-group-solid">
                  <label>Nama Resmi Institusi / Universitas <span class="required">*</span></label>
                  <input
                    type="text"
                    v-model="form.invoice_institution_name"
                    class="input-solid"
                    placeholder="Contoh: UNIVERSITAS BUMIGORA"
                    required
                  />
                </div>

                <div class="form-group-solid">
                  <label>Alamat Lengkap Institusi <span class="required">*</span></label>
                  <textarea
                    v-model="form.invoice_institution_address"
                    class="input-solid"
                    rows="3"
                    placeholder="Contoh: Jl. Ismail Marzuki No.22, Cilinaya, Kec. Cakranegara, Kota Mataram, Nusa Tenggara Barat 83127"
                    required
                  ></textarea>
                </div>

                <div class="form-row-2">
                  <div class="form-group-solid">
                    <label>Nomor Telepon Resmi</label>
                    <input
                      type="text"
                      v-model="form.invoice_institution_phone"
                      class="input-solid"
                      placeholder="Contoh: (0370) 634498"
                    />
                  </div>
                  <div class="form-group-solid">
                    <label>Email Kontak Resmi</label>
                    <input
                      type="email"
                      v-model="form.invoice_institution_email"
                      class="input-solid"
                      placeholder="Contoh: info@universitasbumigora.ac.id"
                    />
                  </div>
                </div>

                <div class="form-group-solid">
                  <label>Alamat Website Resmi</label>
                  <input
                    type="text"
                    v-model="form.invoice_institution_website"
                    class="input-solid"
                    placeholder="Contoh: https://universitasbumigora.ac.id"
                  />
                </div>

                <!-- Custom Header Banner Image (Optional) -->
                <div class="form-group-solid" style="margin-top:1.25rem;">
                  <label>Gambar Header Kop Surat Khusus (Banner Opsional)</label>
                  <div class="header-upload-box">
                    <div v-if="headerPreview" class="header-img-preview">
                      <img :src="headerPreview" alt="Kop Surat Banner" />
                      <button type="button" class="btn-solid btn-danger btn-sm" @click="removeHeader" style="margin-top:0.5rem;">
                        <i class="fas fa-trash-alt"></i> Hapus Gambar Kop
                      </button>
                    </div>
                    <div v-else class="empty-header-box">
                      <i class="fas fa-file-image"></i>
                      <p>Gunakan gambar kop surat cetak jika universitas memiliki banner grafis khusus</p>
                    </div>

                    <input
                      type="file"
                      accept="image/*"
                      @change="uploadHeader"
                      :disabled="isUploadingHeader"
                      style="display:none;"
                      ref="headerInput"
                    />
                    <div style="margin-top:0.75rem;">
                      <button
                        type="button"
                        class="btn-solid btn-outline btn-sm"
                        @click="headerInput?.click()"
                        :disabled="isUploadingHeader"
                      >
                        <i class="fas" :class="isUploadingHeader ? 'fa-spinner fa-pulse' : 'fa-upload'"></i>
                        {{ isUploadingHeader ? 'Mengunggah...' : (headerPreview ? 'Ganti Banner Kop' : 'Unggah Banner Kop') }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right: Live A4 Paper Sheet Preview of Invoice PDF -->
            <div class="solid-card">
              <div class="card-title-bar">
                <h3><i class="fas fa-file-pdf text-danger"></i> Simulasi Cetak Lembar Invoice PDF (A4)</h3>
                <span class="card-subtitle">Tampilan cetak resmi bukti tagihan & kuitansi yang diterima mahasiswa</span>
              </div>
              <div class="card-content">
                <div class="a4-sheet-container">
                  <div class="a4-sheet">
                    <!-- Invoice Header Kop -->
                    <div v-if="headerPreview" class="invoice-header-banner">
                      <img :src="headerPreview" alt="Header Kop" style="max-width:100%;max-height:80px;object-fit:contain;" />
                    </div>
                    <div v-else class="invoice-kop-standard">
                      <div class="kop-logo">
                        <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="kop-logo-img" />
                        <i v-else class="fas fa-university kop-logo-icon"></i>
                      </div>
                      <div class="kop-info">
                        <div class="kop-institution">{{ form.invoice_institution_name || 'UNIVERSITAS BUMIGORA' }}</div>
                        <div class="kop-address">{{ form.invoice_institution_address || 'Jl. Ismail Marzuki No.22, Cilinaya, Kota Mataram' }}</div>
                        <div class="kop-contacts">
                          <span>Tel: {{ form.invoice_institution_phone || '-' }}</span>
                          <span>|</span>
                          <span>Email: {{ form.invoice_institution_email || '-' }}</span>
                          <span>|</span>
                          <span>Web: {{ form.invoice_institution_website || '-' }}</span>
                        </div>
                      </div>
                    </div>
                    <div class="kop-divider"></div>

                    <!-- Invoice Title -->
                    <div class="invoice-title-area">
                      <div class="invoice-title">BUKTI PEMBAYARAN UANG KULIAH TUNGGAL (UKT)</div>
                      <div class="invoice-no">No. Kwitansi: <strong>KW-2026/09/UBG-00892</strong></div>
                    </div>

                    <!-- Invoice Meta Grid -->
                    <div class="invoice-meta-grid">
                      <div class="meta-col">
                        <div class="meta-item"><span class="meta-k">NIM:</span> <span class="meta-v">25080110013</span></div>
                        <div class="meta-item"><span class="meta-k">Nama:</span> <span class="meta-v">Ryan Pratama</span></div>
                        <div class="meta-item"><span class="meta-k">Prodi:</span> <span class="meta-v">S1 Rekayasa Sistem Komputer</span></div>
                      </div>
                      <div class="meta-col">
                        <div class="meta-item"><span class="meta-k">Semester:</span> <span class="meta-v">2026/2027 Ganjil</span></div>
                        <div class="meta-item"><span class="meta-k">Metode:</span> <span class="meta-v">NTB Virtual Account</span></div>
                        <div class="meta-item"><span class="meta-k">Tanggal:</span> <span class="meta-v">10 September 2026</span></div>
                      </div>
                    </div>

                    <!-- Invoice Table Item -->
                    <table class="invoice-sample-table">
                      <thead>
                        <tr>
                          <th style="width:10%;">No</th>
                          <th>Deskripsi Rincian Tagihan</th>
                          <th style="text-align:right;width:30%;">Nominal</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>1</td>
                          <td>Uang Kuliah Tunggal (UKT) - Semester Ganjil 2026/2027</td>
                          <td style="text-align:right;font-weight:600;">Rp 3.500.000</td>
                        </tr>
                      </tbody>
                      <tfoot>
                        <tr>
                          <th colspan="2" style="text-align:right;">TOTAL PEMBAYARAN:</th>
                          <th style="text-align:right;color:#16a34a;font-size:0.95rem;">Rp 3.500.000</th>
                        </tr>
                      </tfoot>
                    </table>

                    <!-- Status Stamp & Signature -->
                    <div class="invoice-footer-area">
                      <div class="invoice-stamp-box">
                        <div class="stamp-lunas">LUNAS / VERIFIED</div>
                        <div class="stamp-date">10/09/2026 - NTBVA 25080110013</div>
                      </div>
                      <div class="invoice-signature-box">
                        <div class="sig-city">Mataram, 10 September 2026</div>
                        <div class="sig-title">Bagian Keuangan & Admisi</div>
                        <div class="sig-line"></div>
                        <div class="sig-name">Universitas Bumigora</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sticky Form Action Bar -->
        <div class="settings-action-bar">
          <div class="action-bar-left">
            <span class="action-info"><i class="fas fa-info-circle"></i> Pastikan untuk menekan tombol <strong>Simpan Pengaturan</strong> setelah melakukan perubahan.</span>
          </div>
          <div class="action-bar-right">
            <button type="button" class="btn-solid btn-outline" @click="showResetModal = true">
              <i class="fas fa-undo"></i> Reset Default
            </button>
            <button type="submit" class="btn-solid btn-primary" :disabled="form.processing">
              <i class="fas" :class="form.processing ? 'fa-spinner fa-pulse' : 'fa-save'"></i>
              {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Modal Konfirmasi Reset (Teleported) -->
    <Teleport to="body">
      <div v-if="showResetModal" class="modal-overlay" @click.self="showResetModal = false">
        <div class="modal-box">
          <div class="modal-header">
            <h3 style="color:#d97706;display:flex;align-items:center;gap:0.5rem;">
              <i class="fas fa-exclamation-triangle"></i> Konfirmasi Reset Tema Default
            </h3>
            <button type="button" class="modal-close" @click="showResetModal = false"><i class="fas fa-times"></i></button>
          </div>
          <div class="modal-body">
            <p style="margin:0 0 0.75rem;color:#334155;font-size:0.875rem;line-height:1.5;">
              Apakah Anda yakin ingin mereset seluruh tema warna visual dan tata letak kembali ke konfigurasi awal (default)?
            </p>
            <div style="background:#fffbeb;border:1px solid #fef3c7;border-radius:0.5rem;padding:0.75rem;font-size:0.8125rem;color:#b45309;">
              <i class="fas fa-info-circle"></i> Catatan: Nama website dan identitas institusi tidak akan terhapus saat mereset tema warna.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-solid btn-outline" @click="showResetModal = false">Batal</button>
            <button type="button" class="btn-solid btn-danger" @click="executeResetTheme">
              <i class="fas fa-undo"></i> Ya, Reset Tema
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AuthenticatedLayout>
</template>

<style scoped>
.settings-page-wrapper {
  padding-bottom: 5rem;
}

/* Header */
.settings-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 1.25rem 1.5rem;
  gap: 1rem;
}

.header-badge {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
}

.badge-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.625rem;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
  font-size: 0.75rem;
  font-weight: 700;
  border-radius: 9999px;
}

.badge-pill.pill-neutral {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #475569;
}

.settings-title {
  font-size: 1.375rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.25rem;
}

.settings-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

/* Segmented Navigation Tabs */
.settings-nav-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  background: #f1f5f9;
  padding: 0.375rem;
  border-radius: 0.625rem;
  border: 1px solid #e2e8f0;
  flex-wrap: wrap;
}

.nav-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  background: transparent;
  border: none;
  border-radius: 0.5rem;
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
}

.nav-tab-btn:hover {
  color: #0f172a;
  background: rgba(255, 255, 255, 0.6);
}

.nav-tab-btn.active {
  background: #ffffff;
  color: #2563eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  font-weight: 700;
}

/* Grid & Cards */
.grid-2-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.solid-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}

.card-title-bar {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}

.card-title-bar h3 {
  margin: 0 0 0.25rem;
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.card-subtitle {
  font-size: 0.8125rem;
  color: #64748b;
  display: block;
}

.card-content {
  padding: 1.25rem;
}

/* Form Elements */
.form-group-solid {
  margin-bottom: 1.125rem;
}

.form-group-solid label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.375rem;
}

.form-group-solid .required {
  color: #dc2626;
}

.input-solid {
  width: 100%;
  padding: 0.5625rem 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  color: #0f172a;
  background: #ffffff;
  outline: none;
  transition: border-color 0.15s;
}

.input-solid:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-hint {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.25rem;
}

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

/* Logo Upload Zone */
.logo-upload-zone {
  display: flex;
  gap: 1.25rem;
  align-items: flex-start;
  padding: 1rem;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 0.625rem;
  margin-bottom: 1.25rem;
}

.logo-preview-box {
  width: 120px;
  height: 120px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  padding: 0.5rem;
}

.img-logo-preview {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.empty-logo-placeholder {
  text-align: center;
  color: #94a3b8;
  font-size: 0.75rem;
}

.empty-logo-placeholder i {
  font-size: 2rem;
  margin-bottom: 0.375rem;
  display: block;
}

.logo-upload-controls {
  flex: 1;
}

.upload-btn-group {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.upload-guide-box {
  font-size: 0.75rem;
  color: #64748b;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.guide-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.guide-item i {
  color: #16a34a;
}

/* Sidebar Brand Preview Box */
.sidebar-brand-preview-box {
  margin-top: 1rem;
  border-top: 1px solid #e2e8f0;
  padding-top: 1rem;
}

.preview-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
  margin-bottom: 0.5rem;
}

.brand-mockup {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  border-radius: 0.5rem;
}

.mockup-logo-img {
  width: 36px;
  height: 36px;
  object-fit: contain;
  border-radius: 0.375rem;
}

.mockup-logo-icon {
  font-size: 1.75rem;
}

.mockup-brand-info {
  display: flex;
  flex-direction: column;
}

.mockup-brand-title {
  font-size: 0.9375rem;
  font-weight: 700;
}

.mockup-brand-sub {
  font-size: 0.75rem;
  opacity: 0.85;
}

/* Presets Grid */
.presets-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 0.875rem;
}

.preset-card-item {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.625rem;
  padding: 0.75rem;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
  position: relative;
}

.preset-card-item:hover {
  border-color: #94a3b8;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.preset-card-item.active {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
  background: #f8fafc;
}

.preset-color-strip {
  display: flex;
  height: 28px;
  border-radius: 0.375rem;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.strip-item {
  flex: 1;
}

.preset-name {
  font-size: 0.8125rem;
  font-weight: 700;
  color: #0f172a;
}

.preset-desc {
  font-size: 0.6875rem;
  color: #64748b;
}

.preset-check {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  color: #2563eb;
  font-size: 1rem;
}

/* Color Sections & Fields */
.color-section-title {
  font-size: 0.8125rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding-bottom: 0.375rem;
  border-bottom: 1px solid #f1f5f9;
}

.color-fields-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.color-field-row label {
  display: block;
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
  margin-bottom: 0.25rem;
}

.color-input-wrap {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.native-color {
  width: 34px;
  height: 34px;
  border: 1px solid #cbd5e1;
  border-radius: 0.375rem;
  cursor: pointer;
  padding: 0;
  background: none;
}

.native-color::-webkit-color-swatch-wrapper { padding: 0; }
.native-color::-webkit-color-swatch { border: none; border-radius: 0.25rem; }

.hex-text {
  flex: 1;
  padding: 0.375rem 0.5rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.375rem;
  font-size: 0.8125rem;
  font-family: monospace;
  color: #0f172a;
}

/* Interactive Live Admin Mockup Frame */
.ui-mockup-frame {
  border: 1px solid #cbd5e1;
  border-radius: 0.625rem;
  overflow: hidden;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.mockup-topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0.75rem;
  border-bottom: 1px solid;
  font-size: 0.75rem;
}

.mockup-topbar-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.mockup-page-indicator {
  font-weight: 700;
}

.mockup-user-pill {
  border: 1px solid;
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.6875rem;
}

.mockup-layout-body {
  display: flex;
  min-height: 240px;
}

.mockup-sidebar {
  width: 140px;
  padding: 0.625rem 0.5rem;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
}

.mockup-sidebar-brand {
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.375rem;
  margin-bottom: 0.75rem;
  padding: 0 0.25rem;
}

.mockup-menu-list {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.mockup-menu-item {
  font-size: 0.6875rem;
  padding: 0.3125rem 0.5rem;
  border-radius: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-weight: 500;
}

.mockup-menu-item.active {
  font-weight: 700;
}

.mockup-content-pane {
  flex: 1;
  padding: 0.75rem;
}

.mockup-stats-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  margin-bottom: 0.625rem;
}

.mockup-stat-card {
  padding: 0.5rem;
  border: 1px solid;
  border-radius: 0.375rem;
}

.mockup-stat-title {
  font-size: 0.625rem;
  color: #64748b;
  margin-bottom: 0.125rem;
}

.mockup-stat-num {
  font-size: 0.875rem;
  font-weight: 700;
}

.mockup-stat-sub {
  font-size: 0.5625rem;
  color: #64748b;
}

.mockup-action-card {
  border: 1px solid;
  border-radius: 0.375rem;
  overflow: hidden;
}

.mockup-card-header {
  padding: 0.375rem 0.5rem;
  border-bottom: 1px solid;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.6875rem;
}

.mockup-btn {
  border: none;
  font-size: 0.5625rem;
  font-weight: 700;
  padding: 0.1875rem 0.375rem;
  border-radius: 0.25rem;
  cursor: pointer;
}

.mockup-card-body {
  padding: 0.5rem;
}

.mockup-table-skeleton {
  font-size: 0.625rem;
}

.skeleton-row {
  display: flex;
  justify-content: space-between;
  padding: 0.25rem 0;
  border-bottom: 1px solid #f1f5f9;
}

.skeleton-row.header {
  font-weight: 700;
  color: #64748b;
  border-bottom: 1px solid #e2e8f0;
}

.mockup-badge-success {
  background: #dcfce7;
  color: #15803d;
  padding: 0.0625rem 0.25rem;
  border-radius: 0.1875rem;
  font-size: 0.5625rem;
  font-weight: 700;
}

/* Header Upload Box */
.header-upload-box {
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 0.5rem;
  padding: 1rem;
  text-align: center;
}

.empty-header-box {
  color: #94a3b8;
  font-size: 0.8125rem;
}

.empty-header-box i {
  font-size: 2rem;
  margin-bottom: 0.25rem;
  display: block;
}

.empty-header-box p {
  margin: 0;
}

.header-img-preview img {
  max-width: 100%;
  max-height: 80px;
  object-fit: contain;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  background: #ffffff;
  padding: 0.25rem;
}

/* Live A4 Paper Sheet Preview */
.a4-sheet-container {
  background: #64748b;
  padding: 1rem;
  border-radius: 0.5rem;
  display: flex;
  justify-content: center;
}

.a4-sheet {
  background: #ffffff;
  width: 100%;
  max-width: 520px;
  padding: 1.5rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
  font-family: Arial, sans-serif;
  color: #0f172a;
}

.invoice-kop-standard {
  display: flex;
  gap: 0.875rem;
  align-items: center;
  margin-bottom: 0.5rem;
}

.kop-logo {
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.kop-logo-img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.kop-logo-icon {
  font-size: 2.25rem;
  color: #2563eb;
}

.kop-info {
  flex: 1;
  text-align: center;
}

.kop-institution {
  font-size: 0.9375rem;
  font-weight: 800;
  letter-spacing: 0.5px;
  color: #0f172a;
  line-height: 1.2;
}

.kop-address {
  font-size: 0.6875rem;
  color: #475569;
  line-height: 1.3;
  margin: 0.125rem 0;
}

.kop-contacts {
  font-size: 0.625rem;
  color: #64748b;
  display: flex;
  justify-content: center;
  gap: 0.375rem;
}

.kop-divider {
  border-bottom: 2px solid #0f172a;
  border-top: 1px solid #0f172a;
  height: 3px;
  margin-bottom: 1rem;
}

.invoice-title-area {
  text-align: center;
  margin-bottom: 1rem;
}

.invoice-title {
  font-size: 0.8125rem;
  font-weight: 800;
  text-decoration: underline;
  color: #0f172a;
}

.invoice-no {
  font-size: 0.6875rem;
  color: #64748b;
  margin-top: 0.125rem;
}

.invoice-meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  padding: 0.5rem 0.75rem;
  margin-bottom: 1rem;
  font-size: 0.6875rem;
}

.meta-item {
  display: flex;
  gap: 0.25rem;
  line-height: 1.4;
}

.meta-k {
  width: 65px;
  color: #64748b;
  font-weight: 600;
}

.meta-v {
  font-weight: 700;
  color: #0f172a;
}

.invoice-sample-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.6875rem;
  margin-bottom: 1rem;
}

.invoice-sample-table th, .invoice-sample-table td {
  border: 1px solid #cbd5e1;
  padding: 0.375rem 0.5rem;
}

.invoice-sample-table thead th {
  background: #f1f5f9;
  font-weight: 700;
  color: #0f172a;
}

.invoice-footer-area {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-top: 1.25rem;
}

.stamp-lunas {
  border: 2px solid #16a34a;
  color: #16a34a;
  font-weight: 900;
  font-size: 0.8125rem;
  padding: 0.25rem 0.625rem;
  border-radius: 0.25rem;
  display: inline-block;
  transform: rotate(-5deg);
  letter-spacing: 1px;
}

.stamp-date {
  font-size: 0.5625rem;
  color: #64748b;
  margin-top: 0.375rem;
}

.invoice-signature-box {
  text-align: center;
  font-size: 0.6875rem;
}

.sig-title {
  font-weight: 600;
  color: #64748b;
  margin-bottom: 2rem;
}

.sig-line {
  border-bottom: 1px solid #0f172a;
  width: 120px;
  margin: 0 auto 0.25rem;
}

.sig-name {
  font-weight: 700;
}

/* Sticky Action Bar */
.settings-action-bar {
  position: fixed;
  bottom: 0;
  left: var(--sidebar-width, 260px);
  right: 0;
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
  padding: 0.875rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.05);
  z-index: 40;
}

@media (max-width: 991px) {
  .settings-action-bar {
    left: 0;
  }
}

.action-info {
  font-size: 0.8125rem;
  color: #64748b;
}

.action-bar-right {
  display: flex;
  gap: 0.75rem;
}

/* Button Classes */
.btn-solid {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  padding: 0.5625rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 0.5rem;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
}

.btn-solid.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
}

.btn-solid.btn-primary {
  background: #2563eb;
  color: #ffffff;
  border-color: #2563eb;
}

.btn-solid.btn-primary:hover {
  background: #1d4ed8;
  border-color: #1d4ed8;
}

.btn-solid.btn-outline {
  background: #ffffff;
  border-color: #cbd5e1;
  color: #334155;
}

.btn-solid.btn-outline:hover {
  background: #f8fafc;
  border-color: #94a3b8;
}

.btn-solid.btn-danger {
  background: #dc2626;
  color: #ffffff;
  border-color: #dc2626;
}

.btn-solid.btn-danger:hover {
  background: #b91c1c;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  padding: 1rem;
}

.modal-box {
  background: #ffffff;
  border-radius: 0.75rem;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1rem;
  color: #94a3b8;
  cursor: pointer;
}

.modal-body {
  padding: 1.25rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 0.875rem 1.25rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  border-bottom-left-radius: 0.75rem;
  border-bottom-right-radius: 0.75rem;
}

/* Responsive */
@media (max-width: 1024px) {
  .grid-2-col {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .settings-header {
    flex-direction: column;
  }
  .header-actions {
    width: 100%;
    justify-content: stretch;
  }
  .header-actions button {
    flex: 1;
  }
  .color-fields-grid {
    grid-template-columns: 1fr;
  }
  .form-row-2 {
    grid-template-columns: 1fr;
  }
  .settings-action-bar {
    flex-direction: column;
    gap: 0.75rem;
    align-items: stretch;
    text-align: center;
  }
  .action-bar-right {
    justify-content: stretch;
  }
  .action-bar-right button {
    flex: 1;
  }
}
</style>
