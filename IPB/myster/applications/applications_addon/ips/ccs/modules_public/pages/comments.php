<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Image Ajax
 * Last Updated: $LastChangedDate: 2011-11-30 06:22:32 -0500 (Wed, 30 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: mark $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9916 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_ccs_pages_comments extends ipsCommand
{
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Figure out DB/category
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('ccs') . '/sources/pages.php', 'pageBuilder', 'ccs' );
		$pageBuilder	= new $classToLoad( $this->registry );
		$pageBuilder->loadSkinFile();
		
		$database	= intval($this->request['database']);
		$record		= intval($this->request['record']);

		$database	= $this->caches['ccs_databases'][ $database ];
		
		if( $database['database_is_articles'] )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases.php', "databaseBuilder", "ccs" );
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/articles.php', "articlesBuilder", "ccs" );
			$databases		= new $classToLoad( $this->registry, $pageBuilder );
		}
		else
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/databases.php', "databaseBuilder", "ccs" );
			$databases		= new $classToLoad( $this->registry, $pageBuilder );
		}

		$databases->database	= $database;

		if( !$databases->database['database_id'] OR !$databases->database['database_database'] )
		{
			$this->registry->output->showError( 'noappcomments', 1001351.1 );
		}
		
		if ( $this->registry->permissions->check( 'view', $databases->database ) != TRUE )
		{
			$this->registry->output->showError( 'noappcomments', 1001351.2 );
		}

		$this->lang->loadLanguageFile( array( 'public_lang' ), 'ccs' );
		$this->registry->ccsFunctions->fixStrings( $database['database_id'] );

		if( !$databases->_initDatabase( array() ) )
		{
			$this->registry->output->showError( 'noappcomments', 1001351.3 );
		}

		$databases->categories	= $this->registry->ccsFunctions->getCategoriesClass( $databases->database );

		$record		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $database['database_database'], 'where' => 'primary_id_field=' . $record ) );

		if( !$record['primary_id_field'] )
		{
			$this->registry->output->showError( 'noappcomments', 1001351.31 );
		}
			
		//-----------------------------------------
		// Get comments class
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
		$this->_comments = classes_comments_bootstrap::controller( 'ccs-records', array( 'database' => $databases->database, 'category' => $databases->categories->categories[ $record['category_id'] ], 'record' => $record ) );

		//-----------------------------------------
		// Let's go now
		//-----------------------------------------

		switch( $this->request['do'] )
		{
			case 'add':
				$this->_add();
			break;
			
			case 'delete':
				$this->_delete();
			break;
			
			case 'approve':
				$this->_approve();
			break;
			
			case 'showEdit':
				$this->_showEdit();
			break;
			
			case 'saveEdit':
				$this->_saveEdit();
			break;

			case 'moderate':
				$this->_moderate();
			break;
			
			case 'hide':
				$this->_hide();
			break;
			
			case 'unhide':
				$this->_unhide();
			break;
			
			default:
			case 'findLastComment':
				$this->_findLastComment();
			break;
			
			case 'findComment':
				$this->_findComment();
			break;
        }
    }
		
	/**
	 * Find last page of comments
	 *
	 * @return	@e void		[Redirects]
	 */
	protected function _findLastComment()
	{
		$this->registry->output->silentRedirect( $this->registry->output->buildSEOUrl( "app=ccs&module=pages&section=pages&do=redirect&database={$this->request['database']}&record={$this->request['record']}&comment=_last_", 'public' ) );
	}
	
	/**
	 * Find last page of comments
	 *
	 * @return	@e void		[Redirects]
	 */
	protected function _findComment()
	{
		$this->registry->output->silentRedirect( $this->registry->output->buildSEOUrl( "app=ccs&module=pages&section=pages&do=redirect&database={$this->request['database']}&record={$this->request['record']}&comment={$this->request['comment_id']}", 'public' ) );
	}
	
   /**
     * Moderate
     *
     * @return	@e void
     */
    protected function _moderate()
    {
 		$commentIds	= ( is_array( $_POST['commentIds'] ) ) ? IPSLib::cleanIntArray( $_POST['commentIds'] ) : array();
 		$modact		= trim( $this->request['modact'] );
 		
 		if ( count( $commentIds ) )
 		{
 			try
			{
 				$this->_comments->moderate( $modact, $this->request['record'], $commentIds, $this->memberData );	
 			
 				$this->_findLastComment();
			}
			catch( Exception $error )
			{
				$this->registry->output->showError( $error->getMessage(), 1001351.31 );
			}
 		}
 		else
 		{
 			$this->_findLastComment();
 		}
    }
    
    /**
     * Hide
     *
     * @return	@e void
     */
    protected function _hide()
    {
 		$commentId	= intval( $this->request['comment_id'] );
 		$return		= $this->_comments->hide( $this->request['record'], $commentId, $this->request['reason'], $this->memberData );
 		
 		if ( $return === TRUE )
 		{
 			$this->_findComment();
 		}
 		else
 		{
 			$this->registry->output->showError( $return, 1001351.32 );
 		}
    }
    
    /**
     * Unhide
     *
     * @return	@e void
     */
    protected function _unhide()
    {
 		$commentId	= intval( $this->request['comment_id'] );
 		$return		= $this->_comments->unhide( $this->request['record'], $commentId, $this->memberData );
 		
 		if ( $return === TRUE )
 		{
 			$this->_findComment();
 		}
 		else
 		{
 			$this->registry->output->showError( $return );
 		}
    }

	/**
	 * Deletes a comment
	 *
	 * @return	@e void
	 */
	protected function _delete()
	{
		$commentId = intval( $this->request['comment_id'] );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'posting_bad_auth_key', '1c-global-comments-_delete-0', null, null, 403 );
		}

		if ( ! $commentId OR ! $this->request['record'] )
		{
			$this->registry->output->showError( 'no_permission', '1c-global-comments-_delete-2', null, null, 403 );
		}

		//-----------------------------------------
		// Delete and redirect
		//-----------------------------------------
		
		try
		{
			$this->_comments->delete( $this->request['record'], $commentId, $this->memberData );

			if ( $this->request['modcp'] )
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=core&module=modcp&fromapp=ccs&tab=' . $this->request['modcp'] );
			}
			else
			{
				$this->_findLastComment();
			}
		}
		catch ( Exception $error )
		{
			$this->registry->output->showError( 'no_permission', '1c-global-comments-_delete-2', null, null, 403 );
		}
	}
	
	/**
	 * Approves a comment
	 *
	 * @return	@e void
	 */
	protected function _approve()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$commentId	= intval( $this->request['comment_id'] );

		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'posting_bad_auth_key', '1c-global-comments-_approve-0', null, null, 403 );
		}

		if ( ! $commentId OR ! $this->request['record'] )
		{
			$this->registry->output->showError( 'no_permission', '1c-global-comments-_approve-2', null, null, 403 );
		}
		
		//-----------------------------------------
		// Toggle
		//-----------------------------------------
		
		try
		{
			$this->_comments->visibility( 'on', $this->request['record'], $commentId, $this->memberData );
			
			if ( $this->request['modcp'] )
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=core&module=modcp&fromapp=ccs&tab=' . $this->request['modcp'] );
			}
			else
			{
				$this->_findComment();
			}
		}
		catch ( Exception $error )
		{
			$this->registry->output->showError( 'no_permission', '1c-global-comments-_approve-2', null, null, 403 );
		}
	}
	
	/**
	 * Shows the edit box
	 *
	 * @return	@e void
	 */
	protected function _showEdit()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$commentId	= intval( $this->request['comment_id'] );

		if ( ! $commentId OR ! $this->request['record'] )
		{
			$this->registry->output->showError( 'no_permission', '1c-global-comments-_showEdit-11', null, null, 404 );
		}

		//-----------------------------------------
		// Get form
		//-----------------------------------------
		
		try
		{
			$html	= $this->_comments->displayEditForm( $this->request['record'], $commentId, $this->memberData );

			$parent	= $this->_comments->remapFromLocal( $this->_comments->fetchParent( $this->request['record'] ), 'parent' );

			$this->registry->output->setTitle( $this->lang->words['edit_comment'] . ' ' . $parent['parent_title'] );
			$this->registry->output->addContent( $html );
			
			$this->registry->output->addNavigation( $this->lang->words['edit_comment'] . ' ' . $parent['parent_title'], $this->registry->ccsFunctions->returnDatabaseUrl( $this->request['database'], 0, $this->request['record'] ), '', '', 'none' );
	
			$this->registry->output->sendOutput();
		}
		catch ( Exception $error )
		{
			$this->registry->output->showError( 'no_permission', '1c-global-comments-_showEdit-0', null, null, 403 );
		}
	}
	
	/**
	 * Saves the post
	 *
	 * @return	@e void
	 */
	protected function _saveEdit()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$commentId	= intval( $this->request['comment_id'] );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'posting_bad_auth_key', '1c-global-comments-_saveEdit-0', null, null, 403 );
		}

		if ( ! $this->request['record'] OR ! $commentId )
		{
			$this->registry->output->showError( 'no_permission', '1c-global-comments-_saveEdit-1', null, null, 403 );
		}

		//-----------------------------------------
		// Save
		//-----------------------------------------

		try
		{
			$this->_comments->edit( $this->request['record'], $commentId, $_POST['Post'], $this->memberData );
			
			if ( $this->request['modcp'] )
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=core&module=modcp&fromapp=ccs&tab=' . $this->request['modcp'] );
			}
			else
			{
				$this->_findComment();
			}
		}
		catch ( Exception $error )
		{
			$this->registry->output->showError( 'no_permission', '1c-global-comments-_saveEdit-2', null, null, 403 );
		}
	}
	
	/**
	 * Add a comment via the magic and mystery of NORMAL POSTING FOOL
	 *
	 * @return	@e void
	 */
	protected function _add()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( $this->request['auth_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'posting_bad_auth_key', '1c-global-comments-_add-0', null, null, 403 );
		}

		if ( ! $this->memberData['member_id'] AND $this->settings['guest_captcha'] AND $this->settings['bot_antispam_type'] != 'none' )
		{
			if ( !$this->registry->getClass('class_captcha')->validate() )
			{
				$this->registry->output->showError( 'posting_bad_captcha', '1c-global-comments-_add-3', null, null, 403 );
			}
		}

		//-----------------------------------------
		// Save
		//-----------------------------------------

		try
		{
			$newComment = $this->_comments->add( $this->request['record'], $_POST['Post'] );
			
			if( $newComment['comment_approved'] )
			{
				$this->_findLastComment();
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['comment_approval_required'], $this->registry->output->buildSEOUrl( "app=ccs&module=pages&section=pages&do=redirect&database={$this->request['database']}&record={$this->request['record']}&comment=_last_", 'public' ) );
			}
		}
		catch( Exception $e )
		{
			$this->registry->output->showError( 'no_permission', '1c-global-comments-_add-1', null, null, 403 );
		}
	}
}
