<?php
/**
 * @version $Id: soap.php 455 2018-06-04 16:27:26Z yllen $
 -------------------------------------------------------------------------
 LICENSE

 This file is part of Webservices plugin for GLPI.

 Webservices is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Webservices is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with Webservices. If not, see <http://www.gnu.org/licenses/>.

 @package   Webservices
 @author    Nelly Mahu-Lasson
 @copyright Copyright (c) 2009-2018 Webservices plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/webservices
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
 */

if (!extension_loaded("soap")) {
   header("HTTP/1.0 500 Extension soap not loaded");
   die("Extension soap not loaded");
}

ini_set("soap.wsdl_cache_enabled", "0");

define('DO_NOT_CHECK_HTTP_REFERER', 1);
include ("../../inc/includes.php");

Plugin::load('webservices', true);

Plugin::doHook("webservices");
plugin_webservices_registerMethods();

error_reporting(E_ALL);

try {
   $server = new SoapServer(null, ['uri' => '']);
   $server->setclass('PluginWebservicesSoap');

} catch (Exception $e) {
   echo $e;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   $server->handle();
}
