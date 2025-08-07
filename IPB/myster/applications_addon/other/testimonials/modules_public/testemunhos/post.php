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

class public_testimonials_testemunhos_post extends ipsCommand
{
	private $post_key  = "";
	private $errors    = array();
	private $ispreview = false;
	private $output;
	private $library;
        
	
	public function doExecute( ipsRegistry $registry )
	{
		/* Setup output & library shortcuts */
		$this->output  = $this->registry->output;
		$this->library = $this->registry->getClass('testemunhosLibrary');

		/* Check view permissions */
		$this->library->checkPermissions();

		/* Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_testemunhos' ), 'testemunhos' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_post' ), 'forums' );


		
		/* What to do? */
		switch ( $this->request['do'] )
		{
			case 'save':
				$this->process();
			break;
			
			case 'edit':
			default:
				$this->Form( 'edit' );
			break;
			
			case 'new':
			default:
				$this->Form( 'add' );
			break;
		}
		
		/* Send Output */
		$this->library->sendOutput();
	}


	private function Form( $type='add', $input=array() )
	{

		/* Nav */
		$this->registry->output->addNavigation( $this->lang->words['testemunhos_title'], 'app=testimonials' );

        $classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );

        $editor = new $classToLoad();

        $cid = intval($this->request['cid']);
        
        $cats = $this->DB->buildAndFetch( array( 'select' => 'c_id',
                                                'from'   => 'testemunhos_cats',
                                                'where'  => 'c_id='.$cid ) );
                                                            		
		$data    = array();	
	
	    if ( $type == 'add' )
	    {	
		    /* Build the output */
		    $data['button']     = $this->lang->words['testemunho_post_add_button'];
		    $data['button']     = $data['button'];
		    $data['title']      = $this->lang->words['testemunho_post_add_title'];
		    $data['name']       = isset( $input['titulo'] ) ? $input['titulo'] : $data['t_title'];
		    $data['rating']     = isset( $input['avaliacao'] ) ? $input['avaliacao'] : $data['t_rating'];
		    $data['auth_key']   = $this->member->form_hash;
		    $data['post_key']   = $this->post_key;
		    $data['type']		= $type;		      
		        
		    /* Parsing stuff */
			IPSText::getTextClass('bbcode')->parse_smilies   = $this->settings['testemunhos_emoticons'];
			IPSText::getTextClass('bbcode')->parse_bbcode    = $this->settings['testemunhos_bbcode'];
			IPSText::getTextClass('bbcode')->parse_nl2br     = 1;
			IPSText::getTextClass('bbcode')->parse_html      = 0;
			IPSText::getTextClass('bbcode')->parsing_section = 'testemunhos_submit';
	    	    
		    /* Are we previewing? */
		    if ( $this->request['preview'] )
		    { 
                $text = IPSText::getTextClass('bbcode')->preEditParse( $_POST['Post'] );
	
	            $data['editor'] = $editor->show( 'Post', array(), $text );
				        
			    $this->library->pageOutput .= $this->registry->getClass('output')->getTemplate( 'post' )->preview( $this->library->generatePostPreview( $_POST['Post'] ) );
		    }
		    else
		    {
		        $data['editor'] = $editor->show( 'Post', array() );
		    }		    
	    }
		else if ( $type == 'edit' )
		{
		    /* Init */
		    $this->registry->output->addNavigation( "Editing Testimonial" );
		    
		    $id   = intval( $this->request['id'] );
		    $data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'testemunhos', 'where' => 't_id=' . $id ) );		    

			if ( !id )
			{
				$this->registry->getClass('output')->showError( 'invalid_testemunhos' );
			}

			if ( $this->memberData['sostestemunhos_editar'] )
			{
				$_canEdit = 1;
			}
	
			if ( $this->memberData['member_id'] == $data['t_member_id'] )
			{
				//-----------------------------------------
				// Have we set a time limit?
				//-----------------------------------------
					
				if ( $this->memberData['sostestemunhos_max_time_edit'] > 0 AND !$this->memberData['sostestemunhos_editar'] )
				{
					if ( $data['t_date'] > ( time() - ( intval($this->memberData['sostestemunhos_max_time_edit']) * 60 ) ) )
					{
						$_canEdit = 1;
					}
				}
			}
			
			if ( $_canEdit == 0 )
			{
				$this->registry->getClass('output')->showError( 'cant_edit' );
			}
			            
            if ( $this->request['preview'] )
		    { 

		        /* Build the output */
		        $data['button']     = $this->lang->words['testemunho_post_add_button'];
		        $data['button']     = $data['button'];
		        $data['title']      = $this->lang->words['testemunho_post_add_title'];
		        $data['name']       = isset( $input['titulo'] ) ? $input['titulo'] : $data['t_title'];
		        $data['rating']     = isset( $input['avaliacao'] ) ? $input['avaliacao'] : $data['t_rating'];
		        $data['auth_key']   = $this->member->form_hash;
		        $data['post_key']   = $this->post_key;
		        $data['type']		= $type;	
		        
		        /* Parsing stuff */
			    IPSText::getTextClass('bbcode')->parse_smilies   = $this->settings['testemunhos_emoticons'];
			    IPSText::getTextClass('bbcode')->parse_bbcode    = $this->settings['testemunhos_bbcode'];
			    IPSText::getTextClass('bbcode')->parse_nl2br     = 1;
			    IPSText::getTextClass('bbcode')->parse_html      = 1;
			    IPSText::getTextClass('bbcode')->parsing_section = 'testemunhos_submit';
			    		    
                $text = IPSText::getTextClass('bbcode')->preEditParse( $_POST['Post'] );
	
	            $data['editor'] = $editor->show( 'Post', array(), $text );
				        
			    $this->library->pageOutput .= $this->registry->getClass('output')->getTemplate( 'post' )->preview( $this->library->generatePostPreview( $_POST['Post'] ) );
		    }
		    else
		    {
		        /* Build the output */
		        $data['button']     = $this->lang->words['testemunho_post_add_button'];
		        $data['button']     = $data['button'];
		        $data['title']      = $this->lang->words['testemunho_post_add_title'];
		        $data['name']       = isset( $input['titulo'] ) ? $input['titulo'] : $data['t_title'];
		        $data['rating']     = isset( $input['avaliacao'] ) ? $input['avaliacao'] : $data['t_rating'];
		        $data['auth_key']   = $this->member->form_hash;
		        $data['post_key']   = $this->post_key;
		        $data['type']		= $type;	
		        
		        /* Parsing stuff */
			    IPSText::getTextClass('bbcode')->parse_smilies   = $this->settings['testemunhos_emoticons'];
			    IPSText::getTextClass('bbcode')->parse_bbcode    = $this->settings['testemunhos_bbcode'];
			    IPSText::getTextClass('bbcode')->parse_nl2br     = 1;
			    IPSText::getTextClass('bbcode')->parse_html      = 1;
			    IPSText::getTextClass('bbcode')->parsing_section = 'testemunhos_submit';

                $text = IPSText::getTextClass('bbcode')->preEditParse( $data['t_content'] );
	         
	            $data['editor'] = $editor->show( 'Post', array(), $text );
	        }		        	    
		}
		    	    
	    $this->library->pageOutput .= $this->registry->getClass('output')->getTemplate('testimonials')->postForm( $data, $cats );
	}
	
	
	    	
	private function Form_COPY( $type='add', $input=array() )
	{

        $classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
        $editor = new $classToLoad();

		/* Pode Editar */
		$_canEdit = 0;
		
		/* Navegação */
		$this->registry->output->addNavigation( $this->lang->words['testemunhos_title'], 'app=testimonials' );
			
		/* Init */
		$data    = array();
		
		/* Process any inputs */
		$id   = intval( $this->request['id'] );
		
		/* Don't pull this data if previewing or in error */
		if ( $type == 'edit' AND ! count( $input ) )
		{
			if ( ! $id )
			{
				$this->registry->getClass('output')->showError( 'invalid_id' );
			}
			
			$data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'testemunhos', 'where' => 't_id=' . $id ) );
			
			if ( ! is_array( $data ) OR ! count( $data ) )
			{
				$this->registry->getClass('output')->showError( 'invalid_testemunhos' );
			}
			
			/* O testemunho pode ser editado ? */
			if ( !$data['t_open'] )
			{
				$this->registry->getClass('output')->showError( 'no_edit_closed' );
			}

			/* Este usuário pode editar o testemunho ? */
			if ( $this->memberData['sostestemunhos_editar'] )
			{
				$_canEdit = 1;
			}
	
			if ( $this->memberData['member_id'] == $data['t_member_id'] )
			{
				//-----------------------------------------
				// Have we set a time limit?
				//-----------------------------------------
					
				if ( $this->memberData['sostestemunhos_max_time_edit'] > 0 AND !$this->memberData['sostestemunhos_editar'] )
				{
					if ( $data['t_date'] > ( time() - ( intval($this->memberData['sostestemunhos_max_time_edit']) * 60 ) ) )
					{
						$_canEdit = 1;
					}
				}
			}
			
			if ( $_canEdit == 0 )
			{
				$this->registry->getClass('output')->showError( 'cant_edit' );
			}
			
			$navigation = sprintf( $this->lang->words['testemunho_post_edit_title'], $data['t_title'] );
			$this->registry->output->addNavigation( $navigation, '' );
			$title = sprintf( $this->lang->words['testemunho_post_edit_title'], $data['t_title'] );
			
			/* Deal with the editor content */
			IPSText::getTextClass('bbcode')->parse_smilies   = $this->settings['testemunhos_emoticons'];
			IPSText::getTextClass('bbcode')->parse_bbcode    = $this->settings['testemunhos_bbcode'];
			IPSText::getTextClass('bbcode')->parse_nl2br     = 1;
			IPSText::getTextClass('bbcode')->parse_html      = 0;
			IPSText::getTextClass('bbcode')->parsing_section = 'testemunhos_submit';
			
			
			/* Editor mumbo jumbo */
			if ( IPSText::getTextClass( 'editor' )->method == 'rte' )
	 		{
				$text = IPSText::getTextClass('bbcode')->convertForRTE( $data['t_content'] );
	 		}
	 		else
	 		{
				$text = IPSText::getTextClass('bbcode')->preEditParse( $data['t_content'] );
	 		}
		}
		else
		{;
			/* Error checking */
			if ( $type == 'add' AND !$this->memberData['sostestemunhos_postar_testemunhos'] )
			{
				$this->registry->getClass('output')->showError( 'testemunhos_dontcreatenewtestemunho' );
			}
			
			$navigation = $this->lang->words['testemunho_post_add_title'];
			$this->registry->output->addNavigation( $navigation, '' );
			$title = $this->lang->words['testemunho_post_add_title'];
			
			//$data['t_content'] = isset( $input['_post'] ) ? $input['_post'] : ( isset( $data['t_content'] ) ? $data['t_content'] : '' );
            $data['t_content'] = $_POST['_post'];
			
			/* Deal with the editor content */
			IPSText::getTextClass('bbcode')->parse_smilies   = $this->settings['testemunhos_emoticons'];
			IPSText::getTextClass('bbcode')->parse_bbcode    = $this->settings['testemunhos_bbcode'];
			IPSText::getTextClass('bbcode')->parse_nl2br     = 1;
			IPSText::getTextClass('bbcode')->parse_html      = 0;
			IPSText::getTextClass('bbcode')->parsing_section = 'testemunhos_submit';
			
			$data['t_content_parsed'] = IPSText::getTextClass('bbcode')->preDbParse( $data['t_content'] );
			
			/* Editor mumbo jumbo */
			if ( IPSText::getTextClass( 'editor' )->method == 'rte' )
	 		{
				$text = IPSText::getTextClass('bbcode')->convertForRTE( $data['t_content_parsed'] );
	 		}
	 		else
	 		{
				$text = IPSText::getTextClass('bbcode')->preEditParse( $data['t_content_parsed'] );
	 		}

		}

			/* Deal with the editor content */
			IPSText::getTextClass('bbcode')->parse_smilies   = $this->settings['testemunhos_emoticons'];
			IPSText::getTextClass('bbcode')->parse_bbcode    = $this->settings['testemunhos_bbcode'];
			IPSText::getTextClass('bbcode')->parse_nl2br     = 1;
			IPSText::getTextClass('bbcode')->parse_html      = 1;
			IPSText::getTextClass('bbcode')->parsing_section = 'testemunhos_submit';
			
			$text = IPSText::getTextClass('bbcode')->preEditParse( $data['t_content'] );
		/* Build the output */
		$data['button']     = $this->lang->words['testemunho_post_add_button'];
		$data['button']     = $data['button'];
		$data['title']      = $this->lang->words['testemunho_post_add_title'];
		$data['name']       = isset( $input['titulo'] ) ? $input['titulo'] : $data['t_title'];
		$data['rating']     = isset( $input['avaliacao'] ) ? $input['avaliacao'] : $data['t_rating'];


        $data['editor']     = $text = $editor->show( '_post', array(), $text );
 

		$data['auth_key']   = $this->member->form_hash;
		$data['post_key']   = $this->post_key;
		$data['type']		= $type;

        $editor->removeAutoSavedContent( array( 'member_id' => $this->memberData['member_id'], 'autoSaveKey' => "my-autosave-key" ) );
		
		/* Are we previewing? */
		if ( $this->request['preview'] )
		{
			$this->library->pageOutput .= $this->registry->getClass('output')->getTemplate( 'post' )->preview( $this->library->generatePostPreview( $data['t_content'] ) );
		}


        $cid = intval($this->request['cid']);
        $cats = $this->DB->buildAndFetch( array( 'select' => 'c_id',
                                                'from'   => 'testemunhos_cats',
                                                'where'  => 'c_id='.$cid ) );
		//$title, $data, $this->errors, $cats, $showedit
		$this->library->pageOutput .= $this->registry->getClass('output')->getTemplate('testimonials')->postForm( $data, $cats );
	}
	
	private function process()
	{
	    $classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );

        $editor = new $classToLoad();

		$t_name			= trim( $this->request['titulo'] );
		$t_avaliacao	= intval( $this->request['avaliacao'] );
		$type			= $this->request['type'] == 'edit' ? 'edit' : 'add';
		$id				= ( isset( $this->request['testemunho'] ) AND intval( $this->request['testemunho'] ) ) ? $this->request['testemunho'] : 0;
		$cid = intval($this->request['cid']);

		$preview		= isset( $this->request['preview'] ) ? trim( $this->request['preview'] ) : 0;

		$html_state 	= intval( $this->request['htmlstatus'] );
		
		$this->post_key = $this->request['post_key'] ? $this->request['post_key'] : md5( microtime() );
		
		$post = $editor->process( $_POST['Post'] );
		
		
		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->getClass('output')->showError( 'bad_md5_hash' );
		}
		
		if ( $t_name == '' )
		{
			$this->errors[] = $this->lang->words['testemunho_name_empty'];
		}
					
		if ( $post == '' )
		{
			$this->errors[] = $this->lang->words['testemunho_content_empty'];
		}

		if ( IPSText::mbstrlen( $post ) > ( $this->settings['testemunhos_maxsizecontent'] * 1024 ) AND ! $preview ) 
		{
			$this->errors[] = $this->lang->words['post_too_long'];
		}

		if ( IPSText::mbstrlen( $t_name ) > $this->settings['testemunhos_maxsizetitle'] )
		{
			$this->errors[] = $this->lang->words['topic_title_long'];
		}

		$output = array( 'titulo'    => $t_name,
						 'avaliacao' => $t_avaliacao,
					     'Post'     => $post,
		);
		
		$reload = 0;
		
		if ( count( $this->errors ) )
		{
			$reload = 1;
		}
		
		if ( $preview )
		{
			$reload = 1;
			$this->ispreview = true;
		}

		if ( $reload )
		{
			$this->Form( $type, $output );
			return;
		}
		
		IPSText::getTextClass('bbcode')->parse_html      = ( $this->memberData['g_tutorials_posthtml'] && $html_state ) ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_nl2br     = $this->settings['testemunhos_html'];
		IPSText::getTextClass('bbcode')->parse_smilies   = $this->settings['testemunhos_emoticons'];
		IPSText::getTextClass('bbcode')->parse_bbcode    = $this->settings['testemunhos_bbcode'];
		IPSText::getTextClass('bbcode')->parsing_section = 'testemunhos_submit';
		
        $message = IPSText::getTextClass('bbcode')->preDbParse( $post );
		
		if ( $type == 'add' && ! $this->memberData['sostestemunhos_postar_testemunhos'] )
		{
			$this->registry->getClass('output')->showError( 'testemunhos_dontcreatenewtestemunho' );
		}
			
		$approved = $this->settings['testemunhos_moderatenew'] ? 0 : 1;
		
		
		if ( $type == 'add' )
		{
            $cid = intval($this->request['cid']);
			$this->DB->insert( 'testemunhos', array( 't_title'     => $t_name,
													 't_title_seo' => IPSText::makeSeoTitle( $t_name ),
													 't_cat'	   => $cid,
							 						 't_comments'  => 0,
													 't_views'     => 0,
													 't_date'      => time(),
													 't_last_comment' => time(),
													 't_rating'    => $t_avaliacao,
													 't_approved'  => $approved,
													 't_open'      => 1,
													 't_member_id' => $this->memberData['member_id'],
													 't_content'   => $message,
													 't_htmlstate' => 0,
													 't_ip_address'=> $this->member->ip_address,
			) );

			$testemunho = $this->DB->getInsertId();
			
			/* Quer receber notificações por e-mail ? */
			if ( $this->request['enabletrack'] )
			{
				$this->DB->insert( 'testemunhos_tracker ',
											array( 'member_id'  => $this->memberData['member_id'],
												   't_id' 	    => $testemunho,
												   'start_date' => time(),
							 					   'type'  	    => 'email',
				) );
			}
					
			if ( $approved )
			{
				
				if ( $this->settings['testemunhos_topic'] )
				{
					$this->library->createTopic( $testemunho );
				}
				
				$stats = $this->cache->getCache('testemunhos');
				$stats['approved']++;
				$this->cache->setCache( 'testemunhos', $stats, array( 'array' => 1, 'deletefirst' => 1 ) );
				$this->registry->output->redirectScreen( $this->lang->words['thankyou'], $this->settings['base_url'] . "app=testimonials&showtestimonial=".$testemunho );
			}
			else
			{
				$stats = $this->cache->getCache('testemunhos');
				$stats['unapproved']++;
				$this->cache->setCache( 'testemunhos', $stats, array( 'array' => 1, 'deletefirst' => 1 ) );
				$this->registry->output->redirectScreen( $this->lang->words['thankyou_mod'], $this->settings['base_url'] . "app=testimonials&showtestimonial=".$testemunho );
			}
		}
		else
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'testemunhos', 'where' => 't_id=' . $id ) );
			$this->DB->execute();
			
			if ( $this->DB->getTotalRows() == 1 )
			{
				$append = $this->memberData['sostestemunhos_remove_edit_time'] ? $append = $this->request['showEditLine'] == 1 ? 1 : 0 : 1;
				
				$this->DB->update( 'testemunhos', array( 't_title' => $t_name, 't_rating' => $t_avaliacao, 't_content' => $message, 't_append_edit' => $append, 't_append_edit_time' => time(), 't_append_edit_author' => $this->memberData['members_display_name'] ), 't_id=' . $id );
				
				$this->registry->output->redirectScreen( $this->lang->words['thankyou_edit'], $this->settings['base_url'] . "app=testimonials&showtestimonial=" . $id );
			}
			else
			{
				$this->registry->getClass('output')->showError( 'nonexisting_testemunho' );
			}
		}
	}
}