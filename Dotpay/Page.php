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
 * Page model for Dotpay payment gateway for WooCommerce
 */
class Dotpay_Page {
    private $title = '';
    private $name = '';
    private $id = NULL;
    private $guid = '';
    
    /**
     * Load page, if will the parameter given
     * @param string $name name of page
     */
    public function __construct($name = NULL) {
        if(empty($name))
            return;
        $this->name = $name;
        $post = get_post(get_option('dotpay_'.$this->name.'_id'));
        if($post != NULL) {
            $this->id = $post->ID;
            $this->name = $name;
            $this->title = $post->post_title;
            $this->guid = $post->guid;
        }
    }
    
    /**
     * Return page title
     * @return string
     */
    function getTitle() {
        return $this->title;
    }
    
    /**
     * Return page name
     * @return string
     */
    function getName() {
        return $this->name;
    }

    /**
     * Return page id
     * @return int
     */
    function getId() {
        return $this->id;
    }
    
    /**
     * Return page guid
     * @return string
     */
    function getGuid() {
        return $this->guid;
    }
    
    /**
     * Set page name
     * @param string $name page name
     * @return \Dotpay_Page
     */
    function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Set page title
     * @param string $title page title
     * @return \Dotpay_Page
     */
    function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * Set page id
     * @param int $id page id
     * @return \Dotpay_Page
     */
    function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Set page guid
     * @param string $guid page guid
     * @return \Dotpay_Page
     */
    function setGuid($guid) {
        $this->guid = $guid;
        return $this;
    }

    /**
     * Add page to database as a Wp post
     * @global type $wpdb WPDB object
     */
    public function add() {
        global $wpdb;      
        
        delete_option('dotpay_'.$this->name.'_title');
        add_option('dotpay_'.$this->name.'_title', $this->title, '', 'yes');

        $page = get_page_by_title($this->title);

        if (!$page) {
            $info = array();
            $info['post_title']     = $this->title;
            $info['post_content']   = "[dotpay_content]";
            $info['post_status']    = 'publish';
            $info['post_type']      = 'page';
            $info['comment_status'] = 'closed';
            $info['ping_status']    = 'closed';
            $info['guid']    = $this->guid;
            $info['post_category'] = array(1);

            $pageId = wp_insert_post($info);
        }
        else {
            $page->post_status = 'publish';
            $pageId = wp_update_post($page);
        }

        delete_option('dotpay_'.$this->name.'_id');
        add_option('dotpay_'.$this->name.'_id', $pageId);
    }
    
    /**
     * Remove page
     * @global type $wpdb WPDB object
     */
    public function remove() {
        global $wpdb;

        if($this->id) {
            wp_delete_post($this->id, true);
            delete_option('dotpay_'.$this->name.'_title');
            delete_option('dotpay_'.$this->name.'_id');
        }
    }
    
    /**
     * Return page url
     * @return string
     */
    public function getUrl() {
        return get_permalink($this->id);
    }
    
    /**
     * Return page id based on page name
     * @param type $name page name
     * @return int
     */
    public static function getPageId($name) {
        return (int)get_option('dotpay_'.$name.'_id');
    }
}

?>