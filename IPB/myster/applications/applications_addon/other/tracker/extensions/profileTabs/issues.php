<?php

/**
* Tracker 2.1.0
* 
* My Issues: Profile Tab
* Last Updated: $Date: 2012-11-24 20:31:32 +0000 (Sat, 24 Nov 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	PortalPlugIn
* @link			http://ipbtracker.com
* @version		$Revision: 1393 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class profile_issues extends profile_plugin_parent
{

	/**
	 * Return HTML block
	 *
	 * @access	public
	 * @param	array		Member information
	 * @return	string		HTML block
	 */	
	public function return_html_block( $member=array() ) 
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$content   = '';
		$last_x    = 5;
		$project_ids = array();

		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'tracker' ) . "/app_class_tracker.php", 'app_class_tracker', 'tracker' );
		$app_class_tracker = new $classToLoad( $this->registry );
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'tracker' );

		//-----------------------------------------
		// Got a member?
		//-----------------------------------------

		if ( ! is_array( $member ) OR ! count( $member ) )
		{
			return $this->registry->getClass('output')->getTemplate('tracker_profile')->tabNoContent( 'err_no_issues_to_show' );
		}
						
		//-----------------------------------------
		// Allowed projects...
		//-----------------------------------------
		
		$allowedProjects = implode( ',', $this->registry->tracker->projects()->getSearchableProjects( $this->memberData['member_id'] ) );
		
		if ( ! $allowedProjects )
		{
			return $this->registry->getClass('output')->getTemplate('tracker_profile')->tabNoContent( 'err_no_issues_to_show' );
		}

		//-----------------------------------------
		// Get last X posts
		//-----------------------------------------	
	
		$this->DB->build(
			array(
				'select'   => 'i.*',
				'from'     => array( 'tracker_issues' => 'i' ),
				'where'    => "i.starter_id=" . $member['member_id'] . " AND i.project_id IN ( " . $allowedProjects . " )",
				'order' => 'i.start_date DESC',
				'limit'		=> array( 0, $last_x ),
				'add_join' => array(
					0 => array (
						'select' => 'p.*',
						'from'   => array( 'tracker_posts' => 'p' ),
						'where'  => 'i.firstpost=p.pid',
						'type'   => 'left'
					),
					1 => array(
						'select' => 'r.title as project_title',
						'from'   => array( 'tracker_projects' => 'r' ),
						'where'  => 'r.project_id=i.project_id',
						'type'   => 'left'
					)
				)
			)
		);
		
		$o = $this->DB->execute();

		while( $row = $this->DB->fetch( $o ) )
		{
			if( !$row['pid'] OR ( $row['module_privacy'] && ! $this->registry->tracker->fields()->checkPermission( $this->registry->tracker->projects()->getProject( $row['project_id'] ), 'privacy', 'show' ) ) )
			{
				continue;
			}
			
			$pids[ $row['pid'] ]	= $row['pid'];
						
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= $row['use_emo'];
			IPSText::getTextClass( 'bbcode' )->parse_html				= ( $row['use_html'] and $this->member->getProperty('g_dohtml') and $row['post_htmlstate'] ) ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $row['post_htmlstate'] == 2 ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'issues';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];
			
			$row['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['post'] );
			
			$row['_post_date']  = ipsRegistry::getClass( 'class_localization')->getDate( $row['post_date'], 'SHORT' );
			$row['_date_array'] = IPSTime::date_getgmdate( $row['post_date'] + ipsRegistry::getClass( 'class_localization')->getTimeOffset() );
			
			$row['post'] .= "\n<!--IBF.ATTACHMENT_". $row['pid']. "-->";

			$content .= $this->registry->getClass('output')->getTemplate('profile')->tabSingleColumn( $row, $this->lang->words['profile_read_topic'], $this->settings['base_url'].'app=tracker&amp;showissue='.$row['issue_id'], $row['title'] );
			
		}
				
		//-----------------------------------------
		// Macros...
		//-----------------------------------------
		
		$content = $this->registry->output->replaceMacros( $content );
		
		//-----------------------------------------
		// Return content..
		//-----------------------------------------
		
		return $content ? $this->registry->getClass('output')->getTemplate('tracker_profile')->tabIssues( $content ) : $this->registry->getClass('output')->getTemplate('tracker_profile')->tabNoContent( 'err_no_issues_to_show' );		
	}
}

?>