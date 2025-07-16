<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_bearslivesearch
 *
 * @copyright   Copyright (C) 2025 Troy Hall (N6REJ)
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$moduleId = 'bearslivesearch-' . $module->id;
$inputMargin = htmlspecialchars($params->get('input_margin', '1em 0'), ENT_QUOTES, 'UTF-8');
$outputMargin = htmlspecialchars($params->get('output_margin', '1em 0'), ENT_QUOTES, 'UTF-8');
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
?>
<div class="bearslivesearch<?php echo $moduleclass_sfx; ?>" id="<?php echo $moduleId; ?>">
    <a href="#<?php echo $moduleId; ?>-results" class="visually-hidden visually-hidden-focusable skip-link" tabindex="0"><?php echo Text::_('MOD_BEARSLIVESEARCH_SKIP_TO_RESULTS', 'Skip to search results'); ?></a>
    <form class="bearslivesearch-form" style="margin: <?php echo $inputMargin; ?>;" role="search" aria-label="Site search" autocomplete="off">
        <label for="<?php echo $moduleId; ?>-input" class="visually-hidden"><?php echo Text::_('MOD_BEARSLIVESEARCH'); ?></label>
        <input type="search" id="<?php echo $moduleId; ?>-input" name="q" aria-label="<?php echo Text::_('MOD_BEARSLIVESEARCH'); ?>" placeholder="<?php echo Text::_('MOD_BEARSLIVESEARCH_PLACEHOLDER'); ?>" required />
        <button type="submit" aria-label="<?php echo Text::_('MOD_BEARSLIVESEARCH_SUBMIT'); ?>" class="bearslivesearch-submit">
            <span class="icon-search" aria-hidden="true"></span>
        </button>
    </form>
    <div class="bearslivesearch-results bearslivesearch-results--hidden" id="<?php echo $moduleId; ?>-results" aria-live="polite" aria-atomic="true" tabindex="0"></div>
</div>
