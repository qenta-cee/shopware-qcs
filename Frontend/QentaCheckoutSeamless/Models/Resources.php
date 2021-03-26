<?php
/**
 * Resources of the plugin:
 *  - Returns singleton instances of classes
 *  - Get and set session variables
 *  - Returns internal short names of payment methods
 *  - Returns user data as object
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

/**
 * class responsible for communication with Resources e.G. Components/Models
 */
class Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Resources
{
   /**
    * Singleton pattern - only one instance of ourselves
    *
    * @var Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Resources
    */
    private static $instance;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
    }


   /**
    * Returns instance of Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Resources
    *
    * @return Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Resources
    */
    public static function getSingleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Resources();
            // Autoloader for lib
        }
        return self::$instance;
    }

   /**
    * Invoking inaccessible methods in an object context.
    *
    * @param string $name
    * @parameter mixed $arguments
    *
    * @returns singleton instances
    */
    public function __call($name = NULL, $arguments = NULL)
    {
        switch ($name) {
            case 'Loader':
                $name = 'Shopware_Plugins_Frontend_QentaCheckoutSeamless_Components_' . $name;
                break;
            default:
                $name = 'Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_' . $name;
                break;
        }

        if (method_exists($name, 'getSingleton')) {
            return $name::getSingleton();
        }
        else {
            throw new Enlight_Exception('Class ' . $name . ' or method getSingleton() in this class not found');
        }
    }

    /**
     * Save value of session variable
     *
     * Our kind of session management:
     * Save session variable in array with prefix of the plugin name
     *
     * @param null $var
     * @param null $val
     */
    public function __set($var = NULL, $val = NULL)
    {
    	Shopware()->Session()->_SESSION[Shopware_Plugins_Frontend_QentaCheckoutSeamless_Bootstrap::NAME][$var] = serialize($val);
    }

    /**
     * Returns value of session variable
     * @param null $var
     * @return null
     */
    public function __get($var = NULL)
	{
		if (!empty($var) && isset(Shopware()->Session()->_SESSION[Shopware_Plugins_Frontend_QentaCheckoutSeamless_Bootstrap::NAME][$var])) {
			return unserialize(Shopware()->Session()->_SESSION[Shopware_Plugins_Frontend_QentaCheckoutSeamless_Bootstrap::NAME][$var]);
		}
		else {
			return NULL;
		}
	}

    /**
     * Returns given part of user data as object.
     *
     * @return array
     */
    public function getUser($key = '')
    {
        if (!empty(Shopware()->Session()->sOrderVariables['sUserData']['additional'][$key])) {
            return (object)Shopware()->Session()->sOrderVariables['sUserData']['additional'][$key];
        }
        if (!empty(Shopware()->Session()->sOrderVariables['sUserData'][$key])) {
            return (object)Shopware()->Session()->sOrderVariables['sUserData'][$key];
        }
        elseif(!empty(Shopware()->Session()->sOrderVariables['sUserData'])) {
            return (object)Shopware()->Session()->sOrderVariables['sUserData'];
        } else {
            return null;
        }
    }

    /**
     * Returns short name of payment methods without prefix
     * example: saved shortname qenta_ccard returns ccard
     *
     * @return null|string
     */
    public function getPaymentShortName()
    {
        $name = Shopware()->QentaCheckoutSeamless()->getUser('payment')->name;
        return (is_null($name)) ? NULL : substr($name, strlen(Shopware()->QentaCheckoutSeamless()->Config()->getPrefix('name')));
    }


}
