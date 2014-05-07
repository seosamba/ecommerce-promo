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
        $row = $this->_dbTable->find($object->getId())->current();
        if ($row !== null) {
            $now = time();
            if (strtotime($row->promo_due) < $now) {
                $row->delete();
            } elseif (strtotime($row->promo_from) < $now) {
                $object->setCurrentPrice($row->promo_price);

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
                        'g:sale_price_effective_date' => date(DATE_ATOM, strtotime($row->promo_from)) . '/' . date(
                                DATE_ATOM,
                                strtotime($row->promo_due)
                            )
                    )
                );
            }
        }
    }

}
