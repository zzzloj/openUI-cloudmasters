<?php

/**
* Tracker 2.1.0
* 
* AJAX calls
* Last Updated: $Date: 2012-05-29 14:08:19 +0100 (Tue, 29 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	JavaScript
* @link			http://ipbtracker.com
* @version		$Revision: 1372 $
*/

class public_tracker_ajax_xmlout extends ipsAjaxCommand
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs for the ajax handler]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		$this->lang->loadLanguageFile( array( 'public_topic' ), 'forums' );
		$this->lang->loadLanguageFile( array( 'public_editors' ), 'core' );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'editBoxShow':
				$this->editBoxShow();
			break;
			
			case 'editBoxSave':
				$this->editBoxSave();
			break;
		}
	}
	
	/**
	 * Saves the post
	 *
	 * @access	public
	 * @return	void
	 */
	public function editBoxSave()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$pid		   = intval( $this->request['p'] );
		$fid		   = intval( $this->request['pid'] );
		$tid		   = intval( $this->request['iid'] );
		$attach_pids   = array();

   		$this->request['post_edit_reason'] = $this->convertAndMakeSafe( $_POST['post_edit_reason'] );

   		//-----------------------------------------
		// Set things right
		//-----------------------------------------
		
		$this->request['Post'] =  IPSText::parseCleanValue( $_POST['Post'] );

		//-----------------------------------------
		// Check P|T|FID
		//-----------------------------------------

		if ( ! $pid OR ! $tid OR ! $fid )
		{
			$this->returnString( 'error' );
		}
		
		if ( $this->memberData['member_id'] )
		{
			if ( $this->memberData['restrict_post'] )
			{
				if ( $this->memberData['restrict_post'] == 1 )
				{
					$this->returnString( 'nopermission' );
				}

				$post_arr = IPSMember::processBanEntry( $this->memberData['restrict_post'] );

				if ( time() >= $post_arr['date_end'] )
				{
					//-----------------------------------------
					// Update this member's profile
					//-----------------------------------------
					
					IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'restrict_post' => 0 ) ) );
				}
				else
				{
					$this->returnString( 'nopermission' );
				}
			}
		}

		//-----------------------------------------
		// Load Lang
		//-----------------------------------------

		$this->registry->getClass( 'class_localization')->loadLanguageFile( array( 'public_issues' ) );
		$this->postClass = $this->registry->tracker->post()->editLibrary();
		
		// Set up some stuff
		$this->postClass->setPostContent( $_POST['Post'] );
		$this->postClass->setIssueID( $this->request['iid'] );
		$this->postClass->setProjectID( $this->request['p'] );
		$this->postClass->setPostID( $this->request['pid'] );
		$this->postClass->setAuthor( $this->memberData['member_id'] );
											   
		$this->postClass->setIsAjax(1);
		
		if( isset($this->request['post_htmlstatus']) )	// Off is "0"
		{
			$this->postClass->setSettings( array( 'post_htmlstatus' => $this->request['post_htmlstatus'] ) );
		}
		
		$this->postClass->setSettings(
			array(
				'enableEmoticons'	=> 1
			)
		);
		
		# Forum Data
		$this->postClass->register();

		# Get Edit form
		try
		{
			/**
			 * If there was an error, return it as a JSON error
			 */
			$this->postClass->processPost();
			
			if ( $this->postClass->getPostError() )
			{
				$this->returnJsonError( $this->postClass->getPostError() );
			}
			
			$topic = $this->postClass->issue;
			$post  = $this->postClass->post;
			
			//-----------------------------------------
			// Pre-display-parse
			//-----------------------------------------
			$project = $this->registry->tracker->projects()->getProject( $fid );
			
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= $post['use_emo'];
			IPSText::getTextClass( 'bbcode' )->parse_html				= ( $project['use_html'] and $this->memberData['g_dohtml'] and $post['post_htmlstate'] ) ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $post['post_htmlstate'] == 2 ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'tracker_issues';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];
				
			$post['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $post['post'] );

			if ( IPSText::getTextClass( 'bbcode' )->error )
			{
				$this->returnJsonError( $this->lang->words[ IPSText::getTextClass( 'bbcode' )->error ] );
			}			

			$edit_by	= '';
			
			if ( $post['append_edit'] == 1 AND $post['edit_time'] AND $post['edit_name'] )
			{
				$e_time		= $this->registry->getClass( 'class_localization')->getDate( $post['edit_time'] , 'LONG' );
				$edit_by	= sprintf( $this->lang->words['edited_by'], $post['edit_name'], $e_time );
			}
			
			/* Attachments */
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------

				$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach	= new $classToLoad( $this->registry );
			}

			$this->class_attach->type  = 'tracker';
			$this->class_attach->init();

			$attachHtml             = $this->class_attach->renderAttachments( array( $pid => $post['post'] ) );
			$post['post']           = $attachHtml[ $pid ]['html'];
			$post['attachmentHtml'] = $attachHtml[ $pid ]['attachmentHtml'];
			
			$output		= $this->registry->output->getTemplate('topic')->quickEditPost( array(
																							'post'				=> $this->registry->getClass('output')->replaceMacros( IPSText::stripAttachTag( $post['post'] ) ),
																							'attachmentHtml'    => $post['attachmentHtml'],
																							'pid'				=> $pid,
																							'edit_by'			=> $edit_by,
																							'post_edit_reason'	=> $post['post_edit_reason']
																					) 		);

			//-----------------------------------------
			// Return plain text
			//-----------------------------------------

			$this->returnJsonArray( array( 'successString' => $output ) );
		}
		catch ( Exception $error )
		{
			$this->returnJsonError( $error->getMessage() );
		}
	}
	
	/**
	 * Shows the edit box
	 *
	 * @access	public
	 * @return	void
	 */
	public function editBoxShow()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$pid		 = intval( $this->request['p'] );
		$fid		 = intval( $this->request['pid'] );
		$tid		 = intval( $this->request['iid'] );
		$show_reason = 0;

		//-----------------------------------------
		// Check P|T|FID
		//-----------------------------------------
		
		if ( ! $pid OR ! $tid OR ! $fid )
		{
			$this->returnString( 'error' );
		}
		
		if ( $this->memberData['member_id'] )
		{
			if ( $this->memberData['restrict_post'] )
			{
				if ( $this->memberData['restrict_post'] == 1 )
				{
					$this->returnString( 'nopermission' );
				}
				
				$post_arr = IPSMember::processBanEntry( $this->memberData['restrict_post'] );
				
				if ( time() >= $post_arr['date_end'] )
				{
					//-----------------------------------------
					// Update this member's profile
					//-----------------------------------------
					
					IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'restrict_post' => 0 ) ) );
				}
				else
				{
					$this->returnString( 'nopermission' );
				}
			}
		}

		//-----------------------------------------
		// Get classes
		//-----------------------------------------
		$this->postClass = $this->registry->tracker->post()->editLibrary();
		
		# Forum Data
		$this->postClass->register();
		
		// Data
		$this->postClass->setIssueID( $tid );
		$this->postClass->setProjectID( $fid );
		$this->postClass->setPostID( $pid );
		$this->postClass->setIsAjax(1);
		
		$this->postClass->setAuthor( $this->memberData['member_id'] );
		
		# Get Edit form
		try
		{
			$html = $this->postClass->displayAjaxEditForm();
			
			$html = $this->registry->output->replaceMacros( $html );

			$this->returnHtml( $html );
		}
		catch ( Exception $error )
		{
			$this->returnString( $error->getMessage() );
		}
	}
}

?>