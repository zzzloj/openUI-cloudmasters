<?php

if (!defined('IN_ACP')) {
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_sfs_settings_blockedlist extends ipsCommand {
  
    public function doExecute(ipsRegistry $registry) {
        switch($this->request['do']) {
            default:
            $this->showBlocked();
            break;
      
        case 'show':
            $this->showBlocked();
            break;
        
        case 'remlist':
            $this->remList();
            break;
        
        case 'whitelist':
            $this->whiteList();
            break;
        }
    
        $this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
        $this->registry->output->sendOutput();
    }
  
    private function showBlocked() {
    
        $this->registry->output->html .= "<script type='text/javascript'>
        function Check(chk)
        {
            if(document.bllist.Check_All.value=='Check All') {
                for (i = 0; i < chk.length; i++)
                chk[i].checked = true ;
                document.bllist.Check_All.value='{$this->lang->words['sfs_unChkAll']}';
            } else {
                for (i = 0; i < chk.length; i++)
                chk[i].checked = false ;
                document.bllist.Check_All.value='{$this->lang->words['sfs_chkAll']}';
            }
        }
        </script>
        <div class='acp-box'>";
		$this->registry->output->html .= "<h3>{$this->lang->words['sfs_blockReg']}</h3>";
        $this->registry->output->html .= "<form action='{$this->settings['base_url']}module=settings&amp;section=blockedlist&amp;do=remlist' method='post' name='bllist'>";
		
		$this->registry->output->html .= "<table class='ipsTable single_pad'>";
		$this->registry->output->html .= "<tr>
      <th style='padding:3px 0;text-align:center' width='2%'></th>
      <th style='padding:3px 0;text-align:center' width='10%'>{$this->lang->words['sfs_blockBy']}</th>
      <th style='padding:3px 0;text-align:center' width='10%'>{$this->lang->words['sfs_dateBlock']}</th>
      <th style='padding:3px 0;text-align:center' width='10%'>{$this->lang->words['sfs_times']}</th>
      <th style='padding:3px 0;text-align:center' width='10%'>{$this->lang->words['sfs_seenSfs']}</th>
      <th style='padding:3px 0;text-align:center' width='9%'>{$this->lang->words['sfs_last']}</th>
      <th style='padding:3px 0;text-align:center' width='10%'>{$this->lang->words['sfs_score']}</th>
      <th style='padding:3px 0;text-align:center' width='15%'>{$this->lang->words['sfs_userName']}</th>
      <th style='padding:3px 0;text-align:center' width='15%'>{$this->lang->words['sfs_em']}</th>
      <th style='padding:3px 0;text-align:center' width='8%'>{$this->lang->words['sfs_ip']}</th>
      <th style='padding:3px 0;text-align:right' width='1%'>{$this->lang->words['sfs_Options']}&nbsp;&nbsp;&nbsp;&nbsp;</th>
    </tr>";
    
        $count = $this->DB->build(array('select' => 'blockID', 'from' => 'sfs_blocked'));
        $this->DB->execute($count);
    
        if ($this->DB->GetTotalRows()) {
            $st = isset($this->request['st']) ? intval($this->request['st']) : 0;
            $totBlk = $this->DB->GetTotalRows();
            $pp = 25;
            
            $this->DB->build(array('select' => '*', 'from' => 'sfs_blocked', 'order' => 'blockDate DESC', 'limit' => array($st, $pp)));
            $this->DB->execute();
      
            $pages = $this->registry->output->generatePagination(array('totalItems' => $totBlk,
                                                                  'itemsPerPage' => $pp,
                                                                  'currentStartValue' => $st,
                                                                  'baseUrl' => $this->settings['base_url'].'module=settings&section=blockedlist'
                                                                  ));
                                                                  
            while($blkd = $this->DB->fetch()) {
                if ($blkd['blockedBy'] == "IP") {
                    $rea = $blkd['blockIP'];
                } else {
                    $rea = $blkd['blockEM'];
                }
                $blOptions = "<ul class='ipsControlStrip'>
          <li class='i_edit'><a href='http://www.stopforumspam.com/search?q={$rea}' target='_blank' title='{$this->lang->words['sfs_view']}'>{$this->lang->words['sfs_view']}</a></li>
          <li class='ipsControlStrip_more ipbmenu' id='menu_{$blkd['blockID']}'><a href='#'>&nbsp;</a></li>
          </ul>
          <ul class='acp-menu' id='menu_{$blkd['blockID']}_menucontent' style='display: none'>
          <li class='icon delete'><a href='{$this->settings['base_url']}{$this->form_code}&amp;app=sfs&amp;module=settings&amp;section=blockedlist&amp;do=remlist&amp;id={$blkd['blockID']}' title='{$this->lang->words['sfs_reTra']}'>{$this->lang->words['sfs_reTra']}</a></li>
          <li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}&amp;app=sfs&amp;module=settings&amp;section=blockedlist&amp;do=whitelist&amp;id={$blkd['blockID']}' title='{$this->lang->words['sfs_wlEntry']}'>{$this->lang->words['sfs_wlEntry']}</a></li>
          </ul>";
                $lastDate = date('m/d/Y', $blkd['sfsLast']);
                $blockDate = date('m/d/Y', $blkd['blockDate']);
                $this->registry->output->html .= "<tr class='ipsControlRow'>
        <td style='align:center'><input type='checkbox' name='blocked[]' id ='blocked' value='{$blkd['blockID']}'></td>
        <td style='padding:3px 0;text-align:center'>" . $blkd['blockedBy'] . "</td>
        <td style='padding:3px 0;text-align:center'>" . $blockDate . "</td>
        <td style='padding:3px 0;text-align:center'>" . $blkd['timesBlocked'] . "</td>
        <td style='padding:3px 0;text-align:center'>" . $blkd['sfsFreq'] . "</td>
        <td style='padding:3px 0;text-align:center'>" . $lastDate . "</td>
        <td style='padding:3px 0;text-align:center'>" . $blkd['sfsConf'] . "%</td>
        <td style='padding:3px 0;text-align:center'>" . $blkd['blockUN'] . "</td>
        <td style='padding:3px 0;text-align:center'>" . $blkd['blockEM'] . "</td>
        <td style='padding:3px 0;text-align:center'><a href='" . $this->settings['_base_url'] . "app=members&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip=" . $blkd['blockIP'] . "'>{$blkd['blockIP']}</a></td>
        <td class='col_buttons'>{$blOptions}</td></tr>";
            }
        } else {
            $this->registry->output->html .= "<tr><td style='padding:3px 0;text-align:center' colspan='12'>{$this->lang->words['sfs_noBlock']}</td></tr>";
        }

        $this->registry->output->html .= "</table>";
        if ($totBlk > 1) {
            $cab = "<input type='button' name='Check_All' value='{$this->lang->words['sfs_chkAll']}' onClick='Check(document.bllist.blocked)' class='button'>&nbsp;&nbsp";
        } else {
            $cab = "";
        }
        $this->registry->output->html .= "<div class='acp-actionbar'>{$cab}<input type='submit' class='button' value='{$this->lang->words['sfs_remSel']}' /></div>";
        $this->registry->output->html .= "</form>";
        $this->registry->output->html .= "</div>";
        $this->registry->output->html .= "{$pages}";
    }
  
    private function remList() {
        if(isset($this->request['id'])) {
            $lid = $this->request['id'];
        } else {
            $lid = implode(',', $this->request['blocked']);
        }
        
        if (empty($lid)) {
            $this->registry->output->showError($this->lang->words['sfs_noRem']);
        }
        
        $this->DB->delete('sfs_blocked', 'blockID IN ('.$lid.')');
        $this->registry->output->redirect($this->settings['base_url']."module=settings&section=blockedlist" , "{$this->lang->words['sfs_bloRem']}");
    }
  
    private function whiteList() {
        $wid = $this->request['id'];
    
        $which = $this->DB->buildAndFetch(array('select' => 'blockedBy, blockUN, blockIP', 'from' => 'sfs_blocked', 'where' => "blockID = {$wid}"));
        if ($which['blockedBy'] == "IP") {
            $ins = $which['blockIP'];
        } else {
            $ins = $which['blockUN'];
        }
        $this->DB->insert('sfs_whitelist',(array('wlInfo' => $ins,
                                                'wlEntry' => time())));
        $this->DB->delete('sfs_blocked', 'blockID='.$wid);
        $this->registry->output->redirect($this->settings['base_url']."module=settings&section=blockedlist" , "{$this->lang->words['sfs_wlMove']}");
    }
  
}

?>