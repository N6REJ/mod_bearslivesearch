# Verify ZIP Contents
Write-Host "Verifying mod_bearslivesearch.zip contents..." -ForegroundColor Cyan
Write-Host ""

if (!(Test-Path "mod_bearslivesearch.zip")) {
    Write-Host "ERROR: mod_bearslivesearch.zip not found!" -ForegroundColor Red
    Write-Host "Run build.ps1 first" -ForegroundColor Yellow
    exit
}

# Extract to temp folder
$temp = "temp_verify_" + (Get-Random)
Expand-Archive -Path "mod_bearslivesearch.zip" -DestinationPath $temp -Force

Write-Host "Checking critical files..." -ForegroundColor Green
Write-Host ""

# Check JavaScript file
$jsFile = "$temp\media\js\bearslivesearch.js"
if (Test-Path $jsFile) {
    $jsContent = Get-Content $jsFile -Raw
    Write-Host "✓ JavaScript file found" -ForegroundColor Green
    
    if ($jsContent -match "IMMEDIATELY force allwords") {
        Write-Host "  ✓ Has immediate enforcement code" -ForegroundColor Green
    } else {
        Write-Host "  ✗ MISSING immediate enforcement code!" -ForegroundColor Red
    }
    
    if ($jsContent -match "urlParams\.has\('searchphrase'\)") {
        Write-Host "  ✓ Has URL parameter checking" -ForegroundColor Green
    } else {
        Write-Host "  ✗ MISSING URL parameter checking!" -ForegroundColor Red
    }
} else {
    Write-Host "✗ JavaScript file NOT FOUND!" -ForegroundColor Red
}

# Check template file
$tmplFile = "$temp\tmpl\default.php"
if (Test-Path $tmplFile) {
    $tmplContent = Get-Content $tmplFile -Raw
    Write-Host "✓ Template file found" -ForegroundColor Green
    
    if ($tmplContent -match 'allwords.*checked.*autocomplete') {
        Write-Host "  ✓ Has allwords checked with autocomplete=off" -ForegroundColor Green
    } else {
        Write-Host "  ✗ MISSING proper allwords default!" -ForegroundColor Red
    }
} else {
    Write-Host "✗ Template file NOT FOUND!" -ForegroundColor Red
}

# Check helper file
$helperFile = "$temp\helper.php"
if (Test-Path $helperFile) {
    $helperContent = Get-Content $helperFile -Raw
    Write-Host "✓ Helper file found" -ForegroundColor Green
    
    if ($helperContent -match "quoteName\('access'\)") {
        Write-Host "  ✓ Has ACL permission checks" -ForegroundColor Green
    } else {
        Write-Host "  ✗ MISSING ACL permission checks!" -ForegroundColor Red
    }
} else {
    Write-Host "✗ Helper file NOT FOUND!" -ForegroundColor Red
}

# Cleanup
Remove-Item $temp -Recurse -Force

Write-Host ""
Write-Host "Verification complete!" -ForegroundColor Cyan
Write-Host ""
Write-Host "If all checks passed, upload this ZIP to Joomla" -ForegroundColor White
