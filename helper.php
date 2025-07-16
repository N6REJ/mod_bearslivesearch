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
     * AJAX method for test - required for Joomla's AJAX interface when using format=json
     * 
     * @return string Test message
     */
    public static function testAjax()
    {
        return 'ModBearslivesearchHelper::testAjax() method called successfully!';
    }

    /**
     * AJAX method for search - required for Joomla's AJAX interface when using format=json
     * 
     * @return mixed Search results in a format compatible with Joomla's AJAX interface
     */
    public static function searchAjax()
    {
        try {
            // Enable debug mode temporarily to capture any issues
            $originalDebug = self::$debug;
            self::$debug = true;

            // Log PHP version and other environment info
            \Joomla\CMS\Log\Log::add('PHP Version: ' . PHP_VERSION, \Joomla\CMS\Log\Log::INFO, 'mod_bearslivesearch');
            \Joomla\CMS\Log\Log::add('Server: ' . $_SERVER['SERVER_SOFTWARE'], \Joomla\CMS\Log\Log::INFO, 'mod_bearslivesearch');

            // Start output buffering to capture the output of the search method
            if (!ob_start()) {
                throw new \Exception('Failed to start output buffering');
            }

            // Call the search method
            self::search();

            // Get the buffered output
            $output = ob_get_clean();
            if ($output === false) {
                throw new \Exception('Failed to get output buffer contents');
            }

            // Restore original debug setting
            self::$debug = $originalDebug;

            // Return the output as a string for Joomla's AJAX interface to handle
            return $output;
        } catch (\Exception $e) {
            // Log the error with detailed information
            \Joomla\CMS\Log\Log::add('Error in searchAjax: ' . $e->getMessage(), \Joomla\CMS\Log\Log::ERROR, 'mod_bearslivesearch');
            \Joomla\CMS\Log\Log::add('Error trace: ' . $e->getTraceAsString(), \Joomla\CMS\Log\Log::ERROR, 'mod_bearslivesearch');

            // Clean any remaining output buffer
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Return an error message that will be displayed to the user
            return '<div role="alert">' . \Joomla\CMS\Language\Text::_('MOD_BEARSLIVESEARCH_SEARCH_ERROR') . 
                   '<br>Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        } catch (\Throwable $t) {
            // Log the error (PHP 7+ can throw Throwable)
            \Joomla\CMS\Log\Log::add('Fatal error in searchAjax: ' . $t->getMessage(), \Joomla\CMS\Log\Log::ERROR, 'mod_bearslivesearch');
            \Joomla\CMS\Log\Log::add('Error trace: ' . $t->getTraceAsString(), \Joomla\CMS\Log\Log::ERROR, 'mod_bearslivesearch');

            // Clean any remaining output buffer
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Return an error message that will be displayed to the user
            return '<div role="alert">' . \Joomla\CMS\Language\Text::_('MOD_BEARSLIVESEARCH_SEARCH_ERROR') . 
                   '<br>Fatal Error: ' . htmlspecialchars($t->getMessage()) . '</div>';
        }
    }

    /**
     * Perform a search using Finder (Smart Search)
     *
     * @param string $query The search query
     * @return void Outputs search results directly
     */
    public static function search()
    {
        // Debug output removed

        $app = \Joomla\CMS\Factory::getApplication();
        $input = $app->input;
        $query = trim($input->getString('q', ''));

        if ($query === '') {
            echo '<div role="status">' . \Joomla\CMS\Language\Text::_('MOD_BEARSLIVESEARCH_EMPTY_QUERY') . '</div>';
            return;
        }

        // Direct Finder index table query for live search
        try {
            // Check if the Finder component is installed and enabled
            if (!\Joomla\CMS\Component\ComponentHelper::isEnabled('com_finder')) {
                echo '<div role="alert">' . \Joomla\CMS\Language\Text::_('MOD_BEARSLIVESEARCH_SEARCH_ERROR') . '</div>';
                return;
            }

            // Query the Finder index tables directly, ignoring 404 pages
            $db = \Joomla\CMS\Factory::getDbo();
            $queryObj = $db->getQuery(true);
            $searchLike = '%' . $db->escape($query, true) . '%';

            $queryObj
                ->select('l.url, l.title, l.description, l.route')
                ->from($db->qn('#__finder_links', 'l'))
                ->join('INNER', $db->qn('#__finder_links_terms', 'lt') . ' ON l.link_id = lt.link_id')
                ->join('INNER', $db->qn('#__finder_terms', 't') . ' ON lt.term_id = t.term_id')
                ->where('t.term LIKE ' . $db->q($searchLike))
                ->where('l.state = 1')
                // Exclude 404 pages by title or URL
                ->where('l.title NOT LIKE ' . $db->q('%404%'))
                ->where('l.url NOT LIKE ' . $db->q('%404%'))
                ->group('l.link_id')
                ->order('l.title ASC')
                ->setLimit(10);

            $db->setQuery($queryObj);
            $results = $db->loadObjectList();
        } catch (\Exception $e) {
            // Log the error
            \Joomla\CMS\Log\Log::add('Finder DB search error: ' . $e->getMessage(), \Joomla\CMS\Log\Log::ERROR, 'mod_bearslivesearch');
            echo '<div role="alert">' . \Joomla\CMS\Language\Text::_('MOD_BEARSLIVESEARCH_SEARCH_ERROR') . '</div>';
            return;
        }

        // Check if we have any results
        if (empty($results)) {
            echo '<div role="status">' . \Joomla\CMS\Language\Text::_('MOD_BEARSLIVESEARCH_NO_RESULTS') . '</div>';
            return;
        }

        try {
            $output = '<ul class="bearslivesearch-list" role="list">';

            // Check if results is iterable
            if (empty($results)) {
                // Empty results, but not null - just show empty list
                echo '<div role="status">' . \Joomla\CMS\Language\Text::_('MOD_BEARSLIVESEARCH_NO_RESULTS') . '</div>';
                return;
            }

            if (!is_array($results) && !($results instanceof \Traversable)) {
                // Log detailed information about the results
                \Joomla\CMS\Log\Log::add('Search results are not iterable: ' . gettype($results) . ', value: ' . var_export($results, true), \Joomla\CMS\Log\Log::ERROR, 'mod_bearslivesearch');
                throw new \Exception('Search results are not iterable: ' . gettype($results));
            }

            foreach ($results as $index => $item) {
                // Skip null or invalid items
                if (!is_object($item) && !is_array($item)) {
                    \Joomla\CMS\Log\Log::add('Invalid search result item at index ' . $index . ': ' . gettype($item), \Joomla\CMS\Log\Log::WARNING, 'mod_bearslivesearch');
                    continue;
                }

                try {
                    // Get the title - different versions of Joomla might use different property names
                    $title = '';
                    try {
                        if (is_object($item)) {
                            if (isset($item->title)) {
                                $title = (string)$item->title;
                            } elseif (isset($item->core_title)) {
                                $title = (string)$item->core_title;
                            }
                        } elseif (is_array($item)) {
                            if (isset($item['title'])) {
                                $title = (string)$item['title'];
                            } elseif (isset($item['core_title'])) {
                                $title = (string)$item['core_title'];
                            }
                        }
                    } catch (\Throwable $t) {
                        // Log the error but continue with empty title
                        \Joomla\CMS\Log\Log::add('Error getting title: ' . $t->getMessage(), \Joomla\CMS\Log\Log::WARNING, 'mod_bearslivesearch');
                    }
                    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

                    // Get the description/summary
                    $desc = '';
                    try {
                        if (is_object($item)) {
                            if (isset($item->summary)) {
                                $desc = (string)$item->summary;
                            } elseif (isset($item->description)) {
                                $desc = (string)$item->description;
                            } elseif (isset($item->core_body)) {
                                $desc = (string)$item->core_body;
                            }
                        } elseif (is_array($item)) {
                            if (isset($item['summary'])) {
                                $desc = (string)$item['summary'];
                            } elseif (isset($item['description'])) {
                                $desc = (string)$item['description'];
                            } elseif (isset($item['core_body'])) {
                                $desc = (string)$item['core_body'];
                            }
                        }
                    } catch (\Throwable $t) {
                        // Log the error but continue with empty description
                        \Joomla\CMS\Log\Log::add('Error getting description: ' . $t->getMessage(), \Joomla\CMS\Log\Log::WARNING, 'mod_bearslivesearch');
                    }

                    // Safely strip tags and convert to string
                    try {
                        $desc = htmlspecialchars(strip_tags($desc), ENT_QUOTES, 'UTF-8');
                    } catch (\Throwable $t) {
                        // Log the error but continue with empty description
                        \Joomla\CMS\Log\Log::add('Error processing description: ' . $t->getMessage(), \Joomla\CMS\Log\Log::WARNING, 'mod_bearslivesearch');
                        $desc = '';
                    }

                    // Get the URL
                    $link = '#';
                    try {
                        if (is_object($item)) {
                            if (isset($item->route)) {
                                $link = (string)$item->route;
                            } elseif (isset($item->url)) {
                                $link = (string)$item->url;
                            } elseif (isset($item->link)) {
                                $link = (string)$item->link;
                            }
                        } elseif (is_array($item)) {
                            if (isset($item['route'])) {
                                $link = (string)$item['route'];
                            } elseif (isset($item['url'])) {
                                $link = (string)$item['url'];
                            } elseif (isset($item['link'])) {
                                $link = (string)$item['link'];
                            }
                        }

                        // Process the link through Joomla's router
                        $link = \Joomla\CMS\Router\Route::_($link);
                    } catch (\Throwable $t) {
                        // Log the error but continue with default link
                        \Joomla\CMS\Log\Log::add('Error getting or processing link: ' . $t->getMessage(), \Joomla\CMS\Log\Log::WARNING, 'mod_bearslivesearch');
                        $link = '#';
                    }

                    // Add the item to the output - use concatenation in a try-catch block
                    try {
                        $itemOutput = '<li role="listitem"><a href="' . $link . '"><span class="bearslivesearch-title">' . $title . '</span>';
                        if (!empty($desc)) {
                            $itemOutput .= '<span class="bearslivesearch-desc">' . $desc . '</span>';
                        }
                        $itemOutput .= '</a></li>';
                        $output .= $itemOutput;
                    } catch (\Throwable $t) {
                        // Log the error but continue with a simplified output
                        \Joomla\CMS\Log\Log::add('Error generating output for item: ' . $t->getMessage(), \Joomla\CMS\Log\Log::WARNING, 'mod_bearslivesearch');
                        // Add a simplified version that should work regardless of the error
                        $output .= '<li role="listitem"><a href="#">' . htmlspecialchars($title ?: 'Item', ENT_QUOTES, 'UTF-8') . '</a></li>';
                    }
                } catch (\Exception $e) {
                    // Log the error but continue processing other items
                    \Joomla\CMS\Log\Log::add('Error processing search result item at index ' . $index . ': ' . $e->getMessage(), \Joomla\CMS\Log\Log::WARNING, 'mod_bearslivesearch');
                }
            }

            // Finalize the output
            try {
                $output .= '</ul>';
                echo $output;
            } catch (\Throwable $t) {
                // Log the error
                \Joomla\CMS\Log\Log::add('Error outputting search results: ' . $t->getMessage(), \Joomla\CMS\Log\Log::ERROR, 'mod_bearslivesearch');

                // Provide a fallback output
                echo '<div role="alert">' . \Joomla\CMS\Language\Text::_('MOD_BEARSLIVESEARCH_SEARCH_ERROR') . '</div>';

                // If debug is enabled, show the error
                if (self::$debug) {
                    echo '<div role="alert">DEBUG: Error outputting results: ' . htmlspecialchars($t->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                }
            }
        } catch (\Exception $e) {
            // Log the error
            \Joomla\CMS\Log\Log::add('Error processing search results: ' . $e->getMessage(), \Joomla\CMS\Log\Log::ERROR, 'mod_bearslivesearch');

            // Display a user-friendly error message
            echo '<div role="alert">' . \Joomla\CMS\Language\Text::_('MOD_BEARSLIVESEARCH_SEARCH_ERROR') . '</div>';

            // Display debug information if debug mode is enabled
            if (self::$debug) {
                echo '<div role="alert">DEBUG: Error processing results: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}
