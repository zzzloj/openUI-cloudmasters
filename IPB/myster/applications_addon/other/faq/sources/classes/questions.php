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

class class_questions
{
    public function __construct()
    {
        $this->registry = ipsRegistry::instance();
        $this->DB = $this->registry->DB();
        $this->request =& $this->registry->fetchRequest();
        $this->settings =& $this->registry->fetchSettings();
        $this->memberData =& $this->registry->member()->fetchMemberData();
        $this->cache = $this->registry->cache();
        $this->caches =& $this->cache->fetchCaches();
    }
    
    public function saveQuestion()
    {
        $id = intval($this->request['id']);
        
        $data = array('question' => $this->registry->faqText->parseForSave($_POST['question']),
                        'answer' => $this->registry->faqText->parseForSave($_POST['answer']),
                        'width' => $this->request['width']=='auto' ? -1 : intval($this->request['width']));
                        
        if(!$data['question'])
            $this->registry->output->showError('You must enter a question.', '10FAQQ01');
        if(!$data['answer'])
            $this->registry->output->showError('You must enter an answer.', '10FAQQ02');
            
        if($id)
        {
            $this->DB->update('faq_questions', $data, "question_id={$id}");
            
            // only change the collections if the user has moderate permissions
            if($this->memberData['faq']['moderate'] || IN_ACP)
            {
                $this->saveQuestionCollections($id);
            }
        }
        else
        {
            $data['approved'] = intval($this->memberData['faq']['approve'] || IN_ACP);
            
            if(!$data['approved'])
            {
                $classToLoad = IPSLib::loadLibrary(IPSLib::getAppDir('faq') . '/sources/classes/notifications.php', 'faqNotifications', 'faq');
                $notify = new $classToLoad();
                $notify->notifyQuestionPending($data);
            }
            
            $this->DB->insert('faq_questions', $data);
            $id = $this->DB->getInsertId();
            
            $this->saveQuestionCollections($id);
        }
        
        $this->cache->rebuildCache('faq_collections', 'faq');
    }
    
    public function deleteQuestion($id)
    {
        $this->DB->delete('faq_collections_questions', "question_id=".intval($id));
        $this->DB->delete('faq_questions', "question_id=".intval($id));
        
        $this->cache->rebuildCache('faq_collections', 'faq');
    }
    
    public function approveQuestion($id, $approved=1)
    {
        $this->DB->update('faq_questions', array('approved' => intval($approved)), 'question_id='.intval($id));
        
        $this->cache->rebuildCache('faq_collections', 'faq');
    }
    
    public function saveQuestionCollections($questionId)
    {
        // Make sure we have something to save
        if(!is_array($this->request['collections']) || !count($this->request['collections']))
        {
            $this->request['collections'] = array();
        }
        
        // First get the old collections
        $collections = $this->DB->buildAndFetch(array('select' => 'group_concat(collection_id) as ids', 'from' => 'faq_collections_questions', 'where' => 'question_id='.$questionId));
        $oldIds = explode(",",$collections['ids']);
        
        // Go through all the selected collections
        foreach($this->request['collections'] as $c)
        {
            // If this was not part of the collection before, insert the question at the end
            if(!in_array($c, $oldIds))
            {
                $last = $this->DB->buildAndFetch(array('select' => 'max(sequence) as sequence', 'from' => 'faq_collections_questions', 'where' => 'collection_id='.$c));
                $this->DB->insert('faq_collections_questions', array('collection_id' => $c, 'question_id' => $questionId, 'sequence' => intval($last['sequence']) + 1, 'source' => 'HSC'));
            }
        }
        
        // Delete any un-selected collections
        if(is_array($this->request['collections']) && count($this->request['collections']))
        {
            $this->DB->delete('faq_collections_questions', "question_id={$questionId} and collection_id not in (".implode(",",$this->request['collections']).")");
        }
        else
        {
            $this->DB->delete('faq_collections_questions', "question_id={$questionId}");
        }
    }
}