DELETE FROM `observers_queue` WHERE `observable` = 'Models_Model_Product' AND `observer` = 'PromoObserver';

DROP TABLE IF EXISTS `plugin_promo`;