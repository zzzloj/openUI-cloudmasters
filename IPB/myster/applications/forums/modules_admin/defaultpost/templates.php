<?php
/*
+--------------------------------------------------------------------------
|   [HSC] Default Post Content 2.0.0.0
|   =============================================
|   by Esther Eisner
|   Copyright 2010 HeadStand Consulting
|   esther@headstandconsulting.com
+--------------------------------------------------------------------------
*/

if ( !defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_forums_defaultpost_templates extends ipsCommand 
{
	public $html;
    
    protected $legacy;
	
	public function doExecute( ipsRegistry $registry )
	{
		$this->html               = $this->registry->output->loadTemplate( 'cp_skin_defaultpost' );
		$this->html->form_code    = 'module=defaultpost&amp;section=templates';
		$this->html->form_code_js = 'module=defaultpost&section=templates';
        
        $this->lang->loadLanguageFile(array('admin_defaultpost'), 'forums');

        // Load the editor class        
        $classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor = new $classToLoad();
        
        $version = IPSLib::fetchVersionNumber('core');
        $this->legacy = $version['long'] < 34000;
		
		switch( $this->request['do'] )
		{
            case 'view':
            default:
                $this->_showTemplates();
                break;
                
            case 'edit':
                $this->_templateForm();
                break;
            
            case 'save':
                $this->_saveTemplate();
                break;
            
            case 'delete':
                $this->_deleteTemplate();
                break;
		}
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
    
    protected function _showTemplates()
    {
        $start = intval($this->request['st']);
   		$limit = 50;
        
        $total = $this->DB->buildAndFetch(array('select' => 'count(*) as total', 'from' => 'defaultpost_templates'));
        $pages = $this->registry->output->generatePagination(array('totalItems' => $total['total'],
                                                                    'currentStartValue' => $start,
                                                                    'itemsPerPage' => $limit,
                                                                    'baseUrl' => $this->settings['base_url'] . $this->html->form_code));
        
        $this->DB->build(array('select' => '*', 'from' => 'defaultpost_templates', 'order' => 'name', 'limit' => array($start,$limit)));
        $query = $this->DB->execute();
		while ($row = $this->DB->fetch($query))
		{
		    $row['content'] = $this->parseForDisplay($row['content']);
            $rows[] = $row;
        }
        $this->DB->freeResult($query);
        
		$this->registry->output->html .= $this->html->showTemplates( $rows, $pages );
    }
    
    protected function _templateForm()
    {
        $id = intval($this->request['id']);
        
        $row = $this->DB->buildAndFetch(array('select' => '*', 'from' => 'defaultpost_templates', 'where' => 'id='.$id));
        
        $formData['content'] = $this->loadEditor('content', $row['content']);        
        $formData['id'] = $row['id'];
        $formData['name'] = $this->registry->output->formInput('name',$row['name'],'name',30,'text','','',20);
            
        $this->registry->output->html .= $this->html->templateForm($formData);
    }
    
    protected function _saveTemplate()
    {
        $data = array('name' => $this->DB->addSlashes($this->request['name']),
                        'content' => $this->parseForSave($_POST['content']));
        
        if(intval($this->request['id']))
        {
            $this->DB->update('defaultpost_templtaes', $data, 'id=' . $this->request['id']);
        }
        else
        {
            $this->DB->insert('defaultpost_templates', $data);
        }
        
        $this->registry->output->silentRedirect($this->settings['base_url'] . $this->html->form_code);
    }
    
    protected function _deleteTemplate($id)
    {
        $this->DB->delete('defaultpost_templates','id='.intval($this->request['id']));
        
        $this->registry->output->silentRedirect($this->settings['base_url'] . $this->html->form_code);        
    }
    
    public function parseForEdit($value)
    {
        IPSText::getTextClass( 'bbcode' )->parsing_section	= 'topics';
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
        if($this->legacy)
        {
            $fieldContent = $this->parseForEdit($fieldContent);
        }
        else
        {
            $this->editor->setLegacyMode(false);
            $this->editor->setIsHtml(0);
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
}