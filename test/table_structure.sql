/*
SQLyog Ultimate v8.32 
MySQL - 5.5.59 : Database - www_haolietou_com
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`www_haolietou_com` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `www_haolietou_com`;

/*Table structure for table `qs_badword` */

DROP TABLE IF EXISTS `qs_badword`;

CREATE TABLE `qs_badword` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `badword` varchar(100) NOT NULL,
  `replacement` varchar(100) DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  `is_from` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0-文件导入，1-手动操作',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16789 DEFAULT CHARSET=utf8;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
