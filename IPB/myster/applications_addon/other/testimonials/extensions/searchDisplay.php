<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

class testimonialsSearchDisplay implements iSearchDisplay
{
	public $search_plugin;		
	public function formatContent( $searchRow )
	{
		$read = ( ipsRegistry::getClass( 'classItemMarking' )->isRead( array( 'testemunho_cat' => $searchRow[ 't_cat' ], 'itemID' => $searchRow[ 't_id' ], 'itemLastUpdate' => $searchRow[ 't_date' ] ), 'testimonials' ) === TRUE ) ? 'read' : 'unread';
		$hot	=	( $searchRow[ 't_comments' ] >= ipsRegistry::$settings[ 'hot_topic' ] ) ? 'hot_': '';
		$searchRow[ 'statusIcon' ] = 't_' . $hot . $read;
		
		return ipsRegistry::getClass( 'output' )->getTemplate( 'testimonials' )->testemunhosSearchResult( $searchRow );
	}	
	
	public function getFilterHTML()
	{
		/* Can search? */
		$memberData =& ipsRegistry::member()->fetchMemberData();
		/*if ( $memberData[ 'g_l_allow_search' ] != 1 )
		{
			return '';
		}*/
		
		/* Lang... */
		ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_testemunhos' ), 'testimonials' );
	}
	
	public function buildFilterSQL( $data )
	{
		return array();
	}
}