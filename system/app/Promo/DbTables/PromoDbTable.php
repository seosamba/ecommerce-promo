<?php
/**
 * Promo_Dbtables_PromoDbTable
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Promo_DbTables_PromoDbTable extends Zend_Db_Table_Abstract
{
    protected $_name = 'plugin_promo';

    public function getAllPromoConfigData($productId)
    {
        $where = $this->getAdapter()->quoteInto('product_id = ?', $productId);
        $selectLocalConfig = $this->select()->setIntegrityCheck(false)->from(
            array('promo' => 'plugin_promo'),
            array(
                'discount' => 'promo_discount',
                'promo_from',
                'promo_due',
                'type' => 'price_type',
                new Zend_Db_Expr('"local" as scope')
            )
        )->where($where);
        $selectGlobalConfig = $this->select()->setIntegrityCheck(false)->from(
            array('promo' => 'plugin_promo_main_config'),
            array(
                'discount' => 'promo_discount',
                'promo_from',
                'promo_due',
                'type' => 'price_type',
                new Zend_Db_Expr('"global" as scope')
            )
        );
        $select = $this->select()->union(array($selectLocalConfig, $selectGlobalConfig));
        return $this->getAdapter()->fetchAll($select);
    }
}
