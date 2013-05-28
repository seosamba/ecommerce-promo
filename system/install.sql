DROP TABLE IF EXISTS `plugin_promo`;
CREATE TABLE IF NOT EXISTS `plugin_promo` (
  `product_id` int(10) unsigned NOT NULL,
  `promo_price` decimal(10,2) NOT NULL,
  `promo_from` date DEFAULT NULL,
  `promo_due` date DEFAULT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE `plugin` SET `tags`='ecommerce,merchandising' WHERE `name` = 'promo';

ALTER TABLE `plugin_promo`
  ADD CONSTRAINT `plugin_promo_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shopping_product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

INSERT INTO `observers_queue` (`observable`, `observer`) VALUES ('Models_Model_Product', 'PromoObserver');