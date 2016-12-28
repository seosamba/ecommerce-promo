<?php

class Widgets_Flipclock_Flipclock extends Widgets_Abstract
{

    private $_website = null;

    protected $_websiteUrl = null;

    protected $_cacheable = false;

    protected $_product = null;

    protected $_productMapper = null;

    protected $_zoomMin = 0.0;

    protected $_zoomMax = 2.0;

    protected $_dayPerSec = 86400;

    protected function _init()
    {
        parent::_init();
        $this->_view = new Zend_View(array(
            'scriptPath' => dirname(__FILE__) . '/views'
        ));
        $this->_view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');
        $this->_website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_view->websiteUrl = $this->_website->getUrl();
    }

    protected function _load()
    {
        $this->_productMapper = Models_Mapper_ProductMapper::getInstance();
        $config = Application_Model_Mappers_ConfigMapper::getInstance()->getConfig();
        $systemLanguage = $config['language'];
        $this->_product = $this->_productMapper->findByPageId($this->_toasterOptions['id']);

        $plugin = Tools_Plugins_Tools::findPluginByName('Promo');
        if ($plugin->getStatus() === Application_Model_Models_Plugin::ENABLED) {
            $productId = $this->_product->getId();
            $table = new Zend_Db_Table('plugin_promo');

            $row = $table->find($productId)->current();
            if (!empty($row)) {
                $row = $row->toArray();
                $from = date(Tools_System_Tools::DATE_MYSQL, strtotime('now'));
                $due = date(Tools_System_Tools::DATE_MYSQL, strtotime($row['promo_due']));
                $dateDiff = strtotime($due) - strtotime($from);
                $this->_view->dateDiff = $dateDiff;

                $dayCounter = 'DailyCounter';
                if ($this->_dayPerSec >= $dateDiff) {
                    $dayCounter = 'HourlyCounter';
                }
                $this->_view->dayCounter = $dayCounter;

                $this->_view->language = $systemLanguage;

                $zoomKey = array_search('zoom', $this->_options);
                if ($zoomKey !== false) {
                    $zoom = $this->_options[$zoomKey + 1];
                    if ((is_numeric($zoom)) && ($zoom > $this->_zoomMin && $zoom < $this->_zoomMax)) {
                        $this->_view->zoom = $zoom;
                    }
                }
                $mColorKey = array_search('metric-color', $this->_options);
                if ($mColorKey !== false) {
                    $metricColor = $this->_options[$mColorKey + 1];
                    $this->_view->metricColor = $metricColor;
                }
                $tColorKey = array_search('time-color', $this->_options);
                if ($tColorKey !== false) {
                    $timeColor = $this->_options[$tColorKey + 1];
                    $this->_view->timeColor = $timeColor;
                }
                $tBackgroundKey = array_search('time-background', $this->_options);
                if ($tBackgroundKey !== false) {
                    $timeBackground = $this->_options[$tBackgroundKey + 1];
                    $this->_view->timeBackground = $timeBackground;
                }

                $this->_view->labelAcl = false;

                $labelKey = array_search('labels', $this->_options);
                if ($labelKey !== false) {
                    $labelAcl = $this->_options[$labelKey + 1];
                    if ($labelAcl == 'disable') {
                        $this->_view->labelAcl = true;
                    }
                }

                $diliverKey = array_search('diliver-color', $this->_options);
                if ($diliverKey !== false) {
                    $diliverColor = $this->_options[$diliverKey + 1];
                    $this->_view->diliverColor = $diliverColor;
                }

                return $this->_view->render('flipClock.phtml');
            }

            return '';
        } else {
            if (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
                throw new Exceptions_SeotoasterWidgetException('Plugin Promo does not exists');
            }

            return false;
        }
    }

}