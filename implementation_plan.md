# Implementasi Tahap 1: 1-Click Quick Pay & Smart FinTech Dashboard Mahasiswa

Rencana perombakan menyeluruh pada antarmuka Mahasiswa, khususnya Dashboard dan alur pembayaran tagihan UKT agar menjadi lebih modern, mewah (*FinTech aesthetic*), dan mengusung konsep **Sekali Klik (1-Click Payment)**.

---

## User Review Required

> [!IMPORTANT]
> **Alur 1-Click Quick Pay:**
> Mahasiswa tidak perlu lagi melewati 4–5 halaman untuk membayar UKT. Cukup dari **Dashboard**, kartu tagihan utama akan menampilkan tombol **"⚡ Bayar Instan"**.
> - Jika sudah ada VA aktif yang belum kedaluwarsa, Drawer langsung menampilkan Nomor VA + countdown + tombol Salin.
> - Jika belum ada, modal/drawer instan memungkinkan pembuatan VA hanya dalam 1 klik konfirmasi tanpa meninggalkan dashboard.

---

## Proposed Changes

### Backend (Laravel)

#### [MODIFY] [DashboardController.php](file:///c:/Users/MyBook%20Prime/Documents/Projek/ukt.ubg.ac.id/app/Http/Controllers/Mahasiswa/DashboardController.php)
- Memperkaya data yang dikirim ke `Mahasiswa/Dashboard.vue`:
  - `activeTagihan`: Tagihan semester aktif yang belum lunas (lengkap dengan data beasiswa & pending VA jika ada).
  - `metodePembayarans`: Daftar metode pembayaran aktif (VA NTB Syariah, Transfer, dll.).
  - `mahasiswa`: Info lengkap mahasiswa (NIM, Nama, Prodi, Fakultas) untuk kebutuhan pembayaran instan.
  - `semesterAktif`: Info semester dan batas jatuh tempo dari `SemesterAktif::instance()`.
  - `stats`: Diperluas dengan total nominal belum bayar dan nominal sudah lunas.

---

### Frontend (Vue 3 + Tailwind/CSS)

#### [NEW] [QuickPayDrawer.vue](file:///c:/Users/MyBook%20Prime/Documents/Projek/ukt.ubg.ac.id/resources/js/Components/QuickPayDrawer.vue)
- Komponen slide-over / interactive drawer modern untuk pembayaran instan 1-klik:
  - Tampilan ringkasan tagihan & beasiswa.
  - Pilihan metode pembayaran modern dengan ikon & warna bank.
  - VA Display dengan tombol *1-Click Copy* (disertai feedback visual *"Tersalin!"*).
  - Live Countdown timer sisa batas waktu VA.
  - Petunjuk pembayaran tabbed (*Mobile Banking*, *ATM*, *Internet Banking*).
  - Auto-check status pembayaran secara reactive di background.

#### [MODIFY] [Dashboard.vue](file:///c:/Users/MyBook%20Prime/Documents/Projek/ukt.ubg.ac.id/resources/js/Pages/Mahasiswa/Dashboard.vue)
- **Smart FinTech Hero Card**:
  - Desain kartu virtual debit/bill modern dengan gradien mewah, chip icon, info semester, nominal, dan progress bar sisa hari jatuh tempo.
  - Tombol aksi utama **`[ ⚡ Bayar Instan ]`** (membuka QuickPayDrawer) jika belum lunas, atau badge **`[ ✓ UKT Lunas ]`** dengan tombol cepat download invoice jika sudah dibayar.
- **Modern Quick Stat Cards**:
  - Statistik finansial modern dengan subtle shadows, ikon rounded, dan badge status glowing.
- **Interactive Quick Action Bar**:
  - Pintasan cepat: *Bayar UKT*, *Ajukan Dispensasi*, *Riwayat Transaksi*, *Unduh Invoice Terakhir*.
- **Recent Tagihan & Riwayat Feed**:
  - Daftar tagihan dengan segmented pill filter dan tombol aksi langsung (*Bayar Instan* / *Lihat Bukti*).

#### [MODIFY] [Index.vue (Tagihan)](file:///c:/Users/MyBook%20Prime/Documents/Projek/ukt.ubg.ac.id/resources/js/Pages/Mahasiswa/Tagihan/Index.vue)
- Integrasi `QuickPayDrawer` pada halaman daftar tagihan agar tombol "Bayar" di tabel atau card langsung memunculkan drawer 1-klik tanpa reload/pindah halaman panjang.

---

## Verification Plan

### Automated Tests
- Menjalankan linter / vite build check:
  ```powershell
  npm run build
  ```

### Manual Verification
1. Login sebagai akun Mahasiswa.
2. Akses halaman `/mahasiswa/dashboard`.
3. Verifikasi tampilan **Smart FinTech Card** dan informasi tagihan semester aktif.
4. Klik tombol **"⚡ Bayar Instan"**:
   - Pastikan *QuickPayDrawer* muncul dengan animasi halus tanpa reload halaman.
   - Pilih metode VA / konfirmasi dan verifikasi nomor VA langsung terbit.
   - Coba tombol salin nomor VA dan pastikan notifikasi sukses muncul.
5. Verifikasi tampilan responsif pada layar desktop maupun smartphone (mobile view).
