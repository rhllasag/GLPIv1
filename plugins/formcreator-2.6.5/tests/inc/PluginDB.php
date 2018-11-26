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

class PluginDB extends PHPUnit\Framework\Assert{

   public function checkInstall($pluginname='', $when='') {
      global $DB;

      if ($pluginname == '') {
         return;
      }

      $comparaisonSQLFile = "plugin_".$pluginname."_empty.sql";
      // See http://joefreeman.co.uk/blog/2009/07/php-script-to-compare-mysql-database-schemas/

      $file_content = file_get_contents(GLPI_ROOT."/plugins/".$pluginname."/install/mysql/".$comparaisonSQLFile);
      $a_lines = explode("\n", $file_content);

      $a_tables_ref = [];
      $current_table = '';
      foreach ($a_lines as $line) {
         if (strstr($line, "CREATE TABLE ")
                 OR strstr($line, "CREATE VIEW")) {
            $matches = [];
            preg_match("/`(.*)`/", $line, $matches);
            $current_table = $matches[1];
         } else {
            if (preg_match("/^`/", trim($line))) {
               $line = preg_replace('/\s+/', ' ', $line);
               $s_line = explode("`", $line);
               $s_type = explode("COMMENT", $s_line[2]);
               $s_type[0] = trim($s_type[0]);
               $s_type[0] = str_replace(" COLLATE utf8_unicode_ci", "", $s_type[0]);
               $s_type[0] = str_replace(" CHARACTER SET utf8", "", $s_type[0]);
               $a_tables_ref[$current_table][$s_line[1]] = str_replace(",", "", $s_type[0]);
            }
         }
      }

      // * Get tables from MySQL
      $a_tables_db = [];
      $a_tables = [];
      // SHOW TABLES;
      $query = "SHOW TABLES";
      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         if (strstr($data[0], "formcreator")) {

            $data[0] = str_replace(" COLLATE utf8_unicode_ci", "", $data[0]);
            $data[0] = str_replace("( ", "(", $data[0]);
            $data[0] = str_replace(" )", ")", $data[0]);
            $a_tables[] = $data[0];
         }
      }

      $toIgnore = [];
      foreach ($a_tables as $table) {
         $query = "SHOW CREATE TABLE ".$table;
         $result = $DB->query($query);
         while ($data=$DB->fetch_array($result)) {
            if (!isset($data['Create Table'])) {
               // This is not a table (a view for example)
               $toIgnore[$table] = $table;
               continue;
            }
            $a_lines = explode("\n", $data['Create Table']);

            foreach ($a_lines as $line) {
               if (strstr($line, "CREATE TABLE ")
                       OR strstr($line, "CREATE VIEW")) {
                  $matches = [];
                  preg_match("/`(.*)`/", $line, $matches);
                  $current_table = $matches[1];
               } else {
                  if (preg_match("/^`/", trim($line))) {
                     $line = preg_replace('/\s+/', ' ', $line);
                     $s_line = explode("`", $line);
                     $s_type = explode("COMMENT", $s_line[2]);
                     $s_type[0] = trim($s_type[0]);
                     $s_type[0] = str_replace(" COLLATE utf8_unicode_ci", "", $s_type[0]);
                     $s_type[0] = str_replace(" CHARACTER SET utf8", "", $s_type[0]);
                     $s_type[0] = str_replace(",", "", $s_type[0]);
                     $a_tables_db[$current_table][$s_line[1]] = $s_type[0];
                  }
               }
            }
         }
      }

      $a_tables_ref_tableonly = [];
      foreach ($a_tables_ref as $table=>$data) {
         if (!isset($toIgnore[$table])) {
            $a_tables_ref_tableonly[] = $table;
         }
      }
      $a_tables_db_tableonly = [];
      foreach ($a_tables_db as $table=>$data) {
         $a_tables_db_tableonly[] = $table;
      }

       // Compare
      $tables_toremove = array_diff($a_tables_db_tableonly, $a_tables_ref_tableonly);
      $tables_toadd = array_diff($a_tables_ref_tableonly, $a_tables_db_tableonly);

      // See tables missing or to delete
      $this->assertEquals(count($tables_toadd), 0, 'Tables missing '.$when.' '.print_r($tables_toadd, TRUE));
      $this->assertEquals(count($tables_toremove), 0, 'Tables to delete '.$when.' '.print_r($tables_toremove, TRUE));

      // See if fields are same
      foreach ($a_tables_db as $table=>$data) {
         if (isset($a_tables_ref[$table])) {
            $fields_toremove = array_diff_assoc($data, $a_tables_ref[$table]);
            $fields_toadd = array_diff_assoc($a_tables_ref[$table], $data);
            $diff = "======= DB ============== Ref =======> ".$table."\n";
            $diff .= print_r($data, TRUE);
            $diff .= print_r($a_tables_ref[$table], TRUE);

            // See tables missing or to delete
            $this->assertEquals(count($fields_toadd), 0, 'Fields missing/not good in '.$when.' '.$table.' '.print_r($fields_toadd, TRUE)." into ".$diff);
            $this->assertEquals(count($fields_toremove), 0, 'Fields to delete in '.$when.' '.$table.' '.print_r($fields_toremove, TRUE)." into ".$diff);

         }
      }

   }
}

