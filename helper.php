<?php
/**
 * Bears AJAX Search (Joomla 5, no Finder, with Kunena support, PHP-side pagination)
 *
 * @version 2025.09.09
 * @package Bears AJAX Search
 * @author N6REJ
 * @email troy@hallhome.us
 * @website https://hallhome.us/software
 * @copyright Copyright (C) 2025 N6REJ
 * @license GNU General Public License version 3 or later; see License.txt
 * @since 2025.7.16
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ModuleHelper;

class ModBearslivesearchHelper
{
    /**
     * AJAX method for search - search Joomla articles and Kunena forum posts (if present)
     *
     * @return void Outputs search results directly
     */
    public static function searchAjax()
    {
        try {
            $app = Factory::getApplication();
            $input = $app->input;
            $query = trim($input->getString('q', ''));
            $dateFrom = trim($input->getString('datefrom', ''));
            $dateTo = trim($input->getString('dateto', ''));
            $categoryCheck = (int) $input->get('category', 0);
            $authorCheck = (int) $input->get('author', 0);
            // Hidden categories can arrive as an array of ints
            $hiddenCategories = $input->get('hidden_categories', [], 'array');
            $hiddenCategories = array_values(array_filter(array_map('intval', (array) $hiddenCategories), function($v){ return $v > 0; }));
            $ordering = $input->getString('ordering', 'newest');
            $searchPhrase = strtolower($input->getString('searchphrase', 'exact'));
            
            // Debug logging
            Log::add('AJAX Search called with query: ' . $query . ' | datefrom=' . $dateFrom . ' | dateto=' . $dateTo, Log::INFO, 'mod_bearslivesearch');
            
            // Allow search to proceed if any filter is present, even with empty query
            $hasAnyFilter = ($query !== '' || $dateFrom !== '' || $dateTo !== '' || $categoryCheck || $authorCheck);
            if (!$hasAnyFilter) {
                echo '<div role="status">' . Text::_('MOD_BEARSLIVESEARCH_EMPTY_QUERY') . '</div>';
                return;
            }
        } catch (Exception $e) {
            Log::add('AJAX Search initialization error: ' . $e->getMessage(), Log::ERROR, 'mod_bearslivesearch');
            echo '<div role="alert">Search initialization error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            return;
        }

        // Pagination
        $resultsLimit = 10;
        // Enforce hard max of 200 regardless of admin value
        $resultsLimit = min(200, max(1, (int) $input->get('results_limit', $resultsLimit)));
        $page = max(1, (int) $input->get('page', 1));
        $offset = ($page - 1) * $resultsLimit;


        $db = Factory::getDbo();
        // Determine default 'hidden' categories for com_content
        $defaultHiddenCategories = [];
        try {
            $catQ = $db->getQuery(true)
                ->select($db->qn('id'))
                ->from($db->qn('#__categories'))
                ->where($db->qn('extension') . ' = ' . $db->q('com_content'))
                ->where('(LOWER(' . $db->qn('title') . ') = ' . $db->q('hidden') . ' OR ' . $db->qn('alias') . ' = ' . $db->q('hidden') . ')');
            $db->setQuery($catQ);
            $defaultHiddenCategories = array_map('intval', (array) $db->loadColumn());
        } catch (Exception $e) {
            Log::add('Default hidden categories lookup failed: ' . $e->getMessage(), Log::WARNING, 'mod_bearslivesearch');
        }
        $effectiveHiddenCategories = array_values(array_unique(array_merge($hiddenCategories, $defaultHiddenCategories)));

        $searchLike = '%' . $db->escape($query, true) . '%';
        // Build WHERE fragments for articles
        $articleWhere = [];
        $articleWhere[] = 'state = 1';
        // Category filter
        $categoryId = (int) $input->get('category', 0);
        if ($categoryId) {
            $articleWhere[] = 'catid = ' . (int)$categoryId;
        }
        // Exclude hidden categories (module-configured + default "hidden")
        if (!empty($effectiveHiddenCategories)) {
            $articleWhere[] = 'catid NOT IN (' . implode(',', $effectiveHiddenCategories) . ')';
        }
        // Author filter
        $authorId = (int) $input->get('author', 0);
        if ($authorId) {
            $articleWhere[] = 'created_by = ' . (int)$authorId;
        }
        // Date filters (Joomla articles use DATETIME in 'created')
        if (!empty($dateFrom)) {
            $articleWhere[] = $db->qn('created') . ' >= ' . $db->q($dateFrom . ' 00:00:00');
        }
        if (!empty($dateTo)) {
            $articleWhere[] = $db->qn('created') . ' <= ' . $db->q($dateTo . ' 23:59:59');
        }
        // Text search conditions for articles
        if ($query !== '') {
            if ($searchPhrase === 'anywords' || $searchPhrase === 'allwords') {
                $terms = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
                $termConds = [];
                foreach ((array) $terms as $t) {
                    $likeTerm = '%' . $db->escape($t, true) . '%';
                    $termConds[] = '(' .
                        $db->qn('title') . ' LIKE ' . $db->q($likeTerm) . ' OR ' .
                        $db->qn('introtext') . ' LIKE ' . $db->q($likeTerm) . ' OR ' .
                        $db->qn('fulltext') . ' LIKE ' . $db->q($likeTerm) .
                    ')';
                }
                if (!empty($termConds)) {
                    $glue = ($searchPhrase === 'allwords') ? ' AND ' : ' OR ';
                    $articleWhere[] = '(' . implode($glue, $termConds) . ')';
                }
            } else { // exact
                $articleWhere[] = '(' .
                    $db->qn('title') . ' LIKE ' . $db->q($searchLike) . ' OR ' .
                    $db->qn('introtext') . ' LIKE ' . $db->q($searchLike) . ' OR ' .
                    $db->qn('fulltext') . ' LIKE ' . $db->q($searchLike) .
                ')';
            }
        }

        // Count articles
        $articleCount = 0;
        try {
            $articleCountSql = 'SELECT COUNT(*) FROM ' . $db->qn('#__content');
            if (!empty($articleWhere)) {
                $articleCountSql .= ' WHERE ' . implode(' AND ', $articleWhere);
            }
            $db->setQuery($articleCountSql);
            $articleCount = (int) $db->loadResult();
        } catch (Exception $e) {
            Log::add('Article COUNT error: ' . $e->getMessage(), Log::ERROR, 'mod_bearslivesearch');
        }

        // --- Kunena Forum (optional) WHERE and COUNT ---
        $kunenaInstalled = false;
        $kunenaCount = 0;
        $kunenaSelectSql = '';
        $articleSelectSql = '';
        $orderSql = '';
        try {
            $kunenaTable = $db->replacePrefix('#__kunena_messages');
            $tables = $db->getTableList();
            $kunenaInstalled = in_array($kunenaTable, $tables);
        } catch (Exception $e) {
            $kunenaInstalled = false;
        }

        $messageColumn = '';
        if ($kunenaInstalled) {
            try {
                $columns = $db->getTableColumns('#__kunena_messages');
                if (isset($columns['message'])) {
                    $messageColumn = 'message';
                } elseif (isset($columns['mesage'])) {
                    $messageColumn = 'mesage';
                } elseif (isset($columns['text'])) {
                    $messageColumn = 'text';
                } elseif (isset($columns['content'])) {
                    $messageColumn = 'content';
                }
            } catch (Exception $e) {
                $messageColumn = '';
            }
        }

        $kunenaWhere = [];
        if ($kunenaInstalled && $messageColumn) {
            $kunenaWhere[] = 'm.hold = 0';
            $kunenaWhere[] = 't.hold = 0';
            // Date filters
            if (!empty($dateFrom)) {
                $kunenaWhere[] = 'm.time >= ' . (int) strtotime($dateFrom . ' 00:00:00');
            }
            if (!empty($dateTo)) {
                $kunenaWhere[] = 'm.time <= ' . (int) strtotime($dateTo . ' 23:59:59');
            }
            // Text search
            if ($query !== '') {
                if ($searchPhrase === 'anywords' || $searchPhrase === 'allwords') {
                    $terms = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
                    $termConds = [];
                    foreach ((array) $terms as $t) {
                        $likeTerm = '%' . $db->escape($t, true) . '%';
                        $termConds[] = '(' .
                            'm.' . $messageColumn . ' LIKE ' . $db->q($likeTerm) . ' OR ' .
                            't.subject LIKE ' . $db->q($likeTerm) .
                        ')';
                    }
                    if (!empty($termConds)) {
                        $glue = ($searchPhrase === 'allwords') ? ' AND ' : ' OR ';
                        $kunenaWhere[] = '(' . implode($glue, $termConds) . ')';
                    }
                } else { // exact
                    $kunenaWhere[] = '(' .
                        'm.' . $messageColumn . ' LIKE ' . $db->q($searchLike) . ' OR ' .
                        't.subject LIKE ' . $db->q($searchLike) .
                    ')';
                }
            }

            // Kunena COUNT
            try {
                $kunenaCountSql = 'SELECT COUNT(*) FROM ' . $db->qn('#__kunena_messages') . ' AS m INNER JOIN ' . $db->qn('#__kunena_topics') . ' AS t ON m.thread = t.id';
                if (!empty($kunenaWhere)) {
                    $kunenaCountSql .= ' WHERE ' . implode(' AND ', $kunenaWhere);
                }
                $db->setQuery($kunenaCountSql);
                $kunenaCount = (int) $db->loadResult();
            } catch (Exception $e) {
                Log::add('Kunena COUNT error: ' . $e->getMessage(), Log::WARNING, 'mod_bearslivesearch');
                $kunenaCount = 0;
            }
        }

        // Total matches across sources
        $totalMatches = (int) $articleCount + (int) $kunenaCount;

        // Build ORDER BY SQL
        $ordering = in_array($ordering, ['newest','oldest','popular','alpha'], true) ? $ordering : 'newest';
        switch ($ordering) {
            case 'alpha':
                $orderSql = 'title ASC, created DESC';
                break;
            case 'popular':
                $orderSql = 'hits DESC, created DESC';
                break;
            case 'oldest':
                $orderSql = 'created ASC';
                break;
            case 'newest':
            default:
                $orderSql = 'created DESC';
                break;
        }

        // Build article SELECT
        $articleSelectSql = 'SELECT ' .
            "'article' AS source_type, " .
            $db->qn('title') . ' AS title, ' .
            $db->qn('created') . ' AS created, ' .
            $db->qn('hits') . ' AS hits, ' .
            $db->qn('introtext') . ' AS content_intro, ' .
            $db->qn('fulltext') . ' AS content_full, ' .
            $db->qn('id') . ' AS article_id, ' .
            'NULL AS kunena_msg_id, NULL AS kunena_thread_id, NULL AS kunena_cat_id, NULL AS kunena_message ' .
            'FROM ' . $db->qn('#__content');
        if (!empty($articleWhere)) {
            $articleSelectSql .= ' WHERE ' . implode(' AND ', $articleWhere);
        }

        // Build Kunena SELECT if available
        if ($kunenaInstalled && $messageColumn) {
            $kunenaSelectSql = 'SELECT ' .
                "'kunena' AS source_type, " .
                't.subject AS title, ' .
                'FROM_UNIXTIME(m.time) AS created, ' .
                '0 AS hits, ' .
                'NULL AS content_intro, NULL AS content_full, NULL AS article_id, ' .
                'm.id AS kunena_msg_id, m.thread AS kunena_thread_id, t.catid AS kunena_cat_id, ' .
                'm.' . $messageColumn . ' AS kunena_message ' .
                'FROM ' . $db->qn('#__kunena_messages') . ' AS m INNER JOIN ' . $db->qn('#__kunena_topics') . ' AS t ON m.thread = t.id';
            if (!empty($kunenaWhere)) {
                $kunenaSelectSql .= ' WHERE ' . implode(' AND ', $kunenaWhere);
            }
        }

        // Build UNION query
        $unionSql = '';
        if ($kunenaInstalled && $messageColumn) {
            $unionSql = '(' . $articleSelectSql . ') UNION ALL (' . $kunenaSelectSql . ')';
        } else {
            $unionSql = $articleSelectSql;
        }
        $unionSql .= ' ORDER BY ' . $orderSql;

        // Fetch a single page window from DB
        try {
            $db->setQuery($unionSql, $offset, $resultsLimit);
            $rows = $db->loadObjectList();
        } catch (Exception $e) {
            Log::add('Unified query error: ' . $e->getMessage(), Log::ERROR, 'mod_bearslivesearch');
            echo '<div role="alert">Search error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            return;
        }

        // Output results
        if (empty($rows)) {
            echo '<div role="status">' . Text::_('MOD_BEARSLIVESEARCH_NO_RESULTS') . '</div>';
            return;
        }

        $queryDisplay = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
        $startResult = $offset + 1;
        $endResult = $offset + count($rows);
        $output = '<div class="bearslivesearch-summary">Results ' . $startResult . '-' . $endResult . ' of ' . $totalMatches;
        if ($queryDisplay !== '') {
            $output .= ' for <strong>"' . $queryDisplay . '"</strong>';
        }
        $output .= '</div>';
        $output .= '<ul class="bearslivesearch-list" role="list">';
        foreach ($rows as $i => $row) {
            $type = isset($row->source_type) ? $row->source_type : 'article';
            $title = htmlspecialchars((string)($row->title ?? ''), ENT_QUOTES, 'UTF-8');
            if ($type === 'article') {
                $descRaw = (string) ($row->content_intro ?? ($row->content_full ?? ''));
                $linkRaw = 'index.php?option=com_content&view=article&id=' . (int)($row->article_id ?? 0);
            } else { // kunena
                $descRaw = (string) ($row->kunena_message ?? '');
                $catid = (int)($row->kunena_cat_id ?? 0);
                $thread = (int)($row->kunena_thread_id ?? 0);
                $msgid = (int)($row->kunena_msg_id ?? 0);
                $linkRaw = 'index.php?option=com_kunena&view=topic&catid=' . $catid . '&id=' . $thread . '#msg' . $msgid;
            }
            $desc = htmlspecialchars(mb_substr(strip_tags($descRaw), 0, 200), ENT_QUOTES, 'UTF-8');
            $link = \Joomla\CMS\Router\Route::_($linkRaw);
            $output .= '<li role="listitem">';
            $output .= '<a href="' . $link . '" class="bearslivesearch-title-link"><span class="bearslivesearch-title">' . ($offset + $i + 1) . '. ' . $title;
            if ($type === 'kunena') {
                $output .= ' <span class="forum-label">[Forum Post]</span>';
            }
            $output .= '</span></a>';
            if (!empty($desc)) {
                $output .= '<div class="bearslivesearch-result">' . $desc . '</div>';
            }
            $output .= '</li>';
        }
        $output .= '</ul>';

        // Pagination (accessible) - Joomla 5 compatible
        $totalPages = max(1, (int) ceil($totalMatches / $resultsLimit));
        if ($totalPages > 1) {
            $paginationHtml = self::buildPagination($page, $totalPages, $query, $input);
            $output .= '<nav class="bearslivesearch-pagination" aria-label="Pagination">' . $paginationHtml . '</nav>';
        }
        echo $output;
    }

    /**
     * Build pagination HTML for Joomla 5 compatibility
     *
     * @param int $currentPage Current page number
     * @param int $totalPages Total number of pages
     * @param string $query Search query
     * @param \Joomla\Input\Input $input Input object
     * @return string Pagination HTML
     */
    private static function buildPagination($currentPage, $totalPages, $query, $input)
    {
        $moduleId = (int) $input->get('moduleId', 0);
        $searchQuery = rawurlencode($query);
        $ajaxBase = 'index.php?option=com_ajax&module=bearslivesearch&method=search&format=raw&q=' . $searchQuery;
        if ($moduleId) {
            $ajaxBase .= '&moduleId=' . $moduleId;
        }

        // Add other search parameters
        $params = [];
        $searchParams = ['searchphrase', 'ordering', 'results_limit', 'category', 'author', 'datefrom', 'dateto'];
        foreach ($searchParams as $param) {
            $value = $input->get($param, '');
            if (!empty($value)) {
                $params[] = $param . '=' . urlencode($value);
            }
        }
        // Preserve hidden categories across pagination
        $hidCatsForPage = $input->get('hidden_categories', [], 'array');
        foreach ((array)$hidCatsForPage as $hid) {
            $hid = (int)$hid;
            if ($hid > 0) {
                $params[] = 'hidden_categories[]=' . $hid;
            }
        }
        // Also include default 'hidden' categories by title/alias so pagination preserves them
        try {
            $db = Factory::getDbo();
            $catQ = $db->getQuery(true)
                ->select($db->qn('id'))
                ->from($db->qn('#__categories'))
                ->where($db->qn('extension') . ' = ' . $db->q('com_content'))
                ->where('(LOWER(' . $db->qn('title') . ') = ' . $db->q('hidden') . ' OR ' . $db->qn('alias') . ' = ' . $db->q('hidden') . ')');
            $db->setQuery($catQ);
            $defaultHiddenCategories = array_map('intval', (array) $db->loadColumn());
            $presentHidden = array_map('intval', (array) $hidCatsForPage);
            foreach ($defaultHiddenCategories as $hid) {
                if ($hid > 0 && !in_array($hid, $presentHidden, true)) {
                    $params[] = 'hidden_categories[]=' . $hid;
                }
            }
        } catch (Exception $e) {
            // ignore pagination enrichment errors
        }
        if (!empty($params)) {
            $ajaxBase .= '&' . implode('&', $params);
        }

        $html = '<ul class="pagination">';

        // Previous page
        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $ajaxBase . '&page=' . $prevPage . '" aria-label="Previous page">Previous</a>';
            $html .= '</li>';
        }

        // Page numbers
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);

        // First page if not in range
        if ($startPage > 1) {
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $ajaxBase . '&page=1">1</a>';
            $html .= '</li>';
            if ($startPage > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Page range
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i == $currentPage) {
                $html .= '<li class="page-item active">';
                $html .= '<span class="page-link" aria-current="page">' . $i . '</span>';
                $html .= '</li>';
            } else {
                $html .= '<li class="page-item">';
                $html .= '<a class="page-link" href="' . $ajaxBase . '&page=' . $i . '">' . $i . '</a>';
                $html .= '</li>';
            }
        }

        // Last page if not in range
        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $ajaxBase . '&page=' . $totalPages . '">' . $totalPages . '</a>';
            $html .= '</li>';
        }

        // Next page
        if ($currentPage < $totalPages) {
            $nextPage = $currentPage + 1;
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $ajaxBase . '&page=' . $nextPage . '" aria-label="Next page">Next</a>';
            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * AJAX method to get template positions for admin form
     *
     * @return void Outputs JSON response
     */
    public static function getTemplatePositionsAjax()
    {
        try {
            $app = Factory::getApplication();
            
            // Only allow in admin
            if (!$app->isClient('administrator')) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }

            $positions = [];
            
            // Get the current template
            $template = $app->getTemplate();
            
            // Path to templateDetails.xml file
            $templatePath = JPATH_THEMES . '/' . $template . '/templateDetails.xml';

            if (file_exists($templatePath)) {
                $xml = simplexml_load_file($templatePath);

                if ($xml && isset($xml->positions->position)) {
                    foreach ($xml->positions->position as $position) {
                        $pos = trim((string)$position);
                        if (!empty($pos)) {
                            $positions[] = $pos;
                        }
                    }

                    // Sort positions alphabetically
                    sort($positions);
                }
            }

            echo json_encode(['success' => true, 'data' => $positions]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    /**
     * Main AJAX handler method for Joomla 5
     *
     * @return void Outputs response directly
     */
    public static function getAjax()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $method = $input->get('method', '');
        
        switch ($method) {
            case 'search':
                self::searchAjax();
                break;
            case 'getTemplatePositions':
                self::getTemplatePositionsAjax();
                break;
            default:
                echo json_encode(['error' => 'Invalid method']);
                break;
        }
    }
}
