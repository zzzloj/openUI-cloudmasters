<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class admin_testimonials_tools_tools extends ipsCommand
{
	
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**
	 * Shortcut for url
	 *
	 * @access	private
	 * @var		string			URL shortcut
	 */
	private $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	private
	 * @var		string			JS URL shortcut
	 */
	private $form_code_js;
	
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{

		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_tools' );
		$this->form_code    = $this->html->form_code    = 'module=tools&amp;section=tools';
		$this->form_code_js = $this->html->form_code_js = 'module=tools&section=tools';

        //-------------------------------
        // Grab the settings controller, instantiate and set up shortcuts
        //-------------------------------
                
        $classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('core') . '/modules_admin/settings/settings.php', 'admin_core_settings_settings' );
        $settings       = new $classToLoad();
        $settings->makeRegistryShortcuts( $this->registry );
                			
		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		$this->lang->loadLanguageFile( array( 'admin_testemunhos' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch($this->request['do'])
		{						
			case 'convertstep2':
				$this->_convertTopics2();
				break;
			case 'doconvert':
				$this->_doConvertTopics();
				break;
			case 'logs':
				$this->_search();
				break;
			case 'dologs':
				$this->_dosearch();
				break;
			case 'members':
				$this->_ban();
				break;
			case 'unban':
				$this->_unban();
				break;

			case 'convert':
				 default:
				$this->_convertTopics();
				break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	public function _convertTopics()
	{
		require_once( IPSLib::getAppDir( 'forums' ) .'/sources/classes/forums/class_forums.php' );
		require_once( IPSLib::getAppDir( 'forums' ) .'/sources/classes/forums/admin_forum_functions.php' );
		$f = new admin_forum_functions( $this->registry );
		$f->forumsInit();
		$list = $f->adForumsForumList(1);
		
		$foruns = $this->registry->output->formDropdown( "forum", $list );



		
		$this->registry->output->html           .= $this->html->convertTopics( $foruns );
	}

	public function _convertTopics2()
	{
		if ( !$this->registry->getClass( 'class_forums' )->forum_by_id[ $this->request['forum'] ]['sub_can_post'] )
		{
			$this->registry->output->showError( 'novalid_forum' );
		}
				
		if ( $this->registry->getClass( 'class_forums' )->forum_by_id[ $this->request['forum'] ]['redirect_on'] )
		{
			$this->registry->output->showError( 'novalid_forum' );
		}
		
		$topics = $this->registry->getClass( 'class_forums' )->forum_by_id[ $this->request['forum'] ]['topics'];
		$posts  = $this->registry->getClass( 'class_forums' )->forum_by_id[ $this->request['forum'] ]['posts'];

		$start   = intval( $this->request['st'] ) >= 0 ? intval( $this->request['st'] ) : 0;
		$perpage = 30;
		
		/* Excluir tópicos já convertidos */
		$this->DB->build( array( 'select' => 'topic_id',
						 		 'from'   => 'testemunhos_topicsconverted',
						 		 'where'  => 'forum_id = '.$this->request['forum']
		) );
		
		$exc = $this->DB->execute();

        if ( $this->DB->getTotalRows( $exc ) )
        {
			while ( $e = $this->DB->fetch( $exc ) )
            {
                $excluidos[] = $e['topic_id'];
            }
		}
		
		if ( count($excluidos) > 0 )
		{
			$where = ' and tid NOT IN ('.implode(",", $excluidos ).')';
		}
		else
		{
			$where = '';
		}

		$text = sprintf( $this->lang->words['forum_stats'], $topics, $posts, count($excluidos) );
		
		$this->DB->build( array( 'select' => 'tid, title, posts, starter_name, starter_id,  start_date',
								 'from'  => 'topics',
								 'where' => 'forum_id='.$this->request['forum'].$where,
								 'order' => 'tid',
								 'limit' => array( $start, $perpage )
		) );
		
		$query = $this->DB->execute();

        if ( $this->DB->getTotalRows( $query ) )
        {
			while ( $r = $this->DB->fetch( $query ) )
            {
                $topicos[] = $r;
            }
		}

		$pagination = $this->registry->output->generatePagination( array( 
																'totalItems'		=> count($topicos),
																'itemsPerPage'		=> $perpage,
																'currentStartValue'	=> $start,
																'baseUrl'			=> $this->settings['base_url']."module=tools&section=tools&do=convertstep2&forum=".$this->request['forum'],
		) );

		if( count( $topicos ) )
		{
			foreach ( $topicos as $r )
			{
				$listtopics .= "<tr>
						<td width='40%' style='padding: 5px;'><a target='_blank' href='{$this->settings['board_url']}/index.php?showtopic={$r['tid']}'>{$r['title']}</a></td>
						<td width='9%' align='center'>".$this->registry->getClass('class_localization')->formatNumber( $r['posts'] )."</td>
						<td width='25%' style='padding: 5px;'>{$r['starter_name']}</td>
						<td width='25%' style='padding: 5px;'>".$this->lang->getDate( $r['start_date'], 'SHORT' )."</td>
						<td width='1%' style='padding: 5px;'><input type='checkbox' name='id[]' value='{$r['tid']}' class='checkAll' /></td>
					</tr>";
			}
		}
		else
		{
			$listtopics = "<tr><td colspan='4'><em>".$this->lang->words['notopics']."</em></td></tr>";
		}


        /* Categories */
		$this->DB->build( array( 'select' => '*',
								 'from'  => 'testemunhos_cats',
								 'order' => 'c_id desc',

		) );
		
		$query = $this->DB->execute();

		   $categories .= '<select name="newcat">';
		   $categories .= '<option value="">--Choose A Category--</option>';
       
			while ( $r = $this->DB->fetch( $query ) )
            {
             $categories .= "<option value='{$r['c_id']}'>{$r['c_name']}</option>";
            }
           $categories .= '</select>';
           
		
		$this->registry->output->html .= $this->html->convertTopicsConfirm( $text, $pagination, $listtopics, $categories );
	}

    public function _doConvertTopics()
    {
		if ( !isset($_POST["id"]) )
		{
			$this->registry->output->showError( $this->lang->words['no_selectedtopic'], 11737 );
		}
		
		$i = 0;
		
		foreach( $_POST["id"] as $ID )
		{
			$source = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid ='.$ID ) );
			
			//dados do tópico
			$insert = array(
						't_date'         => $source['start_date'],
						't_views'        => $source['views'],
						't_comments'     => $source['posts'],
						't_last_comment' => $source['last_post'],
						't_rating'       => $source['topic_rating_total'],
						't_approved'     => $source['approved'],
						't_pinned'       => $source['pinned'],
						't_open'	     => $source['state'] == 'open' ? 1 : 0,
						't_member_id'    => $source['starter_id'],
						't_title'	     => $source['title'],
						't_title_seo'    => $source['title_seo'],
						't_cat'		     => $this->request['newcat'],
			);

			$this->DB->insert( 'testemunhos', $insert );			
			$t = $this->DB->getInsertId();
			
			//primeiro post do tópico
			$sourcep = $this->DB->buildAndFetch( array( 'select' => 'post, ip_address, post_htmlstate', 'from' => 'posts', 'where' => 'new_topic = 1 AND topic_id ='.$ID ) );
			
			$this->DB->update( 'testemunhos', array( 't_content' => $sourcep['post'], 't_ip_address' => $sourcep['ip_address'], 't_htmlstate'  => $sourcep['post_htmlstate'] ), "t_id=".$t );
			
			//demais posts do tópico
			$sourcer = $this->DB->build( array( 'select' => 'post, author_id, post_date, ip_address, post_htmlstate', 'from' => 'posts', 'where' => 'new_topic = 0 AND topic_id ='.$ID, 'order' => 'post_date' ) );
			
			$query = $this->DB->execute();
			
			if ( $this->DB->getTotalRows( $query ) )
        	{
				while ( $r = $this->DB->fetch( $query ) )
            	{
					$insertposts = array(
									'date'         	=> $r['post_date'],
									'tid'        	=> $t,
									'member_id'     => $r['author_id'],
									'comment'		=> $r['post'],
					);
					
					$this->DB->insert( 'testemunhos_comments', $insertposts );
				}
			}
			
			//Log de Tópicos Convertidos
			$done = array(
							'datetime'  => time(),
							'topic_id'  => $ID,
							'member_id'	=> $this->memberData['member_id'],
							'forum_id'	=> $this->request['forum'],
			);

			$this->DB->insert( 'testemunhos_topicsconverted', $done );
			$i++;
		}
		
		$nr = sprintf( $this->lang->words['done'], $i );
		
		$this->output  = $this->registry->output;
		$this->library = $this->registry->getClass('testemunhosLibrary');
		$this->library->rebuildTestemunhosCache();
      
        $this->registry->output->redirect( $this->settings['base_url'].$this->html->form_code . "&amp;do=convert", $nr );
    }

	public function _search()
	{
		if ( $this->request['string'] )
		{
			switch ( $this->request['tipo'] )
			{
				case 2:
					$query2 = "member_id = ".intval( $this->request['string'] );
					break;
				case 3:
					$query2 = "t_id = ".intval( $this->request['string'] );
					break;
				case 4:
					$query2 = "t_title like '%{$this->request['string']}%'";
					break;
				case 5:
					$query2 = "action like '%{$this->request['string']}%'";
					break;
	
				case 1:
				default:
					$query2 = "name like '%{$this->request['string']}%'";
					break;
			}
		}

		$start   = intval( $this->request['st'] ) >= 0 ? intval( $this->request['st'] ) : 0;
		$perpage = 50;
		
		$count = $this->DB->buildAndFetch( array( 
								'select'   => "count(*) as cnt",
								'from'     => 'testemunhos_modlogs',
								'where'    => $query2
		) );

		$pagination = $this->registry->output->generatePagination( array( 
																'totalItems'		=> $count['cnt'],
																'itemsPerPage'		=> $perpage,
																'currentStartValue'	=> $start,
																'baseUrl'			=> $this->settings['base_url']."module=tools&section=tools&do=search",
		) );
		 	
		$this->DB->build( array( 'select' => '*',
								 'from'	  => 'testemunhos_modlogs',
								 'where'  => $query2,
								 'order'  => 'datetime desc',
								 'limit'   => array( $start, $perpage )
		) );

		$this->DB->execute();

		if ( $this->DB->getTotalRows() > 0 )
		{
			while ( $row = $this->DB->fetch() )
			{
				$row['t_id'] = $row['t_id'] ? $row['t_id'] : '-';
				
				$logs .= "<tr>
								<td width='5%' style='text-align:center; padding: 5px;'>{$row['t_id']}</td>
								<td width='30%' style='padding: 5px;'>{$row['t_title']}</td>
								<td width='7%' style='text-align:center; padding: 5px;'>{$row['member_id']}</td>
								<td width='15%' style='padding: 5px;'>{$row['name']}</td>
								<td width='10%' style='padding: 5px;'>".$row['ip_address']."</td>
								<td width='15%' style='padding: 5px;'>{$row['action']}</td>
								<td width='18%' align='center'>{$this->registry->getClass('class_localization')->getDate( $row['datetime'], 'SHORT' )}</td>
							</tr>";
			}
		}
		else
		{
			$logs = "<tr><td colspan='7'><em>{$this->lang->words['nologs']}</em></td></tr>";
		}
		
		$this->registry->output->html .= $this->html->toolSearchResults( $logs, $pagination );
	}

	public function _dosearch()
	{
		$this->registry->output->global_message = $this->lang->words['adminSuccessDelete'];	
								
		/* Redirect */
			
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code );		   
    }
       
	public function _ban()
	{
		if ( $this->request['string'] )
		{
			switch ( $this->request['tipo'] )
			{
				case 2:
					$query2 = " AND member_id = ".intval( $this->request['string'] );
					break;
	
				case 1:
				default:
					$query2 = " AND members_display_name like '%{$this->request['string']}%'";
					break;
			}
		}

		$start   = intval( $this->request['st'] ) >= 0 ? intval( $this->request['st'] ) : 0;
		$perpage = 50;
		
		$count = $this->DB->buildAndFetch( array( 
								'select'   => "count(*) as cnt",
								'from'     => 'members',
								'where'    => 'sostestemunhos_banned = 1'//.$query2
		) );

		$pagination = $this->registry->output->generatePagination( array( 
																'totalItems'		=> $count['cnt'],
																'itemsPerPage'		=> $perpage,
																'currentStartValue'	=> $start,
																'baseUrl'			=> $this->settings['base_url']."module=tools&section=tools&do=members",
		) );
		 	
		$this->DB->build( array( 'select' => 'member_id, members_display_name, member_group_id, posts, joined, sostestemunhos_banned_date',
								 'from'	  => 'members',
								 'where'  => 'sostestemunhos_banned = 1'.$query2,
								 'order'  => 'member_id',
								 'limit'   => array( $start, $perpage )
		) );

		$this->DB->execute();

		if ( $this->DB->getTotalRows() > 0 )
		{
			while ( $row = $this->DB->fetch() )
			{
				$users .= "<tr>
								<td width='5%' style='text-align:center; padding: 5px;'>{$this->registry->getClass('class_localization')->formatNumber($row['member_id'])}</td>
								<td width='29%' style='padding: 5px;'>{$row['members_display_name']}</td>
								<td width='20%' style='padding: 5px;'>{$this->caches['group_cache'][ $row['member_group_id'] ]['prefix']}{$this->caches['group_cache'][ $row['member_group_id'] ]['g_title']}{$this->caches['group_cache'][ $row['member_group_id'] ]['suffix']}</td>
								<td width='5%' style='text-align:center; padding: 5px;'>{$this->registry->getClass('class_localization')->formatNumber( $row['posts'] )}</td>
								<td width='20%' style='padding: 5px;'>".$this->registry->getClass('class_localization')->getDate( $row['joined'], 'SHORT' )."</td>
								<td width='20%' style='padding: 5px;'>".$this->registry->getClass('class_localization')->getDate( $row['sostestemunhos_banned_date'], 'SHORT' )."</td>
								<td><a href='{$this->settings['_base_url']}&app=testimonials&module=tools&section=tools&do=unban&id={$row['member_id']}'><img src='{$this->settings['img_url']}/exclamation.png' title='{$this->lang->words['unban']}' alt='{$this->lang->words['unban']}' /></a></td>
							</tr>";
			}
		}
		else
		{
			$users = "<tr><td colspan='6'><em>{$this->lang->words['nobanned']}</em></td></tr>";
		}
		
		$this->registry->output->html .= $this->html->toolSearchBanned( $users, $pagination );
	}
	
	public function _unban()
	{
		if ( !isset( $this->request['id'] ) OR $this->request['id'] == '' )
		{
			$this->registry->output->showError( 'novalid_userid' );
		}
		
		$this->DB->update( 'members', array( 'sostestemunhos_banned' => 0, 'sostestemunhos_banned_date' => 0 ), 'member_id='.$this->request['id'] );
		
		$user = IPSMember::load( $this->request['id'], 'all' );

		$this->output  = $this->registry->output;
		$this->library = $this->registry->getClass('testemunhosLibrary');
		$text = sprintf( $this->lang->words['testemunho_modlog_unban'], $user['members_display_name'] );
		
		$this->library->addModLog( $text, '---' );
		
		$this->registry->output->redirect( $this->settings['base_url'].$this->html->form_code . "&amp;do=members", $this->lang->words['user_unbanned'] );
	}

}