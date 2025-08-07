<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Content - requirements checker
 * Last Updated: $Date: 2011-12-29 18:12:15 -0500 (Thu, 29 Dec 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $ (Orginal: Mark)
 * @copyright	(c) 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		10th December 2011
 * @version		$Revision: 10076 $
 */

class ccs_upgradeCheck
{
	/**
	 * Check we can upgrade
	 *
	 * @return	mixed	Boolean true or error message
	 */
	public function checkForProblems()
	{
		//-----------------------------------------
		// Compatibility check
		//-----------------------------------------
		
		$requiredIpbVersion = 33000; // 3.3.0
		
		$args = func_get_args();
		if ( !empty( $args ) )
		{
			$numbers = IPSSetUp::fetchAppVersionNumbers( 'core' );
		
			/* Are we upgrading core now? */
			if ( isset( $args[0]['core'] ) )
			{
				$ourVersion = $numbers['latest'][0];
			}
			/* No - check installed version */
			else
			{
				$ourVersion = $numbers['current'][0];
			}
			
			if ( $requiredIpbVersion > $ourVersion )
			{
				$allVersions = IPSSetUp::fetchXmlAppVersions( 'core' );
				
				return "This version of IP.Content requires IP.Board {$allVersions[ $requiredIpbVersion ]} or higher.";
			}
		}
		
		return TRUE;
	}
}