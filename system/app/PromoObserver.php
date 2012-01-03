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
            if (strtotime($row->promo_due) < time()){
                $row->delete();
            } else {
                $object->setCurrentPrice($row->promo_price);
            }
        }
    }

}
