<?php
/**
 * ---------------------------------------------------------------------
 * Formcreator is a plugin which allows creation of custom forms of
 * easy access.
 * ---------------------------------------------------------------------
 * LICENSE
 *
 * This file is part of Formcreator.
 *
 * Formcreator is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Formcreator is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Formcreator. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 * @author    Thierry Bugier
 * @author    Jérémy Moreau
 * @copyright Copyright © 2011 - 2018 Teclib'
 * @license   GPLv3+ http://www.gnu.org/licenses/gpl.txt
 * @link      https://github.com/pluginsGLPI/formcreator/
 * @link      https://pluginsglpi.github.io/formcreator/
 * @link      http://plugins.glpi-project.org/#/plugin/formcreator
 * ---------------------------------------------------------------------
 */

class PluginInstallTest extends CommonTestCase
{

   public function setUp() {
      parent::setUp();
      self::setupGLPIFramework();
      self::login('glpi', 'glpi', true);
   }

   protected function setupGLPI() {
      global $CFG_GLPI;

      $settings = [
            'use_notifications' => '1',
      ];
      Config::setConfigurationValues('core', $settings);

      $CFG_GLPI = $settings + $CFG_GLPI;
   }

   /**
    * @engine inline
    */
   public function testInstallPlugin() {
      global $DB;

      $this->setupGLPI();

      $this->assertTrue($DB->connected, 'Problem connecting to the Database');

      $this->login('glpi', 'glpi');

      //Drop plugin configuration if exists
      $config = new Config();
      $config->deleteByCriteria(array('context' => 'formcreator'));

      // Drop tables of the plugin if they exist
      $query = 'SHOW TABLES';
      $result = $DB->query($query);
      while ($data = $DB->fetch_array($result)) {

         if (strstr($data[0], 'glpi_plugin_formcreator') !== false) {
            $DB->query('DROP TABLE '.$data[0]);
         }
      }

      self::resetGLPILogs();

      $plugin = new Plugin();
      $plugin->getFromDBbyDir("formcreator");

      ob_start(function($in) { return ''; });
      $plugin->install($plugin->fields['id']);
      ob_end_clean();

      $PluginDBTest = new PluginDB();
      $PluginDBTest->checkInstall('formcreator', 'install');

      // Check the version of the schema is saved
      $config = Config::getConfigurationValues('formcreator');
      $this->assertArrayHasKey('schema_version', $config);
      $this->assertEquals(PLUGIN_FORMCREATOR_SCHEMA_VERSION, $config['schema_version']);

      // Check the cron task is created
      $cronTask = new CronTask();
      $cronTask->getFromDBbyName(PluginFormcreatorIssue::class, 'SyncIssues');
      $this->assertFalse($cronTask->isNewItem());

      // Enable the plugin
      $plugin->activate($plugin->fields['id']);
      $this->assertTrue($plugin->isActivated('formcreator'), 'Cannot enable the plugin');

   }
}
