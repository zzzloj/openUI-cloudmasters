<?php

/**
 * Product Title:		(SOS34) Track Members
 * Product Version:		1.0.0
 * Author:				Adriano Faria
 * Website:				SOS Invision
 * Website URL:			http://forum.sosinvision.com.br/
 * Email:				administracao@sosinvision.com.br
 */
 
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_trackmembers_core_trackmembers extends ipsCommand
{
	public function doExecute( ipsRegistry $registry )
	{	
		$this->registry->class_localization->loadLanguageFile( array( 'public_trackmembers' ), 'trackmembers' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );		
		
		if ( !IPSMember::isInGroup( $this->memberData, explode( ',', $this->settings['trackmembers_cantrackgroups'] ) ) )
		{
			$this->registry->output->showError( 'no_permission' );
		}
		
        switch( $this->request['do'] )
        {
			case 'track':
				$this->_trackMember( $mid );
			break;

			case 'untrack':
				$this->_unTrackMember( $mid );
			break;

        	default:
        		$this->_viewTrackLogs( $mid );
        	break;
        }

		$this->registry->output->addContent( $this->output );
		$this->registry->getClass('output')->sendOutput();
	}

    public function _trackMember( $mid )
    {
		if ( !IPSMember::isInGroup( $this->memberData, explode( ',', $this->settings['trackmembers_groups'] ) ) )
		{
			$this->registry->output->showError( 'no_permission' );
		}
		
		$mid = intval( $this->request['mid'] );

		if ( !$mid )
		{
			$this->registry->output->showError( 'no_permission' );
		}

		$referrer = my_getenv( 'HTTP_REFERER' );

		IPSMember::save( $mid, array( 'core' => array( 'member_tracked' => 1, 'member_tracked_deadline' => 0 ) ) );

		$this->registry->getClass('output')->redirectScreen( $this->lang->words['tracking_member'], $referrer );
	}

    public function _unTrackMember( $mid )
    {
		if ( !IPSMember::isInGroup( $this->memberData, explode( ',', $this->settings['trackmembers_groups'] ) ) )
		{
			$this->registry->output->showError( 'no_permission' );
		}
		
		$mid = intval( $this->request['mid'] );

		if ( !$mid )
		{
			$this->registry->output->showError( 'no_permission' );
		}

		$referrer = my_getenv( 'HTTP_REFERER' );

		IPSMember::save( $mid, array( 'core' => array( 'member_tracked' => 0, 'member_tracked_deadline' => 0 ) ) );

		$this->registry->getClass('output')->redirectScreen( $this->lang->words['untracked_member'], $referrer );
	}

    public function _viewTrackLogs( $mid )
    {
		$mid = intval( $this->request['mid'] );

		if ( !$mid )
		{
			$this->registry->output->showError( 'no_permission' );
		}

		//-----------------------------------------
		// Figure out sort order, day cut off, etc
		//-----------------------------------------

		$sort_keys		=  array( 'date'	=> 'sort_by_date',
							   	  'app'		=> 'sort_by_app',
		);
		
		$sort_by_keys = array( 'Z-A'  => 'descending_order',
						 	   'A-Z'  => 'ascending_order'
		);
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'trackmembers' ) . '/extensions/coreExtensions.php', 'trackMemberMapping' );
    	$mapping = new $classToLoad;
						     
		$filter_keys  = $mapping->functionToLangStrings();
		$filter_keys['all'] = 'logfilter_all';
		
	    //-----------------------------------------
	    // Additional queries?
	    //-----------------------------------------
	    
	    $add_query_array = array();
	    $add_query       = "";

	    $sort_key		= $this->selectVariable( array(
												1 => ! empty( $this->request['sort_key'] ) ? $this->request['sort_key'] : NULL,
												2 => 'date'		    
		) );

		$sort_by		= $this->selectVariable( array(
												1 => ! empty( $this->request['sort_by'] ) ? $this->request['sort_by'] : NULL,
												2 => 'Z-A'
		) );	
												
		$logfilter	= $this->selectVariable( array(
												1 => ! empty( $this->request['logfilter'] ) ? $this->request['logfilter'] : NULL,
												2 => 'all'				      
		) );
		
		/* HACKER */
		if( ( ! isset( $filter_keys[ $logfilter ] ) ) OR ( ! isset( $sort_keys[ $sort_key ] ) ) OR ( ! isset( $sort_by_keys[ $sort_by ] ) ) )
		{
			$this->registry->output->showError( 'no_permission', 1 );
		}
		
		if ( $logfilter != 'all' )
		{
			$add_query_array[] = "function = '{$logfilter}'";
		}

		if( count( $add_query_array ) )
		{
			$_SQL_EXTRA	= ' AND '. implode( ' AND ', $add_query_array );
		}
		
		$r_sort_by = $sort_by == 'A-Z' ? 'ASC' : 'DESC';

		

		/* pagination */
		$count	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'members_tracker', 'member_id = ' . $mid . $_SQL_EXTRA ) );
		$rows	= array();

		$each	= 10;

		if ( version_compare( IPB_LONG_VERSION, '34000', '>=' ) )
		{
			$st = ( intval($this->request['page']) > 1 ) ? ( ( intval($this->request['page']) - 1 ) * $each ) : 0;
			
			$pages  = $this->registry->output->generatePagination( array( 
				'totalItems'		=> $count['total'],
				'itemsPerPage'		=> $each,
				'currentStartValue'	=> intval( $this->request['page'] ),
				'isPagesMode'		=> true,
				'baseUrl'			=> "app=trackmembers&module=core&section=trackmembers&mid={$mid}&amp;sort_by={$sort_by}&amp;sort_key={$sort_key}&amp;logfilter={$logfilter}"	
			) );
		}
		else
		{
			$st		= intval($this->request['st']);
			$pages  = $this->registry->output->generatePagination( array( 
				'totalItems'		=> $count['total'],
				'itemsPerPage'		=> $each,
				'currentStartValue'	=> $st,
				'baseUrl'			=> "app=trackmembers&module=core&section=trackmembers&mid={$mid}&amp;sort_by={$sort_by}&amp;sort_key={$sort_key}&amp;logfilter={$logfilter}"	
			) );
		}
		

		$member = IPSMember::load( $mid, 'members,profie_portal,sessions' );
		$member = IPSMember::buildDisplayData( $member );
		$logs = array();

		$this->DB->build( array( 
			'select' 	=> '*', 
			'from' 		=> 'members_tracker', 
			'where' 	=> 'member_id = ' . $mid . $_SQL_EXTRA, 
			'order' 	=> "{$sort_key} {$r_sort_by}", 
			'limit' 	=> array( $st, $each ) 
		) );

		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$logs[] = $r;
		}

		$member['_last_active'] = $this->registry->getClass( 'class_localization')->getDate( $member['last_activity'], 'SHORT' );

		//-----------------------------------------
		// Online?
		//-----------------------------------------

		$time_limit			= time() - ( $this->settings['au_cutoff'] * 60 );
		$member['_online']	= 0;
		$bypass_anon		= $this->memberData['g_access_cp'] ? 1 : 0;

		list( $be_anon, $loggedin )	= explode( '&', empty($member['login_anonymous']) ? '0&0' : $member['login_anonymous'] );

		/* Is not anon but the group might be forced to? */
		if ( empty( $be_anon ) && IPSMember::isLoggedInAnon( $member ) )
		{
			$be_anon = 1;
		}

		/* Finally set the online flag */
		if ( ( ( $member['last_visit'] > $time_limit OR $member['last_activity'] > $time_limit ) AND ( $be_anon != 1 OR $bypass_anon == 1 ) ) AND $loggedin == 1 )
		{
			$member['_online'] = 1;
		}

		/* Load status class */
		if ( ! $this->registry->isClassLoaded( 'memberStatus' ) )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/member/status.php', 'memberStatus' );
			$this->registry->setClass( 'memberStatus', new $classToLoad( ipsRegistry::instance() ) );
		}
			
		/* Fetch */
		$status = $this->registry->getClass('memberStatus')->fetchMemberLatest( $member['member_id'] );

		//-----------------------------------------
		// Warnings?
		//-----------------------------------------
		
		$warns = array();
		if ( $member['show_warn'] )
		{
			if ( $member['member_banned'] )
			{
				$warns['ban'] = 0;
				$_warn = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'wl_id', 'from' => 'members_warn_logs', 'where' => "wl_member={$member['member_id']} AND wl_suspend<>0", 'order' => 'wl_date DESC', 'limit' => 1 ) );
				if ( $_warn['wl_id'] )
				{
					$warns['ban'] = $_warn['wl_id'];
				}
			}
			if ( $member['temp_ban'] )
			{
				$warns['suspend'] = 0;
				$_warn = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'wl_id', 'from' => 'members_warn_logs', 'where' => "wl_member={$member['member_id']} AND wl_suspend<>0", 'order' => 'wl_date DESC', 'limit' => 1 ) );
				if ( $_warn['wl_id'] )
				{
					$warns['suspend'] = $_warn['wl_id'];
				}
			}
			if ( $member['restrict_post'] )
			{
				$warns['rpa'] = 0;
				$_warn = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'wl_id', 'from' => 'members_warn_logs', 'where' => "wl_member={$member['member_id']} AND wl_rpa<>0", 'order' => 'wl_date DESC', 'limit' => 1 ) );
				if ( $_warn['wl_id'] )
				{
					$warns['rpa'] = $_warn['wl_id'];
				}
			}
			if ( $member['mod_posts'] )
			{
				$warns['mq'] = 0;
				$_warn = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'wl_id', 'from' => 'members_warn_logs', 'where' => "wl_member={$member['member_id']} AND wl_mq<>0", 'order' => 'wl_date DESC', 'limit' => 1 ) );
				if ( $_warn['wl_id'] )
				{
					$warns['mq'] = $_warn['wl_id'];
				}
			}
		}

		//-----------------------------------------
		// Finish off the rest of the page  $filter_keys[$topicfilter]))
		//-----------------------------------------
		
		$sort_by_html	= "";
		$sort_key_html	= "";
		$filter_html	= "";
		
		foreach( $sort_by_keys as $k => $v )
		{
			$sort_by_html   .= $k == $sort_by      ? "<option value='$k' selected='selected'>{$this->lang->words[ $sort_by_keys[ $k ] ]}</option>\n"
											       : "<option value='$k'>{$this->lang->words[ $sort_by_keys[ $k ] ]}</option>\n";
		}
		
		foreach( $sort_keys as  $k => $v )
		{
			$sort_key_html  .= $k == $sort_key 	   ? "<option value='$k' selected='selected'>{$this->lang->words[ $sort_keys[ $k ] ]}</option>\n"
											       : "<option value='$k'>{$this->lang->words[ $sort_keys[ $k ] ]}</option>\n";
		}
		
		foreach( $filter_keys as  $k => $v )
		{
			$filter_html    .= $k == $logfilter    ? "<option value='$k' selected='selected'>{$this->lang->words[ $v ]}</option>\n"
												   : "<option value='$k'>{$this->lang->words[ $v ]}</option>\n";
		}

		$footer_filter['sort_by']		= $sort_key_html;
		$footer_filter['sort_order']	= $sort_by_html;
		$footer_filter['topic_filter']	= $filter_html;

		$template = $this->registry->output->getTemplate( 'trackmembers' )->trackMemberLogs( $logs, $member, $status, $pages, $footer_filter );

		$this->registry->output->setTitle( $this->settings['board_name'] );

		$this->registry->output->addContent( $template );

		$this->nav[] = array( $member['members_display_name'], 'showuser='.$member['member_id'], $member['members_seo_name'], 'showuser' );
		$this->nav[] = array(  $this->lang->words['viewing_tracking_logs'] );

		foreach( $this->nav as $nav )
		{
			$this->registry->output->addNavigation( $nav[0], $nav[1] );	
		}

		$this->registry->getClass('output')->setTitle( $this->lang->words['tracking_logs_from'] . ' ' . $member['members_display_name'] . ' - ' . ipsRegistry::$settings['board_name'] );
	}

	/**
	 * Given an array of possible variables, the first one found is returned
	 *
	 * @param	array 	Mixed variables
	 * @return	mixed 	First variable from the array
	 * @since	2.0
	 */
    public static function selectVariable($array)
    {
    	if ( !is_array($array) ) return -1;

    	ksort($array);

    	$chosen = -1;

    	foreach ($array as $v)
    	{
    		if ( isset($v) )
    		{
    			$chosen = $v;
    			break;
    		}
    	}

    	return $chosen;
    }
}