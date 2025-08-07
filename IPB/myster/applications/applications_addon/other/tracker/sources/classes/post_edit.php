<?php

/**
* Tracker 2.1.0
* 
* Edit post class
* Last Updated: $Date: 2012-10-10 15:53:22 +0100 (Wed, 10 Oct 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Core
* @link			http://ipbtracker.com
* @version		$Revision: 1388 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class tracker_core_post_edit extends tracker_core_post_main
{
	public function register()
	{
		parent::register();	

		$this->request['pid'] = intval( $this->request['pid'] );

		//-----------------------------------------
		// Load project
		//-----------------------------------------

		if ( isset( $this->request['pid'] ) && is_array( $this->tracker->projects()->getProject( $this->request['pid'] ) ) )
		{
			self::$project = $this->tracker->projects()->getProject( $this->request['pid'] );
		}
		else
		{
			$this->registry->output->showError( 'We could not find the project you were trying to edit an issue in', '20T102' );
		}

		$this->buildPermissions();

		//-----------------------------------------
		// Lets load the topic from the database before we do anything else.
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'tracker_issues', 'where' => "issue_id=".intval($this->request['iid']) ) );
		$this->DB->execute();

		$this->issue = $this->DB->fetch();

		//-----------------------------------------
		// Is it legitimate?
		//-----------------------------------------

		if ( ! $this->issue['issue_id'] )
		{
			$this->registry->output->showError( 'We could not find the issue you were trying to edit', '20T103' );
		}
		
		//-----------------------------------------
		// Load the old post
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'tracker_posts', 'where' => "pid=".intval($this->request['p']) ) );
		$this->DB->execute();

		$this->orig_post = $this->DB->fetch();

		if ( ! $this->orig_post['pid'] )
		{
			$this->registry->output->showError( 'We could not find the post you were trying to edit', '20T107' );
		}

		//-----------------------------------------
		// Same topic?
		//-----------------------------------------

		if ( $this->orig_post['issue_id'] != $this->issue['issue_id'] )
		{
			$this->registry->output->showError( 'The post you were trying to edit does not match the issue ID you specified', '20T108' );
		}

		//-----------------------------------------
		// Generate post key (do we have one?)
		//-----------------------------------------

		if ( ! $this->orig_post['post_key'] )
		{
			//-----------------------------------------
			// Generate one and save back to post and attachment
			// to ensure 1.3 < compatibility
			//-----------------------------------------

			$this->post_key = md5(microtime());

			$this->DB->update( 'tracker_posts', array( 'post_key' => $this->post_key ), 'pid='.$this->orig_post['pid'] );

			$this->DB->update( 'attachments', array( 'attach_post_key' => $this->post_key ), "attach_rel_module='tracker' AND attach_rel_id=".$this->orig_post['pid'] );
		}
		else
		{
			$this->post_key = $this->orig_post['post_key'];
		}

		$this->checkForEdit( $this->issue, intval( $this->request['p'] ) );

		//-----------------------------------------
		// Do we have edit topic abilities?
		//-----------------------------------------

		if ( $this->orig_post['new_issue'] == 1 )
		{
			if ( $this->member->trackerProjectPerms['can_edit_titles'] == 1 )
			{
				$this->edit_title = 1;
			}
			else if ( $this->memberData['g_edit_topic'] == 1 )
			{
				$this->edit_title = 1;
			}
		}
	}

	/*-------------------------------------------------------------------------*/
	// MAIN PROCESS FUNCTION
	/*-------------------------------------------------------------------------*/

	public function processPost()
	{
		//-----------------------------------------
		// Did we remove an attachment?
		//-----------------------------------------

		if ( $this->request['removeattachid'] )
		{
			if ( $this->request[ 'removeattach_'. $this->request['removeattachid'] ] )
			{
				$this->removeAttachment( intval($this->request['removeattachid']), $this->post_key );
				$this->showForm();
			}
		}

		//-----------------------------------------
		// Parse the post, and check for any errors.
		// overwrites saved post intentionally
		//-----------------------------------------

		$this->post = $this->compilePost();

		//-----------------------------------------
		// Check for errors
		//-----------------------------------------

		if ( $this->getPostErrors() or $this->getIsPreview() )
		{
			//-----------------------------------------
			// Show the form again
			//-----------------------------------------

			$this->showForm();
		}
		else
		{
			$this->savePost();
		}
	}

	/*-------------------------------------------------------------------------*/
	// COMPLETE EDIT THINGY
	/*-------------------------------------------------------------------------*/

	public function savePost()
	{
		$time = $this->registry->getClass('class_localization')->getDate( time(), 'LONG' );

		//-----------------------------------------
		// Reset some data
		//-----------------------------------------

		$this->post['ip_address']  = $this->orig_post['ip_address'];
		$this->post['issue_id']    = $this->orig_post['issue_id'];
		$this->post['author_id']   = $this->orig_post['author_id'];
		$this->post['post_date']   = $this->orig_post['post_date'];
		$this->post['author_name'] = $this->orig_post['author_name'];
		$this->post['edit_time']   = time();
		$this->post['edit_name']   = $this->memberData['members_display_name'];
		
		//  Blank
		if ( strlen( trim( IPSText::removeControlCharacters( IPSText::br2nl( $this->post['post'] ) ) ) ) < 1 )
		{
			$this->_postErrors	= 'NO_CONTENT';
		}
		
		if ( IPSText::mbstrlen( $this->post['post'] ) > ( $this->settings['max_post_length'] * 1024 ) )
		{
			$this->_postErrors	= 'CONTENT_TOO_LONG';
		}
		
		if ( $this->_postErrors != "" )
		{
			//-----------------------------------------
			// Show the form again
			//-----------------------------------------
			
			return FALSE;
		}

		if ( $this->edit_title == 1 )
		{
			//-----------------------------------------
			// Update topic title
			//-----------------------------------------

			$this->request['TopicTitle'] = $this->_issueTitle;
			$this->request['TopicTitle'] = IPSText::getTextClass( 'bbcode' )->stripBadWords( $this->request['TopicTitle'] );

			if ( $this->request['TopicTitle'] != "" )
			{
				if ( $this->request['TopicTitle'] != $this->issue['title'] )
				{
					$this->issue['title'] 		= $this->request['TopicTitle'];
					$this->issue['title_seo']	= IPSText::makeSeoTitle( $this->issue['title'] );
					
					if ( $this->member->trackerProjectPerms['can_edit_titles'] AND $this->post['author_id'] != $this->memberData['member_id'] )
					{
						$this->tracker->moderators()->addLog( "Editied Issue Title '{$this->issue['title']}' to '{$this->request['TopicTitle']}'", $this->issue['title'], self::$project['project_id'], $this->issue['issue_id'], $this->orig_post['pid'] );
					}
				}
			}
			
			$this->DB->update( 'tracker_issues', $this->issue, 'issue_id=' . $this->issue['issue_id'] );
					
			/* We need to update project last info? */
			if ( self::$project['last_post']['issue_id'] == $this->issue['issue_id'] )
			{
				$this->tracker->projects()->update( self::$project['project_id'] );
			}
		}
		
		// First post
		if ( $this->orig_post['new_issue'] )
		{
			/* Tagging */
			if ( ! empty( $_POST['ipsTags'] ) )
			{
				$this->registry->trackerTags->replace( $_POST['ipsTags'], array( 'meta_id'		   => $this->issue['issue_id'],
																		  'meta_parent_id' => $this->issue['project_id'],
																		  'member_id'	   => $this->memberData['member_id'],
																		  'meta_visible'   => isset($this->issue['module_privacy']) ? $this->issue['module_privacy'] : 1 ) );
			}
		}

		//-----------------------------------------
		// Reason for edit?
		//-----------------------------------------

		if ( $this->memberData['g_access_cp'] )
		{
			$this->post['post_edit_reason'] = trim( $this->request['post_edit_reason'] );
		}

		//-----------------------------------------
		// Update the database (ib_forum_post)
		//-----------------------------------------

		$this->post['append_edit'] = 1;

		if ( $this->memberData['g_append_edit'] )
		{
			if ( $this->request['add_edit'] != 1 )
			{
				$this->post['append_edit'] = 0;
			}
		}

		$this->DB->setDataType( 'post_edit_reason', 'string' );

		$this->DB->update( 'tracker_posts', $this->post, 'pid='.$this->orig_post['pid'] );

		//-----------------------------------------
		// Make attachments "permanent"
		//-----------------------------------------

		$this->makeAttachmentsPermanent( $this->post_key, $this->orig_post['pid'], 'tracker', array( 'issue_id' => $this->issue['issue_id'] ) );

		//-----------------------------------------
		// Make sure paperclip symbol is OK
		//-----------------------------------------

		$this->recountIssueAttachments($this->issue['issue_id']);
		
		/* remove saved content */
		if ( $this->memberData['member_id'] )
		{
			$this->editor->removeAutoSavedContent( array( 'app' => 'tracker', 'member_id' => $this->memberData['member_id'] ) );
		}

		//-----------------------------------------
		// Not XML? Redirect them back to the topic
		//-----------------------------------------

		if ( $this->getIsAjax() )
		{
			return TRUE;
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['post_edited'], $this->settings['base_url'] . "app=tracker&showissue={$this->issue['issue_id']}&st={$this->request['st']}#entry{$this->orig_post['pid']}");
		}
	}

	/*-------------------------------------------------------------------------*/
	// SHOW FORM
	/*-------------------------------------------------------------------------*/

	public function showForm()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		if ( isset($_POST['Post']) )
		{
			$this->request['Post'] = $this->orig_post['post'];
			$postData              = $this->compilePost();
			$raw_post              = $postData['post'];
		}
		else
		{
			$raw_post              = $this->orig_post['post'];
		}
		
		// Set the post
		$this->setPostContentPreFormatted( $raw_post );

		/* FIELDS-NEW */
		$topic_title = '';

		//-----------------------------------------
		// Is this the first post in the topic?
		//-----------------------------------------

		if ( $this->edit_title == 1 )
		{
			$topic_title = isset( $_POST['TopicTitle'] ) ? $this->request['TopicTitle'] : $this->issue['title'];
		}

		//-----------------------------------------
		// Do we have any posting errors?
		//-----------------------------------------

		if ( $this->getPostErrors() )
		{
			$this->tracker->output .= $this->registry->getClass('output')->getTemplate('tracker_post')->errors( $this->getPostErrors() );
		}

		if ( $this->getIsPreview() )
		{
			$this->tracker->output .= $this->registry->output->getTemplate( 'tracker_post' )->preview( $this->showPostPreview( $this->post['post'], $this->post_key ) );
		}

		//-----------------------------------------
		// Appending a reason for the edit?
		//-----------------------------------------
		$editOptions = array( 'showOptions' => 0, 'checked' => '', 'showReason' => 0, 'reason' => $this->orig_post['post_edit_reason'] );
		
		if ( $this->memberData['g_append_edit'] )
		{
			$editOptions['showOptions'] = 1;

			if ( $this->orig_post['append_edit'] )
			{
				$editOptions['checked'] = " checked";
			}

			if ( $this->member->trackerProjectPerms['can_edit_posts'] || $this->memberData['g_access_cp'] )
			{
				$editOptions['showReason'] = 1;
			}
		}

		// Original post options
		$this->request['post_htmlstatus'] = $this->orig_post['post_htmlstate'];
		$this->request['enablesig']		  = $this->orig_post['use_sig'];
		$this->request['enableemo']		  = $this->orig_post['use_emo'];
		
		// 2.1 tagging
		$tagBox		 = '';
		$where       = array( 'meta_id'		   => $this->issue['issue_id'],
							  'meta_parent_id' => self::$project['project_id'],
							  'member_id'	   => $this->memberData['member_id'] );
				
		if ( $this->registry->trackerTags->can( 'edit', $where ) && ( $this->request['p'] == $this->issue['firstpost'] ) )
		{
			$tagBox = $this->registry->trackerTags->render('entryBox', $where);
		}
			
		/* Form Data */
		$formData	= array(
			'title'				=> $this->lang->words['top_txt_edit'] . ' ' . $this->issue['title'],
			'fields'			=> $fields,
			'checkBoxes'		=> $this->htmlCheckboxes('edit', $this->issue['issue_id'], self::$project['project_id']),
			'formType'			=> 'edit',
			'doCode'			=> 'postedit',
			'topicTitle'		=> $topic_title,
			'canEditTitle'		=> $this->edit_title,
			'captchaHTML'		=> $this->htmlNameField(),
			'editor'			=> $this->htmlPostBody( 'tracker-edit-' . intval($this->request['p']), 1 ),
			'uploadForm'		=> $this->can_upload ? $this->htmlBuildUploads( $this->post_key, 'edit', $this->orig_post['pid'] ) : '',
			'extraData'			=> $this->htmlPostOptions( $editOptions ),
			'issue_id'			=> $this->issue['issue_id'],
			'seoIssue'			=> $this->issue['title_seo'],
			'project_id'		=> self::$project['project_id'],
			'seoProject'		=> self::$project['title_seo'],
			'p'					=> intval( $this->request['p'] ),
			'buttonText'		=> $this->lang->words['submit_edit'],
			'attach_post_key'	=> $this->post_key,
			'tagBox'			=> $tagBox
		);
		
		$this->tracker->output .= $this->registry->getClass('output')->getTemplate('tracker_post')->postTemplate( $formData );

		//-----------------------------------------
		// Add in siggy buttons and such
		//-----------------------------------------

		$this->showPostNavigation();

		// Add in navigation specific to editing.
		$this->registry->output->addNavigation( $this->issue['title'], 'showissue=' . $this->issue['issue_id'], $this->issue['title_seo'], 'showissue', 'publicWithApp' );

		$this->tracker->pageTitle = $this->lang->words['editing_post'] . ' ' . $this->issue['title'];

		$this->tracker->sendOutput();
	}
	
	/**
	 * Displays the ajax edit box
	 *
	 * @access 	public
	 * @return	string		HTML
	 */
	public function displayAjaxEditForm()
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$extraData  = array();
		$errors     = '';
		
		$this->setIsAjax( TRUE );
		
		//-----------------------------------------
		// Form specific...
		//-----------------------------------------
		
		try
		{
			$issue = $this->editSetUp();
		}
		catch( Exception $error )
		{
			throw new Exception( $error->getMessage() );
		}
	
		//-----------------------------------------
		// Appending a reason for the edit?
		//-----------------------------------------
		$extraData['showAppendEdit'] = 0;
		
		if ( $this->memberData['g_append_edit'] )
		{
			$extraData['showEditOptions'] = 1;
			$extraData['showAppendEdit'] = 1;
			
			if ( $this->orig_post['append_edit'] )
			{
				$extraData['checked'] = 'checked';
			}
			else
			{
				$extraData['checked'] = '';
			}
		}
		
		if ( $this->checkForEdit( $this->issue, intval( $this->request['p'] ) ) )
		{
			$extraData['showEditOptions'] = 1;
			
			$extraData['showReason'] = 1;
		}
		
		/* Reset reason for edit */
		$extraData['reasonForEdit']	= $this->request['post_edit_reason'] ? $this->request['post_edit_reason'] : $this->orig_post['post_edit_reason'];
		$extraData['append_edit']	= $this->request['append_edit'] ? $this->request['append_edit'] : $this->orig_post['append_edit'];
		
		$extraData['checkBoxes'] = $this->htmlCheckboxes('edit', $this->issue['issue_id'], self::$project['project_id']);

		//-----------------------------------------
		// Parse the post, and check for any errors.
		//-----------------------------------------
		
		$post        = $this->compilePost();
		$postContent = $this->getPostContentPreFormatted() ? $this->getPostContentPreFormatted() : $this->getPostContent();
		$postContent = $this->_afterPostCompile( $postContent, 'edit' );

		//-----------------------------------------
		// Do we have any posting errors?
		//-----------------------------------------
		
		if ( $this->_postErrors )
		{
			$errors = isset($this->lang->words[ $this->_postErrors ]) ? $this->lang->words[ $this->_postErrors ] : $this->_postErrors;
		}
		
		//-----------------------------------------
		// Do we need to tell browser to load the JS? 
		//-----------------------------------------
		
		$extraData['_loadJs']	= false;
		$extraData['smilies']	= null;
		
		if ( ( $this->tracker->projects()->checkPermission( 'reply', self::$project['project_id'] ) != TRUE )
		   or ( $this->issue['state'] == 'closed' AND !$this->memberData['g_post_closed'] ) )
		{
			$extraData['_loadJs']	= true;
			$extraData['smilies']	= $this->editor->fetchEmoticons();
		}

		$html = $this->registry->getClass('output')->getTemplate('editors')->ajaxEditBox( $postContent, intval( $this->request['p'] ), $errors, $extraData );
		
		return $html;
	}

	/**
	 * Performs set up for editing a post
	 *
	 * @return	array    Topic data
	 *
	 * Exception Error Codes
	 * NO_SUCH_TOPIC		No topic could be found matching the topic ID and forum ID
	 * NO_SUCH_POST		Post could not be loaded
	 * NO_EDIT_PERM		Viewer does not have permission to edit
	 * TOPIC_LOCKED		The topic is locked
	 * NO_REPLY_POLL		This is a poll only topic
	 * NO_TOPIC_ID		No topic ID (durrrrrrrrrrr)
	 */
	public function editSetUp()
	{
		//-----------------------------------------
		// Check for a topic ID
		//-----------------------------------------
		
		if ( ! $this->getIssueID() )
		{
			throw new Exception( 'NO_ISSUE_ID' );
		}
		
		//-----------------------------------------
		// Load and set topic
		//-----------------------------------------
		
		$project_id = intval( $this->getProjectID() );

		if ( ! $this->issue['issue_id'] )
		{
			throw new Exception("NO_SUCH_ISSUE");
		}
		
		if ( $project_id != $this->issue['project_id'] )
		{
			throw new Exception("NO_SUCH_ISSUE");
		}

		//-----------------------------------------
		// Load the old post
		//-----------------------------------------
		
		$this->_originalPost = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'tracker_posts', 'where' => "pid=" . $this->getPostID() ) );

		if ( ! $this->_originalPost['pid'] )
		{
			throw new Exception( "NO_SUCH_POST" );
		}
		
		if ( $this->getIsAjax() === TRUE )
		{
			$this->setSettings( array( 'enableSignature'	=> 1,
									   'enableEmoticons'	=> 1,
									   'post_htmlstatus'	=> $this->getSettings('post_htmlstatus') !== '' ? $this->getSettings('post_htmlstatus') : intval($this->_originalPost['post_htmlstate']),
							) 		);
		}

		//-----------------------------------------
		// Same topic?
		//-----------------------------------------
		
		if ( $this->_originalPost['issue_id'] != $this->issue['issue_id'] )
		{
            ipsRegistry::getClass('output')->showError( 'posting_mismatch_topic', 20311 );
        }
		
		//-----------------------------------------
		// Generate post key (do we have one?)
		//-----------------------------------------
		
		if ( ! $this->_originalPost['post_key'] )
		{
			//-----------------------------------------
			// Generate one and save back to post and attachment
			// to ensure 1.3 < compatibility
			//-----------------------------------------
			
			$this->post_key = md5(microtime());
			
			$this->DB->update( 'tracker_posts', array( 'post_key' => $this->post_key ), 'pid='.$this->_originalPost['pid'] );
			$this->DB->update( 'attachments'  , array( 'attach_post_key' => $this->post_key ), "attach_rel_module='tracker' AND attach_rel_id=".$this->_originalPost['pid'] );
		}
		else
		{
			$this->post_key = $this->_originalPost['post_key'];
		}
		
		//-----------------------------------------
		// Lets do some tests to make sure that we are
		// allowed to edit this topic
		//-----------------------------------------
		
		$_canEdit = $this->checkForEdit( $this->issue, $this->_originalPost['pid'] );

		//-----------------------------------------
		// Is the issue locked?
		//-----------------------------------------

		if ( ( $this->issue['state'] != 'open' ) and ( ! $this->memberData['g_is_supmod'] AND ! $this->moderator['edit_post'] ) )
		{
			if ( $this->memberData['g_post_closed'] != 1 )
			{
				$_canEdit = 0;
			}
		}
		
		if ( $_canEdit != 1 )
		{
			if ( $this->_bypassPermChecks !== TRUE )
			{
				throw new Exception( "NO_EDIT_PERMS" );
			}
		}
		
		//-----------------------------------------
		// If we're not a mod or admin
		//-----------------------------------------

		if ( ! $this->getAuthor('g_is_supmod') AND ! $this->moderator['edit_post'] )
		{
			if ( $this->tracker->projects()->checkPermission( 'reply', self::$project['project_id'] ) !== TRUE )
			{
				$_ok = 0;
			
				//-----------------------------------------
				// Are we a member who started this topic
				// and are editing the topic's first post?
				//-----------------------------------------
			
				if ( $this->getAuthor('member_id') )
				{
					if ( $this->issue['firstpost'] )
					{
						$_post = $this->DB->buildAndFetch( array( 'select' => 'pid, author_id, issue_id',
																  'from'   => 'tracker_posts',
																  'where'  => 'pid=' . intval( $this->issue['firstpost'] ) ) );
																			
						if ( $_post['pid'] AND $_post['issue_id'] == $this->issue['issue_id'] AND $_post['author_id'] == $this->getAuthor('member_id') )
						{
							$_ok = 1;
						}
					}
				}
			
				if ( ! $_ok )
				{
					if ( $this->_bypassPermChecks !== TRUE )
					{
						throw new Exception( "NO_EDIT_PERMS" );
					}
				}
			}
		}
		
		return $this->issue;
	}
}

?>