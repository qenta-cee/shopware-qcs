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

/**
 * class representing a basket object stored to the database
 */
class Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Basket
{

    /**
     * Singleton pattern - only one instance of ourselve
     *
     * @var Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Config
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
     * @return Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Config
     */
    public static function getSingleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Shopware_Plugins_Frontend_QentaCheckoutSeamless_Models_Basket();
        }
        return self::$instance;
    }

    /**
     * getter for basket content
     * @return array|null
     */
    public function getBasket()
    {
        $sql = Shopware()->Db()->select()
            ->from('s_order_basket')
            ->where('sessionID = ?', array(Shopware()->SessionID()));
        $basket = Shopware()->Db()->fetchAll($sql);
        return $basket;
    }

    /**
     * getter for serialized basket item
     *
     * @return string
     */
    public function getSerializedBasket()
    {
        return serialize($this->getBasket());
    }

    /**
     * Restore basket if it's enabled in the configuration
     *
     * @param array $basket
     * @return bool
     */
    public function setBasket($basket = array())
    {
        Shopware()->Db()->delete('s_order_basket', array('sessionID = ?' => Shopware()->SessionID()));
        foreach ($basket as $row) {
            Shopware()->Db()->insert('s_order_basket', $row);
        }
        return TRUE;
    }

    /**
     * setter for serialized basketItems
     *
     * @param $basket
     * @return bool
     */
    public function setSerializedBasket($basket)
    {
        return $this->setBasket(unserialize($basket));
    }

}