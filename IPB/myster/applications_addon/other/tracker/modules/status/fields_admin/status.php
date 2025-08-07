<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Status module
* Last Updated: $Date: 2012-05-27 15:41:13 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminModules
* @link			http://ipbtracker.com
* @version		$Revision: 1369 $
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 * Type: Admin
 * Module: Status
 * Field: Status
 * ACP settings page for status field
 * 
 * @package Tracker
 * @subpackage Module-Status
 * @since 2.0.0
 */
class admin_tracker_module_status_field_status extends iptCommand
{
	public $html;

	protected $ad_skin;

	/*-------------------------------------------------------------------------*/
	// Run me - called by the wrapper
	/*-------------------------------------------------------------------------*/

	public function doExecute( ipsRegistry $registry )
	{
		/* Load Skin Template */
		$this->html = $this->tracker->modules()->loadTemplate( 'cp_skin_status', 'status' );

		/* Load Language */
		$this->lang->loadLanguageFile( array( 'admin_module_status' ) );

		/* URL Bits */
		$this->html->form_code    = $this->form_code    = 'module=modules&amp;section=fields&amp;component=status&amp;file=status';
		$this->html->form_code_js = $this->form_code_js = 'module=modules&section=fields&component=status&file=status';

		//--------------------------------------------
		// Sup?
		//--------------------------------------------

		switch($this->request['do'])
		{
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'tracker_fields_manage' );
				$this->statusOverview();
				break;
		}

		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	/*-------------------------------------------------------------------------*/
	// Categories Overview
	/*-------------------------------------------------------------------------*/

	function statusOverview()
	{
		$count = array();

		//-----------------------------------------
		// Get counts
		//-----------------------------------------
		foreach( $this->registry->tracker->projects()->getCache() as $k => $v )
		{
			$tmp = unserialize( $v['serial_data'] );

			if ( isset( $tmp['status'] ) )
			{
				foreach( $tmp['status'] as $idx => $data )
				{
					$count[ $idx ] += intval( $data );
				}
			}
		}
		
		//-----------------------------------------
		// Get categories
		//-----------------------------------------

		$this->DB->build(
			array(
				'select' => '*',
				'from'   => 'tracker_module_status',
				'order'  => 'position'
			)
		);
		$this->DB->execute();

		$count_order	= $this->DB->getTotalRows();
		$rows			= array();
		
		while( $r = $this->DB->fetch() )
		{
			$r['_count'] = intval($count[ $r['status_id'] ]);

			if ( $r['default'] )
			{
				$r['title'] .= ' (Default)';
			}
			
			$r['_auto_reply']	= 'No';
			$r['_closed']		= 'No';
			
			if ( $r['reply_text'] != '' )
			{
				$r['_auto_reply'] = "<span style='color:green;'>Yes</span>";
			}
			
			if ( $r['closed'] )
			{
				$r['_closed'] = "<span style='color:green;'>Yes</span>";
			}
			
			$rows[] = $r;
		}

		//-----------------------------------------
		// End the table and print
		//-----------------------------------------

		$this->registry->output->html .= $this->html->statusOverview( $rows );
	}
}

?>