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

class testemunhosLibrary
{
	public $registry;
	public $DB;
	public $settings;
	public $request;
	public $lang;
	public $member;
	public $memberData;
	public $cache;
	public $caches;

	/**
    * Constructor
    *
    * @access	public
    * @param	object		ipsRegistry reference
    * @return	void
    */
	public function __construct( $registry )
	{
		/* Make registry objects */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();

	}

	public function rebuildTestemunhosCache()
	{
		$cache = array();
		
		/* Cache: Testemunhos aprovados */
		$t_approved = $this->DB->buildAndFetch( array(
										 'select'   => 'count(t_id) as totalA',
								 		 'from'     => 'testemunhos',
								 		 'where'    => 't_approved = 1',
		) );

		$cache['approved'] = $t_approved['totalA'];

		/* Cache: Testemunhos não aprovados */
		$t_unapproved = $this->DB->buildAndFetch( array(
										 'select'   => 'count(t_id) as totalB',
								 		 'from'     => 'testemunhos',
								 		 'where'    => 't_approved = 0',
		) );
		
		$cache['unapproved'] = $t_unapproved['totalB'];
		
		/* Cache: Total de Visualizações */
		$t_totalviews = $this->DB->buildAndFetch( array(
										 'select'   => 'sum( t_views ) as total',
								 		 'from'     => 'testemunhos',
								 		 'where'    => 't_approved = 1',
		) );
		
		$cache['views'] = $t_totalviews['total'];

		/* Cache: Total de Comentários */
		$t_totalcomm = $this->DB->buildAndFetch( array(
										 'select'   => 'sum( t_comments ) as total',
								 		 'from'     => 'testemunhos',
								 		 'where'    => 't_approved = 1',
		) );
		
		$cache['comments'] = $t_totalcomm['total'];

		/* Cache: Informações da Útima Postagem */
		$last = $this->DB->buildAndFetch( array(
										 'select'   => 't.*',
										 'from'     => array( 'testemunhos' => 't' ),
										 'where'    => 't.t_approved = 1',
										 'add_join' => array( 0 => array( 'select' => 'm.members_display_name, m.member_group_id, m.members_seo_name',
																		  'from'   => array( 'members' => 'm' ),
																		  'where'  => 'm.member_id=t.t_member_id',
																		  'type'   => 'left' ) ),
										 'order'	=> 't.t_date desc',
										 'limit'	=> array(0,1)
		) );
		
		$cache['last_date']			  = $last['t_date'];
		$cache['last_author_id']	  = $last['t_member_id'];
		$cache['last_author_name']    = $last['members_display_name'];
		$cache['last_author_seoname'] = $last['members_seo_name'];
		$cache['last_author_group']   = $last['member_group_id'];
				
		$this->cache->setCache( 'testemunhos', $cache, array( 'array' => 1, 'deletefirst' => 1 ) );
	}

	public function checkPermissions()
	{
		/* Arquivos de Linguagem */
		$this->registry->class_localization->loadLanguageFile( array( 'public_errors', 'testemunhos' ) );

		/* Crítica: podemos visualizar a aplicação? */		
		if ( !$this->memberData['sostestemunhos_view'] )
		{
			$this->registry->output->showError( $this->lang->words['testemunhos_nopermission'] );
		}

		/* Crítica: o usuário está banido? */		
		if ( $this->memberData['sostestemunhos_banned'] )
		{
			$this->registry->output->showError( $this->lang->words['testemunhos_userbanned'] );
		}
	}

	public function checkModerator()
	{
		$this->checkPermissions();
		
		/* Moderador? */
		if ( !$this->memberData['sostestemunhos_aprovar'] AND !$this->memberData['sostestemunhos_fechar'] AND !$this->memberData['sostestemunhos_destacar'] AND !$this->memberData['sostestemunhos_remover_testemunho'] AND !$this->memberData['sostestemunhos_remover_comentario'] AND !$this->memberData['sostestemunhos_banir_membros'] )
		{
			$this->registry->output->showError( 'testemunho_no_staff' );
		}
	}

	public function sendOutput()
	{
		/* Setup page title */
		$this->pageTitle = $this->pageTitle ? $this->pageTitle : $this->caches['app_cache']['testimonials']['app_public_title'];
		
		/* Crítica: a aplicação está online? */
	 	if ( !$this->settings['testemunhos_systemon'] )
		{
			if ( !in_array( $this->memberData['member_group_id'], explode(',', $this->settings['testemunhos_gruposoffline'] ) ) )
	 		{
				$this->registry->output->showError( $this->lang->words['testemunhos_offline'] );
			}
			else
			{
				$this->pageTitle = $this->lang->words['testemunhos_warnoffline']." ".$this->pageTitle;
			}
		}
		
		$this->pageTitle .= " - ".$this->settings['board_name'];
		
		/* Send Output */
		$this->registry->output->setTitle( $this->pageTitle );
		$this->registry->output->addContent( $this->pageOutput.$this->cr() );
		$this->registry->output->sendOutput();
	}

	public function getActiveUsers()
	{
		/* Init */
		$cut_off = time() - ( ($this->settings['au_cutoff'] != "") ? $this->settings['au_cutoff'] * 60 : 900 );
		$rows    = array();
		$active  = array( 'TOTAL'   => 0 ,
						  'NAMES'   => array(),
						  'GUESTS'  => 0 ,
						  'MEMBERS' => 0 ,
						  'ANON'    => 0 ,
						);
		$ar_time = time();
		$cached  = array();
		
		if ( $this->settings['testemunhos_showonlineusers'] )
		{
			if ( $this->memberData['member_id'] )
			{
				$rows = array( $ar_time => array( 'login_type'   => substr( $this->memberData['login_anonymous'],0, 1 ),
												  'running_time' => $ar_time,
												  'member_id'    => $this->memberData['member_id'],
												  'member_name'  => $this->memberData['members_display_name'],
												  'seo_name'     => $this->memberData['members_seo_name'],
												  'member_group' => $this->memberData['member_group_id'] 
				) );
			}
			
			$this->DB->build( array( 'select' => 'id, member_id, member_name, seo_name, login_type, running_time, member_group',
									 'from'   => 'sessions',
									 'where'  => "current_appcomponent='testemunhos' AND running_time > $cut_off",
							)      );
			$this->DB->execute();
			
			while ( $r = $this->DB->fetch() )
			{
				if ( $r['member_id'] > 0 && $r['member_id'] == $this->memberData['member_id'] )
				{
					continue;
				}
				
				$rows[ $r['running_time'].'.'.$r['id'] ] = $r;
			}
			
			krsort( $rows );
			
			foreach ( $rows as $result )
			{
				$last_date = $this->registry->getClass('class_localization')->getTime( $result['running_time'] );
				
				if ( strstr( $result['id'], '_session' ) )
				{
					$botname = preg_replace( '/^(.+?)=/', "\\1", $result['id'] );
					
					if ( !$cached[ $result['member_name'] ] )
					{
						if ( $this->settings['spider_anon'] )
						{
							if ( $this->memberData['g_access_cp'] )
							{
								$active['NAMES'][] = "{$result['member_name']}*";
							}
						}
						else
						{
							$active['NAMES'][] = "{$result['member_name']}";
						}
						
						$cached[ $result['member_name'] ] = 1;
					}
					else
					{
						$active['GUESTS']++;
					}
				}
				
				else if ( !$result['member_id'] )
				{
					$active['GUESTS']++;
				}			
				else
				{
					if ( empty( $cached[ $result['member_id'] ] ) )
					{
						$cached[ $result['member_id'] ] = 1;
						
						$result['prefix'] = $this->settings['testemunhos_colorusernames'] ? $this->caches['group_cache'][ $result['member_group'] ]['prefix'] : "";
						$result['suffix'] = $this->settings['testemunhos_colorusernames'] ? $this->caches['group_cache'][ $result['member_group'] ]['suffix'] : "";
						
						if ( $result['login_type'] )
						{
							if ( $this->memberData['g_access_cp'] && ( $this->settings['disable_admin_anon'] != 1 ) )
							{
								$active['NAMES'][] = "<a href='" . $this->registry->getClass('output')->buildSEOUrl( "showuser={$result['member_id']}", 'public', $result['seo_name'], 'showuser' ) ."' title='$last_date'>{$result['prefix']}{$result['member_name']}{$result['suffix']}</a>*";
								$active['ANON']++;
							}
							else
							{
								$active['ANON']++;
							}
						}
						else
						{
							$active['MEMBERS']++;
							$active['NAMES'][] = "<a href='" . $this->registry->getClass('output')->buildSEOUrl( "showuser={$result['member_id']}", 'public', $result['seo_name'], 'showuser' ) ."' title='$last_date'>{$result['prefix']}{$result['member_name']}{$result['suffix']}</a>";
						}
					}
				}
			}
			
			$active['TOTAL'] = $active['MEMBERS'] + $active['GUESTS'] + $active['ANON'];
			
			$this->lang->loadLanguageFile( array( 'public_boards' ), 'forums' );
			$this->lang->words['active_users'] = sprintf( $this->lang->words['active_users'], $this->settings['au_cutoff'] );
			
			$this->lang->words['active_users_header'] = sprintf( $this->lang->words['active_users_header'], $this->settings['au_cutoff'] );
			
			return $active;
		}
	}


	/**
	 * General function to make dropdowns
	 * 
	 * @author	Terabyte
	 * @access	public
	 * @param	string		Contains the name of the dropdown
	 * @param	array		Contains the options for the dropdown
	 * @param	string		Selected variable in the dropdown
	 * @return	string		Returns an HTML select tag
	 */
	public function makeDropdown( $selectName, $options, $selected='' )
	{
		/* Init vars */
		$html = "<select class='input_select' name='{$selectName}' id='{$selectName}'>\n";
		
		/* Add normal options */
		foreach ( $options as $value => $name )
		{
			$html .= "<option value='{$value}'";
			$html .= $selected == $value ? " selected='selected'" : "";
			$html .= ">{$name}</option>\n";
		}
		
		$html .= "</select>";
		
		return $html;
	}

	public function makeNameFormatted( $name="", $group=0 )
	{
		/* Format names? */
		if ( $this->settings['testemunhos_colorusernames'] )
		{
			return IPSMember::makeNameFormatted( $name, intval($group) );
		}
		else
		{
			return $name;
		}
	}

	public function getAvaliacaoDrop()
	{
		$html = "<select class='input_select' name='avaliacao'>";
		$number = 0;

		for ( $number = 0; $number < 6; $number++ )
		{
			$html .= "<option value='".$number."'";
			$html .= ">".$number."</option>";
		}
					
		$html .= "</select>";
		
		return $html;
	}

	public function addModLog( $action, $testemunho )
	{
		$this->DB->insert( 'testemunhos_modlogs',
						   array( 'member_id'     => $this->memberData['member_id'],
						   		  'name'		  => $this->memberData['members_display_name'],
								  'ip_address'    => $this->member->ip_address,
								  'datetime'      => time(),
								  't_id'		  => $testemunho['t_id'],
								  't_title'		  => $testemunho['t_title'],
								  'action'        => $action,
							),
						   true
		);
	}

	final public function cr()
	{
		$str = "<div style='margin-top:10px;font-size:0.85em;' class='desc right'><p id='copyright' class='right'>Powered by <strong>{$this->caches['app_cache']['testimonials']['app_title']} {$this->caches['app_cache']['testimonials']['app_version']}</strong> &copy;".date("Y")."&nbsp;<a href='http://rawcodes.net' title='Rawcodes.net' target='_blank'>Rawcodes.net</a></p></div>";
		return $str;
	}

 	public function createTopic( $tid )
 	{
		$testemunho = $this->DB->buildAndFetch( array(
										 'select'   => 't_id, t_member_id, t_title, t_topicid',
								 		 'from'     => 'testemunhos',
								 		 'where'    => 't_id='.$tid,
		) );

		if ( !$testemunho['t_topicid'] )
		{
			require_once( IPSLib::getAppDir('forums') . '/sources/classes/moderate.php' );
			$this->moderatorLibrary	=  new moderatorLibrary( $this->registry );
	
			require_once( IPSLib::getAppDir('forums') . '/sources/classes/post/classPost.php' );
			$this->post				=  new classPost( $this->registry );
			
			$autortopico	= IPSMember::load( $this->settings['testemunhos_topicauthor'] );
			$autortest		= IPSMember::load( $testemunho['t_member_id'] );
	
			$find     = array( "{author}" );
			$replace  = array( $autortest['members_display_name'] );
		        
			$titulo	  = str_replace( $find, $replace, $this->settings['testemunhos_topictitle'] );
		
			$this->topic = array(
								'title'				=> $titulo,
								'state'				=> $this->settings['testemunhos_topicstate'],
								'posts'				=> 0,
								'starter_id'		=> $this->settings['testemunhos_topicauthor'],                           
								'starter_name'		=> $autor['members_display_name'],
								'start_date'		=> time(),
								'last_poster_id'	=> $this->settings['testemunhos_topicauthor'],
								'last_poster_name'	=> $autor['members_display_name'],
								'last_post'			=> time(),
								'author_mode'		=> 1,
								'poll_state'		=> 0,
								'last_vote'			=> 0,
								'views'				=> 0,
								'forum_id'			=> $this->settings['testemunhos_topicforum'],
								'approved'			=> 1,
								'pinned'			=> 0 
			);
		
			$this->DB->insert( 'topics', $this->topic );
			$this->topic['tid'] = $this->DB->getInsertId();


            $find2    = array( "{title}", "{url}", "{author}" );
			$replace  = array( $testemunho['t_title'], $this->settings['board_url']."/index.php?app=testimonials&showtestimonial=".$tid, "[b]".$autortest['members_display_name']."[/b]" );
		
	        $mensagem = str_replace( $find2, $replace, $this->settings['testemunhos_topictemplate'] );
			 
			$post = array(
						'author_id'			=> $this->settings['testemunhos_topicauthor'],
						'use_sig'			=> 1,
						'use_emo'			=> 1,
						'ip_address'		=> $autor['ip_address'],
						'post_date'			=> time(),
						'post'				=> $mensagem,
						'author_name'		=> $autor['members_display_name'],
						'topic_id'			=> $this->topic['tid'],
						'queued'			=> 0,
						'post_htmlstate'	=> 0,
						'post_key'			=> md5( microtime() ),
			);
					 
			$this->DB->insert( 'posts', $post );

			$this->DB->update( 'testemunhos', array( 't_topicid' => $this->topic['tid'] ), 't_id='.$tid );
			$this->moderatorLibrary->rebuildTopic( $this->topic['tid'], false );
						
			$this->moderatorLibrary->forumRecount( $this->topic['forum_id'] );			
            $this->cache->rebuildCache( 'stats', 'global' );
		}
	}

	public function fetchTestemunhoFolderIcon( $testemunho, $showDot=false, $isRead=false )
	{
		/* Init vars */
		$image = '';
		$isHot = '';
		$isDot = '';
		
		/* Sort ticket state */
		if ( $testemunho['t_open'] == 0 )
		{
			$image = "closed";
		}
		else
		{
			/* Is Hot? */
			if ( ($testemunho['t_comments'] + 1) >= $this->settings['hot_topic'] )
			{
				$isHot = "hot_";
			}

			/* We read it? */
			if ( $isRead )
			{
				$image = "read";
			}
			else
			{
				$image = "unread";
			}
			
			/* We replied? */
			if ( $showDot )
			{
				$isDot = '_dot';
			}
		}

		return "t_".$isHot.$image.$isDot;
	}
	
	public function generatePostPreview( $postContent="" )
    {

		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ) );

    	IPSText::getTextClass( 'bbcode' )->parse_html      			= $this->settings['testemunhos_html'];
		IPSText::getTextClass( 'bbcode' )->parse_nl2br     			= 1;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode    			= $this->settings['testemunhos_bbcode'];
		IPSText::getTextClass( 'bbcode' )->parse_smilies   			= $this->settings['testemunhos_emoticons'];
		IPSText::getTextClass( 'bbcode' )->parsing_section 			= 'testemunhos_submit';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];
		
		# Make sure we have the pre-display look
	    //$postContent = IPSText::getTextClass( 'bbcode' )->preDisplayParse(  $postContent  );
        $postContent = IPSText::getTextClass( 'bbcode' )->preDisplayParse( IPSText::getTextClass( 'bbcode' )->preDbParse( $postContent ) );

		
		return $postContent;
    }

	public function enviarMP( $titulo, $text, $user, $testemunho )
	{

        $classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );

        $editor = new $classToLoad();

        $mensagem = $editor->process( $text );
	
		$titulo  = stripslashes( $titulo );

		IPSText::getTextClass('bbcode')->parse_html     = 0;
        IPSText::getTextClass('bbcode')->parse_nl2br    = 1;
        IPSText::getTextClass('bbcode')->parse_smilies  = 1;
        IPSText::getTextClass('bbcode')->parse_bbcode   = 1;
        IPSText::getTextClass('bbcode')->parsing_section = 'pms';

		$mensagem  = IPSText::getTextClass('bbcode')->preEditParse( $mensagem );
		$template  = IPSText::getTextClass('bbcode')->preEditParse( $text );
        
        require_once( IPSLib::getAppDir( 'members' ).'/sources/classes/messaging/messengerFunctions.php' );
        $messengerFunctions = new messengerFunctions( $this->registry );
        
        $dadosuser = IPSMember::load( $user, 'all', 'id' );

		$find  = array( "{name}", "{testimonial}" );
       	$replace = array( $dadosuser['members_display_name'], $testemunho );
        
        $mensagem = str_replace( $find, $replace, $template );

		$autor = $this->settings['testemunhos_autormps'] == 1 ? $this->settings['testemunhos_autormps'] : $this->memberData['member_id'];

        try
        {
        	$messengerFunctions->sendNewPersonalTopic(  $user,
		                                        		$autor,
		                                        		array(),
		                                        		$titulo,
		                                        		IPSText::getTextClass( 'editor' )->method == 'rte' ? nl2br($mensagem) : $mensagem,
                                                		array(  'origMsgID'			=> 0,
						                        				'fromMsgID'         => 0,
						                    					'postKey'           => md5(microtime()),
						                        				'trackMsg'          => 0,
						                        				'addToSentFolder'	=> 1,
						                        				'hideCCUser'        => 0,
						                        				'forcePm'           => 1,
						                        				'isSystem'          => ( $this->settings['testemunhos_reply'] == 1 ) ? FALSE : TRUE,
														)
			);
        }
        
        catch( Exception $error )
        {
			$msg		= $error->getMessage();
			$toMember	= IPSMember::load( $this->warn_member['member_id'], 'core' );
				   
			if ( strstr( $msg, 'BBCODE_' ) )
			{
				$msg = str_replace( 'BBCODE_', '', $msg );
	
				$this->registry->output->showError( $msg, 10252 );
			}
			else if ( isset($this->lang->words[ 'err_' . $msg ]) )
			{
				$this->lang->words[ 'err_' . $msg ] = $this->lang->words[ 'err_' . $msg ];
				$this->lang->words[ 'err_' . $msg ] = str_replace( '#NAMES#'   , implode( ",", $messengerFunctions->exceptionData ), $this->lang->words[ 'err_' . $msg ] );
				$this->lang->words[ 'err_' . $msg ] = str_replace( '#TONAME#'  , $toMember['members_display_name']    , $this->lang->words[ 'err_' . $msg ] );
				$this->lang->words[ 'err_' . $msg ] = str_replace( '#FROMNAME#', $this->memberData['members_display_name'], $this->lang->words[ 'err_' . $msg ] );
				
				$this->registry->output->showError( 'err_' . $msg, 10253 );
			}
			else if( $msg != 'CANT_SEND_TO_SELF' )
			{
				$_msgString = $this->lang->words['err_UNKNOWN'] . ' ' . $msg;
				$this->registry->output->showError( $_msgString, 10254 );
			}
        }
	}

	public function enviarEmails( $testemunho=array() )
	{
		if ( !$testemunho['t_id'] )
		{
			return FALSE;
		}
		
		$reg = $this->DB->buildAndFetch( array( 'select' => 't_id', 'from' => 'testemunhos_tracker', 'where' => 't_id='.$testemunho['t_id'] ) );
		
		if ( !$reg['t_id'] )
		{
			return FALSE;
		}
		
		$count = 0;
		
		$lastComm = $this->DB->buildAndFetch( array( 'select'   => 'c.cid, c.member_id',
								 					 'from'     => array( 'testemunhos_comments' => 'c'),
								 					 'where'    => 'c.tid='.$testemunho['t_id'],
								 					 'add_join' => array( array( 'select' => ' m.members_display_name',
															 'from'   => array( 'members' => 'm' ),
															 'where'  => 'm.member_id=c.member_id',
															 'type'   => 'left' ) ),
								 					 'order'	=> 'date DESC',
								 					 'limit'	=> array(0,1)
		) );
		
		/* Get data from DB */
		$this->DB->build( array( 'select'   => 'tr.*',
								 'from'     => array( 'testemunhos_tracker' => 'tr' ),
								 'where'    => "tr.t_id=".$testemunho['t_id']." AND tr.member_id != ".$lastComm['member_id'],
								 'add_join' => array( array( 'select' => 'm.member_id, m.members_display_name, m.email, m.email_full, m.language, m.member_group_id, m.mgroup_others',
															 'from'   => array( 'members' => 'm' ),
															 'where'  => 'm.member_id=tr.member_id',
															 'type'   => 'left' ) ),
								 
		) );
		
		$outer = $this->DB->execute();
		
		if ( $this->DB->getTotalRows($outer) )
		{
			while ( $r = $this->DB->fetch($outer) )
			{
				/* Group cannot view */
				if ( !$this->caches['group_cache'][ $r['member_group_id'] ]['sostestemunhos_view'] )
				{
					continue;
				}
				
				$count++;
				
				$r['language'] = $r['language'] ? $r['language'] : '';
				
				IPSText::getTextClass('email')->getTemplate( 'messsage_email_newcomment', $r['language'], 'public_testemunhos', 'testemunhos' );
				
				IPSText::getTextClass('email')->buildMessage( array( 'TID'  	  => $testemunho['t_id'],
																	 'POST'       => $this->settings['board_url'].'/index.php?app=testimonials&module=testemunhos&section=findpost&id='.$lastComm['cid'],
																	 'TITLE'      => $testemunho['t_title'],
																	 'POSTER'     => $lastComm['members_display_name'],
																	 'NAME'       => $r['members_display_name']
				) );

				IPSText::getTextClass( 'email' )->subject	= $this->lang->words['subject_email_newcomment'];
				IPSText::getTextClass( 'email' )->to		= $r['email'];
	
				IPSText::getTextClass( 'email' )->sendMail();
			}
		}

		return TRUE;
	}

}
?>