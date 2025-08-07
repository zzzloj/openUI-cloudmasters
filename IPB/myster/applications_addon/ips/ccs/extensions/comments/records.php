<?php
/**
 * Calendar comments class
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Downloads
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9916 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class comments_ccs_records extends classes_comments_renderer
{
	/**
	 * Database information
	 *
	 * @param	array
	 */
 	protected $database			= array();

	/**
	 * Category information
	 *
	 * @param	array
	 */
 	protected $category			= array();

	/**
	 * Field information
	 *
	 * @param	array
	 */
 	protected $fields			= array();

	/**
	 * New forum post just saved
	 *
	 * @param	array
	 */
 	protected $_result			= array();

	/**
	 * Cached topic data, if available
	 *
	 * @param	array
	 */
 	protected $topic			= array();
 	
 	/**
 	 * Use forums?
 	 * 
 	 * @param	bool
 	 */
 	protected $useForums		= false;

 	/**
 	 * Current record we are viewing
 	 * 
 	 * @param	bool
 	 */
 	protected $_currentRecord	= array();

	/**
	 * Internal remap array
	 *
	 * @param	array
	 */
	protected $_remap			= array( 'comment_id'			=> 'comment_id',
										 'comment_author_id'	=> 'comment_user',
										 'comment_author_name'	=> 'comment_author',
										 'comment_text'			=> 'comment_post',
										 'comment_ip_address'	=> 'comment_ip_address',
										 'comment_edit_date'	=> 'comment_edit_date',
										 'comment_date'			=> 'comment_date',
										 'comment_approved'		=> 'comment_approved',
										 'comment_parent_id'	=> 'comment_record_id' );
					 
	/**
	 * Internal parent remap array
	 *
	 * @param	array
	 */
	protected $_parentRemap		= array( 'parent_id'			=> 'primary_id_field',
 										 'parent_owner_id'		=> 'member_id',
									     'parent_parent_id'		=> 'category_id',
									     'parent_title'			=> '',
									     'parent_seo_title'		=> '',
									     'parent_date'			=> 'record_saved' );

	/**
	 * Init method
	 *
	 * @param	mixed	Extra data
	 * @return	@e void
	 */
	public function init( $extraData=null )
	{
		//-----------------------------------------
		// Store database and category data
		//-----------------------------------------
		
		$this->database			= $extraData['database'];
		$this->category			= $extraData['category'];
		$this->_currentRecord	= $extraData['record'];

		$this->fields	= array();

		if( is_array($this->cache->getCache('ccs_fields')) AND is_array($this->caches['ccs_fields'][ $this->database['database_id'] ]) AND count($this->caches['ccs_fields'][ $this->database['database_id'] ]) )
		{
			foreach( $this->caches['ccs_fields'][ $this->database['database_id'] ] as $_field )
			{
				$this->fields[ $_field['field_id'] ]	= $_field;
			}
		}

		//-----------------------------------------
		// Determine if we are using forums or not
		//-----------------------------------------
		
	    $forumIntegration	= $this->category['category_forum_override'] ? $this->category['category_forum_record'] : $this->database['database_forum_record'];
	    $commentsToo		= $this->category['category_forum_override'] ? $this->category['category_forum_comments'] : $this->database['database_forum_comments'];
	    //print_r($this->category);var_dump($forumIntegration);var_dump($commentsToo);exit;
	    if( $forumIntegration AND $commentsToo )
	    {
			$this->useForums	= true;
	    }
	    else
	    {
			$this->_parentRemap['parent_title']	= $this->database['database_field_title'] != 'primary_id_field' ? $this->database['database_field_title'] : '';
	    }
	    
		//-----------------------------------------
		// And pass to parent
		//-----------------------------------------
		
	    return parent::init();
	}
	
	/**
	 * Parent SEO template
	 *
	 * @return	string
	 */
	public function seoTemplate()
	{
		return 'page';
	}

	/**
	 * Who am I?
	 *
	 * @return	string
	 */
	public function whoAmI()
	{
		return 'ccs-records';
	}
	
	/**
	 * Comment table
	 *
	 * @return	string
	 */
	public function table()
	{
		return $this->useForums ? 'posts' : 'ccs_database_comments';
	}

	/**
	 * Return the reputation data formatted.  Abstracted so apps can override if needed.
	 *
	 * @param	string	Application
	 * @param	array 	Remap data
	 * @param	array 	Record data from database
	 * @return	@string Reputation formatted
	 */
	public function returnReputationFormatted( $app, $_remap, $row )
	{
		return $this->registry->repCache->getLikeFormatted( array( 'app' => $this->useForums ? 'forums' : $app, 'type' => $this->useForums ? 'pid' : $_remap['comment_id'], 'id' => $row['comment_id'], 'rep_like_cache' => $row['rep_like_cache'] ) );
	}
	
	/**
	 * Fetch parent
	 *
	 * @return	array
	 */
	public function fetchParent( $id )
	{
		if( !$id OR !$this->database['database_id'] )
		{
			return array();
		}

		static $cachedRecords	= array();

		if( !isset($cachedRecords[ $this->_currentRecord['primary_id_field'] ]) )
		{
			$cachedRecords[ $this->_currentRecord['primary_id_field'] ]	= $this->_currentRecord;
		}

		if( !isset($cachedRecords[ $id ]) )
		{
			$_record				= $this->DB->buildAndFetch( array(
																	'select'	=> '*',
																	'from'		=> $this->database['database_database'],
																	'where'		=> 'primary_id_field=' . intval($id),
															)		);

			if( $_record['primary_id_field'] )
			{
				$cachedRecords[ $id ]	= array_merge( $_record, $this->database );
			}
		}
		
		return $cachedRecords[ $id ];
	}
	
	/**
	 * Fetch settings
	 *
	 * @return	array
	 */
	public function settings()
	{
		return array( 'urls-showParent' => "app=ccs&module=pages&section=pages&do=redirect&database=" . $this->database['database_id'] . "&record=%s",
					  'urls-report'		=> $this->getReportLibrary()->canReport( 'ccs' ) ? "app=core&amp;module=reports&amp;rcom=ccs&amp;comment_id=%s&amp;database=" . $this->database['database_id'] . "&amp;record=%s" : '' );
	}
	
	/**
	 * Number of items per page
	 *
	 * @return	int
	 */
	public function perPage()
	{
		return 25;
	}
	
	/**
	 * Build a URL using the proper domain as necessary
	 * 
	 * @param	string	URL
	 * @return	@e string
	 */
 	protected function _buildUrl( $url )
 	{
		$_domain	= parse_url( $this->settings['base_url'], PHP_URL_HOST );
		$_reqDomain	= $_SERVER['HTTP_HOST'];
		
		if( $_domain != $_reqDomain )
		{
			$replacement	= '';
			$_possibilities	= explode( "\n", str_replace( "\r", '', $this->settings['ccs_root_url'] ) );
			
			foreach( $_possibilities as $_try )
			{
				$_thisDomain = parse_url( $_try, PHP_URL_HOST );
				
				if( $_thisDomain == $_reqDomain )
				{
					$replacement = rtrim( $_try, '/' ) . '/' . $this->settings['ccs_root_filename'] . '?s=' . $this->member->session_id . '&';
					break;
				}
			}
			
			if( $replacement )
			{
				return $replacement . $url;
			}
			else
			{
				return $this->settings['base_url'] . $url;
			}
		}
		else
		{
			return $this->settings['base_url'] . $url;
		}
 	}

	/**
	 * Adjust parameters
	 *
	 * @param	string	Skin template being called
	 * @param	array	Array of parameters to be passed to skin template
	 * @return	array	Skin parameters to be passed to template (array keys MUST be preserved)
	 */
	public function preOutputAdjustment( $template, $params )
	{
		switch( $template )
		{
			case 'commentsList':
			default:
				$params['data']['ajaxSaveUrl']			= $this->_buildUrl( "app=ccs&module=ajax&section=comments&do=add&database={$this->database['database_id']}&record={$params['parent']['parent_id']}" );
				$params['data']['ajaxDeleteUrl']		= $this->_buildUrl( "app=ccs&module=ajax&section=comments&do=delete&database={$this->database['database_id']}&record={$params['parent']['parent_id']}" );
				$params['data']['ajaxModerateUrl']		= $this->_buildUrl( "app=ccs&module=ajax&section=comments&do=moderate&database={$this->database['database_id']}&record={$params['parent']['parent_id']}" );
				$params['data']['ajaxShowEditUrl']		= $this->_buildUrl( "app=ccs&module=ajax&section=comments&do=showEdit&database={$this->database['database_id']}&record={$params['parent']['parent_id']}" );
				$params['data']['ajaxSaveEditUrl']		= $this->_buildUrl( "app=ccs&module=ajax&section=comments&do=saveEdit&database={$this->database['database_id']}&record={$params['parent']['parent_id']}" );
				$params['data']['ajaxFetchReplyUrl']	= $this->_buildUrl( "app=ccs&module=ajax&section=comments&do=fetchReply&database={$this->database['database_id']}&record={$params['parent']['parent_id']}" );
				$params['data']['findLastComment']		= $this->_buildUrl( "app=ccs&module=pages&section=pages&do=redirect&database={$this->database['database_id']}&record={$params['parent']['parent_id']}&comment=_last_" );
				$params['data']['baseUrl']				= "app=ccs&amp;module=pages&amp;section=comments&amp;database={$this->database['database_id']}&amp;record={$params['parent']['parent_id']}";
				$params['data']['formUrl']				= $this->registry->output->buildSEOUrl( "app=ccs&amp;database={$this->database['database_id']}&amp;record={$params['parent']['parent_id']}" );
				$params['data']['formApp']				= 'ccs';
				$params['data']['formModule']			= 'pages';
				$params['data']['formSection']			= 'comments';
				
				if( $this->useForums )
				{
					$params['data']['repApp']				= 'forums';
					$params['data']['repType']				= 'pid';
				}
			break;
			
			case 'form':
				$params['settings']['baseUrl']			= "app=ccs&amp;module=pages&amp;section=comments&amp;database={$this->database['database_id']}&amp;record={$params['parent']['parent_id']}";
				$params['settings']['formApp']			= 'ccs';
				$params['settings']['formModule']		= 'pages';
				$params['settings']['formAction']		= $this->settings['base_url'] . 'database=' . $this->database['database_id'] . '&amp;record=' . $params['parent']['parent_id'];
			break;
		}
		
		return $params;
	}

	/**
	 * Adjust query used in fetch call
	 *
	 * @param	array	Array of query params
	 * @param	array 	Remapped columns used in the query
	 * @param	array 	Parent data
	 * @param	array 	Filters to use in the query
	 * @return	array	Array of query params
	 */
	public function alterFetchQuery( $query, $remap, $parent, $filters )
	{
		if( $this->useForums )
		{
			//-----------------------------------------
			// Joins
			//-----------------------------------------
			
			$_post_joins = array(
								array(
										'type'		=> 'left',
										'select'	=> 'm.*',
										'where'		=> 'm.member_id=p.author_id',
										'from'		=> array( 'members' => 'm' )
									),
								array(
										'type'		=> 'left',
										'select'	=> 'pp.*',
										'where'		=> 'pp.pp_member_id=m.member_id',
										'from'		=> array( 'profile_portal' => 'pp' )
									),
								array(
										'select'	=> 'pd.*',
										'from'		=> array( 'pfields_content' => 'pd' ),
										'where'		=> 'pd.member_id=m.member_id',
										'type'		=> 'left',
									),
								);
				
			//-----------------------------------------
			// Reputation system joins
			//-----------------------------------------
			
			if( $this->settings['reputation_enabled'] )
			{
				$_post_joins[] = $this->registry->repCache->getUserHasRatedJoin( 'pid', 'p.pid', 'forums' );

				if( $this->settings['reputation_show_content'] )
				{
					$_post_joins[] = $this->registry->repCache->getTotalRatingJoin( 'pid', 'p.pid', 'forums' );
				}
			}

			//-----------------------------------------
			// Get query
			//-----------------------------------------

			$where	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ) );
			
			if( $this->database['moderate_approvec'] AND $this->database['moderate_deletec'] )
			{
				$where	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'notVisible', 'visible' ) );
			}
			else if( $this->database['moderate_approvec'] )
			{
				$where	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'queued', 'visible' ) );
			}
			else if( $this->database['moderate_deletec'] )
			{
				$where	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'sdelete', 'visible' ) );
			}
			
			$where	.= " AND ";

			if ( isset( $filters['comment_id'] ) )
			{
				if ( is_numeric( $filters['comment_id'] ) )
				{
					$where .= 'p.pid=' . intval( $filters['comment_id'] ) . ' AND ';
				}
				else if ( is_array( $filters['comment_id'] ) )
				{
					$where .= 'p.pid IN(' . implode( ",", IPSLib::cleanIntArray( $filters['comment_id'] ) ) . ') AND ';
				}
			}

			if( $this->request['comments'] == 'unapproved' && $this->can( 'visibility', array( 'comment_parent_id' => $parent['parent_id'] ) ) )
			{
				$where	.= 'p.queued=1 AND ';
			}
		
			$this->topic	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => "tid=" . intval($parent['record_topicid']) ) );

			$query	= array( 
							'select'	=> 'p.pid as comment_id, p.author_name as comment_author, p.author_id as comment_user, p.post as comment_post, p.ip_address as comment_ip_address,
											p.edit_time as comment_edit_date, p.post_date as comment_date, ' . intval($parent['parent_id']) . ' as comment_record_id, 
											CASE WHEN p.queued=0 THEN 1 ELSE ( CASE WHEN p.queued=2 THEN -1 ELSE 0 END ) END as comment_approved',
							'from'		=> array( 'posts' => 'p' ),
							'where'		=> $where . "p.pid <> " . intval($this->topic['topic_firstpost']) . " AND p.topic_id=" . intval($parent['record_topicid']),
							'order'		=> $this->settings['post_order_column'] . ' ' . $this->settings['post_order_sort'],
							'add_join'	=> $_post_joins,
							'limit'		=> $query['limit'],
							);
		}
		else
		{
			$query['where']	.= " AND c.comment_database_id=" . $this->database['database_id'];

			if( $this->request['comments'] == 'unapproved' && $this->can( 'visibility', array( 'comment_parent_id' => $parent['parent_id'] ) ) )
			{
				$query['where']	.= " AND c.comment_approved=0";
			}
		}
		
		return $query;
	}
	
	/**
	 * Pre save
	 * Accepts an array of GENERIC data and allows manipulation before it's added to DB
	 *
	 * @param	string	Type of save (edit/add)
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @param	int		Comment id (if available)
	 * @param	int		Parent id
	 * @return 	array	Array of GENERIC data
	 */
	public function preSave( $type, array $array, $commentId=0, $parentId=0 )
	{
		if ( $type == 'add' )
		{
			//-----------------------------------------
			// Adjust approved flag if necessary
			//-----------------------------------------

			if ( $array['comment_approved'] )
			{
				$array['comment_approved']	= !$this->database['database_comment_approve'] ? 1 : ( $this->database['moderate_approvec'] ? 1 : 0 );
			}

			IPSLib::doDataHooks( $array, 'ccsAddComment' );
		}
		else
		{
			IPSLib::doDataHooks( $array, 'ccsEditComment' ); 
		}
		
		$array['comment_database_id']	= $this->database['database_id'];
		
		//-----------------------------------------
		// Adjust for posts if needed
		//-----------------------------------------
			
		if( $this->useForums )
		{
			if( $type == 'add' )
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
				$_topics		= new $classToLoad( $this->registry, $this->registry->ccsFunctions->getFieldsClass(), $this->registry->ccsFunctions->getCategoriesClass( $this->database ), $this->fields );
				$record			= $this->fetchParent( $parentId );

				$this->_result	= $_topics->postComment( $record, $this->category, $this->database, $this->remapToLocal( $array ) );

				$array	= array();
			}
			else
			{
				$this->DB->update( 'posts', array( 'edit_time' => IPS_UNIX_TIME_NOW, 'post' => $array['comment_text'] ), 'pid=' . intval( $commentId ) );
				
				$array	= array();
			}
		}
		
		return $array;
	}
	
	/**
	 * Post save
	 * Accepts an array of GENERIC data and allows manipulation after it's added to DB
	 *
	 * @param	string	Type of action (edit/add)
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @param	int		Comment id (if available)
	 * @param	int		Parent id
	 * @return 	array	Array of GENERIC data
	 */
	public function postSave( $type, array $array, $commentId=0, $parentId=0 )
	{
		//-----------------------------------------
		// Rebuild cached count of comments
		//-----------------------------------------
		
		$this->_rebuildCommentCount( $parentId );

		//-----------------------------------------
		// Get record data
		//-----------------------------------------

		$record			= $this->fetchParent( $parentId );
		
		//-----------------------------------------
		// Bump record if necessary
		//-----------------------------------------
		
		if( $this->database['database_comment_bump'] AND $type == 'add' AND $array['comment_approved'] )
		{
			$this->DB->update( $this->database['database_database'], array( 'record_updated' => IPS_UNIX_TIME_NOW ), 'primary_id_field=' . $parentId );

			if( $record['category_id'] )
			{
				$this->registry->ccsFunctions->getCategoriesClass( $this->database )->recache( $record['category_id'] );
			}
		}

		//-----------------------------------------
		// Forum stuff
		//-----------------------------------------
		
		if( $this->useForums )
		{
			if( $type == 'edit' )
			{
				IPSContentCache::drop( 'post', $commentId );
			}
		}

		//-----------------------------------------
		// Data hooks
		//-----------------------------------------
		
		IPSLib::doDataHooks( $array, 'ccsComment' . ucfirst( $type ) . 'PostSave' );
		
		if( $this->useForums AND $type == 'add' AND !count($this->_result) )
		{
			throw new Exception( 'MISSING_DATA' );
		}

		//-----------------------------------------
		// Mark read
		//-----------------------------------------

		$this->registry->classItemMarking->markRead( array( 'catID' => $record['category_id'], 'itemID' => $record['primary_id_field'], 'databaseID' => $this->database['database_id'] ), 'ccs' );

		return ( $this->useForums AND $type == 'add' ) ? $this->_result : $array;
	}
	
	/**
	 * Post delete. Can do stuff and that
	 *
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 * @return 	void
	 */
	public function postDelete( $commentIds, $parentId )
	{
		//-----------------------------------------
		// Rebuild cached count of comments
		//-----------------------------------------
		
		$this->_rebuildCommentCount( $parentId );
		
		$_dataHook	= array( 'commentIds'	=> $commentIds,
							 'parentId'		=> $parentId );

		IPSLib::doDataHooks( $_dataHook, 'ccsCommentPostDelete' );
	}
	
	/**
	 * Toggles visibility
	 * 
	 * @param	string	on/off
	 * @param	array	Array of comment IDs to be deleted
	 * @param	int		Parent ID
	 * @return 	void
	 */
	public function postVisibility( $toggle, $commentIds, $parentId )
	{
		//-----------------------------------------
		// Rebuild cached count of comments
		//-----------------------------------------
		
		$this->_rebuildCommentCount( $parentId );
		
		$_dataHook	= array( 'toggle'		=> $toggle,
							 'commentIds'	=> $commentIds,
							 'parentId'		=> $parentId );

		IPSLib::doDataHooks( $_dataHook, 'ccsCommentToggleVisibility' );
	}
	
	/**
	 * Rebuild comment counts for a record
	 *
	 * @param	int		Record ID
	 * @return	@e void
	 */
	protected function _rebuildCommentCount( $parentId )
	{
		//-----------------------------------------
		// Update comments count for record
		//-----------------------------------------
		
		$record	= $this->fetchParent( $parentId );

		if( $this->useForums )
		{
			$topic		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => "tid={$record['record_topicid']}" ) );
			
			$approved	= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'posts', 'where' => $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ) ) . " AND pid <> {$topic['topic_firstpost']} AND topic_id={$record['record_topicid']}" ) );
			$unapproved	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'posts', 'where' => $this->registry->class_forums->fetchPostHiddenQuery( array( 'notVisible' ) ) . " AND pid <> {$topic['topic_firstpost']} AND topic_id={$record['record_topicid']}" ) );
		}
		else
		{
			$approved	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'ccs_database_comments', 'where' => "comment_approved=1 AND comment_database_id={$this->database['database_id']} AND comment_record_id=" . $parentId ) );
			$unapproved	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'ccs_database_comments', 'where' => "comment_approved=0 AND comment_database_id={$this->database['database_id']} AND comment_record_id=" . $parentId ) );
		}

		$this->DB->update( $this->database['database_database'], array( 'record_comments' => $approved['total'], 'record_comments_queued' => $unapproved['total'] ), 'primary_id_field=' . $parentId );
		
		//-----------------------------------------
		// Update comments count for category
		//-----------------------------------------

		if( $record['category_id'] )
		{
			$approved	= $this->DB->buildAndFetch( array( 'select' => 'SUM(record_comments) as total', 'from' => $this->database['database_database'], 'where' => "category_id=" . $record['category_id'] ) );
			$unapproved	= $this->DB->buildAndFetch( array( 'select' => 'SUM(record_comments_queued) as total', 'from' => $this->database['database_database'], 'where' => "category_id=" . $record['category_id'] ) );

			$this->DB->update( 'ccs_database_categories', array( 'category_record_comments' => $approved['total'], 'category_record_comments_queued' => $unapproved['total'] ), 'category_id=' . $record['category_id'] );
		}
	}
	
	/**
	 * Fetch a total count of comments we can see
	 *
	 * @param	mixed	parent Id or parent array
	 * @return	int
	 */
	public function count( $parent )
	{
		//-----------------------------------------
		// Get parent
		//-----------------------------------------
		
		if ( is_numeric( $parent ) )
		{
			$parent	= $this->fetchParent( $parent );
		}

		//-----------------------------------------
		// If we use the forums, query count realtime.
		// If it is not what it should be, rebuild cached counts.
		// If using built in comments, just get from cache.	
		//-----------------------------------------
		
		if( $this->useForums AND $this->topic['tid'] )
		{
			$where	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'visible' ) );
			
			if( $this->database['moderate_approvec'] AND $this->database['moderate_deletec'] )
			{
				$where	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'notVisible', 'visible' ) );
			}
			else if( $this->database['moderate_approvec'] )
			{
				$where	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'queued', 'visible' ) );
			}
			else if( $this->database['moderate_deletec'] )
			{
				$where	= $this->registry->class_forums->fetchPostHiddenQuery( array( 'sdelete', 'visible' ) );
			}
			
			$count	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'posts', 'where' => $where . " AND pid <> " . intval($this->topic['topic_firstpost']) . " AND topic_id={$parent['record_topicid']}" ) );
			
			if( $this->database['moderate_approvec'] AND $count['total'] != $parent['record_comments'] + $parent['record_comments_queued'] )
			{
				$this->_rebuildCommentCount( $parent['parent_id'] ? $parent['parent_id'] : $parent['primary_id_field'] );
			}
			else if( !$this->database['moderate_approvec'] AND $count['total'] != $parent['record_comments'] )
			{
				$this->_rebuildCommentCount( $parent['parent_id'] ? $parent['parent_id'] : $parent['primary_id_field'] );
			}
			
			return $count['total'];
		}
		else
		{				
			if( $this->database['moderate_approvec'] )
			{
				return $parent['record_comments'] + $parent['record_comments_queued'];
			}
			else
			{
				return $parent['record_comments'];
			}
		}
	}
	
	/**
	 * Perform a permission check
	 *
	 * @param	string	Type of check (add/edit/delete/editall/deleteall/approve all)
	 * @param	array 	Array of GENERIC data
	 * @return	true or string to be used in exception
	 */
	public function can( $type, array $array )
	{ 
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$comment = array();

		if ( empty( $array['comment_parent_id'] ) )
		{
			trigger_error( "No parent ID passed to " . __FILE__, E_USER_WARNING );
		}
		
		//-----------------------------------------
		// Get record data
		//-----------------------------------------
		
		$record	= $this->fetchParent( $array['comment_parent_id'] );

		//-----------------------------------------
		// Get comment
		//-----------------------------------------
		
		if ( $array['comment_id'] )
		{ 
			$comment	= $this->fetchById( $array['comment_id'] );
		}

		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		switch( $type )
		{
			case 'view':
				if ( $this->registry->permissions->check( 'view', $this->database ) != TRUE OR $this->registry->permissions->check( 'show', $this->database ) != TRUE )
				{
					return 'NO_PERMISSION';
				}
				
				if( !$this->database['database_comments'] )
				{
					return 'NO_PERMISSION';
				}
				
				if ( $this->category['category_has_perms'] AND $this->registry->permissions->check( 'show', $this->category ) != TRUE )
				{
					return 'NO_PERMISSION';
				}

				if( $record['record_approved'] == -1 AND !$this->database['moderate_delete'] )
				{
					return 'NO_PERMISSION';
				}

				if( $record['record_approved'] == 0 AND ( !$this->memberData['member_id'] OR $this->memberData['member_id'] != $record['member_id'] ) AND !$this->database['moderate_approve'] )
				{
					return 'NO_PERMISSION';
				}

				return true;
			break;
			
			case 'edit':
				if( !$this->database['_can_comment'] or $record['record_locked'] )
				{
					return 'NO_PERMISSION';
				}

				if( !$this->database['moderate_editc'] AND ( !$this->memberData['member_id'] OR $this->memberData['member_id'] != $comment['comment_author_id'] ) )
				{
					return 'NO_PERMISSION';
				}

				return true;
			break;
			
			case 'add':
				if( !$this->database['_can_comment'] or $record['record_locked'] )
				{
					return 'NO_PERMISSION';
				}

				foreach( $this->fields as $k => $v )
				{
					if( $v['field_key'] == 'article_comments' )
					{
						if( !$record[ 'field_' . $k ] )
						{
							return 'NO_PERMISSION';
						}
					}
					else if( $v['field_key'] == 'article_cutoff' )
					{
						if( $record[ 'field_' . $k ] AND time() > $record[ 'field_' . $k ] )
						{
							return 'NO_PERMISSION';
						}
					}
				}

				if( !$this->database['moderate_approve'] AND !$record['record_approved'] )
				{
					return 'NO_PERMISSION';
				}
				else if( !$this->database['moderate_delete'] AND $record['record_approved'] == -1 )
				{
					return 'NO_PERMISSION';
				}

				return true;
			break;
			
			case 'delete':
				return $this->database['moderate_deletec'] ? true : 'NO_PERMISSION';
			break;
			
			case 'visibility':
				return $this->database['moderate_approvec'] ? true : 'NO_PERMISSION';
			break;
			
			case 'moderate':
				if( $this->database['moderate_approvec'] OR $this->database['moderate_deletec'] OR IPSMember::canModerateContent( $this->memberData, IPSMember::CONTENT_HIDE, $comment['comment_author_id'] ) 
					OR IPSMember::canModerateContent( $this->memberData, IPSMember::CONTENT_UNHIDE, $comment['comment_author_id'] ) )
				{
					return true;
				}
			
				return 'NO_PERMISSION';
			break;
			
			case 'hide':
				return ( $this->database['moderate_deletec'] OR IPSMember::canModerateContent( $this->memberData, IPSMember::CONTENT_HIDE, $comment['comment_author_id'] ) ) ? TRUE : 'NO_PERMISSION';
			break;
			
			case 'unhide':
				return ( $this->database['moderate_deletec'] OR IPSMember::canModerateContent( $this->memberData, IPSMember::CONTENT_UNHIDE, $comment['comment_author_id'] ) ) ? TRUE : 'NO_PERMISSION';
			break;
		}
	}

	/**
	 * Returns remap keys (generic => local)
	 *
	 * @return	array
	 */
	public function remapKeys( $type='comment' )
	{
		return ( $type == 'comment' ) ? $this->_remap : $this->_parentRemap;
	}
	
	/**
	 * Fetch a comment by ID (no perm checks)
	 *
	 * @param	 int 		Comment ID
	 */
	public function fetchById( $commentId )
	{
		if( $this->useForums )
		{
			if ( ! isset( $this->_cache[ $commentId ] ) )
			{
				$comment = $this->DB->buildAndFetch( array( 'select'	=> 'pid as comment_id, author_name as comment_author, author_id as comment_user, post as comment_post, ip_address as comment_ip_address,
																			edit_time as comment_edit_date, post_date as comment_date, 0 as comment_record_id, 
																			CASE WHEN queued=0 THEN 1 ELSE ( CASE WHEN queued=2 THEN -1 ELSE 0 END ) END as comment_approved',
										 		 			'from'  => 'posts',
										 					'where'	=> 'pid=' . intval( $commentId ) ) );

				$this->_cache[ $comment['comment_id'] ] = $comment;
			}
			
			return $this->_cache[ $commentId ];
		}
		else
		{
			return parent::fetchById( $commentId );
		}
	}

	/**
	 * Fetch raw comments.  Overridden so we can parse attachments for the forums.
	 *
	 * @param	mixed		parent ID
	 * @param	array		Filters
	 * @return	array
	 */
	public function fetch( $parent, $filters=array() )
	{
		$comments	= parent::fetch( $parent, $filters );
		
		if( $this->useForums )
		{
			//-----------------------------------------
			// Fetch attachments, if necessary
			//-----------------------------------------
				
			if( $this->topic['topic_hasattach'] )
			{
				//-----------------------------------------
				// INIT. Yes it is
				//-----------------------------------------
				
				$postHTML = array();
				
				//-----------------------------------------
				// Separate out post content
				//-----------------------------------------
				
				foreach( $comments as $id => $post )
				{
					$postHTML[ $id ] = $post['comment']['comment_text'];
				}
				
				$this->lang->loadLanguageFile( array( 'public_topic' ), 'forums' );
				
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------
					
				if ( ! is_object( $this->class_attach ) )
				{	
					$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
					$this->class_attach		   =  new $classToLoad( $this->registry );
				}
				
				//-----------------------------------------
				// Not got permission to view downloads?
				//-----------------------------------------
				
				if ( $this->registry->permissions->check( 'download', $this->registry->class_forums->forum_by_id[ $this->topic['forum_id'] ] ) === FALSE )
				{
					$this->settings['show_img_upload'] =  0 ;
				}
				
				//-----------------------------------------
				// Continue...
				//-----------------------------------------
				
				$this->class_attach->type  = 'post';
				$this->class_attach->init();
	
				$attachHTML = $this->class_attach->renderAttachments( $postHTML, array_keys( $comments ), 'ccs_global' );
		
				/* Now parse back in the rendered posts */
				if( is_array($attachHTML) AND count($attachHTML) )
				{
					foreach( $attachHTML as $id => $data )
					{
						/* Get rid of any lingering attachment tags */
						if ( stristr( $data['html'], "[attachment=" ) )
						{
							$data['html'] = IPSText::stripAttachTag( $data['html'] );
						}
	
						$comments[ $id ]['comment']['comment_text']			= $data['html'] . ( $data['attachmentHtml'] ? '<br />' . $data['attachmentHtml'] : '' );
					}
				}
			}
		}
		
		return $comments;
	}

	/**
	 * Deletes a comment
	 *
	 * @param	int		Image ID of parent
	 * @param	mixed	Comment ID or array of comment IDs
	 * @param	array	Member Data of current member
	 * @reutrn	html
	 * EXCEPTIONS
	 * MISSING_DATA		Ids missing
	 * NO_PERMISSION	No permission
	 */
	public function delete( $parentId, $commentId, $memberData )
	{
		if( $this->useForums )
		{
			/* Init */
			if ( is_numeric( $memberData ) )
			{
				$memberData = IPSMember::load( $memberData, 'all' );
			}
			
			/* Check */
			if ( ! $memberData['member_id'] OR ! $parentId OR ! $commentId )
			{
				throw new Exception('MISSING_DATA');
			}

			/* Permission test */
			$can	= $this->can( 'moderate', array( 'comment_parent_id' => $parentId ) );
			
			if ( $can !== true )
			{
				throw new Exception( $can );
			}

			$cids		= array();
			$comments	= array();
			
			if( is_array($commentId) )
			{
				$this->DB->build( array(
										'select'	=> 'p.pid',
										'from'		=> array( 'posts' => 'p' ),
										'where'		=> 'p.pid IN (' . implode( ",", IPSLib::cleanIntArray( $commentId ) ) . ')',
										'add_join'	=> array(
															array(
																'select'	=> 'r.*',
																'from'		=> array( $this->database['database_database'] => 'r' ),
																'where'		=> 'r.record_topicid=p.topic_id',
																'type'		=> 'left',
																)
															)
								)		);
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$cids[]		= $r['pid'];
					$comments[]	= $r;
				}
			}
			else
			{
				$comment	= $this->DB->buildAndFetch( array(
																'select'	=> 'p.pid',
																'from'		=> array( 'posts' => 'p' ),
																'where'		=> 'p.pid=' . intval($commentId),
																'add_join'	=> array(
																					array(
																						'select'	=> 'r.*',
																						'from'		=> array( $this->database['database_database'] => 'r' ),
																						'where'		=> 'r.record_topicid=p.topic_id',
																						'type'		=> 'left',
																						)
																					)
														)		);

				$cids[]		= $comment['pid'];
			}

			/* Pre delete */
			$this->preDelete( $cids, $parentId );

			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
			$_topics		= new $classToLoad( $this->registry, $this->registry->ccsFunctions->getFieldsClass(), $this->registry->ccsFunctions->getCategoriesClass( $this->database ), $this->fields );
			
			if( count($comments) )
			{
				foreach( $comments as $comment )
				{
					$_topics->removeComment( $comment, $this->category, $this->database, $comment['pid'] );
				}
			}
			else
			{
				$_topics->removeComment( $comment, $this->category, $this->database, $comment['pid'] );
			}

			/* Post delete */
			$this->postDelete( $cids, $parentId );
			
			return true;
		}
		else
		{
			return parent::delete( $parentId, $commentId, $memberData );
		}
	}
	
	/**
	 * Toggles visbility a comment
	 *
	 * @param	string	on/off
	 * @param	int		Image ID of parent
	 * @param	mixed	Comment ID or array of comment IDs
	 * @param	array	Member Data of current member
	 * @reutrn	html
	 * EXCEPTIONS
	 * MISSING_DATA		Ids missing
	 * NO_PERMISSION	No permission
	 */
	public function visibility( $toggle, $parentId, $commentId, $memberData )
	{
		if( $this->useForums )
		{
			/* Init */
			if ( is_numeric( $memberData ) )
			{
				$memberData = IPSMember::load( $memberData, 'all' );
			}
			
			/* Check */
			if ( ! $memberData['member_id'] OR ! $parentId OR ! $commentId )
			{
				throw new Exception('MISSING_DATA');
			}

			/* Permission test */
			$can	= $this->can( 'visibility', array( 'comment_parent_id' => $parentId ) );
			
			if ( $can !== true )
			{
				throw new Exception( $can );
			}

			$cids		= array();
			$comments	= array();
			$record		= $this->fetchParent( $parentId );

			if( is_array($commentId) )
			{
				$this->DB->build( array(
										'select'	=> 'p.pid',
										'from'		=> array( 'posts' => 'p' ),
										'where'		=> 'p.pid IN (' . implode( ",", IPSLib::cleanIntArray( $commentId ) ) . ')',
										'add_join'	=> array(
															array(
																'select'	=> 'r.*',
																'from'		=> array( $this->database['database_database'] => 'r' ),
																'where'		=> 'r.record_topicid=p.topic_id',
																'type'		=> 'left',
																)
															)
								)		);
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$cids[]		= $r['pid'];
					$comments[]	= $r;
				}
			}
			else
			{
				$comment	= $this->DB->buildAndFetch( array(
																'select'	=> 'p.pid',
																'from'		=> array( 'posts' => 'p' ),
																'where'		=> 'p.pid=' . intval($commentId),
																'add_join'	=> array(
																					array(
																						'select'	=> 'r.*',
																						'from'		=> array( $this->database['database_database'] => 'r' ),
																						'where'		=> 'r.record_topicid=p.topic_id',
																						'type'		=> 'left',
																						)
																					)
														)		);

				$cids[]		= $comment['pid'];
			}

			/* Pre delete */
			$update	= $this->preVisibility( $toggle, $cids, $parentId, array() );

			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
			$_topics		= new $classToLoad( $this->registry, $this->registry->ccsFunctions->getFieldsClass(), $this->registry->ccsFunctions->getCategoriesClass( $this->database ), $this->fields );

			if( count($comments) )
			{
				foreach( $comments as $comment )
				{
					$_topics->toggleComment( $record, $this->category, $this->database, $comment['pid'], $toggle == 'on' ? 1 : 0 );
				}
			}
			else
			{
				$_topics->toggleComment( $record, $this->category, $this->database, $comment['pid'], $toggle == 'on' ? 1 : 0 );
			}

			$this->postVisibility( $toggle, $cids, $parentId );

			return true;
		}
		else
		{
			return parent::visibility( $toggle, $parentId, $commentId, $memberData );
		}
	}
	
	/**
	 * Unhide a comment
	 *
	 * @param	int			Parent ID
	 * @param	int|array	Comment IDs
	 * @param	int|array	Member Data
	 * @return	bool|string	TRUE on sucess, error string on error
	 */
	public function unhide( $parentId, $commentIds, $memberData )
	{
		if( $this->useForums )
		{
			/* Init */
			if ( is_numeric( $memberData ) )
			{
				$memberData = IPSMember::load( $memberData, 'all' );
			}
			
			/* Check */
			if ( !is_array( $commentIds ) )
			{
				$commentIds = array( $commentIds );
			}

			if ( ! $memberData['member_id'] OR ! $parentId )
			{
				return 'MISSING_DATA';
			}
			
			/* Permission Check */
			foreach( $commentIds as $k )
			{
				$permCheck = $this->can( 'unhide', array( 'comment_id' => $k, 'comment_parent_id' => $parentId ) );

				if ( $permCheck !== TRUE )
				{
					return $permCheck;
				}
			}
			
			/* Do it */
			$update	= $this->preVisibility( 1, $commentIds, $parentId, array() );

			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
			$_topics		= new $classToLoad( $this->registry, $this->registry->ccsFunctions->getFieldsClass(), $this->registry->ccsFunctions->getCategoriesClass( $this->database ), $this->fields );
			$record			= $this->fetchParent( $parentId );
			
			foreach( $commentIds as $_id )
			{
				$_topics->unhideComment( $record, $this->category, $this->database, $_id );
			}
			
			IPSDeleteLog::removeEntries( $commentIds, $this->whoAmI() );

			$this->postVisibility( 1, $commentIds, $parentId );

			return true;
		}
		else
		{
			return parent::unhide( $parentId, $commentIds, $memberData );
		}
	}
	
	/**
	 * Hide a comment
	 *
	 * @param	int			Parent ID
	 * @param	int|array	Comment IDs
	 * @param	string		Reason
	 * @param	int|array	Member Data
	 * @return	bool|string	TRUE on sucess, error string on error
	 */
	public function hide( $parentId, $commentIds, $reason, $memberData )
	{
		if( $this->useForums )
		{
			/* Init */
			if ( is_numeric( $memberData ) )
			{
				$memberData = IPSMember::load( $memberData, 'all' );
			}
			
			/* Check */
			if ( !is_array( $commentIds ) )
			{
				$commentIds = array( $commentIds );
			}

			if ( ! $memberData['member_id'] OR ! $parentId )
			{
				return 'MISSING_DATA';
			}
			
			/* Permission Check */
			foreach( $commentIds as $k )
			{
				$permCheck = $this->can( 'hide', array( 'comment_id' => $k, 'comment_parent_id' => $parentId ) );

				if ( $permCheck !== TRUE )
				{
					return $permCheck;
				}
			}
			
			/* Do it */
			$update	= $this->preVisibility( -1, $commentIds, $parentId, array() );

			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
			$_topics		= new $classToLoad( $this->registry, $this->registry->ccsFunctions->getFieldsClass(), $this->registry->ccsFunctions->getCategoriesClass( $this->database ), $this->fields );
			$record			= $this->fetchParent( $parentId );
			
			foreach( $commentIds as $_id )
			{
				$_topics->hideComment( $record, $this->category, $this->database, $_id, $reason );
				
				IPSDeleteLog::addEntry( $k, $this->whoAmI(), $reason, $memberData );
			}

			$this->postVisibility( -1, $commentIds, $parentId );

			return true;
		}
		else
		{
			return parent::hide( $parentId, $commentIds, $reason, $memberData );
		}
	}

	/**
	 * Sends "like" notifications
	 *
	 * @param	array	Array of GENERIC data (comment_xxxx)
	 * @param	string	Comment Text
	 * @return 	nowt lad
	 */
	public function sendNotifications( array $array, $comment )
	{
		$parent			= $this->fetchParent( $array['comment_parent_id'] );

		/* Send them if they are approved */
		if ( $array['comment_approved'] && $parent['record_approved'] )
		{
			$this->lang->loadLanguageFile( array( 'public_lang' ), 'ccs' );
			
			$this->registry->ccsFunctions->fixStrings( $this->database['database_id'] );

			//-----------------------------------------
			// Format title
			//-----------------------------------------

			$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();

			$_title	= $fieldsClass->getFieldValue( $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ], $parent, $this->fields[ str_replace( 'field_', '', $this->database['database_field_title'] ) ]['field_truncate'] );
	
			//-----------------------------------------
			// Send
			//-----------------------------------------
			
			try
			{
				$_author	= IPSMember::load( $array['comment_author_id'], 'basic' );

				require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
				$_like = classes_like::bootstrap( 'ccs', $this->database['database_database'] . '_records' );
				$_like->sendNotifications( $parent['primary_id_field'], array( 'immediate', 'offline' ), array(
																										'notification_key'		=> 'ccs_notifications',
																										'notification_url'		=> $this->registry->ccsFunctions->returnDatabaseUrl( $this->database['database_id'], 0, $parent, $array['comment_id'] ),
																										'email_template'		=> 'ccs_comment_notification',
																										'email_subject'			=> sprintf( $this->lang->words['subject__ccs_comment_notification'], 
																																			$this->registry->output->buildSEOUrl( 'showuser=' . $array['comment_author_id'], 'public', $_author['members_seo_name'], 'showuser' ),
																																			$array['comment_author_name'],
																																			$this->registry->ccsFunctions->returnDatabaseUrl( $this->database['database_id'], 0, $parent, $array['comment_id'] ),
																																			$_title
																																		),
																										'build_message_array'	=> array(
																																	'NAME'		=> '-member:members_display_name-',
																																	'POSTER'	=> $array['comment_author_name'],
																																	'LINK'		=> $this->registry->ccsFunctions->returnDatabaseUrl( $this->database['database_id'], 0, $parent, $array['comment_id'] ),
																																	'TITLE'		=> $_title,
																																	'TEXT'		=> $comment
																																		)
																								) 		);
			}
			catch( Exception $e )
			{
				
			}
		}
	}
}