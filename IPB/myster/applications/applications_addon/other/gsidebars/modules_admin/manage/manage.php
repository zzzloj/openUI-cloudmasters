<?php   

//-----------------------------------------------
// (DP32) Global Sidebars
//-----------------------------------------------
//-----------------------------------------------
// Application
//-----------------------------------------------
// Author: DawPi
// Site: http://www.ipslink.pl
// Written on: 04 / 02 / 2011
// Updated on: 28 / 08 / 2012
//-----------------------------------------------
// Copyright (C) 2011-2012 DawPi
// All Rights Reserved
//-----------------------------------------------   

class admin_gsidebars_manage_manage extends ipsCommand
{
	public $html;

	public function doExecute( ipsRegistry $registry )
	{
		/* Load skin and lang */
		
		$this->html               = $this->registry->output->loadTemplate( 'sidebars_cp' );		
		$this->html->form_code    = 'module=manage&amp;section=manage';
		$this->html->form_code_js = 'module=manage&section=manage';	
		
		/* Load lib */

		$this->lib 		= $this->registry->getClass('gsLibrary');	
		
		/* Check ACP restrictions */
		
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'manage' );	
		
		/* Set standard editor */
		
		$this->member->setProperty( 'members_editor_choice', 'std' );
						
		/* What we should to do? */

		switch ( $this->request['do'] )
		{
			/** Sidebars stuff **/

			case 'check':
				$this->doCheck();
			break;

			case 'add':
				$this->form( 'add' );
			break;

			case 'edit':
				$this->form( 'edit' );
			break;

			case 'remove':
				$this->doRemove();
			break;	

			case 'removeall':
				$this->doRemoveAll();
			break;
								
			case 'toggle':
				$this->doToggle();
			break;	
			
			case 'toggleWrapper':
				$this->doToggleWrapper();
			break;	
			
			case 'toggleWrapper2':
				$this->doToggleWrapper2();
			break;
			
			case 'toggleRandom':
				$this->doToggleRandom();
			break;
				
			case 'togglePinned':
				$this->doTogglePinned();
			break;	
						
			case 'updateCache':
				$this->doUpdateSidebarsCache();
			break;
			
			/** Adverts stuff **/
			
			case 'manageAdverts':
				$this->doManageAdverts();
			break;		

			case 'addAdvert':
				$this->formAdvert( 'add' );
			break;

			case 'editAdvert':
				$this->formAdvert( 'edit' );
			break;

			case 'checkAdvert':
				$this->doCheckAdvert();
			break;

			case 'toggleAdvert':
				$this->doToggleAdvert();
			break;	
									
			case 'reorderAdverts':
				$this->doReorderAdverts();
			break;	

			case 'updateAdvertsCache':
				$this->doUpdateAdvertsCache();
			break;

			case 'removeAdvert':
				$this->doRemoveAdvert();
			break;	

			case 'removeallAdverts':
				$this->doRemoveAllAdverts();
			break;
			
			/** Default stuff **/
			
			case 'view':												
			default:
				$this->viewMain();
			break;
		}

		$this->registry->output->html_main 	.= $this->registry->output->global_template->global_frame_wrapper();

		$this->registry->output->html 		.= $this->lib->c_acp();

		$this->registry->output->sendOutput();
	}

	public function viewMain()
	{		
		/* INIT */
		
		$apps		= array();
		
		$sidebars	= array();
		
		/* Get content from DB */
						 
		$this->DB->build( array(
							'select'	=> 's.*',
							'from'		=> array( 'dp3_gs_sidebars' => 's' ),								 
							'order'     => 's.s_id ASC',																																
		)	);							 
									 
		/* Execute */
		
		$q = $this->DB->execute();
		
		/* Parse */
		
		if ( $this->DB->getTotalRows( $q ) )
		{
			while ( $row = $this->DB->fetch( $q ) )
			{							
				/* Counts */
				
				if( $row['s_type'] != 'standard' )
				{
					$row['s_count']		  = (int) count( $this->lib->cleanArray( explode(',', $row['s_adverts'] ) ) );
					$row['s_count_adv']	  = (int) count( $this->lib->cleanArray( explode(',', $row['s_adv_adverts'] ) ) );
                    $row['s_count_nexus'] = (int) count( $this->lib->cleanArray( explode(',', $row['s_nexus_adverts'] ) ) );
				}
				else
				{
					$row['s_count']		= 'x';
					$row['s_count_adv']	= 'x';					
				}
							
				/* Set type name */
				
				$defName		= $this->lang->words[ 'sidebar_type__' . $row['s_type'] ];
				
				$row['_s_type']	= $row['s_type'];
				
				$row['s_type']	= $defName ? $defName : $this->lang->words[ 'sidebar_type__custom'] . $this->caches['app_cache'][ $row['s_type'] ]['app_public_title'];
				
				/* Add them */
				
				$sidebars[ $row['_s_type'] ] = $row;
			}
		}
		
		/* Make global on the top */
		
		if( count( $sidebars ) )
		{
			/* Check global sidebar */
			                
			if( in_array( 'all', array_keys( $sidebars ) ) )
			{			
				$tmp[] = $sidebars['all'];
			}
			
			/* Check standard sidebar */
			                
			if( in_array( 'standard', array_keys( $sidebars ) ) )
			{
				$tmp[] = $sidebars['standard'];
			}
			
			/* Remove */
			
			unset( $sidebars['all'], $sidebars['standard'] );
			
			/* Do we have global sidebar? */
			
			if( count( $this->lib->cleanArray( $tmp ) ) )
			{
				/* Merge them */
				
				$sidebars = array_merge( $tmp, $sidebars );
			}
			
			/* Clean memoery */
			
			unset( $tmp );
		}

		/* Build apps array */
		
		$apps	= $this->lib->getApps();
	
		/* Add to output */
		
		$this->registry->output->html .= $this->html->sidebarsListView( $sidebars, $apps );
	}

				
	public function form( $type = 'add' )
	{
		/* INIT */
		
		$data		= array();
		
		$formData	= array();
		
		$st	    	= $this->request['st'];	
		
		$id			= intval( $this->request['s_id'] );
		
		/* What type we want to do? */
		
		if( $type == 'add' )
		{
			$button             	= $this->lang->words['add_sidebar'];
					
			$formData['type']      	= 'add';
											
			$formData['formDo'] 	= $this->lang->words['adding_sidebar']; 					
		}
		else
		{
			if( $id )
			{
				$button            	= $this->lang->words['edit_sidebar'];
				
				$formData['type']  	= 'edit';			
									
				$formData['formDo'] = $this->lang->words['editing_sidebar'];	
			
				/* Get data from SQL */
				
				$data = $this->DB->buildAndFetch( array(
												'select'	=> 's.*',
												'from'		=> array( 'dp3_gs_sidebars' => 's' ), 
												'where'	 	=> 's.s_id = ' . $id,									
				)	);	
				
				/* No data? */
				
				if ( ! $this->DB->getTotalRows() )
				{
				   $this->registry->output->showError( $this->lang->words['errornoitems'], 'DP31GS_001' );				
				}						
			}
			else
			{
				$this->registry->output->showError( $this->lang->words['errornoid'], 'DP31GS_002' );
			}
		}
		
		/* Set type */
		
		$actionType = ( isset( $this->request['rawType'] ) && $this->request['rawType'] ) ? $this->request['rawType'] : $data['s_type'];
		
		/* Only one stuff */
		
		if( $type == 'add' )
		{
			/* Only one sidebar per type */
		
			$ckOne = $this->DB->buildAndFetch( array( 'select' => 's_type', 'from' => 'dp3_gs_sidebars', 'where' => 's_type = "' . $actionType . '"' ) );
			
			if( $ckOne['s_type'] )
			{
				$this->registry->output->showError( $this->lang->words['error_cant_have_two_sidebars'], 'DP31GS_003' );
			}	
		}

		/** Build standard fields **/
		
		/* ID */
			
		$formData['s_id']   			= $data['s_id'];
		
		/* Name */
		
		$formData['_s_name']			= $data['s_name'];
		$formData['s_name']				= $this->registry->output->formInput( 's_name', $data['s_name'], '', 40 );			
		
		/* Enabled */
						
		$formData['s_enabled']			= $this->registry->output->formYesNo( 's_enabled', isset( $data['s_enabled'] ) ? $data['s_enabled'] : 1 );				
		
		/* Wrapper */
						
		$formData['s_wrapper']			= $this->registry->output->formYesNo( 's_wrapper', isset( $data['s_wrapper'] ) ? $data['s_wrapper'] : 1 );	
		
		/* Own wrapper */
						
		$formData['s_wrapper_separate']	= $this->registry->output->formYesNo( 's_wrapper_separate', isset( $data['s_wrapper_separate'] ) ? $data['s_wrapper_separate'] : 0 );	
		
		/* Random order */
						
		$formData['s_random']			= $this->registry->output->formYesNo( 's_random', isset( $data['s_random'] ) ? $data['s_random'] : 0 );	
		
		/* Show at once */
		
		$formData['s_limit_at_once']	=  $this->registry->output->formInput( 's_limit_at_once', isset( $data['s_limit_at_once'] ) ? $data['s_limit_at_once'] : 0, '', 5 );	
								
		/* Groups */
		
		foreach( $this->registry->cache()->getCache('group_cache') as $g_id => $group )
		{
			$mem_group[] = array( $g_id , $group['g_title'] );
		}
		
		$formData['s_groups'] = $this->registry->output->formMultiDropdown( "s_groups[]", $mem_group, explode( ',', $data['s_groups'] ), 5 );
		
		/* Set sidebar name */
		
		$formData['sidebarType'] 	= $this->lang->words['sidebar_type__' . $actionType ] ? $this->lang->words['sidebar_type__' . $actionType ] : ( $this->lang->words[ 'sidebar_type__custom'] . $this->caches['app_cache'][ $actionType ]['app_public_title'] );
		
		/* Type */
		
		$formData['rawType']		= $actionType;
		
		/* Global sidebar? Disable on pages stuff OR enable on for standard sidebar */
		
		if( in_array( $actionType, array( 'all', 'standard' ) ) )
		{
			/* Build apps array */
		
			$_apps	= $this->lib->getApps();
			
			/* Do we have them? */
			
			if( count( $_apps ) )
			{
				/* Index only for 'all' type */
				
				if( $actionType == 'all' )
				{
					$apps[]		= array( 'index', $this->lang->words['sidebar_type__index'] );
				}
				
				/* Rest */
				
				$apps[]		= array( 'forum', $this->lang->words['sidebar_type__forum'] );
				$apps[]		= array( 'topic', $this->lang->words['sidebar_type__topic'] );
				
				foreach( $_apps as $id => $name )
				{										
					$apps[] 	= array( $id, $this->lang->words['sidebar_type__custom'] . $name );
				}
			}

			$formData['disable_on']  	= $this->registry->output->formMultiDropdown( "disable_on[]", $apps, explode(',', $data['s_custom'] ), 5, 'disable_on' );			
		}
		
		/* Make the forums list for forum type */
		
		if( $actionType == 'forum' )
		{
			/* Load forums functions */
	
			$this->registry->class_forums->forumsInit();
					
			require_once( IPS_ROOT_PATH . 'applications/forums/sources/classes/forums/admin_forum_functions.php' );
			$this->libForums               = new admin_forum_functions( $this->registry );		
			$this->libForums->forumsInit();	
			
			/* Load forums itself */
				
			$forums = $this->libForums->adForumsForumList(1);
			
			$formData['s_forums'] = $this->registry->output->formMultiDropdown( "s_forums[]", $forums,  explode( ',', $data['s_custom'] ), 5, 's_forums' );			
		}
					
		/* Add to output */
			
		$this->registry->output->html .= $this->html->sidebarForm( $formData, $button, $st );			
	}

	
	public function doCheck()
	{	
		/* Cancel? */
		
		if( $this->request['cancel_operation'] && isset( $this->request['cancel_operation'] ) )
		{
			self::viewMain();
			return;
		}
			
		/* INIT */
		
		$id 		= intval( $this->request['s_id'] );		
		$name		= trim( $this->request['s_name'] );
		$enabled	= intval( $this->request['s_enabled'] );		
		$wrapper	= intval( $this->request['s_wrapper'] );
		$wrapper2	= intval( $this->request['s_wrapper_separate'] );			
		$groups		= $this->request['s_groups'];
		$type		= trim( $this->request['rawType'] );
		$random		= intval( $this->request['s_random'] );
		$atOnce		= intval( $this->request['s_limit_at_once'] );
		
		/* Check default values */
		
		if( ! IPSText::mbstrlen( $name ) )
		{
			$this->registry->output->showError( $this->lang->words['error_no_sidebar_title'], 'DP31GS_004' );	
		}
		
		/* Build custom stuff */
		
		if( in_array( $type, array( 'all', 'standard' ) ) && is_array( $this->request['disable_on'] ) )
		{
			$custom = implode( ',', $this->request['disable_on'] );
		}
		elseif( ( $type == 'forum' ) && is_array( $this->request['s_forums'] ) )
		{
			$custom = implode( ',', $this->request['s_forums'] );			
		}
												
		/* Build array with data */
		
		$saveArray = array(
							's_name'				=> IPSText::getTextClass('bbcode')->xssHtmlClean( nl2br( IPSText::stripslashes( $name ) ) ),
							's_type'				=> $type,
							's_enabled'				=> $enabled,
							's_groups'				=> is_array( $groups ) ? implode( ',', $groups ) : '',
							's_custom'				=> $custom,
							's_wrapper'				=> $wrapper,
							's_wrapper_separate'	=> $wrapper ? 0 : $wrapper2,
							's_random'				=> $random,
							's_limit_at_once'		=> $atOnce
			    );

  	  	/* Check type and go ahead */
			 
        if ( $this->request['type'] == 'add' )
  	    {			
			/* Prepare def value */
			
			$saveArray['s_adverts']	= '';
			
			/* Check exists type */
			
			$ck = $this->DB->buildAndFetch( array( 'select' => 's_type', 'from' => 'dp3_gs_sidebars', 'where' => 's_type = "' . $type . '"' ) );
			
			if( IPSText::mbstrlen( $ck['s_type'] ) )
			{
				$this->registry->output->showError( $this->lang->words['error_cant_have_two_sidebars'], 'DP31GS_005' );
			}				
			
			/* Insert! */
			
			$this->DB->insert( 'dp3_gs_sidebars', $saveArray ); 			
			$sidID	= $this->DB->getInsertId();
			
			/* Update cache */
			
			$this->cache->rebuildCache( 'gs_sidebars', 'gsidebars' );

			/* Are we going to add advert? */
			
			if( isset( $this->request['add_first_ad'] ) && $this->request['add_first_ad'] && $sidID )
			{
				$url = '&amp;do=addAdvert&amp;s_id=' . $sidID;
			}
			
			/* Set message */
				
			$this->registry->output->global_message = $this->lang->words['redirectadd_sidebar'];	
									
			/* Redirect */
				
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code . $url . '&amp;st=' . $this->request['st'] );		
        }
        
        else if ( $this->request['type'] == 'edit' )
        {
			/* Count the act number of ads */
			
			$act = $this->DB->buildAndFetch( array( 'select' => 's_adverts', 'from' => 'dp3_gs_sidebars', 'where' => 's_id = ' .$id ) );
			
			$actAdvertsNumber = count( explode( ',', $act['s_adverts'] ) );
			
			/* More than maximum? Set to display all */
			
			if( $actAdvertsNumber <= $saveArray['s_limit_at_once'] )
			{
				$saveArray['s_limit_at_once'] = 0;	
			}
	 	 		  
			/* Update! */
		 	 
			$this->DB->update( 'dp3_gs_sidebars', $saveArray, 's_id = ' . $id ); 
		
			/* Update cache */
			
			$this->cache->rebuildCache( 'gs_sidebars', 'gsidebars' );

			/* Are we going to add advert? */
			
			if( isset( $this->request['add_first_ad'] ) && $this->request['add_first_ad'] && $id )
			{
				$url = '&amp;do=addAdvert&amp;s_id=' . $id;
			}
			
			/* Set message */
				
			$this->registry->output->global_message = $this->lang->words['redirectedit_sidebar'];	
									
			/* Redirect */
				
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code . $url . '&amp;st=' . $this->request['st'] );		
		}		
	}
	

	public function doRemove()
	{
		/* Get ID */
		
		$id = intval( $this->request['s_id'] );
		
		if ( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['errornoid'], 'DP31GS_006' );		
		}
		/* Get all info about this category */
		
		$act = $this->DB->buildAndFetch( array( 'select' => 's_name', 'from' => 'dp3_gs_sidebars', 'where' => 's_id = ' . $id ) );
		
		/* Delete it */
		
		$this->DB->delete( 'dp3_gs_sidebars', 's_id = ' . $id );
        $this->DB->delete( 'dp3_gs_adverts', 'a_sidebar_id = ' . $id ); #fix: 1.0.9.1
        
		/* Update cache */
			
		$this->cache->rebuildCache( 'gs_sidebars', 'gsidebars' );
									
		/* Set message */
		
		$this->registry->output->global_message = sprintf( $this->lang->words['messagesuccessremoved_sidebar'], $act['s_name'] );		
		
		/* Save log and redirect */
		
		$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['logsuccessdeleted_sidebar'], $act['s_name'] ) );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code );
	}
	
	
	public function doRemoveAll()
	{			
		/* Delete all */
		
		$this->DB->delete( 'dp3_gs_sidebars' );
		$this->DB->delete( 'dp3_gs_adverts' );
		
		/* Update cache */
			
		$this->cache->rebuildCache( 'gs_sidebars', 'gsidebars' );
		$this->cache->rebuildCache( 'gs_adverts', 'gsidebars' );
					
		/* Set message */
		
		$this->registry->output->global_message = $this->lang->words['messageallsuccessdeleted_sidebars'];
					
		/* Save log and redirect */
		
		$this->registry->getClass('adminFunctions')->saveAdminLog( $this->lang->words['logsuccessalldeleted_sidebars'] );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code );	
	}


	public function doUpdateSidebarsCache()
	{
		/* Update cache */
			
		$this->cache->rebuildCache( 'gs_sidebars', 'gsidebars' );
			
		/* Set message */
		
		$this->registry->output->global_message = $this->lang->words['success_sidebars_cache_updated'];	
						
		/* Redirect */	
		  
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code );				
	}
	

	public function doToggle()
	{
		/*INIT */
		
		$id = intval( $this->request['s_id'] );
		
		$act = $this->DB->buildAndFetch( array( 'select' => 's_id, s_enabled', 'from' => 'dp3_gs_sidebars', 'where' => 's_id = ' . $id ) );
		
		/* No ID? */
		
		if ( ! $this->DB->getTotalRows() )
		{
			 $this->registry->output->showError( $this->lang->words['errornoid'], 'DP31GS_007' );
		}

		/* Set proper value */
		
		$sett = ( $act['s_enabled'] ) ? 0 : 1 ;
		
		/* Update */
		
		$this->DB->update( 'dp3_gs_sidebars', array( 's_enabled' => $sett ) , 's_id = ' . $id );

		/* Update cache */
			
		$this->cache->rebuildCache( 'gs_sidebars', 'gsidebars' );
		
		/* Set message */
		
		$this->registry->output->global_message = $this->lang->words['messagesuccesstoggled'];
				
		/* Redirect */	
		  
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code );			
	}
	
	public function doToggleWrapper()
	{
		/*INIT */
		
		$id = intval( $this->request['s_id'] );
		
		$act = $this->DB->buildAndFetch( array( 'select' => 's_id, s_wrapper', 'from' => 'dp3_gs_sidebars', 'where' => 's_id = ' . $id ) );
		
		/* No ID? */
		
		if ( ! $this->DB->getTotalRows() )
		{
			 $this->registry->output->showError( $this->lang->words['errornoid'], 'DP31GS_023' );
		}

		/* Set proper value */
		
		$sett = ( $act['s_wrapper'] ) ? 0 : 1 ;
		
		/* Prepare array */
		
		$toggle = array( 's_wrapper' => $sett );
		
		if( $sett )
		{
			$toggle['s_wrapper_separate'] = 0;
		}
		
		/* Update */
		
		$this->DB->update( 'dp3_gs_sidebars', $toggle, 's_id = ' . $id );

		/* Update cache */
			
		$this->cache->rebuildCache( 'gs_sidebars', 'gsidebars' );
		
		/* Set message */
		
		$this->registry->output->global_message = $this->lang->words['message_success_toggled_wrapper'];
				
		/* Redirect */	
		  
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code );			
	}
	
	
	public function doToggleWrapper2()
	{
		/*INIT */
		
		$id = intval( $this->request['s_id'] );
		
		$act = $this->DB->buildAndFetch( array( 'select' => 's_id, s_wrapper_separate', 'from' => 'dp3_gs_sidebars', 'where' => 's_id = ' . $id ) );
		
		/* No ID? */
		
		if ( ! $this->DB->getTotalRows() )
		{
			 $this->registry->output->showError( $this->lang->words['errornoid'], 'DP31GS_024' );
		}

		/* Set proper value */
		
		$sett = ( $act['s_wrapper_separate'] ) ? 0 : 1 ;
		
		/* Prepare array */
		
		$toggle = array( 's_wrapper_separate' => $sett );
		
		if( $sett )
		{
			$toggle['s_wrapper'] = 0;
		}
				
		/* Update */
		
		$this->DB->update( 'dp3_gs_sidebars', $toggle, 's_id = ' . $id );

		/* Update cache */
			
		$this->cache->rebuildCache( 'gs_sidebars', 'gsidebars' );
		
		/* Set message */
		
		$this->registry->output->global_message = $this->lang->words['message_success_toggled_wrapper2'];
				
		/* Redirect */	
		  
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code );			
	}

	
	public function doToggleRandom()
	{
		/* INIT */
		
		$id = intval( $this->request['s_id'] );
		
		$act = $this->DB->buildAndFetch( array( 'select' => 's_id, s_random', 'from' => 'dp3_gs_sidebars', 'where' => 's_id = ' . $id ) );
		
		/* No ID? */
		
		if ( ! $this->DB->getTotalRows() )
		{
			 $this->registry->output->showError( $this->lang->words['errornoid'], 'DP31GS_025' );
		}

		/* Set proper value */
		
		$sett = ( $act['s_random'] ) ? 0 : 1 ;

		/* Update */
		
		$this->DB->update( 'dp3_gs_sidebars', array( 's_random' => $sett ), 's_id = ' . $id );

		/* Update cache */
			
		$this->cache->rebuildCache( 'gs_sidebars', 'gsidebars' );
		
		/* Set message */
		
		$this->registry->output->global_message = $this->lang->words['message_success_toggled_random'];
				
		/* Redirect */	
		  
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code );			
	}
	
	///////////////////////////
	////   ADVERTS STUFF   ////
	///////////////////////////

	public function doManageAdverts()
	{
		/* INIT */
		
		$ads				= array();
		$duplicatedAds		= array();
		$duplicatedSidebars	= array();		
		$sid				= intval( $this->request['s_id'] );

		/* Navigation */
		
		$navTitle								= $this->lib->sidebarsCache[ $sid ]['s_name'];
		
		$this->registry->output->extra_nav[]	= array( $this->settings['base_url'] . $this->html->form_code . '&amp;do=manageAdverts&s_id=' . $sid, $navTitle );
		
		$this->registry->output->extra_title[]	= $navTitle;		 

		/* Standard sidebar? Can't add there advert! */
		
		if( $this->lib->sidebarsCache[ $sid ]['s_type'] == 'standard' )
		{
			$this->registry->output->showError( $this->lang->words['cant_add_advert_to_standard_sidebar'], 'DP31GS_022' );		
		}
				
		/* Do we have at least one sidebar? */

		if( ! count( $this->lib->sidebarsCache ) )
		{
			$this->registry->output->showError( $this->lang->words['error_cant_add_advert_no_any_sidebar'], 'DP31GS_008' );			
		}
		
		/* Do we have sidebar ID? */
		
		if( ! $sid )
		{
			$this->registry->output->showError( $this->lang->words['errornoid'], 'DP31GS_009' );			
		}
		
		/* Get sidebar data from DB */
		
		$sidebar = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'dp3_gs_sidebars', 'where' => 's_id = ' . $sid ) );
		
		/* Do we have sidebar data? */
		
		if( ! $sidebar['s_id'] )
		{
			$this->registry->output->showError( $this->lang->words['errornoitems'], 'DP31GS_010' );			
		}
        
        /* Nexus integration */
        
        if( ! is_file( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php' ) )	
        {
            $where = ' AND a_is_nexus = 0';
        }
        else
        {
            $where = '';
        }
		
		/* Get all adverts from DB */
								 
		$this->DB->build( array(
							'select'	=> 'a.*',
							'from'		=> array( 'dp3_gs_adverts' => 'a' ),								 
							'order'     => 'a.a_pinned DESC, a.a_position ASC',	
							'where'		=> 'a.a_sidebar_id = ' . $sid . $where,
																																						
		)	);							 
									 
		/* Execute */
		
		$q = $this->DB->execute();
		
		/* Parse */
		
		if ( $this->DB->getTotalRows( $q ) )
		{
			while ( $row = $this->DB->fetch( $q ) )
			{				
				/* Add duplicated */
				
				$duplicatedAds[ $row['a_id'] ] = $row['a_duplicate_id'];
				
				/* Add them */
				
				$ads[ $row['a_id'] ] = $row;
			}
		}
		
		/* Do we have duplicated ads? */
		
		if( count( $duplicatedAds ) )
		{
			$this->DB->build( array(
								'select'	=> 'a.a_id, a.a_sidebar_id',
								'from'		=> array( 'dp3_gs_adverts' => 'a' ),								 
								'where'		=> 'a.a_id  IN ( ' . implode( ',', $duplicatedAds  ) . ' )'
																																							
			)	);							 
										 
			/* Execute */
			
			$q2 = $this->DB->execute();
			
			/* Parse */
			
			if ( $this->DB->getTotalRows( $q2 ) )
			{
				while ( $row2 = $this->DB->fetch( $q2 ) )
				{
					$duplicatedSidebars[ $row2['a_id'] ] = $row2['a_sidebar_id'];
				}
				
				if( count( $duplicatedAds ) )
				{
				    foreach( $duplicatedAds as $adId => $duplicatedAdId )
				    {
						$ads[ $adId ]['a_duplicate_sid'] = $duplicatedSidebars[ $duplicatedAdId ];	
					}
				}			
			}
		}
	
		/* Add to output */
		
		$this->registry->output->html .= $this->html->adsListView( $ads, $sidebar );		
	}

				
	public function formAdvert( $type = 'add' )
	{
		/* INIT */
		
		$data			= array();
		
		$formData		= array();
		
		$existsAdverts	= array();
		
		$st	    		= $this->request['st'];	
		
		$id				= intval( $this->request['a_id'] );
		
		$sid			= intval( $this->request['s_id'] );

		/* Navigation */
		
		$navTitle								= $this->lib->sidebarsCache[ $sid ]['s_name'];
		
		$this->registry->output->extra_nav[]	= array( $this->settings['base_url'] . $this->html->form_code . '&amp;do=manageAdverts&s_id=' . $sid, $navTitle );
		
		$this->registry->output->extra_title[]	= $navTitle;	
		
		/* Standard sidebar? Can't add there advert! */
		
		if( $this->lib->sidebarsCache[ $sid ]['s_type'] == 'standard' )
		{
			$this->registry->output->showError( $this->lang->words['cant_add_advert_to_standard_sidebar'], 'DP31GS_021' );		
		}
				
		/* What type we want to do? */
		
		if( $type == 'add' )
		{
			$button             	= $this->lang->words['add_advert'];
					
			$formData['type']      	= 'add';
											
			$formData['formDo'] 	= $this->lang->words['adding_advert']; 					
		}
		else
		{
			if( $id )
			{
				$button            	= $this->lang->words['edit_advert'];
				
				$formData['type']  	= 'edit';			
									
				$formData['formDo'] = $this->lang->words['editing_advert'];	
			
				/* Get data from SQL */
				
				$data = $this->DB->buildAndFetch( array(
												'select'	=> 'a.*',
												'from'		=> array( 'dp3_gs_adverts' => 'a' ), 
												'where'	 	=> 'a.a_id = ' . $id																					
				)	);	
				
				/* No data? */
				
				if ( ! $this->DB->getTotalRows() )
				{
				   $this->registry->output->showError( $this->lang->words['errornoitems'], 'DP31GS_011' );				
				}						
			}
			else
			{
				$this->registry->output->showError( $this->lang->words['errornoid'], 'DP31GS_012' );
			}
		}

		/** Build standard fields **/
		
		/* ID */
			
		$formData['a_id']   		= $data['a_id'];
		
		/* Sidebar ID */
		
		$formData['s_id']   		= $sid;
				
		/* Name */
		
		$formData['_a_name']		= $data['a_name'];
		$formData['a_name']			= $this->registry->output->formInput( 'a_name', $data['a_name'],'', 40, 'text', '', '' );			
		
		/* Enabled */
						
		$formData['a_enabled']		= $this->registry->output->formYesNo( 'a_enabled', isset( $data['a_enabled'] ) ? $data['a_enabled'] : 1 );	

		/* PHP mode */
		
		$formData['a_php_mode']		= $this->registry->output->formYesNo( 'a_php_mode', isset( $data['a_php_mode'] ) ? $data['a_php_mode'] : 0 );
				
		/* Raw mode */
		
		$formData['a_raw_mode']		= $this->registry->output->formYesNo( 'a_raw_mode', isset( $data['a_raw_mode'] ) ? $data['a_raw_mode'] : 0 );						

		/* Advert content */

        $formData['a_content']      = $this->registry->output->formTextarea( 'a_content', $data['a_content'], 100, 15, 'text', "style='width:100%;'" );			
		
		/** Advanced adverts stuff **/
		
		if( $this->lib->advEnabled() )
		{
			/* Use checkbox */

			$formData['use_adv_advert'] = $this->registry->output->formCheckbox( 'use_adv_advert', ( $data['a_is_advanced'] ? 1 : 0 ), 1, 'use_adv_advert' );
			
			/* Get all adverts */
			
			$adverts				= $this->lib->getAllowedAdverts();
			
			$formData['adv_advert']	= $this->registry->output->formDropdown( 'adv_advert', $adverts, $data['a_is_advanced'] );
			
			/*  Enable */
			
			$formData['integration_enabled'] = true;
			
			/* Advert is set? */
			
			$formData['advert_is_set'] = $data['a_is_advanced'] ? 1 : 0;
		}
		else
		{
			$formData['integration_enabled'] = false;
		}
        
        /* Nexus Integration */
        
        if( is_file( IPSLib::getAppDir( 'gsidebars' ) . '/sources/classes/libraryNexus.php' ) )
        {
            $formData['nexusStuff'] = $this->registry->gsLibraryNexus->getNexusForm( $data );
        }
		
		/* Build array with all exists adverts */
		
		$this->DB->build( array(
							'select'	=> 'a.a_id, a.a_name',
							'from'		=> array( 'dp3_gs_adverts' => 'a' ),
							'where'		=> 'a.a_duplicate_id = 0',								 
							'order'     => 'a.a_sidebar_id ASC',
							'add_join'  => array( 
												  0 => array( 'select' => 's.s_name',
															  'from'   => array( 'dp3_gs_sidebars' => 's' ),
															  'where'  => 's.s_id=a.a_sidebar_id',
															  'type'   => 'left' )
												)			  																																						
		)	);							 
									 
		/* Execute */
		
		$ads = $this->DB->execute();
		
		/* Parse */
		
		if ( $this->DB->getTotalRows( $ads ) )
		{			
			while ( $ad = $this->DB->fetch( $ads ) )
			{
				$existsAdverts[] = array( $ad['a_id'], $ad['a_name'] . ' (' . $ad['s_name'] . ')' );
			}
			/* Use checkbox */

			$formData['use_exists_advert'] = $this->registry->output->formCheckbox( 'use_exists_advert', ( $data['a_duplicate_id'] ? 1 : 0 ), 1, 'use_exists_advert' );
			
			/* Display form */	
					
			$formData['existsAdvert']	= $this->registry->output->formDropdown( 'existsAdvert', $existsAdverts, $data['a_duplicate_id'] );
			
			/* Advert is set? */
			
			$formData['exists_advert_is_set'] = $data['a_duplicate_id'] ? 1 : 0;
			
			/* More info */
			
			if( $data['a_duplicate_id'] )
			{
				/* Get exists advert details */
				
				$dataExists = $this->DB->buildAndFetch( array(
												'select'	=> 'a.a_id, a.a_sidebar_id',
												'from'		=> array( 'dp3_gs_adverts' => 'a' ), 
												'where'	 	=> 'a.a_id = ' . $data['a_duplicate_id']																					
				)	);	
				
				/* Build info */
							
				$formData['exists_advert_id'] 	= $dataExists['a_id'];
				$formData['exists_sidebar_id']	= $dataExists['a_sidebar_id'];	
			}			
		}			

		/* Add to output */
			
		$this->registry->output->html .= $this->html->advertForm( $formData, $button, $st );			
	}
	

	public function doCheckAdvert()
	{	
		/* Cancel? */
		
		if( $this->request['cancel_operation'] && isset( $this->request['cancel_operation'] ) )
		{
			self::doManageAdverts();
			return;
		}
			
		/* INIT */
		
		$id 			= intval( $this->request['a_id'] );
		
		$sid 			= intval( $this->request['s_id'] );
		
		$name			= trim( $this->request['a_name'] );

		$enabled		= intval( $this->request['a_enabled'] );
		
		$php			= intval( $this->request['a_php_mode'] );
		
		$raw			= intval( $this->request['a_raw_mode'] );
		
		$_content		= trim( $this->request['a_content'] );
		
		$useAdvanced 	= intval( $this->request['use_adv_advert'] );
		
		$choosenAdv		= intval( $this->request['adv_advert'] );
		
		$useExist 		= intval( $this->request['use_exists_advert'] );
		
		$choosenExist	= intval( $this->request['existsAdvert'] );

		$useNexus 	    = intval( $this->request['use_nexus_advert'] );
		
		$choosenNexus	= intval( $this->request['nexus_advert'] );
        		
		/* Check default values */
		
		if( ! IPSText::mbstrlen( $name ) )
		{
			$this->registry->output->showError( $this->lang->words['error_no_advert_title'], 'DP31GS_013' );	
		}
		
		if( ! IPSText::mbstrlen( $_content ) && ! $useAdvanced && ! $useExist && ! $useNexus )
		{
			$this->registry->output->showError( $this->lang->words['error_no_advert_content'], 'DP31GS_014' );	
		}
        
        /* Load editor lib */
        
        $classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
        $editor = new $classToLoad();
    
  		/* Check PHP */
          
          if( $php )
  		{
  			self::checkPHP( $_POST['a_content'] );
  		}      
 
		/* Store it */
		
		$content = $_POST['a_content'];					
															
		/* Build array with data */
		
		$saveArray = array(
							'a_name'			=> IPSText::getTextClass('bbcode')->xssHtmlClean( nl2br( IPSText::stripslashes( $name ) ) ),
							'a_php_mode'		=> $php,
							'a_raw_mode'		=> $php ? 1 : $raw,
							'a_enabled'			=> $enabled,
							'a_content'			=> $raw? $content : IPSText::getTextClass('bbcode')->xssHtmlClean( $content ),
							'a_is_advanced'		=> $useAdvanced ? $choosenAdv : 0,
                            'a_is_nexus'		=> $useNexus ? $choosenNexus : 0,
							'a_duplicate_id'	=> $useExist ? $choosenExist : 0
			    );

  	  	/* Check type and go ahead */
			 
        if ( $this->request['type'] == 'add' )
  	    {			
			/* Get max position from act adverts */
			
			$act = $this->DB->buildAndFetch( array( 'select' => 'MAX(a_position) as act_position', 'from' => 'dp3_gs_adverts', 'where' => 'a_sidebar_id = ' . $sid ) );
			
			$saveArray['a_position'] 	= $act['act_position'] + 1;
			
			/* Add sidebar ID */
			
			$saveArray['a_sidebar_id'] 	= $sid;						
			
			/* Insert! */
			
			$this->DB->insert( 'dp3_gs_adverts', $saveArray ); 
			
			$advertID	= $this->DB->getInsertId();
			
			$advertID	= ( ! $saveArray['a_is_advanced'] ) ? $advertID : $choosenAdv;
            
			$advertID	= ( $choosenAdv ) ? $choosenAdv : ( ( ! $saveArray['a_is_nexus'] ) ? $advertID : $choosenNexus );
			/* Add advert to the sidebars table */
			
			$this->storeAdvertInSidebar( 'add', $advertID, $sid, $saveArray['a_is_advanced'], $saveArray['a_is_nexus']  );

			/* Update cache */
			
			$this->cache->rebuildCache( 'gs_adverts', 'gsidebars' );
			$this->cache->rebuildCache( 'gs_sidebars', 'gsidebars' );

			/* Set message */
				
			$this->registry->output->global_message = $this->lang->words['redirectadd_advert'];	
									
			/* Redirect */
				
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code . '&amp;do=manageAdverts&amp;s_id=' . $sid . '&amp;st=' . $this->request['st'] );	
        }
        
        else if ( $this->request['type'] == 'edit' )
        {		 	 		  
			/* Get current data */
			
			$act = $this->DB->buildAndFetch( array( 'select' => 'a_is_advanced, a_sidebar_id, a_is_nexus', 'from' => 'dp3_gs_adverts', 'where' => 'a_id = ' . $id ) );
			
			/* We have advanced or nexus advert type set? */
			
			if( $act['a_is_advanced'] )
			{
				/* And now we do not want this type */
				
				if( ! $useAdvanced )
				{
					$this->storeAdvertInSidebar( 'remove', $id, $act['a_sidebar_id'], $act['a_is_advanced'] );
				}
			}
			elseif( $useAdvanced )
			{
			     $this->storeAdvertInSidebar( 'add', $choosenAdv, $act['a_sidebar_id'], true );	
			}
            elseif( $act['a_is_nexus'] )
            {
				/* And now we do not want this type */
				
				if( ! $usNexus )
				{
					$this->storeAdvertInSidebar( 'remove', $id, $act['a_sidebar_id'], false, $act['a_is_nexus'] );
				}
            }
            elseif( $useNexus )
            {                
			     $this->storeAdvertInSidebar( 'add', $choosenAdv, $act['a_sidebar_id'], false, true );
            }
			
			/* Update! */
		 	 
			$this->DB->update( 'dp3_gs_adverts', $saveArray, 'a_id = ' . $id ); 
		
			/* Update cache */
			
			$this->cache->rebuildCache( 'gs_adverts', 'gsidebars' );
			$this->cache->rebuildCache( 'gs_sidebars', 'gsidebars' );
			
			/* Set message */
				
			$this->registry->output->global_message = $this->lang->words['redirectedit_advert'];	
            
            /* Remove autosaved */
            
            $editor->removeAutoSavedContent( array( 'member_id' => $this->memberData['member_id'], 'autoSaveKey' => "gs_advert" ) );
									
			/* Redirect */
				
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code . '&amp;do=manageAdverts&amp;s_id=' . $sid . '&amp;st=' . $this->request['st'] );			
		}		
	}


	private function checkPHP( $code = '' )
	{
		/* Do we have code? */
		
		if( ! IPSText::mbstrlen( $code ) )
		{
			return true;
		}
		
		/* Buffer */
		
		ob_start();
		
		/* Check error */
					
		if( eval( "?>" . $code . "<? " ) === FALSE )
		{
			$this->registry->output->showError( $this->lang->words['php_parse_problem'], 'DP31GS_015' );	
		}
		
		/* Clean buffer */
		
		ob_end_clean();		
	}
	
	public function storeAdvertInSidebar( $action = 'add', $aid = 0, $sid = 0, $adv = FALSE, $nexus = FALSE )
	{
		/* Do we have both ID's? */
		
		if( ! $aid || ! $sid )
		{
			return;
		}
		
		/* Build type */
		
		$type		= ( $adv )   ? 's_adv_adverts'   : 's_adverts';
        $type       = ( $nexus ) ? 's_nexus_adverts' : $type;
		
		/* Get actual sidebar data */
		
		$sidebar 	= $this->DB->buildAndFetch( array( 'select' => $type, 'from' => 'dp3_gs_sidebars', 'where' => 's_id = ' . $sid ) );
		
		/* Get all exists adverts */
		
		if( $sidebar[ $type ] )
		{
			$ads		= explode( ',', $sidebar[ $type ] );
			
			/* Are we adding it? */
			
			if( $action == 'add' )
			{
				array_push( $ads, $aid );
			}
			else
			{
				unset( $ads[ array_search( $aid, $ads ) ] );
			}
			
			$save		= array_unique( $ads );
		}
		else
		{
			if( $action == 'add' )
			{
				$save	= array( $aid );
			}
			else
			{
				$save	= array();
			}
		}
		
		/* Save */
		
		$this->DB->update( 'dp3_gs_sidebars', array( $type => implode( ',', $save ) ), 's_id = ' . $sid ); 
	} 	


	public function doToggleAdvert()
	{
		/* INIT */
		
		$id		= intval( $this->request['a_id'] );
		
		$sid	= intval( $this->request['s_id'] );
		
		$type 	= trim( $this->request['type'] );
			
		/* Do we have ID? */
		
		if( ! $id )
		{
			$this->registry->output->showError( $this->lang->words['error_no_id'], 'DP31GS_016' );				
		}
		
		/* Set type */
		
		switch( $type )
		{
			case 'raw':
				$_type	= 'a_raw_mode';				
			break;
			
			case 'enabled':
				$_type	= 'a_enabled';				
			break;
			
			case 'php':
				$_type	= 'a_php_mode';				
			break;							
		}
		
		/* Do we have type? */
		
		if( ! IPSText::mbstrlen( $_type ) )
		{
			$this->registry->output->showError( $this->lang->words['error_no_data'], 'DP31GS_017' );			
		}
						
		/* Get current informations */
		
		$act = $this->DB->buildAndFetch( array( 'select' => "a_id, a_content, {$_type}", 'from' => 'dp3_gs_adverts', 'where' => 'a_id = ' . $id ) );

		/* Check PHP */
		
		if( ! $act['a_php_mode'] && ( $type == 'php' ) )
		{
			self::checkPHP( $act['a_content'] );
		}
				
		/* No ID? */
				
		if ( ! $this->DB->getTotalRows() )
		{
			 $this->registry->output->showError( $this->lang->words['error_no_data'], 'DP31GS_018' );
		}
		
		if( $act[ $_type ] )
		{
			$sett = 0;
		}
		else
		{
			$sett = 1;
		}

		/* Finally do what we want! */
		
		$this->DB->update( 'dp3_gs_adverts', array( $_type => $sett ) , 'a_id = ' . $id );
        
        if( $type == 'php' )
        {
          if( ! $act[ $_type ] )
          {
            $this->DB->update( 'dp3_gs_adverts', array( 'a_raw_mode' => 1 ) , 'a_id = ' . $id ); 
          }
        }
		
		/* Update cache */
			
		$this->cache->rebuildCache( 'gs_adverts', 'gsidebars' );
			
		/* Set message */
		
		$this->registry->output->global_message = $this->lang->words['successtoggled_advert_status'];	
						
		/* Redirect */	
		  
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code . '&do=manageAdverts&s_id=' . $sid );				
	}
	

	public function doUpdateAdvertsCache()
	{
		/* Update cache */
			
		$this->cache->rebuildCache( 'gs_adverts', 'gsidebars' );

		/* Are we in adverts management? */
		
		if( $this->request['s_id'] )
		{
			$url = '&do=manageAdverts&s_id=' . intval( $this->request['s_id'] );
		}
					
		/* Set message */
		
		$this->registry->output->global_message = $this->lang->words['success_adverts_cache_updated'];	
						
		/* Redirect */	
		  
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code . $url );				
	}
	

	public function doRemoveAdvert()
	{
		/* Get ID */
		
		$id 	= intval( $this->request['a_id'] );
		
		$sid	= intval( $this->request['s_id'] );
		
		if ( ! $id || ! $sid )
		{
			$this->registry->output->showError( $this->lang->words['errornoid'], 'DP31GS_019' );		
		}
		
		/* Get all info about this category */
		
		$act = $this->DB->buildAndFetch( array( 'select' => 'a_name', 'from' => 'dp3_gs_adverts', 'where' => 'a_id = ' . $id ) );
		
		/* Get all linked adverts */
								 
		$this->DB->build( array(
							'select'	=> 'a.a_id, a.a_sidebar_id',
							'from'		=> array( 'dp3_gs_adverts' => 'a' ),								 
							'where'     => 'a.a_duplicate_id = ' . $id,																																
		)	);							 
									 
		/* Execute */
		
		$q = $this->DB->execute();
		
		/* Parse */
		
		if ( $this->DB->getTotalRows( $q ) )
		{
			$duplicatedAdverts = array();
			
			while ( $row = $this->DB->fetch( $q ) )
			{
				$duplicatedAdverts[] = $row;
			}
		}			
		
		/* Delete it */
		
		$this->DB->delete( 'dp3_gs_adverts', 'a_id = ' . $id );
		$this->DB->delete( 'dp3_gs_adverts', 'a_duplicate_id = ' . $id );
		
		/* Remove it from sidebars table */
		
		self::storeAdvertInSidebar( 'remove', $id, $sid );
		
		/* Remove also duplicated adverts */
		
		if( is_array( $duplicatedAdverts ) && count( $duplicatedAdverts ) )
		{
			foreach( $duplicatedAdverts as $duplicatedAdvert )
			{
				self::storeAdvertInSidebar( 'remove', $duplicatedAdvert['a_id'], $duplicatedAdvert['a_sidebar_id'] );				
			}
		}

		/* Update caches */
	
		$this->cache->rebuildCache( 'gs_adverts', 'gsidebars' );			
		$this->cache->rebuildCache( 'gs_sidebars', 'gsidebars' );
									
		/* Set message */
		
		$this->registry->output->global_message = sprintf( $this->lang->words['messagesuccessremoved_advert'], $act['a_name'] );		
		
		/* Save log and redirect */
		
		$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['logsuccessdeleted_advert'], $act['a_name'] ) );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code . '&do=manageAdverts&s_id=' . $sid );
	}
	
	
	public function doRemoveAllAdverts()
	{			
		/* Get ID */
				
		$sid	= intval( $this->request['s_id'] );
		
		if ( ! $sid )
		{
			$this->registry->output->showError( $this->lang->words['errornoid'], 'DP31GS_020' );		
		}
				
		/* Delete all */
		
		$this->DB->delete( 'dp3_gs_adverts', 'a_sidebar_id = ' . $sid );
		
		/* Update sidebars stuff */
		
		$this->DB->update( 'dp3_gs_sidebars', array( 's_adverts' => '' ), 's_id = ' . $sid );

		/* Update cache */

		$this->cache->rebuildCache( 'gs_adverts', 'gsidebars' );			
		$this->cache->rebuildCache( 'gs_sidebars', 'gsidebars' );
					
		/* Set message */
		
		$this->registry->output->global_message = $this->lang->words['messageallsuccessdeleted_adverts'];
					
		/* Save log and redirect */
		
		$this->registry->getClass('adminFunctions')->saveAdminLog( $this->lang->words['logsuccessalldeleted_adverts'] );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code );	
	}
	
			
	public function doReorderAdverts()
	{
		/* Get ajax class */
		
		$classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax = new $classToLoad();		
		
		/* Checks... */

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}		
			
		/* Save new position */

 		$position = 1 + intval( $this->request['st'] );

 		if( is_array( $this->request['adverts'] ) AND count($this->request['adverts'] ) )
 		{
		 	foreach( $this->request['adverts'] as $this_id )
 			{
 				$this->DB->update( 'dp3_gs_adverts', array( 'a_position' => $position ), 'a_id = ' . $this_id );
 				
 				$position++;
 			}
 		}

		/* Update caches */
			
		$this->cache->rebuildCache( 'gs_adverts', 'gsidebars' );			
		$this->cache->rebuildCache( 'gs_sidebars', 'gsidebars' );
				
		/* A ok */
 		
		$ajax->returnString( 'OK' );
 		
		exit();		
	}	
	
	public function doTogglePinned()
	{
		/* INIT */
		
		$id 	= intval( $this->request['a_id'] );
		$sid	= intval( $this->request['s_id'] );
		
		/* No ID's? */
		
		if ( ! $id || ! $sid )
		{
			$this->registry->output->showError( $this->lang->words['errornoid'], 'DP31GS_026' );
		}
		
		/* Get current informations */
		
		$act = $this->DB->buildAndFetch( array( 'select' => 'a_pinned', 'from' => 'dp3_gs_adverts', 'where' => 'a_id = ' . $id ) );
		
		/* Already pinned? */
		
		if( ! $act['a_pinned'] )
		{
			/* Update */
			
			$this->DB->update( 'dp3_gs_adverts', array( 'a_pinned' => 1 ), 'a_id = ' . $id );
			$this->DB->update( 'dp3_gs_adverts', array( 'a_pinned' => 0 ), 'a_id <> ' . $id . ' AND a_sidebar_id = ' . $sid );
		}
		else
		{
			$this->DB->update( 'dp3_gs_adverts', array( 'a_pinned' => 0 ), 'a_id = ' . $id );	
		}

		/* Update cache */
			
		$this->cache->rebuildCache( 'gs_adverts', 'gsidebars' );
		
		/* Set message */
		
		$this->registry->output->global_message = $this->lang->words['message_success_toggled_pinned'];
				
		/* Redirect */	
		  
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->html->form_code . '&amp;do=manageAdverts&s_id=' . $sid );	
	}
}// End of class