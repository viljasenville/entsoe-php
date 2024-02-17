<?php

// Tarvittavat php-moduulit: 
// - php-xml
// - php-pdo-mysql

// Entso-e: rajapinnan tiedot
$base = "https://web-api.tp.entsoe.eu/api";
$key = "oma avain";

// MySQL-kannan tiedot
$tietokanta['palvelin'] = "kantapalvelin";
$tietokanta['kanta']    = "tietokannan nimi";
$tietokanta['kayttaja'] = "kantakäyttäjän nimi";
$tietokanta['salasana'] = "kantakäyttäjän salasana";

// Debug-moodi: haku ilman tallentamista
$debug = true;

// Varsinainen skripti alkaa
$yhteys = yhdistaTietokantaan();

haeData();
echo "Haku OK";

function haeData(){
	global $base, $key;

	$auth = '?securityToken='.$key;
	$interval = urlencode(date("Y-m-d", strtotime('-1 day')).'T00:00Z/'.date("Y-m-d", strtotime('+2 day')).'T00:00Z');
	$doc = '&documentType=A44';
	$indomain = '&in_domain=10YFI-1--------U';
	$outdomain = '&out_domain=10YFI-1--------U';

	$params = '&timeInterval='.$interval.$doc.$indomain.$outdomain;
	$url = $base.$auth.$params;

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_USERAGENT, 'API Client');
	$data = curl_exec($curl);
	curl_close($curl);

	kasitteleData($data);
}

function kasitteleData($data){
	$xml = simplexml_load_string($data);
	$fn = 'price.amount';

	foreach($xml->children()->TimeSeries as $series) {
		foreach($series->children()->Period as $day) {
			$start = $day->children()->timeInterval->children()->start;
			$alkuaikaleima = strtotime($start);

			foreach($day->children()->Point as $point){
				$pos = $point->children()->position;
				$arvo = $point->children()->$fn;

				$aikaleima = $alkuaikaleima + ($pos - 1) * 3600;
				$arvo = $arvo / 1000 * 100;
				vieKantaan($aikaleima, $arvo);
			}
		}
	}
}

function vieKantaan($aikaleima, $tuntihinta){
	global $yhteys, $debug;

	if ($debug) {
		echo "INSERT INTO tuntihinta (aikaleima, tuntihinta) VALUES ($aikaleima, $tuntihinta) ON DUPLICATE KEY UPDATE tuntihinta = $tuntihinta;\r\n";
	} else {
		$kysely = $yhteys->prepare("INSERT INTO tuntihinta (aikaleima, tuntihinta) VALUES (?, ?) ON DUPLICATE KEY UPDATE tuntihinta = ?");	
		$kysely->execute(array($aikaleima, $tuntihinta, $tuntihinta));
	}
}

function yhdistaTietokantaan(){
	global $tietokanta;

	if(!$debug)	{
		try {
			$yhteysasetukset = "mysql:host=" . $tietokanta['palvelin'] . "; dbname=" . $tietokanta['kanta'];
			$tietokantayhteys = new PDO($yhteysasetukset, $tietokanta['kayttaja'], $tietokanta['salasana']);
		} catch (PDOException $e) {
			echo $e;
		}

		$tietokantayhteys->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$tietokantayhteys->exec("SET NAMES utf8");
	}

	return $tietokantayhteys;
}

?>
