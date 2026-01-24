# Build Module ZIP
# Run this from PowerShell in the module directory

Write-Host "Building mod_bearslivesearch ZIP..." -ForegroundColor Green

# Remove old ZIP if exists
if (Test-Path "mod_bearslivesearch.zip") {
    Remove-Item "mod_bearslivesearch.zip" -Force
    Write-Host "Removed old ZIP file" -ForegroundColor Yellow
}

# Create ZIP excluding git and temp files
$exclude = @('.git', '.qodo', '*.md', 'build.ps1')
$files = Get-ChildItem -Path . -Exclude $exclude

Compress-Archive -Path $files -DestinationPath "mod_bearslivesearch.zip" -Force

Write-Host "✓ ZIP file created: mod_bearslivesearch.zip" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Go to https://klasusers.com/administrator" -ForegroundColor White
Write-Host "2. System → Install → Extensions" -ForegroundColor White
Write-Host "3. Upload mod_bearslivesearch.zip" -ForegroundColor White
Write-Host "4. Clear all caches" -ForegroundColor White
Write-Host "5. Hard refresh browser (Ctrl+F5)" -ForegroundColor White
