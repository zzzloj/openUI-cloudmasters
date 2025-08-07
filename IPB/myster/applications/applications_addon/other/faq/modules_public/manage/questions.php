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

class public_faq_manage_questions extends ipsCommand
{
    public function doExecute(ipsRegistry $registry)
    {
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
                
            case 'approve':
                $this->_approve(1);
                break;
                
            case 'unapprove':
                $this->_approve(0);
                break;
        }
        
        $this->registry->output->addContent($this->output);
        $this->registry->output->sendOutput();
    }
    
    protected function _show()
    {
        if(!$this->memberData['faq']['moderate'])
        {
            $this->registry->output->showError('no_permission');
        }
        
        $start = intval($this->request['st']);
        $limit = 20;
        $total = $this->DB->buildAndFetch(array('select' => 'count(question_id) as total', 'from' => 'faq_questions'));
        
        $pages = $this->registry->output->generatePagination(array('totalItems' => $total['total'],
                                                                    'currentStartValue' => $start,
                                                                    'itemsPerPage' => $limit,
                                                                    'baseUrl' => "app=faq&amp;module=manage&amp;section=questions"));
                                                                    
        $this->DB->build(array('select' => 'question_id, question, approved', 'from' => 'faq_questions', 'limit' => array($start, $limit)));
        $query = $this->DB->execute();
        while($row = $this->DB->fetch($query))
        {
            $row['question'] = $this->registry->faqText->parseForDisplay($row['question']);
            $rows[] = $row;
        }
        $this->DB->freeResult($query);
        
        $this->registry->output->setTitle($this->lang->words['manage_questions']);
        $this->registry->output->addNavigation($this->lang->words['faq'], 'app=faq', IPSText::makeSeoTitle('faq'), 'app=faq');
        $this->registry->output->addNavigation($this->lang->words['manage_questions'], '');
        
        $this->output .= $this->registry->output->getTemplate('faq_manage')->manageQuestions($rows, $pages);
    }
    
    protected function _edit()
    {
        if(intval($this->request['id']))
        {
            if(!$this->memberData['faq']['moderate'])
            {
                $this->registry->output->showError('no_permission');
            }
            
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
            if(!is_array($row) || !count($row))
            {
                $this->registry->output->showError('Could not find selected Question.', '10FAQ01');
            }
            
            $row['collections'] = explode(",", $row['collections']);
        }
        else
        {
            if(!$this->memberData['faq']['add'])
            {
                $this->registry->output->showError('no_permission');
            }
            
            $row['collections'] = array();
        }
        
        $row['question'] = $this->registry->faqText->loadEditor('question', $row['question'], array('type' => 'mini', 'minimize' => 'true'));
        $row['answer'] = $this->registry->faqText->loadEditor('answer', $row['answer'], array('type' => 'mini', 'minimize' => 'true'));
        $row['width'] = $row['width'] == -1 ? 'auto' : $row['width'];
        
        $collections = $this->_getCollections();
        
        $title = $this->request['id'] > 0 ? $this->lang->words['edit_question'] : $this->lang->words['add_question']; 
        $this->registry->output->setTitle($title);
        $this->registry->output->addNavigation($this->lang->words['faq'], 'app=faq', IPSText::makeSeoTitle($this->lang->words['faq']), 'app=faq');
        $this->registry->output->addNavigation($this->lang->words['manage_questions'], 'app=faq&amp;module=manage&amp;section=questions');
        $this->registry->output->addNavigation($title, '');
        
        $this->output .= $this->registry->output->getTemplate('faq_manage')->questionForm($row, $collections);
    }
    
    protected function _save()
    {
        $this->registry->class_questions->saveQuestion();
        
        if($this->memberData['faq']['moderate'])
        {
            $this->registry->output->silentRedirect($this->registry->output->buildUrl("app=faq&amp;module=manage&amp;section=questions", "public"));
        }
        else
        {
            $this->registry->output->silentRedirect($this->registry->output->buildSEOUrl("app=faq", 'public', IPSText::makeSeoTitle('faq'), "app=faq"));
        }
    }
    
    protected function _delete()
    {
        if(!$this->memberData['faq']['moderate'])
        {
            $this->registry->output->showError('no_permission');
        }
        
        $this->registry->class_questions->deleteQuestion($this->request['id']);
        
        $this->registry->output->silentRedirect($this->registry->output->buildUrl("app=faq&amp;module=manage&amp;section=questions", "public"));
    }
    
    protected function _approve($approved=1)
    {
        if(!$this->memberData['faq']['approve'] && !$this->memberData['faq']['moderate'])
        {
            $this->registry->output->showError('no_permission');
        }
        
        $this->registry->class_questions->approveQuestion($this->request['id'], intval($approved));
        
        $this->registry->output->silentRedirect($this->registry->output->buildUrl("app=faq&amp;module=manage&amp;section=questions", "public"));
    }
    
    protected function _getCollections()
    {
        $collections = array();
        
        $this->DB->build(array('select' => 'collection_id, name', 'from' => 'faq_collections', 'order' => 'name'));
        $query = $this->DB->execute();
        while($row = $this->DB->fetch($query))
        {
            $collections[] = $row;
        }
        $this->DB->freeResult($query);
        
        return $collections;
    }
}