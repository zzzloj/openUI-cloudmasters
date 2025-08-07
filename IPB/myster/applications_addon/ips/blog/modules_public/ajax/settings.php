<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.6.3
 * Tags
 * Last Updated: $Date: 2012-05-10 21:10:13 +0100 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Blog
 * @link		http://www.invisionpower.com
 * @since		6/24/2008
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_blog_ajax_settings extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		switch( $this->request['do'] )
		{
			case 'check_member':
			default:
				$this->checkMember();
				break;
		}
	}
	
	/**
	 * Check Member
	 */
	protected function checkMember()
	{
		$member = IPSMember::load( $this->request['member'], 'none', 'displayname' );
		if ( $member['member_id'] )
		{
			$this->returnString('OK');
		}
		else
		{
			$this->returnJsonError('NO_MEMBER');
		}
	}
}