<?php
class MagicSpaces_Onsale_Onsale extends Tools_MagicSpaces_Abstract {

    protected function _run() {
        return ($this->_checkPromoForProduct($this->_getProductId())) ? $this->_spaceContent : '';
    }

    private function _getProductId() {
        if (isset($this->_params[0])) {
            return $this->_params[0];
        }
        elseif (null !== ($productData = Models_Mapper_ProductMapper::getInstance()->findByPageId($this->_toasterData['id']))) {
            return $productData->getId();
        }

        return null;
    }

    private function _checkPromoForProduct($productId) {
        if (null === $productId) {
            return false;
        }

        // Get promo-data to product
        $table = new Promo_DbTables_PromoDbTable();
        if (null === ($row = $table->fetchRow(array('product_id = ?' => $productId)))) {
            return false;
        }

        // Checked the current date within a range
        if (strtotime($row->promo_due) < time()) {
            $row->delete();
            return false;
        }
        elseif (strtotime($row->promo_from) > time()) {
            return false;
        }

        // If noZeroPrice in config set to 1 - hidden promo text
        $noZeroPrice = Models_Mapper_ShoppingConfig::getInstance()->getConfigParam('noZeroPrice');
        if ((int) $noZeroPrice === 1 && floatval($row->promo_price) == 0) {
            return false;
        }

        return true;
    }
}