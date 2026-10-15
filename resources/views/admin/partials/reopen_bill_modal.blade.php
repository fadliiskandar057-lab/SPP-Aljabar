<div class="modal fade reopen-bill-modal" id="reopenBillModal" tabindex="-1" aria-labelledby="reopenBillTitle" aria-describedby="reopenBillDescription" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="reopenBillForm" method="post">
                @csrf
                <div class="modal-header align-items-start border-0 pb-0">
                    <div class="d-flex gap-3 align-items-center">
                        <span class="reopen-bill-icon" aria-hidden="true"><i class="bi bi-arrow-counterclockwise"></i></span>
                        <div>
                            <span class="reopen-bill-eyebrow">Koreksi pembayaran</span>
                            <h5 class="modal-title" id="reopenBillTitle">Buka ulang tagihan?</h5>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body pt-3">
                    <p id="reopenBillDescription" class="text-muted small mb-3">Periksa tagihan berikut sebelum mengembalikannya ke daftar tunggakan.</p>
                    <div class="reopen-bill-details">
                        <div class="reopen-bill-student">
                            <span class="small text-muted">Siswa</span>
                            <strong id="reopenBillStudent"></strong>
                            <span id="reopenBillNis" class="small text-muted"></span>
                        </div>
                        <dl class="row g-2 mb-0 mt-1">
                            <dt class="col-6 small text-muted fw-normal">Periode tagihan</dt>
                            <dd id="reopenBillPeriod" class="col-6 small text-end fw-semibold mb-0"></dd>
                            <dt class="col-6 small text-muted fw-normal">Nominal</dt>
                            <dd id="reopenBillAmount" class="col-6 text-end fw-bold mb-0"></dd>
                        </dl>
                    </div>
                    <div class="reopen-bill-impact mt-3" id="reopenBillImpact">
                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                        <div><strong class="small">Tagihan akan kembali belum lunas</strong>
                            <p class="small mb-0 mt-1">Konfirmasi ini membatalkan transaksi manual terakhir yang berhasil, jika ada. Tagihan kembali masuk tunggakan siswa.</p>
                        </div>
                    </div>
                    <div class="alert alert-warning small mt-3 mb-0" id="reopenBillBlocked" hidden>
                        Tagihan ini memiliki pembayaran tunai atau Midtrans yang sudah sah. Lakukan koreksi melalui <a href="{{ route('treasury.payments') }}" class="alert-link">Transaksi Pembayaran</a>.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button id="reopenBillSubmit" type="submit" class="btn btn-warning fw-semibold" disabled><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Ya, Buka Ulang</button>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    .reopen-bill-modal [hidden] { display:none !important; }
    .reopen-bill-modal .modal-content { border:0; border-radius:16px; box-shadow:0 24px 64px rgba(16,24,40,.18); }
    .reopen-bill-modal .modal-header { padding:1.5rem 1.5rem 0; }
    .reopen-bill-modal .modal-body { padding:1.5rem; }
    .reopen-bill-modal .modal-footer { padding:0 1.5rem 1.5rem; }
    .reopen-bill-icon { display:grid; place-items:center; width:48px; height:48px; flex-shrink:0; border-radius:12px; background:#fff3d6; color:#875400; font-size:1.5rem; }
    .reopen-bill-eyebrow { display:block; margin-bottom:.2rem; color:#667085; font-size:.72rem; font-weight:700; }
    .reopen-bill-modal .modal-title { color:#172b4d; font-weight:750; }
    .reopen-bill-details { padding:1rem; border:1px solid #e4e7ec; border-radius:10px; background:#f9fafb; }
    .reopen-bill-student { display:grid; gap:.2rem; padding-bottom:.8rem; margin-bottom:.6rem; border-bottom:1px solid #e4e7ec; overflow-wrap:anywhere; }
    .reopen-bill-impact { display:flex; align-items:flex-start; gap:.7rem; padding:.9rem; border:1px solid #f4d99d; border-radius:10px; color:#704b0a; background:#fffbf0; }
    .reopen-bill-impact > i { flex-shrink:0; margin-top:.1rem; }
    .reopen-bill-modal .modal-footer .btn { margin:0; min-height:42px; }
    @media (max-width:400px) { .reopen-bill-modal .modal-footer { flex-wrap:wrap; } .reopen-bill-modal .modal-footer .btn { flex:1 1 auto; } }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('reopenBillModal');
    const form = document.getElementById('reopenBillForm');
    const submit = document.getElementById('reopenBillSubmit');
    const originalLabel = submit.innerHTML;
    modal.addEventListener('show.bs.modal', event => {
        const trigger = event.relatedTarget;
        form.removeAttribute('action');
        submit.disabled = true;
        submit.innerHTML = originalLabel;
        if (!trigger) return;
        const blocked = trigger.dataset.blocked === '1';
        document.getElementById('reopenBillStudent').textContent = trigger.dataset.student;
        document.getElementById('reopenBillNis').textContent = 'NIS ' + trigger.dataset.nis;
        document.getElementById('reopenBillPeriod').textContent = trigger.dataset.period;
        document.getElementById('reopenBillAmount').textContent = trigger.dataset.amount;
        document.getElementById('reopenBillImpact').hidden = blocked;
        document.getElementById('reopenBillBlocked').hidden = !blocked;
        if (!blocked && trigger.dataset.reopenUrl) {
            form.setAttribute('action', trigger.dataset.reopenUrl);
            submit.disabled = false;
        }
    });
    form.addEventListener('submit', event => {
        if (submit.disabled || !form.getAttribute('action')) {
            event.preventDefault();
            return;
        }
        submit.disabled = true;
        submit.textContent = 'Membuka ulang…';
    });
});
</script>
