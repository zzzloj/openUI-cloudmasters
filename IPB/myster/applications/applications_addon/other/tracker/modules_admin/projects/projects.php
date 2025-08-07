<?php

/**
* Tracker 2.1.0
* 
* List Projects module
* Last Updated: $Date: 2012-05-29 14:08:19 +0100 (Tue, 29 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Admin
* @link			http://ipbtracker.com
* @version		$Revision: 1372 $
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 * Projects ACP class
 *
 * @package Tracker
 * @subpackage Admin
 * @since 2.0.0
 */
class admin_tracker_projects_projects extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @var object Skin templates
	 * @access private
	 * @since 2.0.0
	 */
	private $html;

	/**
	 * Shortcut for url
	 *
	 * @var string URL shortcut
	 * @access private
	 * @since 2.0.0
	 */
	private $form_code;

	/**
	 * Shortcut for url (javascript)
	 *
	 * @var string JS URL shortcut
	 * @access private
	 * @since 2.0.0
	 */
	private $form_code_js;

	/**
	 * Project parent
	 *
	 * @var array Project parent info
	 * @access private
	 * @since 1.2.0
	 */
	private $par = array();

	/**
	 * Main class entry point
	 *
	 * @param object ipsRegistry $registry reference
	 * @return void [Outputs to screen]
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load skin and language
		//-----------------------------------------

		$this->html = $this->registry->output->loadTemplate('cp_skin_projects');
		$this->registry->class_localization->loadLanguageFile( array( 'admin_projects' ) );

		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------

		$this->form_code    = $this->html->form_code    = 'module=projects&amp;section=projects';
		$this->form_code_js = $this->html->form_code_js = 'module=projects&section=projects';

		//-----------------------------------------
		// Parenting?
		//-----------------------------------------

		if( isset( $this->request['parent']) and intval($this->request['parent']) > 0 )
		{
			$this->par = $this->registry->tracker->projects()->getProject( $this->request['parent'] );
		}
		else
		{
			$this->par['project_id'] = 0;
		}

		//--------------------------------------------
		// Sup?
		//--------------------------------------------

		switch($this->request['do'])
		{
			case 'add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tracker_projects_manage' );
				$this->projectForm('add');
				break;
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tracker_projects_manage' );
				$this->projectForm('edit');
				break;
			case 'add_save':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tracker_projects_manage' );
				$this->projectSave('add');
				break;
			case 'edit_save':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tracker_projects_manage' );
				$this->projectSave('edit');
				break;
			case 'delete':
				$this->projectDelete();
				break;
			case 'dodelete':
				$this->projectDoDelete();
				break;
			case 'project_move':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tracker_projects_manage' );
				$this->projectMove();
				break;
			case 'stats':
				//$this->projectStatistics();
				//break;
			default:
				$this->projectOverview();
				break;
		}

		# Output #
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	private function projectStatistics()
	{
		if ( intval( $this->request['parent'] ) != $this->request['parent'] OR ! $this->request['parent'] )
		{
			$this->registry->output->showError( 'We could not find the project you asked for' );
		}

		$this->registry->output->html .= $this->html->project_statistics();
		return;
	}

	/**
	 * Project overview
	 *
	 * @TODO	Make use of the cache to get all projects
	 * @access	private
	 * @param	integer	Project id
	 * @return	void
	 */
	private function projectOverview()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$count   = array();

		//-----------------------------------------
		// Get projects
		//-----------------------------------------

		$this->DB->build(
			array(
				'select' => '*',
				'from'   => 'tracker_projects',
				'where'  => "parent_id = 0",
				'order'  => 'position ASC'
			)
		);
		$q = $this->DB->execute();

		$cache = $this->registry->tracker->projects()->getCache();
		$count = $this->DB->getTotalRows($q);
		$rows  = '';

		if ( $count > 0 )
		{
			while( $r = $this->DB->fetch($q) )
			{
				if ( isset( $cache[ $r['project_id'] ] ) && is_array( $cache[ $r['project_id'] ] ) && count( $cache[ $r['project_id'] ] ) > 0 )
				{
					$r['num_issues'] = $cache[ $r['project_id'] ]['num_issues'];
					$r['num_posts']  = $cache[ $r['project_id'] ]['num_posts'] ? $cache[ $r['project_id'] ]['num_posts'] : 0;
				}
				else
				{
					$r['num_issues'] = "??";
					$r['num_posts']  = "??";
				}

				if ( is_array( $this->registry->tracker->projects()->getChildren( $r['project_id'] ) ) && count( $this->registry->tracker->projects()->getChildren( $r['project_id'] ) ) > 0 )
				{
					$r['has_children']	= true;
				}
	
				$rows .= $this->html->project_row( $r );
			}
		}
		else 
		{
			# No rows #
			$rows = $this->html->project_row( $r );
		}

		$this->registry->output->html .= $this->html->project_overview( $rows, $count );
	}

	/**
	 * Project form
	 *
	 * @access	private
	 * @param	string	Are we editing or adding
	 * @return	void
	 */
	private function projectForm( $type )
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------

		$project_id = intval($this->request['project_id']);
		$project    = array();
		$perms      = "";

		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------

		if ( $type == 'add' )
		{
			$project = array( 
				'cat_only'              => 0,
				'enable_rss'            => 1,
				'use_html'              => 0,
				'use_ibc'               => 1,
				'private_issues'        => 1,
				'private_default'       => 0
			);

			$code   = 'add_save';
			$title  = $this->lang->words['p_addproject'];
			$button = $this->lang->words['p_addproject'];
		}
		else
		{
			$project = $this->DB->buildAndFetch(
				array(
					'select'   => 'p.*', 
					'from'     => array('tracker_projects' => 'p'), 
					'add_join' => array(
						1 => array( 
							'select' => 'pm.*',
							'from'   => array( 'permission_index' => 'pm' ),
							'where'  => "pm.perm_type_id=p.project_id AND pm.app='tracker' AND pm.perm_type='project'",
							'type'   => 'left',
						)
					),
					'where'    => 'project_id='.$project_id
				)
			);

			if ( !$project['project_id'] )
			{
				$this->registry->output->global_message = "No ID was passed, please try again.";
				$this->projectOverview();
				return;
			}

			$code   = 'edit_save';
			$button = $this->lang->words['p_editproject'];
			$title  = $this->lang->words['p_editproject'] . $project['title'];
		}

		# Basic options #
		$this->request['parent_id']			= $this->request['parent_id'] ? $this->request['parent_id'] : $this->request['parent'];

		$form['parent']				= $this->registry->output->formDropdown( 'parent_id', $this->registry->tracker->projects()->makeAdminDropdown(), ( isset( $this->request['parent_id'] ) AND $this->request['parent_id'] ) ? intval( $this->request['parent_id'] ) : $project['parent_id'] );
		
		$form['title']              = $this->registry->output->formInput( 'title', ( isset( $this->request['title'] ) AND $this->request['title'] ) ? intval( $this->request['title'] ) : $project['title'] );
		
		$form['description']        = $this->registry->output->formTextarea( 'description',     IPSText::br2nl( ( isset( $this->request['description']) AND $this->request['description'] ) ? $this->request['description'] : $project['description'] ) );

		$form['category']           = $this->registry->output->formYesNo(    'category',        ( isset($this->request['category'])        AND $this->request['category'] )        ? $this->request['category']        : ( $project['cat_only'] == 1 ? 1 : 0 ) );

		$form['rss']                = $this->registry->output->formYesNo(    'rss',             ( isset($this->request['rss'])             AND $this->request['rss'] )             ? $this->request['rss']             : $project['enable_rss'] );
		
		# Posting Options #
		$form['html']               = $this->registry->output->formYesNo(    'html',            ( isset($this->request['html'])            AND $this->request['html'] )            ? $this->request['html']            : $project['use_html'] );
		$form['bbcode']             = $this->registry->output->formYesNo(    'bbcode',          ( isset($this->request['bbcode'])          AND $this->request['bbcode'] )          ? $this->request['bbcode']          : $project['use_ibc'] );
		
		# Suggestions (2.1)
		$form['suggestions']             = $this->registry->output->formYesNo(    'suggestions',          ( isset($this->request['suggestions'])          AND $this->request['suggestions'] )          ? $this->request['suggestions']          : $project['enable_suggestions'] );
		
		# Tagging (2.1)
		$form['tagging']             = $this->registry->output->formYesNo(    'tagging',          ( isset($this->request['tagging'])          AND $this->request['tagging'] )          ? !$this->request['tagging']          : !$project['disable_tagging'] );
		
		# Private Issues #
		$form['private_issues']     = $this->registry->output->formYesNo( 'private_issues', ( isset($this->request['private_issues']) AND $this->request['private_issues'] ) ? $this->request['private_issues'] : $project['private_issues'] );
		$form['private_default']    = $this->registry->output->formYesNo( 'private_default', ( isset($this->request['private_default']) AND $this->request['private_default'] ) ? $this->request['private_default'] : $project['private_default'] );

		//-----------------------------------------
		// Show permissions
		//-----------------------------------------

		$project_perms = $this->registry->tracker->perms()->adminPermMatrix( 'project', $project );

		//-----------------------------------------
		// Send output
		//-----------------------------------------

		$this->registry->output->html .= $this->html->project_form( $form, $button, $code, $title, $project, $project_perms, $this->registry->tracker->projects()->projectFields($project) );
	}

	/**
	 * Save new/edited project
	 *
	 * @access	private
	 * @param	string	Type of save we're performing
	 * @return	void
	 */
	private function projectSave( $type='add' )
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------

		$save = array(
			'project_id'			=> intval($this->request['project_id']),
			'title'					=> IPSText::safeslashes( IPSText::getTextClass('bbcode')->xssHtmlClean( nl2br( $this->request['title'] ) ) ),
			'description'			=> IPSText::safeslashes( html_entity_decode( IPSText::getTextClass('bbcode')->xssHtmlClean( nl2br( $this->request['description'] ) ) ) ),
			'cat_only'				=> intval($this->request['category']),
			'enable_rss'			=> intval($this->request['rss']),
			'use_html'				=> intval($this->request['html']),
			'use_ibc'				=> intval($this->request['bbcode']),
			'private_issues'		=> intval($this->request['private_issues']),
			'private_default'		=> intval($this->request['private_default']),
			'parent_id'				=> intval($this->request['parent_id']),
			'enable_suggestions'	=> intval($this->request['suggestions']),
			'disable_tagging'		=> intval($this->request['tagging']) ? 0 : 1
		);

		//--------------------------------------------
		// Checks...
		//--------------------------------------------

		if ( $type == 'edit' )
		{
			if ( empty( $save['project_id'] ) )
			{
				$this->registry->output->global_message = $this->lang->words['p_err_id'];
				$this->projectOverview();
				return;
			}
		}

		if ( ! $save['title'] )
		{
			$this->registry->output->global_message = $this->lang->words['p_err_incomplete'];
			$this->projectForm( $type );
			return;
		}

		if ( ! isset( $save['parent_id'] ) )
		{
			$this->registry->output->global_message = $this->lang->words['p_err_noparent'];
			$this->projectForm( $type );
			return;
		}

		//--------------------------------------------
		// Save...
		//--------------------------------------------

		if ( $type == 'add' )
		{
			$row = $this->DB->buildAndFetch (
				array (
					'select' => 'max(position) as position',
					'from'   => 'tracker_projects',
					'where'  => "parent_id = {$save['parent_id']}"
				)
			);

			$order = $row['position'] + 1;

			$save['position']      = $order;

			$this->DB->insert( 'tracker_projects', $save );
			$this->request['project_id'] = $this->DB->getInsertId();
			$this->registry->output->global_message = $this->lang->words['p_created'];
		}
		else
		{
			$this->DB->update( 'tracker_projects', $save, 'project_id=' . $save['project_id'] );
			$this->registry->output->global_message = $this->lang->words['p_updated'];
		}
		
		/* Save fields enabled/disabled and field order */
		$fieldOrder = 1;
		$fieldData	= json_decode( $_POST['enable_data'], true );

		if ( is_array( $fieldData ) && count( $fieldData ) > 0 )
		{
			foreach( $fieldData as $k => $v )
			{
				if ( preg_match( "/enabled_(.+?)$/", $k, $matches ) )
				{
					$this->DB->update( 'tracker_project_field',
						array(
							'position'	=> $fieldOrder,
							'enabled'	=> $v
						),
						'field_id=' . intval($matches[1]) . ' AND project_id=' . intval( $this->request['project_id'] )
					);
					
					$fieldOrder++;
				}
			}
		}

		if ( $this->request['json_data'] )
		{
			$data = json_decode( $_POST['json_data'], true );
			
			if ( is_array($data) )
			{
				foreach( $data as $k => $v )
				{
					$fid    = intval( str_replace( 'field_', '', $k ) );
					$field  = $this->registry->tracker->fields()->getField( $fid );
					$module = $this->registry->tracker->modules()->getModuleByID( $field['module_id'] );

					/* Send it off to the 'save' function */
					$this->registry->tracker->fields()->perms()->savePermMatrix( $v['perms'], $save['project_id'], $field['field_keyword'], $module['directory'] );

					/* And the save function */
					unset( $v['perms'] );
					$this->registry->tracker->fields()->extension( $field['field_keyword'], $module['directory'], 'project_form' )->save( $v );
				}
			}
		}

		//-----------------------------------------
		// Save permissions
		//-----------------------------------------

		$this->registry->tracker->perms()->savePermMatrix( $this->request['perms'], $save['project_id'], 'project' );

		//-----------------------------------------
		// Update statistics & project cache
		//-----------------------------------------

		$this->registry->tracker->cache('stats')->rebuild();
		$this->registry->tracker->projects()->update($save['project_id']);
		$this->registry->tracker->moderators()->rebuild();

		if ( $this->inAjax == true )
		{
			return 'url';
		}

		$this->projectOverview();
	}

	/**
	 * Get ready to delete a project
	 *
	 * @access 	private
	 * @param	void
	 * @return	void
	 */
	private function projectDelete()
	{
		$project_id = intval($this->request['project_id']);

		if ( ! $project_id )
		{
			$this->registry->output->showError("Could not determine the project ID to delete.");
		}

		$project = $this->registry->tracker->projects()->getProject( $project_id );

		//-----------------------------------------
		// Count the number of reports/projects
		//-----------------------------------------
		$reports	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'tracker_issues', 'where' => 'project_id='.$project_id ) );

		$this->DB->build(
			array(
				'select'	=> 'project_id',
				'from'		=> 'tracker_projects',
				'where'		=> 'parent_id=' . $project_id
			)
		);
		$projects = $this->DB->execute();
		
		$showMoveProjects	= 0;
		$dontShow			= array( 0 => $project_id );
		
		if ( $this->DB->getTotalRows($projects) )
		{
			$showMoveProjects = 1;
			
			while ( $child = $this->DB->fetch($projects) )
			{
				$dontShow[] = $child['project_id'];
			}
		}
		
		// Get dropdowns 
		$pdropdown = $this->registry->tracker->projects()->makeAdminDropdown(); 

		foreach( $pdropdown as $k => $v )
		{
			if ( in_array( $v[0], $dontShow ) )
			{
				unset($pdropdown[$k]);
			}
		}
		
		// Get dropdowns 
		$dropdown = $this->registry->tracker->projects()->makeAdminDropdown(FALSE); 

		foreach( $dropdown as $k => $v )
		{
			if ( in_array( $v[0], $dontShow ) )
			{
				unset($dropdown[$k]);
			}
		}

		// Delete issues
		$form['delete_issues'] = $this->registry->output->formYesNo( 'delete_issues', 1 );

		if ( $reports['count'] > 0 )
		{
			$form['moveto']			= $this->registry->output->formDropdown( 'moveto', $dropdown );
		}
		
		if ( $showMoveProjects )
		{
			$form['moveprojects']	= $this->registry->output->formDropdown( 'moveprojects', $pdropdown );
		}

		// Show form
		$this->registry->output->html .= $this->html->delete_project( $form, $project, $reports['count'] );
	}

	/**
	 * Process the delete project request
	 *
	 * @access 	private
	 * @param	void
	 * @return	void
	 */
	private function projectDoDelete()
	{
		$project_id     = intval( $this->request['project_id'] );
		$moveto         = intval( $this->request['moveto'] );
		$moveprojects	= intval( $this->request['moveprojects'] );
		$delete_issues  = intval( $this->request['delete_issues'] );

		//----------------------------------------------------
		// Are there any issues belonging to this project?
		//----------------------------------------------------

		$this->DB->build(
			array(
				'select' => 'issue_id',
				'from'   => 'tracker_issues',
				'where'  => 'project_id='.$project_id
			)
		);
		$this->DB->execute();

		$issues_exist = $this->DB->getTotalRows();

		//----------------------------------------------------
		// Move or delete - exactly how worthless are these issues?
		//----------------------------------------------------

		if( $issues_exist && $delete_issues ) 
		{
			$arr_issues = array();

			while ( $r = $this->DB->fetch() )
			{
				$arr_issues[] = $r['issue_id'];
			}

			$project_issues = implode( ',', $arr_issues);

			$project_posts = "";

			if( $project_issues != "")
			{
				$this->DB->build(
					array(
						'select' => 'issue_id',
						'from'   => 'tracker_posts',
						'where'  => 'issue_id IN('.$project_issues.')'
					)
				);
				$this->DB->execute();

				$arr_posts = array();

				while ( $r = $this->DB->fetch() )
				{
					$arr_posts[] = $r['issue_id'];
				}

				$project_posts = implode( ',', $arr_posts);
			}

			// bye, bye...
			if( $project_posts != "")
			{
				$this->DB->delete( 'tracker_posts', 'issue_id IN('.$project_posts.')' );	
			}

			if( $project_issues != "")
			{
				$this->DB->delete( 'tracker_issues', 'issue_id IN('.$project_issues.')' );
			}
		}
		else if( $issues_exist && $moveto )
		{
			//-----------------------------------------
			// Move topics
			//-----------------------------------------

			$this->DB->update( 'tracker_issues', array( 'project_id' => $moveto ), 'project_id='.$project_id );

			//-----------------------------------------
			// Update status counts
			//-----------------------------------------

			$this->registry->tracker->projects()->update( $moveto );
		}
		
		//-----------------------------------------
		// Move old projects to new parent
		//-----------------------------------------
		
		$this->DB->update( 'tracker_projects', array( 'parent_id' => $moveprojects ), 'parent_id=' . $project_id );

		//-----------------------------------------
		// Delete project
		//-----------------------------------------

		$project = $this->registry->tracker->projects()->getProject( $project_id );
		$this->DB->delete( 'tracker_projects', 'project_id='.$project_id );
		$this->DB->delete( 'tracker_project_field', 'project_id='.$project_id );

		//-----------------------------------------
		// Delete project permissions
		//-----------------------------------------

		$project = $this->registry->tracker->projects()->getProject( $project_id );
		$this->DB->delete( 'permission_index', "app='tracker' AND perm_type='project' AND perm_type_id=".$project_id );

		//-----------------------------------------
		// Update statistics & projects
		//-----------------------------------------

		$this->registry->tracker->cache('stats')->rebuild();
		$this->registry->tracker->projects()->rebuild();
		$this->registry->tracker->moderators()->rebuild();

		$this->projectOverview( $project['parent_id'] );
	}
}

?>