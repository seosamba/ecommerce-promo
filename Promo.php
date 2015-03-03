<?php
/**
 * Promo price plugin for SEOTOASTER 2.0 eCommerce part
 */

class Promo extends Tools_Plugins_Abstract {

	const DISPLAY_NAME = 'On sale';

	const PROMO_SECURE_TOKEN = 'PromoToken';

    protected $_dependsOn = array(
		'shopping'
	);

	/**
	 * @var Zend_Controller_Action_Helper_Json
	 */
	protected $_jsonHelper;

	protected function _init() {
		$missedPlugins = array_diff($this->_dependsOn, Tools_Plugins_Tools::getEnabledPlugins(true));

		if (!empty($missedPlugins)) {
			throw new Exceptions_SeotoasterPluginException('Required plugins should be enabled: <b>' . implode(',', $missedPlugins) . '</b>');
		}

		$this->_jsonHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('json');

		$this->_view->setScriptPath(__DIR__ . '/system/views');
	}

	public function tabAction() {
		if (!Tools_Security_Acl::isAllowed(Shopping::RESOURCE_STORE_MANAGEMENT)) {
			throw new Exceptions_SeotoasterPluginException('Forbidden');
		}

		$pid = $this->_request->getParam('productId');
		if ($pid) {
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
            $secureToken = $this->_request->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
            $tokenValid = Tools_System_Tools::validateToken($secureToken, self::PROMO_SECURE_TOKEN);
            if (!$tokenValid) {
                $this->_responseHelper->fail('');
            }
            $promoFrom = filter_var($this->_request->getParam('promo-from'), FILTER_SANITIZE_STRING);
			$promoDue = filter_var($this->_request->getParam('promo-due'), FILTER_SANITIZE_STRING);
            $dateValidator = new Zend_Validate_Date(array('format' => 'd-M-Y', 'locale' => 'en'));
            if (!$dateValidator->isValid($promoDue) || !$dateValidator->isValid($promoFrom)) {
                $this->_jsonHelper->direct(array(
                    'result'   => $this->_translator->translate('Wrong date format'),
                    'callback' => null
                ));
            }

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

	public function merchandisingAction() {
		if (!Tools_Security_Acl::isAllowed(Shopping::RESOURCE_STORE_MANAGEMENT)) {
			throw new Exceptions_SeotoasterPluginException('Forbidden');
		}

		$cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
		if ($this->_request->isPost()) {
            $secureToken = $this->_request->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
            $tokenValid = Tools_System_Tools::validateToken($secureToken, self::PROMO_SECURE_TOKEN);
            if (!$tokenValid) {
                $this->_responseHelper->fail('');
            }
            $promoTable = new Zend_Db_Table('plugin_promo');
			if ($this->_request->has('dismiss')) {
				try {
					$result = $promoTable->delete('product_id IS NOT NULL');
					$this->_responseHelper->success($this->_translator->translate('Updated') .' '. $result . ' products');
				} catch (Exception $e) {
					$this->_responseHelper->fail('Error');
				}
			} else {
				try {
					$discount = filter_var($this->_request->getParam('promo-price'), FILTER_VALIDATE_FLOAT);
					$promoFrom = filter_var($this->_request->getParam('promo-from'), FILTER_SANITIZE_STRING);
					$promoDue = filter_var($this->_request->getParam('promo-due'), FILTER_SANITIZE_STRING);

					if (is_numeric($discount)) {
						$discount = floatval($discount);
						if ($discount > 100 || $discount < 0) {
							$this->_responseHelper->fail($this->_translator->translate('Sales discount should be between 0 and 100 percents'));
						}
					} else {
						$this->_responseHelper->fail($this->_translator->translate('Sale discount should be a number'));
					}

					$dateValidator = new Zend_Validate_Date(array('format' => 'd-M-Y', 'locale' => 'en'));
					if ($dateValidator->isValid($promoFrom)) {
						$promoFrom = date(Tools_System_Tools::DATE_MYSQL, strtotime($promoFrom));
					} else {
						$this->_responseHelper->fail($this->_translator->translate('Wrong date format'));
					}
					if ($dateValidator->isValid($promoDue)) {
						$promoDue = date(Tools_System_Tools::DATE_MYSQL, strtotime($promoDue));
					} else {
						$this->_responseHelper->fail($this->_translator->translate('Wrong date format'));
					}

					$sql = 'INSERT INTO `plugin_promo` SELECT id as product_id, ROUND(price-price*%1$d/100, 2) as promo_price, \'%2$s\' as promo_from, \'%3$s\' as promo_due FROM `shopping_product` ON DUPLICATE KEY UPDATE promo_price = ROUND(price-price*%1$d/100, 2), promo_from = \'%2$s\', promo_due = \'%3$s\'';
					$sql = sprintf($sql, $discount, $promoFrom, $promoDue);
					$promoTable->getAdapter()->query($sql);
					$cacheHelper->clean(false, false, array('product_price'));
					$this->_responseHelper->success($this->_translator->translate('All products were updated'));
				} catch (Exception $e) {
					$this->_responseHelper->fail($e->getMessage());
				}
			}
			$this->_responseHelper->response('Bad request', true, 400);
		}
		echo $this->_view->render('merchandisingTab.phtml');
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
