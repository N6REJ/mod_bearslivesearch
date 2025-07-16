<?php
/**
 * Bears Live Search
 *
 * @version 2025.7.15.1
 * @package Bears Live Search
 * @author N6REJ
 * @email troy@hallhome.us
 * @website https://hallhome.us/software
 * @copyright Copyright (C) 2025 Troy Hall (N6REJ)
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
$useFontAwesome = (int) $params->get('use_fontawesome', 0);
$inputIcon = trim($params->get('input_icon', 'fa-search'));
$iconPosition = $params->get('icon_position', 'end');

// Add CSS and JS
$wa = $app->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('mod_bearslivesearch', 'modules/mod_bearslivesearch/media/css/bearslivesearch.css');
$wa->registerAndUseScript('mod_bearslivesearch', 'modules/mod_bearslivesearch/media/js/bearslivesearch.js', ['version' => 'auto'], ['defer' => true]);
if ($useFontAwesome) {
    // Use Joomla's built-in FontAwesome system
    $wa->useStyle('fontawesome');
}

require ModuleHelper::getLayoutPath('mod_bearslivesearch', $params->get('layout', 'default'));
