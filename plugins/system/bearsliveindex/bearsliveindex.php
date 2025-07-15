<?php
/**
 * Bears Live Search
 *
 * @version 2025.7.15.1
 * @package Bears Live Search
 * @author N6REJ
 * @email troy@hallhome.us
 * @website https://www.hallhome.us
 * @copyright Copyright (C) 2025 Troy Hall (N6REJ)
 * @license GNU General Public License version 3 or later; see LICENSE.txt
 * @since 2025.7.15
 */

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Task\TaskScheduler;
use Joomla\CMS\Task\TaskResult;
use Joomla\CMS\Task\TaskInterface;

// No direct access
defined('_JEXEC') or die;

class PlgSystemBearsliveindex extends CMSPlugin
{
    protected $app;

    public function onExtensionAfterSave($context, $table, $isNew)
    {
        // Only act on this plugin
        if ($context !== 'com_plugins.plugin' || $table->element !== 'bearsliveindex' || $table->folder !== 'system') {
            return;
        }
        $this->registerOrUpdateTask();
    }

    public function onAfterInitialise()
    {
        // Register the task if not present (e.g., after install)
        if ($this->app->isClient('administrator')) {
            $this->registerOrUpdateTask();
        }
    }

    private function registerOrUpdateTask()
    {
        if (!class_exists('Joomla\\CMS\\Task\\TaskScheduler')) {
            return;
        }
        $scheduler = new TaskScheduler();
        $taskName = 'bearsliveindex.finderindex';
        $cron = $this->params->get('schedule', '0 0 * * *');
        $task = $scheduler->getTask($taskName);
        if (!$task) {
            $task = $scheduler->createTask($taskName, [
                'title' => 'Bearsliveindex: Finder Index',
                'description' => 'Automatically run Finder (Smart Search) indexer.',
                'expression' => $cron,
                'callback' => [self::class, 'runFinderIndex'],
            ]);
        } else {
            $task->setExpression($cron);
            $scheduler->updateTask($task);
        }
    }

    public static function runFinderIndex(TaskInterface $task): TaskResult
    {
        // Run the Finder indexer
        try {
            JLoader::import('finder.indexer', JPATH_ADMINISTRATOR . '/components/com_finder/helpers/indexer.php');
            $indexer = new FinderIndexer;
            $indexer->batchIndex();
            return TaskResult::success(Text::_('PLG_SYSTEM_BEARSLIVEINDEX_INDEX_SUCCESS'));
        } catch (Exception $e) {
            return TaskResult::failure(Text::_('PLG_SYSTEM_BEARSLIVEINDEX_INDEX_ERROR') . ': ' . $e->getMessage());
        }
    }
}
