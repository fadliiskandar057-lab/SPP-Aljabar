# Role Keuangan, Akun, Audit, dan Push Implementation Plan

> **For agentic workers:** Use the host's available task-by-task implementation workflow. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Memisahkan Admin Manajemen dan Bendahara, menyediakan audit/notifikasi lintas staf, laporan bertanda tangan Kepala Sekolah, pembayaran masa depan, akun mandiri, serta push notification PWA.

**Architecture:** Pertahankan `admin_tu` sebagai nilai role lama namun gunakan label Admin Manajemen; tambahkan `bendahara` dan kelompok route terpisah untuk membatasi aksi di server. Semua aksi sensitif melewati `ActivityLogger`, yang menyimpan audit permanen serta meneruskan notifikasi aplikasi dan Web Push ke staf berwenang. PWA service worker menerima Web Push dari backend menggunakan subscription per perangkat.

**Tech Stack:** Laravel 12/PHP 8.3, MySQL, Blade, DomPDF, Laravel notifications/mail/password broker, Service Worker/Push API, dan `minishlink/web-push` untuk VAPID.

## Global Constraints

- Admin Manajemen boleh mengelola data master dan akun, namun tidak dapat memproses atau membatalkan transaksi.
- Bendahara boleh mengelola operasional pembayaran dan laporan, namun tidak dapat mengubah data master, konfigurasi, atau akun pengguna.
- Kepala Sekolah hanya membaca laporan dan aktivitas serta menjadi penanda tangan laporan; Wali Kelas dan Siswa mempertahankan batas akses yang ada.
- Pembatalan pembayaran harus mencatat alasan wajib, pelaku, waktu, siswa/NIS, dan invoice.
- Aktivitas sensitif dibagikan kepada Admin Manajemen, Bendahara, dan Kepala Sekolah; pemberitahuan tidak menambah wewenang penerima.
- Nama/NIP penanda tangan laporan diambil dari satu akun Kepala Sekolah bertanda `is_report_signer`.
- Push hanya dikirim setelah pengguna memberi izin dan menyimpan subscription; produksi harus HTTPS dan memiliki VAPID key.

---

### Task 1: Fondasi data, role, dan test harness

**Files:**
- Create: `database/migrations/2026_10_07_000001_add_finance_roles_and_account_fields.php`
- Create: `database/migrations/2026_10_07_000002_create_activity_logs_table.php`
- Create: `database/migrations/2026_10_07_000003_create_push_subscriptions_table.php`
- Create: `app/Models/ActivityLog.php`
- Create: `app/Models/PushSubscription.php`
- Modify: `app/Models/User.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `composer.json`, `composer.lock`
- Create: `tests/TestCase.php`, `tests/Feature/RoleAndSchemaTest.php`

**Interfaces:**
- Consumes: existing `users.role` enum and `users` account records.
- Produces: `User::isManagementStaff(): bool`, `ActivityLog` polymorphic subject fields, `PushSubscription` relation, `users.email`, `users.nip`, and `users.is_report_signer`.

- [ ] **Step 1: Add the focused failing test**

Create Laravel test bootstrap and feature tests that migrate SQLite/MySQL test schema, assert `bendahara` can be created, email is unique, only one report signer can be selected through the application service, and activity/subscription records belong to their users.

- [ ] **Step 2: Verify the relevant failure**

Run: `php artisan test --filter=RoleAndSchemaTest`
Expected: failure because models, migrations, test bootstrap, and the `bendahara` enum value do not exist.

- [ ] **Step 3: Implement the minimum behavior**

Add nullable unique `email`, nullable `nip`, and boolean `is_report_signer` to users; migrate the MySQL role enum to include `bendahara`; add `activity_logs` with nullable actor FK, action, subject type/ID, summary, JSON metadata, IP address, and timestamps; add subscriptions keyed by user, endpoint, public key, auth token, encoding, and timestamps. Add relations/fillable/casts to models. Add a transaction-backed signer setter that clears other `kepala_sekolah` signers before enabling one. Add a seeded Bendahara and install `minishlink/web-push`.

- [ ] **Step 4: Verify the focused pass**

Run: `php artisan test --filter=RoleAndSchemaTest`
Expected: all schema and relation assertions pass.

- [ ] **Step 5: Run the affected integration check**

Run: `php artisan migrate:fresh --seed`
Expected: migrations complete and seeded accounts include Admin Manajemen, Bendahara, Kepala Sekolah, Wali Kelas, and Siswa.

- [ ] **Step 6: Commit the passing deliverable**

```bash
git add composer.json composer.lock database/migrations app/Models database/seeders tests
git commit -m "feat: add finance role and audit data model"
```

### Task 2: Server-enforced separation of management and treasury routes

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Middleware/RoleMiddleware.php`
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/dashboards/admin.blade.php`
- Create: `tests/Feature/RoleAuthorizationTest.php`

**Interfaces:**
- Consumes: `role` middleware and role values from Task 1.
- Produces: `management.*` routes for master/account management; `treasury.*` routes for payment, arrears, reports, and invoices; role-specific navigation.

- [ ] **Step 1: Add the focused failing test**

Create route tests authenticating each staff role. Assert Admin Manajemen receives 403 for every treasury mutation and report route; Bendahara receives 403 for student/class/fee/settings/user mutations; Bendahara receives 200 for transaction, cash-queue, arrears, and report pages; Kepala Sekolah remains read-only.

- [ ] **Step 2: Verify the relevant failure**

Run: `php artisan test --filter=RoleAuthorizationTest`
Expected: failure because the existing `admin.*` group grants all operational routes to `admin_tu` and Bendahara routes do not exist.

- [ ] **Step 3: Implement the minimum behavior**

Split the existing admin route group without moving unrelated controller logic: master/student/class/year/fee/settings/user routes use `role:admin_tu` and `management.` names; payment/arrears/cash/manual payment/cancel/report/PDF/Excel/invoice routes use `role:bendahara` and `treasury.` names. Permit invoice authorization for Bendahara. Route dashboards by role and render distinct sidebars. Convert every displayed `Admin TU` label to `Admin Manajemen`; show only Bendahara navigation to Bendahara.

- [ ] **Step 4: Verify the focused pass**

Run: `php artisan test --filter=RoleAuthorizationTest`
Expected: staff endpoints return the expected 200/302 responses for the correct role and 403 for the other role.

- [ ] **Step 5: Run the affected integration check**

Run: `php artisan route:list --path=manajemen; php artisan route:list --path=bendahara`
Expected: no payment-changing route appears under management and no master/account-changing route appears under treasury.

- [ ] **Step 6: Commit the passing deliverable**

```bash
git add routes/web.php app/Http/Middleware app/Http/Controllers/DashboardController.php resources/views/layouts/app.blade.php resources/views/dashboards tests/Feature/RoleAuthorizationTest.php
git commit -m "feat: separate management and treasury access"
```

### Task 3: Permanent activity logging and staff notifications

**Files:**
- Create: `app/Services/ActivityLogger.php`
- Modify: `app/Services/WebNotificationService.php`
- Modify: `app/Http/Controllers/AdminController.php`
- Modify: `app/Http/Controllers/SiswaPortalController.php`
- Modify: `app/Http/Controllers/MidtransWebhookController.php`
- Create: `app/Http/Controllers/ActivityLogController.php`
- Create: `resources/views/activities/index.blade.php`
- Modify: `routes/web.php`, `resources/views/layouts/app.blade.php`
- Create: `tests/Feature/ActivityLogTest.php`

**Interfaces:**
- Consumes: `ActivityLogger::record(User $actor, string $action, Model $subject, string $summary, array $metadata = []): ActivityLog`.
- Produces: immutable audit rows, in-app notifications for management staff, and `activities.index` read route.

- [ ] **Step 1: Add the focused failing test**

Create tests for a Bendahara manual-payment cancellation: missing `reason` returns validation error; a valid reason changes the payment status, creates one audit row with actor/action/subject/reason, and creates notifications for all three staff roles. Add a master-data update test proving Admin Manajemen emits an activity visible to Bendahara and Kepala Sekolah.

- [ ] **Step 2: Verify the relevant failure**

Run: `php artisan test --filter=ActivityLogTest`
Expected: failure because no activity logger, activity route, or cancellation reason exists.

- [ ] **Step 3: Implement the minimum behavior**

Implement `ActivityLogger` in a database transaction: serialize only whitelisted before/after fields, record request IP, fan out existing notifications to `admin_tu`, `bendahara`, and `kepala_sekolah`, and link recipients to the activity page. Call it after successful master changes, account changes, billing configuration/exemption changes, manual/cash confirmations, Midtrans state transitions, cancellations, reopening bills, and report exports. Add `reason` validation (`required|string|max:1000`) to all staff cancellation/reopen flows and include it in the log/notification. Activity list is read-only and paginated for the three management-staff roles.

- [ ] **Step 4: Verify the focused pass**

Run: `php artisan test --filter=ActivityLogTest`
Expected: required reason, immutable row data, and recipient notifications all pass.

- [ ] **Step 5: Run the affected integration check**

Run: `php artisan test --filter='ActivityLogTest|RoleAuthorizationTest'`
Expected: audit additions do not allow a role to call another role's mutation route.

- [ ] **Step 6: Commit the passing deliverable**

```bash
git add app/Services app/Http/Controllers resources/views/activities routes/web.php resources/views/layouts/app.blade.php tests/Feature/ActivityLogTest.php
git commit -m "feat: audit sensitive staff actions"
```

### Task 4: Treasury reports, signer, and forward-period payments

**Files:**
- Modify: `app/Http/Controllers/AdminController.php`
- Modify: `resources/views/admin/laporan.blade.php`
- Modify: `resources/views/reports/pdf.blade.php`
- Modify: `resources/views/admin/student_arrears.blade.php`
- Modify: `resources/views/admin/payments.blade.php`
- Create: `app/Services/FuturePaymentService.php`
- Create: `tests/Feature/TreasuryReportAndFuturePaymentTest.php`

**Interfaces:**
- Consumes: Bendahara routes, `User::where('role', 'kepala_sekolah')->where('is_report_signer', true)`, Tagihan/Pembayaran relations.
- Produces: `FuturePaymentService::createAndSettleUntil(Siswa $siswa, Carbon $lastPeriod, Carbon $paidAt, User $actor): Collection`, signer-aware report PDF.

- [ ] **Step 1: Add the focused failing test**

Test a Bendahara report request for a start/end month/year and assert the PDF response contains the selected period, signer name, and NIP. Test no signer produces a validation response. Test a future payment through a selected final month creates exactly one tagihan/payment per missing month, marks each bill paid, and a repeat submission creates no duplicates.

- [ ] **Step 2: Verify the relevant failure**

Run: `php artisan test --filter=TreasuryReportAndFuturePaymentTest`
Expected: failure because signer information is hard-coded and no future-payment service or form exists.

- [ ] **Step 3: Implement the minimum behavior**

Retain the observed `bulan_awal/tahun_awal/bulan_akhir/tahun_akhir` filtering and pass an active signer into PDF rendering. Replace hard-coded signature text with Kepala Sekolah name/NIP. Add Bendahara form controls for student, final month/year, method, and paid date. The service validates that final period is not before the earliest unsettled/current bill, chooses fee using existing class/year rules, uses `firstOrCreate` per student/month/year, refuses zero/missing fees, creates individual success payments with `verified_by`, and logs the grouped action. Do not create bills for inactive students or overwrite `lunas`/`gratis` bills.

- [ ] **Step 4: Verify the focused pass**

Run: `php artisan test --filter=TreasuryReportAndFuturePaymentTest`
Expected: filtered signed PDF and idempotent monthly future settlement tests pass.

- [ ] **Step 5: Run the affected integration check**

Run: `php artisan test --filter='TreasuryReportAndFuturePaymentTest|ActivityLogTest'`
Expected: report exports and grouped payments yield audit entries without duplicated bills.

- [ ] **Step 6: Commit the passing deliverable**

```bash
git add app/Http/Controllers/AdminController.php app/Services/FuturePaymentService.php resources/views/admin resources/views/reports/pdf.blade.php tests/Feature/TreasuryReportAndFuturePaymentTest.php
git commit -m "feat: add signed treasury reports and future payments"
```

### Task 5: Self-service profiles and password reset

**Files:**
- Modify: `app/Http/Controllers/AuthController.php`
- Create: `app/Http/Controllers/ProfileController.php`
- Modify: `routes/web.php`
- Create: `resources/views/auth/forgot-password.blade.php`
- Create: `resources/views/auth/reset-password.blade.php`
- Create: `resources/views/profile/edit.blade.php`
- Modify: `resources/views/auth/login.blade.php`, `resources/views/layouts/app.blade.php`
- Create: `tests/Feature/AccountSecurityTest.php`

**Interfaces:**
- Consumes: Laravel `Password` broker, `User` email fields, authenticated user session.
- Produces: `profile.edit`, `profile.update`, `profile.password.update`, `password.request`, `password.email`, `password.reset`, `password.store` routes.

- [ ] **Step 1: Add the focused failing test**

Test authenticated users can change name/email after supplying current password, cannot claim another user's email, cannot change password with a wrong current password, and can log in with the new password. Test a password-reset request with an existing email creates a token and queues/sends the reset notification; unknown emails get the same generic confirmation response.

- [ ] **Step 2: Verify the relevant failure**

Run: `php artisan test --filter=AccountSecurityTest`
Expected: failure because account profile and password-broker routes/views are absent.

- [ ] **Step 3: Implement the minimum behavior**

Use Laravel password broker endpoints and built-in reset token validation. Profile updates accept `name` and unique `email`; password update requires `current_password`, a minimum-8-character confirmed new password, and invalidates other sessions after success. Do not expose or edit student academic/contact fields from this generic profile form. Add links from login and the authenticated profile menu. Record profile/password changes in activity logs only for management-staff users; never persist password values or reset tokens in log metadata.

- [ ] **Step 4: Verify the focused pass**

Run: `php artisan test --filter=AccountSecurityTest`
Expected: profile, password, uniqueness, and reset-token assertions pass.

- [ ] **Step 5: Run the affected integration check**

Run: `php artisan test --filter='AccountSecurityTest|RoleAuthorizationTest'`
Expected: every role can access only its own profile and reset lifecycle remains independent of role.

- [ ] **Step 6: Commit the passing deliverable**

```bash
git add app/Http/Controllers/AuthController.php app/Http/Controllers/ProfileController.php routes/web.php resources/views/auth resources/views/profile resources/views/layouts/app.blade.php tests/Feature/AccountSecurityTest.php
git commit -m "feat: add self-service profiles and password reset"
```

### Task 6: PWA and Web Push delivery

**Files:**
- Create: `public/manifest.webmanifest`
- Create: `public/service-worker.js`
- Create: `public/images/icon-192.png`, `public/images/icon-512.png`
- Create: `app/Services/WebPushService.php`
- Create: `app/Http/Controllers/PushSubscriptionController.php`
- Modify: `routes/web.php`, `resources/views/layouts/app.blade.php`, `resources/views/profile/edit.blade.php`
- Modify: `app/Services/ActivityLogger.php`, `.env.example`, `config/services.php`
- Create: `tests/Feature/PushSubscriptionTest.php`

**Interfaces:**
- Consumes: browser `PushManager.subscribe()`, `POST /push-subscriptions`, `DELETE /push-subscriptions/{subscription}`, VAPID environment variables.
- Produces: `WebPushService::sendToUsers(Collection $users, string $title, string $body, string $url): void`, browser push subscription persistence, clickable push notifications.

- [ ] **Step 1: Add the focused failing test**

Test an authenticated user can create/update a subscription for the same endpoint, cannot delete another user's subscription, and disabling push deletes their own subscription. Mock `WebPushService` and assert `ActivityLogger` queues a push payload only for subscribed activity recipients.

- [ ] **Step 2: Verify the relevant failure**

Run: `php artisan test --filter=PushSubscriptionTest`
Expected: failure because service worker, endpoints, subscription model wiring, and push sender do not exist.

- [ ] **Step 3: Implement the minimum behavior**

Add manifest metadata/icons and service-worker installation from the shared layout. In a secure context, profile UI asks permission only after an explicit user click, obtains VAPID public key from an endpoint/config, and posts the subscription. Store endpoint/public key/auth/encoding idempotently per user. `WebPushService` sends title/body/url using VAPID keys, removes subscriptions rejected as gone/expired, and records server delivery failures without exposing keys. The service worker shows the notification and focuses or opens the authenticated target URL on click. Use `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, and `VAPID_SUBJECT` in `.env.example`; production must set them and serve HTTPS.

- [ ] **Step 4: Verify the focused pass**

Run: `php artisan test --filter=PushSubscriptionTest`
Expected: subscription ownership/upsert/opt-out and mocked push-recipient tests pass.

- [ ] **Step 5: Run the affected integration check**

Run: `php artisan test`
Expected: all feature tests pass; manually load the site over HTTPS, enable push, close the tab, trigger a staff activity, and observe one notification whose click opens the activity/object page.

- [ ] **Step 6: Commit the passing deliverable**

```bash
git add public/manifest.webmanifest public/service-worker.js public/images app/Services/WebPushService.php app/Http/Controllers/PushSubscriptionController.php app/Services/ActivityLogger.php routes/web.php resources/views/layouts/app.blade.php resources/views/profile/edit.blade.php config/services.php .env.example tests/Feature/PushSubscriptionTest.php
git commit -m "feat: add PWA push notifications"
```

## Unresolved externally observable decisions

- Email delivery for password-reset links requires production SMTP/API credentials; without them Laravel can record mail locally but users will not receive reset emails.
- Android delivery uses browser/PWA Web Push, not a native Play Store application. Users who deny permission, disable notifications, use a non-supporting browser, or force-stop the browser will not receive background alerts.
- Existing successful online Midtrans payments remain non-cancelable by a staff member; only the current manual-payment cancellation path is expanded. Allowing reversals/refunds of settled online payments would require a separate Midtrans refund policy and integration.
