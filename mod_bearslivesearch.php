<?php
/**
 * Bears Live Search
 *
 * @version 2025.07.16.1255
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

// Add CSS and JS
$wa = $app->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('mod_bearslivesearch', 'modules/mod_bearslivesearch/media/css/bearslivesearch.css');
$wa->registerAndUseScript('mod_bearslivesearch', 'modules/mod_bearslivesearch/media/js/bearslivesearch.js', ['version' => 'auto'], ['defer' => true]);

// Fetch published article categories (robust method)
use Joomla\CMS\Categories\Categories;
$categories = [];
$cats = Categories::getInstance('Content')->get('root')->getChildren();
foreach ($cats as $cat) {
    if ($cat->published) {
        $categories[] = $cat;
    }
}

require ModuleHelper::getLayoutPath('mod_bearslivesearch', $params->get('layout', 'default'), array('categories' => $categories));
