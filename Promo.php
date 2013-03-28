<?php
/**
 * Promo price plugin for SEOTOASTER 2.0 eCommerce part
 */

class Promo extends Tools_Plugins_Abstract {

	const DISPLAY_NAME = 'On sale';

	protected $_dependsOn = array(
		'shopping'
	);

	protected function _init() {
		$missedPlugins = array_diff($this->_dependsOn, Tools_Plugins_Tools::getEnabledPlugins(true));

		if (!empty($missedPlugins)) {
			throw new Exceptions_SeotoasterPluginException('Required plugins should be enabled: <b>' . implode(',', $missedPlugins) . '</b>');
		}

		$this->_jsonHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('json');

		$this->_view->setScriptPath(__DIR__ . '/system/views');
	}

	public function run($requestedParams = array()) {
		if (!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
			$this->_response->clearAllHeaders()->setBody(null);
			$this->_response->setHttpResponseCode(Api_Service_Abstract::REST_STATUS_FORBIDDEN)
					->sendResponse();
			exit;
		}
		$dispatchersResult = parent::run($requestedParams);
		if ($dispatchersResult) {
			return $dispatchersResult;
		}
	}

	public function tabAction() {
		if (isset($this->_requestedParams['productId'])) {
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
			$promoFrom = filter_var($this->_request->getParam('promo-from'), FILTER_SANITIZE_STRING);
			$promoDue = filter_var($this->_request->getParam('promo-due'), FILTER_SANITIZE_STRING);
			$row->promo_price = filter_var($this->_request->getParam('promo-price'), FILTER_SANITIZE_STRING);
			$row->promo_from = date(Tools_System_Tools::DATE_MYSQL, strtotime($promoFrom));
			$row->promo_due = date(Tools_System_Tools::DATE_MYSQL, strtotime($promoDue));
			try {
				$result = $row->save();
				if ($result) {
					Zend_Controller_Action_HelperBroker::getStaticHelper('cache')->clean(false, false, array('prodid_' . $row->product_id));
				}
			} catch (Exception $e) {
				echo $e->getMessage();
			}
			$this->_jsonHelper->direct(array(
				'result'   => $this->_translator->translate((boolean)$result ? 'Done' : 'Error'),
				'callback' => null
			));

			return;
		}

		echo $this->_view->render('tab.phtml');
	}

	protected function _makeOptionPrice() {
		$table = new Promo_DbTables_PromoDbTable();
		if (($row = $table->fetchRow(array('product_id = ?' => $this->_options[1]))) !== null) {
			if (strtotime($row->promo_due) < time()) {
				$row->delete();
				return null;
			}
			return $row->promo_price;
		}
		return null;
	}

}
