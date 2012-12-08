<?php
# MediaWiki Tasks version 0.5.7 (adapted for MediaWiki 1.18 by Deplicator)
# Internationalized by Golob
# Copyright (c) 2005 Aran Clary Deltac
# http://arandeltac.com/MediaWiki_Tasks
# Copyright (c) 2006 Sylvain Machefert, mods by mhc, Filo, James,
# Guillaume Pratte, Dangerville, OZZ
# http://www.mediawiki.org/wiki/Extension:Tasks_Extension
# Distributed under that same terms as MediaWiki itself.

class SpecialTasks extends SpecialPage {
 
                        function __construct() {
                                parent::__construct( 'Tasks' );
                        }
 
                # constructor
                function SpecialTasks($restriction = '')
                {
                                parent::__construct( 'Tasks', $restriction ) ;
                }
 
                # override of the abstract execute() function, manages the output
                function execute($args_string) 
                {
                                global $wgOut, $wgTitle, $wgParser;
                                $page_titles = array();
                                $dbr =& wfGetDB( DB_MASTER );
 
                                # Parse arguments.
                                $args = array();
                                if ($args_string != '') {
                                                foreach (explode('&',$args_string) as $pair) {
                                                                $pair = explode('=',$pair);
                                                                $args[$pair[0]] = $pair[1];
                                                }
                                }
 
                                # Define options for the SQL.
                                $options = array( 'ORDER BY'=>'status' );
                                if (isset($args['limit'])) {
                                                $options['LIMIT'] = $args['limit'];
                                }
                                if (isset($args['owner'])) {
                                                $args['owner'] = str_replace( '_', ' ', $args['owner'] );
                                }
 
                                # Create the WHERE clause.
                                $where = array();
                                //$where[]='tasks.page_id=page.page_id';
                                $where[]='t.page_id=p.page_id';
                                if (isset($args['status'])) {
                                                $where[] = 'status<="'.$args['status'].'"';
                                }
                                if (isset($args['owner'])) {
                                                $where[] = 'owner="'.$args['owner'].'"';
                                }
                                if (!isset($args['hidden'])) {
                                                $where[] = 'hidden="n"';
                                }
 
                                # Run the SQL.
                                
                                /*
                                $res = $dbr->
                                select( array('tasks','page'),
                                array('page.page_id','status','owner','summary','page_namespace'),
                                $where, 'Database::select',$options);
                                */
 
                                $res = $dbr->
                                select( array($dbr->tableName('tasks').' as t',$dbr->tableName('page').' as p'),
                                array('p.page_id','status','owner','summary','page_namespace'),
                                $where, 'Database::select',$options);
                                #select( $table, $vars, $conds='', $fname = 'Database::select', $options = array() )
                                if (!$res) { return; }
 
                                # Set the title for this page.
                                if (isset($args['owner'])) {
                                                $wgOut->setPageTitle($args['owner'].wfMsg( 'taskwhose' ) );
                                }
                                else {
                                                $wgOut->setPageTitle( wfMsg( 'tasks' ) );
                                }
                                # Generate HTML list of tasks.
                                #attach title and options
                                $wgParser->parse('nothing', $wgTitle, new ParserOptions(), false, true);
                                $count = 0;
                                $last_severity = '';
                                while ($task = $dbr->fetchRow( $res )) {
                                                if (!isset($page_titles[$task['page_id']])) {
                                                                $page_titles[$task['page_id']] =
                                                                Title::makeTitle( $task['page_namespace'],
                                                                Title::newFromID($task['page_id'])->getText()
                                                                );
 
                                                }
                                                $page_title = $page_titles[$task['page_id']];
                                                $wgOut->addHTML(
                                                                formatTask(
                                                                                $task['status'],
                                                                                $task['summary'],
                                                                                $task['owner'],
                                                                                $page_title,
                                                                                $wgParser)
                                                                );
                                                $count++;
                                }
                                $dbr->freeResult( $res );
 
                                # Display a message if there are no tasks.
                                if (!$count) {
                                                $wgOut->addWikiText(wfmsg('notasks'));
                                }
 
                                # http://meta.wikimedia.org/wiki/Talk:Permissions (Error message with MediaWiki v1.6.5)
                                # explains line below
                                $wgOut->setArticleFlag( false );
                }
}