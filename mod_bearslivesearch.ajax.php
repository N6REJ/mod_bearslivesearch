<?php
/**
 * Bears Live Search AJAX Handler
 *
 * @package     Joomla.Site
 * @subpackage  mod_bearslivesearch
 * @copyright   Copyright (C) 2025 Troy Hall (N6REJ)
 * @license     GNU General Public License version 3 or later; see License.txt
 */

defined('_JEXEC') or die;
die('AJAX FILE LOADED');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

class modBearslivesearchAjax
{
    public static function search()
    {
        // Debug: Output all request parameters
        $input = Factory::getApplication()->input;
        $query = trim($input->getString('q', ''));
        header('Content-Type: text/html; charset=utf-8');
        echo '<!-- DEBUG: Query param: ' . htmlspecialchars($query) . ' -->';

        if ($query === '') {
            echo '<div role="status">' . Text::_('MOD_BEARSLIVESEARCH_EMPTY_QUERY') . '</div>';
            return;
        }

        try {
            // Use Joomla's general search model (com_search)
            if (!class_exists('Joomla\\Component\\Search\\Site\\Model\\SearchModel')) {
                echo '<div role="alert">DEBUG: SearchModel class not found.</div>';
                return;
            }
            $model = new \Joomla\Component\Search\Site\Model\SearchModel();
            $results = $model->getData(['searchword' => $query]);
        } catch (Exception $e) {
            echo '<div role="alert">' . Text::_('MOD_BEARSLIVESEARCH_SEARCH_ERROR') . '<br>DEBUG: ' . htmlspecialchars($e->getMessage()) . '</div>';
            return;
        }

        if (empty($results)) {
            echo '<div role="status">' . Text::_('MOD_BEARSLIVESEARCH_NO_RESULTS') . '</div>';
            return;
        }

        // Output accessible results
        $output = '<ul class="bearslivesearch-list" role="list">';
        foreach ($results as $item) {
            $title = htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8');
            $desc = htmlspecialchars(strip_tags($item->text), ENT_QUOTES, 'UTF-8');
            $link = Route::_($item->href);
            $output .= '<li role="listitem"><a href="' . $link . '"><span class="bearslivesearch-title">' . $title . '</span><span class="bearslivesearch-desc">' . $desc . '</span></a></li>';
        }
        $output .= '</ul>';
        echo $output;
    }
}
