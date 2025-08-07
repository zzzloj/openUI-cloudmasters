<?php

/**
* Tracker 2.1.0
* 
* Projects Javascript PHP Interface
* Last Updated: $Date: 2012-05-27 15:41:13 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Admin
* @link			http://ipbtracker.com
* @version		$Revision: 1369 $
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Type: Admin
 * Project AJAX processor
 * 
 * @package Tracker
 * @subpackage Admin
 * @since 2.0.0
 */
class admin_tracker_ajax_projects extends ipsAjaxCommand 
{

	/**
	 * Skin functions object handle
	 *
	 * @access private
	 * @var object
	 * @since 2.0.0
	 */
	private $skinFunctions;
	/**
	 * HTML Skin object
	 *
	 * @access protected
	 * @var object
	 * @since 2.0.0
	 */
	protected $html;

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
		$this->html = $this->registry->output->loadTemplate('cp_skin_projects');
		
		//-----------------------------------------
		// What shall we do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'recache':
				$this->recache();
				break;
			case 'reorder':
				$this->reorder();
				break;
			case 'fieldOptions':
				$this->fieldOptions();
				break;
			case 'createSimple':
				$this->create();
				break;
			case 'deleteSimple':
				$this->delete();
				break;
			default:
				$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
				break;
		}
	}
	
	/**
	 * Creates the basic requirements for the project
	 *
	 * @return void [JSON array]
	 * @access private
	 * @since 2.0.0
	 */
	private function create()
	{
		if ( ! $this->request['title'] )
		{
			$this->returnJsonArray( array( 'error' => 'true' ) );
		}
		
		$save = array(
			'title'             => IPSText::getTextClass('bbcode')->xssHtmlClean( nl2br( IPSText::stripslashes( $_POST['title'] ) ) ),
			'description'       => IPSText::getTextClass('bbcode')->xssHtmlClean( nl2br( IPSText::stripslashes( $_POST['description'] ) ) ),
			'cat_only'          => intval($this->request['category']),
			'enable_rss'        => intval($this->request['rss']),
			'parent_id'			=> intval($this->request['parent_id']),
		);
		
		// Create project
		$row = $this->DB->buildAndFetch (
			array (
				'select' => 'max(position) as position',
				'from'   => 'tracker_projects',
				'where'  => "parent_id = {$save['parent_id']}"
			)
		);

		$order = $row['position'] + 1;

		$save['position']      = $order;

		$this->DB->insert( 'tracker_projects', $save );
		$project = array();
		$project['project_id'] = $this->DB->getInsertId();
		
		// Fields cache
		$cache = $this->registry->tracker->projects()->projectFields($project);
		$this->registry->tracker->projects()->rebuild();
		
		// Sort any javascript modules out
		
		$storedJavascript = array();
		
		foreach( $cache as $k => $v )
		{
			if ( file_exists( $this->registry->tracker->modules()->getModuleFolder($v['module']['directory']) . 'js/project_form.js' ) )
			{
				if ( ! in_array( $v['module']['directory'], $storedJavascript ) )
				{
					$storedJavascript[] = $this->registry->tracker->modules()->getModuleURL( $v['module']['directory'] ) . "js/project_form.js";
				}
			}
		}
		
		// Return to javascript
		$this->returnJsonArray(
			array(
				'project_id'	=> $project['project_id'],
				'fields'		=> $this->registry->tracker->parseJavascriptTemplate( $this->html->fieldsCache($cache) ),
				'javascript'	=> $storedJavascript
			)
		);
	}

	/**
	 * Deletes any project that was 'half-made' and cancelled by user.
	 *
	 * @return void [JSON array]
	 * @access private
	 * @since 2.0.0
	 */
	private function delete()
	{
		if ( ! $this->request['project_id'] )
		{
			$this->returnJsonArray( array( 'error' => 'true' ) );
		}
		
		$this->DB->delete( 'tracker_projects', 'project_id=' . intval( $this->request['project_id'] ) );
		$this->DB->delete( 'tracker_project_field', 'project_id=' . intval( $this->request['project_id'] ) );
		
		$this->registry->tracker->projects()->rebuild();
		$this->returnJsonArray( array( 'success' => true ) );
	}
	
	/**
	 * Loads field options that the project can control
	 *
	 * @return void [JSON array]
	 * @access private
	 * @since 2.0.0
	 */
	private function fieldOptions()
	{
		$out = array( 'error' => 'true' );

		if ( isset( $this->request['field'] ) && intval( $this->request['field'] ) == $this->request['field'] && 
		     isset( $this->request['project_id'] ) && intval( $this->request['project_id'] ) == $this->request['project_id'] )
		{
			$project = $this->registry->tracker->projects()->getProject( $this->request['project_id'] );
			$field   = $this->registry->tracker->fields()->getField( $this->request['field'] );
			$module  = $this->registry->tracker->modules()->getModuleByID( $field['module_id'] );

			if ( $this->registry->tracker->modules()->moduleIsInstalled( $module['directory'] ) )
			{
				$extension = $this->registry->tracker->fields()->extension( $field['field_keyword'], $module['directory'], 'project_form' );
				$content   = array();
				$meta      = array();
				$perms     = array();
				$tabs      = array();

				if ( isset( $project['fields'] ) && is_array( $project['fields'] ) && isset( $project['fields'][ $field['field_id'] ] ) && is_array( $project['fields'][ $field['field_id'] ] ) )
				{
					$meta = $project['fields'][ $field['field_id'] ];

					if ( isset( $meta['perms'] ) && is_array( $meta['perms'] ) )
					{
						$perms = $meta['perms'];

						unset( $meta['perms'] );
					}
				}

				$extension->setupData( $project, $meta );

				$tabs[99] = array(
					'title'   => 'Permissions',
					'key'     => 'perms',
					'active'  => true
				);

				$project_perms = $this->registry->tracker->fields()->perms()->adminPermMatrix( $field['field_keyword'], $perms, $module['directory'] );

				$content[99] = array(
					'key'      => 'perms',
					'css'      => 'perms',
					'field'    => $field['field_keyword'],
					'type'	   => $module['directory'] . 'Field' . ucfirst($field['field_keyword']) . 'Project',
					'content'  => $project_perms
				);

				$tabs       = array_reverse( array_merge( $tabs, $extension->getTabs() ) );
				$content    = array_reverse( array_merge( $content, $extension->getContent() ) );
				$javascript = $extension->getJavascript();

				$out        = array( 'tabs' => $tabs, 'content' => $content );

				if ( $javascript )
				{
					$out['javascript']['additional'] = $javascript;
				}

				/* Javascript module? */
				if ( file_exists( $this->registry->tracker->modules()->getModuleFolder($module['directory']) . 'js/project_form.js' ) )
				{
					$out['javascript']['file']      = $this->registry->tracker->modules()->getModuleURL($module['directory']) . 'js/project_form.js';
					$out['javascript']['className'] = $module['directory'] . '_project_form';	
				}
			}
		}

		$this->returnJsonArray( $out );
	}

	/**
	 * Calls for a projects cache rebuild
	 *
	 * @return void [JSON array output 'result' => 'success']
	 * @access private
	 * @since 2.0.0
	 */
	private function recache()
	{
		$this->registry->tracker->projects()->rebuild();

		$this->returnJsonArray( array( 'result' => 'success' ) );
	}

	/**
	 * Uses order input to save a new project order
	 *
	 * The array coming in does not contain position id's, but rather relys
	 * solely on the order of the array.  Arrays are nested to show children
	 * with the project ID being the array keys:
	 *
	 * array(
	 *     4 => array(),
	 *     1 => array(
	 *          2 => array(
	 *               3 => array()
	 *          ),
	 *          5 => array()
	 *     )
	 * )
	 *
	 * Translates to:
	 *
	 * Project ID: 4
	 * Project ID: 1
	 * |-- Project ID: 2
	 * |---- Project ID: 3
	 * |-- Project ID: 5
	 *
	 * @return void [JSON array output 'result' => 'success|error']
	 * @access private
	 * @since 2.0.0
	 */
	private function reorder()
	{
		$newOrder = json_decode($_POST['order'], true);
		$out      = array();

		// Make sure the new order is valid
		if ( is_array( $newOrder ) && count( $newOrder ) > 0 )
		{
			$count = 1;

			// Loop through the root projects
			foreach( $newOrder as $id => $children )
			{
				$this->DB->update( 'tracker_projects', array( 'position' => $count, 'parent_id' => 0 ), 'project_id=' . $id );

				// Run the children
				$this->reorderInternal( $id, $children );

				$count++;
			}

			$out = array( 'result' => 'success' );
		}
		else
		{
			$out = array( 'error' => 'true', 'result' => 'error' );
		}

		$this->returnJsonArray( $out );
	}

	/**
	 * Internal/recurrsive project order save
	 *
	 * @param int $parent the parent ID
	 * @param array $newOrder array of the child keys
	 * @return void
	 * @access private
	 * @since 2.0.0
	 */
	private function reorderInternal( $parent, $newOrder )
	{
		// Check for valid input
		if ( is_array( $newOrder ) && count( $newOrder ) > 0 )
		{
			$count = 1;

			// Loop through the children
			foreach( $newOrder as $id => $children )
			{
				$this->DB->update( 'tracker_projects', array( 'position' => $count, 'parent_id' => $parent ), 'project_id=' . $id );

				// Run the children
				$this->reorderInternal( $id, $children );

				$count++;
			}
		}
	}
}