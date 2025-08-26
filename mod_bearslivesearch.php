<?php
/**
 * Bears Live Search
 *
 * @version 2025.08.26.2
 * @package Bears Live Search
 * @author N6REJ
 * @email troy@hallhome.us
 * @website https://hallhome.us/software
 * @copyright Copyright (C) 2025 N6REJ
 * @license GNU General Public License version 3 or later; see License.txt
 * @since 2025.7.15
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

// Load module params
$inputMargin = $params->get('input_margin', '1em 0');
$outputMargin = $params->get('output_margin', '1em 0');
$width = $params->get('width', '50%');

// Add CSS and JS
$app = Factory::getApplication();
$wa = $app->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('mod_bearslivesearch', 'modules/mod_bearslivesearch/media/css/bearslivesearch.css');
$wa->registerAndUseScript('mod_bearslivesearch', 'modules/mod_bearslivesearch/media/js/bearslivesearch.js', ['version' => 'auto'], ['defer' => true]);

// Fetch published article categories (full nested tree, like admin)
use Joomla\CMS\Categories\Categories;
$categories = [];

// Build list of hidden category IDs (from params + default 'hidden' title/alias)
$hiddenIds = [];
try {
    $hiddenFromParams = (array) $params->get('hidden_categories', []);
    $hiddenFromParams = array_values(array_filter(array_map('intval', $hiddenFromParams), function($v){ return $v > 0; }));
    $hiddenIds = $hiddenFromParams;
    // Also auto-detect a category named/aliased 'hidden' for com_content
    $db = \Joomla\CMS\Factory::getDbo();
    $q = $db->getQuery(true)
        ->select($db->qn('id'))
        ->from($db->qn('#__categories'))
        ->where($db->qn('extension') . ' = ' . $db->q('com_content'))
        ->where('(LOWER(' . $db->qn('title') . ') = ' . $db->q('hidden') . ' OR ' . $db->qn('alias') . ' = ' . $db->q('hidden') . ')');
    $db->setQuery($q);
    $defaultHidden = array_map('intval', (array) $db->loadColumn());
    if (!empty($defaultHidden)) {
        $hiddenIds = array_values(array_unique(array_merge($hiddenIds, $defaultHidden)));
    }
} catch (\Throwable $e) {
    // Ignore hidden lookup errors; proceed with what we have
}

try {
    $root = Categories::getInstance('Content')->get('root');
    $pushChildren = function($node) use (&$categories, &$pushChildren, &$hiddenIds) {
        $children = $node->getChildren();
        if (!$children) return;
        foreach ($children as $child) {
            // Only include published categories and not hidden ones
            $isHidden = in_array((int)$child->id, (array)$hiddenIds, true);
            if (!empty($child->published) && !$isHidden) {
                // Compute indent based on level (root is level 1)
                $level = isset($child->level) ? max(0, (int)$child->level - 1) : 0;
                $indent = $level > 0 ? str_repeat('— ', $level) : '';
                // Copy minimal fields expected by template
                $categories[] = (object) [
                    'id' => (int) $child->id,
                    'title' => $indent . $child->title
                ];
                // Recurse into visible children
                $pushChildren($child);
            } else {
                // If this child is hidden, skip it AND its descendants
                continue;
            }
        }
    };
    if ($root) {
        $pushChildren($root);
    }
} catch (\Throwable $e) {
    // Fallback to previous behavior if categories API fails (apply hidden filter if possible)
    try {
        $cats = Categories::getInstance('Content')->get('root')->getChildren();
        foreach ($cats as $cat) {
            if (!empty($cat->published) && !in_array((int)$cat->id, (array)$hiddenIds, true)) {
                $categories[] = (object) ['id' => (int)$cat->id, 'title' => $cat->title];
            }
        }
    } catch (\Throwable $e2) {}
}

// Fetch authors with published articles
$authors = [];
try {
    $db = \Joomla\CMS\Factory::getDbo();
    $query = $db->getQuery(true)
        ->select('created_by, COUNT(*) as num')
        ->from($db->qn('#__content'))
        ->where('state = 1')
        ->group('created_by')
        ->order('num DESC');
    $db->setQuery($query);
    $authorRows = $db->loadObjectList();
    if ($authorRows) {
        // Get user names
        $userIds = array_map(function($row) { return (int)$row->created_by; }, $authorRows);
        if ($userIds) {
            $userQuery = $db->getQuery(true)
                ->select('id, name')
                ->from($db->qn('#__users'))
                ->where('id IN (' . implode(',', $userIds) . ')');
            $db->setQuery($userQuery);
            $userList = $db->loadAssocList('id', 'name');
            foreach ($authorRows as $row) {
                if (!empty($userList[$row->created_by])) {
                    $authors[] = (object)[
                        'id' => (int)$row->created_by,
                        'name' => $userList[$row->created_by]
                    ];
                }
            }
        }
    }
} catch (Exception $e) {}

require ModuleHelper::getLayoutPath('mod_bearslivesearch', $params->get('layout', 'default'), array('categories' => $categories, 'authors' => $authors));
