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

require_once __DIR__ . '/Components/CSRFWhitelistAware.php';

/**
 * WirecardCheckoutSeamless Bootstrap class
 *
 * This class is hooking into the bootstrap mechanism of Shopware.
 */
class Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{

    /**
     * Name of payment controller
     * needed for several URLs
     *
     * @var string
     */
    const CONTROLLER = 'WirecardCheckoutSeamless';

    /**
     * Starting position for Wireqrd CEE payment methods
     */
    const STARTPOSITION = 50;

    /**
     * Plugin name
     */
    const NAME = 'Shopware_5.WirecardCheckoutSeamless';

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
        return '1.10.0';
    }

    /**
     * Returns the label of the plugin as string
     *
     * @return string
     */
    public function getLabel()
    {
        return "Wirecard Checkout Seamless";
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

        $copLink = '<a href="https://checkout.wirecard.com/cop/'
            . '?shopsystem=Shopware'
            . '&shopversion=' . $shopversion
            . '&integration=WCS'
            . '&pluginversion=' . $this->getVersion()
            . '" target="_blank">Wirecard Checkout Portal</a>';



        $image = dirname(__FILE__) . '/wirecard-logo.png';
        $imageData = base64_encode(file_get_contents($image));

        $src = 'data: '.mime_content_type($image).';base64,'.$imageData;

        return array(
            'version' => $this->getVersion(),
            'autor' => 'Wirecard',
            'copyright' => 'Wirecard',
            'label' => $this->getLabel(),
            'support' => 'http://www.wirecard.at/en/get-in-contact/',
            'link' => 'http://www.wirecard.at',
            'description' => '<img src="'.$src.'" /><div style="line-height: 1.6em"><h3>WIRECARD - YOUR FULL SERVICE PAYMENT PROVIDER - COMPREHENSIVE SOLUTIONS FROM ONE SINGLE SOURCE</h3>'
                . '<p>' . file_get_contents(dirname(__FILE__) . '/info.txt') . '</p>'
                . '<p>If you have no Wirecard account, please register yourself via ' . $copLink . '.</p></div>'
        );
    }

    /**
     * @return array
     * @throws Enlight_Exception
     */
    public function install()
    {
        self::init();

	    // Shopversion is not available for latest Shopwareversion
	    if (strlen(Shopware::VERSION)) {
	        if (!$this->assertMinimumVersion('4.0.0')) {
                throw new Enlight_Exception('This plugin needs minimum Shopware 4.0.0');
            }

            if (!$this->assertMinimumVersion('5.2.0')) {
                if (!$this->assertRequiredPluginsPresent(array('Payment'))) {
                    throw new Enlight_Exception('This plugin requires the plugin payment');
                }
            }
	    } else {
	        throw new Enlight_Exception('Unknown/Unsupported Shopware version. Please update to a supported version.');
        }

        $this->createEvents();
        $this->createPayments();
        $this->createForm();
        $this->createTranslations();

        foreach (Shopware()->WirecardCheckoutSeamless()->Config()->getDbTables() as $sql) {
            Shopware()->Db()->exec($sql);
        }
        $info = Shopware()->Db()->describeTable('wirecard_checkout_seamless');
        if (!isset($info['session'])) {
            Shopware()->Db()->exec('ALTER TABLE wirecard_checkout_seamless ADD COLUMN session MEDIUMTEXT NULL');
        } else {
            if ($info['session']['DATA_TYPE'] !== 'mediumtext') {
                Shopware()->Db()->exec('ALTER TABLE wirecard_checkout_seamless MODIFY session MEDIUMTEXT');
            }
        }
        if (!isset($info['remoteAddr'])) {
            Shopware()->Db()->exec('ALTER TABLE wirecard_checkout_seamless ADD COLUMN remoteAddr VARCHAR(80) NULL ');
        } else {
            if ($info['remoteAddr']['DATA_TYPE'] !== 'varchar') {
                Shopware()->Db()->exec('ALTER TABLE wirecard_checkout_seamless MODIFY remoteAddr VARCHAR(80)');
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
        if ($this->assertMinimumVersion('5')) {
            /** @var \Shopware\Components\CacheManager $cacheManager */
            $cacheManager = $this->get('shopware.cache_manager');
            $cacheManager->clearThemeCache();
        }

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
            Shopware()->Pluginlogger()->error('WirecardCheckoutSeamless: delete failed: ' . $e->getMessage(),
                'ERROR'
            );
        }

        return $this->secureUninstall();

    }

    public function update($version)
    {
        if (version_compare($version, '1.7.0', '<=')) {
            //removing paymentType click2pay
            Shopware()->Db()->delete('s_core_paymentmeans', 'name = "wirecard_c2p"');
        }

        if (version_compare($version, '1.8.1', '<=')) {
            //removing old logging method
            $em = $this->get('models');
            $form = $this->Form();
            $wirecard_log = $form->getElement('WIRECARD_LOG');
            if ($wirecard_log !== null) {
                $em->remove($wirecard_log);
            }
            $wirecard_delete_log = $form->getElement('DELETELOG');
            if ($wirecard_delete_log !== null) {
                $em->remove($wirecard_delete_log);

            }
            $em->flush();
        }

        if (version_compare($version, '1.10.0', '<=')) {
            Shopware()->Db()->delete('s_core_paymentmeans', 'name = "wirecard_quick"');
            Shopware()->Db()->delete('s_core_paymentmeans', 'name = "wirecard_elv"');
            Shopware()->Db()->delete('s_core_paymentmeans', 'name = "wirecard_mpass"');
            Shopware()->Db()->delete('s_core_paymentmeans', 'name = "wirecard_skrilldirect"');

            $em = $this->get('models');
            $form = $this->Form();
            $wirecard_keep_orders = $form->getElement('KEEP_UNSUCCESSFUL_ORDERS');
            if ($wirecard_keep_orders !== null) {
                $em->remove($wirecard_keep_orders);
            }
            $wirecard_restore_basket = $form->getElement('RESTORE_BASKET');
            if ($wirecard_restore_basket !== null) {
                $em->remove($wirecard_restore_basket);
            }
            $wirecard_shop_prefix = $form->getElement('SHOP_PREFIX');
            if ($wirecard_shop_prefix !== null) {
                $em->remove($wirecard_shop_prefix);
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

        $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        $shop = $repository->findOneBy(['id' => 1]);
        $currencies = array();
        foreach ($shop->getCurrencies() as $elem) {
            $currency = array($elem->getCurrency(), $elem->getName());
            array_push($currencies, $currency);
        }

        $form->setElement(
            'text',
            'CUSTOMERID',
            array(
                'label' => 'Kundennummer',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'description' => 'Ihre Wirecard-Kundennummer (customerId, im Format D2#####)',
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
                'description' => 'Geheime Zeichenfolge, die Sie von Wirecard erhalten haben, zum Signieren und Validieren von Daten zur Prüfung der Authentizität (Testmodus: B8AKTPWBRMNBV455FG6M2DANE99WU2).',
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
                'description' => 'Benachrichtigung per E-Mail über Zahlungen Ihrer Kunden, falls ein Kommunikationsproblem zwischen Wirecard und Ihrem Onlineshop aufgetreten ist. Bitte kontaktieren Sie unsere Sales-Teams um dieses Feature freizuschalten.',
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
                'description' => 'Falls "Nein" gesetzt ist, gilt der strengere SAQ A-EP. Falls "Ja" gesetzt ist, wird in Wirecard Checkout Seamless das "PCI DSS SAQ A Compliance"-Feature verwendet und es gilt der SAQ A.',
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
            'WIRECARD_SAVERESPONSE',
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
                'description' => 'Speichern aller Ergebnisse des Bezahlprozesses, d.h. jedes Aufrufs des Wirecard Checkout Servers der Bestätigungs-URL.',
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
                    array(1, 'Wirecard order number'),
                    array(2, 'Gateway reference number')
                ),
                'description' => 'Als Shopware Transaction ID wird entweder die shopinterne Bestellnummer oder die Referenznummer des Acquirers verwendet.',
                'required' => false,
                'order' => ++$i
            )
        );

        $form->setElement(
            'select',
            'WIRECARD_CONFIRM_HEADER_STYLE',
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
                    array('wirecard', 'Wirecard')
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
                'store' => $currencies,
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
                'store' => $currencies,
                'multiSelect' => true,
                'description' => 'Bitte wählen Sie mindestens eine gültige Währung für Kauf auf Raten.',
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
                    'description' => 'Customer number you received from Wirecard (Test mode: D200001).'
                ),
                'SHOPID' => Array(
                    'label' => 'Shop ID',
                    'description' => 'Shop identifier in case of more than one shop (test mode: seamless).'
                ),
                'SECRET' => Array(
                    'label' => 'Secret',
                    'description' => 'String which you received from Wirecard for signing and validating data to prove their authenticity (test mode: B8AKTPWBRMNBV455FG6M2DANE99WU2).'
                ),
                'SERVICE_URL' => Array(
                    'label' => 'URL to imprint page',
                    'description' => 'URL on the payment page which leads to the imprint page of the online shop.'
                ),
                'CONFIRM_MAIL' => Array(
                    'label' => 'Notification e-mail',
                    'description' => 'Receiving notification by e-mail regarding the orders of your consumers if an error occurred in the communication between Wirecard and your online shop. Please contact our sales teams to activate this feature.'
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
                    'description' => 'Selecting "No", the stringent SAQ A-EP is applicable. Selecting "Yes", Wirecard Checkout Seamless is integrated with the "PCI DSS SAQ A Compliance" feature and SAQ A is applicable.'
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
                'WIRECARD_SAVERESPONSE' => Array(
                    'label' => 'Save payment process results',
                    'description' => 'Save all results regarding the payment process, i.e. each Wirecard Checkout Server response to the confirmation URL to the defined field.'
                ),
                'WIRECARD_CONFIRM_HEADER_STYLE' => Array(
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
            __DIR__ . '/Views/responsive/frontend/_public/src/js/wirecard_seamless.js'
        );
        return new Doctrine\Common\Collections\ArrayCollection($jsFiles);
    }

    /**
     * Create and save payment methods
     */
    protected function createPayments()
    {
        $prefixDescription = Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Config::getSingleton()
            ->getPrefix('description');
        $prefixName = Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Config::getSingleton()->getPrefix(
            'name'
        );

        $translation = new Shopware_Components_Translation();
        $aTranslations = array();
        foreach (Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Config::getSingleton()->getPaymentMethods() as $pos => $pm) {
            $oPayment = $this->Payments()->findOneBy(array('name' => $prefixName . $pm['name']));
            if (!$oPayment) {
                $payment = array(
                    'name' => $prefixName . $pm['name'],
                    'description' => $prefixDescription . $pm['description'],
                    'action' => self::CONTROLLER,
                    'active' => (isset($pm['active'])) ? (int)$pm['active'] : 0,
                    'position' => self::STARTPOSITION + $pos,
                    'pluginID' => $this->getId(),
                    'additionalDescription' => ''
                );
                if (isset($pm['template']) && !is_null($pm['template'])) {
                    $payment['template'] = $pm['template'];
                }
                $oPayment = $this->createPayment($payment);
            } else {
                if (isset($pm['template']) && !is_null($pm['template'])) {
                    $oPayment->setTemplate($pm['template']);
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
     * @return Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Resources
     */
    public static function init()
    {
        // Register resource WirecardCheckoutSeamless
        // The instance is available with Shopware()->WirecardCheckoutSeamless()
        if (!Shopware()->Bootstrap()->issetResource('WirecardCheckoutSeamless')) {
            Shopware()->Bootstrap()->registerResource(
                'WirecardCheckoutSeamless',
                Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Resources::getSingleton()
            );
        }

        // Register root directory for this plugin
        Shopware()->WirecardCheckoutSeamless()->Config()->setPluginRoot(
            substr(dirname(__FILE__), 1 + strlen($_SERVER['DOCUMENT_ROOT']))
        );
    }

    public function afterInit()
    {
        $this->registerCustomModels();
        $this->get('Loader')->registerNamespace('Shopware\\Plugins\\WirecardCheckoutSeamless', $this->Path());
        $this->get('Loader')->registerNamespace('WirecardCEE', $this->Path() . 'Components/WirecardCEE/');
    }

    /**
     * Event listener method
     *
     * @param Enlight_Event_EventArgs $args
     * @return string
     */
    public static function onGetControllerPathFrontend(Enlight_Event_EventArgs $args)
    {
        Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Bootstrap::init();
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
        $mid = Shopware()->WirecardCheckoutSeamless()->Config()->PAYOLUTION_MID;
        if (strlen($mid) === 0) {
            return false;
        }

        $mId = urlencode(base64_encode($mid));

        return $mId;
    }

    /**
     * set confirmmail after ordercreation false (only for WirecardCheckoutSeamless)
     * @param Enlight_Event_EventArgs $args
     * @return bool
     */
    public function defineSending(Enlight_Event_EventArgs $args)
    {
        $userData = Shopware()->Session()->sOrderVariables['sUserData'];
        $additional = $userData['additional'];
        $paymentaction = $additional['payment']['action'];
        $sPaymentstate = Shopware()->Session()->sPaymentstate;

        //only prevent confirmationmail for WirecardCheckoutSeamless payment action
        if ($paymentaction == 'WirecardCheckoutSeamless' && $sPaymentstate !== 'success') {
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
                    $view->sPayments = $this->hidePayment($view->sPayments, 'wirecard_invoice');
                }
                if ( ! $this->isActivePayment('installment')) {
                    $view->sPayments = $this->hidePayment($view->sPayments, 'wirecard_installment');
                }

                $view->addTemplateDir($this->Path() . 'Views/common/');
                if (Shopware()->Shop()->getTemplate()->getVersion() >= 3) {
                    $view->addTemplateDir($this->Path() . 'Views/responsive/');
                } else {
                    $view->addTemplateDir($this->Path() . 'Views/');
                    $view->extendsTemplate('frontend/checkout/wirecard.tpl');
                }

                break;
            case 'confirm':
                self::init();

                $view->addTemplateDir($this->Path() . 'Views/common/');
                if (Shopware()->Shop()->getTemplate()->getVersion() >= 3) {
                    $view->addTemplateDir($this->Path() . 'Views/responsive/');
                } else {
                    $view->addTemplateDir($this->Path() . 'Views/');
                    $view->extendsTemplate('frontend/checkout/wirecard.tpl');
                }

                // Output of common errors
                if (null != Shopware()->WirecardCheckoutSeamless()->wirecard_action) {
                    self::showErrorMessages($view);
                }

                // Don't show additional data for selected payment methods
                if (in_array(
                    Shopware()->WirecardCheckoutSeamless()->getPaymentShortName(),
                    Shopware()->WirecardCheckoutSeamless()->Config()->getPaymentsWithAdditionalData()
                )
                ) {
                    if (Shopware()->WirecardCheckoutSeamless()->Datastorage()->initiate() === false) {
                        // hide technical error message
                        Shopware()->WirecardCheckoutSeamless()->wirecard_message = 'Could not initiate DataStorage!';
                        self::showErrorMessages($view);
                    }

                    Shopware()->WirecardCheckoutSeamless()->storageId = Shopware()->WirecardCheckoutSeamless()->Datastorage()->getStorageId();
                }

                $view->oldShopVersion = false;

                if (!$this->assertMinimumVersion('5')) {
                    $view->oldShopVersion = true;
                }

                $view->paymentTypeName = Shopware()->WirecardCheckoutSeamless()->getPaymentShortName();
                //redirect to payment choice if not-active payment was chosen (invoice/installment)
                if ( ! $this->isActivePayment(Shopware()->WirecardCheckoutSeamless()->getPaymentShortName())) {
                    $controller->forward('shippingPayment');
                }

                $view->wirecardAdditionalHeadline = Shopware()->WirecardCheckoutSeamless()->getUser('payment')->description;
                $view->wirecardDatastorageReadUrl = Shopware()->Front()->Router()->assemble(Array(
                    'controller' => 'wirecardcheckoutseamless',
                    'action' => 'datastorageRead',
                    'sUseSSL' => true
                ));

                /** @var Enlight_Components_Snippet_Namespace ns */
                $ns = Shopware()->Snippets()->getNamespace('engine/Shopware/Plugins/Community/Frontend/WirecardCheckoutSeamless/Views/frontend/checkout/wirecard');
                $view->noPaymentdataMessage = $ns['WirecardMessageNoPaymentdata'];

                /** @var Enlight_Components_Snippet_Namespace ns */
                $ns = Shopware()->Snippets()->getNamespace('frontend/checkout/confirm');
                $view->confirmErrorAGB = $ns['ConfirmErrorAGB'];
                $view->paymentLogo = 'frontend/_public/images/' . Shopware()->Session()->sOrderVariables['sUserData']['additional']['payment']['name'] . '.png';

                switch ($view->paymentTypeName) {
                    case 'eps':
                        $view->financialInstitutions = WirecardCEE_QMore_PaymentType::getFinancialInstitutions(
                            'EPS'
                        );
                        $view->wirecardAdditional = 'financialInstitutions';
                        $view->financialInstitutionsSelected = Shopware()->WirecardCheckoutSeamless()->financialInstitution;
                        break;

                    case 'ideal':
                        $view->financialInstitutions = WirecardCEE_QMore_PaymentType::getFinancialInstitutions(
                            'IDL'
                        );
                        $view->wirecardAdditional = 'financialInstitutions';
                        $view->financialInstitutionsSelected = Shopware()->WirecardCheckoutSeamless()->financialInstitution;
                        break;

                    case 'ccard':
                    case 'ccard-moto':
                    case 'maestro':
                        $view->hasPciCert = !Shopware()->WirecardCheckoutSeamless()->Config()->PCI3_DSS_SAQ_A_ENABLE;
                        $view->displayCardholder = Shopware()->WirecardCheckoutSeamless()->Config()->CREDITCARD_SHOWCARDHOLDER;
                        $view->displayCvc = Shopware()->WirecardCheckoutSeamless()->Config()->CREDITCARD_SHOWCVC;
                        $view->displayIssueDate = Shopware()->WirecardCheckoutSeamless()->Config()->CREDITCARD_SHOWISSUEDATE;
                        $view->displayIssueNumber = Shopware()->WirecardCheckoutSeamless()->Config()->CREDITCARD_SHOWISSUENUMBER;
                        // Show 20 years beginning from the current year for
                        // for issue and expire date of credit cards
                        $view->cartYear = range(date('Y'), date('Y') + 20);
                    case 'giropay':
                    case 'pbx':
                    case 'elv':
                    case 'sepa-dd':
                    case 'voucher':
                        $view->wirecardAdditional = 'seamless';
                        $view->wirecardJavascript = Shopware()->WirecardCheckoutSeamless()->Datastorage()->getJavascriptUrl();
                        break;
                    case 'invoice':
                    case 'installment':
                        $view->wirecardAdditional = 'seamless';
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

                        if (Shopware()->WirecardCheckoutSeamless()->Config()->INVOICE_PROVIDER) {
                            $view->payolutionTerms = Shopware()->WirecardCheckoutSeamless()->Config()->PAYOLUTION_TERMS;
                            if (Shopware()->WirecardCheckoutSeamless()->Config()->PAYOLUTION_TERMS) {
                                $view->wcsPayolutionLink1 = '<a id="wcs-payolutionlink" href="https://payment.payolution.com/payolution-payment/infoport/dataprivacyconsent?mId=' . $this->getPayolutionLink() . '" target="_blank">';
                                $view->wcsPayolutionLink2 = '</a>';
                            }
                        }
                        break;
                    default:
                        $view->wirecardAdditional = 'none';
                        break;
                }
                break;

            case 'finish':
                self::init();
                $variables = Shopware()->Session()->offsetGet('sOrderVariables');
                $confirmMailFailed = false;
                $confirmMailFailed = $variables['confirmMailDeliveryFailed'];
                $view->addTemplateDir($this->Path() . 'Views/common/');
                if (Shopware()->Shop()->getTemplate()->getVersion() >= 3) {
                    $view->addTemplateDir($this->Path() . 'Views/responsive/');
                } else {
                    $view->addTemplateDir($this->Path() . 'Views/');
                    $view->extendsTemplate('frontend/checkout/wirecard_finish.tpl');
                }

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
            Shopware()->WirecardCheckoutSeamless()->financialInstitution = $financialInstitution;
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
        switch ($paymentName) {
            case 'invoice':
            case 'wirecard_invoice':
               $currencies = Shopware()->WirecardCheckoutSeamless()->Config()->INVOICE_CURRENCY;

                if (isset($currencies)) {
                    $currentCurrency = Shopware()->Shop()->getCurrency()->getCurrency();

                    foreach ($currencies as $currency) {
                        if ((string)$currency == (string)$currentCurrency) {
                            return true;
                        }
                    }
                    if ($currencies->count()) {
                        return false;
                    }
                }

                return true;
            case 'installment':
            case 'wirecard_installment':
               $currencies = Shopware()->WirecardCheckoutSeamless()->Config()->INSTALLMENT_CURRENCY;

                if (isset($currencies)) {
                    $currentCurrency = Shopware()->Shop()->getCurrency()->getCurrency();

                    foreach ($currencies as $currency) {
                        if ((string)$currency == (string)$currentCurrency) {
                            return true;
                        }
                    }
                    if ($currencies->count()) {
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
        $view->wirecard_error = Shopware()->WirecardCheckoutSeamless()->wirecard_action;
        $view->wirecard_message = Shopware()->WirecardCheckoutSeamless()->wirecard_message;
        Shopware()->WirecardCheckoutSeamless()->wirecard_action = null;
        Shopware()->WirecardCheckoutSeamless()->wirecard_message = null;
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
        if ($this->assertMinimumVersion('5')) {
            foreach ($cronManager->getAllJobs() AS $job) {
                if ($job->getName() == $cronName) {
                    return true;
                }
            }
            return false;
        } else {
            return $cronManager->getJobByName($cronName) ? true : false;
        }
    }
}
