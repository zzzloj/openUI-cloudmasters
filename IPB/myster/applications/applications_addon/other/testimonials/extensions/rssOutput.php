<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class rss_output_testemunhos
{
	/**
	 * Expiration date
	 *
	 * @access	private
	 * @var		integer			Expiration timestamp
	 */
	private $expires			= 0;

	/**
	 * Grab the RSS links
	 *
	 * @access	public
	 * @return	string		RSS document
	 */
	public function getRssLinks()
	{		
		$return	= array();

		if( ipsRegistry::$settings['testemunhos_rss_on'] )
		{
			/* Lang */
			ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_testemunhos' ), 'testemunhos' );

	        $total = ipsRegistry::$settings['testemunhos_rss_items'] ? ipsRegistry::$settings['testemunhos_rss_items'] : 10;
			$title = ipsRegistry::$settings['testemunhos_rss_title'] ? ipsRegistry::$settings['testemunhos_rss_title'] : sprintf( ipsRegistry::getClass('class_localization')->words['rss_title'], $total );

	        $return[] = array( 'title' => $title, 'url' => ipsRegistry::getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . "/index.php?app=core&amp;module=global&amp;section=rss&amp;type=testemunhos", true, 'section=rss' ) );
	    }

	    return $return;
	}
	
	/**
	 * Grab the RSS document content and return it
	 *
	 * @access	public
	 * @return	string		RSS document
	 */
	public function returnRSSDocument()
	{
		/* Lang */
		ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_testemunhos' ), 'testemunhos' );
			
		//--------------------------------------------
		// Require classes
		//--------------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classRss.php' );
		$class_rss              =  new classRss();
		$class_rss->doc_type    =  ipsRegistry::$settings['gb_char_set'];

        $total = ipsRegistry::$settings['testemunhos_rss_items'] ? ipsRegistry::$settings['testemunhos_rss_items'] : 10;
		$title = ipsRegistry::$settings['testemunhos_rss_title'] ? ipsRegistry::$settings['testemunhos_rss_title'] : sprintf( ipsRegistry::getClass('class_localization')->words['rss_title'], $total );
		
		//-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        IPSText::getTextClass( 'bbcode' )->bypass_badwords	= 0;

		$channel_id = $class_rss->createNewChannel( array( 'title'        => ipsRegistry::getClass('class_localization')->words['testemunhos_title'],
														   'link'		  => ipsRegistry::$settings['board_url'] . '/index.php?app=testemunhos&amp;module=testemunhos&amp;section=rss',
															'pubDate'     => $class_rss->formatDate( time() ),
															'ttl'         => 30 * 60,
															'description' => $title
		) );
												
		ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'testemunhos', 'where' => 't_approved=1', 'order' => 't_date DESC', 'limit' => array( 0, ipsRegistry::$settings['testemunhos_rss_items'] ) ) );
		
		$outer = ipsRegistry::DB()->execute();
		
		while( $t = ipsRegistry::DB()->fetch($outer) )
		{
			IPSText::getTextClass( 'bbcode' )->parse_bbcode = 1;
			IPSText::getTextClass( 'bbcode' )->parse_html   = 0;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br  = 1;
	
			$t['t_content'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $t['t_content'] );

			$content  = substr( $t['t_content'], 0, 100 );
	        $content .= "<br /><br />[<a href='".ipsRegistry::$settings['base_url']."app=testemunhos&amp;showtestimonial={$t['t_id']}'>".ipsRegistry::getClass('class_localization')->words['rss_readmore']."</a>]";
	        $final_content = $content;
	        $t['content'] = $t['content'];
	           
	        $class_rss->addItemToChannel( $channel_id, array( 'title'       => $t['t_title'],
															   'link'        => ipsRegistry::$settings['base_url'].'app=testemunhos&amp;showtestimonial='.$t['t_id'],
															   'description' => $content,
															   'content'     => $t['t_content'],
															   'pubDate'	 => $class_rss->formatDate( $t['t_date'] ) ) );								     
		}
		
		$class_rss->createRssDocument();
		
		$class_rss->rss_document = ipsRegistry::getClass('output')->replaceMacros( $class_rss->rss_document );

		return $class_rss->rss_document;
	}
	
	/**
	 * Grab the RSS document expiration timestamp
	 *
	 * @access	public
	 * @return	integer		Expiration timestamp
	 */
	public function grabExpiryDate()
	{
		// Generated on the fly, so just return expiry of one hour
		return time() + 3600;
	}
}