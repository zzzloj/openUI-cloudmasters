<?php

/**
* Tracker 2.1.0
* 
* ACP Field file
* Last Updated: $Date: 2012-07-05 20:42:21 +0100 (Thu, 05 Jul 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @link			http://ipbtracker.com
* @version		$Revision: 1379 $
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 * Field modules ACP class
 *
 * @package Tracker
 * @subpackage Admin
 * @since 2.0.0
 */
class admin_tracker_modules_fields extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @var object Skin templates
	 * @access private
	 * @since 2.0.0
	 */
	private $html;

	/**
	 * Shortcut for url
	 *
	 * @var string URL shortcut
	 * @access private
	 * @since 2.0.0
	 */
	private $form_code;

	/**
	 * Shortcut for url (javascript)
	 *
	 * @var string JS URL shortcut
	 * @access private
	 * @since 2.0.0
	 */
	private $form_code_js;

	/**
	 * The navigation adds for fields
	 *
	 * @var array the adds
	 * @access private
	 * @since 2.0.0
	 */
	private $navigation = array();

	/**
	 * Main class entry point
	 *
	 * @param object ipsRegistry $registry reference
	 * @return void [Outputs to screen]
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------

		$this->html = $this->registry->output->loadTemplate('cp_skin_modules');
		$this->registry->class_localization->loadLanguageFile( array( 'admin_modules' ) );

		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------

		$this->form_code    = $this->html->form_code = 'module=modules&amp;section=fields';
		$this->form_code_js = $this->html->form_code_js = 'module=modules&section=fields';

		//-----------------------------------------
		// Load a setting file OR launch internal instructions
		//-----------------------------------------

		if ( isset( $this->request['component'] ) && isset( $this->request['file'] ) )
		{
			//-----------------------------------------
			// Load the inputs
			//-----------------------------------------

			$module = $this->request['component'];
			$file   = $this->request['file'];

			//-----------------------------------------
			// Show settings form
			//-----------------------------------------

			$className = 'admin_tracker_module_'.$module.'_field_'.$file;
			$filePath  = $this->registry->tracker->modules()->getModuleFolder( $module ) . 'fields_admin/' . $file . '.php';

			//-----------------------------------------
			// View settings
			//-----------------------------------------

			if ( $this->registry->tracker->modules()->moduleIsInstalled( $module, FALSE ) && file_exists( $filePath ) )
			{
				require_once( $filePath );
				
				if ( class_exists( $className ) )
				{
					$settingsClass = new $className();
					$settingsClass->execute( $this->registry );
				}
			}
		}
		else
		{
			//-----------------------------------------
			// What to do...
			//-----------------------------------------

			switch( $this->request['do'] )
			{
				default:
					$this->request['do'] = 'overview';
					$this->listFields();
					break;
			}

			//-----------------------------------------
			// Pass to CP output hander
			//-----------------------------------------

			$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
			$this->registry->getClass('output')->sendOutput();
		}
	}

	public function listFields()
	{
		/* Output page */
		$this->registry->output->html .= $this->html->field_splash( $this->getFields() );
	}

	private function getFieldNavigation( $field )
	{
		$out = '';

		if ( ! ( count( $this->navigation ) > 0 ) )
		{
			$NAVIGATION = array();

			if ( file_exists( DOC_IPS_ROOT_PATH . 'cache/tracker/fieldsNavigation.php' ) )
			{
				require_once( DOC_IPS_ROOT_PATH . 'cache/tracker/fieldsNavigation.php' );
			}

			if ( count( $NAVIGATION ) > 0 )
			{
				$this->navigation = $NAVIGATION;
			}
		}

		if ( isset( $this->navigation[ $field ] ) )
		{
			$out = $this->navigation[ $field ];
		}

		return $out;
	}

	private function getFields()
	{
		$out 	= array();
		$pos	= 0;

		/* Iterate through the folder */
		$this->DB->build(
			array(
				'select'  => '*',
				'from'    => 'tracker_field',
				'order'   => 'position ASC'
			)
		);
		$s = $this->DB->execute();

		if ( $this->DB->getTotalRows( $s ) )
		{
			while( $field = $this->DB->fetch( $s ) )
			{
				$pos++;
				
				$out[$pos]           = $field;
				$out[$pos]['module'] = $this->registry->tracker->modules()->getModuleByID( $field['module_id'] );
				$out[$pos]['nav']    = $this->getFieldNavigation( $field['field_keyword'] );
			}
		}

		/* Return the data to the running script */
		return $out;
	}
}

?>