<?php

if (!defined('IN_ACP')) {
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_sfs_tools_tools extends ipsCommand
{
    
    public function doExecute(ipsRegistry $registry) {
  
        switch($this->request['do']) {
            default:
            $this->tools();
            break;
      
        case 'delblocks':
            $this->deleteBlocks();
            break;
        }
    
        $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
        $this->registry->output->sendOutput();
    }
    
    private function tools() {
        $this->registry->output->html .= "<div class='acp-box'>";
		$this->registry->output->html .= "<h3>{$this->lang->words['sfs_empBL']}</h3>";
        $this->registry->output->html .= "<form action='".$this->settings['base_url']."module=tools&amp;section=tools&amp;do=delblocks' method='post'>";
		$this->registry->output->html .= "<table class='ipsTable'>";
        $this->registry->output->html .= "<tr><td class='field_field'><strong>{$this->lang->words['sfs_empBL']}</strong><br />{$this->lang->words['sfs_blRemWarn']}</td></tr>";
        $this->registry->output->html .= "</table>";
		$this->registry->output->html .= "<div class='acp-actionbar'><input type='submit' class='button' value='{$this->lang->words['sfs_empBut']}' /></div>";
		$this->registry->output->html .= "</form>";
    }
    
    private function deleteBlocks() {
        $this->DB->delete('sfs_blocked');
        $this->registry->output->redirect($this->settings['base_url']."module=tools&section=tools" , "{$this->lang->words['sfs_blRed']}");
    }
    
}

?>