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

class faqCache
{
    public function __construct()
    {
        $this->registry = ipsRegistry::instance();
        $this->DB = $this->registry->DB();
        $this->cache = $this->registry->cache();
        $this->caches =& $this->cache->fetchCaches();
        
        ipsRegistry::getAppClass('faq');
    }
    
    public function cacheCollections()
    {
        $cache = array();
        
        $this->DB->build(array('select' => 'c.collection_id, c.collection_key, c.heading, c.name',
                                'from' => array('faq_collections' => 'c'),
                                'order' => 'c.collection_id, cq.sequence',
                                'add_join' => array(
                                    array('select' => 'cq.source, cq.question_id',
                                                'from' => array('faq_collections_questions' => 'cq'),
                                                'where' => 'c.collection_id=cq.collection_id',
                                                'type' => 'left'),
                                    array('select' => 'q.question',
                                                'from' => array('faq_questions' => 'q'),
                                                'where' => "cq.question_id=q.question_id and cq.source='HSC' and q.approved=1",
                                                'type' => 'left'),
                                    array('select' => 'h.title',
                                                'from' => array('faq' => 'h'),
                                                'where' => "cq.question_id=h.id and cq.source='IPB'",
                                                'type' => 'left')
                                )));
        $query = $this->DB->execute();
        while($row = $this->DB->fetch($query))
        {
            $question['id'] = $row['source'] . '-' . $row['question_id'];
            
            switch($row['source'])
            {
                case 'IPB':
                    $question['title'] = $row['title'];
                    break;
                    
                case 'HSC':
                default:
                    $question['title'] = $this->registry->faqText->parseForDisplay($row['question']);
                    break;
            }
            
            $cache[$row['collection_key']]['heading'] = $row['heading'] ? $row['heading'] : $row['name'];
            $cache[$row['collection_key']]['questions'][] = $question;
        }
        $this->DB->freeResult($query);
        
        $this->cache->setCache('faq_collections', $cache, array('array' => 1, 'donow' => 1));
    }
}