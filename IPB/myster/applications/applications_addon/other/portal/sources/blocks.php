<?php
/*
+--------------------------------------------------------------------------
|   Portal 1.4.0
|   =============================================
|   by Michael John
|   Copyright 2011-2013 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
|   Based on IP.Board Portal by Invision Power Services
|   Website - http://www.invisionpower.com/
+--------------------------------------------------------------------------
*/

if( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit;
}

class portalBlockGateway
{
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $caches;
	protected $cache;    
    
	public function __construct( ipsRegistry $registry )
	{
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			= $this->registry->getClass('class_localization');
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
	}
    
 	public function show_newest_posts()
	{
		/* INIT */
		$forums    = array();        
		$forum_ids = array();
		$post_rows = array();
		$mem_ids   = array();
		$mem_data  = array();
        
        # Grab the forums we can use
 		foreach( explode( ',', $this->settings['portal_newest_posts_forums'] ) as $forum_id )
 		{
 			if( !$forum_id )
 			{
 				continue;
 			}

 			$forums[] = intval($forum_id);
 		}        
		
		/* Grab last X data */
		foreach ( $this->registry->class_forums->forum_by_id as $forumID => $forumData )
		{
			/* Allowing this forum? */
			if ( count($forums) && !in_array( $forumID, $forums ) )
			{
				continue;
			}
			
			/* Can we read? */
			if ( !$this->registry->permissions->check( 'read', $forumData ) )
			{
				continue;
			}

			/* Can read, but is it password protected, etc? */
			if ( ! $this->registry->class_forums->forumsCheckAccess( $forumID, 0, 'forum', array(), true ) )
			{
				continue;
			}

			if ( ! $forumData['can_view_others'] )
			{
				continue;
			}
			
			/* Still here? */
			$forum_ids[] = $forumID;
		}
		
		/* Query */
		if ( count( $forum_ids ) )
		{
			$this->DB->build( array( 'select' => 'p.pid, p.post_date, p.post, p.author_id',
									 'from'   => array( 'posts' => 'p' ),
									 'where'  => "t.approved=1 AND t.forum_id IN (" . implode( ",", $forum_ids ) . ") AND ".$this->registry->class_forums->fetchPostHiddenQuery('visible'),
									 'add_join' => array( 0 => array( 'select' => 't.tid, t.title_seo, t.title',
																	  'from'   => array( 'topics' => 't' ),
																	  'where'  => 't.tid=p.topic_id',
																	  'type'   => 'left' ) ),
									 'order'    => 'p.post_date DESC',
									 'limit'    => array( 0, $this->settings['portal_NewPostsNumber'] ),
							)	   );
			$this->DB->execute();
			
			while ( $row = $this->DB->fetch() )
			{
				/* Add to our array of member IDs */
				if ( !in_array( $row['author_id'], $mem_ids ) )
				{
					$mem_ids[] = $row['author_id'];
				}
				
				/* Clean up the post a bit */
				$row['post'] = strip_tags( IPSText::UNhtmlspecialchars( IPSText::getTextClass( 'bbcode' )->stripAllTags( $row['post'] ) ) );
				
				/* Add to the array */
				$post_rows[] = $row;
			}
		}
		
		/* Get the member info */
		if ( count( $mem_ids ) )
		{
			/* Load their data */
			$members = IPSMember::load( $mem_ids, 'all' );
			
			/* Loop and build display data */
			foreach ( $mem_ids as $mid )
			{
				$mem_data[$mid] = IPSMember::buildProfilePhoto( $members[$mid] );
			}
			
			/* Associate author to post */
			if ( count( $post_rows ) )
			{
				foreach ( $post_rows as $k => $v )
				{
					$post_rows[$k]['author'] = $mem_data[$v['author_id']];
				}
			}
		}
		
		/* Return the newest posts template */
		return $this->registry->output->getTemplate('portal')->newestPosts( $post_rows );
  	}    
    
	public function show_board_stats()
	{
		$this->lang->loadLanguageFile( array( 'public_boards' ), 'forums' );	   
       
		/* Load the board index lib */
		require_once( IPSLib::getAppDir( 'forums' ) . '/modules_public/forums/boards.php' );
		$statfunc = new public_forums_forums_boards();
		$statfunc->makeRegistryShortcuts( $this->registry );
		
		/* Active User details */
		$active = $statfunc->getActiveUserDetails();
		
		/* Sort out the Stats info */
		$stats_info = $statfunc->getTotalTextString();
		
		/* Build the full stats array */
		$stats = array_merge( $active, array( 'text'    => $this->lang->words['total_word_string'],
											  'posts'   => $statfunc->total_posts,
											  'active'  => $statfunc->users_online,
											  'members' => $statfunc->total_members,
											  'cut_off' => $this->settings['au_cutoff'],
											  'info'	=> $stats_info,
							)				);
       	
		/* Return the output */
		return $this->registry->getClass('output')->getTemplate('portal')->boardStats( $stats );
  	}    
    
 	public function show_newest_members()
	{
		/* INIT */
		$members = array();
		$mids    = array();
		$final   = array();
		$rank    = 0;
		
		/* Query for the newest members */
		$this->DB->build( array( 'select' => 'member_id',
								 'from'   => 'members',
								 'where'  => "member_banned=0 AND member_group_id NOT IN (" . $this->settings['portal_newest_members_exclude'] . ")",
								 'order'  => 'joined DESC',
								 'limit'  => array( 0, $this->settings['portal_newest_members_number'] ),
						)	   );
		$this->DB->execute();
		
		/* Got any results? */
		while ( $row = $this->DB->fetch() )
		{          
			$mids[] = $row['member_id'];
		}
		
		/* Load their data */
		$members = IPSMember::load( $mids, 'all' );
		
		/* Loop and build display data */
		foreach ( $mids as $mid )
		{
			$members[$mid]['rank'] = ++$rank;
			$final[]               = IPSMember::buildProfilePhoto( $members[ $mid ] );
		}
		
		/* Return the top posters template */
		return $this->registry->output->getTemplate('portal')->newestMembers( $final );
  	}     
    
	public function show_top_posters()
	{
		/* Init our members array */
		$members = array();
		$mids    = array();
		$final   = array();
		$rank    = 0;
        
		/* Query for the top posters */
		$this->DB->build( array( 'select' => 'member_id',
								 'from'   => 'members',
								 'where'  => "member_banned=0 AND members_display_name <> '' AND member_group_id NOT IN (" . $this->settings['portal_top_posters_exclude'] . ")",
								 'order'  => 'posts DESC',
								 'limit'  => array( 0, $this->settings['portal_top_posters_number'] ),
						)	   );
		$this->DB->execute();
		
		/* Got any results? */
		while ( $row = $this->DB->fetch() )
		{
			$mids[] = $row['member_id'];
		}
		
		/* Load their data */
		$members = IPSMember::load( $mids, 'all' );
		
		/* Loop and build display data */
		foreach ( $mids as $mid )
		{
			$members[$mid]['rank'] = ++$rank;
			$final[]               = IPSMember::buildProfilePhoto( $members[$mid] );
		}
		
		/* Return the top posters template */
		return $this->registry->output->getTemplate('portal')->topPosters( $final );
  	}        
    
	/**
	 * Show the recently started topic titles
	 *
	 * @return	string		HTML content to replace tag with
	 */
	public function latest_topics_sidebar()
	{
		$results	= array();
		$limit		= $this->settings['latest_topics_sidebar'] ? $this->settings['latest_topics_sidebar'] : 5;
		
		$results	= $this->registry->class_forums->hooks_recentTopics( $limit, false );

		return count($results) ? $this->registry->getClass('output')->getTemplate('portal')->latestPosts( $results ) : '';
	}
    
	/**
	 * Show the "news" articles
	 *
	 * @return	string		HTML content to replace tag with
	 */
	public function latest_topics_main()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------

 		$attach_pids	= array();
 		$attach_posts	= array();
 		$forums			= array();
 		$topics			= array();
 		$output			= array();
		$where_clause	= array();
 		$limit			= $this->settings['latest_topics_main'] ? intval( $this->settings['latest_topics_main'] ) : 3;
 		$posts			= intval( $this->memberData['posts'] );
        $page           = ( $this->request['page'] > 1 ) ? ( ( $this->request['page'] - 1 ) * $limit ) : 0;

 		//-----------------------------------------
    	// Grab articles new/recent in 1 bad ass query
    	//-----------------------------------------

 		foreach( explode( ',', $this->settings['portal_latest_topics_forums'] ) as $forum_id )
 		{
 			if( !$forum_id )
 			{
 				continue;
 			}

 			$forums[] = intval($forum_id);
 		}
		
		/* Loop through the forums and build a list of forums we're allowed access to */
		$forumIdsOk  = array();
	
		foreach( $this->registry->class_forums->forum_by_id as $id => $data )
		{
			/* Allowing this forum? */
			if ( count($forums) && !in_array( $id, $forums ) )
			{
				continue;
			}
			
			/* Can we read? */
			if ( ! $this->registry->permissions->check( 'read', $data ) )
			{
				continue;
			}

			/* Can read, but is it password protected, etc? */
			if ( ! $this->registry->class_forums->forumsCheckAccess( $id, 0, 'forum', array(), true ) )
			{
				continue;
			}

			if ( ! $data['can_view_others'] )
			{
				continue;
			}
			
			if ( $data['min_posts_view'] > $posts )
			{
				continue;
			}
            
			if ( $data['password'] != '' )
			{
				continue;
			}            

			$forumIdsOk[] = $id;
		}

		if( !count($forumIdsOk) )
		{
			return '';
		}

		//-----------------------------------------
		// Get topics
		//-----------------------------------------        
        
        # Setup our topic conditions
		$where_clause[]   = "t.forum_id IN (" . implode( ",", $forumIdsOk ) . ")";		
		$parseAttachments = false;
		$topics           = array();
		$topicCount       = 0;
        $defaultOrder     = ( $this->settings['portal_topics_order_by'] ) ? $this->settings['portal_topics_order_by'] : 'last_post';    
        
        # Skip pinned topics
        if( $this->settings['portal_exclude_pinned'] )
        {
            $where_clause[]   = "t.pinned=0";    
        } 
        
        # Topic sql query
		$this->DB->build( array( 
								'select'	=> 't.*',
								'from'		=> array( 'topics' => 't' ),
                                'order'     => "t.{$defaultOrder} DESC",
								'where'		=> "t.approved=1 AND t.state != 'link' AND " . implode( ' AND ', $where_clause ),
                                'limit'     => array( $page, $limit ),
								'add_join'	=> array(
													array( 
															'select'	=> 'p.*',
															'from'	=> array( 'posts' => 'p' ),
															'where'	=> 'p.pid=t.topic_firstpost',
															'type'	=> 'left'
														),
													array(
															'select'	=> 'f.id, f.name, f.name_seo, f.use_html',
															'from'		=> array( 'forums' => 'f' ),
															'where'		=> "f.id=t.forum_id",
															'type'		=> 'left',
														),
													array( 
															'select'	=> 'm.member_id, m.members_display_name, m.member_group_id, m.members_seo_name, m.mgroup_others, m.login_anonymous, m.last_visit, m.last_activity',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=p.author_id',
															'type'		=> 'left'
														),
													array( 
															'select'	=> 'pp.*',
															'from'		=> array( 'profile_portal' => 'pp' ),
															'where'		=> 'pp.pp_member_id=m.member_id',
															'type'		=> 'left'
														),
												
													)
					)		);
		
		$outer = $this->DB->execute();
 		
 		while( $row = $this->DB->fetch( $outer ) )
 		{ 			
 			$bottom_string		= "";
 			$read_more			= "";
 			$top_string			= "";
 			$got_these_attach	= 0;
 			
			if( $row['topic_hasattach'] )
			{
				$parseAttachments = true;
			}

			//-----------------------------------------
			// Parse the post
			//-----------------------------------------
			
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= $row['use_emo'];
			IPSText::getTextClass( 'bbcode' )->parse_html				= ( $row['use_html'] and $row['post_htmlstate'] ) ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $row['post_htmlstate'] == 2 ? 1 : 0;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];
			$row['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['post'] );
 			
 			//-----------------------------------------
 			// BASIC INFO
 			//-----------------------------------------
 			
 			$real_posts			= $row['posts'];
 			$row['posts']		= ipsRegistry::getClass('class_localization')->formatNumber(intval($row['posts']));

            $row	= IPSMember::buildDisplayData( $row );
            
			# Attachments?			
			if( $row['pid'] )
			{
				$attach_pids[ $row['pid'] ] = $row['pid'];
			}
			if ( IPSMember::checkPermissions('download', $row['forum_id'] ) === FALSE )
			{
				$this->settings[ 'show_img_upload'] =  0;
			} 
                        
            $row['share_links'] = IPSLib::shareLinks( $row['title'], array( 'url' => $this->registry->output->buildSEOUrl( 'showtopic=' . $row['tid'], 'publicNoSession', $row['title_seo'], 'showtopic' )  ) );
         
			$topics[] = $row;
		}
        
        # Pagination
        if( $this->settings['portal_topics_pagination'] )
        {
      		//$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'topics', 'where' => "approved=1 AND state != 'link' AND " . str_replace( "t.", "", implode( ' AND ', $where_clause ) ) ) );
    		
    		$pages = $this->registry->output->generatePagination( array( 'totalItems'		 => 25,
    																	 'itemsPerPage'		 => $limit,
    																	 'currentStartValue' => $page,
                                                                         'isPagesMode'		 => true,
                                                                         'seoTitle'			 => "app=portal",
                                                                         'seoTemplate'		 => "app=portal",
    																	 'baseUrl'			 => "app=portal",
    															)		);             
        }
        
        

 		$output = $this->registry->getClass('output')->getTemplate('portal')->articles( $topics, $pages );
 		
 		//-----------------------------------------
 		// Process Attachments
 		//-----------------------------------------
 		
 		if ( $parseAttachments AND count( $attach_pids ) )
 		{
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------
				
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach                  = new $classToLoad( $this->registry );
				
				$this->class_attach->attach_post_key = '';

				ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_topic' ), 'forums' );
			}
			
			$this->class_attach->attach_post_key	=  '';
			$this->class_attach->type				= 'post';
			$this->class_attach->init();
		
			$output = $this->class_attach->renderAttachments( $output, $attach_pids );
			$output	= $output[0]['html'];
 		}
 		
 		return $output;
 	}        
    
	public function portal_sitenav()
	{
 		if ( ! $this->settings['portal_show_nav'] )
 		{
 			return;
 		}
 		
 		$links		= array();
 		$raw_nav	= $this->settings['portal_nav'];
 		
 		foreach( explode( "\n", $raw_nav ) as $l )
 		{
 			$l = str_replace( "&#039;", "'", $l );
 			$l = str_replace( "&quot;", '"', $l );
 			$l = str_replace( '{board_url}', $this->settings['base_url'], $l );
 			
 			preg_match( "#^(.+?)\[(.+?)\]$#is", trim($l), $matches );
 			
 			$matches[1] = trim($matches[1]);
 			$matches[2] = trim($matches[2]);
 			
 			if ( $matches[1] and $matches[2] )
 			{
	 			$matches[1] = str_replace( '&', '&amp;', str_replace( '&amp;', '&', $matches[1] ) );
	 			
	 			$links[] = $matches;
 			}
 		}
 		
 		if( !count($links) )
 		{
 			return;
 		}

 		return $this->registry->getClass('output')->getTemplate('portal')->siteNavigation( $links );
  	}
  	
	/**
	 * Show the affiliates block
	 *
	 * @return	string		HTML content to replace tag with
	 */
	public function portal_affiliates()
	{
        # Bye Bye
 		return;
 	}
    
	public function online_users_show()
	{
		//-----------------------------------------
		// Get the users from the DB
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir( 'forums', 'forums' ) . '/boards.php', 'public_forums_forums_boards' );
		$boards	= new $classToLoad();
		$boards->makeRegistryShortcuts( $this->registry );
		
		$active				= $boards->getActiveUserDetails();
		$active['visitors']	= $active['GUESTS']  + $active['ANON'];
		$active['members']	= $active['MEMBERS'];

 		return $this->registry->getClass('output')->getTemplate('portal')->onlineUsers( $active );
 	}    
    
	public function portal_show_poll()
	{
 		if ( ! $this->settings['portal_poll_url'] )
 		{
 		     return;	
 		}
        
 		//-----------------------------------------
		// Get the topic ID of the entered URL
		//-----------------------------------------
		
		/* Friendly URL */
		if( $this->settings['use_friendly_urls'] )
		{
			preg_match( "#/topic/(\d+)(.*?)/#", $this->settings['portal_poll_url'], $match );
			$tid = intval( trim( $match[1] ) );
		}
		/* Normal URL */
		else
		{
			preg_match( "/(\?|&amp;)(t|showtopic)=(\d+)($|&amp;)/", $this->settings['portal_poll_url'], $match );
			$tid = intval( trim( $match[3] ) );
		}
		
		if ( !$tid )
		{
			return;
		}
        
        $this->request['t'] = $tid;
        
		$this->registry->class_localization->loadLanguageFile( array( 'public_boards', 'public_topic' ), 'forums' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_editors' ), 'core' );

 		//-----------------------------------------
		// Load forum class
		//-----------------------------------------

		if( !$this->registry->isClassLoaded('class_forums') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
			$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
			
			$this->registry->getClass('class_forums')->strip_invisible = 1;
			$this->registry->getClass('class_forums')->forumsInit();
    	}

 		//-----------------------------------------
		// Load topic class
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('topics') )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/topics.php", 'app_forums_classes_topics', 'forums' );
			$this->registry->setClass( 'topics', new $classToLoad( $this->registry ) );
		}
        		
		try
		{
			$this->registry->getClass('topics')->autoPopulate();
		}
		catch( Exception $crowdCheers )
		{
            return;  
		}        
        
 		//-----------------------------------------
		// We need to get the poll function
		//-----------------------------------------        
        
		$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir( 'forums', 'forums' ) . '/topics.php', 'public_forums_forums_topics', 'forums' );
		$topic = new $classToLoad();
        $topic->forumClass = $this->registry->getClass('class_forums');        
		$topic->makeRegistryShortcuts( $this->registry );

        # Move this over to use above class rather then query.
		$topic->topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where'  => "tid=" . $tid ) );
		$topic->forum = ipsRegistry::getClass('class_forums')->forum_by_id[ $topic->topic['forum_id'] ];
	
		$this->request['f'] = $topic->forum['id'] ;
        
 		//-----------------------------------------
		// If good, display poll
		//----------------------------------------- 
        		
		if ( $topic->topic['poll_state'] )
		{
 			return $this->registry->getClass('output')->getTemplate('portal')->pollWrapper( $topic->_generatePollOutput(), $topic->topic );
 		}
 		else
 		{
 			return;
 		} 
 	}    
}
?>