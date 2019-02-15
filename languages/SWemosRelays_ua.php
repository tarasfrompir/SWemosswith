<?php
$dictionary = array(
 'SWemosRelays_MODULE_NAME' => 'Wemos Relay',
);
foreach ($dictionary as $k => $v) {
 if (!defined('LANG_' . $k)) {
  define('LANG_' . $k, $v);
 }
}
