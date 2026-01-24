# Build and Test Instructions

## The Problem
You're testing with OLD code that's still installed on your Joomla site. The changes we made are in your local files but NOT deployed to https://klasusers.com yet.

## Solution: Reinstall the Module

### Step 1: Build the Module ZIP

**Option A: Manual (Windows)**
1. Open File Explorer to `E:\MY_PROJECTS\mod_bearslivesearch`
2. Select ALL files and folders EXCEPT:
   - `.git` folder
   - `.qodo` folder
   - `BUILD_AND_TEST.md`
   - `MERGE_SUMMARY.md`
   - `TROUBLESHOOTING.md`
3. Right-click → Send to → Compressed (zipped) folder
4. Name it `mod_bearslivesearch_v2025.09.10.zip`

**Option B: Using PowerShell**
```powershell
# From E:\MY_PROJECTS\mod_bearslivesearch
Compress-Archive -Path .\* -DestinationPath .\mod_bearslivesearch_v2025.09.10.zip -Force -Exclude .git,.qodo,*.md
```

### Step 2: Install on Joomla

1. Go to https://klasusers.com/administrator
2. Navigate to **System → Install → Extensions**
3. Click **Browse for file** or drag and drop
4. Select `mod_bearslivesearch_v2025.09.10.zip`
5. Click **Upload & Install**
6. You should see "Installation of the module was successful" or "Update successful"

### Step 3: Clear ALL Caches

1. **Joomla Cache:**
   - System → Clear Cache → Select All → Delete
   - System → Maintenance → Clear Expired Cache

2. **Browser Cache:**
   - Press Ctrl+Shift+Delete
   - Select "Cached images and files"
   - Click Clear
   - OR just press Ctrl+F5 for hard refresh

3. **Check JavaScript is Updated:**
   - Open https://klasusers.com
   - Press F12 (Developer Tools)
   - Go to Sources tab
   - Find `bearslivesearch.js`
   - Look for line 17-21, should see:
   ```javascript
   // IMMEDIATELY force allwords to be checked BEFORE anything else
   var allwordsRadio = form.querySelector('input[name="searchphrase"][value="allwords"]');
   if (allwordsRadio) {
       allwordsRadio.checked = true;
   }
   ```

### Step 4: Test

1. Go to https://klasusers.com (no URL parameters)
2. Open browser DevTools (F12) → Console tab
3. Type this command:
   ```javascript
   document.querySelector('input[name="searchphrase"]:checked').value
   ```
4. **Expected result:** `"allwords"`

5. Start typing in the search box (e.g., type "b")
6. Check the URL in the address bar
7. **Expected:** `https://klasusers.com/?q=b&searchphrase=allwords&ordering=newest&results_limit=10`

### Step 5: Test ACL

1. Create a test article with "Registered" access level
2. Log out
3. Search for that article
4. **Expected:** Article should NOT appear
5. Log in as registered user
6. Search again
7. **Expected:** Article SHOULD appear

### Step 6: Check Logs

If ACL isn't working, check the logs:
1. Go to `/administrator/logs/mod_bearslivesearch.php`
2. Look for entries like:
   ```
   ACL Check - User ID: 0 | Authorized Levels: 1
   Article SELECT SQL: SELECT ... WHERE ... access IN (1)
   ```

---

## Quick Verification Checklist

- [ ] Built new ZIP file from updated code
- [ ] Installed ZIP in Joomla (saw success message)
- [ ] Cleared Joomla cache
- [ ] Cleared browser cache (Ctrl+F5)
- [ ] Verified JavaScript file is updated (check Sources in DevTools)
- [ ] Tested: "All words" is checked by default
- [ ] Tested: URL shows `searchphrase=allwords` when typing
- [ ] Tested: ACL filtering works with restricted articles

---

## If Still Not Working

The most common issue is that the JavaScript file is cached. Try:

1. **Add version parameter to force reload:**
   - Edit the module's main PHP file
   - Add a version parameter to the JavaScript registration
   
2. **Check browser console for errors:**
   - Press F12 → Console tab
   - Look for any JavaScript errors
   - If there are errors, the script might not be running at all

3. **Verify the actual installed file:**
   - Use FTP or file manager
   - Check `/modules/mod_bearslivesearch/media/js/bearslivesearch.js`
   - Verify it has the immediate enforcement code at line 17-21

4. **Nuclear option - Clear browser data:**
   - Close ALL browser windows
   - Reopen browser
   - Go directly to https://klasusers.com (not from history/bookmark)
   - Test again
