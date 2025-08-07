<?php

/**
 * Product Title:		(SOS32) Quick Manage Forums
 * Product Version:		1.0.1
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

class public_forums_manage_manage extends ipsCommand
{
	public function doExecute( ipsRegistry $registry )
	{
        switch( $this->request['do'] )
        {
			case 'saveForm':
				$this->saveForm();
			break;
			
        	default:
        		$this->showForm();
        	break;
        }

		$this->registry->output->addContent( $this->output );
		$this->registry->getClass('output')->sendOutput();
	}

    public function showForm()
    {
		if ( !isset( $this->request['f']) OR $this->request['f'] == '' )
		{
			$this->registry->output->showError( 'no_permission' );
		}

		if ( $this->member->form_hash != $this->request['auth_key'] )
		{
			$this->registry->output->showError( 'no_permission' );
		}
		
		if ( $this->memberData['g_access_cp'] )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_permissions.php', 'class_permissions' );
			$permission	 = new $classToLoad( $this->registry  );

			if ( !$permission->checkPermission( 'forums_edit', 'forums', 'forums' ) )
			{
				$this->registry->output->showError( 'no_permission' );
			}
		}
		else
		{
			$this->registry->output->showError( 'no_permission' );
		}

		$this->registry->class_localization->loadLanguageFile( array( 'admin_forums', 'public_forums' ), 'forums' );

		$forum = $this->registry->getClass('class_forums')->forum_by_id [ $this->request['f'] ];

		$dd_moderate = array(
						 '0' => $this->lang->words['for_no'],
						 '1' => $this->lang->words['for_modall'],
						 '2' => $this->lang->words['for_modtop'],
						 '3' => $this->lang->words['for_modrep']
		);

		foreach ( $dd_moderate as $k => $v )
		{
			if ( $k == $forum['preview_posts'] )
			{
				$moderate .= '<option value="'.$k.'" selected="selected">'.$v.'</option>' . "\n";
			}
			else
			{
				$moderate .= '<option value="'.$k.'">'.$v.'</option>' . "\n";
			}
		}
		
		foreach( $this->caches['group_cache'] as $r )
		{
			$selected = "";

			if ( in_array( $r['g_id'], explode( ",", $forum['password_override'] ) ) )
			{
				$selected = ' selected';
			}

			$groups .= "<option value='".$r['g_id']."'".$selected.">".$r['g_title']."</option>\n";
		}
			
		$template = $this->registry->output->getTemplate('forum')->manageForm( $forum, $moderate, $groups );

		$this->registry->output->setTitle( $forum['name'] . ' - ' . $this->settings['board_name']);
		$this->registry->output->addContent( $template );
		
		$this->nav = $this->registry->class_forums->forumsBreadcrumbNav( $this->request['f'] );

		if ( is_array( $this->nav ) AND count( $this->nav ) )
		{
			foreach( $this->nav as $_nav )
			{
				$this->registry->output->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}
    }

	public function saveForm()
	{
		if ( !isset( $this->request['f']) OR $this->request['f'] == '' )
		{
			$this->registry->output->showError( 'no_permission' );
		}
		
		if ( $this->memberData['g_access_cp'] )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_permissions.php', 'class_permissions' );
			$permission	 = new $classToLoad( $this->registry  );

			if ( !$permission->checkPermission( 'forums_edit', 'forums', 'forums' ) )
			{
				$this->registry->output->showError( 'no_permission' );
			}
		}
		else
		{
			$this->registry->output->showError( 'no_permission' );
		}

		if ( $this->member->form_hash != $this->request['auth_key'] )
		{
			$this->registry->output->showError( 'no_permission' );
		}

		$this->registry->class_localization->loadLanguageFile( array( 'admin_forums' ), 'forums' );

		$save = array(	'name'						=> IPSText::getTextClass('bbcode')->xssHtmlClean( nl2br( IPSText::stripslashes( $_POST['name'] ) ) ),
						'name_seo'					=> IPSText::makeSeoTitle( $this->request['name'] ),
						'description'				=> IPSText::getTextClass('bbcode')->xssHtmlClean( nl2br( IPSText::stripslashes( $_POST['description'] ) ) ),
						'use_ibc'					=> intval($this->request['use_ibc']),
						'use_html'					=> intval($this->request['use_html']),
						'password'					=> $this->request['password'],
						'password_override'			=> is_array($this->request['password_override']) ? implode( ",", $this->request['password_override'] ) : '',
						'preview_posts'				=> intval($this->request['preview_posts']),
						'allow_poll'				=> intval($this->request['allow_poll']),
						'allow_pollbump'			=> intval($this->request['allow_pollbump']),
						'forum_allow_rating'		=> intval($this->request['forum_allow_rating']),
						'inc_postcount'				=> intval($this->request['inc_postcount']),
						'notify_modq_emails'		=> $this->request['notify_modq_emails'],
						'permission_showtopic'		=> $this->request['parent_id'] == -1 ? 1 : intval($this->request['permission_showtopic']),
						'min_posts_post'			=> intval( $this->request['min_posts_post'] ),
						'min_posts_view'			=> intval( $this->request['min_posts_view'] ),
						'can_view_others'			=> intval( $this->request['can_view_others'] ),
						'hide_last_info'			=> intval( $this->request['hide_last_info'] ),
						'disable_sharelinks'		=> intval( $this->request['disable_sharelinks'] ),
						'tag_predefined'			=> $this->request['tag_predefined'],
						'forums_bitoptions'			=> IPSBWOPtions::freeze( $this->request, 'forums', 'forums' ),
						'permission_custom_error'	=> nl2br( IPSText::stripslashes($_POST['permission_custom_error']) ) );

		$this->DB->update( 'forums', $save, "id=" . $this->request['f'] );
		
		/* Save the log */
		$this->DB->insert( 'admin_logs', array( 'appcomponent' => $this->request['app'],
												'module'       => $this->request['module'],
												'section'      => $this->request['section'],
												'do'           => $this->request['do'],
												'member_id'    => $this->memberData['member_id'],
												'ctime'        => time(),
												'note'         => "Forum '".$this->registry->class_forums->forum_by_id[ $this->request['f'] ]['name']."'  edited via forum",
												'ip_address'   => $this->member->ip_address,
		) );

		$this->registry->output->redirectScreen( "Forum".$this->lang->words['for__edited'], $this->settings['base_url'] . "showforum=" . $this->request['f'] );
	}
}