<?php

/**
 * <pre>
 * Invision Power Services
 * IP.CCS block AJAX functions
 * Last Updated: $Date: 2012-01-24 20:44:16 -0500 (Tue, 24 Jan 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		1st March 2009
 * @version		$Revision: 10185 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_ajax_blocks extends ipsAjaxCommand
{
	/**
	 * HTML library
	 *
	 * @access	public
	 * @var		object
	 */
	public $html;

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
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_blocks' );

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'preview':
			default:
				$this->_showBlockPreview();
			break;

			case 'reorder':
				$this->_doReorder();
			break;
			
			case 'reorderCats':
				$this->_doReorderCats();
			break;
			
			case 'addcategory':
				$this->_addCategoryForm();
			break;

			case 'checkKey':
				$this->_checkKey();
			break;

			case 'templatetags':
				$this->_getTemplateTags();
			break;
		}
	}

	/**
	 * Retrieve template tags for block templates
	 *
	 * @return	@e void
	 */
	protected function _getTemplateTags()
	{
		$type	= $this->request['parentType'];
		$child	= $this->request['subType'];

		if( !$type )
		{
			$htmlTemplate = $this->registry->output->loadTemplate( 'cp_skin_blocks' );

			$this->returnHtml( $htmlTemplate->inlineBlockTagsContent( array(), $this->lang->words['define_block_tags_select'] ) );
		}

		if( $type == '*' OR !$child )
		{
			$htmlTemplate = $this->registry->output->loadTemplate( 'cp_skin_blocks' );

			$this->returnHtml( $htmlTemplate->inlineBlockTagsContent( array(), $this->lang->words['define_block_tags_vague'] ) );
		}

		//-----------------------------------------
		// Account for stuff we can catch
		//-----------------------------------------

		if( $type == 'databases' )
		{
			$htmlTemplate = $this->registry->output->loadTemplate( 'cp_skin_blocks' );

			$this->returnHtml( $htmlTemplate->inlineBlockTagsContent( array(), $this->lang->words['define_block_tags_vague'] ) );
		}

		if( $type == 'articles' )
		{
			foreach( $this->cache->getCache('ccs_databases') as $database )
			{
				if( $database['database_is_articles'] )
				{
					$child	= $database['database_id'] . ';' . $child;
					break;
				}
			}
		}

		$this->returnHtml( $this->registry->ccsAcpFunctions->getBlockTags( 'feed', $type . ',' . $child, true ) );
	}
	
	/**
	 * Check a supplied block key to make sure it's not in use
	 *
	 * @return	@e void
	 */
	protected function _checkKey()
	{
		$value		= $this->request['value'];
		
		/* Let this one be empty, as the issue is likely an internal problem and not something the admin did */
		if( !$value )
		{
			$this->returnJsonError( '' );
		}
		
		$value	= strtolower( preg_replace( "/[^a-zA-Z0-9]/sm", '_', $value ) );
		
		$block	= $this->DB->buildAndFetch( array( 'select' => 'block_id', 'from' => 'ccs_blocks', 'where' => "block_key='{$value}'" ) );
		
		if( $block['block_id'] )
		{
			$this->returnJsonArray( array( 'value' => $value . '_' . time() ) );
		}
		else
		{
			$this->returnJsonArray( array( 'value' => $value ) );
		}
	}
	
	/**
	 * Return the add category form
	 *
	 * @return	@e void		[Outputs to screen]
	 */
	public function _addCategoryForm() 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$htmlTemplate = $this->registry->output->loadTemplate( 'cp_skin_blocks' );

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		$this->returnHtml( $htmlTemplate->ajaxCategoryForm() );
	}

	/**
	 * Reorder blocks within a container
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _doReorder()
	{
		//-----------------------------------------
		// Get valid categories
		//-----------------------------------------
		
		$categories	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='block'" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$categories[]	= $r['container_id'];
		}
		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position		= 1;
 		$newCategory	= in_array( intval($this->request['category']), $categories ) ? intval($this->request['category']) : 0;
 		
 		if( is_array($this->request['block']) AND count($this->request['block']) )
 		{
 			foreach( $this->request['block'] as $block_id )
 			{
 				if( !$block_id )
 				{
 					continue;
 				}
 				
 				$this->DB->update( 'ccs_blocks', array( 'block_position' => $position, 'block_category' => $newCategory ), 'block_id=' . $block_id );
 				
 				$position++;
 			}
 		}

 		$this->registry->adminFunctions->saveAdminLog( $this->lang->words['ccs_adminlog_blocksreordered'] );

 		$this->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * Reorder categories
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _doReorderCats()
	{
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['category']) AND count($this->request['category']) )
 		{
 			foreach( $this->request['category'] as $category_id )
 			{
 				if( !$category_id )
 				{
 					continue;
 				}
 				
 				$this->DB->update( 'ccs_containers', array( 'container_order' => $position ), 'container_id=' . $category_id );
 				
 				$position++;
 			}
 		}

 		$this->registry->adminFunctions->saveAdminLog( $this->lang->words['ccs_adminlog_blockcatsreordered'] );

 		$this->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * Show the block preview
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _showBlockPreview()
	{
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->returnNull();
		}
		
		$block	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $id ) );
		
		$this->returnHtml( $this->html->blockPreview( $block ) );
	}
}