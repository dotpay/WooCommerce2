<?php

/**
*
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to tech@dotpay.pl so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade WooCommerce to newer
* versions in the future. If you wish to customize WooCommerce for your
* needs please refer to http://www.dotpay.pl for more information.
*
*  @author    Dotpay Team <tech@dotpay.pl>
*  @copyright Dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*/

/**
 * Model for credit cards, used by One Click payments
 */
class Dotpay_Card {
    
    /**
     * Install model and prepare database
     */
    public static function install() {
        self::installDb();
        self::installPage();
    }
    
    /**
     * Uninstall model and clear database
     */
    public static function uninstall() {
        self::uninstallDb();
        self::uninstallPage();
    }
    
    /**
     * Add card to database and return card hash
     * @global type $wpdb WPDB object
     * @param int $customerId customer id
     * @param int $orderId order id
     * @return string
     */
    public static function addCard($customerId, $orderId) {
        global $wpdb;
        $existedCard = $wpdb->get_row( 'SELECT * FROM '.$wpdb->prefix.DOTPAY_GATEWAY_ONECLICK_TAB_NAME.' WHERE customer_id = '.(int)$customerId.' AND order_id = '.(int)$orderId);
        if(empty($existedCard)) {
            $hash = self::generateCardHash();
            $wpdb->insert( 
                $wpdb->prefix.DOTPAY_GATEWAY_ONECLICK_TAB_NAME, 
                array( 
                    'customer_id' => $customerId, 
                    'order_id' => $orderId,
                    'hash' => $hash
                ), 
                array( 
                    '%d', 
                    '%d',
                    '%s'
                ) 
            );
        } else {
            $hash = $existedCard->hash;
        }
        return $hash;
    }
    
    /**
     * Add additional info to saved card
     * @global type $wpdb WPDB object
     * @param int $id card id
     * @param int $cardId card identifier from Dotpay
     * @param string $mask card mask name
     * @param string $brand card brand
	 * @param string $logo logo card brand
     */
    public static function updateCard($id, $cardId, $mask, $brand, $logo) {
        global $wpdb;
        $wpdb->update( 
            $wpdb->prefix.DOTPAY_GATEWAY_ONECLICK_TAB_NAME,
            array( 
                'card_id' => $cardId,
                'mask' => $mask,
                'brand' => $brand,
				'logo' => $logo,
                'register_date' => date('Y-m-d')
            ), 
            array( 'cc_id' => $id ), 
            array( 
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            ), 
            array( '%d' ) 
        );
    }
    
    /**
     * Remove card for the given id
     * @global type $wpdb WPDB object
     * @param int $id card id
     */
    public static function removeCard($id) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix.DOTPAY_GATEWAY_ONECLICK_TAB_NAME, array( 'cc_id' => $id ), array( '%d' ) );
    }
    
    /**
     * Remove all cards for the given customer
     * @global type $wpdb WPDB object
     * @param int $customerId customer id
     */
    public static function removeAllCardsForCustomer($customerId) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix.DOTPAY_GATEWAY_ONECLICK_TAB_NAME, array( 'customer_id' => $customerId ), array( '%d' ) );
    }
    
    /**
     * Return card data for the given order id
     * @global type $wpdb WPDB object
     * @param int $orderId order id
     * @return \stdClass
     */
    public static function getCardFromOrder($orderId) {
        global $wpdb;
        return $wpdb->get_row( 'SELECT * FROM '.$wpdb->prefix.DOTPAY_GATEWAY_ONECLICK_TAB_NAME.' WHERE order_id = '.(int)$orderId );
    }
    
    /**
     * Return array of cards with only the cards, which can be used in One Click payments
     * @global type $wpdb
     * @param int $customerId customer id
     * @return type
     */
    public static function getUsefulCardsForCustomer($customerId) {
        global $wpdb;
        return $wpdb->get_results( 'SELECT * FROM '.$wpdb->prefix.DOTPAY_GATEWAY_ONECLICK_TAB_NAME.' WHERE customer_id = '.(int)$customerId .' AND card_id IS NOT NULL');
    }
    
    /**
     * Return array of all cards for the given customer
     * @global type $wpdb
     * @param int $customerId customer id
     * @return type
     */
    public static function getCardsForCustomer($customerId) {
        global $wpdb;
        return $wpdb->get_results( 'SELECT * FROM '.$wpdb->prefix.DOTPAY_GATEWAY_ONECLICK_TAB_NAME.' WHERE customer_id = '.(int)$customerId );
    }
    
    /**
     * Return card data fot the given card id
     * @global type $wpdb WPDB object
     * @param int $id card id
     * @return type
     */
    public static function getCardById($id) {
        global $wpdb;
        return $wpdb->get_row( 'SELECT * FROM '.$wpdb->prefix.DOTPAY_GATEWAY_ONECLICK_TAB_NAME.' WHERE cc_id = '.(int)$id );
    }
    
	 /**
     * Return engine type database of WP `users` table 
     * @global type $wpdb WPDB object
     * @param The name of the database for WordPress DB_NAME
     * @return string
     */
    protected static function getEngineUsersTable() {
        global $wpdb;
		$Engine_users_Table = $wpdb->get_row( "SELECT ENGINE FROM information_schema.TABLES where TABLE_SCHEMA = '".DB_NAME."' AND ENGINE IS NOT NULL AND TABLE_NAME = '".$wpdb->prefix."users'" );
        return $Engine_users_Table->ENGINE;
    }
	
	
    /**
     * Return card hash
     * @return type
     */
    private static function generateCardHash() {
        $microtime = '' . microtime(true);
        $md5 = md5($microtime);

        $mtRand = mt_rand(0, 11);

        $md5Substr = substr($md5, $mtRand, 21);

        $a = substr($md5Substr, 0, 6);
        $b = substr($md5Substr, 6, 5);
        $c = substr($md5Substr, 11, 6);
        $d = substr($md5Substr, 17, 4);

        return "{$a}-{$b}-{$c}-{$d}";
    }
    
    /**
     * Generate card hash, unique in the database
     * @global type $wpdb WPDB object
     * @return string
     */
    private static function getUniqueHash() {
        global $wpdb;
        $count = 200;
        $result = false;
        do {
            $cardHash = self::generateCardHash();
            $test = $wpdb->query('
                SELECT count(*) as count  
                FROM `'.$wpdb->prefix.DOTPAY_GATEWAY_ONECLICK_TAB_NAME.'` 
                WHERE hash = \''.$cardHash.'\'
            ');
            
            if ($test[0]['count'] == 0) {
                $result = $cardHash;
                break;
            }

            $count--;
        } while ($count);
        
        return $result;
    }
    
    /**
     * Install database structure for this model
     * @global type $wpdb WPDB object
     */
    private static function installDb() {
        global $wpdb;
        $sql = 'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.DOTPAY_GATEWAY_ONECLICK_TAB_NAME.'` (
                    `cc_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `order_id` INT UNSIGNED NOT NULL,
                    `customer_id` BIGINT(20) UNSIGNED NOT NULL,
                    `mask` varchar(20) DEFAULT NULL,
                    `brand` varchar(20) DEFAULT NULL,
                    `logo` varchar(200) DEFAULT NULL,
                    `hash` varchar(100) NOT NULL,
                    `card_id` VARCHAR(128) DEFAULT NULL,
                    `register_date` DATE DEFAULT NULL,
                    PRIMARY KEY (`cc_id`),
                    UNIQUE KEY `hash` (`hash`),
                    UNIQUE KEY `cc_order` (`order_id`),
                    UNIQUE KEY `card_id` (`card_id`),
                    KEY `customer_id` (`customer_id`),
                    CONSTRAINT fk_customer_id
                        FOREIGN KEY (customer_id)
                        REFERENCES `'.$wpdb->prefix.'users` (`ID`)
                        ON DELETE CASCADE
                ) ENGINE='.self::getEngineUsersTable().' DEFAULT CHARSET=utf8;';
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    /**
     * Create required page for this model
     */
    private static function installPage() {
        $page = new Dotpay_Page(DOTPAY_CARD_MANAGE_PNAME);
        $page->setTitle(DOTPAY_CARD_MANAGE_PTITLE)
             ->setGuid('/dotpay/cards/manage')
             ->add();
    }
    
    /**
     * Clear database structure after this model
     * @global type $wpdb WPDB object
     */
    private static function uninstallDb() {
        global $wpdb;
        $sql = 'DROP TABLE IF EXISTS `'.$wpdb->prefix.DOTPAY_GATEWAY_ONECLICK_TAB_NAME.'`;';
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->query( $sql );
    }
    
    /**
     * Remove page, required for this model
     */
    private static function uninstallPage() {
        $page = new Dotpay_Page(DOTPAY_CARD_MANAGE_PNAME);
        $page->remove();
    }
}
