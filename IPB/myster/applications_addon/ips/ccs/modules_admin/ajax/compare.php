<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS run template comparison report
 * Last Updated: $Date: 2012-02-28 18:09:58 -0500 (Tue, 28 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		25th Sept 2009
 * @version		$Revision: 10375 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_ajax_compare extends ipsAjaxCommand
{
	/**
	 * Shortcut for url
	 *
	 * @access	protected
	 * @var		string			URL shortcut
	 */
	protected $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	protected
	 * @var		string			JS URL shortcut
	 */
	protected $form_code_js;
	
	/**
	 * HTML library
	 *
	 * @access	public
	 * @var		object
	 */
	public $html;

	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_templates' );

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );
		
		//-----------------------------------------
		// Get template data
		//-----------------------------------------
		
		$id			= intval($this->request['id']);
		$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . $id ) );
		
		if( !$template['template_id'] )
		{
			$this->registry->output->showError( $this->lang->words['no_template_compare'], '11CCS1' );
		}
		
		if( !$template['template_database'] )
		{
			$this->registry->output->showError( $this->lang->words['bad_template_compare'], '11CCS2' );
		}
		
		//-----------------------------------------
		// We've confirmed template exists and is
		//	a database template.  Get original now.
		//-----------------------------------------
		
		$content	= file_get_contents( IPSLib::getAppDir('ccs') . '/xml/demosite.xml' );
		$original	= '';
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classXML.php', 'classXML' );
		$xml			= new $classToLoad( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );

		foreach( $xml->fetchElements('template') as $dtemplate )
		{
			$_template	= $xml->fetchElementsFromRecord( $dtemplate );

			if( $_template['template_key'] == $template['template_key'] )
			{
				$original	= $_template['template_content'];
				break;
			}
		}

		//-----------------------------------------
		// Get Diff library
		//-----------------------------------------
		
		$classToLoad				= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classDifference.php', 'classDifference' );
		$classDifference			= new $classToLoad();
		$classDifference->method	= 'PHP';

		$difference	= $classDifference->formatDifferenceReport( $classDifference->getDifferences( $template['template_content'], $original, 'unified' ), 'unified', false );

		if( !$difference )
		{
			$difference	= nl2br( str_replace( "\t", "&nbsp; &nbsp; ", IPSText::htmlspecialchars( $template['template_content'] ) ) );
		}

		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->returnHtml( $this->html->viewDiffReport( $template, $difference ) );
	}
}
