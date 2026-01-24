# Merge Summary: ACL Security Fixes and Default Search Phrase Improvements

**Branch:** `acl` → `main`  
**Date:** 2025-01-10  
**Status:** ✅ Successfully Merged

---

## Changes Included

### 1. **CRITICAL SECURITY FIX: ACL Permission Checks**

**Problem:** Search was bypassing Joomla's Access Control List (ACL), allowing users to see restricted articles in search results.

**Solution:** Added ACL filtering in `helper.php` (lines 88-107)
- Retrieves user's authorized access levels using `$user->getAuthorisedViewLevels()`
- Filters search results to only show articles the user has permission to view
- Uses `$db->quoteName('access')` for proper Joomla database standards
- Includes SQL injection protection via integer casting
- Added debug logging for troubleshooting

**Impact:** HIGH - This is a security vulnerability fix

---

### 2. **Default Search Phrase Changed to "All Words"**

**Problem:** Default search was "Exact Phrase" which is too restrictive for most users. Browser form caching was also causing the wrong default to be used.

**Solution:** Multiple layers of enforcement
- **HTML:** Changed default `checked` attribute to "allwords" in `tmpl/default.php` (line 114)
- **Form:** Added `autocomplete="off"` to form and all radio buttons to prevent browser caching
- **JavaScript:** Added aggressive enforcement in `media/js/bearslivesearch.js`
  - Enforces "allwords" immediately on page load
  - Re-enforces at 50ms and 200ms to override browser form restoration
  - Re-enforces on window load event
  - **Honors bookmarked URLs** - if URL has `searchphrase` parameter, it's respected

**Impact:** MEDIUM - Improves user experience

---

### 3. **Production Code Cleanup**

**Problem:** Console.log statements were exposing internal application behavior in production.

**Solution:** Removed all console.log statements from JavaScript
- Removed 20+ debugging console.log statements
- Kept only essential error logging (console.error) for critical failures

**Impact:** LOW - Security and professionalism improvement

---

## Files Modified

1. **helper.php**
   - Added ACL permission checks (lines 88-107)
   - Added debug logging (lines 100-101, 290)
   - Used `$db->quoteName('access')` for proper SQL quoting

2. **tmpl/default.php**
   - Changed default to "allwords" with `checked` attribute (line 114)
   - Added `autocomplete="off"` to form (line 91)
   - Added `autocomplete="off"` to all radio buttons (lines 112-118)

3. **media/js/bearslivesearch.js**
   - Added `enforceDefaultSearchPhrase()` function with URL parameter checking
   - Added multiple enforcement points (immediate, 50ms, 200ms, window load)
   - Removed all console.log debugging statements
   - Honors bookmarked URLs with explicit `searchphrase` parameter

4. **README.md**
   - Updated version to 2025.09.10
   - Added security fix documentation

5. **CHANGELOG.md**
   - Added [2025.09.10] entry with security and UX improvements

---

## Testing Instructions

### Test ACL Filtering:
1. Create a test article with restricted access (e.g., "Registered" only)
2. Log out and search for that article
3. **Expected:** Article should NOT appear in search results
4. Log in as registered user and search again
5. **Expected:** Article SHOULD appear now
6. Check logs at `/administrator/logs/mod_bearslivesearch.php` for ACL debug info

### Test "All Words" Default:
1. Clear browser cache (Ctrl+F5)
2. Load the page fresh (no URL parameters)
3. **Expected:** "All words" radio button should be selected
4. Start typing a search
5. **Expected:** URL should show `searchphrase=allwords`

### Test Bookmark Honoring:
1. Create a bookmark with `?q=test&searchphrase=exact`
2. Load the bookmarked URL
3. **Expected:** "Exact phrase" should be selected (honoring the bookmark)

---

## Deployment Steps

1. **Backup** your current module installation
2. **Reinstall** the module from the updated ZIP
3. **Clear all caches:**
   - System → Clear Cache
   - System → Maintenance → Clear Expired Cache
   - Browser cache (Ctrl+F5)
4. **Test** the ACL filtering with restricted articles
5. **Test** the default search phrase behavior
6. **Check logs** at `/administrator/logs/mod_bearslivesearch.php` for any issues

---

## Version Information

- **Previous Version:** 2025.07.19
- **New Version:** 2025.09.10
- **Security Level:** CRITICAL (ACL bypass vulnerability fixed)
- **Joomla Compatibility:** 5.0+
- **PHP Compatibility:** 8.0+

---

## Rollback Plan

If issues occur:
1. Reinstall the previous version from backup
2. Clear all caches
3. Report the issue with log files from `/administrator/logs/`

---

## Notes

- The ACL fix is **critical** and should be deployed as soon as possible
- The default search phrase change improves UX but is not critical
- All changes are backward compatible
- No database changes required
- No configuration changes required

---

**Merged by:** Qodo AI Assistant  
**Approved by:** Troy Hall (N6REJ)  
**Repository:** https://github.com/N6REJ/mod_bearslivesearch
