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

class admin_faq_questions_questions extends ipsCommand
{
    public function doExecute(ipsRegistry $registry)
    {
        $this->html               = $this->registry->output->loadTemplate( 'cp_skin_faq_questions' );
		$this->html->form_code    = 'module=questions&amp;section=questions';
		$this->html->form_code_js = 'module=questions&section=questions';
        
        switch($this->request['do'])
        {
            case 'view':
            default:
                $this->_show();
                break;
                
            case 'filter':
                $this->_filter();
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
                
            case 'approve':
                $this->_approve(1);
                break;
                
            case 'unapprove':
                $this->_approve(0);
                break;
        }
        
        $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
    }
    
    protected function _show()
    {
        $start = intval($this->request['st']);
        $limit = 20;
        $total = $this->DB->buildAndFetch(array('select' => 'count(question_id) as total', 'from' => 'faq_questions'));
        
        $pages = $this->registry->output->generatePagination(array('totalItems' => $total['total'],
                                                                    'currentStartValue' => $start,
                                                                    'itemsPerPage' => $limit,
                                                                    'baseUrl' => $this->settings['base_url'].$this->html->form_code));
                                                                    
        $this->DB->build(array('select' => 'question_id, question, approved', 'from' => 'faq_questions', 'limit' => array($start, $limit)));
        $query = $this->DB->execute();
        while($row = $this->DB->fetch($query))
        {
            $row['question'] = $this->registry->faqText->parseForDisplay($row['question']);
            $row['approved_img'] = $row['approved'] ? 'tick' : 'cross';
            $rows[] = $row;
        }
        $this->DB->freeResult($query);
        
        $collections = $this->_getCollectionsArray();
        $form['collection_id'] = $this->registry->output->formDropdown('collection_id', $collections, '', 'collection_id');
        
        $this->registry->output->html .= $this->html->showQuestions($rows, $pages, $form);        
    }
    
    protected function _filter()
    {
        switch($this->request['filter'])
        {
            case 'unused':
                $where = 'cq.collection_id is null';
                break;
                
            case 'collection':
                $where = 'cq.collection_id='.intval($this->request['collection_id']);
                break;
        }            
            
        $start = intval($this->request['st']);
        $limit = 20;
        $total = $this->DB->buildAndFetch(array('select' => 'count(q.question_id) as total', 
                                                'from' => array('faq_questions' => 'q'),
                                                'where' => $where,
                                                'add_join' => array(
                                                    array('from' => array('faq_collections_questions' => 'cq'),
                                                            'where' => 'q.question_id=cq.question_id',
                                                            'type' => 'left')
                                                )));
        
        $pages = $this->registry->output->generatePagination(array('totalItems' => $total['total'],
                                                                    'currentStartValue' => $start,
                                                                    'itemsPerPage' => $limit,
                                                                    'baseUrl' => $this->settings['base_url'].$this->html->form_code."&amp;do=filter&amp;filter={$this->request['filter']}"));
                                                                    
        $this->DB->build(array('select' => 'q.question_id, q.question, q.approved', 
                                'from' => array('faq_questions' => 'q'),
                                'where' => $where,
                                'add_join' => array(
                                    array('from' => array('faq_collections_questions' => 'cq'),
                                        'where' => 'q.question_id=cq.question_id',
                                        'type' => 'left')
                                    )));
        $query = $this->DB->execute();
        while($row = $this->DB->fetch($query))
        {
            $row['approved_img'] = $row['approved'] ? 'tick' : 'cross';
            $row['question'] = $this->registry->faqText->parseForDisplay($row['question']);
            $rows[] = $row;
        }
        $this->DB->freeResult($query);
        
        $collections = $this->_getCollectionsArray();
        $form['collection_id'] = $this->registry->output->formDropdown('collection_id', $collections, $this->request['collection_id'], 'collection_id');
        
        $this->registry->output->html .= $this->html->showQuestions($rows, $pages, $form);        
    }
    
    protected function _edit()
    {
        $row = $this->DB->buildAndFetch(array('select' => 'q.*', 
                                                'from' => array('faq_questions' => 'q'), 
                                                'where' => 'q.question_id='.intval($this->request['id']),
                                                'group' => 'q.question_id',
                                                'add_join' => array(
                                                    array('select' => 'group_concat(cq.collection_id) as collections',
                                                            'from' => array('faq_collections_questions' => 'cq'),
                                                            'where' => 'q.question_id=cq.question_id',
                                                            'type' => 'left')
                                                )));
        
        $row['width'] = $row['width'] == -1 ? 'auto' : $row['width'];
                                                
        $collections = $this->_getCollectionsArray();
        
        $formData['id'] = $row['question_id'];
        $formData['question'] = $this->registry->faqText->loadEditor('question', $row['question'], array('minimize' => true, 'type' => 'mini'));
        $formData['answer'] = $this->registry->faqText->loadEditor('answer', $row['answer'], array('minimize' => true, 'type' => 'mini'));
        $formData['width'] = $this->registry->output->formInput('width', $row['width'], 'width', 5);
        $formData['collections'] = $this->registry->output->formMultiDropdown('collections[]', $collections, explode(",",$row['collections']));
        
        $this->registry->output->html .= $this->html->questionForm($formData);
    }
    
    protected function _save()
    {
        $this->registry->class_questions->saveQuestion();
        
        $this->registry->output->silentRedirect($this->settings['base_url'].$this->html->form_code);
    }
    
    protected function _delete()
    {
        $this->registry->class_questions->deleteQuestion($this->request['id']);
        
        $this->registry->output->silentRedirect($this->settings['base_url'].$this->html->form_code);
    }
    
    protected function _approve($approved=1)
    {
        $this->registry->class_questions->approveQuestion($this->request['id'], intval($approved));
        
        $this->registry->output->silentRedirect($this->settings['base_url'].$this->html->form_code);
    }
    
    protected function _getCollectionsArray()
    {
        $collections = array();
        $this->DB->build(array('select' => 'collection_id, name', 'from' => 'faq_collections', 'order' => 'name'));
        $query = $this->DB->execute();
        while($row = $this->DB->fetch($query))
            $collections[] = array($row['collection_id'], $row['name']);
        $this->DB->freeResult($query);
        
        return $collections;
    }
}