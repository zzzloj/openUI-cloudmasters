<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Image Ajax
 * Last Updated: $LastChangedDate: 2011-11-30 09:29:33 -0500 (Wed, 30 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: mark $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Gallery
 * @link		http://www.invisionpower.com
 * @version		$Rev: 9917 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_ccs_ajax_comments extends ipsAjaxCommand
{
	/**
	 * Comments handler
	 * 
	 * @param	object
	 */
 	protected $_comments;

	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void		[Outputs to screen]
	 * @todo	Get proper db/category data
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
		
		$record		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => $database['database_database'], 'where' => 'primary_id_field=' . $record ) );
		
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
			$this->returnJsonError( 'no_permission' );
		}
		
		if ( $this->registry->permissions->check( 'view', $databases->database ) != TRUE )
		{
			$this->returnJsonError( 'no_permission' );
		}

		$this->lang->loadLanguageFile( array( 'public_lang' ), 'ccs' );
		$this->registry->ccsFunctions->fixStrings( $database['database_id'] );

		if( !$databases->_initDatabase( array() ) )
		{
			$this->returnJsonError( 'no_permission' );
		}

		$databases->categories	= $this->registry->ccsFunctions->getCategoriesClass( $databases->database );
			
		//-----------------------------------------
		// Get comments class
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
		$this->_comments = classes_comments_bootstrap::controller( 'ccs-records', array( 'database' => $databases->database, 'category' => $databases->categories->categories[ $record['category_id'] ] ) );

		//-----------------------------------------
		// And ACTION
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'add':
				$this->_add();
			break;
			case 'delete':
				$this->_delete();
			break;
			case 'showEdit':
				$this->_showEdit();
			break;
			case 'saveEdit':
				$this->_saveEdit();
			break;
			case 'fetchReply':
				$this->_fetchReply();
			break;
			case 'moderate':
				$this->_moderate();
			break;
        }
    }
    
    /**
     * Moderate
     *
     * @return	@e void
     * @todo	Whole function
     */
    protected function _moderate()
    {
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
    	$parentId	= intval( $this->request['record'] );
 		$commentIds	= ( is_array( $_POST['commentIds'] ) ) ? IPSLib::cleanIntArray( $_POST['commentIds'] ) : array();
 		$modact		= trim( $this->request['modact'] );
 		 		
 		if ( count( $commentIds ) )
 		{
 			try
			{				
				if ( $modact == 'hide' )
				{
					if( $this->_comments->hide( $parentId, $commentIds, NULL, $this->memberData ) !== true )
					{
						throw new Exception( $r );
					}
				}
				elseif ( $modact == 'unhide' )
				{
					if( $this->_comments->unhide( $parentId, $commentIds, $this->memberData ) !== true )
					{
						throw new Exception( $r );
					}
				}
				else
				{
 					$this->_comments->moderate( $modact, $parentId, $commentIds, $this->memberData );	
 				}
 			
 				$this->returnJsonArray( array( 'msg' => 'ok' ) );
			}
			catch( Exception $error )
			{
				$this->returnJsonError( 'Error ' . $error->getMessage() . ' line: ' . $error->getFile() . '.' . $error->getLine() );
			}
 		}
    }
    
    /**
	 * Fetch a reply for quoting
	 *
	 * @return	@e void
	 * @todo	Whole function
	 */
	protected function _fetchReply()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$commentId	= intval( $this->request['comment_id'] );
		$parentId	= intval( $this->request['record'] );

		if ( ! $commentId OR ! $parentId )
		{
			$this->returnJsonError( 'no_permission' );
		}
		
		//-----------------------------------------
		// Get editor
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$_editor		= new $classToLoad();

		try
		{
			$_editor->setContent( $this->_comments->fetchReply( $parentId, $commentId, $this->memberData, 'topics', true ) );
		
			$this->returnHtml( $_editor->getContent() );
		}
		catch ( Exception $error )
		{
			$this->returnString( 'Error ' . $error->getMessage() );
		}
	}

	/**
	 * Deletes a comment
	 *
	 * @return	@e void
	 * @todo	Whole function
	 */
	protected function _delete()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$commentId	= intval( $this->request['comment_id'] );
		$parentId	= intval( $this->request['record'] );

		if ( ! $commentId OR ! $parentId )
		{
			$this->returnJsonError( 'no_permission' );
		}
		
		//-----------------------------------------
		// Delete hte comment
		//-----------------------------------------
		
		try
		{
			$this->_comments->delete( $parentId, $commentId, $this->memberData );
			
			$this->returnJsonArray( array( 'msg' => 'ok' ) );
		}
		catch ( Exception $error )
		{
			$this->returnJsonError( 'Error ' . $error->getMessage() );
		}
	}
	
	/**
	 * Shows the edit box
	 *
	 * @return	@e void
	 * @todo	Whole function
	 */
	protected function _showEdit()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$commentId	= intval( $this->request['comment_id'] );
		$parentId	= intval( $this->request['record'] );

		if ( ! $commentId OR ! $parentId )
		{
			$this->returnJsonError( 'no_permission' );
		}
		
		//-----------------------------------------
		// Get edit form
		//-----------------------------------------
		
		try
		{
			$this->returnHtml( $this->registry->output->replaceMacros( $this->_comments->displayAjaxEditForm( $parentId, $commentId, $this->memberData ) ) );
		}
		catch ( Exception $error )
		{
			$this->returnString( 'Error ' . $error->getMessage() );
		}
	}
	
	/**
	 * Saves the post
	 *
	 * @return	@e void
	 * @todo	Whole function
	 */
	protected function _saveEdit()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$commentId	= intval( $this->request['comment_id'] );
		$parentId	= intval($this->request['record']);

		if ( ! $parentId OR ! $commentId )
		{
			$this->returnJsonError( 'no_permission' );
		}

		//-----------------------------------------
		// Save the edit
		//-----------------------------------------

		try
		{
			$this->returnJsonArray( array( 'successString' => $this->registry->output->replaceMacros( $this->_comments->edit( $parentId, $commentId, $_POST['Post'], $this->memberData ) ) ) );
		}
		catch ( Exception $error )
		{
			$this->returnJsonError( $error->getMessage() );
		}
	}
	
	/**
	 * Add a comment via the magic and mystery of ajax
	 *
	 * @return	@e void
	 */
	protected function _add()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$parentId	= intval($this->request['record']);
		
		if ( $_POST['Post'] AND $parentId )
		{
			try
			{
				$newComment = $this->_comments->add( $parentId, $_POST['Post'] );
				
				if( $newComment['comment_approved'] )
				{
					return $this->returnHtml( $this->_comments->fetchFormattedSingle( $parentId, $newComment['comment_id'] ) );
				}
				else
				{
					$this->returnJsonError( 'comment_requires_approval' );
				}	
			}
			catch( Exception $e )
			{
				$this->returnJsonError( 'no_permission: ' . $e->getMessage() . var_export( $e, true ) );
			}
		}
		else
		{
			$this->returnJsonError( 'no_permission' );
		}
	}
}