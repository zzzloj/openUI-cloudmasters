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

class admin_forums_defaultpost_forums extends ipsCommand 
{
	public $html;
    
    protected $templates;
    protected $forumArray;
    protected $templateClass;
	
	public function doExecute( ipsRegistry $registry )
	{
		$this->html               = $this->registry->output->loadTemplate( 'cp_skin_defaultpost' );
		$this->html->form_code    = 'module=defaultpost&amp;section=forums';
		$this->html->form_code_js = 'module=defaultpost&section=forums';
        
        $this->lang->loadLanguageFile(array('admin_defaultpost'), 'forums');
        
        $classToLoad = IPSLib::loadActionOverloader(IPSLib::getAppDir('forums') . '/modules_admin/defaultpost/templates.php', 'admin_forums_defaultpost_templates');
        $this->templateClass = new $classToLoad();
        $this->templateClass->makeRegistryShortcuts($this->registry);
        
        $this->_getTemplates();
        $this->_loadForumArray();
		
		switch( $this->request['do'] )
		{
            case 'view':
            default:
                $this->_showForums();
                break;
            
            case 'edit':
                $this->_forumForm();
                break;
            
            case 'save':
                $this->_saveForum();
                break;
            
            case 'delete':
                $this->_deleteForum();
                break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
    
    protected function _showForums()
    {
        if(!is_array($this->templates) || !count($this->templates))
        {
            $this->registry->output->html .= $this->html->noTemplates();
            return;
        }
            
        $start = intval($this->request['st']);
   		$limit = 50;
        
		$total = $this->DB->buildAndFetch(array('select' => 'count(*) as total', 'from' => 'defaultpost_templates_forums'));
        $pages = $this->registry->output->generatePagination(array('totalItems' => $total['total'],
                                                                    'currentStartValue' => $start,
                                                                    'itemsPerPage' => $limit,
                                                                    'baseUrl' => $this->settings['base_url'] . $this->html->form_code));
        
        $this->DB->build(array('select' => 'd.*',
                                'from' => array('defaultpost_templates_forums' => 'd'),
                                'order' => 'f.name',
                                'limit' => array($start, $limit),
                                'add_join' => array(
                                    array('select' => 'f.id, f.name',
                                            'from' => array('forums' => 'f'),
                                            'where' => 'd.forum_id=f.id',
                                            'type' => 'left')
                                )));
        $query = $this->DB->execute();
		while ($row = $this->DB->fetch($query) )
		{
		  $row['newTemplate'] = $this->templates[$row['new_template_id']]['name'];
          $row['newContent'] = $this->templateClass->parseForDisplay($this->templates[$row['new_template_id']]['content']);
          $row['replyContent'] = $this->templateClass->parseForDisplay($this->templates[$row['reply_template_id']]['content']);
          $rows[] = $row;
        }
        $this->DB->freeResult($query);
        
		$this->registry->output->html .= $this->html->showForums( $rows, $pages );
    }
    
    protected function _forumForm()
    {
        $forumId = intval($this->request['forumId']);
        
        if(!is_array($this->templates) || !count($this->templates))
        {
            $this->registry->output->html .= $this->html->noTemplates();
            return;
        }
        
        $row = $this->DB->buildAndFetch(array('select' => '*', 'from' => 'defaultpost_templates_forums', 'where' => 'forum_id='.$forumId));
        
        $forumList = $this->_filterForumArray($forumId);
        if(!is_array($forumList) || count($forumList)==0)
            $this->registry->output->showError('All forums have been configured. Please go to the Manage Forums screen and edit an existing forum.','10DPC01');
        
        $templateList = $this->_buildTemplateArray();
        
        $formData['forumId'] = $this->registry->output->formDropdown('forumId',$forumList,$row['forum_id'],'forumId');
        $formData['newTemplateId'] = $this->registry->output->formDropdown('newTemplateId',$templateList,$row['new_template_id'],'newTemplateId');
        $formData['replyTemplateId'] = $this->registry->output->formDropdown('replyTemplateId',$templateList,$row['reply_template_id'],'replyTemplateId');
            
        $this->registry->output->html .= $this->html->forumForm($formData);
    }
    
    protected function _saveForum()
    {
        $forumId = intval($this->request['forumId']);
        
        $data = array('new_template_id' => intval($this->request['newTemplateId']),
                        'reply_template_id' => intval($this->request['replyTemplateId']));
                        
        $row = $this->DB->buildAndFetch(array('select' => '*', 'from' => 'defaultpost_templates_forums', 'where' => 'forum_id='.$forumId));
        if(is_array($row) && count($row))
        {
            $this->DB->update('defaultpost_templates_forums', $data, 'forum_id='.$forumId);
        }            
        else
        {
            $data['forum_id'] = $forumId;
            $this->DB->insert('defaultpost_templates_forums', $data);
        }
        
        $this->registry->output->silentRedirect($this->settings['base_url'] . $this->html->form_code);
    }
    
    protected function _deleteForum()
    {
        $this->DB->delete('defaultpost_templates_forums','forum_id='.intval($this->request['forumId']));
        
        $this->registry->output->silentRedirect($this->settings['base_url'] . $this->html->form_code);
    }
    
    protected function _getTemplates()
    {
        $this->DB->build(array('select' => '*', 'from' => 'defaultpost_templates', 'order' => 'name'));
        $tQuery = $this->DB->execute();
        while($t = $this->DB->fetch($tQuery))
            $this->templates[$t['id']] = $t;
    }
    
    protected function _loadForumArray()
    {
        require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/forums/class_forums.php' );
        require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/forums/admin_forum_functions.php' );
		$this->forum_functions = new admin_forum_functions( $this->registry );
		$this->forum_functions->forumsInit();
        $this->forumArray = $this->forum_functions->adForumsForumList();
        
        // The first element in this array is the "Make Root" option. Overwrite it with a blank value
        $this->forumArray[0] = array(0, '--');        
    }
    
    protected function _filterForumArray($forumId)
    {
        $this->DB->build(array('select' => '*', 'from' => 'defaultpost_templates_forums'));
        $fQuery = $this->DB->execute();
        while($f = $this->DB->fetch($fQuery))
            $forumsInUse[] = $f['forum_id'];
            
        if(!is_array($forumsInUse) || !count($forumsInUse))
            return $this->forumArray;
            
        foreach($this->forumArray as $f)
        {
            if(!in_array($f[0],$forumsInUse) || $f[0]==$forumId)
                $forumList[] = $f;
        }
        return $forumList;
    }
    
    protected function _buildTemplateArray()
    {
        $templateList[] = array(0,'--');
        
        if(is_array($this->templates) && count($this->templates))
        {
            foreach($this->templates as $t)
                $templateList[] = array($t['id'],$t['name']);
        }        
        return $templateList;
    }

}