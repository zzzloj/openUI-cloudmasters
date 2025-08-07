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

class public_faq_ajax_question extends ipsAjaxCommand
{
    public function doExecute(ipsRegistry $registry)
    {
        switch($this->request['do'])
        {
            case 'answer':
                $this->_showAnswer();
                break;
        }
    }
    
    protected function _showAnswer()
    {
        IPSDebug::addLogMessage('', 'faq', $this->request, true, true);
        list($source, $questionId) = explode("-", $this->request['id']);
        
        switch($source)
        {
            case 'IPB':
                $question = $this->DB->buildAndFetch(array('select' => 'title as question, text as answer', 'from' => 'faq', 'where' => 'id='.intval($questionId)));
                break;
                
            case 'HSC':
            default:
                $question = $this->DB->buildAndFetch(array('select' => '*', 'from' => 'faq_questions', 'where' => 'question_id='.intval($questionId)));
                break;
        }        
        
        $question['question'] = $this->registry->output->replaceMacros($this->registry->faqText->parseForDisplay($question['question']));
        $question['answer'] = $this->registry->output->replaceMacros($this->registry->faqText->parseForDisplay($question['answer']));
        
        if($question['width'] == -1)
        {
            $width = 'auto';
        }
        else
        {
            $width = intval($question['width']) ? $question['width'] : 600;
            $width .= 'px';
        }
        
        $return = array('width' => $width, 
                                    'html' => $this->registry->output->getTemplate('faq')->showQuestion($question));

        $this->returnJsonArray($return);
    }
}