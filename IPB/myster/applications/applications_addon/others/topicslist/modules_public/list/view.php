<?php

/* Revision 2852 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}


class public_topicslist_list_view extends ipsCommand
{
	

	private $forum; 
	private $sort = array();
	
	/**
	 * Main execution point
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry )
    {
   

	$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_home' ), 'topicslist' );


	$ok_l = FALSE;
	foreach( explode( ',' , $this->settings['tl_genabled'] ) as $g )
	{
		if( $g == $this->memberData['member_group_id'] ) {
			$ok_l = TRUE;
			break;
			}
		}
	if ( !$ok_l ) {
		$code = "21TL1";
		$this->registry->getClass('output')->showError( $this->lang->words['tl_noperms'], $code, false ); 
	}
	
	$ok_v = FALSE;
	foreach( explode( ',' , $this->settings['tl_fenabled'] ) as $g )
	{
		if( $this->request['f'] == $g ) {
			$ok_v = TRUE;
			break;
			}
		}
	if ( !$ok_v ) {
		$code = "21TL2";
		$this->registry->getClass('output')->showError( $this->lang->words['tl_nolistf'], $code, false ); 
	}
	

		
	$ok_a = FALSE;	
		
		foreach( explode( ',' , $this->settings['tl_fall'] ) as $g )
		{
			if( $this->request['f'] == $g ) {
				$ok_a = TRUE;
				break;
				}
			}
		
	if( !$ok_a AND strtolower( $this->request['l'] ) == "all" ) {
	
		$code = "21TL4";
		$this->registry->getClass('output')->showError( $this->lang->words['tl_noall'], $code, false );
	
	}
	
	if( strtolower( $this->request['l'] ) == "all" And $this->request['sort'] != '' ){
	
		$code = "21TL5";
		$this->registry->getClass('output')->showError( $this->lang->words['tl_nosortall'], $code, false );
		}
		
	
	$ok_f = $this->checkF( $this->memberData['member_group_id'], $this->request['f'] );
	if( !$ok_f['can'] ) {
	
		$code = "21TL8";
		$this->registry->getClass('output')->showError( $this->lang->words['tl_nopermf'], $code, false );
	
	}
	
	$att = $ok_f['att'];
	# Password protected forums
	$pass = $this->DB->buildAndFetch( array( 	'select' 	=> "password",
								'from'		=> 'forums',
								'where'		=> "id = {$this->request['f']}",
								 ) );
								 
	if( $pass['password'] != '' ){
		if( !IPSCookie::get( "ipbforumpass_".$this->request['f'] ) ) {
					$code = "21TL9";
			$this->registry->getClass('output')->showError( $this->lang->words['tl_passprot'] . "<a href='{$this->settings['board_url']}/index.php?&amp;showforum={$this->request['f']}'>{$this->settings['board_url']}/index.php?&amp;showforum={$this->request['f']}</a>", $code, false );
		}	
	}
	
		
		$this->sort = $this->sortFetch( $this->request['sort'] );

		if( $this->settings['tl_userdecide'] )
		{
			$cookie_ex = IPSCookie::get( 'tl_defaultview');
			if( $cookie_ex ) $mode = $cookie_ex;
			else $mode = $this->settings['tl_detailed'];
		}
		
		else $mode = $this->settings['tl_detailed'];
		
		$letters = $this->getL( $this->request['f'], $ok_a );
		$topics = (( strtolower( $this->request['l'] ) == 'all' ) ? ( $this->aPull( ) ) : ( $this->tPull( $mode, $this->request['l'], $att ) ) );
		
		# Setting Header Maintitle
		$this->registry->output->setTitle($this->lang->words['tl_viewinglist']);
		
		$this->registry->output->addNavigation( $this->lang->words['tl_viewinglist'], "" );	
		
		if( strtolower( $this->request['l'] ) == 'all' ){
			$this->sort['title'] = $this->lang->words['tl_viewingall'];
			}
		elseif( strtolower( $this->request['l'] ) == 'specials' ){
			$this->sort['title'] = $this->lang->words['tl_viewingspecials'];
			}
		
		sscanf( $this->settings['tl_maximgdet'], "%dx%d", $det_wid, $det_hei); 
		sscanf( $this->settings['tl_fbprevimg'], "%dx%d", $prev_wid, $prev_hei);

		
		$dimensions = array(	'det_wid'	=>	$det_wid,
											'det_hei'	=>	$det_hei,
											'prev_wid'	=>	$prev_wid,
											'prev_hei'		=>	$prev_hei,
											'th'		=>	$det_wid+20,
											'td'		=>	$prev_wid -10,
											);
		
		# Skin Output*/
	
		#$template = $this->registry->output->getTemplate('list')->sideBar( $this->sort['title'], $letters, $this->sort['side'], $mode );
		
		if( strtolower( $this->request['l'] ) != "all" )
		{
			switch( $mode )
			{
				case 'fb':
					$lang_mode = $this->lang->words['tl_fb'];
					$template = $this->registry->output->getTemplate('list')->sideBar( $this->sort['title'], $letters, $this->sort['side'], $mode, $lang_mode, $dimensions );
					$template .= $this->registry->output->getTemplate('list')->fb_style( $topics );
				break;
				
				case 'detailed':
					$lang_mode = $this->lang->words['tl_detailed'];
					$template = $this->registry->output->getTemplate('list')->sideBar( $this->sort['title'], $letters, $this->sort['side'], $mode, $lang_mode, $dimensions );
					$template .= $this->registry->output->getTemplate('list')->detailed( $topics, $dimensions );
				break;
				
				default:
					$lang_mode = $this->lang->words['tl_classic'];
					if( $this->request['sort'] == '') $temps = $this->settings['tl_homesort'];
					else $temps = $this->request['sort'];
					$template = $this->registry->output->getTemplate('list')->sideBar( $this->sort['title'], $letters, $this->sort['side'], $mode, $lang_mode, $dimensions );
					$template .= $this->registry->output->getTemplate('list')->classic( $topics, $this->sort['classic'], $temps );
				break;					
			}
		
		}	

		else 
		{			
			$template = $this->registry->output->getTemplate('list')->sideBar( $this->sort['title'], $letters, $this->sort['side'], $mode='classic', $lang_mode, $dimensions );
			$template .= $this->registry->output->getTemplate('list')->classic( $topics, $this->sort['classic'] = '', $temps = 'alpha' ); 
		}

		$this->registry->getClass('output')->addContent( $template );
		$this->registry->getClass('output')->sendOutput();
		
		###### END OF FUNCTION ########
	}
	
	public function checkF( $g, $f )
	{
		
		$gp_id = $this->DB->buildAndFetch( array( 'select' => 'g_perm_id', 'from' => 'groups', 'where' => "g_id={$g}") );
		$this->DB->build( array( 	'select' 	=> "perm_type_id, perm_6",
								'from'		=> 'permission_index',
								'where'		=> "app = 'forums' AND perm_type = 'forum' AND perm_type_id = {$f} AND ((perm_view = '*' || perm_view LIKE '%,{$gp_id['g_perm_id']},%' ) AND ( perm_2 = '*' || perm_2 LIKE '%,{$gp_id['g_perm_id']},%' ) )",
								
								 ) );

		$this->DB->execute();
		
		if( !($r = $this->DB->fetch()) ) return FALSE;
		else return array( 'can'	=> TRUE,
							'att'	=> $r['perm_6']);	
	
		#	END OF FUNCTION
	}
	
	public function sortFetch( $s )
	{
	
		if( $s == '' ){
		
			if( $this->request['l'] == '' ) $s = $this->settings['tl_homesort'];
			else $s = 'title';
		
		}

		switch( $s  )
		{
		
			case 'alpha':
				$sort = array(	'sort'	=>	't.title ASC',
										'title'	=>	$this->lang->words['tl_alphasort'],
										'side'	=>	$this->lang->words['tl_alphasort'],
										'classic'	=>	'classic' );
				break;
				
			case 'update':
				$sort = array( 'sort'	=>	't.last_post DESC',
										'title'	=>	$this->lang->words['tl_rcomment'],
										'side'	=>	$this->lang->words['tl_rcomment'] . $this->lang->words['tl_thisf'],
										'classic'	=>	$this->lang->words['tl_clrcomment'], );
				break;
				
			case 'latests':
				$sort = array( 'sort'		=> 'start_date DESC',
								   'title'	=> $this->lang->words['tl_latestlt'],
								   'side'	=> $this->lang->words['tl_latests'] . $this->lang->words['tl_thisf'],
								   'classic'	=>	$this->lang->words['tl_clstart']);
				break;

			case 'visits':
				$sort = array(  'sort'		=> 'views DESC',
									'title'	=> $this->lang->words['tl_viewslt'] ,
									'side'	=> $this->lang->words['tl_visits'] . $this->lang->words['tl_thisf'],
									'classic'	=>	$this->lang->words['tl_clvisits'],);
				break;
			
			case 'comments':
				$sort = array(	'sort'	=>	'posts DESC',
									'title' =>	$this->lang->words['tl_commentslt'], 
									'side'	=> $this->lang->words['tl_comments'] . $this->lang->words['tl_thisf'],
									'classic'	=>	$this->lang->words['tl_clcomments'],);
				break;
			
			case 'rate':
				$sort = array(	'sort'	=>	'topic_rating_total/topic_rating_hits DESC',
									'title' =>	$this->lang->words['tl_ratelt'], 
									'side'	=> $this->lang->words['tl_rate'] . $this->lang->words['tl_thisf'],
									'classic'	=>	$this->lang->words['tl_clrate'],);
				break;
			
			case 'title':
				$sort = array( 	'sort'	=> 'title',
								'title' => $this->lang->words['tl_alphalt'] . strtoupper( $this->request['l'] ),
								'side'	=> '');
				break;

			default:
				$code = "21TL3";
				$this->registry->getClass('output')->showError( $this->lang->words['tl_strangesort'], $code, false ); 
			break;
		}
		
	
	return $sort;
		#	END OF FUNCTION
	}
	
	public function aPull( )
	{
	
	
		$this->DB->build( array( 	'select' 	=> "tid, t.title, title_seo, starter_name",
								'from'		=> array( 'topics' => 't' ),
								'order'  	=> $this->sort['sort'],
								'where'		=> "t.forum_id = {$this->request['f']} AND approved = 1" . (( !$this->settings['tl_impt'] ) ? ( " AND pinned = 0 " ) : ( "" ) ),
								 ) );

		$this->DB->execute();
	
		$rows = array();
				
		if ( $this->DB->getTotalRows() )
		{
			while ( $r = $this->DB->fetch() )
			{
				$temp[] = $r;

			}
		
			foreach( $temp as $t ){
			
				$arr[  ] = array(  	'title'		=> $t['title'],
									'tseo'		=> $t['title_seo'],
									'tid'		=> $t['tid'],									
									'starter'	=> $t['starter_name'],	);	
			
			}
		}
		
		else {
		
			$code = "21TL7";
			$this->registry->getClass('output')->showError( $this->lang->words['tl_strangel'], $code, false );
		
		}
		
		return $arr;
		#	END OF FUNCTION
	}
	
	public function tPull( $mode, $l = '', $att)
	{
	
		if( $l != '' ){
			
			if( strtolower( $l ) == 'specials' ){
			
				$where = " AND t.title NOT BETWEEN 'a' and 'z' and t.title not LIKE 'z%' and t.title NOT BETWEEN '0' and '9' and t.title NOT LIKE '9%'";
			
			}
			else	$where = " AND ( t.title LIKE '" . utf8_encode( $l ) . "%' )";
			
		}
		
		$join = $this->fetchJoin( );
		
		$this->DB->build( array( 	'select' 	=> "tid, t.title, title_seo, starter_name, starter_id, t.start_date, topic_rating_total, topic_rating_hits, views, t.posts, t.last_post",
								'from'		=> array( 'topics' => 't' ),
								'order'  	=> $this->sort['sort'],
								'where'		=> "t.forum_id = {$this->request['f']} AND approved = 1" . (( $l != '' ) ? ( $where ) : ("")) . (( !$this->settings['tl_impt'] ) ? ( " AND pinned = 0 " ) : ( "" ) ),
								'limit'		=> (( $l == '' ) ? (array( intval($this->settings['tl_hometpull']) ) ) : ( "" ) ),
								'add_join'	=> $join ) );

		$this->DB->execute();

		
		# Loop through the results 
		$rows = array();
				
		if ( $this->DB->getTotalRows() )
		{
			while ( $r = $this->DB->fetch() )
			{
				$words[] = $r;

			}
			
			$i = 1;
			foreach( $words as $r )
			{	
				
				if( $mode == 'detailed' Or $mode == 'fb' ) {
				
			
						$mid		= $r['starter_id'];
						$member	= IPSMember::buildDisplayData( $mid, 'customFields', 'id' );
						$avatar = $member['pp_small_photo'];						
					
			
					$img =  $this->getPrev( $r['post'], $r['attach_thumb_location'], $att );
														
					if( $this->settings['tl_bbcode'] != ""  ){
					
					if( $this->settings['tl_bbcode'] == "/POST" ) $plot = $this->treatPlot( strip_tags( html_entity_decode( $r['post'] ) ) );
					
					else{
						preg_match("!(\[{$this->settings['tl_bbcode']}\])([^\]]+)?\[\/{$this->settings['tl_bbcode']}\]!Ui", $r['post'], $matches );
						$to_subs = array(	'open'	=> "[{$this->settings['tl_bbcode']}]",
														'closure' => "[/{$this->settings['tl_bbcode']}]");
						$matches[0] = str_ireplace( $to_subs, '', $matches[0]);
						$plot = $this->treatPlot( strip_tags( html_entity_decode( $matches[0] ) ) );
						}
					}
				
					else $plot = "";
								
				}
				
				if( $mode == 'classic' ) $plot = "";				
				
				# Getting All posts
				$arr[  ] = array(  	'title'		=> (( strlen( $r['title'] ) > 50 ) ? ( substr($r['title'] , 0, 50 ) . "..." ) : ( $r['title'] )),
									'tseo'		=> $r['title_seo'],
									'tid'		=> $r['tid'],
									'mid'		=>	$mid,
									'watched'	=>	$r['trid'] > 0 ? 1 : 0,
									'lpost'	=>	$r['last_post'],
									'mid_seo'	=>	$r['members_seo_name'],
									'tposts'		=> $r['posts'],
									'tviews'		=> $r['views'] ,
									'date'		=> $r['start_date'],
									'starter'	=> $r['starter_name'],
									'forum'		=> $r['name'],
									'rate'		=> (( $r['topic_rating_hits'] > 0 ) ? ( $r['topic_rating_total']/$r['topic_rating_hits'] ) : ( 0 )),
									'post'		=> $plot ,
									'cover'		=> $img,
									'avatar'	=>	"<img src='{$avatar}' class='ipsUserPhoto ipsUserPhoto_medium' alt=''  />",
									'number' 	=> $i	);	
									
				if( $i == 2 ) $i = 1;
				else ++$i;				

			}

		}
		
		else {
			
			if( isset( $this->request['l'] ) ) {
				$code = "21TL7";
				$this->registry->getClass('output')->showError( $this->lang->words['tl_strangel'], $code, false ); }
			
			else {	
				$code = "21TL6";
				$this->registry->getClass('output')->showError( $this->lang->words['tl_not'], $code, false ); # words
			}
		}
		
		
		if( $mode == 'detailed' ) {
			$arr_num = count( $arr )%2;
			if( $arr_num != 0){
				
				
				$arr[   ] = array(  	'title'		=> '',
									);
				
				
			}
		}	
	
	return $arr;
		# END OF FUNCTION
	}
	
	public function fetchJoin()
	{
	
		$temp = array();
		
		$temp[] = array(	'select'	=>	'm.members_seo_name',
									'from'	=>	array( 	'members'	=> 'm' ),
									'where'	=> 'm.member_id = t.starter_id', 
									'type'		=>	'left',);
									
		$temp[] = array(	'select'	=> 'p.post' . (( $this->settings['tl_pullattach'] ) ? ( ', p.post_key' ) : ( '' )),
									'from'	=> array( 'posts' => 'p' ),
									'where'	=> 'p.topic_id = t.tid AND new_topic = 1',
									'type' 	=> 'left', );
								
		
		if( $this->settings['tl_pullattach'] ) {
			
			$temp[] = array( 	'select'	=>	'a.attach_thumb_location',
											'from'		=>	array( 'attachments' => 'a' ),
											'where'		=>	'a.attach_is_image = 1 AND a.attach_post_key = p.post_key',
											'type'		=>	'left',);
		}
		
		if( $this->settings['tl_tracker'] ){
		
			$temp[] = array( 'select'		=>	'r.trid',
										'from'		=>	array( 'tracker' => 'r' ),
										'where'	=>	"r.topic_id = t.tid AND r.member_id = {$this->memberData['member_id']} ", 
										'type'		=>	'left',);
		
		
		}
	
		return $temp;
	}
			
	public function checkAttp( $array, $i, $img )
	{
	
		$success = FALSE;
		foreach( explode( ',', $array ) as $t ){
		
			if( $t == $i ){
				$cover = $this->settings['upload_url'] . "/" . $img;
				$success = TRUE;
				}		
		}
		
		if( !$success ) return "no_attached_images";
		else return $cover;
	
	}
		
	public function getL( $f, $ok )
	{
		
		# This is is not gonna work for utf-8 with accent letters 
		$this->DB->build( array( 	'select' 	=> "DISTINCT LEFT(t.title, 1)",
								'from'		=> array( 'topics' => 't' ),
								'where'		=> "t.forum_id = {$f}" . ( ( !$this->settings['tl_impt'] ) ? ( " AND pinned = 0" ) : ( "" ) ),
								'order'		=> 'title',
								
								 ) );

		$this->DB->execute();
		
		
		if ( $this->DB->getTotalRows() )
		{
			while ( $r = $this->DB->fetch() )
			{
				$rows[] = $r;

			}
			
			foreach( $rows as $r )
			{	
					foreach( $r as $row ){
					
						$row = $this->treatL( $row  );
						if( ctype_alnum( $row ) ){
						$let[ $row ] = array(  	'title'		=> strtolower( $row ),
												'index'		=> strtoupper( $row ),
												'lang'		=> $this->lang->words['tl_viewl'] . strtoupper( $row ),
												);	
									
					}
					
					else $specials = TRUE;
				}												
			}
			
			if( $specials ){
			
				$let['specials'] = array(	'title'	=>	'specials',
										'index'	=>	$this->lang->words['tl_specials'],
										'lang'	=>	$this->lang->words['tl_specials'],
									);
			
			}
			if( $ok ) $let['all'] = array(	'title'	=>	'all',
									'index'	=>	$this->lang->words['tl_tall'],
									'lang'	=>	$this->lang->words['tl_viewall'],
									);
	
		}
		
		return $let;
		#	END OF FUNCTION
	}
	
	public function treatL( $r )
	{
	
	$to_sub['chars'] = array(
    'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 
    'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 
    'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 
    'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 
    'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 
    'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 
    'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
);
	
	return strtr($r, $to_sub['chars']);	
	# 	END OF FUNCTION
	}
	
	public function treatPlot( $p )
	{
	
		$to_sub['chars'] = array( '<br />'	=>	'', '<br>'	=>	'',	'<br/>'	=>	'', '\n'	=>	'', );
		$p = strtr( $p, $to_sub['chars'] );
		

		$p = preg_replace( "#\[img\](.+?)\[/img\]#ie" , '' , $p );
		$p = preg_replace( "#\[url\=(.+?)\](.+?)\[/url\]#ie" , '' , $p );

		
		return substr( wordwrap( $p , 50, "\n", true), 0, 250 );
		
		# END OF FUNCTION
	}

	public function getPrev($post, $att_loc, $att_perm_id )
	{
	
		$found = FALSE;
		if( ( $this->settings['tl_pullattach'] ) And $att_loc != '' )
			{
				
				$img = $this->checkAttP( $att_perm_id, $this->memberData['member_group_id'], $att_loc );				
				if( $img == "no_attached_images" ) $found = FALSE;
				else $found = TRUE;
			}
			
	if( !$found )
	{
		if( $this->settings['tl_enallimgs'] )
		{
			$success = TRUE;
			
			preg_match_all('!(\[img\])http://[a-z0-9\_\%\-\.\/]+\.(?:jpe?g|png|gif)\[\/img\]!Ui', $post, $matches, PREG_PATTERN_ORDER );
			
			if( !count( $matches ) )	$success = FALSE;
			
			if( $success )
			{
				$found = FALSE;
				
				foreach( $matches[0] as $match )
				{
				
					$to_subs = array(	'open'	=> '[/img]',
												'	close' => '[img]');
												
					$t	=	@getimagesize( str_ireplace( $to_subs, '', $match) );
					
						if( is_array( $t ) )
						{
							$found = TRUE;
							$img = str_ireplace( $to_subs, '', $match);
							break;
						
						}
				}
			}
		}
		
		else
		{
				$found = FALSE;
				
				preg_match('!(\[img\])http://[a-z0-9\_\%\-\.\/]+\.(?:jpe?g|png|gif)\[\/img\]!Ui', $post , $matches );
				
				if( count( $matches ) > 0 ) 
				{
							
					$to_subs = array(		'open'	=> '[/img]',
														'closure' => '[img]');
														
					$matches[0] = str_ireplace( $to_subs, '', $matches[0]);
				
					$img = $matches[0];
					
					$found = TRUE;
			
				}
		}
		
	}
		
		if( $found ) return $img;
		else return "{$this->settings['img_url']}/{$this->settings['tl_noimg']}";

	}
	
	}


#***************************************#
# END OF FILE						    #
#***************************************#
