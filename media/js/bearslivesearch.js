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
                if (!html || !html.trim() || /role="status".*no results/i.test(html)) {
                    results.classList.add('bearslivesearch-results--hidden');
                } else {
                    results.classList.remove('bearslivesearch-results--hidden');
                }
                input.focus();
            }

            function doSearch(query) {
                if (xhr) xhr.abort();
                if (!query.trim()) {
                    updateResults('');
                    return;
                }

                // Show loading indicator
                updateResults('<div class="bearslivesearch-loading" role="status">Searching...</div>');

                // Use the standard AJAX URL for this module
                var ajaxUrl = window.location.origin + '/index.php?option=com_ajax&module=bearslivesearch&method=search&format=raw&q=' + encodeURIComponent(query);

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
