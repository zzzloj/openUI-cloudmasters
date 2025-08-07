<?php

/**
 * Product Title:		(SOS33) Easy Topic Moderation
 * Product Version:		3.0.0
 * Author:				Adriano Faria
 * Website:				SOS Invision
 * Website URL:			http://forum.sosinvision.com.br/
 * Email:				administracao@sosinvision.com.br
 */
 
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_advanced_manage extends ipsCommand
{

	protected $_topicTitle = '';

	public function doExecute( ipsRegistry $registry )
	{
        switch( $this->request['do'] )
        {
			case 'saveNewAuthor':
				$this->_saveNewAuthor();
			break;
			case 'copyTopic':
				$this->_coyTopicForm();
			break;
			case 'doCopyTopic':
				$this->_doCopyTopic();
			break;
			
        	default:
        		$this->_showChangeAuthorForm();
        	break;
        }

		$this->registry->output->addContent( $this->output );
		$this->registry->getClass('output')->sendOutput();
	}

    public function _showChangeAuthorForm()
    {
		if ( !isset( $this->request['f']) OR $this->request['f'] == '' )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}

		if ( !isset( $this->request['t']) OR $this->request['t'] == '' )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}
		
		if ( !$this->memberData['g_access_cp'] )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}

		if ( $this->member->form_hash != $this->request['auth_key'] )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_advanced' ) );
		$this->registry->class_localization->loadLanguageFile( array( 'public_mod'      ) );

		$tid = $this->request['t'];
		
		$topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.$tid ) );

		$pesquisa = $this->DB->buildAndFetch( array( 'select' => 'count(pid) as cnt', 'from' => 'posts', 'where' => "author_id=".$topic['starter_id']." AND topic_id=".$topic['tid'] ) );

		if ( $topic['posts'] == 0 )
		{
			$estado = 'metade';
		}
		else if ( $this->topic['posts'] > 0 AND $pesquisa['cnt'] > 1 )
		{
			$estado = 'completo';
		}

		$template = $this->registry->output->getTemplate('topic')->sos32_enhancedtopics_changeauthorform( $topic, $estado );

		$this->registry->output->setTitle( $forum['name'] . ' - ' . $this->settings['board_name']);
		$this->registry->output->addContent( $template );

		foreach( $this->registry->class_forums->forumsBreadcrumbNav( $topic['forum_id'] ) as $_nav )
		{
			$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1] );
		}

		$this->registry->getClass('output')->addNavigation( $topic['title'], "{$this->settings['_base_url']}showtopic={$topic['tid']}" );
		$this->registry->getClass('output')->setTitle( $this->lang->words['mudar_autoria']." -> ".$topic['title'] );	
    }

	public function _saveNewAuthor()
	{
		$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_advanced' ), 'forums' );

		if ( !isset( $this->request['f']) OR $this->request['f'] == '' )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}

		if ( !isset( $this->request['t']) OR $this->request['t'] == '' )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}
		
		if ( !$this->memberData['g_access_cp'] )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}

		if ( $this->member->form_hash != $this->request['auth_key'] )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}
			
		$tid = intval( $this->request['t'] );
		
		if ( !isset($this->request['newauthor']) OR $this->request['newauthor'] == "" )
		{
			$this->registry->getClass( 'output' )->showError( 'sem_novo_autor' );
		}

		$usuario = $this->DB->buildAndFetch( array( 'select' => 'member_id, members_display_name', 'from' => 'members', 'where' => "members_display_name='{$this->request['newauthor']}'" ) );

		if ( $usuario['member_id'] == "" )
		{
			$this->registry->getClass( 'output' )->showError( 'autor_inexistente' );
		}

		$this->topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.$tid ) );
		
		if ( $this->request['newauthor'] == $this->topic['starter_name'] )
		{
			$this->registry->getClass( 'output' )->showError( 'autores_iguais' );
		}
		
		$posts = 0;
		$autor_original = $this->topic['starter_id'];
		$autor_lastpost = $this->topic['last_poster_id'];

		//atualiza tópico: nova autoria
		$this->DB->update( 'topics', array( 'starter_id' => $usuario['member_id'], 'starter_name' => $usuario['members_display_name'] ), 'tid='.( $this->topic['tid'] ) );
		$posts = 1;

		//atualiza tópico: post único
		if ( !isset( $this->request['mudar'] ) )
		{
			$posts = $posts + 1;
			$this->DB->update( 'topics', array( 'last_poster_id' => $usuario['member_id'], 'last_poster_name' => $usuario['members_display_name'], ), 'tid='.( $this->topic['tid'] ) );
			$this->DB->update( 'posts', array( 'author_id' => $usuario['member_id'], 'author_name' => $usuario['members_display_name'], ), 'pid='.( $this->topic['topic_firstpost'] ) );
		
			if ( $this->registry->class_forums->forum_by_id[ $this->topic['forum_id'] ]['last_id']== $this->topic['tid'] AND  $this->registry->class_forums->forum_by_id[ $this->topic['forum_id'] ]['last_poster_id'] == $autor_original )
			{
				$this->DB->update( 'forums', array( 'last_poster_id' => $usuario['member_id'], 'last_poster_name' => $usuario['members_display_name'], ), 'id='.( $this->topic['forum_id'] ) );
				
				require_once( IPSLib::getAppDir('forums') . '/sources/classes/moderate.php' );
				$moderatorLibrary    =  new moderatorLibrary( $this->registry );
				
				$moderatorLibrary->rebuildTopic( $this->topic['tid'], false );
				$moderatorLibrary->forumRecount( $this->topic['forum_id'] );
				$this->cache->rebuildCache( 'stats', 'global' );
			}
		}
		else
		{
			if ( $this->request['mudar'] == "n" )
			{
				$this->DB->update( 'posts', array( 'author_id' => $usuario['member_id'], 'author_name' => $usuario['members_display_name'], ), 'pid='.( $this->topic['topic_firstpost'] ) );
			}
			else
			{
			 	$pesquisa = $this->DB->buildAndFetch( array( 'select' => 'count(pid) as cnt', 'from' => 'posts', 'where' => "author_id=".$autor_original." AND topic_id=".$this->topic['tid']." AND new_topic = 0" ) );
			 	$posts =  $posts + $pesquisa['cnt'];
			 	
				$this->DB->update( 'posts', array( 'author_id' => $usuario['member_id'], 'author_name' => $usuario['members_display_name'], ), "topic_id={$this->topic['tid']} AND author_id={$autor_original}" );
				
				$lastpost = $this->DB->buildAndFetch( array( 'select' => 'pid, author_id, topic_id', 'from' => 'posts', 'where' => "topic_id={$this->topic['tid']}", 'order' => 'post_date DESC', 'limit' => array( 0,1 ) ) );
				
				$this->DB->update( 'topics', array( 'last_poster_id' => $usuario['member_id'], 'last_poster_name' => $usuario['members_display_name'], ), 'tid='.( $lastpost['topic_id'] ) );
			}
			
			if ( $this->registry->class_forums->forum_by_id[ $this->topic['forum_id'] ]['last_id']== $this->topic['tid'] AND  $this->registry->class_forums->forum_by_id[ $this->topic['forum_id'] ]['last_poster_id'] == $autor_original )
			{
				$this->DB->update( 'forums', array( 'last_poster_id' => $usuario['member_id'], 'last_poster_name' => $usuario['members_display_name'], ), 'id='.( $this->topic['forum_id'] ) );
				
				require_once( IPSLib::getAppDir('forums') . '/sources/classes/moderate.php' );
				$moderatorLibrary    =  new moderatorLibrary( $this->registry );
				
				$moderatorLibrary->rebuildTopic( $this->topic['tid'], false );
				$moderatorLibrary->forumRecount( $this->topic['forum_id'] );
				$this->cache->rebuildCache( 'stats', 'global' );
			}
		}

		//atualiza os posts do ANTIGO e do NOVO autor do tópico
		$posts_orig = IPSMember::load( $autor_original );
		$this->DB->update( 'members', array( 'posts' => $posts_orig['posts']-$posts ), 'member_id='.( $autor_original ) );
		$posts_new = IPSMember::load( $usuario['member_id'] );
		$this->DB->update( 'members', array( 'posts' => $posts_new['posts']+$posts ) , 'member_id='.( $usuario['member_id'] ) );

		$url = "showtopic=".$this->topic['tid']."&st=".intval($this->request['st']);
		$this->registry->output->redirectScreen( $this->lang->words['autoria_alterada'], $this->settings['base_url'] . $url );
	}

    public function _coyTopicForm()
    {
		if ( !isset( $this->request['f']) OR $this->request['f'] == '' )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}

		if ( !isset( $this->request['t']) OR $this->request['t'] == '' )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}

		if ( !$this->memberData['is_mod'] )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}

		if ( $this->member->form_hash != $this->request['auth_key'] )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}

		$this->registry->class_localization->loadLanguageFile( array( 'public_advanced' ), 'forums' );

		$tid = intval( $this->request['t'] );
		
		$this->topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.$tid ) );
		
		$jump_html = $this->registry->getClass('class_forums')->buildForumJump( 0,0,1 );
			
		$template = $this->registry->output->getTemplate( 'topic' )->sos32_enhancedtopics_copyform( $this->topic, $jump_html );

		$this->registry->output->setTitle( $forum['name'] . ' - ' . $this->settings['board_name']);
		$this->registry->output->addContent( $template );

		foreach( $this->registry->class_forums->forumsBreadcrumbNav( $this->topic['forum_id'] ) as $_nav )
		{
			$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1] );
		}

		$this->registry->getClass('output')->addNavigation( $this->topic['title'], "{$this->settings['_base_url']}showtopic={$this->topic['tid']}" );
		$this->registry->getClass('output')->setTitle( $this->lang->words['top_copy']." -> ".$this->topic['title'] );	
	}

    public function _doCopyTopic()
    {
		$this->registry->class_localization->loadLanguageFile( array( 'public_advanced' ), 'forums' );

		if ( !isset( $this->request['f']) OR $this->request['f'] == '' )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}

		if ( !isset( $this->request['t']) OR $this->request['t'] == '' )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}
		
		if ( !$this->memberData['is_mod'] )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}

		if ( $this->member->form_hash != $this->request['auth_key'] )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}
		
		if ( $this->registry->class_forums->forum_by_id[ $this->request['copy_to'] ]['parent_id'] == '-1' OR $this->registry->class_forums->forum_by_id[ $this->request['copy_to'] ]['redirect_on'] OR $this->registry->class_forums->forum_by_id[ $this->request['copy_to'] ]['sub_can_post'] == 0 )
		{
			$this->registry->getClass( 'output' )->showError( 'no_permission' );
		}

		$tid = intval( $this->request['t'] );
		
		$this->topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.$tid ) );
		
		if ( $this->topic['tid'] )
		{
			require_once( IPSLib::getAppDir('forums') . '/sources/classes/moderate.php' );
			$this->moderatorLibrary	=  new moderatorLibrary( $this->registry );
	
			require_once( IPSLib::getAppDir('forums') . '/sources/classes/post/classPost.php' );
			$this->post				=  new classPost( $this->registry );

			$title 		 = $_POST['novo_titulo'] ? $this->setTopicTitle( $_POST['novo_titulo'] ) : $this->topic['title'];		

			if ( $_POST['novo_autor'] )
			{
				$newauthor = IPSMember::load( $_POST['novo_autor'], 'all', 'displayname' );
				$new_id    = $newauthor['member_id'];
				$new_name  = $newauthor['members_display_name'];
			}
			else
			{
				$new_id    = $this->topic['starter_id'];
				$new_name  = $this->topic['starter_name'];
			}

	
			$this->newtopic = array(
								'title'				=> $title,
								'state'				=> $this->request['topic_state'] != '-1' ? $this->request['topic_state'] : $this->topic['state'],
								'posts'				=> 0,
								'starter_id'		=> $new_id,
								'starter_name'		=> $new_name,
								'start_date'		=> time(),
								'last_poster_id'	=> $new_id,
								'last_poster_name'	=> $new_name,
								'last_post'			=> time(),
								'author_mode'		=> 1,
								'poll_state'		=> 0,
								'last_vote'			=> 0,
								'views'				=> 0,
								'forum_id'			=> $this->request['copy_to'],
								'approved'			=> $this->request['topic_approved'] != '-1' ? $this->request['topic_approved'] : $this->topic['approved'],
								'pinned'			=> $this->request['topic_pinned'] != '-1' ? $this->request['topic_pinned'] : $this->topic['pinned'], 
			);

			$this->DB->force_data_type = array(
								'title'            => 'string',
								'starter_name'     => 'string',
								'seo_first_name'   => 'string',
								'seo_last_name'    => 'string',
								'last_poster_name' => 'string'
			);
		
			$this->DB->insert( 'topics', $this->newtopic );
			$this->newtopic['tid'] = $this->DB->getInsertId();
			
			$mensagem = $this->DB->buildAndFetch( array( 'select' => 'post, ip_address', 'from' => 'posts', 'where' => 'pid='.$this->topic['topic_firstpost'] ) );
				
			$post = array(
						'author_id'			=> $new_id,
						'use_sig'			=> 1,
						'use_emo'			=> 1,
						'ip_address'		=> $mensagem['ip_address'],
						'post_date'			=> time(),
						'post'				=> $mensagem['post'],
						'author_name'		=> $new_name,
						'topic_id'			=> $this->newtopic['tid'],
						'queued'			=> 0,
						'post_htmlstate'	=> 0,
						'post_key'			=> md5( microtime() ),
			);
					 
			$this->DB->insert( 'posts', $post );

			$this->DB->update( 'topics', array( 'topic_firstpost' => $post['pid'] ), 'tid=' . $this->newtopic['tid'] );
			$this->moderatorLibrary->rebuildTopic( $this->topic['tid'], false );
			

			if ( $this->registry->class_forums->forum_by_id[ $this->topic['forum_id'] ]['inc_postcount'] AND $this->request['post_count'] )
			{
				$this->incrementUsersPostCount();
			}
						
			$this->moderatorLibrary->forumRecount( $this->newtopic['forum_id'] );
			$this->cache->rebuildCache( 'stats', 'global' );
		}
		else
		{
			$this->registry->getClass( 'output' )->showError( 'topics_no_tid' );
		}
		
		$url = "showtopic=".$this->newtopic['tid'];
		$this->registry->output->redirectScreen( $this->lang->words['topico_copiado'], $this->settings['base_url'] . $url );
	}

	public function setTopicTitle( $topicTitle )
	{
		if ( $topicTitle )
		{
			$this->_topicTitle = $topicTitle;

			/* Clean */
			if( $this->settings['etfilter_shout'] )
			{
				if( function_exists('mb_convert_case') )
				{
					if( in_array( strtolower( $this->settings['gb_char_set'] ), array_map( 'strtolower', mb_list_encodings() ) ) )
					{
						$this->_topicTitle = mb_convert_case( $this->_topicTitle, MB_CASE_TITLE, $this->settings['gb_char_set'] );
					}
					else
					{
						$this->_topicTitle = ucwords( strtolower($this->_topicTitle) );
					}
				}
				else
				{
					$this->_topicTitle = ucwords( strtolower($this->_topicTitle) );
				}
			}
			
			$this->_topicTitle = IPSText::parseCleanValue( $this->_topicTitle );
			$this->_topicTitle = $this->cleanTopicTitle( $this->_topicTitle );
			$this->_topicTitle = IPSText::getTextClass( 'bbcode' )->stripBadWords( $this->_topicTitle );
			
			/* Unicode test */
			if ( IPSText::mbstrlen( $topicTitle ) > $this->settings['topic_title_max_len'] )
			{
				$this->_postErrors = 'topic_title_long';
			}
		
			if ( (IPSText::mbstrlen( IPSText::stripslashes( $topicTitle ) ) < 2) or ( ! $this->_topicTitle )  )
			{
				$this->_postErrors = 'no_topic_title';
			}		
		}
		
		return $topicTitle;
	}

	public function setTopicDescription( $topicDescription )
	{
		$this->_topicDescription = IPSText::parseCleanValue( $topicDescription );
		$this->_topicDescription = trim( IPSText::getTextClass( 'bbcode' )->stripBadWords( IPSText::stripAttachTag( $this->_topicDescription ) ) );
		$this->_topicDescription = preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?$/", '', IPSText::mbsubstr( $this->_topicDescription, 0, 70 ) );
		
		return $topicDescription;
	}

	public function cleanTopicTitle( $title="" )
	{
		if( $this->settings['etfilter_punct'] )
		{
			$title	= preg_replace( "/\?{1,}/"      , "?"    , $title );		
			$title	= preg_replace( "/(&#33;){1,}/" , "&#33;", $title );
		}

		//-----------------------------------------
		// The DB column is 250 chars, so we need to do true mb_strcut, then fix broken HTML entities
		// This should be fine, as DB would do it regardless (cept we can fix the entities)
		//-----------------------------------------

		$title = preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?$/", '', IPSText::mbsubstr( $title, 0, 250 ) );
		
		$title = IPSText::stripAttachTag( $title );
		$title = str_replace( "<br />", "", $title  );
		$title = trim( $title );

		return $title;
	}

	public function incrementUsersPostCount( $inc=1 )
	{
		$update_sql = array();
		$today      = time() - 86400;
		
		$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count, MIN(post_date) as min',
										  		  'from'   => 'posts',
										  		  'where'  => 'author_id=' . $this->newtopic['starter_id'] . ' AND post_date > ' . $today ) );
										
		$update_sql['members_day_posts'] = intval( $count['count'] ) . ',' . intval( $count['min'] );


		$user = IPSMember::load( $this->newtopic['starter_id'] );

		$update_sql['posts'] = $user['posts'] + intval( $inc );
				
		if ($user['g_promotion'] != '-1&-1')
		{
			if ( ! $user['gbw_promote_unit_type'] )
			{
				list($gid, $gposts) = explode( '&', $user['g_promotion'] );
				
				if ( $gid > 0 and $gposts > 0 )
				{
					if ( $user['posts'] + intval( $inc ) >= $gposts )
					{
						$update_sql['member_group_id'] = $gid;
					}
				}
			}
		}
			
		$update_sql['last_post'] = time();
			
		$this->member->setProperty( 'last_post', time() );
			
		IPSMember::save( $user['member_id'], array( 'core' => $update_sql ) );	
	}

}