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
class admin_tracker_ajax_fields extends ipsAjaxCommand 
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
			default:
				$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
				break;
		}
	}

	/**
	 * Calls for a fields cache rebuild
	 *
	 * @return void [JSON array output 'result' => 'success']
	 * @access public
	 * @since 2.0.0
	 */
	private function recache()
	{
		$this->registry->tracker->fields()->rebuild();

		$this->returnJsonArray( array( 'result' => 'success' ) );
	}

	/**
	 * Uses order input to save a new field order
	 *
	 * @return void [JSON array output 'result' => 'success|error']
	 * @access public
	 * @since 2.0.0
	 */
	private function reorder()
	{
		if ( is_array( $this->request['fields'] ) && count( $this->request['fields'] ) > 0 )
		{
			foreach( $this->request['fields'] as $position => $id )
			{
				$this->DB->update( 'tracker_field', array( 'position' => $position ), 'field_id=' . $id );
			}
		}
	}
}