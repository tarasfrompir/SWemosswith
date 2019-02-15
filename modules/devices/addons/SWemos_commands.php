<?php
if (defined('DISABLE_SIMPLE_DEVICES') && DISABLE_SIMPLE_DEVICES == 1) return;

/*
 * array('level' => $level, 'message' => $ph, 'member_id' => $member_id)
 * $details['BREAK'] = 1 / 0
 */
@include_once(ROOT . 'languages/' . $this->name . '_' . SETTINGS_SITE_LANGUAGE . '.php');
@include_once(ROOT . 'languages/' . $this->name . '_default' . '.php');

if ($device_type == 'wemos_relay') {
 if (preg_match('/' . LANG_DEVICES_PATTERN_TURNON . '/uis', $command)) {
  sayReplySafe(LANG_TURNING_ON . ' ' . $device_title . $add_phrase, 2);
  $run_code .= "callMethodSafe('$linked_object.turnOn');";
  $opposite_code .= "callMethodSafe('$linked_object.turnOff');";
  $processed = 1;
  $reply_confirm = 1;
 }
 elseif (preg_match('/' . LANG_DEVICES_PATTERN_TURNOFF . '/uis', $command)) {
  sayReplySafe(LANG_TURNING_OFF . ' ' . $device_title . $add_phrase, 2);
  $run_code .= "callMethodSafe('$linked_object.turnOff');";
  $opposite_code .= "callMethodSafe('$linked_object.turnOn');";
  $processed = 1;
  $reply_confirm = 1;
 }
}
