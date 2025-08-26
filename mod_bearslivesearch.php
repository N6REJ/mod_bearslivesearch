<?php
/**
 * Bears Live Search
 *
 * @version 2025.08.07.2
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

// Load module params
$inputMargin = $params->get('input_margin', '1em 0');
$outputMargin = $params->get('output_margin', '1em 0');
$width = $params->get('width', '50%');

// Add CSS and JS
$wa = $app->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('mod_bearslivesearch', 'modules/mod_bearslivesearch/media/css/bearslivesearch.css');
$wa->registerAndUseScript('mod_bearslivesearch', 'modules/mod_bearslivesearch/media/js/bearslivesearch.js', ['version' => 'auto'], ['defer' => true]);

// Fetch published article categories (full nested tree, like admin)
use Joomla\CMS\Categories\Categories;
$categories = [];
try {
    $root = Categories::getInstance('Content')->get('root');
    $stack = [];
    $pushChildren = function($node) use (&$categories, &$pushChildren) {
        $children = $node->getChildren();
        if (!$children) return;
        foreach ($children as $child) {
            // Only include published categories
            if (!empty($child->published)) {
                // Compute indent based on level (root is level 1)
                $level = isset($child->level) ? max(0, (int)$child->level - 1) : 0;
                $indent = $level > 0 ? str_repeat('— ', $level) : '';
                // Copy minimal fields expected by template
                $categories[] = (object) [
                    'id' => (int) $child->id,
                    'title' => $indent . $child->title
                ];
                // Recurse
                $pushChildren($child);
            }
        }
    };
    if ($root) {
        $pushChildren($root);
    }
} catch (\Throwable $e) {
    // Fallback to previous behavior if categories API fails
    $cats = Categories::getInstance('Content')->get('root')->getChildren();
    foreach ($cats as $cat) {
        if (!empty($cat->published)) {
            $categories[] = (object) ['id' => (int)$cat->id, 'title' => $cat->title];
        }
    }
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
