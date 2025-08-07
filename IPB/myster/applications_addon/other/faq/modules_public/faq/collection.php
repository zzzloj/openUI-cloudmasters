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

class public_faq_faq_collection extends ipsCommand
{
    public function doExecute(ipsRegistry $registry)
    {
        if(!$this->memberData['faq']['view'])
        {
            $this->registry->output->showError('no_permission');
        }
        
        $collection = $this->DB->buildAndFetch(array('select' => '*', 'from' => 'faq_collections', 'where' => 'collection_id='.intval($this->request['collection_id'])));
        if(!$collection['collection_id'])
            $this->registry->output->showError('Invalid FAQ Collection.', '10FAQC01');
            
        $collection['description'] = $this->registry->faqText->parseForDisplay($collection['description']);
        $collection['questions'] = $this->_getCollectionQuestions($collection['collection_id']);
        $collection['heading'] = $collection['heading'] ? $collection['heading'] : $collection['name'];
        
        $this->registry->output->setTitle($collection['name']);
        $this->registry->output->addNavigation($this->lang->words['faq'], 'app=faq', IPSText::makeSeoTitle($this->lang->words['faq']), 'app=faq');
        $this->registry->output->addNavigation($collection['heading'], "faqcollection={$collection['collection_id']}", IPSText::makeSeoTitle($collection['heading']), 'faqcollection');
        
        $this->output .= $this->registry->output->getTemplate('faq')->collectionPage($collection);
        
        $this->registry->output->addContent($this->output);
        $this->registry->output->sendOutput();
    }
    
    protected function _getCollectionQuestions($collectionId)
    {
        $approved = $this->memberData['faq']['moderate'] ? "" : " and q.approved=1";
        $this->DB->build(array('select' => 'cq.source, q.*, h.*',
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
                    $row['answer'] = $this->registry->faqText->parseForDisplay($row['text']);
                    break;
                    
                case 'HSC':
                default:
                    $row['key'] = $row['source'] . '-' . $row['question_id'];
                    $row['question'] = $this->registry->faqText->parseForDisplay($row['question']);
                    $row['answer'] = $this->registry->faqText->parseForDisplay($row['answer']);
                    break;
            }
            
            $questions[] = $row;
        }
        $this->DB->freeResult($query);
        
        return $questions;
    }
}