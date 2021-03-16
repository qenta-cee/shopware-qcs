<?php
/**
 * Shop System Plugins - Terms of use
 *
 * This terms of use regulates warranty and liability between
 * Qenta Central Eastern Europe (subsequently referred to as Qenta CEE)
 * and it's contractual partners (subsequently referred to as customer or customers)
 * which are related to the use of plugins provided by Qenta CEE.
 * The Plugin is provided by Qenta CEE free of charge for it's customers and
 * must be used for the purpose of Qenta CEE's payment platform integration only.
 * It explicitly is not part of the general contract between Qenta CEE and it's customer.
 * The plugin has successfully been tested under specific circumstances
 * which are defined as the shopsystem's standard configuration (vendor's delivery state).
 * The Customer is responsible for testing the plugin's functionality
 * before putting it into production enviroment.
 * The customer uses the plugin at own risk. Qenta CEE does not guarantee it's full
 * functionality neither does Qenta CEE assume liability for any disadvantage related
 * to the use of this plugin. By installing the plugin into the shopsystem the customer
 * agrees to the terms of use. Please do not use this plugin if you do not agree to the terms of use!
 */



/**
 * class using the QentaCheckoutSeamless Frontend interface
 *
 */
class Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Seamless
{
    /**
     * Singleton pattern - only one instance of ourselves
     *
     * @var Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Seamless
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
     * Returns instance of Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Config
     *
     * @return Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Seamless
     */
    public static function getSingleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Seamless();
        }
        return self::$instance;
    }


    /**
     * Initialize Qenta library with common user and
     * plugin config parameter
     *
     * @param $confirmUrl
     * @return string|QentaCEE_QMore_FrontendClient
     */
    public function initPayment($confirmUrl)
    {
        $cfg = Shopware()->QentaCheckoutSeamless()->Config();

        $init = new QentaCEE_QMore_FrontendClient(array(
            'CUSTOMER_ID' => $cfg->customerid,
            'SHOP_ID'     => $cfg->shopid,
            'SECRET'      => $cfg->secret,
            'LANGUAGE'    => Shopware()->Locale()->getLanguage()
        ));

        $init->setPluginVersion($this->getPluginVersion());

        $init->setConfirmUrl($confirmUrl);
        $init->setOrderReference(Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId);
        if(Shopware()->Session()->offsetGet('wcsConsumerDeviceId') != null) {
            $init->consumerDeviceId = Shopware()->Session()->offsetGet('wcsConsumerDeviceId');
            //default set to null, but no effect
            Shopware()->Session()->offsetSet('wcsConsumerDeviceId', null);
        }

        foreach ($cfg->qentaCheckoutSeamlessParameters() as $action => $value) {
            if (!is_null($value)) {
                $init = $init->$action($value);
            }
        }

        if (TRUE == $cfg->setConfirmMail()) {
            $init->setConfirmMail(Shopware()->Config()->mail);
        }

        if (in_array(Shopware()->QentaCheckoutSeamless()->getPaymentShortName(), $cfg->getPaymentsSeamless()) && Shopware()->QentaCheckoutSeamless()->storageId) {
            $init->setStorageReference(Shopware()->SessionID(), Shopware()->QentaCheckoutSeamless()->storageId);
        }

        if (in_array(Shopware()->QentaCheckoutSeamless()->getPaymentShortName(), $cfg->getPaymentsFinancialInstitution())) {
            $init->setFinancialInstitution(Shopware()->QentaCheckoutSeamless()->financialInstitution);
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
        Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: '.$urls['confirm']);

        $init = $this->initPayment($urls['confirm']);

        // add custom params, will be send back by qenta
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
            ->setServiceUrl(Shopware()->QentaCheckoutSeamless()->Config()->service_url)
            ->createConsumerMerchantCrmId($userData['additional']['user']['email'])
            ->setConsumerData($this->getConsumerData($paymentType));

        $reservedItems = Shopware()->Session()->offsetGet('QentaWCSReservedBasketItems');

        if (is_array($reservedItems) && count($reservedItems) > 0) {
            $init->reservedItems = base64_encode(serialize($reservedItems));
            Shopware()->Session()->offsetUnset('QentaWCSReservedBasketItems');
        }

        if (Shopware()->QentaCheckoutSeamless()->Config()->SEND_BASKET_DATA
            || ($paymentType == QentaCEE_QMore_PaymentType::INSTALLMENT && Shopware()->QentaCheckoutSeamless()->Config()->INSTALLMENT_PROVIDER != 'payolution')
            || ($paymentType == QentaCEE_QMore_PaymentType::INVOICE && Shopware()->QentaCheckoutSeamless()->Config()->INVOICE_PROVIDER != 'payolution')
        ) {
            $init->setBasket($this->getShoppingBasket());
        }
        if(Shopware()->QentaCheckoutSeamless()->Config()->ENABLE_DUPLICATE_REQUEST_CHECK)
            $init->setDuplicateRequestCheck(true);

        $customerStatement = sprintf( '%9s', substr(Shopware()->Config()->get('ShopName'), 0, 9));
        if ($paymentType != QentaCEE_QMore_PaymentType::POLI){
            $customerStatement .= ' '.Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId;
        }
        $init->setCustomerStatement($customerStatement);

        Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: '.__METHOD__ . ':' . print_r($init->getRequestData(),true));

        try {
            return $init->initiate();
        } catch (\Exception $e) {
            Shopware()->Pluginlogger()->error('QentaCheckoutSeamless: '.__METHOD__ . ':' . $e->getMessage());
            Shopware()->QentaCheckoutSeamless()->qenta_action = 'failure';
            Shopware()->QentaCheckoutSeamless()->qenta_message = $e->getMessage();
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
        if( ! strlen($shopversion)) {
            $shopversion = '>5.2.21';
        }

        return QentaCEE_QMore_FrontendClient::generatePluginVersion(
            'Shopware',
            $shopversion,
            Shopware_Plugins_Frontend_QentaCheckoutSeamless_Bootstrap::NAME,
            Shopware()->QentaCheckoutSeamless()->Config()->getPluginVersion()
        );
    }

    /**
     * Returns desription of customer - will be displayed in Qenta backend
     * @return string
     */
    public function getUserDescription()
    {
        return sprintf('%s %s %s',
            Shopware()->QentaCheckoutSeamless()->getUser('user')->email,
            Shopware()->QentaCheckoutSeamless()->getUser('billingaddress')->firstname,
            Shopware()->QentaCheckoutSeamless()->getUser('billingaddress')->lastname
        );
    }

    /**
     * Returns customer object
     *
     * @param $paymentType
     * @return QentaCEE_Stdlib_ConsumerData
     */
    public function getConsumerData($paymentType)
    {
        $consumerData = new QentaCEE_Stdlib_ConsumerData();
        $consumerData = $consumerData->setIpAddress($_SERVER['REMOTE_ADDR']);
        $consumerData = $consumerData->setUserAgent($_SERVER['HTTP_USER_AGENT']);

        if (Shopware()->QentaCheckoutSeamless()->Config()->send_additional_data
            || $paymentType == QentaCEE_QMore_PaymentType::INSTALLMENT
            || $paymentType == QentaCEE_QMore_PaymentType::INVOICE
            || $paymentType == QentaCEE_QMore_PaymentType::P24
        ) {
            $consumerData = $consumerData->setEmail(Shopware()->QentaCheckoutSeamless()->getUser('user')->email);
            $consumerData = $consumerData->addAddressInformation($this->getAddress('billing'));
            $consumerData = $consumerData->addAddressInformation($this->getAddress('shipping'));

            $userData = Shopware()->Session()->sOrderVariables['sUserData'];
            $birthday = $userData['additional']['user']['birthday'];
            if (!empty($birthday)) {
                $birthday = $this->getDateObject($birthday);
                if (false !== $birthday) {
                    $consumerData = $consumerData->setBirthDate($birthday);
                }
            }
        }
        Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: '.__METHOD__ . ':' . print_r($consumerData,true));
        return $consumerData;
    }

    /**
     * Returns basket including basket items
     *
     * @return QentaCEE_Stdlib_Basket
     */
    protected function getShoppingBasket()
    {
        $basket = new QentaCEE_Stdlib_Basket();
        $basketContent = Shopware()->Session()->sOrderVariables['sBasket'];

        // Shopware uses fix precision (2) for number_format
        foreach ( $basketContent['content'] as $cart_item_key => $cart_item) {
            $item = new QentaCEE_Stdlib_Basket_Item($cart_item['articleID']);
            $item->setUnitGrossAmount($cart_item['price'])
                 ->setUnitNetAmount(number_format($cart_item['netprice'], 2, '.', ''))
                 ->setUnitTaxAmount(number_format($cart_item['price'] - $cart_item['netprice'], 2, '.', ''))
                 ->setUnitTaxRate($cart_item['tax_rate'])
                 ->setDescription( substr( strip_tags( $cart_item['additional_details']['description']), 0, 127 ) )
                 ->setName(isset($cart_item['additional_details']['articleName']) ? $cart_item['additional_details']['articleName'] : 'Surcharge')
                 ->setImageUrl( isset($cart_item['image']) ? $cart_item['image']['source'] : '' );

            $basket->addItem( $item, $cart_item['quantity']);
        }

        if (isset($basketContent['sShippingcosts']) && $basketContent['sShippingcosts'] > 0) {
            $item = new QentaCEE_Stdlib_Basket_Item('shipping');
            $item->setUnitGrossAmount($basketContent['sShippingcostsWithTax'])
                 ->setUnitNetAmount($basketContent['sShippingcostsNet'])
                 ->setUnitTaxRate($basketContent['sShippingcostsTax'])
                 ->setUnitTaxAmount($basketContent['sShippingcostsWithTax'] - $basketContent['sShippingcostsNet'])
                 ->setName('Shipping')
                 ->setDescription('Shipping');
            $basket->addItem($item);
        }

        return $basket;
    }

    /**
     * Returns address object
     *
     * @param string $type
     * @return QentaCEE_Stdlib_ConsumerData_Address
     */
    protected function getAddress($type = 'billing')
    {
        $prefix = $type . 'address';
        switch ($type) {
            case 'shipping':
                $address = new QentaCEE_Stdlib_ConsumerData_Address(QentaCEE_Stdlib_ConsumerData_Address::TYPE_SHIPPING);
                break;

            default:
                $address = new QentaCEE_Stdlib_ConsumerData_Address(QentaCEE_Stdlib_ConsumerData_Address::TYPE_BILLING);
                break;
        }
        $address = $address->setFirstname(Shopware()->QentaCheckoutSeamless()->getUser($prefix)->firstname);
        $address = $address->setLastname(Shopware()->QentaCheckoutSeamless()->getUser($prefix)->lastname);
        $address = $address->setAddress1(Shopware()->QentaCheckoutSeamless()->getUser($prefix)->street . ' ' . Shopware()->QentaCheckoutSeamless()->getUser($prefix)->streetnumber);
        $address = $address->setZipCode(Shopware()->QentaCheckoutSeamless()->getUser($prefix)->zipcode);
        $address = $address->setCity(Shopware()->QentaCheckoutSeamless()->getUser($prefix)->city);
        switch ($type) {
            case 'billing':
                $address = $address->setCountry(Shopware()->QentaCheckoutSeamless()->getUser('country')->countryiso);
                $address = $address->setPhone(Shopware()->QentaCheckoutSeamless()->getUser($prefix)->phone);
                break;

            case 'shipping':
                $address = $address->setCountry(Shopware()->QentaCheckoutSeamless()->getUser('countryShipping')->countryiso);
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
