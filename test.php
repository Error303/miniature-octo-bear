<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);

require_once 'krumo/class.krumo.php';

$synat = array(
					"Roland" => array(
							"Jupiter" => array(
									"4" => array("goods"), 
									"6" => array("goods"),
									"8" => array("goods"),
									"80" => array("bads"),
									),
							"Juno" => array(
									),
							"Alpha Juno" => array(
									),
							),
					"Oberheim" => array(
							),
					"Korg" => array(
							"MS" => array(
									"10" => array("goods"), 
									"20" => array("goods"),
									"20 mini" => array("goods"),
									"50" => array("goods"),
									"2000" => array("bads"),
									),
							),					
					);

krumo($synat);

foreach ($synat as $key => $value) {
	print $key;
}

?>