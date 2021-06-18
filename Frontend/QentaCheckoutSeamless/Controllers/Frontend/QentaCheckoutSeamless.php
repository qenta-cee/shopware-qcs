<?php
/**
 * QentaCheckoutSeamless controller
 *
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

use Shopware\Components\CSRFWhitelistAware;

/**
 * controller class handling Qenta Checkout Seamless Requests
 */
class Shopware_Controllers_Frontend_QentaCheckoutSeamless extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
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
        $basket = Shopware()->Modules()->Basket();
        $basketQuantities = $basket->sCheckBasketQuantities();
        if (!empty($basketQuantities['hideBasket'])) {
            return $this->redirect(array('controller' => 'checkout'));
        }

        if (Shopware()->QentaCheckoutSeamless()->Config()->BASKET_RESERVE) {
            $reservedItems = array();

            $basketContent = Shopware()->Session()->sOrderVariables['sBasket'];
            foreach ($basketContent['content'] as $cart_item_key => $cart_item) {
                $articleId = (int)$cart_item['articleID'];
                $quantity = (int)$cart_item['quantity'];

                $query = "update s_articles_details set instock = instock - $quantity where articleID = $articleId LIMIT 1";

                Shopware()->Db()->query($query);
                $reservedItems[$articleId] = $quantity;
            }
        }

        Shopware()->Session()->offsetSet('QentaWCSReservedBasketItems', $reservedItems);

        // Create new unique Id for the customer
        Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId = $this->createTransactionUniqueId();

        $sql = 'INSERT INTO `qenta_checkout_seamless` '
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
                ':uniqueId'      => Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId,
                ':hash'          => $this->generateHash(
                    Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId,
                    $this->getAmount(),
                    $this->getCurrencyShortName()
                ),
                ':orderdate'     => date('Y-m-d H:i:s'),
                ':state'         => 'progress',
                ':transactionId' => uniqid(),
                ':method'        => $this->getPaymentShortName(
                    Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId,
                    $this->getAmount(),
                    $this->getCurrencyShortName()
                ),
                ':basket'        => Shopware()->QentaCheckoutSeamless()->Basket()->getSerializedBasket(),
                ':sessiondata'   => base64_encode(serialize($_SESSION)), // store session data for server2server request
                ':remoteAddr'    => $_SERVER['REMOTE_ADDR']
            )
        );

        $this->View()->loadTemplate('responsive/frontend/qenta_checkout_seamless/index.tpl');

        $headerStyle = Shopware()->QentaCheckoutSeamless()->Config()->QENTA_CONFIRM_HEADER_STYLE;
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
        $paymentType = Shopware()->QentaCheckoutSeamless()->Config()->getPaymentMethod($this->getPaymentShortName());

        // Create and save new unique Id for the customer
        if (null == Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId) {
            Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId = $this->createTransactionUniqueId();
        }

        // update session data for server2server request
        $sql = 'UPDATE `qenta_checkout_seamless` SET session = :sessiondata WHERE uniqueId = :uniqueId';
        Shopware()->Db()->query(
            $sql,
            array(
                ':sessiondata' => base64_encode(serialize($_SESSION)),
                ':uniqueId'    => Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId
            ));

        $router = $this->Front()->Router();

        // dont append session to confirmUrl, but __shop is required otherwise redirect to login page
        // is forced by shopware when dispatching the confirm server2server request
        $confirmUrl = $router->assemble(
            array(
                'action'      => 'confirm',
                'forceSecure' => true
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
        if(strlen($_SESSION["wcs_redirect_url"])) {
            if ($paymentType == QentaCEE_Stdlib_PaymentTypeAbstract::SOFORTUEBERWEISUNG) {
                die(json_encode(array('redirectUrl' => $_SESSION["wcs_redirect_url"], 'useIframe' => false)));
            } else {
                die(json_encode(array('redirectUrl' => $_SESSION["wcs_redirect_url"], 'useIframe' => true)));
            }
        }

        // Set customer data like name, address
        $response = Shopware()->QentaCheckoutSeamless()->Seamless()->getResponse(
            $paymentType,
            $this->getAmount(),
            $this->getCurrencyShortName(),
            $urls,
            array(
                'sCoreId'                     => Shopware()->SessionID(),
                '__shop'                      => Shopware()->Shop()->getId(),
                'wQentaCheckoutSeamlessId' => Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId,
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
            Shopware()->QentaCheckoutSeamless()->qenta_action = 'failure';
            Shopware()->QentaCheckoutSeamless()->qenta_message = implode(', ', $msg);
            Shopware()->Pluginlogger()->error('QentaCheckoutSeamless: Message: ' . Shopware()->QentaCheckoutSeamless()->qenta_message
            );
            die($dataFail);
        }
        $_SESSION["wcs_redirect_url"] = $response->getRedirectUrl();
        if ($paymentType == QentaCEE_Stdlib_PaymentTypeAbstract::SOFORTUEBERWEISUNG) {
            die(json_encode(array('redirectUrl' => $response->getRedirectUrl(), 'useIframe' => false)));
        } else {
            die(json_encode(array('redirectUrl' => $response->getRedirectUrl(), 'useIframe' => true)));
        }
    }

    public function datastorageReadAction()
    {
        $ret = new \stdClass();

        /** @var QentaCEE_QMore_DataStorage_Response_Read $response */
        $response = Shopware()->QentaCheckoutSeamless()->Datastorage()->read();

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
        return Shopware()->QentaCheckoutSeamless()->getPaymentShortName();
    }

    /**
     * server2server request, no reliable session
     * store sessiondata serialized in transaction table
     * and restore sessiondata to _SESSION super global
     */
    public function confirmAction()
    {
        try {
            Shopware()->Session()->offsetSet('sPaymentstate', 'failure');

            Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

            $post = $this->Request()->getPost();

            Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: '. __METHOD__ . ':' . print_r($post, 1));

            $return = QentaCEE_QMore_ReturnFactory::getInstance(
                $post,
                Shopware()->QentaCheckoutSeamless()->Config()->SECRET
            );

            if (strlen($this->Request()->getParam('reservedItems'))) {
                $reservedItems = unserialize(base64_decode($this->Request()->getParam('reservedItems')));

                foreach ($reservedItems as $articleId => $quantity) {
                    $query = "UPDATE s_articles_details SET instock = instock + $quantity WHERE articleID = $articleId LIMIT 1";
                    Shopware()->Db()->query($query);
                }
            }

            $paymentUniqueId = $this->Request()->getParam('wQentaCheckoutSeamlessId');
            if (Shopware()->QentaCheckoutSeamless()->Config()->setAsTransactionID() == 'gatewayReferenceNumber') {
                $sTransactionIdField = 'gatewayReferenceNumber';
            }
            else {
                $sTransactionIdField = 'orderNumber';
            }
            $transactionId = $this->Request()->getParam($sTransactionIdField, $paymentUniqueId);

            if (!$return->validate()) {
                Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: '. __METHOD__ . ':Validation error: invalid response');
                die(\QentaCEE_QMore_ReturnFactory::generateConfirmResponseString('Validation error: invalid response'));
            }

            $sql = Shopware()->Db()->select()
                ->from('qenta_checkout_seamless')
                ->where('uniqueId = ?', array($paymentUniqueId));
            $data = Shopware()->Db()->fetchRow($sql);

            $orderId = $data['orderId'];

            $sessionData = unserialize(base64_decode($data['session']));
            if(!is_array($sessionData)) {
                Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: '. __METHOD__ . ':Validation error: invalid session data');
                die(\QentaCEE_QMore_ReturnFactory::generateConfirmResponseString('Validation error: invalid session data'));
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

            $sUser = array (
                'billing_salutation' => $userData['billingaddress']['salutation'],
                'billing_firstname' => $userData['billingaddress']['firstname'],
                'billing_lastname' => $userData['billingaddress']['lastname']
            );

            $message = null;
            
            switch ($return->getPaymentState()) {

                case QentaCEE_QMore_ReturnFactory::STATE_SUCCESS:
                    /** @var QentaCEE_QMore_Return_Success $return */

                    $update['orderNumber'] = $return->getOrderNumber();
                    $update['session'] = '';

                    Shopware()->Session()->offsetSet('sPaymentstate', 'success');

                    // pending url, we already have an order id, just update the payment state
                    if ($data['orderId']) {
                        $this->saveOrder(
                            $data['transactionId'],
                            $paymentUniqueId,
                            Shopware()->QentaCheckoutSeamless()->Config()->getPaymentStatusId('success'),
                            false
                        );
                        //never send mail automatic
                        $orderId = $data['orderId'];
                        $sSystem = Shopware()->Modules()->Admin()->sSYSTEM;

                        $context = array(
                            'sOrderDetails' => $details,
                            'billingaddress'  => $userData['billingaddress'],
                            'shippingaddress' => $userData['shippingaddress'],
                            'additional'      => $userData['additional'],

                            'sShippingCosts' => $sSystem->sMODULES['sArticles']->sFormatPrice($sOrderVariables['sShippingcosts']). ' ' .$sSystem->sCurrency['currency'],
                            'sAmount'        => $sOrderVariables['sAmountWithTax'] ? $sSystem->sMODULES['sArticles']->sFormatPrice($sOrderVariables['sAmountWithTax']).' '.$sSystem->sCurrency['currency'] : $sSystem->sMODULES['sArticles']->sFormatPrice($sOrderVariables['sAmount']).' '.$sSystem->sCurrency['currency'],
                            'sAmountNet'     => $sSystem->sMODULES['sArticles']->sFormatPrice($basketData['AmountNetNumeric']). ' '.$sSystem->sCurrency['currency'],
                            'sDispatch'      => $sOrderVariables['sDispatch'],

                            'sOrderNumber' => $data['orderId'],
                            'sComment'     => $sOrderVariables['sComment'],
                            'sCurrency'    => $sSystem->sCurrency['currency'],
                            'sLanguage'    => $shop->getId(),

                            'sSubShop'     => $mainShop->getId(),
                            'sNet'    => empty($userData['additional']['charge_vat']),
                            'sEsd'     => $userData['additional']['payment']['esdactive'],
                            'sTaxRates'      => $basketData['sTaxRates']
                        );

                        $context['sOrderDay'] = date("d.m.Y");
                        $context['sOrderTime'] = date("H:i");

                        $context['sFactor'] = $sSystem->sCurrency['factor'];
                        $context['sBookingID'] = $data['transactionId'];
                        $context['attributes'] = $userData['billingaddress']['attributes'];

                        // Sending confirm mail for successfull order after pending
                        $mail = Shopware()->TemplateMail()->createMail('sORDER', $context);
                        $mail->addTo($userData['additional']['user']['email']);
                        if(!Shopware()->Config()->get('sNO_ORDER_MAIL')) {
                            $mail->addBcc(Shopware()->Config()->get('mail'));
                        }

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
                        $site = Shopware()->Shop();
                        $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Currency');
                        if (($currency = $this->Request()->getParam('__currency')) !== null) {
                            $site->setCurrency($repository->find($currency));
                            Shopware()->System()->sCurrency = $site->getCurrency()->toArray();
                        }
                        $update['transactionId'] = $return->getOrderNumber();
                        Shopware()->Session()->offsetSet('sPaymentstate', 'success');
                        $update['orderId'] = $this->saveOrder(
                            $transactionId,
                            $paymentUniqueId,
                            Shopware()->QentaCheckoutSeamless()->Config()->getPaymentStatusId('success'),
                            false
                        );
                        $orderId = $update['orderId'];
                    }

                    if (Shopware()->QentaCheckoutSeamless()->Config()->saveReturnValues() > 1) {
                        $this->saveComments($return , $orderId);
                    }
                    break;

                case QentaCEE_QMore_ReturnFactory::STATE_PENDING:
                    /** @var QentaCEE_QMore_Return_Pending $return */

                    Shopware()->Session()->offsetSet('sPaymentstate', 'pending');

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
                            Shopware()->QentaCheckoutSeamless()->Config()->getPaymentStatusId('pending'),
                            false
                        );
                        //do not send automatic mail for ordercreation
                        //only send pendingmail if configured
                        if(Shopware()->QentaCheckoutSeamless()->Config()->SEND_PENDING_MAILS) {
                            $existingOrder = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findByNumber($sOrderVariables['sOrderNumber']);
                            $status = $existingOrder[0]->getPaymentStatus();

                            $orderDate = date("d.m.Y");

                            if($details != null){
                                $orderDate = $details[0]['datum'];
                            }
                            $sOrder = array(
                                'ordernumber' => $sOrderVariables['sOrderNumber'],
                                'status_description' => Shopware()->Snippets()->getNamespace('backend/static/order_status')->get(
                                    $status->getName(),
                                    $status->getDescription()
                                ),
                                'ordertime' => $orderDate
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
                        if (Shopware()->QentaCheckoutSeamless()->Config()->saveReturnValues() > 1) {
                            $this->saveComments($return , $orderId);
                        }
                    }
                    break;

                case QentaCEE_QMore_ReturnFactory::STATE_CANCEL:
                    Shopware()->Session()->offsetSet('sPaymentstate', 'cancel');
                    break;

                case QentaCEE_QMore_ReturnFactory::STATE_FAILURE:
                    Shopware()->Session()->offsetSet('sPaymentstate', 'failure');
                    if ($data['orderId']) {
                        $existingOrder = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findByNumber($data['orderId']);

                        if(($existingOrder[0] instanceof \Shopware\Models\Order\Order) && $existingOrder[0]->getPaymentStatus()->getId() !== Shopware()->QentaCheckoutSeamless()->Config()->getPaymentStatusId('pending')) {
                            Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: '.__METHOD__ . ': failure: do not modify payment status as the order is in a final state');
                            break;
                        } else if($existingOrder[0] instanceof \Shopware\Models\Order\Order) {
                            $this->savePaymentStatus(
                                $data['transactionId'],
                                $paymentUniqueId,
                                Shopware()->QentaCheckoutSeamless()->Config()->getPaymentStatusId('failure'),
                                false
                            );

                            $status = $existingOrder[0]->getPaymentStatus();
                            $orderDate = date("d.m.Y");

                            if($details != null){
                                $orderDate = $details[0]['datum'];
                            }
                            $sOrder = array(
                                'ordernumber' => $sOrderVariables['sOrderNumber'],
                                'status_description' => $status->getName(),
                                'ordertime' => $orderDate
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
                                $variables['sOrderNumber'] = $sOrderVariables['sOrderNumber'];
                                $variables['confirmMailDeliveryFailed'] = true;
                                Shopware()->Session()->offsetSet('sOrderVariables', $variables);
                            }
                        }

                        $errors = array();
                        foreach ( $return->getErrors() as $error ) {
                            $errors[] = $error->getConsumerMessage();
                            $message  = $error->getConsumerMessage();
                        }

                    }
                    break;
                default:
            }

            $update['data'] = serialize($post);
            $update['session'] = base64_encode(serialize($_SESSION)); // save back ev. modified sessiondata

            Shopware()->Db()->update(
                'qenta_checkout_seamless',
                $update,
                "uniqueId = '$paymentUniqueId'"
            );

        } catch (Exception $e) {
            Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: '.__METHOD__ . ':' . $e->getMessage());
            die(QentaCEE_QMore_ReturnFactory::generateConfirmResponseString(htmlspecialchars($e->getMessage())));
        }

        die(QentaCEE_QMore_ReturnFactory::generateConfirmResponseString($message));
    }

    /**
     * Browser return to the shop
     */
    public function returnAction()
    {
        if(strlen($_SESSION["wcs_redirect_url"]))
            unset($_SESSION["wcs_redirect_url"]);

        // Get data saved by qenta callback
        $sql = Shopware()->Db()->select()
            ->from('qenta_checkout_seamless')
            ->where('uniqueId = ?', array(Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId));
        $result = Shopware()->Db()->fetchRow($sql);

        $this->View()->loadTemplate('responsive/frontend/qenta_checkout_seamless/return.tpl');

        $this->View()->redirectUrl = $this->Front()->Router()->assemble(Array('controller' => 'checkout', 'action' => 'confirm', 'sUseSSL' => true));

        $responseData = unserialize($result['data']);

        // write back modified sessiondata, might be modified by the confirm (server2server) request
        $savedSessionData = unserialize(base64_decode($result['session']));
        if (is_array($savedSessionData) && isset($savedSessionData['Shopware'])) {
            Shopware()->Session()->offsetSet('sOrderVariables', $savedSessionData['Shopware']['sOrderVariables']);
        }

        try {

            $return = QentaCEE_QMore_ReturnFactory::getInstance(
                $responseData,
                Shopware()->QentaCheckoutSeamless()->Config()->SECRET
            );

            switch ($return->getPaymentState()) {
                case QentaCEE_QMore_ReturnFactory::STATE_SUCCESS:
                    /** @var $return QentaCEE_QMore_Return_Success */
                    $this->View()->redirectUrl = $this->Front()->Router()->assemble(Array('controller' => 'checkout', 'action' => 'finish', 'sUseSSL' => true));
                    break;

                case QentaCEE_QMore_ReturnFactory::STATE_PENDING:
                    $this->View()->redirectUrl = $this->Front()->Router()->assemble(Array('controller' => 'checkout', 'action' => 'finish', 'sUseSSL' => true, 'pending' => true));
                    break;

                case QentaCEE_QMore_ReturnFactory::STATE_CANCEL:
                    /** @var $return QentaCEE_QMore_Return_Cancel */
                    Shopware()->QentaCheckoutSeamless()->qenta_action = 'cancel';
                    break;

                case QentaCEE_QMore_ReturnFactory::STATE_FAILURE:
                default:
                    /** @var $return QentaCEE_QMore_Return_Failure */
                    $msg = '';
                    for ($i = 1; $i <= $return->getNumberOfErrors(); $i++) {
                        $key = sprintf('error_%d_consumerMessage', $i);
                        if (strlen($msg)) {
                            $msg .= "<br/>";
                        }
                        $msg .= $responseData[$key];
                    }
                    Shopware()->QentaCheckoutSeamless()->qenta_action = 'external_error';
                    Shopware()->QentaCheckoutSeamless()->qenta_message = $msg;

            }

        } catch (Exception $e) {
            Shopware()->Pluginlogger()->error('QentaCheckoutSeamless: '.__METHOD__ . ':' . $e->getMessage());

            /** @var Enlight_Components_Snippet_Namespace ns */
            $ns = Shopware()->Snippets()->getNamespace('engine/Shopware/Plugins/Community/Frontend/QentaCheckoutSeamless/Views/frontend/checkout/qenta');
            // suppress technical error message
            Shopware()->QentaCheckoutSeamless()->qenta_message = $ns['QentaMessageActionFailure'];
            Shopware()->QentaCheckoutSeamless()->qenta_action = 'failure';
        }

        Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId = $this->createTransactionUniqueId();
    }

    /**
     * Called after successfully payment
     */
    public function successAction()
    {
        // Get data saved by qenta callback
        $sql = Shopware()->Db()->select()
            ->from('qenta_checkout_seamless')
            ->where('uniqueId = ?', array(Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId));
        $result = Shopware()->Db()->fetchRow($sql);
        $update = array();

        // Payment accepted: normal successfully transaction
        if (!empty($result['transactionId'])) {
            if ($result['hash'] != $this->generateHash(
                    Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId,
                    $this->getAmount(),
                    $this->getCurrencyShortName()
                )
            ) {
                Shopware()->Pluginlogger()->warning('QentaCheckoutSeamless: Hash could not be verified');
                // Restore old basket
                if (false == Shopware()->QentaCheckoutSeamless()->Basket()->setSerializedBasket($result->basket)) {
                    Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: Restoring basket');
                    $status = Shopware()->QentaCheckoutSeamless()->Config()->getPaymentStatusId('checkup');
                }
                else {
                    Shopware()->Pluginlogger()->warning('QentaCheckoutSeamless: Something is wrong - check order!');
                    $status = Shopware()->QentaCheckoutSeamless()->Config()->getPaymentStatusId('success');
                }
            } // Normal order
            else {
                Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: save successfully order');
                $status = Shopware()->QentaCheckoutSeamless()->Config()->getPaymentStatusId(
                    strtolower($result['state'])
                );
            }

            $update['orderId'] = $this->saveOrder(
                $result['transactionId'],
                Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId,
                $status
            );
            // Update date of payment
            Shopware()->Db()->update(
                's_order',
                array('cleareddate' => date('Y-m-d H:i:s')),
                'ordernumber  = ' . (int)$update['orderId']
            );
            // Save return values by Qenta
            if (true == Shopware()->QentaCheckoutSeamless()->Config()->saveReturnValues() and !empty($result['data'])
            ) {
                $return = QentaCEE_QMore_ReturnFactory::getInstance(
                    unserialize($result['data']),
                    Shopware()->QentaCheckoutSeamless()->Config()->SECRET
                );
                $this->saveComments($return, $update['orderId']);
            }
        } // Under normal conditions this should not happen - maybe if the callback failed
        else {
            Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: save unsuccessfully order');
            $transactionId = (empty($result->transactionId)) ? Shopware()->SessionID() : $result->transactionId;
            $update['orderId'] = $this->saveOrder(
                $transactionId,
                Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId,
                Shopware()->QentaCheckoutSeamless()->Config()->getPaymentStatusId('checkup')
            );
            $update['state'] = 'checkup';
        }
        Shopware()->Db()->update(
            'qenta_checkout_seamless',
            $update,
            'uniqueId = \'' . Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId . '\''
        );
        Shopware()->QentaCheckoutSeamless()->wQentaCheckoutSeamlessId = $this->createTransactionUniqueId();

        $this->View()->loadTemplate('responsive/frontend/qenta_checkout_seamless/return.tpl');
        $this->View()->redirectUrl = $this->Front()->Router()->assemble(Array('controller' => 'checkout', 'action' => 'finish', 'sUseSSL' => true));
    }

    /**
     * Save return data
     *
     * @param QentaCEE_QMore_Return_Success $return
     * @param null $orderNumber
     *
     * @internal param null $transactionId
     */
    protected function saveComments(QentaCEE_Stdlib_Return_ReturnAbstract $return = null, $orderNumber = null)
    {
        $comments = array();
        $comments[] = "------- Qenta Response Data --------";
        $gatewayReferenceNumber ='';
        foreach ($return->getReturned() as $name => $value) {
            if ($name == 'sCoreId' || $name == 'wQentaCheckoutPageId') {
                continue;
            }
            if($name == 'gatewayReferenceNumber'){
                $gatewayReferenceNumber = $value;
            }
            $comments[] = sprintf('%s: %s', $name, $value);
        }
        $comments[] = "---------------------------------------";



        $field = Shopware()->QentaCheckoutSeamless()->Config()->getReturnField();
        Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: Comment field:' . $field);
        if ($field == 'internalcomment') {

            Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: Saving internal comment');
            Shopware()->Db()->update(
                's_order',
                array($field => implode("\n", $comments)),
                'ordernumber = \'' . $orderNumber . '\''
            );
        } else {
            $sql = Shopware()->Db()->select()
                ->from('s_order', array('id'))
                ->where('ordernumber = ?', array($orderNumber));
            $orderId = Shopware()->Db()->fetchOne($sql);

            Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: Saving attribute');
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
        $post = $this->Request()->getPost();

        Shopware()->Pluginlogger()->info('QentaCheckoutSeamless: Called: dsStoreReturnAction');
        if (empty($post['response'])) {
            Shopware()->Pluginlogger()->error('QentaCheckoutSeamless: dsStoreReturnAction: Parameter not found');
            die('Parameter not found');
        }

        $this->View()->loadTemplate('responsive/frontend/qenta_checkout_seamless/storeReturn.tpl');
        $this->View()->qentaResponse = (true == get_magic_quotes_gpc()) ? $post['response'] : addslashes(
            $post['response']
        );
        Shopware()->Pluginlogger()->info(
            'QentaCheckoutSeamless: Response: ' . print_r($this->View()->qentaResponse, 1)
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
            Shopware()->QentaCheckoutSeamless()->Config()->SECRET . '|' . $id . '|' . $amount . '|' . $currencycode
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
