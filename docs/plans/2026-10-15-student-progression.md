# Student Progression Implementation Plan

> **For agentic workers:** Use the host's available task-by-task implementation workflow. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Admin dapat memproses kenaikan kelas/kelulusan dan mengatur tarif semester tanpa merusak riwayat SPP.

**Architecture:** Tambahkan metadata dan riwayat terpisah dari kelas aktif, serta tarif efektif per siswa. Semua pembentukan tagihan memakai satu resolver periode; preview batch disimpan sementara di session dan dikonfirmasi atomik.

**Tech Stack:** Laravel 12, PHP, Blade/Bootstrap, MySQL/TiDB, PHPUnit.

## Global Constraints
Angkatan tetap, alumni tidak dihapus, tagihan lama tidak diubah nominalnya, pembayaran masa depan yang sudah lunas dihormati, dan satu perpindahan per siswa/tahun ajaran tujuan. Hanya admin TU dapat menulis. Preview tidak menulis database. Efektif kenaikan paling lambat bulan berjalan.

### Task 1: Metadata, riwayat, tarif periode
**Files:** migration `2026_10_15_000002_add_student_progression.php`; models `Kelas`, `Siswa`, `Tagihan`, `StudentProgression`, `StudentFeeRate`; service `StudentFeeResolver`; tests `StudentProgressionTest`.
**Interfaces:** `StudentFeeResolver::nominal(Siswa, int $yearId, Carbon $period): ?int`, `className(Siswa, Carbon): string`.
- [x] Add failing tests for period-based tariff, immutable old bills and class names.
- [x] Run `php vendor/phpunit/phpunit/phpunit --filter StudentProgressionTest` against isolated local MySQL; expect absent interfaces/schema.
- [x] Add schema, casts, relations and effective-period resolver with legacy class-fee fallback.
- [x] Verify focused tests.

### Task 2: Atomic preview and confirmation
**Files:** `StudentProgressionController`, `StudentProgressionService`, routes, tests `StudentProgressionTest`.
**Interfaces:** `preview(array): array`, `confirm(array, User): int`; preview session token scoped to admin.
- [x] Add tests for mixed promote/retain/graduate, idempotency, stale snapshots, inactive pupils, and authorization.
- [x] Implement snapshot validation, atomic class updates, preserved or explicit rates and audit records; reject a changed snapshot before any write.
- [x] Verify focused tests and failure leaves all students unchanged.

### Task 3: Admin UI and billing integration
**Files:** `admin/progression.blade.php`, preview, student forms/list, class settings, rate form, layout menu; monthly/future billing services, AdminController fee helper, invoice views.
**Interfaces:** admin GET/POST preview, POST confirm, POST student-fee-rates; class edit includes educational metadata.
- [x] Build responsive class mapping with per-student exception selections, explicit totals and confirmation.
- [x] Route all bill creation/quotes through shared period resolver; snapshots show historic class.
- [x] Test semester changes, prepaid months, unchanged tariffs, graduated pupils and Blade rendering.
- [x] Run full PHPUnit suite, PHP syntax checks, Blade compilation and diff checks.

### Delivery
- [ ] Copy only feature files to the Git checkout, review diff, commit and push using existing authorization.
- [ ] Report supported behavior, test evidence and deployment limitations; do not claim a production deployment without evidence.
