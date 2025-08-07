<?php

/**
* Tracker 2.1.0
* 
* List Projects module
* Last Updated: $Date: 2012-10-31 23:00:03 +0000 (Wed, 31 Oct 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	PublicModules
* @link			http://ipbtracker.com
* @version		$Revision: 1390 $
*/

if ( ! defined( 'IN_IPB' ) )
{ 
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_tracker_projects_issues extends iptCommand
{
	// Store issue, and project, and other information
	protected $issue	= array();
	protected $project	= array();
	protected $other	= array();

	
	/**
	 * Can we edit at least one post?
	 * 
	 * @var		boolean
	 */
 	protected $_canEditAPost	= false;
 	
 	
	/**
	 * Mod actions
	 *
	 * @var		array
	 */
	protected $mod_action 		= array(
									'CLOSE_ISSUE'   => '00',
									'OPEN_ISSUE'	=> '01',
									'MOVE_ISSUE'	=> '02',
									'DELETE_ISSUE'  => '03',
									'EDIT_ISSUE'	=> '05'
								);
	/**
	 * Mod Remap MT
	 * 
	 * @var array
	 */
	protected $modRemap			= array(
									'can_move'			=> 'MOVE_ISSUE',
									'can_lock'			=> 'CLOSE_ISSUE',
									'can_unlock'		=> 'OPEN_ISSUE',
									'can_del_issues'	=> 'DELETE_ISSUE',
									'can_edit_titles'	=> 'EDIT_ISSUE'
								);

	
	// We need to check attachments as we use weird IDs (sorry!)
	// Therefore, we keep a running total of which posts are done.
	protected $done				= array();
	protected $attachHTML		= array();
	
	/**
	* Class entry point
	*
	* @access	public
	* @param	object		Registry reference
	* @return	void		[Outputs to screen/redirects]
	*/	
	public function doExecute( ipsRegistry $registry )
	{
		// Add in our global navigation
		$this->tracker->addGlobalNavigation();

		// Load some language files
		$this->registry->class_localization->loadLanguageFile( array( 'public_project', 'public_issue' ), 'tracker' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_editors' ), 'core' );

		$this->loadIssue();

		/* Private issue */
		switch( $this->request['do'] )
		{
			case 'private':
				$this->_changeIssueVisibility();
				break;
			case 'public':
				$this->_changeIssueVisibility('public');
				break;
			case 'rate':
				$this->_issueRate();
				break;
			default:break;
		}

		$this->processIssue();

		// Perms
		$this->member->trackerProjectPerms	= $this->member->tracker[$this->project['project_id']];
		
		// For Brandon, *sigh*
		$this->tracker->fields()->projectFilterSetup( IPSCookie::get( "tracker_" . $this->project['project_id'] ) );
		$this->tracker->issues()->extra		= $this->tracker->fields()->getProjectFilterURLBits();
		
		// Unread issues?
		$this->project['_hasUnreadIssues']	= $this->tracker->projects()->getHasUnread( $this->project['project_id'] );

		//-----------------------------------------
		// Clean up
		//-----------------------------------------
		unset( $save_array, $read_issues_tid );

		// Support the available views
		if ( isset($this->request['view']) )
		{
			if ($this->request['view'] == 'getlastpost')
			{
				// Simply fire off last post
				$this->returnLastPost();
			}
			else if ($this->request['view'] == 'nextunread')
			{
				$iid   = $this->registry->tracker->issues()->getNextUnreadIssueId();
				
				if ( $iid )
				{
					$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=tracker&amp;showissue=" . $iid['issue_id'], $iid['title_seo'] );
				}
				else
				{
					$this->registry->output->showError( $this->lang->words['issues_none_newer'], null, null, null, 404 );
				}
			}
			else if ($this->request['view'] == 'getnewpost')
			{
				// When did we last view this issue?
				$lastViewed = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $this->project['project_id'], 'itemID' => $this->issue['issue_id'] ), 'tracker' );

				// Grab the post at newest point
				$this->DB->build(
					array(
						'select' => 'MIN(pid) as pid',
						'from'   => 'tracker_posts',
						'where'  => 'queued=0 AND issue_id=' . $this->issue['issue_id'] . ' AND post_date > ' . intval($last_time),
						'limit'  => array( 0,1 )
					)
				);
				$this->DB->execute();

				$post = $this->DB->fetch();

				if ( $post['pid'] )
				{
					$pid = '&amp;gopid=' . $post['pid'] . '&amp;#entry' . $post['pid'];

					$query_extra = $this->memberData['is_mod'] ? '' : ' AND queued=0';

					$this->DB->build(
						array(
							'select' => 'COUNT(*) as posts',
							'from'   => 'tracker_posts',
							'where'  => "issue_id=".$this->issue['issue_id']." AND pid <= ".$post['pid'] . $query_extra,
						)
					);
					$this->DB->execute();

					$cposts = $this->DB->fetch();

					if ( (($cposts['posts']) % $this->settings['display_max_posts']) == 0 )
					{
						$pages = ($cposts['posts']) / $this->settings['display_max_posts'];
					}
					else
					{
						$number = ( ($cposts['posts']) / $this->settings['display_max_posts'] );
						$pages = ceil( $number);
					}

					$st = ($pages - 1) * $this->settings['display_max_posts'];

					if( $this->settings['post_order_sort'] == 'desc' )
					{
						$st = (ceil(($this->topic['posts']/$this->settings['display_max_posts'])) - $pages) * $this->settings['display_max_posts'];
					}

					$this->registry->output->silentRedirect($this->settings['base_url']."app=tracker&amp;showissue=".$this->issue['issue_id']."&st={$st}".$pid, $this->issue['title_seo']);
				}
				else
				{
					// Newest post is last post, so go there
					$this->returnLastPost();
				}
			}
			else if ($this->request['view'] == 'findpost')
			{
				$pid = intval($this->request['p']);

				if ( $pid > 0 )
				{
					$sort_value = $pid;
					$sort_field = 'post_date';

					if($sort_field == 'post_date')
					{
						$date = $this->DB->buildAndFetch(
							array(
								'select' => 'post_date',
								'from'	 => 'tracker_posts',
								'where'	 => 'pid=' . $pid,
							)
						);

						$sort_value = intval($date['post_date']);
					}

					$this->DB->build(
						array(
							'select' => 'COUNT(*) as posts',
							'from'   => 'tracker_posts',
							'where'  => "issue_id=".$this->issue['issue_id']." AND {$sort_field} <=" . $sort_value,
						)
					);
					$this->DB->execute();

					$cposts = $this->DB->fetch();

					if ( (($cposts['posts']) % $this->settings['tracker_comments_perpage']) == 0 )
					{
						$pages = ($cposts['posts']) / $this->settings['tracker_comments_perpage'];
					}
					else
					{
						$number = ( ($cposts['posts']) / $this->settings['tracker_comments_perpage'] );
						$pages = ceil($number);
					}

					$st = ($pages - 1) * $this->settings['tracker_comments_perpage'];

					$this->registry->output->silentRedirect($this->settings['base_url'] . "app=tracker&amp;showissue=".$this->issue['issue_id']."&st={$st}&gopid={$pid}"."&#entry".$pid, $this->issue['title_seo']);
				}
				else
				{
					// Didn't succeed, send last post
					$this->returnLastPost();
				}
			}
			else
			{
				// Didn't succeed, send last post
				$this->returnLastPost();
			}
		}
		
		//-----------------------------------------
		// UPDATE TOPIC?
		//-----------------------------------------

		if ( !isset($this->request['b']) OR !$this->request['b'] )
		{
			if ( ! $this->issue['firstpost'] )
			{
				//--------------------------------------
				// No first topic set - old topic, update
				//--------------------------------------

				$this->DB->build(
					array (
						'select' => 'pid',
						'from'   => 'tracker_posts',
						'where'  => 'issue_id='.$this->issue['issue_id'].' AND new_issue=1'
					)
				);
				$this->DB->execute();

				$post = $this->DB->fetch();

				if ( ! $post['pid'] )
				{
					//-----------------------------------------
					// Get first post info
					//-----------------------------------------

					$this->DB->build(
						array(
							'select' => 'pid',
							'from'   => 'tracker_posts',
							'where'  => "issue_id={$this->issue['issue_id']}",
							'order'  => 'pid ASC',
							'limit'  => array(0,1)
						)
					);
					$this->DB->execute();

					$first_post  = $this->DB->fetch();
					$post['pid'] = $first_post['pid'];
				}

				// Set our new first post in the database
				if ( $post['pid'] )
				{
					$this->DB->update( 'tracker_issues', array( 'firstpost' => $post['pid'] ), 'issue_id=' . $this->issue['issue_id'] );
				}

				// Reload issue
				$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=tracker&amp;showissue=" . $this->issue['issue_id'] . "&b=1&st={$this->request['st']}&p={$this->request['p']}&#entry" . $this->request['p'], $this->issue['title_seo']);
			}
		}

		$this->issue['title'] = str_replace( '[attachmentid=', '&#91;attachmentid=', $this->issue['title'] );

		// Get buttons
		$this->issue['reply_button'] = $this->_getReplyButtonData();

		// Render Issue
		$this->displayIssue();

		$this->tracker->pageTitle = $this->issue['title'] . ' - ' . $this->settings['board_name'];
		$this->tracker->sendOutput();
	}
	
	protected function _issueRate()
	{
		if ( $this->request['type'] != 'suggestion' && $this->request['type'] != 'issue' )
		{
			return false;
		}
		
		$type = $this->request['type'];
		
		// We only allow 1 or -1, so, own up, who's trying to mess the system?
		if ( intval( $this->request['score'] ) != 1 && intval( $this->request['score'] ) != -1 )
		{
			return false;
		}
		
		$score = intval( $this->request['score'] );
		
		// Have we voted before?
		$votedBefore = $this->DB->buildAndFetch(
			array(
				'select'	=> 'rating_id, score',
				'from'		=> 'tracker_ratings',
				'where'		=> "type='{$type}' AND member_id=" . $this->memberData['member_id'] . " AND issue_id=" . $this->issue['issue_id']
			)
		);
		
		if ( $votedBefore['rating_id'] )
		{
			// Check that we can vote again
			if ( $votedBefore['score'] == $score )
			{
				// Remove old vote
				$this->DB->delete( 'tracker_ratings', 'rating_id=' . $votedBefore['rating_id'] );
				
				$weHaveRemoved = 1;
			}
			else
			{
				$weHaveVotedBefore = 1;
				
				$this->DB->update( 'tracker_ratings',
					array(
						'score'	=> $score
					),
					"rating_id=" . $votedBefore['rating_id']
				);
			}
		}
		else
		{
			$this->DB->insert( 'tracker_ratings',
				array(
					'member_id'	=> $this->memberData['member_id'],
					'issue_id'	=> $this->issue['issue_id'],
					'type'		=> $type,
					'score'		=> $score
				)
			);
		}
		
		switch( $type )
		{
			case 'issue':
				$short = 'repro';
				break;
				
			case 'suggestion':
				$short = 'sug';
				break;
		}
		
		// We've done this before
		if ( $weHaveVotedBefore == 1 )
		{
			if ( $score == -1 )
			{
				// We're voting minus, take one off plus
				$repro_up 	= $this->issue[ $short . '_up'] - 1;
				$repro_down	= $this->issue[ $short . '_down'] + 1;
			}
			else
			{
				// We're voting plus, take one off minus
				$repro_up 	= $this->issue[ $short . '_up'] + 1;
				$repro_down	= $this->issue[ $short . '_down'] - 1;			
			}
		}
		else if ( $weHaveRemoved == 1 )
		{
			if ( $score == -1 )
			{
				$repro_up 	= $this->issue[ $short . '_up'];
				$repro_down	= $this->issue[ $short . '_down'] - 1;
			}
			else
			{
				$repro_up	= $this->issue[ $short . '_up'] - 1;
				$repro_down	= $this->issue[ $short . '_down'];
			}
		}
		else
		{
			if ( $score == -1 )
			{
				// We're voting minus, DONT take one off plus
				$repro_up 	= $this->issue[ $short . '_up'];
				$repro_down	= $this->issue[ $short . '_down'] + 1;
			}
			else
			{
				// We're voting plus, DONT take one off minus
				$repro_up 	= $this->issue[ $short . '_up'] + 1;
				$repro_down	= $this->issue[ $short . '_down'];			
			}		
		}
		
		// Now update the main issue
		$this->DB->update( 'tracker_issues',
			array(
				$short . '_up'		=> $repro_up,
				$short . '_down'	=> $repro_down
			),
			'issue_id=' . $this->issue['issue_id']
		);
		
		$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=tracker&showissue={$this->issue['issue_id']}", $this->issue['title_seo'] );
	}
	
	protected function displayIssue()
	{
		$this->registry->tracker->attachments = array();

		// Initialize [first post always displays]
		$this->pids				= array( $this->issue['firstpost'] );
		$this->attachPostIds	= array( $this->issue['firstpost'] );
		
		// Sort out what page we're on
		$offset = intval($this->request['st']) >= 0 ? intval($this->request['st']) + 1 : 0;
		
		// Grab reply IDs for the current page
		$this->DB->build(
			array (
				'select' => 'pid, issue_id, post_date',
				'from'   => 'tracker_posts',
				'where'  => 'issue_id=' . $this->issue['issue_id'],
				'order'  => 'pid',
				'limit'  => array( $offset, $this->settings['tracker_comments_perpage'] )
			)
		);
		$this->DB->execute();

		while( $p = $this->DB->fetch() )
		{
			$this->pids[] = $p['pid'];
			$this->attachPostIds[] = $p['pid'];
		}

		// Make sure the page is valid.
		if ( $offset > 1 && count( $this->pids ) == 1 )
		{
			$this->returnLastPost();
		}

		// Handle everything posty
		$posts = $this->getPosts();
		$posts = $this->_parseAttachments( $posts, 0 );
		$posts = $this->_getOutput( $posts );

		$this->issue['firstPost']	= $this->_parseAttachments( $this->issue['firstPost'], 1 );

		// Issue custom fields
		$this->issue['fields']		= $this->registry->tracker->fields()->display( $this->issue, 'issue' );
		
		// Like system
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );
		$likeClass = classes_like::bootstrap( 'tracker', 'issues' );

		$this->issue['like'] = $likeClass->render( 'summary', $this->issue['issue_id'] );
		
		// Pad issue ID
		$this->issue['issue_display_id'] = sprintf( "%06d", $this->issue['issue_id'] );
		
		// Work out reproduction baby!
		if ( $this->issue['type'] == 'issue' OR ! $this->issue['type'] )
		{
			if ( $this->issue['repro_up'] + $this->issue['repro_down'] > 0 )
			{
				$ratio								= 100 / ($this->issue['repro_up'] + $this->issue['repro_down']);
				$this->issue['repro_up_width']		= $ratio * $this->issue['repro_up'];
				$this->issue['repro_down_width']	= $ratio * $this->issue['repro_down'];
			}
			else
			{
				$this->issue['repro_up_width']		= 50;
				$this->issue['repro_down_width']	= 50;
			}
		}
		
		// Unset any member details we had from GLOBALS
		unset( $this->cached_members );
		
		// Active users
		$this->other['active_users']	= $this->tracker->generateActiveUsers( 'issue', $this->issue['issue_id'] );
		$this->other['fast_reply']		= $this->_getFastReplyData();
		$this->other['load_editor_js']	= false;
		$this->other['smilies']			= '';
		$this->other['mod_links']		= $this->_moderationPanel();
		$this->other['p_dd']			= $this->tracker->projects()->makeDropdown('new_pid');
		
		// Can't fast reply but can edit, editor still needed
		if( !$this->other['fast_reply'] AND $this->_canEditAPost )
		{
			$this->other['load_editor_js']	= true;
			
			$classToLoad			= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
			$editor					= new $classToLoad();
			$this->other['smilies']	= $editor->fetchEmoticons();
		}

		// Enable quick reply box?
		if ( $this->tracker->projects()->checkPermission( 'reply', $this->project['project_id'] ) == TRUE && $this->issue['state'] != 'closed' )
		{
			// Custom fields
			$this->other['dropdowns'] = $this->tracker->fields()->getQuickReplyDropdowns($this->issue);
		}

		/* Add canonical tag */
		$this->registry->getClass('output')->addCanonicalTag( ( $this->request['st'] ) ? 'app=tracker&amp;showissue=' . $this->issue['issue_id'] . '&st=' . $this->request['st'] : 'app=tracker&amp;showissue=' . $this->issue['issue_id'], $this->issue['title_seo'], 'showissue' );

		/* Store root doc URL */
		$this->registry->getClass('output')->storeRootDocUrl( $this->registry->getClass('output')->buildSEOUrl( 'app=tracker&amp;showissue=' . $this->issue['issue_id'], 'publicNoSession', $this->issue['title_seo'], 'showissue' ) );
		
		$this->registry->tracker->output .= $this->registry->getClass('output')->getTemplate('tracker_issue')->issueTemplate( $this->issue, $this->project, $posts, $this->other );
	}
	
	protected function getPosts()
	{
		$posts = array();
		
		// The big old SQL beast.
		$this->DB->build(
			array(
				'select' 	=> 'p.*, p.author_id AS right_author_id',
				'from'		=> array( 'tracker_posts' => 'p' ),
				'add_join'	=> array(
					0 => array(
						'select'	=> 'm.member_id,m.members_seo_name,m.name,m.member_group_id,m.email,m.joined,m.posts, m.last_visit, m.last_activity,m.login_anonymous,m.title, m.warn_level, m.warn_lastwarn, m.members_display_name',
						'from'		=> array( 'members' => 'm' ),
						'where'		=> 'm.member_id=p.author_id',
						'type'		=> 'left'
					),
					1 => array(
						'select'	=> 'pp.*',
						'from'		=> array( 'profile_portal' => 'pp' ),
						'where'		=> 'm.member_id=pp.pp_member_id',
						'type'		=> 'left'
					),
					2 => array(
						'select'	=> 'pc.*',
						'from'		=> array( 'pfields_content' => 'pc' ),
						'where'		=> 'pc.member_id=p.author_id',
						'type'		=> 'left'
					),
				),
				'where'		=> 'p.pid IN (' . implode( ',', $this->pids ) . ')',
				'order'		=> $this->settings['tracker_post_order_column'] . ' ' . $this->settings['tracker_post_order_sort']
			)
		);		
		
		// Had an issue with it losing the reference.
		$query = $this->DB->execute();
		
		// We have no posts, yay!
		if ( ! $this->DB->getTotalRows( $query ) )
		{
			// TODO: $first undefined
			if ( $first >= $this->settings['tracker_comments_perpage'] )
			{
				// AUTO FIX: Get the correct number of replies...
				$this->DB->build(
					array(
						'select' => 'COUNT(*) as pcount',
						'from'   => 'tracker_posts',
						'where'  => "issue_id=".$this->issue['issue_id']
					)
				);

				$newQuery   = $this->DB->execute();
				
				// Grab count, and auto fix.
				$pcount 			= $this->DB->fetch( $newQuery );
				$pcount['pcount'] 	= $pcount['pcount'] > 0 ? $pcount['pcount'] - 1 : 0;

				// Update the issue
				if ( $pcount['pcount'] > 1 )
				{
					$this->DB->update( 'tracker_issues', array( 'posts' => $pcount['pcount'] ), 'issue_id=' . $this->issue['issue_id'] );
				}
				
				// Send them to where they belong
				$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=tracker&showissue={$this->issue['issue_id']}&view=getlastpost", $this->issue['title_seo'] );
			}
		}
		
		// Sort out last/first post dates
		$lastPostDate	= 0;
		$firstPostDate	= 0;
		
		// Loop through the posts we have, and send them for formatting
		while ( $row = $this->DB->fetch( $query ) )
		{
			$row['author_id']				= $row['right_author_id'];
			$row['member_id']				= ( intval( $row['member_id'] ) > 0 ) ? $row['member_id'] : $row['right_author_id'];
			
			// Due to new template, new issue doesn't want to be in normal posts
			// Post from prev page
			if ( ! $row['new_issue'] )
			{
				$posts[ $row['post_date'] . '-0-2-' . $row['pid'] ]		= $this->parseReply( $row );
			}
			else
			{
				$this->issue['firstPost']	= $this->parseReply( $row );
			}
						
			// Get last date for feed
			if ( $row['post_date'] > $lastPostDate )
			{
				$lastPostDate				= $row['post_date'];
			}
			
			// First post date for feed
			if ( $row['post_date'] < $firstPostDate || $firstPostDate == 0 )
			{
				if ( $this->request['st'] > 1 && ! $row['new_issue'] )
				{
					$firstPostDate			= $row['post_date'];			
				}
				else if ( ! $this->request['st'] && $row['new_issue'] )
				{
					$firstPostDate			= $row['post_date'];
				}
			}
		}
		
		// Sort out how we're getting stuff
		if ( count( $posts ) < ( $this->settings['tracker_comments_perpage'] - 1 ) )
		{
			// SQL query
			$where		= " AND date>={$firstPostDate}";
		}
		else if ( $this->request['st'] > 0 )
		{
			$where		= " AND date>{$firstPostDate} AND date<={$lastPostDate}";
		}
		else
		{
			$where		= " AND date<={$lastPostDate}";
		}
		
		// Load our mini feed (new in 2.1)
		$this->DB->build(
			array(
				'select'	=> '*',
				'from'		=> 'tracker_field_changes',
				'where'		=> 'issue_id=' . $this->issue['issue_id'] . $where,
				'order'		=> 'date, field_change_id'
			)
		);
		$mf	= $this->DB->execute();
		
		if ( $this->DB->getTotalRows($mf) )
		{
			while( $change = $this->DB->fetch($mf) )
			{
				/* Project Show Permission? */
				if($change['module'] == 'project')
				{
					if ( ! $this->registry->tracker->projects()->checkPermission( 'show', $change['old_value'] ) )
					{
						continue;
					}
				}

				// Formatting
				$change['field_update'] = true;
				
				// Mod perms
				$change['_can_delete']	= false;
				
				if ( $this->member->trackerProjectPerms['can_del_posts'] )
				{
					$change['_can_delete']	= true;
				}
				
				// Credit to @MadMikeyB for being 'helpful'
				// Could be cached already, and make sure we're not a guest!
				if ( $change['mid'] )
				{
					if ( $this->cached_members[ $change['mid'] ] )
					{
						$change['poster']		= $this->cached_members[ $change['mid'] ];
					}
					else
					{
						$change['poster']		= IPSMember::buildDisplayData( $change['mid'], array( 'signature' => 0, 'avatar' => 1, 'checkFormat' => 1 ) );
						
						// Assign it to cache so we don't do this again!
						$this->cached_members[ $change['mid'] ]	= $change['poster'];
					}
				}
				else
				{
					// We are a guest
					$change['poster']			= IPSMember::buildDisplayData(
						array(
							'member_id'		=> 0,
							'members_display_name'	=> $this->lang->words['global_guestname']
						)
					);
				}
				
				// Core or fields
				if ( in_array( $change['module'], array( 'issue_lock', 'project' ) ) )
				{
					// Format meta
					$change					= $this->formatMeta( $change );
				}
				else
				{			
					// Format meta
					$change					= $this->tracker->fields()->formatMeta( $change );
				}
				
				if ( isset( $change['value'] ) )
				{
					$posts[ $change['date'] . '-0-1-' . $change['field_change_id'] ] = $change;
				}
			}
		}
	
		// Now we've merged, sort chronologically		
		ksort( $posts );
		
		// Sort out previous
		$this->v = '';
		
		// Go through posts 
		reset($posts);

		/* 0 */
		$previousDate = 0;
		
		// Loop through, and mimic a foreach() setup
		while( ! is_null( $k = key($posts) ) )
		{
			$prev 		  = $this->v;

			/* Fix for: #36025 */
			$prev['date'] = isset($prev['date']) ? $prev['date'] : FALSE;

			$this->v 	  = current($posts);

			if ( isset($prev['row']['pid']) OR ( $previousDate + 60 ) <= $this->v['date'] && $previousDate OR ! $prev['date'] )
			{
				$previousDate				= $this->v['date'];
				$this->v['new_feed']		= true;
			}
			
			// Set within our minute grace
			$this->v['date']		  = $previousDate;
			
			$next = next($posts);

			if ( isset($next['row']['pid']) OR $next['date'] >= ( $previousDate + 60 ) OR ! $next['date'] )
			{
				$this->v['end_next_feed'] = true;
			}

			$posts[$k] = $this->v;
		}
		
		return $posts;
	}
	
	/**
	* Formats timeline entries that aren't controlled by modules
	*
	* @access	protected
	* @param	array		Field change array
	* @return	void		[Outputs to screen/redirects]
	*/
	protected function formatMeta( $change )
	{
		if ( ! is_array( $change ) )
		{
			return false;
		}
		
		switch( $change['module'] )
		{
			case 'project':
				$project				= $this->tracker->projects()->getProject( $change['old_value'] );
				
				$change['entryLang']	= $this->lang->words['bt_moved_issue'];
				$change['value']		= "<a href='" . $this->registry->output->buildSEOUrl( 'showproject=' . $change['old_value'], 'publicWithApp', $project['title_seo'], 'showproject' ) . "'>{$project['title']}</a>";
			break;
			
			case 'issue_lock':
				$change['value']		= $change['new_value'] == 'closed' ? $this->lang->words['bt_locked_issue'] : $this->lang->words['bt_opened_issue'];
			break;
		}
		
		return $change;
	}

	/**
	* Loads the issue ready for execution
	*
	* @access	protected
	* @param	array		Issue reference [optional]
	* @return	void		[Outputs to screen/redirects]
	*/
	protected function loadIssue( $issue=array() )
	{
		if ( ! count($issue) )
		{
			// Check the input
			$this->request['iid'] = intval($this->request['iid']);

			if ( $this->request['iid'] <= 0  )
			{
				$this->registry->output->showError( $this->lang->words['bt_no_issue'], 0, FALSE, '', 404 );
			}

			// Grab the information from the database
			$this->DB->build(
				array(
					'select'   => 't.*',
					'from'     => 'tracker_issues t',
					'where'    => 't.issue_id=' . $this->request['iid'],
				)
			);
			$this->DB->execute();

			// Assign it globally so it can be accessed throughout
			$this->issue = $this->DB->fetch();
		}
		else
		{
			$this->issue = $issue;
		}

		// Set our data globally
		$this->registry->tracker->issues()->setIssueData( $this->issue );

		// Make sure we do have a project ID and this issue isn't floating in limbo
		$this->issue['project_id'] = isset($this->issue['project_id']) ? $this->issue['project_id'] : 0;

		// Grab the project details, assign it, error out if doesn't exist.
		$this->project = $this->tracker->projects()->getProject( $this->issue['project_id'] );

		if ( ! $this->project['project_id'] )
		{
			$this->registry->output->showError( $this->lang->words['bt_no_issue'], 0, FALSE, '', 404 );
		}

		// Legacy
		$this->request['pid'] = $this->issue['project_id'];

		// Issue doesn't exist, or can't be read.
		if ( ! $this->issue['issue_id'] )
		{
			$this->registry->output->showError( $this->lang->words['bt_no_issue'], 0, FALSE, '', 404 );
		}

		if ( ! $this->tracker->projects()->checkPermission( 'read', $this->project['project_id'] ) )
		{
			$this->registry->output->showError( $this->lang->words['bt_no_issue'], 0, FALSE, '', 404 );
		}

		// Permissions
		if ( isset( $this->request['pid'] ) && intval( $this->request['pid'] ) )
		{
			$this->tracker->projects()->createPermShortcuts( intval( $this->request['pid'] ) );
			$this->tracker->moderators()->buildModerators( intval($this->request['pid']) );
		}

		// Custom fields
		$this->tracker->fields()->initialize( $this->project['project_id'] );
		$this->tracker->fields()->setIssue( $this->issue );
	}
	
	/**
	* Starts outputting and converting some issue data
	*
	* @access	public
	* @return	void		[Outputs to screen/redirects]
	*/
	protected function processIssue()
	{
		// Make sure we actually have an issue
		if( !$this->issue['issue_id'] || !$this->issue['firstpost'] || $this->issue['posts'] < 0 )
		{
			$this->registry->output->showError( $this->lang->words['bt_no_issue'], 0, FALSE, '', 404 );
		}

		$this->request['show'] 			= isset($this->request['show']) ? intval( $this->request['show'] ) : 0;
		$this->request['st']   			= isset($this->request['st']) > 0 ? intval( $this->request['st'] ) : 0;
		$this->issue['JUMP']			= '';
		$this->first					= $this->request['st'];
		$this->request['view'] 			= isset($this->request['view']) ? $this->request['view'] : NULL;

		// Handle navigation and custom fields navigation
		$this->tracker->projects()->createBreadcrumb( $this->project['project_id'] );
		$this->tracker->fields()->addNavigation( $this );

		// Hi! Light?
		$hl = (isset($this->request['hl']) AND $this->request['hl']) ? '&amp;hl=' . $this->request['hl'] : '';

		$this->other['pagination'] = $this->registry->output->generatePagination(
			array(
				'totalItems'        => $this->issue['posts'],
				'itemsPerPage'      => $this->settings['tracker_comments_perpage'],
				'currentStartValue' => $this->first,
				'baseUrl'           => 'app=tracker&amp;showissue=' . $this->issue['issue_id'].$hl,
			)
		);

		// Numbers into english please
		$this->issue['start_date'] = $this->registry->getClass('class_localization')->getDate( $this->issue['start_date'], 'LONG' );
		
		$this->lang->words['bt_issue_topic_stats'] = str_replace( "<#START#>", $this->issue['ISSUE_START_DATE'], $this->lang->words['bt_issue_topic_stats']);
		$this->lang->words['bt_issue_topic_stats'] = str_replace( "<#POSTS#>", $this->issue['posts']     , $this->lang->words['bt_issue_topic_stats']);

		// Multi quoting, this code's been here years - why not actually use it ;) 2.1
		$this->qpids = IPSCookie::get('mqtids');

		// Mark it as read
		if ( ! $this->request['view'] )
		{
			$this->registry->classItemMarking->markRead( array( 'forumID' => $this->project['project_id'], 'itemID' => $this->issue['issue_id'] ), 'tracker' );
		}
	}
	
	private function _changeIssueVisibility( $type='private' )
	{
		if ( ! intval( $this->issue['issue_id'] ) )
		{
			$this->registry->output->showError( $this->lang->words['bt_no_issue'] );
		}
		
		/* Hash check / perm check */
		if ( $this->request['secure_key'] != $this->member->form_hash || ! $this->tracker->fields()->checkPermission( $this->project, 'privacy', 'update' ) )
		{
			$this->registry->output->showError('no_permission');
		}
		
		$data = ( $type == 'private' ) ? 1 : 0;
		
		if ( $data == $this->issue['module_privacy'] )
		{
			return;
		}
		
		$this->DB->update( 'tracker_issues', array( 'module_privacy' => $data ), 'issue_id=' . $this->issue['issue_id'] );
		
		/* Because init has already been run, we manually add in this line */
		$this->issue['module_privacy']	= $data;
	}
	
	private function _getOutput( $postData )
	{
		foreach( $postData as $id => $post )
		{
			$poster = $post['poster'];
			$row    = $post['row'];
			
			//-----------------------------------------
			// Are we giving this bloke a good ignoring?
			//-----------------------------------------
			$ignoredUsers = array();
	
			foreach ( $this->member->ignored_users as $_i )
			{
				/* For 2.0: Probably should hook in and make a separate option for ignore tracker issues */
				if ( $_i['ignore_topics'] )
				{
					$ignoredUsers[] = $_i['ignore_ignore_id'];
				}
			}
			
			if ( is_array( $ignoredUsers ) && count( $ignoredUsers ) )
			{
				if ( in_array( $poster[ 'member_id' ], $ignoredUsers ) )
				{
					if ( ! strstr( $this->settings[ 'cannot_ignore_groups' ], ',' . $poster[ 'member_group_id' ] . ',' ) )
					{
						$row[ '_ignored' ] = 1;
					}
				}
			}

			if ( $row['append_edit'] == 1 && $row['edit_time'] && $row['edit_name'] )
			{
				$e_time			= $this->registry->getClass( 'class_localization')->getDate( $row['edit_time'] , 'LONG' );
				$row['edit_by']	= sprintf( $this->lang->words['edited_by'], $row['edit_name'], $e_time );
			}
			
			$row['title_seo'] = $this->issue['title_seo'];
			
			// Re assign
			$post['row'] 	= $row;
			$post['poster'] = $poster;
			
			// Send back
			$postData[$id]	= $post;
		}
		
		return $postData;
	}
	
	/**
	* Parse attachments
	*
	* @access	public
	* @param	array	Array of post data
	* @return	string	HTML parsed by attachment class
	*/
	public function _parseAttachments( $postData, $firstPost=0 )
	{
		//-----------------------------------------
		// INIT. Yes it is
		//-----------------------------------------
		
		$postHTML = array();
		
		// Remember our number-0-x-format
		$this->storedIds = array();
		
		//-----------------------------------------
		// Separate out post content
		//-----------------------------------------
		if ( ! $firstPost )
		{
			foreach( $postData as $id => $post )
			{
				// Hot fix, naughty, dont allow first post here
				if ( $post['row']['new_issue'] )
				{
					continue;
				}
				
				// Remove old ID
				unset( $postHTML[ $id ] );
				
				// Get post id
				$id	= strstr( $id, '-0-2-' );
				$id	= str_replace( '-0-2-', '', $id );
				$id	= intval( $id );
				
				$postHTML[ $id ] = $post['row']['post'];
				
				// Remember the full ID
				$this->storedIds[ $post['row']['pid'] ] = $post['row']['post_date'] . '-0-2-' . $post['row']['pid'];
			}
		}
		else
		{
			$postHTML[ $postData['row']['pid'] ]	= $postData['row']['post'];
			$this->attachPostIds[0] = $postData['row']['pid'];

			// Remember the full ID
			$this->storedIds[ $postData['row']['pid'] ] = $postData['row']['post_date'] . '-0-2-' . $postData['row']['pid'];
			$this->firstPostHash						= $this->storedIds[ $postData['row']['pid'] ];
		}
		
		//-----------------------------------------
		// ATTACHMENTS!!!
		//-----------------------------------------

		if ( $this->issue['hasattach'] )
		{
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------

				$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach	= new $classToLoad( $this->registry );
			}

			//-----------------------------------------
			// Not got permission to view downloads?
			//-----------------------------------------
			
			if ( $this->tracker->projects()->checkPermission( 'download', $this->issue['project_id'] ) === FALSE )
			{
				$this->settings['tracker_show_img_upload'] =  0;
			}
			
			//-----------------------------------------
			// Continue...
			//-----------------------------------------
			
			$this->class_attach->type  = 'tracker';
			$this->class_attach->init();
			
			$this->attachHTML = $this->class_attach->renderAttachments( $postHTML, $this->attachPostIds );
		}
		
		// We may have loaded before
		$attachHTML = $this->attachHTML;

		/* Now parse back in the rendered posts */
		if( is_array($attachHTML) AND count($attachHTML) )
		{
			foreach( $attachHTML as $id => $data )
			{
				// Have we done this before?
				if ( isset( $this->done[$id] ) && !$firstPost )
				{
					$postData[ $this->storedIds[ $id ] ]['row']['post']           = $data['html'];
					$postData[ $this->storedIds[ $id ] ]['row']['attachmentHtml'] = $data['attachmentHtml'];

					continue;
				}
				
				/* Get rid of any lingering attachment tags */
				if ( stristr( $data['html'], "[attachment=" ) )
				{
					$data['html'] = IPSText::stripAttachTag( $data['html'] );
				}
				
				if ( ! $firstPost && $id != $this->issue['firstpost'] )
				{
					$postData[ $this->storedIds[ $id ] ]['row']['post']           = $data['html'];
					$postData[ $this->storedIds[ $id ] ]['row']['attachmentHtml'] = $data['attachmentHtml'];
				}
				else
				{
					if ( $this->firstPostHash == $this->storedIds[ $id ] )
					{
						$postData['row']['post']           = $data['html'];
						$postData['row']['attachmentHtml'] = $data['attachmentHtml'];
					}
				}
				
				$this->done[ $id ] = 1;
			}
		}

		return $postData;
	}

	/*-------------------------------------------------------------------------*/
	// Parse post
	/*-------------------------------------------------------------------------*/

	function parseReply( $row=array() )
	{
		$poster = array();

		//-----------------------------------------
		// Cache member
		//-----------------------------------------

		if ( $row['member_id'] )
		{
			//-----------------------------------------
			// Is it in the hash?
			//-----------------------------------------

			if ( isset($this->cached_members[ $row['member_id'] ]) )
			{
				//-----------------------------------------
				// Ok, it's already cached, read from it
				//-----------------------------------------

				$poster = $this->cached_members[ $row['member_id'] ];
			}
			else
			{
				$row['members_cache']				= array();
				
				$poster = IPSMember::buildDisplayData( $row, array( 'signature' => 0, 'customFields' => 0, 'warn' => 1, 'avatar' => 1, 'checkFormat' => 1 ) );
				
				//-----------------------------------------
				// Add it to the cached list
				//-----------------------------------------

				$this->cached_members[ $row['member_id'] ] = $poster;
			}
		}
		else
		{
			//-----------------------------------------
			// It's definitely a guest...
			//-----------------------------------------

			$row['author_name']	= $this->settings['guest_name_pre'] . $row['author_name'] . $this->settings['guest_name_suf'];
			
			$row['author_name'] = $row['author_name'] ? $row['author_name'] : $this->lang->words['global_guestname'];
			
			$poster = IPSMember::buildDisplayData(
				array(
					'member_id'		=> 0,
					'members_display_name'	=> $row['author_name']
				)
			);
		}
		
		//-----------------------------------------
		// Parse the post
		//-----------------------------------------
		IPSText::getTextClass('bbcode')->parse_smilies         = $row['use_emo'];
		IPSText::getTextClass('bbcode')->parse_html            = ( $this->project['use_html'] && $this->caches['group_cache'][ $row['member_group_id'] ]['g_dohtml'] && $row['post_htmlstate'] ) ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_nl2br           = $row['post_htmlstate'] == 2 ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_bbcode          = $this->project['use_ibc'];
		IPSText::getTextClass('bbcode')->parsing_section       = 'tracker_issues';
		IPSText::getTextClass('bbcode')->parsing_mgroup        = $row['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others = $row['mgroup_others'];

		$row['post'] = IPSText::getTextClass('bbcode')->preDisplayParse( $row['post'] );

		//-----------------------------------------
		// Highlight...
		//-----------------------------------------
		if ( $this->request['hl'] )
		{
			$row['post'] = IPSText::searchHighlight( $row['post'], $this->request['hl'] );
		}

		//-----------------------------------------
		// Multi Quoting?
		//-----------------------------------------
		if ( $this->member->tracker['reply_perms'] )
		{
			$row['_mq_selected'] = 0;

			if ( $this->qpids )
			{
				if ( strstr( ','.$this->qpids.',', ','.$row['pid'].',' ) )
				{
					$row['mq_start_image'] = 1;
				}
			}
		}

		//-----------------------------------------
		// Delete button..
		//-----------------------------------------

		$row['_can_delete'] = $this->deleteButton($row['pid'], $poster);
		$row['_can_edit']   = $this->editButton($row['pid'], $poster, $row['post_date']);
		
		if ( $row['_can_edit'] )
		{
			$this->_canEditAPost	= true;
		}
		
		//-----------------------------------------
		// Siggie stuff
		//-----------------------------------------

		$row['signature'] = "";

		if (isset($poster['signature']) AND $poster['signature'] AND  $this->memberData['view_sigs'])
		{
			if ($row['use_sig'] == 1)
			{
				$row['signature'] = $this->registry->output->getTemplate('global')->signature_separator( $poster['signature'] );
			}
		}

		//-----------------------------------------
		// Post number
		//-----------------------------------------

		if ( $this->issue_view_mode == 'linearplus' and $this->issue['firstpost'] == $row['pid'])
		{
			$row['post_count'] = 1;

			if ( ! $this->first )
			{
				$this->post_count++;
			}
		}
		else
		{
			$this->post_count++;

			$row['post_count'] = intval($this->request['st']) + $this->post_count;
		}

		$row['project_id'] = $this->issue['project_id'];

		return array( 'row' => $row, 'poster' => $poster );
	}
	
	/**
	 * Get fast reply status
	 *
	 * @return	string
	 */
	protected function _getFastReplyData()
	{
		/* Init */
		$show      = false;
		
		if (  
		       ( $this->tracker->projects()->checkPermission( 'reply', $this->project['project_id'] ) == TRUE )
		   and ( $this->issue['state'] != 'closed' OR $this->memberData['g_post_closed'] )
		   )
		{
			$show  = true;
		}
		
		return $show;
	}

	/*-------------------------------------------------------------------------*/
	// Render the delete button
	/*-------------------------------------------------------------------------*/

	protected function deleteButton($post_id, $poster)
	{
		if ( ! $this->memberData['member_id'] )
		{
			return false;
		}

		if ( $post_id != $this->issue['firstpost'] )
		{
			if ($this->member->trackerProjectPerms['can_del_posts']) return true;
		}
		else
		{
			if ($this->member->trackerProjectPerms['can_del_issues']) return true;
		}
		
		return false;
	}

	/*-------------------------------------------------------------------------*/
	// Render the edit button
	/*-------------------------------------------------------------------------*/

	protected function editButton($post_id, $poster, $post_date)
	{
		if ( ! $this->memberData['member_id'] )
		{
			return false;
		}
		
		if ( $this->member->trackerProjectPerms['can_edit_posts'] )
		{
			return true;
		}

		/* Check for locked issue */
		if($this->issue['state'] == 'closed' && (!$this->member->trackerProjectPerms['is_super'] && !$this->member->tracker['g_tracker_ismod']))
		{
			return FALSE;
		}

		if ( $poster['member_id'] == $this->memberData['member_id'] and $this->memberData['g_edit_posts'] )
		{
			// Have we set a time limit?

			if ( $this->memberData['g_edit_cutoff'] > 0 )
			{
				if ( $post_date > ( time() - ( intval($this->memberData['g_edit_cutoff']) * 60 ) ) )
				{
					return true;
				}
				
				return false;
			}
			
			return true;
		}

		return false;
	}

	/**
	 * Get reply button data
	 *
	 * @author	Terabyte
	 * @access	private
	 * @return	array
	 **/
	private function _getReplyButtonData()
	{
		/* Init vars */
		$image = '';
		$url   = $this->settings['base_url_with_app']."module=post&amp;section=post&amp;do=postreply&amp;pid=".$this->project['project_id']."&amp;iid=".$this->issue['issue_id'];
		
		if ( $this->issue['state'] == 'closed' )
		{
			/* Do we have the ability to post in closed issues?*/
			if ($this->memberData['g_post_closed'] == 1)
			{
				$image = 'locked';
			}
			else
			{
				$url   = '';
				$image = 'locked';
			}
		}
		elseif ( $this->member->tracker['reply_perms'] )
		{
			$image = "reply";
		}
		else
		{
			$url   = '';
			$image = 'no_reply';
		}
		
		return array( 'image' => $image, 'url' => $url );
	}

	/**
	 * Return if we can see the IP address or not
	 *
	 * @author	Terabyte
	 * @access	public
	 * @return	bool
	 */
	private function _canViewIPAddress()
	{
		if ( $this->member->trackerProjectPerms['is_super'] )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/*-------------------------------------------------------------------------*/
	// Render the moderator links
	/*-------------------------------------------------------------------------*/

	protected function _moderationPanel()
	{
		/* Init */
		$moderator = $this->member->trackerProjectPerms;
		$mod_links = array();
		$_got_data = 0;
		$actions   = array( 'move_issue', 'close_issue', 'open_issue', 'delete_issue', 'edit_issue' );
		
		if ( ! $this->memberData['member_id'] )
		{
			return;
		}
		
		if ( $this->memberData['member_id'] == $this->issue['starter_id'] )
		{
			$_got_data = 1;
		}
		
		if ( $this->memberData['g_is_supmod'] == 1 )
		{
			$_got_data = 1;
		}
		
		if ( !empty( $moderator['mid'] ) )
		{
			$_got_data = 1;
		}
		
		if ( $_got_data == 0 )
		{
		   	return;
		}
		
		foreach( $actions as $key )
		{
			if( is_array($this->_addModLink($key)) )
			{
				if ($this->memberData['g_is_supmod'])
				{
					$mod_links[] = $this->_addModLink($key);
				}
				elseif ( !empty( $moderator ) && count( $moderator ) )
				{
					if ( !empty($moderator[ $key ]) )
					{
						$mod_links[] = $this->_addModLink($key);
					}
					
					// What if member is a mod, but doesn't have these perms as a mod?
					
					elseif ($key == 'can_lock' or $key == 'can_unlock')
					{
						if ($this->memberData['g_open_close_posts'])
						{
							$mod_links[] = $this->_addModLink($key);
						}
					}
					elseif ($key == 'can_del_issues')
					{
						if ( $this->memberData['g_delete_own_topics'] )
						{
							$mod_links[] = $this->_addModLink($key);
						}
					}
				}
				elseif ($key == 'can_lock' or $key == 'can_unlock')
				{
					if ($this->memberData['g_open_close_posts'])
					{
						$mod_links[] = $this->_addModLink($key);
					}
				}
				elseif ($key == 'can_del_issues')
				{
					if ( $this->memberData['g_delete_own_topics'] )
					{
						$mod_links[] = $this->_addModLink($key);
					}
				}
			}
		}

		return $mod_links;
	}
	
	/**
	 * Append mod links
	 *
	 * @param	string	$key
	 * @return	array 	Options
	 */
	public function _addModLink( $key="" )
	{
		if ($key == "") return "";
		
		if ($this->issue['state'] == 'open'   and $key == 'can_unlock') return "";
		if ($this->issue['state'] == 'closed' and $key == 'can_lock') return "";
		if ($this->issue['state'] == 'moved'  and ($key == 'can_lock' or $key == 'can_move')) return "";
		
		return array( 'option' => $this->mod_action[ $this->modRemap[$key] ],
					  'value'  => $this->lang->words[ $this->modRemap[$key] ] );
	}

	/**
	 * Send to the last post in the requested issue.
	 *
	 * @return	void		[Outputs to screen/redirects]
	 */
	protected function returnLastPost()
	{
		// Calculate starting index of the last page.
		$st = $this->issue['posts'] ? ( ceil( $this->issue['posts'] / $this->settings['tracker_comments_perpage'] ) - 1 ) * $this->settings['tracker_comments_perpage'] : 0;
		
		$post = $this->DB->buildAndFetch(
			array(
				'select' => 'pid',
				'from'   => 'tracker_posts',
				'where'  => "issue_id=" . $this->issue['issue_id'],
				'order'  => 'post_date DESC',
				'limit'  => array( 0,1 )
			)
		);

		$this->registry->output->silentRedirect( $this->settings['base_url']."app=tracker&amp;showissue=".$this->issue['issue_id']."&gopid={$post['pid']}&st={$st}&"."#entry".$post['pid'], $this->issue['title_seo']);
	}
}
