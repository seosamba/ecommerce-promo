<?php

/**
 * PromoObserver
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class PromoObserver implements Interfaces_Observer
{

    private $_dbTable;

    private static $_configParams = null;

    public function __construct()
    {
        if (self::$_configParams === null) {
            self::$_configParams = Models_Mapper_ShoppingConfig::getInstance()->getConfigParams();
        }
        $this->_dbTable = new Promo_DbTables_PromoDbTable();
    }


    /**
     * @param $object Models_Model_Product
     */
    public function notify($object)
    {
        $prodId = $object->getId();
        if($prodId){
            $promoConfig = $this->_dbTable->getAllPromoConfigData($prodId);
            if(!empty($promoConfig)){
                $currentPromo = array_shift($promoConfig);
                $now = time();
                if (strtotime($currentPromo['promo_due']) < $now) {
                    if ($currentPromo['scope'] === 'global') {
                        $promoGlobalConfigTable = new Zend_Db_Table('plugin_promo_main_config');
                        $promoGlobalConfigTable->delete('id IS NOT NULL');
                    }
                } elseif (strtotime($currentPromo['promo_from']) < $now) {
                    $currentPrice = $object->getCurrentPrice();
                    if (empty($currentPrice)) {
                        $currentPrice = $object->getPrice();
                    }

                    $currentPromo['sign'] = 'minus';
                    $priceWithPromo = Tools_DiscountTools::applyDiscountData($currentPrice, $currentPromo);
                    $object->setCurrentPrice($priceWithPromo);

                    $productDiscounts = $object->getProductDiscounts();
                    array_push(
                        $productDiscounts,
                        array(
                            'name' => 'promo',
                            'discount' => $currentPromo['discount'],
                            'type' => $currentPromo['type'],
                            'sign' => $currentPromo['sign']
                        )
                    );
                    $object->setProductDiscounts($productDiscounts);


                    $salePrice = number_format(
                        Tools_ShoppingCart::getInstance()->calculateProductPrice(
                            $object,
                            $object->getDefaultOptions()
                        ),
                        2,
                        '.',
                        ''
                    );
                    $object->addExtraProperty(
                        array(
                            'g:sale_price'                => $salePrice . ' ' . self::$_configParams['currency'],
                            'g:sale_price_effective_date' => date(DATE_ATOM, strtotime($currentPromo['promo_from'])) . '/' . date(
                                    DATE_ATOM,
                                    strtotime($currentPromo['promo_due'])
                                )
                        )
                    );
                }
            }
        }
    }

}
