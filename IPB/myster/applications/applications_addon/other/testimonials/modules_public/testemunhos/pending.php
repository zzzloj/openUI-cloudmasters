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

class public_testimonials_testemunhos_pending extends ipsCommand
{
	private $output;
	private $library;

    public function doExecute( ipsRegistry $registry )
    {
		$this->output  = $this->registry->output;
		$this->library = $this->registry->getClass('testemunhosLibrary');
        		
		$this->library->checkPermissions();

		switch( $this->request['code'] )
		{
			case 'list':
			default:
				$this->listall();
				break;
		}

		$this->library->sendOutput();
	}

	private function listall()
	{
		$this->registry->output->addNavigation( $this->lang->words['testemunhos_title'], 'app=testimonials' );
		$this->registry->output->addNavigation( $this->lang->words['testemunhos_lista'], '' );
	
		$url   				= array();
		$query 				= array();
		$extra 				= array();	
		$start				= $this->request['changefilters'] == 1 ? 0 : intval($this->request['st']);
		$filters			= array();
		$sort_type			= isset($this->request['sort_type']) ? strtolower($this->request['sort_type']) : 't_last_comment';
		$sort_order			= $this->settings['testemunhos_order'];
		$max_results		= isset($this->request['max_results']) ? intval($this->request['max_results']) : $this->settings['testemunhos_testemunhospp'];
		
		$cookie_sort_type		= IPSCookie::get('testemunhos_sort_type');
		$cookie_sort_order		= IPSCookie::get('testemunhos_sort_order');
		$cookie_max_results		= IPSCookie::get('testemunhos_max_results');
		$sort_type				= !empty($cookie_sort_type)   ? $cookie_sort_type   : $sort_type;
		$sort_order				= !empty($cookie_sort_order)  ? $cookie_sort_order  : $sort_order;
		$max_results			= !empty($cookie_max_results) ? $cookie_max_results : $max_results;

        $cid = intval( $this->request['cid'] );

		$theviews = $this->DB->buildAndFetch( array(  'select'    => 'c_id, c_views',
								 					  'from'      => 'testemunhos_cats',
								 					  'where'     => 'c_id='.$cid) );
                                                     
        $this->DB->update( 'testemunhos_cats', 'c_views=c_views+1', 'c_id='.$theviews['c_id'], true, true );
		
		if ( $this->request['changefilters'] == 1 && !empty($this->request['remember']) )
		{
			if( $this->request['sort_type'] )
			{
				IPSCookie::set( "testemunhos_sort_type", $this->request['sort_type'] );
			}
			
			if( $this->request['sort_order'] )
			{
				IPSCookie::set( "testemunhos_sort_order", $this->request['sort_order'] );
			}
			
			if( $this->request['max_results'] )
			{
				IPSCookie::set( "testemunhos_max_results", $this->request['max_results'] );
			}
		}

		$sort_type_array = array( 'testemunho_date'   	=> $this->lang->words['testemunhos_filter_date'],
								  'testemunho_author'  	=> $this->lang->words['testemunhos_filter_author'],
								  'testemunho_title'  	=> $this->lang->words['testemunhos_filter_title'],
								  'testemunho_views'  	=> $this->lang->words['testemunhos_filter_views'],
								  'testemunho_comments' => $this->lang->words['testemunhos_filter_comments'],
								  'testemunho_rating'   => $this->lang->words['testemunhos_filter_rating']
		);
		
		switch ( $sort_type )
		{
			case 'testemunho_author':
				$sort = "m.members_display_name, t.t_id";
				break;
			case 'testemunho_title':
				$sort = "t.t_title";
				break;
			case 'testemunho_views':
				$sort = "t.t_views";
				break;
			case 'testemunho_rating':
				$sort = "t.t_rating";
				break;
			case 'testemunho_comments':
				$sort = "t.t_comments";
				break;
			case 'testemunho_date':
			default:
				$sort_type = 'testemunho_date';
				$sort = "t.t_last_comment";
				break;
		}
		
		$filters['sort_type'] = $this->library->makeDropdown( 'sort_type', $sort_type_array, $sort_type );
		
		if ( $sort_type != 'testemunho_date' )
		{
			$url[] = "sort_type=".$sort_type;
		}
		
		$sort_state_array = array(  1 => $this->lang->words['testemunhos_filter_state_open'],
									0 => $this->lang->words['testemunhos_filter_state_closed']
		);
		
		$sort_order_array = array( 'asc'  => $this->lang->words['testemunhos_filter_order_asc'],
								   'desc' => $this->lang->words['testemunhos_filter_order_desc']
		);
		
		if ( isset( $this->request['sort_order'] ) and $sort_order != $this->request['sort_order'] )
		{
			$sort_order = $this->request['sort_order'];
			$url[] = "sort_order={$this->request['sort_order']}";
		}

		$filters['sort_order'] = $this->library->makeDropdown( 'sort_order', $sort_order_array, $sort_order );

		$max_results_array = array( 0   => $this->lang->words['testemunhos_filter_default'],
									10  => '10',
									20  => '20',
									30  => '30',
									50  => '50',
									100 => '100'
		);

		if ( $max_results == $this->settings['testemunhos_testemunhospp'] || !array_key_exists( $max_results, $max_results_array ) )
		{
			$max_results = 0;
		}

		$filters['max_results'] = $this->library->makeDropdown( 'max_results', $max_results_array, $max_results );
		
		if ( $max_results )
		{
			$url[] = "max_results=".$max_results;
		}
		else
		{
			$max_results = $this->settings['testemunhos_testemunhospp'];
		}
	
		if ( !$this->memberData['sostestemunhos_aprovar'] )
		{
		 	$query[] = "t.t_approved = 1";
		}

		if ( count($query) )
		{
			$extra[] = ( count($query) == 1 ) ? implode("", $query) : "(".implode(" OR ", $query).")";
		}
		
		$final = implode(" AND ", $extra);
		$url   = count($url) ? "&amp;".implode( "&amp;", $url ) : "";

		$totalTestemunhos = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as found',
														 	 'from'   => 'testemunhos t',
	                                                         //'where'  => $final
                                                             'where'    => 't.t_approved=0',
		) );
												 
		$totalTestemunhos['found'] = intval($totalTestemunhos['found']);
		
		if ( $totalTestemunhos['found'] && $start >= $totalTestemunhos['found'] )
		{
			$this->output->silentRedirect( $this->settings['base_url']."app=testimonials&amp;".$url );
		}
		
		$testemunhosPages = $this->output->generatePagination( array( 'totalItems'        => $totalTestemunhos['found'],
																  	  'itemsPerPage'      => $max_results,
																  	  'currentStartValue' => $start,
																      'baseUrl'           => "app=testimonials".$url
		) );
		
		if ( $totalTestemunhos['found'] )
		{
			$this->DB->build( array( 'select'   => 't.*',
									 'from'     => array( 'testemunhos' => 't' ),
									 //'where'    => $final,
                                     'where'    => 't.t_approved=0',
									 'order'    => 't.t_pinned desc, t.t_approved, '.$sort.' '.$sort_order,
									 'add_join' => array( array( 'select' => 'm.member_group_id, m.members_display_name, m.members_seo_name',
									 							 'from'   => array( 'members' => 'm' ),
																 'where'  => 'm.member_id=t.t_member_id',
																 'type'   => 'left' ),
                                                          1 => array( 'select' => 'pp.*',
				                                                      'from'   => array( 'profile_portal' => 'pp' ),
				                                                      'where'  => 'pp.pp_member_id=t.t_member_id',
                                         	                'type'   => 'left' )),                                                                                                                                                                               
									 'limit'  	=> array( $start, $max_results )
			) );
			
			$this->DB->execute();
			
			while( $t = $this->DB->fetch() )
			{
				$testemunhos_array[ $t['t_id'] ] = $t;
				$testemunhos_ids[ $t['t_id'] ]   = $t['t_id'];                               
			}

			ksort( $testemunhos_ids );

			foreach( $testemunhos_array as $testemunho )
			{
				$testemunhos_data[ $testemunho['t_id'] ] = $this->parseTestemunhoData( $testemunho );
			}
		}
		
		if ($this->settings['testemunhos_announcment'] == 1 OR $this->settings['testemunhos_announcment'] == 4 )
		{
			$anuncio = IPSText::getTextClass('bbcode')->preDisplayParse( IPSText::getTextClass('bbcode')->preDbParse($this->settings['testemunhos_anuncio'] ) );
		}
        
        $cid = intval($this->request['cid']);
        $cat = $this->DB->buildAndFetch( array( 'select' => 'c_id',
                                                'from'   => 'testemunhos_cats',
                                                'where'  => 'c_id='.$cid ) );
                                                          
		$this->library->pageOutput .= $this->registry->getClass('output')->getTemplate('testimonials')->listPendingTestemunhosWrapper( $testemunhos_data, $filters, $testemunhosPages, $this->library->getActiveUsers(), $anuncio, $cat );
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