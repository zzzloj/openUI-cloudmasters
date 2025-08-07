<?php

/**
* Tracker 2.1.0
* 
* Fields Library File
* Last Updated: $Date: 2013-01-27 20:55:22 +0000 (Sun, 27 Jan 2013) $
*
* @author		$Author: RyanH $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @link			http://ipbtracker.com
* @version		$Revision: 1402 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'init.php'.";
	exit();
}

// Have to load some classes if they aren't already
if ( ! class_exists('tracker_cache') )
{
	require_once( IPSLib::getAppDir('tracker') . '/sources/classes/library.php' );
}

if ( ! class_exists('classPublicPermissions') )
{
	require_once( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php' );
}

/**
 * Fields system controller class
 *
 * @package Tracker
 * @since 2.0.0
 */
class tracker_core_fields extends iptCommand
{
	protected $cache;
	protected $perms;
	protected $project;
	protected $changed;

	protected $fields  = array();
	protected $fieldChanges = array();

	protected $replyText;
	
	protected $hasInitialized = 0;
	
	static protected $instance;
	
	static public function instance()
	{
		$out = NULL;
		
		if ( self::$instance === NULL )
		{
			self::$instance = new self( ipsRegistry::instance() );
			self::$instance->makeRegistryShortcuts( ipsRegistry::instance() );
			self::$instance->execute( ipsRegistry::instance() );
		}
		
		$out = self::$instance;
		
		return $out;
	}

	public function doExecute( ipsRegistry $registry )
	{
		$this->cache = $this->tracker->cache('fields');
	}
	
	public function active( $field, $project )
	{
		$field 		= $this->cache->getFieldByKeyword( $field );
		$module		= $this->registry->tracker->modules()->getModuleByID( $field['module_id'] );
		$project	= $this->registry->tracker->projects()->getProject( $project );
		
		// Check module is enabled first.
		if ( ! $module['enabled'] )
		{
			return false;
		}
		
		foreach( $project['fields'] as $k => $v )
		{
			if ( $k == $field['field_id'] )
			{
				return true;
			}
		}
		
		return false;
	}

	// todo @2.2?
	// can we outsource this into library?
	// alex
	public function addNavigation( $library )
	{
		$elements = array();

		if ( is_array( $this->fields ) && count( $this->fields ) && ( get_class( $library ) == 'public_tracker_projects_issues' || get_class( $library ) == 'tracker_core_projects' ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( $field->checkPermission('show') && ! $field->meta['hide_navigation'] )
				{
					try
					{
						$back = $field->addNavigation();

						if ( is_array( $back ) && count( $back ) )
						{
							$elements[] = $back;
						}
					}
					catch( Exception $ex ){}
				}
			}
			
			if ( count( $elements ) )
			{
				foreach( $elements as $key => $value )
				{
					$urlBits = '';

					for( $i = 0; $i <= $key; $i++ )
					{
						$urlBits .= '&' . $elements[ $i ]['key'];
					}

					$this->registry->output->addNavigation( $value['value'], 'app=tracker&showproject=' . $this->project['project_id'] . $urlBits);
				}
			}
		}
	}

	/* @deprecated 2.1 */
	public function addReplyText( $issue, $post )
	{
		$out = '';

		if ( is_array( $this->fields ) && count( $this->fields ) > 0 )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( $field->checkPermission('update') )
				{
					try
					{
						if ( method_exists( $field, 'addReplyText' ) )
						{
							$out .= $field->addReplyText( $issue, $post );
						}
					}
					catch( Exception $ex ){}
				}
			}
		}
		
		if ( $out != '' )
		{
			$out .= '<br />';
		}

		$out .= $post;

		return $out;
	}

	public function checkForInputErrors( $postModule='' )
	{
		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( ( $postModule == 'new' && $field->checkPermission('submit') ) || ( $postModule == 'reply' && $field->checkPermission('update') ) )
				{
					try
					{
						$field->checkForInputErrors( $postModule );
					}
					catch( Exception $ex ){}
				}
			}
		}
	}

	public function commitFieldChangeRecords()
	{
		$date     = IPS_UNIX_TIME_NOW;
		$memberID = $this->memberData['member_id'];
		
		if ( is_array( $this->fieldChanges ) && count( $this->fieldChanges ) )
		{
			foreach( $this->fieldChanges as $k => $change )
			{
				$change['date'] = $date;
				$change['mid']  = $memberID;

				// Insert change
				$this->DB->insert( 'tracker_field_changes', $change );
				
				// Unset
				unset( $this->fieldChanges[$k] );
			}
		}
	}

	public function compileFieldChanges( $issue )
	{
		$out = FALSE;

		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( $field->checkPermission('update') || $field->checkPermission('submit') )
				{
					try
					{
						$check = $field->compileFieldChange( $issue );

						if ( $check === TRUE )
						{
							$out = TRUE;
							$this->setChanged(1);
						}
					}
					catch( Exception $ex ){}
				}
			}
		}

		return $out;
	}
	
	public function checkPermission( $project, $fieldKeyword, $key )
	{
		if ( ! $this->hasInitialized )
		{
			$this->initialize( $project['project_id'] );
		}
		
		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( $field->fCache['field_keyword'] == $fieldKeyword )
				{
					return $field->checkPermission( $key );
				}
			}
		}
		
		// Field isn't enabled, return true
		return true;
	}

	public function display( $issue, $type, $colspan=4 )
	{
		$count = 1;
		$out   = array();

		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( $field->checkPermission('show') )
				{
					// 2.1 META Options - hide in project view
					if ( $type == 'project' && $field->meta['hide_project_view'] )
					{
						continue;
					}
					
					try
					{
						$out[ $k ]          = $field->display( $issue, $type );

						// No content?
						if ( ! $out[ $k ] )
						{
							unset( $out[ $k ] );
							continue;
						}

						$out[ $k ]['count'] = $count;
						$out[ $k ]['keyword']	= $field->fCache['field_keyword'];
						$count++;

						// Column count
						if ( $out[ $k ]['type'] == 'column' )
						{
							$colspan++;
						}
						
						// Try and grab drop downs
						if ( $type == 'issue' && $field->checkPermission('update') )
						{
							$out[ $k ]['dropdown'] = $field->getQuickReplyDropdown( $issue );
						}
					}
					catch( Exception $ex ){}
				}
			}
		}

		// Colspan
		$out['colspan'] = $colspan;

		return $out;
	}
	
	public function extension( $field, $module, $extension )
	{
		return $this->cache->extension( $field, $module, $extension );
	}
	
	public function field( $keyword, $stopExecute=FALSE, $forcePublic=FALSE )
	{
		return $this->cache->field( $keyword, $stopExecute, $forcePublic );
	}
	
	public function formatMeta( $change )
	{
		$out = array();

		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( $field->checkPermission('show') && $change['module'] == $field->mCache['directory'] && $change['title'] == $field->fCache['field_keyword'] )
				{
					try
					{
						$back = $field->formatMeta( $change );

						if ( is_array( $back ) && count( $back ) )
						{
							$out = $back;
						}
					}
					catch( Exception $ex ){}
				}
			}
		}
		
		return $out;
	}

	public function getCache()
	{
		return $this->cache->getCache();
	}
	
	public function getChanged()
	{
		return $this->changed;
	}

	public function getField( $id )
	{
		return $this->cache->getField( $id );
	}

	public function getFieldByName( $name )
	{
		return $this->cache->getFieldByName( $name );
	}

	public function getFieldByKeyword( $keyword )
	{
		return $this->cache->getFieldByKeyword( $keyword );
	}
	
	public function getFieldClass( $id )
	{
		return $this->fields[ $id ];
	}

	public function getQuickReplyDropdowns( $issue )
	{
		$out = array();

		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( $field->checkPermission('update') )
				{
					try
					{
						$back = $field->getQuickReplyDropdown( $issue );

						if ( is_array( $back ) && count( $back ) )
						{
							$out[ $k ] = $back;
						}
					}
					catch( Exception $ex ){}
				}
			}
		}
		
		return $out;
	}

	public function getPostScreen( $postModule )
	{
		$out = array();

		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( ( $postModule == 'new' && $field->checkPermission('submit') ) || ( $postModule == 'reply' && $field->checkPermission('update') ) )
				{
					try
					{
						$back = $field->getPostScreen( $postModule );

						if ( is_array( $back ) && count( $back ) )
						{
							$out[ $k ] = $back;
						}
					}
					catch( Exception $ex ){}
				}
			}
		}

		return $out;
	}

	public function getProjectFilterCookie( $cookie_temp=array() )
	{
		$out = $cookie_temp;

		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( $field->checkPermission('show') )
				{
					try
					{
						$back = $field->getProjectFilterCookie( $cookie_temp );

						if ( is_array( $back ) && count( $back ) )
						{
							$out = array_merge( $out, $back );
						}
					}
					catch( Exception $ex ){}
				}
			}
		}

		return $out;
	}

	public function getProjectFilterDropdowns()
	{
		$out = array();

		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( $field->checkPermission('show') )
				{
					try
					{
						$out[ $field->fCache['field_keyword'] ] = $field->getProjectFilterDropdown();
					}
					catch( Exception $ex ){}
				}
			}
		}

		return $out;
	}

	public function getProjectFilterQueryAdds( $add_query_array=array() )
	{
		$out = $add_query_array;

		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				try
				{
					$back = $field->getProjectFilterQueryAdds();

					if( strlen( $back ) > 0 )
					{
						$out[] = $back;
					}
				}
				catch( Exception $ex ){}
			}
		}

		return $out;
	}

	public function getProjectFilterURLBits()
	{
		$out = '';

		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( $field->checkPermission('show') )
				{
					try
					{
						$back = $field->getProjectFilterURLBit();

						if ( is_string( $back ) && strlen( $back ) > 0 )
						{
							$out .= "&amp;" . $back;
						}
					}
					catch( Exception $ex ){}
				}
			}
		}

		return $out;
	}

	public function initialize( $projectID, $stopExecute=FALSE, $forcePublic=FALSE )
	{
		if ( $this->tracker->projects()->isProject( $projectID ) )
		{
			$this->project = $this->tracker->projects()->getProject( $projectID );
		}

		if ( is_array( $this->project ) && isset( $this->project['fields'] ) && is_array( $this->project['fields'] ) && count( $this->project['fields'] ) )
		{
			foreach( $this->project['fields'] as $id => $meta )
			{
				$field	= $this->cache->getField( $id );
				$module	= $this->tracker->modules()->getModuleById( $field['module_id'] );

				// Normal Tracker modular fields
				if ( is_array( $field ) && count( $field ) && $field['field_keyword'] != 'custom' && $module['enabled'] )
				{
					try
					{
						$this->fields[ $id ] = $this->cache->field( $field['field_keyword'], $stopExecute, $forcePublic );

						// 2.1, cache
						$this->fields[ $id ]->fCache	= $field;
						$this->fields[ $id ]->mCache	= $module;
						$this->fields[ $id ]->meta		= $meta;
						
						$this->fields[ $id ]->initialize( $this->project, $meta );
					}
					catch( Exception $ex ) {}
				}
			}
		}

		// Now load in custom fields
		if ( $this->tracker->modules()->moduleIsInstalled('custom') )
		{
			// Sort out custom fields in the CORE library because its a royal pain trying to make it modular.
			$customLib		= $this->cache->field('custom', $stopExecute, $forcePublic);
			$customCache	= $customLib->initialize( $this->project, $meta );
			
			$module	= $this->tracker->modules()->getModule('custom');
			
			if ( count( $customCache ) )
			{
				foreach( $customCache as $k => $v )
				{
					if ( is_array( $v ) && count( $v ) > 0 )
					{
						$id = 'custom_' . $v['field_id'];
						
						// For meta formatting
						$v['field_keyword'] = $v['field_id'];
						
						// We need to map our custom fields to use a central PHP file, already had to get around it by
						// hardcoding this into the core files, which isn't too bad on it's own - but I want custom fields using
						// their own module libraries so they are abstracted from the core.
						try
						{
							$this->fields[ $id ] = new public_tracker_module_custom_field_custom_field();
							$this->fields[ $id ]->fCache  = $v;
							$this->fields[ $id ]->mCache  = $module;
							$this->fields[ $id ]->meta	  = $v;
							$this->fields[ $id ]->setModuleCache( $module );
							
							// Execute
							if ( ! $stopExecute )
							{
								$this->fields[ $id ]->execute( $this->registry );
							}
							else
							{
								$this->fields[ $id ]->makeRegistryShortcuts( $this->registry );
							}
							
							// Any module specific intialization
							$this->fields[ $id ]->initialize( $this->project, $v );	
						}
						catch( Exception $ex ) {}
					}
				}
			}
		}

		$this->hasInitialized = 1;
	}
	
	public function test(){ print 123333333333; }

	public function parseToSave( $issue )
	{
		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				try
				{
					$issue = array_merge( $issue, $field->parseToSave() );
				}
				catch(Exception $e){}
			}
		}

		return $issue;
	}

	/**
	 * If needed, creates an instance of and return the Tracker Fields permissions class
	 *
	 * @return tracker_core_field_permissions the permissions class
	 * @access public
	 * @since 2.0.0
	 */
	public function perms()
	{
		$out = NULL;

		if ( ! is_object( $this->perms ) )
		{
			$this->perms = new tracker_core_field_permissions( $this->registry );
		}

		$out = $this->perms;

		return $out;
	}

	public function projectFilterSetup( $cookie_temp=array() )
	{
		$out = array();

		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( $field->checkPermission('show') )
				{
					try
					{
						$field->projectFilterSetup( $cookie_temp );
					}
					catch( Exception $ex ){}
				}
			}
		}
	}

	public function rebuild()
	{
		$this->cache->rebuild();
	}

	public function registerFieldChangeRecord( $issueID, $module, $field = '', $oldValue, $newValue )
	{
		if ( $issueID > 0 && strlen( $module ) > 0 && isset($newValue) )
		{
			$issueID = intval( $issueID );

			$this->fieldChanges[] = array( 'issue_id' => $issueID, 'module' => $module, 'title' => $field, 'old_value' => $oldValue, 'new_value' => $newValue );
		}
	}
	
	protected function setChanged( $value=0 )
	{
		$this->changed = $value;
	}

	public function setFieldUpdates( $issue=array() )
	{
		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( $field->checkPermission('submit') || $field->checkPermission('update') )
				{
					try
					{
						$issue = array_merge( $issue, $field->setFieldUpdate( $issue ) );
					}
					catch( Exception $ex ){}
				}
			}
		}

		return $issue;
	}

	public function setIssue( $issue=array() )
	{
		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				try
				{
					$field->setIssue( $issue );
				}
				catch( Exception $ex ){}
			}
		}

		return $issue;
	}

	public function shutdown( $type = '' )
	{
		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				try
				{
					if ( $field->hasShutdown() )
					{
						$field->shutdown( $type );
					}
				}
				catch( Exception $ex ){}
			}
		}
	}

	public function validateFilterInput()
	{
		$out = FALSE;

		if ( is_array( $this->fields ) && count( $this->fields ) )
		{
			foreach ( $this->fields as $k => $field )
			{
				if ( $field->checkPermission('show') )
				{
					try
					{
						$check = $field->validateFilterInput();

						if ( $check === TRUE )
						{
							$out = TRUE;
						}
					}
					catch( Exception $ex ){}
				}
			}
		}

		return $out;
	}
}

/**
 * Field interface to setup required functions
 *
 * @package Tracker
 * @since 2.0.0
 */
interface iField
{
	public function addNavigation();
	// @optional as of 2.1
	// public function addReplyText( $issue, $post );
	public function checkForInputErrors( $postModule );
	public function checkPermission( $type );
	public function compileFieldChange( $row );
	public function getQuickReplyDropdown( $issue );
	public function getPostScreen( $type );
	public function getProjectFilterCookie();
	public function getProjectFilterDropdown();
	public function getProjectFilterQueryAdds();
	public function getProjectFilterURLBit();
	public function initialize( $project, $metadata=array() );
	public function postCommitUpdate( $issue );
	public function projectFilterSetup( $cookie );
	public function parseToSave();
	public function setFieldUpdate( $issue );
	public function setIssue( $issue );
	public function validateFilterInput();
}

/**
 * Fields cache controller
 * 
 * @package Tracker
 * @since 2.0.0
 */
class tracker_core_cache_fields extends tracker_cache
{
	/**
	 * The name of the cache for ease of programming
	 *
	 * @access protected
	 * @var string
	 * @since 2.0.0
	 */
	protected $cacheName = 'tracker_fields';
	
	/**
	 * The processed fields cache from the database
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */
	protected $fieldCache;
	
	/**
	 * The processed field library files
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */	
	protected $fieldLibs;
	
	/**
	 * The processed extension files
	 *
	 * @access protected
	 * @var array
	 * @since 2.0.0
	 */	
	protected $extLibs;

	/**
	 * Initial function.  Called by execute function in ipsCommand 
	 * following creation of this class
	 *
	 * @param ipsRegistry $registry the IPS Registry
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$this->cache->getCache( $this->cacheName );
		$this->fieldCache = $this->caches[ $this->cacheName ];
	}

	public function extension( $field, $module, $extension )
	{
		$out = NULL;

		if ( strlen( $field ) > 0 && strlen( $module ) > 0 && strlen( $extension ) > 0 )
		{
			if ( isset( $this->extLibs[ $field ][ $extension ] ) )
			{
				return $this->extLibs[ $field ][ $extension ];
			}
			else
			{
				if ( ! isset( $this->extLibs[ $field ] ) )
				{
					$this->extLibs[ $keyword ] = array();
				}

				if ( $this->registry->tracker->modules()->moduleIsInstalled( $module ) )
				{
					$type = 'public';

					if ( IN_ACP )
					{
						$type = 'admin';
					}

					$class     = "tracker_{$type}_{$extension}__{$module}_field_{$field}";
					$file      = $this->registry->tracker->modules()->getModuleFolder($module) . 'extensions/' . $type . '/' . $extension . '.php';

					$this->registry->tracker->modules()->loadInterface( $extension . '.php' );

					if ( is_file( $file ) )
					{
						require_once( $file );

						if ( class_exists( $class ) )
						{
							$this->extLibs[ $field ][ $extension ] = new $class();
							$this->extLibs[ $field ][ $extension ]->execute( $this->registry );

							/* Send it back */
							$out = $this->extLibs[ $field ][ $extension ];
						}
					}
				}
			}
		}

		return $out;
	}

	/**
	 * Mini-registry function for fields, provides
	 * access to field source files.
	 *
	 * @param $keyword Field keyword
	 * @return object
	 * @access public
	 * @since 2.0.0
	 */
	public function field( $keyword, $stopExecute=FALSE, $forcePublic=FALSE )
	{
		$out = NULL;

		if ( ! ( strlen( $keyword ) > 0 ) )
		{
			throw new Exception('No Field KEYWORD Provided');
		}

		if ( isset( $this->fieldLibs[ $keyword ] ) && is_object( $this->fieldLibs[ $keyword ] ) )
		{
			$out = $this->fieldLibs[ $keyword ];
		}
		else
		{
			$field  		= $this->getFieldByKeyword( $keyword );
			$module 		= $this->registry->tracker->modules()->getModuleByID( $field['module_id'] );

			if ( $this->registry->tracker->modules()->moduleIsInstalled( $module['directory'] ) )
			{
				$type = 'public';

				if ( IN_ACP && ! $forcePublic )
				{
					$type = 'admin';
				}

				$file = $this->registry->tracker->modules()->getModuleFolder( $module['directory'] ) . 'fields_' . $type . '/' . $keyword . '.php';

				if ( is_file( $file ) )
				{
					require_once( $file );
					$class = $type . '_tracker_module_' . $module['directory'] . '_field_' . $keyword;

					if ( class_exists( $class ) )
					{
						$this->fieldLibs[ $keyword ] = new $class();
						$this->fieldLibs[ $keyword ]->fCache = $field;
						$this->fieldLibs[ $keyword ]->mCache = $module;
						
						if ( ! $stopExecute )
						{
							$this->fieldLibs[ $keyword ]->execute( $this->registry );
						}
						else
						{
							$this->fieldLibs[ $keyword ]->makeRegistryShortcuts( $this->registry );
						}
					}

					/* Send it back */
					$out = $this->fieldLibs[ $keyword ];
				}
			}
		}

		if ( ! is_object( $out ) )
		{
			throw new Exception('Field Class Not Found');
		}

		return $out;
	}

	/**
	 * Returns the processed cache
	 *
	 * @return array the cache with post-DB processing
	 * @access public
	 * @since 2.0.0
	 */
	public function getCache()
	{
		$out = array();

		if ( is_array( $this->fieldCache ) && count( $this->fieldCache ) )
		{
			$out = $this->fieldCache;
		}

		return $out;
	}

	/**
	 * Returns requested field
	 *
	 * @param int $id the field_id
	 * @return array the field data
	 * @access public
	 * @since 2.0.0
	 */
	public function getField( $id )
	{
		$out = array();

		if ( is_array( $this->fieldCache ) && isset( $this->fieldCache[ $id ] ) && is_array( $this->fieldCache[ $id ] ) )
		{
			$out = $this->fieldCache[ $id ];
		}

		return $out;
	}

	/**
	 * Returns requested field
	 *
	 * @param string $name the field title
	 * @return array the field data
	 * @access public
	 * @since 2.0.0
	 */
	public function getFieldByKeyword( $keyword )
	{
		$out = array();

		if ( is_array( $this->fieldCache ) && count( $this->fieldCache ) )
		{
			foreach( $this->fieldCache as $id => $r )
			{
				if ( $r['field_keyword'] == $keyword )
				{
					$out = $r;
					break;
				}
			}
		}

		return $out;
	}

	/**
	 * Returns requested field
	 *
	 * @param string $name the field title
	 * @return array the field data
	 * @access public
	 * @since 2.0.0
	 */
	public function getFieldByName( $name )
	{
		$out = array();

		if ( is_array( $this->fieldCache ) && count( $this->fieldCache ) )
		{
			foreach( $this->fieldCache as $r )
			{
				if ( $r['field_keyword'] == $name )
				{
					$out = $r;
					break;
				}
			}
		}

		return $out;
	}

	/**
	 * Rebuild the cache fresh using the values from the database
	 *
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function rebuild()
	{
		$fieldCache = array();

		$this->checkForRegistry();

		// If the array is empty then get the projects from the database
		$this->DB->build(
			array(
				'select'  => '*',
				'from'    => 'tracker_field',
				'order'   => 'position'
			)
		);
		$this->DB->execute();

		// Load the local cache from the database
		if ( $this->DB->getTotalRows() )
		{
			while ( $r = $this->DB->fetch() )
			{
				// Setup fields in cache
				$fieldCache[ $r['field_id'] ] = $r;
			}
		}

		// Save the updated cache to the database
		$this->cache->setCache( $this->cacheName, $fieldCache, array( 'array' => 1, 'deletefirst' => 1 ) );

		// Set in the new cache
		$this->fieldCache = $fieldCache;
	}
}

/**
 * Fields permissions controller controller
 * 
 * @package Tracker
 * @subpackage Core
 * @since 2.0.0
 */
class tracker_core_field_permissions extends classPublicPermissions
{
	/**
	 * Builds a permission selection matrix
	 *
	 * @param string $typr The field to build permissions for
	 * @param array $default Current permissions
	 * @param string $app Module that the type belongs too
	 * @param string $only_perm Optional: Only show this permission
	 * @param	boolean		$addOutsideBox	Add or not the outside acp-box
	 * @return string HTML permission matrix
	 * @access public
	 * @since 2.0.0
	 */
	public function adminPermMatrix( $type, $default, $app='', $only_perm='', $addOutsideBox=true )
	{
		/* INIT */
		$map_class    = 'trackerPermMapping' . ucfirst( $app ) . 'Field' . ucfirst( $type ) . 'Project';
		$perm_names   = array();
		$perm_matrix  = array();
		$perm_checked = array();
		$perm_map     = array();
		$html         = $this->registry->output->loadTemplate('cp_skin_projects');

		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_permissions' ), 'members' );

		/* Get Mappings */
		require_once( IPSLib::getAppDir('tracker') . '/extensions/coreExtensions.php' );

		if ( class_exists( $map_class ) )
		{
			$mapping    = new $map_class();
			$perm_names = $mapping->getPermNames();
			$perm_map   = $mapping->getMapping();
		}
		else
		{
			throw new Exception('Permission Mapping Class Not Found');
		}

		/* Language... */
		$this->lang->loadLanguageFile( array( 'admin_tracker_permissions' ), 'tracker' );

		foreach( $perm_names as $key => $perm )
		{
			$perm_names[ $key ] = ipsRegistry::getClass('class_localization')->words['perm_tracker_' . $app . '_' . $key ] ? ipsRegistry::getClass('class_localization')->words['perm_tracker_' . $app . '_' . $key ] : $perm;
		}

		/* Single Perm? */
		if( $only_perm )
		{
			$new_perm_array = array();
			$new_perm_array[ $only_perm ] = $perm_names[ $only_perm ];
			$perm_names = $new_perm_array;
		}

		/* Loop through the masks */
		$this->registry->DB()->build( array( 'select' => '*', 'from' => 'forum_perms', 'order' => "perm_name ASC" ) );
		$this->registry->DB()->execute();

		while( $data = $this->registry->DB()->fetch() )
		{
			/* Reset row */
			$matrix_row = array();

			/* Loop through the permissions */
			foreach( $perm_names as $key => $perm )
			{
				/* Restrict? */
				if( $only_perm && $key != $only_perm )
				{
					continue;
				}

				/* Checked? */
				$checked = '';
				if( $default[ $perm_map[ $key ] ] == '*' )
				{
					$checked = " checked='checked'";

					/* Add the global flag */
					$perm_checked['*'][$key] = $checked;
				}
				else if( in_array( $data['perm_id'], explode( ',', IPSText::cleanPermString( $default[ $perm_map[ $key ] ] ) ) ) )
				{
					$checked = " checked='checked'";
				}

				$perm_checked[ $data['perm_id'] ][ $key ] = $checked;
				$matrix_row[$key] = $perm;
			}

			$data['perm_name'] = str_replace( '%', '&#37;', $data['perm_name'] );

			/* Add row to matrix */
			$perm_matrix[$data['perm_id'].'%'.$data['perm_name']] = $matrix_row;
		}
		
		/* Type */
		$type = $app . 'Field' . ucfirst( $type ) . 'Project';

		/* Return the matrix */
		return $html->permissionMatrix( $perm_names, $perm_matrix, $perm_checked, $mapping->getPermColors(), $app, $type );
	}

	/**
	 * Saves a permission matrix to the database
	 *
	 * @param array $perm_matrix The array of data to be saved
	 * @param int $type_id ID of the type for this permission row. EX: forum_id for a forum
	 * @param string $type The field permission type to build
	 * @param string $app Module that the type belongs too
	 * @return bool success/failure
	 * @access public
	 * @since 2.0.0
	 */	
	public function savePermMatrix( $perm_matrix, $type_id, $type, $app='' )
	{
		/* INIT */
		$map_class = $app . 'Field' . ucfirst( $type ) . 'Project';
		$saveArray = array();

		$this->loadFieldMapping( array( 'app' => 'tracker', 'perm_type' => $map_class ) );

		$mapping = $this->mappings['tracker'][ $map_class ];

		foreach( $perm_matrix as $k => $v )
		{
			$keys = explode( '[', $k );
			
			foreach( $keys as $a => $b )
			{
				$b = str_replace( ']', '', $b );
				$keys[$a] = $b;
			}
			
			$saveArray[ $keys[2] ][ $keys[3] ] = 1;
		}

		parent::savePermMatrix( $saveArray, $type_id, $map_class, 'tracker' );
	}

	/**
	 * Loads the mapping for a permission type
	 *
	 * @param   array   $row   Permission row from permission_index
	 * @return  bool
	 */	
	private function loadFieldMapping( $row )
	{
		if( !$row['app'] )
		{
			return false;
		}

		/* Mapping Class */
		$mapping_class = $row['app'] . 'PermMapping' . ucfirst( $row['perm_type'] );
		
		/* Check for the class */
		if( ! class_exists( $mapping_class ) )
		{
			/* Check for the file */
			if( is_file( IPSLib::getAppDir( $row['app'] ) . '/extensions/coreExtensions.php' ) )
			{
				require_once IPSLib::getAppDir( $row['app'] ) . '/extensions/coreExtensions.php';
			}
			else
			{
				return false;
			}
		}
		
		/* Load the mapping */
		if( class_exists( $mapping_class ) )
		{
			$mapping = new $mapping_class();
			$this->mappings[$row['app']][$row['perm_type']] = $mapping->getMapping();
		}
		
		return true;
	}
}

?>