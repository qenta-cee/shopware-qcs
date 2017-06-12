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
 * class representing the WirecardCheckoutSeamless configuration.
 */
class Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Config
{

    /**
     * Singleton pattern - only one instance of ourselve
     *
     * @var Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Config
     */
    private static $instance;

    /**
     * Root directory of the plugin
     *
     * @var string
     */
    private $pluginRoot = '';


    /**
     * List of payment methods with required financial institution
     *
     * @var array
     */
    private static $paymentsFinancialInstitution = array(
        'eps',
        'ideal'
    );

    /**
     * List of payment methods with additional informations
     * on checkout confirm page
     *
     * @var array
     */
    private static $paymentsSeamless = array(
        'ccard',
        'ccard-moto',
        'maestro',
        'pbx',
        'sepa-dd',
        'giropay',
        'voucher'
    );

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
     * @return Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Config
     */
    public static function getSingleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Config();
        }
        return self::$instance;
    }

    /**
     * Returns MySQL table structure required by this plugin
     *
     * @return array
     */
    public function getDbTables()
    {
        $return = Array();
        foreach ($this->getSnippets() AS $snippet) {
            $sql = " SELECT id FROM s_core_snippets WHERE  namespace = \"" . $snippet['namespace'] . "\" AND shopID = 1 AND localeID = " . $snippet['locale'] . " AND name = \"" . $snippet['name'] . "\"";
            $data = Shopware()->Db()->fetchAll($sql);
            $sql = '';
            if (count($data)) {
                $id = $data[0]['id'];
                $sql = 'UPDATE s_core_snippets SET value = "' . $snippet['value'] . '" WHERE id="' . $id . '"';
            } else {
                $sql = 'INSERT INTO s_core_snippets SET namespace = "' . $snippet['namespace'] . '",
                                                        shopID = "1",
                                                        localeID = "' . $snippet['locale'] . '",
                                                        name = "' . $snippet['name'] . '",
                                                        value = "' . $snippet['value'] . '"';
            }
            $return[] = $sql;
        }
        $return[] = 'CREATE TABLE IF NOT EXISTS `wirecard_checkout_seamless` (
                        `uniqueId` varchar(80) NOT NULL DEFAULT \'\',
                        `hash` varchar(80) DEFAULT NULL,
                        `state` varchar(30) DEFAULT NULL,
                        `orderdate` datetime DEFAULT NULL,
                        `method` varchar(30) DEFAULT NULL,
                        `transactionId` varchar(30) DEFAULT NULL,
                        `orderNumber` varchar(32) DEFAULT NULL,
                        `orderId` varchar(30) DEFAULT NULL,
                        `data` text DEFAULT NULL,
                        `basket` text DEFAULT NULL,
                        PRIMARY KEY (`uniqueId`),
                        KEY `hash` (`hash`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

        return $return;
    }

    /**
     * getter for snippet entries. Used for multilanguage texts
     *
     * @return array:array:string
     */
    public function getSnippets()
    {
        return array(
            array(
                'namespace' => 'frontend/checkout/return',
                'locale' => '1',
                'name' => 'WirecardCheckoutSeamlessPaymentRedirectHeader',
                'value' => 'Weiterleitung'
            ),
            array(
                'namespace' => 'frontend/checkout/return',
                'locale' => '2',
                'name' => 'WirecardCheckoutSeamlessPaymentRedirectHeader',
                'value' => 'Redirect'
            ),
            array(
                'namespace' => 'frontend/checkout/return',
                'locale' => '1',
                'name' => 'WirecardCheckoutSeamlessPaymentRedirectText',
                'value' => 'Sie werden nun weitergeleitet.'
            ),
            array(
                'namespace' => 'frontend/checkout/return',
                'locale' => '2',
                'name' => 'WirecardCheckoutSeamlessPaymentRedirectText',
                'value' => 'You will be redirected in a moment.'
            ),
            array(
                'namespace' => 'frontend/checkout/return',
                'locale' => '1',
                'name' => 'WirecardCheckoutSeamlessPaymentRedirectLinkText',
                'value' => 'Falls Sie nicht weitergeleitet werden, klicken Sie bitte'
            ),
            array(
                'namespace' => 'frontend/checkout/return',
                'locale' => '2',
                'name' => 'WirecardCheckoutSeamlessPaymentRedirectLinkText',
                'value' => 'If the redirect does not work please click'
            ),
            array(
                'namespace' => 'frontend/checkout/return',
                'locale' => '1',
                'name' => 'WirecardCheckoutSeamlessPaymentRedirectLink',
                'value' => 'hier'
            ),
            array(
                'namespace' => 'frontend/checkout/return',
                'locale' => '2',
                'name' => 'WirecardCheckoutSeamlessPaymentRedirectLink',
                'value' => 'here'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardFinancialInstitutions',
                'value' => 'Finanzinstitut'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardFinancialInstitutions',
                'value' => 'Financial institution'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardCcardCardholdername',
                'value' => 'Karteninhaber'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardCcardCardholdername',
                'value' => 'Card holder name'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardCcardPAN',
                'value' => 'Kartennummer'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardCcardPAN',
                'value' => 'Card number'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardCcardSecurityCode',
                'value' => 'Kartenpr&uuml;fnummer'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardCcardSecurityCode',
                'value' => 'Card verification code'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardCcardExpiration',
                'value' => 'Gültig bis'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardCcardExpiration',
                'value' => 'Expiration date'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardCcardIssueNumber',
                'value' => 'Ausgabenummer'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardCcardIssueNumber',
                'value' => 'Issue number'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardCcardIssueMonth',
                'value' => 'Ausgabedatum'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardCcardIssueMonth',
                'value' => 'Issue date'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardPayboxNumber',
                'value' => 'paybox-Nummer'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardPayboxNumber',
                'value' => 'paybox number'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardSEPADDAccount',
                'value' => 'Kontoinhaber'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardSEPADDAccount',
                'value' => 'Account number'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardSEPADDIban',
                'value' => 'IBAN'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardSEPADDIban',
                'value' => 'IBAN'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardSEPADDBic',
                'value' => 'BIC'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardSEPADDBic',
                'value' => 'BIC'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard_seamless',
                'locale' => '1',
                'name' => 'WirecardCheckoutSeamlessPaymentHeader',
                'value' => 'Bitte f&uuml;hren Sie nun die Zahlung durch.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard_seamless',
                'locale' => '2',
                'name' => 'WirecardCheckoutSeamlessPaymentHeader',
                'value' => 'Please proceed with the payment process.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard_seamless',
                'locale' => '1',
                'name' => 'WirecardCheckoutSeamlessPaymentInfoWait',
                'value' => 'Bitte warten ...'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard_seamless',
                'locale' => '2',
                'name' => 'WirecardCheckoutSeamlessPaymentInfoWait',
                'value' => 'Please wait ...'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardMessageActionCancel',
                'value' => 'Der Bezahlprozess wurde abgebrochen.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardMessageActionCancel',
                'value' => 'The payment process has been canceled.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardMessageActionUndefined',
                'value' => 'Zeitüberschreitung während des Bezahlprozesses. Bitte versuchen Sie es erneut.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardMessageActionUndefined',
                'value' => 'A timeout occurred during the payment process. Please try again.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardMessageActionFailure',
                'value' => 'W&auml;hrend des Bezahlprozesses ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut oder w&auml;hlen Sie ein anderes Zahlungsmittel.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardMessageActionFailure',
                'value' => 'An error occurred during the payment process. Please try again or choose a different payment method.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardMessageErrorInit',
                'value' => 'W&auml;hrend der Initialisierung des Bezahlprozesses ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut oder w&auml;hlen Sie ein anderes Zahlungsmittel.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardMessageErrorInit',
                'value' => 'An error occurred during the initialization of the payment process. Please try again or choose a different payment method.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardMessageErrorBankIdeal',
                'value' => 'Bitte w&auml;hlen Sie Ihre Bank aus.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardMessageErrorBankIdeal',
                'value' => 'Please select your bank.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardMessageErrorBankEPS',
                'value' => 'Bitte w&auml;hlen Sie Ihre Bank aus.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardMessageErrorBankEPS',
                'value' => 'Please select your bank.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardMessageNoPaymentdata',
                'value' => 'Die Zahlungsinformation fehlt.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardMessageNoPaymentdata',
                'value' => 'Payment information is missing.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard_finish',
                'locale' => '1',
                'name' => 'WirecardMessageActionPending',
                'value' => 'Ihre Zahlung wurde vom Finanzinstitut noch nicht best&auml;tigt.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard_finish',
                'locale' => '2',
                'name' => 'WirecardMessageActionPending',
                'value' => 'Your financial institution has not yet approved your payment.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardCheckoutSeamlessPayolutionTermsHeader',
                'value' => 'Payolution Konditionen'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardCheckoutSeamlessPayolutionTermsHeader',
                'value' => 'Payolution Terms'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardCheckoutSeamlessPayolutionConsent1',
                'value' => 'Mit der Übermittlung jener Daten an payolution, die für die Abwicklung von Zahlungen mit Kauf auf Rechnung und die Identitäts- und Bonitätsprüfung erforderlich sind, bin ich einverstanden. Meine '
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardCheckoutSeamlessPayolutionConsent1',
                'value' => 'I agree that the data which are necessary for the liquidation of purchase on account and which are used to complete the identy and credit check are transmitted to payolution. My '
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardCheckoutSeamlessPayolutionConsent2',
                'value' => ' kann ich jederzeit mit Wirkung für die Zukunft widerrufen.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardCheckoutSeamlessPayolutionConsent2',
                'value' => ' can be revoked at any time with effect for the future.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardCheckoutSeamlessPayolutionLink',
                'value' => 'Einwilligung'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardCheckoutSeamlessPayolutionLink',
                'value' => 'consent'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardCheckoutSeamlessBirthday',
                'value' => 'Geburtsdatum'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardCheckoutSeamlessBirthday',
                'value' => 'Date of birth'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardCheckoutSeamlessBirthdayInformation',
                'value' => 'Sie müssen mindestens 18 Jahre alt sein, um dieses Zahlungsmittel nutzen zu können.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardCheckoutSeamlessBirthdayInformation',
                'value' => 'You must be at least 18 years of age to use this payment method.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '1',
                'name' => 'WirecardCheckoutSeamlessPayolutionTermsAccept',
                'value' => 'Bitte akzeptieren Sie die payolution Konditionen.'
            ),
            array(
                'namespace' => 'frontend/checkout/wirecard',
                'locale' => '2',
                'name' => 'WirecardCheckoutSeamlessPayolutionTermsAccept',
                'value' => 'Please accept the payolution terms.'
            )
        );
    }

    /**
     * Returns internal name of payment methods defined by Client library
     *
     * @see getPaymentMethods()
     *
     * @param string $type
     *
     * @return string
     * @throws Enlight_Exception
     */
    public function getPaymentMethod($type = '')
    {
        if ('' == trim($type)) {
            throw new Enlight_Exception('Payment type not defined');
        }
        foreach ($this->getPaymentMethods() as $parameter) {
            if (0 == strcmp($parameter['name'], $type)) {
                return $parameter['call'];
            }
        }
        return null;
    }

    /**
     * Returns individual parameters for each payment method
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        $pm = array();
        $pm[] = array(
            'name' => 'ccard',
            'description' => 'Kreditkarte',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::CCARD,
            'translation' => Array('description' => 'Wirecard Credit Card', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'maestro',
            'description' => 'Maestro SecureCode',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::MAESTRO,
            'translation' => Array('description' => 'Wirecard Maestro SecureCode', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'eps',
            'description' => 'eps Online-&Uuml;berweisung',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::EPS,
            'translation' => Array('description' => 'Wirecard eps Online Bank Transfer', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'ideal',
            'description' => 'iDEAL',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::IDL,
            'translation' => Array('description' => 'Wirecard iDEAL', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'giropay',
            'description' => 'giropay',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::GIROPAY,
            'translation' => Array('description' => 'Wirecard giropay', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'sofortueberweisung',
            'description' => 'SOFORT &Uuml;berweisung',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::SOFORTUEBERWEISUNG,
            'translation' => Array('description' => 'Wirecard SOFORT banking', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'bancontact_mistercash',
            'description' => 'Bancontact',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::BMC,
            'translation' => Array('description' => 'Wirecard Bancontact', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'przelewy24',
            'description' => 'Przelewy24',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::P24,
            'translation' => Array('description' => 'Wirecard Przelewy24', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'moneta',
            'description' => 'moneta.ru',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::MONETA,
            'translation' => Array('description' => 'Wirecard moneta.ru', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'poli',
            'description' => 'POLi',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::POLI,
            'translation' => Array('description' => 'Wirecard POLi', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'pbx',
            'description' => 'paybox',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::PBX,
            'translation' => Array('description' => 'Wirecard paybox', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'psc',
            'description' => 'paysafecard',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::PSC,
            'translation' => Array('description' => 'Wirecard paysafecard', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'paypal',
            'description' => 'PayPal',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::PAYPAL,
            'translation' => Array('description' => 'Wirecard PayPal', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'sepa-dd',
            'description' => 'SEPA Lastschrift',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::SEPADD,
            'translation' => Array('description' => 'Wirecard SEPA Direct Debit', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'invoice',
            'description' => 'Kauf auf Rechnung',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::INVOICE,
            'translation' => Array('description' => 'Wirecard Invoice', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'installment',
            'description' => 'Kauf auf Raten',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::INSTALLMENT,
            'translation' => Array('description' => 'Wirecard Installment', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'skrillwallet',
            'description' => 'Skrill Digital Wallet',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::SKRILLWALLET,
            'translation' => Array('description' => 'Wirecard Skrill Digital Wallet', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'ekonto',
            'description' => 'eKonto',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::EKONTO,
            'translation' => Array('description' => 'Wirecard eKonto', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'trustly',
            'description' => 'Trustly',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::TRUSTLY,
            'translation' => Array('description' => 'Wirecard Trustly', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'ccard-moto',
            'description' => 'Kreditkarte - Post / Telefonbestellung',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::CCARD_MOTO,
            'translation' => Array('description' => 'Wirecard Credit Card - Mail Order and Telephone Order', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'tatrapay',
            'description' => 'TatraPay',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::TATRAPAY,
            'translation' => Array('description' => 'Wirecard TatraPay', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'epay',
            'description' => 'ePay.bg',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::EPAYBG,
            'translation' => Array('description' => 'Wirecard ePay.bg', 'additionalDescription' => '')
        );
        $pm[] = array(
            'name' => 'voucher',
            'description' => 'Gutschein',
            'template' => 'wirecard_brands.tpl',
            'call' => WirecardCEE_QMore_PaymentType::VOUCHER,
            'translation' => Array('description' => 'Wirecard Voucher', 'additionalDescription' => '')
        );

        return $pm;
    }

    /**
     * Set root directory of this plugin
     *
     * @param string $dir
     */
    public function setPluginRoot($dir = '')
    {
        $this->pluginRoot = $dir;
    }

    /**
     * Return root directory of this plugin
     *
     * @return string
     */
    public function getPluginRoot()
    {
        return $this->pluginRoot;
    }

    /**
     * Returns value of given plugin configure parameter
     *
     * @param string $var
     *
     * @return string
     * @throws Enlight_Exception
     */
    public function __get($var = null)
    {
        static $config = null;
        if (is_null($config)) {
            $config = Shopware()->Plugins()
              ->Frontend()
              ->WirecardCheckoutSeamless()
              ->pluginConfig();
        }
        $var = strtoupper($var);
        if (isset($config->$var)) {
            return $config->$var;
        } else if($var == 'SHOPID' || $var == 'IFRAME_CSS_URL') {
            //optional field shopId would cause exception if not configured
            return '';
        } else {
            throw new Enlight_Exception('No config variable ' . $var . ' found');
        }
    }

    /**
     * Customer will be informed by Wirecard via e-mail
     * This must be configured in Wirecard backend
     *
     * @return bool
     */
    public function sendConfirmationOfPaymentMail()
    {
        return (1 == $this->CONFIRM_MAIL);
    }

    /**
     * Different prefixes for Wirecard CEE payment methods
     *
     * @param
     *            $type
     *
     * @return string
     */
    public function getPrefix($type)
    {
        switch ($type) {
            case 'description':
                return 'Wirecard ';
            case 'name':
                return 'wirecard_';
        }
    }

    /**
     * Returns WirecardCheckoutSeamless Frontend URL for POST-Request
     *
     * @return string
     */
    public function getWirecardCheckoutSeamlessFrontendInitURL()
    {
        return 'https://secure.wirecard-cee.com/qmore/frontend/init';
    }

    /**
     * Return the version of this plugin, defined in Bootstrap
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return Shopware()->Plugins()
          ->Frontend()
          ->WirecardCheckoutSeamless()
          ->getVersion();
    }

    /**
     * Returns static WirecardCheckoutSeamless parameter
     *
     * @return array
     */
    public function wirecardCheckoutSeamlessParameters()
    {
        return array(
            'setDuplicateRequestCheck' => false
        );
    }

    /**
     * Returns true if auto deposit is enabled
     *
     * @return bool
     */
    public function getAutoDeposit()
    {
        return (1 == $this->AUTO_DEPOSIT);
    }

    /**
     * Returns true if wirecard confirm mail is enabled
     *
     * @return bool
     */
    public function setConfirmMail()
    {
        return (1 == $this->CONFIRM_MAIL);

    }

    /**
     * Returns ID of given state
     *
     * @param string $status
     *
     * @return int
     */
    public function getPaymentStatusId($status = '')
    {
        switch ($status) {
            case 'checkup':
            case 'failure':
                return 21;
            case 'success':
                return 12;
            case 'pending':
                return 19;
            case 'reserved':
                return 18;
        }
    }

    /**
     * Returns array of parameters of all payment methods
     * or of the payment method with the give ID
     *
     * @param int $id
     *
     * @return array
     */
    public function getPaymentMethodName($id = 0)
    {
        $cacheId = 'wirecardcheckoutseamless_paymentmethods';
        if (Shopware()->Cache()->test($cacheId)) {
            $paymentmeans = Shopware()->Cache()->load($cacheId);
        } else {
            $sql = Shopware()->Db()
              ->select()
              ->from(
                  's_core_paymentmeans',
                  array(
                       'id',
                       'name'
                  )
              );
            $paymentmeans = Shopware()->Db()->fetchPairs($sql);
            Shopware()->Cache()->save(
                $paymentmeans,
                $cacheId,
                array(
                     'Shopware_Plugin'
                ),
                86400
            );
        }
        return (isset($paymentmeans[$id])) ? $paymentmeans[$id] : $paymentmeans;
    }

    /**
     * Returns static parameters of the given payment method
     *
     * @see getPaymentMethods()
     *
     * @param string $name
     *
     * @return string
     */
    public function getPaymentMethodId($name = '')
    {
        return array_search($name, $this->getPaymentMethods());
    }

    /**
     * Returns TRUE if the currently by customer selected payment method
     * require additional information (seamless or financial institution)
     *
     * @return bool
     */
    public function hasPaymentMethodAdditionalInformations()
    {
        return in_array(
            $this->getUser('payment')->name,
            $this->getPaymentsWithAdditionalData()
        );
    }


    /**
     * Returns TRUE if return values by WirecardCheckoutSeamless should be saved
     *
     * @return bool
     */
    public function saveReturnValues()
    {
        $saveresponse = $this->WIRECARD_SAVERESPONSE;
        return $saveresponse;
    }

    /**
     * Returns database field for WirecardCheckoutSeamless return values
     *
     * @return string
     */
    public function getReturnField()
    {
        switch ($this->WIRECARD_SAVERESPONSE) {
            case 2:
                return 'internalcomment';
            case 3:
                return 'attribute1';
            case 4:
                return 'attribute2';
            case 5:
                return 'attribute3';
            case 6:
                return 'attribute4';
            case 7:
                return 'attribute5';
            case 8:
                return 'attribute6';
        }
    }

    /**
     * Returns array with payment methods which required additional data
     *
     * @return array
     */
    public function getPaymentsWithAdditionalData()
    {
        return array_merge(
            self::$paymentsSeamless,
            self::$paymentsFinancialInstitution
        );
    }

    /**
     * Returns seamless payment methods
     *
     * @return array
     */
    public function getPaymentsSeamless()
    {
        return self::$paymentsSeamless;
    }

    /**
     * Returns payment methods with required financial institutions
     *
     * @return array
     */
    public function getPaymentsFinancialInstitution()
    {
        return self::$paymentsFinancialInstitution;
    }

    /**
     * @return string
     */
    public function setAsTransactionID()
    {

        switch($this->USE_AS_TRANSACTION_ID) {
            case 2:
                return 'gatewayReferenceNumber';
                break;
            case 1:
            default:
                return 'orderNumber';
                break;
        }
    }
}