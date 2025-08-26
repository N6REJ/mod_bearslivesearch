<?php
/**
 * Bears AJAX Search (Joomla 5, no Finder, with Kunena support, PHP-side pagination)
 *
 * @version 2025.08.26.1
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
        $allResults = [];

        // --- Joomla Articles ---
        $queryObj = $db->getQuery(true)
            ->select([
                $db->qn('id'),
                $db->qn('title'),
                $db->qn('introtext'),
                $db->qn('fulltext'),
                $db->qn('alias'),
                $db->qn('catid'),
                $db->qn('created'),
                $db->qn('hits')
            ])
            ->from($db->qn('#__content'))
            ->where('state = 1');

        // Text search conditions according to searchPhrase
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
                    $queryObj->where('(' . implode($glue, $termConds) . ')');
                }
            } else { // exact
                $queryObj->where('(' .
                    $db->qn('title') . ' LIKE ' . $db->q($searchLike) . ' OR ' .
                    $db->qn('introtext') . ' LIKE ' . $db->q($searchLike) . ' OR ' .
                    $db->qn('fulltext') . ' LIKE ' . $db->q($searchLike) .
                ')');
            }
        }

        $queryObj->order('created DESC');

        // Category filter
        $categoryId = (int) $input->get('category', 0);
        if ($categoryId) {
            $queryObj->where('catid = ' . $categoryId);
        }
        // Exclude hidden categories (module-configured + default "hidden")
        if (!empty($effectiveHiddenCategories)) {
            $queryObj->where('catid NOT IN (' . implode(',', $effectiveHiddenCategories) . ')');
        }
        // Author filter
        $authorId = (int) $input->get('author', 0);
        if ($authorId) {
            $queryObj->where('created_by = ' . $authorId);
        }
        // Date filters (Joomla articles use DATETIME in 'created')
        if (!empty($dateFrom)) {
            $queryObj->where($db->qn('created') . ' >= ' . $db->q($dateFrom . ' 00:00:00'));
        }
        if (!empty($dateTo)) {
            $queryObj->where($db->qn('created') . ' <= ' . $db->q($dateTo . ' 23:59:59'));
        }
        try {
            $db->setQuery($queryObj);
            $articleResults = $db->loadObjectList();
        } catch (Exception $e) {
            Log::add('Article query error: ' . $e->getMessage() . ' | SQL: ' . $queryObj, Log::ERROR, 'mod_bearslivesearch');
            echo '<div role="alert">Article search error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            return;
        }
        foreach ($articleResults as $item) {
            $allResults[] = [
                'type' => 'article',
                'title' => $item->title,
                'desc' => strip_tags($item->introtext ?: $item->fulltext),
                'created' => $item->created,
                'hits' => (int)($item->hits ?? 0),
                'link' => 'index.php?option=com_content&view=article&id=' . (int)$item->id
            ];
        }

        // --- Kunena Forum Posts (if installed) ---
        $kunenaTable = $db->replacePrefix('#__kunena_messages');
        $tables = $db->getTableList();
        $kunenaInstalled = in_array($kunenaTable, $tables);
        if ($kunenaInstalled) {
            try {
                // Check what columns exist in the kunena_messages table
                $columns = $db->getTableColumns('#__kunena_messages');
                $messageColumn = '';
                
                // Different Kunena versions use different column names for message content
                if (isset($columns['message'])) {
                    $messageColumn = 'message';
                } elseif (isset($columns['mesage'])) {
                    $messageColumn = 'mesage'; // Some versions have this typo
                } elseif (isset($columns['text'])) {
                    $messageColumn = 'text';
                } elseif (isset($columns['content'])) {
                    $messageColumn = 'content';
                }
                
                if ($messageColumn) {
                    // Convert date filters to timestamps for Kunena 'time' column
                    $kunenaFromTs = !empty($dateFrom) ? strtotime($dateFrom . ' 00:00:00') : null;
                    $kunenaToTs = !empty($dateTo) ? strtotime($dateTo . ' 23:59:59') : null;

                    $kunenaQuery = $db->getQuery(true)
                        ->select(['m.id', 'm.' . $messageColumn, 'm.thread', 'm.userid', 'm.time', 't.subject', 't.catid'])
                        ->from($db->qn('#__kunena_messages', 'm'))
                        ->join('INNER', $db->qn('#__kunena_topics', 't') . ' ON m.thread = t.id')
                        ->where('m.hold = 0')
                        ->where('t.hold = 0');

                    // Text search for Kunena according to searchPhrase
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
                                $kunenaQuery->where('(' . implode($glue, $termConds) . ')');
                            }
                        } else { // exact
                            $kunenaQuery->where('(' .
                                'm.' . $messageColumn . ' LIKE ' . $db->q($searchLike) . ' OR ' .
                                't.subject LIKE ' . $db->q($searchLike) .
                            ')');
                        }
                    }

                    $kunenaQuery->order('m.time DESC');


                    if (!empty($kunenaFromTs)) {
                        $kunenaQuery->where('m.time >= ' . (int)$kunenaFromTs);
                    }
                    if (!empty($kunenaToTs)) {
                        $kunenaQuery->where('m.time <= ' . (int)$kunenaToTs);
                    }

                    $db->setQuery($kunenaQuery);
                    $kunenaResults = $db->loadObjectList();
                    foreach ($kunenaResults as $kitem) {
                        $messageContent = $kitem->{$messageColumn} ?? '';
                        $allResults[] = [
                            'type' => 'kunena',
                            'title' => $kitem->subject,
                            'desc' => strip_tags($messageContent),
                            'created' => date('Y-m-d H:i:s', (int)$kitem->time),
                            'hits' => 0,
                            'link' => 'index.php?option=com_kunena&view=topic&catid=' . (int)$kitem->catid . '&id=' . (int)$kitem->thread . '#msg' . (int)$kitem->id
                        ];
                    }
                } else {
                    Log::add('Kunena message column not found. Available columns: ' . implode(', ', array_keys($columns)), Log::WARNING, 'mod_bearslivesearch');
                }
            } catch (Exception $e) {
                Log::add('Kunena query error: ' . $e->getMessage(), Log::WARNING, 'mod_bearslivesearch');
                // Continue without Kunena results if there's an error
            }
        }

        // Sort results per requested ordering
        $ordering = in_array($ordering, ['newest','oldest','popular','alpha'], true) ? $ordering : 'newest';
        usort($allResults, function($a, $b) use ($ordering) {
            $aTitle = $a['title'] ?? '';
            $bTitle = $b['title'] ?? '';
            $aHits = isset($a['hits']) ? (int)$a['hits'] : 0;
            $bHits = isset($b['hits']) ? (int)$b['hits'] : 0;
            $aTime = isset($a['created']) ? strtotime($a['created']) : 0;
            $bTime = isset($b['created']) ? strtotime($b['created']) : 0;
            switch ($ordering) {
                case 'alpha':
                    $cmp = strcasecmp($aTitle, $bTitle);
                    if ($cmp !== 0) return $cmp;
                    // tie-breaker: newest first
                    return $bTime <=> $aTime;
                case 'popular':
                    if ($bHits !== $aHits) return $bHits <=> $aHits; // desc
                    return $bTime <=> $aTime; // tie: newest first
                case 'oldest':
                    return $aTime <=> $bTime; // asc
                case 'newest':
                default:
                    return $bTime <=> $aTime; // desc
            }
        });

        $totalMatches = count($allResults);
        $pagedResults = array_slice($allResults, $offset, $resultsLimit);

        // Output results
        if (empty($pagedResults)) {
            echo '<div role="status">' . Text::_('MOD_BEARSLIVESEARCH_NO_RESULTS') . '</div>';
            return;
        }

        $queryDisplay = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
        $startResult = $offset + 1;
        $endResult = $offset + count($pagedResults);
        $output = '<div class="bearslivesearch-summary">Results ' . $startResult . '-' . $endResult . ' of ' . $totalMatches;
        if ($queryDisplay !== '') {
            $output .= ' for <strong>"' . $queryDisplay . '"</strong>';
        }
        $output .= '</div>';
        $output .= '<ul class="bearslivesearch-list" role="list">';
        foreach ($pagedResults as $i => $item) {
            $title = htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8');
            $desc = htmlspecialchars(mb_substr($item['desc'], 0, 200), ENT_QUOTES, 'UTF-8');
            $link = \Joomla\CMS\Router\Route::_($item['link']);
            $output .= '<li role="listitem">';
            $output .= '<a href="' . $link . '" class="bearslivesearch-title-link"><span class="bearslivesearch-title">' . ($offset + $i + 1) . '. ' . $title;
            if ($item['type'] === 'kunena') {
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
