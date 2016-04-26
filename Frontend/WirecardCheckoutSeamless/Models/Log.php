<?php
/**
 * Logging class
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
 * class used for logging requests
 */
class Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Log
{
    /**
    * Singleton pattern - only one instance of ourselves
    *
    * @var Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Log
    */
    private static $instance;

   /**
    * Private constructor - call of singleton method required
    */
    private function __construct()
    {
    }

   /**
    * Returns instance of Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Resources
    *
    * @return Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Models_Log
    */
    public static function getSingleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = self::zendLog();
        }
        return self::$instance;
    }

   /**
    * Initialize zend logger and return log instance
    *
    * @return Zend_Log
    */
    protected static function zendLog()
    {
        // Shopware Compatibilty
        $log = new Zend_Log();
        $log->addPriority('TABLE', 8);
        $log->addPriority('EXCEPTION', 9);
        $log->addPriority('DUMP', 10);
        $log->addPriority('TRACE', 11);
        $writer = new Zend_Log_Writer_Null();
        switch(Shopware()->WirecardCheckoutSeamless()->Config()->logType()) {
            case 2:
                $logdir = dirname(__FILE__) . '/../log/';
                if (is_writable($logdir)) {
                    $writer = new Zend_Log_Writer_Stream($logdir . 'wirecard_' . date('Y-m-d') . '.log');
                }
                break;

            case 3:
                $writer = Zend_Log_Writer_Db::factory(array(
                    'db'        => Shopware()->Db(),
                    'table'     => 's_core_log',
                    'columnmap' => array(
                        'key'       => 'priorityName',
                        'text'      => 'message',
                        'datum'     => 'date',
                        'value2'    => 'remote_address',
                        'value3'    => 'user_agent',
                    )
                ));
                $writer->addFilter(Zend_Log::ERR);
            break;

            case 4:
                $mail = clone Shopware()->Mail();
                $mail->addTo(Shopware()->Config()->Mail);
                $writer = new Zend_Log_Writer_Mail($mail);
                $writer->setSubjectPrependText('Fehler  "'.Shopware()->Config()->Shopname.'" aufgetreten!');
                $writer->addFilter(Zend_Log::WARN);
            break;

            default:
                $writer = new Zend_Log_Writer_Null();
            break;
        }
        $log->addWriter($writer);
        return $log;
    }


    /**
     * Shopware internal logging
     * Useful for static call without Zend_Log instance
     */
    public static function log($text = '')
    {
        if ('' == $text) {
            return;
        }
        Shopware()->Db()->insert('s_core_log', array(
            'type' => 'backend',
            'key' => 'WirecardCheckoutSeamless',
            'text' => (string)$text,
            'datum' => date('Y-m-d H:i:s'),
            'value1' => Shopware()->Session()->sName
        ));
    }

}

