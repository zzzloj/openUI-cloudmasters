<?php

/**
* Tracker 2.1.0
*	- IPS Community Project Developers
*		- Javascript written by Alex Hobbs
* 
* Severity Admin AJAX-PHP Interface
* Last Updated: $Date: 2012-05-27 15:41:13 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2009 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Severity
* @link			http://ipbtracker.com
* @version		$Revision: 1369 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Type: AJAX
 * Module: Severity
 * Field: Severity
 * Severity field cache controller
 * 
 * @package Tracker
 * @subpackage Module-Severity
 * @since 2.0.0
 */
class admin_tracker_module_severity_ajax_admin extends ipsAjaxCommand 
{
	/**
	 * Initial function.  Called by execute function in iptCommand 
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
			case 'save':
				$this->save();
				break;
			default:
				$this->returnJsonArray(array('error'=>'true','message'=>'We could not work out what step to perform'));
				break;
		}
	}

	private function save()
	{
		$data = json_decode($_POST['data'], true);
		$sets = array();

		// Check we have required data
		if ( ! is_array($data) && ! ( count( $data ) > 0 ) )
		{
			$this->returnJsonArray(array('error'=>'true'));
		}

		// Load the severities
		$this->DB->build(
			array(
				'select' => '*',
				'from'   => 'tracker_module_severity'
			)
		);
		$this->DB->execute();

		// Check for rows to process
		if ( $this->DB->getTotalRows() )
		{
			while ( $r = $this->DB->fetch() )
			{
				$sets[ $r['skin_id'] ][ $r['severity_id'] ]['background_color'] = $r['background_color'];
				$sets[ $r['skin_id'] ][ $r['severity_id'] ]['font_color']       = $r['font_color'];
			}
		}

		// Loop through the incoming data
		foreach( $data as $skin => $sevs )
		{
			if ( intval( $skin ) == $skin && is_array( $sevs ) && count( $sevs == 5 ) )
			{
				$skin = intval( $skin );

				foreach( $sevs as $sid => $values )
				{
					if ( intval( $sid ) == $sid && is_array( $values ) && isset( $values['background_color'] ) && isset( $values['font_color'] ) )
					{
						$sid  = intval( $sid );
						$save = array( 'background_color' => $values['background_color'], 'font_color' => $values['font_color'] );

						if ( isset( $sets[ $skin ][ $sid ] ) && is_array( $sets[ $skin ][ $sid ] ) && count( $sets[ $skin ][ $sid ] ) == 2 )
						{
							$this->DB->update( 'tracker_module_severity', $save, "severity_id={$sid} AND skin_id={$skin}" );
						}
						else
						{
							$save['severity_id'] = $sid;
							$save['skin_id']     = $skin;

							$this->DB->insert('tracker_module_severity', $save );
						}
					}
				}
			}
		}

		// Recache
		$this->returnJsonArray( array( 'result' => 'success' ) );
	}

	private function recache()
	{
		$return = $this->registry->tracker->cache('severity','severity')->rebuild();
		print $return;exit;
		if ( $return == 'CANNOT_WRITE' )
		{
			$this->returnString('CANNOT_WRITE');
		}
		
		$this->returnJsonArray(array('result'=>'success'));
	}
}

?>