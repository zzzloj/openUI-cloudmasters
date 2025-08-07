<?php
/**
 * ProMenu
 * Provisionists LLC
 *  
 * @ Package : 			ProMenu
 * @ File : 			mysql_updates.php
 * @ Last Updated : 	Apr 17, 2012
 * @ Author :			Robert Simons
 * @ Copyright :		(c) 2011 Provisionists, LLC
 * @ Link	 :			http://www.provisionists.com/
 * @ Revision : 		2
 */

 $SQL[] = "DELETE FROM skin_templates WHERE template_group='skin_promenu' AND template_name='header_menus';";

 $SQL[] = "DELETE FROM skin_templates WHERE template_group='skin_promenu' AND template_name='header_megamenus';";

 $SQL[] = "DELETE FROM skin_templates WHERE template_group='skin_promenu' AND template_name='header_submenus';";
 
 $SQL[] = "DELETE FROM skin_css WHERE css_app='promenu' AND css_group='promenu_header';";
 


