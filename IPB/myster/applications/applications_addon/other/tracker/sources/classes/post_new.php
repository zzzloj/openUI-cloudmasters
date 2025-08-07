<?php

/**
* Tracker 2.1.0
*
* New issue/post class
* Last Updated: $Date: 2013-02-16 15:36:07 +0000 (Sat, 16 Feb 2013) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Core
* @link			http://ipbtracker.com
* @version		$Revision: 1404 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class tracker_core_post_new extends tracker_core_post_main
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
			parent::$project = $this->tracker->projects()->getProject( $this->request['pid'] );
		}
		else
		{
			$this->registry->output->showError( 'We could not find the project you were trying to create a new issue in', '20T102' );
		}

		/* Build Permissions */
		$this->buildPermissions();

		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		$this->post_key = ( isset($this->request['attach_post_key']) AND $this->request['attach_post_key'] != "" ) ? $this->request['attach_post_key'] : md5( microtime() );

		$this->checkForNewIssue();
	}

	/*-------------------------------------------------------------------------*/
	// MAIN PROCESS FUNCTION
	/*-------------------------------------------------------------------------*/

	public function processPost()
	{
		//-----------------------------------------
		// Parse the post, and check for any errors.
		//-----------------------------------------

		$this->post = $this->compilePost();

		//-----------------------------------------
		// check to make sure we have a valid topic title
		//-----------------------------------------

		$this->request['TopicTitle'] = $this->_issueTitle;

		if ( $this->getPostError() || $this->getIsPreview() )
		{
			//-----------------------------------------
			// Show the form again
			//-----------------------------------------

			$this->showForm();
		}
		else
		{
			//-----------------------------------------
			// Guest w/ CAPTCHA?
			//-----------------------------------------

			if( $this->memberData['member_id'] == 0 AND $this->settings['guest_captcha'] )
			{
				$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classCaptcha.php', 'classCaptcha' );
				$captchaClass = new $classToLoad( $this->registry, $this->settings['bot_antispam_type'] );

				if( $captchaClass->validate() !== TRUE )
				{
					$this->setPostError('err_reg_code');
					$this->showForm();
					return;
				}
			}

			$this->savePost();
		}
	}

	/*-------------------------------------------------------------------------*/
	// ADD TOPIC FUNCTION
	/*-------------------------------------------------------------------------*/

	public function savePost()
	{
		// Field permissions
		$this->tracker->fields()->checkForInputErrors( 'new' );

		//-----------------------------------------
		// Fix up the topic title
		//-----------------------------------------

		$this->request['TopicTitle'] = $this->_issueTitle;

		/* Check length of title */
		if ( strlen( trim( IPSText::removeControlCharacters( IPSText::br2nl( $this->request['TopicTitle'] ) ) ) ) < 1 )
		{
			$this->setPostError('Please enter an issue title');
			$this->showForm();
			$this->tracker->sendOutput();
			exit;
		}

		/* Check length of post < 1 */
		if ( strlen( trim( IPSText::removeControlCharacters( IPSText::br2nl( $this->post['post'] ) ) ) ) < 1 )
		{
			$this->setPostError('NO_CONTENT');
			$this->showForm();
			$this->tracker->sendOutput();
			exit;
		}

		/* Check length of post */
		if ( IPSText::mbstrlen( $this->post['post'] ) > ( $this->settings['max_post_length'] * 1024 ) )
		{
			$this->setPostError('post_too_long');
			$this->showForm();
			$this->tracker->sendOutput();
			exit;
		}

		$state  = 'open';

		//-----------------------------------------
		// Build the master array
		//-----------------------------------------

		$this->issue = array(
			'title'				=> $this->request['TopicTitle'],
			'state'				=> $state,
			'posts'				=> 0,
			'starter_id'		=> $this->memberData['member_id'],
			'starter_name'		=> $this->memberData['member_id'] ? $this->memberData['members_display_name'] : $this->request['UserName'],
			'start_date'		=> IPS_UNIX_TIME_NOW,
			'last_poster_id'	=> $this->memberData['member_id'],
			'last_poster_name'	=> $this->memberData['member_id'] ?  $this->memberData['members_display_name'] : $this->request['UserName'],
			'last_post'			=> IPS_UNIX_TIME_NOW,
			'author_mode'		=> $this->memberData['member_id'] ? 1 : 0,
			'project_id'		=> self::$project['project_id'],
			'type'				=> ( $this->request['type'] == 'issue' || $this->request['type'] == 'suggestion' ) ? $this->request['type'] : 'issue'
		);

		// SEO
		$this->issue['starter_name_seo']		= IPSText::makeSeoTitle( $this->issue['starter_name'] );
		$this->issue['last_poster_name_seo']	= IPSText::makeSeoTitle( $this->issue['last_poster_name'] );
		$this->issue['title_seo']				= IPSText::makeSeoTitle( $this->issue['title'] );

		//-----------------------------------------
		// Insert the topic into the database to get the
		// last inserted value of the auto_increment field
		// follow suit with the post
		//-----------------------------------------

		$force_data_type = array(
			'title'            => 'string',
			'description'      => 'string',
			'starter_name'     => 'string',
			'last_poster_name' => 'string'
		);

		// IPB 3.2 force data type
		foreach( $force_data_type as $k => $v )
		{
			$this->DB->setDataType( $k, $v );
		}

		/* FIELDS-NEW */
		$this->tracker->fields()->compileFieldChanges( $this->issue );
		$this->issue = $this->tracker->fields()->parseToSave( $this->issue );

		//-----------------------------------------
		// Check if we're ok with tags
		//-----------------------------------------

		$where	= array(
			'meta_parent_id'	=> self::$project['project_id'],
			'member_id'			=> $this->memberData['member_id'],
			'existing_tags'		=> explode( ',', IPSText::cleanPermString( $this->request['ipsTags'] ) )
		);

		if ( $this->registry->trackerTags->can( 'add', $where ) AND $this->settings['tags_enabled'] AND ( !empty( $_POST['ipsTags'] ) OR $this->settings['tags_min'] ) )
		{
			$this->registry->trackerTags->checkAdd( $_POST['ipsTags'], array(
																  'meta_parent_id' => $this->issue['project_id'],
																  'member_id'	   => $this->memberData['member_id'],
																  'meta_visible'   => isset($this->issue['module_privacy']) ? $this->issue['module_privacy'] : 1 ) );

			if ( $this->registry->trackerTags->getErrorMsg() )
			{
				$this->setPostError( $this->registry->trackerTags->getFormattedError() );
				$this->showForm();
				$this->tracker->sendOutput();
				exit;
			}

			$_storeTags	= true;
		}

		//-----------------------------------------
		// Update the post info with the upload array info
		//-----------------------------------------

		$this->post['post_key']  = $this->post_key;
		$this->post['new_issue'] = 1;

		$returnData = $this->api->insertIssue( self::$project['project_id'], $this->issue, $this->post );
		$this->issue['issue_id'] 	= $returnData['issue_id'];
		$this->post['pid']			= $returnData['post_id'];

		//-----------------------------------------
		// Make attachments "permanent"
		//-----------------------------------------

		$this->makeAttachmentsPermanent( $this->post_key, $this->post['pid'], 'tracker', array( 'issue_id' => $this->issue['issue_id'] ) );

		$this->sendNotifications();

		/* remove saved content */
		if ( $this->memberData['member_id'] )
		{
			$this->editor->removeAutoSavedContent( array( 'app' => 'tracker', 'member_id' => $this->memberData['member_id'] ) );
		}

		//-----------------------------------------
		// Tagging
		//-----------------------------------------

		if ( $_storeTags )
		{
			$this->registry->trackerTags->add( $_POST['ipsTags'], array( 'meta_id'		   => $this->issue['issue_id'],
																  'meta_parent_id' => $this->issue['project_id'],
																  'member_id'	   => $this->memberData['member_id'],
																  'meta_visible'   => isset($this->issue['module_privacy']) ? $this->issue['module_privacy'] : 1 ) );
		}

		//-----------------------------------------
		// Are we tracking new issue we start 'auto_track'?
		//-----------------------------------------

		$this->addIssueToTracker($this->issue['issue_id']);

		//-----------------------------------------
		// Redirect them back to the topic
		//-----------------------------------------

		$this->registry->output->silentRedirect($this->settings['base_url'] . "app=tracker&amp;showissue={$this->issue['issue_id']}");
	}

	public function sendNotifications()
	{
		/* Work around so we know what issue it is in like plugin */
		$this->cache->updateCacheWithoutSaving( 'trackerCurrentIssue', $this->issue );

		$_url	= $this->registry->output->buildSEOUrl( 'app=tracker&amp;showissue=' . $this->issue['issue_id'], 'public', $this->issue['title_seo'], 'showissue' );

		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );
		$this->_like = classes_like::bootstrap( 'tracker', 'projects' );

		$this->_like->sendNotifications( self::$project['project_id'], array( 'immediate', 'offline' ),
			array(
				'notification_key'		=> 'new_issues',
				'notification_url'		=> $_url,
				'email_template'		=> 'new_issues',
				'email_subject'			=> sprintf( $this->lang->words['subject__new_issues'], $this->settings['base_url'] . 'showuser=' . $this->memberData['member_id'], $this->memberData['members_display_name'], $_url ),
				'build_message_array'	=>
					array(
						'NAME'  		=> '-member:members_display_name-',
						'OWNER'			=> $this->memberData['members_display_name'],
						'PROJECT'		=> self::$project['title'],
						'ISSUE' 		=> $this->issue['title'],
						'ISSUE_CONTENT'	=> $this->post['post'],
						'URL'			=> $_url,
					)
			)
		);
	}

	/*-------------------------------------------------------------------------*/
	// SHOW FORM
	/*-------------------------------------------------------------------------*/

	public function showForm()
	{
		//-----------------------------------------
		// Are we quoting posts?
		//-----------------------------------------

		$raw_post = $this->checkMultiQuote();

		//-----------------------------------------
		// Do we have any posting errors?
		//-----------------------------------------

		if ( $this->getPostError() )
		{
			$this->tracker->output .= $this->registry->getClass('output')->getTemplate('tracker_post')->errors( $this->getPostError() );
		}

		if ( $this->getIsPreview() )
		{
			$this->tracker->output .= $this->registry->getClass('output')->getTemplate('tracker_post')->preview( $this->showPostPreview( $this->post['post'], $this->post_key ) );
		}

		/* FIELDS-NEW */
		$fields = $this->tracker->fields()->getPostScreen( 'new' );

		// 2.1 tagging
		$tagBox      = '';

		$where       = array( 'meta_parent_id'	=> self::$project['project_id'],
							  'member_id'		=> $this->memberData['member_id'],
							  'existing_tags'	=> explode( ',', IPSText::cleanPermString( $this->request['ipsTags'] ) ) );

		if ( $this->registry->trackerTags->can( 'add', $where ) )
		{
			$tagBox = $this->registry->trackerTags->render('entryBox', $where);
		}

		/* Form Data */
		$formData	= array(
			'title'				=> $this->lang->words['bt_post_top_new'] . ': ' . parent::$project['title'],
			'fields'			=> $fields,
			'checkBoxes'		=> $this->htmlCheckboxes('new', 0, self::$project['project_id']),
			'formType'			=> 'new',
			'doCode'			=> 'postnew',
			'topicTitle'		=> $this->_issueTitle,
			'captchaHTML'		=> $this->htmlNameField(),
			'editor'			=> $this->htmlPostBody( 'tracker-new-' . self::$project['project_id'] ),
			'uploadForm'		=> $this->can_upload ? $this->htmlBuildUploads( $this->post_key, 'new' ) : '',
			'extraData'			=> $this->htmlPostOptions(),
			'project_id'		=> self::$project['project_id'],
			'seoProject'		=> self::$project['title_seo'],
			'buttonText'		=> $this->lang->words['bt_post_submit_new'],
			'attach_post_key'	=> $this->post_key,
			'suggestions'		=> self::$project['enable_suggestions'],
			'tagBox'			=> $tagBox
		);

		$this->tracker->output .= $this->registry->getClass('output')->getTemplate('tracker_post')->postTemplate( $formData );

		//-----------------------------------------
		// Page title, and send.
		//-----------------------------------------
		$this->tracker->pageTitle = $this->lang->words['bt_post_submit_new'];

		$this->showPostNavigation();

		$this->tracker->sendOutput();
	}
}

?>