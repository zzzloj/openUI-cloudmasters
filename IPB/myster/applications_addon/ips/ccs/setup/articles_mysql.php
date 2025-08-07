<?php

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class articleTables_mysql
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Run query
	 *
	 * @access	public
	 * @param	int		Database id
	 * @param	array 	Fields
	 * @return	@e void
	 */
	public function createTable( $_databaseId, $_fieldIds )
	{
		/* Create the table */
		$query = "CREATE TABLE " . $this->registry->dbFunctions()->getPrefix() . "ccs_custom_database_{$_databaseId} (
			primary_id_field INT NOT NULL auto_increment PRIMARY KEY,
			member_id INT NOT NULL DEFAULT '0',
			record_saved INT(10) NOT NULL DEFAULT '0',
			record_updated INT(10) NOT NULL DEFAULT '0',
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
			record_topicid INT NOT NULL DEFAULT '0',";
			
		foreach( $_fieldIds as $_field )
		{
			$query	.= "\n			{$_field} MEDIUMTEXT NULL,";
		}
			
		$query .= "\n			KEY record_approved (record_approved),
			KEY record_static_furl (record_static_furl),
			KEY record_topicid (record_topicid),
			KEY category_id (category_id)";

		foreach( $_fieldIds as $_key => $_field )
		{
			if( in_array( $_key, array( 'article_title', 'article_body', 'video_title', 'video_description' ) ) )
			{
				$query	.= ",\n			FULLTEXT ({$_field})";
			}
		}

		$query .= "\n			) ENGINE=MyISAM";
		
		$return_die				= $this->DB->return_die;
		$this->DB->return_die	= true;
		$this->DB->query( $query );
		$this->DB->return_die	= $return_die;
		
		return;
	}
}
