<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Calendar - requirements checker
 * Last Updated: $Date: 2012-05-21 09:09:36 -0400 (Mon, 21 May 2012) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $ (Orginal: Mark)
 * @copyright	(c) 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Calendar
 * @link		http://www.invisionpower.com
 * @since		8th September 2010
 * @version		$Revision: 10771 $
 */

class calendar_upgradeCheck
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
		
		$requiredIpbVersion = 32003; // 3.2.0
		
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
				
				return "This version of IP.Calendar requires IP.Board {$allVersions[ $requiredIpbVersion ]} or higher.";
			}
		}
		
		return TRUE;
	}
}