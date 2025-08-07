<?php

/**
 * Tracker 2.1.0
 * 
 * Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
 *
 * @author		$Author: stoo2000 $
 * @copyright	2001 - 2013 Invision Power Services, Inc.
 *
 * @package		Tracker
 * @subpackage	AdminSkin
 * @link		http://www.invisionpower.com
 * @version		$Revision: 1363 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_tracker_ajax_attachments extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$topic_id		= intval( $this->request['issue_id'] );
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_stats' ), 'forums' );
		
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if ( ! $topic_id )
        {
        	$this->returnJsonError( $this->lang->words['notopic_attach'] );
        }
        
        //-----------------------------------------
        // get topic..
        //-----------------------------------------
        
        $topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'tracker_issues', 'where' => 'issue_id=' . $topic_id ) );
        
        if ( ! $topic['hasattach'] )
        {
        	$this->returnJsonError( $this->lang->words['topic_noattach'] );
        }
        
        //-----------------------------------------
        // Check forum..
        //-----------------------------------------
        
        if ( $this->registry->tracker->projects()->checkPermission( 'read', $topic['project_id'] ) === false )
		{
			$this->returnJsonError( $this->lang->words['topic_noperms'] );
		}
		
		$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('tracker') . '/modules_public/projects/attach.php', 'public_tracker_projects_attach' );
		$attach	= new $classToLoad( $this->registry );
		$attach->makeRegistryShortcuts( $this->registry );
		
		$attachHTML	= $attach->getAttachments( $topic );
		
		if ( !$attachHTML )
		{
			$this->returnJsonError( $this->lang->words['ajax_nohtml_return'] );
		}
		else
		{
			$this->returnHtml( $this->registry->getClass('output')->getTemplate('forum')->forumAttachmentsAjaxWrapper( $attachHTML ) );
		}
	}
}