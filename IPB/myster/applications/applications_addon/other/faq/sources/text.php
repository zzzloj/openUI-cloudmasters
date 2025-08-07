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

class faq_text
{
    protected $legacy;
    public $section = 'questions';
    
    public function __construct()
    {
        $this->registry = ipsRegistry::instance();
        $this->memberData =& $this->registry->member()->fetchMemberData();
        
        $version = IPSLib::fetchVersionNumber('core');
        $this->legacy = $version['long'] < 34000;
    }
    
    public function parseForEdit($value)
    {
        IPSText::getTextClass( 'bbcode' )->parsing_section	= $this->section;
        IPSText::getTextClass( 'bbcode' )->parse_smilies    = 1;
		IPSText::getTextClass( 'bbcode' )->parse_html    	= 1;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br		= 0;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode    	= 1;
        IPSText::getTextClass( 'bbcode' )->bypass_badwords  = 0;
        IPSText::getTextClass( 'bbcode' )->parsing_mgroup = $this->memberData['member_group_id'];
        IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others = $this->memberData['mgroup_others'];
        
        $value = IPSText::getTextClass('bbcode')->preEditParse($value);
        
        return $value;
    }
    
    public function loadEditor($fieldName, $fieldContent, $options=array())
    {
        $classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
        $this->editor = new $classToLoad();
        
        if($this->legacy)
        {
            $fieldContent = $this->parseForEdit($fieldContent);
        }
        else
        {
            $this->editor->setLegacyMode(false);
            $this->editor->setIsHtml(1);
            $this->editor->setAllowBbcode(1);
            $this->editor->setAllowSmilies(1);
            $this->editor->setBbcodeSection($this->section);
        }

        return  $this->editor->show($fieldName, $options, $fieldContent);
    }
    
    public function parseForDisplay($value)
    {
        if($this->legacy)
        {
            return $this->legacyParseForDisplay($value);
        }
        
        require_once(IPS_ROOT_PATH . 'sources/classes/text/parser.php');
        $parser = new classes_text_parser();
        
        $parser->set(array('parseArea' => $this->section,
                            'memberData' => $this->memberData,
                            'parseBBCode' => 1,
                            'parseHtml' => 1,
                            'parseEmoticons' => true));
                            
        return $parser->display($value);
    }
    
    public function legacyParseForDisplay($value)
    {
        IPSText::getTextClass( 'bbcode' )->parsing_section	= $this->section;
        IPSText::getTextClass( 'bbcode' )->parse_smilies    = 1;
		IPSText::getTextClass( 'bbcode' )->parse_html    	= 1;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br		= 1;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode    	= 1;
        IPSText::getTextClass( 'bbcode' )->bypass_badwords  = 0;
        IPSText::getTextClass( 'bbcode' )->parsing_mgroup = $this->memberData['member_group_id'];
        IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others = $this->memberData['mgroup_others'];
        
        $value = IPSText::getTextClass('bbcode')->preDisplayParse($value);
        return $value;
    }
    
    public function parseForSave($value)
    {
        if(!$value)
            return;
            
        $classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
        $this->editor = new $classToLoad();
        
        if(!$this->legacy)
        {
            $this->editor->setLegacyMode(false);
            $this->editor->setIsHtml(1);
            $this->editor->setAllowBbcode(1);
            $this->editor->setAllowSmilies(1);
            $this->editor->setBbcodeSection($this->section);
        }
        
        $value = $this->editor->process($value);
        
        if($this->legacy)
        {
            IPSText::getTextClass( 'bbcode' )->parsing_section	= $this->section;
            IPSText::getTextClass( 'bbcode' )->parse_smilies    = 1;
            IPSText::getTextClass( 'bbcode' )->parse_html    	= 1;
            IPSText::getTextClass( 'bbcode' )->parse_nl2br		= 1;
            IPSText::getTextClass( 'bbcode' )->parse_bbcode    	= 1;
            IPSText::getTextClass( 'bbcode' )->bypass_badwords  = 0;
            IPSText::getTextClass( 'bbcode' )->parsing_mgroup = $this->memberData['member_group_id'];
            IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others = $this->memberData['mgroup_others'];			
            
            $value = IPSText::getTextClass( 'bbcode' )->preDbParse( $value );
        }        
        
        return $value;
    }
    
    public function convertToHtml($t)
    {
        $t = str_replace( "&amp;", "&" , $t );
    	$t = str_replace( "&#60;&#33;--", "<!--"		, $t );
    	$t = str_replace( "--&#62;"       ,"-->"			,  $t );
    	$t = str_replace( "&gt;", ">"          , $t );
    	$t = str_replace( "&lt;", "<"          , $t );
    	$t = str_replace( "&quot;", '"'			, $t );
    	$t = str_replace( "<br/>", "\n" , $t );
        $t = str_replace("<br>", "\n", $t);
        $t = str_replace("<br />", "\n", $t);
    	$t = str_replace( "&#036;", '$'	, $t );
    	$t = str_replace( "&#33;", "!"	, $t );
    	$t = str_replace( "&#39;", "'", $t );
        return $t;
    }
}