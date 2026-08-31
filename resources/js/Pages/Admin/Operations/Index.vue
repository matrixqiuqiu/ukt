<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useToast } from '@/composables/useToast';

const { success, error: toastError } = useToast();

const props = defineProps({
  config: Object,
  endpointMeta: Array,
  apiLogs: {
    type: Array,
    default: () => [],
  },
  vaTransactions: {
    type: Object,
    default: () => ({ pendaftaran: [], daftar_ulang: [], all: [] }),
  },
});

const activeMainTab = ref('koneksi');
const testingToken = ref(false);
const tokenResult = ref(null);
const currentToken = ref(null);
const endpointTests = ref({});
const testingEndpoint = ref(null);

// Auto-fetch transaksi history when history tab is opened
watch(activeMainTab, (val) => {
  if (val === 'history') {
    fetchTransaksiHistory();
  }
  if (val === 'monitoring') {
    fetchMonitoring();
  }
});

// Transaction history state
const vaTransactions = ref(props.vaTransactions);
const historySubTab = ref('ukt');
const historySearch = ref('');
const historyFilterProdi = ref('');
const loadingHistory = ref(false);
const localApiLogs = ref([...props.apiLogs]);

// Monitoring state
const monitoring = ref({ stats: { total_24: 0, success_24: 0, failed_24: 0, error_24: 0 }, r_code_breakdown: [], recent_logs: [] });
const loadingMonitoring = ref(false);

// Transaction detail modal
const showDetailModal = ref(false);
const detailData = ref(null);
const loadingDetail = ref(false);

async function fetchMonitoring() {
  loadingMonitoring.value = true;
  try {
    const resp = await axios.get(route('admin.operations.monitoring'));
    monitoring.value = resp.data;
  } catch (e) {
    toastError('Gagal memuat data monitoring');
  } finally {
    loadingMonitoring.value = false;
  }
}

async function openTransactionDetail(logId) {
  loadingDetail.value = true;
  showDetailModal.value = true;
  detailData.value = null;
  try {
    const resp = await axios.get(route('admin.operations.transaction-detail', logId));
    detailData.value = resp.data;
  } catch (e) {
    toastError('Gagal memuat detail transaksi');
    showDetailModal.value = false;
  } finally {
    loadingDetail.value = false;
  }
}

async function fetchTransaksiHistory() {
  loadingHistory.value = true;
  try {
    const resp = await axios.get(route('admin.operations.transaksi-history'));
    vaTransactions.value = resp.data;
  } catch (e) {
    toastError('Gagal memuat transaksi history');
  } finally {
    loadingHistory.value = false;
  }
}

const filteredTransactions = computed(() => {
  const list = historySubTab.value === 'ukt'
    ? vaTransactions.value.pendaftaran
    : vaTransactions.value.daftar_ulang;

  return list.filter(t => {
    const q = historySearch.value.toLowerCase();
    const matchSearch = !q ||
      t.calon_mahasiswa.nama.toLowerCase().includes(q) ||
      t.calon_mahasiswa.nim.toLowerCase().includes(q) ||
      (t.va.full && t.va.full.toLowerCase().includes(q)) ||
      String(t.nomor).includes(q);
    const matchProdi = !historyFilterProdi.value ||
      t.calon_mahasiswa.jurusan === historyFilterProdi.value;
    return matchSearch && matchProdi;
  });
});

const prodiList = computed(() => {
  const set = new Set(vaTransactions.value.all.map(t => t.calon_mahasiswa.jurusan).filter(Boolean));
  return [...set].sort();
});

// Editable request bodies per endpoint
const editableRequests = ref({});
const editErrors = ref({});

function initEditableRequests() {
  const defaults = {};
  props.endpointMeta.forEach(ep => {
    defaults[ep.key] = JSON.stringify(buildEndpointParams(ep.key), null, 2);
  });
  editableRequests.value = defaults;
}
initEditableRequests();

function getEditableParams(key) {
  const raw = editableRequests.value[key] || '{}';
  try {
    editErrors.value[key] = null;
    return JSON.parse(raw);
  } catch (e) {
    editErrors.value[key] = e.message;
    return null;
  }
}

function onEditInput(key, event) {
  editableRequests.value[key] = event.target.value;
  // Validate on input
  try {
    JSON.parse(event.target.value);
    editErrors.value[key] = null;
  } catch (e) {
    editErrors.value[key] = e.message;
  }
}

function formatDuration(ms) {
  if (!ms && ms !== 0) return '-';
  return ms + ' ms';
}

function formatJson(data) {
  if (!data) return '{}';
  if (typeof data === 'string') {
    try { return JSON.stringify(JSON.parse(data), null, 2); } catch { return data; }
  }
  return JSON.stringify(data, null, 2);
}

function rcodeLabel(rcode) {
  if (rcode === '000') return 'Success';
  if (rcode === null || rcode === undefined) return '-';
  return rcode;
}

async function clearHistoryLogs() {
  if (!confirm('Hapus semua riwayat API logs?')) return;
  try {
    await axios.post(route('admin.operations.clear-api-logs'));
    localApiLogs.value = [];
    success('API logs berhasil dihapus');
  } catch (e) {
    toastError('Gagal menghapus API logs');
  }
}

function testToken() {
  testingToken.value = true;
  tokenResult.value = null;
  currentToken.value = null;

  const startTime = Date.now();

  axios.post(route('admin.operations.test-token'))
    .then((response) => {
      tokenResult.value = response.data;
      const duration = Date.now() - startTime;

      // Also store in endpointTests so the table row shows results
      endpointTests.value['token'] = {
        success: response.data?.success,
        status: response.data?.status,
        rcode: response.data?.rcode,
        message: response.data?.message,
        data: response.data?.data,
        duration_ms: response.data?.duration_ms || duration,
        request: getEditableParams('token'),
      };

      if (response.data?.success) {
        currentToken.value = response.data?.data?.data?.token || null;
        success('Token berhasil didapatkan');
      } else {
        toastError(response.data?.message || 'Gagal mengambil token');
      }
    })
    .catch(() => {
      toastError('Gagal menguji koneksi');
    })
    .finally(() => {
      testingToken.value = false;
    });
}

async function testSingleEndpoint(ep) {
  // Token endpoint uses dedicated testToken()
  if (ep.key === 'token') {
    testToken();
    return;
  }

  const params = getEditableParams(ep.key);
  if (!params) {
    toastError(`${ep.name}: JSON tidak valid`);
    return;
  }

  testingEndpoint.value = ep.key;

  // Always fetch fresh token before sending request
  try {
    const tokenResp = await axios.post(route('admin.operations.test-token'));
    if (!tokenResp.data?.success) {
      toastError('Gagal mengambil token: ' + (tokenResp.data?.message || 'Unknown'));
      testingEndpoint.value = null;
      return;
    }
    const freshToken = tokenResp.data?.data?.data?.token || null;

    // If testing 'flag' endpoint, automatically do 'testbayar' first
    // to ensure payment exists before flagging
    if (ep.key === 'flag') {
      const testbayarParams = getEditableParams('testbayar');
      if (testbayarParams) {
        try {
          await axios.post(route('admin.operations.test-endpoint'), {
            endpoint: 'testbayar',
            token: freshToken,
            params: testbayarParams,
          });
        } catch (e) {
          // Ignore testbayar error, continue to flag
        }
      }
    }

    const response = await axios.post(route('admin.operations.test-endpoint'), {
      endpoint: ep.key,
      token: freshToken,
      params: params,
    });

    endpointTests.value[ep.key] = response.data;
    if (response.data?.success) {
      success(`${ep.name} berhasil`);
    } else {
      toastError(`${ep.name}: ${response.data?.message || 'Gagal'}`);
    }
  } catch (e) {
    toastError(`${ep.name}: Gagal mengirim request`);
  } finally {
    testingEndpoint.value = null;
  }
}

function buildEndpointParams(key) {
  const idMitra = props.config.id_mitra;
  const idProduk = props.config.id_produk;
  const now = new Date();
  const dateStr = now.toISOString().split('T')[0];
  const expiryMs = (props.config.default_expired_days || 0) * 86400000
    + (props.config.default_expired_hours || 0) * 3600000
    + (props.config.default_expired_minutes || 5) * 60000;
  const expiry = new Date(now.getTime() + expiryMs);
  const expiryStr = expiry.toISOString().replace('T', ' ').substring(0, 19);
  const sampleVa = '25080110013';

  switch (key) {
    case 'va':
      return {
        va: sampleVa, id_mitra: idMitra, id_produk: idProduk,
        name: 'DEV TEST NTBVA ' + now.getFullYear(),
        billing_type: props.config.default_billing_type || 'c',
        email: 'dev-ntbva@example.com',
        phone: '081234567890',
        datetime_expired: expiryStr,
        description: 'Pembayaran UKT via NTB VA',
        tagihan: '150000',
      };
    case 'inqva':
      return { va: sampleVa, id_mitra: idMitra, id_produk: idProduk };
    case 'cekstatus':
      return { va: sampleVa, datetime_payment: dateStr, id_mitra: idMitra, id_produk: idProduk };
    case 'flag':
      return { va: sampleVa };
    case 'updateva':
      return {
        va: sampleVa, id_mitra: idMitra, id_produk: idProduk,
        name: 'DEV TEST NTBVA UPDATE',
        billing_type: props.config.default_billing_type || 'c',
        email: 'dev-ntbva@example.com',
        phone: '081234567890',
        datetime_expired: expiryStr,
        description: 'Update Pembayaran UKT via NTB VA',
        tagihan: '150000',
      };
    case 'testbayar':
      return { va: sampleVa, amount: '10000' };
    case 'token':
      return {
        user_id: props.config.user_id || 'bumigora',
        user_secret: props.config.user_secret || '',
        id_mitra: props.config.id_mitra || '031',
      };
  }
}
</script>

<template>
  <Head title="Payment Gateway" />

  <AuthenticatedLayout>
    <template #header>
      <div class="d-flex justify-content-between align-items-center w-100">
        <div>
          <h2 class="page-title mb-0">
            <i class="fas fa-credit-card icon-inline text-primary"></i>
            Payment Gateway
          </h2>
        </div>
        <button class="btn btn-outline-primary btn-sm" @click="testToken" :disabled="testingToken">
          <i class="fas fa-sync-alt icon-inline" :class="{ 'fa-spin': testingToken }"></i>
          Refresh Data
        </button>
      </div>
    </template>

    <!-- Provider Badge -->
    <div class="mb-3">
      <span class="badge badge-primary px-3 py-2" style="font-size: 0.85rem; border-radius: 0.5rem;">
        <i class="fas fa-university icon-inline"></i> NTBVA
      </span>
    </div>

    <!-- Main Tabs -->
    <div class="pg-main-tabs">
      <button type="button" class="pg-tab-btn" :class="{ 'is-active': activeMainTab === 'koneksi' }" @click="activeMainTab = 'koneksi'">
        <i class="fas fa-plug icon-inline"></i>Status Koneksi
      </button>
      <button type="button" class="pg-tab-btn" :class="{ 'is-active': activeMainTab === 'monitoring' }" @click="activeMainTab = 'monitoring'">
        <i class="fas fa-chart-line icon-inline"></i>Monitoring Transaksi
      </button>
      <button type="button" class="pg-tab-btn" :class="{ 'is-active': activeMainTab === 'retry' }" @click="activeMainTab = 'retry'">
        <i class="fas fa-redo icon-inline"></i>Aksi Retry
      </button>
      <button type="button" class="pg-tab-btn" :class="{ 'is-active': activeMainTab === 'history' }" @click="activeMainTab = 'history'">
        <i class="fas fa-history icon-inline"></i>Transaksi History
      </button>
    </div>

    <!-- ========== Tab: Status Koneksi ========== -->
    <div v-show="activeMainTab === 'koneksi'" class="pg-page-stack">
      <div class="row g-3">
        <!-- Status Integrasi -->
        <div class="col-lg-6 mb-3">
          <div class="pg-card">
            <div class="pg-card-header">
              <h3 class="pg-card-title">
                <i class="fas fa-signal icon-inline text-primary"></i>
                Status Integrasi NTBVA
              </h3>
              <button class="pg-btn pg-btn-outline-primary pg-btn-sm" :disabled="testingToken" @click="testToken">
                <i class="fas fa-plug icon-inline" :class="{ 'fa-spin': testingToken }"></i>
                Test Token NTBVA
              </button>
            </div>
            <div class="pg-card-body">
              <template v-if="tokenResult">
                <div class="mb-3">
                  <span class="pg-badge" :class="tokenResult.success ? 'pg-badge-success' : 'pg-badge-danger'">
                    {{ tokenResult.success ? 'SUCCESS' : 'FAILED' }}
                  </span>
                </div>
                <p class="text-muted mb-2">{{ tokenResult.message || 'Permintaan NTBVA berhasil diproses.' }}</p>
                <div class="pg-info-box">
                  <strong>Scope Test:</strong> TOKEN ONLY
                </div>
                <div v-if="tokenResult.duration_ms" class="text-muted mt-2" style="font-size: 0.8rem;">
                  Durasi: {{ formatDuration(tokenResult.duration_ms) }}
                </div>
              </template>
              <template v-else>
                <div class="pg-empty-state">
                  <i class="fas fa-plug"></i>
                  <p>Belum ada pengujian. Klik tombol di atas untuk mulai.</p>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- Konfigurasi ENV -->
        <div class="col-lg-6 mb-3">
          <div class="pg-card">
            <div class="pg-card-header">
              <h3 class="pg-card-title">
                <i class="fas fa-cog icon-inline text-success"></i>
                Konfigurasi ENV NTBVA
              </h3>
            </div>
            <div class="pg-card-body">
              <div class="pg-stats-grid">
                <div class="pg-stat-card">
                  <div class="pg-stat-label">PROVIDER</div>
                  <div class="pg-stat-value">{{ config.nama_mitra || 'ntbva' }}</div>
                </div>
                <div class="pg-stat-card">
                  <div class="pg-stat-label">MODE</div>
                  <div class="pg-stat-value">
                    <span class="pg-badge" :class="config.production ? 'pg-badge-danger' : 'pg-badge-warning'">
                      {{ config.production ? 'PRODUCTION' : 'DEVELOPMENT' }}
                    </span>
                  </div>
                </div>
                <div class="pg-stat-card">
                  <div class="pg-stat-label">ENDPOINT READY</div>
                  <div class="pg-stat-value text-success">{{ endpointMeta.length }}/{{ endpointMeta.length }}</div>
                </div>
              </div>

              <div class="pg-table-wrap">
                <table class="pg-table">
                  <thead>
                    <tr>
                      <th>ENDPOINT</th>
                      <th>URL</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="ep in endpointMeta" :key="ep.key">
                      <td>{{ ep.name }}</td>
                      <td class="pg-table-url">{{ config.url_base }}{{ ep.path }}</td>
                    </tr>
                    <tr>
                      <td>callback</td>
                      <td class="text-muted">-</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="mt-3">
                <span class="pg-badge pg-badge-success">
                  <i class="fas fa-check-circle icon-inline"></i> Semua Endpoint Ready
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Testing Endpoint Table -->
      <div class="pg-card">
        <div class="pg-card-header">
          <h3 class="pg-card-title">
            <i class="fas fa-flask icon-inline text-primary"></i>
            Testing Endpoint NTBVA ({{ config.production ? 'Production' : 'Development' }})
          </h3>
        </div>
        <div class="pg-card-body p-0">
          <div class="pg-table-wrap">
            <table class="pg-table pg-table-full">
              <thead>
                <tr>
                  <th style="min-width: 220px;">ENDPOINT</th>
                  <th style="width: 120px;">STATUS</th>
                  <th style="width: 130px;">HTTP/RCODE</th>
                  <th style="min-width: 320px;">REQUEST</th>
                  <th style="min-width: 320px;">RESPONSE</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="ep in endpointMeta" :key="ep.key">
                  <td>
                    <div class="pg-ep-name">{{ ep.name }}</div>
                    <div class="pg-ep-desc">{{ ep.description }}</div>
                    <div class="pg-ep-url">{{ config.url_base }}{{ ep.path }}</div>
                    <div v-if="ep.key !== 'token'" class="pg-ep-ref">
                      VA Inquiry/Status Used: {{ endpointTests.va?.data?.data?.va || '25080110013' }}
                    </div>
                  </td>
                  <td>
                    <template v-if="endpointTests[ep.key]">
                      <span class="pg-badge" :class="endpointTests[ep.key].success ? 'pg-badge-success' : 'pg-badge-danger'">
                        {{ endpointTests[ep.key].success ? 'success' : 'failed' }}
                      </span>
                      <div class="pg-ep-msg">{{ endpointTests[ep.key].message }}</div>
                      <div class="pg-ep-time">
                        {{ new Date().toLocaleDateString('id-ID') }}, {{ new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}
                      </div>
                    </template>
                    <template v-else>
                      <span class="pg-badge pg-badge-secondary">unknown</span>
                      <div class="pg-ep-msg">Belum pernah dites.</div>
                    </template>
                  </td>
                  <td>
                    <template v-if="endpointTests[ep.key]">
                      <div>HTTP: {{ endpointTests[ep.key].status }}</div>
                      <div>rCode: {{ endpointTests[ep.key].rcode || '-' }}</div>
                      <div class="text-success">{{ rcodeLabel(endpointTests[ep.key].rcode) }}</div>
                      <div class="text-muted" style="font-size: 0.75rem;">Durasi: {{ formatDuration(endpointTests[ep.key].duration_ms) }}</div>
                    </template>
                    <template v-else>
                      <div>HTTP: -</div>
                      <div>rCode: -</div>
                      <div class="text-muted" style="font-size: 0.75rem;">Durasi: 0 ms</div>
                    </template>
                  </td>
                  <td>
                    <textarea
                      class="pg-textarea"
                      :value="editableRequests[ep.key]"
                      @input="onEditInput(ep.key, $event)"
                      rows="10"
                      spellcheck="false"
                      :class="{ 'pg-textarea-error': editErrors[ep.key] }"
                    ></textarea>
                    <div v-if="editErrors[ep.key]" class="pg-edit-error">
                      <i class="fas fa-exclamation-triangle"></i> JSON tidak valid
                    </div>
                    <button
                      class="pg-btn pg-btn-outline-primary pg-btn-sm mt-2"
                      :disabled="testingEndpoint === ep.key || !!editErrors[ep.key]"
                      @click="testSingleEndpoint(ep)"
                    >
                      <i class="fas fa-play icon-inline"></i>
                      {{ testingEndpoint === ep.key ? 'Testing...' : 'Test' }}
                    </button>
                  </td>
                  <td>
                    <pre class="pg-pre">{{ JSON.stringify(endpointTests[ep.key]?.data || [], null, 2) }}</pre>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ========== Tab: Monitoring Transaksi ========== -->
    <div v-show="activeMainTab === 'monitoring'" class="pg-page-stack">
      <!-- Stats Cards -->
      <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
          <div class="pg-card pg-stat-card-mini">
            <div class="pg-stat-card-body">
              <div class="pg-stat-label">Total 24 Jam</div>
              <div class="pg-stat-value text-primary">{{ monitoring.stats.total_24 }}</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6">
          <div class="pg-card pg-stat-card-mini">
            <div class="pg-stat-card-body">
              <div class="pg-stat-label">Success 24 Jam</div>
              <div class="pg-stat-value text-success">{{ monitoring.stats.success_24 }}</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6">
          <div class="pg-card pg-stat-card-mini">
            <div class="pg-stat-card-body">
              <div class="pg-stat-label">Failed 24 Jam</div>
              <div class="pg-stat-value text-warning">{{ monitoring.stats.failed_24 }}</div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6">
          <div class="pg-card pg-stat-card-mini">
            <div class="pg-stat-card-body">
              <div class="pg-stat-label">Error 24 Jam</div>
              <div class="pg-stat-value text-danger">{{ monitoring.stats.error_24 }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- Breakdown rCode -->
        <div class="col-lg-5">
          <div class="pg-card h-100">
            <div class="pg-card-header">
              <h3 class="pg-card-title">
                <i class="fas fa-list-ol icon-inline text-info"></i>
                Breakdown rCode (24 Jam)
              </h3>
            </div>
            <div class="pg-card-body p-0">
              <div v-if="monitoring.r_code_breakdown.length === 0" class="pg-empty-state">
                <i class="fas fa-inbox"></i>
                <p>Belum ada data.</p>
              </div>
              <div v-else class="pg-table-wrap">
                <table class="pg-table pg-table-full">
                  <thead>
                    <tr>
                      <th>RCODE</th>
                      <th>DESCRIPTION</th>
                      <th class="text-end">TOTAL</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, idx) in monitoring.r_code_breakdown" :key="idx">
                      <td><span class="font-mono fw-bold">{{ item.rcode }}</span></td>
                      <td>{{ item.description }}</td>
                      <td class="text-end fw-bold">{{ item.total }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Transaksi Terbaru -->
        <div class="col-lg-7">
          <div class="pg-card h-100">
            <div class="pg-card-header d-flex justify-content-between align-items-center">
              <h3 class="pg-card-title mb-0">
                <i class="fas fa-clock icon-inline text-primary"></i>
                Transaksi Terbaru NTBVA
              </h3>
              <div class="d-flex gap-2 align-items-center">
                <select class="form-select form-select-sm" style="width: auto;">
                  <option>Hapus Harian</option>
                </select>
                <button class="btn btn-sm btn-outline-danger" @click="clearHistoryLogs">
                  <i class="fas fa-trash"></i> Clear
                </button>
              </div>
            </div>
            <div class="pg-card-body p-0">
              <div v-if="monitoring.recent_logs.length === 0" class="pg-empty-state">
                <i class="fas fa-inbox"></i>
                <p>Belum ada transaksi.</p>
              </div>
              <div v-else class="pg-table-wrap">
                <table class="pg-table pg-table-full">
                  <thead>
                    <tr>
                      <th>WAKTU</th>
                      <th>OPERASI</th>
                      <th>STATUS</th>
                      <th>RCODE</th>
                      <th>HTTP</th>
                      <th>DURASI</th>
                      <th>MESSAGE</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="log in monitoring.recent_logs" :key="log.id" style="cursor: pointer;"
                      @click="openTransactionDetail(log.id)">
                      <td style="white-space: nowrap; font-size: 0.75rem;">{{ log.waktu }}</td>
                      <td><span class="pg-badge pg-badge-primary">{{ log.endpoint }}</span></td>
                      <td>
                        <span class="pg-badge" :class="log.success ? 'pg-badge-success' : 'pg-badge-danger'">
                          {{ log.success ? 'Success' : 'Failed' }}
                        </span>
                      </td>
                      <td class="font-mono">{{ log.rcode || '-' }}</td>
                      <td>{{ log.status_code || '-' }}</td>
                      <td>{{ formatDuration(log.duration_ms) }}</td>
                      <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{ log.message || '-' }}
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

    <!-- ========== Tab: Aksi Retry ========== -->
    <div v-show="activeMainTab === 'retry'" class="pg-page-stack">
      <div class="pg-card">
        <div class="pg-card-header">
          <h3 class="pg-card-title">
            <i class="fas fa-redo icon-inline text-warning"></i>
            Aksi Retry
          </h3>
        </div>
        <div class="pg-card-body">
          <div class="pg-empty-state">
            <i class="fas fa-redo"></i>
            <p>Fitur retry akan tersedia untuk transaksi yang gagal atau pending.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ========== Tab: Transaksi History ========== -->
    <div v-show="activeMainTab === 'history'" class="pg-page-stack">
      <div class="pg-card">
        <div class="pg-card-header d-flex justify-content-between align-items-center">
          <h3 class="pg-card-title mb-0">
            <i class="fas fa-history icon-inline text-secondary"></i>
            Transaksi History NTBVA
          </h3>
          <button class="btn btn-sm btn-outline-danger" @click="clearHistoryLogs">
            <i class="fas fa-trash"></i> Clear
          </button>
        </div>
        <div class="pg-card-body p-0">
          <div class="d-flex" style="min-height: 400px;">
            <!-- Vertical Tabs -->
            <div class="pg-vertical-tabs" style="min-width: 200px; border-right: 1px solid #e3e6f0;">
              <button class="pg-vtab-btn" :class="{ active: historySubTab === 'ukt' }"
                @click="historySubTab = 'ukt'">
                <i class="fas fa-graduation-cap me-2"></i>
                UKT
                <span class="badge bg-primary ms-auto">{{ vaTransactions.pendaftaran.length }}</span>
              </button>
              <button class="pg-vtab-btn" :class="{ active: historySubTab === 'daftar_ulang' }"
                @click="historySubTab = 'daftar_ulang'">
                <i class="fas fa-redo me-2"></i>
                Daftar Ulang
                <span class="badge bg-primary ms-auto">{{ vaTransactions.daftar_ulang.length }}</span>
              </button>
            </div>

            <!-- Tab Content -->
            <div class="flex-grow-1">
              <!-- Search & Filter -->
              <div class="px-3 pt-3 pb-2 d-flex gap-3 align-items-center flex-wrap border-bottom">
                <div class="flex-grow-1" style="min-width: 250px;">
                  <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" placeholder="Cari nama, nomor, VA, referensi..."
                      v-model="historySearch">
                  </div>
                </div>
                <div style="min-width: 180px;">
                  <select class="form-select form-select-sm" v-model="historyFilterProdi">
                    <option value="">Semua Prodi</option>
                    <option v-for="prodi in prodiList" :key="prodi" :value="prodi">{{ prodi }}</option>
                  </select>
                </div>
                <button class="btn btn-sm btn-outline-secondary" @click="fetchTransaksiHistory">
                  <i class="fas fa-sync-alt"></i>
                </button>
              </div>

              <!-- Table -->
              <div class="pg-table-wrap" v-if="filteredTransactions.length > 0">
                <table class="pg-table pg-table-full">
                  <thead>
                    <tr>
                      <th>WAKTU</th>
                      <th>CALON MAHASISWA</th>
                      <th>NOMOR</th>
                      <th>VA</th>
                      <th>TAGIHAN</th>
                      <th>STATUS</th>
                      <th>BAYAR</th>
                      <th>INVOICE</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="t in filteredTransactions" :key="t.id" style="cursor: pointer;"
                      @click="openTransactionDetail(t.id)">
                      <td style="white-space: nowrap; font-size: 0.75rem;">
                        <div>{{ t.waktu }}</div>
                        <div v-if="t.waktu_expired" class="text-muted">Expired: {{ t.waktu_expired }}</div>
                      </td>
                      <td>
                        <div class="fw-bold">{{ t.calon_mahasiswa.nama }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">{{ t.calon_mahasiswa.nim }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">{{ t.calon_mahasiswa.jurusan }}</div>
                      </td>
                      <td style="font-family: monospace; font-size: 0.8rem;">{{ t.nomor }}</td>
                      <td>
                        <div style="font-family: monospace; font-size: 0.8rem;">{{ t.va.full }}</div>
                        <div class="text-muted" style="font-size: 0.7rem;">Suffix: {{ t.va.suffix }}</div>
                      </td>
                      <td>Rp {{ Number(t.tagihan.nominal).toLocaleString('id-ID') }}</td>
                      <td>
                        <span class="pg-badge" :class="{
                          'pg-badge-success': t.status === 'dikonfirmasi',
                          'pg-badge-warning': t.status === 'pending',
                          'pg-badge-danger': t.status === 'ditolak'
                        }">
                          {{ t.status === 'dikonfirmasi' ? 'Lunas' : t.status === 'pending' ? 'Pending' : 'Ditolak' }}
                        </span>
                      </td>
                      <td>{{ t.bayar ? 'Rp ' + Number(t.bayar).toLocaleString('id-ID') : '-' }}</td>
                      <td>
                        <span v-if="t.status === 'dikonfirmasi'" class="text-success">
                          <i class="fas fa-check-circle"></i> Lunas
                        </span>
                        <span v-else class="text-warning">
                          <i class="fas fa-clock"></i> Menunggu
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-else class="pg-empty-state">
                <i class="fas fa-receipt"></i>
                <p>Belum ada transaksi {{ historySubTab === 'ukt' ? 'UKT' : 'daftar ulang' }}.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========== Transaction Detail Modal (Teleported to body) ========== -->
    <Teleport to="body">
      <template v-if="showDetailModal">
        <div class="pg-modal-backdrop" @click="showDetailModal = false"></div>
        <div class="pg-modal-overlay">
          <div class="pg-modal-container">
            <!-- Header -->
            <div class="pg-modal-header">
              <div class="d-flex align-items-center gap-2">
                <code style="font-size: 0.9rem; color: #6777ef; font-weight: 600;">&lt;/&gt;</code>
                <span style="font-size: 0.95rem; font-weight: 700; color: #1f2937;">Detail Transaksi NTBVA</span>
                <span v-if="detailData" class="badge" style="font-size: 0.7rem; padding: 3px 10px; border-radius: 4px;"
                  :class="detailData.success ? 'bg-success' : 'bg-warning text-dark'">
                  {{ detailData.success ? 'SUCCESS' : 'PENDING' }}
                </span>
              </div>
              <button type="button" class="pg-modal-close" @click="showDetailModal = false">&times;</button>
            </div>

            <!-- Body -->
            <div class="pg-modal-body" v-if="detailData">
                <div class="d-flex align-items-center gap-3 mb-2 flex-wrap" style="font-size: 0.8rem; color: #6b7280;">
                <span><i class="far fa-clock"></i> {{ detailData.created_at }}</span>
                <span><i class="fas fa-cog"></i> {{ detailData.endpoint }}</span>
                <span>HTTP: <strong style="color: #1f2937;">{{ detailData.status_code }}</strong></span>
                <span>rCode: <strong style="color: #1f2937;">{{ detailData.rcode || '-' }}</strong></span>
                <span>Durasi: <strong style="color: #1f2937;">{{ formatDuration(detailData.duration_ms) }}</strong></span>
              </div>

              <div class="mb-3 py-2 px-3 rounded" style="background: #f0fdf4; border-left: 3px solid #22c55e; font-size: 0.85rem; color: #15803d;">
                <i class="fas fa-check-circle me-1"></i>
                {{ detailData.message || 'Permintaan NTBVA berhasil diproses.' }}
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <div class="border rounded" style="border-color: #e5e7eb;">
                    <div class="px-3 py-2" style="background: #f8fafc; border-bottom: 1px solid #e5e7eb;">
                      <h6 class="mb-0" style="font-size: 0.8rem; font-weight: 700; color: #1f2937;">
                        <i class="fas fa-arrow-down text-danger me-1"></i> RESPONSE PAYLOAD
                      </h6>
                    </div>
                    <div class="p-3">
                      <pre class="mb-0" style="font-size: 0.75rem; max-height: 320px; overflow: auto; font-family: 'SF Mono', 'Fira Code', Consolas, monospace; line-height: 1.6; color: #374151;">{{ formatJson(detailData.response_data) }}</pre>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="border rounded" style="border-color: #e5e7eb;">
                    <div class="px-3 py-2" style="background: #f8fafc; border-bottom: 1px solid #e5e7eb;">
                      <h6 class="mb-0" style="font-size: 0.8rem; font-weight: 700; color: #1f2937;">
                        <i class="fas fa-arrow-up text-primary me-1"></i> REQUEST PAYLOAD
                      </h6>
                    </div>
                    <div class="p-3">
                      <pre class="mb-0" style="font-size: 0.75rem; max-height: 320px; overflow: auto; font-family: 'SF Mono', 'Fira Code', Consolas, monospace; line-height: 1.6; color: #374151;">{{ formatJson(detailData.request_data) }}</pre>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Loading -->
            <div class="pg-modal-body text-center" v-else>
              <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"></div>
              <p class="mt-2 text-muted" style="font-size: 0.85rem;">Memuat detail transaksi...</p>
            </div>

            <!-- Footer -->
            <div class="pg-modal-footer">
              <button type="button" class="btn btn-sm"
                style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-weight: 600;"
                @click="showDetailModal = false">
                <i class="fas fa-times me-1"></i> Tutup
              </button>
            </div>
          </div>
        </div>
      </template>
    </Teleport>
  </AuthenticatedLayout>
</template>

<style scoped>
/* =============================================
   PAYMENT GATEWAY - CLEAN UI/UX
   ============================================= */

/* --- Main Tabs (pill-style) --- */
.pg-main-tabs {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}
.pg-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 1rem;
  border: 2px solid #e3e6f0;
  border-radius: 2rem;
  background: white;
  color: #6c757d;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}
.pg-tab-btn:hover {
  border-color: #6777ef;
  color: #6777ef;
  background: rgba(103, 119, 239, 0.04);
}
.pg-tab-btn.is-active {
  background: #6777ef;
  color: white;
  border-color: #6777ef;
  box-shadow: 0 2px 8px rgba(103, 119, 239, 0.3);
}
.pg-tab-btn .icon-inline { font-size: 0.8rem; }

/* --- Page Stack --- */
.pg-page-stack {
  animation: pgFadeIn 0.2s ease;
}
@keyframes pgFadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}

/* --- Card --- */
.pg-card {
  background: white;
  border: 1px solid #e3e6f0;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  margin-bottom: 1rem;
}
.pg-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #f0f0f0;
  gap: 0.75rem;
}
.pg-card-title {
  font-size: 0.9375rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.pg-card-body {
  padding: 1.25rem;
}

/* --- Stats Grid --- */
.pg-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-bottom: 1rem;
}
.pg-stat-card {
  background: #f8f9fc;
  border: 1px solid #e9ecf2;
  border-radius: 0.625rem;
  padding: 0.875rem;
  text-align: center;
}
.pg-stat-label {
  font-size: 0.6875rem;
  font-weight: 700;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 0.375rem;
}
.pg-stat-value {
  font-size: 1rem;
  font-weight: 700;
  color: #1f2937;
}

/* --- Badge --- */
.pg-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.625rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  line-height: 1.4;
}
.pg-badge-success { background: #d1fae5; color: #065f46; }
.pg-badge-danger { background: #fee2e2; color: #991b1b; }
.pg-badge-warning { background: #fef3c7; color: #92400e; }
.pg-badge-secondary { background: #e5e7eb; color: #374151; }
.pg-badge-primary { background: #ede9fe; color: #4c1d95; }

/* --- Info Box --- */
.pg-info-box {
  background: #f8f9fc;
  border: 1px solid #e9ecf2;
  border-radius: 0.5rem;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: #374151;
}

/* --- Empty State --- */
.pg-empty-state {
  text-align: center;
  padding: 2.5rem 1rem;
  color: #9ca3af;
}
.pg-empty-state i {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
  opacity: 0.3;
  display: block;
}
.pg-empty-state p {
  margin: 0;
  font-size: 0.875rem;
}

/* --- Button --- */
.pg-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
  border: 2px solid transparent;
  white-space: nowrap;
}
.pg-btn-sm { padding: 0.375rem 0.75rem; font-size: 0.75rem; }
.pg-btn-outline-primary {
  background: white;
  color: #6777ef;
  border-color: #6777ef;
}
.pg-btn-outline-primary:hover:not(:disabled) {
  background: #6777ef;
  color: white;
}
.pg-btn:disabled { opacity: 0.55; cursor: not-allowed; }

/* --- Table --- */
.pg-table-wrap {
  border: 1px solid #e9ecf2;
  border-radius: 0.5rem;
  overflow: hidden;
}
.pg-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}
.pg-table thead th {
  background: #f8f9fc;
  padding: 0.625rem 0.875rem;
  font-size: 0.6875rem;
  font-weight: 700;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  border-bottom: 1px solid #e9ecf2;
  margin: 0;
}
.pg-table tbody td {
  padding: 0.625rem 0.875rem;
  border-bottom: 1px solid #f3f4f6;
  color: #374151;
  vertical-align: top;
}
.pg-table tbody tr:last-child td { border-bottom: none; }
.pg-table tbody tr:hover { background: #fafbfc; }
.pg-table-url {
  font-size: 0.75rem;
  word-break: break-all;
  color: #6b7280;
}
.pg-table-full thead th {
  font-size: 0.6875rem;
}

/* --- Endpoint row --- */
.pg-ep-name {
  font-weight: 700;
  color: #6777ef;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}
.pg-ep-desc {
  font-size: 0.78rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
  line-height: 1.4;
}
.pg-ep-url {
  font-size: 0.72rem;
  color: #9ca3af;
  word-break: break-all;
}
.pg-ep-ref {
  font-size: 0.72rem;
  color: #9ca3af;
  margin-top: 0.25rem;
}
.pg-ep-msg {
  font-size: 0.72rem;
  color: #6b7280;
  margin-top: 0.25rem;
}
.pg-ep-time {
  font-size: 0.7rem;
  color: #9ca3af;
  margin-top: 0.125rem;
}

/* --- Pre (code block) --- */
.pg-pre {
  background: #f8f9fc;
  border: 1px solid #e9ecf2;
  border-radius: 0.375rem;
  padding: 0.5rem 0.625rem;
  font-size: 0.7rem;
  white-space: pre-wrap;
  word-break: break-all;
  max-height: 140px;
  overflow-y: auto;
  margin: 0;
  color: #374151;
  font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
  line-height: 1.5;
}

/* --- Editable Textarea --- */
.pg-textarea {
  width: 100%;
  min-height: 200px;
  background: #fafbfc;
  border: 1.5px solid #e5e7eb;
  border-radius: 0.375rem;
  padding: 0.5rem 0.625rem;
  font-size: 0.75rem;
  font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
  line-height: 1.5;
  color: #1f2937;
  resize: vertical;
  transition: border-color 0.2s, box-shadow 0.2s;
  white-space: pre;
  overflow: auto;
}
.pg-textarea:focus {
  outline: none;
  border-color: #6777ef;
  box-shadow: 0 0 0 3px rgba(103, 119, 239, 0.12);
  background: white;
}
.pg-textarea-error {
  border-color: #ef4444;
  background: #fef2f2;
}
.pg-textarea-error:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}
.pg-edit-error {
  font-size: 0.7rem;
  color: #ef4444;
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

/* --- Vertical Tabs (Transaksi History) --- */
.pg-vertical-tabs {
  display: flex;
  flex-direction: column;
  background: #f8f9fc;
}
.pg-vtab-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border: none;
  border-bottom: 1px solid #e9ecf2;
  background: transparent;
  color: #6c757d;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
}
.pg-vtab-btn:hover {
  background: rgba(103, 119, 239, 0.06);
  color: #6777ef;
}
.pg-vtab-btn.active {
  background: white;
  color: #6777ef;
  border-right: 3px solid #6777ef;
  font-weight: 700;
}
.pg-vtab-btn .badge {
  font-size: 0.7rem;
}

/* --- Monitoring Stat Cards --- */
.pg-stat-card-mini {
  background: white;
  border: 1px solid #e3e6f0;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.pg-stat-card-body {
  padding: 1.25rem;
}
.pg-stat-card-mini .pg-stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}
.pg-stat-card-mini .pg-stat-value {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1.2;
  margin-top: 0.25rem;
}

/* --- Custom Modal (Teleported to body) --- */
.pg-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 9999;
  animation: pgFadeIn 0.15s ease;
}
.pg-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  pointer-events: none;
}
.pg-modal-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
  width: 100%;
  max-width: 900px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  pointer-events: auto;
  animation: pgModalIn 0.2s ease;
}
@keyframes pgModalIn {
  from { opacity: 0; transform: translateY(-20px) scale(0.97); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.pg-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e9ecf2;
}
.pg-modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #9ca3af;
  cursor: pointer;
  line-height: 1;
  padding: 0 0.25rem;
}
.pg-modal-close:hover { color: #374151; }
.pg-modal-body {
  padding: 1rem 1.25rem;
  overflow-y: auto;
  flex: 1;
}
.pg-modal-footer {
  display: flex;
  justify-content: flex-end;
  padding: 0.5rem 1rem;
  border-top: 1px solid #e9ecf2;
}
</style>
