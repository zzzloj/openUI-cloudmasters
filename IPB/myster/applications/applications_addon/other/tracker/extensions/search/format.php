<?php

/**
* Tracker 2.1.0
* 
* Last Updated: $Date: 2012-11-24 20:31:32 +0000 (Sat, 24 Nov 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	PublicExtensions
* @link			http://ipbtracker.com
* @version		$Revision: 1393 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_format_tracker extends search_format
{
	/**
	 * Constructor
	 */
	public function __construct( ipsRegistry $registry )
	{
		if ( ! $registry->isClassLoaded( 'tracker' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'tracker' ) . "/sources/classes/library.php", 'tracker_core_library', 'tracker' );
			$this->tracker = new $classToLoad();
			$this->tracker->execute( $registry );
	
			$registry->setClass( 'tracker', $this->tracker );
		}

		$registry->class_localization->loadLanguageFile( array( 'public_search' ), 'tracker' );
		
		parent::__construct( $registry );
		
		/* Set up wrapper */
		$this->templates = array( 'group' => 'tracker_search', 'template' => 'searchResultsAsForum' );
	}
	
	/**
	 * Parse search results
	 *
	 * @access	private
	 * @param	array 	$r				Search result
	 * @return	array 	$html			Blocks of HTML
	 */
	public function parseAndFetchHtmlBlocks( $rows )
	{
		/* Forum stuff */
		$sub               = false;
		$isVnc             = false;
		$search_term	   = IPSSearchRegistry::get('in.clean_search_term');
		$onlyPosts		   = IPSSearchRegistry::get('opt.onlySearchPosts');
		$onlyTitles		   = IPSSearchRegistry::get('display.onlyTitles');
		$noPostPreview	   = IPSSearchRegistry::get('opt.noPostPreview');
		$results		   = array();
		$attachPids		   = array();
		$results		   = array();
		
		/* loop and process */
		foreach( $rows as $id => $data )
		{	
			/* Reset */
			$pages = 0;
			
			/* Set up project */
			$project = $this->registry->tracker->projects()->getProject( $data['project_id'] );
			
			/* Indent */
			$indent = ( $this->last_topic == $data['issue_id'] );
	
			$this->last_topic = $data['issue_id'];
	
			/* Various data */
			$data	= $this->registry->tracker->issues()->parseRow( $data, $project );

			/* For-matt some stuffs */
			if ( ! $data['cache_content'] )
			{
				IPSText::getTextClass( 'bbcode' )->parse_smilies			= $data['use_emo'];
				IPSText::getTextClass( 'bbcode' )->parse_html				= ( $forum['use_html'] and $this->caches['group_cache'][ $data['member_group_id'] ]['g_dohtml'] and $data['post_htmlstate'] ) ? 1 : 0;
				IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $data['post_htmlstate'] == 2 ? 1 : 0;
				IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $project['use_ibc'];
				IPSText::getTextClass( 'bbcode' )->parsing_section			= 'issues';
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $data['member_group_id'];
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $data['mgroup_others'];
			}
			else
			{
				$data['post'] = '<!--cached-' . gmdate( 'r', $data['cache_updated'] ) . '-->' . $data['cache_content'];
			}
			
			$data['post'] = IPSText::searchHighlight( IPSText::getTextClass( 'bbcode' )->preDisplayParse( $data['post'] ), $search_term );
			
			/* Has attachments */
			if ( $data['hasattach'] )
			{
				$attachPids[ $data['pid'] ] = $data['post'];
			}
			
			$rows[ $id ] = $data;
		}
		
		/* Attachments */
		if ( count( $attachPids ) )
		{
			/* Load attachments class */
			if ( ! is_object( $this->class_attach ) )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach		   =  new $classToLoad( $this->registry );
				$this->class_attach->type  = 'tracker';
				$this->class_attach->init();
			}
			
			$attachHTML = $this->class_attach->renderAttachments( array( $data['pid'] => $data['post'] ), array_keys( $attachPids ) );
	
			/* Now parse back in the rendered posts */
			if( is_array($attachHTML) AND count($attachHTML) )
			{
				foreach( $attachHTML as $id => $_data )
				{
					/* Get rid of any lingering attachment tags */
					if ( stristr( $_data['html'], "[attachment=" ) )
					{
						$_data['html'] = IPSText::stripAttachTag( $_data['html'] );
					}
					
					$rows[ $id ]['post']           = $_data['html'];
					$rows[ $id ]['attachmentHtml'] = $_data['attachmentHtml'];
				}
			}
		}

		/* Go through and build HTML */
		foreach( $rows as $id => $data )
		{
			/* Format content */
			list( $html, $sub ) = $this->formatContent( $data );
			
			$results[ $id ] = array( 'html' => $html, 'app' => $data['app'], 'type' => $data['type'], 'sub' => $sub );
		}
		
		return $results;
	}

	/**
	 * Return the output for the followed content results
	 *
	 * @param	array 	$results	Array of results to show
	 * @param	array 	$followData	Meta data from follow/like system
	 * @return	@e string
	 */
	public function parseFollowedContentOutput( $results, $followData )
	{
		/* Issues? */
		if( IPSSearchRegistry::get('in.followContentType') == 'issues' )
		{
			return $this->registry->output->getTemplate('tracker_search')->searchResultsAsForum( $this->parseAndFetchHtmlBlocks( $this->processFollowedResults( $results, $followData ) ) );
		}
		/* Or Projects? */
		else
		{
			$projects	= array();
			$member_ids	= array();

			if( count($results) )
			{
				/* Get project data */
				foreach( $results as $result )
				{
					$projects[ $result ]				= $this->registry->tracker->projects()->getProject($result);
					$projects[ $result ]['_total']		= $this->registry->tracker->projects()->getIssueCount( $result );
					$projects[ $result ]['_replies']	= $this->registry->tracker->projects()->getPostCount( $result );
	
					if( $projects[ $result ]['user']['member_id'] )
					{
						$member_ids[ $result ]	= $projects[ $result ]['user']['member_id'];
					}
				}

				/* Merge in follow data */
				foreach( $followData as $_follow )
				{
					$projects[ $_follow['like_rel_id'] ]['_followData']	= $_follow;
				}

				/* And get latest member info */
				if( count($member_ids) )
				{
					$_members	= IPSMember::load( array_unique($member_ids), 'members,profile_portal' );

					foreach( $member_ids as $projectId => $memberId )
					{
						$_member	= $_members[ $memberId ];

						if( $_member['member_id'] )
						{
							$_member	= IPSMember::buildDisplayData( $_member, array( 'reputation' => 0, 'warn' => 0 ) );

							$projects[ $projectId ]	= array_merge( $_member, $projects[ $projectId ] );
						}
					}
				}
			}

			return $this->registry->output->getTemplate('tracker_search')->followedContentProjectsWrapperProjects( $projects );
		}
	}
	
	/**
	 * Formats the forum search result for display
	 *
	 * @access	public
	 * @param	array   $search_row		Array of data from search_index
	 * @return	mixed	Formatted content, ready for display, or array containing a $sub section flag, and content
	 **/
	public function formatContent( $data )
	{
		$this->registry->tracker->searchFields = $this->registry->tracker->fields()->display( array(), 'project' );
		
		// Fields setup
		$this->registry->tracker->fields()->initialize( $data['project_id'] );

		/* Forum Breadcrum */
		$data['_forum_trail'] = $this->registry->getClass( 'class_forums' )->forumsBreadcrumbNav( $data['forum_id'] );

		/* Is it read?  We don't support last_vote in search. */
		$is_read	= $this->registry->getClass( 'classItemMarking' )->isRead( array( 'forumID' => $data['project_id'], 'itemID' => $data['issue_id'], 'itemLastUpdate' => $data['lastupdate'] ? $data['lastupdate'] : $data['updated'] ), 'tracker' );

		/* Has posted dot */
		$show_dots = ( $this->settings['show_user_posted'] AND $this->memberData['member_id'] AND  $data['_hasPosted'] ) ? 1 : 0;

		/* Icon */
		$data['_icon']  = $this->registry->tracker->issues()->folderIcon( $data, $show_dots, $is_read );

		if ( !$is_read )
		{
			$data['_unreadUrl'] = $this->registry->output->buildSEOUrl( 'app=tracker&amp;showissue=' . $data['issue_id'] . '&amp;view=getunread', 'public', $data['title_seo'], 'showissue' );
		}

		$data['_isRead'] 	= $is_read;
		$data['project']	= $this->registry->tracker->projects()->getProject($data['project_id']);
		$data['fields']		= $this->registry->tracker->fields()->display( $data, 'project' );
		
		/* Display type */
		return array( $this->registry->getClass( 'output' )->getTemplate( 'tracker_search' )->issuePostSearchResultAsProject( $data, 1, 0 ) );
	}

	/**
	 * Formats / grabs extra data for results
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @param	array 	$ids			Ids
	 * @param	array	$followData		Retrieve the follow meta data
	 * @return array
	 */
	public function processFollowedResults( $ids, $followData=array() )
	{
		/* Topics? */
		if( IPSSearchRegistry::get('in.followContentType') == 'issues' )
		{
		//	IPSSearchRegistry::set('set.returnType', 'iid' );
			return $this->processResults( $ids, $followData );
		}

		return $ids;
	}

	/**
	 * Formats / grabs extra data for results
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @access public
	 * @return array
	 */
	public function processResults( $ids, $followData=array() )
	{
		/* INIT */
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$content_title_only = IPSSearchRegistry::get('opt.searchTitleOnly');
		$onlyPosts          = IPSSearchRegistry::get('opt.onlySearchPosts');
		$order_dir 			= ( $sort_order == 'asc' ) ? 'asc' : 'desc';
		$_post_joins		= array();
		$members			= array();
		$results			= array();
		$topicIds			= array();
		$dots				= array();
		$sortKey			= '';
		$sortType			= '';
		$_sdissue_ids			= array();
		$_sdPids			= array();
		
		/* Got some? */
		if ( count( $ids ) )
		{
			/* Cache? */
			if ( IPSContentCache::isEnabled() )
			{
				if ( IPSContentCache::fetchSettingValue('post') )
				{
					$_post_joins[] = IPSContentCache::join( 'post', 'p.pid' );
				}
				
				if ( IPSContentCache::fetchSettingValue('sig') )
				{
					$_post_joins[] = IPSContentCache::join( 'sig' , 'p.author_id', 'ccb', 'left', 'ccb.cache_content as cache_content_sig, ccb.cache_updated as cache_updated_sig' );
				}
			} 
			
			/* Sorting */
			switch( $sort_by )
			{
				default:
				case 'date':
					$sortKey  = 'last_post';
					$sortType = 'numerical';
				break;
				case 'title':
					$sortKey  = 'title';
					$sortType = 'string';
				break;
				case 'posts':
					$sortKey  = 'posts';
					$sortType = 'numerical';
				break;
			}

			/* Set vars */
			IPSSearch::$ask = $sortKey;
			IPSSearch::$aso = strtolower( $order_dir );
			IPSSearch::$ast = $sortType;
			
			/* If we are search in titles only, then the ID array will be issue_ids */
			if( $content_title_only )
			{
				$k = 'issue_id';
				
				$this->DB->build( array( 
									'select'   => "t.*",
									'from'	   => array( 'tracker_issues' => 't' ),
		 							'where'	   => 't.issue_id IN( ' . implode( ',', $ids ) . ')',
									'add_join' => array_merge( array( array( 'select'	=> 'p.*',
																			 'from'		=> array( 'tracker_posts' => 'p' ),
															 				 'where'	=> 'p.issue_id=t.issue_id AND p.new_issue=1',
															 				 'type'		=> 'left' ),
																	  array( 'select'	=> 'm.member_id, m.members_display_name, m.members_seo_name',
																			 'from'		=> array( 'members' => 'm' ),
															 				 'where'	=> 'm.member_id=p.author_id',
															 				 'type'		=> 'left' ) ), $_post_joins ) ) );
			}
			/* Otherwise, it's PIDs */
			else
			{
				$k = 'pid';
				
				$this->DB->build( array( 
									'select'   => "p.*",
									'from'	   => array( 'tracker_posts' => 'p' ),
		 							'where'	   => 'p.pid IN( ' . implode( ',', $ids ) . ')',
									'add_join' => array_merge( array( array( 'select'	=> 't.*',
																			 'from'		=> array( 'tracker_issues' => 't' ),
												 							 'where'	=> 't.issue_id=p.issue_id',
												 							 'type'		=> 'left' ),
																	  array( 'select'	=> 'm.member_id, m.members_display_name, m.members_seo_name',
																			 'from'		=> array( 'members' => 'm' ),
												 							 'where'	=> 'm.member_id=p.author_id',
												 							 'type'		=> 'left' ) ), $_post_joins ) ) );
															
			}
			
			/* Grab data */
			$this->DB->execute();
			
			/* Grab the results */
			while( $row = $this->DB->fetch() )
			{
				$_rows[ $row[ $k ] ] = $row;
			}

			/* Get the 'follow' meta data? */
			if( count($followData) )
			{
				$followData = classes_like_meta::get( $followData );

				/* Merge the data from the follow class into the results */
				foreach( $followData as $_formatted )
				{
					$_rows[ $_formatted['like_rel_id'] ]['_followData']	= $_formatted;
				}
			}
			
			/* Sort */
			if ( count( $_rows ) )
			{
				usort( $_rows, array("IPSSearch", "usort") );
		
				foreach( $_rows as $id => $row )
				{
					/* Prevent member from stepping on it */
					$row['issue_title'] = $row['title'];
					
					/* Got author but no member data? */
					if ( ! empty( $row['author_id'] ) )
					{
						$members[ $row['author_id'] ] = $row['author_id'];
					}
					
					/* Topic ids? */
					if ( ! empty( $row['issue_id'] ) )
					{
						$topicIds[ $row['issue_id'] ] = $row['issue_id'];
					}
				
					$row['cleanSearchTerm'] = urlencode($search_term);
					
					if ( $row['private'] )
					{
						if ( $this->member->tracker['is_super'] )
						{
							$row['_isVisible'] = true;
						}
						else
						{
							$row['_isHidden'] = true;
						}
					}

					$results[ $row['pid'] ] = $this->genericizeResults( $row );
				}
			}
			
			/* Need to load members? */
			if ( count( $members ) )
			{
				$mems = IPSMember::load( $members, 'all' );
				
				foreach( $results as $id => $r )
				{
					if ( ! empty( $r['author_id'] ) AND isset( $mems[ $r['author_id'] ] ) )
					{
						$mems[ $r['author_id'] ]['m_posts'] = $mems[ $r['author_id'] ]['posts'];
						//unset( $mems[ $r['author_id'] ]['posts'] );
						unset( $mems[ $r['author_id'] ]['last_post'] );
						
						if ( isset( $r['cache_content_sig'] ) )
						{ 
							$mems[ $r['author_id'] ]['cache_content'] = $r['cache_content_sig'];
							$mems[ $r['author_id'] ]['cache_updated'] = $r['cache_updated_sig'];
						}
						
						$_mem = IPSMember::buildDisplayData( $mems[ $r['author_id'] ], array( 'signature' => 1 ) );
						
						unset( $_mem['cache_content'], $_mem['cache_updated'] );
						
						$results[ $id ]['_realPosts']	= $results[ $id ]['posts'];
						$results[ $id ]					= array_merge( $results[ $id ], $_mem );
						$results[ $id ]['posts']		= $results[ $id ]['_realPosts'];
					}
				}
			}
			
			/* Generate 'dot' folder icon */
			if ( $this->settings['show_user_posted'] AND count( $topicIds ) )
			{
				$this->DB->build( array( 'select' => 'author_id, issue_id',
										 'from'   => 'tracker_posts',
										 'where'  => 'author_id=' . $this->memberData['member_id'] . ' AND issue_id IN(' . implode( ',', $topicIds ) . ')' ) );
										  
				$this->DB->execute();
				
				while( $p = $this->DB->fetch() )
				{
					$dots[ $p['issue_id'] ] = 1;
				}
				
				/* Merge into results */
				foreach( $results as $id => $r )
				{
					if ( isset( $dots[ $r['issue_id'] ] ) )
					{
						$results[ $id ]['_hasPosted'] = 1;
					}
				}
			}
		}
		
		return $results;
	}
	
	/**
	 * Reassigns fields in a generic way for results output
	 *
	 * @param  array  $r
	 * @return array
	 **/
	public function genericizeResults( $r )
	{
		$r['app']					= 'forums';
		$r['content']				= $r['post'];
		$r['content_title']			= $r['title'];
		$r['updated']				= $r['post_date'];
		$r['lastupdate']			= $r['last_post'];
		$r['type_2']				= 'topic';
		$r['type_id_2']				= $r['issue_id'];
		$r['misc']					= $r['pid'];

		return $r;
	}

}

?>