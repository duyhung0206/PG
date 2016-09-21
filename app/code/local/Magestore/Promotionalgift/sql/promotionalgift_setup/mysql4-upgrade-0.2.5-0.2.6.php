<?php
$installer = $this;

$installer->startSetup();

$installer->run("

DROP TABLE IF EXISTS {$this->getTable('promotionalgift_banner')};

CREATE TABLE {$this->getTable('promotionalgift_banner')} (
  `banner_id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) default '',
  `cmsblock` varchar(255) default '',
  `status` smallint(6) NOT NULL default '0',
  `website_ids` text default '',
  `customer_group_ids` text default '',
  `priority` int(11) unsigned default '0',
  `conditions_serialized` mediumtext NOT NULL,
  PRIMARY KEY (`banner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();
