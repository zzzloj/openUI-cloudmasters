<?php

/*
+--------------------------------------------------------------------------
|   [HSC] FAQ System 1.0
|   =============================================
|   by Esther Eisner
|   Copyright 2012 HeadStand Consulting
|   esther@headstandconsulting.com
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_faq_collections_collections extends ipsCommand
{
    public function doExecute(ipsRegistry $registry)
    {
        $this->html               = $this->registry->output->loadTemplate( 'cp_skin_faq_collections' );
		$this->html->form_code    = 'module=collections&amp;section=collections';
		$this->html->form_code_js = 'module=collections&section=collections';
        
        switch($this->request['do'])
        {
            case 'view':
            default:
                $this->_show();
                break;
                
            case 'add':
            case 'edit':
                $this->_edit();
                break;
                
            case 'save':
                $this->_save();
                break;
                
            case 'delete':
                $this->_delete();
                break;
                
            case 'reorder':
                $this->_reorder();
                break;
        }
        
        $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
    }
    
    private function _show()
    {
        $start = intval($this->request['st']);
        $limit = 20;
        $total = $this->DB->buildAndFetch(array('select' => 'count(collection_id) as total', 'from' => 'faq_collections'));
        
        $pages = $this->registry->output->generatePagination(array('totalItems' => $total['total'],
                                                                    'currentStartValue' => $start,
                                                                    'itemsPerPage' => $limit,
                                                                    'baseUrl' => $this->settings['base_url'].$this->html->form_code));
                                                                    
        $this->DB->build(array('select' => 'collection_id, collection_key, name', 'from' => 'faq_collections', 'order' => 'sequence'));
        $query = $this->DB->execute();
        while($row = $this->DB->fetch($query))
        {
            $rows[] = $row;
        }
        $this->DB->freeResult($query);
        
        $this->registry->output->html .= $this->html->showCollections($rows, $pages);        
    }
    
    private function _edit()
    {
        $row = $this->DB->buildAndFetch(array('select' => '*', 'from' => 'faq_collections', 'where' => 'collection_id='.intval($this->request['id'])));
        
        $formData['id'] = $row['collection_id'];
        $formData['key'] = $this->registry->output->formInput('key', $row['collection_key'], 'key');
        $formData['heading'] = $this->registry->output->formInput('heading', $row['heading'], 'heading');
        $formData['name'] = $this->registry->output->formInput('name', $row['name'], 'name');
        $formData['description'] = $this->registry->faqText->loadEditor('description', $row['description'], array('minimize' => true, 'type' => 'mini'));
        
        $this->registry->output->html .= $this->html->collectionForm($formData);
    }
    
    private function _save()
    {
        $id = intval($this->request['id']);
        
        $data = array('collection_key' => $this->DB->addSlashes($this->request['key']),
                        'name' => $this->DB->addSlashes($this->request['name']),
                        'heading' => $this->DB->addSlashes($this->request['heading']),
                        'description' => $this->registry->faqText->parseForSave($_POST['description']));
                        
        if(!$data['collection_key'])
            $this->registry->output->showError('You must enter a key.', '11FAQC02');
        if(!$data['name'])
            $this->registry->output->showError('You must enter a collection name.', '11FAQC03');
                        
        if($id)
        {
            $this->DB->update('faq_collections', $data, "collection_id={$id}");
        }
        else
        {
            $this->DB->insert('faq_collections', $data);
            $id = $this->DB->getInsertId();
        }
        
        $this->cache->rebuildCache('faq_collections', 'faq');
        
        if($this->request['Next'])
        {
            $this->registry->output->silentRedirect($this->settings['base_url']."app=faq&amp;module=collections&amp;section=questions&amp;collection_id={$id}");
        }
        
        $this->registry->output->silentRedirect($this->settings['base_url'].$this->html->form_code);
    }
    
    private function _delete()
    {
        $id = intval($this->request['id']);
        $this->DB->delete('faq_collections_questions', "collection_id={$id}");
        $this->DB->delete('faq_collections', "collection_id={$id}");
        
        $this->cache->rebuildCache('faq_collections', 'faq');
        
        $this->registry->output->silentRedirect($this->settings['base_url'].$this->html->form_code);
    }
    
    private function _reorder()
    {
        /* Get ajax class */
        $classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
        $ajax = new $classToLoad();
        
        //-----------------------------------------
        // Checks...
        //-----------------------------------------
        if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
        {
            $ajax->returnString( $this->lang->words['postform_badmd5'] );
            exit();
        }
        
        //-----------------------------------------
        // Save new position
        //-----------------------------------------
        $position=1;
        if( is_array($this->request['collections']) && count($this->request['collections']) )
        {
            foreach( $this->request['collections'] as $this_id )
            {
                $this->DB->update('faq_collections', array('sequence' => $position), 'collection_id='.$this_id);
                $position++;
            }
        }
        
        $ajax->returnString( 'OK' );
        exit();
    }
}