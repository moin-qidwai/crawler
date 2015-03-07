<?php
include ("simple_html_dom.php");
$crawled_urls = array();
$found_urls = array();

function rel2abs($rel, $base) {
	if (parse_url($rel, PHP_URL_SCHEME) != '') {
		return $rel;
	}
	if ($rel[0] == '#' || $rel[0] == '?') {
		return $base . $rel;
	}
	extract(parse_url($base));
	$path = preg_replace('#/[^/]*$#', '', $path);
	if ($rel[0] == '/') {
		$path = '';
	}
	$abs = "$host$path/$rel";
	$re = array('#(/.?/)#', '#/(?!..)[^/]+/../#');
	for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {
	}
	$abs = str_replace("../", "", $abs);
	return $scheme . '://' . $abs;
}

function perfect_url($u, $b) {
	$bp = parse_url($b);
	if (($bp['path'] != "/" && $bp['path'] != "") || $bp['path'] == '') {
		if ($bp['scheme'] == "") {
			$scheme = "http";
		} else {
			$scheme = $bp['scheme'];
		}
		$b = $scheme . "://" . $bp['host'] . "/";
	}
	if (substr($u, 0, 2) == "//") {
		$u = "http:" . $u;
	}
	if (substr($u, 0, 4) != "http") {
		$u = rel2abs($u, $b);
	}
	return $u;
}

function crawl_site($u, $type = "") {

	$servername = "localhost";
	$username = "root";
	$password = "";
	try {
	    $conn = new PDO("mysql:host=$servername;dbname=yelpc", $username, $password);
	    // set the PDO error mode to exception
	    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	    //echo "Connected successfully"; 
	    }
	catch(PDOException $e)
	    {
	    echo "Connection failed: " . $e->getMessage();
	    }

	global $crawled_urls, $found_urls;
	$links = array();
	$uen = urlencode($u);
	if(!is_array($crawled_urls))
		$crawled_urls = array();
	if(!is_array($found_urls))
		$found_urls = array();
	if ((array_key_exists($uen, $crawled_urls) == 0 || $crawled_urls[$uen] < date("YmdHis", strtotime('-25 seconds', time())))) {
		$html = file_get_html($u);
		$crawled_urls[$uen] = date("YmdHis");
		if ($type == "loc") {
			if($html != false)
			{
				foreach(array_slice($html->find("ul.locations-list"), 2, 1) as $html2){
					foreach ($html2->find("a") as $li) {
						$url = perfect_url($li->href, $u);
						$enurl = urlencode($url);
						if ($url != '' && substr($url, 0, 4) != "mail" && substr($url, 0, 4) != "java" && array_key_exists($enurl, $found_urls) == 0) {
							$found_urls[$enurl] = 1;
							array_push($links, $url);
						}
					}
				}
			}
		}
		if ($type == "ablist") {
			if($html != false)
			{
				foreach($html->find("div.a-z-biz-list") as $html2){
					foreach ($html2->find("a") as $li) {
						$url = perfect_url($li->href, $u);
						$enurl = urlencode($url);
						if ($url != '' && substr($url, 0, 4) != "mail" && substr($url, 0, 4) != "java" && array_key_exists($enurl, $found_urls) == 0) {
							$found_urls[$enurl] = 1;
							
							array_push($links, $url);
							
						}
					}
				}
			}
		}
		if ($type == "listing") {
			if($html != false)
			{
				if($html->find("div.listing") != null)
				{
					foreach ($html->find("div.listing") as $html2) {
						foreach ($html2->find("a") as $li) {
							$url = perfect_url($li->href, $u);
							$enurl = urlencode($url);
							if ($url != '' && substr($url, 0, 4) != "mail" && substr($url, 0, 4) != "java" && array_key_exists($enurl, $found_urls) == 0) {
								$found_urls[$enurl] = 1;
								
								array_push($links, $url);
								
							}
						}
					}
				}
				else
				{
					foreach ($html->find("a") as $li) {
						$url = perfect_url($li->href, $u);
						$enurl = urlencode($url);
						if ($url != '' && substr($url, 0, 4) != "mail" && substr($url, 0, 4) != "java" && array_key_exists($enurl, $found_urls) == 0) {
							$found_urls[$enurl] = 1;
							
							array_push($links, $url);
						}
					}
				}
			}
		}
		if ($type == "secondlast") {
			if($html)
			{
				if($html->find('[itemprop="postalCode"]') && $html->find('[itemprop="name"]') && $html->find('[itemprop="streetAddress"]') && $html->find('[itemprop="addressLocality"]') && $html->find('[itemprop="addressRegion"]'))
				{
					$center = $html->find('[data-map-state]')[0]->{'data-map-state'};
					$center = get_string_between($center, "center", "}");
					$latitude = get_string_between($center, "latitude", ",");
					$latitude = substr($latitude, strpos($latitude, ":") + 1);
					$longitude = substr($center, strpos($center, "longitude") + 1);
					$longitude = substr($longitude, strpos($longitude, ":") + 1);
					$statement = $conn->prepare("INSERT INTO address(name, address, city, state, postal, lat, lon) VALUES(:name, :address, :city, :state, :postal, :lat, :lon)");
					$statement->execute(array(
						"name" => $html->find('[itemprop="name"]')[0]->innertext,
						"address" => $html->find('[itemprop="streetAddress"]')[0]->innertext,
						"city" => $html->find('[itemprop="addressLocality"]')[0]->innertext,
						"state" => $html->find('[itemprop="addressRegion"]')[0]->innertext,
						"postal" => $html->find('[itemprop="postalCode"]')[0]->innertext,
						"lat" => $latitude,
						"lon" => $longitude
					));
				}
				else
				{
					foreach ($html->find("a") as $li) {
						$url = perfect_url($li->href, $u);
						$enurl = urlencode($url);
						if ($url != '' && substr($url, 0, 4) != "mail" && substr($url, 0, 4) != "java" && array_key_exists($enurl, $found_urls) == 0) {
							$found_urls[$enurl] = 1;
							
							if(strpos($url, "/biz/"))	
								array_push($links, $url);
						}
					}
				}
			}
		}
		if($type == "last")
		{
			if($html)
			{
				if($html->find('[itemprop="postalCode"]') && $html->find('[itemprop="name"]') && $html->find('[itemprop="streetAddress"]') && $html->find('[itemprop="addressLocality"]') && $html->find('[itemprop="addressRegion"]'))
				{
					
					$center = $html->find('[data-map-state]')[0]->{'data-map-state'};
					$center = get_string_between($center, "center", "}");
					$latitude = get_string_between($center, "latitude", ",");
					$latitude = substr($latitude, strpos($latitude, ":") + 1);
					$longitude = substr($center, strpos($center, "longitude") + 1);
					$longitude = substr($longitude, strpos($longitude, ":") + 1);
					$statement = $conn->prepare("INSERT INTO address(name, address, city, state, postal, lat, lon) VALUES(:name, :address, :city, :state, :postal, :lat, :lon)");
					$statement->execute(array(
						"name" => $html->find('[itemprop="name"]')[0]->innertext,
						"address" => $html->find('[itemprop="streetAddress"]')[0]->innertext,
						"city" => $html->find('[itemprop="addressLocality"]')[0]->innertext,
						"state" => $html->find('[itemprop="addressRegion"]')[0]->innertext,
						"postal" => $html->find('[itemprop="postalCode"]')[0]->innertext,
						"lat" => $latitude,
						"lon" => $longitude
					));
				}
			}
		}
	}
	$conn= null;
	return $links;
}

function get_string_between($string, $start, $end){
    $string = " ".$string;
    $ini = strpos($string,$start);
    if ($ini == 0) return "";
    $ini += strlen($start);
    $len = strpos($string,$end,$ini) - $ini;
    return substr($string,$ini,$len);
}


class AsyncOperation extends Thread {

    public function __construct($arg) {
        $this->arg = $arg;
    }

    public function run() {
        if ($this->arg) {
            $ablist = crawl_site($this->arg, "ablist");
			foreach ($ablist as $abvalue) {
				$listing = crawl_site($abvalue, "listing");
				if($listing !== NULL)
				{
					$tt = new secondStage($listing);
					$tt->start();
				}
			}
        }
    }
}

class secondStage extends Thread {

    public function __construct($arg) {
        $this->arg = $arg;
    }

    public function run() {
        if ($this->arg) {
            foreach ($this->arg as $value) {
            	$last = crawl_site($value, "secondlast");
            	foreach ($last as $links) {
            		crawl_site($value, "last");
            	}
            }
        }
    }
}

$citylinks = crawl_site("http://www.yelp.com/locations", "loc");

$t = array();

foreach ($citylinks as $value) {
	$t[] = new AsyncOperation($value);
}

foreach ($t as $thread) {
	$thread->start();
}
?>
