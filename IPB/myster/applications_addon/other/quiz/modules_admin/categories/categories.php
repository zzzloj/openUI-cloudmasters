<?php

class admin_quiz_categories_categories extends ipsCommand
{
	public function doExecute(ipsRegistry $registry)
	{
        $this->categories = $this->registry->getClass('categories');
		$this->html = $this->registry->output->loadTemplate('cp_skin_categories');
		$html = $this->registry->output->loadTemplate( 'cp_skin_categories' );
		$this->lang->loadLanguageFile(array('admin_quiz'), 'quiz');
		$this->form_code    = $this->html->form_code    = 'module=categories&amp;section=categories';
		$this->form_code_js = $this->html->form_code_js = 'module=categories&section=categories';
		
		switch ($this->request['do'])
		{
			case 'categories':
				$this->allCategories();
				break;
			case 'add':
				$this->addCategory();
				break;
			case 'edit':
				$id = $this->request['id'];
				$this->editCategory($id);
				break;
			case 'delete':
				$id = $this->request['id'];
				$this->deleteCategory($id);
			case 'reorder':
				$this->reorder();
				break;
			default:
				$this->allCategories();
				break;
		}
		$this->registry->output->sendOutput();
	}
	
	public function allCategories()
	{
		$categories = $this->categories->findAll();
		/** @todo - for "World (0)" count next to cats. :)
		foreach ($categories as $cat) {
			$cat['count'] = $this->quiz->countById($cat['category_id']);
		}
		**/	
		//send template
        $this->registry->output->html .= $this->html->allCategories($categories); 
        $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
	}
	
	public function addCategory()
	{		
		$data = $_POST;
		if ($data) {
			if ((!empty($_FILES)) && ($_FILES['category_image']['name'] != '')) {
				$classToLoad    			= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classUpload.php', 'classUpload' );
				$upload             	   	= new $classToLoad();
				$upload->out_file_name     	= 'quiz-cat-'.strtolower(IPSText::makeSeoTitle($data['category_name'])).'-'.md5( uniqid( microtime(), true ) );
				$upload->out_file_dir      	= $this->settings['upload_dir'];
				$upload->max_file_size     	= 1024 * 1024 * 15; // 15MB
				$upload->upload_form_field 	= 'category_image';
				$upload->allowed_file_ext  	= array( 'gif', 'png', 'jpg', 'jpeg' );
				$upload->process();
				if ( $upload->error_no )
				{
					switch( $upload->error_no )
					{
						case 1:
							print $this->lang->words['quiz_acp_uploaderr_1'];
							break;
						case 2:
							print $this->lang->words['quiz_acp_uploaderr_2'];
							break;
						case 3:
							print $this->lang->words['quiz_acp_uploaderr_3'];
							break;
						case 4:
							print $this->lang->words['quiz_acp_uploaderr_4'];
							break;
						case 5:
							print $this->lang->words['quiz_acp_uploaderr_5'];
							break;
					}
				}
				$data['category_image']   = $this->settings['upload_url'] . '/' . $upload->parsed_file_name;
			} else {
				$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_acp_error_nocatimg'] ), '11QUIZ05', ''); // 
			}

			$this->categories->save($data);
			$this->registry->output->redirect( $this->settings['base_url'].'module=categories&amp;section=categories&amp;do=categories', $this->lang->words['quiz_acp_category_added'], 2, false, false );
		} else {
			//send template
        	$this->registry->output->html .= $this->html->addCategory(); 
        	$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		}
	}
	
	public function editCategory($id)
	{
		$category	= $this->categories->findById($id);
		$data		= $_POST;
		if ($data) {
			if ((!empty($_FILES)) && $_FILES['category_image']['name'] != '') {
				$classToLoad    			= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classUpload.php', 'classUpload' );
				$upload             	   	= new $classToLoad();
				$upload->out_file_name     	= 'quiz-cat-'.strtolower($data['category_name']).'-'.md5( uniqid( microtime(), true ) );
				$upload->out_file_dir      	= $this->settings['upload_dir'];
				$upload->max_file_size     	= 1024 * 1024 * 15; // 15MB
				$upload->upload_form_field 	= 'category_image';
				$upload->allowed_file_ext  	= array( 'gif', 'png', 'jpg', 'jpeg' );
				$upload->process();
				if ( $upload->error_no )
				{
					switch( $upload->error_no )
					{
						case 1:
							print $this->lang->words['quiz_acp_uploaderr_1'];
							break;
						case 2:
							print $this->lang->words['quiz_acp_uploaderr_2'];
							break;
						case 3:
							print $this->lang->words['quiz_acp_uploaderr_3'];
							break;
						case 4:
							print $this->lang->words['quiz_acp_uploaderr_4'];
							break;
						case 5:
							print $this->lang->words['quiz_acp_uploaderr_5'];
							break;
					}
				}
				$data['category_image']   = $this->settings['upload_url'] . '/' . $upload->parsed_file_name;
			}
			$this->categories->update($data);
			$this->registry->output->redirect( $this->settings['base_url'].'module=categories&amp;section=categories&amp;do=categories', $this->lang->words['quiz_acp_category_updated'], 2, false, false );
		} else {
			$this->registry->output->html .= $this->html->editCategory($category);
			$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		}
	}
	
	public function deleteCategory($id)
	{
		$category	= $this->categories->findById($id);
		if (!empty($category)) {
			$this->categories->delete($id);
			$this->registry->output->redirect( $this->settings['base_url'].'module=categories&amp;section=categories&amp;do=categories', $this->lang->words['quiz_acp_category_deleted'], 2, false, false );
		} else {
			$this->registry->output->showError(	array('quiz_errors', $this->lang->words['quiz_acp_error_nocat'] ), '11QUIZ02', ''); // 
			
		}
	}
	
	public  function  reorder()
	{
		/*  Get  ajax  class  */
		$classToLoad  =  IPSLib::loadLibrary(  IPS_KERNEL_PATH  . 'classAjax.php', 'classAjax' );
		$ajax      =  new  $classToLoad();

		//-----------------------------------------
		//  Checks...
		//-----------------------------------------

		if(  $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString(  $this->lang->words['postform_badmd5'] );
			exit();
		}

		//-----------------------------------------
		// Save new position
		//-----------------------------------------
		$position = 1;
		if( is_array($this->request['quiz']) AND count($this->request['quiz']) )
		{
			foreach( $this->request['quiz'] as $this_id )
			{
				$this->DB->update( 'quiz_categories', array ( 'category_display_order' => $position ), 'category_id=' . $this_id );
				$position++;
			}
		}

		$ajax->returnString( 'OK' );
		exit();
	}
	
}