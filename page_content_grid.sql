# ************************************************************
# Sequel Pro SQL dump
# Version 4096
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: 127.0.0.1 (MySQL 5.6.17)
# Database: ead
# Generation Time: 2014-06-13 17:39:22 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table page_content
# ------------------------------------------------------------

DROP TABLE IF EXISTS `page_content`;

CREATE TABLE `page_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL DEFAULT '0',
  `page_template_container_id` int(11) NOT NULL DEFAULT '0',
  `content_asset` varchar(255) NOT NULL DEFAULT '',
  `content_asset_id` int(11) NOT NULL DEFAULT '0',
  `content_order` smallint(6) NOT NULL DEFAULT '0',
  `timed_start` datetime DEFAULT NULL,
  `timed_end` datetime DEFAULT NULL,
  `col_xs` varchar(255) NOT NULL DEFAULT '12',
  `offset_col_xs` varchar(255) NOT NULL DEFAULT '0',
  `row_xs` varchar(255) NOT NULL DEFAULT 'auto',
  `offset_row_xs` varchar(255) NOT NULL DEFAULT '0',
  `pull_xs` varchar(255) NOT NULL DEFAULT 'none',
  `gutter_xs` varchar(255) NOT NULL DEFAULT 'on on on on',
  `col_sm` varchar(255) NOT NULL DEFAULT 'inherit',
  `offset_col_sm` varchar(255) NOT NULL DEFAULT 'inherit',
  `row_sm` varchar(255) NOT NULL DEFAULT 'inherit',
  `offset_row_sm` varchar(255) NOT NULL DEFAULT 'inherit',
  `pull_sm` varchar(255) NOT NULL DEFAULT 'none',
  `gutter_sm` varchar(255) NOT NULL DEFAULT 'inherit inherit inherit inherit',
  `col_md` varchar(255) NOT NULL DEFAULT 'inherit',
  `offset_col_md` varchar(255) NOT NULL DEFAULT 'inherit',
  `row_md` varchar(255) NOT NULL DEFAULT 'inherit',
  `offset_row_md` varchar(255) NOT NULL DEFAULT 'inherit',
  `pull_md` varchar(255) NOT NULL DEFAULT 'none',
  `gutter_md` varchar(255) NOT NULL DEFAULT 'inherit inherit inherit inherit',
  `col_lg` varchar(255) NOT NULL DEFAULT 'inherit',
  `offset_col_lg` varchar(255) NOT NULL DEFAULT 'inherit',
  `row_lg` varchar(255) NOT NULL DEFAULT 'inherit',
  `offset_row_lg` varchar(255) NOT NULL DEFAULT 'inherit',
  `pull_lg` varchar(255) NOT NULL DEFAULT 'none',
  `gutter_lg` varchar(255) NOT NULL DEFAULT 'inherit inherit inherit inherit',
  `cms_workflow` int(11) NOT NULL DEFAULT '0',
  `cms_created` datetime NOT NULL,
  `cms_modified` datetime NOT NULL,
  `cms_modified_by_user` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
