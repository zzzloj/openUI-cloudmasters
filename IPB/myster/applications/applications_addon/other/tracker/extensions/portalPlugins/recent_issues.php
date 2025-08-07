<?php

/**
* Tracker 2.1.0
* 
* PORTAL PLUG IN MODULE: Recent issues
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	PortalPlugIn
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ppi_recent_issues extends public_portal_portal_portal 
{
	/**
	* Registry object
	*
	* @access    protected
	* @var        object
	*/
	protected $registry;

	/**
	* Database object
	*
	* @access    protected
	* @var        object
	*/
	protected $DB;

	/**
	* Settings object
	*
	* @access    protected
	* @var        object
	*/
	protected $settings;

	/**
	* Request object
	*
	* @access    protected
	* @var        object
	*/
	protected $request;

	/**
	* Language object
	*
	* @access    protected
	* @var        object
	*/
	protected $lang;

	/**
	* Member object
	*
	* @access    protected
	* @var        object
	*/
	protected $member;

	/**
	* Cache object
	*
	* @access    protected
	* @var        object
	*/
	protected $cache;

	/**
	* Constructor
	*
	* @access    public
	* @param     object        ipsRegistry reference
	* @return    void
	*/
	public function __construct( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry   = $registry;
		$this->DB         = $this->registry->DB();
		$this->settings   = $this->registry->settings();
		$this->request    = $this->registry->request();
		$this->lang       = $this->registry->getClass('class_localization');
		$this->member     = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      = $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();

		/* Load lang */
		$this->registry->class_localization->loadLanguageFile( array( 'public_portal' ) );
	}

	/**
	* Recent_issues_discussions_last_x returns the last X issues started in projects
	* the user has permissions to see for the portal.
	*
	* @access    public
	* @return    string
	*/
	public function recent_issues_discussions_last_x()
	{
		/* INIT */
		$html  = "";
		$limit = $this->settings['tracker_issues_portal_lastx'] ? $this->settings['tracker_issues_portal_lastx'] : 5;

		/* What projects are we allowed to view? */
		$allowedProjects = array();

		//TODO: Why a query here? Let's use the cache if possible :)
		$this->DB->build( array( 'select' => 'project_id, project_read_perms', 'from'=> 'tracker_projects' ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			if( $this->registry->getClass('class_localization')->checkPermissions( $r['project_read_perms'] ) )
			{
				$allowedProjects[] = $r['project_id'];
			}
		}

		if( count( $allowedProjects ) > 0 )
		{
			$qe = "i.project_id IN(".implode(',', $allowedProjects ).") AND ";
		}
		else
		{
			return;
		}

		/* Retrieve needed information from database */
		$this->DB->build(
			array(
				'select'   => 'i.issue_id, i.issue_title, i.issue_posts, i.issue_starter_id as member_id, i.issue_starter_name as member_name, i.issue_start_date as post_date',
				'from'     => array( 'tracker_issues' => 'i' ),
				'where'    => "$qe issue_state != 'closed'",
				'add_join' => array(
					0 => array (
						'select' => 'p.title',
						'from'   => array( 'tracker_projects' => 'p' ),
						'where'  => 'p.project_id=i.project_id',
						'type'   => 'left'
					),
					1 => array(
						'select' => 'c.cat_title',
						'from'   => array( 'tracker_categories' => 'c' ),
						'where'  => 'c.cat_id=i.cat_id',
						'type'   => 'left'
					)
				),
				'order' => 'issue_start_date DESC',
				'limit' => array( 0, $limit )
			)
		);
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			$html .= $this->tmplFormatIssue($row, 30);
		}

		/* Return HTML */
		return $this->registry->getClass('output')->getTemplate('skin_tracker_global')->tmpl_latestissues($html);
	}

	/**
	* TmplFormatIssues parses the issues for the portal plugin display, including
	* shortening long issue titles to a desired length.
	*
	* @access    private
	* @param     string        The issue row to be parsed
	* @param     int           Cutoff length for long issues titles
	* @return    string
	*/
	private function tmplFormatIssue( string $entry, int $cut )
	{
		$entry['issue_title'] = strip_tags($entry['issue_title']);
		$entry['issue_title'] = str_replace( "&#33;" , "!" , $entry['issue_title'] );
		$entry['issue_title'] = str_replace( "&quot;", "\"", $entry['issue_title'] );

		if (strlen($entry['issue_title']) > $cut)
		{
			$entry['issue_title'] = substr( $entry['issue_title'],0,($cut - 3) ) . "...";
			$entry['issue_title'] = preg_replace( '/&(#(\d+;?)?)?(\.\.\.)?$/', '...',$entry['issue_title'] );
		}

		$entry['issue_title'] = $entry['issue_title'] . " (" . $entry['cat_title'] . ")";

		$entry['issue_posts'] = $this->registry->getClass('class_localization')->formatNumber($entry['issue_posts']);

		$this->settings['csite_article_date'] = $this->settings['csite_article_date'] ? $this->settings['csite_article_date'] : 'm-j-y H:i';

		$entry['date']  = gmdate( $this->settings['csite_article_date'], $entry['post_date'] + $this->registry->getClass('class_localization')->getTimeOffset() );

		return $this->registry->getClass('output')->getTemplate('skin_tracker_global')->tmpl_issuerow($entry);
	}
}

?>