<?php
/**
 * Bears AJAX Search (Joomla 5, no Finder, with Kunena support, PHP-side pagination)
 *
 * @version 2025.07.19.10
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
        $app = Factory::getApplication();
        $input = $app->input;
        $query = trim($input->getString('q', ''));
        if ($query === '') {
            echo '<div role="status">' . Text::_('MOD_BEARSLIVESEARCH_EMPTY_QUERY') . '</div>';
            return;
        }

        // Pagination
        $resultsLimit = 10;
        // Enforce hard max of 200 regardless of admin value
        $resultsLimit = min(200, max(1, (int) $input->get('results_limit', $resultsLimit)));
        $page = max(1, (int) $input->get('page', 1));
        $offset = ($page - 1) * $resultsLimit;
        $maxFetch = 100; // Max results to fetch from each source for merging

        $db = Factory::getDbo();
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
                $db->qn('created')
            ])
            ->from($db->qn('#__content'))
            ->where('state = 1')
            ->where('(' .
                $db->qn('title') . ' LIKE ' . $db->q($searchLike) . ' OR ' .
                $db->qn('introtext') . ' LIKE ' . $db->q($searchLike) . ' OR ' .
                $db->qn('fulltext') . ' LIKE ' . $db->q($searchLike) .
            ')')
            ->order('created DESC')
            ->setLimit($maxFetch);
        // Category filter
        $categoryId = (int) $input->get('category', 0);
        if ($categoryId) {
            $queryObj->where('catid = ' . $categoryId);
        }
        // Author filter
        $authorId = (int) $input->get('author', 0);
        if ($authorId) {
            $queryObj->where('created_by = ' . $authorId);
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
                'link' => 'index.php?option=com_content&view=article&id=' . (int)$item->id
            ];
        }

        // --- Kunena Forum Posts (if installed) ---
        $db = Factory::getDbo();
        $kunenaTable = $db->replacePrefix('#__kunena_messages');
        $tables = $db->getTableList();
        $kunenaInstalled = in_array($kunenaTable, $tables);
        if ($kunenaInstalled) {
            $kunenaQuery = $db->getQuery(true)
                ->select(['m.id', 'm.message', 'm.thread', 'm.userid', 'm.time', 't.subject', 't.catid'])
                ->from($db->qn('#__kunena_messages', 'm'))
                ->join('INNER', $db->qn('#__kunena_topics', 't') . ' ON m.thread = t.id')
                ->where('m.message LIKE ' . $db->q($searchLike))
                ->where('m.hold = 0')
                ->where('t.hold = 0')
                ->order('m.time DESC')
                ->setLimit($maxFetch);
            $db->setQuery($kunenaQuery);
            $kunenaResults = $db->loadObjectList();
            foreach ($kunenaResults as $kitem) {
                $allResults[] = [
                    'type' => 'kunena',
                    'title' => $kitem->subject,
                    'desc' => strip_tags($kitem->message),
                    'created' => date('Y-m-d H:i:s', (int)$kitem->time),
                    'link' => 'index.php?option=com_kunena&view=topic&catid=' . (int)$kitem->catid . '&id=' . (int)$kitem->thread . '#msg' . (int)$kitem->id
                ];
            }
        }

        // Sort all results by created DESC
        usort($allResults, function($a, $b) {
            return strcmp($b['created'], $a['created']);
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
        $output = '<div class="bearslivesearch-summary">Results ' . $startResult . '-' . $endResult . ' of ' . $totalMatches . ' for <strong>"' . $queryDisplay . '"</strong></div>';
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

        // Pagination (accessible)
        $totalPages = max(1, (int) ceil($totalMatches / $resultsLimit));
        if ($totalPages > 1) {
            \Joomla\CMS\HTML\HTMLHelper::_('behavior.core');
            $pagination = new \JPagination($totalMatches, $offset, $resultsLimit);
            $paginationHtml = $pagination->getPagesLinks();
            // Accessibility patch: add aria-current and aria-labels
            $paginationHtml = preg_replace_callback(
                '/<li[^>]*class="[^"]*active[^"]*"[^>]*>\s*<span[^>]*>(\d+)<\/span>\s*<\/li>/i',
                function ($m) {
                    return '<li class="active"><span aria-current="page">' . $m[1] . '</span></li>';
                },
                $paginationHtml
            );
            $paginationHtml = preg_replace_callback(
                '/<a([^>]+)>(First|Last|Next|Previous)<\/a>/i',
                function ($m) {
                    $label = '';
                    switch (strtolower($m[2])) {
                        case 'first': $label = 'First page'; break;
                        case 'last': $label = 'Last page'; break;
                        case 'next': $label = 'Next page'; break;
                        case 'previous': $label = 'Previous page'; break;
                    }
                    return '<a' . $m[1] . ' aria-label="' . $label . '">' . $m[2] . '</a>';
                },
                $paginationHtml
            );
            // Patch pagination links for AJAX: replace hrefs with correct AJAX endpoint and preserve query/moduleId
            $moduleId = (int) $input->get('moduleId', 0);
            $searchQuery = rawurlencode($query);
            $ajaxBase = 'index.php?option=com_ajax&module=bearslivesearch&method=search&format=raw&q=' . $searchQuery;
            if ($moduleId) {
                $ajaxBase .= '&moduleId=' . $moduleId;
            }
            // Replace hrefs with AJAX URLs
            $paginationHtml = preg_replace_callback(
                '/href="([^"]*start=(\d+)[^"]*)"/i',
                function ($m) use ($ajaxBase) {
                    $pageStart = (int)$m[2];
                    $pageNum = ($pageStart / 10) + 1; // 10 is resultsLimit
                    return 'href="' . $ajaxBase . '&page=' . $pageNum . '"';
                },
                $paginationHtml
            );
            // Only return the results area content (not the whole template)
            $output = '<div class="bearslivesearch-summary">Results ' . $startResult . '-' . $endResult . ' of ' . $totalMatches . ' for <strong>"' . $queryDisplay . '"</strong></div>'
                . '<ul class="bearslivesearch-list" role="list">' . substr($output, strpos($output, '<li')) . '</ul>'
                . '<nav class="bearslivesearch-pagination" aria-label="Pagination">' . $paginationHtml . '</nav>';
        }
        echo $output;
    }
}
