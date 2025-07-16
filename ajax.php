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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;

// No direct access
defined('_JEXEC') or die;

// Only allow AJAX
$app = Factory::getApplication();
$input = $app->input;

// Check for com_ajax context and GET method
if (
    $input->getCmd('option') !== 'com_ajax' ||
    $input->getCmd('module') !== 'bearslivesearch' ||
    $_SERVER['REQUEST_METHOD'] !== 'GET'
) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}
// Optional: Restrict to logged-in users only
// if (Factory::getUser()->guest) {
//     header('HTTP/1.1 403 Forbidden');
//     exit;
// }

$query = trim($input->getString('q', ''));
if ($query === '') {
    echo '<div role="status">' . Text::_('MOD_BEARSLIVESEARCH_EMPTY_QUERY') . '</div>';
    return;
}

// Use Joomla's general search model (com_search)
try {
    $model = new \Joomla\Component\Search\Site\Model\SearchModel();
    $results = $model->getData(['searchword' => $query]);
} catch (Exception $e) {
    echo '<div role="alert">' . Text::_('MOD_BEARSLIVESEARCH_SEARCH_ERROR') . '</div>';
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
