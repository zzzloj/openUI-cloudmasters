<?php

/**
* Tracker 2.1.0
* 
* Projects Javascript PHP Interface
* Last Updated: $Date: 2012-09-02 21:38:05 +0100 (Sun, 02 Sep 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Admin
* @link			http://ipbtracker.com
* @version		$Revision: 1384 $
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Type: Admin
 * Moderator AJAX processor
 * 
 * @package Tracker
 * @subpackage Admin
 * @since 2.0.0
 */
class admin_tracker_ajax_moderators extends ipsAjaxCommand 
{
	/**
	 * Shortcut for url
	 *
	 * @var string URL shortcut
	 * @access protected
	 * @since 2.0.0
	 */
	protected $form_code;
	/**
	 * Shortcut for url (javascript)
	 *
	 * @var string JS URL shortcut
	 * @access protected
	 * @since 2.0.0
	 */
	protected $form_code_js;
	/**
	 * Skin object
	 *
	 * @var object Skin templates
	 * @access protected
	 * @since 2.0.0
	 */
	protected $html;

	/**
	 * Initial function.  Called by execute function in ipsCommand 
	 * following creation of this class
	 *
	 * @param ipsRegistry $registry the IPS Registry
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load HTML */
		$this->html         = $this->registry->output->loadTemplate('cp_skin_moderators');
		$this->form_code    = $this->html->form_code    = 'module=moderators&amp;section=moderators';
		$this->form_code_js = $this->html->form_code_js = 'module=moderators&section=moderators';
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_moderators' ) );

		//-----------------------------------------
		// What shall we do?
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'conflicts':
				$this->checkForConflicts();
				break;
			case 'getProjects':
				$this->findModeratorProjects();
				break;
			case 'permissions':
				$this->permissionsPopup();
				break;
			case 'templateData':
				$this->returnTemplateData();
				break;
			default:
				$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
				break;
		}
	}

	/**
	 * Used by the moderator form to check for individual projects
	 * that a member or group already has moderator privileges for.
	 *
	 * @return void [JSON array output 'result' => 'ok|conflicts|error']
	 * @access public
	 * @since 2.0.0
	 */
	private function checkForConflicts()
	{
		$incoming   = json_decode($_POST['data'], true);
		$type       = '';
		$projects   = array();

		$out        = array( 'result' => 'ok' );
		$pids       = "";
		$testID     = 0;

		// Validate inputs
		if ( isset( $incoming['type'] ) && isset( $incoming['projects'] ) && is_array( $incoming['projects'] ) && count( $incoming['projects'] ) > 0 )
		{
			$type     = $incoming['type'];
			$projects = $incoming['projects'];

			// String out the project ids
			$pids = implode( ",", $projects );

			// Setup the test for group
			if ( $type == 'group' )
			{
				$testID = intval( $incoming['group_id'] );
			}
			// Or check that the member is valid
			else
			{
				$member = $this->DB->buildAndFetch(
					array(	
						'select' => 'member_id, members_display_name',
						'from'   => 'members',
						'where'  => "members_l_display_name='".strtolower($incoming['member_id'])."'"
					)
				);

				if ( is_array( $member ) && isset( $member['member_id'] ) &&  $member['member_id'] > 0 )
				{
					$testID = $member['member_id'];
				}
			}

			// Load rows for the member/group that include the project_ids provided
			$this->DB->build(
				array(
					'select'    => 'm.moderate_id,m.project_id,p.title',
					'from'      => array( 'tracker_moderators' => 'm' ),
					'where'     => "m.type = '{$type}' AND m.mg_id = {$testID} AND m.project_id IN ({$pids})",
					'add_join'  => array(
						0 => array(
							'select' => 'p.title',
							'from'   => array( 'tracker_projects' => 'p' ),
							'where'  => 'm.project_id = p.project_id',
							'type'   => 'left'
						)
					)
				)
			);
			$this->DB->execute();

			// Houston, we have conflicts
			if ( $this->DB->getTotalRows() > 0 )
			{
				$outPIDs = array();

				// Load up the conflicted projects
				while ( $r = $this->DB->fetch() )
				{
					if ( isset( $r['project_id'] ) && $r['project_id'] > 0 && isset( $r['title'] ) && strlen( $r['title'] ) > 0 )
					{
						$outPIDs[ $r['project_id'] ] = $r['title'];
					}
				}

				// Set output
				if ( count( $outPIDs ) > 0 )
				{
					$out = array( 'result' => 'conflicts', 'projects' => $outPIDs );
				}
			}
		}
		// Error out with in sufficient input data
		else
		{
			$out = array( 'result' => 'error', 'error' => TRUE );
		}

		$this->returnJsonArray( $out );
	}

	/**
	 * Used by the moderator listing to expand members and group to shot
	 * the individual projects they moderate.
	 *
	 * @return void [JSON array output 'status' => 'ok|error'
	 * @access public
	 * @since 2.0.0
	 */
	private function findModeratorProjects()
	{
		$content    = '';
		$out        = array( 'status' => 'error' );
		$projects   = array();
		
		if ( isset( $this->request['mg_id'] ) && intval( $this->request['mg_id'] ) > 0 )
		{
			// Get the projects, but make sure we don't get super mods
			$this->DB->build(
				array(
					'select'	=> 'moderate_id,project_id',
					'from'		=> 'tracker_moderators',
					'where'		=> 'is_super=0 AND mg_id=' . intval( $this->request['mg_id'] )
				)
			);
			$e = $this->DB->execute();

			// Loop through projects that this person moderates
			if ( $this->DB->getTotalRows() > 0 )
			{
				while( $row = $this->DB->fetch($e) )
				{
					$projects[ $row['moderate_id'] ] = $this->registry->tracker->projects()->getProject( $row['project_id'] );
				}
			}

			// Loop through our projects
			if ( count( $projects ) > 0 )
			{
				foreach( $projects as $k => $v )
				{
					$content .= $this->html->moderatorChildRow( $k, $v );
				}

				$out = array( 'status' => 'ok', 'content' => $content );
			}
		}

		if ( $out['status'] != 'ok' )
		{
			$out['error'] = TRUE;
		}

		$this->returnJsonArray( $out );
	}

	/**
	 * Asks for all of a members permissions to show in the overview popup
	 *
	 * @return void [JSON array output 'status' => 'ok|error', data => array[ html nodes ]]
	 * @access private
	 * @since 2.0.0
	 */
	private function permissionsPopup()
	{
		$out = array( 'status' => 'error' );

		if ( isset( $this->request['moderate_id'] ) && intval( $this->request['moderate_id'] ) > 0 )
		{
			$moderator = $this->DB->buildAndFetch(
				array(
					'select'    => "md.*",
					'from'      => array('tracker_moderators' => 'md'),
					'add_join'  => array(
						0 => array(
							'select' => 'm.members_display_name',
							'from'   => array( 'members' => 'm' ),
							'where'  => "md.mg_id=m.member_id and md.type='member'",
							'type'   => 'left'
						),
						1 => array(
							'select' => 'g.g_title',
							'from'   => array( 'groups' => 'g' ),
							'where'  => "md.mg_id=g.g_id and md.type='group'",
							'type'   => 'left'
						)
					),
					'where'    => "md.moderate_id = " . intval($this->request['moderate_id']),
				)
			);

			if ( is_array( $moderator ) && count( $moderator ) > 0 && $moderator['moderate_id'] > 0 )
			{
				// Core columns
				$ignore     = array('moderate_id','project_id','template_id','type','mode','mg_id','is_super','name','members_display_name','g_title','can_manage');
				$core       = array('can_edit_posts','can_edit_titles','can_del_issues','can_del_posts','can_lock',
								'can_unlock','can_move','can_manage','can_merge','can_massmoveprune');
				$data       = array();
				$modsToLoad = array();

				// Cache
				$fields     = $this->registry->tracker->fields()->getCache();
				$modules    = $this->registry->tracker->modules()->getCache();
				
				// Alphabetic
				ksort($moderator);
				
				// Loop through our result
				foreach( $moderator as $k => $v )
				{
					if ( ! in_array( $k, $ignore ) )
					{
						if ( in_array( $k, $core ) )
						{
							$data[$k] = array( 'module' => 'core', 'field' => $v );
						}
						else
						{
							$module		= str_replace( strstr( $k, '_' ), '', $k );
							
							foreach( $modules as $a => $b )
							{
								if ( strstr( $k, $b['directory'] . '_' ) )
								{
									$module = $b['directory'];
								}
							}
							
							foreach( $fields as $a => $b )
							{
								if ( strstr( $k, $b['field_keyword'] . '_' ) )
								{
									$field	= $b['field_keyword'];
									$fData	= $this->registry->tracker->fields()->getField( $b['field_id'] );
								}
							}
							
							$data[$k]	= array( 'module' => $field, 'field' => $v, 'title' => $fData['title'] );
							
							// Add to loader
							if ( ! in_array( $module, $modsToLoad ) )
							{
								ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_module_' . $module ) );
								$modsToLoad[] = $module;
							}
						}

						$permMap[$k]	= $this->lang->words[$k] ? $this->lang->words[$k] : $k;
					}
				}

				// Now we know what we need
				$out['status']      = 'ok';
				$out['data']        = $data;
				$out['perm_map']    = $permMap;

				switch( $this->request['template'] )
				{
					case 'true':  $out['name'] = $moderator['name'];
						break;
					case 'false': $out['name'] = $moderator['members_display_name'] ? $moderator['members_display_name'] : $moderator['g_title'];
						break;
				}
			}
		}

		if ( $out['status'] != 'ok' )
		{
			$out['error'] = TRUE;
		}

		// Return it back to Alex
		$this->returnJsonArray( $out );
	}
	
	private function returnTemplateData()
	{
		$out = array( 'status' => 'ok' );
		
		if ( ! $this->request['template_id'] )
		{
			$out['status'] = 'error';
		}
		else
		{
			$template = $this->registry->tracker->moderators()->getTemplate( intval( $this->request['template_id'] ) );
			
			if ( ! $template )
			{
				$out['status'] = 'error';
			}
			else
			{
				unset( $template['moderate_id'] );
				unset( $template['name'] );
				unset( $template['is_super'] );
				unset( $template['num_mods'] );

				$out['data'] = $template;
			}
		}
		
		// Return it back to Alex
		$this->returnJsonArray( $out );
	}
}

?>