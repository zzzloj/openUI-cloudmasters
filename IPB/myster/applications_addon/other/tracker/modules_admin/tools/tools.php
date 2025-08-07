<?php

/**
* Tracker 2.1.0
* 
* Tracker Tools module
* Last Updated: $Date: 2012-11-24 20:31:32 +0000 (Sat, 24 Nov 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Admin
* @link			http://ipbtracker.com
* @version		$Revision: 1393 $
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_tracker_tools_tools extends ipsCommand
{
	public $html;

	/*-------------------------------------------------------------------------*/
	// Run me - called by the wrapper
	/*-------------------------------------------------------------------------*/

	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin and Lang */
		$this->html               = $this->registry->output->loadTemplate( 'cp_skin_tracker' );
		$this->corehtml           = $this->registry->output->loadTemplate( 'cp_skin_templates', 'core' );
		$this->html->form_code    = '&amp;module=tools&amp;section=tools';
		$this->html->form_code_js = '&module=tools&section=tools';

		$this->lang->loadLanguageFile( array( 'admin_tools' ) );

		//-----------------------------------------
		// What shall we do?
		//-----------------------------------------

		switch ( $this->request['do'] )
		{
			case 'doresyncissues':
				$this->resync_issues();
				break;
			case 'dorebuildposts':
				$this->rebuild_posts();
				break;
			case 'rebuildcache':
				$this->build_cache();
				break;
			/*case 'fields_1':
				$this->build_ips_field_perms_1();
				break;
			case 'fields_2':
				$this->build_ips_field_perms_2();
				break;
			case 'fields_3':
				$this->build_ips_field_perms_3();
				break;*/
			case 'overview':
			default:
				$this->tools_index();
				break;
		}

		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/*-------------------------------------------------------------------------*/
	// Tools index page
	/*-------------------------------------------------------------------------*/

	function tools_index()
	{
		$this->registry->output->html .= $this->html->tools();
	}

	/*-------------------------------------------------------------------------*/
	// REBUILD POSTS
	/*-------------------------------------------------------------------------*/

	function rebuild_posts()
	{
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_parse_bbcode.php', 'parseBbcode' );
		$parser		= new $classToLoad( $this->registry );
		$oldparser	= new $classToLoad( $this->registry, 'legacy' );
		$parser->allow_update_caches = 1;

		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$done   = 0;
		$last   = 0;
		$start  = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$dis    = intval($this->request['dis']) >=0 ? intval($this->request['dis']) : 0;
		$output = array();

		//-----------------------------------------
		// Got any more?
		//-----------------------------------------

		$tmp = $this->DB->buildAndFetch( array( 'select' => 'pid', 'from' => 'tracker_posts', 'limit' => array($dis,1)  ) );
		$max = intval( $tmp['pid'] );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		$this->DB->build(
			array(
				'select'    => 'p.*', 
				'from'      => array( 'tracker_posts' => 'p' ),
				'order'     => 'p.pid ASC',
				'where'     => 'p.pid > ' . $start,
				'limit'     => array($end),
				'add_join'  => array(
					1 => array(
						'type'     => 'left',
						'select'   => 't.project_id',
						'from'     => array( 'tracker_issues' => 't' ), 
						'where'    => "t.issue_id=p.issue_id"
					),
					2 => array(
						'type'     => 'left',
						'select'   => 'm.member_group_id',
						'from'     => array( 'members' => 'm' ), 
						'where'    => "m.member_id=p.author_id"
					)
				)
			)
		);

		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			$parser->quote_open            = $oldparser->quote_open            = 0;
			$parser->quote_closed          = $oldparser->quote_closed          = 0;
			$parser->quote_error           = $oldparser->quote_error           = 0;
			$parser->error                 = $oldparser->error                 = '';
			$parser->image_count           = $oldparser->image_count           = 0;
			$parser->parsing_mgroup        = $oldparser->parsing_mgroup        = $r['member_group_id'];
			$parser->parsing_mgroup_others = $oldparser->parsing_mgroup_others = $r['mgroup_others'];
			$parser->parse_nl2br           = $oldparser->parse_nl2br           = ( $r['post_htmlstate'] != 1 ) ? 1 : 0;

			$this->memberData['g_bypass_badwords'] = $this->caches['group_cache'][ $r['member_group_id'] ]['g_bypass_badwords'];

			$project = $this->registry->tracker->projects()->getProject( $r['project_id'] );

			$parser->parse_smilies = $r['use_emo'];
			$parser->parse_html    = ( $project['use_html'] AND $this->caches['group_cache'][ $r['member_group_id'] ]['g_dohtml'] ) ? 1 : 0;
			$parser->parse_bbcode  = $project['use_ibc'];

			/* Parse the way it used to */
			$rawpost = $oldparser->preEditParse( $r['post'] );

			/* Convert to new format */
			$newpost = $parser->preDbParse( $rawpost );

			//-----------------------------------------
			// Remove old \' escaping
			//-----------------------------------------

			$newpost = str_replace( "\\'", "'", $newpost );

			//-----------------------------------------
			// Convert old dohtml?
			//-----------------------------------------

			$htmlstate = 0;

			if ( strstr( strtolower($newpost), '[dohtml]' ) )
			{
				//-----------------------------------------
				// Can we use HTML?
				//-----------------------------------------

				if ( $project['use_html'] )
				{
					$htmlstate = 2;
				}

				$newpost = preg_replace( "#\[dohtml\]#i" , "", $newpost );
				$newpost = preg_replace( "#\[/dohtml\]#i", "", $newpost );
			}
			else
			{
				$htmlstate = intval( $r['post_htmlstate'] );
			}

			//-----------------------------------------
			// Convert old attachment tags
			//-----------------------------------------

			$newpost = preg_replace( "#\[attachmentid=(\d+?)\]#is", "[attachment=\\1:attachment]", $newpost );

			if ( $newpost )
			{
				$this->DB->update( 'tracker_posts', array( 'post' => $newpost, 'post_htmlstate' => $htmlstate ), 'pid='.$r['pid'] );
				$last = $r['pid'];
			}

			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------

		if ( ! $done and ! $max )
		{
			//-----------------------------------------
			// Done..
			//-----------------------------------------

			$text = "<b>Rebuild completed</b><br />".implode( "<br />", $output );
			$url  = "{$this->settings['base_url']}{$this->html->form_code}&do=overview";
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------

			$dis  = $dis + $done;

			$text = "<b>Up to {$dis} processed so far, continuing...</b><br />".implode( "<br />", $output );
			$url  = $this->settings['base_url'] . $this->html->form_code . '&do=' . $this->request['do'] . '&type='.$type.'&pergo='.$this->request['pergo'].'&st='.$last.'&dis='.$dis;
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------

		$this->registry->output->redirect( $url, $text, $time );
	}

	/*-------------------------------------------------------------------------*/
	// Resyncronize Issues
	/*-------------------------------------------------------------------------*/

	function resync_issues()
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		$done   = 0;
		$start  = intval( $this->request['st'] );
		$end    = intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 100;
		$end   += $start;
		$output = array();

		//-----------------------------------------
		// Got any more?
		//-----------------------------------------
		$tmp = $this->DB->buildAndFetch(
			array(
				'select' => 'count(*) as count',
				'from'   => 'tracker_issues',
				'where'  => "issue_id > $end"
			)
		);

		$max = intval( $tmp['count'] );

		//-----------------------------------------
		// Avoid limit...
		//-----------------------------------------

		$this->DB->build(
			array(
				'select' => 'issue_id, title',
				'from'   => 'tracker_issues',
				'where'  => "issue_id >= $start and issue_id < $end",
				'order'  => 'issue_id ASC'
			)
		);
		$outer = $this->DB->execute();

		//-----------------------------------------
		// Process...
		//-----------------------------------------

		while( $r = $this->DB->fetch( $outer ) )
		{
			$this->registry->tracker->issues()->rebuildIssue( $r['issue_id'] );
			$output[] = "Processed Issue: ".$r['title'];
			$done++;
		}

		//-----------------------------------------
		// Finish - or more?...
		//-----------------------------------------
		if ( ! $done and ! $max )
		{
			//-----------------------------------------
			// Done..
			//-----------------------------------------
			$text = "<b>Rebuild completed</b><br />".implode( "<br />", $output );
			$url  = $this->html->form_code."&do=overview";
			$time = 2;
		}
		else
		{
			//-----------------------------------------
			// More..
			//-----------------------------------------
			$text = "<b>$end processed so far, continuing...</b><br />".implode( "<br />", $output );
			$url  = $this->html->form_code.'&amp;do=doresyncissues&amp;pergo='.$this->request['pergo'].'&amp;st='.$end;
			$time = 0;
		}

		//-----------------------------------------
		// Bye....
		//-----------------------------------------
		$this->registry->output->redirect( $this->settings['base_url']."&".$url, $text, $time );
	}
	
	function build_ips_field_perms_1()
	{
		foreach( $this->registry->tracker->projects()->getCache() as $k => $v )
		{
			$severity = $this->DB->buildAndFetch(
				array(
					'select'	=> '*',
					'from'		=> 'permission_index',
					'where'		=> "app='tracker' AND perm_type='severityFieldSeverityProject' AND perm_type_id=" . $v['project_id']
				)
			);
			
			if ( ! $severity['app'] )
			{
				// Insert severity
				$this->DB->insert( 'permission_index',
					array(
						'app'			=> 'tracker',
						'perm_type'		=> 'severityFieldSeverityProject',
						'perm_type_id'	=> $v['project_id'],
						'perm_view'		=> '*',
						'perm_2'		=> ',4,5,',
						'perm_3'		=> ',4,5,'
					)
				);
			}
			
			$status = $this->DB->buildAndFetch(
				array(
					'select'	=> '*',
					'from'		=> 'permission_index',
					'where'		=> "app='tracker' AND perm_type='statusFieldStatusProject' AND perm_type_id=" . $v['project_id']
				)
			);
			
			if ( ! $status['app'] )
			{
				// Insert severity
				$this->DB->insert( 'permission_index',
					array(
						'app'			=> 'tracker',
						'perm_type'		=> 'statusFieldStatusProject',
						'perm_type_id'	=> $v['project_id'],
						'perm_view'		=> '*',
						'perm_2'		=> '*',
						'perm_3'		=> ',4,5,',
						'perm_4'		=> ',4,5,'
					)
				);
			}
		}
		
		$this->registry->output->global_message = "Step 1 complete";
		$this->tools_index();
	}
	
	function build_ips_field_perms_2()
	{
		foreach( $this->registry->tracker->projects()->getCache() as $k => $v )
		{
			$version = $this->DB->buildAndFetch(
				array(
					'select'	=> '*',
					'from'		=> 'permission_index',
					'where'		=> "app='tracker' AND perm_type='versionsFieldVersionProject' AND perm_type_id=" . $v['project_id']
				)
			);
			
			if ( ! $version['app'] )
			{
				// Insert severity
				$this->DB->insert( 'permission_index',
					array(
						'app'			=> 'tracker',
						'perm_type'		=> 'versionsFieldVersionProject',
						'perm_type_id'	=> $v['project_id'],
						'perm_view'		=> '*',
						'perm_2'		=> '*',
						'perm_3'		=> ',4,5,',
						'perm_4'		=> ',4,5,'
					)
				);
			}
			
			$fixed = $this->DB->buildAndFetch(
				array(
					'select'	=> '*',
					'from'		=> 'permission_index',
					'where'		=> "app='tracker' AND perm_type='verionsFieldFixed_inProject' AND perm_type_id=" . $v['project_id']
				)
			);
			
			if ( ! $fixed['app'] )
			{
				// Insert severity
				$this->DB->insert( 'permission_index',
					array(
						'app'			=> 'tracker',
						'perm_type'		=> 'versionsFieldFixed_inProject',
						'perm_type_id'	=> $v['project_id'],
						'perm_view'		=> '*',
						'perm_2'		=> '*',
						'perm_3'		=> ',4,5,',
						'perm_4'		=> ',4,5,',
						'perm_5'		=> ',4,5,'
					)
				);
			}
		}
		
		$this->registry->output->global_message = "Step 2 complete";
		$this->tools_index();
	}
	
	function build_ips_field_perms_3()
	{
		foreach( $this->registry->tracker->projects()->getCache() as $k => $v )
		{
			if ( ! $v['fields'][1] )
			{
				$this->DB->insert( 'tracker_project_field',
					array(
						'project_id'		=> $v['project_id'],
						'field_id'			=> 1,
						'enabled'			=> 1,
						'position'			=> 1
					)
				);
			}
			
			if ( ! $v['fields'][2] )
			{			
				$this->DB->insert( 'tracker_project_field',
					array(
						'project_id'		=> $v['project_id'],
						'field_id'			=> 2,
						'enabled'			=> 1,
						'position'			=> 2
					)
				);
			}			
			
			if ( ! $v['fields'][3] )
			{
				$this->DB->insert( 'tracker_project_field',
					array(
						'project_id'		=> $v['project_id'],
						'field_id'			=> 3,
						'enabled'			=> 1,
						'position'			=> 3
					)
				);
			}
			
			if ( ! $v['fields'][4] )
			{			
				$this->DB->insert( 'tracker_project_field',
					array(
						'project_id'		=> $v['project_id'],
						'field_id'			=> 4,
						'enabled'			=> 1,
						'position'			=> 4
					)
				);
			}
		}
		
		$this->registry->output->global_message = "Step 3 complete";
		$this->tools_index();
	}

	/*-------------------------------------------------------------------------*/
	// Recache something
	/*-------------------------------------------------------------------------*/

	function build_cache()
	{
		$this->registry->tracker->modules()->rebuild();
		$this->registry->tracker->fields()->rebuild();
		$out = $this->registry->tracker->cache('files')->rebuild();
		$this->registry->tracker->moderators()->rebuild();
		$this->registry->tracker->projects()->rebuild();
		$this->registry->tracker->cache('stats')->rebuild();
		
		if ( $out == 'CANNOT_WRITE' )
		{
			$this->registry->output->showError( 'Cannot write to the Tracker cache files, please make sure all files in ' . DOC_IPS_ROOT_PATH . 'cache/tracker/ are writeable (CHMOD 777)' );
		}
		
		/* Loop through and rebuild modules */
		foreach( $this->registry->tracker->modules()->getCache() as $k => $v )
		{
			if ( is_dir( $this->registry->tracker->modules()->getModuleFolder( $v['directory'] ) . '/sources/' ) )
			{
				foreach( new DirectoryIterator( $this->registry->tracker->modules()->getModuleFolder( $v['directory'] ) . '/sources/' ) as $file )
				{
					// Make sure it isn't a folder
					if ( $file->isFile() )
					{
						$name = $file->getFilename();
						
						if ( preg_match( '#cache_(.+?).php#is', $name, $matches ) )
						{
							$this->registry->tracker->cache( $matches[1], $v['directory'] )->rebuild();
						}
					}
				}
			}
		}

		$this->registry->getClass('adminFunctions')->saveAdminLog("Rebuilt All Tracker Caches");
		$this->registry->output->global_message = "Successfully Rebuilt All Tracker Caches";

		$this->tools_index();
	}
}

?>