<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

if ( !defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_testimonials_testemunhos_comment extends ipsCommand
{
	private $output;
	private $library;

	public function doExecute( ipsRegistry $registry )
	{
		$this->output  = $this->registry->output;
		$this->library = $this->registry->getClass('testemunhosLibrary');
		
		/* Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_post' ) );
		$this->registry->class_localization->loadLanguageFile( array( 'public_post' ), 'forums' );
		
		if ( $this->request['preview'] )
		{
			$this->showForm( $this->request['type'] );
		}
		else
		{
			/* What to do? */
			switch ( $this->request['do'] )
			{
				case 'editComment':
					$this->showForm( 'edit' );
				break;
				
				case 'doComment':
					$this->processComment();
				break;
				
				case 'addForm':
				default:
					$this->showForm( 'add' );
				break;
			}
		}
		
		$this->library->sendOutput();
	}
	
	private function showForm( $type='add' )
	{
        $classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
        $editor = new $classToLoad();
        	   
		/* Flood Control ? */
		if ( !$this->memberData['g_avoid_flood'] && $this->settings['flood_control'] > 0 )
		{
			$ultimocomentario = $this->DB->buildAndFetch( array( 'select' => 'date', 'from' => 'testemunhos_comments', 'where' => 'member_id='.$this->memberData['member_id'], 'order' => 'date DESC', 'limit' => array( 0,1 ) ) );

			if ( time() - $ultimocomentario['date'] < $this->settings['flood_control'] )
			{
				$this->registry->getClass('output')->showError( array( 'flood_control', $this->settings['flood_control'] ) );
			}
		}

		/* Clean input */
		$tid  = intval( $this->request['testemunho'] );
		$com = intval( $this->request['comment'] );
		
		$a = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'testemunhos', 'where' => 't_id='.$tid ) );
		
		/* Pull the info for this comment */
		$c = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'testemunhos_comments', 'where' => 'cid='.$com ) );
		
		/* Error checking */
		if ( $type == 'add' && !$this->memberData['sostestemunhos_postar_comentarios'] OR !$a['t_approved'] )
		{
			$this->registry->getClass('output')->showError( 'no_submit_comments' );
		}

		/* Navegação */
		$this->registry->output->addNavigation( $this->lang->words['testemunhos_title'], 'app=testimonials' );
		$this->registry->output->addNavigation( $a['t_title'], 'app=testimonials&showtestimonial='.$a['t_id'] );
		
		/* Navigation & title */
		$navigation = ( $type == 'add' ) ? $this->lang->words['testemunho_post_addcomment_title'] : $this->lang->words['testemunho_post_editcomment_title'];
		$title      = ( $type == 'add' ) ? sprintf( $this->lang->words['commenting_on'], $a['t_title'] ) : sprintf( $this->lang->words['editing_comment_on'], $a['t_title'] );
		
		$this->registry->output->addNavigation( $navigation, '' );
		
		/* Have some previous content in the editor? */
		$raw_post = $this->_checkforPost( $c );

		IPSText::getTextClass('bbcode')->parse_html      = $this->settings['testemunhos_html'];
		IPSText::getTextClass('bbcode')->parse_nl2br     = 1;
		IPSText::getTextClass('bbcode')->parse_smilies   = $this->settings['testemunhos_emoticons'];
		IPSText::getTextClass('bbcode')->parse_bbcode    = $this->settings['testemunhos_bbcode'];
		IPSText::getTextClass('bbcode')->parsing_section = 'testemunhos_comment';
       		
		/* Set the editor */
        $editor = $editor->show( 'Post', array() ,$raw_post);
		
		/* Are we previewing? */
		if ( $this->request['preview'] )
		{
			$this->library->pageOutput .= $this->registry->getClass('output')->getTemplate('post')->preview( $this->library->generatePostPreview( $raw_post ) );
		}
		
		/* Build our output array */
		$data = array( 'testemunho_id'	=> $tid,
					   'comment_id'    	=> $com,
					   'type'   		=> $type,
					   'title'			=> $title,
					   'editor'			=> $editor,
					   'auth_key'		=> $this->member->form_hash,
					 );
		
		/* Page title */
		$this->registry->output->setTitle( $title );
		
		$this->library->pageOutput .= $this->registry->output->getTemplate('testimonials')->testemunhoCommentForm( $data );
		
		$this->library->sendOutput();
	}
	
	public function processComment()
	{
        $classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );

        $editor = new $classToLoad();

		/* Flood Control ? */
		if ( !$this->memberData['g_avoid_flood'] && $this->settings['flood_control'] > 0 )
		{
			$ultimocomentario = $this->DB->buildAndFetch( array( 'select' => 'date', 'from' => 'testemunhos_comments', 'where' => 'member_id='.$this->memberData['member_id'], 'order' => 'date DESC', 'limit' => array( 0,1 ) ) );

			if ( time() - $ultimocomentario['date'] < $this->settings['flood_control'] )
			{
				$this->registry->getClass('output')->showError( array( 'flood_control', $this->settings['flood_control'] ) );
			}
		}

		/* Clean input */
		$testemunho = intval( $this->request['testemunho'] );
		$com      = intval( $this->request['comment_id'] );
		$auth_key = trim( $this->request['auth_key'] );
		$type     = trim( $this->request['type'] );
		
		if ( !$testemunho )
		{
			$this->registry->getClass('output')->showError( 'testemunho_no_tid' );
		}
		
		/* Parse the editor content */
		//$post = IPSText::getTextClass('editor')->processRawPost( 'Post' );
		$post = $editor->process( $_POST['Post'] );
		
		IPSText::getTextClass('bbcode')->parse_html      = $this->settings['testemunhos_html'];
		IPSText::getTextClass('bbcode')->parse_nl2br     = 1;
		IPSText::getTextClass('bbcode')->parse_smilies   = $this->settings['testemunhos_emoticons'];
		IPSText::getTextClass('bbcode')->parse_bbcode    = $this->settings['testemunhos_bbcode'];
		IPSText::getTextClass('bbcode')->parsing_section = 'testemunhos_comment';
		
        $message = IPSText::getTextClass('bbcode')->preDbParse( $post );
		
		$a = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'testemunhos', 'where' => 't_id='.$testemunho ) );

		if ( $a['t_open'] == 0 )
		{
			$this->registry->getClass('output')->showError( 'testemunho_closed' );
		}

		/* Error checking */
		if ( ! is_array( $a ) OR ! count( $a ) )
		{
			$this->registry->getClass('output')->showError( 'testemunho_no_tid' );
		}
		
		if ( ! $a['t_approved'] )
		{
			$this->registry->getClass('output')->showError( 'not_approved', '10TUT053' );
		}
		
		if ( $auth_key != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'bad_md5_hash' );
		}
		
		if ( IPSText::mbstrlen( trim( $_POST['Post'] ) ) < 3 )
		{
			$this->registry->getClass('output')->showError( 'error_comment' );
		}
		
		if ( IPSText::mbstrlen( $post ) > ( $this->settings['testemunhos_maxsizecontent'] * 1024 ) )
		{
			$this->registry->getClass('output')->showError( $this->lang->words['post_too_long'] );
		}
		
		if ( $type == 'edit' )
		{
			$the_com = $this->DB->buildAndFetch( array( 'select' => '*',
														'from'   => 'testemunhos_comments',
														'where'  => 'cid='.$com,
			) );
			
			if ( !$the_com )
			{
				$this->registry->getClass('output')->showError( 'comment_no_exist', '10TUT057' );
			}
			
			if ( $the_com['member_id'] != $this->memberData['member_id'] )
			{
				if ( ! $this->memberData['g_tutorials_comedit'] OR ! $this->memberData['g_tutorials_mod_comedit'] )
				{
					$this->registry->getClass('output')->showError( 'no_edit_comments', '10TUT058' );
				}
			}

		    IPSText::getTextClass('bbcode')->parse_html      = 0;
		    IPSText::getTextClass('bbcode')->parse_nl2br     = 1;
		    IPSText::getTextClass('bbcode')->parse_smilies   = $this->settings['testemunhos_emoticons'];
		    IPSText::getTextClass('bbcode')->parse_bbcode    = $this->settings['testemunhos_bbcode'];
		    IPSText::getTextClass('bbcode')->parsing_section = 'testemunhos_comment';
		
		    $editmessage = IPSText::getTextClass('bbcode')->preDbParse( $post );
					
			$append = $this->memberData['sostestemunhos_remove_edit_time'] ? $append = $this->request['showEditLine'] == 1 ? 1 : 0 : 1;

			$this->DB->update( 'testemunhos_comments', array( 'comment' => $editmessage, 'append_edit' => $append, 'append_edit_time' => time(), append_edit_author => $this->memberData['members_display_name'] ), "cid=".$com );
			
			$this->registry->output->silentRedirect( $this->registry->getClass('output')->buildSEOUrl( 'section=findpost&amp;id='.$com, 'publicWithApp' ) );
		}
		else
		{
			/* Error checking */
			if ( !$this->memberData['sostestemunhos_postar_comentarios'] )
			{
				$this->registry->getClass('output')->showError( 'no_submit_comments' );
			}
			
			/* Insert the comment */
			$insert = array( 'member_id' => $this->memberData['member_id'],
							 'tid'       => $a['t_id'],
							 'date'      => time(),
							 'comment'   => $message,
			);
			
			$this->DB->insert( 'testemunhos_comments', $insert );
			$comment_id = $this->DB->getInsertId();
			
			/* Enviar e-mails para quem assinou */
			$this->library->enviarEmails( $a );

			/* Quer receber notificações por e-mail ? */
			if ( $this->request['enabletrack'] )
			{
				$this->DB->insert( 'testemunhos_tracker ',
											array( 'member_id'  => $this->memberData['member_id'],
												   't_id' 	    => $a['t_id'],
												   'start_date' => time(),
							 					   'type'  	    => 'email',
				) );
			}
			
			$this->DB->update( 'testemunhos', 't_comments=t_comments+1, t_last_comment=' . time(), 't_id='.$a['t_id'], false, true );

			/* Enviar MP ? */
			if ( $this->request['enabletrack_pm'] == 1 )
			{
				$this->library->enviarMP ( $this->settings['testemunhos_mpaprovacao_titulo'], $this->settings['testemunhos_mpaprovacao'], $this->testemunho['t_member_id'], "[url='".$this->settings['board_url']."/index.php?app=testimonials&showtestimonial={$this->testemunho['t_id']}']{$this->testemunho['t_title']}[/url]" );
			}

			$stats = $this->cache->getCache('testemunhos');
			$stats['comments']++;
			$this->cache->setCache( 'testemunhos', $stats, array( 'array' => 1, 'deletefirst' => 1 ) );
			
			/* Redirect */
			$this->registry->output->silentRedirect( $this->registry->getClass('output')->buildSEOUrl( 'module=testemunhos&amp;section=findpost&amp;id='.$comment_id, 'publicWithApp' ) );
		}
	}
	
	private function _checkforPost( $comment=array() )
	{
        //$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
        //$editor = new $classToLoad();	   
		$post = "";

		if ( $comment['comment'] )
		{
		    IPSText::getTextClass('bbcode')->parse_html      = 1;
		    IPSText::getTextClass('bbcode')->parse_nl2br     = 1;
		    IPSText::getTextClass('bbcode')->parse_smilies   = $this->settings['testemunhos_emoticons'];
		    IPSText::getTextClass('bbcode')->parse_bbcode    = $this->settings['testemunhos_bbcode'];
		    IPSText::getTextClass('bbcode')->parsing_section = 'testemunhos_comment';
		    
			return IPSText::getTextClass('bbcode')->preEditParse( $comment['comment'] );
		}
		
		if ( !$this->request['qpid'] )
		{
			$this->request['qpid'] =  IPSCookie::get( 'testemunhos_pids' );
			
			if ( $this->request['qpid'] == "," )
			{
				$this->request[ 'qpid'] =  "" ;
			}
		}
		
		$this->request['qpid'] = preg_replace( "/[^,\d]/", "", trim( $this->request['qpid'] ) );
		
		if ( $this->request['qpid'] )
		{
			IPSCookie::set( 'testemunhos_pids', ',', 0 );
			
			$quoted_pids = preg_split( '/,/', $this->request['qpid'], -1, PREG_SPLIT_NO_EMPTY );
			
			/* Get the posts from the DB and ensure we have suitable read permissions to quote them */
			if ( count( $quoted_pids ) )
			{
				$quoted_pids = IPSLib::cleanIntArray( $quoted_pids );
								
				$this->DB->build( array( 'select'   => 'c.*',
										 'from'     => array( 'testemunhos_comments' => 'c' ),
										 'where'    => 'c.cid IN(' . implode( ',', $quoted_pids ) . ')',
										 'add_join' => array( 0 => array( 'select' => 'm.members_display_name',
																		  'from'   => array( 'members' => 'm' ),
																		  'where'  => 'm.member_id=c.member_id',
																		  'type'   => 'left' ) ),
								)	   );
				$q = $this->DB->execute();
				
				while ( $tp = $this->DB->fetch( $q ) )
				{	
				
					IPSText::getTextClass('bbcode')->parse_html      = 1;
		            IPSText::getTextClass('bbcode')->parse_nl2br     = 1;
		            IPSText::getTextClass('bbcode')->parse_smilies   = $this->settings['testemunhos_emoticons'];
		            IPSText::getTextClass('bbcode')->parse_bbcode    = $this->settings['testemunhos_bbcode'];
		            IPSText::getTextClass('bbcode')->parsing_section = 'testemunhos_comment';
		    				
					$tmp_post = trim( IPSText::getTextClass('bbcode')->preEditParse( $tp['comment'] ) );
					
					if ( $this->settings['strip_quotes'] )
					{
						$tmp_post = IPSText::getTextClass('bbcode')->stripQuotes( $tmp_post );
					}
					
					if ( $tmp_post )
					{
						if ( IPSText::getTextClass('editor')->method == 'rte' )
						{
							$post .= "[quote name='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $tp['members_display_name'] ) . "' date='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $this->registry->getClass('class_localization')->getDate( $tp['date'], 'LONG', 1 )) . "']<br />{$tmp_post}<br />[/quote]<br /><br /><br />";
						}
						else
						{
							$post .= "[quote name='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $tp['members_display_name'] ) . "' date='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $this->registry->getClass('class_localization')->getDate( $tp['date'], 'LONG', 1 )) . "']\n{$tmp_post}\n[/quote]\n\n\n";
						}
					}
				}
				
				$post = trim( $post ) . "\n";
			}
		}
		
		if ( $this->request['Post'] )
		{
			/* Raw post from preview? */
			$post .= isset( $_POST['Post'] ) ? IPSText::htmlspecialchars( $_POST['Post'] ) : "";
			
			if ( isset( $raw_post ) )
			{
				$post = IPSText::raw2form( $post );
			}
		}
		
		return $post;
	}
}