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
?>
<div class="bearslivesearch<?php echo $moduleclass_sfx; ?>" id="<?php echo $moduleId; ?>">
    <form class="bearslivesearch-form" style="margin: <?php echo $inputMargin; ?>;" role="search" aria-label="Site search" autocomplete="off">
        <label for="<?php echo $moduleId; ?>-input" class="visually-hidden"><?php echo JText::_('MOD_BEARSLIVESEARCH'); ?></label>
        <input type="search" id="<?php echo $moduleId; ?>-input" name="q" aria-label="<?php echo JText::_('MOD_BEARSLIVESEARCH'); ?>" placeholder="<?php echo JText::_('MOD_BEARSLIVESEARCH_PLACEHOLDER'); ?>" required />
        <button type="submit" aria-label="<?php echo JText::_('MOD_BEARSLIVESEARCH_SUBMIT'); ?>" class="bearslivesearch-submit">
            <span class="icon-search" aria-hidden="true"></span>
        </button>
    </form>
    <div class="bearslivesearch-results" style="margin: <?php echo $outputMargin; ?>;" aria-live="polite" aria-atomic="true" tabindex="0"></div>
</div>
