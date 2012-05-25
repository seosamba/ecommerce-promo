<?php
/**
 * PromoObserver
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class PromoObserver implements Interfaces_Observer {

    private $_dbTable;

    function __construct()
    {
        $this->_dbTable = new Promo_DbTables_PromoDbTable();
    }


    public function notify($object)
    {
        $row = $this->_dbTable->find($object->getId())->current();
        if ($row !== null) {
	        $now = time();
            if (strtotime($row->promo_due) < $now){
                $row->delete();
            } elseif (strtotime($row->promo_from) < $now) {
                $object->setCurrentPrice($row->promo_price);
            }
        }
    }

}
