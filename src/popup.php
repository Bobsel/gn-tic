<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<header>
<title>Galaxy-Network Scan</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</header>
<body>
<pre>
<?
$scan = urldecode($_GET['text']);
$scan = preg_replace("/<br>/i","\n",$scan);
echo $scan;
?>
</pre>
</body>
</html>