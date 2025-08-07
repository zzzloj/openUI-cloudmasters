<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.0.1
 * Parse media code and return it
 * Last Updated: $Date: 2011-12-08 21:50:46 -0500 (Thu, 08 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 9974 $
 *
 */

class public_ccs_ajax_media extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
		$content	= $this->request['value'];
		
		IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 0;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'global';

		$this->returnString( IPSText::getTextClass('bbcode')->parseSingleBbcodes( $content, 'display', 'sharedmedia' ) );
	}
}
