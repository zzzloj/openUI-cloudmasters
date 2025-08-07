<?php

/**
* Tracker 2.1.0
*  - IPS Community Project Developers
* 
* Severities module
* Last Updated: $Date: 2012-07-05 20:42:21 +0100 (Thu, 05 Jul 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2008 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	AdminModules
* @link			http://ipbtracker.com
* @version		$Revision: 1379 $
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_tracker_module_severity_field_severity extends iptCommand
{
	public $html;

	/*-------------------------------------------------------------------------*/
	// Run me - called by the wrapper
	/*-------------------------------------------------------------------------*/

	public function doExecute( ipsRegistry $registry )
	{
		$this->registry = $registry;
		
		/* Load Skin Template */
		$this->html = $this->tracker->modules()->loadTemplate( 'cp_skin_severity', 'severity' );

		/* Load Language */
		$this->lang->loadLanguageFile( array( 'admin_module_severity' ) );

		/* URL Bits */
		$this->html->form_code    = $this->form_code    = 'module=modules&amp;section=fields&amp;component=severity&amp;file=severity';
		$this->html->form_code_js = $this->form_code_js = 'module=modules&section=fields&component=severity&file=severity';

		//--------------------------------------------
		// Sup?
		//--------------------------------------------

		switch($this->request['do'])
		{
			default:
				$this->showColors();
				break;
		}

		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	/*-------------------------------------------------------------------------*/
	// Shows the severity colors
	/*-------------------------------------------------------------------------*/

	function showColors()
	{
		$cache		= array();
		
		// Load the severities
		$this->DB->build(
			array(
				'select' => '*',
				'from'   => 'tracker_module_severity'
			)
		);

		$s = $this->DB->execute();

		// Check for rows to process
		if ( $this->DB->getTotalRows( $s ) )
		{
			while ( $r = $this->DB->fetch( $s ) )
			{
				$cache[$r['skin_id']][$r['severity_id']] = $r;
			}
		}

		foreach( $this->caches['skinsets'] as $k => $v )
		{
			/* Use previous data if it's available */
			$sets[$k][1]['background_color']	= isset($cache[$k][1]['background_color'])	? $cache[$k][1]['background_color'] : '#CAECAF';
			$sets[$k][1]['font_color']			= isset($cache[$k][1]['font_color'])		? $cache[$k][1]['font_color']		: '#000000';
			$sets[$k][2]['background_color']	= isset($cache[$k][2]['background_color'])	? $cache[$k][2]['background_color'] : '#F0F3A4';
			$sets[$k][2]['font_color']			= isset($cache[$k][2]['font_color'])		? $cache[$k][2]['font_color']		: '#000000';
			$sets[$k][3]['background_color']	= isset($cache[$k][3]['background_color'])	? $cache[$k][3]['background_color'] : '#F5D484';
			$sets[$k][3]['font_color']			= isset($cache[$k][3]['font_color'])		? $cache[$k][3]['font_color']		: '#000000';
			$sets[$k][4]['background_color']	= isset($cache[$k][4]['background_color'])	? $cache[$k][4]['background_color'] : '#F5B984';
			$sets[$k][4]['font_color']			= isset($cache[$k][4]['font_color'])		? $cache[$k][4]['font_color']		: '#000000';
			$sets[$k][5]['background_color']	= isset($cache[$k][5]['background_color'])	? $cache[$k][5]['background_color'] : '#B93B3B';
			$sets[$k][5]['font_color']			= isset($cache[$k][5]['font_color'])		? $cache[$k][5]['font_color']		: '#FFFFFF';

			$sets[$k]['set_name'] = $v['set_name'];
		}
		
		$this->registry->output->html .= $this->html->showSeverity($sets);
	}
}

?>