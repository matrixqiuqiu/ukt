<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useToast } from '@/composables/useToast';

const { success, error: toastError } = useToast();

const props = defineProps({
  config: Object,
  endpointMeta: Array,
  btnConfig: Object,
  btnEndpointMeta: Array,
  apiLogs: {
    type: Array,
    default: () => [],
  },
  vaTransactions: {
    type: Object,
    default: () => ({ pendaftaran: [], daftar_ulang: [], all: [] }),
  },
});

const activeProvider = ref('ntb');
const isBtn = computed(() => activeProvider.value === 'btn');
const activeConfig = computed(() => isBtn.value ? props.btnConfig : props.config);
const providerName = computed(() => isBtn.value ? 'Bank BTN (SNAP VA)' : (props.config?.nama_mitra || 'Bank NTB Syariah (NTBVA)'));

const activeMainTab = ref('koneksi');
const testingToken = ref(false);
const tokenResult = ref(null);
const currentToken = ref(null);
const endpointTests = ref({});
const testingEndpoint = ref(null);

// BTN state (cermin NTB)
const btnTestingToken = ref(false);
const btnTokenResult = ref(null);
const btnCurrentToken = ref(null);
const btnEndpointTests = ref({});
const btnTestingEndpoint = ref(null);

// Auto-fetch data when tab switches
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
const showClearLogsModal = ref(false);

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
    ? (vaTransactions.value?.pendaftaran || [])
    : (vaTransactions.value?.daftar_ulang || []);

  return list.filter(t => {
    const q = historySearch.value.toLowerCase().trim();
    const matchSearch = !q ||
      (t.calon_mahasiswa?.nama && t.calon_mahasiswa.nama.toLowerCase().includes(q)) ||
      (t.calon_mahasiswa?.nim && t.calon_mahasiswa.nim.toLowerCase().includes(q)) ||
      (t.va?.full && t.va.full.toLowerCase().includes(q)) ||
      String(t.nomor || '').includes(q);
    const matchProdi = !historyFilterProdi.value ||
      t.calon_mahasiswa?.jurusan === historyFilterProdi.value;
    return matchSearch && matchProdi;
  });
});

const prodiList = computed(() => {
  const set = new Set((vaTransactions.value?.all || []).map(t => t.calon_mahasiswa?.jurusan).filter(Boolean));
  return [...set].sort();
});

// Editable request bodies per endpoint
const editableRequests = ref({});
const editErrors = ref({});
const btnEditableRequests = ref({});
const btnEditErrors = ref({});

function initEditableRequests() {
  const defaults = {};
  (props.endpointMeta || []).forEach(ep => {
    defaults[ep.key] = JSON.stringify(buildEndpointParams(ep.key), null, 2);
  });
  editableRequests.value = defaults;
  const btnDefaults = {};
  (props.btnEndpointMeta || []).forEach(ep => {
    btnDefaults[ep.key] = JSON.stringify(buildBtnEndpointParams(ep.key), null, 2);
  });
  btnEditableRequests.value = btnDefaults;
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
  if (rcode === '000') return 'Success (000)';
  if (rcode === null || rcode === undefined) return '-';
  return 'Code: ' + rcode;
}

function clearHistoryLogs() {
  showClearLogsModal.value = true;
}

async function executeClearLogs() {
  showClearLogsModal.value = false;
  try {
    await axios.post(route('admin.operations.clear-api-logs'));
    localApiLogs.value = [];
    if (activeMainTab.value === 'monitoring') {
      fetchMonitoring();
    }
    success('Riwayat API logs berhasil dibersihkan.');
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
        success('Token NTBVA berhasil didapatkan.');
      } else {
        toastError(response.data?.message || 'Gagal mengambil token');
      }
    })
    .catch(() => {
      toastError('Gagal menguji koneksi token');
    })
    .finally(() => {
      testingToken.value = false;
    });
}

async function testSingleEndpoint(ep) {
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

  try {
    const tokenResp = await axios.post(route('admin.operations.test-token'));
    if (!tokenResp.data?.success) {
      toastError('Gagal mengambil token: ' + (tokenResp.data?.message || 'Unknown'));
      testingEndpoint.value = null;
      return;
    }
    const freshToken = tokenResp.data?.data?.data?.token || null;

    if (ep.key === 'flag') {
      const testbayarParams = getEditableParams('testbayar');
      if (testbayarParams) {
        try {
          await axios.post(route('admin.operations.test-endpoint'), {
            endpoint: 'testbayar',
            token: freshToken,
            params: testbayarParams,
          });
        } catch (e) {}
      }
    }

    const response = await axios.post(route('admin.operations.test-endpoint'), {
      endpoint: ep.key,
      token: freshToken,
      params: params,
    });

    endpointTests.value[ep.key] = response.data;
    if (response.data?.success) {
      success(`${ep.name} berhasil diproses.`);
    } else {
      toastError(`${ep.name}: ${response.data?.message || 'Gagal'}`);
    }
  } catch (e) {
    toastError(`${ep.name}: Gagal mengirim request`);
  } finally {
    testingEndpoint.value = null;
  }
}

function getBtnEditableParams(key) {
  const raw = btnEditableRequests.value[key] || '{}';
  try {
    btnEditErrors.value[key] = null;
    return JSON.parse(raw);
  } catch (e) {
    btnEditErrors.value[key] = e.message;
    return null;
  }
}

function onBtnEditInput(key, event) {
  btnEditableRequests.value[key] = event.target.value;
  try {
    JSON.parse(event.target.value);
    btnEditErrors.value[key] = null;
  } catch (e) {
    btnEditErrors.value[key] = e.message;
  }
}

function btnTestToken() {
  btnTestingToken.value = true;
  btnTokenResult.value = null;
  btnCurrentToken.value = null;
  const startTime = Date.now();
  axios.post(route('admin.operations.test-btn-token'))
    .then((response) => {
      btnTokenResult.value = response.data;
      const duration = Date.now() - startTime;
      btnEndpointTests.value['token'] = {
        success: response.data?.success,
        status: response.data?.status,
        rcode: response.data?.rcode,
        message: response.data?.message,
        data: response.data?.data,
        duration_ms: response.data?.duration_ms || duration,
        request: { grantType: 'client_credentials' },
      };
      if (response.data?.success) {
        btnCurrentToken.value = response.data?.data?.accessToken || null;
        success('Token BTN berhasil didapatkan.');
      } else {
        toastError(response.data?.message || 'Gagal mengambil token BTN');
      }
    })
    .catch(() => {
      toastError('Gagal menguji koneksi token BTN');
    })
    .finally(() => {
      btnTestingToken.value = false;
    });
}

async function btnTestSingleEndpoint(ep) {
  if (ep.key === 'token') {
    btnTestToken();
    return;
  }
  const params = getBtnEditableParams(ep.key);
  if (!params) {
    toastError(`${ep.name}: JSON tidak valid`);
    return;
  }
  btnTestingEndpoint.value = ep.key;
  try {
    const response = await axios.post(route('admin.operations.test-btn-endpoint'), {
      endpoint: ep.key,
      params: params,
    });
    btnEndpointTests.value[ep.key] = response.data;
    if (response.data?.success) {
      success(`${ep.name} berhasil diproses.`);
    } else {
      toastError(`${ep.name}: ${response.data?.message || 'Gagal'}`);
    }
  } catch (e) {
    toastError(`${ep.name}: Gagal mengirim request`);
  } finally {
    btnTestingEndpoint.value = null;
  }
}

function buildBtnEndpointParams(key) {
  const svcId = props.btnConfig?.partner_service_id || '96719';
  const sampleVa = svcId + '25080110013';
  switch (key) {
    case 'create':
    case 'update':
      return {
        customerNo: '25080110013',
        virtualAccountNo: sampleVa,
        virtualAccountName: key === 'create' ? 'DEV TEST BTNVA' : 'DEV TEST BTNVA UPDATE',
        trxId: 'BTN' + String(Date.now()).slice(-12),
        totalAmount: { value: key === 'create' ? '150000.00' : '170000.00', currency: 'IDR' },
        virtualAccountTrxType: 'C',
        additionalInfo: { description: 'Pembayaran UKT via BTN VA' },
      };
    case 'inquiry':
    case 'delete':
      return {
        customerNo: '25080110013',
        virtualAccountNo: sampleVa,
        trxId: 'BTN' + String(Date.now()).slice(-12),
      };
    case 'status':
      return {
        customerNo: '25080110013',
        virtualAccountNo: sampleVa,
        inquiryRequestId: 'INQ' + String(Date.now()).slice(-12),
      };
    case 'report': {
      const d = new Date().toISOString().split('T')[0];
      return { startDate: d, endDate: d };
    }
    case 'token':
      return { grantType: 'client_credentials' };
    default:
      return {};
  }
}

function buildEndpointParams(key) {
  const idMitra = props.config?.id_mitra || '031';
  const idProduk = props.config?.id_produk || '01';
  const now = new Date();
  const dateStr = now.toISOString().split('T')[0];
  const expiryMs = ((props.config?.default_expired_days || 0) * 86400000)
    + ((props.config?.default_expired_hours || 0) * 3600000)
    + ((props.config?.default_expired_minutes || 5) * 60000);
  const expiry = new Date(now.getTime() + expiryMs);
  const expiryStr = expiry.toISOString().replace('T', ' ').substring(0, 19);
  const sampleVa = '25080110013';

  switch (key) {
    case 'va':
      return {
        va: sampleVa, id_mitra: idMitra, id_produk: idProduk,
        name: 'DEV TEST NTBVA ' + now.getFullYear(),
        billing_type: props.config?.default_billing_type || 'c',
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
        billing_type: props.config?.default_billing_type || 'c',
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
        user_id: props.config?.user_id || 'bumigora',
        user_secret: props.config?.user_secret || '',
        id_mitra: props.config?.id_mitra || '031',
      };
    default:
      return {};
  }
}
</script>

<template>
  <Head title="Payment Gateway & Operations" />

  <AuthenticatedLayout>
    <!-- Header Page -->
    <div class="op-header-wrap">
      <div>
        <div class="op-provider-badge">
          <span class="provider-pill">
            <i class="fas fa-university"></i> Provider: <strong>{{ providerName }}</strong>
          </span>
          <span class="mode-pill" :class="activeConfig?.production ? 'mode-prod' : 'mode-dev'">
            <span class="mode-dot"></span> {{ activeConfig?.production ? 'PRODUCTION' : 'SANDBOX / DEVELOPMENT' }}
          </span>
        </div>
        <h1 class="op-page-title">Payment Gateway & Operasional API</h1>
        <p class="op-page-subtitle">Uji integrasi endpoint, pantau transaksi real-time, dan audit komunikasi {{ isBtn ? 'BTN SNAP VA' : 'NTBVA' }}</p>
      </div>
      <div class="op-header-actions">
        <button v-if="!isBtn" type="button" class="op-btn op-btn-primary" @click="testToken" :disabled="testingToken">
          <i class="fas" :class="testingToken ? 'fa-spinner fa-pulse' : 'fa-plug'"></i>
          {{ testingToken ? 'Menguji Token...' : 'Uji Koneksi Token' }}
        </button>
        <button v-else type="button" class="op-btn op-btn-primary" @click="btnTestToken" :disabled="btnTestingToken">
          <i class="fas" :class="btnTestingToken ? 'fa-spinner fa-pulse' : 'fa-plug'"></i>
          {{ btnTestingToken ? 'Menguji Token...' : 'Uji Koneksi Token' }}
        </button>
      </div>
    </div>

    <!-- Provider Tabs: NTB | BTN -->
    <div class="op-main-tabs" style="margin-bottom:1rem;">
      <button
        type="button"
        class="op-tab-btn"
        :class="{ active: activeProvider === 'ntb' }"
        @click="activeProvider = 'ntb'"
      >
        <i class="fas fa-university"></i> Bank NTB Syariah
      </button>
      <button
        type="button"
        class="op-tab-btn"
        :class="{ active: activeProvider === 'btn' }"
        @click="activeProvider = 'btn'"
      >
        <i class="fas fa-university"></i> Bank BTN
      </button>
    </div>

    <!-- Main Navigation Tabs -->
    <div class="op-main-tabs">
      <button
        type="button"
        class="op-tab-btn"
        :class="{ active: activeMainTab === 'koneksi' }"
        @click="activeMainTab = 'koneksi'"
      >
        <i class="fas fa-plug"></i> Status Koneksi & Endpoint
      </button>
      <button
        type="button"
        class="op-tab-btn"
        :class="{ active: activeMainTab === 'monitoring' }"
        @click="activeMainTab = 'monitoring'"
      >
        <i class="fas fa-chart-line"></i> Monitoring Transaksi (24 Jam)
      </button>
      <button
        type="button"
        class="op-tab-btn"
        :class="{ active: activeMainTab === 'history' }"
        @click="activeMainTab = 'history'"
      >
        <i class="fas fa-history"></i> Riwayat Transaksi VA
      </button>
      <button
        type="button"
        class="op-tab-btn"
        :class="{ active: activeMainTab === 'retry' }"
        @click="activeMainTab = 'retry'"
      >
        <i class="fas fa-redo"></i> Aksi Retry
      </button>
    </div>

    <!-- ================= TAB 1: STATUS KONEKSI & TESTING (NTB) ================= -->
    <div v-show="activeMainTab === 'koneksi' && activeProvider === 'ntb'" class="op-tab-content">
      <!-- Top Row Cards -->
      <div class="op-cards-row">
        <!-- Status Integrasi Card -->
        <div class="op-solid-card">
          <div class="card-head">
            <h3 class="card-title">
              <i class="fas fa-signal" style="color:#2563eb;"></i> Status Integrasi NTBVA
            </h3>
            <button type="button" class="op-btn-sm op-btn-secondary" :disabled="testingToken" @click="testToken">
              <i class="fas" :class="testingToken ? 'fa-spinner fa-pulse' : 'fa-sync-alt'"></i> Tes Token
            </button>
          </div>
          <div class="card-body">
            <template v-if="tokenResult">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                <span class="solid-tag" :class="tokenResult.success ? 'tag-success' : 'tag-danger'">
                  <i :class="tokenResult.success ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                  {{ tokenResult.success ? 'TOKEN BERHASIL DIDAPATKAN' : 'TOKEN GAGAL' }}
                </span>
                <span style="font-size:0.75rem;color:#64748b;font-weight:600;">
                  Latency: {{ formatDuration(tokenResult.duration_ms) }}
                </span>
              </div>
              <p style="margin:0 0 0.75rem;font-size:0.875rem;color:#334155;line-height:1.5;">
                {{ tokenResult.message || 'Koneksi ke endpoint auth NTBVA berhasil.' }}
              </p>
              <div v-if="currentToken" class="token-box">
                <div class="token-label">TOKEN AKTIF:</div>
                <div class="token-value">{{ currentToken }}</div>
              </div>
            </template>
            <template v-else>
              <div class="empty-test-box">
                <i class="fas fa-plug"></i>
                <p>Belum ada pengujian token. Klik tombol <strong>"Uji Koneksi Token"</strong> di atas untuk memverifikasi.</p>
              </div>
            </template>
          </div>
        </div>

        <!-- Konfigurasi ENV Card -->
        <div class="op-solid-card">
          <div class="card-head">
            <h3 class="card-title">
              <i class="fas fa-sliders-h" style="color:#16a34a;"></i> Konfigurasi Parameter Gateway
            </h3>
          </div>
          <div class="card-body">
            <div class="config-stats-grid">
              <div class="config-stat-item">
                <div class="stat-lbl">PROVIDER</div>
                <div class="stat-val">{{ config?.nama_mitra || 'ntbva' }}</div>
              </div>
              <div class="config-stat-item">
                <div class="stat-lbl">ID MITRA</div>
                <div class="stat-val font-mono">{{ config?.id_mitra || '-' }}</div>
              </div>
              <div class="config-stat-item">
                <div class="stat-lbl">ID PRODUK</div>
                <div class="stat-val font-mono">{{ config?.id_produk || '-' }}</div>
              </div>
              <div class="config-stat-item">
                <div class="stat-lbl">ENDPOINTS</div>
                <div class="stat-val text-success">{{ endpointMeta?.length || 0 }} Ready</div>
              </div>
            </div>

            <div class="env-url-box">
              <span class="url-lbl">BASE URL:</span>
              <span class="url-text font-mono">{{ config?.url_base || 'Belum diatur' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Workbench Testing Endpoints Table -->
      <div class="op-solid-card">
        <div class="card-head">
          <h3 class="card-title">
            <i class="fas fa-flask" style="color:#4f46e5;"></i> Workbench Pengujian Endpoint API NTBVA
          </h3>
          <span style="font-size:0.75rem;color:#64748b;font-weight:600;">
            Lingkungan: <strong>{{ config?.production ? 'Production' : 'Development / Sandbox' }}</strong>
          </span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="op-table">
              <thead>
                <tr>
                  <th style="width:280px;">ENDPOINT & DESKRIPSI</th>
                  <th style="width:140px;">STATUS & LATENCY</th>
                  <th>REQUEST PAYLOAD (JSON)</th>
                  <th>RESPONSE PAYLOAD</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="ep in endpointMeta" :key="ep.key">
                  <td>
                    <div class="ep-badge-method">POST</div>
                    <div class="ep-name">{{ ep.name }}</div>
                    <div class="ep-desc">{{ ep.description }}</div>
                    <div class="ep-path font-mono">{{ config?.url_base }}{{ ep.path }}</div>
                  </td>
                  <td>
                    <template v-if="endpointTests[ep.key]">
                      <span class="solid-tag" :class="endpointTests[ep.key].success ? 'tag-success' : 'tag-danger'">
                        {{ endpointTests[ep.key].success ? '200 SUCCESS' : 'FAILED' }}
                      </span>
                      <div class="ep-rcode font-mono">{{ rcodeLabel(endpointTests[ep.key].rcode) }}</div>
                      <div class="ep-latency"><i class="far fa-clock"></i> {{ formatDuration(endpointTests[ep.key].duration_ms) }}</div>
                    </template>
                    <template v-else>
                      <span class="solid-tag tag-secondary">BELUM DITES</span>
                      <div class="ep-latency">-</div>
                    </template>
                  </td>
                  <td>
                    <textarea
                      class="op-code-textarea"
                      :value="editableRequests[ep.key]"
                      @input="onEditInput(ep.key, $event)"
                      rows="6"
                      spellcheck="false"
                      :class="{ 'code-error': editErrors[ep.key] }"
                    ></textarea>
                    <div v-if="editErrors[ep.key]" class="code-error-msg">
                      <i class="fas fa-exclamation-triangle"></i> JSON tidak valid
                    </div>
                    <button
                      type="button"
                      class="op-btn-sm op-btn-primary"
                      style="margin-top:0.5rem;"
                      :disabled="testingEndpoint === ep.key || !!editErrors[ep.key]"
                      @click="testSingleEndpoint(ep)"
                    >
                      <i class="fas" :class="testingEndpoint === ep.key ? 'fa-spinner fa-pulse' : 'fa-play'"></i>
                      {{ testingEndpoint === ep.key ? 'Mengirim...' : 'Kirim Request' }}
                    </button>
                  </td>
                  <td>
                    <pre class="op-pre-box">{{ JSON.stringify(endpointTests[ep.key]?.data || [], null, 2) }}</pre>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= TAB 1B: STATUS KONEKSI & TESTING (BTN) ================= -->
    <div v-show="activeMainTab === 'koneksi' && activeProvider === 'btn'" class="op-tab-content">
      <!-- Top Row Cards -->
      <div class="op-cards-row">
        <!-- Status Integrasi Card -->
        <div class="op-solid-card">
          <div class="card-head">
            <h3 class="card-title">
              <i class="fas fa-signal" style="color:#2563eb;"></i> Status Integrasi BTN SNAP VA
            </h3>
            <button type="button" class="op-btn-sm op-btn-secondary" :disabled="btnTestingToken" @click="btnTestToken">
              <i class="fas" :class="btnTestingToken ? 'fa-spinner fa-pulse' : 'fa-sync-alt'"></i> Tes Token
            </button>
          </div>
          <div class="card-body">
            <template v-if="btnTokenResult">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                <span class="solid-tag" :class="btnTokenResult.success ? 'tag-success' : 'tag-danger'">
                  <i :class="btnTokenResult.success ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                  {{ btnTokenResult.success ? 'TOKEN BERHASIL DIDAPATKAN' : 'TOKEN GAGAL' }}
                </span>
                <span style="font-size:0.75rem;color:#64748b;font-weight:600;">
                  Latency: {{ formatDuration(btnTokenResult.duration_ms) }}
                </span>
              </div>
              <p style="margin:0 0 0.75rem;font-size:0.875rem;color:#334155;line-height:1.5;">
                {{ btnTokenResult.message || 'Koneksi ke endpoint auth BTN berhasil.' }}
              </p>
              <div v-if="btnCurrentToken" class="token-box">
                <div class="token-label">TOKEN AKTIF:</div>
                <div class="token-value">{{ btnCurrentToken }}</div>
              </div>
            </template>
            <template v-else>
              <div class="empty-test-box">
                <i class="fas fa-plug"></i>
                <p>Belum ada pengujian token. Klik tombol <strong>"Uji Koneksi Token"</strong> di atas untuk memverifikasi.</p>
              </div>
            </template>
          </div>
        </div>

        <!-- Konfigurasi ENV Card -->
        <div class="op-solid-card">
          <div class="card-head">
            <h3 class="card-title">
              <i class="fas fa-sliders-h" style="color:#16a34a;"></i> Konfigurasi Parameter Gateway
            </h3>
          </div>
          <div class="card-body">
            <div class="config-stats-grid">
              <div class="config-stat-item">
                <div class="stat-lbl">PROVIDER</div>
                <div class="stat-val">BTN</div>
              </div>
              <div class="config-stat-item">
                <div class="stat-lbl">PARTNER SVC ID</div>
                <div class="stat-val font-mono">{{ btnConfig?.partner_service_id || '-' }}</div>
              </div>
              <div class="config-stat-item">
                <div class="stat-lbl">CHANNEL ID</div>
                <div class="stat-val font-mono">{{ btnConfig?.channel_id || '-' }}</div>
              </div>
              <div class="config-stat-item">
                <div class="stat-lbl">ENDPOINTS</div>
                <div class="stat-val text-success">{{ btnEndpointMeta?.length || 0 }} Ready</div>
              </div>
              <div class="config-stat-item">
                <div class="stat-lbl">CLIENT SECRET</div>
                <div class="stat-val" :class="btnConfig?.has_client_secret ? 'text-success' : 'text-danger'">{{ btnConfig?.has_client_secret ? 'Terisi' : 'Kosong' }}</div>
              </div>
              <div class="config-stat-item">
                <div class="stat-lbl">PRIVATE KEY</div>
                <div class="stat-val" :class="btnConfig?.has_private_key ? 'text-success' : 'text-danger'">{{ btnConfig?.has_private_key ? 'Terisi' : 'Kosong' }}</div>
              </div>
            </div>

            <div class="env-url-box">
              <span class="url-lbl">BASE URL:</span>
              <span class="url-text font-mono">{{ btnConfig?.base_url || 'Belum diatur' }}</span>
            </div>
            <div class="env-url-box" style="margin-top:0.5rem;">
              <span class="url-lbl">CLIENT KEY:</span>
              <span class="url-text font-mono">{{ btnConfig?.client_key || 'Belum diatur' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Workbench Testing Endpoints Table -->
      <div class="op-solid-card">
        <div class="card-head">
          <h3 class="card-title">
            <i class="fas fa-flask" style="color:#4f46e5;"></i> Workbench Pengujian Endpoint API BTN
          </h3>
          <span style="font-size:0.75rem;color:#64748b;font-weight:600;">
            Lingkungan: <strong>{{ btnConfig?.production ? 'Production' : 'Development / Sandbox' }}</strong>
          </span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="op-table">
              <thead>
                <tr>
                  <th style="width:280px;">ENDPOINT & DESKRIPSI</th>
                  <th style="width:140px;">STATUS & LATENCY</th>
                  <th>REQUEST PAYLOAD (JSON)</th>
                  <th>RESPONSE PAYLOAD</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="ep in btnEndpointMeta" :key="ep.key">
                  <td>
                    <div class="ep-badge-method">POST</div>
                    <div class="ep-name">{{ ep.name }}</div>
                    <div class="ep-desc">{{ ep.description }}</div>
                    <div class="ep-path font-mono">{{ btnConfig?.base_url }}{{ ep.path }}</div>
                  </td>
                  <td>
                    <template v-if="btnEndpointTests[ep.key]">
                      <span class="solid-tag" :class="btnEndpointTests[ep.key].success ? 'tag-success' : 'tag-danger'">
                        {{ btnEndpointTests[ep.key].success ? '200 SUCCESS' : 'FAILED' }}
                      </span>
                      <div class="ep-rcode font-mono">{{ rcodeLabel(btnEndpointTests[ep.key].rcode) }}</div>
                      <div class="ep-latency"><i class="far fa-clock"></i> {{ formatDuration(btnEndpointTests[ep.key].duration_ms) }}</div>
                    </template>
                    <template v-else>
                      <span class="solid-tag tag-secondary">BELUM DITES</span>
                      <div class="ep-latency">-</div>
                    </template>
                  </td>
                  <td>
                    <textarea
                      class="op-code-textarea"
                      :value="btnEditableRequests[ep.key]"
                      @input="onBtnEditInput(ep.key, $event)"
                      rows="6"
                      spellcheck="false"
                      :class="{ 'code-error': btnEditErrors[ep.key] }"
                    ></textarea>
                    <div v-if="btnEditErrors[ep.key]" class="code-error-msg">
                      <i class="fas fa-exclamation-triangle"></i> JSON tidak valid
                    </div>
                    <button
                      type="button"
                      class="op-btn-sm op-btn-primary"
                      style="margin-top:0.5rem;"
                      :disabled="btnTestingEndpoint === ep.key || !!btnEditErrors[ep.key]"
                      @click="btnTestSingleEndpoint(ep)"
                    >
                      <i class="fas" :class="btnTestingEndpoint === ep.key ? 'fa-spinner fa-pulse' : 'fa-play'"></i>
                      {{ btnTestingEndpoint === ep.key ? 'Mengirim...' : 'Kirim Request' }}
                    </button>
                  </td>
                  <td>
                    <pre class="op-pre-box">{{ JSON.stringify(btnEndpointTests[ep.key]?.data || [], null, 2) }}</pre>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= TAB 2: MONITORING TRANSAKSI (24 JAM) ================= -->
    <div v-show="activeMainTab === 'monitoring'" class="op-tab-content">
      <!-- 4 Metrics Grid -->
      <div class="op-metrics-grid">
        <div class="op-metric-box">
          <div class="metric-icon-bg" style="background:#eff6ff;color:#2563eb;">
            <i class="fas fa-exchange-alt"></i>
          </div>
          <div>
            <div class="metric-lbl">Total Transaksi (24 Jam)</div>
            <div class="metric-num">{{ monitoring?.stats?.total_24 || 0 }}</div>
          </div>
        </div>

        <div class="op-metric-box">
          <div class="metric-icon-bg" style="background:#f0fdf4;color:#16a34a;">
            <i class="fas fa-check-circle"></i>
          </div>
          <div>
            <div class="metric-lbl">Berhasil / Success (24 Jam)</div>
            <div class="metric-num text-success">{{ monitoring?.stats?.success_24 || 0 }}</div>
          </div>
        </div>

        <div class="op-metric-box">
          <div class="metric-icon-bg" style="background:#fefce8;color:#d97706;">
            <i class="fas fa-clock"></i>
          </div>
          <div>
            <div class="metric-lbl">Gagal / Incomplete (24 Jam)</div>
            <div class="metric-num text-warning">{{ monitoring?.stats?.failed_24 || 0 }}</div>
          </div>
        </div>

        <div class="op-metric-box">
          <div class="metric-icon-bg" style="background:#fee2e2;color:#dc2626;">
            <i class="fas fa-exclamation-circle"></i>
          </div>
          <div>
            <div class="metric-lbl">Error HTTP / Server (24 Jam)</div>
            <div class="metric-num text-danger">{{ monitoring?.stats?.error_24 || 0 }}</div>
          </div>
        </div>
      </div>

      <!-- Breakdown & Recent Logs Split Row -->
      <div class="op-cards-row">
        <!-- Breakdown rCode -->
        <div class="op-solid-card" style="flex: 1 1 380px;">
          <div class="card-head">
            <h3 class="card-title">
              <i class="fas fa-list-ol" style="color:#0284c7;"></i> Breakdown Response Code (rCode)
            </h3>
          </div>
          <div class="card-body p-0">
            <div v-if="!monitoring.r_code_breakdown || monitoring.r_code_breakdown.length === 0" class="empty-state-box">
              <i class="fas fa-inbox"></i>
              <p>Belum ada data rCode tercatat dalam 24 jam terakhir.</p>
            </div>
            <div v-else class="table-responsive">
              <table class="op-table">
                <thead>
                  <tr>
                    <th>RCODE</th>
                    <th>KETERANGAN</th>
                    <th style="text-align:right;">TOTAL</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, idx) in monitoring.r_code_breakdown" :key="idx">
                    <td><span class="solid-tag tag-primary font-mono">{{ item.rcode }}</span></td>
                    <td style="font-size:0.8125rem;">{{ item.description }}</td>
                    <td style="text-align:right;font-weight:700;color:#0f172a;">{{ item.total }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Recent Logs Table -->
        <div class="op-solid-card" style="flex: 2 1 540px;">
          <div class="card-head">
            <h3 class="card-title">
              <i class="fas fa-history" style="color:#4f46e5;"></i> Riwayat Komunikasi API Terbaru
            </h3>
            <div style="display:flex;align-items:center;gap:0.5rem;">
              <button type="button" class="op-btn-sm op-btn-secondary" @click="fetchMonitoring" :disabled="loadingMonitoring">
                <i class="fas" :class="loadingMonitoring ? 'fa-spinner fa-pulse' : 'fa-sync-alt'"></i> Refresh
              </button>
              <button type="button" class="op-btn-sm op-btn-danger" @click="clearHistoryLogs">
                <i class="fas fa-trash"></i> Bersihkan Log
              </button>
            </div>
          </div>
          <div class="card-body p-0">
            <div v-if="!monitoring.recent_logs || monitoring.recent_logs.length === 0" class="empty-state-box">
              <i class="fas fa-inbox"></i>
              <p>Belum ada riwayat transaksi tercatat.</p>
            </div>
            <div v-else class="table-responsive">
              <table class="op-table op-table-hover">
                <thead>
                  <tr>
                    <th>WAKTU</th>
                    <th>ENDPOINT</th>
                    <th>STATUS</th>
                    <th>RCODE</th>
                    <th>LATENCY</th>
                    <th>PESAN</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="log in monitoring.recent_logs"
                    :key="log.id"
                    style="cursor:pointer;"
                    @click="openTransactionDetail(log.id)"
                  >
                    <td style="font-size:0.75rem;color:#64748b;white-space:nowrap;">{{ log.waktu }}</td>
                    <td><span class="solid-tag tag-primary font-mono">{{ log.endpoint }}</span></td>
                    <td>
                      <span class="solid-tag" :class="log.success ? 'tag-success' : 'tag-danger'">
                        {{ log.success ? 'Success' : 'Failed' }}
                      </span>
                    </td>
                    <td class="font-mono" style="font-weight:600;font-size:0.8125rem;">{{ log.rcode || '-' }}</td>
                    <td style="font-size:0.75rem;color:#64748b;">{{ formatDuration(log.duration_ms) }}</td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.75rem;color:#475569;">
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

    <!-- ================= TAB 3: RIWAYAT TRANSAKSI VA ================= -->
    <div v-show="activeMainTab === 'history'" class="op-tab-content">
      <div class="op-solid-card">
        <div class="card-head">
          <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
              <h3 class="card-title">
                <i class="fas fa-receipt" style="color:#2563eb;"></i> Transaksi Tagihan & VA {{ isBtn ? 'BTN' : 'NTB' }}
              </h3>
            <!-- Sub tabs -->
            <div class="op-sub-tabs">
              <button
                type="button"
                class="sub-tab-btn"
                :class="{ active: historySubTab === 'ukt' }"
                @click="historySubTab = 'ukt'"
              >
                UKT Mahasiswa <span class="sub-counter">{{ vaTransactions?.pendaftaran?.length || 0 }}</span>
              </button>
              <button
                type="button"
                class="sub-tab-btn"
                :class="{ active: historySubTab === 'daftar_ulang' }"
                @click="historySubTab = 'daftar_ulang'"
              >
                Daftar Ulang <span class="sub-counter">{{ vaTransactions?.daftar_ulang?.length || 0 }}</span>
              </button>
            </div>
          </div>

          <!-- Toolbar filter -->
          <div class="history-toolbar">
            <div class="clean-search-box">
              <i class="fas fa-search search-icon"></i>
              <input
                type="search"
                class="clean-search-input"
                v-model="historySearch"
                placeholder="Cari nama, NIM, nomor VA..."
              />
              <button v-if="historySearch" type="button" class="search-clear-btn" @click="historySearch = ''">
                <i class="fas fa-times"></i>
              </button>
            </div>

            <select class="history-select" v-model="historyFilterProdi">
              <option value="">Semua Program Studi</option>
              <option v-for="prodi in prodiList" :key="prodi" :value="prodi">{{ prodi }}</option>
            </select>

            <button type="button" class="op-btn-sm op-btn-secondary" @click="fetchTransaksiHistory" :disabled="loadingHistory">
              <i class="fas" :class="loadingHistory ? 'fa-spinner fa-pulse' : 'fa-sync-alt'"></i>
            </button>
          </div>
        </div>

        <div class="card-body p-0">
          <div v-if="filteredTransactions.length > 0" class="table-responsive">
            <table class="op-table op-table-hover">
              <thead>
                <tr>
                  <th>WAKTU & EXPIRED</th>
                  <th>MAHASISWA & PRODI</th>
                  <th>NOMOR</th>
                  <th>NOMOR VA</th>
                  <th>TAGIHAN</th>
                  <th>STATUS</th>
                  <th>PEMBAYARAN</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="t in filteredTransactions"
                  :key="t.id"
                  style="cursor:pointer;"
                  @click="openTransactionDetail(t.id)"
                >
                  <td style="font-size:0.75rem;white-space:nowrap;">
                    <div style="font-weight:600;color:#0f172a;">{{ t.waktu }}</div>
                    <div v-if="t.waktu_expired" style="color:#64748b;font-size:0.6875rem;">Exp: {{ t.waktu_expired }}</div>
                  </td>
                  <td>
                    <div style="font-weight:700;color:#0f172a;">{{ t.calon_mahasiswa?.nama }}</div>
                    <div style="font-size:0.75rem;color:#475569;font-family:monospace;">{{ t.calon_mahasiswa?.nim }}</div>
                    <div style="font-size:0.6875rem;color:#64748b;">{{ t.calon_mahasiswa?.jurusan }}</div>
                  </td>
                  <td class="font-mono" style="font-size:0.8125rem;">{{ t.nomor }}</td>
                  <td>
                    <div class="font-mono" style="font-weight:700;color:#2563eb;font-size:0.875rem;">{{ t.va?.full }}</div>
                    <div style="font-size:0.6875rem;color:#64748b;">Suffix: {{ t.va?.suffix }}</div>
                  </td>
                  <td style="font-weight:700;color:#0f172a;font-size:0.875rem;">
                    Rp {{ Number(t.tagihan?.nominal || 0).toLocaleString('id-ID') }}
                  </td>
                  <td>
                    <span class="solid-tag" :class="{
                      'tag-success': t.status === 'dikonfirmasi',
                      'tag-warning': t.status === 'pending',
                      'tag-danger': t.status === 'ditolak'
                    }">
                      {{ t.status === 'dikonfirmasi' ? 'LUNAS' : (t.status === 'pending' ? 'PENDING' : 'DITOLAK') }}
                    </span>
                  </td>
                  <td>
                    <div v-if="t.bayar" style="font-weight:700;color:#16a34a;font-size:0.875rem;">
                      Rp {{ Number(t.bayar).toLocaleString('id-ID') }}
                    </div>
                    <div v-else style="color:#94a3b8;font-size:0.8125rem;">-</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="empty-state-box">
            <i class="fas fa-receipt"></i>
            <p>Tidak ada transaksi {{ historySubTab === 'ukt' ? 'UKT' : 'daftar ulang' }} yang cocok.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= TAB 4: AKSI RETRY ================= -->
    <div v-show="activeMainTab === 'retry'" class="op-tab-content">
      <div class="op-solid-card">
        <div class="card-head">
          <h3 class="card-title">
            <i class="fas fa-redo" style="color:#d97706;"></i> Antrean & Aksi Retry Transaksi Gagal
          </h3>
        </div>
        <div class="card-body">
          <div class="empty-state-box">
            <i class="fas fa-check-double" style="color:#16a34a;"></i>
            <h4 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0.5rem 0 0.25rem;">Semua Transaksi Normal</h4>
            <p style="color:#64748b;font-size:0.875rem;max-width:460px;margin:0 auto;">
              Tidak ada transaksi yang tertunda atau membutuhkan percobaan ulang (retry). Antrean retry otomatis akan aktif jika terjadi kegagalan jaringan callback gateway.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= MODAL DETAIL TRANSAKSI ================= -->
    <Teleport to="body">
      <template v-if="showDetailModal">
        <div class="op-modal-backdrop" @click="showDetailModal = false"></div>
        <div class="op-modal-overlay" @click.self="showDetailModal = false">
          <div class="op-modal-box">
            <!-- Header Modal -->
            <div class="op-modal-header">
              <div style="display:flex;align-items:center;gap:0.625rem;">
                <code class="tag-code">&lt;/&gt;</code>
                <span style="font-size:1rem;font-weight:700;color:#0f172a;">Detail Payload Komunikasi NTBVA</span>
                <span v-if="detailData" class="solid-tag" :class="detailData.success ? 'tag-success' : 'tag-warning'">
                  {{ detailData.success ? '200 SUCCESS' : 'FAILED' }}
                </span>
              </div>
              <button type="button" class="modal-close-btn" @click="showDetailModal = false">&times;</button>
            </div>

            <!-- Body Modal -->
            <div class="op-modal-body" v-if="detailData">
              <div class="detail-meta-bar">
                <span><i class="far fa-clock"></i> {{ detailData.created_at }}</span>
                <span><i class="fas fa-cog"></i> {{ detailData.endpoint }}</span>
                <span>HTTP: <strong style="color:#0f172a;">{{ detailData.status_code }}</strong></span>
                <span>rCode: <strong style="color:#0f172a;">{{ detailData.rcode || '-' }}</strong></span>
                <span>Durasi: <strong style="color:#0f172a;">{{ formatDuration(detailData.duration_ms) }}</strong></span>
              </div>

              <div class="detail-alert-box" :class="detailData.success ? 'alert-success' : 'alert-warning'">
                <i :class="detailData.success ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'"></i>
                {{ detailData.message || 'Permintaan NTBVA berhasil diproses.' }}
              </div>

              <!-- Payloads 2 Columns -->
              <div class="payloads-grid">
                <!-- Request Payload -->
                <div class="payload-panel">
                  <div class="payload-head">
                    <i class="fas fa-arrow-up text-primary"></i> REQUEST PAYLOAD (Dikirim)
                  </div>
                  <pre class="payload-pre">{{ formatJson(detailData.request_data) }}</pre>
                </div>

                <!-- Response Payload -->
                <div class="payload-panel">
                  <div class="payload-head">
                    <i class="fas fa-arrow-down text-danger"></i> RESPONSE PAYLOAD (Diterima)
                  </div>
                  <pre class="payload-pre">{{ formatJson(detailData.response_data) }}</pre>
                </div>
              </div>
            </div>

            <!-- Loading Modal -->
            <div class="op-modal-body text-center" v-else style="padding:3rem 1.5rem;">
              <i class="fas fa-spinner fa-pulse" style="font-size:2rem;color:#2563eb;margin-bottom:0.75rem;"></i>
              <p style="color:#64748b;font-size:0.875rem;margin:0;">Memuat data detail transaksi...</p>
            </div>

            <!-- Footer Modal -->
            <div class="op-modal-footer">
              <button type="button" class="op-btn-sm op-btn-secondary" @click="showDetailModal = false">
                <i class="fas fa-times"></i> Tutup
              </button>
            </div>
          </div>
        </div>
      </template>

      <!-- Modal Clear API Logs Confirmation -->
      <div v-if="showClearLogsModal" class="op-modal-backdrop" @click.self="showClearLogsModal = false"></div>
      <div v-if="showClearLogsModal" class="op-modal-overlay" @click.self="showClearLogsModal = false">
        <div class="op-modal-box" style="max-width:440px;">
          <div class="op-modal-header">
            <h3 style="color:#dc2626;font-size:1.125rem;font-weight:700;margin:0;display:flex;align-items:center;gap:0.5rem;">
              <i class="fas fa-trash-alt"></i> Hapus Riwayat API Logs
            </h3>
            <button type="button" class="modal-close-btn" @click="showClearLogsModal = false">&times;</button>
          </div>
          <div class="op-modal-body">
            <p style="margin:0;color:#475569;font-size:0.875rem;line-height:1.5;">
              Apakah Anda yakin ingin menghapus seluruh rekaman riwayat komunikasi API logs? Data riwayat yang telah dibersihkan tidak dapat dipulihkan.
            </p>
          </div>
          <div class="op-modal-footer">
            <button type="button" class="op-btn-sm op-btn-secondary" @click="showClearLogsModal = false">Batal</button>
            <button type="button" class="op-btn-sm op-btn-danger" @click="executeClearLogs">
              <i class="fas fa-trash"></i> Ya, Hapus Semua
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AuthenticatedLayout>
</template>

<style scoped>
/* =============================================
   OPERATIONS & PAYMENT GATEWAY SOLID STYLING
   ============================================= */

.op-header-wrap {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.op-provider-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 0.375rem;
}
.provider-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  background: #eff6ff;
  color: #1e40af;
  border: 1px solid #bfdbfe;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}
.mode-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.04em;
}
.mode-prod { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.mode-dev { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.mode-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.op-page-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.02em;
}
.op-page-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0.25rem 0 0;
}

/* Main Navigation Tabs */
.op-main-tabs {
  display: flex;
  gap: 0.375rem;
  background: #e2e8f0;
  padding: 4px;
  border-radius: 0.75rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}
.op-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #475569;
  background: transparent;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}
.op-tab-btn:hover {
  color: #0f172a;
}
.op-tab-btn.active {
  background: #ffffff;
  color: #0f172a;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  font-weight: 700;
}

/* Solid Cards */
.op-solid-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 1rem;
  overflow: hidden;
  margin-bottom: 1.25rem;
}
.op-cards-row {
  display: flex;
  gap: 1.25rem;
  flex-wrap: wrap;
}
.op-cards-row > .op-solid-card {
  flex: 1 1 340px;
}

.card-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid #f1f5f9;
  flex-wrap: wrap;
  gap: 0.75rem;
}
.card-title {
  font-size: 0.9375rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.card-body {
  padding: 1.25rem;
}
.p-0 { padding: 0 !important; }

/* Buttons */
.op-btn {
  height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0 1.125rem;
  font-size: 0.8125rem;
  font-weight: 600;
  border-radius: 0.5rem;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all 0.15s ease;
  text-decoration: none;
}
.op-btn-primary { background: #2563eb; color: #ffffff; }
.op-btn-primary:hover:not(:disabled) { background: #1d4ed8; }

.op-btn-sm {
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  padding: 0 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 0.375rem;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all 0.15s ease;
}
.op-btn-sm.op-btn-primary { background: #2563eb; color: #ffffff; }
.op-btn-sm.op-btn-primary:hover:not(:disabled) { background: #1d4ed8; }
.op-btn-sm.op-btn-secondary { background: #ffffff; color: #334155; border-color: #cbd5e1; }
.op-btn-sm.op-btn-secondary:hover:not(:disabled) { background: #f1f5f9; color: #0f172a; }
.op-btn-sm.op-btn-danger { background: #dc2626; color: #ffffff; }
.op-btn-sm.op-btn-danger:hover:not(:disabled) { background: #b91c1c; }

/* Solid Tags */
.solid-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.2rem 0.5rem;
  border-radius: 0.375rem;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.02em;
}
.tag-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.tag-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.tag-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.tag-primary { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.tag-secondary { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

.token-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.75rem;
  font-size: 0.75rem;
}
.token-label { font-weight: 700; color: #64748b; font-size: 0.6875rem; margin-bottom: 0.25rem; }
.token-value { word-break: break-all; font-family: monospace; color: #0f172a; line-height: 1.4; }

.config-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
  gap: 0.625rem;
  margin-bottom: 0.875rem;
}
.config-stat-item {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.625rem 0.75rem;
  text-align: center;
}
.stat-lbl { font-size: 0.6875rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
.stat-val { font-size: 0.9375rem; font-weight: 800; color: #0f172a; margin-top: 0.125rem; }

.env-url-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.url-lbl { font-weight: 700; color: #64748b; flex-shrink: 0; }
.url-text { color: #0f172a; word-break: break-all; }

/* Table */
.op-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}
.op-table thead th {
  background: #f8fafc;
  padding: 0.75rem 1rem;
  font-size: 0.6875rem;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  border-bottom: 1px solid #e2e8f0;
  text-align: left;
}
.op-table tbody td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  vertical-align: top;
}
.op-table-hover tbody tr:hover {
  background: #f8fafc;
}

.ep-badge-method {
  display: inline-block;
  background: #0f172a;
  color: #ffffff;
  font-size: 0.625rem;
  font-weight: 800;
  padding: 0.1rem 0.35rem;
  border-radius: 0.25rem;
  margin-bottom: 0.25rem;
}
.ep-name { font-weight: 700; color: #0f172a; font-size: 0.875rem; }
.ep-desc { font-size: 0.75rem; color: #64748b; margin-top: 0.125rem; line-height: 1.4; }
.ep-path { font-size: 0.6875rem; color: #94a3b8; word-break: break-all; margin-top: 0.25rem; }
.ep-rcode { font-size: 0.75rem; font-weight: 600; color: #16a34a; margin-top: 0.25rem; }
.ep-latency { font-size: 0.6875rem; color: #64748b; margin-top: 0.125rem; }

/* Code editor & output */
.op-code-textarea {
  width: 100%;
  box-sizing: border-box;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  padding: 0.5rem 0.625rem;
  font-size: 0.75rem;
  font-family: 'SF Mono', 'Fira Code', Consolas, monospace;
  color: #0f172a;
  line-height: 1.4;
  outline: none;
  resize: vertical;
}
.op-code-textarea:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}
.code-error { border-color: #dc2626 !important; background: #fef2f2; }
.code-error-msg { font-size: 0.6875rem; color: #dc2626; margin-top: 0.25rem; font-weight: 600; }

.op-pre-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.625rem 0.75rem;
  font-size: 0.75rem;
  font-family: 'SF Mono', 'Fira Code', Consolas, monospace;
  color: #1e293b;
  max-height: 160px;
  overflow: auto;
  margin: 0;
  line-height: 1.4;
}

/* Metrics Grid (Tab 2) */
.op-metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 1.25rem;
}
.op-metric-box {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.875rem;
  padding: 1.125rem;
  display: flex;
  align-items: center;
  gap: 0.875rem;
}
.metric-icon-bg {
  width: 44px;
  height: 44px;
  border-radius: 0.625rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}
.metric-lbl { font-size: 0.6875rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; }
.metric-num { font-size: 1.5rem; font-weight: 800; color: #0f172a; line-height: 1.2; margin-top: 0.125rem; }

/* Sub Tabs (History) */
.op-sub-tabs {
  display: flex;
  gap: 0.25rem;
  background: #e2e8f0;
  padding: 3px;
  border-radius: 0.5rem;
}
.sub-tab-btn {
  border: none;
  background: transparent;
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: all 0.15s ease;
  display: flex;
  align-items: center;
  gap: 0.375rem;
}
.sub-tab-btn.active {
  background: #ffffff;
  color: #0f172a;
  font-weight: 700;
  box-shadow: 0 1px 2px rgba(0,0,0,0.08);
}
.sub-counter {
  background: #cbd5e1;
  color: #334155;
  font-size: 0.625rem;
  padding: 0.1rem 0.35rem;
  border-radius: 9999px;
}
.sub-tab-btn.active .sub-counter { background: #eff6ff; color: #1d4ed8; }

.history-toolbar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.clean-search-box {
  position: relative;
  display: flex;
  align-items: center;
  width: 220px;
  height: 34px;
}
.clean-search-box .search-icon {
  position: absolute;
  left: 0.75rem;
  color: #94a3b8;
  font-size: 0.75rem;
  pointer-events: none;
}
.clean-search-input {
  width: 100%;
  height: 34px;
  box-sizing: border-box;
  padding: 0 1.75rem 0 2rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  font-size: 0.75rem;
  background: #ffffff;
  color: #0f172a;
  outline: none;
}
.clean-search-input:focus { border-color: #2563eb; }
.clean-search-box .search-clear-btn {
  position: absolute;
  right: 0.375rem;
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
}
.history-select {
  height: 34px;
  padding: 0 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  font-size: 0.75rem;
  background: #ffffff;
  color: #0f172a;
  outline: none;
}

.empty-state-box {
  text-align: center;
  padding: 3rem 1.5rem;
  color: #64748b;
}
.empty-state-box i {
  font-size: 2.25rem;
  color: #cbd5e1;
  margin-bottom: 0.5rem;
  display: block;
}
.empty-state-box p { margin: 0; font-size: 0.8125rem; }

.empty-test-box {
  text-align: center;
  padding: 1.5rem 1rem;
  color: #64748b;
  background: #f8fafc;
  border-radius: 0.5rem;
}
.empty-test-box i { font-size: 1.75rem; color: #cbd5e1; margin-bottom: 0.5rem; display: block; }
.empty-test-box p { margin: 0; font-size: 0.8125rem; }

/* Modal */
.op-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  z-index: 9999;
}
.op-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.op-modal-box {
  background: #ffffff;
  border-radius: 1rem;
  width: 100%;
  max-width: 860px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  border: 1px solid #e2e8f0;
  overflow: hidden;
}
.op-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}
.tag-code {
  font-size: 0.875rem;
  color: #2563eb;
  font-weight: 700;
  background: #eff6ff;
  padding: 0.15rem 0.4rem;
  border-radius: 0.25rem;
}
.modal-close-btn {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: #64748b;
  cursor: pointer;
}
.modal-close-btn:hover { color: #0f172a; }
.op-modal-body {
  padding: 1.25rem;
  overflow-y: auto;
  flex: 1;
}
.op-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 0.875rem 1.25rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}

.detail-meta-bar {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  font-size: 0.8125rem;
  color: #64748b;
  margin-bottom: 0.75rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f1f5f9;
}
.detail-alert-box {
  padding: 0.625rem 0.875rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}
.alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.alert-warning { background: #fefce8; color: #854d0e; border: 1px solid #fef08a; }

.payloads-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.payload-panel {
  border: 1px solid #e2e8f0;
  border-radius: 0.625rem;
  overflow: hidden;
}
.payload-head {
  padding: 0.5rem 0.75rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.75rem;
  font-weight: 700;
  color: #0f172a;
}
.payload-pre {
  padding: 0.75rem;
  margin: 0;
  font-size: 0.75rem;
  font-family: 'SF Mono', 'Fira Code', Consolas, monospace;
  color: #1e293b;
  max-height: 280px;
  overflow: auto;
  line-height: 1.5;
  background: #ffffff;
}

.font-mono { font-family: 'SF Mono', 'Fira Code', Consolas, monospace !important; }

@media (max-width: 900px) {
  .payloads-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .op-header-wrap { flex-direction: column; align-items: stretch; }
  .op-main-tabs { width: 100%; overflow-x: auto; }
  .card-head { flex-direction: column; align-items: stretch; }
  .history-toolbar { width: 100%; }
  .clean-search-box { width: 100%; }
  .history-select { width: 100%; }
}
</style>
