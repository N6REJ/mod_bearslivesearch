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
        <fieldset class="phrases" style="margin-left:1em;">
            <legend><?php echo Text::_('Search for:'); ?></legend>
            <label class="radio"><input type="radio" name="searchphrase" value="all" checked>All words</label>
            <label class="radio"><input type="radio" name="searchphrase" value="any">Any words</label>
            <label class="radio"><input type="radio" name="searchphrase" value="exact">Exact Phrase</label>
        </fieldset>
        <fieldset class="ordering" style="margin-left:1em;">
            <label for="ordering">Ordering:</label>
            <select id="ordering" name="ordering" class="inputbox">
                <option value="newest" selected>Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="popular">Most Popular</option>
                <option value="alpha">Alphabetical</option>
                <option value="category">Category</option>
            </select>
        </fieldset>
        <fieldset class="only" style="margin-left:1em;">
            <legend>Search Only:</legend>
            <label class="checkbox"><input type="checkbox" name="areas[]" value="articles" checked>Articles</label>
            <label class="checkbox"><input type="checkbox" name="areas[]" value="forum">Forum Posts</label>
        </fieldset>
        <?php
        $defaultLimit = (int) $params->get('results_limit', 10);
        $userLimit = isset($_GET['results_limit']) ? (int) $_GET['results_limit'] : null;
        $selectedLimit = $userLimit ?: $defaultLimit;
        $limitOptions = [5,10,15,20,25,30,50,100,0];
        ?>
        <fieldset class="form-limit" style="margin-left:1em;">
            <label for="results_limit">Display #</label>
            <select id="results_limit" name="results_limit" class="inputbox input-mini">
                <?php foreach ($limitOptions as $opt): ?>
                    <option value="<?php echo $opt; ?>"<?php if ($selectedLimit === $opt) echo ' selected'; ?>><?php echo $opt === 0 ? 'All' : $opt; ?></option>
                <?php endforeach; ?>
            </select>
        </fieldset>
    </form>
    <div class="bearslivesearch-results bearslivesearch-results--hidden" id="<?php echo $moduleId; ?>-results" aria-live="polite" aria-atomic="true"></div>
</div>
