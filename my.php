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
							
							if(strpos($url, "/biz/"))
								file_put_contents("output.txt",$url."\r\n",FILE_APPEND);
						}
					}
				}
			}
		}
		if ($type == "last") {
			if($html)
			{
				foreach ($html->find("a") as $li) {
					$url = perfect_url($li->href, $u);
					$enurl = urlencode($url);
					if ($url != '' && substr($url, 0, 4) != "mail" && substr($url, 0, 4) != "java" && array_key_exists($enurl, $found_urls) == 0) {
						$found_urls[$enurl] = 1;
						
						if(strpos($url, "/biz/"))	
							file_put_contents("output.txt",$url."\r\n",FILE_APPEND);
					}
				}
			}
		}
	}
	return $links;
}

$citylinks = crawl_site("http://www.yelp.com/locations", "loc");

class AsyncOperation extends Thread {

    public function __construct($arg) {
        $this->arg = $arg;
    }

    public function run() {
        if ($this->arg) {
            $ablist = crawl_site($this->arg, "ablist");
			foreach ($ablist as $abvalue) {
				$listing = crawl_site($abvalue, "listing");
				foreach ($listing as $list) {
					$last = crawl_site($list, "last");
					
				}
			}
        }
    }
}

foreach ($citylinks as $value) {
	$t = new AsyncOperation($value);
	$t->start();
}

