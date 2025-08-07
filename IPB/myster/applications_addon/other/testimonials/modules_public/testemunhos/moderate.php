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

class public_testimonials_testemunhos_moderate extends ipsCommand
{
	private $output;
	private $library;
	public $testemunho;
	public $statsCache;

	public function doExecute( ipsRegistry $registry )
	{
		/* Setup output & library shortcuts */
		$this->output  = $this->registry->output;
		$this->library = $this->registry->getClass('testemunhosLibrary');
		
		/* Check mod perms */
		$this->library->checkModerator();
		
		/* Do some initialization */
		$this->_doSomeInit();
		
		//-----------------------------------------
		// What are we trying to do? =|
		//-----------------------------------------
		
		switch ( $this->request['do'] )
		{
			case 'close':
				$this->_fecharTestemunho();
				break;
			case 'open':
				$this->_abrirTestemunho();
				break;				
			case 'closet':
				$this->_fecharTestemunhoT();
				break;
			case 'opent':
				$this->_abrirTestemunhoT();
				break;
				
			case 'pin':
				$this->_destacarTestemunho();
				break;
			case 'unpin':
				$this->_retiraDestaqueTestemunho();
				break;				
			case 'pint':
				$this->_destacarTestemunhoT();
				break;
			case 'unpint':
				$this->_retiraDestaqueTestemunhoT();
				break;
				
			case 'approve':
				$this->_aprovarTestemunho();
				break;
			case 'unapprove':
				$this->_desaprovarTestemunho();
				break;				
			case 'tapprove':
				$this->_taprovarTestemunho();
				break;
			case 'tunapprove':
				$this->_tdesaprovarTestemunho();
				break;   
				
			case 'delete':
				$this->_deleteTestemunho();
				break;
			case 'doDelete':
				$this->_dodeleteTestemunho();
				break;
				
			case 'deletecomment':
				$this->_deleteComment();
				break;
			case 'ban':
				$this->_banMember();
				break;
			case 'doBanMember':
				$this->_doBanMember();
				break;
			case 'banFromComment':
				$this->_banMemberFromComment();
				break;

			default:
				$this->registry->output->showError( 'missing_files' );
				break;
		}

		$this->library->sendOutput();
	}

	private function _doSomeInit()
	{
		/* Check the auth_key */
		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'bad_md5_hash' );
		}
		
		if ( $this->request['do'] == 'banFromComment' )
		{
			$test = $this->DB->buildAndFetch( array( 'select' => 'tid',
													 'from'   => 'testemunhos_comments',
													 'where'  => 'cid='.intval($this->request['ID'])
			) );
		}
		
		if ( $this->request['do'] == 'banFromComment' )
		{
			$where = intval( $test['tid'] );
		}
		else
		{
			$where = intval( $this->request['ID'] );
		}

		$this->testemunho = $this->DB->buildAndFetch( array( 'select' => '*',
														 'from'   => 'testemunhos',
														 'where'  => 't_id='.$where
		) );
		
		if ( !$this->testemunho['t_id'] )
		{
			$this->registry->output->showError( 'missing_files' );
		}
		
		/* Get our stats cache with getCache to be sure */
		$this->statsCache = $this->cache->getCache('testemunhos');
	}
	
	private function _fecharTestemunho()
	{
		if ( !$this->memberData['sostestemunhos_fechar'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}
		
		if ( $this->testemunho['t_open'] == 0 )
		{
			$this->registry->output->showError( 'testemunho_alreadyclosed' );
		}
        
        $cid = intval( $this->request['cid'] );
        
		$this->DB->update( 'testemunhos', array( 't_open' => 0 ), 't_id='.$this->testemunho['t_id'] );

		$this->library->addModLog( $this->lang->words['testemunho_modlog_close'], $this->testemunho );

        $this->registry->output->redirectScreen( $this->lang->words['testemunho_closed'], $this->settings['base_url_with_app']."&module=testemunhos&section=list&showcat=".$cid );
		
		
	}

	private function _abrirTestemunho()
	{
		if ( !$this->memberData['sostestemunhos_fechar'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}
		
		if ( $this->testemunho['t_open'] )
		{
			$this->registry->output->showError( 'testemunho_alreadyopened' );
		}
		
        $cid = intval( $this->request['cid'] );
        
		$this->DB->update( 'testemunhos', array( 't_open' => 1 ), 't_id='.$this->testemunho['t_id'] );
				
		$this->library->addModLog( $this->lang->words['testemunho_modlog_open'], $this->testemunho );		

        $this->registry->output->redirectScreen( $this->lang->words['testemunho_opened'], $this->settings['base_url_with_app']."&module=testemunhos&section=list&showcat=".$cid );
		
	}

	
	private function _fecharTestemunhoT()
	{
		if ( !$this->memberData['sostestemunhos_fechar'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}
		
		if ( $this->testemunho['t_open'] == 0 )
		{
			$this->registry->output->showError( 'testemunho_alreadyclosed' );
		}
        
        $id = intval( $this->request['ID'] );
        
		$this->DB->update( 'testemunhos', array( 't_open' => 0 ), 't_id='.$this->testemunho['t_id'] );

		$this->library->addModLog( $this->lang->words['testemunho_modlog_close'], $this->testemunho );

        $this->registry->output->redirectScreen( $this->lang->words['testemunho_closed'], $this->settings['base_url_with_app']."&module=testemunhos&section=view&showtestimonial=".$id );
		
		
	}

	private function _abrirTestemunhoT()
	{
		if ( !$this->memberData['sostestemunhos_fechar'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}
		
		if ( $this->testemunho['t_open'] )
		{
			$this->registry->output->showError( 'testemunho_alreadyopened' );
		}
		
        $id = intval( $this->request['ID'] );
        
		$this->DB->update( 'testemunhos', array( 't_open' => 1 ), 't_id='.$this->testemunho['t_id'] );
				
		$this->library->addModLog( $this->lang->words['testemunho_modlog_open'], $this->testemunho );

        $this->registry->output->redirectScreen( $this->lang->words['testemunho_opened'], $this->settings['base_url_with_app']."&module=testemunhos&section=view&showtestimonial=".$id );
		
	}
	
	
	private function _aprovarTestemunho()
	{
		if ( !$this->memberData['sostestemunhos_aprovar'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}

		if ( $this->testemunho['t_approved'] )
		{
			$this->registry->output->showError( 'testemunho_alreadyapproved' );
		}
		
		$this->DB->update( 'testemunhos', array( 't_approved' => 1 ), 't_id='.$this->testemunho['t_id'] );

        $cid = intval( $this->request['cid'] );
         
        $all = $this->DB->buildAndFetch( array( 'select' => '*',	'from' => 'testemunhos_cats', 'where' => 'c_id='.$cid ) ); 
                                                    
        $this->DB->update( 'testemunhos_cats', 'c_test=c_test+1', 'c_id='.$cid, true, true );
                		
		if ( $this->settings['testemunhos_topic'] )
		{
			$this->library->createTopic( $this->testemunho['t_id'] );
		}
		
		if ( $this->settings['testemunhos_sendpm'] )
		{
			$this->library->enviarMP ( $this->settings['testemunhos_mpaprovacao_titulo'], $this->settings['testemunhos_mpaprovacao'], $this->testemunho['t_member_id'], "[url='".$this->settings['board_url']."/index.php?app=testimonials&showtestimonial={$this->testemunho['t_id']}']{$this->testemunho['t_title']}[/url]" );
		}
		
		$this->library->addModLog( $this->lang->words['testemunho_modlog_approved'], $this->testemunho );
		
		$this->library->rebuildTestemunhosCache();
		
		$this->registry->output->redirectScreen( $this->lang->words['testemunho_aprovado'], $this->registry->getClass('output')->buildSEOUrl( "showlist=".$cid, 'publicWithApp', $all['c_name_seo'], 'showlist' ) );
	}
	
	private function _desaprovarTestemunho()
	{
		if ( !$this->memberData['sostestemunhos_aprovar'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}
		
		if ( !$this->testemunho['t_approved'] )
		{
			$this->registry->output->showError( 'testemunho_alreadyunapproved' );
		}

        $cid = intval( $this->request['cid'] );
        
        $all = $this->DB->buildAndFetch( array( 'select' => '*',	'from' => 'testemunhos_cats', 'where' => 'c_id='.$cid ) ); 
                                                    
        $this->DB->update( 'testemunhos_cats', 'c_test=c_test-1', 'c_id='.$cid, true, true );
        		
		$this->DB->update( 'testemunhos', array( 't_approved' => 0 ), 't_id='.$this->testemunho['t_id'] );

		if ( $this->settings['testemunhos_sendpm'] )
		{
			$this->library->enviarMP ( $this->settings['testemunhos_mpdesaprovacao_titulo'], $this->settings['testemunhos_mpdesaprovacao'], $this->testemunho['t_member_id'], $this->testemunho['t_title'] );
		}
		
		$this->library->rebuildTestemunhosCache();
		
		$this->library->addModLog( $this->lang->words['testemunho_modlog_unapproved'], $this->testemunho );
		
		$this->registry->output->redirectScreen( $this->lang->words['testemunho_desaprovado_index'], $this->registry->getClass('output')->buildSEOUrl( "showlist=".$cid, 'publicWithApp', $all['c_name_seo'], 'showlist' ) );
        //$this->registry->output->redirectScreen( $this->lang->words['testemunho_desaprovado_index'], $this->settings['base_url_with_app']."&module=testemunhos&section=list&showcat=".$cid );
	}


	private function _taprovarTestemunho()
	{
		if ( !$this->memberData['sostestemunhos_aprovar'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}

		if ( $this->testemunho['t_approved'] )
		{
			$this->registry->output->showError( 'testemunho_alreadyapproved' );
		}
		
		$this->DB->update( 'testemunhos', array( 't_approved' => 1 ), 't_id='.$this->testemunho['t_id'] );

        $id = intval( $this->request['ID'] );
        $cid = intval( $this->request['cid'] );
                                                    
        $this->DB->update( 'testemunhos_cats', 'c_test=c_test+1', 'c_id='.$cid, true, true );
                		
		if ( $this->settings['testemunhos_topic'] )
		{
			$this->library->createTopic( $this->testemunho['t_id'] );
		}
		
		if ( $this->settings['testemunhos_sendpm'] )
		{
			$this->library->enviarMP ( $this->settings['testemunhos_mpaprovacao_titulo'], $this->settings['testemunhos_mpaprovacao'], $this->testemunho['t_member_id'], "[url='".$this->settings['board_url']."/index.php?app=testimonials&showtestimonial={$this->testemunho['t_id']}']{$this->testemunho['t_title']}[/url]" );
		}
		
		$this->library->addModLog( $this->lang->words['testemunho_modlog_approved'], $this->testemunho );
		
		$this->library->rebuildTestemunhosCache();
		
		$this->registry->output->redirectScreen( $this->lang->words['testemunho_aprovado'], $this->registry->getClass('output')->buildSEOUrl( "showtestimonial=".$id, 'publicWithApp', $this->testemunho['t_title_seo'], 'showtestimonial' ) );
        //$this->registry->output->redirectScreen( $this->lang->words['testemunho_aprovado'], $this->settings['base_url_with_app']."module=testemunhos&section=view&showtestimonial=".$id."&cid=".$cid );
	}
	
	private function _tdesaprovarTestemunho()
	{
		if ( !$this->memberData['sostestemunhos_aprovar'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}
		
		if ( !$this->testemunho['t_approved'] )
		{
			$this->registry->output->showError( 'testemunho_alreadyunapproved' );
		}

        $id = intval( $this->request['ID'] );
        $cid = intval( $this->request['cid'] );
                                                     
        $this->DB->update( 'testemunhos_cats', 'c_test=c_test-1', 'c_id='.$cid, true, true );
        		
		$this->DB->update( 'testemunhos', array( 't_approved' => 0 ), 't_id='.$this->testemunho['t_id'] );

		if ( $this->settings['testemunhos_sendpm'] )
		{
			$this->library->enviarMP ( $this->settings['testemunhos_mpdesaprovacao_titulo'], $this->settings['testemunhos_mpdesaprovacao'], $this->testemunho['t_member_id'], $this->testemunho['t_title'] );
		}
		
		$this->library->rebuildTestemunhosCache();
		
		$this->library->addModLog( $this->lang->words['testemunho_modlog_unapproved'], $this->testemunho );
		
		$this->registry->output->redirectScreen( $this->lang->words['testemunho_desaprovado_index'], $this->registry->getClass('output')->buildSEOUrl( "showtestimonial=".$id, 'publicWithApp', $this->testemunho['t_title_seo'], 'showtestimonial' ) );
        //$this->registry->output->redirectScreen( $this->lang->words['testemunho_desaprovado_index'], $this->settings['base_url_with_app']."module=testemunhos&section=view&showtestimonial=".$id."&cid=".$cid );
	}
    
	private function _destacarTestemunho()
	{
		if ( !$this->memberData['sostestemunhos_destacar'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}

		if ( $this->testemunho['t_pinned'] )
		{
			$this->registry->output->showError( 'testemunho_alreadypinned' );
		}

        $cid = intval( $this->request['cid'] );	
        	
		$this->DB->update( 'testemunhos', array( 't_pinned' => 1 ), 't_id='.$this->testemunho['t_id'] );
		
		$this->library->addModLog( $this->lang->words['testemunho_modlog_pinned'], $this->testemunho );
		
        $this->registry->output->redirectScreen( $this->lang->words['testemunho_destacado'], $this->settings['base_url_with_app']."&module=testemunhos&section=list&showcat=".$cid );
	}
	
	private function _retiraDestaqueTestemunho()
	{
		if ( !$this->memberData['sostestemunhos_destacar'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}
		
		if ( !$this->testemunho['t_approved'] )
		{
			$this->registry->output->showError( 'testemunho_alreadyunapinned' );
		}
		
        $cid = intval( $this->request['cid'] );
        
		$this->DB->update( 'testemunhos', array( 't_pinned' => 0 ), 't_id='.$this->testemunho['t_id'] );
		
		$this->library->addModLog( $this->lang->words['testemunho_modlog_unpinned'], $this->testemunho );
		
        $this->registry->output->redirectScreen( $this->lang->words['testemunho_semdestaque'], $this->settings['base_url_with_app']."&module=testemunhos&section=list&showcat=".$cid );
	}

	
	private function _destacarTestemunhoT()
	{
		if ( !$this->memberData['sostestemunhos_destacar'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}

		if ( $this->testemunho['t_pinned'] )
		{
			$this->registry->output->showError( 'testemunho_alreadypinned' );
		}

        $id = intval( $this->request['ID'] );	
        	
		$this->DB->update( 'testemunhos', array( 't_pinned' => 1 ), 't_id='.$this->testemunho['t_id'] );
		
		$this->library->addModLog( $this->lang->words['testemunho_modlog_pinned'], $this->testemunho );
		
		$this->registry->output->redirectScreen( $this->lang->words['testemunho_destacado'], $this->settings['base_url_with_app']."&module=testemunhos&section=view&showtestimonial=".$id );
	}
	
	private function _retiraDestaqueTestemunhoT()
	{
		if ( !$this->memberData['sostestemunhos_destacar'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}
		
		if ( !$this->testemunho['t_approved'] )
		{
			$this->registry->output->showError( 'testemunho_alreadyunapinned' );
		}
		
        $id = intval( $this->request['ID'] );
        
		$this->DB->update( 'testemunhos', array( 't_pinned' => 0 ), 't_id='.$this->testemunho['t_id'] );
		
		$this->library->addModLog( $this->lang->words['testemunho_modlog_unpinned'], $this->testemunho );
		
		$this->registry->output->redirectScreen( $this->lang->words['testemunho_semdestaque'], $this->settings['base_url_with_app']."&module=testemunhos&section=view&showtestimonial=".$id );
	}
	
	
	private function _deleteTestemunho()
	{
		$this->registry->output->addNavigation( $this->lang->words['testemunhos_title'], 'app=testimonials' );

		if ( !$this->memberData['sostestemunhos_remover_testemunho'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}
		
        $cid = intval( $this->request['cid'] );
        
		$testemunho = $this->DB->buildAndFetch( array( 'select' => 't_id, t_title, t_comments', 'from' => 'testemunhos', 'where' => "t_id=".$this->request['ID'] ) );
		
		$comments = $this->DB->buildAndFetch( array( 'select' => 'count(*) as cnt', 'from' => 'testemunhos_comments', 'where' => "tid=".$this->request['ID'] ) );
		
		$comms = $comments['cnt'];
		
		if ( $comms == 1 )
		{
			$text = $this->lang->words['testemunho_deletar_confirm_comm'];
		}
		else if ( $comms > 1 )
		{			
			$text = sprintf( $this->lang->words['testemunho_deletar_confirm_comms'], $comms );
		}
		else
		{
			$text = "";
		}

		if ( $this->request['l'] == 'i' )
		{
			$where = 'app=testimonials';
		}
		else
		{
			$where = 'app=testimonials&showtestimonial='.$testemunho['t_id'];
		}
		
		$this->library->pageOutput .= $this->registry->getClass('output')->getTemplate('testimonials')->deleteTestemunho( $testemunho, $where, $text );
		$this->registry->output->addNavigation( $this->lang->words['testemunho_deletar'].": ".$testemunho['t_title'], '' );
		$this->registry->getClass('output')->setTitle( $this->lang->words['t_delete'].": ".$this->topic['title'] );
		
		$this->library->sendOutput();
	}

	private function _dodeleteTestemunho()
	{
		if ( !$this->memberData['sostestemunhos_remover_testemunho'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}
        
        $cid = intval( $this->request['cid'] );

		$tt = $this->DB->buildAndFetch( array('select'    => 't_id, t_approved',
								 			  'from'      => 'testemunhos',
								 			  'where'     => 't_id='.$this->request['ID']) );
                                                     
		$thetest = $this->DB->buildAndFetch( array(  'select'    => 'c_id, c_test',
								 					 'from'      => 'testemunhos_cats',
								 					 'where'     => 'c_id='.$cid) );
        if($tt['t_approved'] == 1) 
        {
            $this->DB->update( 'testemunhos_cats', 'c_test=c_test-1', 'c_id='.$thetest['c_id'], true, true );
        }                                            
        
                
		/* excluir o testemunho */
		$this->DB->delete( 'testemunhos', 't_id='.$this->request['ID'] );
		/* excluir os comentários */
		$this->DB->delete( 'testemunhos_comments', 'tid='.$this->request['ID'] );
		/* excluir assinaturas de e-mails*/
		$this->DB->delete( 'testemunhos_tracker', 't_id='.$this->request['ID'] );

		$this->library->rebuildTestemunhosCache();

		$this->library->addModLog( sprintf( $this->lang->words['testemunho_modlog_removed'], $this->testemunho['t_title'] ), $this->testemunho );
		
		//$this->registry->output->redirectScreen( $this->lang->words['testemunho_excluido'], $this->settings['base_url_with_app']."st=".$this->request['st'] );
        $this->registry->output->redirectScreen( $this->lang->words['testemunho_excluido'], $this->settings['base_url_with_app']."&module=testemunhos&section=list&showcat=".$cid );
	}

	private function _deleteComment()
	{
		if ( !$this->memberData['sostestemunhos_remover_comentario'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}

		$this->DB->delete( 'testemunhos_comments', 'cid='.$this->request['CID'] );
	
		$stats = $this->cache->getCache('testemunhos');
		$stats['comments']--;
		$this->cache->setCache( 'testemunhos', $stats, array( 'array' => 1, 'deletefirst' => 1 ) );
		
		/* Atualizar o total de comentários */
		$totalComm = $this->DB->buildAndFetch( array( 'select' => 't_comments, t_date',
													  'from'   => 'testemunhos',
													  'where'  => 't_id='.intval( $this->request['ID'] )
		) );
		
		$newValue = intval( $totalComm['t_comments'] - 1 );
		
		$this->DB->update( 'testemunhos', array( 't_comments' => $newValue ), 't_id='. $this->request['ID'] );
		
		/* Atualizar o testemunho com o último comentário, se tiver.. se não, com a data de abertura do testemunho */
		$lastComm = $this->DB->buildAndFetch( array(  'select' => 'date',
													  'from'   => 'testemunhos_comments',
													  'where'  => 'tid='.intval( $this->request['ID'] ),
													  'order'  => 'date desc',
													  'limit'  => array( 0, 1 )
		) );
        		
		if ( $lastComm['date'] )
		{
			$this->DB->update( 'testemunhos', array( 't_last_comment' => $lastComm['date'] ), 't_id='. $this->request['ID'] );
		}
		else
		{
			$this->DB->update( 'testemunhos', array( 't_last_comment' => $totalComm['t_date'] ), 't_id='. $this->request['ID'] );
		}

		$this->library->addModLog( sprintf( $this->lang->words['testemunho_modlog_commentremoved'], $this->testemunho['t_title'] ), $this->testemunho );
		
		$this->registry->output->redirectScreen( $this->lang->words['comentario_excluido'], $this->settings['base_url_with_app']. 'showtestimonial='. $this->request['ID'] );
	}

	private function _banMember()
	{
		if ( !$this->memberData['sostestemunhos_banir_membros'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}
		
		if ( !isset( $this->request['ID'] ) OR !$this->request['ID'] )
		{
			$this->registry->output->showError( 'ban_noid' );
		}

		$this->registry->output->addNavigation( $this->lang->words['testemunhos_title'], 'app=testimonials' );
		
		$t = $this->DB->buildAndFetch( array( 'select' => 't_member_id', 'from' => 'testemunhos', 'where' => "t_id=".$this->request['ID'] ) );
		
		$user = IPSMember::load( $t['t_member_id'], 'all' );
			
		$text   = sprintf( $this->lang->words['banir_membro_confirm'], $user['members_display_name'] );
		$author = $user['member_id'];
		$author_name = $user['members_display_name'];
		
		$this->library->pageOutput .= $this->registry->getClass('output')->getTemplate('testimonials')->banMember( $text, $author, $author_name );
		$this->registry->output->addNavigation( $this->lang->words['banir'], '' );
		$this->registry->getClass('output')->setTitle( $this->lang->words['t_delete'].": ".$this->topic['title'] );
		
		$this->library->sendOutput();		
	}

	private function _doBanMember()
	{
		if ( !$this->memberData['sostestemunhos_banir_membros'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}

		if ( !isset( $this->request['memberid'] ) OR !$this->request['memberid'] )
		{
			$this->registry->output->showError( 'ban_noid' );
		}
		
		if ( $this->request['memberid'] == $this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'ban_sameuser' );
		}
		
		$user = IPSMember::load( $this->request['memberid'], 'all' );
		
		if ( $user['sostestemunhos_banned'] == 1 )
		{
			$this->registry->output->showError( 'ban_alreadybanned' );
		}
		
		$this->DB->update( 'members', array( 'sostestemunhos_banned' => 1, 'sostestemunhos_banned_date' => time() ), 'member_id='.$this->request['memberid'] );
		
		$text = sprintf( $this->lang->words['testemunho_modlog_ban'], $this->request['membername'] );
		
		$this->library->addModLog( $text, '---' );
		
		$this->registry->output->redirectScreen( $this->lang->words['membro_banido'], $this->settings['base_url_with_app']. 'showtestimonial='. $this->request['ID'] );
	}

	public function _banMemberFromComment()
	{
		if ( !$this->memberData['sostestemunhos_banir_membros'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}

		if ( !isset( $this->request['ID'] ) OR !$this->request['ID'] )
		{
			$this->registry->output->showError( 'ban_noid' );
		}
		
		$c = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'testemunhos_comments', 'where' => "cid=".$this->request['ID'] ) );
		
		$this->registry->output->addNavigation( $this->lang->words['testemunhos_title'], 'app=testimonials' );

		$user = IPSMember::load( $c['member_id'], 'all' );
			
		$text   = sprintf( $this->lang->words['banir_membro_confirm'], $user['members_display_name'] );
		$author = $user['member_id'];
		$author_name = $user['members_display_name'];
		
		$this->library->pageOutput .= $this->registry->getClass('output')->getTemplate('testimonials')->banMember( $text, $author, $author_name );
		$this->registry->output->addNavigation( $this->lang->words['banir'], '' );
		$this->registry->getClass('output')->setTitle( $this->lang->words['t_delete'].": ".$this->topic['title'] );
		
		$this->library->sendOutput();	
		
		$this->registry->output->redirectScreen( $this->lang->words['membro_banido'], $this->settings['base_url_with_app']. 'showtestimonial='. $this->request['ID'] );
	}
		
}
?>