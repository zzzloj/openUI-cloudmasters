<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.6.3
 * Reputation configuration for application
 * Last Updated: $Date: 2012-09-27 11:58:26 -0400 (Thu, 27 Sep 2012) $
 * </pre>
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Forums
 * @link		http://www.invisionpower.com
 * @version		$Rev: 11387 $ 
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class reputation_blog
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_topic' ), 'forums' );

		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php', 'classReputationCache' );
		ipsRegistry::setClass( 'repCache', new $classToLoad() );
	}

	/**
	 * Database Query to get results
	 *
	 * @param	string	'given' if we want to return items a user has liked
	 *					'received' if we want to fetch items a user has posted that others have liked
	 *					'most' if we want to fetch 'most' repped items
	 * @param	array	Member this applies to
	 *
	 * @return	array	Parameters to pass to ipsRegistry::DB()->build
	 *					Must return at least the data from reputation_index
	 *					limit and order will be applied automatically
	 */
	public function fetch( $type=NULL, $member=NULL )
	{
		
		$userGiving = 'r';
		$extraJoin = array();
		$_where = array();
		
		$_where[]	= "r.app='blog' AND r.type='entry_id' AND e.entry_id<>0 AND b.blog_disabled=0";
		$_where[]	= "( e.entry_status = 'published' OR e.entry_author_id=" . ipsRegistry::member()->getProperty('member_id') . " )";
		$_where[]	= '(b.blog_owner_only=0 OR e.entry_author_id=' . ipsRegistry::member()->getProperty('member_id') . ')';
		$_where[]	= "(b.blog_authorized_users IS NULL OR b.blog_authorized_users='' OR e.entry_author_id=" . ipsRegistry::member()->getProperty('member_id') . " OR FIND_IN_SET("  . ipsRegistry::member()->getProperty('member_id') .  ", b.blog_authorized_users) )";

		if ( $type == 'most' )
		{
			return array( 'type'   => 'entry_id',

					/* Used in first query to fetch type_ids */
					'inner'  => array( 'select'   => 'e.entry_id',
							'from'     => array( 'blog_entries' => 'e' ),
							//'where'    => '1=1',
							'where'    => 'e.entry_status=\'published\'',
							'add_join' => array( array( 'select' => '',
									'from'   => array( 'blog_blogs' => 'b' ),
									'where'  => 'b.blog_id=e.blog_id',
									'type'   => 'inner' ) ) 
					),
					/* Used in second query to fetch actual data */
					'joins'   => array( array( 'select'    => 'rc.*',
							'from'		=> array( 'reputation_cache' => 'rc' ),
							'where'		=> "rc.app='blog' AND rc.type='entry_id' AND rc.type_id=r.type_id" ),
							array( 'select'    => 'e.*',
									'from'		=> array( 'blog_entries' => 'e' ),
									'where'		=> 'r.type_id=e.entry_id' ),
							array( 'select'    => 'b.*',
									'from'		=> array( 'blog_blogs' => 'b' ),
									'where'		=> 'e.blog_id=b.blog_id' ),
										)
						);
		}
		else
		{
			if ( $type !== NULL )
			{
				$_where[] = ( ( $type == 'given' ) ? "r.member_id={$member['member_id']}" : "e.entry_author_id={$member['member_id']}" );
			}
			else
			{
				$userGiving = 'r2';
				$extraJoin = array( array(
						'from'		=> array( 'reputation_index' => 'r2' ),
						'where'		=> "r2.app=r.app AND r2.type=r.type AND r2.type_id=r.type_id AND r2.member_id=" . ipsRegistry::member()->getProperty('member_id')
				) );
			}
							
			return array(
					'select'	=> "r.*, rc.*, {$userGiving}.member_id as repUserGiving, e.*, b.*",
					'from'		=> array( 'reputation_index' => 'r'),
					'add_join'	=> array_merge( $extraJoin, array(
							array(
									'from'		=> array( 'reputation_cache' => 'rc' ),
									'where'		=> "rc.app=r.app AND rc.type=r.type AND rc.type_id=r.type_id"
							),
							array(
									'from'		=> array( 'blog_entries' => 'e' ),
									'where'		=> 'r.type_id=e.entry_id'
							),
							array(
									'from'		=> array( 'blog_blogs' => 'b' ),
									'where'		=> 'b.blog_id=e.blog_id'
							)
					) ),
					'where'		=>	implode( ' AND ', $_where ),
			);
			
		}
	}

	/**
	 * Process Results
	 *
	 * @param	array	Row from database using query specified in fetch()
	 * @return	array	Same data with any additional processing necessary
	 */
	public function process( $row )
	{
		/* Build poster's display data */
		$member = $row['entry_author_id'] ? IPSMember::load( $row['entry_author_id'], 'profile_portal,pfields_content,sessions,groups', 'id' ) : IPSMember::setUpGuest();
		$row    = array_merge( $row, IPSMember::buildDisplayData( $member, array( 'reputation' => 0, 'warn' => 0 ) ) );

		/* Parse BBCode */
		IPSText::getTextClass('bbcode')->parse_smilies			= $row['entry_use_emo'];
		IPSText::getTextClass('bbcode')->parse_html				= ( $member['g_dohtml'] and $row['entry_htmlstate'] ) ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_nl2br			= $row['entry_htmlstate'] == 2 ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
		IPSText::getTextClass('bbcode')->parsing_section		= 'blog_entry';
		IPSText::getTextClass('bbcode')->parsing_mgroup  		= $member['member_group_id'];
		IPSText::getTextClass('bbcode')->parsing_mgroup_others	= $member['mgroup_others'];
		$row['entry'] = IPSText::getTextClass('bbcode')->preDisplayParse( $row['entry'] );

		/* Get rep buttons */
		if ( $row['repUserGiving'] == ipsRegistry::member()->getProperty('member_id') )
		{
			$row['has_given_rep'] = $row['rep_rating'];
		}

		$row['rep_points'] = ipsRegistry::getClass('repCache')->getRepPoints( array( 'app' => 'blog', 'type' => 'entry_id', 'type_id' => $row['entry_id'], 'rep_points' => $row['rep_points'] ) );
		$row['repButtons'] = ipsRegistry::getClass('repCache')->getLikeFormatted( array( 'app' => 'blog', 'type' => 'entry_id', 'id' => $row['entry_id'], 'rep_like_cache' => $row['rep_like_cache'] ) );

		/* Return */
		return $row;
	}

	/**
	 * Display Results
	 *
	 * @param	array	Results after being processed
	 * @param	string	HTML
	 */
	public function display( $results )
	{
		return ipsRegistry::getClass('output')->getTemplate('blog_portal')->tabReputation_entries( $results );
	}
}

$rep_author_config = array( 'comment_id' => array( 'column' => 'member_id', 'table'  => 'blog_comments' ),
							'entry_id'   => array( 'column' => 'entry_author_id', 'table'  => 'blog_entries' ),
							);