<?php

/**
* Tracker 2.1.0
* 
* RSS output plugin
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author 		$Author: stoo2000 $
* @copyright	(c) 2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @link			http://ipbtracker.com
* @since		6/24/2008
* @version		$Revision: 1363 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class rss_output_tracker
{
	/**
	* Expiration date
	*
	* @access	private
	* @var		integer			Expiration timestamp
	*/
	private $expires = 0;

	
	/**
	 * Grab the RSS links
	 *
	 * @access	public
	 * @return	string		RSS document
	 */
	public function getRssLinks()
	{
		return array();
	}

	/**
	 * Grab the RSS document content and return it
	 *
	 * @access	public
	 * @return	string		RSS document
	 */
	public function returnRSSDocument()
	{
		//--------------------------------------------
		// Set up some registry shortcuts
		//--------------------------------------------

		$this->registry   = ipsRegistry::instance();
		$this->DB         = $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->member     = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();

		$this->project_id	= $this->request['id'] ? intval($this->request['id']) : 0;

		//--------------------------------------------
		// Require classes
		//--------------------------------------------

		require_once( IPS_KERNEL_PATH . 'classRss.php' );
		$class_rss              =  new classRss();
		$class_rss->doc_type    =  ipsRegistry::$settings['gb_char_set'];

		IPSText::getTextClass( 'bbcode' )->bypass_badwords	= 0;

		//--------------------------------------------
		// Is the project really a project?
		//--------------------------------------------

		$p_check = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'title, enable_rss', 'from' => 'tracker_projects', 'where' => "project_id = {$this->project_id}" ) );

		if( ! $p_check['title'] || $p_check['enable_rss'] == '0')
		{
			$this->registry->output->showError( 'The RSS feed you tried to access does not exist!', '20T110' );
		}

		//--------------------------------------------
		// Ok then, are we good to carry on?
		//--------------------------------------------
		$app_class_tracker	= ipsRegistry::getAppClass( 'tracker' );

		$this->registry->tracker->projects()->createPermShortcuts( $this->project_id );

		if ( ! $this->member->tracker['show_perms'] )
		{
			$this->registry->output->showError( 'You do not have permission to view this project', '10T100' );
		}

		if ( ! $this->member->tracker['read_perms'] )
		{
			$this->registry->output->showError( 'You do not have permission to read issues within this project ', '30T100' );
		}

		//--------------------------------------------
		// Set up the channel
		//--------------------------------------------

		$channel_id = $class_rss->createNewChannel(
			array(
				'title'         => "{$this->settings['board_name']}: {$p_check['title']}",
				'link'        => "{$this->settings['board_url']}/index.php?app=tracker",
				'pubDate'     => $class_rss->formatDate( time() ),
				'ttl'         => 30 * 60,
			)
		);

		//--------------------------------------------
		// Gather the issues together
		//--------------------------------------------
			
		ipsRegistry::DB()->build(
			array(
				'select'   => 't.*',
				'from'     => array('tracker_issues' => 't'),
				'add_join' => array( 
					array(
						'select' => 'o.use_html, o.use_ibc',
						'from'   => array( 'tracker_projects' => 'o' ),
						'where'  => "o.project_id={$this->project_id}",
						'type'   => 'left'
					)
				),
				'where' => "t.project_id={$this->project_id}",
				'order' => 't.start_date DESC',
				'limit' => array( 0,10 )
			)
		);
		$outer = ipsRegistry::DB()->execute();	

		while( $r = ipsRegistry::DB()->fetch($outer) )
		{
			// Hide private issues
			if ( $r['module_privacy'] )
			{
				continue;
			}
			
			//--------------------------------------------
			// Grab the issues' post
			//--------------------------------------------

			$post = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'post, use_emo', 'from' => 'tracker_posts', 'where' => "issue_id={$r['issue_id']} AND new_issue=1") );

			//--------------------------------------------
			// Put together all the parse settings
			//--------------------------------------------

			IPSText::getTextClass( 'bbcode' )->parse_bbcode  = $r['use_ibc'];
			IPSText::getTextClass( 'bbcode' )->parse_html    = $r['use_html'];
			IPSText::getTextClass( 'bbcode' )->parse_smilies = $post['use_emo'] ? 1: 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br   = 1;
			
			$post['post'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $post['post'] );
			
			//--------------------------------------------
			// Add it to the channel
			//--------------------------------------------
		
			$class_rss->addItemToChannel(
				$channel_id,
				array(
					'title'       => $r['title'],
					'link'        => ipsRegistry::getClass('output')->formatUrl( ipsRegistry::getClass('output')->buildUrl( "app=tracker&amp;showissue={$r['issue_id']}", 'public' ), IPSText::makeSeoTitle( $r['title'] ), 'showissue' ),
					'description' => $post['post'],
					'pubDate'     => $class_rss->formatDate( $r['start_date'] ),
					'guid'        => $r['issue_id']
				)
			);
		}

		//--------------------------------------------
		// Send it all through to output
		//--------------------------------------------

		$class_rss->createRssDocument();

		$class_rss->rss_document = ipsRegistry::getClass('output')->replaceMacros( $class_rss->rss_document );

		return $class_rss->rss_document;
	}

	/**
	 * Grab the RSS document expiration timestamp
	 *
	 * @access	public
	 * @return	integer		Expiration timestamp
	 */
	public function grabExpiryDate()
	{
		// Generated on the fly, so just return expiry of one hour
		return time() + 3600;
	}
}

?>