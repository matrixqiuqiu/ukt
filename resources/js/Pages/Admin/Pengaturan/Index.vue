<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    theme: Object,
});

const form = useForm({
    sidebar_bg: props.theme?.sidebar_bg || '#1e293b',
    sidebar_text: props.theme?.sidebar_text || '#94a3b8',
    sidebar_icon: props.theme?.sidebar_icon || '#94a3b8',
    sidebar_active_text: props.theme?.sidebar_active_text || '#ffffff',
    sidebar_active_bg: props.theme?.sidebar_active_bg || '#4f46e5',
    sidebar_hover_bg: props.theme?.sidebar_hover_bg || '#334155',
    navbar_bg: props.theme?.navbar_bg || '#ffffff',
    navbar_text: props.theme?.navbar_text || '#1e293b',
    navbar_border: props.theme?.navbar_border || '#e2e8f0',
    primary_color: props.theme?.primary_color || '#4f46e5',
    logo_text: props.theme?.logo_text || '#ffffff',
    content_bg: props.theme?.content_bg || '#f8fafc',
    content_text: props.theme?.content_text || '#1e293b',
    card_bg: props.theme?.card_bg || '#ffffff',
    card_border: props.theme?.card_border || '#e2e8f0',
    invoice_institution_name: props.theme?.invoice_institution_name || '',
    invoice_institution_address: props.theme?.invoice_institution_address || '',
    invoice_institution_phone: props.theme?.invoice_institution_phone || '',
    invoice_institution_email: props.theme?.invoice_institution_email || '',
    invoice_institution_website: props.theme?.invoice_institution_website || '',
    invoice_logo: props.theme?.invoice_logo || '',
    invoice_header_image: props.theme?.invoice_header_image || '',
});

const activeTab = ref('theme');
const logoPreview = ref(props.theme?.invoice_logo || '');
const headerPreview = ref(props.theme?.invoice_header_image || '');
const isUploadingLogo = ref(false);
const isUploadingHeader = ref(false);
const logoInput = ref(null);
const headerInput = ref(null);

const presets = [
    {
        name: 'Default (Indigo)',
        sidebar_bg: '#1e293b', sidebar_text: '#94a3b8', sidebar_icon: '#94a3b8', sidebar_active_text: '#ffffff',
        sidebar_active_bg: '#4f46e5', sidebar_hover_bg: '#334155',
        navbar_bg: '#ffffff', navbar_text: '#1e293b', navbar_border: '#e2e8f0',
        primary_color: '#4f46e5', logo_text: '#ffffff',
    },
    {
        name: 'Biru Kampus',
        sidebar_bg: '#0f172a', sidebar_text: '#93c5fd', sidebar_icon: '#93c5fd', sidebar_active_text: '#ffffff',
        sidebar_active_bg: '#2563eb', sidebar_hover_bg: '#1e3a5f',
        navbar_bg: '#ffffff', navbar_text: '#0f172a', navbar_border: '#bfdbfe',
        primary_color: '#2563eb', logo_text: '#ffffff',
    },
    {
        name: 'Hijau Universitas',
        sidebar_bg: '#052e16', sidebar_text: '#86efac', sidebar_icon: '#86efac', sidebar_active_text: '#ffffff',
        sidebar_active_bg: '#16a34a', sidebar_hover_bg: '#14532d',
        navbar_bg: '#ffffff', navbar_text: '#052e16', navbar_border: '#bbf7d0',
        primary_color: '#16a34a', logo_text: '#ffffff',
    },
    {
        name: 'Merah Marun',
        sidebar_bg: '#1c0a00', sidebar_text: '#fca5a5', sidebar_icon: '#fca5a5', sidebar_active_text: '#ffffff',
        sidebar_active_bg: '#dc2626', sidebar_hover_bg: '#450a0a',
        navbar_bg: '#ffffff', navbar_text: '#1c0a00', navbar_border: '#fecaca',
        primary_color: '#dc2626', logo_text: '#ffffff',
    },
    {
        name: 'Ungu Akademik',
        sidebar_bg: '#0e0520', sidebar_text: '#c4b5fd', sidebar_icon: '#c4b5fd', sidebar_active_text: '#ffffff',
        sidebar_active_bg: '#7c3aed', sidebar_hover_bg: '#2e1065',
        navbar_bg: '#ffffff', navbar_text: '#0e0520', navbar_border: '#ddd6fe',
        primary_color: '#7c3aed', logo_text: '#ffffff',
    },
    {
        name: 'Emas',
        sidebar_bg: '#1a1500', sidebar_text: '#fcd34d', sidebar_icon: '#fcd34d', sidebar_active_text: '#1a1500',
        sidebar_active_bg: '#eab308', sidebar_hover_bg: '#422006',
        navbar_bg: '#ffffff', navbar_text: '#1a1500', navbar_border: '#fde68a',
        primary_color: '#eab308', logo_text: '#1a1500',
    },
];

const applyPreset = (preset) => {
    Object.keys(preset).forEach(key => {
        if (key !== 'name') {
            form[key] = preset[key];
        }
    });
};

const submit = () => {
    form.put(route('admin.pengaturan.update'), {
        preserveScroll: true,
    });
};

const resetTheme = () => {
    if (confirm('Yakin ingin mereset tema ke default?')) {
        router.post(route('admin.pengaturan.reset'), {}, { preserveScroll: true });
    }
};

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
        } else {
            console.error('Upload failed:', data);
        }
    } catch (error) {
        console.error('Upload failed:', error);
    } finally {
        isUploadingLogo.value = false;
    }
};

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
        } else {
            console.error('Upload failed:', data);
        }
    } catch (error) {
        console.error('Upload failed:', error);
    } finally {
        isUploadingHeader.value = false;
    }
};
</script>

<template>
    <Head title="Pengaturan Tema" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="page-heading">Pengaturan Tema</h2>
        </template>
        <div class="page-body">
            <div class="container-xl">
                <!-- Preset Themes -->
                <div class="custom-card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <h4>Template Tema</h4>
                        <button class="m-btn m-btn-sm m-btn-secondary" @click="resetTheme">
                            <i class="fas fa-undo"></i> Reset Default
                        </button>
                    </div>
                    <div class="card-body">
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;">
                            <button
                                v-for="preset in presets"
                                :key="preset.name"
                                class="preset-card"
                                :class="{ active: form.sidebar_bg === preset.sidebar_bg && form.sidebar_active_bg === preset.sidebar_active_bg }"
                                @click="applyPreset(preset)"
                            >
                                <div class="preset-preview">
                                    <div class="preset-sidebar" :style="{ background: preset.sidebar_bg }">
                                        <div class="preset-sidebar-item" :style="{ background: preset.sidebar_active_bg }"></div>
                                        <div class="preset-sidebar-item" :style="{ background: preset.sidebar_hover_bg }"></div>
                                    </div>
                                    <div class="preset-navbar" :style="{ background: preset.navbar_bg, borderBottomColor: preset.navbar_border }"></div>
                                </div>
                                <span class="preset-name">{{ preset.name }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                 <!-- Tab Navigation -->
                 <div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;border-bottom:2px solid var(--gray-200);">
                     <button
                         type="button"
                         class="tab-btn"
                         :class="{ active: activeTab === 'theme' }"
                         @click="activeTab = 'theme'"
                     >
                         <i class="fas fa-palette"></i> Tema
                     </button>
                     <button
                         type="button"
                         class="tab-btn"
                         :class="{ active: activeTab === 'content' }"
                         @click="activeTab = 'content'"
                     >
                         <i class="fas fa-layer-group"></i> Warna Content
                     </button>
                     <button
                         type="button"
                         class="tab-btn"
                         :class="{ active: activeTab === 'invoice' }"
                         @click="activeTab = 'invoice'"
                     >
                         <i class="fas fa-file-invoice"></i> Kop Surat Invoice
                     </button>
                 </div>

                 <form @submit.prevent="submit">
                     <!-- Theme Tab -->
                     <div v-show="activeTab === 'theme'">
                         <div style="display:grid;grid-template-columns:1fr 1fr;gap: 1.5rem;">
                             <!-- Sidebar Settings -->
                             <div class="custom-card">
                                 <div class="card-header">
                                     <h4><i class="fas fa-bars" style="margin-right:0.5rem;"></i> Sidebar</h4>
                                 </div>
                                 <div class="card-body">
                                     <div class="theme-field">
                                         <label class="theme-label">Background</label>
                                         <div class="theme-color-input">
                                             <input type="color" v-model="form.sidebar_bg" class="color-picker" />
                                             <input type="text" v-model="form.sidebar_bg" class="form-control" />
                                         </div>
                                     </div>
                                     <div class="theme-field">
                                         <label class="theme-label">Teks Menu</label>
                                         <div class="theme-color-input">
                                             <input type="color" v-model="form.sidebar_text" class="color-picker" />
                                             <input type="text" v-model="form.sidebar_text" class="form-control" />
                                         </div>
                                     </div>
                                     <div class="theme-field">
                                         <label class="theme-label">Warna Ikon</label>
                                         <div class="theme-color-input">
                                             <input type="color" v-model="form.sidebar_icon" class="color-picker" />
                                             <input type="text" v-model="form.sidebar_icon" class="form-control" />
                                         </div>
                                     </div>
                                     <div class="theme-field">
                                         <label class="theme-label">Background Aktif</label>
                                         <div class="theme-color-input">
                                             <input type="color" v-model="form.sidebar_active_bg" class="color-picker" />
                                             <input type="text" v-model="form.sidebar_active_bg" class="form-control" />
                                         </div>
                                     </div>
                                     <div class="theme-field">
                                         <label class="theme-label">Teks Aktif</label>
                                         <div class="theme-color-input">
                                             <input type="color" v-model="form.sidebar_active_text" class="color-picker" />
                                             <input type="text" v-model="form.sidebar_active_text" class="form-control" />
                                         </div>
                                     </div>
                                     <div class="theme-field">
                                         <label class="theme-label">Background Hover</label>
                                         <div class="theme-color-input">
                                             <input type="color" v-model="form.sidebar_hover_bg" class="color-picker" />
                                             <input type="text" v-model="form.sidebar_hover_bg" class="form-control" />
                                         </div>
                                     </div>
                                     <div class="theme-field">
                                         <label class="theme-label">Teks Logo</label>
                                         <div class="theme-color-input">
                                             <input type="color" v-model="form.logo_text" class="color-picker" />
                                             <input type="text" v-model="form.logo_text" class="form-control" />
                                         </div>
                                     </div>
                                 </div>
                             </div>

                             <!-- Navbar Settings -->
                             <div class="custom-card">
                                 <div class="card-header">
                                     <h4><i class="fas fa-minus" style="margin-right:0.5rem;"></i> Navbar</h4>
                                 </div>
                                 <div class="card-body">
                                     <div class="theme-field">
                                         <label class="theme-label">Background</label>
                                         <div class="theme-color-input">
                                             <input type="color" v-model="form.navbar_bg" class="color-picker" />
                                             <input type="text" v-model="form.navbar_bg" class="form-control" />
                                         </div>
                                     </div>
                                     <div class="theme-field">
                                         <label class="theme-label">Teks</label>
                                         <div class="theme-color-input">
                                             <input type="color" v-model="form.navbar_text" class="color-picker" />
                                             <input type="text" v-model="form.navbar_text" class="form-control" />
                                         </div>
                                     </div>
                                     <div class="theme-field">
                                         <label class="theme-label">Border</label>
                                         <div class="theme-color-input">
                                             <input type="color" v-model="form.navbar_border" class="color-picker" />
                                             <input type="text" v-model="form.navbar_border" class="form-control" />
                                         </div>
                                     </div>
                                     <div class="theme-field">
                                         <label class="theme-label">Warna Primer</label>
                                         <div class="theme-color-input">
                                             <input type="color" v-model="form.primary_color" class="color-picker" />
                                             <input type="text" v-model="form.primary_color" class="form-control" />
                                         </div>
                                     </div>

                                     <!-- Preview -->
                                     <div style="margin-top:1.5rem;padding:1rem;background:var(--gray-50);border-radius:0.75rem;">
                                         <div style="font-size:0.75rem;font-weight:600;color:var(--gray-600);margin-bottom:0.75rem;">Preview</div>
                                         <div class="theme-preview">
                                             <div class="theme-preview-sidebar" :style="{ background: form.sidebar_bg }">
                                                 <div style="padding:0.75rem;">
                                                     <div style="width:80%;height:8px;background:rgba(255,255,255,0.3);border-radius:4px;margin-bottom:0.75rem;"></div>
                                                     <div v-for="i in 4" :key="i" style="height:6px;border-radius:3px;margin-bottom:0.5rem;" :style="{ background: i === 1 ? form.sidebar_active_bg : form.sidebar_hover_bg, width: (70 + Math.random() * 20) + '%' }"></div>
                                                 </div>
                                             </div>
                                             <div class="theme-preview-main" :style="{ background: form.navbar_bg, borderBottomColor: form.navbar_border }">
                                                 <div style="width:60%;height:6px;background:rgba(0,0,0,0.1);border-radius:3px;"></div>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                      </div>

                      <!-- Content Tab -->
                      <div v-show="activeTab === 'content'" class="custom-card">
                          <div class="card-header">
                              <h4><i class="fas fa-layer-group" style="margin-right:0.5rem;"></i> Pengaturan Warna Content</h4>
                          </div>
                          <div class="card-body">
                              <div style="display:grid;grid-template-columns:1fr 1fr;gap: 1.5rem;">
                                  <div>
                                      <div class="theme-field">
                                          <label class="theme-label">Background Content</label>
                                          <div class="theme-color-input">
                                              <input type="color" v-model="form.content_bg" class="color-picker" />
                                              <input type="text" v-model="form.content_bg" class="form-control" />
                                          </div>
                                      </div>
                                      <div class="theme-field">
                                          <label class="theme-label">Teks Content</label>
                                          <div class="theme-color-input">
                                              <input type="color" v-model="form.content_text" class="color-picker" />
                                              <input type="text" v-model="form.content_text" class="form-control" />
                                          </div>
                                      </div>
                                  </div>
                                  <div>
                                      <div class="theme-field">
                                          <label class="theme-label">Background Card</label>
                                          <div class="theme-color-input">
                                              <input type="color" v-model="form.card_bg" class="color-picker" />
                                              <input type="text" v-model="form.card_bg" class="form-control" />
                                          </div>
                                      </div>
                                      <div class="theme-field">
                                          <label class="theme-label">Border Card</label>
                                          <div class="theme-color-input">
                                              <input type="color" v-model="form.card_border" class="color-picker" />
                                              <input type="text" v-model="form.card_border" class="form-control" />
                                          </div>
                                      </div>
                                  </div>
                              </div>

                              <!-- Preview -->
                              <div style="margin-top:1.5rem;padding:1.5rem;border:1px solid var(--gray-200);border-radius:0.75rem;">
                                  <div style="font-size:0.75rem;font-weight:600;color:var(--gray-600);margin-bottom:0.75rem;">Preview Content</div>
                                  <div :style="{ background: form.content_bg, color: form.content_text }">
                                      <div :style="{ background: form.card_bg, borderColor: form.card_border }" class="border p-3 rounded">
                                          <h5 class="mb-2">Judul Card</h5>
                                          <p class="mb-0">Ini adalah contoh teks content yang akan ditampilkan di halaman.</p>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>

                      <!-- Invoice Tab -->
                     <div v-show="activeTab === 'invoice'" class="custom-card">
                         <div class="card-header">
                             <h4><i class="fas fa-file-invoice" style="margin-right:0.5rem;"></i> Pengaturan Kop Surat Invoice</h4>
                         </div>
                         <div class="card-body">
                             <div class="theme-field">
                                 <label class="theme-label">Nama Institusi</label>
                                 <input type="text" v-model="form.invoice_institution_name" class="form-control" placeholder="Contoh: Universitas Bumiguna" />
                             </div>
                             <div class="theme-field">
                                 <label class="theme-label">Alamat</label>
                                 <textarea v-model="form.invoice_institution_address" class="form-control" rows="3" placeholder="Alamat lengkap institusi"></textarea>
                             </div>
                             <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                                 <div class="theme-field">
                                     <label class="theme-label">No. Telepon</label>
                                     <input type="text" v-model="form.invoice_institution_phone" class="form-control" placeholder="Contoh: (021) 1234567" />
                                 </div>
                                 <div class="theme-field">
                                     <label class="theme-label">Email</label>
                                     <input type="email" v-model="form.invoice_institution_email" class="form-control" placeholder="Contoh: info@ubg.ac.id" />
                                 </div>
                             </div>
                             <div class="theme-field">
                                 <label class="theme-label">Website</label>
                                 <input type="url" v-model="form.invoice_institution_website" class="form-control" placeholder="Contoh: https://www.ubg.ac.id" />
                             </div>

                             <!-- Logo Institusi -->
                             <div class="theme-field">
                                 <label class="theme-label">Logo Institusi (dipakai di PDF invoice, favicon & sidebar admin)</label>
                                 <div style="border:2px dashed var(--gray-300);border-radius:0.75rem;padding:1.5rem;text-align:center;">
                                     <div v-if="logoPreview" style="margin-bottom:1rem;">
                                         <img :src="logoPreview" alt="Logo Institusi" style="max-width:140px;max-height:96px;object-fit:contain;border:1px solid var(--gray-200);border-radius:0.5rem;background:#fff;padding:0.25rem;" />
                                     </div>
                                     <div v-else style="margin-bottom:1rem;color:var(--gray-500);">
                                         <i class="fas fa-image" style="font-size:2rem;margin-bottom:0.5rem;"></i>
                                         <p>Belum ada logo</p>
                                     </div>
                                     <div>
                                         <input
                                             type="file"
                                             accept="image/*"
                                             @change="uploadLogo"
                                             :disabled="isUploadingLogo"
                                             style="display:none;"
                                             ref="logoInput"
                                         />
                                         <button
                                             type="button"
                                             class="m-btn m-btn-sm m-btn-primary"
                                             @click="logoInput?.click()"
                                             :disabled="isUploadingLogo"
                                         >
                                             <i class="fas" :class="isUploadingLogo ? 'fa-spinner fa-pulse' : 'fa-upload'"></i>
                                             {{ isUploadingLogo ? 'Mengunggah...' : 'Upload Logo' }}
                                         </button>
                                         <div style="font-size:0.75rem;color:var(--gray-500);margin-top:0.5rem;">
                                             Format: JPG, PNG, GIF. Maks 2MB.
                                         </div>
                                     </div>
                                 </div>
                             </div>

                             <!-- Kop Surat Image -->
                             <div class="theme-field">
                                 <label class="theme-label">Kop Surat (Image)</label>
                                 <div style="border:2px dashed var(--gray-300);border-radius:0.75rem;padding:1.5rem;text-align:center;">
                                     <div v-if="headerPreview" style="margin-bottom:1rem;">
                                         <img :src="headerPreview" alt="Kop Surat" style="max-width:100%;max-height:120px;object-fit:contain;border:1px solid var(--gray-200);border-radius:0.5rem;" />
                                     </div>
                                     <div v-else style="margin-bottom:1rem;color:var(--gray-500);">
                                         <i class="fas fa-file-image" style="font-size:2rem;margin-bottom:0.5rem;"></i>
                                         <p>Belum ada kop surat</p>
                                     </div>
                                     <div>
                                         <input
                                             type="file"
                                             accept="image/*"
                                             @change="uploadHeader"
                                             :disabled="isUploadingHeader"
                                             style="display:none;"
                                             ref="headerInput"
                                         />
                                         <button
                                             type="button"
                                             class="m-btn m-btn-sm m-btn-primary"
                                             @click="headerInput?.click()"
                                             :disabled="isUploadingHeader"
                                         >
                                             <i class="fas" :class="isUploadingHeader ? 'fa-spinner fa-pulse' : 'fa-upload'"></i>
                                             {{ isUploadingHeader ? 'Mengunggah...' : 'Upload Kop Surat' }}
                                         </button>
                                         <div style="font-size:0.75rem;color:var(--gray-500);margin-top:0.5rem;">
                                             Format: JPG, PNG, GIF. Maks 5MB.
                                         </div>
                                     </div>
                                 </div>
                             </div>

                             <!-- Preview Kop Surat -->
                             <div style="margin-top:1.5rem;padding:1.5rem;border:1px solid var(--gray-200);border-radius:0.75rem;">
                                 <div style="font-size:0.75rem;font-weight:600;color:var(--gray-600);margin-bottom:0.75rem;">Preview Kop Surat</div>
                                 <div style="text-align:center;border-bottom:2px solid var(--gray-300);padding-bottom:1rem;margin-bottom:1rem;">
                                     <h3 style="font-size:1.25rem;font-weight:700;margin:0 0 0.25rem;">{{ form.invoice_institution_name || 'Nama Institusi' }}</h3>
                                     <p style="font-size:0.8125rem;color:var(--gray-600);margin:0 0 0.25rem;">
                                         {{ form.invoice_institution_address || 'Alamat institusi' }}
                                     </p>
                                     <p style="font-size:0.8125rem;color:var(--gray-600);margin:0;">
                                         Tel: {{ form.invoice_institution_phone || '-' }} |
                                         Email: {{ form.invoice_institution_email || '-' }} |
                                         Web: {{ form.invoice_institution_website || '-' }}
                                     </p>
                                 </div>
                             </div>
                         </div>
                     </div>

                     <!-- Submit -->
                     <div style="margin-top:1.5rem;display:flex;justify-content:flex-end;gap:0.75rem;">
                         <button type="button" class="m-btn m-btn-secondary" @click="resetTheme">
                             <i class="fas fa-undo"></i> Reset
                         </button>
                         <button type="submit" class="m-btn m-btn-primary" :disabled="form.processing">
                             <i class="fas fa-save"></i>
                             {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                         </button>
                     </div>
                 </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.preset-card {
    background: white;
    border: 2px solid var(--gray-200);
    border-radius: 0.75rem;
    padding: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.preset-card:hover {
    border-color: var(--gray-400);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.preset-card.active {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}

.preset-preview {
    display: flex;
    height: 48px;
    border-radius: 0.375rem;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.preset-sidebar {
    width: 40%;
    padding: 0.375rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.preset-sidebar-item {
    height: 4px;
    border-radius: 2px;
    width: 80%;
}

.preset-navbar {
    flex: 1;
    border-bottom: 2px solid;
}

.preset-name {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--gray-700);
}

.tab-btn {
    padding: 0.75rem 1.5rem;
    background: white;
    border: 2px solid transparent;
    border-bottom: 2px solid var(--gray-200);
    border-radius: 0;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--gray-600);
    cursor: pointer;
    transition: all 0.2s;
}

.tab-btn:hover {
    background: var(--gray-50);
    color: var(--gray-800);
}

.tab-btn.active {
    border-color: var(--gray-200) var(--gray-200) var(--primary);
    color: var(--primary);
    font-weight: 600;
}

.theme-field {
    margin-bottom: 1rem;
}

.theme-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.375rem;
}

.theme-color-input {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.color-picker {
    width: 40px;
    height: 40px;
    border: 2px solid var(--gray-200);
    border-radius: 0.5rem;
    cursor: pointer;
    padding: 2px;
}

.color-picker::-webkit-color-swatch-wrapper { padding: 0; }
.color-picker::-webkit-color-swatch { border: none; border-radius: 0.25rem; }

.theme-color-input .form-control {
    flex: 1;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-family: monospace;
}

.theme-preview {
    display: flex;
    height: 80px;
    border-radius: 0.5rem;
    overflow: hidden;
    border: 1px solid var(--gray-200);
}

.theme-preview-sidebar {
    width: 35%;
}

.theme-preview-main {
    flex: 1;
    display: flex;
    align-items: center;
    padding: 0 0.75rem;
    border-bottom: 2px solid;
}
</style>
