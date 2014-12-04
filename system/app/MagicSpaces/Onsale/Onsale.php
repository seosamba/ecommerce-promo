<?php
class MagicSpaces_Onsale_Onsale extends Tools_MagicSpaces_Abstract {

    /**
     * @var null|Models_Model_Product
     */
    protected $_productData = null;

    protected function _run() {
        return ($this->_checkPromoForProduct($this->_getProductId())) ? $this->_spaceContent : '';
    }

    private function _getProductId() {
        $productMapper =  Models_Mapper_ProductMapper::getInstance();
        if (isset($this->_params[0])) {
            $this->_productData = $productMapper->find($this->_params[0]);
            return $this->_params[0];
        }
        elseif (null !== ($productData = $productMapper->findByPageId($this->_toasterData['id']))) {
            $this->_productData = $productData;
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
        $promoConfig = $table->getAllPromoConfigData($productId);
        if(empty($promoConfig)){
           return false;
        }
        $currentPromo = array_shift($promoConfig);

        // Checked the current date within a range
        if (strtotime($currentPromo['promo_due']) < time()) {
            if ($currentPromo['scope'] === 'global') {
                $promoGlobalConfigTable = new Zend_Db_Table('plugin_promo_main_config');
                $promoGlobalConfigTable->delete('id IS NOT NULL');
            }
            return false;
        }
        elseif (strtotime($currentPromo['promo_from']) > time()) {
            return false;
        }

        // If noZeroPrice in config set to 1 - hidden promo text
        $noZeroPrice = Models_Mapper_ShoppingConfig::getInstance()->getConfigParam('noZeroPrice');
        $currentPrice = $this->_productData->getCurrentPrice();
        if (empty($currentPrice)) {
            $currentPrice = $this->_productData->getPrice();
        }
        $currentPromo['sign'] = 'minus';
        $priceWithPromo = Tools_DiscountTools::applyDiscountData($currentPrice, $currentPromo);
        if ((int) $noZeroPrice === 1 && floatval($priceWithPromo) <= 0) {
            return false;
        }

        return true;
    }
}