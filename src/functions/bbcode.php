<?php
############################################################
#                                                          #
# @file:                                                   #
# @author:                                                 #
# @date:                                                   #
# @version: 1.0                                            #
# @comments:                                               #
#                                                          #
############################################################

// Funktionen zur Textformatierung

/**
 * Funktion formatTextData
 *
 * Diese Methode bereitet den anzuzeigenden Text auf.
 * Es werden Url's und emails geparst, [img] tags ausgewertet.
 * @param     string $string der zu bearbeitende text
 * @return    string der formatierte Text
 */
function formatTextData($string)
{
 global $searcharray,$replacearray;
 //Nach Url's parsen
 $string = parseUrl($string);
 //umbrechen
 #$string = wordwrap($string,80,"\n",true);
 // zeilenumbrüche durch <br> ersetzen
 $string = nl2br($string);
 //Smilies
 #$string = smilies($string);
 if(!isset($searcharray) && !isset($replacearray)) {
  //liste
  $searcharray[]="/\[list=(['\"]?)([^\"']*)\\1]".
                 "(.*)\[\/list((=\\1[^\"']*\\1])|(\]))/esiU";
  $replacearray[]="formatlist('\\3', '\\2')"; 
  $searcharray[]="/\[list](.*)\[\/list\]/esiU";
  $replacearray[]="formatlist('\\1')";
  //url
  $searcharray[]="/\[url=(['\"]?)([^\"']*)\\1](.*)\[\/url\]/esiU";
  $replacearray[]="formaturl('\\2','\\3')";
  $searcharray[]="/\[url]([^\"]*)\[\/url\]/esiU";
  $replacearray[]="formaturl('\\1')";
  //image
  $searcharray[]="/\[img]([^\"]*)\[\/img\]/siU";	
  $replacearray[]="<img src=\"\\1\" border=0>";
  //email
  $searcharray[] = "/\[email=(['\"]?)([^\"']*)\\1](.*)\[\/email\]/siU";
  $replacearray[] = "<a href=\"mailto:\\2\">\\3</a>";
  $searcharray[] = "/\[email](.*)\[\/email\]/siU";
  $replacearray[] = "<a href=\"mailto:\\1\">\\1</a>";
  //phpcode
  $searcharray[]="/\[code](.*)\[\/code\]/esiU";	
  $replacearray[]="formatcodetag('\\1')";
  //highlite
  $searcharray[]="/\[php](.*)\[\/php\]/esiU";	
  $replacearray[]="phphighlite('\\1')";
  //bold
  $searcharray[] = "/\[B](.*)\[\/B\]/siU";
  $replacearray[] = "<b>\\1</b>";
  //underline
  $searcharray[] = "/\[U](.*)\[\/U\]/siU";
  $replacearray[] = "<u>\\1</u>";
  //italic
  $searcharray[] = "/\[I](.*)\[\/I\]/siU";
  $replacearray[] = "<i>\\1</i>";
  //fontsize
  $searcharray[] = "/\[SIZE=(['\"]?)([^\"']*)\\1](.*)\[\/SIZE\]/siU";
  $replacearray[] = "<font size=\"\\2\">\\3</font>";
  //fontcolor
  $searcharray[] = "/\[COLOR=(['\"]?)([^\"']*)\\1](.*)\[\/COLOR\]/siU";
  $replacearray[] = "<font color=\"\\2\">\\3</font>";
  //font
  $searcharray[] = "/\[FONT=(['\"]?)([^\"']*)\\1](.*)\[\/FONT\]/siU";
  $replacearray[] = "<font face=\"\\2\">\\3</font>";
  //align
  $searcharray[] = "/\[ALIGN=(['\"]?)([^\"']*)\\1](.*)\[\/ALIGN\]/siU";
  $replacearray[] = "<div align=\"\\2\">\\3</div>";
  //mark
  $searcharray[] = "/\[MARK=(['\"]?)([^\"']*)\\1](.*)\[\/MARK\]/siU";
  $replacearray[] = "<span style=\"background-color: \\2\">\\3</span>";
  //quote
  $searcharray[] = "/\[QUOTE](.*)\[\/QUOTE\]/siU";
  $replacearray[] = "<blockquote><font size=1>Zitat:".
                    "</font><hr>\\1<hr></blockquote>";
  }

 $string = preg_replace($searcharray, $replacearray, $string);
 $string = str_replace("\\'", "'", $string);
 return $string;
} // end func

/**
 * Funktion formaturl
 *
 * Diese Funktion formatiert eine Url.
 * @param     string $url   die Url
 * @param     string $title ein Titel (optional)
 * @param     integer maxwidth  die maximle Länge der Url
 * @param     integer widthl  die anzahl der linken zeichen
 * @param     integer width2  die Anzahl der zeichen, die rechts angezeigt 
 *                            werden
 * @return    string  formatierte Url
 */
function formaturl($url, $title="", $maxwidth=60, $width1=40, $width2=-15) {
 if(!trim($title)) $title=$url;
 if(!preg_match("/[a-z]:\/\//si", $url)) $url = "http://$url";
 if(strlen($title)>$maxwidth) $title = substr($title,0,$width1)."...".substr($title,$width2);
 return "<a href=\"$url\" target=\"_blank\">".str_replace("\\\"", "\"", $title)."</a>";
}


/**
 * Funktion parseUrl
 *
 * Diese Funktion durchsucht den String nach Url's und email tags und ersetzt 
 * diese Teile durch BB Code.
 * @param     string $string der zu durchsuchende String
 * @return    string String, wo alle tags durch BBCode ersetzt sind
 */
function parseUrl($string)
{
  // nach url und email suchen
 $urlsearch[]="/([^]_a-z0-9-=\"'\/])((https?|ftp):\/\/|www\.)([^".
              "\r\n\(\)\*\^\$!`\"'\|\[\]\{\};<>]*)/si";
 $urlsearch[]="/^((https?|ftp):\/\/|www\.)([^".
              "\r\n\(\)\*\^\$!`\"'\|\[\]\{\};<>]*)/si";
 $urlreplace[]="\\1[URL]\\2\\4[/URL]";
 $urlreplace[]="[URL]\\1\\3[/URL]";
 $emailsearch[]="/([\s])([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[".
                "a-zZ0-9-]+)*(\.[a-zA-Z]{2,}))/si";
 $emailsearch[]="/^([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-z".
                "A-Z0-9-]+)*(\.[a-zA-Z]{2,}))/si";
 $emailreplace[]="\\1[EMAIL]\\2[/EMAIL]";
 $emailreplace[]="[EMAIL]\\0[/EMAIL]";
 $string = preg_replace($urlsearch, $urlreplace, $string);
 if (strpos($string, "@")) $string = preg_replace($emailsearch, $emailreplace, $string);
 return $string;
} // end func

function formatcodetag($code) {
	return "<blockquote><pre><font size=1>code:</font><hr>".str_replace("<br>","",str_replace("\\\"","\"",$code))."<hr></pre></blockquote>";
}

function formatlist($list, $listtype="") {
 if ($listtype) {
   $listtype = " type=\"$listtype\"";
 }
 $list = str_replace("\\\"","\"",$list);
 if ($listtype) return "<ol$listtype>".str_replace("[*]","<li>", $list)."</ol>";
 else return "<ul>".str_replace("[*]","<li>", $list)."</ul>";
}

function phphighlite($code) {
 
 $code = str_replace("&gt;", ">", $code);
 $code = str_replace("&lt;", "<", $code);
 $code = str_replace("&amp;", "&", $code);
 $code = str_replace('$', '\$', $code);
 $code = str_replace('\n', '\\\\n', $code);
 $code = str_replace('\r', '\\\\r', $code);
 $code = str_replace('\t', '\\\\t', $code);
 $code = str_replace("<br>", "\r\n", $code);
 $code = str_replace("<br />", "\r\n", $code);
 
 $code = stripslashes($code);

 ob_start();
 $oldlevel=error_reporting(0);
 highlight_string($code);
 error_reporting($oldlevel);
 $buffer = ob_get_contents();
 ob_end_clean();
 //$buffer = str_replace("&quot;", "\"", $buffer);
 return "<blockquote><pre><font size=1>php:</font><hr>$buffer<hr></pre></blockquote>";
}

function smilies($out) {
	global $smiliecache,$SmiliePath;
	if(!$smiliecache) return;
	for($i = 0; $i < count($smiliecache); $i++) 
    $out=str_replace ($smiliecache[$i]['smiliestext'], "<img src=".$SmiliePath.$smiliecache[$i]['smiliespath']." border=0>", $out);
  return $out;
}

function editPostdata($data) {
 $data = str_replace("\'","'", $data);
 $data = str_replace("'","&acute;", $data);
 $data = str_replace("\"","&quot;", $data);
 return $data;
}

function editDBdata($data) {
 $data = str_replace("&acute;","'", $data);
 $data = str_replace("&quot;","\"", $data); 
 return $data;
}


/**
 * Short description.
 *
 * Detail description
 * @param     
 * @since     1.0
 * @access    private
 * @return    void
 * @throws    
 */
function getclickysmilies($tableRows,$tableColumns,$path,$css="")
{
  global $smiliecache;
  global $smilie;
  global $smiliebits;
  global $url;
  global $smiliegetmore;
  global $cssclass;
  global $Columns;

  if (!$smiliecache) return;
  
  $url = $path;
  $Columns = $tableColumns;
  if ($css) $cssclass = " class = \"".$css."\"";

  $totalSmilies = count($smiliecache);

  if(($tableRows*$tableColumns) < $totalSmilies) { 
    $smiliegetmore= getTemplate("admin.bbsmilie.getmore");
  }

  foreach($smiliecache as $smilie) {
    $smilieArray[] = getTemplate("admin.bbsmilie.bit");
  }
  unset($smilie);

  $count = 0;
  for ($i=0; $i<$tableRows; $i++) {
    if ($count < $totalSmilies) {
      $smiliebits .= "\t<tr\">\n";
      for ($j=0; $j<$tableColumns; $j++) {
        if ($count == $totalSmilies) {
          $smiliebits .= "\t<td $cssclass colspan=\"".($tableColumns-$j)."\">&nbsp;</td>";break;
        }
        $smiliebits .= "\t<td $cssclass align=\"center\">".$smilieArray[$count]."&nbsp;</td>\n";
        $count++;
      }
      $smiliebits .= "\t</tr>\n";
    }
  }

  $bbcode_smilies = getTemplate("admin.bbsmilie");
  return $bbcode_smilies;
} // end func

?>