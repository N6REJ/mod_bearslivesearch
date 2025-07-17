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
                if (!html || !html.trim()) {
                    // Use the language string from the DOM or fallback
                    var noResults = module.getAttribute('data-no-results') || 'No results found.';
                    results.innerHTML = '<div class="bearslivesearch-no-results" role="status">' + noResults + '</div>';
                } else {
                    results.innerHTML = html;
                }
                results.classList.remove('bearslivesearch-results--hidden');
                input.focus();
            }

            function doSearch(query, page) {
                if (xhr) xhr.abort();
                if (!query.trim()) {
                    updateResults('');
                    return;
                }

                // Show loading indicator
                updateResults('<div class="bearslivesearch-loading" role="status">Searching...</div>');

                // Serialize all form fields
                var params = [];
                var formData = new FormData(form);
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
                var ajaxUrl = window.location.origin + '/index.php?option=com_ajax&module=bearslivesearch&method=search&format=raw&' + params.join('&');

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
                doSearch(query, 1);
            });

            input.addEventListener('input', function() {
                var query = input.value;
                if (query !== lastQuery) {
                    lastQuery = query;
                    doSearch(query, 1);
                }
            });

            // Pagination click handler (event delegation)
            results.addEventListener('click', function(e) {
                var anchor = e.target.closest('a');
                if (anchor && results.contains(anchor)) {
                    e.preventDefault();
                    var href = anchor.getAttribute('href');
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
                    doSearch(input.value, page);
                }
            });
        });
    });
})();
