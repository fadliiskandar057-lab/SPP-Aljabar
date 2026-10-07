param(
    [switch]$Tunnel,
    [int]$Port = 8000
)

$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$schedulerJob = $null
$ngrokProcess = $null

function Set-EnvValue {
    param([string]$Key, [string]$Value)

    $path = Join-Path $PSScriptRoot '.env'
    $line = "$Key=$Value"
    $contents = Get-Content -LiteralPath $path
    $pattern = '^\s*' + [regex]::Escape($Key) + '\s*='
    if ($contents -match $pattern) {
        $contents = $contents | ForEach-Object { if ($_ -match $pattern) { $line } else { $_ } }
        Set-Content -LiteralPath $path -Value $contents
    } else {
        Add-Content -LiteralPath $path -Value $line
    }
}

try {
    if (-not (Get-Command php -ErrorAction SilentlyContinue)) { throw 'PHP tidak ditemukan. Jalankan install.bat terlebih dahulu.' }
    if (-not (Test-Path -LiteralPath '.env')) { throw 'File .env belum ada. Jalankan install.bat terlebih dahulu.' }

    Write-Host ''
    Write-Host 'SPP Al Jabbar - Mode Demo' -ForegroundColor Cyan
    Write-Host "Aplikasi : http://127.0.0.1:$Port"
    Write-Host 'Tekan Ctrl+C untuk menghentikan aplikasi, scheduler, dan tunnel.' -ForegroundColor Yellow

    $schedulerJob = Start-Job -ScriptBlock {
        param($project)
        Set-Location -LiteralPath $project
        & php artisan schedule:work
    } -ArgumentList $PSScriptRoot
    Write-Host '[OK] Scheduler Laravel aktif.' -ForegroundColor Green

    if ($Tunnel -and (Get-Command ngrok -ErrorAction SilentlyContinue)) {
        $log = Join-Path $PSScriptRoot 'storage\logs\ngrok.log'
        $errorLog = Join-Path $PSScriptRoot 'storage\logs\ngrok-error.log'
        $ngrokProcess = Start-Process -FilePath 'ngrok' -ArgumentList @('http', $Port, '--log=stdout') -WindowStyle Hidden -PassThru -RedirectStandardOutput $log -RedirectStandardError $errorLog
        $publicUrl = $null
        foreach ($attempt in 1..10) {
            Start-Sleep -Seconds 1
            try {
                $tunnel = (Invoke-RestMethod -Uri 'http://127.0.0.1:4040/api/tunnels' -TimeoutSec 2).tunnels |
                    Where-Object { $_.public_url -like 'https://*' } | Select-Object -First 1
                if ($tunnel) { $publicUrl = $tunnel.public_url; break }
            } catch { }
        }
        if ($publicUrl) {
            $webhook = "$publicUrl/midtrans/webhook"
            Set-EnvValue 'MIDTRANS_WEBHOOK_URL' $webhook
            Write-Host "[OK] Tunnel ngrok aktif: $publicUrl" -ForegroundColor Green
            Write-Host "Webhook Midtrans: $webhook" -ForegroundColor Yellow
            Write-Host 'Salin URL webhook tersebut sekali ke Dashboard Midtrans Sandbox > Settings > Payment > Notification URL.' -ForegroundColor Yellow
        } else {
            Write-Host '[INFO] ngrok tidak memberikan URL. Periksa authtoken atau storage/logs/ngrok.log.' -ForegroundColor Yellow
        }
    } elseif ($Tunnel) {
        Write-Host '[INFO] ngrok belum terpasang. Aplikasi tetap dapat mengonfirmasi pembayaran karena status dicek langsung ke server Midtrans.' -ForegroundColor Yellow
        Write-Host 'Untuk webhook cadangan, instal ngrok lalu jalankan start.bat lagi.' -ForegroundColor Yellow
    }

    & php artisan serve --host=127.0.0.1 --port=$Port
} finally {
    if ($schedulerJob) { Stop-Job -Job $schedulerJob -ErrorAction SilentlyContinue; Remove-Job -Job $schedulerJob -Force -ErrorAction SilentlyContinue }
    if ($ngrokProcess -and -not $ngrokProcess.HasExited) { Stop-Process -Id $ngrokProcess.Id -Force -ErrorAction SilentlyContinue }
}
