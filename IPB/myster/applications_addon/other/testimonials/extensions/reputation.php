<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

$rep_author_config = array( 't_id' => array( 'column' => 't_member_id', 'table'  => 'testemunhos' ) );


                          
$rep_log_joins = array(
						array(
								'select' => 't.t_title as repContentTitle, t.t_id as repContentID',
								'from'   => array( 'testemunhos' => 't' ),
								'where'  => 'r.type="t_id" AND r.type_id=t.t_id and r.app="testimonials"',
								'type'   => 'left'
							),
					);

$rep_log_where = "t.t_member_id=%s";

$rep_log_link = 'app=testimonials&module=testemunhos&section=view&showtestimonial=%d';