--
-- Table structure for table `action_track`
--

CREATE TABLE `action_track` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) unsigned NOT NULL default '0',
  `asset_name` varchar(255) NOT NULL default '',
  `action` varchar(255) NOT NULL default '',
  `asset_id` int(11) unsigned NOT NULL default '0',
  `timestamp` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `body_image`
--

CREATE TABLE `body_image` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cms_headline` varchar(255) NOT NULL default '',
  `small_image` varchar(255) NOT NULL default '',
  `large_image` varchar(255) NOT NULL default '',
  `alt` varchar(255) NOT NULL default 'default',
  `align` varchar(255) NOT NULL default 'default',
  `link` varchar(255) NOT NULL default '',
  `popout` tinyint(1) NOT NULL default '0',
  `description` text NOT NULL,
  `cms_active` tinyint(1) NOT NULL default '1',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_draft` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `cms_active` (`cms_active`),
  KEY `cms_deleted` (`cms_deleted`)
) ENGINE=InnoDB;

--
-- Table structure for table `cms_asset_info`
--

CREATE TABLE `cms_asset_info` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `asset` varchar(255) NOT NULL default '',
  `asset_name` varchar(255) NOT NULL default '',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `object` (`asset`)
) ENGINE=InnoDB;

--
-- Table structure for table `cms_asset_template`
--

CREATE TABLE `cms_asset_template` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `asset` varchar(255) NOT NULL default '',
  `template_filename` varchar(255) NOT NULL default '',
  `page_template_container_id` int(11) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;


--
-- Table structure for table `cms_audit_trail`
--

CREATE TABLE `cms_audit_trail` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user_id` int(11) unsigned NOT NULL default '0',
  `asset` varchar(255) NOT NULL default '',
  `asset_id` int(11) unsigned NOT NULL default '0',
  `workflow_id` int(11) unsigned NOT NULL default '0',
  `workflow_group_id` int(11) unsigned NOT NULL default '0',
  `page_id` int(10) unsigned NOT NULL default '0',
  `page_content_id` int(11) unsigned NOT NULL default '0',
  `action_taken` int(10) unsigned NOT NULL default '0',
  `ip` varchar(255) NOT NULL default '',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `cms_auth`
--

CREATE TABLE `cms_auth` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `real_name` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `username` varchar(255) NOT NULL default '',
  `password` varchar(32) NOT NULL default '',
  `user_level` int(2) NOT NULL default '0',
  `feed_token` varchar(32) default NULL,
  `confirmation_token` varchar(32) default NULL,
  `status` tinyint(1) NOT NULL default '1',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`real_name`)
) ENGINE=InnoDB;

--
-- Table structure for table `cms_counters`
--

CREATE TABLE `cms_counters` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `object_name` varchar(255) NOT NULL default '',
  `object_status` varchar(255) NOT NULL default '',
  `object_id` int(11) unsigned NOT NULL default '0',
  `session_id` varchar(255) NOT NULL default '',
  `ip` varchar(20) NOT NULL default '',
  `refer` varchar(255) default NULL,
  `country` char(2) default NULL,
  `region` char(2) default NULL,
  `datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `country` (`country`),
  KEY `ip` (`ip`),
  KEY `datetime` (`datetime`)
) ENGINE=InnoDB;

--
-- Table structure for table `cms_drafts`
--

CREATE TABLE `cms_drafts` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `asset` varchar(255) NOT NULL default '',
  `asset_id` int(11) unsigned NOT NULL default '0',
  `version_id` int(11) NOT NULL default '0',
  `draft` longtext NOT NULL,
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `object` (`asset`),
  KEY `object_id` (`asset_id`)
) ENGINE=InnoDB;

--
-- Table structure for table `cms_nterchange_versions`
--

CREATE TABLE `cms_nterchange_versions` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `asset` varchar(255) NOT NULL default '',
  `asset_id` int(11) unsigned NOT NULL default '0',
  `version` longtext NOT NULL,
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `object` (`asset`),
  KEY `object_id` (`asset_id`)
) ENGINE=InnoDB;

--
-- Table structure for table `cms_settings`
--

CREATE TABLE `cms_settings` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `setting` int(10) NOT NULL default '0',
  `value` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `code_caller`
--

CREATE TABLE `code_caller` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cms_headline` varchar(255) NOT NULL default '',
  `content` text,
  `dynamic` tinyint(1) NOT NULL default '0',
  `cms_active` tinyint(1) NOT NULL default '1',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_draft` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `html_header`
--

CREATE TABLE `html_header` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cms_headline` varchar(255) NOT NULL default '',
  `content` text,
  `cms_active` tinyint(1) NOT NULL default '1',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `media_element`
--

CREATE TABLE `media_element` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cms_headline` varchar(255) NOT NULL default '',
  `media_file` varchar(255) NOT NULL default '',
  `link_title` varchar(255) NOT NULL default '',
  `cms_active` tinyint(1) NOT NULL default '1',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_draft` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `cms_active` (`cms_active`),
  KEY `cms_deleted` (`cms_deleted`)
) ENGINE=InnoDB;

--
-- Table structure for table `tab`
--

CREATE TABLE `tab` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cms_headline` varchar(255) NOT NULL default '',
  `title` varchar(255) default NULL,
  `cms_active` tinyint(1) NOT NULL default '1',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_draft` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Table structure for table `page`
--

CREATE TABLE `page` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `parent_id` int(11) unsigned,
  `path` varchar(255) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `filename` varchar(64) NOT NULL default '',
  `page_template_id` int(11) unsigned NOT NULL default '0',
  `visible` tinyint(1) NOT NULL default '1',
  `active` tinyint(1) NOT NULL default '1',
  `printable` tinyint(1) NOT NULL default '1',
  `cache_lifetime` int(11) NOT NULL default '0',
  `client_cache_lifetime` int(11) NOT NULL default '3600',
  `secure_page` tinyint(1) NOT NULL default '0',
  `permissions_id` int(11) unsigned NOT NULL default '0',
  `sort_order` int(3) NOT NULL default '0',
  `external_url` varchar(255) NOT NULL default '',
  `external_url_popout` tinyint(1) NOT NULL default '0',
  `workflow_group_id` int(11) unsigned NOT NULL default '0',
  `workflow_recursive` tinyint(1) NOT NULL default '1',
  `disclaimer_required` tinyint(1) NOT NULL default '0',
  `disclaimer_recursive` tinyint(1) NOT NULL default '0',
  `meta_keywords` varchar(255) NOT NULL default '',
  `meta_description` varchar(255) NOT NULL default '',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `page_content`
--

CREATE TABLE `page_content` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `page_id` int(11) unsigned NOT NULL default '0',
  `page_template_container_id` int(11) unsigned NOT NULL default '0',
  `content_asset` varchar(255) NOT NULL default '',
  `content_asset_id` int(11) unsigned NOT NULL default '0',
  `content_order` tinyint(3) NOT NULL default '0',
  `timed_start` datetime default NULL,
  `timed_end` datetime default NULL,
  `cms_workflow` int(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `page_mirror`
--

CREATE TABLE `page_mirror` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cms_headline` varchar(255) NOT NULL default '',
  `page_id` int(11) unsigned NOT NULL,
  `cms_active` tinyint(1) NOT NULL default '1',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_draft` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `cms_active` (`cms_active`),
  KEY `cms_deleted` (`cms_deleted`)
) ENGINE=InnoDB;

--
-- Table structure for table `page_template`
--

CREATE TABLE `page_template` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `template_name` varchar(255) NOT NULL default '',
  `template_filename` varchar(255) NOT NULL default '',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `page_template_containers`
--

CREATE TABLE `page_template_containers` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `page_template_id` int(11) NOT NULL default '0',
  `container_name` varchar(255) NOT NULL default '',
  `container_var` varchar(255) NOT NULL default '',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cms_headline` varchar(255) NOT NULL default '',
  `type` varchar(255) NOT NULL default '',
  `group_id` int(11) unsigned NOT NULL default '0',
  `cms_active` tinyint(1) NOT NULL default '1',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `permissions_group`
--

CREATE TABLE `permissions_group` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cms_headline` varchar(255) NOT NULL default '',
  `group_name` varchar(255) NOT NULL default '',
  `cms_active` tinyint(1) NOT NULL default '1',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `permissions_user`
--

CREATE TABLE `permissions_user` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cms_headline` varchar(255) NOT NULL default '',
  `real_name` varchar(255) NOT NULL default '',
  `handle` varchar(255) NOT NULL default '',
  `password` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `last_login` varchar(255) NOT NULL default '',
  `cms_active` tinyint(1) NOT NULL default '1',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `permissions_user_group`
--

CREATE TABLE `permissions_user_group` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cms_headline` varchar(255) NOT NULL default '',
  `group_id` int(11) unsigned NOT NULL default '0',
  `user_id` int(11) unsigned NOT NULL default '0',
  `cms_active` tinyint(1) NOT NULL default '1',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `redirect`
--

CREATE TABLE `redirect` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cms_headline` varchar(255) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  `redirect` varchar(255) NOT NULL default '',
  `regex` tinyint(1) NOT NULL default '0',
  `count` int(11) unsigned NOT NULL default '0',
  `cms_active` tinyint(1) NOT NULL default '1',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `smarty_cache`
--

CREATE TABLE `smarty_cache` (
  `cache_id` char(32) NOT NULL,
  `cache_contents` mediumtext NOT NULL,
  PRIMARY KEY  (`cache_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `text`
--

CREATE TABLE `text` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cms_headline` varchar(255) NOT NULL default '',
  `content` text,
  `cms_active` tinyint(1) NOT NULL default '1',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_draft` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `whats_new`
--

CREATE TABLE `whats_new` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cms_headline` varchar(255) NOT NULL default '',
  `date_submitted` date default NULL,
  `summary` varchar(255) NOT NULL default '',
  `content` varchar(255) NOT NULL default '',
  `link` varchar(255) NOT NULL default '',
  `cms_active` tinyint(1) NOT NULL default '1',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_draft` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `workflow`
--

CREATE TABLE `workflow` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `page_id` int(11) unsigned NOT NULL default '0',
  `page_content_id` int(11) unsigned NOT NULL default '0',
  `workflow_group_id` int(11) unsigned NOT NULL default '0',
  `asset` varchar(255) NOT NULL default '',
  `asset_id` int(11) unsigned NOT NULL default '0',
  `action` int(3) NOT NULL default '0',
  `draft` longtext NOT NULL,
  `submitted` tinyint(1) NOT NULL default '0',
  `approved` tinyint(1) NOT NULL default '0',
  `comments` text NOT NULL,
  `timed_start` datetime default NULL,
  `timed_end` datetime default NULL,
  `parent_workflow` int(11) unsigned NOT NULL default '0',
  `completed` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `object` (`asset`),
  KEY `object_id` (`asset_id`)
) ENGINE=InnoDB;

--
-- Table structure for table `workflow_group`
--

CREATE TABLE `workflow_group` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `workflow_title` varchar(255) NOT NULL default '',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `workflow_users`
--

CREATE TABLE `workflow_users` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `workflow_group_id` int(11) unsigned NOT NULL default '0',
  `user_id` int(11) unsigned NOT NULL default '0',
  `role` int(3) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;

--
-- Table structure for table `test_sample`
--

CREATE TABLE `test_sample` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `cms_headline` varchar(255) NOT NULL default '',
  `the_varchar` varchar(255) NOT NULL,
  `the_text` text,
  `the_blob` blob NOT NULL,
  `the_tinyint` tinyint(1) NOT NULL,
  `the_int` int(11) NOT NULL,
  `the_float` float NOT NULL,
  `the_datetime` datetime NOT NULL,
  `the_date` date NOT NULL,
  `the_time` time NOT NULL,
  `the_year` year(4) NOT NULL,
  `cms_active` tinyint(1) NOT NULL default '1',
  `cms_deleted` tinyint(1) NOT NULL default '0',
  `cms_draft` tinyint(1) NOT NULL default '0',
  `cms_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `cms_modified_by_user` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB;
