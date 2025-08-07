<?php
/*
+--------------------------------------------------------------------------
|   Form Manager v1.0.0
|   =============================================
|   by Michael
|   Copyright 2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_format_form extends search_format
{
	/**
	 * Constructor
	 */
	public function __construct( ipsRegistry $registry )
	{
		parent::__construct( $registry );
	}
	
	/**
	 * Parse search results
	 *
	 * @access	private
	 * @param	array 	$r				Search result
	 * @return	array 	$html			Blocks of HTML
	 */
	public function parseAndFetchHtmlBlocks( $rows )
	{
		return parent::parseAndFetchHtmlBlocks( $rows );
	}
	
	/**
	 * Formats the forum search result for display
	 *
	 * @access	public
	 * @param	array   $search_row		Array of data from search_index
	 * @return	mixed	Formatted content, ready for display, or array containing a $sub section flag, and content
	 **/
	public function formatContent( $data )
	{      
		ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_external' ), 'form' );
		
		$is_read = ipsRegistry::getClass( 'classItemMarking' )->isRead( array( 'forumID' => $data['log_form_id'], 'itemID' => $data['type_id_2'], 'itemLastUpdate' => $data['log_date'] ), 'form' );
			
		$is_member = '';
			
		if ( ipsRegistry::$settings['show_user_posted'] )
		{
			if ( intval( ipsRegistry::member()->getProperty('member_id') ) == $data['member_id'] )
			{
				$is_member = 1;
			}
		}
		
		$data['result_icon'] = ipsRegistry::getClass( 'class_forums' )->fetchTopicFolderIcon( $search_row, $is_member, $is_read );

		return array( ipsRegistry::getClass( 'output' )->getTemplate( 'form_external' )->logSearchResults( $data, IPSSearchRegistry::get('display.onlyTitles') ), 0 );
    }

	/**
	 * Formats / grabs extra data for results
	 * Takes an array of IDS (can be IDs from anything) and returns an array of expanded data.
	 *
	 * @access public
	 * @return array
	 */
	public function processResults( $ids )
	{
		$rows = array();
		
		foreach( $ids as $i => $d )
		{
			$rows[ $i ] = $this->genericizeResults( $d );
		}
		
		return $rows;	
	}
	
	/**
	 * Reassigns fields in a generic way for results output
	 *
	 * @param  array  $r
	 * @return array
	 **/
	public function genericizeResults( $r )
	{
		$r['app']                 = 'form';
		$r['content']             = $r['message'];
		$r['content_title']       = $r['message'];
		$r['updated']             = $r['log_date'];
		$r['type_2']              = 'log';
		$r['type_id_2']           = $r['log_id'];
		$r['misc']                = $r['log_date'];
		$r['member_id']			  = $r['member_id'];
		
		return $r;
	}

}