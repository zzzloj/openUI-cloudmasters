<?php

/**
 * Product Title:		(SOS34) Track Member
 * Product Version:		1.0.0
 * Author:				Adriano Faria
 * Website:				SOS Invision
 * Website URL:			http://forum.sosinvision.com.br/
 * Email:				administracao@sosinvision.com.br
 */

class trackMembersLib
{	
	public static $member = array();
	
	/**
	 * @return the $member
	 */
	public static function getMember( $key = '' )
	{
		if ( $key AND isset( self::$member[ $key ] ) )
		{
			return self::$member[ $key ];
		}
		
		return self::$member;
	}

	/**
	 * @param field_type $member
	 */
	public static function setMember( $member )
	{
		self::$member = $member;
	}

	/**
	 * Check if we can track this member.
	 * 
	 * @param 	mixed 	$member		Member ID, or array of member data
	 * @param 	string	$function	Function name that makes the call
	 * @param	mixed	$functionId	Pass 'FALSE' to prevent the flood check query, or the relationship ID of the function
	 * @return	bool	To track, or not to track.
	 */
	public static function canTrack( $member, $function, $functionId = 0 )
	{
		self::setMember( $member );
		
		/* System off? */
		if ( ! ipsRegistry::$settings['trackmembers_onoff'] )
		{
			return false;
		}
		
		/* Load memberData */
		if ( ! is_array( $member ) )
		{
			self::setMember( IPSMember::load( self::getMember(), 'core' ) );
		}
		
		/* Get out, guest! */
		if ( ! self::getMember( 'member_id' ) )
		{
			return false;
		}
		
		/* Allowed to be tracked? */
		if ( ! IPSMember::isInGroup( self::getMember(), explode( ',', ipsRegistry::$settings['trackmembers_groups'] ) ) OR ! self::getMember( 'member_tracked' ) )
		{
			return false;
		}
		
		/* Tracking this function/action for this member? */
		if ( ! self::checkFunction( $function ) )
		{
			return false;
		}
		
		/* Flooding the system? */
		if ( ! self::floodCheck( $function, $functionId ) )
		{
			return false;
		}
		
		/* Still here? Load lang file */
		ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_trackmembers' ), 'trackmembers' );
		
		return true;
	}
	
	
	
	/**
	 * Check if we are supposed to track this action, based on the function name
	 * 
	 * @param 	string $function	Function name that makes the call
	 * @return	bool
	 */
	private static function checkFunction( $function )
	{
		$curSettings = IPSMember::getFromMemberCache( self::getMember(), 'trackmembers' );
		
		if ( $curSettings[ $function ] AND $curSettings[ $function ] == 0 )
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Make sure we're not flooding the database by 
	 * adding the same data within a given time.
	 * 
	 * @param 	string 	$function	Function name that makes the call
	 * @param	mixed	$functionId	Pass 'FALSE' to prevent the flood check query, or the relationship ID of the function
	 * @return	bool
	 */
	private static function floodCheck( $function, $functionId = 0 )
	{
		$floodTime = (int) ipsRegistry::$settings['trackmembers_floodControl'];
		
		if ( ! $floodTime )
		{
			return true;
		}
		
		if ( $functionId === FALSE )
		{
			return true;
		}
		
		$extraWhere = ( $functionId > 0 ) ? ' AND function_id =' . $functionId : '';
		
		$mid = self::getMember( 'member_id' );
		$lastLog = ipsRegistry::DB()->buildAndFetch(
			array(
				'select'	=> '*',
				'from'		=> 'members_tracker',
				'where'		=> "member_id={$mid} AND function='{$function}'" . $extraWhere,
				'order'		=> 'date desc',
			)
		);

		if ( is_array( $lastLog ) AND count( $lastLog ) )
		{
			if ( $lastLog['date'] < time() - ( $floodTime * 60 ) )
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		
		return true;
	}
}