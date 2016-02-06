<?php

function scan_format($scan) {
  if(!count($scan)) return;
  if($scan['hasmili']) {
    for($j=0;$j < count($scan['mili_fleets']);$j++){
      $scan['mili_formated_fleets'][$j] = formatmiliscanline($scan['mili_fleets'][$j],$j>0);
  }
  }
  if($scan['hasnews']) {
    $newsdata = preg_replace("/(Angriff(?::|))/i","<span class=\"tbl_red\">$1</span>",$scan['news_newsdata']);
    $newsdata = preg_replace("/(Verteidigung(?::|))/i","<span class=\"tbl_green\">$1</span>",$newsdata);
    $newsdata = preg_replace("/(Rückzug(?::|))/i","<span class=\"tbl_blue\">$1</span>",$newsdata);
    $newsdata = preg_replace("/\s(\d{1,4}):(\d{1,2})\s(\w+)/i"," <a href=\"scans.php?gala=$1&pos=$2".$scan['expand_backlink'].
      "\">$1:$2 $3</a> ",$newsdata);
    preg_match_all("/^(?:(.*?(?:<\/span>)|.*?)\s)(.*?)$/im",$newsdata,$lines,PREG_SET_ORDER);
    $scan['news_formated_newsdata'] = $lines;
  }
  $maxrows = 0;
  $simulator_link = array();
  if($scan['hasgscan']) {
    array_push($simulator_link,
      "deff9=".$scan['gscan_rubium'],
      "deff10=".$scan['gscan_pulsar'],
      "deff11=".$scan['gscan_coon'],
      "deff12=".$scan['gscan_centurion'],
      "deff13=".$scan['gscan_horus']);
    $maxrows = 6;
    $scan['colspan']++;
  }
  if($scan['hassector']) {
    array_push($simulator_link,
    "kristall=".$scan['sector_kristall'],
    "metall=".$scan['sector_metall']);
    $maxrows = 8;
    $scan['colspan']++;
  }
  if($scan['hasunit']) {
    array_push($simulator_link,
    "deff0=".$scan['jaeger'],
    "deff1=".$scan['bomber'],
    "deff2=".$scan['fregatten'],
    "deff3=".$scan['zerstoerer'],
    "deff4=".$scan['kreuzer'],
    "deff5=".$scan['schlachter'],
    "deff6=".$scan['traeger'],
    "deff7=".$scan['kleptoren'],
    "deff8=".$scan['cancris']);
    $maxrows = 10;
    $scan['colspan']++;
  }
  if(count($simulator_link)) {
    $scan['simulator_link'] = "simulator.php?".join("&",$simulator_link);
  }
  $scan['maxrows'] = $maxrows;
  if($scan['colspan'] > 0) {
    $scan['width'] = floor(100 / $scan['colspan']);
  }
  $scan = generateirclink($scan);
	return $scan;
}


#
# liefert den numeric - Parameterwert oder default
#

function param_num($param,$default=null,$post=false) {
  if ($post) {
    $value = $_POST[$param];
  } else {
    $value = $_REQUEST[$param];
  }
  if (!is_numeric($value) || !isset($value) || $value < 1) $value = $default;
  return $value;
}

#
# liefert den Stringwert
#
function param_str($param,$post=false) {
  $value = "";
  if ($post) {
    $value = trim($_POST[$param]);
  } else {
    $value = trim($_REQUEST[$param]);
  }
  return $value;
}

#
# liefert den Wochentag
#
function getWeekday($id){
  $day = "";
  switch ($id) {
    case 0 : {$day = "Montag";break;}
    case 1 : {$day = "Dienstag";break;}
    case 2 : {$day = "Mittwoch";break;}
    case 3 : {$day = "Donnerstag";break;}
    case 4 : {$day = "Freitag";break;}
    case 5 : {$day = "Samstag";break;}
    case 6 : {$day = "Sonntag";break;}
    default: $day = "error";
  }
  return $day;
}

$co = "00";
$bg = "14";
$co2 = "01";
$bg2 = "15";


function generateIrcFleetLink($fleet,$username,$gala,$pos,$time,$date) {
  $col1 = "01";
  $bg1 = "07";

  $col2 = "01";
  $bg2 = "14";
  $col3 = "15";
  $bg3 = "14";

  $link[] = "".$col1.",".$bg1."NL Flotte ".$username." (".$gala.":".$pos.")";
  $link[] = "".$col2.",".$bg2."Cleptoren: ".$col3.",".$bg3."".$fleet['kleptoren']."".$col2.",".$bg2." Cancris:
".$col3.",".$bg3."".$fleet['cancris']."".$col2.",".$bg2." Fregatten: ".$col3.",".$bg3."".$fleet['fregatten']."".$col2.",".$bg2;
  $link[] = "".$col2.",".$bg2."Zerstörer: ".$col3.",".$bg3."".$fleet['zerstoerer']."".$col2.",".$bg2." Kreuzer:
".$col3.",".$bg3."".$fleet['kreuzer']."".$col2.",".$bg2." Träger: ".$col3.",".$bg3."".$fleet['traeger']."".$col2.",".$bg2;
  $link[] = "".$col2.",".$bg2."Jäger: ".$col3.",".$bg3."".$fleet['jaeger']."".$col2.",".$bg2." Bomber:
".$col3.",".$bg3."".$fleet['bomber']."".$col2.",".$bg2." Schlachter: ".$col3.",".$bg3."".$fleet['schlachter']."".$col2.",".$bg2;
  $link[] = "".$col2.",".$bg2."letztes update ".$col3.",".$bg3."".$date."".$col2.",".$bg2." um ".$col3.",".$bg3."".$time."".$col2.",".$bg2;
  for ($i=0;$i<count($link);$i++) {
    $result .= $link[$i]."\\n";
  }
  return $result;
}

function generate_irc_fleet($fleet){
  global $co,$bg,$co2,$bg2;
  $vo = "".$co.",".$bg;
  $hi = "".$co2.",".$bg2;
  if ($fleet['jaeger']) {
    $link .= " ".$vo." ".$fleet['jaeger']." ".$hi." Jäger";
  }
  if ($fleet['bomber']) {
    $link .= " ".$vo." ".$fleet['bomber']." ".$hi." Bomber";
  }
  if ($fleet['fregatten']) {
    $link .= " ".$vo." ".$fleet['fregatten']." ".$hi." Fregatten";
  }
  if ($fleet['zerstoerer']) {
    $link .= " ".$vo." ".$fleet['zerstoerer']." ".$hi." Zerstörer";
  }
  if ($fleet['kreuzer']) {
    $link .= " ".$vo." ".$fleet['kreuzer']." ".$hi." Kreuzer";
  }
  if ($fleet['schlachter']) {
    $link .= " ".$vo." ".$fleet['schlachter']." ".$hi." Schlachter";
  }
  if ($fleet['traeger']) {
    $link .= " ".$vo." ".$fleet['traeger']." ".$hi." Träger";
  }
  if ($fleet['kleptoren']) {
    $link .= " ".$vo." ".$fleet['kleptoren']." ".$hi." Kleptoren";
  }
  if ($fleet['cancris']) {
    $link .= " ".$vo." ".$fleet['cancris']." ".$hi." Cancris";
  }
  return $link;
}

function generate_irc_user_fleet($num,$fleet,$user) {
  global $co,$bg,$co2,$bg2;
  $res = array();
  $vo = "".$co.",".$bg;
  $hi = "".$co2.",".$bg2;
  $link[] = "".$co.",".$bg."Flotte von [".$user['tag']."] ".$user['nick']." (".$user['gala'].":".$user['pos'].")";
  switch ($num) {
     case 0:
      $link[] = $hi."Im Orbit: ".generate_irc_fleet($fleet);
       break;
     case 1:
      $link[] = $hi."Flotte 1: ".generate_irc_fleet($fleet);
       break;
     case 2:
      $link[] = $hi."Flotte 2: ".generate_irc_fleet($fleet);
       break;
     case 3:
      $link[] = generate_irc_fleet($fleet);
       break;
  }
  return join("\\n",$link)."\\n";
}

function generate_irc_inc_summary($inc,$atter,$deffer) {
  global $co,$bg,$co2,$bg2;
  $res = array();
  $vo = "".$co.",".$bg;
  $hi = "".$co2.",".$bg2;
  $link[] = $vo."Zusammenfassung von [".$inc['tag']."] ".$inc['nick']." (".$inc['gala'].":".$inc['pos'].")";
  $link[] = $hi."Angreifer: ".generate_irc_fleet($atter);
  $link[] = $hi."Verteidiger: ".generate_irc_fleet($deffer);
  return join("\\n",$link)."\\n";
}

function formatdate($pattern,$date){
  if(!$date) return;
  $today = date($pattern);
  $yesterday = date($pattern,mktime(0,0,0,date("m"),date("d")-1,date("Y")));
  $tomorrow = date($pattern,mktime(0,0,0,date("m"),date("d")+1,date("Y")));
  if ($date == $today) {
    $result = "heute";
  } elseif ($date == $yesterday) {
    $result = "gestern";
  } elseif ($date == $tomorrow) {
    $result = "morgen";
  } else {
    $result = $date;
  }
  return $result;
}

function formatdate_unix($pattern,$date){
  if(!$date) return;
  $today = date("d.m.Y",time());
  $yesterday = date("d.m.Y",time()-86400);
  $tomorrow = date("d.m.Y",time()+86400);
  $time = date("d.m.Y",$date);
  if ($time == $today) {
    $result = "heute";
  } elseif ($time == $yesterday) {
    $result = "gestern";
  } elseif ($time == $tomorrow) {
    $result = "morgen";
  } else {
    $result = date($pattern,$date);
  }
  return $result;
}

function showPageBar($page,$pages,$link,$pagestring="page",$css="",$site="Seite : ") {
  //Nochmal die sicherheitsabfrage, muss schon im skript passieren
//  global $sessionlink;
  if ($pages < $page && $pages != 0) {
    $page = $pages;
  }

  $pattern = (!stristr($link,"?")) ? "?" : "&";

  if ($css) {
    $cssclass = " class = \"$css\" ";
  }
  if ($pages <= 1) return;
  $page_link = "<span ".$cssclass."> ".$site." [";
  if ($page!=1) {
    $page_link .= "&nbsp;&nbsp;<a ".$cssclass."href=\"".$link.$pattern.
                  $pagestring."=1".$sessionlink.
                  "\">&laquo;</a>&nbsp;&nbsp;<a ".$cssclass." href=\"".
                  $link.$pattern.$pagestring."=".($page-1).
                  $sessionlink."\">&#139;</a>";
  }

  if ($page>=6) {
    $page_link .= "&nbsp;&nbsp;<a ".$cssclass."href=\"".$link.$pattern.
                  $pagestring."=".($page-5).$sessionlink.
                  "\">...</a>";
  }

  $pagex = ($page+4>=$pages) ? $pages : $page+4;

  for($i=$page-4 ; $i<=$pagex ; $i++) {
    if($i<=0) $i=1;
    if($i==$page) {
      $page_link .= "<span class=\"tbl_red\">&nbsp;&nbsp;$i</span>";
    } else {
      $page_link .= "&nbsp;&nbsp;<a ".$cssclass."href=\"".$link.$pattern.
                    $pagestring."=".$i.$sessionlink."\">".$i."</a>";
    }
  }
  if(($pages-$page)>=5) {
    $page_link .= "&nbsp;&nbsp;<a ".$cssclass." href=\"".$link.$pattern.$pagestring."=".
                  ($page+5).$sessionlink."\">...</a>";
  }

  if($page!=$pages && $pages != 0) {
    $page_link .= "&nbsp;&nbsp;<a ".$cssclass." href=\"".$link.$pattern.$pagestring."=".
                  ($page+1).$sessionlink."\">&#155;</a>&nbsp;&nbsp;<a ".$cssclass." href=\"".
                  $link.$pattern.$pagestring."=".$pages.$sessionlink.
                  "\">&raquo;</a>";
  }
  $page_link .= "&nbsp;&nbsp;]</span>";

  return $page_link;
} // end func

function formatmiliscanline($fleet,$showstatus=true) {
  $line = "";
  if ($fleet['jaeger']) $line .= "&nbsp;".$fleet['jaeger']." Jäger ";
  if ($fleet['bomber']) $line .= "&nbsp;".$fleet['bomber']." Bomber ";
  if ($fleet['fregatten']) $line .= "&nbsp;".$fleet['fregatten']." Fregatten ";
  if ($fleet['zerstoerer']) $line .= "&nbsp;".$fleet['zerstoerer']." Zerstörer ";
  if ($fleet['kreuzer']) $line .= "&nbsp;".$fleet['kreuzer']." Kreuzer ";
  if ($fleet['schlachter']) $line .= "&nbsp;".$fleet['schlachter']." Schlachter ";
  if ($fleet['traeger']) $line .= "&nbsp;".$fleet['traeger']." Träger ";
  if ($fleet['kleptoren']) $line .= "&nbsp;".$fleet['kleptoren']." Kleptoren ";
  if ($fleet['cancris']) $line .= "&nbsp;".$fleet['cancris']." Cancris ";
  if($showstatus) {
    if ($fleet['type']) {
        $fleet['type'] = preg_replace("/(Angriff\w+)/i","<span class=\"tbl_red\">$1</span>",$fleet['type']);
        $fleet['type'] = preg_replace("/(Verteidigung\w+)/i","<span class=\"tbl_green\">$1</span>",$fleet['type']);
        $fleet['type'] = preg_replace("/(Rück\w+)/i","<span class=\"tbl_blue\">$1</span>",$fleet['type']);
        $status[] = $fleet['type'];
    }
    if ($fleet['dir']) $status[] = "".$fleet['dir']."";
    if (!$fleet['type'] && !$fleet['dir']) $status[] = "n/a";
    $line .= "&nbsp;&lt;".join(" ",$status)."&gt;";
  }
  return $line;
}

function _format_irc_miliscanline($fleet,$printstatus=true){
  $col2 = "01";
  $bg2 = "14";
  $col3 = "15";
  $bg3 = "14";

  $col1 = "01";
  $bg1 = "07";
  $color1 =  "".$col3.",".$bg3;
  $color2 =  "".$col2.",".$bg2;
  $items = array ("Jäger"=>"jaeger","Bomber"=>"bomber","Fregatten"=>"fregatten","Zerstörer"=>"zerstoerer","Kreuzer"=>"kreuzer",
  "Schlachter"=>"schlachter","Träger"=>"traeger","Kleptoren"=>"kleptoren","Cancris"=>"cancris");
  $line = array();
  foreach($items as $key => $val){
    if($fleet[$val]) {
      $line[] = $color1.$fleet[$val].$color2." $key";
    }
  }
  $line = join(" ",$line);
  if($printstatus) {
    if ($fleet['type']) {
        $fleet['type'] = preg_replace("/(Angriff\w+)/i", "04,".$bg2."$1".$color2,$fleet['type']);
        $fleet['type'] = preg_replace("/(Verteid\w+)/i", "09,".$bg2."$1".$color2,$fleet['type']);
        $fleet['type'] = preg_replace("/(Rück\w+)/i", "10,".$bg2."$1".$color2,$fleet['type']);
        $status[] = $fleet['type'];
    }
    if ($fleet['dir']) $status[] = $fleet['dir'];
    if (!$fleet['type'] && !$fleet['dir']) $status[] = "N/A";
    $line .= " $color1<".join(" ",$status).">$color2";
  }
  return $line;
}

function generateirclink($scan,$popup=false) {

  $col2 = "01";
  $bg2 = "14";
  $col3 = "15";
  $bg3 = "14";


  $col1 = "01";
  $bg1 = "07";

  if ($scan['hassector']) {
    #$scan['punkte'] = substr_replace(strrev(chunk_split(strrev($scan['punkte']),3,'.')),'',0,1);
    $link = array();
    $link[] = "".$col1.",".$bg1."NL SektorScan (".$scan['sector_prec']."%) ".$scan['nick']." (".$scan['gala'].":".$scan['pos'].")";
    $link[] = "".$col2.",".$bg2."Punkte: ".$col3.",".$bg3.$scan['sector_punkte2'].
      " ".$col2.",".$bg2."Asteroiden: ".$col3.",".$bg3.$scan['sector_roids']."".$col2.",".$bg2;
    $link[] = "".$col2.",".$bg2."Schiffe: ".$col3.",".$bg3."".$scan['sector_ships']."".
        $col2.",".$bg2." Geschütze: ".$col3.",".$bg3."".$scan['sector_deff'];
    $link[] = "".$col2.",".$bg2."Metall-Exen: ".$col3.",".$bg3."".$scan['sector_metall']."".
        $col2.",".$bg2." Kristall-Exen: ".$col3.",".$bg3."".$scan['sector_kristall']."".$col2.",".$bg2;
    $link[] = "".$col1.",".$bg1."Alter: ".$scan['sector_scanage']." ";
    $scan['sector_link'] = urlencode(join("<br>",$link)."<br>");
    $scan['sector_copy'] = join("\\n",$link)."\\n";
  }
  if ($scan['hasunit']) {
    $link = array();
    $link[] = "".$col1.",".$bg1."NL Unitscan (".$scan['unit_prec']."%) ".$scan['nick']." (".$scan['gala'].":".$scan['pos'].")";
    $link[] = "".$col2.",".$bg2."Cleptoren: ".$col3.",".$bg3."".$scan['kleptoren']."".$col2.",".$bg2.
        " Cancris: ".$col3.",".$bg3."".$scan['cancris']."".$col2.",".$bg2." Fregatten: ".$col3.",".$bg3."".$scan['fregatten']."".$col2.",".$bg2;
    $link[] = "".$col2.",".$bg2."Zerstörer: ".$col3.",".$bg3."".$scan['zerstoerer']."".$col2.",".$bg2.
        " Kreuzer: ".$col3.",".$bg3."".$scan['kreuzer']."".$col2.",".$bg2." Träger: ".$col3.",".$bg3."".$scan['traeger']."".$col2.",".$bg2;
    $link[] = "".$col2.",".$bg2."Jäger: ".$col3.",".$bg3."".$scan['jaeger']."".$col2.",".$bg2.
        " Bomber: ".$col3.",".$bg3."".$scan['bomber']."".$col2.",".$bg2." Schlachter: ".$col3.",".$bg3."".$scan['schlachter']."".
        $col2.",".$bg2;
    $link[] = "".$col1.",".$bg1."Alter: ".$scan['unit_scanage']." ";

    $scan['unit_link'] = urlencode(join("<br>",$link)."<br>");
    $scan['unit_copy'] = join("\\n",$link)."\\n";
  }
  if ($scan['hasgscan']) {
    $link = array();
    $link[] = "".$col1.",".$bg1."NL Geschützscan (".$scan['gscan_prec']."%) ".$scan['nick']." (".$scan['gala'].":".$scan['pos'].")";
    $link[] = "".$col2.",".$bg2."Horus: ".$col3.",".$bg3."".$scan['gscan_horus']."".$col2.",".$bg2.
      " Rubium: ".$col3.",".$bg3."".$scan['gscan_rubium']."".$col2.",".$bg2."".$col2.",".$bg2.
      " Pulsar: ".$col3.",".$bg3."".$scan['gscan_pulsar']."".$col2.",".$bg2." Coon: ".$col3.",".$bg3."".$scan['gscan_coon']."".
      $col2.",".$bg2." Centurion: ".$col3.",".$bg3."".$scan['gscan_centurion']."".$col2.",".$bg2;
    $link[] = "".$col1.",".$bg1."Alter: ".$scan['gscan_scanage']." ";

    $scan['gscan_link'] = urlencode(join("<br>",$link)."<br>");
    $scan['gscan_copy'] = join("\\n",$link)."\\n";
  }
  if ($scan['hasnews']) {
    $link = array();
    $link[] = "".$col1.",".$bg1."NL Newsscan (".$scan['news_prec']."%) ".$scan['nick']." (".$scan['gala'].":".$scan['pos'].")";
    $newsdata = $scan['news_newsdata'];
    $newsdata = preg_replace("/Verteidigung/i","\x02\x0309,".$bg2."Verteidigung\x03$col2,$bg2\x02",$newsdata);
    $newsdata = preg_replace("/Angriff/i","\x02\x0304,".$bg2."Angriff\x03$col2,$bg2\x02",$newsdata);
    $newsdata = preg_replace("/Rückzug/i","\x02\x0310,".$bg2."Rückzug\x03$col2,$bg2\x02",$newsdata);
    $newsdata = preg_replace("/Ankunft:/i","\x02Ankunft:\x02",$newsdata);
    $newsdata = preg_replace("/Heute/i","\x02Heute\x02",$newsdata);
    $newsdata = preg_replace("/[\f\r\t]/is","",$newsdata);
    foreach ( split("\n",$newsdata) as $line){
      $link[] =  "".$col2.",".$bg2.$line;
    }
    $link[] = "".$col1.",".$bg1."Alter: ".$scan['news_scanage']." ";

    $scan['news_link'] = urlencode(join("<br>",$link)."<br>");
    $scan['news_copy'] = join("\\n",$link)."\\n";
  }
  if ($scan['hasmili']) {
    $link = array();
    $link[] = "".$col1.",".$bg1."NL Miliscan (".$scan['mili_prec']."%) ".$scan['nick']." (".$scan['gala'].":".$scan['pos'].")";
    $link[] = "".$col2.",".$bg2."Orbit "._format_irc_miliscanline($scan['mili_fleets'][0],false);
    $link[] = "".$col2.",".$bg2."Flotte 1: "._format_irc_miliscanline($scan['mili_fleets'][1]);
    $link[] = "".$col2.",".$bg2."Flotte 2: "._format_irc_miliscanline($scan['mili_fleets'][2]);
    $link[] = "".$col1.",".$bg1."Alter: ".$scan['mili_scanage']." ";

    $scan['mili_link'] = urlencode(join("<br>",$link)."<br>");
    $scan['mili_copy'] = join("\\n",$link)."\\n";
  }
  return $scan;
}

function searchArray($key,$value,$array) {
  for ($i=0;$i < count($array);$i++) {
    if ($array[$i][$key] == $value){
      return $array[$i];
    }
  }
  return;
}

# liefert einen String mit den werten des arrys mit kommas getrennt

function getSQLArray($array){
  $s = "";
  if (count($array)){
    $s = $array[0];
    for($i=1;$i < count($array);$i++){
      $s .= ",".$array[$i];
    }
    $s = "(".$s.")";
  }
  return $s;
}

function getArrayFromList($list,$key=0){
  $return = array();
  if ($list) {
    for($i=0;$i < count($list);$i++){
      if (isset($list[$i][$key])) {
        $return[] = $list[$i][$key];
      }
    }
  }
  return $return;
}

function gnticktime($eta) {
  //$diff = $eta % 15 - 15 + date("i") % 15;
  $mod1 = $eta % 15;
  if($mod1) $mod1 = 15 - $mod1;
  $mod2 = date("i") % 15;
  $diff = $mod2 - $mod1;
  $eta = $eta - $diff;
  return $eta;
}

function gnarrival($eta) {
  return mktime(date("H"),date("i") + gnticktime($eta)+1,0,date("m"),date("d"),date("Y"));
}

function getScanAge($time) { 
  $duration = time() - $time; 
  $stunden = floor($duration / 3600); 
  $duration = $duration % 3600;
  $minuten = floor($duration / 60);
  return sprintf("%02d:%02d h",$stunden,$minuten);
}

?>
