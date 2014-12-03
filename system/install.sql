DROP TABLE IF EXISTS `plugin_promo`;
CREATE TABLE IF NOT EXISTS `plugin_promo` (
  `product_id` int(10) unsigned NOT NULL,
  `promo_discount` decimal(10,2) NOT NULL,
  `promo_from` date DEFAULT NULL,
  `promo_due` date DEFAULT NULL,
  `price_type` enum('percent','unit') COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `plugin_promo_main_config`;
CREATE TABLE IF NOT EXISTS `plugin_promo_main_config` (
  `id` int(10) unsigned AUTO_INCREMENT,
  `promo_discount` decimal(10,2) NOT NULL,
  `promo_from` date DEFAULT NULL,
  `promo_due` date DEFAULT NULL,
  `price_type` enum('percent','unit') COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE `plugin` SET `tags`='ecommerce,merchandising' WHERE `name` = 'promo';

ALTER TABLE `plugin_promo`
  ADD CONSTRAINT `plugin_promo_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shopping_product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

INSERT INTO `observers_queue` (`observable`, `observer`) VALUES ('Models_Model_Product', 'PromoObserver');