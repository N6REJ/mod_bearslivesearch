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

            function updateResults(html) {
                results.innerHTML = html;
                results.focus();
            }

            function doSearch(query) {
                if (xhr) xhr.abort();
                if (!query.trim()) {
                    updateResults('');
                    return;
                }

                // Show loading indicator
                updateResults('<div class="bearslivesearch-loading" role="status">Searching...</div>');

                // Try different URL formats for the AJAX request
                var urlFormats = [
                    // First try the test method to verify the AJAX system is working
                    window.location.origin + '/index.php?option=com_ajax&module=bearslivesearch&method=test&format=raw',

                    // Then try different formats for the search method
                    // Absolute URLs with window.location.origin
                    window.location.origin + '/index.php?option=com_ajax&module=bearslivesearch&method=search&format=raw&q=' + encodeURIComponent(query),
                    window.location.origin + '/index.php?option=com_ajax&module=bearslivesearch&format=raw&q=' + encodeURIComponent(query),

                    // Relative URLs with and without leading slash
                    '/index.php?option=com_ajax&module=bearslivesearch&method=search&format=raw&q=' + encodeURIComponent(query),
                    'index.php?option=com_ajax&module=bearslivesearch&method=search&format=raw&q=' + encodeURIComponent(query),

                    // Try with different module name formats
                    window.location.origin + '/index.php?option=com_ajax&module=mod_bearslivesearch&method=search&format=raw&q=' + encodeURIComponent(query),
                    '/index.php?option=com_ajax&module=mod_bearslivesearch&method=search&format=raw&q=' + encodeURIComponent(query),

                    // Try with different format parameters
                    window.location.origin + '/index.php?option=com_ajax&module=bearslivesearch&method=search&format=json&q=' + encodeURIComponent(query),
                    window.location.origin + '/index.php?option=com_ajax&module=bearslivesearch&method=search&q=' + encodeURIComponent(query)
                ];

                var currentUrlIndex = 0;

                function tryNextUrl() {
                    if (currentUrlIndex >= urlFormats.length) {
                        console.error('All AJAX URL formats failed');
                        // Fallback to direct URL access
                        var fallbackUrl = window.location.origin + '/index.php?option=com_search&searchword=' + encodeURIComponent(query);
                        console.log('Falling back to direct search URL:', fallbackUrl);
                        updateResults('<div role="alert">AJAX search failed. <a href="' + fallbackUrl + '">Click here to search</a> or try again later.</div>');
                        return;
                    }

                    var ajaxUrl = urlFormats[currentUrlIndex];
                    console.log('Trying AJAX URL format ' + (currentUrlIndex + 1) + ':', ajaxUrl);

                    xhr = new XMLHttpRequest();
                    xhr.open('GET', ajaxUrl, true);
                    xhr.timeout = 5000; // 5 seconds timeout

                    // Handle timeout
                    xhr.ontimeout = function() {
                        console.error('AJAX request timed out for format ' + (currentUrlIndex + 1));
                        currentUrlIndex++;
                        tryNextUrl();
                    };

                    // Handle network errors
                    xhr.onerror = function() {
                        console.error('Network error for format ' + (currentUrlIndex + 1));
                        currentUrlIndex++;
                        tryNextUrl();
                    };
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            console.log('AJAX response status for format ' + (currentUrlIndex + 1) + ':', xhr.status);
                            if (xhr.status === 200) {
                                // Special case for test method
                                if (currentUrlIndex === 0) {
                                    console.log('Test method successful, response:', xhr.responseText);
                                    // Test was successful, now try the actual search
                                    currentUrlIndex = 1; // Skip to the first search URL
                                    tryNextUrl();
                                } else {
                                    updateResults(xhr.responseText);
                                }
                            } else {
                                console.error('AJAX error for format ' + (currentUrlIndex + 1) + ':', xhr.status, xhr.statusText);
                                currentUrlIndex++;
                                tryNextUrl();
                            }
                        }
                    };
                    xhr.send();
                }

                tryNextUrl();
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var query = input.value;
                lastQuery = query;
                doSearch(query);
            });

            input.addEventListener('input', function() {
                var query = input.value;
                if (query !== lastQuery) {
                    lastQuery = query;
                    doSearch(query);
                }
            });
        });
    });
})();
