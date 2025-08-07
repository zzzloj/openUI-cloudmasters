<?php

if (!defined('IN_ACP')) {
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_sfs_settings_whitelist extends ipsCommand {

    public function doExecute(ipsRegistry $registry) {
  
    switch($this->request['do']) {
        default:
            $this->showWhitelist();
            break;
        
        case 'remlist':
            $this->remList();
            break;
        
        case 'addentry':
            $this->addEntry();
            break;
    }
    
    $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
    $this->registry->output->sendOutput();
    }
  
    private function showWhitelist() {
        $this->registry->output->html .= "<div class='acp-box'>";
		$this->registry->output->html .= "<h3>{$this->lang->words['add_wlEntry']}</h3>";
		
		$this->registry->output->html .= "<form action='{$this->settings['base_url']}module=settings&amp;section=whitelist&amp;do=addentry' method='post'>";
		
		$this->registry->output->html .= "<table class='ipsTable single_pad'>";
		
		$this->registry->output->html .= "<tr><td class='field_title' align='center'><strong
class='title'>{$this->lang->words['add_wlWhat']}</strong></td><td class='field_field' align='center'>{$this->registry->output->formInput('newWl')}</td></tr>";
		
		$this->registry->output->html .= "</table>";
		$this->registry->output->html .= "<div class='acp-actionbar'><input type='submit' class='button' value='{$this->lang->words['sfs_addEntry']}' /></div>";
        $this->registry->output->html .= "</form>";
        $this->registry->output->html .= "</div>";
  
        $this->registry->output->html .= "<div class='acp-box'>";
		$this->registry->output->html .= "<h3>Whitelisted Entries</h3>";
		
		$this->registry->output->html .= "<table class='ipsTable single_pad'>";
		
		$this->registry->output->html .= "<tr>
      <th width='40%'>{$this->lang->words['sfs_dateEntered']}</th>
      <th width='40%'>{$this->lang->words['sfs_Whitelisted']}</th>
      <th width='20%'>{$this->lang->words['sfs_Remove']}</th>
    </tr>";
    
        $count = $this->DB->build(array('select' => 'wlID', 'from' => 'sfs_whitelist'));
        $this->DB->execute($count);
    
        if ($this->DB->GetTotalRows()) {
            $st = isset($this->request['st']) ? intval($this->request['st']) : 0;
            $totWl = $this->DB->GetTotalRows();
            $pp = 25;
      
            $this->DB->query("SELECT * FROM ".$this->settings['sql_tbl_prefix']."sfs_whitelist LIMIT $st, $pp");
      
            $pages = $this->registry->output->generatePagination(array('totalItems' => $totWl,
                                                                  'itemsPerPage' => $pp,
                                                                  'currentStartValue' => $st,
                                                                  'baseUrl' => $this->settings['base_url'].'module=settings&section=whitelist'
                                                                  ));
                                                                  
        while($wl = $this->DB->fetch()) {
            $entryDate = date('m/d/Y', $wl['wlEntry']);
            $this->registry->output->html .= "<tr>
        <td>" . $entryDate . "</td>
        <td>" . $wl['wlInfo'] . "</td>
        <td class='ipsActionButton'><a href='{$this->settings['base_url']}{$this->form_code}&amp;app=sfs&amp;module=settings&amp;section=whitelist&amp;do=remlist&amp;id={$wl['wlID']}' title='{$this->lang->words['sfs_remWhite']}'><img src='{$this->settings['acp_url']}skin_cp/images/icons/delete.png'>{$this->lang->words['sfs_remWhite']}</a></td></tr>";
        }
        } else {
            $this->registry->output->html .= "<tr><td style='padding:3px 0;text-align:center' colspan='3'>{$this->lang->words['sfs_noWhite']}</td></tr>";
        }
		
        $this->registry->output->html .= "</table>";
        $this->registry->output->html .= "</div>";
        $this->registry->output->html .= "{$pages}";
    }
  
    private function remList() {
        $lid = $this->request['id'];
        $this->DB->delete('sfs_whitelist', 'wlID='.$lid);
        $this->registry->output->redirect($this->settings['base_url']."module=settings&section=whitelist" , "{$this->lang->words['sfs_whiteRem']}");
    }
  
    private function addEntry() {
        $this->DB->insert('sfs_whitelist',(array('wlInfo' => $this->request['newWl'],
                                              'wlEntry' => time())));
        $this->registry->output->redirect($this->settings['base_url']."module=settings&section=whitelist" , "{$this->lang->words['sfs_whiteAdd']}");
    }

}

?>