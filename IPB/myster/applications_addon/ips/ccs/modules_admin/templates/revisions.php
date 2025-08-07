<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS revisions gateway
 * Last Updated: $Date: 2012-01-30 17:54:50 -0500 (Mon, 30 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		25th Sept 2009
 * @version		$Revision: 10218 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_templates_revisions extends ipsCommand
{
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
		// Load revisions manager library and pass request
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );
		
		switch( $this->request['ttype'] )
		{
			case 'page':
				$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . 'module=templates&amp;section=pages&amp;type=' . $this->request['ttype'], $this->lang->words['page_templates_title'] );
			break;
			
			case 'database':
				$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . 'module=templates&amp;section=pages&amp;type=' . $this->request['ttype'], $this->lang->words['page_templates_dbtitle'] );
			break;
			
			case 'article':
				$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . 'module=templates&amp;section=pages&amp;type=' . $this->request['ttype'], $this->lang->words['art_templates_title'] );
			break;

			case 'blocks':
				$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . 'module=templates&amp;section=blocks', $this->lang->words['block_template_title'] );
			break;
		}
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/revisions.php', 'revisionManager', 'ccs' );
		$_revisions		= new $classToLoad( $this->registry );
		$_revisions->passRequest();
	}
}
