<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS pages: displays the pages created in the ACP
 * Last Updated: $Date: 2012-02-09 11:20:16 -0500 (Thu, 09 Feb 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10284 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class public_ccs_articles_promote extends ipsCommand
{
	/**
	 * Permissions
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $permissions	= array( 'copy' => false, 'move' => false );

	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Get groups
		//-----------------------------------------
		
		$myGroups	= array( $this->memberData['member_group_id'] );
		$secondary	= IPSText::cleanPermString( $this->memberData['mgroup_others'] );
		$secondary	= $secondary ? explode( ',', $secondary ) : array();
		
		if( count($secondary) )
		{
			$myGroups	= array_merge( $myGroups, $secondary );
		}
				
		//-----------------------------------------
		// Check online/offline first
		//-----------------------------------------

		if( !$this->settings['ccs_online'] )
		{
			$show		= false;
			
			if( $this->settings['ccs_offline_groups'] )
			{
				$groups		= explode( ',', $this->settings['ccs_offline_groups'] );
				
				foreach( $myGroups as $groupId )
				{
					if( in_array( $groupId, $groups ) )
					{
						$show	= true;
						break;
					}
				}
			}
			
			if( !$show )
			{
				IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
				IPSText::getTextClass('bbcode')->parse_html			= 0;
				IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
				IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
				IPSText::getTextClass('bbcode')->parsing_section	= 'global';
		
				$this->registry->output->showError( IPSText::getTextClass('bbcode')->preDisplayParse( $this->settings['ccs_offline_message'] ), '10CCS4' );
			}
		}

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_lang' ) );
		
		//-----------------------------------------
		// Allowing post promotion?
		//-----------------------------------------
		
		if( !$this->settings['ccs_promote'] )
		{
			$this->registry->output->showError( $this->lang->words['promote_disabled'], '10CCS5', false, '', 404 );
		}
		
		//-----------------------------------------
		// Are we allowed to copy or move?
		//-----------------------------------------
		
		$_copy	= explode( ',', $this->settings['ccs_promote_g_copy'] );
		$_move	= explode( ',', $this->settings['ccs_promote_g_move'] );
		$_allow	= false;

		foreach( $myGroups as $groupId )
		{
			if( in_array( $groupId, $_copy ) )
			{
				$this->permissions['copy']	= true;
				$_allow	= true;
			}

			if( in_array( $groupId, $_move ) )
			{
				$this->permissions['move']	= true;
				$_allow	= true;
			}
		}
		
		if( !$_allow )
		{
			$this->registry->output->showError( $this->lang->words['noperm_promote'], '10CCS6' );
		}

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'doPromote':
				$this->_promote();
			break;
			
			default:
				$this->_intermediary();
			break;
		}
	}
	
	/**
	 * Intermediary screen.  This is where we confirm the details of the article, 
	 * collect any additional data we need, and let user select which method to use 
	 * (if they can use more than one method) to promote the post.
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _intermediary()
	{
		//-----------------------------------------
		// Get the post
		//-----------------------------------------
		
		$post	= $this->_getPost();
		
		//-----------------------------------------
		// Get forum library
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
		$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
		$this->registry->getClass('class_forums')->strip_invisible = 1;
		$this->registry->getClass('class_forums')->forumsInit();
		$this->memberData = IPSMember::setUpModerator( $this->memberData );
		
		//-----------------------------------------
		// Check access
		//-----------------------------------------
		
		if( !$this->registry->getClass('class_forums')->forumsCheckAccess( $post['forum_id'], false, 'topic', $post, true ) )
		{
			$this->registry->output->showError( $this->lang->words['nopost_to_promote'], '10CCS7' );
		}

		//-----------------------------------------
		// Categories
		//-----------------------------------------
		
		$database	= array();
		
		if( count($this->caches['ccs_databases']) AND is_array($this->caches['ccs_databases']) )
		{
			foreach( $this->caches['ccs_databases'] as $dbid => $_database )
			{
				if( $_database['database_is_articles'] )
				{
					$database	= $_database;
				}
			}
		}
		
		if( !$database['database_id'] or $this->registry->ccsFunctions->returnDatabaseUrl( $database['database_id'] ) == '#' )
		{
			$this->registry->output->showError( $this->lang->words['promote_no_db_id'], '10CCS8' );
		}
		
		$this->registry->ccsFunctions->fixStrings( $database['database_id'] );
		
		$database	= $this->setModerator( $database );
		
		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();

		$fields	= array();

		if( is_array($this->caches['ccs_fields'][ $database['database_id'] ]) AND count($this->caches['ccs_fields'][ $database['database_id'] ]) )
		{
			foreach( $this->caches['ccs_fields'][ $database['database_id'] ] as $_field )
			{
				if( !$_field['field_user_editable'] )
				{
					continue;
				}
			
				$fields[ $_field['field_id'] ]	= $_field;
			}
		}

		$categories	= $this->registry->ccsFunctions->getCategoriesClass( $database )->getSelectMenu( array(), 'add' );
		
		//-----------------------------------------
		// Remap for field handler...
		//-----------------------------------------
		
		foreach( $fields as $_field )
		{
			switch( $_field['field_key'] )
			{
				case 'article_title':
					$post['field_' . $_field['field_id'] ]	= $post['title'];
				break;

				case 'article_body':
					//-----------------------------------------
					// Check for attachments, and put in links as appropriate
					//-----------------------------------------
					
					$attachments	= array();
					
					$this->DB->build( array( 'select' => '*', 'from' => 'attachments', 'where' => "attach_rel_module='post' AND attach_rel_id=" . $post['pid'] ) );
					$outer	= $this->DB->execute();
					
					while( $r = $this->DB->fetch($outer) )
					{
						if( strpos( $post['post'], "attachment=" . $r['attach_id'] ) !== false )
						{
							$post['post']	= preg_replace( "/\[attachment={$r['attach_id']}:([^\]]+?)\]/ims", 
															sprintf( $this->lang->words['promote__viewattach'], $this->registry->output->buildSEOUrl( "app=core&amp;module=attach&amp;section=attach&amp;attach_rel_module=post&amp;attach_id=" . $r['attach_id'], 'public' ), $r['attach_file'] ),
															$post['post'] 
														);
						}
						else
						{
							$attachments[]	= $r;
						}
					}
					
					if( count($attachments) )
					{
						$post['post']	.= "<br /><br />";
						
						foreach( $attachments as $attach )
						{
							$post['post']	.= sprintf( $this->lang->words['promote__viewattach'], $this->registry->output->buildSEOUrl( "app=core&amp;module=attach&amp;section=attach&amp;attach_rel_module=post&amp;attach_id=" . $attach['attach_id'], 'public' ), $attach['attach_file'] ) . "<br />";
						}
						
						$post['post']	= rtrim($post['post']);
					}
					
					//-----------------------------------------
					// Set default post field
					//-----------------------------------------
					
					$post['field_' . $_field['field_id'] ]	= $post['post'];
				break;
				
				case 'article_homepage':
					$post['field_' . $_field['field_id'] ]	= ',1,';
				break;
				
				case 'article_date':
					$post['field_' . $_field['field_id'] ]	= $post['post_date'];
					
					//$post['field_' . $_field['field_id'] ]	+= ( $this->memberData['time_offset'] * 3600 );
					
					//if( $this->memberData['dst_in_use'] )
					//{
					//	$post['field_' . $_field['field_id'] ] += 3600;
					//}
				break;
				
				case 'article_expiry':
					$post['field_' . $_field['field_id'] ]	= '';
				break;
				
				case 'article_comments':
					$post['field_' . $_field['field_id'] ]	= '1';
				break;
				
				case 'article_cutoff':
					$post['field_' . $_field['field_id'] ]	= '';
				break;
			}
		}
		
		$post['_tagBox']	= $this->_getTagBox( $database, $post );

		//-----------------------------------------
		// Data hook
		//-----------------------------------------
		
		IPSLib::doDataHooks( $post, 'ccsPrePromote' );

		//-----------------------------------------
		// Good to go?  Output form then
		//-----------------------------------------
		
		$this->registry->output->setTitle( $this->lang->words['promote_post_to_article'] );
		$this->registry->output->addContent( $this->registry->output->getTemplate('ccs_global')->promoteForm( $post, $categories, $database, $fields, $fieldsClass, $this->permissions ) );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Promote the post to an article
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _promote()
	{
		//-----------------------------------------
		// Check secure key
		//-----------------------------------------

		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', '20CCS1.1', null, null, 403 );
		}

		//-----------------------------------------
		// Get the post
		//-----------------------------------------
		
		$post	= $this->_getPost();
				
		//-----------------------------------------
		// Method
		//-----------------------------------------
		
		$_method	= '';
		
		if( $this->permissions['copy'] AND !$this->permissions['move'] )
		{
			$_method	= 'copy';
		}
		else if( !$this->permissions['copy'] AND $this->permissions['move'] )
		{
			$_method	= 'move';
		}
		else
		{
			$_method	= in_array( $this->request['promote_method'], array( 'copy', 'move' ) ) ? $this->request['promote_method'] : 'copy';
		}
		
		//-----------------------------------------
		// Get forum library
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php", 'class_forums', 'forums' );
		$this->registry->setClass( 'class_forums', new $classToLoad( $this->registry ) );
		$this->registry->getClass('class_forums')->strip_invisible = 1;
		$this->registry->getClass('class_forums')->forumsInit();
		$this->memberData = IPSMember::setUpModerator( $this->memberData );
		
		//-----------------------------------------
		// Check access
		//-----------------------------------------
		
		if( !$this->registry->getClass('class_forums')->forumsCheckAccess( $post['forum_id'], false, 'topic', $post, true ) )
		{
			$this->registry->output->showError( $this->lang->words['nopost_to_promote'], '10CCS9' );
		}

		//-----------------------------------------
		// Categories
		//-----------------------------------------
		
		$database	= array();
		
		if( count($this->caches['ccs_databases']) AND is_array($this->caches['ccs_databases']) )
		{
			foreach( $this->caches['ccs_databases'] as $dbid => $_database )
			{
				if( $_database['database_is_articles'] )
				{
					$database	= $_database;
				}
			}
		}
		
		if( !$database['database_id'] )
		{
			$this->registry->output->showError( $this->lang->words['promote_no_db_id'], '10CCS10' );
		}
		
		$this->registry->ccsFunctions->fixStrings( $database['database_id'] );
		
		$database	= $this->setModerator( $database );
		
		//-----------------------------------------
		// Get possible field types
		//-----------------------------------------
		
		$fieldsClass	= $this->registry->ccsFunctions->getFieldsClass();

		$fields			= array();
		$_commends		= '';

		if( is_array($this->caches['ccs_fields'][ $database['database_id'] ]) AND count($this->caches['ccs_fields'][ $database['database_id'] ]) )
		{
			foreach( $this->caches['ccs_fields'][ $database['database_id'] ] as $_field )
			{
				$fields[ $_field['field_id'] ]	= $_field;
				
				if( $_field['field_key'] == 'article_comments' )
				{
					$_comments	= $_field['field_id'];
				}
			}
		}
				
		//-----------------------------------------
		// Category
		//-----------------------------------------
		
		$libCategories	= $this->registry->ccsFunctions->getCategoriesClass( $database );

		if( count($libCategories->categories) AND !$this->request['category_id'] )
		{
			$this->registry->output->showError( $this->lang->words['promote_no_catid'], '10CCS11' );
		}
		
		$_cat	= $libCategories->categories[ $this->request['category_id'] ];
		
		//-----------------------------------------
		// Start save array
		//-----------------------------------------

		$this->settings['post_key']	= $this->request['post_key'];
		
		$_save	= array( 'post_key' => $this->request['post_key'] );
		
		//-----------------------------------------
		// Remove non-user-editable fields
		//-----------------------------------------
		
		foreach( $fields as $_field )
		{
			if( !$_field['field_user_editable'] )
			{
				$_save['field_' . $_field['field_id'] ] = $_field['field_default_value'];
			}
			else
			{
				$_save['field_' . $_field['field_id'] ]	= $fieldsClass->processInput( $_field );
			}
		}

		//-----------------------------------------
		// Any errors?
		//-----------------------------------------
		
		if( $error = $fieldsClass->getError() )
		{
			//-----------------------------------------
			// Cat lib
			//-----------------------------------------
			
			$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : 0;

			$categories	= $libCategories->getSelectMenu( array(), 'add' );
			
			$post['_tagBox']	= $this->_getTagBox( $database, $post );

			$this->registry->output->setTitle( $this->lang->words['promote_post_to_article'] );
			$this->registry->output->addContent( $this->registry->output->getTemplate('ccs_global')->promoteForm( array_merge( $post, $_POST ), $categories, $database, $fields, $fieldsClass, $this->permissions, false, $error ) );
			$this->registry->output->sendOutput();
			
			return;
		}
					
		foreach( $fields as $field )
		{
			$this->DB->setDataType( 'field_' . $field['field_id'], 'string' );
		}
		
		//-----------------------------------------
		// Some other data for add
		//-----------------------------------------
		
		$_save['member_id']				= $post['author_id'];
		$_save['record_saved']			= time();
		$_save['record_updated']		= time();
		$_save['category_id']			= $_cat['category_id'];
		$_save['record_dynamic_furl']	= IPSText::makeSeoTitle( $fieldsClass->getFieldValue( $fields[ str_replace( 'field_', '', $database['database_field_title'] ) ], $_save, $fields[ str_replace( 'field_', '', $database['database_field_title'] ) ]['field_truncate'] ) );
		$_save['record_approved']		= 1;
		$_save['record_topicid']		= 0;
		
		//-----------------------------------------
		// Tagging
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('ccsTags-' . $database['database_id'] ) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'ccsTags-' . $database['database_id'], classes_tags_bootstrap::run( 'ccs', 'records-' . $database['database_id'] ) );
		}

		//-----------------------------------------
		// Check if we're ok with tags
		//-----------------------------------------
		
		$where		= array( 'meta_parent_id'	=> $_save['category_id'],
							 'member_id'		=> $this->memberData['member_id'],
							 'existing_tags'	=> explode( ',', IPSText::cleanPermString( $_POST['ipsTags'] ) ) );
									  
		if ( $this->registry->getClass('ccsTags-' . $database['database_id'] )->can( 'add', $where ) AND $this->settings['tags_enabled'] AND ( !empty( $_POST['ipsTags'] ) OR $this->settings['tags_min'] ) )
		{
			$this->registry->getClass('ccsTags-' . $database['database_id'] )->checkAdd( $_POST['ipsTags'], array(
																  'meta_parent_id' => $_save['category_id'],
																  'member_id'	   => $this->memberData['member_id'],
																  'meta_visible'   => $_save['record_approved'] ) );

			if ( $this->registry->getClass('ccsTags-' . $database['database_id'] )->getErrorMsg() )
			{
				foreach( $fields as $_field )
				{
					$this->fieldsClass->onErrorCallback( $_field );
				}
	
				$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : 0;
	
				$categories	= $libCategories->getSelectMenu( array(), 'add' );
				
				$post['_tagBox']	= $this->_getTagBox( $database, $post );
		
				$this->registry->output->setTitle( $this->lang->words['promote_post_to_article'] );
				$this->registry->output->addContent( $this->registry->output->getTemplate('ccs_global')->promoteForm( array_merge( $post, $_POST ), $categories, $database, $fields, $fieldsClass, $this->permissions, false, $this->registry->getClass('ccsTags-' . $database['database_id'] )->getFormattedError() ) );
				$this->registry->output->sendOutput();
				
				return;
			}
		}
		
		//-----------------------------------------
		// Extra details
		//-----------------------------------------
		
		if( $database['moderate_extras'] )
		{
			$_save['record_static_furl']		= IPSText::makeSeoTitle( trim($this->request['record_static_furl']) );
			$_save['record_meta_keywords']		= trim($this->request['record_meta_keywords']);
			$_save['record_meta_description']	= trim($this->request['record_meta_description']);
			$_save['record_template']		= intval($this->request['article_template']);

			if( $this->request['record_authorname'] )
			{
				$member					= IPSMember::load( $this->request['record_authorname'], 'members', 'displayname' );
				$_save['member_id']		= $member['member_id'] ? $member['member_id'] : $_save['member_id'];
			}
			
			if( $_save['record_static_furl'] )
			{
				$_check	= $this->DB->buildAndFetch( array( 'select' => 'primary_id_field', 'from' => $database['database_database'], 'where' => "record_static_furl='{$_save['record_static_furl']}'" ) );
				
				if( $_check['primary_id_field'] )
				{
					foreach( $fields as $_field )
					{
						$this->fieldsClass->onErrorCallback( $_field );
					}
		
					$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : 0;
		
					$categories	= $libCategories->getSelectMenu( array(), 'add' );
					
					$post['_tagBox']	= $this->_getTagBox( $database, $post );
			
					$this->registry->output->setTitle( $this->lang->words['promote_post_to_article'] );
					$this->registry->output->addContent( $this->registry->output->getTemplate('ccs_global')->promoteForm( array_merge( $post, $_POST ), $categories, $database, $fields, $fieldsClass, $this->permissions, false, $this->lang->words['static_furl_in_use'] ) );
					$this->registry->output->sendOutput();
					
					return;
				}
			}
		}
		
		//-----------------------------------------
		// Topic association
		//-----------------------------------------
		
		if( $this->settings['ccs_promote_associate'] )
		{
			$forumIntegration	= $_cat['category_forum_override'] ? $_cat['category_forum_record'] : $database['database_forum_record'];
			$commentsToo		= $_cat['category_forum_override'] ? $_cat['category_forum_comments'] : $database['database_forum_comments'];

			if( trim( $database['perm_5'], ' ,' ) AND $_save['field_' . $_comments ] AND $forumIntegration AND $commentsToo )
			{
				switch( $this->settings['ccs_promote_associate'] )
				{
					case 1:
						if( $this->request['promote_associate'] )
						{
							$_save['record_topicid']	= $post['tid'];
							$_save['record_comments']	= $post['posts'];
						}
					break;
					
					case 2:
						$_save['record_topicid']	= $post['tid'];
						$_save['record_comments']	= $post['posts'];
					break;
				}
			}
		}
		
		//-----------------------------------------
		// Previewing?
		//-----------------------------------------

		if( $this->request['preview'] )
		{
			$this->request['category']	= $this->request['category_id'] ? $this->request['category_id'] : 0;

			$categories	= $libCategories->getSelectMenu( array(), 'add' );
			
			$post['_tagBox']	= $this->_getTagBox( $database, $post );
	
			$this->registry->output->setTitle( $this->lang->words['promote_post_to_article'] );
			$this->registry->output->addContent( $this->registry->output->getTemplate('ccs_global')->promoteForm( array_merge( $post, $_POST ), $categories, $database, $fields, $fieldsClass, $this->permissions, true ) );
			$this->registry->output->sendOutput();
			
			return;
		}

		//-----------------------------------------
		// Data hook
		//-----------------------------------------
		
		IPSLib::doDataHooks( $_save, 'ccsPrePromoteSave' );

		//-----------------------------------------
		// Insert and recount
		//-----------------------------------------
		
		$this->DB->insert( $database['database_database'], $_save );
		
		$id	= $this->DB->getInsertId();
		
		$_save['primary_id_field']	= $id;
		
		if( $_save['record_approved'] )
		{
			$this->DB->update( 'ccs_databases', array( 'database_record_count' => ($database['database_record_count'] + 1) ), 'database_id=' . $database['database_id'] );
		}
		
		//-----------------------------------------
		// Post process
		//-----------------------------------------
		
		foreach( $fields as $field )
		{
			$fieldsClass->postProcessInput( $field, $id );
		}

		//-----------------------------------------
		// Store tags
		//-----------------------------------------
		
		if ( ! empty( $_POST['ipsTags'] ) )
		{
			$this->registry->getClass('ccsTags-' . $database['database_id'] )->add( $_POST['ipsTags'], array( 'meta_id'			=> $_save['primary_id_field'],
												      				 'meta_parent_id'	=> $_save['category_id'],
												      				 'member_id'		=> $_save['member_id'],
												      				 'meta_visible'		=> $_save['record_approved'] ) );
		}

		//-----------------------------------------
		// Update category
		//-----------------------------------------
		
		$libCategories->recache( $_save['category_id'] );
		
	    //-----------------------------------------
	    // Send out appropriate notifications
	    //-----------------------------------------
	    
	    if( $_save['record_approved'] )
	    {
			//-----------------------------------------
			// Post topic if configured to do so
			//-----------------------------------------
			
			if( !$_save['record_topicid'] )
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/topics.php', 'topicsLibrary', 'ccs' );
				$_topics		= new $classToLoad( $this->registry, $fieldsClass, $libCategories, $fields );
				$_topics->postTopic( $_save, $libCategories->categories[ $_save['category_id'] ], $database );
			}

			$_save['poster_name']	= $this->memberData['members_display_name'];
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases.php', 'databaseBuilder', 'ccs' );
			$databases		= new $classToLoad( $this->registry );
			$databases->sendRecordNotification( $database, $libCategories->categories[ $_save['category_id'] ], $_save );
	    }
	    else
	    {
			$_save['poster_name']	= $this->memberData['members_display_name'];
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases.php', 'databaseBuilder', 'ccs' );
			$databases		= new $classToLoad( $this->registry );
			$databases->sendRecordPendingNotification( $database, $libCategories->categories[ $_save['category_id'] ], $_save );
	    }
		
	    $url	= $this->registry->ccsFunctions->returnDatabaseUrl( $database['database_id'], 0, $_save );

		//-----------------------------------------
		// Social sharing
		//-----------------------------------------

		if( $_save['record_approved'] )
		{
			IPSMember::sendSocialShares( array( 'title' => $fieldsClass->getFieldValue( $fields[ str_replace( 'field_', '', $database['database_field_title'] ) ], $_save, $fields[ str_replace( 'field_', '', $database['database_field_title'] ) ]['field_truncate'] ), 'url' => $url ) );
		}

		//-----------------------------------------
		// Data hook
		//-----------------------------------------
		
		IPSLib::doDataHooks( $_save, 'ccsPostPromoteSave' );

		//-----------------------------------------
		// If we are copying...
		//-----------------------------------------
	    
	    if( $_method == 'copy' )
	    {
	    	if( $this->settings['ccs_promote_link'] )
	    	{
	    		if( !$this->settings['ccs_promote_nolink'] OR !$this->request['promote_no_link'] )
	    		{
	    			$post['post']	= $post['post'] . $this->registry->output->getTemplate('ccs_global')->promoteLinkAppend( $post, $_save, $url );
	    			
	    			$this->DB->update( "posts", array( 'post' => $post['post'] ), "pid=" . $post['pid'] );
	    			$this->DB->delete( "content_cache_posts", "cache_content_id=" . $post['pid'] );
	    		}
	    	}
	    }
	    
		//-----------------------------------------
		// Otherwise, if we are moving...
		//-----------------------------------------
	    
	    else
	    {
	    	if( $this->settings['ccs_promote_link'] )
	    	{
	    		if( !$this->settings['ccs_promote_nolink'] OR !$this->request['promote_no_link'] )
	    		{
	    			$post['post']	= $this->registry->output->getTemplate('ccs_global')->promoteLinkReplace( $post, $_save, $url );
	    			
	    			$this->DB->update( "posts", array( 'post' => $post['post'] ), "pid=" . $post['pid'] );
	    			$this->DB->delete( "content_cache_posts", "cache_content_id=" . $post['pid'] );
	    		}
	    		else if( !$_save['record_topicid'] )
	    		{
	    			$this->_deletePost( $post );
	    		}
	    	}
	    	else if( !$_save['record_topicid'] )
	    	{
	    		$this->_deletePost( $post );
	    	}
	    }
	    
		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		$this->registry->output->redirectScreen( $this->lang->words['post_promoted_to_article'], $url );
	}
	
	/**
	 * Delete the post ("move" method)
	 *
	 * @access	protected
	 * @param	array 		Post data
	 * @return	@e void
	 */
	protected function _deletePost( $post )
	{
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . "/sources/classes/moderate.php", 'moderatorLibrary', 'forums' );
		$moderatorLib	= new $classToLoad( $this->registry );
		$moderatorLib->init( $this->registry->getClass('class_forums')->forum_by_id[ $post['forum_id'] ], $post );
		
		if( $post['pid'] == $post['topic_firstpost'] )
		{
			$moderatorLib->topicDelete( $post['tid'] );
		}
		else
		{
			$moderatorLib->postDelete( $post['pid'] );
		}
	}
	
	/**
	 * Get the post
	 *
	 * @access	protected
	 * @return	array
	 */
	protected function _getPost()
	{
		$pid	= intval($this->request['post']);
		$post	= array();
		
		if( $pid )
		{
			$post	= $this->DB->buildAndFetch( array( 
													'select'	=> 'p.*',
													'from'		=> array( 'posts' => 'p' ),
													'where'		=> 'p.pid=' . $pid,
													'add_join'	=> array(
																		array(
																			'select'	=> 't.*',
																			'from'		=> array( 'topics' => 't' ),
																			'where'		=> 't.tid=p.topic_id',
																			'type'		=> 'left',
																			)
																		)
											)		);
		}
		
		if( !$post['pid'] )
		{
			$this->registry->output->showError( $this->lang->words['nopost_to_promote'], '10CCS12' );
		}

		$post['post_key']			= md5( 'ccs' . $post['post_key'] );
		$this->settings['post_key']	= $post['post_key'];
		
		return $post;
	}
	
	/**
	 * Get tag box
	 * 
	 * @param	array	Database data
	 * @param	array	Post data
	 * @return	@e string
	 */
 	protected function _getTagBox( $database, $post )
 	{
		//-----------------------------------------
		// Tagging
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('ccsTags-' . $database['database_id'] ) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'ccsTags-' . $database['database_id'], classes_tags_bootstrap::run( 'ccs', 'records-' . $database['database_id'] ) );
		}

		//-----------------------------------------
		// Tagging
		//-----------------------------------------

		$_tagBox	= '';

		$where = array( 'meta_parent_id'	=> $this->request['category_id'],
						'member_id'			=> $this->memberData['member_id'],
						);

		if( $post['pid'] )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'core_tags', 'where' => "tag_meta_app='forums' AND tag_meta_area='topics' AND tag_meta_id=" . intval($post['topic_id']) ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$where['existing_tags'][]	= $r['tag_text'];
			}
		}
		
		if( isset($_REQUEST['ipsTags']) )
		{
			$where['existing_tags']	= explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) );
		}
		
		if ( $this->registry->getClass('ccsTags-' . $database['database_id'] )->can( 'add', $where ) )
		{
			$_tagBox = $this->registry->getClass('ccsTags-' . $database['database_id'] )->render( 'entryBox', $where );
		}
		
		return $_tagBox;
 	}
 	
 	/**
 	 * Set moderator permission
 	 * 
 	 * @param	array	Database
 	 * @return	@e array
 	 */
	protected function setModerator( $database )
	{
		//-----------------------------------------
		// Supermods can moderate
		//-----------------------------------------
		
		if( $this->memberData['g_is_supmod'] )
		{
			$database['moderate_extras']	= 1;
			
			return $database;
		}
		
		//-----------------------------------------
		// Cache, in case we get called more than once
		//-----------------------------------------
		
		static $moderators	= array();
		static $modChecked	= false;
		
		if( !$modChecked )
		{
			$modChecked	= true;
			
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_moderators', 'where' => 'moderator_database_id=' . $database['database_id'] ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$moderators[ $r['moderator_type'] ][]	= $r;
			}
		}
		
		//-----------------------------------------
		// Check group perms first
		//-----------------------------------------
		
		if( is_array($moderators['group']) AND count($moderators['group']) )
		{
			$_myGroups	= array( $this->memberData['member_group_id'] );
			
			if( $this->memberData['mgroup_others'] )
			{
				$_others	= explode( ',', IPSText::cleanPermString( $this->memberData['mgroup_others'] ) );
				$_myGroups	= array_merge( $_myGroups, $_others );
			}
			
			foreach( $moderators['group'] as $_moderator )
			{
				if( in_array( $_moderator['moderator_type_id'], $_myGroups ) )
				{
					$database['moderate_extras']	= $_moderator['moderator_extras'];
				}
			}
		}
		
		//-----------------------------------------
		// Then individual member mod perms
		//-----------------------------------------
		
		if( is_array($moderators['member']) AND count($moderators['member']) )
		{
			foreach( $moderators['member'] as $_moderator )
			{
				if( $_moderator['moderator_type_id'] == $this->memberData['member_id'] )
				{
					$database['moderate_extras']	= $_moderator['moderator_extras'];
				}
			}
		}
		
		return $database;
	}
}

