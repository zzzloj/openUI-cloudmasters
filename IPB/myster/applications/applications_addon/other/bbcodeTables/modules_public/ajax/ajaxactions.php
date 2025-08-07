<?php

/*
 * App: Custom BBCode Tables for IPB 3.4.x
 * Ver: 1.1.6
 * Web: http://www.ipbaccess.com
 * Author: Zafer BAHADIR - Oscar
 * customTables.php
 */

if ( !defined('IN_IPB') )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_bbcodeTables_ajax_ajaxactions extends ipsAjaxCommand
{

    private $output;

    public function doExecute( ipsRegistry $registry )
	{
        $this->registry =  $registry;
        $this->output   =  $this->registry->output;
        $this->cache    =  $this->registry->cache();
        $this->caches   =& $this->registry->cache()->fetchCaches();
        $this->library  =  $this->registry->getClass('bbcodeTables_library');

        switch ($_POST['mode'])
        {
            case 'create':              $this->createTables();          break;
            case 'addRow':              $this->createTables();          break;
            case 'deleteRow':           $this->createTables();          break;

            case 'editBBCode':          $this->editBBCode();            break;
            case 'deletePostBBCode':    $this->deletePostBBCode();          break;
            case 'editAddRow':          $this->editBBCode();            break;
            case 'editDeleteRows':      $this->editBBCode();            break;
            case 'editSavePostBBCode':  $this->editSavePostBBCode();    break;
            case 'finalTable':          $this->finalTable();            break;
            case 'postBBCodeList':      $this->postBBCodeList();        break;
        }
	}




    public function createTables()
    {

        $bbcodekey = $_POST['bbcode_key'];
        $bbcodeid = $_POST['bbcodeid'];
        $set['hpos'] = $_POST['headerpos'];
        $set['bbcodekey'] = $bbcodekey;
        $myset['bbcode_key'] = $bbcodekey;

        // AddRow
        if($_POST['mode']=='addRow')
        {
            //get bbcode data
            $tableData = $this->DB->buildAndFetch( array('select'=> '*','from'=>'bbcodeTables','where'=> "id='".$bbcodeid."'") );
            $set['table_width'] = $tableData['table_width'];
            $set['cols'] = $tableData['columns'];

            //get content data
            foreach($this->request as $key => $line)
            {
              if(substr($key,0,5) == 'input')
              $keys[$key]= $line;
            }

            $serial = implode(",",$keys);
            $content = $this->library->serialToArray($serial,$tableData['columns']);
            array_push($content, array_fill(0, $tableData['rows'], '-'));

            // //update bbcode content
            $this->DB->update( 'bbcodeTables', array(
                'content'           => serialize($content),
                'title'             => $this->request['table_title'],
                'description'       => $this->request['table_desc'],
                'table_cell_vpos'   => $this->request['table_cell_vpos'],
                'table_width'       => $this->request['table_width'],
                'rows'=> count($content),
                ),
                "id='".$bbcodeid."'" );

            $set = $this->library->getTableContents( $bbcodeid, 'edit', 'session' );
            $set['cols'] = $tableData['columns'];
            $set['id'] = $bbcodeid;

            $myset = $this->cache->getCache('bbcodeTables_settings');
            $set['bbcodekey'] = $myset['bbcode_key'];

            // Create Pos SelectBox
            $pos = array("Left","Center","Right");
            $set['pos_select'] .= '<select name="table_cell_vpos" id="table_cell_vpos" size="1">';
            foreach($pos as $p)
            {
                $set['pos_select'] .= "<option value='$p'>".$p."</option>";
                if($p == $set['table_cell_vpos'])
                $set['pos_select'] .= "<option value='$p' selected='selected'>Selected => ".$p."</option>";
            }
            $set['pos_select'] .= '</select>';

            $form = $this->registry->getClass('output')->getTemplate('bbcodeTables')->bbcode_form($set);
            $this->returnJsonArray( array('createTables' => $form ) );

        }

        // delete Row
        if($_POST['mode']=='deleteRow')
        {

            //get bbcode data
            $tableData = $this->DB->buildAndFetch( array('select'=> '*','from'=>'bbcodeTables','where'=> "id='".$bbcodeid."'") );
            $set['table_width'] = $tableData['table_width'];
            $set['cols'] = $tableData['columns'];

            //get content data
            foreach($this->request as $key => $line)
            {
                if(substr($key,0,5) == 'input')
                $keys[$key]= $line;
            }

            $serial = implode(",",$keys);
            $content = $this->library->serialToArray($serial,$set['cols']);

            // get checkbox values
            foreach($this->request['chk'] as $s)
            {
                unset($content[$s]);
                $content2 = array_values($content);
            }

            // //update bbcode content
            $this->DB->update( 'bbcodeTables', array(
                'content'           => serialize($content2),
                'title'             => $this->request['table_title'],
                'description'       => $this->request['table_desc'],
                'table_cell_vpos'   => $this->request['table_cell_vpos'],
                'table_width'       => $this->request['table_width'],
                    'rows'=> count($content2),
                ),
                "id='".$bbcodeid."'" );

            $set = $this->library->getTableContents( $bbcodeid, 'edit','session' );
            $set['cols'] = $tableData['columns'];

            // Create Pos SelectBox
            $pos = array("Left","Center","Right");
            $set['pos_select'] .= '<select name="table_cell_vpos" id="table_cell_vpos" size="1">';
            foreach($pos as $p)
            {
                $set['pos_select'] .= "<option value='$p'>".$p."</option>";
                if($p == $set['table_cell_vpos'])
                $set['pos_select'] .= "<option value='$p' selected='selected'>Selected => ".$p."</option>";
            }
            $set['pos_select'] .= '</select>';

            $form = $this->registry->getClass('output')->getTemplate('bbcodeTables')->bbcode_form($set);
            $this->returnJsonArray( array('createTables' => $form ) );

        }

        // Create Table
        if($_POST['mode']=='create')
        {

            $set['tds'] = $trb;
            $set['cols'] = $_POST['columns'];
            $tbsize = $_POST['columns'] * 110;
            $set['table_width'] = $tbsize."px";

            //save session
            $this->cache->setCache('bbcodeTables_settings', $myset, array('array' => 1, 'donow'=> 0, 'deletetefirst' => 0));

            $tdgroups .= "<Td width='1%'><INPUT type='checkbox' name='chkold' value='0' /></Td>";

            for($i=1; $i<=$set['cols']; $i++)
            {
                // left header first box
                if ($set['hpos'] == 'Left' and $i==1)
                    $val1 = "Left Header";
                else
                    $val1 = "" ;

                // top header top box
                if ($set['hpos'] == 'Top')
                        $val1 = "Top Header$i";

                // both headers
                if ($set['hpos'] == 'Both')
                {
                    if($i==1)
                        $val1 = "***";
                    else
                        $val1 = "Top Header".($i-1);
                }

                $tdgroups .= "<td><INPUT type='text' class='input_text' name='input$i' id='input$i' value='$val1' size='10' /></td>";
            }

            $set['rowsBit'] = $tdgroups;

            //create select box
            $set['pos_select'] = '
            <select name="table_cell_vpos" id="table_cell_vpos" size="1">
            <option value="left" selected="selected">Left</option>
            <option value="center">Center</option>
            <option value="right">Right</option>
            </select>';

            $this->DB->insert(
                'bbcodeTables', array(
                'content'           => serialize($content),
                'title'             => $this->request['table_title'],
                'table_width'       => $set['table_width'],
                'table_cell_vpos'   => $set['pos_select'],
                'headerpos'         => $this->request['headerpos'],
                'rows'              => 1,
                'columns'           => $_POST['columns'],
                'userid'            => $this->memberData['member_id'],
                'username'          => $this->memberData['members_display_name'],
                ) );
            $set['id'] = $this->DB->getInsertId();


            $this->DB->insert(
                'bbcodeTables_sessions', array(
                'bbcodeid'  => $set['id'],
                'time'      => time(),
                'session'   => $_POST['bbcode_key'],
                ) );

            // Session Check and delete old records
            $ref = time()-54000;
            $this->DB->delete('bbcodeTables_sessions','time < '.$ref);

            $form = $this->registry->getClass('output')->getTemplate('bbcodeTables')->bbcode_form($set);
            $this->returnJsonArray( array('createTables' => $form ) );
        }

    }


    public function finalTable()
    {
        // create content data
        foreach($this->request as $key => $line)
        {
          if(substr($key,0,5) == 'input')
          {
            $keys[$key]= $line;
          }
        }

        $serial = implode(",",$keys);
        $content = serialize($this->library->serialToArray($serial,$this->request['columns']));

        //update bbcode content
        $this->DB->update( 'bbcodeTables', array(
            'title'             => $this->request['table_title'],
            'content'           => $content,
            'table_width'       => $this->request['table_width'],
            'description'       => $this->request['table_desc'],
            'table_cell_vpos'   => $this->request['table_cell_vpos'],
            'userid'            => $this->memberData['member_id'],
            'username'          => $this->memberData['members_display_name'],
            ),
            "id='".$this->request['bbcodeid']."'" );

        $myset = $this->cache->getCache('bbcodeTables_settings');
        //$set['bbcodekey'] = $myset['bbcode_key'];

        // get tables list in this session
        $form = $this->library->sessionBBCodeList( $myset['bbcode_key'] );
        $this->returnJsonArray( array('sessionBBCodeList' => $form ) );
    }


    public function editBBCode()
    {

        $bbcodeid = $_POST['bbcodeid'];
        $listmode = $_POST['listmode'];

        //get bbcode data
        $tableData = $this->DB->buildAndFetch( array('select'=> '*','from'=>'bbcodeTables','where'=> "id='".$bbcodeid."'") );

        //save settings for editBBcode
        $myset = $this->cache->getCache('bbcodeTables_settings');

        $newset['bbcode_key'] = $myset['bbcode_key'];
        $newset['bbcodeid'] = $bbcodeid;
        $newset['listmode'] = $listmode;
        $newset['bbcodeData'] = $tableData;


        $this->cache->setCache('bbcodeTables_settings', $newset, array('array' => 1, 'donow'=> 0, 'deletetefirst' => 0));

        //Delete Rows
        if($_POST['mode']=='editDeleteRows')
        {

            //get content data
            foreach($this->request as $key => $line)
            {
                if(substr($key,0,5) == 'input')
                $keys[$key]= $line;
            }

            $serial = implode(",",$keys);
            $content = $this->library->serialToArray($serial, $tableData['columns']);

            // get checkbox values
            foreach($this->request['chk'] as $s)
            {
                unset($content[$s]);
                $content2 = array_values($content);
            }

            // //update bbcode content
            $this->DB->update( 'bbcodeTables', array(
                'content'           => serialize($content2),
                'headerpos'         => $this->request['headerpos'],
                'title'             => $this->request['table_title'],
                'description'       => $this->request['table_desc'],
                'table_width'       => $this->request['table_width'],
                'table_cell_vpos'   => $this->request['table_cell_vpos'],
                'rows'=> count($content2),
                ),
                "id='".$bbcodeid."'" );
        }

        //Add Rows and saved current values
        if($_POST['mode']=='editAddRow')
        {

            //get content data
            foreach($this->request as $key => $line)
            {
              if(substr($key,0,5) == 'input')
              $keys[$key]= $line;
            }

            $serial = implode(",",$keys);
            $content = $this->library->serialToArray($serial, $tableData['columns']);
            array_push($content, array_fill(0, $tableData['rows'], '-'));

            // //update bbcode content
            $this->DB->update( 'bbcodeTables', array(
                'content'           => serialize($content),
                'title'             => $this->request['table_title'],
                'headerpos'         => $this->request['headerpos'],
                'description'       => $this->request['table_desc'],
                'table_width'       => $this->request['table_width'],
                'table_cell_vpos'   => $this->request['table_cell_vpos'],
                'rows'=> count($content),
                ),
                "id='".$bbcodeid."'" );
        }

        // get bbcode lists
        $bbcodeList = $this->library->getTableContents( $bbcodeid, 'edit','post' );
        $bbcodeList['listmode'] = $listmode;

        // Create Pos SelectBox
        $pos = array("Left","Center","Right");
        $bbcodeList['pos_select'] .= '<select name="table_cell_vpos" id="table_cell_vpos" size="1">';
        foreach($pos as $p)
        {
            $bbcodeList['pos_select'] .= "<option value='$p'>".$p."</option>";
            if($p == $bbcodeList['table_cell_vpos'])
            $bbcodeList['pos_select'] .= "<option value='$p' selected='selected'>Selected => ".$p."</option>";
        }
        $bbcodeList['pos_select'] .= '</select>';

        // Create HeaderPos SelectBox
        $hpos = array("Left","Top","Both");
        $bbcodeList['headerpos_select'] .= '<select name="headerpos" id="headerpos" size="1">';
        foreach($hpos as $hp)
        {
            $bbcodeList['headerpos_select'] .= "<option value='$hp'>".$hp."</option>";
            if($hp == $bbcodeList['headerpos'])
            $bbcodeList['headerpos_select'] .= "<option value='$hp' selected='selected'>Selected => ".$hp."</option>";
        }
        $bbcodeList['headerpos_select'] .= '</select>';


        $form = $this->registry->getClass('output')->getTemplate('bbcodeTables')->bbcode_editform($bbcodeList);
        $this->returnJsonArray( array('editForm' => $form ) );
    }

    public function deletePostBBCode()
    {
        $bbcodeid = $this->request['bbcodeid'];

        if($this->request['listmode'] == 'post')
        {
            $myset = $this->cache->getCache('bbcodeTables_settings');
            $postid = $myset['postid'];

            // Remove for session list
            $this->DB->delete('bbcodeTables_sessions',"bbcodeid=".$bbcodeid);

            // delete bcode
            $this->DB->delete('bbcodeTables',"id=".$bbcodeid);

            // get tables list in this post
            $form = $this->library->bbcodePostLists( $postid );
            $this->returnJsonArray( array('postBBCodeListDiv' => $form ) );
        }

        if($this->request['listmode'] == 'session')
        {

            // Remove for session list
            $this->DB->delete('bbcodeTables_sessions',"bbcodeid=".$bbcodeid);

            // delete bcode
            $this->DB->delete('bbcodeTables',"id=".$bbcodeid);

            $myset = $this->cache->getCache('bbcodeTables_settings');
            $form = $this->library->sessionBBCodeList($myset['bbcode_key']);
            $this->returnJsonArray( array('sessionBBCodeListDiv' => $form ) );
        }

    }

    public function editSavePostBBCode()
    {

        $myset = $this->cache->getCache('bbcodeTables_settings');

        //get content data
        foreach($this->request as $key => $line)
        {
          if(substr($key,0,5) == 'input')
          {
            $keys[$key]= $line;
          }
        }

        $serial = implode(",",$keys);
        $content = serialize($this->library->serialToArray($serial,$myset['bbcodeData']['columns'] ));

        //update bbcode content
        $res = $this->DB->update( 'bbcodeTables', array(
            'content'           => $content,
            'title'             => $this->request['table_title'],
            'headerpos'         => $this->request['headerpos'],
            'description'       => $this->request['table_desc'],
            'table_width'       => $this->request['table_width'],
            'table_cell_vpos'   => $this->request['table_cell_vpos'],
            ),
            "id='".$this->request['bbcodeid']."'" );

        if($myset['listmode'] == 'post')
        {
            $form = $this->library->bbcodePostLists( $myset['bbcodeData']['postid'] );
            $this->returnJsonArray( array('postBBCodeListDiv' => $form ) );
        }
        else
        {
            $form = $this->library->sessionBBCodeList($myset['bbcode_key']);
            $this->returnJsonArray( array('sessionBBCodeListDiv' => $form ) );
        }

    }

        public function postBBCodeList()
        {
            $postid = $this->request['postid'];

            //save session
            $myset['postid'] = $postid;
            $this->cache->setCache('bbcodeTables_settings', $myset, array('array' => 1, 'donow'=> 0, 'deletetefirst' => 0));

            $form = $this->library->bbcodePostLists( $postid);
            $this->returnJsonArray( array('postBBCodeListDiv' => $form ) );
        }


}

?>
