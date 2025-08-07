<?php

/* Revision: 2830 */
class public_topicslist_list_home extends ipsCommand
{
	
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
	
	$detailed = $this->settings['tl_detailed'];
	
	$this->sort = $this->sortFetch( $this->request['sort'] );
	
	# Can you view the Home? 
	$ok_view = FALSE;
	foreach( explode( ',' , $this->settings['tl_genabled'] ) as $g )
	{
		if( $g == $this->memberData['member_group_id'] ) {
			$ok_view = TRUE;
			break;
			}
		}
		
		if ( !$ok_view ) {
		$code = "21TL1";
		$this->registry->getClass('output')->showError( $this->lang->words['tl_noperms'], $code, false ); 
	}
	
		if( $this->settings['tl_userdecide'] )
		{
			$cookie_ex = IPSCookie::get( 'tl_defaultview');
			if( $cookie_ex ) $mode = $cookie_ex;
			else $mode = $this->settings['tl_detailed'];
		}
		
		else $mode = $this->settings['tl_detailed'];
		
		$where = $this->checkP( $this->settings['tl_fenabled'], $this->memberData['member_group_id'] );
		$attach = $where['attach'];
		$topics = $this->tPull( $mode, $l = '', $where['ids'], $attach );
		
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

		else {
		
			$template = $this->registry->output->getTemplate('list')->sideBar( $this->sort['title'], $letters, $this->sort['side'], $mode, $lang_mode, $dimensions );
			$template .= $this->registry->output->getTemplate('list')->classic( $topics, $this->sort['classic'] = '', $temps = 'alpha' ); 
			
			}
			
		# Setting Header Maintitle
		$this->registry->output->setTitle( $this->lang->words['tl_home'] );
		
		$this->registry->output->addNavigation( $this->lang->words['tl_home'], "" );	
		
	#	$template .= $this->registry->output->getTemplate('list')->test( $topics ); 
		
		# Skin Output
		$this->registry->getClass('output')->addContent( $template );
		$this->registry->getClass('output')->sendOutput();
		
		# END OF FUNCTION
	}
	
	public function sortFetch( $s )
	{
	
		if( $s == '' ) $s = $this->settings['tl_homesort'];
		
		switch( $s  )
		{
		
			case 'alpha':
				$sort = array(	'sort'	=>	'title ASC',
										'title'	=>	$this->lang->words['tl_alphasort'],
										'side'	=>	$this->lang->words['tl_alphasort'], );
				break;
				
			case 'update':
				$sort = array( 'sort'	=>	't.last_post DESC',
										'title'	=>	$this->lang->words['tl_rcomment'],
										'side'	=>	$this->lang->words['tl_rcomment'] . $this->lang->words['tl_thisf'],
										'classic'	=>	$this->lang->words['tl_clrcomment'], );
				break;
				
			case 'latests':
				$sort = array(	 'sort'		=> 'start_date DESC',
							   'title'	=> $this->lang->words['tl_latests'],
								'classic'	=>	$this->lang->words['tl_clstart']);
				break;

			case 'visits':
				$sort = array(  'sort'		=> 'views DESC',
									'title'	=> $this->lang->words['tl_views'],
									'classic'	=>	$this->lang->words['tl_clvisits'],);
				break;
			
			case 'comments':
				$sort = array(	'sort'	=>	'posts DESC',
									'title' =>	$this->lang->words['tl_comments'],
									'classic'	=>	$this->lang->words['tl_clcomments'],);
				break;
			
			case 'rate':
				$sort = array(	'sort'	=>	'topic_rating_total/topic_rating_hits DESC',
									'title' =>	$this->lang->words['tl_rate'], 
									'classic'	=>	$this->lang->words['tl_clrate'],);
				break;
			
			default:
				$code = "21TL3";
				$this->registry->getClass('output')->showError( $this->lang->words['tl_strangesort'], $code, false ); # words
			break;
		}
	
	return $sort;
		
		# END OF FUNCTION
	}
	
	public function checkP( $fids, $gid )
	{

		$fids[0] = $fids[ ( strlen($fids) - 1 ) ] = ' ';
		$gp_id = $this->DB->buildAndFetch( array( 'select' => 'g_perm_id', 'from' => 'groups', 'where' => "g_id={$gid}") );
		$this->DB->build( array( 	'select' 	=> "perm_type_id",
								'from'		=> 'permission_index',
								'where'		=> "(app = 'forums' AND perm_type = 'forum' AND perm_type_id IN (" . $fids . ")) AND ((perm_view = '*' || perm_view LIKE '%,{$gp_id['g_perm_id']},%' ) AND ( perm_2 = '*' || perm_2 LIKE '%,{$gp_id['g_perm_id']},%' ) )",
								'order'		=> 'perm_type_id',
								 ) );

		$this->DB->execute();
				
		while ( $r = $this->DB->fetch() )
			{
				$temp[] = $r['perm_type_id'];
			}
		
		$ids = implode( ',', $temp );
		
		$this->DB->build( array( 	'select' 	=> "id",
								'from'		=> 'forums',
								'where'		=> "id IN (". $ids .") AND password = ''",
								 ) );

		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
			{
				$final_ids[] = $r['id'];
			}
		
		$this->DB->build( array( 	'select' 	=> "perm_type_id, perm_6",
													'from'		=> 'permission_index',
													'where'		=> "app = 'forums' AND perm_type = 'forum' AND perm_type_id IN (" . implode( ',', $final_ids ) . ")",
													'order'		=> 'perm_type_id',
								 ) );

		$this->DB->execute();
		
		$attach = array();
		while ( $r = $this->DB->fetch() )
			{
				$attach[ $r['perm_type_id'] ] = $r['perm_6'];
			}

		
		return array( 'ids'	=> implode( ',', $final_ids ),
						'attach'	=>	$attach );
		# NOTE: For security reasons, topics belonging to password protected forums will NOT be shown in home.
		# END OF FUNCTION
	}	
		
	public function tPull( $mode, $l = '', $where, $attach )
	{
	
		
		$join = $this->fetchJoin( );
			
		$this->DB->build( array( 	'select' 	=> "tid, t.title, title_seo, t.forum_id, t.starter_name, t.starter_id, t.start_date, topic_rating_total, topic_rating_hits, t.views, t.posts, t.last_post",
								'from'		=> array( 'topics' => 't' ),
								'order'  	=> $this->sort['sort'],
								'where'		=> "t.forum_id IN (" . $where . ") AND approved = 1" . (( !$this->settings['tl_impt'] ) ? (" AND pinned = 0" ) : ( "" ) ),
								'limit'		=> array( intval($this->settings['tl_hometpull']) ),
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
			$p = 0;
			foreach( $words as $r )
			{	
				
				if( $mode == 'fb' Or $mode == 'detailed') {
					
						$mid		= $r['starter_id'];
						$member	= IPSMember::buildDisplayData( $mid, 'customFields', 'id' );
						$avatar = $member['pp_small_photo'];
											
					$img =  $this->getPrev( $r['post'], $r['attach_thumb_location'], $attach[ $r['id'] ] );					

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
				
				# Getting All posts
				$arr[   ] = array(  'title'		=> (( strlen( $r['title'] ) > 50 ) ? ( substr($r['title'] , 0, 50 ) . "..." ) : ( $r['title'] )),
									'tseo'		=> $r['title_seo'],
									'tid'		=> $r['tid'],
									'fid'		=>	$r['id'],
									'fseo'	=>	$r['name_seo'],
									'mid'		=>	$r['starter_id'],
									'watched'	=>	$r['trid'] > 0 ? 1 : 0,
									'lpost'	=>	$r['last_post'],
									'mid_seo'	=>	$r['members_seo_name'],
									'tposts'		=> $r['posts'],
									'tviews'		=> $r['views'],
									'date'		=> $r['start_date'],
									'starter'	=> $r['starter_name'],
									'forum'		=> $r['name'],
									'rate'		=> (( $r['topic_rating_hits'] > 0 ) ? ( $r['topic_rating_total']/$r['topic_rating_hits'] ) : ( 0 )),
									'post'		=> $plot ,
									'cover'		=> $img,
									'avatar'	=>	"<img src='{$avatar}' class='ipsUserPhoto ipsUserPhoto_medium' alt=''  />",
									'number' 	=> $i,);	
				if( $i == 2 ) $i = 1;
				else ++$i;				

			}

		}
		
		if( $mode == 'detailed' )
		{
			$arr_num = count( $arr )%2;
			if( $arr_num != 0){
				
				
				$arr[   ] = array(  	'title'		=> '',
									);
				
				
			}
		}
	
	return $arr;

	
	#	END OF FUNCTION
	}
	
	public function checkAttp( $array, $i, $img )
	{
	
		$success = FALSE;
		foreach( explode( ',', $array ) as $t ){
		
			if( $t == $i ){
				$cover = $this->settings['upload_url'] . "/" . $img;
				$success = TRUE;
				break;
				}		
		}
		
		if( !$success ) return "no_attached_images";
		else return $cover;
	
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
									
		$temp[] = array(	'select'	=>	'f.id, f.name, f.name_seo',
									'from'	=>	array( 'forums' => 'f' ),
									'where'	=>	'f.id = t.forum_id', );
		
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
	
}

#***************************************#
# END OF FILE						   #
#***************************************#
