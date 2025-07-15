<?php
/**
 * Bears Live Search
 *
 * @version 2025.07.15
 * @package Bears Live Search
 * @author N6REJ
 * @email troy@hallhome.us
 * @website https://www.hallhome.us
 * @copyright Copyright (C) 2025 Troy Hall (N6REJ)
 * @license GNU General Public License version 3 or later; see License.txt
 * @since 2025.7.15
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;

class PlgFinderBearslivesearch extends CMSPlugin
{
    public function onFinderQueryPrepare(&$query, $params)
    {
        // Implement AJAX search logic here
        return true;
    }
}
