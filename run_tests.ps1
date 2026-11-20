Write-Host "=============================="
Write-Host " STEP 5: PROVING IT WORKS"
Write-Host "=============================="

Write-Host "`n>>> Running: npm run build"
cmd.exe /c "npm.cmd run build"
if ($LASTEXITCODE -ne 0) {
    Write-Error "Build failed!"
    exit 1
}

Write-Host "`n>>> Starting application server (php -S 127.0.0.1:8000 router.php)..."
$server = Start-Process -FilePath "php" -ArgumentList "-S 127.0.0.1:8000 router.php" -PassThru
Start-Sleep -Seconds 2

try {
    Write-Host "`n>>> Running end-to-end tests against Neon PostgreSQL..."
    php test_e2e.php
    if ($LASTEXITCODE -ne 0) {
        Write-Error "E2E tests failed!"
        exit 1
    }
} finally {
    Write-Host "`n>>> Stopping application server..."
    Stop-Process -Id $server.Id -Force
}
