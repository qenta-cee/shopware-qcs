<?php
/*
* Die vorliegende Software ist Eigentum von Wirecard CEE und daher vertraulich
* zu behandeln. Jegliche Weitergabe an dritte, in welcher Form auch immer, ist
* unzulaessig.
*
* Software & Service Copyright (C) by
* Wirecard Central Eastern Europe GmbH,
* FB-Nr: FN 195599 x, http://www.wirecard.at
*/
/**
 * @name WirecardCEE_QMore_BackendClient
 * @category WirecardCEE
 * @package WirecardCEE_QMore
 * @version 3.1.0
 */
class WirecardCEE_QMore_BackendClient extends WirecardCEE_Stdlib_Client_ClientAbstract {
    /**
     * Password
     * @var string
     */
    const PASSWORD                 = 'password';

    /**
     * Payment Number
     * @var string
     */
    const PAYMENT_NUMBER         = 'paymentNumber';

    /**
     * Credit number
     * @var string
     */
    const CREDIT_NUMBER         = 'creditNumber';

    /**
     * Source order number
     * @var string
     */
    const SOURCE_ORDER_NUMBER     = 'sourceOrderNumber';

    /**
     * Command
     * @var string
     */
    const COMMAND                 = 'command';

    /**
     * Plugin version
     * @var string
     */
    const PLUGIN_VERSION         = 'pluginVersion';

    /**
     * Command: Approve reversal
     * @staticvar string
     * @internal
     */
    protected static $COMMAND_APPROVE_REVERSAL     = 'approveReversal';

    /**
     * Command: Deposit
     * @staticvar string
     * @internal
     */
    protected static $COMMAND_DEPOSIT             = 'deposit';

    /**
     * Command: Deposit reveresal
     * @staticvar string
     * @internal
     */
    protected static $COMMAND_DEPOSIT_REVERSAL     = 'depositReversal';

    /**
     * Command: Get order details
     * @staticvar string
     * @internal
     */
    protected static $COMMAND_GET_ORDER_DETAILS = 'getOrderDetails';

    /**
     * Command: Recur payment
     * @staticvar string
     * @internal
     */
    protected static $COMMAND_RECUR_PAYMENT     = 'recurPayment';

    /**
     * Command: Refund
     * @staticvar string
     * @internal
     */
    protected static $COMMAND_REFUND             = 'refund';

    /**
     * Command: Refund reversal
     * @staticvar string
     * @internal
     */
    protected static $COMMAND_REFUND_REVERSAL     = 'refundReversal';

    /**
     * using FIXED fingerprint order (0 = dynamic, 1 = fixed)
     * @var int
     */
    protected $_fingerprintOrderType = 1;

    /**
     * Creates an instance of an WirecardCEE_QMore_BackendClient object.
     *
     * @param array $aConfig
     */
    public function __construct(Array $aConfig = null) {
        $this->_fingerprintOrder = new WirecardCEE_Stdlib_FingerprintOrder();

        //if no config was sent fallback to default config file
        if(is_null($aConfig)) {
            $aConfig = WirecardCEE_QMore_Module::getConfig();
        }

        if (isset($aConfig['WirecardCEEQMoreConfig'])) {
            // we only need the WirecardCEEQMoreConfig here
            $aConfig = $aConfig['WirecardCEEQMoreConfig'];
        }

        // let's store configuration details in internal objects
        $this->oUserConfig = new WirecardCEE_Stdlib_Config($aConfig);
        $this->oClientConfig = new WirecardCEE_Stdlib_Config(WirecardCEE_QMore_Module::getClientConfig());

        // now let's check if the CUSTOMER_ID, SHOP_ID, LANGUAGE and SECRET
        // exist in $this->oUserConfig object that we created from config array
        $sCustomerId =     isset($this->oUserConfig->CUSTOMER_ID)     ? trim($this->oUserConfig->CUSTOMER_ID) : null;
        $sShopId =         isset($this->oUserConfig->SHOP_ID)         ? trim($this->oUserConfig->SHOP_ID)     : null;
        $sLanguage =     isset($this->oUserConfig->LANGUAGE)     ? trim($this->oUserConfig->LANGUAGE)     : null;
        $sSecret =         isset($this->oUserConfig->SECRET)         ? trim($this->oUserConfig->SECRET)         : null;
        $sPassword =     isset($this->oUserConfig->PASSWORD)     ? trim($this->oUserConfig->PASSWORD)    : null;

        // If not throw the InvalidArgumentException exception!
        if (empty($sCustomerId) || is_null($sCustomerId)) {
            throw new WirecardCEE_QMore_Exception_InvalidArgumentException(sprintf('CUSTOMER_ID passed to %s is invalid.', __METHOD__));
        }

        if (empty($sLanguage) || is_null($sLanguage)) {
            throw new WirecardCEE_QMore_Exception_InvalidArgumentException(sprintf('LANGUAGE passed to %s is invalid.', __METHOD__));
        }

        if (empty($sSecret) || is_null($sSecret)) {
            throw new WirecardCEE_QMore_Exception_InvalidArgumentException(sprintf('SECRET passed to %s is invalid.', __METHOD__));
        }

        if (empty($sPassword) || is_null($sPassword)) {
            throw new WirecardCEE_QMore_Exception_InvalidArgumentException(sprintf('PASSWORD passed to %s is invalid.', __METHOD__));
        }

        // everything ok! let's set the fields
        $this->_setField(self::CUSTOMER_ID, $sCustomerId);
        $this->_setField(self::SHOP_ID, $sShopId);
        $this->_setField(self::LANGUAGE, $sLanguage);
        $this->_setField(self::PASSWORD, $sPassword);

        $this->_setSecret($sSecret);
    }

    /**
     * Refund
     *
     * @throws WirecardCEE_Stdlib_Client_Exception_InvalidResponseException
     * @return WirecardCEE_QMore_Response_Backend_Refund
     */
    public function refund($iOrderNumber, $iAmount, $sCurrency) {
        $this->_requestData[self::COMMAND] = self::$COMMAND_REFUND;

        $this->_setField(self::ORDER_NUMBER, $iOrderNumber);
        $this->_setField(self::AMOUNT, $iAmount);
        $this->_setField(self::CURRENCY, strtoupper($sCurrency));

        $this->_fingerprintOrder->setOrder(Array(
                self::CUSTOMER_ID,
                self::SHOP_ID,
                self::PASSWORD,
                self::SECRET,
                self::LANGUAGE,
                self::ORDER_NUMBER,
                self::AMOUNT,
                self::CURRENCY
        ));

        return new WirecardCEE_QMore_Response_Backend_Refund($this->_send());
    }

    /**
     * Refund reversal
     *
     * @throws WirecardCEE_Stdlib_Client_Exception_InvalidResponseException
     * @return WirecardCEE_QMore_Response_Backend_RefundReversal
     */
    public function refundReversal($iOrderNumber, $iCreditNumber) {
        $this->_requestData[self::COMMAND] = self::$COMMAND_REFUND_REVERSAL;

        $this->_setField(self::ORDER_NUMBER, $iOrderNumber);
        $this->_setField(self::CREDIT_NUMBER, $iCreditNumber);

        $this->_fingerprintOrder->setOrder(Array(
                self::CUSTOMER_ID,
                self::SHOP_ID,
                self::PASSWORD,
                self::SECRET,
                self::LANGUAGE,
                self::ORDER_NUMBER,
                self::CREDIT_NUMBER
        ));

        return new WirecardCEE_QMore_Response_Backend_RefundReversal($this->_send());
    }

    /**
     * Recur payment
     *
     * @throws WirecardCEE_Stdlib_Client_Exception_InvalidResponseException
     * @return WirecardCEE_QMore_Response_Backend_RecurPayment
     */
    public function recurPayment($iSourceOrderNumber, $iAmount, $sCurrency, $sOrderDescription, $iOrderNumber = null, $bDepositFlag = null) {
        $this->_requestData[self::COMMAND] = self::$COMMAND_RECUR_PAYMENT;

        if(!is_null($iOrderNumber)) {
            $this->_setField(self::ORDER_NUMBER, $iOrderNumber);
        }

        $this->_setField(self::SOURCE_ORDER_NUMBER, $iSourceOrderNumber);
        $this->_setField(self::AMOUNT, $iAmount);
        $this->_setField(self::CURRENCY, strtoupper($sCurrency));

        if(!is_null($bDepositFlag)) {
            $this->_setField(self::AUTO_DEPOSIT, $bDepositFlag ? self::$BOOL_TRUE : self::$BOOL_FALSE);
        }

        $this->_setField(self::ORDER_DESCRIPTION, $sOrderDescription);

        $this->_fingerprintOrder->setOrder(Array(
                self::CUSTOMER_ID,
                self::SHOP_ID,
                self::PASSWORD,
                self::SECRET,
                self::LANGUAGE,
                self::ORDER_NUMBER,
                self::SOURCE_ORDER_NUMBER,
                self::AUTO_DEPOSIT,
                self::ORDER_DESCRIPTION,
                self::AMOUNT,
                self::CURRENCY
        ));

        return new WirecardCEE_QMore_Response_Backend_RecurPayment($this->_send());
    }

    /**
     * Returns order details
     *
     * @param int $iOrderNumber
     * @throws WirecardCEE_Stdlib_Client_Exception_InvalidResponseException
     * @return WirecardCEE_QMore_Response_Backend_GetOrderDetails
     */
    public function getOrderDetails($iOrderNumber) {
        $this->_requestData[self::COMMAND] = self::$COMMAND_GET_ORDER_DETAILS;
        $this->_setField(self::ORDER_NUMBER, $iOrderNumber);

        $this->_fingerprintOrder->setOrder(Array(
                self::CUSTOMER_ID,
                self::SHOP_ID,
                self::PASSWORD,
                self::SECRET,
                self::LANGUAGE,
                self::ORDER_NUMBER
        ));

        return new WirecardCEE_QMore_Response_Backend_GetOrderDetails($this->_send());
    }

    /**
     * Approve reversal
     *
     * @throws WirecardCEE_Stdlib_Client_Exception_InvalidResponseException
     * @return WirecardCEE_QMore_Response_Backend_ApproveReversal
     */
    public function approveReversal($iOrderNumber) {
        $this->_requestData[self::COMMAND] = self::$COMMAND_APPROVE_REVERSAL;
        $this->_setField(self::ORDER_NUMBER, $iOrderNumber);

        $this->_fingerprintOrder->setOrder(Array(
                self::CUSTOMER_ID,
                self::SHOP_ID,
                self::PASSWORD,
                self::SECRET,
                self::LANGUAGE,
                self::ORDER_NUMBER
        ));
        return new WirecardCEE_QMore_Response_Backend_ApproveReversal($this->_send());
    }

    /**
     * Deposit
     *
     * @throws WirecardCEE_Stdlib_Client_Exception_InvalidResponseException
     * @return WirecardCEE_QMore_Response_Backend_Deposit
     */
    public function deposit($iOrderNumber, $iAmount, $sCurrency) {
        $this->_requestData[self::COMMAND] = self::$COMMAND_DEPOSIT;

        $this->_setField(self::ORDER_NUMBER, $iOrderNumber);
        $this->_setField(self::AMOUNT, $iAmount);
        $this->_setField(self::CURRENCY, strtoupper($sCurrency));

        $this->_fingerprintOrder->setOrder(Array(
                self::CUSTOMER_ID,
                self::SHOP_ID,
                self::PASSWORD,
                self::SECRET,
                self::LANGUAGE,
                self::ORDER_NUMBER,
                self::AMOUNT,
                self::CURRENCY
        ));
        return new WirecardCEE_QMore_Response_Backend_Deposit($this->_send());
    }

    /**
     * Deposit reversal
     *
     * @throws WirecardCEE_Stdlib_Client_Exception_InvalidResponseException
     * @return WirecardCEE_QMore_Response_Backend_DepositReversal
     */
    public function depositReversal($iOrderNumber, $iPaymentNumber) {
        $this->_requestData[self::COMMAND] = self::$COMMAND_DEPOSIT_REVERSAL;

        $this->_setField(self::ORDER_NUMBER, $iOrderNumber);
        $this->_setField(self::PAYMENT_NUMBER, $iPaymentNumber);

        $this->_fingerprintOrder->setOrder(Array(
                self::CUSTOMER_ID,
                self::SHOP_ID,
                self::PASSWORD,
                self::SECRET,
                self::LANGUAGE,
                self::ORDER_NUMBER,
                self::PAYMENT_NUMBER
        ));
        return new WirecardCEE_QMore_Response_Backend_DepositReversal($this->_send());
    }

    /**
     * *******************
     * PROTECTED METHODS *
     * *******************
     */

    /**
     * Backend URL for POST-Requests
     *
     * @see WirecardCEE_Stdlib_Client_ClientAbstract::_getRequestUrl()
     * @return string
     */
    protected function _getRequestUrl() {
        return $this->oClientConfig->BACKEND_URL . "/" . $this->_getField(self::COMMAND);
    }

    /**
     * getter for given field
     *
     * @param string $name
     * @return string|null
     */
    protected function _getField($name) {
        return array_key_exists($name, $this->_requestData) ? $this->_requestData[$name] : null;
    }

    /**
     * Returns the user agent string
     *
     * @return string
     */
    protected function _getUserAgent() {
        return "{$this->oClientConfig->MODULE_NAME};{$this->oClientConfig->MODULE_VERSION}";
    }
}