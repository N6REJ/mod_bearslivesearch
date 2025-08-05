<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_bearslivesearch
 *
 * @copyright   Copyright (C) 2025 Troy Hall (N6REJ)
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

/**
 * End Positions field - reads actual positions from site template
 */
class JFormFieldEndpositions extends FormField
{
    /**
     * The form field type.
     */
    protected $type = 'endpositions';

    /**
     * Method to get the field input markup.
     */
    protected function getInput()
    {
        $options = array();
        
        // Add default "none" option
        $options[] = '<option value="">' . Text::_('JNONE') . '</option>';
        
        try {
            // Get the active site template (not admin template)
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('template'))
                ->from($db->quoteName('#__template_styles'))
                ->where($db->quoteName('client_id') . ' = 0')
                ->where($db->quoteName('home') . ' = 1');
            
            $db->setQuery($query);
            $templateName = $db->loadResult();
            
            // Fallback: if no default template found, get the first site template
            if (empty($templateName)) {
                $query = $db->getQuery(true)
                    ->select($db->quoteName('template'))
                    ->from($db->quoteName('#__template_styles'))
                    ->where($db->quoteName('client_id') . ' = 0')
                    ->order($db->quoteName('id') . ' ASC');
                
                $db->setQuery($query, 0, 1);
                $templateName = $db->loadResult();
            }
            
            // Get the filesystem path for the template
            // Use JPATH_ROOT to get the correct base path for your environment
            $filesystemPath = JPATH_ROOT . '/templates/' . $templateName;
            
            // Try different possible template XML file names
            $possiblePaths = array(
                $filesystemPath . '/templateDetails.xml',
                $filesystemPath . '/template_details.xml',
                $filesystemPath . '/details.xml',
                $filesystemPath . '/template.xml',
                $filesystemPath . '/' . $templateName . '.xml'
            );
            
            $templatePath = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $templatePath = $path;
                    break;
                }
            }

            if ($templatePath !== null) {
                // Disable XML errors temporarily
                $useErrors = libxml_use_internal_errors(true);
                
                $xmlContent = file_get_contents($templatePath);
                if ($xmlContent !== false) {
                    $xml = simplexml_load_string($xmlContent);
                    
                    if ($xml !== false) {
                        $positions = array();
                        
                        // Try different possible XML structures
                        if (isset($xml->positions->position)) {
                            // Standard structure: <positions><position>name</position></positions>
                            foreach ($xml->positions->position as $position) {
                                $pos = trim((string)$position);
                                if (!empty($pos)) {
                                    $positions[] = $pos;
                                }
                            }
                        } elseif (isset($xml->positions)) {
                            // Alternative structure: <positions>name1,name2,name3</positions>
                            $positionsText = trim((string)$xml->positions);
                            if (!empty($positionsText)) {
                                $posArray = explode(',', $positionsText);
                                foreach ($posArray as $pos) {
                                    $pos = trim($pos);
                                    if (!empty($pos)) {
                                        $positions[] = $pos;
                                    }
                                }
                            }
                        }
                        
                        // Also try to find positions in other common locations
                        if (empty($positions) && isset($xml->position)) {
                            // Direct position elements: <position>name</position>
                            foreach ($xml->position as $position) {
                                $pos = trim((string)$position);
                                if (!empty($pos)) {
                                    $positions[] = $pos;
                                }
                            }
                        }

                        if (!empty($positions)) {
                            // Remove duplicates and sort
                            $positions = array_unique($positions);
                            sort($positions);

                            // Create options for each position
                            foreach ($positions as $position) {
                                $selected = ($this->value == $position) ? ' selected="selected"' : '';
                                $options[] = '<option value="' . htmlspecialchars($position) . '"' . $selected . '>' . htmlspecialchars($position) . '</option>';
                            }
                        }
                    }
                }
                
                // Restore error handling
                libxml_use_internal_errors($useErrors);
            }
        } catch (Exception $e) {
            // Continue to fallback
        }
        
        // If no positions found, add fallback positions
        if (count($options) <= 1) { // Only the "None" option exists
            $fallbackPositions = array('header', 'navigation', 'breadcrumbs', 'content-top', 'content-bottom', 'footer', 'debug', 'position-1', 'position-2', 'position-3');
            foreach ($fallbackPositions as $position) {
                $selected = ($this->value == $position) ? ' selected="selected"' : '';
                $options[] = '<option value="' . htmlspecialchars($position) . '"' . $selected . '>' . htmlspecialchars($position) . '</option>';
            }
        }

        // Build the select field HTML
        $html = '<select name="' . $this->name . '" id="' . $this->id . '" class="form-select">';
        $html .= implode('', $options);
        $html .= '</select>';

        return $html;
    }
}
