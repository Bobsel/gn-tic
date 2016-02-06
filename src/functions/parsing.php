<?php

/*searchScanLine($array,$pattern,$i=0) {
  for ($j=&$i;$j<count($array);$j++) {
    if (preg_match($pattern,$array[$j][1],$matches)) {
      return $matches;
    }
  }
}*/


#
# generiert aus einem String einen regex, dabei werden nummern, sonderzeichen usw.
# durch den jeweiligen regex ausdruck ersetzt
#
function generateRegex($string) {
  $string = preg_replace("/\d/s","\\d",$string);
  $string = preg_replace("/\(/s","\\(",$string);
  $string = preg_replace("/\)/s","\\)",$string);
  $string = preg_replace("/\|/s","\\|",$string);
  $string = preg_replace("/\^/s","\\^",$string);
  $string = preg_replace("/\s/s","\\s",$string);
  $string = preg_replace("/\[/s","\\[",$string);
  $string = preg_replace("/\]/s","\\]",$string);
  $string = preg_replace("/\+/s","\\+",$string);
  $string = preg_replace("/\*/s","\\*",$string);
  $string = preg_replace("/\?/s","\\?",$string);
  $string = preg_replace("/\//s","\\/",$string);
  $string = preg_replace("/\{/s","\\{",$string);
  $string = preg_replace("/\}/s","\\}",$string);
  return $string;
}

function parseWurstFleet($content) {
  $content = _deleteIrcTags($content);
  $_fleet = array(
    "jaeger","bomber","fregatten","zerstoerer","kreuzer","schlachter","traeger","kleptoren","cancris"
  );
  if(preg_match("'^.*?Jäger:\s*?(\d+?)[^\d]*?Bomber:\s*?(\d+?)[^\d]*?Fregatten:\s*?(\d+?)[^\d]*?Zerstörer:\s*?(\d+?)[^\d]*?Kreuzer:\s*?(\d+?)[^\d]*?Schlachter:\s*?(\d+?)[^\d]*?Träger:\s*?(\d+?)[^\d]*?Cleps:\s*?(\d+?)[^\d]*?Cancris:\s*?(\d+)'im",$content,$subs)) {
    $fleet = array();
    foreach ($_fleet as $i => $key) {
      $fleet[$key] = $subs[$i+1];
    }
    return $fleet;
  } else {
    return false;
  }
}

#
# parst nach Flotteninfos
#
function parseFleet($line) {
	$fleet_keys = array("jaeger","bomber","fregatten","zerstoerer","kreuzer","traeger","schlachter","kleptoren","cancris");
  $fleet = array();
	preg_match("/([\d,\.]+?)[^\d<]*?(?:Jäger|Jäg)/is",$line,$results);
  if ($results[1]) $fleet['jaeger'] = $results[1];
  preg_match("/([\d,\.]+?)[^\d<]*?(?:Bomber|Bom)/is",$line,$results);
  if ($results[1]) $fleet['bomber'] = $results[1];
  preg_match("/([\d,\.]+?)[^\d<]*?(?:Fregatten|Fre)/is",$line,$results);
  if ($results[1]) $fleet['fregatten'] = $results[1];
  preg_match("/([\d,\.]+?)[^\d<]*?(?:Zerstörer|Zer)/is",$line,$results);
  if ($results[1]) $fleet['zerstoerer'] = $results[1];
  preg_match("/([\d,\.]+?)[^\d<]*?(?:Kreuzer|Kre)/is",$line,$results);
  if ($results[1]) $fleet['kreuzer'] = $results[1];
  preg_match("/([\d,\.]+?)[^\d<]*?(?:Träger|Trä|Trägerschiffe)/is",$line,$results);
  if ($results[1]) $fleet['traeger'] = $results[1];
  preg_match("/([\d,\.]+?)[^\d<]*?(?:Schlachter|Ssch|Schlachtschiffe|Schl)/is",$line,$results);
  if ($results[1]) $fleet['schlachter'] = $results[1];
  preg_match("/([\d,\.]+?)[^\d<]*?(?:Kleptoren|Clep|Kaperschiffe|Kaper|Kap)/is",$line,$results);
  if ($results[1]) $fleet['kleptoren'] = $results[1];
  preg_match("/([\d,\.]+?)[^\d<]*?(?:Cancris|Schild|Schutz|Schutzschiffe|Schu)/is",$line,$results);
  if ($results[1]) $fleet['cancris'] = $results[1];
  foreach($fleet_keys as $key) {
  	if($fleet[$key]) {
  		$fleet[$key] = preg_replace("/(?:\.|,)/is","",$fleet[$key]);
  	}
  }
  
/*  if(preg_match("/[<\[]\s*?Im \s*?[>\]]/is",$line,$results)) {
    $fleet['status'] = 0;
    $fleet['return_flight'] = 0;
  } elseif(preg_match("/[<\[]\s*?([^\s]+?)\s([^\s]+?)\s*?[>\]]/is",$line,$results)) {
    $fleet['type'] = $results[1];
    $fleet['dir'] = $results[2];
    $type = strtolower($fleet['type']);
    if($type == "angriffsflug") {
      $fleet['status'] = 1;
      $fleet['return_flight'] = 0;
    }elseif($type == "verteidigungsflug") {
      $fleet['status'] = 2;
      $fleet['return_flight'] = 0;
    }elseif($type == "rückflug") {
      $fleet['status'] = 0;
      $fleet['return_flight'] = 1;
    }
  }*/

  if(preg_match("/[<\[]\s*?Orbit\s*?[>\]]/is",$line,$results)) {
    $fleet['status'] = 0;
    $fleet['return_flight'] = 0;
  } elseif(preg_match("/[<\[]\s*?([^\s]+?)?\s([^\s]+?)?\s*?[>\]]/is",$line,$results)) {
    $fleet['type'] = $results[1];
    $fleet['dir'] = $results[2];
    $type = strtolower($fleet['type']);
    if($type == "angriffsflug") {
      $fleet['status'] = 1;
      $fleet['return_flight'] = 0;
    }elseif($type == "verteidigungsflug") {
      $fleet['status'] = 2;
      $fleet['return_flight'] = 0;
    }elseif($type == "rückflug") {
      $fleet['status'] = 0;
      $fleet['return_flight'] = 1;
    }
  }
  
#  echo "jäger: ".$fleet['jaeger']."<br>";
#  echo "bomber: ".$fleet['bomber']."<br>";
#  echo "fregatten: ".$fleet['fregatten']."<br>";
 # echo "zerstoerer: ".$fleet['zerstoerer']."<br>";
#  echo "kreuzer: ".$fleet['kreuzer']."<br>";
#  echo "schlachter: ".$fleet['schlachter']."<br>";
#  echo "träger: ".$fleet['traeger']."<br>";
#  echo "kleptoren: ".$fleet['kleptoren']."<br>";
#  echo "cancris: ".$fleet['cancris']."<br>";
#  echo "type: ".$fleet['type']."<br>";
#  echo "dir: ".$fleet['dir']."<br>";
  return $fleet;
}

function _deleteIrcTags($data) {
  $data = preg_replace("/\x03\d{1,2},\d{1,2}/sm","",$data);
  $data = preg_replace("/\x03\d{1,2}/sm","",$data);
  $data = preg_replace("/\x02/sm","",$data);
  $data = preg_replace("/\x0F/sm","",$data);
  return $data;
}

function removeColorAndFormat($data) {
  $data = preg_replace("/\x03\d{1,2},\d{1,2}/sm","",$data);
  $data = preg_replace("/\x03\d{1,2}/sm","",$data);
  $data = preg_replace("/\x02/sm","",$data);
  $data = preg_replace("/\x0F/sm","",$data);
  $data = preg_replace("/\x03/sm","",$data);
	return $data;
}

#
# durchsucht einen textblock nach Scans
#
function parseScan($data) {
#  $data=trim($data);
	#lines
  preg_match_all("/^(.*?)[\x0A]*$/sm",$data,$result,PREG_SET_ORDER);
  #$colregex = "(?:\x03\d{1,2},\d{1,2}|)";
	
  $scans = array();
  // parsing step
  $step = 0;
  $identregex = "";
  $type = "";
  $scan = array();

  for($i=0;$i<count($result);$i++) {
    $actualline = $result[$i][1];
	  $nocolor = removeColorAndFormat($actualline); 
    //scan first line

    // gn sector
    if (preg_match("/(.*?)Galaxy-Network SektorScan \((\d+?)%\)\s(.+?)\s\((\d+?):(\d+?)\)/i",$nocolor,$line)) {
      $scan = array();
      $scan['type'] = "sector";
      $scan['prec'] = trim($line[2]);
      $scan['nick'] = trim($line[3]);
      $scan['gala'] = trim($line[4]);
      $scan['pos'] = trim($line[5]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "gn_sector";
      continue;
    }
    
    // gn unit
    if (preg_match("/(.*?)Galaxy-Network EinheitenScan \((\d+?)%\)\s(.+?)\s\((\d+?):(\d+?)\)/i",$nocolor,$line)) {
      $scan = array();
      $scan['type'] = "unit";
      $scan['prec'] = trim($line[2]);
      $scan['nick'] = trim($line[3]);
      $scan['gala'] = trim($line[4]);
      $scan['pos'] = trim($line[5]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "gn_unit";
      continue;
    }
    // gn gscan
    if (preg_match("/(.*?)Galaxy-Network Geschützscan\s*?\((\d+?)%\)\s(.+?)\s\((\d+?):(\d+?)\)/i",$nocolor,$line)) {
      $scan = array();
      $scan['type'] = "gscan";
      $scan['prec'] = trim($line[2]);
      $scan['nick'] = trim($line[3]);
      $scan['gala'] = trim($line[4]);
      $scan['pos'] = trim($line[5]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "gn_gscan";
      continue;
    }
    // gn miliscan
    if (preg_match("/(.*?)(:?Galaxy-Network Miliscan|Militärscan) \s*?\((\d+?)%\)\s(.+?)\s\((\d+?):(\d+?)\)/i",$nocolor,$line)) {
      $scan = array();
      $scan['type'] = "mili";
      $scan['prec'] = trim($line[2]);
      $scan['nick'] = trim($line[3]);
      $scan['gala'] = trim($line[4]);
      $scan['pos'] = trim($line[5]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "gn_mili";
      continue;
    }
    // gn newsscan
    if (preg_match("/(.*?)Galaxy-Network Newsscan\s*?\((\d+?)%\)\s(.+?)\s\((\d+?):(\d+?)\)/i",$nocolor,$line)) {
      $scan = array();
      $scan['type'] = "news";
      $scan['prec'] = trim($line[2]);
      $scan['nick'] = trim($line[3]);
      $scan['gala'] = trim($line[4]);
      $scan['pos'] = trim($line[5]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "gn_news";
      continue;
    }


        // wurst +n86 gscan
    if(preg_match("'(.*?)Geschützscan\s\((\d+)%\)\s(.+?)\s\((\d+):(\d+)\)'i",$nocolor,$line)) {
      $scan['type'] = "gscan";
      $scan['prec'] = trim($line[2]);
//      if(strlen($line[3])) $scan['svs'] = trim($line[3]);
      $scan['nick'] = trim($line[3]);
      $scan['gala'] = trim($line[4]);
      $scan['pos'] = trim($line[5]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "wurst_gscan";
    }    
    
        // wurst +athene unit
    if(preg_match("'(.*?)Einheitenscan.*?[\(\[]\s*?(\d+)%(?:\s*@\s*(\d+)\s*svs|.*?)[\)\]]\s*(.+?)\s*?[\(\[](\d+):(\d+)[\)\]]'i",$nocolor,$line)) {
      $scan['type'] = "unit";
      $scan['prec'] = trim($line[2]);
      if(strlen($line[3])) $scan['svs'] = trim($line[3]);
      $scan['nick'] = trim($line[4]);
      $scan['gala'] = trim($line[5]);
      $scan['pos'] = trim($line[6]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "wurst_unit";
    }

    // wurst +athene miliscan
    if(preg_match("'(.*?)Militärscan.*?[\(\[]\s*?(\d+)%(?:\s*@\s*(\d+)\s*svs|.*?)[\)\]]\s*(.+?)\s*?[\(\[](\d+):(\d+)[\)\]]'i",$nocolor,$line)) {
      $scan['type'] = "mili";
      $scan['prec'] = trim($line[2]);
      if(strlen($line[3])) $scan['svs'] = trim($line[3]);
      $scan['nick'] = trim($line[4]);
      $scan['gala'] = trim($line[5]);
      $scan['pos'] = trim($line[6]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "wurst_mili";
    }

//##################################### Edit Gonz #####################################

// Vasi sekscan
//Sektorscan [100%] firefly [126:1]
//Geschützscan [100%] firefly [126:1]

if (preg_match("/(.*?)Sektorscan\s\[(\d+?)%\]\s(.*?)\s\[(\d+?):(\d+?)\]/i",$nocolor,$line)) {
      $scan['type'] = "sector";
      $scan['prec'] = trim($line[2]);
      $scan['nick'] = trim($line[3]);
      $scan['gala'] = trim($line[4]);
      $scan['pos'] = trim($line[5]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "vasi_sector";
      echo($type);
    }    

// Kerrigan sekscan
    
if(preg_match("/(.*?)Sektorscan\s*?\[(\d+?)%\]\s(.+?)\s\[(\d+?):(\d+?)\]/i",$nocolor,$line)) {
      $scan['type'] = "sector";
      $scan['prec'] = trim($line[2]);
      $scan['nick'] = trim($line[3]);
      $scan['gala'] = trim($line[4]);
      $scan['pos'] = trim($line[5]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "kerrigan_sector";
    }    

// Kerrigan unitscan
// Einheitenscan [100%] S4vi3r-.- [21:4]
    
if(preg_match("/(.*?)Einheitenscan\s*?\[(\d+?)%\]\s(.+?)\s\[(\d+?):(\d+?)\]/i",$nocolor,$line)) {
      $scan['type'] = "unit";
      $scan['prec'] = trim($line[2]);
      $scan['nick'] = trim($line[3]);
      $scan['gala'] = trim($line[4]);
      $scan['pos'] = trim($line[5]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step=1;
      $type = "kerrigan_unit";
    }    
    
// Kerrigan miliscan
    
if(preg_match("/(.*?)Militärscan\s*?\[(\d+?)%\]\s(.+?)\s\[(\d+?):(\d+?)\]/i",$nocolor,$line)) {
      $scan['type'] = "mili";
      $scan['prec'] = trim($line[2]);
      $scan['nick'] = trim($line[3]);
      $scan['gala'] = trim($line[4]);
      $scan['pos'] = trim($line[5]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "kerrigan_mili";
    }    

/*  sector

Sektorscan (100% @ 985 sVs) Brayn (540:1).
Punkte: 9.189.969 Asteroiden: 21
Schiffe: 1251 Geschütze: 1085
Metall-Exen: 158 Kristall-Exen: 74
Ilos Scansklave (71:1) - KiBO, Ilo: nix - SC, NL & Freunde: 1x - rest: 2x -
*/

if (preg_match("/(.*?)Sektorscan \((\d+?)%\s@\s(\d+?)\ssVs\)\s(.*?)\s\((\d+?):(\d+?)\)/i",$nocolor,$line)) {
      $scan = array();
      $scan['type'] = "sector";
      $scan['prec'] = trim($line[2]);
      $scan['svs'] = trim($line[3]);
      $scan['nick'] = trim($line[4]);
      $scan['gala'] = trim($line[5]);
      $scan['pos'] = trim($line[6]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "sweep_sector";
      continue;
    }
/* sweep gscan
Geschützscan (100% @ 5000 sVs) duffyduck3 (186:3)
Rubium: 57 Pulsar: 10 Coon: 8 Centurion: 5 Abfangjäger: 699
Ilos Scansklave (71:1) - KiBO, Ilo: nix - SC, NL & Freunde: 1x - rest: 2x -
*/
    if (preg_match("/(.*?)Geschützscan\s*?\((\d+?)%\s@\s(\d+?)\ssVs\)\s(.*?)\s\((\d+?):(\d+?)\)/i",$nocolor,$line)) {
      $scan = array();
      $scan['type'] = "gscan";
      $scan['prec'] = trim($line[2]);
      $scan['nick'] = trim($line[4]);
      $scan['gala'] = trim($line[5]);
      $scan['pos'] = trim($line[6]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "sweep_gscan";
      continue;
    }
    
//Geschützscan [100%] firefly [126:1]
//LO: 3.000 | LR: 500 | MR: 100 | SR: 90 | AJ: 2.000
//150:1 - Vasi scheisst die Wand an!

    if (preg_match("/(.*?)Geschützscan\s\[(\d+?)%\]\s(.*?)\s\[(\d+?):(\d+?)\]/i",$nocolor,$line)) {
      $scan = array();
      $scan['type'] = "gscan";
      $scan['prec'] = trim($line[2]);
      $scan['nick'] = trim($line[3]);
      $scan['gala'] = trim($line[4]);
      $scan['pos'] = trim($line[5]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "vasi_gscan";
      echo("vasi");
      continue;
    }        
    
        // gn_ingame unit
    if (preg_match("/(.*?)Einheitenscan\sErgebnis\s\(Genauigkeit\s(\d+?)%\)/i",$nocolor,$line)) {
      $scan = array();
      $scan['type'] = "unit";
      $scan['prec'] = trim($line[2]);
//      $scan['nick'] = trim($line[3]);
//      $scan['gala'] = trim($line[4]);
//      $scan['pos'] = trim($line[5]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "ingame_unit";
      continue;
    }
    

  //yasp sector scan

/*
Sektorscan [100%] Greenman [125:9]
Punkte: 69.314.484 × Asteroiden: 90
Schiffsanzahl: 15.223 × Geschützanzahl: 1
Metallextraktoren: 608 × Kristallextraktoren: 143
Scan von: csL [566:7] - Sv: 300
Kristall verscannt, heute: 18.000, gesamt: 48.000
*/

// Sektorscan (100% @ 985 sVs) Brayn (540:1).


if (preg_match("/(.*?)Sektorscan\s\([\d+?]%\s@\s(\d+?)\ssVs\)\s(.*?)\s\((\d+?):(\d+?)\)/i",$nocolor,$line)) {
      $scan = array();
      $scan['type'] = "sector";
      $scan['prec'] = trim($line[2]);
      $scan['svs'] = trim($line[3]);
      $scan['nick'] = trim($line[4]);
      $scan['gala'] = trim($line[5]);
      $scan['pos'] = trim($line[6]);
      $identregex = $line[1];
      if ($identregex) {$identregex = generateregex($identregex);}
      $step++;
      $type = "yasp_sector";
      continue;
    }


    
//##################################### Edit Gonz #####################################    
    
    // gn sector scan
    if($type =="gn_sector") {
      switch ($step) {
        case 1:
          if(preg_match("/^".$identregex."Punkte:\s*?(.*)/i",$nocolor,$line)) {
            $scan['punkte'] = trim(preg_replace("/\./s","",$line[1]));
            $step++;
          }
        continue;
        break;
        case 2:
          if(preg_match("/^".$identregex."Schiffe:\s*?(.*?)\s*?Verteidigung:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['ships'] = trim($line[1]);
            $scan['deff'] = trim($line[2]);
            $step++;
          }
        continue;
        break;
        case 3:
          if(preg_match("/^".$identregex."M-Extraktoren:\s*?(.*?)\s*?K-Extraktoren:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['metall'] = trim($line[1]);
            $scan['kristall'] = trim($line[2]);
            $step++;
          }
        continue;
        break;
        case 4:
          if(preg_match("/^".$identregex."Asteroiden:\s*?(\d+)/i",$nocolor,$line)) {
           $scan['roids'] = trim($line[1]);
           $step++;
           $scans[] = $scan;
          }
        continue;
        break;
        case 5:
          if(preg_match("/^".$identregex."Galaxy-Network SektorScan \(\s*?(.+?)\s*?Svs\)(\s(.+?)\s\((\d+?):(\d+?)\)|)/i",$nocolor,$line)) {
            $last_scan = &$scans[count($scans)-1];
            $last_scan['svs'] = trim($line[1]);
            if (strlen(trim($line[3]))) {
              $last_scan['scanner'] = trim($line[3])." (".trim($line[4]).":".trim($line[5]).")";
            }
            $step=0;
            $type = "";
          }
        continue;
        break;
      }
    }
    //gn unit scan
    if ($type == "gn_unit") {
            switch ($step) {
        case 1:
          if(preg_match("/^".$identregex."Kleptoren:\s*?(\d+?)\s*?Cancris:\s*?(\d+?)\s*?Fregatten:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['kleptoren'] = trim($line[1]);
            $scan['cancris'] = trim($line[2]);
            $scan['fregatten'] = trim($line[3]);
            $step++;
          }
        continue;
        break;
        case 2:
          if(preg_match("/^".$identregex."Zerstörer:\s*?(\d+?)\s*?Kreuzer:\s*?(\d+?)\s*?Träger:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['zerstoerer'] = trim($line[1]);
            $scan['kreuzer'] = trim($line[2]);
            $scan['traeger'] = trim($line[3]);
            $step++;
          }
        continue;
        break;
        case 3:
          if(preg_match("/^".$identregex."Jäger:\s*?(\d+?)\s*?Bomber:\s*?(\d+?)\s*?Schlachter:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['jaeger'] = trim($line[1]);
            $scan['bomber'] = trim($line[2]);
            $scan['schlachter'] = trim($line[3]);
            $step++;
            $scans[] = $scan;
          }
        continue;
        break;
        case 4:
          if(preg_match("/^".$identregex."Galaxy-Network Einheitenscan \(\s*?(.+?)\s*?Svs\)(\s(.+?)\s\((\d+?):(\d+?)\)|)/i",$nocolor,$line)) {
            $last_scan = &$scans[count($scans)-1];
            $last_scan['svs'] = trim($line[1]);
            if (strlen(trim($line[3]))) {
              $last_scan['scanner'] = trim($line[3])." (".trim($line[4]).":".trim($line[5]).")";
            }
            $step=0;
            $type = "";
          }
        continue;
        break;
            }
    }
    // gn gscan
    if($type == "gn_gscan") {
      switch ($step) {
        case 1:
          $found = false;
          if(preg_match("/^".$identregex.".*?Horus\s*?(\d+).*$/i",$nocolor,$line)) {
            $scan['horus'] = trim($line[1]);
            $found = true;
          }
          if(preg_match("/^".$identregex.".*?Rubium\s*?(\d+)./i",$nocolor,$line)) {
            $scan['rubium'] = trim($line[1]);
            $found = true;
          }
          if(preg_match("/^".$identregex.".*?Pulsar\s*?(\d+)./i",$nocolor,$line)) {
            $scan['pulsar'] = trim($line[1]);
            $found = true;
          }
          if(preg_match("/^".$identregex.".*?Coon\s*?(\d+)./i",$nocolor,$line)) {
            $scan['coon'] = trim($line[1]);
            $found = true;
          }
          if(preg_match("/^".$identregex.".*?Centurion\s*?(\d+)./i",$nocolor,$line)) {
            $scan['centurion'] = trim($line[1]);
            $found = true;
          }
          if(preg_match("/^".$identregex."Galaxy-Network Geschützscan \(\s*?(.+?)\s*?Svs\)(\s(.+?)\s\((\d+?):(\d+?)\)|)/i",$nocolor,$line)) {
            $scan['svs'] = trim($line[1]);
            if (strlen(trim($line[3]))) {
              $scan['scanner'] = trim($line[3])." (".trim($line[4]).":".trim($line[5]).")";
            }
            $scans[] = $scan;
            $step=0;
            $type = "";
          }
          if($found) {
            $step++;
            $scans[] = $scan;
          }
        continue;
        break;
        case 2:
          if(preg_match("/^".$identregex."Galaxy-Network Geschützscan \(\s*?(.+?)\s*?Svs\)(\s(.+?)\s\((\d+?):(\d+?)\)|)/i",$nocolor,$line)) {
            $last_scan = &$scans[count($scans)-1];
            $last_scan['svs'] = trim($line[1]);
            if (strlen(trim($line[3]))) {
              $last_scan['scanner'] = trim($line[3])." (".trim($line[4]).":".trim($line[5]).")";
            }
            $step=0;
            $type = "";
          }
        continue;
        break;
      }
    }
    // gn mili
    if($type == "gn_mili") {
      switch ($step) {
        case 1:
          if(preg_match("/".$identregex.".*?Im Orbit:(.*?)$/i",$nocolor,$line)) {
            $scan['fleets'][0] = parseFleet($line[1]);
            $step++;
          }
        continue;
        break;
        case 2:
          if(preg_match("/".$identregex.".*?Flotte 1:(.*?)$/i",$nocolor,$line)) {
            $scan['fleets'][1] = parseFleet($line[1]);
            $step++;
          }
        continue;
        break;
        case 3:
          if(preg_match("/".$identregex.".*?Flotte 2:(.*?)$/i",$nocolor,$line)) {
            $scan['fleets'][2] = parseFleet($line[1]);
            $step++;
            $scans[] = $scan;
          }
        continue;
        break;
        case 4:
          if(preg_match("/^".$identregex."Galaxy-Network Miliscan \(\s*?(.+?)\s*?Svs\)(\s(.+?)\s\((\d+?):(\d+?)\)|)/i",$nocolor,$line)) {
            $last_scan = &$scans[count($scans)-1];
            $last_scan['svs'] = trim($line[1]);
            if (strlen(trim($line[3]))) {
              $last_scan['scanner'] = trim($line[3])." (".trim($line[4]).":".trim($line[5]).")";
            }
            $step=0;
            $type = "";
          }
        continue;
        break;
      }
    }
    // wurst mili
    if($type == "wurst_mili") {
      switch ($step) {
        case 1:
          if(preg_match("/".$identregex.".*?Im Orbit:(.*?)$/i",$nocolor,$line)) {
            $scan['fleets'][0] = parseFleet($line[1]);
            $step++;
          }
        continue;
        break;
        case 2:
          if(preg_match("/".$identregex.".*?Flotte 1:(.*?)$/i",$nocolor,$line)) {
            $scan['fleets'][1] = parseFleet($line[1]);
            $step++;
          }
        continue;
        break;
        case 3:
          if(preg_match("/".$identregex.".*?Flotte 2:(.*?)$/i",$nocolor,$line)) {
            $scan['fleets'][2] = parseFleet($line[1]);
            $step++;
            $scans[] = $scan;
            $step=0;
            $type = "";
          }
        continue;
        break;
      }
    }

//################################## Edit Gonz #########################################

    /*
Sektorscan (100% @ 985 sVs) Brayn (540:1)
Punkte: 9.189.969 Asteroiden: 21
Schiffe: 1251 Geschütze: 1085
Metall-Exen: 158 Kristall-Exen: 74
Ilos Scansklave (71:1) - KiBO, Ilo: nix - SC, NL & Freunde: 1x - rest: 2x -
    */

    // sweep sector scan
    if($type =="sweep_sector") {
      switch ($step) {
        case 1:
          if(preg_match("/^".$identregex."Punkte:\s*?(.*?)\s*?Asteroiden:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['punkte'] = trim(preg_replace("/\./s","",$line[1]));
            $scan['roids'] = trim($line[2]);
            $step++;
          }
        continue;
        break;
        case 2:
          if(preg_match("/^".$identregex."Schiffe:\s*?(\d+)\s*?Geschütze:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['ships'] = trim($line[1]);
            $scan['deff'] = trim($line[2]);
            $step++;
          }
        continue;
        break;
        case 3:
          if(preg_match("/^".$identregex."Metall-Exen:\s*?(\d+)\s*?Kristall-Exen:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['metall'] = trim($line[1]);
            $scan['kristall'] = trim($line[2]);
//            $step++;
            $scans[] = $scan;
            $step=0;
            $type = "";

          }
        continue;
        break;
      }
    }
    
    // sweep gscan
/*    
Geschützscan (100% @ 5000 sVs) duffyduck3 (186:3)
Rubium: 57 Pulsar: 10 Coon: 8 Centurion: 5 Abfangjäger: 699
Ilos Scansklave (71:1) - KiBO, Ilo: nix - SC, NL & Freunde: 1x - rest: 2x -
*/

        if($type == "sweep_gscan") {
      switch ($step) {
        case 1:
          $found = false;
          if(preg_match("/^".$identregex.".*?Abfangjäger:\s(.+)\s.*$/i",$nocolor,$line)) {
            $scan['horus'] = trim(preg_replace("/\./s","",$line[1]));
            $found = true;
          }
          else {
            $scan['horus'] = 0;
          }
          
          if(preg_match("/^".$identregex.".*?Rubium:\s*?(.+)\s./i",$nocolor,$line)) {
            $scan['rubium'] = trim(preg_replace("/\./s","",$line[1]));
            $found = true;
          }
          else {
            $scan['rubium'] = 0;
          }
          
          if(preg_match("/^".$identregex.".*?Pulsar:\s*?(.+)\s./i",$nocolor,$line)) {
            $scan['pulsar'] = trim(preg_replace("/\./s","",$line[1]));
            $found = true;
          }
          else {
            $scan['pulsar'] = 0;
          }

          if(preg_match("/^".$identregex.".*?Coon:\s*?(.+)\s./i",$nocolor,$line)) {
            $scan['coon'] = trim(preg_replace("/\./s","",$line[1]));
            $found = true;
          }
          else {
            $scan['coon'] = 0;
          }
          
          if(preg_match("/^".$identregex.".*?Centurion:\s*?(.+)\s./i",$nocolor,$line)) {
            $scan['centurion'] = trim(preg_replace("/\./s","",$line[1]));
            $found = true;
            $scans[] = $scan;
            $step=0;
            $type = "";
          }
          else {
            $scan['centurion'] = 0;
            $scans[] = $scan;
            $step=0;
            $type = "";
          }
          
          
/*          if(preg_match("/^".$identregex."Galaxy-Network Geschützscan \(\s*?(.+?)\s*?Svs\)(\s(.+?)\s\((\d+?):(\d+?)\)|)/i",$nocolor,$line)) {
            $scan['svs'] = trim($line[1]);
            if (strlen(trim($line[3]))) {
              $scan['scanner'] = trim($line[3])." (".trim($line[4]).":".trim($line[5]).")";
            }
            $scans[] = $scan;
            $step=0;
            $type = "";
          }*/
        continue;
        break;
      }
    }
    
//Geschützscan [100%] firefly [126:1]
//LO: 3.000 | LR: 500 | MR: 100 | SR: 90 | AJ: 2.000
//150:1 - Vasi scheisst die Wand an!

        if($type == "vasi_gscan") {
      switch ($step) {
        case 1:
          $found = false;
          if(preg_match("/^".$identregex.".*?AJ:\s(.+)/i",$nocolor,$line)) {
            $scan['horus'] = trim(preg_replace("/\./s","",$line[1]));
            $found = true;
          }
          else {
            $scan['horus'] = 0;
          }
          
          if(preg_match("/^".$identregex.".*?LO:\s*?(.+)/i",$nocolor,$line)) {
            $scan['rubium'] = trim(preg_replace("/\./s","",$line[1]));
            $found = true;
          }
          else {
            $scan['rubium'] = 0;
          }
          
          if(preg_match("/^".$identregex.".*?LR:\s*?(.+)/i",$nocolor,$line)) {
            $scan['pulsar'] = trim(preg_replace("/\./s","",$line[1]));
            $found = true;
          }
          else {
            $scan['pulsar'] = 0;
          }

          if(preg_match("/^".$identregex.".*?MR:\s*?(.+)/i",$nocolor,$line)) {
            $scan['coon'] = trim(preg_replace("/\./s","",$line[1]));
            $found = true;
          }
          else {
            $scan['coon'] = 0;
          }
          
          if(preg_match("/^".$identregex.".*?SR:\s*?(.+)./i",$nocolor,$line)) {
            $scan['centurion'] = trim(preg_replace("/\./s","",$line[1]));
            $found = true;
            $scans[] = $scan;
            $step=0;
            $type = "";
          }
          else {
            $scan['centurion'] = 0;
            $scans[] = $scan;
            $step=0;
            $type = "";
          }
          
          
/*          if(preg_match("/^".$identregex."Galaxy-Network Geschützscan \(\s*?(.+?)\s*?Svs\)(\s(.+?)\s\((\d+?):(\d+?)\)|)/i",$nocolor,$line)) {
            $scan['svs'] = trim($line[1]);
            if (strlen(trim($line[3]))) {
              $scan['scanner'] = trim($line[3])." (".trim($line[4]).":".trim($line[5]).")";
            }
            $scans[] = $scan;
            $step=0;
            $type = "";
          }*/
        continue;
        break;
      }
    }
    
//Sektorscan [100%] firefly [126:1]
//Punkte: 177.462.309 | Asteroiden: 60
//Schiffe: 2.150 | Geschütze: 5.690
//MetExen: 650 | KrisExen: 400    


    if($type =="vasi_sector") {
    echo("vasi_zeilendurchlauf");
      switch ($step) {
        case 1:
          echo("zeile1 start");
          if(preg_match("/^".$identregex."Punkte:\s*?(.*?)\s.\sAsteroiden:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['punkte'] = trim(preg_replace("/\./s","",$line[1]));
            $scan['roids'] = trim($line[2]);
            $step++;
            echo("zeile1");
          }
        continue;
        break;
        case 2:
          echo("zeile2 start");
          if(preg_match("/^".$identregex."Schiffe:\s*?(.*?)\s|\sGeschütze:\s*?(.*?)/i",$nocolor,$line)) {
            $scan['ships'] = trim(preg_replace("/\./s","",$line[1]));
            $scan['deff'] = trim(preg_replace("/\./s","",$line[2]));
            $step++;
            echo("zeile2");
          }
        continue;
        break;
        case 3:
          echo("zeile3 start");
          if(preg_match("/^".$identregex."MetExen:\s*?(.*?)\s|\sKrisExen:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['metall'] = trim($line[1]);
            $scan['kristall'] = trim($line[2]);
//            $step++;
            $scans[] = $scan;
            $step=0;
            $type = "";
            echo("zeile3");

          }
        continue;
        break;

      }
    }


    // yasp sectorscan
/*    [23:11] <Aladin>  Punkte: 57.096.002 - Asteroiden: 63
[23:11] <Aladin>  Schiffe: 7.040 - Geschütze: 1.351
[23:11] <Aladin>  Metall-Exen: 515 - Kristall-Exen: 325
  */  
    
    if($type =="kerrigan_sector") {
      switch ($step) {
        case 1:
          if(preg_match("/^".$identregex."Punkte:\s*?(.*?)\s\-\sAsteroiden:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['punkte'] = trim(preg_replace("/\./s","",$line[1]));
            $scan['roids'] = trim($line[2]);
            $step++;
          }
        continue;
        break;
        case 2:
          if(preg_match("/^".$identregex."Schiffe:\s*?(.*?)\s\-\sGeschütze:\s*?(.*?)/i",$nocolor,$line)) {
            $scan['ships'] = trim(preg_replace("/\./s","",$line[1]));
            $scan['deff'] = trim(preg_replace("/\./s","",$line[2]));
            $step++;
          }
        continue;
        break;
        case 3:
          if(preg_match("/^".$identregex."Metall-Exen:\s*?(.*?)\s\-\sKristall-Exen:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['metall'] = trim($line[1]);
            $scan['kristall'] = trim($line[2]);
//            $step++;
            $scans[] = $scan;
            $step=0;
            $type = "";

          }
        continue;
        break;

      }
    }

    
    // kerrigan unit scan
    
    /*
Einheitenscan [100%] S4vi3r-.- [21:4]
Kaperschiffe: 4.500 - Schutzschiffe: 3.100
Fregatten: 330 - Zerstörer: 0 - Kreuzer: 35 - Schlachtschiffe: 0
Trägerschiffe: 30 - Jäger: 0 - Bomber: 0
Res an 33:4 ; Alli nix , SC/PDX/NL 1x , Rest 2x , bis auf Lude , der 4x :p
    */
    
    if ($type == "kerrigan_unit") {
        switch ($step) {
        case 1:
          //Kaperschiffe: 4.500 - Schutzschiffe: 3.100
          if(preg_match("/^".$identregex."Kaperschiffe:\s*?(.+)\s\-\sSchutzschiffe:\s*?(.+)/i",$nocolor,$line)) {
            $scan['kleptoren'] = trim(preg_replace("/\./","",$line[1]));
            $scan['cancris'] = trim(preg_replace("/\./","",$line[2]));
//            $scan['cancris'] = trim($line[2]);
            $step++;
          }
        continue;
        break;
        case 2:
        
        //Fregatten: 330 - Zerstörer: 0 - Kreuzer: 35 - Schlachtschiffe: 0
        //Fregatten: 466 - Zerstörer: 137 - Kreuzer: 0 - Schlachtschiffe: 0
          if(preg_match("/^".$identregex."Fregatten:\s*?(.+)\s\-\sZerstörer:\s*?(.+)\s\-\sKreuzer:\s*?(.+)\s\-\sSchlachtschiffe:\s*?(.+)/i",$nocolor,$line)) {
          $scan['fregatten'] = trim(preg_replace("/\./","",$line[1]));
//            $scan['fregatten'] = trim($line[1]);
            $scan['zerstoerer'] = trim(preg_replace("/\./","",$line[2]));
            $scan['kreuzer'] = trim(preg_replace("/\./","",$line[3]));
            $scan['schlachter'] = trim(preg_replace("/\./","",$line[4]));
            $step++;
          }
        continue;
        break;
        case 3:

        //Trägerschiffe: 30 - Jäger: 0 - Bomber: 0
          if(preg_match("/^".$identregex."Trägerschiffe:\s*?(.+)\s\-\sJäger:\s*?(.+)\s\-\sBomber:\s*?(.+)\s*?/i",$nocolor,$line)) {
            $scan['traeger'] = trim(preg_replace("/\./","",$line[1]));
            $scan['jaeger'] = trim(preg_replace("/\./","",$line[2]));
            $scan['bomber'] = trim(preg_replace("/\./","",$line[3]));
//            $step++;
            $scans[] = $scan;
            $step=0;
            $type= "";            
          }
        continue;
        break;
            }
    }    
    
    // Kerrigan mili
    
/*    Militärscan [100%] S4vi3r-.- [21:4]
      Orbit: 330 Fregatten - 35 Kreuzer
      Flotte 1: 30 Träger - 3.100 Schutz [Verteidigungsflug ChefSmoka67]
      Flotte 2: 4.500 Kaper [Im Orbit]
      Res an 33:4 ; Alli nix , SC/PDX/NL 1x , Rest 2x , bis auf Lude , der 4x :p
*/
      
    if($type == "kerrigan_mili") {
      switch ($step) {
        case 1:
          // Orbit: 330 Fregatten - 35 Kreuzer
          if(preg_match("/".$identregex.".*?Im Orbit:(.*?)$/i",$nocolor,$line)) {
            $scan['fleets'][0] = parseFleet($line[1]);//."  ".$line[2]."  ".$line[3]."  ".$line[4]);
            $step++;
          }
        continue;
        break;
        case 2:
          if(preg_match("/".$identregex.".*?Flotte 1:(.*?)$/i",$nocolor,$line)) {
            $scan['fleets'][1] = parseFleet($line[1]);
            $step++;
          }
        continue;
        break;
        case 3:
          if(preg_match("/".$identregex.".*?Flotte 2:(.*?)$/i",$nocolor,$line)) {
            $scan['fleets'][2] = parseFleet($line[1]);
            $step++;
            $scans[] = $scan;
            $step=0;
            $type = "";
          }
        continue;
        break;

      }
    }
    
        // gn gscan
    if($type == "wurst_gscan") {
      switch ($step) {
        case 1:
          $found = false;
          if(preg_match("/^".$identregex.".*?Abfangjäger:\s*?(\d+).*$/i",$nocolor,$line)) {
            $scan['horus'] = trim($line[1]);
            $found = true;
          }
          if(preg_match("/^".$identregex.".*?Rubium:\s*?(\d+)./i",$nocolor,$line)) {
            $scan['rubium'] = trim($line[1]);
            $found = true;
          }
          if(preg_match("/^".$identregex.".*?Pulsar:\s*?(\d+)./i",$nocolor,$line)) {
            $scan['pulsar'] = trim($line[1]);
            $found = true;
          }
          if(preg_match("/^".$identregex.".*?Coon:\s*?(\d+)./i",$nocolor,$line)) {
            $scan['coon'] = trim($line[1]);
            $found = true;
          }
          if(preg_match("/^".$identregex.".*?Centurion:\s*?(\d+)./i",$nocolor,$line)) {
            $scan['centurion'] = trim($line[1]);
            $found = true;
          }
          if(preg_match("/^".$identregex."Geschützscan \(\s*?(.+?)\s*?Svs\)(\s(.+?)\s\((\d+?):(\d+?)\)|)/i",$nocolor,$line)) {
            $scan['svs'] = trim($line[1]);
            if (strlen(trim($line[3]))) {
              $scan['scanner'] = trim($line[3])." (".trim($line[4]).":".trim($line[5]).")";
            }
            $scans[] = $scan;
            $step=0;
            $type = "";
          }
          if($found) {
            $step++;
            $scans[] = $scan;
          }
        continue;
        break;
        case 2:
          if(preg_match("/^".$identregex."Geschützscan \(\s*?(.+?)\s*?Svs\)(\s(.+?)\s\((\d+?):(\d+?)\)|)/i",$nocolor,$line)) {
            $last_scan = &$scans[count($scans)-1];
            $last_scan['svs'] = trim($line[1]);
            if (strlen(trim($line[3]))) {
              $last_scan['scanner'] = trim($line[3])." (".trim($line[4]).":".trim($line[5]).")";
            }
            $step=0;
            $type = "";
          }
        continue;
        break;
      }
    }
    
        //wurst unit scan
    if ($type == "wurst_unit") {
            switch ($step) {
        case 1:
          if(preg_match("/^".$identregex."Kleptoren:\s*?(\d+?)\s*?Cancris:\s*?(\d+?)\s*?Fregatten:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['kleptoren'] = trim($line[1]);
            $scan['cancris'] = trim($line[2]);
            $scan['fregatten'] = trim($line[3]);
            $step++;
          }
        continue;
        break;
        case 2:
          if(preg_match("/^".$identregex."Zerstörer:\s*?(\d+?)\s*?Kreuzer:\s*?(\d+?)\s*?Träger:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['zerstoerer'] = trim($line[1]);
            $scan['kreuzer'] = trim($line[2]);
            $scan['traeger'] = trim($line[3]);
            $step++;
          }
        continue;
        break;
        case 3:
          if(preg_match("/^".$identregex."Jäger:\s*?(\d+?)\s*?Bomber:\s*?(\d+?)\s*?Schlachter:\s*?(\d+)/i",$nocolor,$line)) {
            $scan['jaeger'] = trim($line[1]);
            $scan['bomber'] = trim($line[2]);
            $scan['schlachter'] = trim($line[3]);
//            $step++;
            $scans[] = $scan;
            $step=0;
            $type = "";

          }
        continue;
        break;

          }
         }

    //gn_ingame unit scan
    if ($type == "ingame_unit") {
            switch ($step) {
        case 1:
          if(preg_match("/^".$identregex."Name:\s*?(.*?)/i",$nocolor,$line)) {
            $scan['nick'] = trim($line[1]);
            $step++;
          }
        continue;
        break;
        case 2:
          if(preg_match("/^".$identregex."Koordinaten:\s*?(\d+?):(\d+?)/i",$nocolor,$line)) {
            $scan['gala'] = trim($line[1]);
            $scan['pos'] = trim($line[2]);
            $step++;
          }
        continue;
        break;
        case 3:
          if(preg_match("/^".$identregex."Jäger\s*?.\s*?\"Leo\"\s*?(\d+?)/i",$nocolor,$line)) {
            $scan['jaeger'] = trim($line[1]);
            $step++;
          }
        continue;
        break;
        case 4:
          if(preg_match("/^".$identregex."Bomber\s*?.\s*?\"Aquilae\"\s*?(\d+?)/i",$nocolor,$line)) {
            $scan['bomber'] = trim($line[1]);
            $step++;
          }
        continue;
        break;
        case 5:
          if(preg_match("/^".$identregex."Fregatten\s*?.\s*?\"Fornax\"\s*?(\d+?)/i",$nocolor,$line)) {
            $scan['fregatten'] = trim($line[1]);
            $step++;
          }
        continue;
        break;
        case 6:
          if(preg_match("/^".$identregex."Zerstörer\s*?.\s*?\"Draco\"\s*?(\d+?)/i",$nocolor,$line)) {
            $scan['zerstoerer'] = trim($line[1]);
            $step++;
          }
        continue;
        break;
        case 7:
          if(preg_match("/^".$identregex."Kreuzer\s*?.\s*?\"Goron\"\s*?(\d+?)/i",$nocolor,$line)) {
            $scan['kreuzer'] = trim($line[1]);
            $step++;
          }
        continue;
        break;
        case 8:
          if(preg_match("/^".$identregex."Schlachtschiff\s*?.\s*?\"Pentalin\"\s*?(\d+?)/i",$nocolor,$line)) {
            $scan['traeger'] = trim($line[1]);
            $step++;
          }
        continue;
        break;
        case 9:
          if(preg_match("/^".$identregex."Trägerschiff\s*?.\s*?\"Zenit\"\s*?(\d+?)/i",$nocolor,$line)) {
            $scan['schlachter'] = trim($line[1]);
            $step++;
          }
        continue;
        break;
        case 10:
          if(preg_match("/^".$identregex."Kaperschiff\s*?.\s*?\"Cleptor\"\s*?(\d+?)/i",$nocolor,$line)) {
            $scan['kleptoren'] = trim($line[1]);
            $step++;
          }
        continue;
        break;
        case 11:
          if(preg_match("/^".$identregex."Schutzschiff\s*?.\s*?\"Cancri\"\s*?(\d+?)/i",$nocolor,$line)) {
            $scan['cancris'] = trim($line[1]);
            $step++;
          }
        continue;
        break;
            }
    }


/*

[18:54] <SoulreaverX> Galaxy-Network SektorScan (100%) TheRealPredator (101:9)
[18:54] <SoulreaverX> Punkte: 30.470.491
[18:54] <SoulreaverX> Schiffe: 5262 Verteidigung: 0
[18:54] <SoulreaverX> M-Extraktoren: 807 K-Extraktoren: 200
[18:54] <SoulreaverX> Asteroiden: 52
[18:54] <SoulreaverX> Galaxy-Network SektorScan (200 SVs) DaFrEaK (186:8)

[18:30] <SoulreaverX>  Sektorscan [100%] Greenman [125:9]
[18:30] <SoulreaverX>  Punkte: 69.314.484 × Asteroiden: 90
[18:30] <SoulreaverX>  Schiffsanzahl: 15.223 × Geschützanzahl: 1
[18:30] <SoulreaverX>  Metallextraktoren: 608 × Kristallextraktoren: 143
[18:30] <SoulreaverX>  Scan von: csL [566:7] - Sv: 300
[18:30] <SoulreaverX>  Kristall verscannt, heute: 18.000, gesamt: 48.000


*/

  // yasp sector scan
    if($type =="yasp_sector") {
      switch ($step) {
        case 1:
          if(preg_match("/^".$identregex."Punkte:\s(.*?)\s×\sAsteroiden:\s(.*?)/i",$nocolor,$line)) {
            $scan['punkte'] = trim(preg_replace("/\./s","",$line[1]));
            $scan['roids'] = trim(preg_replace("/\./s","",$line[2]));
            $step++;
          }
        continue;
        break;
        case 2:
          if(preg_match("/^".$identregex."Schiffsanzahl:\s(.*?)\s×\sGeschützanzahl:\s(.*?)/i",$nocolor,$line)) {
            $scan['ships'] = trim(preg_replace("/\./s","",$line[1]));
            $scan['deff'] = trim(preg_replace("/\./s","",$line[2]));
            $step++;
          }
        continue;
        break;
        case 3:
          if(preg_match("/^".$identregex."Metallextraktoren:\s(.*?)\s×\sKristallextraktoren:\s(.*?)/i",$nocolor,$line)) {
            $scan['metall'] = trim($line[1]);
            $scan['kristall'] = trim($line[2]);
            $step++;
          }
        continue;
        break;
        case 4:
          if(preg_match("/^".$identregex."Scan\svon:\s(.*?)\s/i",$nocolor,$line)) {
           $step++;
           $scans[] = $scan;

          }
        continue;
        break;

        case 5:
          if(preg_match("/^".$identregex."Galaxy-Network SektorScan \(\s*?(.+?)\s*?Svs\)(\s(.+?)\s\((\d+?):(\d+?)\)|)/i",$nocolor,$line)) {
            $last_scan = &$scans[count($scans)-1];
            $last_scan['svs'] = trim($line[1]);
            if (strlen(trim($line[3]))) {
              $last_scan['scanner'] = trim($line[3])." (".trim($line[4]).":".trim($line[5]).")";
            }
            $step=0;
            $type = "";
          }
        continue;
        break;
      }
    }


         
         

         
         
         
    
//################################## Edit Gonz #########################################    
        
    // gn news
    if($type == "gn_news") {
      switch ($step) {
        case 1:
          if(preg_match("/^".$identregex."(.*?(?:Rückzug|Angriff|Fehler|Verteidigung).*)/i",$nocolor,$line)) {
            if(!$scan['newsdata']) $scan['newsdata'] = array();
            $scan['newsdata'][] = preg_replace("/[\n\f\r\t]/s","",$line[1]);
          } elseif(preg_match("/".$identregex."Galaxy-Network NewsScan \((.+?)\s*?Svs\)(\s([^\s]+?)\s\((\d+?):(\d+?)\)|)/i",$nocolor,$line)) {
            if($scan['newsdata']) $scan['newsdata'] = join("\n",$scan['newsdata']);
            $scan['svs'] = trim($line[1]);
            if (strlen(trim($line[3]))) {
              $scan['scanner'] = trim($line[3])." (".trim($line[4]).":".trim($line[5]).")";
            }
            $scans[] = $scan;
            $step=0;
            $type = "";
          }
        continue;
      }
    }
  }
    return $scans;
}

function parse_user_flottenbewegung(&$code,$gala,$pos) {
  if(!preg_match("'\sflottenbewegung\s'is",$code)) return false;
  $_fleet = array(
  "jaeger" => "jäger","bomber"=>"bomber","fregatten"=>"fregatte","zerstoerer"=>"zerstörer","kreuzer"=>"kreuzer","schlachter"=>"schlachtschiff","traeger"=>"trägerschiff","kleptoren"=>"kaperschiff","cancris"=>"schutzschiff"
  );
  $fleet = array();
  foreach ($_fleet as $key => $regex) {
    if(preg_match("'".$regex."[^\d]+?([\d,]+)(?:\s*?([\d,]+)|)(?:\s*?([\d,]+)|)'is",$code,$matches)) {
      $fleet[0][$key] = str_replace(",","",$matches[1]);
      if(isset($matches[2])) $fleet[1][$key] = str_replace(",","",$matches[2]);
      if(isset($matches[3])) $fleet[2][$key] = str_replace(",","",$matches[3]);
    }
  }
  //status, tgala, tpos, return_flight, eta, ticks, defftype (bei verteidigung)
/*  $_status = array(
    "Flotte ([12])\s{1,3}Wartet auf Befehle",
    "Flotte ([12])\s{1,3}Befindet sich auf dem R+ückflug von eine[rm] (\w*).\s{4}(\d{1,4}):(\d{1,2}) \w+ --> (\d{1,4}):(\d{1,2}) \w+ - ETA (?:(\d):(\d+)|(\d+))",
    "Flotte ([12])\s{1,3}Ist auf dem (Angriff|Verteidigung)sflug\s{1,4}(\d{1,4}):(\d{1,2}) \w+ --> (\d{1,4}):(\d{1,2}) \w+ - ETA (?:(\d+):(\d+)|(\d+))(?: Minuten)?\s{1,}(?:Die Flotte wird in dem Sektor |Das Gefecht wird )(?:(\d+):(\d+)|(\d+))(?: Minuten)?",
    "Flotte ([12])\s{1,3}Die Flotte kämpft für euch!\s{1,4}Das Gefecht mit (\d{1,4}):(\d{1,2}) \w+ wird noch (?:(\d+):(\d+)|(\d+))(?: Minuten)?"
  );
  $type = 0;  
  foreach ($_status as $regex) {
      if(preg_match_all("'".$regex."'is",$code,$matches,PREG_SET_ORDER)) {
       //echo "<pre>\$matches:".print_r($matches,1)."\n\n\n\n\n\n\n\n</pre>";
        switch ($type) {
          case 0:
            // im orbit
          break;
          case 1: {
              // rückflug
              foreach ($matches as $line) {
                $fleetnum = $line[1];
                $fleet[$fleetnum]['return_flight'] = 1;
                if($line[2] == "Verteidigung") 
                  $fleet[$fleetnum]['status'] = 2;
                else
                  $fleet[$fleetnum]['status'] = 1;
                $fleet[$fleetnum]['tgala'] = $line[3];
                $fleet[$fleetnum]['tpos'] = $line[4];
                $fleet[$fleetnum]['gala'] = $line[5];
                $fleet[$fleetnum]['pos'] = $line[6];
                if(strlen($line[7])){
                  $fleet[$fleetnum]['eta'] = $line[7]*60+$line[8];
                } else {
                  $fleet[$fleetnum]['eta'] = $line[9];
                }
              }
            break;
          }
          case 2: {
            // angriff/verteidigung
              foreach ($matches as $line) {
                $fleetnum = $line[1];
                if($line[2] == "Verteidigung") 
                  $fleet[$fleetnum]['status'] = 2;
                else {
                  $fleet[$fleetnum]['status'] = 1;
                  $fleet[$fleetnum]['returntime'] = 450;
                }
                $fleet[$fleetnum]['tgala'] = $line[5];
                $fleet[$fleetnum]['tpos'] = $line[6];
                $fleet[$fleetnum]['gala'] = $line[3];
                $fleet[$fleetnum]['pos'] = $line[4];
                if(strlen($line[7])){
                  $fleet[$fleetnum]['eta'] = $line[7]*60+$line[8];
                } else {
                  $fleet[$fleetnum]['eta'] = $line[9];
                }
                if(strlen($line[10])){
                  $fleet[$fleetnum]['orbittime'] = $line[10]*60+$line[11];
                } else {
                  $fleet[$fleetnum]['orbittime'] = $line[12];
                }
              }
            break;
          }
          case 3: {
            // im orbit, angriff
              foreach ($matches as $line) {
                $fleetnum = $line[1];
                $fleet[$fleetnum]['status'] = 1;
                $fleet[$fleetnum]['returntime'] = 450;
                $fleet[$fleetnum]['tgala'] = $line[2];
                $fleet[$fleetnum]['tpos'] = $line[3];
                
                $fleet[$fleetnum]['eta'] = 0;

                if(strlen($line[4])){
                  $fleet[$fleetnum]['orbittime'] = $line[4]*60+$line[5];
                } else {
                  $fleet[$fleetnum]['orbittime'] = $line[6];
                }
              }
            break;
          }
        }
        /*
        foreach ($_tmpfleet as $key => $val) {
          if (!is_int($val)) {
            $val = str_replace("_","",$val);
            if ($key == "eta") {
              //echo "<pre>".print_r($matches,1)."</pre>";
              if (isset($matches[$val][0])) { $fleet[$matches[1][0]][$key] = $matches[$val][0] *60 + $matches[$val+1][0]; }
              else { $fleet[$matches[1][0]][$key] = $matches[$val+2][0]; }
              if (isset($matches[$val][1])) { $fleet[$matches[1][1]][$key] = $matches[$val][1] *60 + $matches[$val+1][1]; }
              elseif (isset($matches[$val+2][1])) { $fleet[$matches[1][1]][$key] = $matches[$val+2][1]; }
            }
            if ($key == "ticks") {
              if (isset($matches[$val][0])) { $fleet[$matches[1][0]][$key] = $matches[$val][0] *4 + (int)($matches[$val+1][0] /15); }
              else { $fleet[$matches[1][0]][$key] = (int)($matches[$val+2][0] /15); }
              if (isset($matches[$val][1])) { $fleet[$matches[1][1]][$key] = $matches[$val][1] *4 + (int)($matches[$val+1][1] /15); }
              elseif (isset($matches[$val+2][1])) { $fleet[$matches[1][1]][$key] = (int)($matches[$val+2][1] / 15); }
            }
            elseif ($key == "status") {
              $statusreplace = array("Angriff"=>1,"Verteidigung"=>2);
              $fleet[$matches[1][0]][$key] = $statusreplace[$matches[$val][0]];
              if ($matches[1][1]) { $fleet[$matches[1][1]][$key] = $statusreplace[$matches[$val][1]]; }
            }
            else {
              $fleet[$matches[1][0]][$key] = $matches[$val][0];
              if ($matches[1][1]) { $fleet[$matches[1][1]][$key] = $matches[$val][1]; }
            }
          }
          else {
            $fleet[$matches[1][0]][$key] = $val;
            if ($matches[1][1]) { $fleet[$matches[1][1]][$key] = $val; }
          }
        }
      }
      $type++;
    }*/
/*    if (isset($fleet[$matches[1][0]])) {
      $fleet[$matches[1][0]]["gala"] = $gala;
      $fleet[$matches[1][0]]["pos"] = $pos;
    }
    if (isset($fleet[$matches[1][1]])) {
      $fleet[$matches[1][1]]["gala"] = $gala;
      $fleet[$matches[1][1]]["pos"] = $pos;
    }*/
  return $fleet;
}

function parse_user_verteidigung(&$code) {
  if(!preg_match("'\sverteidigung\s'is",$code)) return false;
  $_check = array(
    "rubium" => "leichtes Orbitalgeschütz",
    "horus" => "abfangjäger",
    "pulsar" => "leichtes raumgeschütz",
    "coon" => "mittleres raumgeschütz",
    "centurion" => "schweres raumgeschütz"
  );
  $return = array();
  foreach ($_check as $key => $regex) {
    if(preg_match("'".$regex.".*?k[^\d]*?(\d+)'is",$code,$matches)) {
      $return[$key] = $matches[1];
    }
  }
  return $return;
}

function parse_taktikansicht(&$code) {
  if(!preg_match("'\sflottenzusammensetzung\s'is",$code) || !preg_match("'\sVerteidigungseinheiten\s'is",$code)) return false;
  //flotte
  $_fleet = array(
  "jaeger" => "jäger","bomber"=>"bomber","fregatten"=>"fregatte","zerstoerer"=>"zerstörer","kreuzer"=>"kreuzer","schlachter"=>"schlachtschiff","traeger"=>"trägerschiff","kleptoren"=>"kaperschiff","cancris"=>"schutzschiff"
  );
  $_deff = array(
    "rubium" => "leichtes Orbitalgeschütz",
    "horus" => "abfangjäger",
    "pulsar" => "leichtes raumgeschütz",
    "coon" => "mittleres raumgeschütz",
    "centurion" => "schweres raumgeschütz"
  );
  $result = array();
  if(preg_match_all("'flottenzusammensetzung von ([^\s]+?) \((\d+):(\d+)\)(.*?)galaxy-network'is",$code,$users,PREG_SET_ORDER)) {
    foreach ($users as $user) {
      $fleet = array();
      foreach ($_fleet as $key => $regex) {
        if(preg_match("'".$regex."[^\d]+?([\d,]+)(?:\s*?([\d,]+)|)(?:\s*?([\d,]+)|)'is",$user[4],$matches)) {
          $fleet[0][$key] = str_replace(",","",$matches[1]);
          if(isset($matches[2])) $fleet[1][$key] = str_replace(",","",$matches[2]); else $fleet[1][$key] = 0;
          if(isset($matches[3])) $fleet[2][$key] = str_replace(",","",$matches[3]); else $fleet[2][$key] = 0;
        }
      }
      $deff = array();
      foreach ($_deff as $key => $regex) {
        if(preg_match("'".$regex.".[^\d]*?(\d+)'is",$user[4],$matches)) {
          $deff[$key] = $matches[1];
        }
      }
      $result[] = array("fleets" => $fleet,"deff" => $deff,"nick" => $user[1],"gala" => $user[2],"pos" => $user[3]);
    }
    $code = preg_replace("'flottenzusammensetzung von ([^\s]+?) \((\d+):(\d+)\)(.*?)galaxy-network'is"," ",$code);
  }
  return $result ;
}


?>