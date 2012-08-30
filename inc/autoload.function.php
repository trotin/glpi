<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

include_once (GLPI_ROOT."/config/based_config.php");
include_once (GLPI_ROOT."/config/define.php");

/**
 * Is the script launch in Command line ?
 *
 * @return boolean
**/
function isCommandLine() {
   return (!isset($_SERVER["SERVER_NAME"]));
}


/**
 * Determine if an object name is a plugin one
 *
 * @param $classname    class name to analyze
 *
 * @return false or an object containing plugin name and class name
**/
function isPluginItemType($classname) {

   if (preg_match("/Plugin([A-Z][a-z0-9]+)([A-Z]\w+)/",$classname,$matches)) {
      $plug           = array();
      $plug['plugin'] = $matches[1];
      $plug['class']  = $matches[2];
      return $plug;
   }
   // Standard case
   return false;
}



/// Translation functions
/// since version 0.84

/**
 * For translation
 *
 * @param $str : string
 *
 * @return translated string
**/
function __($str) {
   global $TRANSLATE;

   $trans = $TRANSLATE->_($str);
   // Wrong call when plural defined
   if (is_array($trans)) {
      return $trans[0];
   }
   return  $trans;
}


/**
 * For translation
 *
 * @param $str    string
 *
 * @return protected string (with htmlentities)
**/
function __s($str) {
   return htmlentities(__($str), ENT_QUOTES, 'UTF-8');
}


/**
 * For translation
 *
 * @since version 0.84
 *
 * @param $ctx string    context
 * @param $str string   to translate
 *
 * @return protected string (with htmlentities)
**/
function _sx($ctx, $str) {
   return htmlentities(_x($ctx, $str), ENT_QUOTES, 'UTF-8');
}


/**
 * to delete echo in translation
 *
 * @param $str : string
 *
 * @return echo string
**/
function _e($str) {
   echo __($str);
}


/**
 * For translation
 *
 * @param $sing      string in singular
 * @param $plural    string in plural
 * @param $nb               to select singular or plurial
 *
 * @return translated string
**/
function _n($sing, $plural, $nb) {
   global $TRANSLATE;

   return $TRANSLATE->plural($sing, $plural, $nb);
}


/**
 * For translation
 *
 * @since version 0.84
 *
 * @param $sing      string in singular
 * @param $plural    string in plural
 * @param $nb               to select singular or plurial
 *
 * @return protected string (with htmlentities)
**/
function _sn($sing, $plural, $nb) {
   global $TRANSLATE;

   return htmlentities($TRANSLATE->plural($sing, $plural, $nb), ENT_QUOTES, 'UTF-8');
}


/**
 * For context in translation
 *
 * @param $ctx string   context
 * @param $str string   to translate
 *
 * @return string
**/
function _x($ctx, $str) {

   // simulate pgettext
   $msg   = $ctx."\004".$str;
   $trans = __($msg);

   if ($trans == $msg) {
      // No translation
      return $str;
   }
   return $trans;
}


/**
 * Echo for context in translation
 *
 * @param $ctx string   context
 * @param $str string   to translated
 *
 * @return string
**/
function _ex($ctx, $str) {

   // simulate pgettext
   $msg   = $ctx."\004".$str;
   $trans = __($msg);

   if ($trans == $msg) {
      // No translation
      echo $str;
   }
   echo $trans;
}


/**
 * For context in plural translation
 *
 * @param $ctx       string   context
 * @param $sing      string   in singular
 * @param $plural    string   in plural
 * @param $nb                 to select singular or plurial
 *
 * @return string
**/
function _nx($ctx, $sing, $plural, $nb) {

   // simulate pgettext
   $singmsg    = $ctx."\004".$sing;
   $pluralmsg  = $ctx."\004".$plural;
   $trans      = _n($singmsg, $pluralmsg, $nb);

   if ($trans == $singmsg) {
      // No translation
      return $sing;
   }
   if ($trans == $pluralmsg) {
      // No translation
      return $plural;
   }
   return $trans;
}


/**
 * To load classes
 *
 * @param $classname : class to load
**/
function glpi_autoload($classname) {
   global $DEBUG_AUTOLOAD, $CFG_GLPI;
   static $notfound = array('xStates'    => true,
                            'xAllAssets' => true, );

   // empty classname or non concerted plugin
   if (empty($classname) || is_numeric($classname)) {
      return false;
   }

   $dir = GLPI_ROOT . "/inc/";
   if ($plug = isPluginItemType($classname)) {
      $plugname = strtolower($plug['plugin']);
      $dir      = GLPI_ROOT . "/plugins/$plugname/inc/";
      $item     = strtolower($plug['class']);
      // Is the plugin activate ?
      // Command line usage of GLPI : need to do a real check plugin activation
      if (isCommandLine()) {
         $plugin = new Plugin();
         if (count($plugin->find("directory='$plugname' AND state=".Plugin::ACTIVATED)) == 0) {
            // Plugin does not exists or not activated
            return false;
         }
      } else {
         // Standard use of GLPI
         if (!in_array($plugname,$_SESSION['glpi_plugins'])) {
            // Plugin not activated
            return false;
         }
      }

   } else {
      // Is ezComponent class ?
      if (preg_match('/^ezc([A-Z][a-z]+)/',$classname,$matches)) {
         include_once(GLPI_EZC_BASE);
         ezcBase::autoload($classname);
         return true;
      }
      // Is Zend class ?
      if (preg_match('/^Zend/',$classname,$matches)) {
         if (GLPI_ZEND_PATH) {
            set_include_path(GLPI_ZEND_PATH . PATH_SEPARATOR . get_include_path());
         }
         require_once("Zend/Loader.php");

         Zend_Loader::loadClass($classname);
         return true;
      }
      // Do not try to load phpcas using GLPi autoload
      if (preg_match('/^CAS_.*/', $classname)) {
         return false;
      }

      $item = strtolower($classname);
   }

   // No errors for missing classes due to implementation
   if (!in_array($item,$CFG_GLPI['missingclasses'])){
      if (file_exists("$dir$item.class.php")) {
         include_once("$dir$item.class.php");
         if (isset($_SESSION['glpi_use_mode'])
             && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)) {
            $DEBUG_AUTOLOAD[] = $classname;
         }

      } else if (!isset($notfound["x$classname"])) {
         // trigger an error to get a backtrace, but only once (use prefix 'x' to handle empty case)
//          trigger_error("GLPI autoload : file $dir$item.class.php not founded trying to load class '$classname'");
         $notfound["x$classname"] = true;
      }
   }
}

// Use spl autoload to allow stackable autoload.
spl_autoload_register('glpi_autoload');

?>