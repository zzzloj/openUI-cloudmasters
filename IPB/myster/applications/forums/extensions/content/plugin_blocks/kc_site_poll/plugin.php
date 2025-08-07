<?php

/**
 * Show a poll widget
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9350 $ 
 * @since		1st March 2009
 * @modified by Keith Connell
 */

class plugin_kc_site_poll implements pluginBlockInterface
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $DB;
	protected $settings;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	protected $registry;
	protected $request;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------
		
		$this->registry		= $registry;
		$this->DB			= $registry->DB();
		$this->settings		= $registry->fetchSettings();
		$this->member		= $registry->member();
		$this->memberData	=& $registry->member()->fetchMemberData();
		$this->cache		= $registry->cache();
		$this->caches		=& $registry->cache()->fetchCaches();
		$this->request		= $registry->fetchRequest();
		$this->lang 		= $registry->class_localization;
	}
	
	/**
	 * Return the tag help for this block type
	 *
	 * @access	public
	 * @return	array
	 */
	public function getTags()
	{
		return array(
					$this->lang->words['block_plugin__generic'] => array( 
																		array( '&#36;title', $this->lang->words['block_custom__title'] ) ,
																		array( '&#36;content', $this->lang->words['block_plugin_sp_content'] ) 
																		),
					);
	}
	
	/**
	 * Return the plugin meta data
	 *
	 * @access	public
	 * @return	array 			Plugin data (name, description, hasConfig)
	 */
	public function returnPluginInfo()
	{
		return array(
					'key'			=> 'kc_site_poll',
					'name'			=> $this->lang->words['plugin_name__kc_site_poll'],
					'description'	=> $this->lang->words['plugin_description__kc_site_poll'],
					'hasConfig'		=> true,
					'templateBit'	=> 'block__kc_site_poll',
					);
	}
	
	/**
	 * Get plugin configuration data.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnPluginConfig( $session )
	{
		/* Get the forums list for the drop down */
		require_once( IPSLib::getAppDir( 'forums' ) .'/sources/classes/forums/class_forums.php' );
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) .'/sources/classes/forums/admin_forum_functions.php', 'admin_forum_functions', 'forums' );
						
		$forums = new $classToLoad( $this->registry );
		$forums->forumsInit();
		$forums_dd = $forums->adForumsForumList(1);
		
		/* Get the Groups */
		$this->DB->build( array( 'select' => '*', 'from' => 'groups', 'order' => 'g_title ASC' ) );
		$this->DB->execute();
						
		while( $groups = $this->DB->fetch() )
		{
			if ( $groups['g_access_cp'] )
			{
				$groups['g_title'] .= ' ' . $this->lang->words['setting_staff_tag'] . ' ';
			}
					
			$groups_dd[] = array( $groups['g_id'], $groups['g_title'] );
		}
		
		/* Build the config data */
		return array(
					array(
						'label'			=> $this->lang->words['kc_site_poll_forum'],
						'description'	=> $this->lang->words['kc_site_poll_forum_desc'],
						'field'			=> $this->registry->output->formDropdown( 'plugin__kc_site_poll_dd', $forums_dd, $session['config_data']['custom_config']['poll_dd'] ),
						),
					array(
						'label'			=> $this->lang->words['kc_site_poll_groups'],
						'description'	=> $this->lang->words['kc_site_poll_groups_desc'], 
						'field'			=> $this->registry->output->formMultiDropdown( 'plugin__kc_site_poll_g_dd[]', $groups_dd, explode( ",", $session['config_data']['custom_config']['poll_g_dd'] ) ),
						),
					array(
						'label'			=> $this->lang->words['kc_site_poll_pin'],
						'description'	=> $this->lang->words['kc_site_poll_pin_desc'],
						'field'			=> $this->registry->output->formYesNo( 'plugin__kc_site_poll_pin', $session['config_data']['custom_config']['poll_pin'] ),
						),
					);

	}

	/**
	 * Check the plugin config data
	 *
	 * @access	public
	 * @param	array 			Submitted plugin data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Plugin data to use )
	 */
	public function validatePluginConfig( $data )
	{

		return array( 	$data['plugin__kc_site_poll_dd'] ? true : false, array( 	'poll_dd' => $data['plugin__kc_site_poll_dd'],
																					'poll_pin' => $data['plugin__kc_site_poll_pin'],
																					'poll_g_dd'	=> is_array($data['plugin__kc_site_poll_g_dd'])? implode(",", IPSLib::cleanIntArray($data['plugin__kc_site_poll_g_dd'])):$data['plugin__kc_site_poll_g_dd'],
																				) 
					);
	}
	
	/**
	 * Execute the plugin and return the HTML to show on the page.  
	 * Can be called from ACP or front end, so the plugin needs to setup any appropriate lang files, skin files, etc.
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @return	string				Block HTML to display or cache
	 */
	public function executePlugin( $block )
	{
		

		$this->lang->loadLanguageFile( array( 'public_boards', 'public_topic' ), 'forums' );
		$this->lang->loadLanguageFile( array( 'public_editors' ), 'core' );

		$pluginConfig	= $this->returnPluginInfo();
		$templateBit	= $pluginConfig['templateBit'] . '_' . $block['block_id'];
		
		/* Init */
		$config	= unserialize($block['block_config']);
		$showResults = 0;
		$pollData    = array();
		$forum = intval($config['custom']['poll_dd']);
		$pin = intval($config['custom']['poll_pin']);
		$groups = explode(",", IPSText::cleanPermString($config['custom']['poll_g_dd']) );
		$member_groups = array( $this->memberData['member_group_id'] );

		if( $this->memberData['mgroup_others'] )
		{
			$member_groups = array_merge( $member_groups, explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) ) );
		}

		$allow_access = 0;

		foreach( $member_groups as $r )
		{
			foreach($groups as $g )
			{
				if( $r == $g )
				{
					$allow_access = 1;
					break;
				}
			}
		}

		if( !$allow_access )
		{
			return '';
		}

		//-----------------------------------------
		// Get the poll information...
		//-----------------------------------------
		if($pin)
		{
			$topicData = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => "forum_id= {$forum} AND poll_state='1' AND pinned= {$pin}", 'order' => "tid DESC", 'limit' => '1' ) );
		}
		else
		{
			$topicData = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => "forum_id= {$forum} AND poll_state='1'", 'order' => "tid DESC", 'limit' => '1' ) );
		}
		if( !$topicData['tid'])
		{
			return '';
		}

		$poll  = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'polls', 'where' => "tid= {$topicData['tid']}" ) );

		//-----------------------------------------
		// check we have a poll
		//-----------------------------------------

		if( !$poll['pid'] )
		{
			return '';
		}

		//-----------------------------------------
		// Do we have a poll question?
		//-----------------------------------------

		if ( !$poll['poll_question'] )
		{
			$poll['poll_question'] = $topicData['title'];
		}
		
		//-----------------------------------------
		// Additional Poll Vars
		//-----------------------------------------

		$poll['_totalVotes']  = 0;
		$poll['_memberVoted'] = 0;
		$memberChoices        = array();

		//-----------------------------------------
		// Have we voted in this poll?
		//-----------------------------------------

		if ( $poll['poll_view_voters'] AND $this->settings['poll_allow_public'] )
		{
			$this->DB->build( array( 'select'   => 'v.*',
									 'from'     => array( 'voters' => 'v' ),
									 'where'    => 'v.tid=' . $topicData['tid'],
									 'add_join' => array( array( 'select' => 'm.*',
																 'from'   => array( 'members' => 'm' ),
																 'where'  => 'm.member_id=v.member_id',
																 'type'   => 'left' ) ) ) );
		}
		else
		{
			$this->DB->build( array( 'select'   => '*',
									 'from'     => 'voters',
									 'where'    => 'tid=' . $topicData['tid'] ) );
		}

		$this->DB->execute();

		while( $voter = $this->DB->fetch() )
		{
			$poll['_totalVotes']++;

			if ( $voter['member_id'] == $this->memberData['member_id'] )
			{
				$poll['_memberVoted'] = 1;
			}

			/* Member choices */
			if ( $poll['poll_view_voters'] AND $voter['member_choices'] AND $this->settings['poll_allow_public'] )
			{
				$_choices = unserialize( $voter['member_choices'] );

				if ( is_array( $_choices ) AND count( $_choices ) )
				{
					$memberData = array( 'member_id'            => $voter['member_id'],
										 'members_seo_name'     => $voter['members_seo_name'],
										 'members_display_name' => $voter['members_display_name'],
										 'members_colored_name' => str_replace( '"', '\"', IPSMember::makeNameFormatted( $voter['members_display_name'], $voter['member_group_id'] ) ),
										 '_last'                => 0 );

					foreach( $_choices as $_questionID => $data )
					{
						foreach( $data as $_choice )
						{
							$memberChoices[ $_questionID ][ $_choice ][ $voter['member_id'] ] = $memberData;
						}
					}
				}
			}
		}

		//-----------------------------------------
		// Already Voted
		//-----------------------------------------

		if ( $poll['_memberVoted'] )
		{
			$showResults = 1;
		}

		//-----------------------------------------
		// Created poll and can't vote in it
		//-----------------------------------------

		if ( ($poll['starter_id'] == $this->memberData['member_id']) and ($this->settings['allow_creator_vote'] != 1) )
		{
			$showResults = 1;
		}

		//-----------------------------------------
		// Guest, but can view results without voting
		//-----------------------------------------

		if ( ! $this->memberData['member_id'] AND $this->settings['allow_result_view'] )
		{
			$showResults = 1;
		}

		//-----------------------------------------
		// is the topic locked?
		//-----------------------------------------

		if ( $topicData['state'] == 'closed' )
		{
			$showResults = 1;
		}

		//-----------------------------------------
		// Can we see the poll before voting?
		//-----------------------------------------

		if ( $this->settings['allow_result_view'] == 1 AND $this->request['mode'] == 'show' )
		{
			$showResults = 1;
		}

		//-----------------------------------------
		// Parse it
		//-----------------------------------------

		$poll_answers 	 = unserialize(stripslashes($poll['choices']));

		if( !is_array($poll_answers) OR !count($poll_answers) )
		{
			$poll_answers = unserialize( preg_replace( '!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", stripslashes( $poll['choices'] ) ) );
		}
		if( !is_array($poll_answers) OR !count($poll_answers) )
		{
			$poll_answers = '';
		}

		reset($poll_answers);

		foreach ( $poll_answers as $id => $data )
		{
			if( !is_array($data['choice']) OR !count($data['choice']) )
			{
				continue;
			}

			//-----------------------------------------
			// Get the question
			//-----------------------------------------

			$pollData[ $id ]['question'] = $data['question'];

			$tv_poll = 0;

			# Get total votes for this question
			if( is_array($poll_answers[ $id ]['votes']) AND count($poll_answers[ $id ]['votes']) )
			{
				foreach( $poll_answers[ $id ]['votes'] as $number)
				{
					$tv_poll += intval( $number );
				}
			}

			//-----------------------------------------
			// Get the choices for this question
			//-----------------------------------------

			foreach( $data['choice'] as $choice_id => $text )
			{
				$choiceData = array();
				$choice     = $text;
				$voters     = array();

				# Get total votes for this question -> choice
				$votes   = intval($data['votes'][ $choice_id ]);

				if ( strlen($choice) < 1 )
				{
					continue;
				}

				$choice = IPSText::getTextClass( 'bbcode' )->parsePollTags($choice);

				if ( $showResults )
				{
					$percent = $votes == 0 ? 0 : $votes / $tv_poll * 100;
					$percent = sprintf( '%.2F' , $percent );
					$width   = $percent > 0 ? intval($percent * 2) : 0;

					/* Voters */
					if ( $poll['poll_view_voters'] AND $memberChoices[ $id ][ $choice_id ] )
					{
						$voters = $memberChoices[ $id ][ $choice_id ];
						$_tmp   = $voters;

						$lastDude = array_pop( $_tmp );

						$voters[ $lastDude['member_id'] ]['_last'] = 1;
					}

					$pollData[ $id ]['choices'][ $choice_id ] = array( 'votes'   => $votes,
													  				   'choice'  => $choice,
																	   'percent' => $percent,
																	   'width'   => $width,
																	   'voters'  => $voters );
				}
				else
				{
					$pollData[ $id ]['choices'][ $choice_id ] =  array( 'type'   => !empty($data['multi']) ? 'multi' : 'single',
													   					'votes'  => $votes,
																		'choice' => $choice );
				}
			}
		}
			$pluginConfig	= $this->returnPluginInfo();
			$templateBit	= $pluginConfig['templateBit'] . '_' . $block['block_id'];
			$html = $this->registry->output->getTemplate('global')->kc_site_poll( $poll, $topicData, $pollData, $showResults, $block['block_name'] );
			
			ob_start();
	 		$_return	= $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $html );
	 		ob_end_clean();
	 		
			return $_return;
	}
}