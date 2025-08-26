/**
 * Bears Live Search
 *
 * @version 2025.7.15.1
 * @package Bears Live Search
 * @author N6REJ
 * @email troy@hallhome.us
 * @website https://hallhome.us/software
 * @copyright Copyright (C) 2025 Troy Hall (N6REJ)
 * @license GNU General Public License version 3 or later; see LICENSE.txt
 * @since 2025.7.15
 */
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var modules = document.querySelectorAll('.bearslivesearch');
        modules.forEach(function(module) {
            var form = module.querySelector('.bearslivesearch-form');
            var input = form.querySelector('input[type="search"]');
            var results = module.querySelector('.bearslivesearch-results');
            var lastQuery = '';
            var xhr;
            var searchMode = module.getAttribute('data-search-mode') || 'inline';
            var endPosition = module.getAttribute('data-end-position') || '';
            var originalPageState = null; // Store original page state for restoration
            var isRestoring = false; // Flag to prevent re-transformation during restoration

            // Suppress AJAX when programmatically resetting filters
            var suppressFilterSearch = false;

            // Capture default values of all filters (non-search input)
            function captureDefaultFilters() {
                if (!form || form.__blsDefaultsCaptured) return;
                var controls = form.querySelectorAll('select, textarea, input:not([type="search"])');
                controls.forEach(function(ctrl) {
                    if (ctrl.type === 'radio') {
                        if (ctrl.checked) ctrl.setAttribute('data-bls-default-checked', '1');
                        ctrl.setAttribute('data-bls-group', ctrl.name || '');
                    } else if (ctrl.type === 'checkbox') {
                        ctrl.setAttribute('data-bls-default-checked', ctrl.checked ? '1' : '0');
                    } else {
                        ctrl.setAttribute('data-bls-default', ctrl.value);
                    }
                });
                form.__blsDefaultsCaptured = true;
            }

            // Reset filters to their captured defaults (does not touch the search input)
            function resetFiltersToDefault() {
                if (!form) return;
                suppressFilterSearch = true;
                try {
                    var controls = form.querySelectorAll('select, textarea, input:not([type="search"])');
                    controls.forEach(function(ctrl) {
                        if (ctrl.type === 'radio') {
                            var shouldCheck = ctrl.getAttribute('data-bls-default-checked') === '1';
                            ctrl.checked = !!shouldCheck;
                        } else if (ctrl.type === 'checkbox') {
                            var def = ctrl.getAttribute('data-bls-default-checked');
                            ctrl.checked = def === '1';
                        } else {
                            var defVal = ctrl.getAttribute('data-bls-default');
                            if (defVal !== null) ctrl.value = defVal;
                            else ctrl.value = '';
                        }
                        // Fire change event to update any UI wrappers
                        var ev = new Event('change', { bubbles: true });
                        ctrl.dispatchEvent(ev);
                    });
                } finally {
                    suppressFilterSearch = false;
                }
            }

            // Ensure default search phrase is 'exact' unless URL explicitly sets it
            function enforceDefaultSearchPhrase() {
                try {
                    if (!form) return;
                    var urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.has('searchphrase')) return; // respect URL if provided
                    var radios = form.querySelectorAll('input[name="searchphrase"]');
                    var exactRadio = form.querySelector('input[name="searchphrase"][value="exact"]');
                    if (!radios || !radios.length || !exactRadio) return;
                    // Force exact as the only checked on initial load
                    radios.forEach(function(r){ r.checked = (r === exactRadio); });
                } catch (e) { /* noop */ }
            }

            // Also enforce after full window load to override late scripts/autofill
            try { window.addEventListener('load', enforceDefaultSearchPhrase); } catch (e) {}

            function updateResults(html) {
                // Ensure results container exists
                if (!results || !module.contains(results)) {
                    results = module.querySelector('.bearslivesearch-results');
                    if (!results) {
                        results = document.createElement('div');
                        results.className = 'bearslivesearch-results bearslivesearch-results--hidden';
                        results.id = (module.id || 'bearslivesearch') + '-results';
                        results.setAttribute('aria-live', 'polite');
                        results.setAttribute('aria-atomic', 'true');
                        module.appendChild(results);
                    }
                }

                // Reveal criteria/filter rows if hidden (for "after" mode)
                var criteriaRows = module.querySelectorAll('.bearslivesearch-criteria-hidden');
                if (criteriaRows.length) {
                    criteriaRows.forEach(function(row) {
                        row.classList.remove('bearslivesearch-criteria-hidden');
                    });
                }
                if (!html || !html.trim()) {
                    // Use the language string from the DOM or fallback
                    var noResults = module.getAttribute('data-no-results') || 'No results found.';
                    results.innerHTML = '<div class="bearslivesearch-no-results" role="status">' + noResults + '</div>';
                } else {
                    results.innerHTML = html;
                }
                results.classList.remove('bearslivesearch-results--hidden');
                input && input.focus();
            }

            // Update browser URL to reflect current search criteria (non-destructive history)
            function updateBrowserUrl(formData, page) {
                try {
                    var params = [];
                    // Only include non-empty values
                    formData.forEach(function(value, key) {
                        if (value !== undefined && value !== null && String(value).trim() !== '') {
                            params.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                        }
                    });
                    if (page && page > 1) {
                        params.push('page=' + encodeURIComponent(page));
                    }
                    var newUrl = window.location.pathname + (params.length ? ('?' + params.join('&')) : '');
                    // Use replaceState to avoid stacking history entries on every keystroke
                    history.replaceState(history.state, document.title, newUrl);
                } catch (e) {
                    // No-op if history API unavailable
                }
            }

            function doSearch(query, page, allowEmpty) {
                if (xhr) xhr.abort();

                // Serialize current form fields upfront for URL updates
                var formData = new FormData(form);
                updateBrowserUrl(formData, page);

                if (!query || !query.trim()) {
                    if (!allowEmpty) {
                        updateResults('');
                        // Hide and clear results when input is empty
                        if (results) {
                            results.innerHTML = '';
                            results.classList.add('bearslivesearch-results--hidden');
                        }
                        return;
                    }
                }

                // If we're in separate page mode and not yet transformed, transform the page first
                if (searchMode === 'separate_page' && !isTransformedOrSearchPage()) {
                    if (query && query.trim()) {
                        transformPageToSearchResults(query);
                        return; // transformPageToSearchResults will trigger the search
                    }
                    // If query is empty, skip transform and proceed with AJAX inline
                }

                // Show loading indicator
                updateResults('<div class="bearslivesearch-loading" role="status">Searching...</div>');

                // Serialize all form fields for AJAX (include empties, backend can decide)
                var params = [];
                formData.forEach(function(value, key) {
                    if (value !== undefined && value !== null) {
                        params.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                    }
                });
                if (page && page > 1) {
                    params.push('page=' + encodeURIComponent(page));
                }
                // Get moduleId from the container's id attribute
                var moduleContainer = form.closest('.bearslivesearch');
                var moduleId = '';
                if (moduleContainer && moduleContainer.id) {
                    var match = moduleContainer.id.match(/bearslivesearch-(\d+)/);
                    if (match) {
                        moduleId = match[1];
                    }
                }
                if (moduleId) {
                    params.push('moduleId=' + encodeURIComponent(moduleId));
                }
                // Build AJAX URL using the PHP-provided base URL (most reliable)
                var ajaxUrl = '';
                var baseUrl = moduleContainer.getAttribute('data-base-url');
                
                if (baseUrl) {
                    // Use the base URL provided by PHP (most reliable)
                    if (baseUrl.endsWith('/')) {
                        baseUrl = baseUrl.slice(0, -1);
                    }
                    ajaxUrl = baseUrl + '/index.php?option=com_ajax&module=bearslivesearch&method=search&format=raw&' + params.join('&');
                    console.log('Using PHP-provided base URL:', baseUrl);
                } else {
                    // Fallback 1: Try to use the base tag if available
                    var baseTag = document.querySelector('base[href]');
                    if (baseTag) {
                        var baseHref = baseTag.getAttribute('href');
                        if (baseHref.endsWith('/')) {
                            baseHref = baseHref.slice(0, -1);
                        }
                        ajaxUrl = baseHref + '/index.php?option=com_ajax&module=bearslivesearch&method=search&format=raw&' + params.join('&');
                        console.log('Using base tag URL:', baseHref);
                    } else {
                        // Fallback 2: Use root URL (most common case)
                        var origin = window.location.origin;
                        ajaxUrl = origin + '/index.php?option=com_ajax&module=bearslivesearch&method=search&format=raw&' + params.join('&');
                        console.log('Using root URL fallback:', origin);
                    }
                }
                
                console.log('Final AJAX URL:', ajaxUrl);

                xhr = new XMLHttpRequest();
                xhr.open('GET', ajaxUrl, true);
                xhr.timeout = 5000; // 5 seconds timeout

                xhr.ontimeout = function() {
                    updateResults('<div role="alert">AJAX search timed out. Please try again.</div>');
                };
                xhr.onerror = function() {
                    updateResults('<div role="alert">AJAX search failed. Please check your connection.</div>');
                };
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            updateResults(xhr.responseText);
                        } else {
                            updateResults('<div role="alert">AJAX search failed. Please try again.</div>');
                        }
                    }
                };
                xhr.send();
            }

            function createCompleteSearchPage(query, formData, originalModuleId, originalForm) {
                var moduleId = originalModuleId + '-search-page';
                
                // Create container and add title
                var container = document.createElement('div');
                container.className = 'bearslivesearch bearslivesearch-search-page';
                container.id = moduleId;
                
                var title = document.createElement('h1');
                title.textContent = 'Search Results';
                title.style.marginBottom = '1.5rem';
                container.appendChild(title);
                
                // Move the original form (don't clone it)
                // First, update the original form's values
                var originalInput = originalForm.querySelector('input[type="search"]');
                if (originalInput) {
                    originalInput.value = query;
                }
                
                // Update form field values based on formData
                var formElements = originalForm.elements;
                for (var i = 0; i < formElements.length; i++) {
                    var element = formElements[i];
                    var value = formData.get(element.name);
                    if (value !== null) {
                        if (element.type === 'radio' || element.type === 'checkbox') {
                            element.checked = (element.value === value);
                        } else {
                            element.value = value;
                        }
                    }
                }
                
                // Show all hidden criteria in the original form
                var hiddenCriteria = originalForm.querySelectorAll('.bearslivesearch-criteria-hidden');
                hiddenCriteria.forEach(function(row) {
                    row.classList.remove('bearslivesearch-criteria-hidden');
                });
                
                // Move the original form to the container
                container.appendChild(originalForm);
                
                var resultsDiv = document.createElement('div');
                resultsDiv.className = 'bearslivesearch-results';
                resultsDiv.id = moduleId + '-results';
                resultsDiv.setAttribute('aria-live', 'polite');
                resultsDiv.setAttribute('aria-atomic', 'true');
                resultsDiv.innerHTML = '<div class="bearslivesearch-loading" role="status">Loading search results...</div>';
                container.appendChild(resultsDiv);
                
                return container;
            }

            function storeOriginalPageState() {
                if (originalPageState) return; // Already stored
                
                console.log('Storing original page state...');
                
                // Store a more comprehensive snapshot
                originalPageState = {
                    title: document.title,
                    url: window.location.href,
                    bodyHTML: document.body.innerHTML, // Store complete HTML
                    bodyAttributes: Array.from(document.body.attributes).map(function(attr) {
                        return { name: attr.name, value: attr.value };
                    }),
                    documentHTML: document.documentElement.outerHTML // Backup: entire document
                };
                
                console.log('Original page state stored');
            }
            
            function restoreOriginalPageState() {
                if (!originalPageState || isRestoring) return;
                
                console.log('Restoring original page state...');
                isRestoring = true;
                
                // First, restore the URL to prevent re-transformation
                document.title = originalPageState.title;
                history.replaceState(null, originalPageState.title, originalPageState.url);
                
                // Method 1: Try restoring body innerHTML (most reliable)
                try {
                    document.body.innerHTML = originalPageState.bodyHTML;
                    
                    // Restore body attributes
                    originalPageState.bodyAttributes.forEach(function(attr) {
                        document.body.setAttribute(attr.name, attr.value);
                    });
                    
                    console.log('Page restored using innerHTML method');
                } catch (e) {
                    console.error('Failed to restore using innerHTML:', e);
                    
                    // Fallback: reload the original URL
                    window.location.href = originalPageState.url;
                    return;
                }
                
                // Clear the stored state
                var originalUrl = originalPageState.url;
                originalPageState = null;
                
                // Re-initialize the module since we restored the original DOM
                setTimeout(function() {
                    // Re-initialize all modules since DOM was restored
                    var restoredModules = document.querySelectorAll('.bearslivesearch');
                    restoredModules.forEach(function(restoredModule) {
                        var moduleId = restoredModule.getAttribute('data-module-id');
                        if (moduleId === module.getAttribute('data-module-id')) {
                            // Update references to restored elements
                            module = restoredModule;
                            form = module.querySelector('.bearslivesearch-form');
                            input = form.querySelector('input[type="search"]');
                            results = module.querySelector('.bearslivesearch-results');
                            
                            // Clear the input BEFORE setting up event listeners
                            input.value = '';
                            lastQuery = '';
                            
                            // Re-setup event listeners for this specific module
                            enforceDefaultSearchPhrase();
                            captureDefaultFilters();
                            setupLiveSearch();
                            setupPagination();
                            
                            // Focus the input
                            input.focus();
                            
                            console.log('Module re-initialized after restoration');
                            
                            // Clear the restoration flag after a delay
                            setTimeout(function() {
                                isRestoring = false;
                            }, 500);
                        }
                    });
                }, 100);
            }

            function transformPageToSearchResults(query) {
                if (!query.trim()) return;
                
                // Store original page state before transformation
                storeOriginalPageState();
                
                // Serialize all form fields for URL
                var params = [];
                var formData = new FormData(form);
                formData.forEach(function(value, key) {
                    if (value !== undefined && value !== null && value.trim() !== '') {
                        params.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                    }
                });
                
                // Update URL without page reload
                var newUrl = window.location.pathname;
                if (params.length > 0) {
                    newUrl += '?' + params.join('&');
                }
                history.pushState({searchMode: true}, 'Search Results', newUrl);
                
                // Transform the existing module and hide content below
                transformPageContent(query, formData);
            }
            
            function transformPageContent(query, formData) {
                var moduleContainer = module.closest('.bearslivesearch');
                var moduleId = moduleContainer.getAttribute('data-module-id');
                
                // Use the special class as our precise divider point
                var moduleSelector = '.bearslivesearch-module-' + moduleId;
                var targetModule = document.querySelector(moduleSelector);
                
                if (!targetModule) {
                    console.error('Could not find module with selector:', moduleSelector);
                    return;
                }
                
                console.log('Target module found:', targetModule);
                
                // AGGRESSIVE APPROACH: Remove all content that comes after the search module in document order
                // This handles multiple modules in the same position correctly
                
                // Get all elements in the document
                var allElements = Array.from(document.body.querySelectorAll('*'));
                
                // Find the index of our target module
                var moduleIndex = allElements.indexOf(targetModule);
                
                if (moduleIndex === -1) {
                    console.error('Could not find target module in document elements');
                    return;
                }
                
                console.log('Target module found at index:', moduleIndex, 'of', allElements.length, 'total elements');
                
                // Collect all elements that come after the module and are not descendants of the module
                var elementsToRemove = [];
                
                for (var i = moduleIndex + 1; i < allElements.length; i++) {
                    var element = allElements[i];
                    
                    // Don't remove elements that are inside the target module
                    if (!targetModule.contains(element)) {
                        elementsToRemove.push(element);
                    }
                }
                
                // Find the end position element if configured
                var endPositionElement = null;
                if (endPosition && endPosition.trim() !== '') {
                    // Look for the end position element
                    endPositionElement = document.getElementById(endPosition) ||
                                       document.querySelector('.' + endPosition) ||
                                       document.querySelector('[class*="' + endPosition + '"]');
                }
                
                // If no end position configured or found, default to footer
                if (!endPositionElement) {
                    endPositionElement = document.getElementById('footer') ||
                                       document.querySelector('footer') ||
                                       document.querySelector('[class*="footer"]');
                }
                
                // Remove elements between the module and the end position
                elementsToRemove.forEach(function(element) {
                    // Only remove if the element is still in the DOM (not already removed as a child)
                    if (element.parentElement) {
                        var shouldRemove = true;
                        
                        // If we found an end position element, preserve it and everything after it
                        if (endPositionElement) {
                            // Check if this element is the end position element or comes after it
                            var elementIndex = allElements.indexOf(element);
                            var endPositionIndex = allElements.indexOf(endPositionElement);
                            
                            if (endPositionIndex !== -1 && elementIndex >= endPositionIndex) {
                                shouldRemove = false; // Preserve this element and everything after end position
                            }
                            
                            // Also preserve if this element contains the end position element
                            if (element.contains(endPositionElement)) {
                                shouldRemove = false;
                            }
                        }
                        
                        if (shouldRemove) {
                            console.log('Removing element after module:', element.tagName, element.id || element.className);
                            element.remove();
                        } else {
                            console.log('Preserving element at/after end position:', element.tagName, element.id || element.className);
                        }
                    }
                });
                
                console.log('Removed', elementsToRemove.length, 'elements after the search module');
                
                // Transform the target module in place
                targetModule.classList.add('bearslivesearch-search-page');
                
                // Update the original form's values
                var originalInput = form.querySelector('input[type="search"]');
                if (originalInput) {
                    originalInput.value = query;
                }
                
                // Update form field values based on formData
                var formElements = form.elements;
                for (var i = 0; i < formElements.length; i++) {
                    var element = formElements[i];
                    var value = formData.get(element.name);
                    if (value !== null) {
                        if (element.type === 'radio' || element.type === 'checkbox') {
                            element.checked = (element.value === value);
                        } else {
                            element.value = value;
                        }
                    }
                }
                
                // Show all hidden criteria in the original form
                var hiddenCriteria = form.querySelectorAll('.bearslivesearch-criteria-hidden');
                hiddenCriteria.forEach(function(row) {
                    row.classList.remove('bearslivesearch-criteria-hidden');
                });
                
                // Create results container and add it inside the target module (same container as form)
                var resultsDiv = document.createElement('div');
                resultsDiv.className = 'bearslivesearch-results bearslivesearch-page-results';
                resultsDiv.id = targetModule.id + '-results';
                resultsDiv.setAttribute('aria-live', 'polite');
                resultsDiv.setAttribute('aria-atomic', 'true');
                resultsDiv.innerHTML = '<div class="bearslivesearch-loading" role="status">Loading search results...</div>';
                
                // Insert results inside the target module (after the form)
                targetModule.appendChild(resultsDiv);
                results = resultsDiv;
                
                // Mark as transformed
                targetModule.setAttribute('data-transformed', 'true');
                
                // Update page title
                document.title = 'Search Results - ' + document.title.replace(/^Search Results - /, '');
                
                // Trigger search with current query
                setTimeout(function() {
                    doSearch(query, 1);
                }, 100);
            }

            // Check if we're already transformed or should be treated as search results page
            function isTransformedOrSearchPage() {
                return module.closest('.bearslivesearch').hasAttribute('data-transformed') || 
                       module.closest('.bearslivesearch').classList.contains('bearslivesearch-search-page') ||
                       (results && !results.classList.contains('bearslivesearch-results--hidden') && window.location.search.indexOf('q=') !== -1);
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var query = input.value;
                lastQuery = query;
                
                // If we're in separate page mode and NOT already transformed, transform the page
                if (searchMode === 'separate_page' && !isTransformedOrSearchPage()) {
                    transformPageToSearchResults(query);
                } else {
                    // Otherwise use AJAX (inline mode or already transformed)
                    doSearch(query, 1);
                }
            });

            // Function to set up live search
            function setupLiveSearch() {
                if (!input) return;
                input.addEventListener('input', function() {
                    var query = input.value;
                    var wasCleared = (!query || query.trim() === '') && (lastQuery && lastQuery.trim() !== '');
                    if (wasCleared) {
                        resetFiltersToDefault();
                    }
                    console.log('Input changed. Query:', query, 'Length:', query.length, 'Last query:', lastQuery);
                    if (query !== lastQuery) {
                        lastQuery = query;
                        console.log('Triggering search for:', query);
                        doSearch(query, 1);
                    }
                });
                // Handle native clear (x) in supporting browsers
                input.addEventListener('search', function() {
                    if (!input.value || input.value.trim() === '') {
                        resetFiltersToDefault();
                        doSearch('', 1);
                    }
                });
            }

            // Function to set up pagination
            function setupPagination() {
                if (results) {
                    results.addEventListener('click', function(e) {
                        var anchor = e.target.closest('a');
                        if (!anchor || !results.contains(anchor)) {
                            return;
                        }

                        // Allow default for non-left clicks or with modifier keys (new tab/window)
                        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
                            return;
                        }

                        var href = anchor.getAttribute('href') || '';
                        var isPagination = /[?&](?:page|start|limitstart)=\d+/.test(href) || !!anchor.closest('.pagination') || anchor.hasAttribute('data-page');

                        // If it's a normal result link, allow default navigation
                        if (!isPagination) {
                            return;
                        }

                        // Handle pagination via AJAX
                        e.preventDefault();
                        var page = 1;
                        var match = href && href.match(/[?&]page=(\d+)/);
                        if (match) {
                            page = parseInt(match[1], 10);
                        } else {
                            match = href && href.match(/[?&](?:start|limitstart)=(\d+)/);
                            if (match) {
                                var start = parseInt(match[1], 10);
                                var perPage = 10;
                                var perPageInput = module.querySelector('[name="results_limit"]');
                                if (perPageInput && perPageInput.value) {
                                    perPage = parseInt(perPageInput.value, 10) || 10;
                                }
                                page = Math.floor(start / perPage) + 1;
                            }
                        }
                        doSearch(input.value, page, true);
                    });
                }
            }

            // Check if we're already on a search results URL and auto-transform
            var urlParams = new URLSearchParams(window.location.search);
            var queryFromUrl = urlParams.get('q');
            
            console.log('Search mode:', searchMode);
            console.log('Query from URL:', queryFromUrl);
            console.log('Module element:', module);
            
            if (queryFromUrl && queryFromUrl.trim()) {
                console.log('Found search query in URL, auto-transforming...');
                
                // We're on a search results URL, auto-transform the page regardless of mode
                input.value = queryFromUrl;
                
                // Set form values from URL
                var searchphrase = urlParams.get('searchphrase') || 'exact';
                var ordering = urlParams.get('ordering') || 'newest';
                var resultsLimit = urlParams.get('results_limit') || '10';
                
                // Set radio buttons
                var searchphraseRadios = form.querySelectorAll('input[name="searchphrase"]');
                searchphraseRadios.forEach(function(radio) {
                    radio.checked = (radio.value === searchphrase);
                });
                
                // Set select values
                var orderingSelect = form.querySelector('select[name="ordering"]');
                if (orderingSelect) orderingSelect.value = ordering;
                
                var limitSelect = form.querySelector('select[name="results_limit"]');
                if (limitSelect) limitSelect.value = resultsLimit;
                
                // Set other form fields
                ['category', 'author', 'datefrom', 'dateto'].forEach(function(fieldName) {
                    var value = urlParams.get(fieldName);
                    if (value) {
                        var field = form.querySelector('[name="' + fieldName + '"]');
                        if (field) field.value = value;
                    }
                });
                
                // Force separate page mode for URL-based searches
                searchMode = 'separate_page';
                
                // Transform the page immediately
                console.log('Calling transformPageToSearchResults...');
                setTimeout(function() {
                    transformPageToSearchResults(queryFromUrl);
                }, 100);
            }

            // Attach change listeners to filters (non-search fields) to trigger AJAX
            function setupFilterChange() {
                if (!form) return;
                var controls = form.querySelectorAll('select, textarea, input:not([type="search"]):not([type="submit"]):not([type="button"])');
                controls.forEach(function(ctrl) {
                    var evtName = (ctrl.tagName === 'SELECT' || ctrl.type === 'checkbox' || ctrl.type === 'radio' || ctrl.type === 'date') ? 'change' : 'input';
                    ctrl.addEventListener(evtName, function() {
                        if (suppressFilterSearch) return;
                        // Always search page 1 on filter change; allow empty query searches
                        doSearch(input.value, 1, true);
                    });
                });
            }

            // Set up live search and pagination for all modes
            // This enables search-as-you-type functionality even in separate page mode
            enforceDefaultSearchPhrase();
            captureDefaultFilters();
            setupLiveSearch();
            setupPagination();
            setupFilterChange();

            // Re-setup live search after transformation
            var originalTransform = transformPageContent;
            transformPageContent = function(query, formData) {
                originalTransform.call(this, query, formData);
                
                // Update form and input references to the overlay search page
                var overlay = window.bearsSearchOverlay;
                if (overlay) {
                    form = overlay.querySelector('.bearslivesearch-form');
                    input = overlay.querySelector('input[type="search"]');
                    
                    // Set up event listeners for the new form in overlay
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();
                            var query = input.value;
                            lastQuery = query;
                            doSearch(query, 1);
                        });
                    }
                }
                
                // After transformation, set up live search
                enforceDefaultSearchPhrase();
                captureDefaultFilters();
                setupLiveSearch();
                setupPagination();
                setupFilterChange();
            };
        });
    });
})();
