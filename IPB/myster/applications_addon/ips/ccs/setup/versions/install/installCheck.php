<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS installation checker
 * Last Updated: $Date: 2012-01-27 13:49:51 -0500 (Fri, 27 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		11th May 2009
 * @version		$Revision: 10211 $
 */
 
class ccs_installCheck
{
	/**
	 * Check for any problems and report errors if any exist
	 *
	 * @access	public
	 * @return	array
	 */
	public function checkForProblems()
	{
		$info  = array( 'notexist' => array(), 'notwrite' => array(), 'other' => array() );
		
		if( !is_file( DOC_IPS_ROOT_PATH . 'media_path.php' ) )
		{
			if( is_file( DOC_IPS_ROOT_PATH . 'media_path.dist.php' ) )
			{
				if( !@rename( DOC_IPS_ROOT_PATH . 'media_path.dist.php', DOC_IPS_ROOT_PATH . 'media_path.php' ) )
				{
					$info['other'][]	= "You must rename 'media_path.dist.php' to 'media_path.php'.  The file will be found in the 'root' of your IP.Board installation (the same folder where initdata.php is located).";
				}
			}
			else
			{
				$info['other'][]	= "You must upload 'media_path.dist.php' and rename it to 'media_path.php'.  The file should be uploaded to the 'root' of your IP.Board installation (the same folder where initdata.php is located).";
			}
		}
		
		return $info;
	}
}