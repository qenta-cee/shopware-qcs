<?php
/**
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
 * class using the WirecardCheckoutSeamless Frontend interface
 *
 */
class Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Seamless
{
    /**
     * Singleton pattern - only one instance of ourselves
     *
     * @var Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Seamless
     */
    private static $instance;

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
     * @return Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Seamless
     */
    public static function getSingleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Seamless();
        }
        return self::$instance;
    }


    /**
     * Initialize Wirecard library with common user and
     * plugin config parameter
     *
     * @param $confirmUrl
     * @return string|WirecardCEE_QMore_FrontendClient
     */
    public function initPayment($confirmUrl)
    {
        $cfg = Shopware()->WirecardCheckoutSeamless()->Config();

        $init = new WirecardCEE_QMore_FrontendClient(array(
            'CUSTOMER_ID' => $cfg->customerid,
            'SHOP_ID'     => $cfg->shopid,
            'SECRET'      => $cfg->secret,
            'LANGUAGE'    => Shopware()->Locale()->getLanguage()
        ));

        $init->setPluginVersion($this->getPluginVersion());

        $init->setConfirmUrl($confirmUrl);
        $init->setOrderReference(Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId);

        foreach ($cfg->wirecardCheckoutSeamlessParameters() as $action => $value) {
            if (!is_null($value)) {
                $init = $init->$action($value);
            }
        }

        if (TRUE == $cfg->setConfirmMail()) {
            $init->setConfirmMail(Shopware()->Config()->mail);
        }

        if (in_array(Shopware()->WirecardCheckoutSeamless()->getPaymentShortName(), $cfg->getPaymentsSeamless()) && Shopware()->WirecardCheckoutSeamless()->storageId) {
            $init->setStorageReference(Shopware()->SessionID(), Shopware()->WirecardCheckoutSeamless()->storageId);
        }

        if (in_array(Shopware()->WirecardCheckoutSeamless()->getPaymentShortName(), $cfg->getPaymentsFinancialInstitution())) {
            $init->setFinancialInstitution(Shopware()->WirecardCheckoutSeamless()->financialInstitution);
        }

        return $init;
    }

    /**
     * Returns Response object
     *
     * @param $paymentType string short name of payment method defined by Client library
     * @param $amount int|float basket-value
     * @param $currencyShortName string Currency
     * @param $urls
     * @param array $params
     * @return object
     */
    public function getResponse($paymentType, $amount, $currencyShortName, $urls, $params = array())
    {
        Shopware()->Pluginlogger()->info('WirecardCheckoutSeamless: '.$urls['confirm']);

        $init = $this->initPayment($urls['confirm']);

        // add custom params, will be send back by wirecard
        foreach ($params as $k => $v)
            $init->$k = $v;

        $userData = Shopware()->Session()->sOrderVariables['sUserData'];

        $init->setAmount($amount)
            ->setCurrency($currencyShortName)
            ->setPaymentType($paymentType)
            ->setOrderDescription($this->getUserDescription())
            ->setSuccessUrl($urls['success'])
            ->setPendingUrl($urls['pending'])
            ->setCancelUrl($urls['cancel'])
            ->setFailureUrl($urls['failure'])
            ->setServiceUrl(Shopware()->WirecardCheckoutSeamless()->Config()->service_url)
            ->createConsumerMerchantCrmId($userData['additional']['user']['email'])
            ->setConsumerData($this->getConsumerData($paymentType));

        if(Shopware()->WirecardCheckoutSeamless()->Config()->ENABLE_DUPLICATE_REQUEST_CHECK)
            $init->setDuplicateRequestCheck(true);

        $init->generateCustomerStatement(
            Shopware()->WirecardCheckoutSeamless()->Config()->getShopName(),
            Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId
        );


        Shopware()->Pluginlogger()->info('WirecardCheckoutSeamless: '.__METHOD__ . ':' . print_r($init->getRequestData(),true));

        try {
            return $init->initiate();
        } catch (\Exception $e) {
            Shopware()->Pluginlogger()->error('WirecardCheckoutSeamless: '.__METHOD__ . ':' . $e->getMessage());
            Shopware()->WirecardCheckoutSeamless()->wirecard_action = 'failure';
            Shopware()->WirecardCheckoutSeamless()->wirecard_message = $e->getMessage();
    	}

        return null;
    }

    /**
     * Returns version of this plugin
     *
     * @return string
     */
    protected function getPluginVersion()
    {
        $shopversion = Shopware::VERSION;
        if($shopversion == '') {
            $shopversion = '>5.2.21';
        }

        return WirecardCEE_QMore_FrontendClient::generatePluginVersion(
            'Shopware',
            $shopversion,
            Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Bootstrap::NAME,
            Shopware()->WirecardCheckoutSeamless()->Config()->getPluginVersion()
        );
    }

    /**
     * Returns desription of customer - will be displayed in Wirecard backend
     * @return string
     */
    public function getUserDescription()
    {
        return sprintf('%s %s %s',
            Shopware()->WirecardCheckoutSeamless()->getUser('user')->email,
            Shopware()->WirecardCheckoutSeamless()->getUser('billingaddress')->firstname,
            Shopware()->WirecardCheckoutSeamless()->getUser('billingaddress')->lastname
        );
    }

    /**
     * Returns customer object
     *
     * @param $paymentType
     * @return WirecardCEE_Stdlib_ConsumerData
     */
    public function getConsumerData($paymentType)
    {
        $consumerData = new WirecardCEE_Stdlib_ConsumerData();
        $consumerData = $consumerData->setIpAddress($_SERVER['REMOTE_ADDR']);
        $consumerData = $consumerData->setUserAgent($_SERVER['HTTP_USER_AGENT']);

        if (Shopware()->WirecardCheckoutSeamless()->Config()->send_additional_data || ($paymentType == 'INSTALLMENT' || $paymentType == 'INVOICE' || $paymentType == 'PRZELEWY24'))
        {
            $consumerData = $consumerData->setEmail(Shopware()->WirecardCheckoutSeamless()->getUser('user')->email);
            $consumerData = $consumerData->addAddressInformation($this->getAddress('billing'));
            $consumerData = $consumerData->addAddressInformation($this->getAddress('shipping'));

            $userData = Shopware()->Session()->sOrderVariables['sUserData'];
            $birthday = $userData['additional']['user']['birthday'];
            $birthday = $this->getDateObject($birthday);
            if (FALSE !== $birthday) {
                $consumerData = $consumerData->setBirthDate($birthday);
            }

        }
        Shopware()->Pluginlogger()->info('WirecardCheckoutSeamless: '.__METHOD__ . ':' . print_r($consumerData,true));
        return $consumerData;
    }


    /**
     * Returns address object
     *
     * @param string $type
     * @return WirecardCEE_Stdlib_ConsumerData_Address
     */
    protected function getAddress($type = 'billing')
    {
        $prefix = $type . 'address';
        switch ($type) {
            case 'shipping':
                $address = new WirecardCEE_Stdlib_ConsumerData_Address(WirecardCEE_Stdlib_ConsumerData_Address::TYPE_SHIPPING);
                break;

            default:
                $address = new WirecardCEE_Stdlib_ConsumerData_Address(WirecardCEE_Stdlib_ConsumerData_Address::TYPE_BILLING);
                break;
        }
        $address = $address->setFirstname(Shopware()->WirecardCheckoutSeamless()->getUser($prefix)->firstname);
        $address = $address->setLastname(Shopware()->WirecardCheckoutSeamless()->getUser($prefix)->lastname);
        $address = $address->setAddress1(Shopware()->WirecardCheckoutSeamless()->getUser($prefix)->street . ' ' . Shopware()->WirecardCheckoutSeamless()->getUser($prefix)->streetnumber);
        $address = $address->setZipCode(Shopware()->WirecardCheckoutSeamless()->getUser($prefix)->zipcode);
        $address = $address->setCity(Shopware()->WirecardCheckoutSeamless()->getUser($prefix)->city);
        switch ($type) {
            case 'billing':
                $address = $address->setCountry(Shopware()->WirecardCheckoutSeamless()->getUser('country')->countryiso);
                $address = $address->setPhone(Shopware()->WirecardCheckoutSeamless()->getUser($prefix)->phone);
                break;

            case 'shipping':
                $address = $address->setCountry(Shopware()->WirecardCheckoutSeamless()->getUser('countryShipping')->countryiso);
                break;
        }
        return $address;
    }

    /**
     * Returns DateTime object of customer's birthday
     * @param string $date
     * @return bool|DateTime
     */
    protected function getDateObject($date = '')
    {
        $birthday = new DateTime($date);
        $error = $birthday->getLastErrors();
        if (0 == $error['warning_count'] && 0 == $error['error_count']) {
            return $birthday;
        }
        else {
            return FALSE;
        }
    }

}
