<?php
/*
   +----------------------------------------------------------------------+
   | PEAR Web site version 1.0                                            |
   +----------------------------------------------------------------------+
   | Copyright (c) 2003 The PEAR Group                                    |
   +----------------------------------------------------------------------+
   | This source file is subject to version 2.02 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available at through the world-wide-web at                           |
   | http://www.php.net/license/2_02.txt.                                 |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Author: Martin Jansen <mj@php.net>                                   |
   +----------------------------------------------------------------------+
   $Id$
*/

require_once "XML/Tree.php";

/**
 * Base class for generating RSS feeds
 *
 * @author Martin Jansen <mj@php.net>
 * @category RSS
 * @package Damblan
 * @version $Revision$
 */
class Damblan_RSS_Common {

    var $_tree = null;
    var $_root = null;
    var $_channel = null;
    var $_seq = null;

    function Damblan_RSS_Common() {
        $this->_tree = new XML_Tree;
        $this->_root = &$this->_tree->addRoot("rdf:RDF");

        $this->_root->setAttribute("xmlns:rdf", "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
        $this->_root->setAttribute("xmlns", "http://purl.org/rss/1.0/");
        $this->_root->setAttribute("xmlns:dc", "http://purl.org/dc/elements/1.1/");

        $this->_channel = &$this->_root->addChild("channel");
        $this->_channel->setAttribute("rdf:about", "http://pear.php.net/");
        $this->_channel->addChild("link", "http://pear.php.net/");
        $this->_channel->addChild("dc:creator", "pear-webmaster@php.net");
        $this->_channel->addChild("dc:publisher", "pear-webmaster@php.net");
        $this->_channel->addChild("dc:language", "en-us");

        $c_items = &$this->_channel->addChild("items");
        $this->_seq = &$c_items->addChild("rdf:Seq");
    }

    /**
     * Set the title of the RSS feed
     *
     * @param  string Title
     * @return void
     */
    function setTitle($title) {
        $this->_channel->addChild("title", $title);
    }

    /**
     * Set the description of the RSS feed
     *
     * @access public
     * @param  string Description
     * @return void
     */
    function setDescription($desc) {
        $this->_channel->addChild("description", $desc);
    }

    /**
     * Create a new item
     *
     * @access public
     * @param  string Content for the title tag
     * @param  string Content for the link tag
     * @param  string Content for the description tag
     * @return object XML_Tree_Node instance
     */
    function newItem($title, $link, $desc) {
        $item = new XML_Tree_Node("item");

        $item->setAttribute("rdf:about", $link);
        $item->addChild("title", $title);
        $item->addChild("link", htmlspecialchars($link));
        $item->addChild("description", htmlspecialchars($desc));

        return $item;
    }

    /**
     * Add new item to the RSS file
     *
     * @access public
     * @param  object XML_Tree_Node instance
     * @return void
     */
    function addItem(&$item) {
        $e = $item->getElement(array(1));
        $c = &$this->_seq->addChild("rdf:li");
        $c->setAttribute("rdf:resource", $e->content);
        $this->_root->addChild($item);
    }

    /**
     * Add a list of items to the feed
     *
     * @access protected
     * @param  array List of items
     * @return void
     */
    function __addItems($list) {
        foreach ($list as $item) {
            $node = $this->newItem($item['name'] . " " . $item['version'], "http://pear.php.net/package/" . $item['name'], $item['releasenotes']);
            $this->addItem($node);
        }
    }

    /**
     * Return string representation of the RSS feed
     *
     * @access public
     * @return void
     */
    function toString() {
        return $this->_root->get();
    }
}
?>