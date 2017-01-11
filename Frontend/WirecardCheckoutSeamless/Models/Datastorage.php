<?php
/**
 * WirecardCheckoutSeamless Datastorage link
 *
 * Shop System Plugins - Terms of use
 *
 * This terms of use regulates warranty and liability between
 * Wirecard Central Eastern Europe (subsequently referred to as WDCEE)
 * and it's contractual partners (subsequently referred to as customer or customers)
 * which are related to the use of plugins provided by WDCEE.
 * The Plugin is provided by WDCEE free of charge for it's customers and
 * must be used for the purpose of WDCEE's payment platform integration only.
 * It explicitly is not part of the general contract between WDCEE and it's customer.
 * The plugin has successfully been tested under specific circumstances
 * which are defined as the shopsystem's standard configuration (vendor's delivery state).
 * The Customer is responsible for testing the plugin's functionality
 * before putting it into production enviroment.
 * The customer uses the plugin at own risk. WDCEE does not guarantee it's full
 * functionality neither does WDCEE assume liability for any disadvantage related
 * to the use of this plugin. By installing the plugin into the shopsystem the customer
 * agrees to the terms of use. Please do not use this plugin if you do not agree to the terms of use!
 */

/**
 * class for DataStorage initiation
 */
class Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Datastorage
{
    /**
     * Singleton pattern - only one instance of ourselve
     *
     * @var Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Config
     */
    private static $instance;

    /** @var WirecardCEE_QMore_DataStorage_Response_Initiation $response */
    private $response = null;

    private $readResponse = null;

    /**
     * Private constructor
     * Call of singleton method is required
     */
    private function __construct()
    {
    }

    /**
     * Returns instance of Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Config
     *
     * @return Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Datastorage
     */
    public static function getSingleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Datastorage();
        }
        return self::$instance;
    }

    /**
     * Initiate WirecardCheckoutSeamless datastorage
     * Returns true if datastorage could be initiate successfully
     *
     * @return bool
     * @throws Exception
     */
    public function initiate()
    {
        $router = Enlight_Application::Instance()->Front()->Router();
        $returnUrl = $router->assemble(
            array(
                 'action' => 'dsStoreReturn',
                 'sUseSSL' => true
            )
        );

        /** @var Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Resources $seamless */
        $seamless = Shopware()->WirecardCheckoutSeamless();
        /** @var Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Config $config */
        $config = $seamless->Config();

        $dataStorageInit = new WirecardCEE_QMore_DataStorageClient(Array(
            'CUSTOMER_ID' => $config->CUSTOMERID,
            'SHOP_ID'     => $config->SHOPID,
            'LANGUAGE'    => Shopware()->Locale()->getLanguage(),
            'SECRET'      => $config->SECRET
        ));
        $dataStorageInit->setReturnUrl($returnUrl);
        $dataStorageInit->setOrderIdent(Shopware()->SessionID());

        if ($config->PCI3_DSS_SAQ_A_ENABLE)
        {
            $dataStorageInit->setJavascriptScriptVersion('pci3');

            if (strlen(trim($config->IFRAME_CSS_URL)))
                $dataStorageInit->setIframeCssUrl(trim($config->IFRAME_CSS_URL));

            $dataStorageInit->setCreditCardShowCardholderNameField($config->CREDITCARD_SHOWCARDHOLDER);
            $dataStorageInit->setCreditCardShowCvcField($config->CREDITCARD_SHOWCVC);
            $dataStorageInit->setCreditCardShowIssueDateField($config->CREDITCARD_SHOWISSUEDATE);
            $dataStorageInit->setCreditCardShowIssueNumberField($config->CREDITCARD_SHOWISSUENUMBER);
        }

        try {
            $this->response = $dataStorageInit->initiate();
            if ($this->response->getStatus() == WirecardCEE_QMore_DataStorage_Response_Initiation::STATE_SUCCESS) {
                $dataStorageRead = new WirecardCEE_QMore_DataStorage_Request_Read(Array(
                    'CUSTOMER_ID' => $config->CUSTOMERID,
                    'SHOP_ID'     => $config->SHOPID,
                    'SECRET'      => $config->SECRET,
                    'LANGUAGE'    => Shopware()->Locale()->getLanguage(),
                ));

                $this->readResponse = $dataStorageRead->read($this->getStorageId());
                return true;
            }
            else {
            	$dsErrors = $this->response->getErrors();
                $msg = array();
        		foreach($dsErrors AS $error)
		        {
                    if (strcmp('undefined', strtolower($error))) {
                        Shopware()->WirecardCheckoutSeamless()->wirecard_action = 'undefined';
                    }
                    $msg[] = 'DataStorage: ' . $error->getConsumerMessage();
        		}
                Shopware()->WirecardCheckoutSeamless()->wirecard_action = 'error_init';
                Shopware()->WirecardCheckoutSeamless()->wirecard_message = implode(', ', $msg);
                Shopware()->WirecardCheckoutSeamless()->Log()->Err(__METHOD__ . ':' . implode(', ', $msg));
            }
        } catch (Exception $e) {
            if ($e instanceof WirecardCEE_Stdlib_Exception_ExceptionInterface) {
                Shopware()->WirecardCheckoutSeamless()->Log()->Err(__METHOD__ . ':' . $e->getMessage());
                Shopware()->WirecardCheckoutSeamless()->wirecard_action = 'error_init';
                Shopware()->WirecardCheckoutSeamless()->wirecard_message = $e->getMessage();
            } else {
                throw $e;
        	}
        }
        return false;
    }

    /**
     * @return WirecardCEE_QMore_DataStorage_Response_Read
     * @throws Enlight_Exception
     * @throws Exception
     */
    public function read()
    {
        /** @var Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Resources $seamless */
        $seamless = Shopware()->WirecardCheckoutSeamless();
        /** @var Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Config $config */
        $config = $seamless->Config();

        $dataStorageRead = new WirecardCEE_QMore_DataStorage_Request_Read(Array(
            'CUSTOMER_ID' => $config->CUSTOMERID,
            'SHOP_ID'     => $config->SHOPID,
            'SECRET'      => $config->SECRET,
            'LANGUAGE'    => Shopware()->Locale()->getLanguage(),
        ));

        try
        {
            return $dataStorageRead->read(Shopware()->WirecardCheckoutSeamless()->storageId);
        } catch (Exception $e) {
            if ($e instanceof WirecardCEE_Stdlib_Exception_ExceptionInterface) {
            Shopware()->WirecardCheckoutSeamless()->Log()->Err('DataStorage Read: ' . $e->getMessage());
            throw new Enlight_Exception('DataStorage: ' . $e->getMessage());
            } else {
                throw $e;
        	}

    	}
    }

    /**
     * Returns storage id
     *
     * @return string
     */
    public function getStorageId()
    {
        if (!is_object($this->response))
            return null;

        return $this->response->getStorageId();
    }

    /**
     * Returns WirecardCheckoutSeamless javascript url
     *
     * @return mixed
     */
    public function getJavascriptUrl()
    {
        if (!is_object($this->response))
            return null;

        return $this->response->getJavascriptUrl();
    }

}
