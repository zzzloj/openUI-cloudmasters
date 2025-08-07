<?php
/**
 * @file		menu.php 	IP.Content AJAX functions for menu management
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		21 Dec 2011
 * $LastChangedDate: 2012-01-17 21:56:35 -0500 (Tue, 17 Jan 2012) $
 * @version		v3.4.5
 * $Revision: 10146 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

/**
 * @class		admin_ccs_ajax_menu
 * @brief		IP.Content AJAX functions for menu management
 */
class admin_ccs_ajax_menu extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'reorder':
			default:
				$this->_doReorder();
			break;
		}
	}

	/**
	 * Reorder blocks within a container
	 *
	 * @return	@e void
	 */
	protected function _doReorder()
	{
 		//-----------------------------------------
 		// Figure out positions
 		//-----------------------------------------

 		$position	= 1;
 		$_last		= null;
 		$menus		= array();
 		$apps		= array();
		
 		if( is_array($this->request['menuitems']) AND count($this->request['menuitems']) )
 		{
 			foreach( $this->request['menuitems'] as $menu_id )
 			{
 				if( !$menu_id )
 				{
 					continue;
 				}

 				/* jQuery sortable does not like ids with underscores, so we use a random replacement that we need to correct here on the backend */
 				$menu_id	= str_replace( 'zzxzz', '_', $menu_id );
 				
 				if( in_array( $menu_id, array_keys( ipsRegistry::$applications ) ) )
 				{
 					$apps[ $menu_id ]	= $position;

	 				if( $_last AND !in_array( $_last, array_keys( ipsRegistry::$applications ) ) )
	 				{
				 		//-----------------------------------------
				 		// We need to loop back through menus and update
				 		// all that have an id that ISN'T a string
				 		//-----------------------------------------
				 		
				 		$_temp	= 1;
				 		
				 		foreach( $menus as $k => $v )
				 		{
				 			if( !in_array( $k, array_keys( ipsRegistry::$applications ) ) AND strpos( $v, '_' ) === false )
				 			{
				 				$menus[ $k ]	= $menu_id . '_' . $_temp;
				 				
				 				$_temp++;
				 			}
				 		}
	 				}
 				}
 				else
 				{
 					$menus[ $menu_id ]	= $position;
 				}
 				
				$_last	= $menu_id;

 				$position++;
 			}
 		}

 		//-----------------------------------------
 		// Store positions
 		//-----------------------------------------
 		
 		if ( count( $menus ) )
 		{
  			foreach( $menus as $id => $pos )
	 		{
	 			$this->DB->update( 'ccs_menus', array( 'menu_position' => $pos ), 'menu_id=' . intval($id) );
	 		}
 		}

 		//-----------------------------------------
 		// Store apps
 		//-----------------------------------------
		
 		if ( count( $apps ) )
 		{
	 		foreach( $apps as $key => $pos )
	 		{
	 			$this->DB->update( 'core_applications', array( 'app_position' => $pos ), "app_directory='{$key}'" );
	 		}
 		}

 		//-----------------------------------------
 		// Recache
 		//-----------------------------------------
 		
 		$this->cache->rebuildCache( 'ccs_menu', 'ccs' );
 		$this->cache->rebuildCache( 'app_cache', 'global' );
 		
 		$this->registry->adminFunctions->saveAdminLog( $this->lang->words['ccs_menus_reordered'] );

 		//-----------------------------------------
 		// And return..
 		//-----------------------------------------
 		
 		$this->returnString( 'OK' );
 		exit();
	}
}