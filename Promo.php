<?php
/**
 * Promo price plugin for SEOTOASTER 2.0 eCommerce part
 */

class Promo extends Tools_Plugins_Abstract {

    protected $_dependsOn = array(
        'shopping'
    );

	protected function _init() {

        $enabledPlugins = array();
        foreach (Tools_Plugins_Tools::getEnabledPlugins() as $plugin) {
            array_push($enabledPlugins, $plugin->getName());
        }
        $missedPlugins = array_diff($this->_dependsOn, $enabledPlugins);
        if (!empty($missedPlugins)) {
            throw new Exceptions_SeotoasterPluginException('Required plugins should be enabled: <b>'.implode(',', $missedPlugins).'</b>');
        }
        $this->_jsonHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('json');

        $this->_view->setUseStreamWrapper(true)->setScriptPath(__DIR__.'/system/views');
    }

	public function run($requestedParams = array()) {
        if (!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)){
//            throw new Exceptions_SeotoasterPluginException('<script>top.location.href="'.$this->_websiteUrl.'"</script>');
//            $this->_redirector->gotoUrl($this->_websiteUrl);
            return 'Forbidden';
        }
		$dispatchersResult = parent::run($requestedParams);
		if($dispatchersResult) {
			return $dispatchersResult;
		}
	}

    public function tabAction() {
        if (isset($this->_requestedParams['productId'])){
            $pid = $this->_requestedParams['productId'];

            $table = new Zend_Db_Table('plugin_promo');
            $row = $table->find($pid)->current();
            if ($row === null) {
                $row = $table->createRow(array(
                    'product_id' => $pid
                ));
            }
            $this->_view->data = $row->toArray();
        }


        if ($this->_request->isPost()) {
            $row->promo_price   = filter_var($this->_request->getParam('promo-price'), FILTER_SANITIZE_STRING);
            $row->promo_due     = filter_var($this->_request->getParam('promo-due'), FILTER_SANITIZE_STRING);
            try {
                $result = $row->save();
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            $this->_jsonHelper->direct(array(
                'result'    => $this->_translator->translate( (boolean)$result ? 'Done' : 'Error' ) ,
                'callback'  => null
            ));

            return;
        }

        echo $this->_view->render('tab.phtml');
    }

    protected function _makeOptionPrice(){
        $table = new Promo_DbTables_PromoDbTable();
        if ( ($row = $table->fetchRow(array('product_id = ?' => $this->_options[1]))) !== null ) {
            if (strtotime($row->promo_due) < time()){
                $row->delete();
                return null;
            }
            return $row->promo_price;
        }
        return null;
    }

}
