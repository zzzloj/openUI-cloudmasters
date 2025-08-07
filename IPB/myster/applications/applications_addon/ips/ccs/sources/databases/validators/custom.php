<?php

/**
 * <pre>
 * Invision Power Services
 * Validator: Custom validation placeholder
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
				'key'		=> 'custom',
				'language'	=> $this->lang->words['validator__custom'],
				'regex'		=> '',
				'callback'	=> null,
				'error'		=> $this->lang->words['validator__custom_error'],
				);
