<? // WR-forum v 1.9.9  //  20.07.12 г.  //  Miha-ingener@yandex.ru

error_reporting(0); // РАЗКОМЕНТИРУЙТЕ для постоянной работы
//error_reporting (E_ALL); // ВРЕМЕННО - на время тестирования и отладки скрипта
@ini_set('register_globals','off');// Все скрипты написаны для этой настройки php

include "config.php";

// Определяем URL форума
$host=$_SERVER["HTTP_HOST"]; $self=$_SERVER["PHP_SELF"]; $furl=str_replace('index.php','',"http://$host$self");

$valid_types=array("zip","rar","doc","xls","7z","jpg","jpeg","bmp","gif","png"); // допустимые расширения загружаемых файлов

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


// Функция содержит ПРОДОЛЖЕНИЕ ШАПКИ. Вызывается: addtop();
function addtop() { global $wrfname,$fskin,$date,$time;
// ищем КУКи и выводим ИМЯ
if (isset($_COOKIE['wrfcookies'])) {$wrfc=$_COOKIE['wrfcookies']; $wrfc=replacer($wrfc); $wrfc=explode("|", $wrfc); $wrfname=$wrfc[0];} else {unset($wrfname); unset($wrfpass); $wrfpass="";}
echo'<TD align=right>';
if (isset($wrfname)) {print "<a href='tools.php?event=profile&pname=$wrfname' class=mainmenu><img src=\"$fskin/icon_mini_profile.gif\" border=0 hspace=3 />Ваш профиль</a>&nbsp; <a href='index.php?event=clearcooke' class=mainmenu><img src=\"$fskin/ico-login.gif\" border=0 hspace=3 />Выход [<B>$wrfname</B>]</a>";
$name=strtolower($wrfname); if (is_file("data-pm/$name.dat")) {$linespm=file("data-pm/$name.dat"); $pmi=count($linespm); print" <img src=\"$fskin/icon_mini_profile.gif\" border=0 hspace=3 />[<a href='pm.php?readpm&id=$wrfname'><font color=red><B>Есть сообщения в ПМ: $pmi шт.</b></font></a>]";}
}
if (!isset($_COOKIE['wrfcookies']) and !isset($_GET['id'])) {print "<span class=mainmenu>
<a href='tools.php?event=reg' class=mainmenu><img src=\"$fskin/icon_mini_register.gif\" border=0 hspace=3 />Регистрация</a>&nbsp;&nbsp;
<a href='tools.php?event=login' class=mainmenu> <img src=\"$fskin/buttons_spacer.gif\" border=0 hspace=3>Вход</a></td>";}
if (is_file("$fskin/tiptop.html")) include("$fskin/tiptop.html"); // подключаем дополнение к ВЕРХУШКе
print"</span></td></tr></table></td></tr></table><span class=gensmall>Сегодня: $date - $time";
return true;}


function prcmp ($a, $b) {if ($a==$b) return 0; if ($a<$b) return -1; return 1;} // Функция сортировки


function nospam() { global $max_key,$rand_key; // Функция АНТИСПАМ 2011 !
if (array_key_exists("image", $_REQUEST)) { $num=replacer($_REQUEST["image"]);
for ($i=0; $i<10; $i++) {if (md5("$i+$rand_key")==$num) {imgwr($st,$i); die();}} }
$xkey=""; mt_srand(time()+(double)microtime()*1000000);
$dopkod=mktime(0,0,0,date("m"),date("d"),date("Y")); // доп.код: меняется каждые 24 часа
$stime=md5("$dopkod+$rand_key");// доп.код
echo'<table border=0 cellspacing=0 cellpadding=0><tr height=30><TD>Защитный код:</TD>';
$nummax=0; for ($i=0; $i<=$max_key; $i++) {
$snum[$i]=mt_rand(0,9); $psnum=md5($snum[$i]+$rand_key+$dopkod);
$secret=mt_rand(0,1); $styles='bgcolor=#FFFF00';
if ($nummax<3) { if ($secret==1 or $i==0) {$styles='bgcolor=#FF9B9B'; $xkey=$xkey.$snum[$i]; $nummax++;}}
echo "<td width=20 $styles><img src=antispam.php?image=$psnum border=0 alt=''>\n<img src=antispam.php?image=$psnum height=1 width=1 border=0></td>\r\n";}
$xkey=md5("$xkey+$rand_key+$dopkod"); //число + ключ из config.php + код меняющийся кажые 24 часа
print"<td><input name='usernum' class=post type='text' maxlength=$nummax size=6> (введите цифры, которые на <font style='font-weight:bold'> красном фоне</font>)
<input name=xkey type=hidden value='$xkey'>
<input name=stime type=hidden value='$stime'>
</td></tr></table>";
return; }


function addmsg($qm) { // ФУНКЦИЯ добавления темы/сообщения
global $wrfname,$maxname,$canupfile,$antispam,$max_key,$rand_key,$max_upfile_size,$smile,$smiles,$valid_types,$datadir;

//Проверка ЗАПРЕТА IP-пользователя на добавление объявлений (файл bad_ip.dat)
$ip=$_SERVER['REMOTE_ADDR']; // определяем IP юзера
if (is_file("$datadir/bad_ip.dat")) { $lines=file("$datadir/bad_ip.dat"); $i=count($lines);
if ($i>0) {do {$i--; $idt=explode("|", $lines[$i]);
if ($idt[0]===$ip) exit("<center><br><br><B>Админитратор заблокировал для Вашего IP: $ip<br> возможность добавлять что-либо по следующей причине:<br><br> <font color=red><B>$idt[1].</B></font><br><br>Вам разрешено просматривать сообщения,<br> а вот ДОБАВЛЯТЬ ТЕМЫ/СООБЩЕНИЯ категорически ЗАПРЕЩЕНО!</B><br><br>");
} while($i > "1");} unset($lines);}

echo'<tr><td class=row1 width=14% height=25><span class=gen><b>Имя</b></span></td>
<td class=row2 width=76%><span class=genmed>';
if (!isset($wrfname)) print "<input type=text name=name class=post maxlength=$maxname size=28> E-mail <input type=text name=email class=post size=30>";
else {
if (isset($_COOKIE['wrfcookies'])) {$wrfc=$_COOKIE['wrfcookies']; $wrfc=htmlspecialchars($wrfc); $wrfc=stripslashes($wrfc); $wrfc=explode("|", $wrfc); $wrfpass=replacer($wrfc[1]);} else {unset($wrfpass); $wrfpass="";}
print "<b>$wrfname</b><input type=hidden name=name value='$wrfname'><input type=hidden name=who value='да'><input type=hidden name=userpass value=\"$wrfpass\">";}

echo'</span></td></tr>
<tr><td class=row1 valign=top><span class=genmed><b>Сообщение</b><br><br>Для вставки имени, кликните на точку рядом с ним.<br><br>Смайлики:<br>
<table align=center width=100 height=70><tr><td valign=top>';

if ($smile==TRUE) {$i=count($smiles)-1;
for($k=0; $k<$i; $k=$k+2) {$j=$k+1; print"<A href='javascript:%20x()' onclick=\"DoSmilie(' $smiles[$j]');\"><img src='smile/$smiles[$k].gif' border=0></a> ";} }
print"<a name='add' href='#add' onclick=\"window.open('tools.php?event=moresmiles','smiles','width=350,height=300,left=50,top=150,toolbar=0,status=0,border=0,scrollbars=1')\">Ещё смайлы</a>
</tr></td></table></span></td>
<td class=row2 valign=top><span class=gen><table width=450><tr valign=middle><td><span class=genmed>
<input type=button class=button value='B' style='font-weight:bold; width:30px' onclick=\"DoSmilie(' [b]  [/b] ');\">&nbsp;
<input type=button class=button value='RB' style='font-weight:bold; width:30px; color:red' onclick=\"DoSmilie('[RB] [/RB]');\">&nbsp;
<INPUT type=button class=button value='Цитировать выделенное' style='width: 180px' onclick='REPLIER.msg.value += \"[Quote]\"+(window.getSelection?window.getSelection():document.selection.createRange().text)+\"[/Quote]\"'>&nbsp;
<input type=button class=button value=' Код ' onclick=\"DoSmilie(' [Code]  [/Code] ');\">&nbsp;
<input type=button class=button value=' IMG ' style='font-weight:bold; color:navy' onclick=\"DoSmilie('[img][/img]');\">&nbsp;
</span></td></tr><tr>
<td colspan=9><span class=gen><textarea name=msg cols=95 rows=10 class=post>$qm</textarea></span></td>";

if ($canupfile==TRUE and isset($wrfname)) { $max=round($max_upfile_size/10.24)/100;
print "<tr><td class=row1 valign=top>Добавление ";

foreach($valid_types as $v) print "<B>$v</B>, ";
print" максимально допустимый размер: <B>$max Кб.</B>
<input type=file name=file class=post size=70></td></tr></table></TD>";}

if ($antispam==TRUE and !isset($wrfname)) nospam(); // АНТИСПАМ !

echo'</tr></table></span></td></tr>
<tr><td class=row1 colspan=2 align=center height=28><input type=submit tabindex=5 class=mainoption value=" Отправить ">&nbsp;&nbsp;&nbsp;<input type=reset tabindex=6 class=mainoption value=" Очистить "></td>
</tr></table></form>';

return;} // КОНЕЦ функции-формы ДОБАВЛЕНИЯ ТЕМЫ/ОТВЕТА



// Выбран ВЫХОД из форума - очищаем куки
if(isset($_GET['event'])) {if ($_GET['event']=="clearcooke") {setcookie("wrfcookies","",time()); Header("Location: $furl"); exit;}}



// ДОБАВЛЕНИЕ ТЕМЫ или ОТВЕТА - ШАГ 1
if(isset($_GET['event'])) {

//Проверка ЗАПРЕТА IP-пользователя на добавление объявлений (файл bad_ip.dat)
$ip=$_SERVER['REMOTE_ADDR']; // определяем IP юзера
if (is_file("$datadir/bad_ip.dat")) { $lines=file("$datadir/bad_ip.dat"); $i=count($lines);
if ($i>0) {do {$i--; $idt=explode("|", $lines[$i]);
if ($idt[0]===$ip) exit("<noindex><script language='Javascript'>function reload() {location = \"$furl\"}; setTimeout('reload()', 10000);</script><center><br><br><B>Админитратор заблокировал для Вашего IP: $ip<br> возможность добавлять что-либо по следующей причине:<br><br> <font color=red><B>$idt[1].</B></font><br><br>Вам разрешено просматривать сообщения,<br> а вот ДОБАВЛЯТЬ ТЕМЫ/СООБЩЕНИЯ категорически ЗАПРЕЩЕНО!</B></noindex>");
} while($i > "1");} unset($lines);}

if ($stop==TRUE) exit("Временно добавление тем и сообщений приостановлено!");

if (($_GET['event']=="addtopic") or ($_GET['event']=="addanswer")) {
if (isset($_POST['name'])) $name=$_POST['name'];
$name=trim($name); // Вырезает ПРОБЕЛьные символы 
$zag=$_POST['zag']; $msg=$_POST['msg'];

$fid=$_GET['id']; 
if ($_GET['event']=="addanswer") $id=substr($fid,3,4);
if (strlen($fid)>3) $fid=substr($fid,0,3);

if (isset($_POST['who'])) $who=$_POST['who']; else $who="";
if (isset($_POST['email'])) $email=$_POST['email']; else $email="";
if (isset($_POST['page'])) $page=$_POST['page'];
if (isset($_POST['maxzd'])) $maxzd=$_POST['maxzd']; else $maxzd="0"; if ($maxzd==null) $maxzd="0";
if ((!ctype_digit($maxzd)) or (strlen($maxzd)>2)) exit("<B>$back. Попытка взлома по звёздам или ошибка в файле статистики</B>");

if ($nosssilki==TRUE and $_GET['event']=="addanswer" and preg_match("/href|url|http|www|\.ru|\.com|\.net|\.info|\.org/i",$msg) and preg_match("/\.jpg|\.gif|\.jpeg|\.png/is",$msg)<>TRUE and !isset($wrfname)) exit("<center>Уважаемый гость с IP $ip , если Вы хотите делиться ссылками с другими пользователями форума - пройдите регистрацию.<BR>Если Вы спаммер - выйдете вон! </center><BR><BR>");

// защита по топику fid
if (!ctype_digit($fid) or strlen($fid)>3) exit("<B>$back. Попытка взлома через номер рубрики. Номер должен содержать только цифры и быть менее 4 символов</B>");

//--А-Н-Т-И-С-П-А-М--проверка кода--
if ($antispam==TRUE and !isset($_COOKIE['wrfcookies'])) {
if (!isset($_POST['usernum']) or !isset($_POST['xkey']) or !isset($_POST['stime']) ) exit("данные из формы не поступили!");
$usernum=replacer($_POST['usernum']); $xkey=replacer($_POST['xkey']); $stime=replacer($_POST['stime']);
$dopkod=mktime(0,0,0,date("m"),date("d"),date("Y")); // доп.код. Меняется каждые 24 часа
$usertime=md5("$dopkod+$rand_key");// доп.код
$userkey=md5("$usernum+$rand_key+$dopkod");
if (($usertime!=$stime) or ($userkey!=$xkey)) exit("введён ОШИБОЧНЫЙ код!");}

// проходим по всем разделам и топикам - ищем запращиваемый
// на тот случай, если mainforum.dat - пуст, подключаем резервную копию

$realbase="1"; if (is_file("$datadir/mainforum.dat")) $mainlines=file("$datadir/mainforum.dat");
if (!isset($mainlines)) $datasize=0; else $datasize=sizeof($mainlines);
if ($datasize<=0) {if (is_file("$datadir/copy.dat")) {$realbase="0"; $mainlines=file("$datadir/copy.dat"); $datasize=sizeof($mainlines);}}
if ($datasize<=0) exit("$back. Проблемы с Базой данных, файл данных пуст - обратитесь к администратору");
$i=count($mainlines);

$realfid=null; $fotodetali="|||";
do {$i--; $dt=explode("|", $mainlines[$i]);
if ($dt[0]==$fid) {$realfid=$i; if ($dt[1]=="razdel") exit("$back. Данной ветки форума не существует");} // присваиваем $realfid - № п/п строки
} while($i>0);

if (!isset($realfid)) exit("$back. Ошибка с номером рубрики. Она не существует в базе");

$dt=explode("|",$mainlines[$realfid]);
if (is_file("$datadir/topic$fid.dat")) {$tlines=file("$datadir/topic$fid.dat"); $tc=count($tlines)-2; $i=$tc+2; $ok=null;
// нужно пробежаться по топику, найти тему. Если есть - нормуль, нету - значит добавление сообщений ЗАПРЕЩЕНО!
if ($_GET['event']=="addanswer") {
do {$i--; $tdt=explode("|", $tlines[$i]);
if ($tdt[7]=="$fid$id") {$ok=1; if ($tdt[8]=="closed") exit("$back тема закрыта и добавление сообщений запрещено!"); }
} while($i>0);
if ($ok!=1) exit("$back тема закрыта и добавление сообщений запрещено!"); }

} else $tc="2";
if ($dt[11]>0) {if ($tc>=$dt[11]) exit("$back. Превышено ограничение на кол-во допустимых тем в данной рубрике! Не более <B>$dt[11]</B> тем!");}

// проверка Логина/Пароля юзера. Может он хакер, тогда облом ему

// Этап 1
if (isset($_POST['userpass'])) $userpass=replacer($_POST['userpass']); // получаем и обрабатываем пароль юзера из файлов
else $userpass="";
$realname="";
if (isset($_COOKIE['wrfcookies'])) {
    $wrfc=$_COOKIE['wrfcookies']; $wrfc=htmlspecialchars($wrfc); $wrfc=stripslashes($wrfc);
    $wrfc=explode("|", $wrfc); $wrfname=$wrfc[0]; $wrfpass=$wrfc[1];
} else {$who=null; $wrfname=null; $wrfpass=null;}

// Этап 2
if ($who!=null) { $who=0; $ok=FALSE;
if ($wrfname!=null & $wrfpass!=null) {
$lines=file("$datadir/usersdat.php"); $i=count($lines);
do {$i--; $rdt=explode("|", $lines[$i]);
   if (isset($rdt[1])) { $realname=strtolower($rdt[0]);
   if (strtolower($wrfname)===$realname & $wrfpass===$rdt[1] & $userpass===$rdt[1]) {
   $name=$wrfname; $who="да"; $ok="$i";}}
} while($i > "1");

if ($ok==FALSE) {setcookie("wrfcookies","",time()); exit("Ошибка при работе с КУКИ! <font color=red><B>Вы не сможете оставить сообщение, попробуйте подать его как гость.</B></font> Ваш логин и пароль не найдены в базе данных, попробуйте зайти на форум вновь. Если ошибка повторяется - обратитесь к администратору форума.");}
}}


print"<html><head><link rel='stylesheet' href='$fskin/style.css' type='text/css'></head><body>";

if ($_GET['event']=="addtopic" and $cangutema==FALSE and !isset($wrfname)) exit("<center>Администратор запретил гостям создавать темы!</center><BR><BR>");
if ($_GET['event']=="addanswer" and $cangumsg==FALSE and !isset($wrfname)) exit("<center>Администратор запретил гостям отвечать в темах!</center><BR><BR>");


if (isset($_FILES['file']['name'])) { // ЕСЛИ ДОБАВЛЯЕМ ФАЙЛ
$fotoname=replacer($_FILES['file']['name']); if (strlen($fotoname)>3) { $fotosize=$_FILES['file']['size']; // Имя и размер файла

//---- ЗАЩИТЫ от ВЗЛОМА -----

// 1. Проверяем РАСШИРЕНИЕ
$ext = strtolower(substr($fotoname, 1 + strrpos($fotoname, ".")));
if (!in_array($ext, $valid_types)) {echo "<B>ФАЙЛ НЕ загружен.</B> Возможные причины:<BR>
- разрешена загрузка только файлов с такими расширениями: <B>";
$patern=""; foreach($valid_types as $v) print"$v, ";
echo'</B><BR>
- Вы пытаетесь загрузить файл с двойным расширением;<BR>
- неверно введён адрес или выбран испорченный файл;</B><BR>'; exit;}

// 2. считаем КОЛ-ВО ТОЧЕК в выражении - если большей одной - СВОБОДЕН!
$findtchka=substr_count($fotoname, "."); if ($findtchka>1) exit("ТОЧКА встречается в имени файла $findtchka раз(а). Это ЗАПРЕЩЕНО! <BR>\r\n");

// 2. если в имени есть .php, .html, .htm - свободен! 
$bag="Извините, но в имени ФАйла <B>запрещено</B> использовать .php, .html, .htm";
if (preg_match("/\.php/i",$fotoname)) exit("Вхождение <B>.php</B> найдено. $bag");
if (preg_match("/\.html/i",$fotoname)) exit("Вхождение <B>.html</B> найдено. $bag");
if (preg_match("/\.htm/i",$fotoname)) exit("Вхождение <B>.htm</B> найдено. $bag");

// 3. защищаем от РУССКИХ букв в имени файла и проверка РАСШИРЕНИЯ файла 
$patern=""; foreach($valid_types as $v) $patern.="$v|";
if (!preg_match("/^[a-z0-9\.\-_]+\.(".$patern.")+$/is",$fotoname)) exit("$fotoname - <br>Запрещено использовать РУССКИЕ буквы в имени файла, а также запрещено загружать файлы с расширением отличным от заданных!!");

// 4. Проверяем, может быть файл с таким именем уже есть на сервере
if (file_exists("$filedir/$fotoname")) exit("$back. Файл с таким именем уже существует на сервере! Либо измените имя на другое, <br>либо обновите страницу - возможно Вы пытаетесь добавить сообщение и файл повторно!!");

// 5. Размер файла
$fotoksize=round($fotosize/10.24)/100; // размер ЗАГРУЖАЕМОГО файла в Кб.
$fotomax=round($max_upfile_size/10.24)/100; // максимальный размер файла в Кб.
if ($fotoksize>$fotomax) exit("Вы превысили допустимый размер файла! <BR><B>Максимально допустимый</B> размер: <B>$fotomax </B>Кб.<BR> <B>Вы пытаетесь</B> загрузить файл размером: <B>$fotoksize</B> Кб!");

// ЕСЛИ включен порядок присвоения файлу случайного имени при загрузке - генерируем случайное имя
if ($random_name==TRUE) {do $key=mt_rand(100000,999999); while (file_exists("$filedir/$key.$ext")); $fotoname="$key.$ext";}

if (copy($_FILES['file']['tmp_name'], $filedir."/".$fotoname)) {print "<br><br>Файл УСПЕШНО загружен: $fotoname (Размер: $fotosize байт)"; $fotodetali="1|$fotoname|$fotosize|";}
else echo "ОШИБКА загрузки файла - $fotoname...\n"; }}

$tektime=time();
$name=wordwrap($name,30,' ',1); // разрываем длинные строки
$zag=wordwrap($zag,50,' ',1);
$name=str_replace("|","I",$name);
$who=str_replace("|","&#124;",$who);
$email=str_replace("|","&#124;",$email);
$zag=str_replace("|","&#124;",$zag);
$msg=str_replace("|","&#124;",$msg);

$smname=$name; if (strlen($name)>18) {$smname=substr($name,0,18); $smname.="..";}
$smzag=$zag; if (strlen($zag)>24) {$smzag=substr($zag,0,24); $smzag.="..";}

// БЛОК ГЕНЕРИРУЕТ СЛЕДУЮЩИЙ ПО ПОРЯДКУ НОМЕР ТЕМЫ, начиная просмотр с 1000
// считываем весь файл с темами в память
if ($_GET['event']=="addtopic") { $id=1000; $id="$fid$id";
$allid=null; $records=file("$datadir/topic$fid.dat"); $imax=count($records); $i=$imax;
if ($i > 0) { do {$i--; $rd=explode("|",$records[$i]); $allid[$i]=$rd[7]; } while($i>0);
//natcasesort($allid); // сортируем по возрастанию
do $id++; while(in_array($id,$allid) or is_file("$datadir/$id.dat"));
} else $id=$fid."1000"; 
$id=substr($id,3,4); // Нам нужен чистый ID из 4-х символов
} // if (event==addtopic)

// генерируем имя файлу с темой - СТАРЫЙ механизм
//if ($_GET['event']=="addtopic") { if ($fid<10) $add="0"; else $add="";
//do $id=mt_rand(1000,9999); while (file_exists("$datadir/$add$fid$id.dat"));
//$id="$add$fid$id"; }

if (strlen($id)>8) exit("<B>$back. Номер темы должен быть числом. Критическая ошибка скрипта или попытка взлома</B>");
if (strlen(ltrim($zag))<3) exit("$back ! Ошибка в вводе данных заголовка!");

$ip=$_SERVER['REMOTE_ADDR']; // определяем IP юзера
$text="$name|$who|$email|$zag|$msg|$date|$time|$fid$id||$tektime|$smname|$smzag|$fotodetali|$ip||||";
$text=replacer($text); $exd=explode("|",$text); 
$name=$exd[0]; $zag=$exd[3]; $smname=$exd[10]; $smzag=$exd[11]; $smmsg=$exd[4];

if (!isset($name) || strlen($name) > $maxname || strlen($name) <1) exit("$back Ваше <B>Имя пустое, или превышает $maxname</B> символов!</B></center>");
if (preg_match("/[^(\\w)|(\\x7F-\\xFF)|(\\-)]/",$name)) exit("$back Ваше имя содержит запрещённые символы. Разрешены русские и английские буквы, цифры, подчёркивание и тире.");
if (strlen(ltrim($zag))<3 || strlen($zag) > $maxzag) exit("$back Слишком короткое название темы или <B>название превышает $maxzag</B> символов!</B></center>");
if (strlen(ltrim($msg))<2 || strlen($msg) > $maxmsg) exit("$back Ваше <B>сообщение короткое или превышает $maxmsg</B> символов.</B></center>");
if (!preg_match('/^([0-9a-zA-Z]([-.w]*[0-9a-zA-Z])*@([0-9a-zA-Z][-w]*[0-9a-zA-Z].)+[a-zA-Z]{2,9})$/si',$email) and strlen($email)>30 and $email!="") exit("$back и введите корректный E-mail адрес!</B></center>");

// функция АНТИФЛУД здесь - повторное добавление сообщения/темы запрещено!
if (isset($tlines)) {
if ($tc<"-1") {$sdt[0]=null; $sdt[3]=null;} else {$last=$tlines[$tc+1]; $sdt=explode("|",$last);}

if ($_GET['event'] =="addtopic") { // ЕСЛИ добавление ТЕМЫ: имя = имя в файле, тема = последняя тема в файле
if ($name==$sdt[0] and $exd[3]==$sdt[3]) exit("$back. Такая тема уже создана. Спамить на форуме запрещено!");

} else { // ЕСЛИ добавление сообщения: имя = имя в файле, сообщение = последнему сообщению в файле
if (is_file("$datadir/$fid$id.dat")) {$linesn=file("$datadir/$fid$id.dat"); $in=count($linesn)-1;
if ($in > 0) { $dtf=explode("|",$linesn[$in]);
if ($name==$dtf[0] and $exd[4]==$dtf[4]) exit("$back. Такое сообщение уже размещено в данной теме. Спамить на форуме запрещено!");}
}
}} // if $event=="addtopic"


// ЕСЛИ введена команда АП, то меняем дату создания файла и тема самая первая будет
if ($_GET['event'] =="addanswer") { //при ОТВЕТе В ТЕМЕ

$pageadd=""; // расчитываем верную страницу и номер сообщения
if ($_GET['event']=="addanswer") {$in=$in+2; $page=ceil($in/$qq); if ($page!=1) $pageadd="&page=$page";}

// Проверяем, давно ли реактивировали тему
$timetek=time(); $timefile=filemtime("$datadir/$fid$id.dat"); 
$timer=$timetek-$timefile; // узнаем сколько прошло времени (в секундах) 
// $timer<10 - 10 секунд защита от антифлуда
if ($smmsg=="ап!") {
if ($timer<10 and $timer>0) exit("$back тема была активна менее $timer секунд назад. Подождите чуть-чуть.");
touch("$datadir/$fid$id.dat");
print "<script language='Javascript'>function reload() {location = \"index.php?id=$fid$id&$pageadd#m$in\"}; setTimeout('reload()', 1500);</script>
<table width=100% height=80%><tr><td><table border=1 cellpadding=10 cellspacing=0 bordercolor=#224488 align=center valign=center width=60%><tr><td><center>
Спасибо, <B>$name</B>, тема реактивирована.<BR><BR>Через несколько секунд Вы будете автоматически перемещены в текущую тему <BR><B>$zag</B>.<BR><BR>
<B><a href='index.php?id=$fid$id&$pageadd#m$in'>ДАЛЬШЕ >>></a></B></td></tr></table></td></tr></table></center></body></html>";
exit; }

if ($timer<10 and $timer>0) exit("$back тема была активна менее $timer секунд назад.");
}


$razdelname="";
if ($realbase==TRUE and $maxzd<1) { // Если подключена рабочая база, а не копия
$lines=file("$datadir/mainforum.dat"); $max=sizeof($lines)-1;
$dt=explode("|", $lines[$realfid]); $dt[5]++; $main_id="$fid$id";
if ($_GET['event']=="addtopic") $dt[4]++;
$txtdat="$dt[0]|$dt[1]|$dt[2]|$main_id|$dt[4]|$dt[5]|$smname|$date|$time|$tektime|$smzag|$dt[11]|$dt[12]||||";
$razdelname=$dt[1];
// запись данных на главную страницу
$fp=fopen("$datadir/mainforum.dat","a+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
for ($i=0;$i<=$max;$i++) {if ($i==$realfid) fputs($fp,"$txtdat\r\n"); else fputs($fp,$lines[$i]);}
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
} // if ($realbase==TRUE)

if ($newmess==TRUE and $maxzd<1) { // запись в отдельный файл нового сообщения
if (is_file("$datadir/topic$fid.dat")) $nlines=count(file("$datadir/topic$fid.dat")); else $nlines=1;

if (is_file("$datadir/$fid$id.dat")) $nlines2=count(file("$datadir/$fid$id.dat"))+1; else $nlines2=1;

$newmessfile="$datadir/news.dat";
$newlines=file("$newmessfile"); $ni=count($newlines)-1; $i2=0; $newlineexit="";
$ntext="$fid|$main_id|$date|$time|$smname|$zag|$msg|$nlines|$nlines2|$razdelname|$who||||";
$ntext=str_replace("
", "<br>", $ntext);

// Блок проверяет, есть ли уже новое сообщение в этой теме. Если есть - отсеивает. На выходе - массив без этой строки.
for ($i=0;$i<=$ni;$i++) { $ndt=explode("|",$newlines[$i]);
if (isset($ndt[1])) {if ("$fid$id"!=$ndt[1]) $newlineexit.="$newlines[$i]"; $i2++; } }

// Записываем свежее сообщение в массив и далее сохраняем его в файл
if ($maxzd<1) { // Если тема доступна для всех - нет ограничений по звёздам
if ($i2>0) { // Если есть такая тема, то пишем весь массив, иначе тока строку
$newlineexit.=$ntext;
$fp=fopen("$newmessfile","w");
flock ($fp,LOCK_EX);
fputs($fp,"$newlineexit\r\n");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
} else {
$fp=fopen("$newmessfile","a+");
flock ($fp,LOCK_EX);
fputs($fp,"$ntext\r\n");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);}

$file=file($newmessfile);$i=count($file);
if ($i>="15") {
$fp=fopen($newmessfile,"w");
flock ($fp,LOCK_EX);
unset($file[0]);
fputs($fp, implode("",$file));
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);}
}
}
} // if ($newmess==TRUE)


// БЛОК добавляет +1 к репе и +1 к сообщению или +1 к кол-ву тем, созданных юзером

if (isset($_COOKIE['wrfcookies']) and ($ok!=FALSE)) {
$ufile="$datadir/userstat.dat"; $ulines=file("$ufile"); $ui=count($ulines)-1; $ulinenew=""; $fileadd=0;
// Если юзер загружает файл - то ему ещё +5 в РЕПУ
if (isset($_FILES['file']['name']) and $repaaddfile!=FALSE) {if (strlen($_FILES['file']['name'])>1) $fileadd=$repaaddfile;}

// Ищем юзера по имени в файле userstat.dat
for ($i=0;$i<=$ui;$i++) {$udt=explode("|",$ulines[$i]);
if ($udt[0]==$wrfname) {
$udt[2]++; $udt[3]=$udt[3]+$fileadd;
if ($_GET['event']=="addtopic") {$udt[1]++; $udt[3]=$udt[3]+$repaaddtem;} else $udt[3]=$udt[3]+$repaaddmsg;
$ulines[$i]="$udt[0]|$udt[1]|$udt[2]|$udt[3]|$udt[4]|$udt[5]||||\r\n";}
$ulinenew.="$ulines[$i]";}
// Пишем данные в файл
$fp=fopen("$ufile","w");
flock ($fp,LOCK_EX);
fputs($fp,"$ulinenew");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp); }


if ($sendadmin==TRUE) { // отправляем информацию об ответе в теме АДМИНИСТРАТОРУ

$headers=""; // Настройки для отправки писем
$headers.="Content-Type: text/html; charset=windows-1251\r\n";
$headers.="From: Robot <".$adminemail.">\r\n";
$headers.="X-Mailer: WR-Forum PHP/".phpversion();

// оставляем только первые 200 символов сообщения
$msgmax=strlen($msg); if ($msgmax>200) $msgmax=200; $msg=substr($msg,0,$msgmax); $msg=replacer($msg);

// Собираем всю информацию в теле письма
$allmsg="<html><head>
<meta http-equiv='Content-Type' content='text/html; charset=windows-1251'><meta http-equiv='Content-Language' content='ru'>
</head><body>
<BR><BR><center>Это сообщение отправлено вам роботом<BR> форума <a href='$furl' target='_blank'><B>$fname</B></a><BR><BR>
<table cellspacing=0 width=700 bgcolor=navy><tr><td><table cellpadding=6 cellspacing=1 width='100%'>
<tr bgcolor=#F7F7F7><td> Сообщение:</td><td><BR>На форуме добавлено <a href='$furl"."index.php?id=$id$pageadd' target='_blank'>новое сообщение</a> от <b>$name</b>. 
Вот часть сообщения:<br><br><font color=navy>$msg</font><br>
Посмотрите его полностью.<BR><BR>
Всего Вам доброго!<BR></td></tr>
<tr bgcolor=#F7F7F7><td>Дата отправки сообщения:</td><td>$time - <B>$date г.</B></td></tr>
<tr bgcolor=#F7F7F7><td>Перейти на главную страницу:</td><td><a href='$furl'>$furl</a></td></tr>
</table></td></tr></table></center><BR><BR>* Данное письмо сгенерировано и отправлено роботом, отвечать на него не нужно.
</body></html>";
mail("$adminemail","=?windows-1251?B?".base64_encode("Извещение от форума $fname")."?=",$allmsg,$headers);
} // if $sendadmin==TRUE


if ($_GET['event'] =="addtopic") { // Добавление ТЕМЫ - запись данных
// Пишем В ТОПИК
$fp=fopen("$datadir/topic$fid.dat","a+");
flock ($fp,LOCK_EX);
fputs($fp,"$text\r\n");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

// Пишем В ТЕМУ
$fp=fopen("$datadir/$fid$id.dat","a+");
flock ($fp,LOCK_EX);
fputs($fp,"$text\r\n");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

print "<script language='Javascript'>function reload() {location = \"index.php?id=$fid$id\"}; setTimeout('reload()', 1500);</script>
<table width=100% height=80%><tr><td><table border=1 cellpadding=10 cellspacing=0 bordercolor=#224488 align=center valign=center width=60%><tr><td><center>
Спасибо, <B>$name</B>, за добавление темы!<BR><BR>Через несколько секунд Вы будете автоматически перемещены в созданную тему.<BR><BR>
<B><a href='index.php?id=$fid$id'>ДАЛЬШЕ >>></a></B></td></tr></table></td></tr></table></center></body></html>";
exit; }



if ($_GET['event'] =="addanswer") { //ОТВЕТ В ТЕМЕ - запись данных
$timetek=time(); $timefile=filemtime("$datadir/$fid$id.dat"); 
$timer=$timetek-$timefile; // узнаем сколько прошло времени (в секундах) 
$fp=fopen("$datadir/$fid$id.dat","a+");
flock ($fp,LOCK_EX);
fputs($fp,"$text\r\n");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
if ($timer<0) {$viptime=strtotime("+2 year"); touch("$datadir/$fid$id.dat",$viptime);}

print "<script language='Javascript'>function reload() {location = \"index.php?id=$fid$id$pageadd#m$in\"}; setTimeout('reload()', 1500);</script>
<table width=100% height=80%><tr><td><table border=1 cellpadding=10 cellspacing=0 bordercolor=#224488 align=center valign=center width=60%><tr><td><center>
Спасибо, <B>$name</B>, Ваш ответ успешно добавлен.<BR><BR>Через несколько секунд Вы будете автоматически перемещены в текущую тему <BR><B>$zag</B>.<BR><BR>
<B><a href='index.php?id=$fid$id$pageadd#m$in'>ДАЛЬШЕ >>></a></B></td></tr></table></td></tr></table></center></body></html>";
exit;
}
}




//--------------- ШАПКА для всех страниц форума ------------//
// определяем дату последнего визита. +5 минут погрешности //

if (isset($_COOKIE['wrfcookies'])) {
$wrfc=$_COOKIE['wrfcookies']; $wrfc=explode("|",replacer($wrfc));
$wrfname=$wrfc[0];$wrfpass=$wrfc[1];$wrftime1=$wrfc[2];$wrftime2=$wrfc[3];
if (time()>($wrftime1+240)) { $tektime=time(); $wrfcookies="$wrfc[0]|$wrfc[1]|$tektime|$wrftime1|";
setcookie("wrfcookies", $wrfcookies, time()+1728000);
$wrfc=$_COOKIE['wrfcookies']; $wrfc=explode("|",replacer($wrfc));
$wrfname=$wrfc[0];$wrfpass=$wrfc[1];$wrftime1=$wrfc[2];$wrftime2=$wrfc[3];
} }
// ------------






// БЛОК подключает копию главного файла при повреждении
if (is_file("$datadir/mainforum.dat")) $mainlines=file("$datadir/mainforum.dat"); $imax=count($mainlines); $i=$imax;
if (!isset($mainlines)) $datasize=0; else $datasize=sizeof($mainlines);
if ($datasize<=0) {if (is_file("$datadir/copy.dat")) {$mainlines=file("$datadir/copy.dat"); $datasize=sizeof($mainlines);}}
if ($datasize<=0) exit("<center><b>Файл РУБРИК несуществует! Зайдите в <a href='admin.php'>админку</a> и создайте рубрики!</b>");

$error=FALSE; $frname=null; $frtname=""; $rfid="";

// ДЛЯ ссылки типа razdel=
if (isset($_GET['razdel'])) {
do {$i--; $dt=explode("|", $mainlines[$i]);
if ($dt[0]==$_GET['razdel']) {$rfid=$i; $frname="$dt[2] ->";}
} while($i >0);
$i=$imax;}

if (isset($_GET['id'])) { // Блок выводит в статусной строке: ТЕМА -> РАЗДЕЛ -> ФОРУМ
$id=$_GET['id'];
if (strlen($id)<=3 and !is_file("$datadir/topic$id.dat")) $error="ый Вами раздел";
if (strlen($id)> 3 and !is_file("$datadir/$id.dat")) $error="ая Вами тема";
if (!ctype_digit($id)) $error="ая Вами тема или раздел";
if (isset($_GET['quotemsg'])) $error=TRUE;

if(strlen($id)>3) {$fid=substr($id,0,3); $id=substr($id,3,4);} else $fid=$id;

// проходим по всем разделам и топикам - ищем запрашиваемый
do {$i--; $dt=explode("|", $mainlines[$i]);
if ($dt[0]==$fid) { $frname="$dt[1] ->";
if (isset($dt[11])) { if($dt[11]>0) $maxtem=$dt[11]; else $maxtem="999";}}
} while($i >0);

//$frtname="1"; $frname="2"; $fname="3";
// Блок считывает название темы для отображения в шапке форума
if (strlen($id)>3 and is_file("$datadir/topic$fid.dat")) {
$lines=file("$datadir/topic$fid.dat"); $imax=count($lines); $i=$imax;
do {$i--; $dt=explode("|",$lines[$i]);
if($dt[7]=="$fid$id") $frtname="$dt[3] ->";
} while ($i>0); }

if ($error==TRUE) { // ЗАПРЕЩАЕМ ИНДЕКСАЦИЮ страниц с цитированием / УДАЛЁННЫЕ РАЗДЕЛЫ / ТЕМЫ!
$topurl="$fskin/top.html";
ob_start(); include $topurl; $topurl=ob_get_contents(); ob_end_clean();
$topurl=str_replace("<meta name=\"Robots\" content=\"index,follow\">",'<meta name="Robots" content="noindex,follow">',$topurl);
print"$topurl";
if (strlen($error)>1) exit("</td></tr></table><div align=center><br>Извините, но запрашиваем$error отсутствует.<br>
Рекомендую перейти на главную страницу форума по <a href='$furl'>этой ссылке</a>,<br>
и найти интересующую Вас тему.<br></div></td></tr></table></td></tr></table></td></tr></table></body></html>"); }

// здесь проверяем СУЩЕСТВУЕТ ЛИ СТРАНИЦА, на которую пришёл юзер
if (strlen($id)==3) { $lines=file("$datadir/topic$id.dat"); $imax=count($lines);
if (isset($_GET['page'])) $page=$_GET['page']; else $page=1;
$maxikpage=ceil($imax/$qqmain); if ($page>$maxikpage and $imax!=0) {
$topurl="$fskin/top.html";
ob_start(); include $topurl; $topurl=ob_get_contents(); ob_end_clean();
$topurl=str_replace("<meta name=\"Robots\" content=\"index,follow\">",'<meta name="Robots" content="noindex,follow">',$topurl);
print"$topurl";
exit("<noindex><br><br><br><br><center><table class=forumline width=700><tr><th class=thHead colspan=4 height=25>Страница отсутствует!</th></tr>
<tr class=row2><td class=row1><center><BR><BR><B><span style='FONT-SIZE: 14px'>Такой страницы нет. В этой рубрике всего $maxikpage страниц.<br> Вы можете перейти 
<a href='index.php?id=$id'>на первую</a> или <a href='index.php?id=$id&page=$maxikpage'>последнюю страницу</a>.<br>
Через несколько секунд Вы будете перемещены на последнюю страницу.<br><br></td></tr></table>
<script language='Javascript'>function reload() {location = \"index.php?id=$id&page=$maxikpage\"}; setTimeout('reload()', 10000);</script></noindex>
</td></tr></table></td></tr></table></body></html>");
$error=TRUE; }}

} // if (isset($_GET['id']))

if ($error==FALSE) include("$fskin/top.html"); addtop(); // подключаем ШАПКУ форума








// выводим ГЛАВНУЮ СТРАНИЦУ ФОРУМА

if (is_file("$datadir/usersdat.php")) { // считываем имя последнего зарегистрировавшегося
$userlines=file("$datadir/usersdat.php"); $dayx="";
$usercount=count($userlines); $ui=$usercount-1; $uu=$ui;
$tdt=explode("|", $userlines[$ui]);} else { $fp=fopen("$datadir/usersdat.php","a+"); fputs($fp,"<?die;?>\r\n"); fflush ($fp); fclose($fp); $ui=""; $tdt[0]="";}
$today=time();

if (!isset($_GET['id'])) {
if (strlen($frname)>1) {$frname=str_replace('->','',$frname); $frname="-> <B>$frname</B>";}
print"<table><tr><td><span class=nav>&nbsp;&nbsp;&nbsp;<a href='$furl' class=nav>$fname</a> $frname</span></td></tr></table>
<table width=100% cellpadding=2 cellspacing=1 class=forumline>
<tr>
<th width=62% colspan=2 class=thCornerL height=25>Форумы</th>
<th width=7% class=thTop>Тем</th>
<th width=7% class=thCornerR>Ответов</th>
<th width=28% class=thCornerR nowrap=nowrap>Обновление</th>
</tr>";

// Выводим все РУБРИКИ НА ГЛАВНОЙ
$adminmsg=""; if (is_file("$datadir/mainforum.dat")) $lines=file("$datadir/mainforum.dat");
if (!isset($lines)) $datasize=0; else $datasize=sizeof($lines);
if ($datasize<=0) {if (is_file("$datadir/copy.dat")) {$lines=file("$datadir/copy.dat"); $datasize=sizeof($lines);} $adminmsg="<font color=red><B>Администратор, внимание!!!</B> Файл БД с рубриками повреждён. Восстановите его из резервной копии в админке!</font><br>";}
if ($datasize<=0) exit("Проблемы с Базой данных - обратитесь к администратору");
$i=count($lines); $n="0"; $a1=$rfid-1; $u=$i-1; $fid="0"; $itogotem="0"; $itogomsg="0"; $alt=""; $konec="";

do {$a1++; $dt=explode("|", $lines[$a1]);

if (isset($dt[1])) { // Если строчка потерялась в скрипте (пустая строка) - то просто её НЕ выводим

if ($dt[1]=="razdel" and isset($_GET['razdel'])) $konec++; else {

// определяем тип: топик или заголовок
if ($dt[1]=="razdel") print "<tr height=28><td class=catLeft colspan=2 align=center><a href='index.php?razdel=$dt[0]'>$dt[2]</a></td><td class=rowpic colspan=4 align=right>&nbsp;</td></tr>";
 else {
//$dt[9] - дата размещения сообщения; $wrftime2 - последнее посещение
// Если $dt[9] раньше (т.е. больше) $wrftime2 значит раздел форума - новый
$foldicon="folder.gif"; if (isset($wrfname)) {if (isset($dt[9])) {if ($dt[9]>$wrftime2) $foldicon="foldernew.gif";}}


if (is_file("$datadir/$dt[3].dat")) { $msgsize=sizeof(file("$datadir/$dt[3].dat")); // считаем кол-во страниц в файле
if ($msgsize>$qq) $page=ceil($msgsize/$qq); else $page=1;} else $page=1;
if ($page!=1) $pageadd="&page=$page"; else $pageadd="";

if ($dt[7]==$date) $dt[7]="сегодня";
$maxzvezd=null; if (isset($dt[12])) { if ($dt[12]>=1) {$maxzvezd="*Доступна участникам, имеющим <font color=red><B>$dt[12]</B> звезд";
$dt[4]=""; $dt[5]="";
if ($dt[12]==1) $maxzvezd.="у";
if ($dt[12]==2 or $dt[12]==3 or $dt[12]==4) $maxzvezd.="ы";
$maxzvezd.=" минимум</font>";}}
$fid="$dt[0]"; 

// ФИШКА: если есть файлы типа main-FID.gif, где FID - число, то вместо обычных фолдерозаглушек, ставим этот файл
if (is_file("$fskin/main-$fid.gif")) {$foldicon="main-$fid.gif"; $alt="$dt[1]";} else $alt="";

$dt[8]=substr($dt[8],0,-3);
$dt[10]=replacer($dt[10]);
print "<tr align=center valign=middle height=50>
<td width=3% class=row1><img src=\"$fskin/$foldicon\" alt='$alt' border=0></td>
<td width=60% class=row1 align=left><span class=forumlink><a href=\"index.php?id=$fid\">$dt[1]</a> $maxzvezd<BR></span><small>$dt[2]</small></td>
<td class=row2><small>$dt[4]</small></td>
<td class=row2><small>$dt[5]</small></td>
<td class=row2 align=left><span class=gensmall>тема: "; if (strlen($dt[10])>0) print "<a href=\"index.php?id=$dt[3]$pageadd#m$msgsize\" title='$dt[10]'>$dt[10]</a>"; print"<BR>автор: <B>$dt[6]</B><BR>дата: <B>$dt[7]</B> - $dt[8]</span></td></tr>\r\r\n";
$itogotem=$itogotem+$dt[4];$itogomsg=$itogomsg+$dt[5]; }} if ($konec==2) $a1=$u;
} // if isset($dt[1]
} while($a1 < $u);
echo('</table><BR>');


// БЫСТРЫЙ ПЕРЕХОД к теме
echo '<table width=100% cellpadding=3 cellspacing=1 class=forumline><TR><TD class=catHead><span class=cattitle>Навигация</span></td></tr>
<tr><td class=row1 align=right><span class=gensmall>
Быстрый переход по рубрикам &nbsp; <select onchange="window.location=(\'index.php?id=\'+this.options[this.selectedIndex].value)">
<option>Выберите рубрику</option>';
$ii=count($mainlines); $cn=0; $i=0;
do {$dt=explode("|", $mainlines[$i]);
if ($dt[1]=="razdel") {if ($cn!=0) {echo'</optgroup>'; $cn=0;} $cn++; print"<optgroup label='$dt[2]'>";} else {print" <option value='$dt[0]'>|-$dt[1]</option>";}
$i++;} while($i<$ii);
echo'</optgroup></select></TD></TR></TABLE><br>';


if ($statistika==TRUE and !isset($_GET['razdel'])) { // СТАТИСТИКА ИТОГО ТЕМ/СООБЩЕНИЙ/ПРАВА ЮЗЕРОВ

if ($cangutema==TRUE) $c1="разрешено"; else $c1="запрещено";
if ($cangumsg ==TRUE) $c2="разрешено"; else $c2="запрещено";
$codename=urlencode($tdt[0]);
print"<table width=100% cellpadding=3 cellspacing=1 class=forumline><tr><td class=catHead colspan=2 height=28><span class=cattitle>Статистика</span></td></tr><tr>
<td class=row1 align=center valign=middle rowspan=2><img src=\"$fskin/whosonline.gif\"></td>
<td class=row1 align=left width=95%><span class=gensmall>
Сообщений: <b>$itogomsg</b><br>Тем: <b>$itogotem</b><br>Всего зарегистрировано участников: <b>";
if (!isset($wrfname)) print"$ui"; else print"<a href=\"tools.php?event=who\">$ui</a>";
echo'</b><br>Последним зарегистрировался: <B>';
if (!isset($wrfname)) print"$tdt[0]"; else print"<a href=\"tools.php?event=profile&pname=$codename\">$tdt[0]</a>";
print"</B><BR> Гостям <B>$c1</B> создавать темы и <B>$c2</B> отвечать в темах<BR>
$adminmsg
</span></td></tr></table>"; 


// СТАТИСТИКА -=ДНИ РОЖДЕНИЯ=-

if (is_file("$datadir/usersdat.php")) { // считываем всех юзеров, ищем дни варения
$userlines=file("$datadir/usersdat.php");
$usercount=count($userlines); $ui=$usercount-1; $t_people=""; $z_people=""; $s_people=""; $p_people=""; $s_p="0";
do {$udt=explode("|",$userlines[$ui]);
if (isset($udt[1])) {
$udt[5]=substr("$udt[5]",0, 10); // обрезаем дату рождения по формату
if (preg_match("(\d{2}\.\d{2}\.\d{4})",$udt[5])) { // соответствует шаблону ДД.ММ.ГГГГ
$bday=substr("$udt[5]",0,-5);
$dmtoday=date("d.m");
$todaydt=explode(".",$dmtoday);
$codename=urlencode($udt[0]);
// собираем для МЕГОДНЯШНИХ
if ($bday==$dmtoday) $t_people.="<a href='tools.php?event=profile&pname=$codename'>$udt[0]</a>,&nbsp;&nbsp; ";
$dt=explode(".",$udt[5]);
if ($dt[1]==12) $year=1972; else $year=1973; // для того, чтобы верно считать с декабря по январь
$newdate=mktime(0,0,0,$dt[1],$dt[0],$year); // для дня рождения
$tekdt=mktime(0,0,0,$todaydt[1],$todaydt[0],$year); // текущую дату переводим в этот формат
$deystodate=round(($newdate-$tekdt)/86400); // через сколько дней наступит событие
// собираем для ВЧЕРАШНИХ
if ($deystodate=="-1") $z_people.="<a href='tools.php?event=profile&pname=$codename'>$udt[0]</a>,&nbsp; ";
// собираем ПРИБЛИЖАЮЩИХСЯ в массив (БОЛЬШЕ 1 и МЕНЬШЕ 7 дней)
if ($deystodate>1 and $deystodate<7) {if ($deystodate<10) $deystodate="0$deystodate"; $s_peo[$s_p]="$deystodate|$udt[0]|"; $s_p++;}
} // if указан день варения в переменной $udt[5]
} // if iiset($udt[1])
$ui--; } while ($ui>0);

if (isset($s_peo)) {
usort($s_peo,"prcmp"); // сортируем дни по возрастанию
$i=0; do {$sdt=explode("|",$s_peo[$i]);
$sdt[0]=intval($sdt[0]); // преобразуем строку в число, чтобы отбросить 0 в начале
$codename=urlencode($sdt[1]);
$s_people.="<b><a href='tools.php?event=profile&pname=$codename'>$sdt[1]</a></b> ($sdt[0] дн.), "; $i++;
} while ($i<$s_p); }

// На тот случай, если дней рождения нет, делаем заглушки
if (strlen($z_people)>1) $z_people="<span class=gensmall><br>Вчера: <B>$z_people</B><font color=#800040> поздравляем с Днём варения!</font><br>";
if (strlen($t_people)>1) $t_people="<span class=gensmall><br>Сегодня: <B>$t_people</B><font color=red>искренне поздравляю!</font><br>";
if (strlen($s_people)>1) $s_people="<span class=gensmall><br>Скоро: $s_people <font color=#55002B>совсем скоро день варения!</font><br>";

if (strlen($z_people)>1 or strlen($t_people)>1 or strlen($s_people)>1){
print"<br><table width=100% cellpadding=3 cellspacing=1 class=forumline>
<tr><td class=catHead colspan=2 height=28><span class=cattitle>Дни рождения</span></td></tr><tr>
<td class=row1 align=center valign=middle rowspan=2><img src=\"$fskin/whosonline.gif\"></td>
<td class=row1 align=left width=95%>
$z_people $t_people $s_people<br>
</span></td></tr></table>";}
} // КОНЕЦ БЛОКА ДНИ РОЖДЕНИЯ



// СТАТИСТИКА -= Последние сообщения с форума =-
if (is_file("$datadir/news.dat")) { $newmessfile="$datadir/news.dat";
$lines=file($newmessfile); $i=count($lines); //if ($i>10) $i=10; (РАСКОМЕНТИРУЙ - ВОТ ГДЕ СИЛА!!! ;-))
if ($i>1) {
echo('<br><table width=100% cellpadding=3 cellspacing=1 class=forumline>
<tr><td class=catHead colspan=2 height=28><span class=cattitle>Последние сообщения</span></td></tr><tr>
<td class=row1 align=center valign=middle rowspan=2><img src="'.$fskin.'/whosonline.gif"></td>
<td class=row1 align=left width=95%><span class=gensmall>');

$a1=$i-1;$u="-1"; // выводим данные по возрастанию или убыванию
do {$dt=explode("|",$lines[$a1]); $a1--;

if (isset($dt[1])) { // Если строчка потерялась в скрипте (пустая строка) - то просто её НЕ выводим
$msg=htmlspecialchars($dt[6]);
$msg=str_replace("[b] "," ",$msg); $msg=str_replace("[/b]"," ",$msg);
$msg=str_replace("[RB] "," ",$msg); $msg=str_replace("[/RB]"," ",$msg);
$msg=str_replace("[Code] "," ",$msg); $msg=str_replace("[/Code]"," ",$msg);
$msg=str_replace("[Quote] "," ",$msg); $msg=str_replace("[/Quote]"," ",$msg);
$msg=str_replace("<br>","\r\n", $msg);
$msg=str_replace("'","`",$msg);
$msg=str_replace("&lt;br&gt;&lt;br&gt;","\r\n", $msg);
$dt[2]=str_replace(".201",".1", $dt[2]);
$dt[2]=substr($dt[2],0,8);
$dt[3]=substr($dt[3],0,5);
if ($dt[8]>$qq) $page=ceil($dt[8]/$qq); else $page=1; // Считаем страницу
if ($page!=1) $pageadd="&page=$page"; else $pageadd="";

if ($dt[10]=="да") {$codename=urlencode($dt[4]); if (!isset($wrfname)) $name="$dt[4]"; else $name="<B><a href='tools.php?event=profile&pname=$codename'>$dt[4]</a></B>";} else {$name="гость $dt[4]";}
print"$dt[2] - $dt[3]: <B><a href='index.php?id=$dt[0]'>$dt[9]</a></B> -> <B><a href='index.php?id=$dt[1]$pageadd#m$dt[8]' title='$msg \r\n\r\n Отправлено $dt[3], $dt[2] г.'>$dt[5]</a></B> - $name.<br>";
} // если строчка потерялась
$a11=$u; $u11=$a1;
} while($a11 < $u11);
echo'</span></td></tr></table>';}

} // Конец блока последних сообщений

if (is_file("tubest.php")) include("tubest.php"); // подключаем файл с: БЛОКом 15-и самых обсуждаемых тем и БЛОКом 10 самых активных пользователей

} // конец if (statistika==TRUE)

if (is_file("$fskin/bottom.html")) include("$fskin/bottom.html"); // подключаем НИЖНИЙ БЛОК форума

} // if !isset($id) // конец главной страницы

















if (isset($_GET['id'])) { $id=$_GET['id'];


// страница С ТЕМАМИ выбранной РУБРИКИ
if (strlen($id)==3) { $fid=$id;

// Защиты
if (!ctype_digit($fid) or strlen($fid)>3) exit("<B>$back. Номер рубрики должен быть цифровым и содержать менее 4 символов</B>");
$imax=count($mainlines); if (($fid>999) or (strlen($fid)==0)) exit("<b>Данный раздел удалён или не существует.</b>");

// Исключаем ошибку вызова несуществующей страницы
if (!isset($_GET['page'])) $page=1; else {$page=$_GET['page']; if (!ctype_digit($page)) $page=1; if ($page<1) $page=1;}

// Уточняем статус по кол-ву ЗВЁЗД юзера. Если меньше допустимых N в этой рубрике - то досвиданья!
$maxzd=null;
do {$imax--; $ddt=explode("|", $mainlines[$imax]); if ($ddt[0]==$fid) $maxzd=$ddt[12]; } while($imax>"0");
if ($maxzd>=1) {
if (!ctype_digit($maxzd)) exit("$back звёзды исчисляются в цифрах. а в файле данных - ерунда!");

$noacsess="<br><br><br><br><center><table class=forumline width=700><tr><th class=thHead colspan=4 height=25>Доступ в раздел ограничен</th></tr>
<tr class=row2><td class=row1><center><BR><BR><B><span style='FONT-SIZE: 14px'>Для просмотра данного раздела необходимо быть зарегистрированным и иметь рейтинг не менее $maxzd звёзд.";

// БЛОК проверяет логин и пароль юзера, считывает кол-во его звёзд и сравнивает с заданным в рубрике
if (isset($_COOKIE['wrfcookies'])) { // весь блок работает при наличии КУКИ
$text=$_COOKIE['wrfcookies']; 
$text=replacer($text);
$wrfc=explode("|",$text); $wrfname=$wrfc[0]; if (isset($wrfc[1])) $wrfpass=$wrfc[1]; else exit("$back попытка взлома - в куки нет пароля. Куда он делся ;-) ?");

// пробегаем файл с юзерами
$iu=$usercount; $ok=FALSE;
do {$iu--; $du=explode("|",$userlines[$iu]);
if (isset($du[1])) { $realname=strtolower($du[0]);
if (strtolower($wrfname)===$realname & $wrfpass===$du[1]) {$ok="$i"; if ($du[2]<$maxzd) exit("$noacsess У Вас всего $du[2] звёзд.</B></center><BR><BR>$back<BR><BR></td></table><br>"); }}
} while($iu > "0");
} else exit("$noacsess</B></center><BR><BR>$back<BR><BR></td></table><br>"); // если юзер тот, за кого себя выдаёт то его пускаем, иначе - обломаем
if ($ok!=FALSE) exit("$noacsess</B></center><BR><BR>$back<BR><BR></td></table><br>");
}

$addbutton="<table width=100%><tr><td align=left valign=middle>";
if ($stop!=TRUE) $addbutton.="<span class=nav><a name='add' href=\"index.php?newtema=add&id=$fid#add\"><img src='$fskin/newthread.gif' border=0></a>&nbsp;</span></td>";
else $addbutton.="Извините за неудобство, но администратор временно приостановил добавление тем и сообщений!";



// определяем есть ли информация в файле с данными
if (is_file("$datadir/topic$fid.dat")) {
$msglines=file("$datadir/topic$fid.dat"); $maxi=count($msglines); $i=$maxi;

if (isset($_POST['findme']) or isset($_GET['findme'])) {
// ЕСЛИ есть фильтр по названию темы, то:
// - Считываем файл с темами и отбираем в отдельный массив только те, которые содаржат в названии искомую фразу
// - в $maxi записываем кол-во тем
// - в $msglines[$i] записываем данные
setlocale(LC_ALL,'ru_RU.CP1251'); // ! РАЗРЕШАЕМ РАБОТУ ФУНКЦИЙ, работающих с регистором и с РУССКИМИ БУКВАМИ
if (isset($_POST['findme'])) $findme=replacer($_POST['findme']);
if (isset($_GET['findme'])) { $findme=replacer($_GET['findme']); $findme=urldecode($findme);}
$stroka=strlen($findme); if($stroka<4 or $stroka>30) exit("разрешается поиск в количестве от 4-х до 30-и символов!");
$tmplines=$msglines; $msglines=null; $i=0;
foreach($tmplines as $v) {$dt=explode("|", $v); if (stristr($dt[3],$findme)) {$msglines[$i]=$v; $i++;}}
$maxi=$i-1;} else $findme="";

$frname=str_replace(' ->','',$frname); //вырезаем лишние символы
print"<table width=100%><TR>
<td><span class=nav>&nbsp;&nbsp;&nbsp;<a href='$furl' class=nav>$fname</a> -> <a href='index.php?id=$id'><B>$frname</B></a> -></span></td>

<TD align=right><form action='index.php?id=$fid&find' method=POST name=finder>Фильтр по названию темы: 
<input name='findme' value='$findme' class=post type='text' maxlength=30 size=20>
<input type=submit class=mainoption value='Искать'></form></td></tr></table>

<table width=100% cellpadding=2 cellspacing=1 class=forumline><tr>
<th width=60% colspan=2 class=thCornerL height=25>Тема</th>
<th width=10% class=thTop nowrap=nowrap>Cообщений</th>
<th width=12% class=thCornerR nowrap=nowrap>Автор</th>
<th width=18% class=thCornerR>Обновления</th>
</tr>";

if ($maxi>0) {

if ($maxi>$maxtem-1) $addbutton="<table width=100%><TR><TD>Количество допустимых тем в рубрике исчерпано.";


// БЛОК СОРТИРОВКИ: последние ответы ВВЕРХУ (по времени создания файла с темой)!
do {$i--; $dt=explode("|", $msglines[$i]);
   $filename="$dt[7].dat"; if (is_file("$datadir/$filename")) $ftime=filemtime("$datadir/$filename"); else $ftime="";
   $newlines[$i]="$ftime|$dt[7]|$i|";
} while($i > 0);
usort($newlines,"prcmp");
//print"<PRE>"; print_r($msglines); //exit;
// $newlines - массив с данными: ДАТА | ИМЯ_ФАЙЛА_С_ТЕМОЙ | № п/п |
// $msglines - массив со всеми темами выбранной рубрики
$i=$maxi;
do {$i--; $dtn=explode("|", $newlines[$i]);
  $numtp="$dtn[2]"; $lines[$i]="$msglines[$numtp]";
} while($i > 0);
// КОНЕЦ блока сортировки

// Показываем QQmain ТЕМ
$fm=$maxi-$qqmain*($page-1);
if ($fm<"0") $fm=$qqmain; $lm=$fm-$qqmain; if ($lm<"0") $lm="0";

do {$fm--; $num=$fm+2;
$dt=explode("|", $lines[$fm]);

// нужно для определения темы на VIP-статус
$dtn=explode("|", $newlines[$fm]);
$timer=time()-$dtn[0]; // узнаем сколько прошло времени (в секундах) 


$filename=$dt[7]; 
if (is_file("$datadir/$filename.dat")) { // если файл с темой существует - то показать тему в списке!
$msgsize=sizeof(file("$datadir/$filename.dat"));

// --------- Выделяем новые сообщения
$linetmp=file("$datadir/$filename.dat"); if (sizeof($linetmp)!=0) {
$pos=$msgsize-1; $dtt=explode("|", $linetmp[$pos]);
$foldicon="folder.gif";
// Если последнее сообщение в форуме произошло раньше посещения - значит раздел форума - новый
if (isset($wrfname)) {if (isset($dtt[9])) {if ($dtt[9]>$wrftime2) $foldicon="foldernew.gif";}}
if (strlen($dt[8])>1 and $dt[8]=="closed") {if ($msgsize<"20") $foldicon="close.gif"; else $foldicon="closed.gif";}} else $foldicon="foldernew.gif";
// --------- Конец

print "<tr height=50 align=center valign=middle>
<td width=3% class=row1><img src=\"$fskin/$foldicon\" border=0></td>
<td class=row1 align=left><span class=forumlink><b>";

if ($timer<0) echo'<font color=red>VIP </font>';

$dt[3]=replacer($dt[3]);
print"<a href=\"index.php?id=$dt[7]\" title='$dt[3]'>$dt[3]</a></B>";


if ($msgsize>$qq) { // ВЫВОДИМ СПИСОК ДОСТУПНЫХ СТРАНИЦ ТЕМЫ
$maxpaget=ceil($msgsize/$qq); $addpage="";
echo'</b></span><small>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div style="padding:6px;" class=pgbutt>Страницы: ';
if ($maxpaget<=5) $f1=$maxpaget; else $f1=5;
for($i=1; $i<=$f1; $i++) {if ($i!=1) $addpage="&page=$i"; print"<a href=index.php?id=$dt[7]$addpage>$i</a> &nbsp;";}
if ($maxpaget>5) print "... <a href=index.php?id=$dt[7]&page=$maxpaget>$maxpaget</a>"; }


print"</div></td><td class=row2>$msgsize</td><td class=row2><span class=gensmall>";

$codename=urlencode($dt[0]);
if ($dt[1]=="да") {
if (!isset($wrfname)) print "$dt[0]"; else print "<a href='tools.php?event=profile&pname=$codename':$dt[2]>$dt[0]</a>";
print"<BR><small>$users</small>"; } else print"$dt[0]<BR><small>$guest</small>";

// защита if (strlen...) только если файл есть и имеет верный формат - выводим
if ($msgsize>=2) {$linesdat=file("$datadir/$filename.dat"); $dtdat=explode("|", $linesdat[$msgsize-1]);
if (strlen($linesdat[$msgsize-1])>10) {$dt[0]=$dtdat[0]; $dt[1]=$dtdat[1]; $dt[2]=$dtdat[2]; $dt[5]=$dtdat[5]; $dt[6]=$dtdat[6];}}

$dt[6]=substr($dt[6],0,-3);
if ($dt[5]===$date) $dt[5]="<B>сегодня</B>";
print "</span></td>
<td class=row2 align=left><span class=gensmall>автор: $dt[0]<BR>дата/время: $dt[5] в $dt[6]</span></td>
</tr>\r\r\n";

} //if is_file

} while($lm < $fm);


// формируем переменную $pageinfo - со СПИСКОМ СТРАНИЦ
if (strlen($findme)>1) $findadd="&findme=$findme"; else $findadd="";
$pageinfo=""; $addpage=""; $maxpage=ceil(($maxi+1)/$qqmain); if ($page>$maxpage) $page=$maxpage;
$pageinfo.="<div style='padding:6px;' class=pgbutt>Страницы: &nbsp;";
if ($page>3 and $maxpage>5) $pageinfo.="<a href=index.php?id=$fid$findadd>1</a> ... ";
$f1=$page+2; $f2=abs($page-2); if ($f2=="0") $f2=1; if ($page>=$maxpage-1) $f1=$maxpage;
if ($maxpage<=5) {$f1=$maxpage; $f2=1;}
for($i=$f2; $i<=$f1; $i++) { if ($page==$i) $pageinfo.="<B>$i</B> &nbsp;"; 
else {if ($i!=1) $addpage="&page=$i"; $pageinfo.="<a href=index.php?id=$fid$addpage$findadd>$i</a> &nbsp;";} }
if ($page<=$maxpage-3 and $maxpage>5) $pageinfo.="... <a href=index.php?id=$fid&page=$maxpage$findadd>$maxpage</a>";
$pageinfo.='</div>';

print"
$addbutton<TD><table width=100%><tr><td align=right colspan=3>
$pageinfo</b></span></td></tr></table>";

} else print"$addbutton";

} else print"$addbutton";

echo'</tr></table><BR>';


if (isset($_GET['newtema'])) { if ($cangutema=="0" and !isset($wrfname)) print"<center><h5>Администратор запретил создавать гостям темы! Для регистрации пройдите по ссылке: <B><a href='tools.php?event=reg'>зарегистрироваться</a></B></h5></center><BR><BR>"; else {
$maxzag=$maxzag-10; // так нужно!!!
print"<form action=\"index.php?event=addtopic&id=$fid\" method=post enctype=\"multipart/form-data\" name=REPLIER><table width=100% class=forumline><tr><td class=catHead colspan=2 height=28><span class=cattitle>Добавление темы</span></td></tr>
<tr><td class=row1 align=right valign=top>Заголовок темы</TD><TD class=row2>
<input type=hidden name=maxzd value='$maxzd'><input type=text class=post name=zag maxlength=$maxzag size=70>
</TD></TR>";
addmsg("");
} }


if ($maxi>0) { // БЫСТРЫЙ ПЕРЕХОД к теме
echo '<br><table width=100% cellpadding=3 cellspacing=1 class=forumline><TR><TD class=catHead><span class=cattitle>Навигация</span></td></tr>
<tr><td class=row1 align=right><span class=gensmall>
Быстрый переход по темам &nbsp; <select onchange="window.location=(\'index.php?id=\'+this.options[this.selectedIndex].value)">
<option>Выберите тему</option>';
$ii=$maxi; $cn=0; $i=0;
do {$dt=explode("|", $lines[$i]); print" <option value='$dt[7]'>$dt[3]</option>"; $i++;} while($i<$ii);
echo'</optgroup></select></TD></TR></TABLE>'; } // if($maxi>0)
}








// показываем СООБЩЕНИЯ выбранной темы
if (strlen($id)>6) { $fid=substr($id,0,3);

// определяем есть ли информация в файле с данными
if (!is_file("$datadir/$id.dat")) exit("<BR>$back. Извините, но такой темы на форуме не существует.<BR> Скорее всего её удалил администратор.");
$lines=file("$datadir/$id.dat"); $mitogo=count($lines); $i=$mitogo; $maxi=$i-1;

if ($mitogo>0) { $tblstyle="row1"; $printvote=null;

// Считываем СТАТИСТИКУ ВСЕХ УЧАСТНИКОВ
if (is_file("$datadir/userstat.dat")) {$ufile="$datadir/userstat.dat"; $ulines=file("$ufile"); $ui=count($ulines)-1;}

// Ищем тему в topicХХ.dat - проверяем не закрыта ли тема? и сразу же ищем есть ли в топике
$ok=FALSE; $closed=FALSE; if (is_file("$datadir/topic$fid.dat")) {
$msglines=file("$datadir/topic$fid.dat"); $mg=count($msglines);
do {$mg--; $mt=explode("|",$msglines[$mg]);
if ($mt[7]==$id and $mt[8]=="closed") $closed=TRUE;
if ($mt[7]==$id) $ok=1; // тема есть в указанном разделе?
} while($mg > "0");}

$realbase="1"; if (is_file("$datadir/mainforum.dat")) $mainlines=file("$datadir/mainforum.dat");
if (!isset($mainlines)) $datasize=0; else $datasize=sizeof($mainlines);
if ($datasize<=0) {if (is_file("$datadir/copy.dat")) {$realbase="0"; $mainlines=file("$datadir/copy.dat"); $datasize=sizeof($mainlines);}}
if ($datasize<=0) exit("$back. Проблемы с Базой данных - обратитесь к администратору");
$i=count($mainlines); $imax=$i;

// Уточняем статус по кол-ву ЗВЁЗД юзера. Если меньше допустимых N в этой рубрике - то досвиданья!
$maxzd=null; do {$imax--; $ddt=explode("|",$mainlines[$imax]); if ($ddt[0]==$fid) $maxzd=$ddt[12]; } while($imax>"0");

if ($maxzd>=1) {
if (!ctype_digit($maxzd)) exit("$back звёзды исчисляются в цифрах. а в файле данных - ерунда!");
$noacsess="<br><br><br><br><table class=forumline align=center width=700><tr><th class=thHead colspan=4 height=25>Доступ в раздел ограничен</th></tr>
<tr class=row2><td class=row1><center><BR><BR><B><span style='FONT-SIZE: 14px'>Для просмотра данного раздела необходимо быть зарегитстрированным и иметь рейтинг не менее $maxzd звёзд.";

if ($ok==FALSE) exit("<br><br>$back. Номер раздела указан ошибочно!<br> В указаном разделе нет такой темы!");
// БЛОК проверяет логин и пароль юзера, считывает кол-во его звёзд и сравнивает с заданным в рубрике
if (isset($_COOKIE['wrfcookies'])) { // весь блок работает при наличии КУКИ
$text=$_COOKIE['wrfcookies']; 
$text=replacer($text);
$wrfc=explode("|",$text); $wrfname=$wrfc[0]; if (isset($wrfc[1])) $wrfpass=$wrfc[1]; else exit("$back попытка взлома - в куки нет пароля. Куда он делся ;-) ?");
// пробегаем файл с юзерами и считываем его в память
$iu=$usercount; $ok=FALSE;
do {$iu--; $du=explode("|",$userlines[$iu]);
if (isset($du[1])) { $realname=strtolower($du[0]);
if (strtolower($wrfname)===$realname & $wrfpass===$du[1]) {$ok="$i"; print" $ok $du[2]<$maxzd"; if ($du[2]<$maxzd) exit("$noacsess У Вас всего $du[2] звёзд.</B></center><BR><BR>$back<BR><BR></td></table><br>"); }}
} while($iu > "0");
} else exit("$noacsess</B></center><BR><BR>$back<BR><BR></td></table><br>"); // если юзер тот, за кого себя выдаёт то его пускаем, иначе - обломаем
if ($ok==FALSE) exit("$noacsess</B></center><BR><BR>$back<BR><BR></td></table><br>");
}



// Исключаем ошибку вызова несуществующей страницы
if (!isset($_GET['page'])) $page=1; else {$page=$_GET['page']; if (!ctype_digit($page)) $page=1; if ($page<1) $page=1;}

$fm=$qq*($page-1); if ($fm>$maxi) $fm=$maxi-$qq;
$lm=$fm+$qq; if ($lm>$maxi) $lm=$maxi+1;


// формируем переменную $pageinfo - со СПИСКОМ СТРАНИЦ
$pageinfo=""; $addpage=""; $maxpage=ceil(($maxi+1)/$qq); if ($page>$maxpage) $page=$maxpage;
$pageinfo.="<div align=center style='padding:6px;' class=pgbutt>Страницы: &nbsp;";
if ($page>3 and $maxpage>5) $pageinfo.="<a href=index.php?id=$id>1</a> ... ";
$f1=$page+2; $f2=abs($page-2); if ($f2=="0") $f2=1; if ($page>=$maxpage-1) $f1=$maxpage;
if ($maxpage<=5) {$f1=$maxpage; $f2=1;}
for($i=$f2; $i<=$f1; $i++) { if ($page==$i) $pageinfo.="<B>$i</B> &nbsp;"; 
else {if ($i!=1) $addpage="&page=$i"; $pageinfo.="<a href=index.php?id=$id$addpage>$i</a> &nbsp;";} }
if ($page<=$maxpage-3 and $maxpage>5) $pageinfo.="... <a href=index.php?id=$id&page=$maxpage>$maxpage</a>";
$pageinfo.='</div>';

$qm=null; $flag=0;
do {$dt=explode("|", replacer($lines[$fm]));

$youwr=null; $fm++; $num=$maxi-$fm+2; $status="";

if (strlen($lines[$fm-1])>5) { // Если строчка потерялась в скрипте (пустая строка) - то просто её НЕ выводим

if (isset($_GET['quotemsg'])) {$quotemsg=replacer($_GET['quotemsg']); if(ctype_digit($quotemsg) and $quotemsg==$fm) $qm="[Quote][b]$dt[0] пишет:[/b]\r\n".$dt[4]."[/Quote]";}

$msg=str_replace("[b]","<b>",$dt[4]);
$msg=str_replace("[/b]","</b>", $msg);
$msg=str_replace("[RB]","<font color=red><B>", $msg);
$msg=str_replace("[/RB]","</B></font>", $msg);
$msg=str_replace("&lt;br&gt;","<br>",$msg);
$msg=preg_replace("#\[Quote\]\s*(.*?)\s*\[/Quote\]#is","<br><B><U>Цитата:</U></B><table width=80% border=0 cellpadding=5 cellspacing=1 style='padding: 5px; margin: 1px'><tr><td class=quote>$1</td></tr></table>",$msg);
$msg=preg_replace("#\[Code\]\s*(.*?)\s*\[/Code\]#is"," <br><B><U>PHP код:</U></B><table width=80% border=0 cellpadding=5 cellspacing=1 style='padding: 5px; margin: 1px'><tr><td class=code >$1</td></tr></table>",$msg);

if ($antimat==TRUE) { // АНТИМАТ
$pattern="/\w{0,5}[хx]([хx\s\!@#\$%\^&*+-\|\/]{0,6})[уy]([уy\s\!@#\$%\^&*+-\|\/]{0,6})[ёiлeеюийя]\w{0,7}|\w{0,6}[пp]([пp\s\!@#\$%\^&*+-\|\/]{0,6})[iие]([iие\s\!@#\$%\^&*+-\|\/]{0,6})[3зс]([3зс\s\!@#\$%\^&*+-\|\/]{0,6})[дd]\w{0,10}|[сcs][уy]([уy\!@#\$%\^&*+-\|\/]{0,6})[4чkк]\w{1,3}|\w{0,4}[bб]([bб\s\!@#\$%\^&*+-\|\/]{0,6})[lл]([lл\s\!@#\$%\^&*+-\|\/]{0,6})[yя]\w{0,10}|\w{0,8}[её][bб][лске@eыиаa][наи@йвл]\w{0,8}|\w{0,4}[еe]([еe\s\!@#\$%\^&*+-\|\/]{0,6})[бb]([бb\s\!@#\$%\^&*+-\|\/]{0,6})[uу]([uу\s\!@#\$%\^&*+-\|\/]{0,6})[н4ч]\w{0,4}|\w{0,4}[еeё]([еeё\s\!@#\$%\^&*+-\|\/]{0,6})[бb]([бb\s\!@#\$%\^&*+-\|\/]{0,6})[нn]([нn\s\!@#\$%\^&*+-\|\/]{0,6})[уy]\w{0,4}|\w{0,4}[еe]([еe\s\!@#\$%\^&*+-\|\/]{0,6})[бb]([бb\s\!@#\$%\^&*+-\|\/]{0,6})[оoаa@]([оoаa@\s\!@#\$%\^&*+-\|\/]{0,6})[тnнt]\w{0,4}|\w{0,10}[ё]([ё\!@#\$%\^&*+-\|\/]{0,6})[б]\w{0,6}|\w{0,4}[pп]([pп\s\!@#\$%\^&*+-\|\/]{0,6})[иeеi]([иeеi\s\!@#\$%\^&*+-\|\/]{0,6})[дd]([дd\s\!@#\$%\^&*+-\|\/]{0,6})[oоаa@еeиi]([oоаa@еeиi\s\!@#\$%\^&*+-\|\/]{0,6})[рr]\w{0,12}/i";
$msg=preg_replace("$pattern","<b><font color='red'>Цензура</font></b>",$msg); }

if ($smile==TRUE) { // СМАЙЛИКИ
$i=count($smiles)-1; for($k=0; $k<$i; $k=$k+2)
{$j=$k+1; $msg=str_replace("$smiles[$j]","<img src='smile/$smiles[$k].gif' border=0>",$msg);}}

// Если разрешена публикация УРЛов
if ($liteurl==TRUE) $msg=preg_replace ("#([^\[img\]])(http|https|ftp|goper):\/\/([a-zA-Z0-9\.\?&=\;\-\/_]+)([\W\s<\[]+)#i", "\\1<a href=\"\\2://\\3\" target=\"_blank\">\\2://\\3</a>\\4", $msg);

// запускать ТОЛЬКО после замены АДРЕСА URL!!!
$msg=preg_replace('#\[img\](.+?)\[/img\]#','<img src="$1" border="0">',$msg);

// считываем в память данные по пользователю
if ($dt[1]=="да") { $iu=$usercount; $predup="0";
do {$iu--; $du=explode("|", $userlines[$iu]); if ($du[0]==$dt[0])
{ if (isset($du[12])) {$status=$du[13]; $reiting=$du[2]; $youavatar=$du[12]; $email=$du[3]; $icq=$du[7]; $site=$du[8]; $userpn=$iu;}
if (isset($_COOKIE['wrfcookies'])) $youwr=preg_replace("#(\[url=([^\]]+)\](.*?)\[/url\])|(http://(www.)?[0-9a-z\.-]+\.[a-z]{2,6}[0-9a-z/\?=&\._-]*)#","<a href=\"$4\" target='_blank'>$4</a> ",$du[11]); else $youwr=$du[11];}
} while($iu > "0");
}

if ($tblstyle=="row1") $tblstyle="row2"; else $tblstyle="row1";

if ($flag==FALSE) { // БЛОК ПЕЧАТАЕМ ОДИН РАЗ
$frname=str_replace(' ->','',$frname); $frtname=str_replace(' ->','',$frtname); //вырезаем лишние символы
$flag=TRUE; print "
<table><tr><td>
<span class=nav>&nbsp;&nbsp;&nbsp;<a href=\"$furl\" class=nav>$fname</a> ->
<a href=\"index.php?id=$fid\" class=nav>$frname</a> ->
<a href=\"index.php?id=$id\" class=nav><strong>$frtname</strong></a></span>
</td></tr></table>
$pageinfo
<table class=forumline width=100% cellspacing=1 cellpadding=3><tr>
<th class=thLeft width=160 height=26 nowrap=nowrap>Автор</th><th class=thRight>Сообщение</th>";}

print"</tr>
<tr height=150><td class=$tblstyle valign=top><span class=name><BR><center>";

// Проверяем: это гость?
if (!isset($youwr)) {if (strlen($dt[2])>5) print "$dt[0] "; else print"$dt[0] "; $kuda=$fm-1; print" <a href='javascript:%20x()' name='m$fm' onclick=\"DoSmilie('[b]$dt[0][/b], ');\" class=nav>".chr(149)."</a><BR><br>";
// если емайл указан, печатаем форму для отправки ЛС
if (strlen($dt[2])>5) print"
<form method='post' action='tools.php?event=mailto' target='email' onclick=\"window.open('tools.php?event=mailto','email','width=600,height=350,left=100,top=100');return true;\">
<input type=hidden name='email' value='$dt[2]'><input type=hidden name='name' value='$dt[0]'><input type=hidden name='id' value='$id'>
<input type=image src='$fskin/ico_pm.gif' alt='личное сообщение'></form>";
print"<br><BR><small>$guest</small>";}


else {
$codename=urlencode($dt[0]);
if (!isset($wrfname)) print"$dt[0]"; else print"<a name='m$fm' href='tools.php?event=profile&pname=$codename' class=nav>$dt[0]</a>";
print" <a href='javascript:%20x()' onclick=\"DoSmilie('[b]$dt[0][/b], ');\" class=nav>".chr(149)."</a><BR><BR><small>";
if (strlen($status)>2 & $dt[1]=="да" & isset($youwr)) print"$status"; else print"$users";
if (isset($reiting)) {if ($reiting>0) {echo'<BR>'; if (is_file("$fskin/star.gif")) {for ($ri=0;$ri<$reiting;$ri++) {print"<img src='$fskin/star.gif' border=0> ";} } }}

if (isset($youavatar)) { if (is_file("avatars/$youavatar")) $avpr="$youavatar"; else $avpr="noavatar.gif";
print "<BR><BR><img src='avatars/$avpr'><BR> 


<form action='pm.php?id=$dt[0]' method=POST name=citata>
<input type=image border=0 src='data-pm/pm.gif' alt='Отправить ПЕРСОНАЛЬНОЕ СООБЩЕНИЕ'>
</form></span> <!--
<a href='tools.php?event=profile&pname=$dt[0]'><img src='$fskin/profile.gif' alt='Профиль' border=0></a>
<a href='$site'><img src='$fskin/www.gif' alt='www' border=0></a><BR>
<a href='$icq'><img src='$fskin/icq.gif' alt='ICQ' border=0></a>
<form method='post' action='tools.php?event=mailto' target='email' onclick=\"window.open('tools.php?event=mailto','email','width=600,height=350,left=100,top=100');return true;\">
<input type=hidden name='email' value='$dt[2]'><input type=hidden name='name' value='$dt[0]'><input type=hidden name='id' value='$id'>
<input type=image src='$fskin/ico_pm.gif' alt='личное сообщение'></form> -->
";}
} // isset($youwr)

if (isset($youwr) and is_file("$datadir/userstat.dat")) { // ТОЛЬКО участники видят всю репутацию! ;-)
if (isset($ulines[$userpn])) {
if (strlen($ulines[$userpn])>5) {
$ddu=explode("|",replacer($ulines[$userpn]));
$winop="window.open('tools.php?event=repa&name=$dt[0]&who=$userpn','repa','width=500,height=500,left=100,top=100')";
print"</small></span><br>
<noindex>
<fieldset STYLE='color:#646464'>
<legend STYLE='font-weight:bold;'>Статистика:</legend>
<div style='PADDING:3px;' align=left class=gensmall>Тем создано: $ddu[1]<br>Сообщений: $ddu[2]<br>Репутация: $ddu[3] <A href='#$fm' style='text-decoration:none' onclick=\"$winop\">&#177;</A></div>
</fieldset></noindex>"; }}}

print "</td>
<td class=$tblstyle width=100% height=28 valign=top>
<table width=100% height=100%><tr valign=center><td><span class=postbody>$msg</span>";



// БЛОК ГОЛОСОВАНИЯ (Если админ завёл голосование, то выводим)
if ($fm==1+$qq*($page-1) and is_file("$datadir/$id-vote.dat")) { // БЛОК ПЕЧАТАЕМ ОДИН РАЗ
$vlines=file("$datadir/$id-vote.dat");
if (sizeof($vlines)>0) {$vitogo=count($vlines); $vi=1; $vdt=explode("|",$vlines[0]);

print"<FORM name=wrvote action='vote.php?id=$id' method=POST target='WRGolos'>
<TABLE class=forumline cellSpacing=1 cellPadding=0 align=center border=0>
<TR><Th colspan=3 class=thHead><B>Голосование: &nbsp;$vdt[0]&nbsp;</B></Th></TR>
<TR class=$tblstyle><TD class=$tblstyle>";

do {$vdt=explode("|",$vlines[$vi]);
print"&nbsp;&nbsp;&nbsp;&nbsp; <INPUT name='votec' type=radio value='$vi'> &nbsp; <B>$vdt[0]</B><br><br>";
$vi++; } while($vi<$vitogo);

print "<center><INPUT name='id' type=hidden value='$id'>
<INPUT type=submit value='проголосовать' onclick=\"window.open('vote.php?id=$id','WRGolos','width=650,height=300,left=200,top=200,toolbar=0,status=0,border=0,scrollbars=0')\" border=0>
<br><br><A href='#' onclick=\"window.open('vote.php?rezultat&id=$id','WRGolos','width=650,height=300,left=200,top=200,toolbar=0,status=0,border=0,scrollbars=0')\" target='WRRezultGolos'>Результаты</A></center></FORM>
</TD></TR></TABLE>"; }} // КОНЕЦ БЛОКА ГОЛОСОВАНИЯ


print"</td></tr><TR><TD>";


// Если ПРИКРЕПЛЁН ФАЙЛ к сообщению - то показываем значёк и ссылку на него или картинку
if (isset($dt[12])) { if ($dt[12]!="" and is_file("$filedir/$dt[13]")) {
$fsize=round($dt[14]/10.24)/100; echo'<fieldset style="width:30%; color:#008000"><legend>Прикреплён файл:</legend>';
if (preg_match("/.(jpg|jpeg|bmp|gif|png)+$/is",$dt[13]))
print"<img border=0 src='$filedir/$dt[13]'>"; else 
print"<img border=0 src='$fskin/ico_file.gif'> <a href='$filedir/$dt[13]'>$dt[13]</a> ($fsize Кб.)</fieldset>"; }}

// печатаем подпись участника
if (isset($youwr)) { if (strlen($youwr)>3) print "<tr><td valign=bottom><span class=postbody>--------------------------------------------------<BR><small>$youwr</small>";}

// Если имя юзер участник и имя юзера=имени в сообщении и сообщение последнее, то вывести кнопку удаления
$codename=urlencode($dt[0]);
if (isset($wrfname)) { if ($fm==($maxi+1) and strtolower($dt[0])==strtolower($wrfname)) print"<table><TR><td width=180 bgcolor=#FF2244><B><a href='tools.php?event=deletemsg&id=$id&username=$codename' title='УДАЛИТЬ' onclick=\"return confirm('Будет удалёно Ваше последнее сообщение! Удалить? Уверены?')\" >.УДАЛИТЬ СООБЩЕНИЕ.</a></B></td></tr></table>";}

$addpage=""; if ($page>1) $addpage="&page=$page"; // нужно для цитирования
print"</table></td></tr><tr>
<td class=row3 valign=middle><span class=postdetails><I>Сообщение # <B>$fm</B></I></span></td>
<td class=row3 width=100% height=28><span class=postdetails><b>$dt[5]</b> в $dt[6]
<form action='index.php?id=$id$addpage&quotemsg=$fm#add' method=POST name=citata><input type=submit class=mainoption value='Цитировать'></form></span></td></tr>
<tr><td class=spaceRow colspan=2 height=1><img src=\"$fskin/spacer.gif\" width=1 height=1></td>";

} // если строчка потерялась
} while($fm < $lm);

print" </tr></table> $pageinfo";


if ($cangumsg==FALSE and !isset($wrfname)) {print"<center>Администратор запретил отвечать гостям на сообщения! Для регистрации пройдите по ссылке: <B><a href='tools.php?event=reg'>зарегистрироваться</a></B></center><BR><BR>"; } else {
if ($closed==FALSE) {

if (isset($_COOKIE['wrfcookies'])) {$wrfc=$_COOKIE['wrfcookies']; $wrfc=htmlspecialchars($wrfc); $wrfc=stripslashes($wrfc); $wrfc=explode("|", $wrfc); $wrfpass=replacer($wrfc[1]);} else {unset($wrfpass); $wrfpass="";}

if ($stop==FALSE) {

print "
<form action=\"index.php?event=addanswer&id=$id\" method=post name=REPLIER enctype=\"multipart/form-data\">
<input type=hidden name=userpass value=\"$wrfpass\">
<input type=hidden name=page value='$page'>
<input type=hidden name=zag value=\"$dt[3]\">
<input type=hidden name=maxzd value='$maxzd'>
<table cellpadding=3 cellspacing=1 width=100% class=forumline>
<tr><th class=thHead colspan=2 height=25><b>Сообщение</b></th></tr>";

addmsg($qm);
} else echo'<center>Извините за неудобство, но администратор временно приостановил добавление тем и сообщений!';
} else echo'<center><font style="font-size: 16px;font-weight:bold;"><BR>Тема закрыта для обсуждения!<BR><BR>';
}}

}
} // if isset($id)

?>
</td></tr></table>
<center><font size=-2><small>Powered by <a href="http://www.wr-script.ru" title="php скрипт форума" class="copyright">WR-Forum</a> Professional &copy; 1.9.9<br></small></font></center>
</body>
</html>
