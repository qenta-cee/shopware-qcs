<?php
/**
 * WirecardCheckoutSeamless controller
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

use Shopware\Components\CSRFWhitelistAware;

/**
 * controller class handling Wirecard Checkout Seamless Requests
 */
class Shopware_Controllers_Frontend_WirecardCheckoutSeamless extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{

    /**
     * Use Bootstrap init
     * Called by any actions
     */
    public function init()
    {
    }

    /**
     * Index action
     * Different view for seamless and other payment methods
     */
    public function indexAction()
    {
        // Create new unique Id for the customer
        Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId = $this->createTransactionUniqueId();

        $sql = 'INSERT INTO `wirecard_checkout_seamless` '
            . '(`uniqueId`, `hash`, `state`, `orderdate`, `method`, `transactionId`, `basket`, `session`, `remoteAddr`) '
            . 'VALUES '
            . '(:uniqueId, :hash, :state, :orderdate, :method, :transactionId, :basket, :sessiondata, :remoteAddr) '
            . 'ON DUPLICATE KEY UPDATE '
            . '`hash` = :hash, '
            . '`state` = :state, '
            . '`orderdate` = :orderdate, '
            . '`method` = :method, '
            . '`transactionId` =  :transactionId, '
            . '`basket` = :basket, '
            . '`session` = :sessiondata, '
            . '`remoteAddr` = :remoteAddr';

        Shopware()->Db()->query(
            $sql,
            array(
                ':uniqueId'      => Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId,
                ':hash'          => $this->generateHash(
                    Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId,
                    $this->getAmount(),
                    $this->getCurrencyShortName()
                ),
                ':orderdate'     => date('Y-m-d H:i:s'),
                ':state'         => 'progress',
                ':transactionId' => uniqid(),
                ':method'        => $this->getPaymentShortName(
                    Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId,
                    $this->getAmount(),
                    $this->getCurrencyShortName()
                ),
                ':basket'        => Shopware()->WirecardCheckoutSeamless()->Basket()->getSerializedBasket(),
                ':sessiondata'   => base64_encode(serialize($_SESSION)), // store session data for server2server request
                ':remoteAddr'    => $_SERVER['REMOTE_ADDR']
            )
        );

        if (Shopware()->Shop()->getTemplate()->getVersion() >= 3) {
            $this->View()->loadTemplate('responsive/frontend/wirecard_checkout_seamless/index.tpl');
        }
        else {
            $this->View()->loadTemplate('frontend/checkout/wirecard_seamless.tpl');
        }

        $headerStyle = Shopware()->WirecardCheckoutSeamless()->Config()->WIRECARD_CONFIRM_HEADER_STYLE;
        $headerTemplate = $headerStyle == 1 ? 'frontend/index/index.tpl' : 'frontend/checkout/confirm.tpl';
        $this->View()->assign('headerTemplate', $headerTemplate);
        $this->View()->assign('saveOrderUrl', $this->Front()->Router()->assemble(Array('action' => 'saveOrder', 'sUseSSL' => true)));
        $this->View()->assign('checkoutConfirmUrl', $this->Front()->Router()->assemble(Array('controller' => 'checkout', 'action' => 'confirm', 'sUseSSL' => true)));
    }

    /**
     * Initiate order
     * Called by index action
     */
    public function saveOrderAction()
    {
        $paymentType = Shopware()->WirecardCheckoutSeamless()->Config()->getPaymentMethod($this->getPaymentShortName());

        // Create and save new unique Id for the customer
        if (null == Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId) {
            Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId = $this->createTransactionUniqueId();
        }

        // update session data for server2server request
        $sql = 'UPDATE `wirecard_checkout_seamless` SET session = :sessiondata WHERE uniqueId = :uniqueId';
        Shopware()->Db()->query(
            $sql,
            array(
                ':sessiondata' => base64_encode(serialize($_SESSION)),
                ':uniqueId'    => Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId
            ));

        $router = $this->Front()->Router();

        // dont append session to confirmUrl, but __shop is required otherwise redirect to login page
        // is forced by shopware when dispatching the confirm server2server request
        $confirmUrl = $router->assemble(
            array(
                'action'      => 'confirm',
                'forceSecure' => true,
                'appendSession' => true
            )
        );

        $returnUrl = $router->assemble(
            array(
                'action'  => 'return',
                'sUseSSL' => true
            )
        );

        $urls = array(
            'success' => $returnUrl,
            'pending' => $returnUrl,
            'cancel'  => $returnUrl,
            'failure' => $returnUrl,
            'confirm' => $confirmUrl
        );

        // Set customer data like name, address
        $response = Shopware()->WirecardCheckoutSeamless()->Seamless()->getResponse(
            $paymentType,
            $this->getAmount(),
            $this->getCurrencyShortName(),
            $urls,
            array(
                'sCoreId'                     => Shopware()->SessionID(),
                '__shop'                      => Shopware()->Shop()->getId(),
                'wWirecardCheckoutSeamlessId' => Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId,
                '__currency'                  => Shopware()->Shop()->getCurrency()->getId(),
            )
        );

        $dataFail = json_encode(
            array('redirectUrl' => $this->Front()->Router()->assemble(Array('controller' => 'checkout', 'action' => 'confirm', 'sUseSSL' => true)), 'useIframe' => false)
        );
        if ($response === null) {
            die($dataFail);
        }

        if ($response->getNumberOfErrors() > 0) {
            $msg = array();
            foreach ($response->getErrors() as $error) {
                $msg[] = $error->getConsumerMessage();
            }
            Shopware()->WirecardCheckoutSeamless()->wirecard_action = 'failure';
            Shopware()->WirecardCheckoutSeamless()->wirecard_message = implode(', ', $msg);
            Shopware()->WirecardCheckoutSeamless()->Log()->Err(
                'Message: ' . Shopware()->WirecardCheckoutSeamless()->wirecard_message
            );
            die($dataFail);
        }

        die(json_encode(array('redirectUrl' => $response->getRedirectUrl(), 'useIframe' => true)));
    }

    public function datastorageReadAction()
    {
        $ret = new \stdClass();

        /** @var WirecardCEE_QMore_DataStorage_Response_Read $response */
        $response = Shopware()->WirecardCheckoutSeamless()->Datastorage()->read();

        $ret->status = $response->getStatus();
        $ret->paymentInformaton = $response->getPaymentInformation();
        print json_encode($ret);
        die;
    }

    /**
     * Returns short name of payment methods - overwrites parent method
     *
     * @return string
     */
    public function getPaymentShortName()
    {
        return Shopware()->WirecardCheckoutSeamless()->getPaymentShortName();
    }

    /**
     * server2server request, no reliable session
     * store sessiondata serialized in transaction table
     * and restore sessiondata to _SESSION super global
     */
    public function confirmAction()
    {
        try {
            Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

            $post = $this->processHTTPRequest();

            Shopware()->WirecardCheckoutSeamless()->Log()->Debug(__METHOD__ . ':' . print_r($post, 1));

            $return = WirecardCEE_QMore_ReturnFactory::getInstance(
                $post,
                Shopware()->WirecardCheckoutSeamless()->Config()->SECRET
            );

            $paymentUniqueId = $this->Request()->getParam('wWirecardCheckoutSeamlessId');
            if (Shopware()->WirecardCheckoutSeamless()->Config()->setAsTransactionID() == 'gatewayReferenceNumber') {
                $sTransactionIdField = 'gatewayReferenceNumber';
            }
            else {
                $sTransactionIdField = 'orderNumber';
            }
            $transactionId = $this->Request()->getParam($sTransactionIdField, $paymentUniqueId);

            if (!$return->validate()) {
                Shopware()->WirecardCheckoutSeamless()->Log()->Debug(__METHOD__ . ':Validation error: invalid response');
                print \WirecardCEE_QMore_ReturnFactory::generateConfirmResponseString('Validation error: invalid response');
                return;
            }

            $sql = Shopware()->Db()->select()
                ->from('wirecard_checkout_seamless')
                ->where('uniqueId = ?', array($paymentUniqueId));
            $data = Shopware()->Db()->fetchRow($sql);

            $sessionData = unserialize(base64_decode($data['session']));
            if(!is_array($sessionData)) {
                Shopware()->WirecardCheckoutSeamless()->Log()->Debug(__METHOD__ . ':Validation error: invalid session data');
                print \WirecardCEE_QMore_ReturnFactory::generateConfirmResponseString('Validation error: invalid session data');
                return;
            }

            // restore session
            $_SESSION = $sessionData;

            // restore remote address
            $_SERVER['REMOTE_ADDR'] = $data['remoteAddr'];

            $update['state'] = strtolower($return->getPaymentState());

            // data for confirm mail
            $sOrderVariables = Shopware()->Session()->sOrderVariables;
            $userData = Shopware()->Session()->sOrderVariables['sUserData'];
            $basketData = Shopware()->Session()->sOrderVariables['sBasket'];

            $shop = Shopware()->Shop();
            $mainShop = $shop->getMain() !== null ? $shop->getMain() : $shop;
            $details = $basketData['content'];

            $context = array(
                'sOrderDetails' => $details,
                'billingaddress'  => $userData['billingaddress'],
                'shippingaddress' => $userData['shippingaddress'],
                'additional'      => $userData['additional'],

                'sShippingCosts' => $sOrderVariables['sShippingcosts'],
                'sAmount'        => $sOrderVariables['sAmount'],
                'sAmountNet'     => $sOrderVariables['sAmountNet'],

                'sOrderNumber' => $sOrderVariables['sOrderNumber'],
                'sComment'     => $sOrderVariables['sComment'],
                'sCurrency'    => $sOrderVariables['sSYSTEM']->sCurrency['currency'],
                'sLanguage'    => $shop->getId(),

                'sSubShop'     => $mainShop->getId(),
                'sNet'    => $sOrderVariables['sNet'],
                'sTaxRates'      => $sOrderVariables['sTaxRates'],
            );

            $sUser = array (
                'billing_salutation' => $userData['billingaddress']['salutation'],
                'billing_firstname' => $userData['billingaddress']['firstname'],
                'billing_lastname' => $userData['billingaddress']['lastname']
            );

            switch ($return->getPaymentState()) {

                case WirecardCEE_QMore_ReturnFactory::STATE_SUCCESS:
                    /** @var WirecardCEE_QMore_Return_Success $return */

                    $update['orderNumber'] = $return->getOrderNumber();
                    $update['session'] = '';
                    $context['sOrderNumber'] = $sOrderVariables['sOrderNumber'];
                    $context['sOrderDay'] = date("d.m.Y");
                    $context['sOrderTime'] = date("H:i");

                    // pending url, we already have an order id, just update the payment state
                    if ($data['orderId']) {
                        $this->savePaymentStatus(
                            $data['transactionId'],
                            $paymentUniqueId,
                            Shopware()->WirecardCheckoutSeamless()->Config()->getPaymentStatusId('success'),
                            true
                        );
                        $orderId = $data['orderId'];

                        // Sending confirm mail for successfull order after pending
                        $mail = Shopware()->TemplateMail()->createMail('sORDER', $context);
                        $mail->addTo($userData['additional']['user']['email']);

                        try {
                            $mail->send();
                        } catch (\Exception $e) {
                            $variables = Shopware()->Session()->offsetGet('sOrderVariables');
                            $variables['sOrderNumber'] = $context['sOrderNumber'];
                            $variables['confirmMailDeliveryFailed'] = true;
                            Shopware()->Session()->offsetSet('sOrderVariables', $variables);
                        }
                    }
                    else {
                        $this->View()->specificMessage = 'Da läuft was!';

                        $site = Shopware()->Shop();
                        $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Currency');
                        if (($currency = $this->Request()->getParam('__currency')) !== null) {
                            $site->setCurrency($repository->find($currency));
                            Shopware()->System()->sCurrency = $site->getCurrency()->toArray();
                        }
                        $update['transactionId'] = $return->getOrderNumber();
                        $update['orderId'] = $this->saveOrder(
                            $transactionId,
                            $paymentUniqueId,
                            Shopware()->WirecardCheckoutSeamless()->Config()->getPaymentStatusId('success')
                        );
                        $orderId = $update['orderId'];

                        // Sending confirm mail for successfull order
                        $mail = Shopware()->TemplateMail()->createMail('sORDER', $context);
                        $mail->addTo($userData['additional']['user']['email']);

                        try {
                            $mail->send();
                        } catch (\Exception $e) {
                            $variables = Shopware()->Session()->offsetGet('sOrderVariables');
                            $variables['sOrderNumber'] = $context['sOrderNumber'];
                            $variables['confirmMailDeliveryFailed'] = true;
                            Shopware()->Session()->offsetSet('sOrderVariables', $variables);
                        }
                    }

                    if (Shopware()->WirecardCheckoutSeamless()->Config()->saveReturnValues()) {
                        $this->saveComments($return, $orderId);
                    }
                    break;

                case WirecardCEE_QMore_ReturnFactory::STATE_PENDING:
                    /** @var WirecardCEE_QMore_Return_Pending $return */

                    if (!$data['orderId']) {
                        $update['transactionId'] = $transactionId;

                        if ($return->getOrderNumber()) {
                            $update['transactionId'] = $return->getOrderNumber();
                            $transactionId = $return->getOrderNumber();
                        }

                        $site = Shopware()->Shop();
                        $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Currency');
                        if (($currency = $this->Request()->getParam('__currency')) !== null) {
                            $site->setCurrency($repository->find($currency));
                            Shopware()->System()->sCurrency = $site->getCurrency()->toArray();
                        }
                        $update['orderId'] = $this->saveOrder(
                            $transactionId,
                            $paymentUniqueId,
                            Shopware()->WirecardCheckoutSeamless()->Config()->getPaymentStatusId('pending')
                        );

                        //only send pendingmail if configured
                        if(Shopware()->WirecardCheckoutSeamless()->Config()->SEND_PENDING_MAILS) {
                            $existingOrder = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findByNumber($sOrderVariables['sOrderNumber']);
                            $status = $existingOrder[0]->getPaymentStatus();

                            $sOrder = array(
                                'ordernumber' => $sOrderVariables['sOrderNumber'],
                                'status_description' => $status->getName()
                            );

                            $pendingContext = array(
                                'sUser' => $sUser,
                                'sOrder' => $sOrder
                            );

                            // Sending confirm mail for successfull order
                            $mail = Shopware()->TemplateMail()->createMail('sORDERSTATEMAIL1', $pendingContext);
                            $mail->addTo($userData['additional']['user']['email']);

                            try {
                                $mail->send();
                            } catch (\Exception $e) {
                                $variables = Shopware()->Session()->offsetGet('sOrderVariables');
                                $variables['sOrderNumber'] = $sOrderVariables['sOrderNumber'];
                                $variables['confirmMailDeliveryFailed'] = true;
                                Shopware()->Session()->offsetSet('sOrderVariables', $variables);
                            }
                        }
                    }
                    break;

                case WirecardCEE_QMore_ReturnFactory::STATE_CANCEL:
                    break;

                case WirecardCEE_QMore_ReturnFactory::STATE_FAILURE:
                    if ($data['orderId']) {
                        $existingOrder = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findByNumber($data['orderId']);

                        if(($existingOrder[0] instanceof \Shopware\Models\Order\Order) && $existingOrder[0]->getPaymentStatus()->getId() !== Shopware()->WirecardCheckoutSeamless()->Config()->getPaymentStatusId('pending')) {
                            Shopware()->WirecardCheckoutSeamless()->Log()->Debug(__METHOD__ . ': failure: do not modify payment status as the order is in a final state');
                            break;
                        }

                        if(Shopware()->WirecardCheckoutSeamless()->Config()->keep_unsuccessful_orders) {
                            Shopware()->WirecardCheckoutSeamless()->Log()->Debug(__METHOD__ . ': failure: update order state: ' . $data['orderId']);
                            $this->savePaymentStatus(
                                $data['transactionId'],
                                $paymentUniqueId,
                                Shopware()->WirecardCheckoutSeamless()->Config()->getPaymentStatusId('failure')
                            );
                        } else if($existingOrder[0] instanceof \Shopware\Models\Order\Order) {
                            Shopware()->WirecardCheckoutSeamless()->Log()->Debug(__METHOD__ . ': failure: delete order: ' . $data['orderId']);
                            Shopware()->Models()->remove($existingOrder[0]);
                            Shopware()->Models()->flush();

                            $status = $existingOrder[0]->getPaymentStatus();

                            $sOrder = array(
                                'ordernumber' => $sOrderVariables['sOrderNumber'],
                                'status_description' => $status->getName()
                            );

                            $pendingContext = array (
                                'sUser' => $sUser,
                                'sOrder' => $sOrder
                            );

                            // Sending confirm mail for failed order after pending
                            $mail = Shopware()->TemplateMail()->createMail('sORDERSTATEMAIL4', $pendingContext);
                            $mail->addTo($userData['additional']['user']['email']);

                            try {
                                $mail->send();
                            } catch (\Exception $e) {
                                $variables = Shopware()->Session()->offsetGet('sOrderVariables');
                                $variables['sOrderNumber'] = $context['sOrderNumber'];
                                $variables['confirmMailDeliveryFailed'] = true;
                                Shopware()->Session()->offsetSet('sOrderVariables', $variables);
                            }
                        }
                        $update['session'] = '';
                    }
                    break;
                default:
            }

            $update['data'] = serialize($post);

            Shopware()->Db()->update(
                'wirecard_checkout_seamless',
                $update,
                "uniqueId = '$paymentUniqueId'"
            );

        } catch (Exception $e) {
            Shopware()->WirecardCheckoutSeamless()->Log()->Debug(__METHOD__ . ':' . $e->getMessage());
            print WirecardCEE_QMore_ReturnFactory::generateConfirmResponseString(htmlspecialchars($e->getMessage()));
            return;
        }

        print WirecardCEE_QMore_ReturnFactory::generateConfirmResponseString();
    }

    /**
     * Browser return to the shop
     */
    public function returnAction()
    {
        // Get data saved by wirecard callback
        $sql = Shopware()->Db()->select()
            ->from('wirecard_checkout_seamless')
            ->where('uniqueId = ?', array(Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId));
        $result = Shopware()->Db()->fetchRow($sql);

        if (Shopware()->Shop()->getTemplate()->getVersion() >= 3) {
            $this->View()->loadTemplate('responsive/frontend/wirecard_checkout_seamless/return.tpl');
        }
        else {
            $this->View()->loadTemplate('frontend/checkout/return.tpl');
        }

        $this->View()->redirectUrl = $this->Front()->Router()->assemble(Array('controller' => 'checkout', 'action' => 'confirm', 'sUseSSL' => true));

        $responseData = unserialize($result['data']);
        try {

            $return = WirecardCEE_QMore_ReturnFactory::getInstance(
                $responseData,
                Shopware()->WirecardCheckoutSeamless()->Config()->SECRET
            );

            switch ($return->getPaymentState()) {
                case WirecardCEE_QMore_ReturnFactory::STATE_SUCCESS:
                    /** @var $return WirecardCEE_QMore_Return_Success */
                    $this->View()->redirectUrl = $this->Front()->Router()->assemble(Array('controller' => 'checkout', 'action' => 'finish', 'sUseSSL' => true));
                    break;

                case WirecardCEE_QMore_ReturnFactory::STATE_PENDING:
                    $this->View()->redirectUrl = $this->Front()->Router()->assemble(Array('controller' => 'checkout', 'action' => 'finish', 'sUseSSL' => true, 'pending' => true));
                    break;

                case WirecardCEE_QMore_ReturnFactory::STATE_CANCEL:
                    /** @var $return WirecardCEE_QMore_Return_Cancel */
                    Shopware()->WirecardCheckoutSeamless()->wirecard_action = 'cancel';
                    break;

                case WirecardCEE_QMore_ReturnFactory::STATE_FAILURE:
                default:
                    /** @var $return WirecardCEE_QMore_Return_Failure */
                    $msg = '';
                    for ($i = 1; $i <= $return->getNumberOfErrors(); $i++) {
                        $key = sprintf('error_%d_consumerMessage', $i);
                        if (strlen($msg)) {
                            $msg .= "<br/>";
                        }
                        $msg .= $responseData[$key];
                    }
                    Shopware()->WirecardCheckoutSeamless()->wirecard_action = 'external_error';
                    Shopware()->WirecardCheckoutSeamless()->wirecard_message = $msg;

            }

        } catch (Exception $e) {
            Shopware()->WirecardCheckoutSeamless()->Log()->Err(__METHOD__ . ':' . $e->getMessage());

            /** @var Enlight_Components_Snippet_Namespace ns */
            $ns = Shopware()->Snippets()->getNamespace('engine/Shopware/Plugins/Community/Frontend/WirecardCheckoutSeamless/Views/frontend/checkout/wirecard');
            // suppress technical error message
            Shopware()->WirecardCheckoutSeamless()->wirecard_message = $ns['WirecardMessageActionFailure'];
            Shopware()->WirecardCheckoutSeamless()->wirecard_action = 'failure';
        }

        Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId = $this->createTransactionUniqueId();
    }

    /**
     * Called after successfully payment
     */
    public function successAction()
    {
        // Get data saved by wirecard callback
        $sql = Shopware()->Db()->select()
            ->from('wirecard_checkout_seamless')
            ->where('uniqueId = ?', array(Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId));
        $result = Shopware()->Db()->fetchRow($sql);
        $update = array();

        // Payment accepted: normal successfully transaction
        if (!empty($result['transactionId'])) {
            if ($result['hash'] != $this->generateHash(
                    Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId,
                    $this->getAmount(),
                    $this->getCurrencyShortName()
                )
            ) {
                Shopware()->WirecardCheckoutSeamless()->Log()->Debug('Hash could not be verified');
                // Restore old basket
                if (false == Shopware()->WirecardCheckoutSeamless()->Basket()->setSerializedBasket($result->basket)) {
                    Shopware()->WirecardCheckoutSeamless()->Log()->Debug('Restoring basket');
                    $status = Shopware()->WirecardCheckoutSeamless()->Config()->getPaymentStatusId('checkup');
                }
                else {
                    Shopware()->WirecardCheckoutSeamless()->Log()->Debug('Something is wrong - check order!');
                    $status = Shopware()->WirecardCheckoutSeamless()->Config()->getPaymentStatusId('success');
                }
            } // Normal order
            else {
                Shopware()->WirecardCheckoutSeamless()->Log()->Debug('save successfully order');
                $status = Shopware()->WirecardCheckoutSeamless()->Config()->getPaymentStatusId(
                    strtolower($result['state'])
                );
            }

            $update['orderId'] = $this->saveOrder(
                $result['transactionId'],
                Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId,
                $status
            );
            // Update date of payment
            Shopware()->Db()->update(
                's_order',
                array('cleareddate' => date('Y-m-d H:i:s')),
                'ordernumber  = ' . (int)$update['orderId']
            );
            // Save return values by Wirecard
            if (true == Shopware()->WirecardCheckoutSeamless()->Config()->saveReturnValues() and !empty($result['data'])
            ) {
                $return = WirecardCEE_QMore_ReturnFactory::getInstance(
                    unserialize($result['data']),
                    Shopware()->WirecardCheckoutSeamless()->Config()->SECRET
                );
                $this->saveComments($return, $update['orderId']);
            }
        } // Under normal conditions this should not happen - maybe if the callback failed
        else {
            Shopware()->WirecardCheckoutSeamless()->Log()->Debug('save unsuccessfully order');
            $transactionId = (empty($result->transactionId)) ? Shopware()->SessionID() : $result->transactionId;
            $update['orderId'] = $this->saveOrder(
                $transactionId,
                Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId,
                Shopware()->WirecardCheckoutSeamless()->Config()->getPaymentStatusId('checkup')
            );
            $update['state'] = 'checkup';
        }
        Shopware()->Db()->update(
            'wirecard_checkout_seamless',
            $update,
            'uniqueId = \'' . Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId . '\''
        );
        Shopware()->WirecardCheckoutSeamless()->wWirecardCheckoutSeamlessId = $this->createTransactionUniqueId();

        if (Shopware()->Shop()->getTemplate()->getVersion() >= 3) {
            $this->View()->loadTemplate('responsive/frontend/wirecard_checkout_seamless/return.tpl');
        }
        else {
            $this->View()->loadTemplate('frontend/checkout/return.tpl');
        }

        $this->View()->redirectUrl = $this->Front()->Router()->assemble(Array('controller' => 'checkout', 'action' => 'finish', 'sUseSSL' => true));
    }

    /**
     * Returns array with post parameters
     * fix for Shopware input filter
     */
    public static function processHTTPRequest()
    {
        $get_string = file_get_contents('php://input');
        parse_str($get_string, $post);
        return $post;
    }

    /**
     * Save return data
     *
     * @param WirecardCEE_QMore_Return_Success $return
     * @param null $orderNumber
     *
     * @internal param null $transactionId
     */
    protected function saveComments(WirecardCEE_QMore_Return_Success $return = null, $orderNumber = null)
    {
        $comments = array();
        foreach ($return->getReturned() as $name => $value) {
            if ($name == 'sCoreId' || $name == 'wWirecardCheckoutSeamlessId') {
                continue;
            }
            $comments[] = "$name: $value";
        }

        $field = Shopware()->WirecardCheckoutSeamless()->Config()->getReturnField();
        Shopware()->WirecardCheckoutSeamless()->Log()->Debug('Comment field:' . $field);
        if ($field == 'internalcomment') {

            Shopware()->WirecardCheckoutSeamless()->Log()->Debug('Saving internal comment');
            Shopware()->Db()->update(
                's_order',
                array($field => implode("\n", $comments)),
                'ordernumber = \'' . $orderNumber . '\''
            );
        }
        else {
            $sql = Shopware()->Db()->select()
                ->from('s_order', array('id'))
                ->where('ordernumber = ?', array($orderNumber));
            $orderId = Shopware()->Db()->fetchOne($sql);

            Shopware()->WirecardCheckoutSeamless()->Log()->Debug('Saving attribute');
            Shopware()->Db()->update(
                's_order_attributes',
                array($field => implode("\n", $comments)),
                'orderID = ' . (int)$orderId
            );
        }
    }

    /**
     * Datastorage return action
     */
    public function dsStoreReturnAction()
    {
        Shopware()->WirecardCheckoutSeamless()->Log()->Debug('Called: dsStoreReturnAction');
        $post = $this->processHTTPRequest();
        if (empty($post['response'])) {
            Shopware()->WirecardCheckoutSeamless()->Log()->Err('dsStoreReturnAction: Parameter not found');
            die('Parameter not found');
        }

        if (Shopware()->Shop()->getTemplate()->getVersion() >= 3) {
            $this->View()->loadTemplate('responsive/frontend/wirecard_checkout_seamless/storeReturn.tpl');
        }
        else {
            $this->View()->loadTemplate('frontend/checkout/dsStoreReturn.tpl');
        }

        $this->View()->wirecardResponse = (true == get_magic_quotes_gpc()) ? $post['response'] : addslashes(
            $post['response']
        );
        Shopware()->WirecardCheckoutSeamless()->Log()->Debug(
            'Response: ' . print_r($this->View()->wirecardResponse, 1)
        );

    }

    /**
     * generates a internal hash to validate returned payment
     *
     * @param $id
     * @param $amount
     * @param $currencycode
     *
     * @return string
     */
    public function generateHash($id, $amount, $currencycode)
    {
        return md5(
            Shopware()->WirecardCheckoutSeamless()->Config()->SECRET . '|' . $id . '|' . $amount . '|' . $currencycode
        );
    }

    /**
     *
     * returns a uniq String with default length 10.
     *
     * @param int $length
     *
     * @return string
     */
    public function createTransactionUniqueId($length = 10)
    {
        $tid = '';

        $alphabet = "023456789abcdefghikmnopqrstuvwxyzABCDEFGHIKMNOPQRSTUVWXYZ";

        for ($i = 0; $i < $length; $i++) {
            $c = substr($alphabet, mt_rand(0, strlen($alphabet) - 1), 1);

            if ((($i % 2) == 0) && !is_numeric($c)) {
                $i--;
                continue;
            }
            if ((($i % 2) == 1) && is_numeric($c)) {
                $i--;
                continue;
            }

            $alphabet = str_replace($c, '', $alphabet);
            $tid .= $c;
        }

        return $tid;
    }

    public function getWhitelistedCSRFActions()
    {
        return array(
            'confirm',
            'dsStoreReturn'
        );
    }
}
