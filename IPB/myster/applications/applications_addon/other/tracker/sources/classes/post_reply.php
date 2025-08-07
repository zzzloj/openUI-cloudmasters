<?php

/**
* Tracker 2.1.0
* 
* New reply/post class
* Last Updated: $Date: 2012-12-06 00:21:06 +0000 (Thu, 06 Dec 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Core
* @link			http://ipbtracker.com
* @version		$Revision: 1394 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class tracker_core_post_reply extends tracker_core_post_main
{
	protected $quote_pids  = array();
	protected $quote_posts = array();

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
			$this->registry->output->showError( 'We could not find the project you were trying to reply to an issue in', '20T102' );
		}

		/* Build Permissions */
		$this->buildPermissions();

		//-----------------------------------------
		// Set up post key
		//-----------------------------------------

		$this->post_key = ( isset($this->request['attach_post_key']) AND $this->request['attach_post_key'] != "" ) ? $this->request['attach_post_key'] : md5( microtime() );

		//-----------------------------------------
		// Lets load the topic from the database
		// before we do anything else.
		//-----------------------------------------

		$this->DB->build(
			array(
				'select' => '*', 
				'from'   => 'tracker_issues', 
				'where'  => "project_id=".intval(self::$project['project_id'])." AND issue_id=".intval($this->request['iid']),
			)
		);
		$this->DB->execute();

		$this->issue = $this->DB->fetch();

		//-----------------------------------------
		// Check permissions, etc
		//-----------------------------------------

		if ( ! $this->issue['issue_id'] )
		{
			$this->registry->output->showError( 'We could not find the issue you were trying to reply to', '20T103' );
		}

		$this->tracker->fields()->setIssue( $this->issue );

		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		$this->checkForReply( $this->issue );
	}

	/*-------------------------------------------------------------------------*/
	// MAIN PROCESS FUNCTION
	/*-------------------------------------------------------------------------*/

	public function processPost()
	{
		$this->post = $this->compilePost();

		if ( $this->getPostError() )
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

			if( $this->member->getProperty('member_id') == 0 AND $this->settings['guest_captcha'] )
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

			//-----------------------------------------
			// Redirect them back to the topic
			//-----------------------------------------

			if ( $this->request['return_to_listing'] == 'yes' )
			{
				/* Mark as read */
				$this->registry->classItemMarking->markRead( array( 'forumID' => $this->issue['project_id'], 'itemID' => $this->issue['issue_id'] ), 'tracker' );

				/* Redirect back */
				$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=tracker&showproject=' . $this->issue['project_id'] . $this->request['bounce'] );
			}
			
			$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=tracker&showissue={$this->issue['issue_id']}&view=getlastpost" );
		}
	}

	/*-------------------------------------------------------------------------*/
	// ADD THE REPLY
	/*-------------------------------------------------------------------------*/

	public function savePost()
	{
		if ( ! $this->request['fast_reply_used'] )
		{
			// Field permissions
			$this->tracker->fields()->checkForInputErrors( 'reply' );
		}
		
		//-----------------------------------------
		// Insert the post into the database to get the
		// last inserted value of the auto_increment field
		//-----------------------------------------

		$this->post['issue_id'] = $this->issue['issue_id'];

		//-----------------------------------------
		// Get the last post time of this topic not counting
		// this new reply
		//-----------------------------------------

		$this->last_post = $this->issue['issue_last_post'];

		//-----------------------------------------
		// Add post to DB
		//-----------------------------------------

		$this->post['post_key']    = $this->post_key;
		$this->post['post_parent'] = isset($this->request['parent_id']) ? intval($this->request['parent_id']) : 0;

		/* FIELDS-NEW */
		if ( ! $this->request['fast_reply_used'] )
		{
			$this->tracker->fields()->compileFieldChanges( $this->issue );
			$this->issue = $this->tracker->fields()->setFieldUpdates( $this->issue );
		}
		
		// @deprecated 2.1
		$this->post['post'] = $this->tracker->fields()->addReplyText( $this->issue, $this->post['post'] );
		
		// Blank?
		$weChangedFields = false;
			
		if ( strlen( trim( IPSText::removeControlCharacters( IPSText::br2nl( $this->post['post'] ) ) ) ) < 1 && ! $this->getIsPreview() )
		{	
			if ( $this->tracker->fields()->getChanged() && ! $this->request['fast_reply_used'] )
			{
				$weChangedFields = true;
			}
			else
			{
				$this->setPostError('NO_CONTENT');
				$this->showForm();
				$this->tracker->sendOutput();
				exit;
			}
		}
		
		/* Check length of post */
		if ( IPSText::mbstrlen( $this->post['post'] ) > ( $this->settings['max_post_length'] * 1024 ) && ! $this->getIsPreview() )
		{
			if ( $this->tracker->fields()->getChanged() && ! $this->request['fast_reply_used'] )
			{
				$weChangedFields = true;
			}
			else
			{
				$this->setPostError('post_too_long');
				$this->showForm();
				$this->tracker->sendOutput();
				exit;
			}
		}
		
		if ( $this->getIsPreview() )
		{
			$this->showForm();
			$this->tracker->sendOutput();
			exit;
		}

		//-----------------------------------------
		// Typecast
		//-----------------------------------------

		$force_data_type = array(
			'pid'  => 'int',
			'post' => 'string'
		);
		
		// IPB 3.2 force data type
		foreach( $force_data_type as $k => $v )
		{
			$this->DB->setDataType( $k, $v );
		}

		// Bug #21523 fix - can't update identity column in MSSQL
		$issueId = $this->post['issue_id'];

		if ( ! $weChangedFields )
		{
			$this->DB->insert( 'tracker_posts', $this->post );
		
			$this->post['pid'] = $this->DB->getInsertId();

			//-----------------------------------------
			// Get the correct number of replies
			//-----------------------------------------

			$this->DB->build( array( 'select' => 'COUNT(*) as posts', 'from' => 'tracker_posts', 'where' => "issue_id={$this->issue['issue_id']}" ) );
			$this->DB->execute();

			$posts = $this->DB->fetch();

			$pcount = intval( $posts['posts'] - 1 );
		}
		
		//-----------------------------------------
		// UPDATE TOPIC
		//-----------------------------------------

		$poster_name = $this->memberData['member_id'] ? $this->memberData['members_display_name'] : $this->request['UserName'];

		if ( ! $weChangedFields )
		{
			$this->issue['posts']					= $pcount;
		}
		
		$this->issue['last_poster_id']			= $this->memberData['member_id'];
		$this->issue['last_poster_name']		= $poster_name;
		$this->issue['last_post']				= IPS_UNIX_TIME_NOW;
		$this->issue['last_poster_name_seo']	= IPSText::makeSeoTitle( $this->issue['last_poster_name'] );

		$force_data_type = array(
			'title'				=> 'string',
			'starter_name'		=> 'string',
			'last_poster_name'	=> 'string',
			'icon_id'			=> 'int'
		);
		
		// IPB 3.2 force data type
		foreach( $force_data_type as $k => $v )
		{
			$this->DB->setDataType( $k, $v );
		}
		
		/* FIELDS-NEW */
		if ( ! $this->request['fast_reply_used'] )
		{
			$this->issue = $this->tracker->fields()->parseToSave( $this->issue );
			$this->tracker->fields()->commitFieldChangeRecords();
		}
		
		$this->DB->update( 'tracker_issues', $this->issue, "issue_id={$this->issue['issue_id']}"  );

		self::$project['last_post']				= IPS_UNIX_TIME_NOW;
		self::$project['last_issue_id']			= $this->issue['issue_id'];
		self::$project['last_issue_title']		= $this->issue['title'];
		self::$project['last_poster_id']		= $this->memberData['member_id'];
		self::$project['last_poster_name']		= $poster_name;
		self::$project['last_poster_name_seo']	= $this->issue['last_poster_name_seo'];

		$this->tracker->projects()->update( self::$project['project_id'] );

		//-----------------------------------------
		// Update the cache
		//-----------------------------------------
		$this->sendNotifications();
		$this->tracker->cache('stats')->updatePosts(1);

		//-----------------------------------------
		// Make attachments "permanent"
		//-----------------------------------------

		$this->makeAttachmentsPermanent( $this->post_key, $this->post['pid'], 'tracker', array( 'issue_id' => $this->issue['issue_id'] ) );
		
		//-----------------------------------------
		// Are we tracking new issue we start 'auto_track'?
		//-----------------------------------------
		
		$this->addIssueToTracker($this->issue['issue_id']);
		
		/* remove saved content */
		if ( $this->memberData['member_id'] )
		{
			$this->editor->removeAutoSavedContent( array( 'app' => 'tracker', 'member_id' => $this->memberData['member_id'] ) );
		}
	}
	
	public function sendNotifications()
	{
		/* Work around so we know what issue it is in like plugin */
		$this->cache->updateCacheWithoutSaving( 'trackerCurrentIssue', $this->issue );

		$_url	= $this->registry->output->buildSEOUrl( 'app=tracker&amp;showissue=' . $this->issue['issue_id'] . '&amp;view=findpost&amp;p=' . $this->post['pid'], 'public', $this->issue['title_seo'], 'showisuse' );
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );
		$this->_like = classes_like::bootstrap( 'tracker', 'issues' );
		
		$this->_like->sendNotifications( $this->issue['issue_id'], array( 'immediate', 'offline' ), 
			array(
				'notification_key'		=> 'issue_reply',
				'notification_url'		=> $_url,
				'email_template'		=> 'issue_reply',
				'email_subject'			=> sprintf( $this->lang->words['subject__issue_reply'], $this->settings['base_url'] . 'showuser=' . $this->memberData['member_id'], $this->memberData['members_display_name'], $_url ),
				'build_message_array'	=> 
					array(
						'NAME'  		=> '-member:members_display_name-',
						'OWNER'			=> $this->memberData['members_display_name'],
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
		$this->setPostContentPreFormatted( $this->checkMultiQuote() );

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
		$fields = $this->tracker->fields()->getPostScreen('reply');
		
		/* Form Data */
		$formData	= array(
			'title'				=> $this->lang->words['bt_post_top_reply'] . ' ' . $this->issue['title'],
			'fields'			=> $fields,
			'checkBoxes'		=> $this->htmlCheckboxes('reply', $this->issue['issue_id'], self::$project['project_id']),
			'formType'			=> 'reply',
			'doCode'			=> 'postreply',
			'captchaHTML'		=> $this->htmlNameField(),
			'editor'			=> $this->htmlPostBody( 'tracker-reply-' . $this->issue['issue_id'] ),
			'uploadForm'		=> $this->can_upload ? $this->htmlBuildUploads( $this->post_key, 'reply' ) : '',
			'extraData'			=> $this->htmlPostOptions(),
			'issue_id'			=> $this->issue['issue_id'],
			'seoIssue'			=> $this->issue['title_seo'],
			'project_id'		=> self::$project['project_id'],
			'seoProject'		=> self::$project['title_seo'],
			'buttonText'		=> $this->lang->words['bt_post_submit_reply'],
			'attach_post_key'	=> $this->post_key
		);
		
		$this->tracker->output .= $this->registry->getClass('output')->getTemplate('tracker_post')->postTemplate( $formData );

		//-----------------------------------------
		// Still here?
		//-----------------------------------------

		$this->showPostNavigation();

		$this->tracker->pageTitle = $this->lang->words['replying_in'].' '.$this->issue['title'];

		//-----------------------------------------
		// Reset multi-quote cookie
		//-----------------------------------------

		IPSCookie::set('mqtids', ',', 0);

		$this->tracker->sendOutput();
	}
}

?>