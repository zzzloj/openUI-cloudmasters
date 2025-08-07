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

class public_faq_faq_faq extends ipsCommand
{
    public function doExecute(ipsRegistry $registry)
    {
        if(!$this->memberData['faq']['view'])
        {
            $this->registry->output->showError('no_permission');
        }
        
        $this->DB->build(array('select' => '*', 'from' => 'faq_collections', 'order' => 'sequence'));
        $query = $this->DB->execute();
        while($row = $this->DB->fetch($query))
        {
            $row['description'] = $this->registry->faqText->parseForDisplay($row['description']);
            $row['questions'] = $this->_getCollectionQuestions($row['collection_id']);
            $row['heading'] = $row['heading'] ? $row['heading'] : $row['name'];
            
            if(is_array($row['questions']) && count($row['questions']))
                $collections[] = $row;
        }
        $this->DB->freeResult($query);
        
        $this->registry->output->setTitle($this->lang->words['faq']);
        $this->registry->output->addNavigation($this->lang->words['faq'], 'app=faq', IPSText::makeSeoTitle($this->lang->words['faq']), 'app=faq');
        
        $this->output .= $this->registry->output->getTemplate('faq')->showFullFAQ($collections);
        
        $this->registry->output->addContent($this->output);
        $this->registry->output->sendOutput();
    }
    
    protected function _getCollectionQuestions($collectionId)
    {
        $approved = $this->memberData['faq']['moderate'] ? "" : " and q.approved=1";
        $this->DB->build(array('select' => 'cq.source, q.question_id, q.question, h.id, h.title, q.approved',
                                'from' => array('faq_collections_questions' => 'cq'),
                                'where' => 'cq.collection_id='.intval($collectionId),
                                'order' => 'cq.sequence',
                                'add_join' => array(
                                    array('from' => array('faq_questions' => 'q'),
                                            'where' => "cq.question_id=q.question_id and cq.source='HSC'{$approved}",
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
                    $row['key'] = $row['source'] . '-' . $row['id'];
                    $row['question'] = $row['title'];
                    break;
                    
                case 'HSC':
                default:
                    $row['key'] = $row['source'] . '-' . $row['question_id'];
                    $row['question'] = $this->registry->faqText->parseForDisplay($row['question']);
                    break;
            }
            
            $questions[] = $row;
        }
        $this->DB->freeResult($query);
        
        return $questions;
    }
}