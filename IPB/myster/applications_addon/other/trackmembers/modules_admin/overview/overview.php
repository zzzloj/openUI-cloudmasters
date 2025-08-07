<?php

/**
 * Product Title:		(SOS34) Track Members
 * Product Version:		1.0.0
 * Author:				Adriano Faria
 * Website:				SOS Invision
 * Website URL:			http://forum.sosinvision.com.br/
 * Email:				administracao@sosinvision.com.br
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class admin_trackmembers_overview_overview extends ipsCommand
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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_overview' );

		/* Set up stuff */
		$this->form_code	= $this->html->form_code	= 'module=overview&amp;section=overview';
		$this->form_code_js	= $this->html->form_code_js	= 'module=overview&section=overview';

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch($this->request['do'])
		{
			case 'settings':
				$this->_manageSettings();
			break;
			case 'show':
				$this->_showLinks();
			break;
			case 'new':
				$this->_showForm( 'new' );
			break;
			case 'edit':
				$this->_showForm( 'edit' );
			break;
			case 'doadd':
				$this->_saveLink( 'new' );
			break;
			case 'doedit':
				$this->_saveLink( 'edit' );
			break;
			case 'position':
				$this->_managePosition();
			break;
			case 'prune':
				$this->pruneLinks();
			break;
			case 'toggleState':
				$this->toggleLinkState();
			break;
			case 'remove':
				$this->removeLink();
			break;
							
			case 'overview':
			default:
				$this->home();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/*-------------------------------------------------------------------------*/
	// Home
	/*-------------------------------------------------------------------------*/

	public function home()
	{
		$this->DB->build( array( 'select' => 'upgrade_version_id, upgrade_version_human, upgrade_date',
								 'from'   => 'upgrade_history',
								 'where'  => "upgrade_app='trackmembers'",
								 'order'  => 'upgrade_version_id DESC',
								 'limit'  => array( 0, 2 )
		) );
   		
		$this->DB->execute();
		
   		while ( $row = $this->DB->fetch() )
   		{
   			$row['_date'] = $this->registry->getClass('class_localization')->formatTime( $row['upgrade_date'], 'SHORT' );
   			$data['upgrade'][] = $row;
   		}
   		
		$members 	= $this->DB->buildAndFetch( array( 'select' => 'count(member_id) as total',
								 				   	   'from'   => 'members',
								 				   	   'where'	=> "member_tracked = 1"
		) );

		$tracks 	= $this->DB->buildAndFetch( array( 'select' => 'count(id) as total',
								 				   	   'from'   => 'members_tracker'
		) );

		$members	= intval( $members['total'] );
		$tracks 	= intval( $tracks['total'] );

		$this->registry->output->html .= $this->html->overviewIndex( $data, $members, $tracks );
	}

	public function _showForm( $type )
	{
		$link = array();
	
		if ( $type == 'edit' )
		{
			$id = intval( $this->request['id'] );

			if ( !$id )
			{
				$this->registry->output->showError( $this->lang->words['nolinkprovided'] );
			}
			
			$link = $this->DB->buildAndFetch( array( 'select' => '*',
								 	 				 'from'   => 'downloads_portal_links',
								 	 				 'where'  => 'id = '.$id
			) );
						
			if( !$link['id'] )
			{
				$this->registry->output->showError( $this->lang->words['nolinkprovided'] );
			}
		}

		$this->registry->output->html .= $this->html->newLink( $link );
	}

	/*-------------------------------------------------------------------------*/
	// Settings
	/*-------------------------------------------------------------------------*/

	public function _manageSettings()
	{
		$classToLoad = IPSLib::loadActionOverloader( IPS_ROOT_PATH . 'applications/core/modules_admin/settings/settings.php', 'admin_core_settings_settings' );
		$settings    = new $classToLoad();
		$settings->makeRegistryShortcuts( $this->registry );
		
		$this->lang->loadLanguageFile( array( 'admin_tools' ), 'core' );
		
		$settings->html			= $this->registry->output->loadTemplate( 'cp_skin_settings', 'core' );	
				
		$settings->form_code	= $settings->html->form_code    = 'module=tools&amp;section=settings';
		$settings->form_code_js	= $settings->html->form_code_js = 'module=tools&section=settings';

		$this->request['conf_title_keyword'] = 'trackmembers';
		$settings->return_after_save         = $this->settings['base_url'].$this->form_code.'&do=settings';
		$settings->_viewSettings();	
	}
	
	public function _showLinks()
	{
		/* Visible and Invisible Links */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'downloads_portal_links',
								 'order'  => 'cposition'
		) );
   		
		$this->DB->execute();
		
		$visible	= array();
		$invisible  = array();
		
   		while ( $r = $this->DB->fetch() )
   		{
			if ( $r['visivel'] )
			{
				$visible[] = $r;
			}
			
			if ( !$r['visivel'] )
			{
				$invisible[] = $r;
			}
   		}

		$this->registry->output->html .= $this->html->showLinks( $visible, $invisible );
	}

	public function _saveLink( $type )
	{
		if ( $this->request['title'] == '' )
		{
			$this->registry->output->showError( $this->lang->words['savelink_emptyfields'] );
		}

		if ( $this->request['link'] == '' )
		{
			$this->registry->output->showError( $this->lang->words['savelink_emptyfields'] );
		}
		
		$view = $this->request['visivel'] == 'on' ? 1 : 0;
		$newj = $this->request['blank']   == 'on' ? 1 : 0;
		$imgl = $this->request['img'] 			  ? $this->request['img'] : $this->settings['downloadsblock_defaultimage'];

		if ( $type == 'new' )
		{
			$insert = array( 'titulo'		=> $this->request['title'], 
							 'link'			=> $this->request['link'],
							 'imglink'		=> $imgl,
							 'member_id'	=> $this->memberData['member_id'],
							 'visivel'		=> $view,
							 'novajanela'	=> $newj,
							 'date'			=> time()							 
			);

			$this->DB->insert( 'downloads_portal_links', $insert );
			$cf = $this->DB->getInsertId();
		
			$this->DB->update( 'downloads_portal_links', array( 'cposition' => $cf ), "id={$cf}" );
			$texto = $this->lang->words['savelink_linkadded'];
		}
		else
		{
			$this->DB->update( 'downloads_portal_links', array( 'titulo' => $this->request['title'], 'link' => $this->request['link'], 'imglink' => $imgl, 'visivel' => $view, 'novajanela' => $newj ), "id={$this->request['id']}" );
			
			$texto = $this->lang->words['savelink_linkupdated'];
		}
		
		$this->registry->output->redirect( $this->settings['base_url'] . $this->form_code . '&amp;do=show', $texto );
	}

	public function _managePosition()
	{
		require_once( IPS_KERNEL_PATH . 'classAjax.php' );
		$ajax = new classAjax();

		if ( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		$position = 1;

 		if( is_array($this->request['cf']) AND count($this->request['cf']) )
 		{
 			foreach( $this->request['cf'] as $this_id )
 			{
 				$this->DB->update( 'downloads_portal_links', array( 'cposition' => $position ), 'id=' . $this_id );
 				$position++;
 			}
 		}

 		$ajax->returnString( 'OK' );
 		exit();
	}

	public function pruneLinks()
	{
		$this->DB->delete( 'downloads_portal_links' );
		
		$this->registry->output->global_message = $this->lang->words['showlinks_prunedlinks'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=show' );
	}

	public function toggleLinkState()
	{
		$id = intval( $this->request['id'] );

		if ( !$id )
		{
			$this->registry->output->showError( $this->lang->words['nolinkprovided'] );
		}
		
		$link = $this->DB->buildAndFetch( array( 'select' => 'visivel',
									 	 'from'   => 'downloads_portal_links',
									 	 'where'  => 'id = '.$id
		) );
		
		$visivel = $link['visivel'] == 1 ? 0 : 1;
		
		$this->DB->update( 'downloads_portal_links', array( 'visivel' => $visivel ), "id={$id}" );

		$this->registry->output->global_message = $this->lang->words['showlinks_statestoggled'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=show' );
	}

	public function removeLink()
	{
		$id = intval( $this->request['id'] );

		if ( !$id )
		{
			$this->registry->output->showError( $this->lang->words['nolinkprovided'] );
		}
		
		$this->DB->delete( 'downloads_portal_links', 'id = '.$id );

		$this->registry->output->global_message = $this->lang->words['showlinks_removed'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=show' );
	}
	
}