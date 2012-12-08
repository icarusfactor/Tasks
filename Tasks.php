<?php
# MediaWiki Tasks version 0.5.7 (adapted for MediaWiki 1.18 by Deplicator)
# Internationalized by Golob
# Copyright (c) 2005 Aran Clary Deltac
# http://arandeltac.com/MediaWiki_Tasks
# Copyright (c) 2006 Sylvain Machefert, mods by mhc, Filo, James,
# Guillaume Pratte, Dangerville, OZZ
# http://www.mediawiki.org/wiki/Extension:Tasks_Extension
# Distributed under that same terms as MediaWiki itself.

$dir = dirname(__FILE__) . '/';
 
$wgExtensionCredits['specialpage'][] = array(
        'path'           => $dir,
        'name'           => 'Tasks',
        'author'         => 'Aran Clary Deltac',
        'url'            => 'http://www.mediawiki.org/wiki/Extension:Tasks_Extension',
        'descriptionmsg' => 'tasks-desc',
        'version'        => '0.5.7'
        );
 
 
 
$wgAutoloadClasses['SpecialTasks'] = $dir . 'SpecialTasks.php'; 
$wgSpecialPages['Tasks'] = 'SpecialTasks'; 
 
$wgExtensionFunctions[] = "wfTasksExtension";
$wgExtensionMessagesFiles['Tasks'] = $dir . 'Tasks.i18n.php';
$wgExtensionMessagesFiles['TasksAliases'] = $dir . 'Tasks.alias.php';
$wgHooks['ArticleSave'][] = 'clearTasks';
$wgHooks['ArticleSaveComplete'][] = 'saveTasks';
global $tasks_buffer;
 
#-----------------------------------------------#
 
#-----------------------------------------------#
# Purpose   : Declare parser extensions.
function wfTasksExtension() {
        global $wgParser;
        $wgParser->setHook( "tasks", "tasksHook" );
}
 
#-----------------------------------------------#
# Purpose   : Display a list of tasks.
# Parameters: content, A list of tasks, one per line.
#             args, An array of arguments.
# Arguments : hidden, All tasks will be hidden in the HTML.
# Returns   : Tasks HTML.
function tasksHook( $content, $args = null, $parser) {
        global $tasks_buffer;
        clearTasks();
        addToTaskBuffer($content);
 
        $hidden = '';
        if (isset($args['hidden'])) {
                $hidden = 'y';
        }
        else {
                $hidden = 'n';
        }
 
        $output = '';
#if you want tasks create a new section...
      $parserOutput = $parser->parse('== [[Special:'.wfMsg( 'tasks' ).'|'.wfMsg( 'tasks' ).']] ==', $parser->mTitle, $parser->mOptions, false, false);
      $output .= $parserOutput->getText();
 foreach ($tasks_buffer as $task) {
                $task['hidden'] = $hidden;
                $output .= formatTask(
                                        $task['status'],
                                        $task['summary'],
                                        $task['owner'],
                                        '',
                                        $parser);
        }
        return $output;
}
 
function addToTaskBuffer($content) {
        global $tasks_buffer;
        $tasks = preg_split("/[\n\r]+/", $content);
        foreach ($tasks as $task) {
                if (preg_match('/^\s*\[([123x!? ])\]\s*(.*)$/',$task,$matches)) {
                        $status = $matches[1];
                        $summary = $matches[2];
 
                        $owner = '';
                        # the regexp updated by SebM (sebm@seren-com-pl) 22 March 2007    
                        # to allow parentheses ( ) in task description                                   
                 if (preg_match('/^(.+?)\s*\(([^()]+)\)$/',$summary,$matches)) {
                                $summary = $matches[1];
                                $owner = $matches[2];
                        }
 
                        $tasks_buffer[] = array(
                                'summary' => $summary,
                                'status' => $status,
                                'owner' => $owner,
                                'hidden' => 'n'
                        );
                }
        }
}
 
#-----------------------------------------------#
# Purpose   : HTML format a task.
function formatTask( $status, $summary, $owner='', $page='', &$parser ) {
        global $wgScriptPath, $wgTitle;
        $imgTitle = wfMessage( 'taskregular' )->text(); $alt = '[ ]';
        $img = '<img src="'.$wgScriptPath.'/extensions/Tasks/images/Ntask';
 
        switch ($status) {
                case 'x': $img .= '_done'; $alt = '[x]'; $imgTitle = wfMessage( 'taskdone' )->text(); break;
                case '!': $img .= '_alert'; $alt = '[!]'; $imgTitle = wfMessage( 'taskalert' )->text(); break;
                case '1': $img .= '_1'; $alt = '[1]'; $imgTitle = wfMessage( 'taskhipriority' )->text(); break;
                case '2': $img .= '_2'; $alt = '[2]'; $imgTitle = wfMessage( 'taskmidpriority' )->text(); break;
                case '3': $img .= '_3'; $alt = '[3]'; $imgTitle = wfMessage( 'tasklopriority' )->text(); break;
                case '?': $img .= '_what'; $alt = '[?]'; $imgTitle = wfMessage( 'taskwhat' )->text(); break;       }
        #$img .= '.png" width="13" height="13" alt="'.$alt.'" title="'.$imgTitle.'" /> ';
        #added for custom images   
        $img .= '.png" width="14" height="15" style="margin-top:-3px;" alt="'.$alt.'" title="'.$imgTitle.'" /> ';

 
        if ($owner) { $summary .= ' ([[Special:'.wfMessage( 'tasks' )->text().'/owner='.$owner.'|'.$owner.']])'; }
        if ($page) { $summary = "[[:$page]]: ".$summary; }
 
        $parserOutput = $parser->parse($summary, $parser->mTitle, $parser->mOptions, false, false);
        return $img . ' ' . $parserOutput->getText() . '<br />';
}
 
#-----------------------------------------------#
# Purpose   : Used before saveing a page to clear the tasks buffer.
function clearTasks() {    
        global $tasks_buffer;
        $tasks_buffer = array();
        return 1;
}
 
#-----------------------------------------------#
# Purpose   : Used after a page is saved to first delete
#             all tasks and then save the new ones created
#             in the tasks buffer.
# Parameters: article, The article object.
# rev: 2006-03-12: <calendar> extension will cause double-logging of
# tasks rendered on a sub-page.  "Rebuild" section prevents this.
function saveTasks( $article, $user, $text ) {
        global $tasks_buffer;
        $page_id = $article->getID();
        $dbr =& wfGetDB( DB_MASTER );
        # Delete all tasks for this page.
 $dbr->delete(
                'tasks',
                array( 'page_id' => $page_id )
        );
 
        # Rebuild the $tasks_buffer array (in case we're on a <calendar> page)
 clearTasks();
        $matches = array();
        $elements[] = 'tasks';
 
        $text = Parser::extractTagsAndParams( $elements, $text, $matches );
        foreach( $matches as $marker => $data ) {
                list( $element, $content, $params, $tag ) = $data;
                addToTaskBuffer($content);
        }
 
        # Re-insert all tasks that were created when parseing this page.
 foreach ($tasks_buffer as $task) {
                $task['page_id'] = $page_id;
                $dbr->insert(
                                'tasks',
                                $task
                );
        }
        return 1;
}