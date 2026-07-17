param(
    [switch]$Fresh,
    [switch]$NoServe
)

$ErrorActionPreference = "Stop"

function Write-Step {
    param([string]$Message)
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Write-Ok {
    param([string]$Message)
    Write-Host "[OK] $Message" -ForegroundColor Green
}

function Require-Command {
    param([string]$Name, [string]$InstallHint)

    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "$Name tidak ditemukan. $InstallHint"
    }
}

function Run-Command {
    param(
        [string]$Title,
        [string]$File,
        [string[]]$Arguments = @()
    )

    Write-Step $Title
    & $File @Arguments

    if ($LASTEXITCODE -ne 0) {
        throw "Perintah gagal: $File $($Arguments -join ' ')"
    }
}

function Get-EnvValue {
    param(
        [string]$Path,
        [string]$Key,
        [string]$Default = ""
    )

    if (-not (Test-Path -LiteralPath $Path)) {
        return $Default
    }

    $line = Get-Content -LiteralPath $Path | Where-Object { $_ -match "^\s*$([regex]::Escape($Key))\s*=" } | Select-Object -First 1
    if (-not $line) {
        return $Default
    }

    $value = ($line -replace "^\s*$([regex]::Escape($Key))\s*=", "").Trim()
    $value = $value.Trim('"').Trim("'")

    if ($value -eq "") {
        return $Default
    }

    return $value
}

try {
    Set-Location -LiteralPath $PSScriptRoot

    Write-Host "Installer Aplikasi Pembayaran SPP" -ForegroundColor Yellow
    Write-Host "Folder project: $PSScriptRoot"

    Write-Step "Memeriksa aplikasi yang dibutuhkan"
    Require-Command "php" "Install PHP lewat Laragon, lalu buka ulang terminal."
    Require-Command "composer" "Install Composer dari https://getcomposer.org/download/."
    Require-Command "npm" "Install Node.js LTS dari https://nodejs.org/."
    Write-Ok "PHP, Composer, dan npm tersedia."

    if (-not (Test-Path -LiteralPath ".env")) {
        Write-Step "Membuat file .env dari .env.example"
        Copy-Item -LiteralPath ".env.example" -Destination ".env"
        Write-Ok ".env berhasil dibuat."
    } else {
        Write-Ok ".env sudah ada."
    }

    $dbHost = Get-EnvValue ".env" "DB_HOST" "127.0.0.1"
    $dbPort = Get-EnvValue ".env" "DB_PORT" "3306"
    $dbName = Get-EnvValue ".env" "DB_DATABASE" "spp_al_jabbar"
    $dbUser = Get-EnvValue ".env" "DB_USERNAME" "root"
    $dbPass = Get-EnvValue ".env" "DB_PASSWORD" ""

    Run-Command "Install dependency Laravel" "composer" @("install", "--no-interaction")
    Run-Command "Install dependency frontend" "npm" @("install")

    $appKey = Get-EnvValue ".env" "APP_KEY" ""
    if ($appKey -eq "") {
        Run-Command "Generate APP_KEY" "php" @("artisan", "key:generate", "--force")
    } else {
        Write-Ok "APP_KEY sudah terisi."
    }

    Write-Step "Membuat database MySQL/MariaDB jika belum ada"
    $env:INSTALL_DB_HOST = $dbHost
    $env:INSTALL_DB_PORT = $dbPort
    $env:INSTALL_DB_NAME = $dbName
    $env:INSTALL_DB_USER = $dbUser
    $env:INSTALL_DB_PASS = $dbPass

    $phpCreateDatabase = @'
<?php
$host = getenv('INSTALL_DB_HOST') ?: '127.0.0.1';
$port = getenv('INSTALL_DB_PORT') ?: '3306';
$name = getenv('INSTALL_DB_NAME') ?: 'spp_al_jabbar';
$user = getenv('INSTALL_DB_USER') ?: 'root';
$pass = getenv('INSTALL_DB_PASS');

if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
    fwrite(STDERR, "Nama database tidak valid: {$name}\n");
    exit(1);
}

$pdo = new PDO(
    "mysql:host={$host};port={$port};charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "Database siap: {$name}\n";
'@

    $phpCreateDatabase | php
    if ($LASTEXITCODE -ne 0) {
        throw "Gagal membuat database. Pastikan MySQL Laragon sudah Start dan konfigurasi DB di .env benar."
    }

    $phpCheckDemoData = @'
<?php
$host = getenv('INSTALL_DB_HOST') ?: '127.0.0.1';
$port = getenv('INSTALL_DB_PORT') ?: '3306';
$name = getenv('INSTALL_DB_NAME') ?: 'spp_al_jabbar';
$user = getenv('INSTALL_DB_USER') ?: 'root';
$pass = getenv('INSTALL_DB_PASS');

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = 'users'"
);
$stmt->execute([$name]);

if ((int) $stmt->fetchColumn() === 0) {
    echo "DEMO_DATA=0\n";
    exit;
}

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
echo ((int) $stmt->fetchColumn() > 0) ? "DEMO_DATA=1\n" : "DEMO_DATA=0\n";
'@

    if ($Fresh) {
        Write-Host ""
        Write-Host "Mode Fresh aktif: semua tabel dan data lama di database '$dbName' akan dihapus." -ForegroundColor Yellow
        $confirmation = Read-Host "Ketik RESET untuk lanjut"
        if ($confirmation -ne "RESET") {
            throw "Reset database dibatalkan."
        }

        Run-Command "Reset database, migrate, dan seed data contoh" "php" @("artisan", "migrate:fresh", "--seed", "--force")
    } else {
        Run-Command "Menjalankan migration database" "php" @("artisan", "migrate", "--force")

        $demoCheckOutput = $phpCheckDemoData | php
        if ($LASTEXITCODE -ne 0) {
            throw "Gagal mengecek data demo di database."
        }

        if ($demoCheckOutput -match "DEMO_DATA=1") {
            Write-Ok "Data demo sudah ada, seeder dilewati."
        } else {
            Run-Command "Mengisi seeder data contoh" "php" @("artisan", "db:seed", "--force")
        }
    }

    Run-Command "Membersihkan cache Laravel" "php" @("artisan", "optimize:clear")

    Write-Host ""
    Write-Host "Instalasi selesai." -ForegroundColor Green
    Write-Host "Akun demo:"
    Write-Host "  Admin TU        username: admin      password: password"
    Write-Host "  Kepala Sekolah  username: kepala     password: password"
    Write-Host "  Wali Kelas      username: wali       password: password"
    Write-Host "  Siswa           username: 26260001   password: password"

    if (-not $NoServe) {
        Write-Host ""
        Write-Host "Server Laravel akan dijalankan di http://127.0.0.1:8000" -ForegroundColor Yellow
        Write-Host "Biarkan jendela ini terbuka selama aplikasi digunakan."
        Write-Host ""
        & php artisan serve --host=127.0.0.1 --port=8000
    }
} catch {
    Write-Host ""
    Write-Host "Instalasi gagal:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
