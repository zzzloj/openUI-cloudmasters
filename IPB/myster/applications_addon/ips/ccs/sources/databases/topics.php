<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Content topic posting library
 * Last Updated: $Date: 2011-12-30 17:51:21 -0500 (Fri, 30 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		18th February 2010
 * @version		$Revision: 10079 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class topicsLibrary
{
	/**
	 * Enable debug mode - useful when topics or comments are not posting to the forums correctly
	 * 
	 * @var	bool
	 */
 	protected $debugMode	= false;
 	
	/**
	 * Moderator library
	 *
	 * @access	protected
	 * @var 	object
	 */
	protected $moderatorLibrary;

	/**
	 * Posting library
	 *
	 * @access	protected
	 * @var 	object
	 */
	protected $post;
	
	/**
	 * Forum data
	 *
	 * @access	protected
	 * @var 	array
	 */
	protected $forum		= array();

	/**
	 * Current topic data
	 *
	 * @access	protected
	 * @var 	array
	 */
	protected $topic		= array();

	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/

	/**
	 * Current type
	 *
	 * @access	protected
	 * @var		string
	 */	
	protected $type;

	/**
	 * Custom fields class
	 *
	 * @access	public
	 * @var		obj
	 */
	public $fieldsClass;
	
	/**
	 * Categories class
	 *
	 * @access	public
	 * @var		obj
	 */
	public $categories;

	/**
	 * Array of information about the fields in this database
	 *
	 * @access	public
	 * @var		array
	 */
	public $fields			= array();
		
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @param	object		Custom fields object
	 * @param	object		Categories object
	 * @param	array 		Custom fields
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $fieldsClass=null, $categories=null, $fields=array() )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		//-----------------------------------------
		// ccsFunctions class
		//-----------------------------------------
		
		if( !$this->registry->isClassLoaded('ccsFunctions') )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/functions.php', 'ccsFunctions', 'ccs' );
			$this->registry->setClass( 'ccsFunctions', new $classToLoad( $this->registry ) );
		}
		
		//-----------------------------------------
		// Custom fields class
		//-----------------------------------------
		
		if( is_object($fieldsClass) )
		{
			$this->fieldsClass	= $fieldsClass;
		}
		else
		{
			$this->fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();
		}
		
		//-----------------------------------------
		// Category class
		//-----------------------------------------
		
		if( is_object($categories) )
		{
			$this->categories	= $categories;
		}
		
		//-----------------------------------------
		// Custom fields
		//-----------------------------------------
		
		$this->fields	= array();
		
		if( is_array($fields) AND count($fields) )
		{
			$this->fields	= $fields;
		}

		//-----------------------------------------
		// Make sure we have language file
		//-----------------------------------------
		
		$this->lang->loadLanguageFile( array( 'public_lang' ), 'ccs' );

		//-----------------------------------------
		// Disable merging concurrent posts
		// @link http://community.invisionpower.com/resources/bugs.html/_/ip-content/double-post-option-will-insert-comment-into-article-r40263
		//-----------------------------------------

		$this->settings['post_merge_conc']	= 0;

		//-----------------------------------------
		// Trick topic library into not modifying polls
		// @link http://community.invisionpower.com/resources/bugs.html/_/ip-content/editing-articles-wipes-promoted-topic-poll-r40954
		//-----------------------------------------

		$this->settings['max_poll_questions']	= 0;
	}

	/**
	 * Quickly check if this library should be posting a topic
	 *
	 * @param	array 	Database information
	 * @param	array 	Category information
	 * @return	@e bool
	 */
	public function checkForTopicSupport( $database, $category )
	{
		$checkAgainst	= $category['category_forum_override'] ? $category['category_forum_record'] : $database['database_forum_record'];
		$forumId		= $category['category_forum_override'] ? $category['category_forum_forum'] : $database['database_forum_forum'];

		if( $checkAgainst AND $forumId )
		{
			return true;
		}

		return false;
	}
	
	/**
	 * Increment comment count for articles for replies posted in the topic
	 *
	 * @access	public
	 * @param	array 		New post insert data
	 * @return	@e void
	 */
	public function checkAndIncrementComments( $insert )
	{
		/* Do not update if comment was posted from IP.Content as it updates already */
		if( IPS_APP_COMPONENT == 'ccs' )
		{
			return;
		}

		if( $insert['topic_id'] )
		{
			$_databases	= $this->cache->getCache('ccs_databases');
			
			foreach( $_databases as $_database )
			{
				//-----------------------------------------
				// Got a record using this tid in this db?
				//-----------------------------------------
				
				$_record	= $this->DB->buildAndFetch( array( 'select' => 'primary_id_field', 'from' => $_database['database_database'], 'where' => 'record_topicid=' . $insert['topic_id'] ) );
				
				if( $_record['primary_id_field'] )
				{
					$this->DB->update( $_database['database_database'], "record_comments=record_comments+1", "primary_id_field=" . $_record['primary_id_field'], true, true );
					
					if( $_record['category_id'] )
					{
						$this->DB->update( 'ccs_database_categories', "category_record_comments=category_record_comments+1", "category_id=" . $_record['category_id'], true, true );
					}

					break;
				}
			}
		}
	}

	/**
	 * Remove a posted comment
	 *
	 * @access	public
	 * @param	array 		Record/article information
	 * @param	array 		Category information
	 * @param	array 		Database information
	 * @param	int			Post ID
	 * @return	boolean		Removed successfully
	 */
	public function removeComment( $record, $category, $database, $pid )
	{
		//-----------------------------------------
		// Have a topic?
		//-----------------------------------------
		
		if( !$record['record_topicid'] OR !$pid )
		{
			return false;
		}

		//-----------------------------------------
		// Get forum libs
		//-----------------------------------------
		
		$this->_getForumLibraries();
		
		$topic		= $this->DB->buildAndFetch( array( 'select' => 'title, topic_firstpost', 'from' => 'topics', 'where' => 'tid=' . $record['record_topicid'] ) );
		$forumId	= $category['category_forum_override'] ? $category['category_forum_forum'] : $database['database_forum_forum'];

		$this->moderatorLibrary->init( $this->registry->getClass('class_forums')->allForums[ $forumId ] );

		$this->moderatorLibrary->postDeleteFromDb( $pid );
		$this->moderatorLibrary->addModerateLog( $forumId, $record['record_topicid'], '', $topic['title'], $this->lang->words['log_comment_del'] );

		//-----------------------------------------
		// Recount "comments"
		//-----------------------------------------
		
		$count	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'posts', 'where' => $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ), '' ) . " AND pid <> {$topic['topic_firstpost']} AND topic_id={$record['record_topicid']}" ) );
		
		$this->DB->update( $database['database_database'], array( 'record_comments' => intval($count['total']) ), 'primary_id_field=' . $record['primary_id_field'] );
		
		return true;
	}
	
	/**
	 * Toggle comment approved/unapproved
	 *
	 * @access	public
	 * @param	array 		Record/article information
	 * @param	array 		Category information
	 * @param	array 		Database information
	 * @param	int			Post ID
	 * @param	bool		Comment approved
	 * @return	boolean		Removed successfully
	 */
	public function toggleComment( $record, $category, $database, $pid, $approved=true )
	{
		//-----------------------------------------
		// Have a topic?
		//-----------------------------------------
		
		if( !$record['record_topicid'] OR !$pid )
		{
			return false;
		}

		//-----------------------------------------
		// Get forum libs
		//-----------------------------------------

		$this->_getForumLibraries();
		
		#$topic		= $this->DB->buildAndFetch( array( 'select' => 'title, topic_firstpost', 'from' => 'topics', 'where' => 'tid=' . $record['record_topicid'] ) );
		$forumId	= $category['category_forum_override'] ? $category['category_forum_forum'] : $database['database_forum_forum'];

		$this->moderatorLibrary->init( $this->registry->getClass('class_forums')->allForums[ $forumId ] );

		$this->moderatorLibrary->postToggleApprove( array( $pid ), $approved, $record['record_topicid'] );

		return true;
	}

	/**
	 * Hide a comment (soft delete)
	 *
	 * @access	public
	 * @param	array 		Record/article information
	 * @param	array 		Category information
	 * @param	array 		Database information
	 * @param	int			Post ID
	 * @param	string		Reason for delete
	 * @return	boolean		Removed successfully
	 */
	public function hideComment( $record, $category, $database, $pid, $reason )
	{
		//-----------------------------------------
		// Have a topic?
		//-----------------------------------------
		
		if( !$record['record_topicid'] OR !$pid )
		{
			return false;
		}

		//-----------------------------------------
		// Get forum libs
		//-----------------------------------------

		$this->_getForumLibraries();
		
		#$topic		= $this->DB->buildAndFetch( array( 'select' => 'title, topic_firstpost', 'from' => 'topics', 'where' => 'tid=' . $record['record_topicid'] ) );
		$forumId	= $category['category_forum_override'] ? $category['category_forum_forum'] : $database['database_forum_forum'];

		$this->moderatorLibrary->init( $this->registry->getClass('class_forums')->allForums[ $forumId ] );

		$this->moderatorLibrary->postToggleSoftDelete( array( $pid ), true, $reason, $record['record_topicid'] );

		return true;
	}

	/**
	 * Unhide a comment (soft delete)
	 *
	 * @access	public
	 * @param	array 		Record/article information
	 * @param	array 		Category information
	 * @param	array 		Database information
	 * @param	int			Post ID
	 * @return	boolean		Removed successfully
	 */
	public function unhideComment( $record, $category, $database, $pid )
	{
		//-----------------------------------------
		// Have a topic?
		//-----------------------------------------
		
		if( !$record['record_topicid'] OR !$pid )
		{
			return false;
		}

		//-----------------------------------------
		// Get forum libs
		//-----------------------------------------

		$this->_getForumLibraries();
		
		#$topic		= $this->DB->buildAndFetch( array( 'select' => 'title, topic_firstpost', 'from' => 'topics', 'where' => 'tid=' . $record['record_topicid'] ) );
		$forumId	= $category['category_forum_override'] ? $category['category_forum_forum'] : $database['database_forum_forum'];

		$this->moderatorLibrary->init( $this->registry->getClass('class_forums')->allForums[ $forumId ] );

		$this->moderatorLibrary->postToggleSoftDelete( array( $pid ), false, '', $record['record_topicid'] );

		return true;
	}
	
	/**
	 * Remove a posted topic
	 *
	 * @access	public
	 * @param	array 		Record/article information
	 * @param	array 		Category information
	 * @param	array 		Database information
	 * @return	boolean		Removed successfully
	 */
	public function removeTopic( $record, $category, $database )
	{
		//-----------------------------------------
		// Have a topic?
		//-----------------------------------------
		
		if( !$record['record_topicid'] )
		{
			return false;
		}

		//-----------------------------------------
		// Should we delete?
		//-----------------------------------------
		
		$delete		= $category['category_forum_override'] ? $category['category_forum_delete'] : $database['database_forum_delete'];
		$forumId	= $category['category_forum_override'] ? $category['category_forum_forum'] : $database['database_forum_forum'];
		
		if( $delete )
		{
			//-----------------------------------------
			// Get forum libs
			//-----------------------------------------
			
			$this->_getForumLibraries();
			
			$topic	= $this->DB->buildAndFetch( array( 'select' => 'title', 'from' => 'topics', 'where' => 'tid=' . $record['record_topicid'] ) );

			$this->moderatorLibrary->init( $this->registry->getClass('class_forums')->allForums[ $forumId ] );

			$this->DB->build( array( 'select' => 'tid, forum_id', 'from' => 'topics', 'where' => "state='link' AND moved_to='" . $record['record_topicid'] . '&' . $forumId . "'" ) );
			$this->DB->execute();
			
			if ( $linked_topic = $this->DB->fetch() )
			{
				$this->DB->delete( 'topics', "tid=" . $linked_topic['tid'] );
				
				$this->moderatorLibrary->forumRecount( $linked_topic['forum_id'] );
			}
			
			$this->moderatorLibrary->topicDelete( $record['record_topicid'] );
			$this->moderatorLibrary->addModerateLog( $forumId, $record['record_topicid'], '', $topic['title'], $this->lang->words['log_topic_del'] );
		}
		
		return true;
	}
	
	/**
	 * Sort out the topic
	 *
	 * @access	public
	 * @param	array 		Record/article information
	 * @param	array 		Category information
	 * @param	array 		Database information
	 * @param 	array		Comment information
	 * @return	array		Comment data
	 */	
	public function postComment( $record, $category, $database, $comment )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_global' ), 'core' );

		//-----------------------------------------
		// Approved?
		//-----------------------------------------

		if( $record['record_approved'] < 1 )
		{
			return false;
		}
		
		//-----------------------------------------
		// Try to fix topic automatically if we can..
		//-----------------------------------------
		
		if( !$record['record_topicid'] )
		{
			if( !$this->postTopic( $record, $category, $database ) )
			{
				return false;
			}
		}

		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------

		if( !is_array($this->fields) OR !count($this->fields) )
		{
			if( is_array($this->caches['ccs_fields'][ $database['database_id'] ]) AND count($this->caches['ccs_fields'][ $database['database_id'] ]) )
			{
				foreach( $this->caches['ccs_fields'][ $database['database_id'] ] as $_field )
				{
					$this->fields[ $_field['field_id'] ]	= $_field;
				}
			}
		}

		//-----------------------------------------
		// Get category handler
		//-----------------------------------------
		
		if( !is_object($this->categories) )
		{
			$this->categories	= $this->registry->ccsFunctions->getCategoriesClass( $database );
		}

		//-----------------------------------------
		// Format the fields for the record
		//-----------------------------------------

		$member	= $comment['comment_user'] ? IPSMember::load( $comment['comment_user'] ) : IPSMember::setUpGuest( $comment['comment_author'] );

		foreach( $record as $k => $v )
		{
			if( preg_match( "/^field_(\d+)$/", $k, $matches ) )
			{
				$record[ $k . '_value' ]	= $this->fieldsClass->getFieldValue( $this->fields[ $matches[1] ], array_merge( $member, $record ) );
				$record[ $this->fields[ $matches[1] ]['field_key'] ]	= $record[ $k . '_value' ];
			}
		}

		//-----------------------------------------
		// Get forum libs
		//-----------------------------------------
		
		$this->_getForumLibraries();
		
		//-----------------------------------------
		// Post comment
		//-----------------------------------------

		$topic	= $this->DB->buildAndFetch( array( 'select' => 'forum_id', 'from' => 'topics', 'where' => "tid=" . $record['record_topicid'] ) );

		try
		{
			/* Are we following? */
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like	= classes_like::bootstrap( 'forums','topics' );
			$_likes	= $_like->isLiked( $record['record_topicid'], $member['member_id'] );

			$this->post->setBypassPermissionCheck( true );
			$this->post->setIsAjax( true );
			$this->post->setPublished( $comment['comment_approved'] ? true : false );
			$this->post->setTopicID( $record['record_topicid'] );
			$this->post->setForumID( $topic['forum_id'] );
			$this->post->setForumData( $this->registry->class_forums->getForumById( $topic['forum_id'] ) );
			$this->post->setAuthor( $member );
			$this->post->setPostContentPreFormatted( $comment['comment_post'] );
			$this->post->setSettings( array( 'enableSignature' => 1,
									   'enableEmoticons' => 1,
									   'post_htmlstatus' => 0,
									   'enableTracker' => ( $_likes ? 1 : 0 ) ) );
			
			if( $this->post->addReply() === false )
			{
				if( $this->debugMode )
				{
					print_r($this->post->getPostError());exit;
				}
			}
			
			$post	= $this->post->getPostData();
		}
		catch( Exception $e )
		{
			if( $this->debugMode )
			{
				print $e->getMessage();exit;
			}
		}

		return array_merge( $comment, $post, array( 'pid' => $post['pid'], 'comment_id' => $post['pid'] ) );
	}
	
	/**
	 * Sort out the topic
	 *
	 * @access	public
	 * @param	array 		Record/article information
	 * @param	array 		Category information
	 * @param	array 		Database information
	 * @param 	string		Type [new|edit]
	 * @return	boolean		Posted successfully
	 */	
	public function postTopic( $record, $category, $database, $type = 'new' )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_global' ), 'core' );

		//-----------------------------------------
		// Approved?
		//-----------------------------------------

		if( $record['record_approved'] < 1 )
		{
			return false;
		}

		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------

		$fieldsByKey	= array();
		
		if( !is_array($this->fields) OR !count($this->fields) )
		{
			if( is_array($this->caches['ccs_fields'][ $database['database_id'] ]) AND count($this->caches['ccs_fields'][ $database['database_id'] ]) )
			{
				foreach( $this->caches['ccs_fields'][ $database['database_id'] ] as $_field )
				{
					$this->fields[ $_field['field_id'] ]	= $_field;
				}
			}
		}
		
		foreach( $this->fields as $_field )
		{
			$fieldsByKey[ $_field['field_key'] ]	= $_field;
		}
		
		//-----------------------------------------
		// Published?
		//-----------------------------------------

		if( $fieldsByKey['article_date'] AND $record['field_' . $fieldsByKey['article_date']['field_id'] ] > time() )
		{
			return false;
		}

		//-----------------------------------------
		// "Lock" the record from duplicate posting
		//-----------------------------------------

		if( !$record['record_topicid'] )
		{
			$this->DB->update( $database['database_database'], array( 'record_topicid' => -1 ), 'primary_id_field=' . $record['primary_id_field'] );
		}

		//-----------------------------------------
		// Get category handler
		//-----------------------------------------
		
		if( !is_object($this->categories) )
		{
			$this->categories	= $this->registry->ccsFunctions->getCategoriesClass( $database );
		}

		//-----------------------------------------
		// Format the fields for the record
		//-----------------------------------------

		$member			= $record['member_id'] ? IPSMember::load( $record['member_id'] ) : IPSMember::setUpGuest();

		foreach( $record as $k => $v )
		{
			if( preg_match( "/^field_(\d+)$/", $k, $matches ) )
			{
				$record[ $k . '_value' ]	= $this->fieldsClass->getFieldValue( $this->fields[ $matches[1] ], array_merge( $member, $record ) );
				$record[ $this->fields[ $matches[1] ]['field_key'] ]	= $record[ $k . '_value' ];
			}
		}

		//-----------------------------------------
		// Get our options
		//-----------------------------------------
		
		$topicOptions	= array(
								'postTopic'		=> $category['category_forum_override'] ? $category['category_forum_record'] : $database['database_forum_record'],
								'forumId'		=> $category['category_forum_override'] ? $category['category_forum_forum'] : $database['database_forum_forum'],
								'prefix'		=> $category['category_forum_override'] ? $category['category_forum_prefix'] : $database['database_forum_prefix'],
								'suffix'		=> $category['category_forum_override'] ? $category['category_forum_suffix'] : $database['database_forum_suffix'],
								);

		//-----------------------------------------
		// Post the topic?
		//-----------------------------------------

		if( $topicOptions['postTopic'] AND $topicOptions['forumId'] )
		{
			//-----------------------------------------
			// Get forum libs
			//-----------------------------------------
			
			$this->_getForumLibraries();

			//-----------------------------------------
			// Retrieve tags, in case this is not a form submit
			//-----------------------------------------

			if( !$_POST['ipsTags'] )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'core_tags', 'where' => "tag_meta_app='ccs' AND tag_meta_area='records-{$database['database_id']}' AND tag_meta_id=" . $record['primary_id_field'] ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$_POST['ipsTags'][]	= $r['tag_text'];

					if( $r['tag_prefix'] )
					{
						$_REQUEST['ipsTags_prefix']	= 1;
					}
				}
			}

			//-----------------------------------------
			// Prevent duplicate social shares
			//-----------------------------------------

			$_share_facebook					= $this->request['share_x_facebook'];
			$_share_twitter						= $this->request['share_x_twitter'];
			$this->request['share_x_facebook']	= null;
			$this->request['share_x_twitter']	= null;

			//---------------------------------------------------------
			// Update topic or post a new one?
			// @link	http://community.invisionpower.com/tracker/issue-25814-topics-not-posted-in-the-forum-for-old-records/
			//---------------------------------------------------------
			
			if(	$type == 'new' OR ( $type == 'edit' AND !$record['record_topicid'] ) )
			{
				$this->_postNewTopic( $record, $category, $database, $topicOptions, $member );
			}
			else
			{
				$this->_postUpdatedTopic( $record, $category, $database, $topicOptions, $member );
			}

			//-----------------------------------------
			// Restore social shares
			//-----------------------------------------

			$this->request['share_x_facebook']	= $_share_facebook;
			$this->request['share_x_twitter']	= $_share_twitter;
			
			return true;
		}

		$this->_unlockRecordTopic( $database, $record );

		return false;
	}

	/**
	 * Unlock a 'locked' record so topic can try to post again later
	 *
	 * @param	array 	Database data
	 * @param	array 	Record
	 * @return	@e bool
	 */
	protected function _unlockRecordTopic( $database, $record )
	{
		//-----------------------------------------
		// Topic did not post - unlock
		//-----------------------------------------

		if( !$record['record_topicid'] )
		{
			$this->DB->update( $database['database_database'], array( 'record_topicid' => 0 ), 'primary_id_field=' . $record['primary_id_field'] );
		}

		return true;
	}

	/**
	 * Post a new topic
	 *
	 * @access	protected
	 * @param	array 		Record/article information
	 * @param	array 		Category information
	 * @param	array 		Database information
	 * @param	array 		Topic posting options
	 * @param	array 		Member data
	 * @return	boolean
	 */	
	protected function _postNewTopic( $record, $category, $database, $topicOptions, $member )
	{
		if( !$member['member_id'] )
		{
			$this->request['UserName']	= $member['members_display_name'];
		}
		
		$ttitle			= $record[ $database['database_field_title'] . '_value' ];
		
		if( $topicOptions['prefix'] )
		{
			$ttitle		= $topicOptions['prefix'] . $ttitle;
		}
		
		if( $topicOptions['suffix'] )
		{
			$ttitle		.= $topicOptions['suffix'];
		}
		
		$post_content	= $this->_buildPostContent( $record, $category, $database, $topicOptions );

		try
		{
			$_html			= intval($this->fields[ str_replace( 'field_', '', $database['database_field_content'] ) ]['field_html']);
				
			$this->post->setBypassPermissionCheck( true );
			$this->post->setIsAjax( false );
			$this->post->setPublished( true );
			$this->post->setForumID( $topicOptions['forumId'] );
			$this->post->setAuthor( $member );
			$this->post->setPostContentPreFormatted( $post_content );
			$this->post->setPreventFromArchiving( true );
			$this->post->setTopicTitle( IPSText::truncate( IPSText::UNhtmlspecialchars( $ttitle ), $this->settings['topic_title_max_len'] ) );
			$this->post->setSettings( array( 'enableSignature' => 1,
									   'enableEmoticons' => 1,
									   'post_htmlstatus' => $_html ) );

			$_backupMember		= $this->memberData;
			$this->memberData	= $member;

			if( $this->post->addTopic() === false )
			{
				if( $this->debugMode )
				{
					print_r($this->post->getPostError());exit;
				}

				$this->_unlockRecordTopic( $database, $record );

				$this->memberData	= $_backupMember;

				return false;
			}

			$this->memberData	= $_backupMember;
			
			$topic	= $this->post->getTopicData();
			$post	= $this->post->getPostData();

			if( $topic['tid'] )
			{
				$this->DB->update( 'topics', array( 'topic_archive_status' => 3 ), 'tid=' . $topic['tid'] );
			}
		}
		catch( Exception $e )
		{
			if( $this->debugMode )
			{
				print $e->getMessage();exit;
			}

			$this->_unlockRecordTopic( $database, $record );

			return false;
		}

		$this->DB->update( $database['database_database'], array( 'record_topicid' => $topic['tid'] ), "primary_id_field=" . $record['primary_id_field'] );		
		
		return true;
	}
	
	/**
	 * Update an existing topic
	 *
	 * @access	protected
	 * @param	array 		Record/article information
	 * @param	array 		Category information
	 * @param	array 		Database information
	 * @param	array 		Topic posting options
	 * @param	array 		Member data
	 * @return	boolean
	 */	
	protected function _postUpdatedTopic( $record, $category, $database, $topicOptions, $member )
	{
		//-----------------------------------------
		// No topic?  Nothing to update then
		//-----------------------------------------

		if( !$record['record_topicid'] )
		{
			return false;
		}

		if( !$member['member_id'] )
		{
			$this->request['UserName']	= $member['members_display_name'];
		}

		//-----------------------------------------
		// Topic title
		//-----------------------------------------
		
		$ttitle			= $record[ $database['database_field_title'] . '_value' ];
		
		if( $topicOptions['prefix'] )
		{
			$ttitle		= $topicOptions['prefix'] . $ttitle;
		}
		
		if( $topicOptions['suffix'] )
		{
			$ttitle		.= $topicOptions['suffix'];
		}

		try
		{
			$firstpost	= $this->DB->buildAndFetch( array( 'select'	=> '*',
															'from'	=> 'topics',
															'where'	=> 'tid=' . $record['record_topicid']
													)		);

			if( $firstpost['forum_id'] != $topicOptions['forumId'] )
			{
				$topicOptions['forumId']	= $firstpost['forum_id'];
			}

			if( $firstpost['topic_firstpost'] )
			{
				/* Are we following? */
				require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
				$_like	= classes_like::bootstrap( 'forums','topics' );
				$_likes	= $_like->isLiked( $record['record_topicid'], $member['member_id'] );

				$post_content	= $this->_buildPostContent( $record, $category, $database, $topicOptions );
				$_html			= intval($this->fields[ str_replace( 'field_', '', $database['database_field_content'] ) ]['field_html']);

				$this->post->setBypassPermissionCheck( true );
				$this->post->setIsAjax( false );
				$this->post->setPublished( $firstpost['approved'] ? true : false );
				$this->post->setPostID( $firstpost['topic_firstpost'] );
				$this->post->setTopicData( $firstpost );
				$this->post->setTopicID( $record['record_topicid'] );
				$this->post->setTopicTitle( IPSText::truncate( IPSText::UNhtmlspecialchars( $ttitle ), $this->settings['topic_title_max_len'] ) );
				$this->post->setForumID( $topicOptions['forumId'] );
				$this->post->setAuthor( $member );
				$this->post->setPostContentPreFormatted( $post_content );
				$this->post->setPreventFromArchiving( true );
				$this->post->setSettings( array( 'enableSignature' => 1,
										   'enableEmoticons' => 1,
										   'post_htmlstatus' => $_html,
										   'enableTracker' => ( $_likes ? 1 : 0 ) ) );
				
				$_backupMember		= $this->memberData;
				$this->memberData	= $member;

				if( $this->post->editPost() === false )
				{
					if( $this->debugMode )
					{
						print_r($this->post->getPostError());exit;
					}

					$this->memberData	= $_backupMember;

					$this->_unlockRecordTopic( $database, $record );

					return false;
				}

				$this->memberData	= $_backupMember;

				$post	= $this->post->getPostData();

				$this->DB->update( 'topics', array( 'topic_archive_status' => 3 ), 'tid=' . $record['record_topicid'] );
			}
		}
		catch( Exception $e )
		{
			if( $this->debugMode )
			{
				print $e->getMessage();exit;
			}

			$this->_unlockRecordTopic( $database, $record );

			return false;
		}

		return true;
	}
	
	/**
	 * Build the actual post content
	 *
	 * @access	protected
	 * @param	array 		Record/article information
	 * @param	array 		Category information
	 * @param	array 		Database information
	 * @param	array 		Topic posting options
	 * @return	boolean
	 */	
	protected function _buildPostContent( $record, $category, $database, $topicOptions )
	{
		$_return	= '';
		$_fieldCnt	= 0;
		
		$url = $this->registry->ccsFunctions->returnDatabaseUrl( $database['database_id'], 0, $record );
		
		//-----------------------------------------
		// Include fields set in ACP
		//-----------------------------------------
		
		foreach( $this->fields as $_field )
		{
			if( $_field['field_topic_format'] )
			{
				// <s>Should be just field_x not field_x_value as that will be the post as HTML - we want BBCode</s>
				// If we don't return proper field value, dropdowns, radio, checkbox and other custom fields are not parsed correctly
				$_thisValue	= str_replace( '{key}', $_field['field_name'], str_replace( '{value}', $this->fieldsClass->getFieldValue( $this->fields[ $_field['field_id'] ], $record ), $_field['field_topic_format'] ) );
				
				if( $_thisValue )
				{
					$_return	.= $_thisValue;
				}
			}
		}
		
		//-----------------------------------------
		// Fallback to just using content if none configured
		//-----------------------------------------
		
		if( !$_return )
		{
			// Should be just field_x not field_x_value as that will be the post as HTML - we want BBCode
			$_return	= $record[ $database['database_field_content'] ];
		}
				
		//-----------------------------------------
		// Fix [page] links
		// http://community.invisionpower.com/tracker/issue-29032-page-tags-in-posts
		//-----------------------------------------
		
		$_return = preg_replace( '/<a href=[\'"](.+?)&amp;pg=(\d+)[\'"]/', "<a href=\"{$url}&amp;pg=\$2\"", $_return );

		//-----------------------------------------
		// Parse out attachment tags to links
		//-----------------------------------------
		
		$_return	= preg_replace_callback( "#\[attachment=(\d+?)\:(?:[^\]]+?)\]#is", array( &$this, '_parseAttachments' ), $_return );
		
		//-----------------------------------------
		// Append link
		//-----------------------------------------

		$_return	.= $this->registry->output->getTemplate('ccs_global')->topicCommentsLink( $record, $url );
		
		return $_return;
	}
	
	/**
	 * Parse out attachment tags to point to attachments
	 *
	 * @access	protected
	 * @param	array 		Matches from regex
	 * @return	string		Text to replace with
	 */
	protected function _parseAttachments( $matches )
	{
		$_id	= intval($matches[1]);
		
		if( !$_id )
		{
			return '';
		}
		else
		{
			$_attachment	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'attachments', 'where' => 'attach_id=' . $_id ) );
			
			if( $_attachment['attach_id'] )
			{
				return '[url="' . $this->registry->output->buildSEOUrl( "app=core&amp;module=attach&amp;section=attach&amp;attach_id=" . $_id ) . '"]' . $this->lang->words['posttopic_dl_attach_pre'] . $_attachment['attach_file'] . '[/url] ';
			}
		}
	}

	/**
	 * Load the forum libraries
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _getForumLibraries()
	{
		//---------------------------------------------------------
		// Get some libraries we need
		//---------------------------------------------------------

		ipsRegistry::getAppClass( 'forums' );
		
		$classToLoad			= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
		$this->moderatorLibrary	=  new $classToLoad( $this->registry );

		$classToLoad			= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php', 'classPost', 'forums' );
		$classToLoad			= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php', 'classPostForms', 'forums' );
		$this->post				=  new $classToLoad( $this->registry );
	}
}
