CREATE TABLE IF NOT EXISTS `PREFIX_seller` (
	`id_seller` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`id_customer` int(10) unsigned NOT NULL,
	`id_shop` int(10) unsigned NOT NULL,
  `id_lang` int(10) unsigned NOT NULL,
	`name` varchar(128) character set utf8 NOT NULL,
	`email` varchar(128) NOT NULL,
	`link_rewrite` varchar(128) character set utf8 NOT NULL,
	`shop` varchar(128) character set utf8 NOT NULL,
	`cif` varchar(32) DEFAULT NULL,
	`phone` varchar(32) DEFAULT NULL,
	`fax` varchar(32) DEFAULT NULL,
	`address` text,  
	`country` varchar(75) DEFAULT NULL,
	`state` varchar(75) DEFAULT NULL,
	`city` varchar(75) DEFAULT NULL,
	`postcode` varchar(12) DEFAULT NULL,
	`description` text,
	`active` int(2) unsigned NOT NULL,
	`date_add` datetime NOT NULL,
	`date_upd` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY  (`id_seller`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_product` (
	`id_seller_product` INT(10) UNSIGNED NOT NULL,
	`id_product` INT(10) UNSIGNED NOT NULL,
	`id_product_copy` INT(10) DEFAULT 0 NULL,
	PRIMARY KEY (`id_seller_product`, `id_product`),
	INDEX `id_product` (`id_product`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_incidence` (
	`id_seller_incidence` int(10) NOT NULL AUTO_INCREMENT,
	`reference` VARCHAR(8) NOT NULL,
	`id_order` int(10) NOT NULL,
	`id_product` int(10) NOT NULL,
	`id_customer` int(10) NOT NULL,
	`id_seller` int(10) NOT NULL,
	`id_shop` int(10) NOT NULL,
  `active` int(2) unsigned NOT NULL,
	`date_add` datetime NOT NULL,
	`date_upd` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `en_attente` int(10) NOT NULL DEFAULT 0,
  `id_cart` int(10) NOT NULL DEFAULT 0,
	PRIMARY KEY  (`id_seller_incidence`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_incidence_message` (
	`id_seller_incidence_message` int(10) NOT NULL AUTO_INCREMENT,
	`id_seller_incidence` int(10) NOT NULL,
	`id_customer` int(10) NOT NULL,
	`id_seller` int(10) NOT NULL,
	`description` text,
	`readed` int(2) NOT NULL,
  `active` int(2) unsigned NOT NULL,
	`date_add` datetime NOT NULL,
	`date_upd` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `readed_cust` int(10) NOT NULL DEFAULT 0,
	PRIMARY KEY  (`id_seller_incidence_message`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_payment` (
	`id_seller_payment` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`id_seller` INT(10) UNSIGNED NOT NULL,
	`payment` varchar(128) character set utf8 NOT NULL,
	`account` varchar(128) character set utf8 NOT NULL,
	PRIMARY KEY (`id_seller_payment`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_commision` (
  `id_seller_commision` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_seller` int(10) NOT NULL,
  `id_shop` int(10) NOT NULL,
  `commision` int(10) NOT NULL,
   PRIMARY KEY  (`id_seller_commision`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_commision_history` (
  `id_seller_commision_history` INT(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_order` int(10) NOT NULL,
  `id_product` int(10) NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `id_seller` int(10) NOT NULL,
  `id_shop` int(10) NOT NULL,
  `price` float(10) NOT NULL,
  `quantity` int(10) NOT NULL,
  `commision` float(10) NOT NULL,
  `id_seller_commision_history_state` int(10) NOT NULL,
  `date_add` DATETIME NOT NULL,
  `date_upd` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `paid` float(10) NOT NULL DEFAULT 0,
   PRIMARY KEY  (`id_seller_commision_history`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_commision_history_state` (
	`id_seller_commision_history_state` INT(10) NOT NULL AUTO_INCREMENT,
  `reference` varchar(32) character set utf8 NOT NULL,
	`active` int(2) NOT NULL,
  `color` varchar(20) character set utf8 NOT NULL,
	PRIMARY KEY (`id_seller_commision_history_state`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_commision_history_state_lang` (
	`id_seller_commision_history_state` INT(10) NOT NULL,
	`id_lang` INT(10) NOT NULL,
	`name` VARCHAR(64) NOT NULL,
	PRIMARY KEY (`id_seller_commision_history_state`, `id_lang`),
	KEY `id_lang` (`id_lang`),
    KEY `name` (`name`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_favorite` (
	`id_customer` INT(10) UNSIGNED NOT NULL,
	`id_seller` INT(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`id_customer`, `id_seller`),
	INDEX `id_seller` (`id_seller`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_category` (
	`id_seller_category` INT(10) NOT NULL AUTO_INCREMENT,
	`id_category` INT(10) UNSIGNED NOT NULL,
  `id_shop` INT(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`id_seller_category`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_carrier` (
        `id_seller_carrier` int( 10 ) NOT NULL AUTO_INCREMENT ,
        `id_seller` int( 10 ) NOT NULL ,
        `id_carrier` int( 10 ) NOT NULL ,
        PRIMARY KEY ( `id_seller_carrier` )
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_email` (
	`id_seller_email` INT(10) NOT NULL AUTO_INCREMENT,
  `reference` varchar(45) character set utf8 NOT NULL,
	PRIMARY KEY (`id_seller_email`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_email_lang` (
	`id_seller_email` INT(10) NOT NULL,
	`id_lang` INT(10) NOT NULL,
	`subject` VARCHAR(155) NOT NULL,
  `content` text NOT NULL,
  `description` text NOT NULL,
	PRIMARY KEY (`id_seller_email`, `id_lang`),
	KEY `id_lang` (`id_lang`),
    KEY `subject` (`subject`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_comment` (
  `id_seller_comment` int(10) unsigned NOT NULL auto_increment,
  `id_seller` int(10) unsigned NOT NULL,
  `id_customer` int(10) unsigned NOT NULL,
  `id_guest` int(10) unsigned NULL,
  `id_order` int(10) unsigned NOT NULL,
  `title` varchar(64) NULL,
  `content` text NOT NULL,
  `customer_name` varchar(64) NULL,
  `grade` float unsigned NOT NULL,
  `validate` tinyint(1) NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `date_add` datetime NOT NULL,
  PRIMARY KEY (`id_seller_comment`),
  KEY `id_seller` (`id_seller`),
  KEY `id_customer` (`id_customer`),
  KEY `id_guest` (`id_guest`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_comment_criterion` (
  `id_seller_comment_criterion` int(10) unsigned NOT NULL auto_increment,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id_seller_comment_criterion`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_comment_criterion_lang` (
  `id_seller_comment_criterion` INT(11) UNSIGNED NOT NULL ,
  `id_lang` INT(11) UNSIGNED NOT NULL ,
  `name` VARCHAR(64) NOT NULL ,
  PRIMARY KEY ( `id_seller_comment_criterion` , `id_lang` )
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_comment_criterion_seller` (
  `id_seller` int(10) UNSIGNED NOT NULL,
  `id_seller_comment_criterion` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_seller`,`id_seller_comment_criterion`),
  KEY `id_seller_comment_criterion` (`id_seller_comment_criterion`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_comment_grade` (
  `id_seller_comment` int(10) unsigned NOT NULL,
  `id_seller_comment_criterion` int(10) unsigned NOT NULL,
  `grade` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_seller_comment`, `id_seller_comment_criterion`),
  KEY `id_seller_comment_criterion` (`id_seller_comment_criterion`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `PREFIX_seller_product_comment` (
  `id_seller_product_comment` int(10) unsigned NOT NULL auto_increment,
  `id_product` int(10) unsigned NOT NULL,
  `id_customer` int(10) unsigned NOT NULL,
  `id_guest` int(10) unsigned NULL,
  `title` varchar(64) NULL,
  `content` text NOT NULL,
  `customer_name` varchar(64) NULL,
  `grade` float unsigned NOT NULL,
  `validate` tinyint(1) NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `date_add` datetime NOT NULL,
  PRIMARY KEY (`id_seller_product_comment`),
  KEY `id_product` (`id_product`),
  KEY `id_customer` (`id_customer`),
  KEY `id_guest` (`id_guest`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_product_comment_criterion` (
  `id_seller_product_comment_criterion` int(10) unsigned NOT NULL auto_increment,
  `id_seller_product_comment_criterion_type` tinyint(1) NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id_seller_product_comment_criterion`)
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_product_comment_criterion_product` (
  `id_product` int(10) unsigned NOT NULL,
  `id_seller_product_comment_criterion` int(10) unsigned NOT NULL,
  PRIMARY KEY(`id_product`, `id_seller_product_comment_criterion`),
  KEY `id_seller_product_comment_criterion` (`id_seller_product_comment_criterion`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_product_comment_criterion_lang` (
  `id_seller_product_comment_criterion` INT(11) UNSIGNED NOT NULL ,
  `id_lang` INT(11) UNSIGNED NOT NULL ,
  `name` VARCHAR(64) NOT NULL ,
  PRIMARY KEY ( `id_seller_product_comment_criterion` , `id_lang` )
) ENGINE=ENGINE_TYPE  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_product_comment_criterion_category` (
  `id_seller_product_comment_criterion` int(10) unsigned NOT NULL,
  `id_category` int(10) unsigned NOT NULL,
  PRIMARY KEY(`id_seller_product_comment_criterion`, `id_category`),
  KEY `id_category` (`id_category`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_product_comment_grade` (
  `id_seller_product_comment` int(10) unsigned NOT NULL,
  `id_seller_product_comment_criterion` int(10) unsigned NOT NULL,
  `grade` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_seller_product_comment`, `id_seller_product_comment_criterion`),
  KEY `id_seller_product_comment_criterion` (`id_seller_product_comment_criterion`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_product_comment_usefulness` (
  `id_seller_product_comment` int(10) unsigned NOT NULL,
  `id_customer` int(10) unsigned NOT NULL,
  `usefulness` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id_seller_product_comment`, `id_customer`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_product_comment_report` (
  `id_seller_product_comment` int(10) unsigned NOT NULL,
  `id_customer` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_seller_product_comment`, `id_customer`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seller_holiday` (
            `id_seller_holiday` int( 10 ) NOT NULL AUTO_INCREMENT ,
            `id_seller` int( 10 ) NOT NULL ,
            `from` DATE NULL DEFAULT NULL ,
            `to` DATE NULL DEFAULT NULL ,
            PRIMARY KEY ( `id_seller_holiday` )
            ) ENGINE = InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `PREFIX_specific_price` ADD `reduction_mode` ENUM('promo','solde') NOT NULL AFTER `reduction_type`;
ALTER TABLE `PREFIX_orders`  ADD `visited_seller` INT NOT NULL DEFAULT '0';
ALTER TABLE `PREFIX_orders`  ADD `id_seller` INT NOT NULL DEFAULT '0';
ALTER TABLE `PREFIX_orders`  ADD `visited` INT NOT NULL DEFAULT '0';
ALTER TABLE `PREFIX_orders`  ADD `slip_amount` FLOAT NOT NULL DEFAULT '0';
ALTER TABLE `PREFIX_orders`  ADD `slip_confirmed` INT NOT NULL DEFAULT '0';
ALTER TABLE `PREFIX_orders`  ADD `slip_motif` INT NOT NULL DEFAULT '0';

ALTER TABLE `PREFIX_product` ADD `comments` VARCHAR(70) NULL;

ALTER TABLE `PREFIX_order_state` AUTO_INCREMENT=15;