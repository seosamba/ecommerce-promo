<?php
/**
 * Promo price plugin for SEOTOASTER 2.0 eCommerce part
 */

class Promo extends Tools_Plugins_Abstract {

	const DISPLAY_NAME = 'On sale';

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
        $promoGlobalConfigTable = new Zend_Db_Table('plugin_promo_main_config');
        $globalConfigData = $promoGlobalConfigTable->fetchRow();
		if ($this->_request->isPost()) {
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

                    $data = array(
                       'promo_discount' => $discount,
                       'promo_from' => $promoFrom,
                       'promo_due'  => $promoDue,
                       'price_type' => 'percent'
                    );
                    if (!empty($globalConfigData)) {
                        $globalConfigData = $globalConfigData->toArray();
                        $where = $promoGlobalConfigTable->getAdapter()->quoteInto('id = ?', $globalConfigData['id']);
                        $promoGlobalConfigTable->update($data, $where);
                    } else {
                        $promoGlobalConfigTable->insert($data);
                    }
                    $promoTable->delete('product_id IS NOT NULL');
                    $cacheHelper->clean(false, false, array('product_price'));
					$this->_responseHelper->success($this->_translator->translate('All products were updated'));
				} catch (Exception $e) {
					$this->_responseHelper->fail($e->getMessage());
				}
			}
			$this->_responseHelper->response('Bad request', true, 400);
		} else {
            if (!empty($globalConfigData)) {
                $globalConfigData = $globalConfigData->toArray();
                $this->_view->discountPrice = $globalConfigData['promo_discount'];
                $this->_view->promoFrom =  $globalConfigData['promo_from'];
                $this->_view->promoDue = $globalConfigData['promo_due'];
            }
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
