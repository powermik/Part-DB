<?php
/*
    part-db version 0.1
    Copyright (C) 2005 Christoph Lechner
    http://www.cl-projects.de/

    part-db version 0.2+
    Copyright (C) 2009 K. Jacobs and others (see authors.php)
    http://code.google.com/p/part-db/

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA

    $Id$

    Changelog (sorted by date):
        [DATE]      [NICKNAME]          [CHANGES]
        2013-ß9-09   mik           - initial coding
*/

    include_once('start_session.php');

    $messages = array();
    $fatal_error = false; // if a fatal error occurs, only the $messages will be printed, but not the site content

    /********************************************************************************
    *
    *   Evaluate $_REQUEST
    *
    *********************************************************************************/

    $part_id            = isset($_REQUEST['pid'])               ? (integer)$_REQUEST['pid']             : 0;
    $labeltype          = isset($_REQUEST['labeltype'])         ? (string)$_REQUEST['labeltype']        : "small";

    $action = 'default';

    /********************************************************************************
    *
    *   Initialize Objects
    *
    *********************************************************************************/

    $html = new HTML($config['html']['theme'], $config['html']['custom_css'], 'Label');

    try
    {
        $database           = new Database();
        $log                = new Log($database);
        $current_user       = new User($database, $current_user, $log, 1); // admin
        $part               = new Part($database, $current_user, $log, $part_id);
        $footprint          = $part->get_footprint();
        $storelocation      = $part->get_storelocation();
        $manufacturer       = $part->get_manufacturer();
        $category           = $part->get_category();
        $all_orderdetails   = $part->get_orderdetails();
    }
    catch (Exception $e)
    {
        $messages[] = array('text' => nl2br($e->getMessage()), 'strong' => true, 'color' => 'red');
        $fatal_error = true;
    }

    /********************************************************************************
    *
    *   Set the rest of the HTML variables
    *
    *********************************************************************************/

    $html->use_javascript(array('validatenumber', 'popup', 'qrcode.min'));

    // global settings
    $html->set_variable('use_modal_popup',          $config['popup']['modal'], 'boolean');
    $html->set_variable('popup_width',              $config['popup']['width'], 'integer');
    $html->set_variable('popup_height',             $config['popup']['height'], 'integer');

    if (! $fatal_error)
    {
        try
        {
            // part attributes
            $html->set_variable('pid',                      $part->get_id(), 'integer');
            $html->set_variable('name',                     $part->get_name(), 'string');
            $html->set_variable('description',              $part->get_description(), 'string');
            $html->set_variable('category_full_path',       $part->get_category()->get_full_path(), 'string');
            $html->set_variable('instock',                  $part->get_instock(), 'integer');
            $html->set_variable('mininstock',               $part->get_mininstock(), 'integer');
            $html->set_variable('visible',                  $part->get_visible(), 'boolean');
            $html->set_variable('comment',                  nl2br($part->get_comment()), 'string');
            $html->set_variable('footprint_full_path',      (is_object($footprint) ? $footprint->get_full_path() : '-'), 'string');
            $html->set_variable('footprint_filename',       (is_object($footprint) ? str_replace(BASE, BASE_RELATIVE, $footprint->get_filename()) : ''), 'string');
            $html->set_variable('storelocation_full_path',  (is_object($storelocation) ? $storelocation->get_full_path() : '-'), 'string');
            $html->set_variable('storelocation_is_full',    (is_object($storelocation) ? $storelocation->get_is_full() : false), 'boolean');
            $html->set_variable('manufacturer_full_path',   (is_object($manufacturer) ? $manufacturer->get_full_path() : '-'), 'string');
            $html->set_variable('category_full_path',       (is_object($category) ? $category->get_full_path() : '-'), 'string');
            $html->set_variable('auto_order_exists',        ($part->get_instock() < $part->get_mininstock()), 'boolean');
            $html->set_variable('manual_order_exists',      ($part->get_manual_order() && ($part->get_instock() >= $part->get_mininstock())), 'boolean');
            $html->set_variable('partdetailurl',            dirname($_SERVER["SCRIPT_URI"]).'/id/1'.$part->get_id());

        }
        catch (Exception $e)
        {
            $messages[] = array('text' => nl2br($e->getMessage()), 'strong' => true, 'color' => 'red');
            $fatal_error = true;
        }
    }

    /********************************************************************************
    *
    *   Generate HTML Output
    *
    *********************************************************************************/

    $reload_link = $fatal_error ? 'show_part_info.php?pid='.$part_id : '';  // an empty string means that the...
    $html->print_header($messages, $reload_link);                           // ...reload-button won't be visible

    if (! $fatal_error)
	switch ($labeltype) {
	case "large":
        	$html->print_template('show_part_label_large');
		break;
	case "small":
	default:
        	$html->print_template('show_part_label_small');
	}

    $html->print_footer();

?>
