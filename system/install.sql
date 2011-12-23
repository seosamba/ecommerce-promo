CREATE TABLE IF NOT EXISTS `plugin_promo` (
  `product_id` int(10) unsigned NOT NULL,
  `promo_price` decimal(10,2) NOT NULL,
  `promo_due` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `plugin_promo`
  ADD CONSTRAINT `plugin_promo_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shopping_product` (`id`);

INSERT INTO `observers_queue` (`namespace`, `observer`) VALUES ('Models_Model_Product', 'PromoObserver');