<script setup>
import { ref, computed, watch } from 'vue';
import { Link } from '@inertiajs/vue3';

/**
 * DataTable Global — reusable untuk Mahasiswa, Tagihan, Pembayaran, dll
 *
 * Cara pakai:
 * <DataTable
 *   :columns="[
 *     { key:'nim', label:'NIM', sortable:true },
 *     { key:'nama', label:'Nama' },
 *     { key:'aksi', label:'Aksi', sortable:false }
 *   ]"
 *   :data="mahasiswas"            // bisa Array atau Paginated Object {data, links, from, to, total}
 *   searchable
 *   search-placeholder="Cari NIM/Nama..."
 *   :per-page="10"
 *   @search="(q)=>router.get(route('admin.mahasiswa.index'), {search:q})"
 * >
 *   <template #cell-nim="{ row }"><b class="font-mono">{{ row.nim }}</b></template>
 *   <template #cell-aksi="{ row }"><Link :href="...">Detail</Link></template>
 *   <template #empty>Tidak ada data.</template>
 * </DataTable>
 *
 * Props:
 * - columns: Array<{key,label,sortable,width,align}>
 * - data: Array | {data:Array, links, from, to, total} (support pagination server)
 * - searchable: Boolean
 * - searchPlaceholder: String
 * - loading: Boolean
 * - perPage: Number (client pagination)
 * - serverMode: Boolean (auto-detect jika data.links ada)
 */

const props = defineProps({
    columns: { type: Array, required: true },
    data: { type: [Array, Object], required: true },
    loading: { type: Boolean, default: false },
    searchable: { type: Boolean, default: false },
    searchPlaceholder: { type: String, default: 'Cari...' },
    perPage: { type: Number, default: 10 },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
    serverMode: { type: Boolean, default: null },
    emptyText: { type: String, default: 'Tidak ada data ditemukan.' },
});

const emit = defineEmits(['search', 'sort', 'page-change', 'per-page-change']);

const searchQuery = ref('');
const sortKey = ref('');
const sortAsc = ref(true);
const currentPage = ref(1);
const perPageInternal = ref(props.perPage);

const isServerMode = computed(() => {
    if (props.serverMode !== null) return props.serverMode;
    return props.data && typeof props.data === 'object' && Array.isArray(props.data.data) && 'links' in props.data;
});

const rawRows = computed(() => {
    if (Array.isArray(props.data)) return props.data;
    if (props.data && Array.isArray(props.data.data)) return props.data.data;
    return [];
});

let searchDebounce = null;
watch(searchQuery, (val) => {
    if (isServerMode.value) {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => emit('search', val), 350);
    }
});

const filteredRows = computed(() => {
    if (isServerMode.value) return rawRows.value;
    if (!props.searchable || !searchQuery.value) return rawRows.value;
    const q = searchQuery.value.toLowerCase();
    return rawRows.value.filter(row =>
        props.columns.some(col => {
            const v = row[col.key];
            return v !== null && String(v).toLowerCase().includes(q);
        })
    );
});

const sortedRows = computed(() => {
    if (isServerMode.value) return filteredRows.value;
    if (!sortKey.value) return filteredRows.value;
    const key = sortKey.value;
    const dir = sortAsc.value ? 1 : -1;
    return [...filteredRows.value].sort((a,b) => {
        const av = a[key] ?? '';
        const bv = b[key] ?? '';
        if (av === bv) return 0;
        // numeric compare if both numbers
        if (!isNaN(av) && !isNaN(bv)) return (Number(av) - Number(bv)) * dir;
        return String(av).localeCompare(String(bv)) * dir;
    });
});

const totalRows = computed(() => isServerMode.value ? (props.data.total ?? filteredRows.value.length) : filteredRows.value.length);
const totalPages = computed(() => Math.ceil(totalRows.value / perPageInternal.value) || 1);

const paginatedRows = computed(() => {
    if (isServerMode.value) return sortedRows.value;
    const start = (currentPage.value - 1) * perPageInternal.value;
    return sortedRows.value.slice(start, start + perPageInternal.value);
});

watch(perPageInternal, () => { currentPage.value = 1; emit('per-page-change', perPageInternal.value); });
watch(() => props.data, () => { currentPage.value = 1; });

const onSort = (key, sortable) => {
    if (sortable === false) return;
    if (isServerMode.value) {
        // toggle asc if same key
        if (sortKey.value === key) sortAsc.value = !sortAsc.value;
        else { sortKey.value = key; sortAsc.value = true; }
        emit('sort', { key: sortKey.value, direction: sortAsc.value ? 'asc' : 'desc' });
        return;
    }
    if (sortKey.value === key) sortAsc.value = !sortAsc.value;
    else { sortKey.value = key; sortAsc.value = true; }
};

const goPage = (p) => {
    if (p < 1 || p > totalPages.value) return;
    currentPage.value = p;
    emit('page-change', p);
};

const serverLinks = computed(() => {
    if (!isServerMode.value) return [];
    return props.data.links || [];
});
const serverFrom = computed(() => props.data.from ?? (paginatedRows.value.length ? 1 : 0));
const serverTo = computed(() => props.data.to ?? paginatedRows.value.length);
const serverTotal = computed(() => props.data.total ?? totalRows.value);
</script>

<template>
    <div class="datatable">
        <!-- Toolbar -->
        <div v-if="searchable || $slots.toolbar" class="datatable-toolbar">
            <div v-if="searchable" class="input-group input-group--search datatable-search">
                <span class="input-group__text">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11.5" cy="11.5" r="9.5"/><path stroke-linecap="round" d="M18.5 18.5L22 22"/></g></svg>
                </span>
                <input type="search" class="input" v-model="searchQuery" :placeholder="searchPlaceholder" />
            </div>
            <div class="datatable-toolbar-actions">
                <slot name="toolbar" />
                <select v-if="!isServerMode" v-model.number="perPageInternal" class="filter-select" style="max-width:90px;">
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }} / hal</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="m-data-table">
                <thead>
                    <tr>
                        <th v-for="col in columns" :key="col.key" :style="{ width: col.width || 'auto', textAlign: col.align || 'left', cursor: col.sortable===false ? 'default' : 'pointer' }" @click="onSort(col.key, col.sortable)">
                            <span style="display:inline-flex;align-items:center;gap:0.35rem;">
                                {{ col.label }}
                                <i v-if="col.sortable!==false" class="fas" :class="sortKey===col.key ? (sortAsc ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort'" style="font-size:0.7rem;opacity:0.4;"></i>
                            </span>
                        </th>
                        <slot name="header-extra" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td :colspan="columns.length + ($slots['header-extra'] ? 1 : 0)" style="text-align:center;padding:2rem;">
                            <i class="fas fa-spinner fa-spin"></i> Memuat...
                        </td>
                    </tr>
                    <tr v-else-if="paginatedRows.length===0">
                        <td :colspan="columns.length + ($slots['header-extra'] ? 1 : 0)" style="text-align:center;padding:2rem;color:var(--gray-500);">
                            <slot name="empty">{{ emptyText }}</slot>
                        </td>
                    </tr>
                    <tr v-for="(row, idx) in paginatedRows" :key="row.id ?? idx">
                        <td v-for="col in columns" :key="col.key" :style="{ textAlign: col.align || 'left' }">
                            <slot :name="'cell-' + col.key" :row="row" :value="row[col.key]" :index="idx">
                                {{ row[col.key] ?? '-' }}
                            </slot>
                        </td>
                        <slot name="row-extra" :row="row" />
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-wrap" v-if="totalRows>0">
            <span class="pagination-info">
                <template v-if="isServerMode">
                    Menampilkan {{ serverFrom }}-{{ serverTo }} dari {{ serverTotal }} data
                </template>
                <template v-else>
                    Menampilkan {{ (currentPage-1)*perPageInternal + 1 }}-{{ Math.min(currentPage*perPageInternal, totalRows) }} dari {{ totalRows }} data
                </template>
            </span>

            <!-- Server pagination links -->
            <div v-if="isServerMode && serverLinks.length>3" class="pagination">
                <template v-for="link in serverLinks" :key="link.label">
                    <span v-if="!link.url" class="page-item disabled"><span class="page-link" v-html="link.label"></span></span>
                    <span v-else class="page-item" :class="{ active: link.active }"><Link :href="link.url" class="page-link" v-html="link.label" preserve-state /></span>
                </template>
            </div>

            <!-- Client pagination -->
            <div v-else-if="!isServerMode && totalPages>1" class="pagination">
                <button class="page-item page-link" :disabled="currentPage===1" @click="goPage(currentPage-1)">‹ Prev</button>
                <button v-for="p in totalPages" :key="p" class="page-item page-link" :class="{ active: p===currentPage }" @click="goPage(p)">{{ p }}</button>
                <button class="page-item page-link" :disabled="currentPage===totalPages" @click="goPage(totalPages)">Next ›</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.datatable-toolbar { display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap; justify-content:space-between; margin-bottom:1rem; }
.datatable-search { max-width:300px; flex:1; min-width:180px; }
.datatable-toolbar-actions { display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap; }
.table-responsive { width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; }
.table-responsive .m-data-table { min-width:600px; }
.pagination-wrap { display:flex; justify-content:space-between; align-items:center; padding:1rem 0 0; border-top:1px solid var(--gray-100); margin-top:0.75rem; gap:1rem; flex-wrap:wrap; }
.pagination-info { font-size:0.8125rem; color:var(--gray-600); }
.pagination { display:flex; gap:0.25rem; flex-wrap:wrap; }
.page-link[disabled] { opacity:0.5; cursor:not-allowed; }
@media (max-width:640px){
    .datatable-toolbar { flex-direction:column; align-items:stretch; }
    .datatable-search { max-width:100%; }
    .pagination-wrap { flex-direction:column; align-items:stretch; text-align:center; }
    .pagination { justify-content:center; }
}
</style>
