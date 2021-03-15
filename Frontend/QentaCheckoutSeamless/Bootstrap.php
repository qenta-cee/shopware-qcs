<?php
/**
 * Shop System Plugins - Terms of use
 *
 * This terms of use regulates warranty and liability between
 * Qenta Central Eastern Europe (subsequently referred to as WDCEE)
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

require_once __DIR__ . '/Components/CSRFWhitelistAware.php';

/**
 * QentaCheckoutSeamless Bootstrap class
 *
 * This class is hooking into the bootstrap mechanism of Shopware.
 */
class Shopware_Plugins_Frontend_QentaCheckoutSeamless_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{

    /**
     * Name of payment controller
     * needed for several URLs
     *
     * @var string
     */
    const CONTROLLER = 'QentaCheckoutSeamless';

    /**
     * Starting position for Wireqrd CEE payment methods
     */
    const STARTPOSITION = 50;

    /**
     * Plugin name
     */
    const NAME = 'Shopware_5.QentaCheckoutSeamless';

    public function getCapabilities()
    {
        return array(
            'install' => true,
            'enable' => true,
            'update' => true,
            'secureUninstall' => true
        );
    }

    /**
     * Returns the version of plugin as string.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.10.15';
    }

    /**
     * Returns the label of the plugin as string
     *
     * @return string
     */
    public function getLabel()
    {
        return "Qenta Checkout Seamless";
    }

    /**
     * Informations about this plugin
     *
     * @return array
     */
    public function getInfo()
    {
    	$shopversion = Shopware::VERSION;

	    // In Shopware 5.2.22 there is no possibility getting shopware version
    	if ( ! strlen($shopversion)) {
    		$shopversion = '>5.2.21';
	    }

        $language = Shopware()->Locale()->getLanguage();

        switch ($language) {
            case 'en':
                $copLink = '<a href="https://checkoutportal.com/gb/shopware/" target="_blank">Qenta Checkout Portal</a>';
                break;
            case 'it':
                $copLink = '<a href="https://checkoutportal.com/it/shopware/" target="_blank">Qenta Checkout Portal</a>';
                break;
            case 'nl':
                $copLink = '<a href="https://checkoutportal.com/nl/shopware/" target="_blank">Qenta Checkout Portal</a>';
                break;
            case 'de':
            default:
                $copLink = '<a href="https://checkoutportal.com/de/shopware/" target="_blank">Qenta Checkout Portal</a>';
                break;
        }

        $image = dirname(__FILE__) . '/qenta-logo.png';
        $imageData = base64_encode(file_get_contents($image));

        $src = 'data: '.mime_content_type($image).';base64,'.$imageData;

        return array(
            'version' => $this->getVersion(),
            'autor' => 'Qenta',
            'copyright' => 'Qenta',
            'label' => $this->getLabel(),
            'support' => 'http://www.wirecard.at/en/get-in-contact/',
            'link' => 'http://www.wirecard.at',
            'description' => '<img src="'.$src.'" /><div style="line-height: 1.6em"><h3>QENTA - YOUR FULL SERVICE PAYMENT PROVIDER - COMPREHENSIVE SOLUTIONS FROM ONE SINGLE SOURCE</h3>'
                . '<p>' . file_get_contents(dirname(__FILE__) . '/info.txt') . '</p>'
                . '<p>If you have no Qenta account, please register yourself via ' . $copLink . '.</p></div>'
        );
    }

    /**
     * @return array
     * @throws Enlight_Exception
     */
    public function install()
    {
        self::init();

        $this->createEvents();
        $this->createPayments();
        $this->createForm();
        $this->createTranslations();

        foreach (Shopware()->QentaCheckoutSeamless()->Config()->getDbTables() as $sql) {
            Shopware()->Db()->exec($sql);
        }
        $info = Shopware()->Db()->describeTable('qenta_checkout_seamless');
        if (!isset($info['session'])) {
            Shopware()->Db()->exec('ALTER TABLE qenta_checkout_seamless ADD COLUMN session MEDIUMTEXT NULL');
        } else {
            if ($info['session']['DATA_TYPE'] !== 'mediumtext') {
                Shopware()->Db()->exec('ALTER TABLE qenta_checkout_seamless MODIFY session MEDIUMTEXT');
            }
        }
        if (!isset($info['remoteAddr'])) {
            Shopware()->Db()->exec('ALTER TABLE qenta_checkout_seamless ADD COLUMN remoteAddr VARCHAR(80) NULL ');
        } else {
            if ($info['remoteAddr']['DATA_TYPE'] !== 'varchar') {
                Shopware()->Db()->exec('ALTER TABLE qenta_checkout_seamless MODIFY remoteAddr VARCHAR(80)');
            }
        }

        return array(
            'success' => true,
            'invalidateCache' => array('frontend', 'config', 'template', 'theme')
        );
    }

    /**
     * This derived method is called automatically each time the plugin will be reinstalled
     * (does not delete databases)
     *
     * @return array
     */
    public function secureUninstall()
    {
        /** @var \Shopware\Components\CacheManager $cacheManager */
        $cacheManager = $this->get('shopware.cache_manager');
        $cacheManager->clearThemeCache();

        return array(
            'success' => true,
            'invalidateCache' => array('frontend', 'config', 'template', 'theme')
        );
    }

    /**
     * This derived method is called automatically each time the plugin will be uninstalled
     *
     * @return array
     */
    public function uninstall()
    {
        //TODO: uninstall Routine.. remove translations, remove snippets
        try {
            Shopware()->Db()->delete('s_core_paymentmeans', 'pluginID = ' . (int)$this->getId());
            Shopware()->Db()->delete('s_crontab', 'pluginID = ' . (int)$this->getId());

        } catch (Exception $e) {
            Shopware()->Pluginlogger()->error('QentaCheckoutSeamless: delete failed: ' . $e->getMessage(),
                'ERROR'
            );
        }

        return $this->secureUninstall();

    }

    public function update($version)
    {
        if (version_compare($version, '1.7.0', '<=')) {
            //removing paymentType click2pay
            Shopware()->Db()->delete('s_core_paymentmeans', 'name = "qenta_c2p"');
        }

        if (version_compare($version, '1.8.1', '<=')) {
            //removing old logging method
            $em = $this->get('models');
            $form = $this->Form();
            $qenta_log = $form->getElement('QENTA_LOG');
            if ($qenta_log !== null) {
                $em->remove($qenta_log);
            }
            $qenta_delete_log = $form->getElement('DELETELOG');
            if ($qenta_delete_log !== null) {
                $em->remove($qenta_delete_log);

            }
            $em->flush();
        }

        if (version_compare($version, '1.10.0', '<=')) {
            Shopware()->Db()->delete('s_core_paymentmeans', 'name = "qenta_quick"');
            Shopware()->Db()->delete('s_core_paymentmeans', 'name = "qenta_elv"');
            Shopware()->Db()->delete('s_core_paymentmeans', 'name = "qenta_mpass"');
            Shopware()->Db()->delete('s_core_paymentmeans', 'name = "qenta_skrilldirect"');

            $em = $this->get('models');
            $form = $this->Form();
            $qenta_keep_orders = $form->getElement('KEEP_UNSUCCESSFUL_ORDERS');
            if ($qenta_keep_orders !== null) {
                $em->remove($qenta_keep_orders);
            }
            $qenta_restore_basket = $form->getElement('RESTORE_BASKET');
            if ($qenta_restore_basket !== null) {
                $em->remove($qenta_restore_basket);
            }
            $qenta_shop_prefix = $form->getElement('SHOP_PREFIX');
            if ($qenta_shop_prefix !== null) {
                $em->remove($qenta_shop_prefix);
            }
            $em->flush();
        }

        return $this->install();
    }

    /**
     * Plugin configuration form
     */
    public function createForm()
    {
        $form = $this->Form();
        $i = 0;

        $form->setElement(
            'text',
            'CUSTOMERID',
            array(
                'label' => 'Kundennummer',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'description' => 'Ihre Qenta-Kundennummer (customerId, im Format D2#####)',
                'required' => true,
                'order' => ++$i
            )
        );

        $form->setElement(
            'text',
            'SHOPID',
            array(
                'label' => 'Shop ID',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'description' => 'Shop-Kennung bei mehreren Onlineshops (Testmodus: seamless)',
                'order' => ++$i
            )
        );

        $form->setElement(
            'text',
            'SECRET',
            array(
                'label' => 'Secret',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'description' => 'Geheime Zeichenfolge, die Sie von Qenta erhalten haben, zum Signieren und Validieren von Daten zur Prüfung der Authentizität (Testmodus: B8AKTPWBRMNBV455FG6M2DANE99WU2).',
                'required' => true,
                'order' => ++$i
            )
        );

        $form->setElement(
            'text',
            'SERVICE_URL',
            array(
                'label' => 'URL zur Impressum-Seite',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'description' => 'URL auf der Bezahlseite, die zur Impressum-Seite des Onlineshops führt.',
                'required' => true,
                'order' => ++$i
            )
        );

        $form->setElement(
            'checkbox',
            'CONFIRM_MAIL',
            array(
                'label' => 'Benachrichtigungsmail',
                'value' => 0,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'description' => 'Benachrichtigung per E-Mail über Zahlungen Ihrer Kunden, falls ein Kommunikationsproblem zwischen Qenta und Ihrem Onlineshop aufgetreten ist. Bitte kontaktieren Sie unsere Sales-Teams um dieses Feature freizuschalten.',
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'checkbox',
            'PCI3_DSS_SAQ_A_ENABLE',
            array(
                'label' => 'SAQ A konform',
                'value' => 0,
                'description' => 'Falls "Nein" gesetzt ist, gilt der strengere SAQ A-EP. Falls "Ja" gesetzt ist, wird in Qenta Checkout Seamless das "PCI DSS SAQ A Compliance"-Feature verwendet und es gilt der SAQ A.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'text',
            'IFRAME_CSS_URL',
            array(
                'label' => 'iFrame CSS-URL',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'description' => 'Vollständige URL auf eine CSS-Datei, um die iFrame-Eingabefelder anzupassen, wenn das "PCI DSS SAQ A Compliance"-Feature verwendet wird.',
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'checkbox',
            'CREDITCARD_SHOWCARDHOLDER',
            array(
                'label' => 'Feld für Karteninhaber anzeigen',
                'value' => 1,
                'description' => 'Anzeige des Feldes zur Eingabe des Kreditkarteninhabers im Formular während des Bezahlprozesses.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'checkbox',
            'CREDITCARD_SHOWCVC',
            array(
                'label' => 'Feld für CVC anzeigen',
                'value' => 1,
                'description' => 'Anzeige des Feldes zur Eingabe der Kreditkartenprüfnummer (CVC) im Formular während des Bezahlprozesses.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'checkbox',
            'CREDITCARD_SHOWISSUEDATE',
            array(
                'label' => 'Feld für Ausgabedatum anzeigen',
                'value' => 0,
                'description' => 'Anzeige des Feldes zur Eingabe des Kreditkarten-Ausgabedatums im Formular während des Bezahlprozesses.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'checkbox',
            'CREDITCARD_SHOWISSUENUMBER',
            array(
                'label' => 'Feld für Ausgabennummer anzeigen',
                'value' => 0,
                'description' => 'Anzeige des Feldes zur Eingabe der Kreditkarten-Ausgabenummer im Formular während des Bezahlprozesses.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'checkbox',
            'AUTO_DEPOSIT',
            array(
                'label' => 'Automatisches Abbuchen',
                'value' => 0,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'description' => 'Automatisches Abbuchen der Zahlungen. Bitte kontaktieren Sie unsere Sales-Teams um dieses Feature freizuschalten.',
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'checkbox',
            'SEND_ADDITIONAL_DATA',
            array(
                'label' => 'Verrechnungsdaten des Konsumenten mitsenden',
                'value' => 0,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'description' => 'Weiterleitung der Rechnungs- und Versanddaten des Kunden an den Finanzdienstleister.',
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'checkbox',
            'SEND_BASKET_DATA',
            array(
                'label' => 'Warenkorbdaten des Konsumenten mitsenden',
                'value' => 0,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'description' => 'Weiterleitung des Warenkorbs des Kunden an den Finanzdienstleister.',
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'select',
            'QENTA_SAVERESPONSE',
            array(
                'label' => 'Speichern der Bezahlprozess-Ergebnisse',
                'value' => 1,
                'store' => array(
                    array(1, 'Do not save'),
                    array(2, 'Internal commentfield'),
                    array(3, 'free text 1'),
                    array(4, 'free text 2'),
                    array(5, 'free text 3'),
                    array(6, 'free text 4'),
                    array(7, 'free text 5'),
                    array(8, 'free text 6'),
                ),
                'description' => 'Speichern aller Ergebnisse des Bezahlprozesses, d.h. jedes Aufrufs des Qenta Checkout Servers der Bestätigungs-URL.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'select',
            'USE_AS_TRANSACTION_ID',
            array(
                'label' => 'Shopware transaction ID',
                'value' => 1,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'store' => array(
                    array(1, 'Qenta order number'),
                    array(2, 'Gateway reference number')
                ),
                'description' => 'Als Shopware Transaction ID wird entweder die shopinterne Bestellnummer oder die Referenznummer des Acquirers verwendet.',
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'select',
            'QENTA_CONFIRM_HEADER_STYLE',
            array(
                'label' => 'Headerstyle',
                'value' => 1,
                'store' => array(
                    array(1, 'Fat'),
                    array(2, 'Slim'),
                ),
                'description' => 'Style des Header beim letzten Schritt in der Bezahlung.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'checkbox',
            'SEND_PENDING_MAILS',
            array(
                'label' => 'Mail für Pendingstatus versenden',
                'value' => 0,
                'description' => 'Falls "Ja" gesetzt ist, werden Mails zu noch nicht bestätigten Zahlungen verschickt.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'checkbox',
            'ENABLE_DUPLICATE_REQUEST_CHECK',
            array(
                'label' => 'Überprüfung auf doppelte Anfragen',
                'value' => 0,
                'description' => 'Überprüfung auf mehrfache Anfragen seitens Ihres Kunden.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'checkbox',
            'PAYOLUTION_TERMS',
            array(
                'label' => 'Payolution Kondition',
                'value' => 1,
                'description' => 'Anzeige der Checkbox mit den payolution-Bedingungen, die vom Kunden während des Bezahlprozesses bestätigt werden müssen, wenn Ihr Onlineshop als "Trusted Shop" zertifiziert ist.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'text',
            'PAYOLUTION_MID',
            array(
                'label' => 'Payolution mID',
                'value' => '',
                'description' => 'payolution-Händler-ID, bestehend aus dem Base64-enkodierten Firmennamen, die für den Link "Einwilligen" gesetzt werden kann.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'select',
            'INVOICE_PROVIDER',
            array(
                'label' => 'Provider für Kauf auf Rechnung',
                'value' => 'payolution',
                'store' => array(
                    array('payolution', 'payolution'),
                    array('ratepay', 'RatePay'),
                    array('qenta', 'Qenta')
                ),
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'select',
            'INVOICE_CURRENCY',
            array(
                'label' => 'Akzeptierte Währungen für Kauf auf Rechnung',
                'value' => '',
                'store' => 'base.Currency',
                'valueField' => 'currency',
                'multiSelect' => true,
                'description' => 'Bitte wählen Sie mindestens eine gültige Währung für Kauf auf Rechnung.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'select',
            'INSTALLMENT_PROVIDER',
            array(
                'label' => 'Provider für Kauf auf Raten',
                'value' => 'payolution',
                'store' => array(
                    array('payolution', 'payolution'),
                    array('ratepay', 'RatePay')
                ),
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'select',
            'INSTALLMENT_CURRENCY',
            array(
                'label' => 'Akzeptierte Währungen für Kauf auf Raten',
                'value' => '',
                'store' => 'base.Currency',
                'valueField' => 'currency',
                'multiSelect' => true,
                'description' => 'Bitte wählen Sie mindestens eine gültige Währung für Kauf auf Raten.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'checkbox',
            'BASKET_RESERVE',
            array(
                'label' => 'Warenkorb Reservierung',
                'value' => 0,
                'description' => 'Artikel während des Zahlungsprozesses reservieren. Lagerbestand wird reduziert.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => false,
                'order' => ++$i
            )
        );
    }

    /**
     * addes the translations for admin interface to the database
     *
     * used in install but also could be used from outside later on.
     *
     * @return void
     */
    public function createTranslations()
    {
        $form = $this->Form();
        $translations = Array(
            'en_GB' => Array(
                'CUSTOMERID' => Array(
                    'label' => 'Customer ID',
                    'description' => 'Customer number you received from Qenta (Test mode: D200001).'
                ),
                'SHOPID' => Array(
                    'label' => 'Shop ID',
                    'description' => 'Shop identifier in case of more than one shop (test mode: seamless).'
                ),
                'SECRET' => Array(
                    'label' => 'Secret',
                    'description' => 'String which you received from Qenta for signing and validating data to prove their authenticity (test mode: B8AKTPWBRMNBV455FG6M2DANE99WU2).'
                ),
                'SERVICE_URL' => Array(
                    'label' => 'URL to imprint page',
                    'description' => 'URL on the payment page which leads to the imprint page of the online shop.'
                ),
                'CONFIRM_MAIL' => Array(
                    'label' => 'Notification e-mail',
                    'description' => 'Receiving notification by e-mail regarding the orders of your consumers if an error occurred in the communication between Qenta and your online shop. Please contact our sales teams to activate this feature.'
                ),
                'PAYOLUTION_TERMS' => Array(
                    'label' => 'Payolution terms',
                    'description' => 'If your online shop is certified by "Trusted Shops", display the corresponding checkbox with payolution terms for the consumer to agree with during the checkout process.'
                ),
                'PAYOLUTION_MID' => Array(
                    'label' => 'Payolution mID',
                    'description' => 'Your payolution merchant ID consisting of the base64-encoded company name which is used in the link for "consent" to the payolution terms.'
                ),
                'INVOICE_PROVIDER' => Array(
                    'label' => 'Invoice Provider'
                ),
                'INVOICE_CURRENCY' => Array(
                    'label' => 'Accepted currencies for Invoice',
                    'description' => 'Please select at least one currency to use Invoice.'
                ),
                'INSTALLMENT_PROVIDER' => Array(
                    'label' => 'Installment Provider'
                ),
                'INSTALLMENT_CURRENCY' => Array(
                    'label' => 'Accepted currencies for Installment',
                    'description' => 'Please select at least one currency to use Installment.'
                ),
                'PCI3_DSS_SAQ_A_ENABLE' => Array(
                    'label' => 'SAQ A compliance',
                    'description' => 'Selecting "No", the stringent SAQ A-EP is applicable. Selecting "Yes", Qenta Checkout Seamless is integrated with the "PCI DSS SAQ A Compliance" feature and SAQ A is applicable.'
                ),
                'IFRAME_CSS_URL' => Array(
                    'label' => 'Iframe CSS-URL',
                    'description' => 'Entry of a full URL to a CSS file in order to customize the iframe input fields when the "PCI DSS SAQ A Compliance" feature is used.'
                ),
                'CREDITCARD_SHOWCVC' => Array(
                    'label' => 'Display CVC field',
                    'description' => 'Display input field to enter the CVC in your credit card form during the checkout process.'
                ),
                'CREDITCARD_SHOWCARDHOLDER' => Array(
                    'label' => 'Display card holder field',
                    'description' => 'Display input field to enter the card holder name in your credit card form during the checkout process.'
                ),
                'CREDITCARD_SHOWISSUEDATE' => Array(
                    'label' => 'Display issue date field',
                    'description' => 'Display input field to enter the credit card issue date in your credit card form during the checkout process. Some credit cards do not have an issue date.'
                ),
                'CREDITCARD_SHOWISSUENUMBER' => Array(
                    'label' => 'Display issue number field',
                    'description' => 'Display input field to enter the credit card issue number in your credit card form during the checkout process. Some credit cards do not have an issue number.'
                ),
                'AUTO_DEPOSIT' => Array(
                    'label' => 'Automated deposit',
                    'description' => 'Enabling an automated deposit of payments. Please contact our sales teams to activate this feature.'
                ),
                'SEND_ADDITIONAL_DATA' => Array(
                    'label' => 'Forward consumer data',
                    'description' => 'Forwarding shipping and billing data about your consumer to the respective financial service provider.'
                ),
                'SEND_BASKET_DATA' => Array(
                    'label' => 'Forward basket data',
                    'description' => 'Forwarding basket data to the respective financial service provider.'
                ),
                'QENTA_SAVERESPONSE' => Array(
                    'label' => 'Save payment process results',
                    'description' => 'Save all results regarding the payment process, i.e. each Qenta Checkout Server response to the confirmation URL to the defined field.'
                ),
                'QENTA_CONFIRM_HEADER_STYLE' => Array(
                    'label' => 'Header style',
                    'description' => 'Style of header within the last step in payment process.'
                ),
                'SEND_PENDING_MAILS' => Array(
                    'label' => 'Send Pendingstate mails',
                    'description' => 'Selecting "Yes", mails will be sent for pending orders'
                ),
                'ENABLE_DUPLICATE_REQUEST_CHECK' => Array(
                    'label' => 'Check for duplicate requests',
                    'description' => 'Checking duplicate requests made by your consumer.'
                ),
                'BASKET_RESERVE' => Array(
                    'label' => 'Basket items reservation',
                    'description' => 'Reserve basket items during checkout process. Items are reduced from stock.'
                )
            )
        );
        $shopRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');
        foreach ($translations as $locale => $snippets) {
            $localeModel = $shopRepository->findOneBy(array('locale' => $locale));
            if ($localeModel === null) {
                continue;
            }
            foreach ($snippets AS $element => $snippet) {
                $elementModel = $form->getElement($element);
                if ($elementModel === null) {
                    continue;
                }
                $translationModel = new \Shopware\Models\Config\ElementTranslation();
                $translationModel->setLocale($localeModel);
                if (array_key_exists('label', $snippet)) {
                    $translationModel->setLabel($snippet['label']);
                }
                if (array_key_exists('description', $snippet)) {
                    $translationModel->setDescription($snippet['description']);
                }
                //no translations set yet. we can add new translations
                if (!$elementModel->hasTranslations()) {
                    $elementModel->addTranslation($translationModel);
                }
            }
        }
    }

    /**
     * subscribe to several events
     */
    protected function createEvents()
    {
        // Returns pamynt controller path
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_' . self::CONTROLLER,
            'onGetControllerPathFrontend'
        );

        // Display additional data on checkout confirm page
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch',
            'onPostDispatch'
        );

        // Save selected POST parameters (financial institutions)
        $this->subscribeEvent(
            'Enlight_Controller_Action_PreDispatch',
            'onPreDispatch'
        );

        $this->subscribeEvent(
            'Shopware_Controllers_Backend_OrderState_Notify',
            'sendStateNotify'
        );

        // Subscribe the needed event for less merge and compression
        $this->subscribeEvent(
            'Theme_Compiler_Collect_Plugin_Less',
            'addLessFiles'
        );

        $this->subscribeEvent(
            'Theme_Compiler_Collect_Plugin_Javascript',
            'addJsFiles'
        );

        // Prevent ordermail after pending
        $this->subscribeEvent(
            'Shopware_Modules_Order_SendMail_Send',
            'defineSending'
        );

    }

    /**
     * Provide the file collection for less
     *
     * @param Enlight_Event_EventArgs $args
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function addLessFiles(Enlight_Event_EventArgs $args)
    {
        $less = new \Shopware\Components\Theme\LessDefinition(
        //configuration
            array(),

            //less files to compile
            array(
                __DIR__ . '/Views/responsive/frontend/_public/src/less/all.less'
            ),

            //import directory
            __DIR__
        );

        return new Doctrine\Common\Collections\ArrayCollection(array($less));
    }

    /**
     * Provide the file collection for js files
     *
     * @param Enlight_Event_EventArgs $args
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function addJsFiles(Enlight_Event_EventArgs $args)
    {
        $jsFiles = array(
            __DIR__ . '/Views/responsive/frontend/_public/src/js/qenta_seamless.js'
        );
        return new Doctrine\Common\Collections\ArrayCollection($jsFiles);
    }

    /**
     * Create and save payment methods
     */
    protected function createPayments()
    {
        $prefixDescription = Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Config::getSingleton()
            ->getPrefix('description');
        $prefixName = Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Config::getSingleton()->getPrefix(
            'name'
        );

        $translation = new Shopware_Components_Translation();
        $aTranslations = array();
        foreach (Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Config::getSingleton()->getPaymentMethods() as $pos => $pm) {
            $oPayment = $this->Payments()->findOneBy(array('name' => $prefixName . $pm['name']));
            if (!$oPayment) {
                $payment = array(
                    'name' => $prefixName . $pm['name'],
                    'description' => $prefixDescription . $pm['description'],
                    'action' => self::CONTROLLER,
                    'active' => (isset($pm['active'])) ? (int)$pm['active'] : 0,
                    'position' => self::STARTPOSITION + $pos,
                    'pluginID' => $this->getId(),
                    'additionalDescription' => $pm['additionalDescription']
                );
                if (isset($pm['template']) && !is_null($pm['template'])) {
                    $payment['template'] = $pm['template'];
                }
                $oPayment = $this->createPayment($payment);
            } else {
                if (isset($pm['template']) && !is_null($pm['template'])) {
                    $oPayment->setTemplate($pm['template']);
                }
                if (isset($pm['additionalDescription']) && $pm['additionalDescription'] != '') {
                    $additional = $oPayment->getAdditionalDescription();
                    if ( $additional === '' ) {
                        if ($oPayment->getTemplate() == 'qenta_brands.tpl') {
                            $oPayment->setTemplate(null);
                        }
                        $oPayment->setAdditionalDescription($pm['additionalDescription']);
                    }
                }
            }

            $aTranslations[$oPayment->getId()] = $pm['translation'];
        }
        $translation->write(2, 'config_payment', 1, $aTranslations, 0);
    }

    /**
     * Shopware 4 compatibility mode
     *
     * @see Config.php
     */
    public function pluginConfig()
    {
        return $this->Config();
    }

    /**
     * Initial parameters called by bootstrap and controller
     *
     * @return Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Resources
     */
    public static function init()
    {
        // Register resource QentaCheckoutSeamless
        // The instance is available with Shopware()->QentaCheckoutSeamless()
        if (!Shopware()->Bootstrap()->issetResource('QentaCheckoutSeamless')) {
            Shopware()->Bootstrap()->registerResource(
                'QentaCheckoutSeamless',
                Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Resources::getSingleton()
            );
        }

        // Register root directory for this plugin
        Shopware()->QentaCheckoutSeamless()->Config()->setPluginRoot(
            substr(dirname(__FILE__), 1 + strlen($_SERVER['DOCUMENT_ROOT']))
        );
    }

    public function afterInit()
    {
        $this->registerCustomModels();
        $this->get('Loader')->registerNamespace('Shopware\\Plugins\\QentaCheckoutSeamless', $this->Path());
        $this->get('Loader')->registerNamespace('QentaCEE', $this->Path() . 'Components/QentaCEE/');
    }

    /**
     * Event listener method
     *
     * @param Enlight_Event_EventArgs $args
     * @return string
     */
    public static function onGetControllerPathFrontend(Enlight_Event_EventArgs $args)
    {
        Shopware_Plugins_Frontend_QentaCheckoutSeamless_Bootstrap::init();
        Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/');
        return dirname(__FILE__) . '/Controllers/Frontend/' . self::CONTROLLER . '.php';
    }

    /**
     * return encoded mId for PayolutionLink
     *
     * @return string
     */
    public function getPayolutionLink()
    {
        $mid = Shopware()->QentaCheckoutSeamless()->Config()->PAYOLUTION_MID;
        if (strlen($mid) === 0) {
            return false;
        }

        $mId = urlencode(base64_encode($mid));

        return $mId;
    }

    /**
     * set confirmmail after ordercreation false (only for QentaCheckoutSeamless)
     * @param Enlight_Event_EventArgs $args
     * @return bool
     */
    public function defineSending(Enlight_Event_EventArgs $args)
    {
        $userData = Shopware()->Session()->sOrderVariables['sUserData'];
        $additional = $userData['additional'];
        $paymentaction = $additional['payment']['action'];
        $sPaymentstate = Shopware()->Session()->sPaymentstate;

        //only prevent confirmationmail for QentaCheckoutSeamless payment action
        if ($paymentaction == 'QentaCheckoutSeamless' && $sPaymentstate !== 'success') {
            return false;
        }
    }

    /**
     * Display additional data for seamless payment methods and
     * payment methods with required
     *
     * @param Enlight_Controller_EventArgs|Enlight_Event_EventArgs $args
     */
    public function onPostDispatch(Enlight_Event_EventArgs $args)
    {
        // Display additional data
        if (!$args->getSubject()->Request()->isDispatched()
            || $args->getSubject()->Response()->isException()
            || 0 != strcmp('frontend', $args->getSubject()->Request()->getModuleName())
            || 0 != strcmp('checkout', $args->getSubject()->Request()->getControllerName())
        ) {
            return;
        }
        /**@var $controller Shopware_Controllers_Frontend_Listing */
        $controller = $args->getSubject();

        /** @var $shopContext \Shopware\Models\Shop\Shop */
        $shopContext = $this->get('shop');

        /** @var Enlight_View_Default $view */
        $view = $controller->View();

        switch ($args->getSubject()->Request()->getActionName()) {
            case 'shippingPayment':
                self::init();

                // do pre-check for invoice and installment
                if ( ! $this->isActivePayment('invoice')) {
                    $view->sPayments = $this->hidePayment($view->sPayments, 'qenta_invoice');
                }
                if ( ! $this->isActivePayment('installment')) {
                    $view->sPayments = $this->hidePayment($view->sPayments, 'qenta_installment');
                }

                $view->addTemplateDir($this->Path() . 'Views/common/');
                $view->addTemplateDir($this->Path() . 'Views/responsive/');

                break;
            case 'confirm':
                self::init();

                $view->addTemplateDir($this->Path() . 'Views/common/');
                $view->addTemplateDir($this->Path() . 'Views/responsive/');
                $customerId = Shopware()->QentaCheckoutSeamless()->Config()->customerid;

                if (Shopware()->Session()->offsetGet('wcsConsumerDeviceId') != null) {
                    $consumerDeviceId = Shopware()->Session()->offsetGet('wcsConsumerDeviceId');
                } else {
                    $timestamp = microtime();
                    $consumerDeviceId = md5($customerId . "_" . $timestamp);
                    Shopware()->Session()->offsetSet('wcsConsumerDeviceId', $consumerDeviceId);
                }
                $paymentName = Shopware()->QentaCheckoutSeamless()->getPaymentShortName();
                if ((Shopware()->QentaCheckoutSeamless()->Config()->INVOICE_PROVIDER == 'ratepay' && $paymentName == 'invoice') ||
                    (Shopware()->QentaCheckoutSeamless()->Config()->INSTALLMENT_PROVIDER == 'ratepay' && $paymentName == 'installment')) {
                    $ratepay = '<script language="JavaScript">var di = {t:"' . $consumerDeviceId . '",v:"WDWL",l:"Checkout"};</script>';
                    $ratepay .= '<script type="text/javascript" src="//d.ratepay.com/' . $consumerDeviceId . '/di.js"></script>';
                    $ratepay .= '<noscript><link rel="stylesheet" type="text/css" href="//d.ratepay.com/di.css?t=' . $consumerDeviceId . '&v=WDWL&l=Checkout"></noscript>';
                        $ratepay .= '<object type="application/x-shockwave-flash" data="//d.ratepay.com/WDWL/c.swf" width="0" height="0"><param name="movie" value="//d.ratepay.com/WDWL/c.swf" /><param name="flashvars" value="t=' . $consumerDeviceId . '&v=WDWL"/><param name="AllowScriptAccess" value="always"/></object>';
                    $view->ratePayScript = $ratepay;
                }

                // Output of common errors
                if (null != Shopware()->QentaCheckoutSeamless()->qenta_action) {
                    self::showErrorMessages($view);
                }

                // Don't show additional data for selected payment methods
                if (in_array(
                    Shopware()->QentaCheckoutSeamless()->getPaymentShortName(),
                    Shopware()->QentaCheckoutSeamless()->Config()->getPaymentsWithAdditionalData()
                )
                ) {
                    if (Shopware()->QentaCheckoutSeamless()->Datastorage()->initiate() === false) {
                        // hide technical error message
                        Shopware()->QentaCheckoutSeamless()->qenta_message = 'Could not initiate DataStorage!';
                        self::showErrorMessages($view);
                    }

                    Shopware()->QentaCheckoutSeamless()->storageId = Shopware()->QentaCheckoutSeamless()->Datastorage()->getStorageId();
                }

                $view->oldShopVersion = false;

                if (!$this->assertMinimumVersion('5')) {
                    $view->oldShopVersion = true;
                }

                $view->paymentTypeName = Shopware()->QentaCheckoutSeamless()->getPaymentShortName();
                //redirect to payment choice if not-active payment was chosen (invoice/installment)
                if ( ! $this->isActivePayment(Shopware()->QentaCheckoutSeamless()->getPaymentShortName())) {
                    $controller->forward('shippingPayment');
                }

                $view->qentaAdditionalHeadline = Shopware()->QentaCheckoutSeamless()->getUser('payment')->description;
                $view->qentaDatastorageReadUrl = Shopware()->Front()->Router()->assemble(Array(
                    'controller' => 'qentacheckoutseamless',
                    'action' => 'datastorageRead',
                    'sUseSSL' => true
                ));

                /** @var Enlight_Components_Snippet_Namespace ns */
                $ns = Shopware()->Snippets()->getNamespace('engine/Shopware/Plugins/Community/Frontend/QentaCheckoutSeamless/Views/frontend/checkout/qenta');
                $view->noPaymentdataMessage = $ns['QentaMessageNoPaymentdata'];

                /** @var Enlight_Components_Snippet_Namespace ns */
                $ns = Shopware()->Snippets()->getNamespace('frontend/checkout/confirm');
                $view->confirmErrorAGB = $ns['ConfirmErrorAGB'];
                $view->paymentLogo = 'frontend/_public/images/' . Shopware()->Session()->sOrderVariables['sUserData']['additional']['payment']['name'] . '.png';

                switch ($view->paymentTypeName) {
                    case 'eps':
                        $view->financialInstitutions = QentaCEE_QMore_PaymentType::getFinancialInstitutions(
                            'EPS'
                        );
                        $view->qentaAdditional = 'financialInstitutions';
                        $view->financialInstitutionsSelected = Shopware()->QentaCheckoutSeamless()->financialInstitution;
                        break;

                    case 'ideal':
                        $view->financialInstitutions = QentaCEE_QMore_PaymentType::getFinancialInstitutions(
                            'IDL'
                        );
                        $view->qentaAdditional = 'financialInstitutions';
                        $view->financialInstitutionsSelected = Shopware()->QentaCheckoutSeamless()->financialInstitution;
                        break;

                    case 'ccard':
                    case 'ccard-moto':
                    case 'maestro':
                        $view->hasPciCert = !Shopware()->QentaCheckoutSeamless()->Config()->PCI3_DSS_SAQ_A_ENABLE;
                        $view->displayCardholder = Shopware()->QentaCheckoutSeamless()->Config()->CREDITCARD_SHOWCARDHOLDER;
                        $view->displayCvc = Shopware()->QentaCheckoutSeamless()->Config()->CREDITCARD_SHOWCVC;
                        $view->displayIssueDate = Shopware()->QentaCheckoutSeamless()->Config()->CREDITCARD_SHOWISSUEDATE;
                        $view->displayIssueNumber = Shopware()->QentaCheckoutSeamless()->Config()->CREDITCARD_SHOWISSUENUMBER;
                        // Show 20 years beginning from the current year for
                        // for issue and expire date of credit cards
                        $view->cartYear = range(date('Y'), date('Y') + 20);
                    case 'giropay':
                    case 'pbx':
                    case 'elv':
                    case 'sepa-dd':
                    case 'voucher':
                        $view->qentaAdditional = 'seamless';
                        $view->qentaJavascript = Shopware()->QentaCheckoutSeamless()->Datastorage()->getJavascriptUrl();
                        break;
                    case 'invoice':
                    case 'installment':
                        $view->qentaAdditional = 'seamless';
                        $user                     = Shopware()->Session()->sOrderVariables['sUserData'];
                        $birth                    = null;

                        if ( ! is_null($user) && isset($user['additional']['user']['birthday'])) {
                            $birth = $user['additional']['user']['birthday'];
                        } else if ( ! is_null($user) && isset($user['billingaddress']['birthday'])) {
                            $birth = $user['billingaddress']['birthday'];
                        }

                        // Values for datefields
                        $view->years  = range(date('Y'), date('Y') - 100);
                        $view->days   = range(1, 31);
                        $view->months = range(1, 12);

                        $birthday = array('-', '-', '-');
                        if ($birth != null) {
                            $birthday = explode('-', $birth);
                        }

                        $view->bYear  = $birthday[0];
                        $view->bMonth = $birthday[1];
                        $view->bDay   = $birthday[2];

                        $view->payolutionTerms = false;
                        if ((Shopware()->QentaCheckoutSeamless()->Config()->INVOICE_PROVIDER == 'payolution' && $view->paymentTypeName == 'invoice') ||
                            (Shopware()->QentaCheckoutSeamless()->Config()->INSTALLMENT_PROVIDER == 'payolution' && $view->paymentTypeName == 'installment')) {
                            $view->payolutionTerms = Shopware()->QentaCheckoutSeamless()->Config()->PAYOLUTION_TERMS;
                            if (Shopware()->QentaCheckoutSeamless()->Config()->PAYOLUTION_TERMS) {
                                $view->wcsPayolutionLink1 = '<a id="wcs-payolutionlink" href="https://payment.payolution.com/payolution-payment/infoport/dataprivacyconsent?mId=' . $this->getPayolutionLink() . '" target="_blank">';
                                $view->wcsPayolutionLink2 = '</a>';
                            }
                        }
                        break;
                    default:
                        $view->qentaAdditional = 'none';
                        break;
                }
                break;

            case 'finish':
                self::init();
                $variables = Shopware()->Session()->offsetGet('sOrderVariables');
                $confirmMailFailed = false;
                $confirmMailFailed = $variables['confirmMailDeliveryFailed'];
                $view->addTemplateDir($this->Path() . 'Views/common/');
                $view->addTemplateDir($this->Path() . 'Views/responsive/');
                //consumerDeviceId session must be set null here
                Shopware()->Session()->offsetSet('wcsConsumerDeviceId', null);

                $view->pendingPayment = $args->getSubject()->Request()->get('pending');
                $view->confirmMailFailed = $confirmMailFailed;
                break;
            default:
                return;
        }
    }

    /**
     * Save selected POST paramter for payment methods with required
     * financial institutions in session
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onPreDispatch(Enlight_Event_EventArgs $args)
    {
        $financialInstitution = $args->getSubject()->Request()->get('financialInstitution');
        if (isset($financialInstitution)) {
            self::init();
            Shopware()->QentaCheckoutSeamless()->financialInstitution = $financialInstitution;
        }

        $birthDate = $args->getSubject()->Request()->get('birthdate');
        if (!empty($birthDate)) {
            self::init();
            Shopware()->Session()->sOrderVariables['sUserData']['additional']['user']['birthday'] = $birthDate;
        }
    }

    /**
     * Pre-check for invoice and installment payments
     *
     * @param $quantity
     * @param $amount
     * @param $paymentName
     *
     * @return bool
     */
    private function isActivePayment($paymentName)
    {
        $shop = Shopware()->Container()->get('shop');
        $current_currency = $shop->getCurrency()->getCurrency();
        switch ($paymentName) {
            case 'invoice':
            case 'qenta_invoice':
                $currencies = Shopware()->QentaCheckoutSeamless()->Config()->INVOICE_CURRENCY;

                if (isset($currencies)) {
                    foreach ($currencies as $currency) {
                        if ((string)$currency == (string)$current_currency) {
                            return true;
                        }
                    }
                    if(count($currencies)){
                        return false;
                    }
                }
                return true;
            case 'installment':
            case 'qenta_installment':
                $currencies = Shopware()->QentaCheckoutSeamless()->Config()->INSTALLMENT_CURRENCY;

                if (isset($currencies)) {
                    foreach ($currencies as $currency) {
                        if ((string)$currency == (string)$current_currency) {
                            return true;
                        }
                    }
                    if(count($currencies)){
                        return false;
                    }
                }
                return true;
            default:
                return true;
        }
    }

    /**
     * Remove payment from active payments
     *
     * @param $payments
     * @param $paymentName
     *
     * @return mixed
     */
    protected function hidePayment($payments, $paymentName)
    {
        if (is_array($payments)) {
            foreach ($payments as $key => $value) {
                if ($value['name'] == $paymentName) {
                    unset($payments[$key]);

                    return $payments;
                }
            }
        }

        return $payments;
    }

    /**
     * Display error messages for customer
     *
     * @param $view
     */
    protected static function showErrorMessages($view)
    {
        $view->qenta_error = Shopware()->QentaCheckoutSeamless()->qenta_action;
        $view->qenta_message = Shopware()->QentaCheckoutSeamless()->qenta_message;
        Shopware()->QentaCheckoutSeamless()->qenta_action = null;
        Shopware()->QentaCheckoutSeamless()->qenta_message = null;
    }

    /**
     * check of a cronjob has already been created.
     * @param $cronName
     * @return bool
     */
    protected function hasCronJob($cronName)
    {
        /** @var $cronManager Enlight_Components_Cron_Manager */
        $cronManager = Shopware()->Cron();
        //we have to do a workaround due to a bug in Shopware 5s Cron DBAL Adapter (http://jira.shopware.de/?ticket=SW-11682)
        foreach ($cronManager->getAllJobs() AS $job) {
            if ($job->getName() == $cronName) {
                return true;
            }
        }
        return false;
    }
}
