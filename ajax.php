<?php
/**
 * Bears Live Search AJAX Handler
 *
 * @version 2025.08.06.3
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

use Joomla\CMS\Factory;

// Load the helper class
require_once __DIR__ . '/helper.php';

$app = Factory::getApplication();
$input = $app->input;
$method = $input->get('method', '');

switch ($method) {
    case 'search':
        ModBearslivesearchHelper::searchAjax();
        break;
    
    case 'getTemplatePositions':
        ModBearslivesearchHelper::getTemplatePositionsAjax();
        break;
    
    default:
        echo json_encode(['error' => 'Invalid method']);
        break;
}
