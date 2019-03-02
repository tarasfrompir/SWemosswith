<?php

chdir(dirname(__FILE__) . '/../');

include_once './config.php';
include_once './lib/loader.php';
include_once './lib/threads.php';
set_time_limit(0);
$checked_time = 0;
include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");
$ctl = new control_modules();
echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;

DebMes ('Start');
// берем все обьекты со свойством UPNPADDRESS (это показатель того что это УПНП устройство)
// еще обязательное свойство для них ipaddress
// впредь создаем обязательно такое поле для УПНП устройства
$devices = get_all_upnp_devices();
//DebMes (serialize ($devices));

// проверяем устройства на online i pravilnost UPNPADDRESS
$controladdress =array();
foreach( $devices as $device ) {
	$i++;
	$out = chek_upnp_address($device);
    if ($out) {
		$controladdress[$i] = getGlobal($device.'.UPNPADDRESS');
	} 
}

// podpisivaem ustroystve na sobitiya vhodit massiv adresov
// vihodit resultat podpiska na sobitiya
// get subscriptions fields
$subscribs = array();
// poluchem polya
foreach( $controladdress as $address ) {
    $out = get_subscription_filds($address);
    $subscribs = array_merge($subscribs, $out);
	DebMes($address);
}
// subscribe to events
foreach( $subscribs as $field ) {
    subscribe($field);
}

// main cycle

    // create socket
    $socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
    socket_bind($socket, getLocalIp(), 54321) or die("Could not bind to socket\n");
    socket_listen($socket, 1024) or die("Could not set up socket listener\n");

	
while (1) {
    if (time() - $checked_time > 10)   {
        $checked_time = time();
        setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);

	
		// get answer
		$spawn = socket_accept($socket) or die("Could not accept incoming connection\n");   
		// read client input
		$input = socket_read($spawn, 2048) or die("Could not read input\n");
		socket_close($spawn);
		//DebMes('telo  - '.$input);
		
		// ishem uuid ustroystva oni otragautsya v notyfy
		$uuid_device = substr($input, strpos($input, "NOTIFY") + 6);
		$uuid_device = trim($uuid_device);
		$uuid_device = str_replace('/', "", $uuid_device);
		$uuid_device = substr($uuid_device, 0, 41);
        //vibiraem ustroystvo
        $device_notified = getObjectsByProperty('UUID','=',$uuid_device);
		Debmes ('imya ustroystva'.$device_notified[0]);
		
		// berem telo soobsheniya
		// regem zagolovki
		$input = substr($input, strpos($input, "\r\n\r\n") + 4);

		
	    // создаем хмл документ
        $doc = new \DOMDocument();
        $doc->loadXML($input);
		// poluchem spisok elementov
		$xpath = new \DOMXpath( $doc );
        $nodes = $xpath->query( '//*' );
        // berem ih znacheniya
		foreach( $nodes as $node ) {
			$f_name = $node->nodeName;
			$field = $doc->getElementsByTagName($f_name)[0];
			$value = $field->nodeValue;
            // заменяем для виключателя
		    if ($f_name=='BinaryState') {
			  $f_name='status' ;
			}
			if ($field AND $value) {
			  setGlobal($device_notified[0].'.'.$f_name, $value);
			}
        }

         $subscribs = $doc->getElementsByTagName('eventSubURL');
    }       
      


    if (file_exists('./reboot') || IsSet($_GET['onetime'])){
      // close sockets
      socket_close($socket);
      exit;
   }

}

DebMes("Unexpected close of cycle: " . basename(__FILE__));
	
	
/////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////section for internal function////////////////////////////////////////////


// функция получения полей для событий на устройстве 
function get_subscription_filds($upnpaddress) {
    $out = array();
    // получаем XML
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $upnpaddress);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    // создаем хмл документ
    $doc = new \DOMDocument();
    $doc->loadXML($response);
    //DebMes($response);
	// berem polya na kotorie mogna podpisatsya
    $subscribs = $doc->getElementsByTagName('eventSubURL');
	$uuid = $doc->getElementsByTagName('UDN');
    if (!$subscribs){ 
	    return false;
	}
	$i = 0;
	foreach( $subscribs as $row ) {
		$i ++;
		$link = $row->nodeValue;
		if (!in_array($link, $out)) {
            $out[$i]['value'] = $link;
            $out[$i]['controlladdress'] = $upnpaddress;
			$out[$i]['callback'] = $uuid[0]->nodeValue;
		}
    }
    return $out;
}
	
	
// function for sibscribe to device event
function subscribe($row='') {
    $parts=parse_url($row['controlladdress']);
    $fp = fsockopen($parts['host'],isset($parts['port'])?$parts['port']:80,$errno, $errstr, 30);
    $request = 'SUBSCRIBE '.$row['value'].' HTTP/1.1'."\r\n";
    $request .= 'NT: upnp:event'."\r\n";
    $request .= 'TIMEOUT: Second-600'."\r\n";
    $request .= 'HOST: '.$parts['host'].':'.$parts['port']."\r\n";
    $request .= 'CALLBACK: <http://'.getLocalIp().':54321/'.$row['callback'].'>'."\r\n";
    $request .= 'Content-Length: 0'."\r\n\r\n";

    fwrite($fp, $request);
    while (!feof($fp)) {
       $answer = fgets ($fp,128);
		if ($answer = 'HTTP/1.1 200 OK') {
			$out = true;
			break;
		}
    }
    fclose($fp);
	return $out;
}


// берем все обьекты со свойством UPNPADDRESS (это показатель того что это УПНП устройство)
// впредь создаем обязательно такое поле для УПНП устройства
function get_all_upnp_devices() {
//$out = getObjectsByProperty('UPNPADDRESS');
$out = array();
$classes=SQLSelect("SELECT * FROM properties WHERE TITLE='UPNPADDRESS'");
foreach( $classes as $class ) {
	if ($class['OBJECT_ID']) {
		$object=SQLSelectOne("SELECT * FROM objects WHERE ID='".$class['OBJECT_ID']."'");
		$out [$object['ID']] = $object['TITLE'];
	} else if ($class['CLASS_ID']) {
        $objects=SQLSelect("SELECT * FROM objects WHERE CLASS_ID='".$class['CLASS_ID']."'");
	    foreach( $objects as $object ) {
	         $out [$object['ID']] = $object['TITLE'];
	    }
	}
}
return $out;

// poluchem polya
foreach( $controladdress as $address ) {
    $out = get_subscription_filds($address);
    $subscribs = array_merge($subscribs, $out);
	DebMes($address);
}
return $out;
}

// проверем UPNPADDRESS and UUID и если он не правильный то меняем его в свойствах устройства
function chek_upnp_address($device) {
	$upnpaddress = getGlobal($device.'.UPNPADDRESS');
	$ip = getGlobal($device.'.ipaddress');
	
	// проверяем онлайн ли устройство
	if (!ping($ip)) {
		return false;
	}
	// proveryaem na pravilnost upnp adresa
    // получаем XML
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $upnpaddress);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    // proverka na otvet
    $retcode = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
	// esli ne получили ответа то контрол адресс не правильный
	if ($retcode!=200) {
		// berem noviy adres upnp nekotorie menyaut ih
		$upnpaddress = search_UPNPADDRESS($ip);		
		setGlobal($device.'.UPNPADDRESS', $upnpaddress);
		// proveryaem na pravilnost upnp adresa
        // получаем XML
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $upnpaddress);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        // создаем хмл документ
	    $doc = new \DOMDocument();
		$doc->loadXML($response);
		// berem polya na kotorie mogna podpisatsya
		$uuid = $doc->getElementsByTagName('UDN');
		$uuid = $uuid[0]->nodeValue;
		setGlobal($device.'.UUID', $uuid);
	}
    return true;
}

    // функция получения CONTROL_ADDRESS при его отсутствии или его ге правильности
function search_UPNPADDRESS($ip_addres = '255.255.255.255') {
    //create the socket
    $socket = socket_create(AF_INET, SOCK_DGRAM, 0);
    socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, true);
    //all
    $request  = 'M-SEARCH * HTTP/1.1'."\r\n";
    $request .= 'HOST: 239.255.255.250:1900'."\r\n";
    $request .= 'MAN: "ssdp:discover"'."\r\n";
    $request .= 'MX: 2'."\r\n";
    $request .= 'ST: ssdp:all'."\r\n";
    $request .= 'USER-AGENT: Majordomo/ver-x.x UDAP/2.0 Win/7'."\r\n";
    $request .= "\r\n";
        
    socket_sendto($socket, $request, strlen($request), 0, $ip_addres, 1900);
    // send the data from socket
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>'2', 'usec'=>'128'));
    do {
        $buf = null;
        if (($len = @socket_recvfrom($socket, $buf, 2048, 0, $ip, $port)) == -1) {
            echo "socket_read() failed: " . socket_strerror(socket_last_error()) . "\n";
        }
        if(!is_null($buf)){
            $messages = explode("\r\n", $buf);
                foreach( $messages as $row ) {
                    if( stripos( $row, 'loca') === 0 ) {
                        $response = str_ireplace( 'location: ', '', $row );
						break;
                    }
                }
        }
    } while(!is_null($buf));
    socket_close($socket);
    $response = str_ireplace("Location:", "", $response);
    return $response;
}