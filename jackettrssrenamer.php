<?
//-----custom config-----//
$jackettip = '192.168.1.100'; //eg. '192.168.1.100'
$jackettapikey = ''; //jacket api key
$category = '5070'; //eg '5070'
//-----custom config-----//
$jackettrsslink = 'http://'.$jackettip.':9117/api/v2.0/indexers/dmhy/results/torznab/api?apikey='.$jackettapikey.'&t=search&cat='.$category.'&limit=500&q=';
//------sample release source-----//
$Nekomoe = urlencode("喵萌奶茶屋")."+".urlencode("繁")."+1080p";
$UHA = urlencode("悠哈")."+CHT+1080p";
$DMG = urlencode("動漫國")."+".urlencode("繁");
$ktxp = urlencode("極影")."+BIG5";

$releasegroups = array(
	"All" => "",
	"Lilith" => "Lilith",
	"MMSUB" => "MMSUB",
	"Nekomoe" => $Nekomoe,
	"UHA" => $UHA,
	"極影" => $ktxp,
	"DMG" => $DMG
);

//-----Dictionary import-----//
$Dictionary = array_column(array_map('str_getcsv', file(__DIR__.'/Dictionary.csv')), 1, 0);
if(!empty($Dictionary)) {
	echo "Current Dictionary set:<br>";
	foreach($Dictionary as $find => $replace){
		echo $find." => ".$replace."<br>";
		}
	echo "<br>";
} else {echo "Cannot load Dictionary";}

//-----Regrex rule import-----//
/*$Dictionary = array_column(array_map('str_getcsv', file('Dictionary_regrex.csv')), 1, 0);
echo "Current Regrex Rule:<br>";
foreach($Dictionary as $find => $replace){
	echo $find." => ".$replace."<br>";
	}
echo "<br>";*/

//-----Start renamer each release group-----//
echo "Revised RSS URL<br>";
foreach ($releasegroups as $releasegroup => $q){
	$count = 0;
	//-----Get original RSS from Jackett-----//
	$url = $jackettrsslink.$q;
	$html = file_get_contents($url);
	//-----Check jackett is running-----//
	if(empty($html)){echo "cannot get source rss from jackett: ".$url; exit;}

	//-----release group formating-----//
	if($releasegroup == "Lilith"){
		$html = preg_replace('/\[Lilith\-Raws\]\s(.*\/)\s/', '[Lilith-Raws] ', $html);
	}
	if($releasegroup == "MMSUB"){
		$html = preg_replace('/\【MMSUB\】(.*\/)\s/', '[MMSUB] ', $html);
	}
	if($releasegroup == "UHA"){
		$html = preg_replace('/\【悠哈璃羽字幕社\】\[.*\_(.*\]\[\d)/', '[UHA-WINGS] $1', $html);
		$html = preg_replace('/\【悠哈璃羽字幕社\】\[.*\/(.*\]\[\d)/', '[UHA-WINGS] $1', $html);
	}
	if($releasegroup == "Nekomoe"){
		$html = preg_replace('/\【喵萌奶茶屋\】.*\★\[.*\/(.*\]\[\d)/', '[Nekomoekissaten] $1', $html);
		$html = preg_replace('/\【喵萌奶茶屋\】.*\★\[(.*\]\[\d)/', '[Nekomoekissaten] $1', $html);
	}

	//-----Sereise Name replacement-----//
	foreach($Dictionary as $find => $replace){
		$html = str_replace($find,$replace,$html);
		}

	//-----Episode number offset-----//
	$offset = 50;
	$html = preg_replace_callback(
	    '/Alicization War of Underworld\s-\s(\d+)/',
	    function($match) use ($offset) { return ('Alicization War of Underworld E'.($match[1] + $offset)); },
	    $html
	    );	//"Alicization War of Underworld - XX"  => "Alicization War of Underworld EXX(+50) "

	//-----logical replacement-----//
	$html = preg_replace('/\s-\s(\d+)/', ' E$1 ', $html);			//" - XX"  => " EXX "
	$html = preg_replace('/-\s(\d+)/', ' E$1 ', $html);				//"- XX"  => " EXX "
	$html = preg_replace('/\]\[(\d+)\]/', ' E$1 ', $html);			//"][XX]"  => " EXX "
	$html = preg_replace('/\[(\d+)\]/', ' E$1 ', $html);			//"[XX]"  => " EXX "
	$html = preg_replace('/\【(\d+)\】/', ' E$1 ', $html);			//"【XX】"  => " EXX "
	$html = preg_replace('/第(\d+)話/', ' E$1 ', $html);			//" 第13話 " => " EXX "
	$html = preg_replace('/第(\d+)集/', ' E$1 ', $html);			//" 第13話 " => " EXX "

	$html = preg_replace('/\[(\d+)-(\d+)\]/', ' E$1-E$2 ', $html);	//"[01-12]"  => " E01-E12 "
	$html = preg_replace('/第(\d+)-(\d+)話/', ' E$1-E$2 ', $html);	//"[01-12]"  => " E01-E12 "

	$html = preg_replace('/\s(\d+)\]\s/',' S$1', $html);			//" XX] "  => " SXX"
	$html = preg_replace('/\_(\d+)\]\s/',' S$1', $html);			//"_XX] "  => " SXX"

	//-----remove space between season and episode-----//
	$html = preg_replace('/\sS(\d+)\sE(\d+)/', ' S$1E$2', $html);	//"SXX EXX"  => " SXXEXX"

	//-----Fine tuning-----//
	$html = str_replace("【","[",$html);			//"【"  => "["
	$html = str_replace("】","]",$html);			//"】"  => "]"
	$html = str_replace("_"," ",$html);				//"_"  => " "
	$html = str_replace("End","",$html);			//"End"  => ""
	$html = str_replace("END","",$html);			//"End"  => ""
	$html = str_replace("  "," ",$html);			//"  "  => " "

	//-----Save renamed RSS file-----//
	file_put_contents(__DIR__.'/'.$releasegroup.'.xml',$html,LOCK_EX);
	$count = substr_count($html,"<item>");

	//-----Provide Full RSS Feed URL-----//
	if(filesize(__DIR__.'/'.$releasegroup.'.xml') > 0){
	echo "Full RSS Feed URL of <b>".$releasegroup."</b> (".$count." items)"."
	: http://".$_SERVER['HTTP_HOST'].str_replace("jackettrssrenamer.php",$releasegroup.".xml",$_SERVER['REQUEST_URI'])."<br>";}
}

//-----ENF-----//
exit;
//regrex calculator: https://www.phpliveregex.com/#tab-preg-replace//
?>
