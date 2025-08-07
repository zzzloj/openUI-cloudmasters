<?php

class tracker_core_issues extends iptCommand
{
	protected $issueData = array();
	
	public function doExecute(ipsRegistry $registry){}

	/**
	* @DEPRECATED - will be
	* Check to see if permissions are correct for issues
	* 
	* @param	mixed	The whole issue array from the database
	* @access	public
	* @return	void
	*/
	public function checkPermission( $issue=array(), $type='reply' )
	{
		switch( $type )
		{
			case 'reply':
				if ( ! $this->member->tracker['reply_perms'] )
				{
					$this->registry->output->showError( 'You do not have permission to reply to this issue', '20T104' );
				}

				$this->checkLocked( $issue );
				break;

			case 'edit':
				$this->checkLocked( $issue );

				if ( $this->member->trackerProjectPerms['can_edit_posts'] )
				{
					return true;
				}

				$_ok = 0;

				if ( $this->member->tracker['reply_perms'] )
				{
					if ( $this->memberData['member_id'] )
					{
						$_post = $this->DB->buildAndFetch(
							array(
								'select' => 'pid, author_id, issue_id',
								'from'   => 'tracker_posts',
								'where'  => 'pid=' . $this->request['p']
							)
						);

						if ( $_post['pid'] AND $_post['issue_id'] == $issue['issue_id'] AND $_post['author_id'] == $this->memberData['member_id'] )
						{
							$_ok = 1;
							return TRUE;
						}
					}
				}

				if ( ! $_ok )
				{
					$this->registry->output->showError( array( 'LEVEL' => '1', 'MSG' => 'not_op') );
				}

				break;
		}
	}

	/**
	* Check to see if an issue is locked
	* 
	* @param	mixed	The whole issue array from the database
	* @access	public
	* @return	void
	*/
	public function checkLocked( $issue=array() )
	{
		if ( ( $issue['state'] != 'open' ) and ( ! $this->memberData['g_access_cp'] ) )
		{
			if ( $this->memberData['g_post_closed'] != 1 )
			{
				$this->registry->output->showError( 'You do not have permission to perform the requested operation because the issue is locked', '20T111' );
			}
		}
	}

	/**
	* Did we post in this issue? This works closely with folder icons
	*
	* @param	array	Issue IDS array
	* @param	string	Dot flag
	* @param	array	Issue data array
	* @access	public
	* @return	array	NEW issue data array
	*/
	function checkUserPosted( $issue_ids=array(), $parse_dots=1, $issue_array=array() )
	{
		if ( ( $this->settings['tracker_show_user_posted'] == 1 ) and ( $this->memberData['member_id'] ) and ( count( $issue_ids ) ) and ( $parse_dots ) )
		{
			$this->DB->build(
				array(
					'select' => 'author_id, issue_id',
					'from'   => 'tracker_posts',
					'where'  => "author_id=".$this->memberData['member_id']." AND issue_id IN(".implode( ",", $issue_ids ).")",
				)
			);
			$this->DB->execute();

			while( $p = $this->DB->fetch() )
			{
				if ( is_array( $issue_array[ $p['issue_id'] ] ) )
				{
					$issue_array[ $p['issue_id'] ]['author_id'] = $p['author_id'];
				}
			}
		}

		return $issue_array;
	}

	/**
	* Generate the appropriate folder icon for a topic
	*
	* @param	array	Topic data array
	* @param	string	Dot flag
	* @access	public
	* @return	string	Parsed macro
	*/
	public function folderIcon( $issue, $dot='', $is_read=false )
	{
		return array(
					'is_read'		=> $is_read,
					'is_closed'		=> $issue['state'] == 'closed' ? true : false,
					'is_poll'		=> false,
					'show_dot'		=> $dot,
					'is_moved'		=> false,
					'is_hot'		=> ( $issue['posts'] + 1 >= $this->settings['tracker_hot_issue'] ) ? true : false,
					);
	}
	
	/**
	 * Simply returns our current issue
	 */
	public function getIssueData()
	{
		return $this->issueData;
	}
	
	/**
	 * Fetch the next unread topicID
	 * @param array or null $issueData
	 */
	public function getNextUnreadIssueId( $issueData=false )
	{
		$issueData   = ( ! is_array( $issueData ) ) ? $this->getIssueData() : $issueData;
		$readItems   = $this->registry->classItemMarking->fetchReadIds( array( 'forumID' => $issueData['project_id'] ), 'tracker' );
		$lastMarked  = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $issueData['project_id'] ), 'tracker' );
		
		/* Add in this topic ID to be sure */
		$readItems[ $issueData['issue_id'] ] = $issueData['issue_id'];
		
		/* Can we see private issues? */
		$approved = ' AND module_privacy=0';
		
		// Is module privacy enabled?
		if ( ! $this->registry->tracker->modules()->moduleIsInstalled( 'privacy', TRUE ) )
		{
			$approved = '';
		}
		else if ( $issueData['module_privacy'] && $this->registry->tracker->fields()->field('privacy')->checkPermission('show') )
		{
			$approved = '';
		}
		
		/* First, attempt to fetch a topic older than this one */
		$iid = $this->DB->buildAndFetch( array( 'select' => 'issue_id, title_seo',
												'from'   => 'tracker_issues',
												'where'  => "project_id=" . intval( $issueData['project_id'] ) . " AND issue_id NOT IN(".implode(",",array_values($readItems)).") AND last_post < " . intval($issueData['last_post']) . "{$approved} AND last_post > " . $lastMarked,
												'order'  => 'last_post DESC',
												'limit'  => array( 0, 1 ) )	);
		
		if ( ! $iid )
		{
			$iid = $this->DB->buildAndFetch( array( 'select' => 'issue_id, title_seo',
													'from'   => 'tracker_issues',
													'where'  => "project_id=" . intval( $issueData['project_id'] ) . " {$approved} AND issue_id NOT IN(".implode(",",array_values($readItems)).") AND last_post > " . intval($issueData['last_post']),
													'order'  => 'last_post DESC',
													'limit'  => array( 0, 1 ) )	);
		}
		
		return $iid;
	}

	/**
	* Initiate our cookie system, mhmm cookies!
	*
	* @param	null
	* @access	public
	* @return	array	NEW issue data array
	*/
	public function initiateReadArray()
	{
		if ( $read = IPSCookie::get( 'issuesread' ) )
		{
			if( $read != "-1" )
			{
				$this->read_array = IPSLib::cleanIntArray( unserialize( stripslashes( $read ) ) );
			}
			else
			{
				$this->read_array = array();
			}
		}

		return $this->read_array;
	}

	/*-------------------------------------------------------------------------*/
	// Builds the bug's content row
	/*-------------------------------------------------------------------------*/

	public function parseRow( $row = array(), $project )
	{
		//-----------------------------------------
		// Get a real ID so that moved
		// issue don't break owt
		//-----------------------------------------

		$row['real_issue_id'] = $row['issue_id'];
		$last_time            = 0;
		$row['last_post']     = $row['last_post'];
		$row['_last_post']    = $row['last_post'];
		
		// Last user image
		if ( $row['last_poster_id'] )
		{
			$row['user']		= IPSMember::buildDisplayData( $row['last_poster_id'], array( 'avatar' => 1, 'signature' => 0 ) );
		}
		else
		{
			// We are a guest! Credit to @MadMikeyB for being 'helpful'
			$row['user']		= IPSMember::buildDisplayData(
				array(
					'member_id'	=> 0,
					'members_display_name'	=> $row['last_poster_name'] ? $this->settings['guest_name_pre'] . $row['last_poster_name'] . $this->settings['guest_name_suf'] : $this->lang->words['global_guestname']
				)
			);
		}
		
		//-----------------------------------------
		// Yawn
		//-----------------------------------------

		$row['last_text']   = $this->lang->words['bt_project_last_post_by'];
		$row['posts']       = $row['posts'];

		//-----------------------------------------
		// Not reading from DB or past out tracking limit
		// At this point: last_vist =
		// last_visit > board_marked ? last_visit : board_marked
		//-----------------------------------------
		$lastMarked	= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $row['project_id'], 'itemID' => $row['issue_id'] ), 'tracker' );

		$row['_hasUnread'] = ( $lastMarked < $row['last_post'] ) ? true : false;
		
		/* Determine which link normal or /unread */
		if ( $lastMarked && $row['posts'] && $row['_hasUnread'] )
		{
			/* They've seen this topic but not all of it or there have been new */
			$row['_canJumpToUnread'] = true;
		}
		
		if ( $row['_hasUnread'] )
		{
			$row['_unreadUrl'] = $this->registry->output->buildSEOUrl( 'showissue=' . $row['issue_id'] . '&amp;view=getunread', 'publicWithApp', $row['title_seo'], 'showissue' );
		}
		
		$row['folder_img']	= $this->tracker->issues()->folderIcon( $row, ( $this->memberData['member_id'] == $row['author_id'] && $row['author_id'] ), ( $row['_hasUnread'] ? 0 : 1 ) );

		$row['start_date'] = $this->registry->getClass('class_localization')->getDate( $row['start_date'], 'LONG' );

		//-----------------------------------------
		// Pages 'n' posts
		//-----------------------------------------

		$row['pages'] = '';

		$pages = ceil( $row['posts'] / $this->settings['tracker_comments_perpage'] );
		
		if ( $pages > 1 )
		{
			for ( $i = 0; $i < $pages; ++$i )
			{
				if ( $i == 3 and $pages > 4 )
				{
					$row['pages'][] = array(	'last'   => 1,
					 					    	'st'     => ($pages - 1) * $this->settings['tracker_comments_perpage'],
					  							'page'   => $pages );
					break;
				}
				else
				{
					$row['pages'][] = array(	'last' => 0,
												'st'   => $i * $this->settings['tracker_comments_perpage'],
												'page' => $i + 1 );
				}
			}
		}

		//-----------------------------------------
		// Format some numbers
		//-----------------------------------------

		$row['posts']  = $this->registry->getClass('class_localization')->formatNumber( $row['posts'] );

		//-----------------------------------------
		// Last time stuff...
		//-----------------------------------------
		$row['last_post']  = $this->registry->getClass('class_localization')->getDate( $row['last_post'], 'SHORT' );

		/* Tags */
		if ( ! empty( $row['tag_cache_key'] ) )
		{
			$row['tags'] = $this->registry->trackerTags->formatCacheJoinData( $row );
		}

		// Add in fields
		$row['fields']			= $this->tracker->fields()->display( $row, 'project' );

		return $row;
	}

	//-----------------------------------------
	// Rebuild issue
	//-----------------------------------------
	public function rebuildIssue( $tid )
	{
		$tid = intval($tid);

		if ( $this->settings['tracker_post_order_column'] != 'post_date' )
		{
			$this->settings['tracker_post_order_column'] = 'pid';
		}

		if ( $this->settings['tracker_post_order_sort'] != 'desc' )
		{
			$this->settings['tracker_post_order_sort'] = 'asc';
		}

		//-----------------------------------------
		// Get the correct number of replies
		//-----------------------------------------

		$posts = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as posts', 'from' => 'tracker_posts', 'where' => "issue_id={$tid}" ) );

		$pcount = intval( $posts['posts'] - 1 );

		//-----------------------------------------
		// Get last post info
		//-----------------------------------------
		$last_post = $this->DB->buildAndFetch(
			array(
				'select'   => 'p.post_date, p.issue_id, p.author_id, p.author_name, p.pid',
				'from'     => array( 'tracker_posts' => 'p' ),
				'add_join' => array(
					0 => array(
						'select' => 't.project_id',
						'from'   => array( 'tracker_issues' => 't' ),
						'where'  => 't.issue_id=p.issue_id',
						'type'   => 'left'
					),
					1 => array(
						'select' => 'm.member_id, m.members_display_name',
						'from'   => array( 'members' => 'm' ),
						'where'  => 'm.member_id=p.author_id',
						'type'   => 'left'
					),
				),
				'where' => 'p.issue_id=' . $tid,
				'order' => 'p.' . $this->settings['tracker_post_order_column'] . ' DESC',
				'limit' => array( 0, 1 )
			)
		);

		// 2.1 feeds could be newer than last post
		$check_last_post = $this->DB->buildAndFetch(
			array(
				'select'   => 'p.date, p.issue_id, p.mid',
				'from'     => array( 'tracker_field_changes' => 'p' ),
				'add_join' => array(
					0 => array(
						'select' => 't.project_id',
						'from'   => array( 'tracker_issues' => 't' ),
						'where'  => 't.issue_id=p.issue_id',
						'type'   => 'left'
					),
					1 => array(
						'select' => 'm.member_id, m.members_display_name',
						'from'   => array( 'members' => 'm' ),
						'where'  => 'm.member_id=p.mid',
						'type'   => 'left'
					),
				),
				'where' => 'p.issue_id=' . $tid,
				'order' => 'p.date DESC',
				'limit' => array( 0, 1 )
			)
		);
		
		// Compare
		if ( $check_last_post['date'] > $last_post['post_date'] )
		{
			$last_post = $check_last_post;
			
			// DB Schema
			$last_post['post_date']	= $last_post['date'];
			$last_post['author_id']	= $last_post['mid'];
		}
		
		$last_poster_name = $last_post['members_display_name'] ? $last_post['members_display_name'] : $last_post['author_name'];

		//-----------------------------------------
		// Get first post info
		//-----------------------------------------

		$this->DB->build(
			array(
				'select'   => 'p.post_date, p.author_id, p.author_name, p.pid',
				'from'     => array( 'tracker_posts' => 'p' ),
				'where'    => "p.issue_id=$tid",
				'order'    => "p.{$this->settings['tracker_post_order_column']} ASC",
				'limit'    => array(0,1),
				'add_join' => array(
					0 => array(
						'select' => 'm.member_id, m.members_display_name',
						'from'   => array( 'members' => 'm' ),
						'where'  => "p.author_id=m.member_id",
						'type'   => 'left'
					)
				)
			)
		);

		$this->DB->execute();

		$first_post = $this->DB->fetch();

		$first_poster_name = $first_post['members_display_name'] ? $first_post['members_display_name'] : $first_post['author_name'];

		//-----------------------------------------
		// Get number of attachments
		//-----------------------------------------
		$attach = $this->DB->buildAndFetch(
			array(
				'select'   => 'COUNT(*) as count',
				'from'     => array( 'attachments' => 'a' ),
				'where'    => "p.issue_id={$tid} AND a.attach_rel_module='tracker'",
				'add_join' => array(
					0 => array(
						'from'   => array( 'tracker_posts' => 'p' ),
						'where'  => 'p.pid=a.attach_rel_id',
						'type'   => 'left'
					)
				)
			)
		);

		//-----------------------------------------
		// Update topic
		//-----------------------------------------
		$this->DB->force_data_type = array(
			'starter_name'     => 'string',
			'last_poster_name' => 'string'
		);

		$this->DB->update(
			'tracker_issues',
			array(
				'last_post'         => $last_post['post_date'] ? $last_post['post_date'] : $first_post['post_date'],
				'last_poster_id'    => $last_post['author_id'] ? $last_post['author_id'] : ( $pcount > 0 ? 0 : $first_post['author_id'] ),
				'last_poster_name'  => $last_poster_name ? $last_poster_name : ( $pcount > 0 ? $this->lang->words['global_guestname'] : $first_poster_name ),
				'posts'             => $pcount,
				'starter_id'        => $first_post['author_id'],
				'starter_name'      => $first_poster_name,
				'start_date'        => $first_post['post_date'],
				'firstpost'         => $first_post['pid'],
				'hasattach'         => intval($attach['count'])
			),
			'issue_id='.$tid
		);

		//-----------------------------------------
		// Update first post
		//-----------------------------------------
		if ( (!isset($first_post['new_issue']) OR $first_post['new_issue'] != 1) and $first_post['pid'] )
		{
			$this->DB->update( 'tracker_posts', array( 'new_issue' => 0 ), 'issue_id=' . $tid, true );
			$this->DB->update( 'tracker_posts', array( 'new_issue' => 1 ), 'pid=' . $first_post['pid'], true );
		}

		return TRUE;
	}
	
	/**
	 * Set our issue data
	 * @param array or null $issueData
	 */
	public function setIssueData( $issueData )
	{
		$this->issueData = $issueData;
	}
}

?>