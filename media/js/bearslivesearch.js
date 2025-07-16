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
                // AJAX call to Joomla endpoint (to be implemented)
                xhr = new XMLHttpRequest();
                xhr.open('GET', 'index.php?option=com_ajax&module=bearslivesearch&method=search&format=raw&q=' + encodeURIComponent(query), true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        updateResults(xhr.responseText);
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
