<?php
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime; 

$synaList = str_getcsv(include('synat.php'), ";");

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);

require_once 'rss_php.php';
require_once 'approximate-search.phps';
require_once 'krumo/class.krumo.php';

$levenMatchFactor = 3;

$synaFeed = new rss_php;
//$synafeed->load('http://muusikoiden.net/extra/rss/tori.php?category=4');
$synaFeed->load('fiidi');
$synaItems = $synaFeed->getItems(true);


function getItemsForSale($itemFeed) {

	$itemsForSale = array();

	//Check if an item is for sale
	foreach ($itemFeed as $value) {
		$string = $value['title'];	
		foreach ($string as $s) {
			$regex = '/(?<=\ään:.).*(?=.\()/';
			preg_match($regex, $s, $itemTitle);	
		}

		if ($itemTitle)	{

			//Parse item id
			$string = $value['link'];
			foreach ($string as $s) {
				$regex = '/(?<=\=).*/';
				preg_match($regex, $s, $itemId);	
			}

			//Parse item location
			$string = $value['title'];	
			foreach ($string as $s) {
				$regex = '/[^(]+(?=\)$)/';
				preg_match($regex, $s, $itemLocation);	
			}
			
			//Parse item price
			$string = $value['description'];
			foreach ($string as $s) {
				$regex = '/\d+$/';
				preg_match($regex, $s, $itemPrice);	
			}

			if (!$itemPrice) $itemPrice[0] = NULL;

			//Parse item time
			$string = $value['title'];	
			foreach ($string as $s) {
				$regex = '/.*(?=\,)/';
				preg_match($regex, $s, $itemTime);	
			}
			
			$itemFormattedTime = str_getcsv($itemTime[0], ".");
			$itemTime[0] = $itemFormattedTime[2] . "-" . $itemFormattedTime[1] . "-" . $itemFormattedTime[0];
			
			array_push($itemsForSale,array(
				'itemId'=>$itemId[0],
				'itemTitle'=>$itemTitle[0],
				'itemLocation'=>$itemLocation[0],
				'itemPrice'=>$itemPrice[0],
				'itemTime'=>$itemTime[0]));
			
				
		}
	}
	return $itemsForSale;
	
}

function matchMaker($needleItems, $haystackItems) {

	global $levenMatchFactor;
	$searchResults = array();

	foreach ($haystackItems as $itemEntry) {
		$haystackItem = $itemEntry['itemTitle'];
		$haystackId = $itemEntry['itemId'];


		$levenMatches = array();
	
		foreach ($needleItems as $needleItem) {
				if (levenshtein(strtolower($haystackItem), strtolower($needleItem)) <= $levenMatchFactor) {
					$levenMatches[] = $needleItem;
				}
			}
		
			if ($levenMatches) {
				//echo ("Osta tämä: $haystackItem - $haystackId\n");				
				//echo ("        r: " . implode(", ", $levenMatches) . "\n\n");
				$searchResults[$haystackId] = array(
					'title'=>$haystackItem,
					'location'=>$itemEntry['itemLocation'],
					'price'=>$itemEntry['itemPrice'],
					'time'=>$itemEntry['itemTime'],
					'related'=>implode(";",$levenMatches)
				);
			}
	}
	return $searchResults;
}

$searchResults = matchMaker($synaList,getItemsForSale($synaItems));

/*
foreach(PDO::getAvailableDrivers() as $driver)
    {
    echo ("$driver\n");
    }
*/

//print_r ($searchResults);

try {
	$db = new PDO('sqlite:databass');
  	$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	} catch (Exception $e) { die ($e);
}

try {
	$posts = $db->prepare('PRAGMA foreign_keys = ON;');
	$posts = $db->prepare('SELECT id,title FROM "synthItems"');
	$posts->execute();

} catch (Exception $e) { die ($e); }

$storedResults = array();
$storedId = array();

while ($post = $posts->fetchObject()):
	$id = $post->id;
	$storedResults[$id] = array('title'=>$post->title);
endwhile;

$newResults = array_diff_key($searchResults,$storedResults);

//print_r ($newResults);


foreach ($newResults as $key => $newResult) {

echo $newResult['title'];
echo ("\n");

$posts = $db->prepare('INSERT INTO synthItems (id,title,location,price,time,related) VALUES (:id,:title,:location,:price,:time,:related);');
$posts->bindValue(':id', $key, SQLITE3_INTEGER); 
$posts->bindValue(':title', $newResult['title'], SQLITE3_TEXT); 
$posts->bindValue(':location', $newResult['location'], SQLITE3_TEXT); 
$posts->bindValue(':price', $newResult['price'], SQLITE3_INTEGER); 
$posts->bindValue(':time', $newResult['time'], SQLITE3_TEXT); 
$posts->bindValue(':related', $newResult['related'], SQLITE3_TEXT); 
$posts->execute();

}






$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime);
echo ("\nAika: ".round($totaltime,2)." sekuntia\n"); 

?>
