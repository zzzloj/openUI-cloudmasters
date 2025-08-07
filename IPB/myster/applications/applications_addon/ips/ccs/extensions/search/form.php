<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Returns HTML for the form (optional class, not required)
 * Last Updated: $Date: 2011-12-20 13:34:04 -0500 (Tue, 20 Dec 2011) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10037 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_form_ccs
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
	 * Construct
	 * 
	 * @access	public
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make object */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Language */
		ipsRegistry::instance()->getClass('class_localization')->loadLanguageFile( array( 'public_lang' ), 'ccs' );
		
		/* Load the caches */
		$this->cache->getCache( array( 'ccs_databases', 'ccs_fields' ) );

		/* Set some params for template */
		if( !$this->request['search_app_filters']['ccs']['searchInKey'] )
		{
			IPSSearchRegistry::set( 'ccs.searchInKey', $this->settings['ccs_default_search'] );
			$this->request['search_app_filters']['ccs']['searchInKey']	= $this->settings['ccs_default_search'];
		}
	}

	/**
	 * Return sort drop down
	 * 
	 * @access	public
	 * @return	array
	 */
	public function fetchSortDropDown()
	{
		//-----------------------------------------
		// Generic pages
		//-----------------------------------------
		
		$array = array();
		
		if( $this->request['do'] != 'user_activity' )
		{
			$array = array( 
							'pages' => array( 
												'date'		=> $this->lang->words['ss_page_date'],
												'title'		=> $this->lang->words['ss_page_title'],
											),
						);
		}

		//-----------------------------------------
		// Databases (and comments)
		//-----------------------------------------
		
		if( is_array($this->caches['ccs_databases']) AND count($this->caches['ccs_databases']) )
		{
			foreach( $this->caches['ccs_databases'] as $database )
			{
				if ( $this->registry->permissions->check( 'view', $database ) == TRUE AND $database['database_search'] )
				{
					$array['database_' . $database['database_id'] ]	= array(
																			'date_added'	=> $this->lang->words['ss_db_adddate'],
																			'date_updated'	=> $this->lang->words['ss_db_editdate'],
																			'rating'		=> $this->lang->words['ss_db_rating'],
																			'views'			=> $this->lang->words['ss_db_views'],
																			);

					if( is_array($this->caches['ccs_fields'][ $database['database_id'] ]) AND count($this->caches['ccs_fields'][ $database['database_id'] ]) )
					{
						foreach( $this->caches['ccs_fields'][ $database['database_id'] ] as $_field )
						{
							if( in_array( $_field['field_type'], array( 'input', 'textarea', 'radio', 'select' ) ) )
							{
								$array['database_' . $database['database_id'] ]['field_' . $_field['field_id'] ]	= $_field['field_name'];
							}

							if( 'field_' . $_field['field_id'] == $database['database_field_title'] AND in_array( $_field['field_type'], array( 'input', 'textarea', 'editor' ) ) && $this->request['do'] == 'search' )
							{
								$array['database_' . $database['database_id'] ]['relevancy']	= $this->lang->words['ss_db_relevancy'];
							}

							if( 'field_' . $_field['field_id'] == $database['database_field_content'] AND in_array( $_field['field_type'], array( 'input', 'textarea', 'editor' ) ) && $this->request['do'] == 'search' )
							{
								$array['database_' . $database['database_id'] ]['relevancy']	= $this->lang->words['ss_db_relevancy'];
							}
						}
					}
					
					if( !$database['database_forum_comments'] AND trim( $database['perm_5'], ' ,' ) )
					{
						$array['database_' . $database['database_id'] . '_comments' ]	= array(
																							'date'	=> $this->lang->words['ss_db_comm_date'],
																							);
					}
				}
			}
		}

		return $array;
	}
	
	/**
	 * Return sort in
	 * Optional function to allow apps to define searchable 'sub-apps'.
	 * 
	 * @access	public
	 * @return	array
	 */
	public function fetchSortIn()
	{
		$array = array();
		
		if( $this->request['do'] != 'user_activity' AND !$this->request['search_tags'] )
		{
			$array = array( 
							array( 'pages', $this->lang->words['ss_type_pages'] ) 
						);
		}

		//-----------------------------------------
		// Databases (and comments)
		//-----------------------------------------

		if( is_array($this->cache->getCache('ccs_databases')) AND count($this->cache->getCache('ccs_databases')) )
		{
			foreach( $this->cache->getCache('ccs_databases') as $database )
			{
				if ( $this->registry->permissions->check( 'view', $database ) == TRUE AND $database['database_search'] )
				{
					$array[]	= array( 'database_' . $database['database_id'], $database['database_name'] );

					if( !$database['database_forum_comments'] AND trim( $database['perm_5'], ' ,' ) AND !$this->request['search_tags'] )
					{
						$array[]	= array( 'database_' . $database['database_id'] . '_comments', sprintf( $this->lang->words['ss_type_comm_suff'], $database['database_name'] ) );
					}
				}
			}
		}
		
		return $array;
	}
}
