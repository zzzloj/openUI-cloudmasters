<?php

/**
 * <pre>
 * Invision Power Services
 * Validator: URL
 * Last Updated: $Date: 2010-12-17 07:53:02 -0500 (Fri, 17 Dec 2010) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		2nd Sept 2009
 * @version		$Revision: 7443 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

$CONFIG	= array(
				'key'		=> 'url',
				'language'	=> $this->lang->words['validator__url'],
				'regex'		=> null,
				'callback'	=> array( 'IPSText', 'xssCheckUrl' ),
				'error'		=> $this->lang->words['validator__url_error'],
				);
