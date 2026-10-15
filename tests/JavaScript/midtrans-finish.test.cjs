const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../resources/views/partials/midtrans_finish_script.blade.php'), 'utf8')
    .replace(/<\/?script>/g, '')
    .replace(/\{\{[\s\S]*?\}\}/g, '"test-value"');

async function verify(responses) {
    const redirects = [];
    const alerts = [];
    const button = { innerHTML: 'Bayar', textContent: 'Bayar', disabled: false };
    let calls = 0;
    const context = vm.createContext({
        document: { getElementById: () => button },
        window: { location: { replace: url => redirects.push(url) } },
        snap: { pay() {} },
        alert: message => alerts.push(message),
        setTimeout: callback => callback(),
        fetch: async () => {
            assert.equal(redirects.length, 0, 'Must not redirect while verification is pending');
            const result = responses[Math.min(calls++, responses.length - 1)];
            if (result instanceof Error) throw result;
            return { ok: result.ok !== false, json: async () => result };
        },
    });
    vm.runInContext(source, context);
    await vm.runInContext('finishPayment()', context);
    return { redirects, alerts, button, calls };
}

test('waits for verified success after a pending response', async () => {
    const result = await verify([
        { ok: true, status: 'pending', paid: false },
        { ok: true, status: 'settlement', paid: true, redirect: '/bendahara/tunggakan-siswa' },
    ]);
    assert.equal(result.calls, 2);
    assert.deepEqual(result.redirects, ['/bendahara/tunggakan-siswa']);
    assert.deepEqual(result.alerts, []);
});

test('remaining pending offers status retry without redirect', async () => {
    const result = await verify([{ ok: true, status: 'pending', paid: false }]);
    assert.equal(result.calls, 5);
    assert.deepEqual(result.redirects, []);
    assert.equal(result.button.textContent, 'Periksa Status Pembayaran');
    assert.equal(result.button.disabled, false);
});

test('verification error stays on checkout and permits retry', async () => {
    const result = await verify([{ ok: false, message: 'Verifikasi gagal' }]);
    assert.equal(result.calls, 1);
    assert.deepEqual(result.redirects, []);
    assert.deepEqual(result.alerts, ['Verifikasi gagal']);
});

test('failed payment does not redirect as if it succeeded', async () => {
    const result = await verify([{ ok: true, status: 'failed', paid: false }]);
    assert.equal(result.calls, 1);
    assert.deepEqual(result.redirects, []);
    assert.equal(result.button.disabled, false);
});

test('network error does not redirect', async () => {
    const result = await verify([new Error('Network unavailable')]);
    assert.deepEqual(result.redirects, []);
    assert.equal(result.button.disabled, false);
});
