<?php
/**
* Installation Schematic File
* Generated on Thu, 04 Dec 2008 16:39:43 +0000 GMT
*/

$TABLE[] = "CREATE TABLE tracker_field (
  field_id int(10) NOT NULL AUTO_INCREMENT,
  field_keyword varchar(40) NOT NULL DEFAULT '',
  module_id int(10) NOT NULL DEFAULT '0',
  setup varchar(250) NOT NULL DEFAULT '',
  title varchar(250) NOT NULL DEFAULT '',
  position int(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (field_id)
);";

$TABLE[] = "CREATE TABLE tracker_field_changes (
  field_change_id int(20) NOT NULL AUTO_INCREMENT,
  date int(10) DEFAULT NULL,
  mid int(8) NOT NULL DEFAULT '0',
  issue_id int(10) NOT NULL DEFAULT '0',
  module varchar(250) DEFAULT NULL,
  title varchar(250) DEFAULT NULL,
  old_value varchar(250) DEFAULT NULL,
  new_value varchar(250) DEFAULT NULL,
  PRIMARY KEY (field_change_id),
  KEY issue_id (issue_id),
  KEY field_change_mid (mid)
);";

$TABLE[] = "CREATE TABLE tracker_issues (
  issue_id int(10) NOT NULL AUTO_INCREMENT,
  project_id int(10) NOT NULL DEFAULT '0',
  title varchar(200) NOT NULL DEFAULT '',
  title_seo varchar(200) NOT NULL DEFAULT '',
  state varchar(8) DEFAULT NULL,
  posts int(10) DEFAULT NULL,
  starter_id mediumint(8) NOT NULL DEFAULT '0',
  starter_name varchar(255) DEFAULT NULL,
  starter_name_seo varchar(255) DEFAULT NULL,
  start_date int(10) DEFAULT NULL,
  last_poster_id mediumint(8) NOT NULL DEFAULT '0',
  last_poster_name varchar(255) DEFAULT NULL,
  last_poster_name_seo varchar(255) DEFAULT NULL,
  last_post int(10) DEFAULT NULL,
  author_mode tinyint(1) DEFAULT NULL,
  hasattach smallint(5) NOT NULL DEFAULT '0',
  firstpost int(10) NOT NULL DEFAULT '0',
  private tinyint(1) DEFAULT '0',
  type varchar(255) NULL DEFAULT NULL,
  sug_up int(11) NULL DEFAULT '0',
  sug_down int(11) NULL DEFAULT '0',
  repro_up int(11) NULL DEFAULT '0',
  repro_down int(11) NULL DEFAULT '0',
  PRIMARY KEY (issue_id),
  KEY issue_firstpost (firstpost),
  KEY issue_last_post (project_id,last_post),
  KEY project_id (project_id),
  KEY issue_starter_id (starter_id,project_id),
  KEY last_post_sorting (last_post,project_id)
);";

$TABLE[] = "CREATE TABLE tracker_logs (
  id int(10) NOT NULL AUTO_INCREMENT,
  project_id int(5) DEFAULT '0',
  issue_id int(10) NOT NULL DEFAULT '0',
  post_id int(10) DEFAULT NULL,
  member_id mediumint(8) NOT NULL DEFAULT '0',
  member_name varchar(255) NOT NULL DEFAULT '',
  ip_address varchar(16) NOT NULL DEFAULT '0',
  http_referer varchar(255) DEFAULT NULL,
  ctime int(10) DEFAULT NULL,
  issue_name varchar(128) DEFAULT NULL,
  action varchar(128) DEFAULT NULL,
  query_string varchar(128) DEFAULT NULL,
  PRIMARY KEY (id)
);";

$TABLE[] = "CREATE TABLE tracker_moderators (
  moderate_id int(10) NOT NULL AUTO_INCREMENT,
  project_id int(10) NOT NULL DEFAULT '0',
  template_id int(10) NOT NULL DEFAULT '0',
  type varchar(10) NOT NULL,
  mode varchar(10) NOT NULL,
  mg_id int(10) NOT NULL,
  can_edit_posts tinyint(1) NOT NULL DEFAULT '0',
  can_edit_titles tinyint(1) NOT NULL DEFAULT '0',
  can_del_posts tinyint(1) NOT NULL DEFAULT '0',
  can_del_issues tinyint(1) NOT NULL DEFAULT '0',
  can_lock tinyint(1) NOT NULL DEFAULT '0',
  can_unlock tinyint(1) NOT NULL DEFAULT '0',
  can_move tinyint(1) NOT NULL DEFAULT '0',
  can_manage tinyint(1) NOT NULL DEFAULT '0',
  can_merge tinyint(1) NOT NULL DEFAULT '0',
  can_massmoveprune tinyint(1) NOT NULL DEFAULT '0',
  is_super tinyint(1) NOT NULL DEFAULT '0',
  name varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (moderate_id),
  KEY mg_id (type,mg_id)
);";

$TABLE[] = "CREATE TABLE tracker_module (
  module_id int(10) NOT NULL AUTO_INCREMENT,
  title varchar(250) NOT NULL DEFAULT '',
  description varchar(250) NOT NULL DEFAULT '',
  author varchar(250) NOT NULL DEFAULT '',
  version varchar(250) NOT NULL DEFAULT '',
  long_version int(10) NOT NULL DEFAULT '0',
  directory varchar(255) NOT NULL DEFAULT '',
  added int(10) NOT NULL DEFAULT '0',
  protected tinyint(1) NOT NULL DEFAULT '0',
  enabled tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (module_id)
);";

$TABLE[] = "CREATE TABLE tracker_module_upgrade_history (
  upgrade_id int(10) NOT NULL AUTO_INCREMENT,
  upgrade_version_id int(10) NOT NULL DEFAULT '0',
  upgrade_version_human varchar(200) NOT NULL DEFAULT '',
  upgrade_date int(10) NOT NULL DEFAULT '0',
  upgrade_mid int(10) NOT NULL DEFAULT '0',
  upgrade_notes text,
  upgrade_module varchar(32) NOT NULL DEFAULT 'core',
  PRIMARY KEY (upgrade_id),
  KEY upgrades (upgrade_module,upgrade_version_id)
);";

$TABLE[] = "CREATE TABLE tracker_posts (
  pid int(10) NOT NULL AUTO_INCREMENT,
  append_edit tinyint(1) DEFAULT '0',
  edit_time int(10) DEFAULT NULL,
  author_id mediumint(8) NOT NULL DEFAULT '0',
  author_name varchar(255) DEFAULT NULL,
  use_sig tinyint(1) NOT NULL DEFAULT '0',
  use_emo tinyint(1) NOT NULL DEFAULT '0',
  ip_address varchar(16) NOT NULL DEFAULT '',
  post_date int(10) DEFAULT NULL,
  icon_id smallint(3) DEFAULT NULL,
  post mediumtext,
  queued tinyint(1) NOT NULL DEFAULT '0',
  issue_id int(10) NOT NULL DEFAULT '0',
  post_title varchar(255) DEFAULT NULL,
  new_issue tinyint(1) DEFAULT '0',
  edit_name varchar(255) DEFAULT NULL,
  post_key varchar(32) NOT NULL DEFAULT '0',
  post_parent int(10) NOT NULL DEFAULT '0',
  post_htmlstate smallint(1) NOT NULL DEFAULT '0',
  post_edit_reason varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (pid),
  KEY issue_id (issue_id,queued,pid,post_date),
  KEY author_id (author_id,issue_id),
  KEY post_date (post_date),
  KEY ip_address (ip_address),
  KEY post_key (post_key)
);";

$TABLE[] = "CREATE TABLE tracker_project_field (
  project_id int(10) NOT NULL,
  field_id int(10) NOT NULL,
  position int(5) NOT NULL DEFAULT '0',
  enabled tinyint(2) NOT NULL DEFAULT '1',
  custom tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (project_id,field_id)
);";

$TABLE[] = "CREATE TABLE tracker_project_metadata (
  metadata_id int(10) NOT NULL AUTO_INCREMENT,
  field_id int(10) NOT NULL DEFAULT '0',
  project_id int(10) NOT NULL DEFAULT '0',
  meta_key varchar(250) NOT NULL DEFAULT '',
  meta_value text,
  PRIMARY KEY (metadata_id),
  KEY field_id (field_id),
  KEY project_id (project_id),
  KEY meta_key (meta_key)
);";

$TABLE[] = "CREATE TABLE tracker_projects (
  project_id int(10) NOT NULL AUTO_INCREMENT,
  title varchar(200) NOT NULL DEFAULT '',
  description text,
  template_id int(10) NOT NULL DEFAULT '0',
  parent_id int(10) NOT NULL DEFAULT '0',
  cat_only tinyint(1) NOT NULL DEFAULT '0',
  email_new tinyint(1) NOT NULL DEFAULT '0',
  position int(5) NOT NULL DEFAULT '0',
  enable_rss tinyint(1) NOT NULL DEFAULT '0',
  use_html tinyint(1) NOT NULL DEFAULT '0',
  use_ibc tinyint(1) NOT NULL DEFAULT '1',
  quick_reply tinyint(1) NOT NULL DEFAULT '1',
  private_issues tinyint(1) NOT NULL DEFAULT '0',
  private_default tinyint(1) NOT NULL DEFAULT '0',
  enable_suggestions tinyint(1) NOT NULL DEFAULT '1',
  disable_tagging tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (project_id)
)";

$TABLE[] = "CREATE TABLE tracker_ratings (
  rating_id int(11) NOT NULL AUTO_INCREMENT,
  member_id int(11) DEFAULT NULL,
  issue_id int(11) NOT NULL,
  type varchar(255) NOT NULL,
  score tinyint(1) DEFAULT NULL,
  PRIMARY KEY (rating_id)
);";

/* Alters */
$TABLE[] = "ALTER TABLE groups ADD g_tracker_view_offline TINYINT(1) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE groups ADD g_tracker_attach_max INT(10) NOT NULL DEFAULT '-1';";
$TABLE[] = "ALTER TABLE groups ADD g_tracker_attach_per_post INT(10) NOT NULL DEFAULT '0';";

if(!defined('IPS_IS_INSTALLER') OR ( defined('IPS_IS_INSTALLER') && IPS_IS_INSTALLER == FALSE))
{
	$DB  = ipsRegistry::DB();
	$m = $DB->build(array(	'select'	=>	'm.directory, m.long_version',
							'from'		=>	array('tracker_module'	=>	'm'),
							'where'		=>	'enabled=1'));
	$mo = $this->DB->execute($m);

	if($this->DB->getTotalRows($mo))
	{
		while($mod = $this->DB->fetch($mo))
		{
			$path	= IPSLib::getAppDir('tracker')."/modules/{$mod['directory']}/";
			$version = 0;

			/* Load the content of the file */
			$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH.'classXML.php', 'classXML' );
			$xml			= new $classToLoad( IPS_DOC_CHAR_SET );

			$xml->loadXML( file_get_contents($path . 'xml/information.xml') );

			foreach( $xml->fetchElements('data') as $data )
			{
				$data		= $xml->fetchElementsFromRecord( $data );
				$version	= $data['long_version'];
			}

			if(file_exists($path."setup/install/sql/{$mod['directory']}_mysql_tables.php") && $data['long_version'] == $mod['long_version'])
			{
				include_once($path."setup/install/sql/{$mod['directory']}_mysql_tables.php");
			}
		}
	}
}

?>