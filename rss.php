<? // WR-forum v 1.9.9  //  06.03.12 г.  //  Miha-ingener@yandex.ru
   // Модифицированный скрипт Rootman для отображения последних тем и сообщений в формате RSS-новостей

include ("config.php");

function replacer ($text) { // ФУНКЦИЯ очистки кода
$text=str_replace("&#032;",' ',$text);
$text=str_replace(">",'&gt;',$text);
$text=str_replace("<",'&lt;',$text);
$text=str_replace("\"",'&quot;',$text);
$text=preg_replace("/\n\n/",'<p>',$text);
$text=preg_replace("/\n/",'<br>',$text);
$text=preg_replace("/\\\$/",'&#036;',$text);
$text=preg_replace("/\r/",'',$text);
$text=preg_replace("/\\\/",'&#092;',$text);
// если magic_quotes включена - чистим везде СЛЭШи в этих случаях: одиночные (') и двойные кавычки ("), обратный слеш (\)
if (get_magic_quotes_gpc()) { $text=str_replace("&#092;&quot;",'&quot;',$text); $text=str_replace("&#092;'",'\'',$text); $text=str_replace("&#092;&#092;",'&#092;',$text); }
$text=str_replace("\r\n","<br> ",$text);
$text=str_replace("\n\n",'<p> ',$text);
$text=str_replace("\n",'<br> ',$text);
$text=str_replace("\t",'',$text);
$text=str_replace("\r",'',$text);
$text=str_replace('   ',' ',$text);
return $text; }


function unreplacer($text) { // ФУНКЦИЯ замены спецсимволов конца строки на обычные
$text=str_replace("&lt;br&gt;","<br>",$text); return $text;}


// Получение адреса страницы
$rss="http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
$url=str_replace("rss.php","index.php",$rss);

if (!isset($_GET['whatisthis'])) {

$fname=replacer($fname);
$fdesription=replacer($fdesription);
$adminemail=replacer($adminemail);
// Заголовок RSS

echo "<?xml version=\"1.0\" encoding=\"windows-1251\"?>
<rss version=\"2.0\">
 <channel>
   <title>RSS лента новостей: $fname</title>
   <link>$url</link>
   <description>$fdesription</description>
   <language>Russian</language>
   <copyright>Rootman</copyright>
   <managingEditor>$adminemail</managingEditor>
   <webMaster>$adminemail</webMaster>
   <generator>WR-Forum 1.9 RSS-module</generator>
   <lastBuildDate>$date $time</lastBuildDate>
";

// Чтение новостей и их вывод на экран
$lines=file("$datadir/news.dat");
$itogo=sizeof($lines); $x=$itogo-1;
do { $dt=explode("|",replacer($lines[$x]));

// конвертируем дату в формат ленты RSS  
$dt1=explode(".",$dt[2]);
$dt2=explode(":",$dt[3]);
$then=mktime($dt2[0],$dt2[1],$dt2[2],$dt1[1],$dt1[0],$dt1[2]);
$xdate=date("r",$then);

$tname=$dt[9];
$zag=$dt[5];
$name=$dt[4];
$msg=$dt[6];
$msg=str_replace("&","&amp;",$msg);
$msg=str_replace('\"','"',$msg);
$msg=str_replace("[b]","<p>",$msg);
$msg=str_replace("[/b]","</p>",$msg);
$msg=str_replace("[RB]","<p>",$msg);
$msg=str_replace("[/RB]","</p>",$msg);
$msg=str_replace("[Code]","<p>",$msg);
$msg=str_replace("[/Code]","</p>",$msg);
$msg=str_replace("[Quote]","<p>",$msg);
$msg=str_replace("[/Quote]","</p>",$msg);
$msg=str_replace("<br>","\r\n", $msg);
//$msg=unreplacer($msg);
$msg=str_replace("&amp;lt;br&amp;gt;","<p></p>", $msg);
$msg="<![CDATA[$msg]]>";

$fid=$dt[0];
$id=$dt[1];

echo "
<item>
 <title>$zag</title>
  <link>$url?id=$id</link>
   <description>&lt;b&gt;$name пишет:&lt;/b&gt; &lt;br&gt;&lt;br&gt; $msg &lt;br&gt;&lt;br&gt;</description>
   <author>$name</author>
  <comments>$url?fid=$fid&amp;id=$id</comments>
 <pubDate>$xdate</pubDate>
</item>
";
$x--;
} while ($x>=0); // end do ... while

echo "
 </channel>
</rss>";

} else { // Страница не поддерживает RSS, поэтому выводим информацию об RSS 

echo '<html>
<head>
<meta http-equiv="Content-Type"
content="text/html; charset=windows-1251">
<title>Что такое RSS</title>
</head>
<body bgcolor="#FFFFFF">
<p><font size="5">Что такое RSS?</font></p>
<p><strong>RSS</strong> - это сокращение от <b>R</b>eally
<b>S</b>imple <b>S</b>yndication, что в переводе
на русский звучит, как Действительно Простая… Синдикация?
Хотя, скорее, Действительно Простое Синдицирование, - так более
правильно, но не более понятно. </p>
<p>Смотрим словарь: </p>
<blockquote>
<p>Syndicate - 1) агентство печати, приобретающее информацию, статьи
и т. п. и продающее их различным газетам для одновременной
публикации, (сущ.) 2) приобретать информацию и пр. (гл.)</p>
</blockquote>
<p>Итого, <strong>RSS</strong> - это Простое
Приобретение Информации.</p>
<p><strong>RSS </strong>- это разновидность
XML, формат, специально придуманный для того, чтобы легко и быстро
делиться контентом. Изначально придуманный Netscape для их портала
Netcenter, он быстро завоевал популярность и стал черезвычайно широко использоваться.</p>
<p>В настоящее время <strong>RSS</strong>
наиболее популярен для передачи лент новостей прямо на рабочий стол
конечного пользователя, что существенно экономит траффик как
самого пользователя (не надо каждый раз загружать WEB-страницу), так и
хостера.</p> 
<p>Для работы с <b>RSS</b> на этом форуме вам
необходима специальная программа для чтения <b>RSS</b>-файлов. Мы рекомендуем
вам установить простую в использовании, компактную и
бесплатную программу <a href="http://www.google.ru/search?hl=ru&amp;q=Abilon+RSS&amp;lr=">Abilon</a>
настроив которую вы будете в режиме on-line получать всю информацию о
новых темах и сообщениях на нашем форуме и всегда будете в курсе
событий.</p>
<p>Адрес <b>RSS</b>-колонки форума - <b>'.$rss.'</b></p>
</body>
</html>
'; }

?>
