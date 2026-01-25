# Build Module ZIP - Comprehensive Version
# Run this from PowerShell in E:\MY_PROJECTS\mod_bearslivesearch

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Building mod_bearslivesearch ZIP" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Remove old ZIP if exists
if (Test-Path "mod_bearslivesearch.zip") {
    Remove-Item "mod_bearslivesearch.zip" -Force
    Write-Host "✓ Removed old ZIP file" -ForegroundColor Yellow
}

# List of files/folders to include
$itemsToInclude = @(
    "helper.php",
    "mod_bearslivesearch.php",
    "mod_bearslivesearch.xml",
    "updates.xml",
    "CHANGELOG.md",
    "README.md",
    "tmpl",
    "media",
    "fields",
    "language"
)

Write-Host "Including files:" -ForegroundColor Green
foreach ($item in $itemsToInclude) {
    if (Test-Path $item) {
        Write-Host "  ✓ $item" -ForegroundColor White
    } else {
        Write-Host "  ✗ $item (NOT FOUND)" -ForegroundColor Red
    }
}
Write-Host ""

# Create the ZIP
Compress-Archive -Path $itemsToInclude -DestinationPath "mod_bearslivesearch.zip" -Force

if (Test-Path "mod_bearslivesearch.zip") {
    $zipSize = (Get-Item "mod_bearslivesearch.zip").Length / 1KB
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "✓ SUCCESS!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "ZIP file created: mod_bearslivesearch.zip" -ForegroundColor White
    Write-Host "Size: $([math]::Round($zipSize, 2)) KB" -ForegroundColor White
    Write-Host ""
    Write-Host "NEXT STEPS:" -ForegroundColor Cyan
    Write-Host "1. Go to https://klasusers.com/administrator" -ForegroundColor White
    Write-Host "2. System → Install → Extensions" -ForegroundColor White
    Write-Host "3. Upload mod_bearslivesearch.zip" -ForegroundColor White
    Write-Host "4. System → Clear Cache → Select All → Delete" -ForegroundColor White
    Write-Host "5. In browser: Ctrl+Shift+Delete → Clear cached files" -ForegroundColor White
    Write-Host "6. Hard refresh: Ctrl+F5" -ForegroundColor White
    Write-Host "7. Test by typing in search box" -ForegroundColor White
    Write-Host ""
} else {
    Write-Host "✗ FAILED to create ZIP file" -ForegroundColor Red
}
