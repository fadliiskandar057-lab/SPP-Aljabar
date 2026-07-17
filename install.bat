@echo off
setlocal EnableExtensions

cd /d "%~dp0"
title Installer SPP Al Jabbar

:menu
cls
echo ============================================================
echo                 SPP AL JABBAR - INSTALLER
echo ============================================================
echo.
echo Folder project:
echo %cd%
echo.
echo Pilih tindakan:
echo.
echo   1. Install / Update aman + jalankan server
echo      - Tidak menghapus database lama
echo      - Cocok untuk revisi/update client
echo.
echo   2. Install / Update aman saja, tanpa jalankan server
echo      - Tidak menghapus database lama
echo      - Cocok setelah mengganti file project
echo.
echo   3. Reset database fresh + seed demo + jalankan server
echo      - Menghapus semua data lama
echo      - Gunakan hanya untuk demo atau install dari nol
echo.
echo   4. Bersihkan cache Laravel saja
echo.
echo   5. Keluar
echo.
set /p choice="Masukkan pilihan [1-5]: "

if "%choice%"=="1" goto install_serve
if "%choice%"=="2" goto install_only
if "%choice%"=="3" goto fresh_install
if "%choice%"=="4" goto clear_cache
if "%choice%"=="5" goto end

echo.
echo Pilihan tidak valid.
pause
goto menu

:install_serve
call :run_ps
if errorlevel 1 goto failed
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0install.ps1"
if errorlevel 1 goto failed
goto done

:install_only
call :run_ps
if errorlevel 1 goto failed
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0install.ps1" -NoServe
if errorlevel 1 goto failed
goto done

:fresh_install
cls
echo ============================================================
echo                  PERINGATAN RESET DATABASE
echo ============================================================
echo.
echo Mode ini akan menghapus semua tabel dan data lama.
echo Jangan gunakan pilihan ini jika database client sudah berisi data.
echo.
set /p confirm="Ketik RESET untuk lanjut: "
if /i not "%confirm%"=="RESET" (
    echo.
    echo Reset dibatalkan.
    pause
    goto menu
)
call :run_ps
if errorlevel 1 goto failed
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0install.ps1" -Fresh
if errorlevel 1 goto failed
goto done

:clear_cache
call :check_php
if errorlevel 1 goto failed
echo.
echo Membersihkan cache Laravel...
php artisan optimize:clear
if errorlevel 1 goto failed
php artisan view:cache
if errorlevel 1 goto failed
echo.
echo Cache berhasil dibersihkan.
pause
goto menu

:run_ps
where powershell >nul 2>nul
if errorlevel 1 (
    echo.
    echo PowerShell tidak ditemukan.
    echo Jalankan installer lewat Windows PowerShell.
    pause
    exit /b 1
)
exit /b 0

:check_php
where php >nul 2>nul
if errorlevel 1 (
    echo.
    echo PHP tidak ditemukan.
    echo Buka terminal dari Laragon atau pastikan PHP sudah masuk PATH.
    pause
    exit /b 1
)
exit /b 0

:failed
echo.
echo Proses gagal. Periksa pesan error di atas.
pause
goto menu

:done
echo.
echo Proses selesai.
pause
goto menu

:end
endlocal
