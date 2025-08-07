<?php

class cp_skin_sfs_member_form extends output
{

    public function __destruct()
    {
    }

    public function sfsMemContent($member) {

        if ($member['sfsNextCheck'] < time()) {
            require_once(IPS_KERNEL_PATH.'classFileManagement.php');
            $this->sfsChk = new classFileManagement();
            $nm = urlencode(iconv($this->settings['gb_char_set'], "UTF-8", $member['members_display_name']));
            $em = urlencode(iconv($this->settings['gb_char_set'], "UTF-8", $member['email']));
            $c = $this->sfsChk->getFileContents("http://www.stopforumspam.com/api?username={$nm}&ip={$member['ip_address']}&email={$em}&f=serial");
            $n = time() + 86400;
            $this->DB->update('pfields_content', array('sfsMemInfo' => $c, 'sfsNextCheck' => $n), 'member_id = '.$member['member_id']);
        } else {
            $c = $member['sfsMemInfo'];
        }
        $r = unserialize($c);
        $gt = ($member['sfsNextCheck'] - time()) / 3600;
        $tr = 24 - ceil($gt);

        if ($r['username']['appears'] > 0) {
            $name = "<td width='20%'>{$r['username']['frequency']}</td>
            <td width='20%'>{$r['username']['lastseen']}</td>
            <td width='15%'>{$r['username']['confidence']}</td>
            <td width='25%' class='ipsActionButton'><a href='http://www.stopforumspam.com/search/{$member['members_display_name']}' target='_blank'><img src='{$this->settings['acp_url']}skin_cp/images/icons/view.png'>{$this->lang->words['sfs_sfsView']}</a></td>";
        } else {
            $name = "<td colspan='4'>{$this->lang->words['sfs_unNotEx']}</td>";
        }
        
        if ($r['ip']['appears'] > 0) {
            $ip = "<td width='20%'>{$r['ip']['frequency']}</td>
            <td width='20%'>{$r['ip']['lastseen']}</td>
            <td width='15%'>{$r['ip']['confidence']}</td>
            <td width='25%'><label class='ipsActionButton'><a href='http://www.stopforumspam.com/search/{$member['ip_address']}' target='_blank'><img src='{$this->settings['acp_url']}skin_cp/images/icons/view.png'>{$this->lang->words['sfs_sfsView']}</a></label> ";
        } else {
            $ip = "<td colspan='3'>{$this->lang->words['sfs_ipNotEx']}</td><td>";
        }
        
        if ($r['email']['appears'] > 0) {
            $email = "<td width='20%'>{$r['email']['frequency']}</td>
            <td width='20%'>{$r['email']['lastseen']}</td>
            <td width='15%'>{$r['email']['confidence']}</td>
            <td width='25%' class='ipsActionButton'><a href='http://www.stopforumspam.com/search/{$member['email']}' target='_blank'><img src='{$this->settings['acp_url']}skin_cp/images/icons/view.png'>{$this->lang->words['sfs_sfsView']}</a></td>";
        } else {
            $email = "<td colspan='4'>{$this->lang->words['sfs_emNotEx']}</td>";
        }
        
        if ($tr < 23 && $tr > 0) {
            $lc = "{$this->lang->words['sfs_ls']} {$tr} {$this->lang->words['sfs_ago']}";
        } else {
            $lc = "{$this->lang->words['sfs_lth']}";
        }
    
        $IPBHTML = "";
    
        $IPBHTML .= <<<EOF
    	
    	<div id='tab_MEMBERS_{$tabID}_content'>
    		<table class='ipsTable double_pad'>
    			<tr>
                    <th width='20%'></th>
    				<th width='20%'>{$this->lang->words['sfs_seenSfs']}</th>
                    <th width='20%'>{$this->lang->words['sfs_last']}</th>
                    <th width='15%'>{$this->lang->words['sfs_score']}</th>
                    <th width='25%'>{$this->lang->words['sfs_Options']}</th>
    			</tr>
                <tr>
                    <td width='20%'><strong>{$this->lang->words['sfs_userName']}</strong></td>
                    {$name}
                </tr>
                <tr>
                    <td width='20%'><strong>{$this->lang->words['sfs_ipAddr']}</strong></td>
                    {$ip}  <label class='ipsActionButton'><a href='{$this->settings['_base_url']}app=members&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$member['ip_address']}'><img src='{$this->settings['acp_url']}skin_cp/images/icons/information.png'>{$this->lang->words['sfs_ipHis']}</a></label></td>
                </tr>
                <tr>
                    <td width='20%'><strong>{$this->lang->words['sfs_emAddr']}</strong></td>
                    {$email}
                </tr>
                <tr>
                    <td width='20%'><strong>{$this->lang->words['sfs_sfsLastUp']}</strong></td>
                    <td colspan='3'>{$lc}</td>
                    <td width='20%' class='ipsActionButton'><a href='{$this->settings['_base_url']}app=sfs&module=members&section=report&do=upmemcache&who={$member['member_id']}'><img src='{$this->settings['acp_url']}skin_cp/images/icons/arrow_refresh.png'>{$this->lang->words['sfs_forUpdate']}</a></td>
    		</table>
    	</div>

EOF;

        return $IPBHTML;
    }

    public function sfsMemTab($member) {

        $IPBHTML = "";

        $IPBHTML .= <<<EOF
	       <li id='tab_MEMBERS_{$tabID}' class=''>{$this->lang->words['sfs_sfs']}</li>
EOF;

        return $IPBHTML;
    }

}

?>