<?php

if ( !defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_badges_manage_main extends ipsCommand
{
	private $html;
	private $form_code;
	private $form_code_js;
	
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Load skin */
		$this->html			= $this->registry->output->loadTemplate('cp_skin_badges');
		
		/* Set up stuff */
		$this->form_code	= $this->html->form_code	= 'module=manage&amp;section=main';
		$this->form_code_js	= $this->html->form_code_js	= 'module=manage&section=main';
		
		/* Load lang */
		$this->lang->loadLanguageFile( array( 'admin_badges' ) );
		
		switch( $this->request['do'] )
		{
			case 'main':
				$this->overview();
			break;
			
			case 'newBadges':
				$this->badgesForm( 'new' );
			break;
			
			case 'editBadges':
				$this->badgesForm( 'edit' );
			break;
			
			case 'doBadges':
				$this->processForm();
			break;
			
			case'deleteBadges':
				$this->deleteBadges();
			break;
			
			case 'runBadges':
				$this->runBadge();
			break;
			
			default:
				$this->overview();
			break;
		}
			
		/* Pass to CP output hander */
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	private function overview()
	{
		/* INIT */
		$badges = array();
		
		/* Basic admin page stuff */
		$this->registry->output->html_help_title  = sprintf( $this->lang->words['main_page_title'], $this->lang->words['mod_title'] );
		$this->registry->output->html_help_msg = $this->lang->words['main_page_detail'];
		
		/* Query for the badges? */
		$this->DB->build( array( 'select' => '*', 'from' => 'HQ_badges', 'order' => 'ba_id' ) );
		$this->DB->execute();
		
		foreach( $this->registry->cache()->getCache('group_cache') as $g_id => $group )
		{
			$mem_group2[] = array( $g_id , $group['g_title'] );
		}
		
		while( $query = $this->DB->fetch() )
		{
			$img	= ( $query['ba_enabled'] ) ? 'tick.png' : 'cross.png'; 
			
			if( count( explode(',', $query['ba_gid'] ) ) )
				{
					foreach( explode(',', $query['ba_gid'] ) as $g_id )
					{
						$query['ba_gid_s'] .= IPSMember::makeNameFormatted( $mem_group[ $g_id ], $g_id ) . ', ';
					}
					
					/* Cut last 2 chars ( remove , ) */

					$query['ba_gid_s'] = substr( $query['ba_gid_s'], 0, strlen( $query['ba_gid_s'] ) - 2 );
				}
			
			$badges[] = $query;
		}
		
		/* Build the table */
		$this->registry->output->html .= $this->html->list_badges( $badges );
	}
	
	private function badgesForm( $type='new' )
	{
		/* INIT */
		$query  = array();
		$plugin = array();
		$id     = intval( $this->request['id'] );
		
		/* Sort out what we're doing with this form */
		if ( $type == 'edit' )
		{
			$title = $this->lang->words['edit_Badges'];
			$blurb = $this->lang->words['Badges_form_detail_edit'];
			
			if ( !$id )
			{
				$this->registry->output->global_message = $this->lang->words['no_found_Badges'];
				$this->overview();
				return;
			}
			
			$query = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'HQ_badges', 'where' => 'ba_id='.$id ) );

			if ( !$query['ba_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['no_found_Badges'];
				$this->overview();
				return;
			}
		}
		else
		{
			$title = $this->lang->words['create_Badges'];
			$blurb = $this->lang->words['Badges_form_detail_new'];
		}
		
		/* Post key */
		$query['post_key'] = $this->request['post_key'] ? $this->request['post_key'] : md5( microtime() );

		/* Modules */
		$this->DB->build( array( 'select' => '*', 'from' => 'HQ_badges_modules') );
		$this->DB->execute();

		$i=0;
		while( $r = $this->DB->fetch() )
		{
			$plugin[$i]['0'] = $r['ba_mid'];
			$plugin[$i]['1'] = $r['ba_mname'];
			$i++;
		}

		/* Page Info */
		$this->registry->output->html_help_title  = sprintf( $title, $this->lang->words['mod_title'] );
		$this->registry->output->html_help_msg = $this->lang->words['Badges_form_detail_'.$type];		
		$this->registry->output->extra_nav[] = array( '', $title );
		
		/* Build the query form */
		$this->registry->output->html .= $this->html->badges_form( $query, $plugin, $type, $title, $blurb );
	}
	
	private function processForm()
	{
		/* Which type? */
		$type = ( $this->request['type'] == 'edit' ) ? 'edit' : 'new';
		/* Clean inputs */
		$id       = intval( $this->request['id'] );
		$enable   = intval( $this->request['enabled'] );
		$second	  = intval( $this->request['second']);
		$groups	  = (!empty($this->request['groups'])) ? implode(',',$this->request['groups']) : NULL;
		$forums	  = (!empty($this->request['forums'])) ? implode(',',$this->request['forums']) : NULL;
		$icon	  = $this->request['icon'];
		$post_key = trim( $this->request['post_key'] );
		$link 	  = trim( $this->request['link'] );
		$cstyle	  = trim( $this->request['cstyle'] );
		$image 	  = trim( $this->request['image'] );
		$uploadimg   = $this->request['upload'];
		
		/* Initial error checking */
		if ( !$groups )
		{
			$this->registry->output->global_message = $this->lang->words['no_Badges_groups'];
			$this->badgesForm( $type );
			return;
		}
        echo $this->request['upload'];
		$nome = rand(1,99999)."-badge";
		
		if ( ! is_object( $upload ) )
		{
			require_once( IPS_KERNEL_PATH . 'classUpload.php' );/*noLibHook*/
			$upload    = new classUpload();
		}
			$upload->out_file_name		= $nome.'-'.$uploadimg->$original_file_name;
			$upload->out_file_dir		= $this->settings['upload_dir'] . '/badges';
			$upload->max_file_size		= 1000 * 1000;
			$upload->upload_form_field 	= 'upload';
			$upload->make_script_safe  = 1;
			$upload->allowed_file_ext   = array( 'jpg', 'jpeg', 'png', 'gif' );
			$noup = 0;
			$upload->process();

			# Error?
			if ( $upload->error_no )
			{
				switch( $upload->error_no )
				{
					case 1:
						// No 
						if ( $type != 'edit' ) {
							$error = 'error_upload_no_file';
							break;
						} else {
							$noup = 1;
						}
					case 2:
						if ( $noup == 0 ) {
							// Invalid file ext
							$error = 'error_invalid_mime_type';
							break;
						}
					case 3:
						if ( $noup == 0 ) {
							// Too big...
							$error = 'error_upload_too_big';
							break;
						}
					case 4:
						if ( $noup == 0 ) {
							// Cannot move uploaded file
							$error = 'error_upload_failed';
							break;
						}
					case 5:
						if ( $noup == 0 ) {
							// Possible XSS attack (image isn't an image)
							$error = 'error_upload_failed';
							break;
						}
				}
			if ( $noup == 0 ) 
			$this->registry->output->showError( $error );

			}

			if ( $type == 'edit' )
			{
				# Delete the old icon
				$old 	= $this->DB->buildAndFetch( array(
										 'select'   => 'ba_image',
								 		 'from'     => 'HQ_badges',
								 		 'where'    => 'ba_id = '.$id,
				) );
				$uDir = $this->settings['upload_dir'] . '/badges';
				if ($noup == 0)
				@unlink( $uDir . '/' . $old['ba_image'] );
			}
			if ( $noup == 0 ) {
				$img = $upload->parsed_file_name;
			} else {
				$img = $old['ba_image'];
			}
		/* Build our data to save */
		$data = array( 'ba_gid'     => $groups,
					   'ba_type'    => $icon,
					   'ba_enabled' => $enable,
					   'ba_sg'      => $second,
					   'ba_forums' 	=> $forums,
					   'ba_links'	=> $link,
					   'ba_image'	=> $img,
					   'ba_cstyle'	=> $cstyle,
					 );
		
		/* Edit?  Or New? */
		if ( $type == 'edit' )
		{
			if ( !$id )
			{
				$this->registry->output->global_message = $this->lang->words['no_Badges_id'];
				$this->badgesForm( $type );
				return;
			}
			$this->DB->update( 'HQ_badges', $data, 'ba_id='.$id );
		}
		else
		{
			$this->DB->insert( 'HQ_badges', $data );
			$id = $this->DB->getInsertId();
		}
		
		/* Log the action and redirect */
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['mod_title'].": ".$this->lang->words['message_'.$type]." '".$id."'" );
		
		$this->registry->output->global_message = $this->lang->words['message_'.$type]." '".$id."'";
		$this->overview();
		return;
	}
	
	private function deleteBadges()
	{
		/* Clean the input */
		$id = intval( $this->request['id'] );
		
		/* Error checking */
		if ( !$id )
		{
			$this->registry->output->global_message = $this->lang->words['no_found_Badges'];
			$this->overview();
			return;
		}
		
		/* Pull the info for this query */
		$query = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'HQ_badges', 'where' => 'ba_id='.$id ) );
		
		/* Make sure there is something to delete */
		if ( !$query['ba_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['no_found_Badges'];
			$this->overview();
			return;
		}
		
		/* Delete the query */
		$this->DB->delete( 'HQ_badges', 'ba_id='.$id );

		# Delete the old icon
		$uDir = $this->settings['upload_dir'] . '/badges';
		@unlink( $uDir . '/' . $query['ba_image'] );
		
		/* Log the action and redirect */
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['mod_title'].": ".$this->lang->words['deleted_Badges']." '".$query['ba_id']."'" );
			
		$this->registry->output->global_message = $this->lang->words['deleted_Badges']." '".$query['ba_id']."'";
		$this->overview();
		return;
	}
	
}