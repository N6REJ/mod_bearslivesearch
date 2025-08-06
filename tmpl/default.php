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
$width = htmlspecialchars($params->get('width', '50%'), ENT_QUOTES, 'UTF-8');
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$position = $params->get('position', 'none');
$positionClass = ' bearslivesearch-float-' . $position;

$inputBorderRadius = htmlspecialchars($params->get('border_radius', '0'), ENT_QUOTES, 'UTF-8');
$inputBorderSize = htmlspecialchars($params->get('border_size', '1px'), ENT_QUOTES, 'UTF-8');
$inputBorderColor = htmlspecialchars($params->get('border_color', '#e0e0e0'), ENT_QUOTES, 'UTF-8');
$accentBorderColor = htmlspecialchars($params->get('accent_border_color', '#007bff'), ENT_QUOTES, 'UTF-8');
$searchIconClass = $params->get('search_icon');
$searchIcon = !empty($searchIconClass) ? '<span class="' . htmlspecialchars($searchIconClass, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></span>' : '';
$searchMode = $params->get('search_mode', 'inline');
$endPosition = $params->get('end_position', '');
?>
<style>
/* Set CSS custom properties */
#<?php echo $moduleId; ?> {
    --bearslivesearch-accent-border-color: <?php echo $accentBorderColor; ?>;
}

/* Form container settings */
#<?php echo $moduleId; ?> .bearslivesearch-form {
    border-radius: <?php echo $inputBorderRadius; ?> !important;
    border: <?php echo $inputBorderSize; ?> solid <?php echo $inputBorderColor; ?> !important;
    box-sizing: border-box !important;
    margin: <?php echo $inputMargin; ?> !important;
    width: <?php echo $width; ?> !important;
}

/* Form positioning */
#<?php echo $moduleId; ?>.bearslivesearch-float-left .bearslivesearch-form {
    float: left !important;
    margin-left: 0 !important;
}
#<?php echo $moduleId; ?>.bearslivesearch-float-right .bearslivesearch-form {
    float: right !important;
    margin-right: 0 !important;
}
#<?php echo $moduleId; ?>.bearslivesearch-float-none .bearslivesearch-form {
    margin-left: auto !important;
    margin-right: auto !important;
}

/* Rows within form use full width of form */
#<?php echo $moduleId; ?> .bearslivesearch-row {
    width: 100% !important;
    box-sizing: border-box !important;
    clear: both !important;
}

/* Results container - always 100% width */
#<?php echo $moduleId; ?> .bearslivesearch-results {
    width: 100% !important;
    margin: <?php echo $outputMargin; ?> !important;
    box-sizing: border-box !important;
}
</style>
<script>
// JavaScript for search functionality only - CSS handles all styling
document.addEventListener('DOMContentLoaded', function() {
    var element = document.getElementById('<?php echo $moduleId; ?>');
    if (element) {
        // Only handle search functionality here
        // All width and positioning is handled by CSS
        console.log('Bears Live Search module initialized: <?php echo $moduleId; ?>');
    }
});
</script>
<div class="bearslivesearch bearslivesearch-module-<?php echo $module->id; ?><?php echo $moduleclass_sfx ? ' ' . $moduleclass_sfx : ''; ?><?php echo $positionClass; ?>" id="<?php echo $moduleId; ?>" data-search-mode="<?php echo htmlspecialchars($searchMode, ENT_QUOTES, 'UTF-8'); ?>" data-module-id="<?php echo $module->id; ?>" data-end-position="<?php echo htmlspecialchars($endPosition, ENT_QUOTES, 'UTF-8'); ?>">
    <a href="#<?php echo $moduleId; ?>-results" class="visually-hidden visually-hidden-focusable skip-link" tabindex="0"><?php echo Text::_('MOD_BEARSLIVESEARCH_SKIP_TO_RESULTS', 'Skip to search results'); ?></a>
    <form class="bearslivesearch-form" style="margin: <?php echo $inputMargin; ?>;" role="search" aria-label="Site search" autocomplete="off">
        <label for="<?php echo $moduleId; ?>-input" class="visually-hidden"><?php echo Text::_('MOD_BEARSLIVESEARCH'); ?></label>
        <div class="bearslivesearch-row bearslivesearch-row-flex">
            <input type="search" id="<?php echo $moduleId; ?>-input" name="q" aria-label="<?php echo Text::_('MOD_BEARSLIVESEARCH'); ?>" placeholder="<?php echo Text::_('MOD_BEARSLIVESEARCH_PLACEHOLDER'); ?>" required />
            <button type="submit" aria-label="<?php echo Text::_('MOD_BEARSLIVESEARCH_SUBMIT'); ?>" class="bearslivesearch-submit">
                <?php echo $searchIcon; ?>
            </button>
        </div>

        <!-- Row 2: Search for criteria -->
        <div class="bearslivesearch-row bearslivesearch-row-margin<?php if ($params->get('show_criteria', 'always') === 'after') echo ' bearslivesearch-criteria-hidden'; ?>">
            <span><?php echo Text::_('MOD_SEARCH_SEARCHFOR'); ?>:</span>
            <label class="bearslivesearch-radio-label">
                <input type="radio" name="searchphrase" value="anywords" checked />
                <?php echo Text::_('MOD_SEARCH_ANYWORDS'); ?>
            </label>
            <label class="bearslivesearch-radio-label">
                <input type="radio" name="searchphrase" value="allwords" />
                <?php echo Text::_('MOD_SEARCH_ALLWORDS'); ?>
            </label>
            <label class="bearslivesearch-radio-label">
                <input type="radio" name="searchphrase" value="exact" />
                <?php echo Text::_('MOD_SEARCH_EXACTPHRASE'); ?>
            </label>
        </div>

        <!-- Row 3: Sort by and Results per page -->
        <div class="bearslivesearch-row bearslivesearch-row-margin<?php if ($params->get('show_criteria', 'always') === 'after') echo ' bearslivesearch-criteria-hidden'; ?>" style="display:flex;align-items:center;gap:1em;">
            <label for="<?php echo $moduleId; ?>-ordering">
                <?php echo Text::_('JGLOBAL_SORT_BY'); ?>
            </label>
            <select name="ordering" id="<?php echo $moduleId; ?>-ordering" class="form-control bearslivesearch-fit-content">
                <option value="newest"><?php echo Text::_('JGLOBAL_NEWEST_FIRST'); ?></option>
                <option value="oldest"><?php echo Text::_('JGLOBAL_OLDEST_FIRST'); ?></option>
                <option value="popular"><?php echo Text::_('JGLOBAL_MOST_POPULAR'); ?></option>
                <option value="alpha"><?php echo Text::_('JGLOBAL_ALPHABETICAL'); ?></option>
            </select>
            <label for="<?php echo $moduleId; ?>-results_limit" style="margin-left:1em;">
                <?php echo Text::_('MOD_BEARSLIVESEARCH_RESULTS_LIMIT', 'Results per page'); ?>
            </label>
            <select name="results_limit" id="<?php echo $moduleId; ?>-results_limit" class="form-control bearslivesearch-fit-content">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="20">20</option>
                <option value="50">50</option>
            </select>
        </div>

        <!-- Standard Joomla search filters row -->
        <div class="joomla-search-filters<?php if ($params->get('show_criteria', 'always') === 'after') echo ' bearslivesearch-criteria-hidden'; ?>">
            <div class="form-group">
                <label for="<?php echo $moduleId; ?>-category">
                    <?php echo Text::_('JCATEGORY'); ?>
                </label>
                <select name="category" id="<?php echo $moduleId; ?>-category" class="form-control">
                    <option value=""><?php echo Text::_('JOPTION_SELECT_CATEGORY'); ?></option>
                    <?php if (!empty($categories)) : ?>
                        <?php foreach ($categories as $cat) : ?>
                            <option value="<?php echo (int)$cat->id; ?>"><?php echo htmlspecialchars($cat->title, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="<?php echo $moduleId; ?>-author">
                    <?php echo Text::_('JAUTHOR'); ?>
                </label>
                <select name="author" id="<?php echo $moduleId; ?>-author" class="form-control">
                    <option value=""><?php echo Text::_('JOPTION_SELECT_AUTHOR'); ?></option>
                    <?php if (!empty($authors)) : ?>
                        <?php foreach ($authors as $author) : ?>
                            <option value="<?php echo (int)$author->id; ?>"><?php echo htmlspecialchars($author->name, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="<?php echo $moduleId; ?>-datefrom">
                    <?php echo Text::_('JSEARCH_FILTER_DATE_FROM'); ?>
                </label>
                <input type="date" name="datefrom" id="<?php echo $moduleId; ?>-datefrom" class="form-control" />
            </div>
            <div class="form-group">
                <label for="<?php echo $moduleId; ?>-dateto">
                    <?php echo Text::_('JSEARCH_FILTER_DATE_TO'); ?>
                </label>
                <input type="date" name="dateto" id="<?php echo $moduleId; ?>-dateto" class="form-control" />
            </div>
        </div>
    </form>
    <?php if ($searchMode === 'inline') : ?>
    <div class="bearslivesearch-results bearslivesearch-results--hidden" id="<?php echo $moduleId; ?>-results" aria-live="polite" aria-atomic="true"></div>
    <?php endif; ?>
</div>
