<?php

/**
 * <pre>
 * Invision Power Services
 * Attachments field type abstraction
 * Last Updated: $Date: 2012-02-10 20:05:52 -0500 (Fri, 10 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		2nd Sept 2009
 * @version		$Revision: 10288 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class fields_attachments
{
	/**#@+
	 * Registry objects
	 *
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
	 * Error string stored from last process
	 *
	 * @var		string
	 */
	protected $error		= '';
	
	/**
	 * Attachments class
	 *
	 * @var		object
	 */
	protected $class_attach;

	/**
	 * Parsed attachment HTML
	 *
	 * @var		array
	 */
	protected $parsed	= array();

	/**
	 * Constructor
	 *
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
	 * Return default field types
	 *
	 * @param	string		Table name
	 * @return	@e array
	 */
	public function getTypes()
	{
		return array(
					array( 'attachments', $this->lang->words['field_type__attachments'] ),
					);
	}
	
	/**
	 * Return HTML to display field on ACP form
	 *
	 * @param	array		Field data
	 * @param	mixed		Default value
	 * @return	@e string
	 */
	public function getAcpField( $field, $default='' )
	{
		$id			= $field['field_id'];
		$type		= $field['field_type'];
		$options	= $field['field_extra'];
		
		if( $type == 'attachments' )
		{
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------

				$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach = new $classToLoad( $this->registry );
			}
			
			$this->registry->class_localization->loadLanguageFile( array( 'public_post' ), 'forums' );
			$this->class_attach->type		= 'ccs';
			$this->class_attach->init();
			$this->class_attach->getUploadFormSettings();

			return $this->registry->output->getTemplate('ccs_global')->attachments( array( 'post_key' => $this->settings['post_key'], 'stats' => $this->class_attach->attach_stats ) );

		}
		
		return '';
	}
	
	/**
	 * Return HTML to display the field on the front-end
	 *
	 * @param	array		Field data
	 * @param	mixed		Default value
	 * @return	@e string
	 */
	public function getPublicField( $field, $default='' )
	{
		$id			= $field['field_id'];
		$type		= $field['field_type'];
		$options	= $field['field_extra'];
		
		if( $type == 'attachments' )
		{
			if ( ! is_object( $this->class_attach ) )
			{
				$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach = new $classToLoad( $this->registry );
			}
			
			$this->registry->class_localization->loadLanguageFile( array( 'public_post' ), 'forums' );
			$this->class_attach->type		= 'ccs';
			$this->class_attach->init();
			$this->class_attach->getUploadFormSettings();

			$this->lang->words['upload_title']	= $field['field_name'];
			
			return $this->registry->output->getTemplate('ccs_global')->attachments( array( 'post_key' => $this->settings['post_key'], 'stats' => $this->class_attach->attach_stats ) );
		}
		
		return '';
	}
	
	/**
	 * Get error, if set
	 *
	 * @return	@e mixed
	 */
	public function getError()
	{
		return $this->error ? $this->error : false;
	}

	/**
	 * Process the input and return normalized value to store
	 *
	 * @param	array 		Field data
	 * @return	@e string
	 */
	public function processInput( $field )
	{
		if( $field['field_type'] == 'attachments' )
		{
			$_key	= trim($this->request['post_key']);
			
			$_count	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'attachments', 'where' => "attach_rel_module='ccs' AND attach_post_key='{$_key}'" ) );
			
			$_count['total'] = intval($_count['total']);
			
			if( $field['field_required'] AND $_count['total'] < 1 )
			{
				$this->error	= sprintf( $this->lang->words['dbfield_required'], $field['field_name'] );
			}
			
			return $_count['total'];
		}
		
		return 0;
	}
	
	/**
	 * Process input after data has been saved to database.  Returns false on error.
	 *
	 * @param	array 		Field data
	 * @return	@e bool
	 */
	public function postProcessInput( $field, $record_id=0 )
	{
		if( !$record_id )
		{
			return false;
		}
		
		if( $field['field_type'] == 'attachments' )
		{
			if ( ! is_object( $this->class_attach ) )
			{
				$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach = new $classToLoad( $this->registry );
			}
			
			$this->class_attach->type				= 'ccs';
			$this->class_attach->attach_post_key	= $this->request['post_key'];
			$this->class_attach->attach_rel_id		= $record_id;
			$this->class_attach->init();
			
			$return = $this->class_attach->postProcessUpload( array( 'field_id' => $field['field_id'], 'database_id' => $field['field_database_id'], 'record_id' => $record_id ) );

			$this->DB->update( 'ccs_custom_database_' . $field['field_database_id'], array( 'field_' . $field['field_id'] => $return['count'] ), 'primary_id_field=' . $record_id );
		}

		return true;
	}
	
	/**
	 * Record deletion callback.  Returns false on error.
	 *
	 * @param	array 		Field data
	 * @param	array		Record data
	 * @return	@e bool
	 */
	public function postProcessDelete( $field, $record )
	{
		$mapIds	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_attachments_map', 'where' => "map_database_id={$field['field_database_id']} AND map_field_id={$field['field_id']} AND map_record_id={$record['primary_id_field']}" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$mapIds[]	= $r['map_id'];
		}
		
		if( count($mapIds) )
		{
			if ( ! is_object( $this->class_attach ) )
			{
				$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach = new $classToLoad( $this->registry );
			}
			
			$this->class_attach->type		= 'ccs';
			$this->class_attach->init();
			
			$this->class_attach->bulkRemoveAttachment( $mapIds );
			
			$this->DB->delete( 'ccs_attachments_map', "map_database_id={$field['field_database_id']} AND map_field_id={$field['field_id']} AND map_record_id={$record['primary_id_field']}" );
		}
		
		return true;
	}
	
	/**
	 * Process the field and return a display value
	 *
	 * @param	array 		Field data
	 * @param	array		Record data
	 * @param	int			Number of characters to truncate at (0 means no truncating)
	 * @return	@e string
	 */
	public function getFieldValue( $field, $record=array(), $truncate=0 )
	{
		$fieldValue	= $record['field_' . $field['field_id'] ];
		
		if( !$fieldValue )
		{
			return '';
		}

		$returnHTML	= '';
				
		if( $field['field_type'] == 'attachments' )
		{
			if( $truncate )
			{
				return sprintf( $this->lang->words['_attachments_count'], $fieldValue );
			}

			$_id	= $record['primary_id_field'];
			$_key	= md5( $field['field_database_id'] . '.' . $field['field_id'] . '.' . $_id );

			if( $this->parsed[ $_key ] )
			{
				return $this->parsed[ $_key ];
			}
			
			$mapIds		= array();
			$_attachIds	= array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_attachments_map', 'where' => "map_database_id={$field['field_database_id']} AND map_field_id={$field['field_id']} AND map_record_id={$_id}" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$mapIds[]		= $r['map_id'];
				$_attachIds[]	= $r['map_attach_id'];
				
				$this->caches['ccs_attachments_data'][ $_id ][ $r['map_id'] ]	= array();
			}

			if( count($mapIds) )
			{
				$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
				
				if ( ! is_object( $this->class_attach ) )
				{
					$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
					$this->class_attach = new $classToLoad( $this->registry );
				}
			
				$this->class_attach->type  = 'ccs';
				$this->class_attach->init();
				
				$_html	= '';
				
				foreach( $_attachIds as $attach_id )
				{
					$_html	.= "[attachment={$attach_id}:IPC]";
				}

				$attachHTML = $this->class_attach->renderAttachments( $_html, $mapIds, 'ccs_global' );

				/* Now parse back in the rendered posts */
				foreach( $attachHTML as $id => $data )
				{
					$returnHTML	.= $data['html'];
				}
			}

			$this->parsed[ $_key ]	= $returnHTML;
		}

		return $returnHTML;
	}
	
	/**
	 * Process input and return data to display for preview
	 *
	 * @param	array 		Field data
	 * @param	array		Record data
	 * @param	int			Number of characters to truncate at (0 means no truncating)
	 * @return	@e string
	 */
	public function getFieldValuePreview( $field, $record=array(), $truncate=0 )
	{
		$returnHTML	= '';
		
		if( $field['field_type'] == 'attachments' )
		{
			if( $truncate )
			{
				$fieldValue	= $record['field_' . $field['field_id'] ];
				
				return sprintf( $this->lang->words['_attachments_count'], $fieldValue );
			}

			$attachIds	= array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'attachments', 'where' => "attach_rel_module='ccs' AND attach_post_key='{$record['post_key']}'" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$attachIds[]	= $r['attach_id'];
			}

			if( count($attachIds) )
			{
				$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
				
				if ( ! is_object( $this->class_attach ) )
				{
					$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
					$this->class_attach = new $classToLoad( $this->registry );
				}
			
				$this->class_attach->type  = 'ccs';
				$this->class_attach->init();

				//-----------------------------------------
				// Create the 'fake' html for preview
				//-----------------------------------------
				
				$_attachHtml	= '';
				
				foreach( $attachIds as $_attachId )
				{
					$_attachHtml .= "[attachment={$_attachId}:test]<br /><br />";
				}
				
				$attachHTML = $this->class_attach->renderAttachments( $_attachHtml, array(), 'ccs_global' );

				/* Now parse back in the rendered posts */
				foreach( $attachHTML as $id => $data )
				{
					$returnHTML	.= $data['html'] . $data['attachmentHtml'];
				}
			}
		}

		return $returnHTML;
	}
	
	/**
	 * Produce where clause for search queries
	 *
	 * @param	array 		Field data
	 * @param	string		Supplid value
	 * @return	@e string
	 */
	public function getSearchWhere( $field, $search='' )
	{
		//-----------------------------------------
		// Cannot search attachments
		//-----------------------------------------
		
		return '';
	}

	/**
	 * Compare two versions of a particular field and return an HTML diff report
	 *
	 * @param	array 		Field data
	 * @param	string		Current data in the field
	 * @param	string		Previous data in the field
	 * @return	@e string
	 */
	public function compareRevision( $field, $current, $previous )
	{
		if( $field['field_type'] == 'attachments' )
		{
			if( $current == $previous )
			{
				return $current;
			}
			else
			{
				return "<ins>" . $current . "</ins> <del>" . $previous . "</del>";
			}
		}

		return '';
	}

	/**
	 * Field deletion callback.
	 *
	 * @param	array 		Database data
	 * @param	array		Field data
	 * @return	@e bool
	 */
	public function preDeleteField( $database, $field )
	{
		//-----------------------------------------
		// Loop over records
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => $database['database_database'] ) );
		$outer = $this->DB->execute();

		while( $record = $this->DB->fetch($outer) )
		{
			$this->postProcessDelete( $field, $record );
		}
		
		return true;
	}
}