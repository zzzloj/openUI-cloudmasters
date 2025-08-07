<?php
/**
 * @file		templates.php 	IP.Content template AJAX functions
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: bfarber $
 * @since		1 Mar 2009
 * $LastChangedDate: 2012-01-17 21:56:35 -0500 (Tue, 17 Jan 2012) $
 * @version		v3.4.5
 * $Revision: 10146 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

/**
 * @class		admin_ccs_ajax_templates
 * @brief		IP.Content template AJAX functions
 */
class admin_ccs_ajax_templates extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'reorder':
				$this->_doReorder();
			break;
			
			case 'getpages':
				$this->_showPages();
			break;

			case 'getdbs':
				$this->_showDatabases();
			break;
			
			case 'addcategory':
				$this->_addCategoryForm();
			break;

			case 'getcontenttypes':
				$this->_getContentTypes();
			break;
		}
	}
	
	/**
	 * Returns a list of content types for given feed type
	 *
	 * @return	@e void
	 */
	public function _getContentTypes()
	{
		$contentTypes	= array();

		if( !$this->request['type'] )
		{
			$this->returnJsonError( $this->lang->words['no_type_provided'] );
		}

		if( $this->request['type'] == '*' )
		{
			$contentTypes	= $this->registry->ccsAcpFunctions->getGenericContentTypes();
		}
		else
		{
			if( $this->registry->ccsAcpFunctions->getBlockObject( $this->request['type'] ) )
			{
				$contentTypes	= $this->registry->ccsAcpFunctions->getBlockObject( $this->request['type'] )->returnContentTypes( array(), false );
			}
		}

		$this->returnJsonArray( $contentTypes );
	}

	/**
	 * Return the add category form
	 *
	 * @return	@e void
	 */
	public function _addCategoryForm() 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$htmlTemplate = $this->registry->output->loadTemplate( 'cp_skin_templates' );

		$this->returnHtml( $htmlTemplate->ajaxCategoryForm() );
	}

	/**
	 * Get pages using a given template
	 *
	 * @return	@e void
	 */
	public function _showPages() 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$htmlTemplate = $this->registry->output->loadTemplate( 'cp_skin_templates' );

		//-----------------------------------------
		// Find pages and return
		//-----------------------------------------
		
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->returnHtml( $htmlTemplate->modalError( $this->lang->words['couldnot_find_template'] ) );
		}
		
		$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . $id ) );
		$pages		= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => 'page_template_used=' . $id ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$pages[]	= $r;
		}
		
		$this->returnHtml( $htmlTemplate->showPagesModal( $template, $pages ) );
	}

	/**
	 * Get databases using a given template
	 *
	 * @return	@e void
	 */
	public function _showDatabases() 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$htmlTemplate = $this->registry->output->loadTemplate( 'cp_skin_templates' );

		//-----------------------------------------
		// Find pages and return
		//-----------------------------------------
		
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->returnHtml( $htmlTemplate->modalError( $this->lang->words['couldnot_find_template'] ) );
		}
		
		$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . $id ) );
		$databases	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_databases', 'where' => 'database_template_categories=' . $id . ' OR database_template_listing=' . $id . ' OR database_template_display=' . $id ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$databases[] = $r;
		}
		
		$this->returnHtml( $htmlTemplate->showDatabasesModal( $template, $databases ) );
	}
	
	/**
	 * Reorder templates within a container
	 *
	 * @return	@e void
	 */
	protected function _doReorder()
	{
		//-----------------------------------------
		// Get valid categories
		//-----------------------------------------
		
		$categories	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type IN('template','dbtemplate','arttemplate')" ) );
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
 		
 		if( is_array($this->request['template']) AND count($this->request['template']) )
 		{
 			foreach( $this->request['template'] as $template_id )
 			{
 				if( !$template_id )
 				{
 					continue;
 				}
 				
 				$this->DB->update( 'ccs_page_templates', array( 'template_position' => $position, 'template_category' => $newCategory ), 'template_id=' . $template_id );
 				
 				$position++;
 			}
 		}

 		$this->registry->adminFunctions->saveAdminLog( $this->lang->words['ccs_templates_reordered'] );

 		$this->returnString( 'OK' );
 		exit();
	}
}
