<?php

/*
 * App: Custom BBCode Tables for IPB 3.4.x
 * Ver: 1.1.6
 * Web: http://www.ipbaccess.com
 * Author: Zafer BAHADIR - Oscar
 * customTables.php
 */

if ( !defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'init.php'.";
	exit();
}

class bbcodeTables_library
{
	protected $registry;
	protected $request;
	protected $DB;


	public function __construct( $registry )
	{
		$this->registry     =  $registry;
		$this->request      =& $this->registry->fetchRequest();
		$this->DB           =  $this->registry->DB();
        $this->lang         =  $this->registry->getClass('class_localization');
        $this->settings     =& $this->registry->fetchSettings();
	}

	public function serialToArray( $serial,$columns )
	{
        $items = explode(",",$serial);
        $pass = 0;
        for($i=0 ;$i<(count($items)/$columns); $i++)
        {
            for($a=0; $a<$columns; $a++)
            {
                $result[$i][] = $items[$a+$pass];
                if($a==$columns-1) $pass = $pass + $columns;
            }
        }
        return $result;
	}


	public function sessionBBCodeList( $bbcode_key )
	{

        $set['status'] = "sessionBBCodeList";
        $this->DB->build( array(
            'select'   => 's.*',
            'from'     => array( 'bbcodeTables_sessions' => 's' ),
            'where'    => "session='".$bbcode_key."'",
            'order'    => 's.sid DESC',

            'add_join' => array( 0 => array(
            'select' => 't.*',
            'from'   => array( 'bbcodeTables' => 't' ),
            'where'  => 't.id = s.bbcodeid',
            'type'   => 'left') ),
            ));
        $this->DB->execute();

        while ( $row = $this->DB->fetch() )
        {
            $set['info'] = "BBCodeTables List This Session";
            $data['title'] = $row['title'];
            $data['id'] = $row['id'];
            $list .= $this->registry->output->getTemplate('bbcodeTables')->bbcode_rows($data,$set);
        }

        $form = $this->registry->getClass('output')->getTemplate('bbcodeTables')->bbcode_results($list, $set);

        if(is_array($data) AND count($data))
        return $form;
        else
        return "blank";

    }

	public function bbcodePostLists( $postid )
	{

            $set['status'] = "postBBCodeList";
            $this->DB->build( array(
			    'select' => '*',
			    'from'   => 'bbcodeTables',
			    'where'  => "postid=".$postid,
			    ) );

	        $this->DB->execute();
            while( $data = $this->DB->fetch() )
	        {
                $test['id'] = $data['id'];
                $list .= $this->registry->output->getTemplate('bbcodeTables')->bbcode_rows($data,$set);
            }
            $set['info'] = $this->lang->words['form_table_bbcodethispost'];
            $loadList = $this->registry->getClass('output')->getTemplate('bbcodeTables')->bbcode_results($list,$set);

            if(is_array($test) AND count($test))
            return $loadList;
            else
            return "";

    }


	public function getTableContents( $bbcodeid , $mode, $section)
	{

        $tableData = $this->DB->buildAndFetch( array('select'=> '*','from'=>'bbcodeTables','where'=> "id='".$bbcodeid."'") );
        $colon = $tableData['columns'];
        $item = unserialize($tableData['content']);

        $pass = 0;
        for($i=0 ;$i<$tableData['rows']; $i++)
        {
            for($a=0; $a<$colon; $a++)
            {
                if($a=='0')
                {
                //$data['sira'] = $i;
                $tr .="<tr>";
                $tr .= "<Td width='1%'><INPUT type='checkbox' id='chkBox' name='chk[]' value='".$i."' onclick=\"selectControl(this.form,'$section')\" /></td>";
                }

                $data['title'] = $item[$i][$a];
                /*
                if($tableData['headerpos'] != 'Top' and $a==0 )
                {
                    if($i==0 and $tableData['headerpos'] == 'Both')
                    $data['title'] = "*-*";
                    else
                    {
                    if($section == 'session')
                    $data['title'] = "Left Header*$section";
                    }
                }
                */
                //create id
                $data['id'] = ($a+$pass);
                if($a==($colon-1)) $pass = $pass + $colon;

                $set['status'] = $mode;
                $tr.= $this->registry->output->getTemplate('bbcodeTables')->bbcode_rows($data,$set);

                if($a==($colon-1))
                {
                    $tr .="</tr>";
                }
            }
        }
        $tableData['rowsBit'] =  $tr;

        return $tableData;
	}
}