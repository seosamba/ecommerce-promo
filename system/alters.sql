-- version: 2.2.0
ALTER TABLE `plugin_promo` ADD COLUMN `price_type` enum('percent','unit') COLLATE utf8_unicode_ci DEFAULT 'percent';
ALTER TABLE `plugin_promo` CHANGE `promo_price` `promo_discount` decimal(10,2) NOT NULL;

CREATE TABLE IF NOT EXISTS `plugin_promo_main_config` (
  `id` int(10) unsigned AUTO_INCREMENT,
  `promo_discount` decimal(10,2) NOT NULL,
  `promo_from` date DEFAULT NULL,
  `promo_due` date DEFAULT NULL,
  `price_type` enum('percent','unit') COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- These alters are always the latest and updated version of the database
UPDATE `plugin` SET `version`='2.3.1' WHERE `name`='promo';
SELECT version FROM `plugin` WHERE `name` = 'promo';

