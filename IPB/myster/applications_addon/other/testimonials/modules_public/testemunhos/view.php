<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

if ( !defined('IN_IPB') )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_testimonials_testemunhos_view extends ipsCommand
{
	private $output;
	private $library;
	public $testemunho = array();
	protected $qpids = array();

	public function doExecute( ipsRegistry $registry )
	{
	    $cid = intval( $this->request['cid'] );
		
        $this->registry->output->addNavigation( $this->lang->words['testemunhos_title'], "app=testimonials" );
       
		$this->output  = $this->registry->output;
		$this->library = $this->registry->getClass('testemunhosLibrary');
		
		$this->library->checkPermissions();
        //$cid = intval( $this->request['showtestimonial'] );
		
		$this->qpids = IPSCookie::get( 'testemunhos_pids' );
		
		$tid = intval( $this->request['showtestimonial'] );
       

		if ( $this->settings['reputation_enabled'] )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php' );
			$this->registry->setClass( 'repCache', new classReputationCache() );
			
			$_joins[] = $this->registry->repCache->getUserHasRatedJoin( 't_id', 't.t_id', 'testimonials' );
			$_joins[] = $this->registry->repCache->getTotalRatingJoin(  't_id', 't.t_id', 'testimonials' );
		}

		$this->testemunho = $t = $this->DB->buildAndFetch( array( 'select'   => 't.*',
											  					  'from'     => array( 'testemunhos' => 't' ),
											  					  'where'    => 't.t_id='.$tid,
                                                                  //'where'    => 't.t_cat='.$cid,
											  					  'add_join' => $_joins
		) );


        
        if ( $this->settings['reputation_enabled'] )
        {
            $t['like']    = $this->registry->repCache->getLikeFormatted( array( 'app' => 'testemunhos', 'type' => 't_id', 'id' => $t['t_id'], 'rep_like_cache' => $t['rep_like_cache'] ) );
        }
           	                           
		/* Temos um testemunho? */
		if ( !$t )
		{
			$this->registry->getClass('output')->showError( 'testemunho_no_tid' );
		}
		
		/* Want to view something else? */
		$this->_doViewCheck();

		$all = $this->DB->buildAndFetch( array( 'select' => '*',	'from' => 'testemunhos_cats', 'where' => 'c_id='.$t['t_cat'] ) ); 
		
        $this->registry->output->addNavigation( $all['c_name'], $this->registry->getClass('output')->buildSEOUrl( "showlist=".$all['c_id'], 'publicWithApp', $all['c_name_seo'], 'showlist' ) );
   
     
		/* What to do? */
		switch ( $this->request['do'] )
		{
			case 'next':
				$this->viewNextTestemunho( $t );
			break;
			
			case 'prev':
				$this->viewPreviousTestemunho( $t );
			break;
            			
			default:
				$this->testemunho( $t );
			break;
		}
	}
	
	public function testemunho( $t )
	{
		$this->registry->output->addNavigation( $this->lang->words['viewing_testemunho'].": ".$t['t_title'], '' );
   

		$_canEdit = 0;

		/* Pode editar? */
		if ( $this->memberData['sostestemunhos_editar'] )
		{
			$_canEdit = 1;
		}

		if ( $this->memberData['member_id'] == $t['t_member_id'] )
		{
			//-----------------------------------------
			// Have we set a time limit?
			//-----------------------------------------
				
			if ( $this->memberData['sostestemunhos_max_time_edit'] > 0 AND !$this->memberData['sostestemunhos_editar'] )
			{
				if ( $t['t_date'] > ( time() - ( intval($this->memberData['sostestemunhos_max_time_edit']) * 60 ) ) )
				{
					$_canEdit = 1;
				}
			}
		}

		if ( !$t['t_approved'] && !$this->memberData['sostestemunhos_aprovar'] && $t['t_member_id'] != $this->memberData['member_id'] )
		{
			$this->registry->getClass('output')->showError( 'not_approved' );
		}
		
		if ( $t['t_member_id'] )
		{
			$author = IPSMember::buildDisplayData( $t['t_member_id'], array( 'customFields' => 1, 'warn' => 1, 'checkFormat' => 1 ) );
		}
		else
		{
			$t['author_name']	= $this->settings['guest_name_pre'] . $this->settings['guest_name_suf'];
			
			$author = IPSMember::setUpGuest( $t['author_name'] );
			$author['members_display_name']		= $t['author_name'];
			$author['_members_display_name']	= $t['author_name'];
			$author['custom_fields']			= "";
			$author['warn_img']					= "";
			$author['name_css']					= 'unreg';
		}
		
		IPSText::getTextClass('bbcode')->parse_html      = $this->settings['testemunhos_html'];
		IPSText::getTextClass('bbcode')->parse_nl2br     = 1;
		IPSText::getTextClass('bbcode')->parse_bbcode    = $this->settings['testemunhos_bbcode'];
		IPSText::getTextClass('bbcode')->parse_smilies   = $this->settings['testemunhos_emoticons'];
		IPSText::getTextClass('bbcode')->parsing_section = 'testemunhos_submit';

		$t['t_content'] = IPSText::getTextClass('bbcode')->preDisplayParse(  $t['t_content'] );

		if( $author['signature'] )
		{
			$t['signature'] = $this->registry->output->getTemplate('global')->signature_separator( IPSText::getTextClass( 'bbcode' )->preDisplayParse( IPSText::getTextClass( 'bbcode' )->preDbParse( $author['signature'] ) ) );
		}

		/* Reputation */
		$t['has_given_rep'] = intval( $t['has_given_rep'] );
		$t['rep_points']    = intval( $t['rep_points'] );

		/* Comentários */
		$this->registry->class_localization->loadLanguageFile( array( 'public_editors' ), 'core' );
			
		/* Starting at...*/
		$com_st = $this->request['st'] ? intval( $this->request['st'] ) : 0;
		
		/* Query */
		$this->DB->build( array( 'select'   => 'c.*',
								 'from'     => array( 'testemunhos_comments' => 'c' ),
								 'where'    => 'c.tid='.$t['t_id'],
								 'add_join' => array( 0 => array( 'select' => 'm.member_id, m.members_display_name, m.member_group_id, m.members_seo_name',
																  'from'   => array( 'members' => 'm' ),
																  'where'  => 'm.member_id=c.member_id',
																  'type'   => 'left' ),
													  1 => array( 'select' => 'pp.pp_thumb_photo, pp.pp_thumb_width, pp.pp_thumb_height, signature',
																  'from'   => array( 'profile_portal' => 'pp' ),
																  'where'  => "m.member_id=pp.pp_member_id",
																  'type'   => 'left' ) ),
								 'order'    => 'date',
								 'limit'    => array( $com_st, $this->settings['testemunhos_comentariospp'] ),
		) );
		
		$outer = $this->DB->execute();
			
		while ( $r = $this->DB->fetch( $outer ) )
		{
			/* Post count */
			$post_count++;
				
			/* Parsing stuff */
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= $this->settings['testemunhos_emoticons'];
			IPSText::getTextClass( 'bbcode' )->parse_html				= $this->settings['testemunhos_html'];
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $this->settings['testemunhos_bbcode'];
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'testemunhos_comment';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];
				
			$r['comment'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['comment'] );
			$r['post_count'] = $com_st + $post_count;
				
			/* Multi-quotes */
			$r['_mq_selected'] = false;
				
			if ( $this->qpids )
			{
				if ( strstr( ','.$this->qpids.',', ','.$r['cid'].',' ) )
				{
					$r['_mq_selected'] = true;
				}
			}

			if ( $r['signature'] )
			{
				$r['sig'] = $this->registry->output->getTemplate('global')->signature_separator( IPSText::getTextClass( 'bbcode' )->preDisplayParse( IPSText::getTextClass( 'bbcode' )->preDbParse( $r['signature'] ) ) );
			}
				
			/* Is this guy a douchebag? */
			foreach ( $this->member->ignored_users as $_i )
			{
				if ( $_i['ignore_topics'] && $_i['ignore_ignore_id'] == $r['mid'] )
				{
					if ( ! strstr( $this->settings['cannot_ignore_groups'], ','.$r['member_group_id'].',' ) )
					{
						$r['_ignored']	= true;
						break;
					}
				}
			}
				
			$comments[] = array_merge( $r, IPSMember::buildDisplayData( $r['member_id'], array( 'customFields' => 1, 'warn' => 0, 'checkFormat' => 1 ) ) );
		}

		$com_pages = $this->registry->output->generatePagination( array( 'totalItems'        => $t['t_comments'],
																		 'itemsPerPage'      => $this->settings['testemunhos_comentariospp'],
																		 'currentStartValue' => $com_st,
																		 'baseUrl'           => "app=testimonials&amp;showtestimonial=".$t['t_id'],
		) );
		
		/* Incrementar visualizações do testemunho */
		if ( strpos( my_getenv( 'HTTP_REFERER' ), "testemunho=".$t['t_id'] ) === false )
		{
			$this->DB->update( 'testemunhos', 't_views=t_views+1', 't_id='.$t['t_id'], true, true );
		}

		/* Atualizar cache com visualizações do testemunho */
		if ( strpos( my_getenv( 'HTTP_REFERER' ), "testemunho=".$t['t_id'] ) === false )
		{
			$stats = $this->cache->getCache('testemunhos');
			$stats['views']++;
			$this->cache->setCache( 'testemunhos', $stats, array( 'array' => 1, 'deletefirst' => 1 ) );
		}

		/* Mark the item as read */
		$this->registry->classItemMarking->markRead( array( 'testemunhoCat' => $t['t_cat'], 'itemID' => $t['t_id'] ), 'testimonials' );

		/* Add a canonical tag */
		$this->registry->getClass('output')->addCanonicalTag( "app=testimonials&amp;showtestimonial=".$t['t_id'], $t['t_title_seo'], 'testemunhos' );
		
		/* Add meta tags */
		$this->registry->output->addMetaTag( 'keywords', $t['t_title'], TRUE );

		if ($this->settings['testemunhos_announcment'] == 2 OR $this->settings['testemunhos_announcment'] == 4 )
		{
			$anuncio = IPSText::getTextClass('bbcode')->preDisplayParse( IPSText::getTextClass('bbcode')->preDbParse($this->settings['testemunhos_anuncio'] ) );
		}

			$rating = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'testemunhos_ratings', 'where' => "rating_tid={$t['t_id']} and rating_member_id=".$this->memberData['member_id'] ) );
			
		$t['rate_show']    = 1;
		$t['rate_int']     = $t['t_rating_hits'] ? round( $t['t_rating'] ) : 0;
		$t['rate_id']	   = 0;
		$t['rating_value'] = $rating['rating_value'] ? $rating['rating_value'] : -1;
		$t['rate_img']     = 1;		
		$t['allow_rate']   = 1;

 		$cat = $this->DB->buildAndFetch( array( 'select' => '*',
												'from'   => 'testemunhos_cats',
												'where'  => 'c_id='.$t['t_cat']
												
												//'limit'  => array( 0,1 )
		) );       
        		
		$this->library->pageTitle  .= $this->testemunho['t_title'];
		$this->library->pageOutput .= $this->registry->output->getTemplate('testimonials')->testemunhoView( $t, $author, $comments, $com_pages, $_canEdit, $cat );
		
		$this->library->sendOutput();
	}

	private function viewNextTestemunho( $t )
	{
		/* Find the next newest */
		$row = $this->DB->buildAndFetch( array( 'select' => 't_id, t_title_seo',
												'from'   => 'testemunhos',
												'where'  => "t_approved=1 AND t_last_comment > ".$t['t_last_comment'],
												'order'  => 't_last_comment'
												//'limit'  => array( 0,1 )
		) );
		
		if ( $row )
		{
			/* Redirect */
			$this->registry->output->silentRedirect( $this->registry->getClass('output')->buildSEOUrl( 'showtestimonial='.$row['t_id'], 'publicWithApp', $row['t_title_seo'], 'testemunho' ) );
		}
		else
		{
			/* None newer */
			$this->registry->getClass('output')->showError( 'no_newer' );
		}
	}
	
	private function viewPreviousTestemunho( $t )
	{
		/* Find the next newest */
		$row = $this->DB->buildAndFetch( array( 'select' => 't_id, t_title_seo',
												'from'   => 'testemunhos',
												'where'  => "t_approved=1 AND t_last_comment < ".$t['t_last_comment'],
												'order'  => 't_last_comment'
												//'limit'  => array( 0,1 )
		) );
		
		if ( $row )
		{
			/* Redirect */
			$this->registry->output->silentRedirect( $this->registry->getClass('output')->buildSEOUrl( 'showtestimonial='.$row['t_id'], 'publicWithApp', $row['t_title_seo'], 'testemunho' ) );
		}
		else
		{
			/* None newer */
			$this->registry->getClass('output')->showError( 'no_older' );
		}
	}
	
	private function _doViewCheck()
	{
		if ($this->request['view'] == 'getlastpost')
		{
			//-----------------------------------------
			// Last post
			//-----------------------------------------
			
			$this->_returnLastPost();
		}
		else if ($this->request['view'] == 'getnewpost')
		{
			//-----------------------------------------
			// Newest post
			//-----------------------------------------
			
			$st	       = 0;
			$pid	   = "";
			$markers   = $this->memberData['members_markers'];
			
			$last_time = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'testemunhoCat' => $this->testemunho['t_cat'], 'itemID' => $this->testemunho['t_id'] ), 'testimonials' );

			$this->DB->build( array( 
											'select' => 'MIN(cid) as comment_id',
											'from'   => 'testemunhos_comments',
											'where'  => "tid={$this->testemunho['t_id']} AND date > " . intval( $last_time ),
											'limit'  => array( 0,1 )
								)	);						
			$this->DB->execute();
			
			$comment = $this->DB->fetch();
			
			if ( $comment['comment_id'] )
			{
				$pid = "&amp;#comment".$comment['comment_id'];
				
				$this->DB->build( array( 'select' => 'COUNT(*) as comments', 'from' => 'testemunhos_comments', 'where' => "tid={$this->testemunho['t_id']} AND cid <= {$comment['comment_id']}" ) );										
				$this->DB->execute();
			
				$cComments = $this->DB->fetch();
				
				if ( (($cComments['comments']) % $this->settings['display_max_posts']) == 0 )
				{
					$pages = ( $cComments['comments'] ) / $this->settings['display_max_posts'];
				}
				else
				{
					$number = ( ( $cComments['comments'] ) / $this->settings['display_max_posts'] );
					$pages = ceil( $number );
				}
				
				$st = ($pages - 1) * $this->settings['display_max_posts'];
				
				$st = ( ceil( ( $this->testemunho['t_comments'] / $this->settings['display_max_posts'] ) ) - $pages ) * $this->settings['display_max_posts'];	
				
				$this->registry->output->silentRedirect( $this->settings['base_url_with_app']."showtestimonial=".$this->testemunho['t_id']."&st={$st}".$pid, $this->testemunho['t_title_seo'] );
			}
			else
			{
				$this->_returnLastPost();
			}
		}
		else if ( $this->request['view'] == 'findcomment' )
		{
			//-----------------------------------------
			// Find a post
			//-----------------------------------------
			
			$cid = intval($this->request['comment_id']);
			
			if ( $cid > 0 )
			{
				$sort_value = $cid;
				
				$date = $this->DB->buildAndFetch( array( 'select' => 'date', 'from' => 'testemunhos_comments', 'where' => 'cid=' . $cid ) );

				$sort_value = $date['date'];
				

				$this->DB->build( array( 'select' => 'COUNT(*) as comments', 'from' => 'testemunhos_comments', 'where' => "tid={$this->testemunho['t_id']} AND posted <=" . intval( $sort_value ) ) );										
				$this->DB->execute();
				
				$cComments = $this->DB->fetch();
				
				if ( (($cComments['comments']) % $this->settings['display_max_posts']) == 0 )
				{
					$pages = ($cComments['comments']) / $this->settings['display_max_posts'];
				}
				else
				{
					$number = ( ($cComments['comments']) / $this->settings['display_max_posts'] );
					$pages = ceil($number);
				}
				
				$st = ($pages - 1) * $this->settings['display_max_posts'];
				
				$st = ( ceil( ( $this->testemunho['t_comments'] / $this->settings['display_max_posts'] ) ) - $pages ) * $this->settings['display_max_posts'];
				
				
				$this->registry->output->silentRedirect( $this->settings['base_url_with_app']."showtestimonial=".$this->testemunho['t_id']."&amp;st={$st}#comment".$cid );
			}
			else
			{
				$this->_returnLastPost();
			}
		}
	}
	
	private function _returnLastPost()
	{
		if ( $this->testemunho['t_comments'] )
		{
			if ( (($this->testemunho['t_comments'] + 1) % $this->settings['display_max_posts']) == 0 )
			{
				$pages = ($this->testemunho['t_comments'] + 1) / $this->settings['display_max_posts'];
			}
			else
			{
				$number = ( ($this->testemunho['t_comments'] + 1) / $this->settings['display_max_posts'] );
				$pages = ceil( $number);
			}
			
			$st = (ceil(($this->testemunho['t_comments']/$this->settings['display_max_posts'])) - $pages) * $this->settings['display_max_posts'];
		}
		
		$this->DB->build( array( 
										'select' => 'cid',
										'from'   => 'testemunhos_comments',
										'where'  => "tid=".$this->testemunho['t_id'],
										'order'  => 'date DESC',
										'limit'  => array( 0,1 )
							)	  );
							 
		$this->DB->execute();
		
		$post = $this->DB->fetch();
		
		$this->registry->output->silentRedirect($this->settings['base_url_with_app']."showtestimonial=".$this->testemunho['t_id']."&amp;st={$st}&"."#comment".$post['cid'], $this->testemunho['t_title_seo'] );
	}


    private function cats()
    {
        $this->library->pageOutput .= $this->registry->output->getTemplate('testimonials')->testimonialCats();        
    }
    

	public function parseTestemunhoData( $testemunho )
	{
		$lastUpdate = ( isset( $testemunho['t_last_comment'] ) AND $testemunho['t_last_comment'] > 0 ) ? $testemunho['t_last_comment'] : $testemunho['t_date'];
		
		$is_read = $this->registry->classItemMarking->isRead( array( 'testemunhoCat' => $testemunho['t_cat'], 'itemID' => $testemunho['t_id'], 'itemLastUpdate' => $lastUpdate ), 'testimonials' );
		
		$testemunho['members_display_name'] = $this->registry->getClass('testemunhosLibrary')->makeNameFormatted( $testemunho['members_display_name'], $testemunho['member_group_id'] );

		$testemunho['t_date'] 	  = $this->lang->getDate( $testemunho['t_date'], 'SHORT' );
		$testemunho['t_last_comment'] = $this->lang->getDate( $testemunho['t_last_comment'], 'SHORT' );
		$testemunho['t_comments'] = $this->lang->formatNumber( intval($testemunho['t_comments']) );
		$testemunho['t_views'] 	  = $this->lang->formatNumber( intval($testemunho['t_views']) );

		$showDot = false;

		if ( $this->memberData['member_id'] && $this->memberData['member_id'] == $testemunho['t_member_id'] )
		{
			$showDot = true;
		}
		
		/* Comentários */
		$this->DB->build( array( 'select'   => 'member_id',
								 'from'     => 'testemunhos_comments',
								 'where'    => 'tid='.$testemunho['t_id']
		) );
		
		$outer = $this->DB->execute();
		
		if ( $this->DB->getTotalRows( $outer ) )
		{
			while ( $r = $this->DB->fetch( $outer ) )
			{
				if ( $this->memberData['member_id'] && $this->memberData['member_id'] == $r['member_id'] )
				{
					$showDot = true;
				}
			}
		}
		
		if ( $this->memberData['member_id'] && $this->memberData['member_id'] == $testemunho['t_member_id'] )
		{
			$showDot = true;
		}
		
		$testemunho['folder_img'] = $this->registry->getClass('testemunhosLibrary')->fetchTestemunhoFolderIcon( $testemunho, $showDot, $is_read );

		if ( ! $is_read )
		{
			$testemunho['go_new_post']  = $this->registry->output->getTemplate( 'testimonials' )->goNewPost( $testemunho );
		}
		else
		{
			$testemunho['go_new_post']  = '';
		}
		
		/* Última Postagem */       
		$hasComm = $this->DB->buildAndFetch( array(  'select'    => 't_id, t_member_id, t_comments',
								 					 'from'      => 'testemunhos',
								 					 'where'     => 't_id='.$testemunho['t_id']) );
        
        if($hasComm['t_comments'])
        {
            $lastComm = $this->DB->buildAndFetch( array( 'select'   => 'member_id',
								 					     'from'     => 'testemunhos_comments',
								 					     'where'    => 'tid='.$testemunho['t_id'],
								 					     'order'	=> 'date DESC',
								 					     'limit'	=> array(0,1)) );  

            $user  = IPSMember::buildDisplayData( $lastComm['member_id'] );
            $v1    = $user['member_id'];
            $v2    = $user['pp_mini_photo'];
            $v3    = $user['members_seo_name'];

			$v4    = $user['members_display_name'];
			$v5    = $user['member_group_id']; 
            
            $testemunho['last_author']	    = $this->registry->getClass('testemunhosLibrary')->makeNameFormatted( $v4, $v5 );                                      
		    $testemunho['last_author_id']   = $v1;
            $testemunho['pp_mini_photo']    = $v2;
            $testemunho['members_seo_name'] = $v3;                                                                  
        } 
        else if(!$hasComm['t_comments'])
        {
            $starter  = IPSMember::buildDisplayData( $hasComm['t_member_id'] ); 
            $v6    = $starter['member_id'];
            $v7    = $starter['pp_mini_photo'];
            $v8    = $starter['members_seo_name'];

			$v9    = $starter['members_display_name'];
			$v10   = $starter['member_group_id']; 
            
            $testemunho['last_author']	    = $this->registry->getClass('testemunhosLibrary')->makeNameFormatted( $v9, $v10 );                                      
		    $testemunho['last_author_id']   = $v6;
            $testemunho['pp_mini_photo']    = $v7;
            $testemunho['members_seo_name'] = $v9;                
        }      
        
        
        return $testemunho;
        
	}
    
}	
?>