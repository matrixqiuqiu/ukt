<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatRupiah } from '@/utils';
import { useToast } from '@/composables/useToast';

const { success, error: toastError } = useToast();

const props = defineProps({
  konfigurasis: Array,
  angkatans: Array,
  jurusans: Array,
  komponens: Array,
  allKomponens: Array,
  summary: Array,
  filters: Object,
  activeTab: {
    type: String,
    default: 'biaya',
  },
  semesterAktif: Object,
});

const currentTab = ref(props.activeTab || 'biaya');

// ----------------------------------------------------
// TAB 1: TARIF BIAYA STATE & METHODS
// ----------------------------------------------------
const showTarifModal = ref(false);
const editTarifMode = ref(false);
const editTarifId = ref(null);
const showDeleteTarifModal = ref(false);
const tarifToDelete = ref(null);

const showCopyModal = ref(false);
const copyForm = useForm({
  source_angkatan: props.angkatans?.[0] || new Date().getFullYear() - 1,
  target_angkatan: new Date().getFullYear(),
});

const filterAngkatan = ref(props.filters?.angkatan || '');
const filterJurusan = ref(props.filters?.jurusan || '');
const searchTarif = ref('');

const tarifForm = useForm({
  komponen_biaya_id: '',
  angkatan: new Date().getFullYear(),
  jurusan: '',
  nominal: 0,
  status_aktif: true,
});

// Format input rupiah
const displayNominal = ref('');

const updateDisplayNominal = (val) => {
  if (!val && val !== 0) {
    displayNominal.value = '';
    tarifForm.nominal = 0;
    return;
  }
  const clean = String(val).replace(/[^0-9]/g, '');
  const num = parseInt(clean, 10) || 0;
  tarifForm.nominal = num;
  displayNominal.value = new Intl.NumberFormat('id-ID').format(num);
};

const onNominalInput = (e) => {
  updateDisplayNominal(e.target.value);
};

const applyFilter = () => {
  const params = {};
  if (filterAngkatan.value) params.angkatan = filterAngkatan.value;
  if (filterJurusan.value) params.jurusan = filterJurusan.value;
  params.tab = 'biaya';
  router.get(route('admin.biaya.index'), params, { preserveState: true });
};

const resetFilter = () => {
  filterAngkatan.value = '';
  filterJurusan.value = '';
  searchTarif.value = '';
  router.get(route('admin.biaya.index'), { tab: 'biaya' }, { preserveState: true });
};

const openCreateTarif = () => {
  editTarifMode.value = false;
  editTarifId.value = null;
  tarifForm.reset();
  tarifForm.status_aktif = true;
  tarifForm.angkatan = props.angkatans?.[0] || new Date().getFullYear();
  tarifForm.jurusan = props.jurusans?.[0] || '';
  tarifForm.komponen_biaya_id = props.komponens?.[0]?.id || '';
  updateDisplayNominal(0);
  showTarifModal.value = true;
};

const openEditTarif = (item) => {
  editTarifMode.value = true;
  editTarifId.value = item.id;
  tarifForm.komponen_biaya_id = item.komponen_biaya_id;
  tarifForm.angkatan = item.angkatan;
  tarifForm.jurusan = item.jurusan;
  tarifForm.nominal = item.nominal;
  tarifForm.status_aktif = item.status_aktif;
  updateDisplayNominal(item.nominal);
  showTarifModal.value = true;
};

const closeTarifModal = () => {
  showTarifModal.value = false;
  tarifForm.clearErrors();
  tarifForm.reset();
};

const submitTarif = () => {
  if (editTarifMode.value) {
    tarifForm.put(route('admin.biaya.update', editTarifId.value), {
      preserveScroll: true,
      onSuccess: () => {
        closeTarifModal();
        success('Konfigurasi tarif berhasil diperbarui.');
      },
      onError: () => toastError('Gagal memperbarui tarif. Periksa isian form.'),
    });
  } else {
    tarifForm.post(route('admin.biaya.store'), {
      preserveScroll: true,
      onSuccess: () => {
        closeTarifModal();
        success('Konfigurasi tarif baru berhasil ditambahkan.');
      },
      onError: () => toastError('Gagal menambahkan tarif. Periksa isian form.'),
    });
  }
};

const confirmDeleteTarif = (item) => {
  tarifToDelete.value = item;
  showDeleteTarifModal.value = true;
};

const executeDeleteTarif = () => {
  if (!tarifToDelete.value) return;
  router.delete(route('admin.biaya.destroy', tarifToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteTarifModal.value = false;
      tarifToDelete.value = null;
      success('Konfigurasi tarif berhasil dihapus.');
    },
    onError: () => toastError('Gagal menghapus konfigurasi tarif.'),
  });
};

const toggleStatusTarif = (item) => {
  router.post(route('admin.biaya.toggle', item.id), {}, {
    preserveScroll: true,
    onSuccess: () => success('Status tarif berhasil diubah.'),
  });
};

const submitCopyAngkatan = () => {
  copyForm.post(route('admin.biaya.copy-angkatan'), {
    preserveScroll: true,
    onSuccess: () => {
      showCopyModal.value = false;
      success(`Konfigurasi tarif berhasil disalin ke angkatan ${copyForm.target_angkatan}.`);
    },
    onError: () => toastError('Gagal menyalin tarif antar-angkatan.'),
  });
};

// Filtered data for table
const filteredKonfigurasis = computed(() => {
  let list = props.konfigurasis || [];
  if (filterAngkatan.value) {
    list = list.filter(k => k.angkatan == filterAngkatan.value);
  }
  if (filterJurusan.value) {
    list = list.filter(k => k.jurusan === filterJurusan.value);
  }
  if (searchTarif.value) {
    const q = searchTarif.value.toLowerCase().trim();
    list = list.filter(k =>
      (k.jurusan && k.jurusan.toLowerCase().includes(q)) ||
      (k.komponen_biaya?.nama && k.komponen_biaya.nama.toLowerCase().includes(q)) ||
      String(k.angkatan).includes(q)
    );
  }
  return list;
});

// Summary stats
const totalTarifConfigs = computed(() => (props.konfigurasis || []).length);
const totalAngkatans = computed(() => (props.angkatans || []).length);
const totalJurusans = computed(() => (props.jurusans || []).length);
const totalKomponenActive = computed(() => (props.komponens || []).length);

// ----------------------------------------------------
// TAB 2: MASTER KOMPONEN BIAYA STATE & METHODS
// ----------------------------------------------------
const showKomponenModal = ref(false);
const editKomponenMode = ref(false);
const editKomponenId = ref(null);
const showDeleteKomponenModal = ref(false);
const showBlockedKomponenModal = ref(false);
const komponenToDelete = ref(null);
const searchKomponen = ref('');

const komponenForm = useForm({
  nama: '',
  kode: '',
  deskripsi: '',
  status_aktif: true,
});

const openCreateKomponen = () => {
  editKomponenMode.value = false;
  editKomponenId.value = null;
  komponenForm.reset();
  komponenForm.status_aktif = true;
  showKomponenModal.value = true;
};

const openEditKomponen = (item) => {
  editKomponenMode.value = true;
  editKomponenId.value = item.id;
  komponenForm.nama = item.nama;
  komponenForm.kode = item.kode;
  komponenForm.deskripsi = item.deskripsi || '';
  komponenForm.status_aktif = Boolean(item.status_aktif);
  showKomponenModal.value = true;
};

const closeKomponenModal = () => {
  showKomponenModal.value = false;
  komponenForm.clearErrors();
  komponenForm.reset();
};

const submitKomponen = () => {
  if (editKomponenMode.value) {
    komponenForm.put(route('admin.komponen-biaya.update', editKomponenId.value), {
      preserveScroll: true,
      onSuccess: () => {
        closeKomponenModal();
        success('Komponen biaya berhasil diperbarui.');
      },
      onError: () => toastError('Gagal memperbarui komponen biaya.'),
    });
  } else {
    komponenForm.post(route('admin.komponen-biaya.store'), {
      preserveScroll: true,
      onSuccess: () => {
        closeKomponenModal();
        success('Komponen biaya baru berhasil ditambahkan.');
      },
      onError: () => toastError('Gagal menambahkan komponen biaya.'),
    });
  }
};

const confirmDeleteKomponen = (item) => {
  komponenToDelete.value = item;
  if (item.konfigurasis_count > 0) {
    showBlockedKomponenModal.value = true;
  } else {
    showDeleteKomponenModal.value = true;
  }
};

const executeDeleteKomponen = () => {
  if (!komponenToDelete.value) return;
  router.delete(route('admin.komponen-biaya.destroy', komponenToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteKomponenModal.value = false;
      komponenToDelete.value = null;
      success('Komponen biaya berhasil dihapus.');
    },
    onError: () => toastError('Gagal menghapus komponen biaya.'),
  });
};

const toggleStatusKomponen = (item) => {
  router.post(route('admin.komponen-biaya.toggle', item.id), {}, {
    preserveScroll: true,
    onSuccess: () => success('Status komponen biaya berhasil diubah.'),
  });
};

const filteredAllKomponens = computed(() => {
  let list = props.allKomponens || [];
  if (searchKomponen.value) {
    const q = searchKomponen.value.toLowerCase().trim();
    list = list.filter(k =>
      (k.nama && k.nama.toLowerCase().includes(q)) ||
      (k.kode && k.kode.toLowerCase().includes(q)) ||
      (k.deskripsi && k.deskripsi.toLowerCase().includes(q))
    );
  }
  return list;
});

const filterByKomponenInTarif = (komponenNama) => {
  currentTab.value = 'biaya';
  searchTarif.value = komponenNama;
};
</script>

<template>
  <Head title="Tarif & Komponen Biaya" />

  <AuthenticatedLayout>
    <div class="biaya-page-wrapper">
      <!-- Header Page -->
      <div class="biaya-header">
        <div class="header-left">
          <div class="header-badge">
            <span class="badge-pill">
              <i class="fas fa-coins"></i> Master Keuangan
            </span>
            <span v-if="semesterAktif" class="badge-pill pill-neutral">
              <i class="fas fa-calendar-alt"></i> TA: <strong>{{ semesterAktif.tahun_akademik }}</strong> (Jatuh Tempo: {{ new Date(semesterAktif.jatuh_tempo).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }})
            </span>
          </div>
          <h1 class="biaya-title">Pengaturan Tarif &amp; Komponen Biaya</h1>
          <p class="biaya-subtitle">Kelola master jenis komponen biaya dan konfigurasi tarif nominal per angkatan &amp; program studi</p>
        </div>
        <div class="header-actions">
          <template v-if="currentTab === 'biaya'">
            <button type="button" class="btn-solid btn-outline" @click="showCopyModal = true">
              <i class="fas fa-copy"></i> Salin Tarif Angkatan
            </button>
            <button type="button" class="btn-solid btn-primary" @click="openCreateTarif">
              <i class="fas fa-plus"></i> Tambah Tarif Baru
            </button>
          </template>
          <template v-else>
            <button type="button" class="btn-solid btn-primary" @click="openCreateKomponen">
              <i class="fas fa-plus"></i> Tambah Komponen
            </button>
          </template>
        </div>
      </div>

      <!-- Segmented Navigation Tabs -->
      <div class="biaya-nav-tabs">
        <button
          type="button"
          class="nav-tab-btn"
          :class="{ active: currentTab === 'biaya' }"
          @click="currentTab = 'biaya'"
        >
          <i class="fas fa-layer-group"></i>
          <span>Matriks Tarif Biaya (Per Angkatan &amp; Prodi)</span>
          <span class="tab-count-badge">{{ totalTarifConfigs }}</span>
        </button>
        <button
          type="button"
          class="nav-tab-btn"
          :class="{ active: currentTab === 'komponen' }"
          @click="currentTab = 'komponen'"
        >
          <i class="fas fa-tags"></i>
          <span>Master Jenis Komponen Biaya</span>
          <span class="tab-count-badge">{{ (allKomponens || []).length }}</span>
        </button>
      </div>

      <!-- ================= TAB 1: MATRIKS TARIF BIAYA ================= -->
      <div v-show="currentTab === 'biaya'" class="tab-panel">
        <!-- 4 Metric Cards Solid -->
        <div class="metrics-grid-4">
          <div class="metric-card-solid">
            <div class="metric-icon bg-blue"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="metric-info">
              <div class="metric-label">Total Konfigurasi Tarif</div>
              <div class="metric-value">{{ totalTarifConfigs }} <small>Aturan</small></div>
            </div>
          </div>
          <div class="metric-card-solid">
            <div class="metric-icon bg-green"><i class="fas fa-user-graduate"></i></div>
            <div class="metric-info">
              <div class="metric-label">Angkatan Terdaftar</div>
              <div class="metric-value">{{ totalAngkatans }} <small>Angkatan</small></div>
            </div>
          </div>
          <div class="metric-card-solid">
            <div class="metric-icon bg-purple"><i class="fas fa-graduation-cap"></i></div>
            <div class="metric-info">
              <div class="metric-label">Program Studi Terdaftar</div>
              <div class="metric-value">{{ totalJurusans }} <small>Prodi</small></div>
            </div>
          </div>
          <div class="metric-card-solid">
            <div class="metric-icon bg-amber"><i class="fas fa-cubes"></i></div>
            <div class="metric-info">
              <div class="metric-label">Komponen Biaya Aktif</div>
              <div class="metric-value">{{ totalKomponenActive }} <small>Komponen</small></div>
            </div>
          </div>
        </div>

        <!-- Filter & Search Toolbar (Height 36px Aligned) -->
        <div class="toolbar-card">
          <div class="toolbar-row">
            <!-- Left: Filter Dropdowns -->
            <div class="toolbar-filters">
              <div class="filter-item">
                <label>Angkatan:</label>
                <select v-model="filterAngkatan" @change="applyFilter" class="select-field-36">
                  <option value="">Semua Angkatan</option>
                  <option v-for="a in angkatans" :key="a" :value="a">{{ a }}</option>
                </select>
              </div>

              <div class="filter-item">
                <label>Prodi:</label>
                <select v-model="filterJurusan" @change="applyFilter" class="select-field-36" style="max-width:240px;">
                  <option value="">Semua Program Studi</option>
                  <option v-for="j in jurusans" :key="j" :value="j">{{ j }}</option>
                </select>
              </div>

              <button
                v-if="filterAngkatan || filterJurusan || searchTarif"
                type="button"
                class="btn-reset-filter"
                @click="resetFilter"
                title="Reset filter"
              >
                <i class="fas fa-times"></i> Reset Filter
              </button>
            </div>

            <!-- Right: Search Box -->
            <div class="toolbar-search">
              <div class="clean-search-box">
                <i class="fas fa-search search-icon"></i>
                <input
                  type="text"
                  v-model="searchTarif"
                  placeholder="Cari prodi / komponen..."
                  class="search-input"
                />
                <button v-if="searchTarif" type="button" class="search-clear-btn" @click="searchTarif = ''">×</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Table Data Card -->
        <div class="solid-card">
          <div class="card-header-bar">
            <h3><i class="fas fa-list text-primary"></i> Daftar Matriks Konfigurasi Tarif Biaya</h3>
            <span class="count-badge-solid">Menampilkan {{ filteredKonfigurasis.length }} dari {{ totalTarifConfigs }} tarif</span>
          </div>
          <div class="card-body-content">
            <div class="table-responsive">
              <table class="solid-data-table">
                <thead>
                  <tr>
                    <th style="width:50px;">No</th>
                    <th style="width:100px;">Angkatan</th>
                    <th>Program Studi</th>
                    <th>Komponen Biaya</th>
                    <th style="text-align:right;width:180px;">Nominal Tarif</th>
                    <th style="width:110px;text-align:center;">Status</th>
                    <th style="width:130px;text-align:center;">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in filteredKonfigurasis" :key="item.id">
                    <td class="text-center" style="color:#64748b;">{{ index + 1 }}</td>
                    <td>
                      <span class="angkatan-tag">{{ item.angkatan }}</span>
                    </td>
                    <td style="font-weight:600;color:#0f172a;">
                      {{ item.jurusan }}
                    </td>
                    <td>
                      <span class="komponen-pill">
                        <i class="fas fa-tag"></i> {{ item.komponen_biaya?.nama || '-' }}
                      </span>
                    </td>
                    <td style="text-align:right;">
                      <span class="nominal-value">{{ formatRupiah(item.nominal) }}</span>
                    </td>
                    <td style="text-align:center;">
                      <span class="status-badge" :class="item.status_aktif ? 'status-active' : 'status-inactive'">
                        <span class="status-dot"></span>
                        {{ item.status_aktif ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td>
                      <div class="action-btn-group">
                        <button
                          type="button"
                          class="btn-act"
                          :class="item.status_aktif ? 'btn-act-warning' : 'btn-act-success'"
                          @click="toggleStatusTarif(item)"
                          :title="item.status_aktif ? 'Nonaktifkan' : 'Aktifkan'"
                        >
                          <i :class="item.status_aktif ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
                        </button>
                        <button
                          type="button"
                          class="btn-act btn-act-edit"
                          @click="openEditTarif(item)"
                          title="Edit Tarif"
                        >
                          <i class="fas fa-pen"></i>
                        </button>
                        <button
                          type="button"
                          class="btn-act btn-act-delete"
                          @click="confirmDeleteTarif(item)"
                          title="Hapus Tarif"
                        >
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="filteredKonfigurasis.length === 0">
                    <td colspan="7" class="empty-state-cell">
                      <i class="fas fa-inbox empty-icon"></i>
                      <p>Tidak ada data konfigurasi tarif yang sesuai dengan kriteria pencarian.</p>
                      <button type="button" class="btn-solid btn-primary btn-sm" @click="openCreateTarif" style="margin-top:0.5rem;">
                        <i class="fas fa-plus"></i> Tambah Tarif Baru
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= TAB 2: MASTER KOMPONEN BIAYA ================= -->
      <div v-show="currentTab === 'komponen'" class="tab-panel">
        <!-- 3 Metric Cards Solid -->
        <div class="metrics-grid-3">
          <div class="metric-card-solid">
            <div class="metric-icon bg-blue"><i class="fas fa-tags"></i></div>
            <div class="metric-info">
              <div class="metric-label">Total Jenis Komponen</div>
              <div class="metric-value">{{ (allKomponens || []).length }} <small>Komponen</small></div>
            </div>
          </div>
          <div class="metric-card-solid">
            <div class="metric-icon bg-green"><i class="fas fa-check-circle"></i></div>
            <div class="metric-info">
              <div class="metric-label">Komponen Status Aktif</div>
              <div class="metric-value">{{ (allKomponens || []).filter(k => k.status_aktif).length }} <small>Aktif</small></div>
            </div>
          </div>
          <div class="metric-card-solid">
            <div class="metric-icon bg-purple"><i class="fas fa-link"></i></div>
            <div class="metric-info">
              <div class="metric-label">Komponen Terpakai di Tarif</div>
              <div class="metric-value">{{ (allKomponens || []).filter(k => k.konfigurasis_count > 0).length }} <small>Terpasang</small></div>
            </div>
          </div>
        </div>

        <!-- Toolbar Komponen -->
        <div class="toolbar-card">
          <div class="toolbar-row">
            <div class="toolbar-filters">
              <div class="clean-search-box" style="width:320px;">
                <i class="fas fa-search search-icon"></i>
                <input
                  type="text"
                  v-model="searchKomponen"
                  placeholder="Cari nama / kode komponen..."
                  class="search-input"
                />
                <button v-if="searchKomponen" type="button" class="search-clear-btn" @click="searchKomponen = ''">×</button>
              </div>
            </div>
            <div class="toolbar-search">
              <button type="button" class="btn-solid btn-primary btn-sm" @click="openCreateKomponen">
                <i class="fas fa-plus"></i> Tambah Komponen Baru
              </button>
            </div>
          </div>
        </div>

        <!-- Table Master Komponen Data -->
        <div class="solid-card">
          <div class="card-header-bar">
            <h3><i class="fas fa-tags text-primary"></i> Master Data Jenis Komponen Biaya</h3>
            <span class="count-badge-solid">Total {{ filteredAllKomponens.length }} komponen</span>
          </div>
          <div class="card-body-content">
            <div class="table-responsive">
              <table class="solid-data-table">
                <thead>
                  <tr>
                    <th style="width:50px;">No</th>
                    <th style="width:120px;">Kode</th>
                    <th>Nama Komponen</th>
                    <th>Deskripsi / Penjelasan</th>
                    <th style="width:140px;text-align:center;">Digunakan di Tarif</th>
                    <th style="width:110px;text-align:center;">Status</th>
                    <th style="width:130px;text-align:center;">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in filteredAllKomponens" :key="item.id">
                    <td class="text-center" style="color:#64748b;">{{ index + 1 }}</td>
                    <td>
                      <span class="code-badge-solid">{{ item.kode }}</span>
                    </td>
                    <td style="font-weight:700;color:#0f172a;">
                      {{ item.nama }}
                    </td>
                    <td style="color:#475569;font-size:0.8125rem;">
                      {{ item.deskripsi || '-' }}
                    </td>
                    <td style="text-align:center;">
                      <button
                        type="button"
                        class="count-link-badge"
                        :disabled="item.konfigurasis_count === 0"
                        @click="filterByKomponenInTarif(item.nama)"
                        :title="item.konfigurasis_count > 0 ? 'Klik untuk melihat tarif terkait' : 'Belum digunakan'"
                      >
                        <i class="fas fa-layer-group"></i> {{ item.konfigurasis_count }} Tarif
                      </button>
                    </td>
                    <td style="text-align:center;">
                      <span class="status-badge" :class="item.status_aktif ? 'status-active' : 'status-inactive'">
                        <span class="status-dot"></span>
                        {{ item.status_aktif ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td>
                      <div class="action-btn-group">
                        <button
                          type="button"
                          class="btn-act"
                          :class="item.status_aktif ? 'btn-act-warning' : 'btn-act-success'"
                          @click="toggleStatusKomponen(item)"
                          :title="item.status_aktif ? 'Nonaktifkan' : 'Aktifkan'"
                        >
                          <i :class="item.status_aktif ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
                        </button>
                        <button
                          type="button"
                          class="btn-act btn-act-edit"
                          @click="openEditKomponen(item)"
                          title="Edit Komponen"
                        >
                          <i class="fas fa-pen"></i>
                        </button>
                        <button
                          type="button"
                          class="btn-act btn-act-delete"
                          @click="confirmDeleteKomponen(item)"
                          title="Hapus Komponen"
                        >
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="filteredAllKomponens.length === 0">
                    <td colspan="7" class="empty-state-cell">
                      <i class="fas fa-tags empty-icon"></i>
                      <p>Belum ada jenis komponen biaya yang cocok.</p>
                      <button type="button" class="btn-solid btn-primary btn-sm" @click="openCreateKomponen" style="margin-top:0.5rem;">
                        <i class="fas fa-plus"></i> Tambah Komponen Baru
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= MODALS TELEPORT ================= -->
      <Teleport to="body">
        <!-- 1. MODAL TAMBAH/EDIT TARIF BIAYA -->
        <div v-if="showTarifModal" class="modal-overlay" @click.self="closeTarifModal">
          <div class="modal-box">
            <div class="modal-header">
              <h3>
                <i class="fas" :class="editTarifMode ? 'fa-pen text-primary' : 'fa-plus-circle text-primary'"></i>
                {{ editTarifMode ? 'Edit Konfigurasi Tarif' : 'Tambah Konfigurasi Tarif Baru' }}
              </h3>
              <button type="button" class="modal-close" @click="closeTarifModal"><i class="fas fa-times"></i></button>
            </div>
            <form @submit.prevent="submitTarif">
              <div class="modal-body">
                <div class="form-group-modal">
                  <label>Jenis Komponen Biaya <span class="req">*</span></label>
                  <select v-model="tarifForm.komponen_biaya_id" class="input-modal-select" required>
                    <option value="" disabled>-- Pilih Komponen Biaya --</option>
                    <option v-for="k in komponens" :key="k.id" :value="k.id">
                      {{ k.nama }} ({{ k.kode }})
                    </option>
                  </select>
                </div>

                <div class="grid-modal-2">
                  <div class="form-group-modal">
                    <label>Tahun Angkatan <span class="req">*</span></label>
                    <select v-model="tarifForm.angkatan" class="input-modal-select" required>
                      <option v-for="a in angkatans" :key="a" :value="a">{{ a }}</option>
                    </select>
                  </div>
                  <div class="form-group-modal">
                    <label>Status Aktif</label>
                    <select v-model="tarifForm.status_aktif" class="input-modal-select">
                      <option :value="true">Aktif (Berlaku)</option>
                      <option :value="false">Nonaktif (Draft)</option>
                    </select>
                  </div>
                </div>

                <div class="form-group-modal">
                  <label>Program Studi <span class="req">*</span></label>
                  <select v-model="tarifForm.jurusan" class="input-modal-select" required>
                    <option value="" disabled>-- Pilih Program Studi --</option>
                    <option v-for="j in jurusans" :key="j" :value="j">{{ j }}</option>
                  </select>
                </div>

                <div class="form-group-modal">
                  <label>Besaran Nominal Biaya (Rupiah) <span class="req">*</span></label>
                  <div class="currency-input-wrapper">
                    <span class="curr-prefix">Rp</span>
                    <input
                      type="text"
                      :value="displayNominal"
                      @input="onNominalInput"
                      class="input-modal-currency"
                      placeholder="0"
                      required
                    />
                  </div>
                  <div class="curr-helper">Terbilang: <strong>{{ formatRupiah(tarifForm.nominal) }}</strong></div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn-solid btn-outline" @click="closeTarifModal">Batal</button>
                <button type="submit" class="btn-solid btn-primary" :disabled="tarifForm.processing">
                  <i class="fas" :class="tarifForm.processing ? 'fa-spinner fa-pulse' : 'fa-save'"></i>
                  {{ tarifForm.processing ? 'Menyimpan...' : 'Simpan Konfigurasi' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- 2. MODAL SALIN TARIF ANTAR-ANGKATAN -->
        <div v-if="showCopyModal" class="modal-overlay" @click.self="showCopyModal = false">
          <div class="modal-box">
            <div class="modal-header">
              <h3 style="color:#2563eb;">
                <i class="fas fa-copy"></i> Salin Tarif Antar-Angkatan
              </h3>
              <button type="button" class="modal-close" @click="showCopyModal = false"><i class="fas fa-times"></i></button>
            </div>
            <form @submit.prevent="submitCopyAngkatan">
              <div class="modal-body">
                <div class="copy-guide-card">
                  <i class="fas fa-lightbulb guide-bulb"></i>
                  <p>Fitur ini menduplikasi seluruh konfigurasi tarif aktif dari <strong>Angkatan Sumber</strong> ke <strong>Angkatan Tujuan</strong>. Data yang sudah ada di angkatan tujuan tidak akan ditimpa.</p>
                </div>

                <div class="grid-modal-2" style="margin-top:1rem;">
                  <div class="form-group-modal">
                    <label>Angkatan Sumber (Asal) <span class="req">*</span></label>
                    <select v-model="copyForm.source_angkatan" class="input-modal-select" required>
                      <option v-for="a in angkatans" :key="a" :value="a">{{ a }}</option>
                    </select>
                  </div>
                  <div class="form-group-modal">
                    <label>Angkatan Tujuan (Baru) <span class="req">*</span></label>
                    <input
                      type="number"
                      v-model="copyForm.target_angkatan"
                      class="input-modal-text"
                      placeholder="Contoh: 2026"
                      required
                    />
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn-solid btn-outline" @click="showCopyModal = false">Batal</button>
                <button type="submit" class="btn-solid btn-primary" :disabled="copyForm.processing">
                  <i class="fas" :class="copyForm.processing ? 'fa-spinner fa-pulse' : 'fa-copy'"></i>
                  {{ copyForm.processing ? 'Menyalin...' : 'Mulai Salin Tarif' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- 3. MODAL HAPUS TARIF -->
        <div v-if="showDeleteTarifModal" class="modal-overlay" @click.self="showDeleteTarifModal = false">
          <div class="modal-box" style="max-width:420px;">
            <div class="modal-header">
              <h3 style="color:#dc2626;"><i class="fas fa-trash-alt"></i> Hapus Konfigurasi Tarif</h3>
              <button type="button" class="modal-close" @click="showDeleteTarifModal = false"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
              <p style="margin:0 0 0.5rem;color:#334155;font-size:0.875rem;">
                Apakah Anda yakin ingin menghapus konfigurasi tarif berikut?
              </p>
              <div v-if="tarifToDelete" class="delete-item-preview">
                <div><strong>Angkatan:</strong> {{ tarifToDelete.angkatan }}</div>
                <div><strong>Prodi:</strong> {{ tarifToDelete.jurusan }}</div>
                <div><strong>Komponen:</strong> {{ tarifToDelete.komponen_biaya?.nama }}</div>
                <div><strong>Nominal:</strong> {{ formatRupiah(tarifToDelete.nominal) }}</div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn-solid btn-outline" @click="showDeleteTarifModal = false">Batal</button>
              <button type="button" class="btn-solid btn-danger" @click="executeDeleteTarif">
                <i class="fas fa-trash-alt"></i> Ya, Hapus Tarif
              </button>
            </div>
          </div>
        </div>

        <!-- 4. MODAL TAMBAH/EDIT KOMPONEN BIAYA -->
        <div v-if="showKomponenModal" class="modal-overlay" @click.self="closeKomponenModal">
          <div class="modal-box">
            <div class="modal-header">
              <h3>
                <i class="fas" :class="editKomponenMode ? 'fa-pen text-primary' : 'fa-plus-circle text-primary'"></i>
                {{ editKomponenMode ? 'Edit Komponen Biaya' : 'Tambah Komponen Biaya Baru' }}
              </h3>
              <button type="button" class="modal-close" @click="closeKomponenModal"><i class="fas fa-times"></i></button>
            </div>
            <form @submit.prevent="submitKomponen">
              <div class="modal-body">
                <div class="form-group-modal">
                  <label>Nama Komponen Biaya <span class="req">*</span></label>
                  <input
                    type="text"
                    v-model="komponenForm.nama"
                    class="input-modal-text"
                    placeholder="Contoh: Uang Kuliah Tunggal (UKT)"
                    required
                  />
                </div>

                <div class="grid-modal-2">
                  <div class="form-group-modal">
                    <label>Kode Singkat / Alias <span class="req">*</span></label>
                    <input
                      type="text"
                      v-model="komponenForm.kode"
                      class="input-modal-text"
                      placeholder="Contoh: UKT"
                      style="text-transform:uppercase;"
                      required
                    />
                  </div>
                  <div class="form-group-modal">
                    <label>Status</label>
                    <select v-model="komponenForm.status_aktif" class="input-modal-select">
                      <option :value="true">Aktif</option>
                      <option :value="false">Nonaktif</option>
                    </select>
                  </div>
                </div>

                <div class="form-group-modal">
                  <label>Deskripsi / Keterangan (Opsional)</label>
                  <textarea
                    v-model="komponenForm.deskripsi"
                    class="input-modal-textarea"
                    rows="3"
                    placeholder="Penjelasan rincian komponen biaya..."
                  ></textarea>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn-solid btn-outline" @click="closeKomponenModal">Batal</button>
                <button type="submit" class="btn-solid btn-primary" :disabled="komponenForm.processing">
                  <i class="fas" :class="komponenForm.processing ? 'fa-spinner fa-pulse' : 'fa-save'"></i>
                  {{ komponenForm.processing ? 'Menyimpan...' : 'Simpan Komponen' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- 5. MODAL HAPUS KOMPONEN -->
        <div v-if="showDeleteKomponenModal" class="modal-overlay" @click.self="showDeleteKomponenModal = false">
          <div class="modal-box" style="max-width:420px;">
            <div class="modal-header">
              <h3 style="color:#dc2626;"><i class="fas fa-trash-alt"></i> Hapus Komponen Biaya</h3>
              <button type="button" class="modal-close" @click="showDeleteKomponenModal = false"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
              <p style="margin:0;color:#334155;font-size:0.875rem;">
                Apakah Anda yakin ingin menghapus komponen biaya <strong>"{{ komponenToDelete?.nama }}"</strong>?
              </p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn-solid btn-outline" @click="showDeleteKomponenModal = false">Batal</button>
              <button type="button" class="btn-solid btn-danger" @click="executeDeleteKomponen">
                <i class="fas fa-trash-alt"></i> Ya, Hapus Komponen
              </button>
            </div>
          </div>
        </div>

        <!-- 6. MODAL BLOKIR HAPUS KOMPONEN (MASIH TERPAKAI) -->
        <div v-if="showBlockedKomponenModal" class="modal-overlay" @click.self="showBlockedKomponenModal = false">
          <div class="modal-box" style="max-width:440px;">
            <div class="modal-header">
              <h3 style="color:#d97706;"><i class="fas fa-exclamation-triangle"></i> Komponen Tidak Dapat Dihapus</h3>
              <button type="button" class="modal-close" @click="showBlockedKomponenModal = false"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
              <p style="margin:0 0 0.75rem;color:#334155;font-size:0.875rem;line-height:1.5;">
                Komponen <strong>"{{ komponenToDelete?.nama }}"</strong> sedang digunakan oleh <strong>{{ komponenToDelete?.konfigurasis_count }} konfigurasi tarif</strong>.
              </p>
              <div style="background:#fffbeb;border:1px solid #fef3c7;border-radius:0.5rem;padding:0.75rem;font-size:0.8125rem;color:#b45309;">
                <i class="fas fa-info-circle"></i> Untuk menjaga integritas data tagihan, hapus atau ganti konfigurasi tarif terkait terlebih dahulu sebelum menghapus komponen ini, atau nonaktifkan statusnya.
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn-solid btn-outline" @click="showBlockedKomponenModal = false">Mengerti</button>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.biaya-page-wrapper {
  padding-bottom: 2rem;
}

/* Header */
.biaya-header {
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

.biaya-title {
  font-size: 1.375rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 0.25rem;
  letter-spacing: -0.02em;
}

.biaya-subtitle {
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
.biaya-nav-tabs {
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

.tab-count-badge {
  background: #e2e8f0;
  color: #475569;
  font-size: 0.6875rem;
  font-weight: 800;
  padding: 0.125rem 0.4375rem;
  border-radius: 9999px;
}

.nav-tab-btn.active .tab-count-badge {
  background: #eff6ff;
  color: #2563eb;
}

/* Metrics Grid */
.metrics-grid-4 {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.metrics-grid-3 {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.metric-card-solid {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}

.metric-icon {
  width: 44px;
  height: 44px;
  border-radius: 0.625rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.125rem;
  flex-shrink: 0;
}

.bg-blue { background: #eff6ff; color: #2563eb; }
.bg-green { background: #f0fdf4; color: #16a34a; }
.bg-purple { background: #faf5ff; color: #7c3aed; }
.bg-amber { background: #fffbeb; color: #d97706; }

.metric-info {
  flex: 1;
}

.metric-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 0.125rem;
}

.metric-value {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
}

.metric-value small {
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
}

/* Toolbar Card */
.toolbar-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 0.875rem 1.25rem;
  margin-bottom: 1.5rem;
}

.toolbar-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.toolbar-filters {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-item label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #475569;
  white-space: nowrap;
}

.select-field-36 {
  height: 36px;
  padding: 0 2rem 0 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  color: #0f172a;
  background: #ffffff;
  outline: none;
  cursor: pointer;
}

.select-field-36:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.btn-reset-filter {
  height: 36px;
  padding: 0 0.75rem;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
}

.btn-reset-filter:hover {
  background: #e2e8f0;
  color: #0f172a;
}

/* Search Box */
.clean-search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.clean-search-box .search-icon {
  position: absolute;
  left: 0.75rem;
  color: #94a3b8;
  font-size: 0.8125rem;
}

.clean-search-box .search-input {
  height: 36px;
  width: 260px;
  padding: 0 1.75rem 0 2.25rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  outline: none;
  background: #ffffff;
  transition: all 0.15s;
}

.clean-search-box .search-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-clear-btn {
  position: absolute;
  right: 0.5rem;
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 1.125rem;
  cursor: pointer;
}

/* Solid Table Card */
.solid-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  overflow: hidden;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}

.card-header-bar {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header-bar h3 {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 700;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.count-badge-solid {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 500;
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.solid-data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
  text-align: left;
}

.solid-data-table thead th {
  background: #f8fafc;
  color: #475569;
  font-weight: 700;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.solid-data-table tbody td {
  padding: 0.8125rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.solid-data-table tbody tr:hover {
  background: #f8fafc;
}

/* Badges & Tags */
.angkatan-tag {
  display: inline-block;
  padding: 0.1875rem 0.5rem;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
  font-size: 0.75rem;
  font-weight: 700;
  border-radius: 0.375rem;
}

.komponen-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.1875rem 0.5rem;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #334155;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 0.375rem;
}

.nominal-value {
  font-weight: 800;
  color: #0f172a;
  font-size: 0.875rem;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.1875rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.6875rem;
  font-weight: 700;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.status-active {
  background: #dcfce7;
  color: #15803d;
}
.status-active .status-dot { background: #16a34a; }

.status-inactive {
  background: #fee2e2;
  color: #b91c1c;
}
.status-inactive .status-dot { background: #dc2626; }

.code-badge-solid {
  display: inline-block;
  padding: 0.1875rem 0.5rem;
  background: #0f172a;
  color: #ffffff;
  font-size: 0.6875rem;
  font-weight: 800;
  letter-spacing: 0.5px;
  border-radius: 0.25rem;
}

.count-link-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.625rem;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #2563eb;
  font-size: 0.75rem;
  font-weight: 700;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: all 0.15s;
}

.count-link-badge:hover:not(:disabled) {
  background: #dbeafe;
}

.count-link-badge:disabled {
  background: #f1f5f9;
  border-color: #e2e8f0;
  color: #94a3b8;
  cursor: default;
}

/* Action Buttons */
.action-btn-group {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
}

.btn-act {
  width: 28px;
  height: 28px;
  border-radius: 0.375rem;
  border: 1px solid transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-act-warning {
  background: #fffbeb;
  border-color: #fde68a;
  color: #d97706;
}
.btn-act-warning:hover { background: #fef3c7; }

.btn-act-success {
  background: #f0fdf4;
  border-color: #bbf7d0;
  color: #16a34a;
}
.btn-act-success:hover { background: #dcfce7; }

.btn-act-edit {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #475569;
}
.btn-act-edit:hover { background: #e2e8f0; color: #0f172a; }

.btn-act-delete {
  background: #fee2e2;
  border-color: #fecaca;
  color: #dc2626;
}
.btn-act-delete:hover { background: #fca5a5; color: #991b1b; }

/* Empty state */
.empty-state-cell {
  text-align: center;
  padding: 3rem 1rem !important;
  color: #64748b;
}

.empty-icon {
  font-size: 2.25rem;
  color: #cbd5e1;
  margin-bottom: 0.5rem;
  display: block;
}

/* Buttons Solid */
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
  height: 36px;
}

.btn-solid.btn-primary {
  background: #2563eb;
  color: #ffffff;
  border-color: #2563eb;
}
.btn-solid.btn-primary:hover:not(:disabled) {
  background: #1d4ed8;
  border-color: #1d4ed8;
}

.btn-solid.btn-outline {
  background: #ffffff;
  border-color: #cbd5e1;
  color: #334155;
}
.btn-solid.btn-outline:hover:not(:disabled) {
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

/* Modals */
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
  max-width: 480px;
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
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 0.5rem;
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

.form-group-modal {
  margin-bottom: 1rem;
}

.form-group-modal label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.375rem;
}

.form-group-modal .req {
  color: #dc2626;
}

.grid-modal-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.input-modal-select,
.input-modal-text {
  width: 100%;
  height: 38px;
  padding: 0 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  color: #0f172a;
  background: #ffffff;
  outline: none;
}

.input-modal-select:focus,
.input-modal-text:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.input-modal-textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  color: #0f172a;
  background: #ffffff;
  outline: none;
}

.input-modal-textarea:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.currency-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.curr-prefix {
  position: absolute;
  left: 0.75rem;
  font-weight: 700;
  color: #64748b;
  font-size: 0.875rem;
}

.input-modal-currency {
  width: 100%;
  height: 40px;
  padding: 0 0.75rem 0 2.25rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
  background: #ffffff;
  outline: none;
}

.input-modal-currency:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.curr-helper {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.25rem;
}

.curr-helper strong {
  color: #16a34a;
}

.copy-guide-card {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 0.5rem;
  padding: 0.875rem 1rem;
  display: flex;
  gap: 0.75rem;
  font-size: 0.8125rem;
  color: #1e40af;
  line-height: 1.45;
}

.guide-bulb {
  font-size: 1.25rem;
  color: #2563eb;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.delete-item-preview {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.75rem;
  font-size: 0.8125rem;
  color: #334155;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

/* Responsive */
@media (max-width: 1024px) {
  .metrics-grid-4 {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .biaya-header {
    flex-direction: column;
  }
  .header-actions {
    width: 100%;
  }
  .header-actions button {
    flex: 1;
  }
  .metrics-grid-4,
  .metrics-grid-3 {
    grid-template-columns: 1fr;
  }
  .toolbar-row {
    flex-direction: column;
    align-items: stretch;
  }
  .clean-search-box .search-input {
    width: 100%;
  }
  .grid-modal-2 {
    grid-template-columns: 1fr;
  }
}
</style>
