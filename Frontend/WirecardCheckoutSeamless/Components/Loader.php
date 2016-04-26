<?php
/**
 * Autoloader for libraries
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
 * Loader class responsible for autoloading WirecardCEE library classes
 */
class Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Components_Loader implements Zend_Loader_Autoloader_Interface
{

    /**
     * Prefix for classes which
     * should be included by this autoloader
     * @var string
     */
    const PREFIX = 'WirecardCEE_';

    /**
     * Singleton pattern - only one instance of ourselves
     *
     * @var Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Components_Loader
     */
    private static $instance;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
    }

    /**
     * Returns instance of Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Components_Loader
     *
     * @return Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Components_Loader
     */
    public static function getSingleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Shopware_Plugins_Frontend_WirecardCheckoutSeamless_Components_Loader();
            self::addComponentsPath();
        }
        return self::$instance;
    }

    /**
     * Add library path to PHP include path
     */
    private static function addComponentsPath()
    {
        set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));
    }

    /**
     * method used by shopware for autoloading
     *
     * @param string $class
     * @return bool|mixed
     */
    public function autoload($class)
    {
        if (!preg_match('/^' . self::PREFIX . '/', $class)) {
            return FALSE;
        }
        $fragment = str_replace('_', '/', trim($class, '_'));
        return include_once(dirname(__FILE__) . '/' . $fragment . '.php');
    }
}