<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_bearslivesearch
 *
 * @copyright   Copyright (C) 2024
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

$moduleId = 'bearslivesearch-' . $module->id;
$inputMargin = htmlspecialchars($params->get('input_margin', '1em 0'), ENT_QUOTES, 'UTF-8');
$outputMargin = htmlspecialchars($params->get('output_margin', '1em 0'), ENT_QUOTES, 'UTF-8');
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_QUOTES, 'UTF-8');
$useFontAwesome = (int) $params->get('use_fontawesome', 0);
$showInputIcon = (int) $params->get('show_input_icon', 1);
$inputIcon = trim($params->get('input_icon', 'fas fa-search'));
$iconPosition = $params->get('icon_position', 'end');

// Prepare icon HTML
if ($useFontAwesome && $inputIcon) {
    $iconHtml = '<span class="bearslivesearch-icon" aria-hidden="true"><i class="' . htmlspecialchars($inputIcon, ENT_QUOTES, 'UTF-8') . '"></i></span>';
} else {
    $iconHtml = '<span class="bearslivesearch-icon" aria-hidden="true">🔍</span>';
}
?>
<div class="bearslivesearch<?php echo $moduleclass_sfx; ?>" id="<?php echo $moduleId; ?>">
    <form class="bearslivesearch-form" style="margin: <?php echo $inputMargin; ?>;" role="search" aria-label="Site search" autocomplete="off">
        <label for="<?php echo $moduleId; ?>-input" class="visually-hidden">Search</label>
        <?php if ($showInputIcon && $iconPosition === 'beginning') echo $iconHtml; ?>
        <input type="search" id="<?php echo $moduleId; ?>-input" name="q" aria-label="Search" placeholder="Search..." required />
        <?php if ($showInputIcon && $iconPosition === 'end') echo $iconHtml; ?>
        <button type="submit" aria-label="Submit search" class="bearslivesearch-submit">
            <span aria-hidden="true">🔍</span>
        </button>
    </form>
    <div class="bearslivesearch-results" style="margin: <?php echo $outputMargin; ?>;" aria-live="polite" aria-atomic="true" tabindex="0"></div>
</div>
