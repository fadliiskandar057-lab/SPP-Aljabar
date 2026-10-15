<script>
let verifyingPayment = false;
async function finishPayment() {
    if (verifyingPayment) return;
    verifyingPayment = true;
    const button = document.getElementById('pay-button');
    const originalLabel = button.innerHTML;
    button.disabled = true;
    button.textContent = 'Memverifikasi pembayaran…';
    try {
        for (let attempt = 0; attempt < 5; attempt++) {
            const response = await fetch({{ Illuminate\Support\Js::from($finishUrl) }}, {
                method: 'POST',
                cache: 'no-store',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': {{ Illuminate\Support\Js::from(csrf_token()) }},
                    'Accept': 'application/json'
                },
                body: '{}'
            });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Status pembayaran belum dapat diverifikasi.');
            }
            if (data.paid) {
                window.location.replace(data.redirect);
                return;
            }
            if (data.status !== 'pending') {
                throw new Error('Pembayaran belum berhasil. Periksa status transaksi sebelum mencoba lagi.');
            }
            if (attempt < 4) await new Promise(resolve => setTimeout(resolve, 1500));
        }
        throw new Error('Pembayaran masih menunggu konfirmasi. Klik Periksa Status Pembayaran untuk memeriksa kembali.');
    } catch (error) {
        alert(error.message || 'Verifikasi gagal. Silakan periksa kembali status pembayaran.');
        button.textContent = 'Periksa Status Pembayaran';
        button.onclick = finishPayment;
    } finally {
        verifyingPayment = false;
        button.disabled = false;
        if (button.textContent === 'Memverifikasi pembayaran…') button.innerHTML = originalLabel;
    }
}

document.getElementById('pay-button').onclick = function () {
    snap.pay({{ Illuminate\Support\Js::from($snapToken) }}, {
        onSuccess: finishPayment,
        onPending: finishPayment,
        onError: function () { alert('Pembayaran gagal. Silakan periksa transaksi.'); }
    });
};
</script>
