<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS mysql abstraction layer for database management
 * Last Updated: $Date: 2011-12-09 18:03:32 -0500 (Fri, 09 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		2nd Sept 2009
 * @version		$Revision: 9979 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class ccs_database_abstraction
{
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
	protected $caches;
	protected $cache;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
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
	
	/**
	 * Create a new database table
	 *
	 * @access	public
	 * @param	string		Table name
	 * @return	bool
	 */
	public function createTable( $name )
	{
		if( !$name )
		{
			return false;
		}
		
		$query = "CREATE TABLE {$name} (
			primary_id_field INT NOT NULL auto_increment PRIMARY KEY,
			member_id INT NOT NULL DEFAULT '0',
			record_saved int(10) NOT NULL DEFAULT '0',
			record_updated int(10) NOT NULL DEFAULT '0',
			post_key VARCHAR(32) NOT NULL DEFAULT '0',
			rating_real SMALLINT NOT NULL DEFAULT '0',
			rating_hits MEDIUMINT NOT NULL DEFAULT '0',
			rating_value MEDIUMINT NOT NULL DEFAULT '0',
			category_id INT NOT NULL DEFAULT '0',
			record_locked TINYINT(1) NOT NULL DEFAULT '0',
			record_comments MEDIUMINT NOT NULL DEFAULT '0',
			record_comments_queued MEDIUMINT NOT NULL DEFAULT '0',
			record_views MEDIUMINT NOT NULL DEFAULT '0',
			record_approved TINYINT(1) NOT NULL DEFAULT '0',
			record_pinned TINYINT(1) NOT NULL DEFAULT '0',
			record_dynamic_furl VARCHAR( 255 ) NULL DEFAULT NULL,
			record_static_furl VARCHAR( 255 ) NULL DEFAULT NULL,
			record_meta_keywords TEXT NULL DEFAULT NULL,
			record_meta_description TEXT NULL DEFAULT NULL,
			record_template INT NOT NULL DEFAULT '0',
			record_topicid INT NOT NULL DEFAULT '0',
			KEY record_approved (record_approved),
			KEY record_static_furl (record_static_furl),
			KEY record_topicid (record_topicid),
			KEY category_id (category_id)
		) ENGINE=MyISAM";

		$return_die				= $this->DB->return_die;
		$this->DB->return_die	= true;
		$this->DB->query( $query );
		$this->DB->return_die	= $return_die;
		
		if( $this->DB->failed )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
}