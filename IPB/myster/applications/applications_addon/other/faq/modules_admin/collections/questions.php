<?php

/*
+--------------------------------------------------------------------------
-   [HSC] FAQ System 1.0
-   =============================================
-   by Esther Eisner
-   Copyright 2012 HeadStand Consulting
-   esther@headstandconsulting.com
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_faq_collections_questions extends ipsCommand
{
    protected $collection;
    
    public function doExecute(ipsRegistry $registry)
    {
        $this->html               = $this->registry->output->loadTemplate( 'cp_skin_faq_collections' );
		$this->html->form_code    = 'module=collections&amp;section=questions';
		$this->html->form_code_js = 'module=collections&section=questions';
        
        $this->collection = $this->DB->buildAndFetch(array('select' => 'collection_id, name', 'from' => 'faq_collections', 'where' => 'collection_id='.intval($this->request['collection_id'])));
        if(!$this->collection['collection_id'])
            $this->registry->output->showError('Invalid Collection.', '11FAQC01');
        
        switch($this->request['do'])
        {
            case 'view':
            default:
                $this->_show();
                break;
                
            case 'save':
                $this->_save();
                break;
                
            case 'reorder':
                $this->_reorder();
                break;
                
            case 'get':
                $this->_getQuestionList();
                break;
        }
        
        $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
    }
    
    private function _show()
    {
        $this->DB->build(array('select' => 'cq.source, q.question_id, q.question, h.id, h.title',
                                'from' => array('faq_collections_questions' => 'cq'),
                                'where' => 'cq.collection_id='.$this->collection['collection_id'],
                                'order' => 'cq.sequence',
                                'add_join' => array(
                                    array('from' => array('faq_questions' => 'q'),
                                            'where' => "cq.question_id=q.question_id and cq.source='HSC'",
                                            'type' => 'left'),
                                    array('from' => array('faq' => 'h'),
                                            'where' => "cq.question_id=h.id and cq.source='IPB'",
                                            'type' => 'left')
                                )));
        $query = $this->DB->execute();
        while($row = $this->DB->fetch($query))
        {
            switch($row['source'])
            {
                case 'IPB':
                    $row['key'] = 'IPB-' . $row['id'];
                    $row['question'] = $row['title'];
                    $helpIds[] = $row['id'];
                    break;
                    
                case 'HSC':
                default:
                    $row['key'] = 'HSC-' . $row['question_id'];
                    $row['question'] = $this->registry->faqText->parseForDisplay($row['question']);
                    $questionIds[] = $row['question_id'];
                    break;
            }
            
            $row['delete'] = $this->registry->output->formCheckbox('delete[]', false, $row['key'], 'delete[]');
            $rows[] = $row;
        }
        $this->DB->freeResult($query);
        
        $sources[] = array('HSC', $this->lang->words['source__HSC']);
        $sources[] = array('IPB', $this->lang->words['source__IPB']);
        
        $formData['source'] = $this->registry->output->formDropdown('source', $sources, 'HSC', 'source', "onchange='getQuestionList();'");
        $formData['questions'] = $this->registry->output->formDropdown('question_id', $this->_getQuestions($questionIds), '', 'question_id');
        
        $this->registry->output->html .= $this->html->collectionQuestions($this->collection, $rows, $formData);
    }
    
    private function _save()
    {
        // Got anything marked for deletion? Remove the questions from the collection
        if(is_array($this->request['delete']) && count($this->request['delete']))
        {
            foreach($this->request['delete'] as $d)
            {
                $_bits = explode("-", $d);
                $toDelete[$_bits[0]][] = $_bits[1];
            }
            
            if(is_array($toDelete) && count($toDelete))
            {
                foreach($toDelete as $source => $ids)
                {
                    $this->DB->delete('faq_collections_questions', "collection_id={$this->collection['collection_id']} and source='{$source}' and question_id in (" . implode(",", $ids) . ")");
                }
            }
        }
        
        // Are we adding a new one?
        if(intval($this->request['question_id']) && !$this->request['save'])
        {
            $lastQuestion = $this->DB->buildAndFetch(array('select' => 'max(sequence) as sequence', 'from' => 'faq_collections_questions', 'where' => "collection_id={$this->collection['collection_id']}"));
            
            $data = array('sequence' => intval($lastQuestion['sequence']) + 1, 
                            'collection_id' => $this->collection['collection_id'],
                            'question_id' => intval($this->request['question_id']), 
                            'source' => $this->request['source'] ? $this->request['source'] : 'HSC');
            $this->DB->insert('faq_collections_questions', $data);
        }
        
        $this->cache->rebuildCache('faq_collections', 'faq');
        
        // Redirects
        if($this->request['Previous'])
        {
            $this->registry->output->silentRedirect($this->settings['base_url']."app=faq&amp;module=collections&amp;section=collections&amp;do=edit&amp;id={$this->collection['collection_id']}");
        }
        
        if($this->request['Finish'])
        {
            $this->registry->output->silentRedirect($this->settings['base_url']."app=faq&amp;module=collections&amp;section=collections");
        }
        
        $this->registry->output->silentRedirect($this->settings['base_url'].$this->html->form_code."&amp;collection_id={$this->collection['collection_id']}");
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
        if( is_array($this->request['questions']) && count($this->request['questions']) )
        {
            foreach( $this->request['questions'] as $q )
            {
                list($source, $this_id) = explode("-", $q);
                
                $this->DB->update('faq_collections_questions', array('sequence' => $position), "collection_id={$this->collection['collection_id']} and source='{$source}' and question_id={$this_id}");
                $position++;
            }
        }
        
        $this->cache->rebuildCache('faq_collections', 'faq');
        
        $ajax->returnString( 'OK' );
        exit();
    }
    
    private function _getQuestions($questionIds=array())
    {
        $questions[] = array('', '');
        
        if(is_array($questionIds) && count($questionIds))
        {
            $this->DB->build(array('select' => 'question_id, question', 'from' => 'faq_questions', 'where' => 'question_id not in ('.implode(",",$questionIds).')'));
        }
        else
        {
            $this->DB->build(array('select' => 'question_id, question', 'from' => 'faq_questions'));
        }
        
        $query = $this->DB->execute();
        while($row = $this->DB->fetch($query))
        {
            $questions[] = array($row['question_id'], IPSText::truncate(strip_tags($row['question']), 75));
        }
        $this->DB->freeResult($query);
        
        return $questions;
    }
    
    protected function _getQuestionList()
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
        
        $ids = array();
        
        $this->DB->build(array('select' => 'question_id', 'from' => 'faq_collections_questions', 'where' => "source='{$this->request['source']}' and collection_id={$this->request['collection_id']}"));
        $query = $this->DB->execute();
        while($row = $this->DB->fetch($query))
        {
            $ids[] = $row['question_id'];
        }
        $this->DB->freeResult($query);
        
        switch($this->request['source'])
        {
            case 'IPB':
                $ajax->returnHtml($this->_getIPBQuestions($ids));
                break;
                
            case 'HSC':
            default:
                $ajax->returnHtml($this->_getHSCQuestions($ids));
                break;
        }
        
        exit();
    }
    
    protected function _getHSCQuestions($ids=array())
    {
        $html = "<option></option>";
        
        if(is_array($ids) && count($ids))
        {
            $this->DB->build(array('select' => 'question_id, question', 'from' => 'faq_questions', 'where' => 'question_id not in ('.implode(",",$ids).')'));
        }
        else
        {
            $this->DB->build(array('select' => 'question_id, question', 'from' => 'faq_questions'));
        }
        
        $query = $this->DB->execute();
        while($row = $this->DB->fetch($query))
        {
            $html .= "<option value='{$row['question_id']}'>" . IPSText::truncate(strip_tags($row['question']), 75) . "</option>";
        }
        $this->DB->freeResult($query);
        
        return $html;
    }
    
    protected function _getIPBQuestions($ids=array())
    {
        $html = "<option></option>";
        
        if(is_array($ids) && count($ids))
        {
            $this->DB->build(array('select' => 'id, title', 'from' => 'faq', 'where' => 'id not in ('.implode(",",$ids).')'));
        }
        else
        {
            $this->DB->build(array('select' => 'id, title', 'from' => 'faq'));
        }
        
        $query = $this->DB->execute();
        while($row = $this->DB->fetch($query))
        {
            $html .= "<option value='{$row['id']}'>" . IPSText::truncate(strip_tags($row['title']), 75) . "</option>";
        }
        $this->DB->freeResult($query);
        
        return $html;
    }
}