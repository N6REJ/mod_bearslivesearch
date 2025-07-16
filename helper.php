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

class ModBearslivesearchHelper
{
    /**
     * Debug mode - set to true to enable debug output
     */
    private static $debug = true;

    /**
     * Test method to verify that the helper class is being called correctly
     * 
     * @return void Outputs test message
     */
    public static function test()
    {
        echo 'ModBearslivesearchHelper::test() method called successfully!';
    }

    /**
     * Perform a search using Finder (Smart Search)
     *
     * @param string $query The search query
     * @return void Outputs search results directly
     */
    public static function search()
    {
        if (self::$debug) {
            echo '<div style="background:#f8f9fa;border:1px solid #ddd;padding:10px;margin:10px 0;font-family:monospace;">';
            echo '<h3>Debug Information</h3>';
            echo '<p>Method called: ModBearslivesearchHelper::search()</p>';
            echo '<p>PHP Version: ' . PHP_VERSION . '</p>';
            echo '<p>Server: ' . $_SERVER['SERVER_SOFTWARE'] . '</p>';
            echo '<p>Request URI: ' . $_SERVER['REQUEST_URI'] . '</p>';
            echo '<p>Query String: ' . $_SERVER['QUERY_STRING'] . '</p>';
            echo '</div>';
        }

        $input = \Joomla\CMS\Factory::getApplication()->input;
        $query = trim($input->getString('q', ''));

        if ($query === '') {
            echo '<div role="status">' . \Joomla\CMS\Language\Text::_('MOD_BEARSLIVESEARCH_EMPTY_QUERY') . '</div>';
            return;
        }

        // Use Smart Search (Finder)
        try {
            if (!class_exists('Joomla\\Component\\Finder\\Site\\Model\\SearchModel')) {
                echo '<div role="alert">DEBUG: Finder SearchModel class not found.</div>';
                return;
            }
            $model = new \Joomla\Component\Finder\Site\Model\SearchModel();
            $results = $model->getData(['q' => $query]);
        } catch (Exception $e) {
            echo '<div role="alert">' . \Joomla\CMS\Language\Text::_('MOD_BEARSLIVESEARCH_SEARCH_ERROR') . '<br>DEBUG: ' . htmlspecialchars($e->getMessage()) . '</div>';
            return;
        }

        if (empty($results)) {
            echo '<div role="status">' . \Joomla\CMS\Language\Text::_('MOD_BEARSLIVESEARCH_NO_RESULTS') . '</div>';
            return;
        }

        $output = '<ul class="bearslivesearch-list" role="list">';
        foreach ($results as $item) {
            $title = htmlspecialchars($item->title ?? '', ENT_QUOTES, 'UTF-8');
            $desc = htmlspecialchars(strip_tags($item->summary ?? ''), ENT_QUOTES, 'UTF-8');
            $link = \Joomla\CMS\Router\Route::_($item->route ?? '#');
            $output .= '<li role="listitem"><a href="' . $link . '"><span class="bearslivesearch-title">' . $title . '</span><span class="bearslivesearch-desc">' . $desc . '</span></a></li>';
        }
        $output .= '</ul>';
        echo $output;
    }
}
