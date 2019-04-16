<?php
chdir(dirname(__FILE__) . '/../');
include_once './config.php';
include_once './lib/loader.php';
include_once './lib/threads.php';
set_time_limit(0);
$checked_time = 0;
include_once ("./load_settings.php");
include_once (DIR_MODULES . "control_modules/control_modules.class.php");

$ctl = new control_modules();
echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;
DebMes('Start UPNP cicle');
// берем все обьекты со свойством UPNPADDRESS (это показатель того что это УПНП устройство)
// впредь создаем обязательно такое поле для УПНП устройства
$devices = get_all_upnp_devices();
//DebMes (serialize ($devices));
// проверяем устройства на online i pravilnost UPNPADDRESS
$controladdress = array();
foreach($devices as $device) {
    $device_ip = gg($device . '.UPNPADDRESS');
    $out = search_controlURL($device_ip, $device);
    if (!in_array($out, $controladdress)) {
        $controladdress = array_merge($controladdress, $out);
    }
}
//DebMes (serialize ($controladdress));
// get subscriptions fields
$subscribs = array();
// poluchem polya
foreach($controladdress as $address) {
    // echo ($address);
    $out = get_subscription_filds($address);
    $subscribs = array_merge($subscribs, $out);
}
//DebMes (serialize ($subscribs));
// subscribe to events
foreach($subscribs as $field) {
    subscribe($field);
    //DebMes (serialize ($field));
}
// main cycle
// create socket
$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
socket_bind($socket, getLocalIp() , 54345) or die("Could not bind to socket\n");
socket_listen($socket, 20840) or die("Could not set up socket listener\n");
while (1) {
    if (time() - $checked_time > 5*60) {
        $checked_time = time();
        setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time() , 1);
    }
    // get answer
    $spawn = socket_accept($socket) or die("Could not accept incoming connection\n");
    // read client
    $input = @socket_read($spawn, 20840);
    //DebMes('telo  - ' . $input);
    socket_close($spawn);
    // ishem imya ustroystva oni otragautsya v notyfy
    $name_device = substr($input, strpos($input, "NOTIFY") + 6);
    $name_device = substr($name_device, 0, strpos($name_device, "HTTP/1.1"));
    $name_device = str_ireplace("/", "", $name_device);
    $name_device = trim($name_device);
    DebMes('notyfy from device ' . $name_device);
    // berem telo soobsheniya
    // regem zagolovki
    $input = substr($input, strpos($input, "\r\n\r\n") + 4);
    if (strlen($input) > 0) {
        DebMes('telo  - ' . $input);
        // создаем хмл документ
        $doc = new DOMDocument();
        $doc->loadXML($input);

		// check the last change answer
		$last_change = $doc->getElementsByTagName('LastChange') [0];
		$value = $last_change->nodeValue;
		if ($value) {
			//DebMes ('value - '.$value);
			$value = preg_replace('/<([[:word:]]+) val="(\d*)"/', '<$1>$2</$1', $value);
			$value = preg_replace('/<([[:word:]]+) [[:word:]]+="([[:word:]]+)" val="(\d*)"\//', '<$1$2>$3</$1$2', $value);
			$value = str_replace("></InstanceID>", ">", $value);

			DebMes ('value - '.$value);
			$doc->loadXML($value);
		}
		

        // poluchem spisok elementov
        $xpath = new DOMXpath($doc);
        $nodes = $xpath->query('//*');

        // berem ih znacheniya
        foreach($nodes as $node) {
            $f_name = $node->nodeName;
            $field = $doc->getElementsByTagName($f_name) [0];
            $value = $field->nodeValue;
            // заменяем для виключателя
            if ($f_name == 'BinaryState') {
                $f_name = 'status';
			}
			// disable fild LastChange
			if ($f_name == 'LastChange') {
                $value='';
            }
			// disable fild Event
			if ($f_name == 'Event') {
                $value='';
            }
            // disable fild SinkProtocolInfo
            if ($f_name=='SinkProtocolInfo') {
                $value='';
            }			
            // disable not implemented
            if ($value=='NOT_IMPLEMENTED') {
                $value='';
            }
            if ($field AND $value) {
                setGlobal($name_device . '.' . $f_name, $value);
            }
        }
    }
    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        // close sockets
        socket_close($socket);
        exit;
    }
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));
// ///////////////////////////////////////////////////////////////////////////////////////////////
// //////////////////////////////////////////////////////////////////////////////////////////////
// ////////////////////section for internal function////////////////////////////////////////////
// берем все обьекты со свойством UPNPADDRESS (это показатель того что это УПНП устройство)
// впредь создаем обязательно такое поле для УПНП устройства
function get_all_upnp_devices()
{
    $out = array();
    $sql = "SELECT ID, TITLE FROM objects where ID in (SELECT OBJECT_ID FROM properties Where TITLE ='UPNPADDRESS') or CLASS_ID in (SELECT CLASS_ID FROM properties Where TITLE ='UPNPADDRESS')";
    $objects = SQLSelect($sql);
    foreach($objects as $object) {
        $out[$object['ID']] = $object['TITLE'];
    }
    return $out;
}
// функция получения CONTROL_ADDRESS при его отсутствии или его ге правильности
function search_controlURL($ip_addres = '255.255.255.255', $device)
{
    // create the socket
    $socket = socket_create(AF_INET, SOCK_DGRAM, 0);
    socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, true);
    // all
    $request = 'M-SEARCH * HTTP/1.1' . "\r\n";
    $request.= 'HOST: 239.255.255.250:1900' . "\r\n";
    $request.= 'MAN: "ssdp:discover"' . "\r\n";
    $request.= 'MX: 2' . "\r\n";
    $request.= 'ST: ssdp:all' . "\r\n";
    $request.= 'USER-AGENT: Majordomo/ver-x.x UDAP/2.0 Win/7' . "\r\n";
    $request.= "\r\n";
    socket_sendto($socket, $request, strlen($request) , 0, $ip_addres, 1900);
    $response = array();
    // send the data from socket
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array(
        'sec' => '2',
        'usec' => '128'
    ));
    do {
        $buf = null;
        if (($len = @socket_recvfrom($socket, $buf, 2048, 0, $ip, $port)) == - 1) {
            echo "socket_read() failed: " . socket_strerror(socket_last_error()) . "\n";
        }
        if (!is_null($buf)) {
            $messages = explode("\r\n", $buf);
            foreach($messages as $row) {
                $i++;
                if (stripos($row, 'loca') === 0) {
                    $answer = str_ireplace("location:", "", $row);
                    $answer = trim($answer);
                    $out['address'] = $answer;
                    $out['device_name'] = $device;
                    if ((!in_array($out, $response))) {
                        $response[$i]['address'] = $answer;
                        $response[$i]['device_name'] = $device;
                    }
                }
            }
        }
    }
    while (!is_null($buf));
    socket_close($socket);
    return $response;
}
// функция получения полей для событий на устройстве
function get_subscription_filds($device)
{
    $out = array();
    // получаем XML
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $device['address']);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    // создаем хмл документ
    $doc = new DOMDocument();
    $doc->loadXML($response);
    // DebMes($response);
    // berem polya na kotorie mogna podpisatsya
    $subscribs = $doc->getElementsByTagName('eventSubURL');
    if (!$subscribs) {
        return false;
    }
    $i = 0;
    foreach($subscribs as $row) {
        $i++;
        $link = $row->nodeValue;
        if (!in_array($link, $out)) {
            $out[$i]['value'] = $link;
            $out[$i]['controlladdress'] = $device['address'];
            $out[$i]['device'] = $device['device_name'];
        }
    }
    return $out;
}
// function for sibscribe to device event
function subscribe($fields = '')
{
    $parts = parse_url($fields['controlladdress']);
    $fp = fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80, $errno, $errstr, 30);
    $request = 'SUBSCRIBE ' . $fields['value'] . ' HTTP/1.1' . "\r\n";
    $request.= 'NT: upnp:event' . "\r\n";
    $request.= 'TIMEOUT: Second-5' . "\r\n";
    $request.= 'HOST: ' . $parts['host'] . ':' . $parts['port'] . "\r\n";
    $request.= 'CALLBACK: <http://' . getLocalIp() . ':54345/' . $fields['device'] . '>' . "\r\n";
    $request.= 'Content-Length: 0' . "\r\n\r\n";
    fwrite($fp, $request);
    while (!feof($fp)) {
        $answer = fgets($fp, 128);
        if ($answer = 'HTTP/1.1 200 OK') {
            $out = true;
            break;
        }
    }
    fclose($fp);
    return $out;
}
///////////////////
