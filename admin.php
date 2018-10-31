<? // WR-forum v 1.9.9  //  20.07.12 г.  //  Miha-ingener@yandex.ru

error_reporting (E_ALL); //error_reporting(0);
ini_set('register_globals','off');// Все скрипты написаны для этой настройки php

include "config.php";

$skey="657567"; // Секретный ключ НЕ МЕНЯТЬ !!! 
$adminpass=$password; // Авторизация


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


function unreplacer ($text) { // ФУНКЦИЯ замены спецсимволов конца строки на обычные
$text=str_replace("&lt;br&gt;","<br>",$text);
$text=str_replace("&#124;","|",$text);
return $text;}


function nospam() { global $max_key,$rand_key; // Функция АНТИСПАМ
if (array_key_exists("image", $_REQUEST)) { $num=replacer($_REQUEST["image"]);
for ($i=0; $i<10; $i++) {if (md5("$i+$rand_key")==$num) {imgwr($st,$i); die();}} }
$xkey=""; mt_srand(time()+(double)microtime()*1000000);
$dopkod=mktime(0,0,0,date("m"),date("d"),date("Y")); // доп.код: меняется каждые 24 часа
$stime=md5("$dopkod+$rand_key");// доп.код
echo'Защитный код: ';
for ($i=0; $i<$max_key; $i++) {
$snum[$i]=mt_rand(0,9); $psnum=md5($snum[$i]+$rand_key+$dopkod);
echo "<img src=antispam.php?image=$psnum border='0' alt=''>\n";
$xkey=$xkey.$snum[$i];}
$xkey=md5("$xkey+$rand_key+$dopkod"); //число + ключ из config.php + код меняющийся кажые 24 часа
print" <input name='usernum' class=post type='text' style='WIDTH: 70px;' maxlength=$max_key size=6>
<input name=xkey type=hidden value='$xkey'>
<input name=stime type=hidden value='$stime'>";
return; }


// Выбран ВЫХОД - очищаем куки
if(isset($_GET['event'])) { if ($_GET['event']=="clearcooke") { setcookie("wrforumm","",time()-3600); Header("Location: index.php"); exit; } }

if (isset($_COOKIE['wrforumm'])) { // Сверяем имя/пароль из КУКИ с заданным в конфиг файле
$text=$_COOKIE['wrforumm'];
$text=trim($text); // Вырезает ПРОБЕЛьные символы 
if (strlen($text)>60) exit("Попытка взлома - длина переменной куки сильно большая!");
$text=replacer($text);
$exd=explode("|",$text); $name1=$exd[0]; $pass1=$exd[1];

if (($name1!=$adminname and $name1!=$modername) or ($pass1!=$adminpass and $pass1!=$moderpass)) {sleep(1); setcookie("wrforumm", "0", time()-3600); Header("Location: admin.php"); exit;}

} else { // ЕСЛИ ваще нету КУКИ

if (isset($_POST['name']) & isset($_POST['pass'])) { // Если есть переменные из формы ввода пароля
$name=str_replace("|","I",$_POST['name']); $pass=str_replace("|","I",$_POST['pass']);
$text="$name|$pass|";
$text=trim($text); // Вырезает ПРОБЕЛьные символы 
if (strlen($text)<4) exit("$back Вы не ввели имя или пароль!");
$text=replacer($text);
$exd=explode("|",$text); $name=$exd[0]; $pass=$exd[1];

//$qq=md5("$pass+$skey"); exit("$qq"); // РАЗБЛОКИРУЙТЕ для получения MD5 своего пароля!

//--А-Н-Т-И-С-П-А-М--проверка кода--
if ($antispam==TRUE) {
if (!isset($_POST['usernum']) or !isset($_POST['xkey']) or !isset($_POST['stime']) ) exit("данные из формы не поступили!");
$usernum=replacer($_POST['usernum']); $xkey=replacer($_POST['xkey']); $stime=replacer($_POST['stime']);
$dopkod=mktime(0,0,0,date("m"),date("d"),date("Y")); // доп.код. Меняется каждые 24 часа
$usertime=md5("$dopkod+$rand_key");// доп.код
$userkey=md5("$usernum+$rand_key+$dopkod");
if (($usertime!=$stime) or ($userkey!=$xkey)) exit("введён ОШИБОЧНЫЙ код!");}


// Сверяем введённое имя/пароль с заданным в конфиг файле
$tektime=time();
// присваиваются куки АДМИНИСТРАТОРУ
if ($name==$adminname & md5("$pass+$skey")==$adminpass) {$wrforumm="$adminname|$adminpass|$tektime|"; setcookie("wrforumm", $wrforumm, time()+18000); Header("Location: admin.php"); exit;}
// присваиваются куки МОДЕРАТОРУ
if ($name==$modername & md5("$pass+$skey")==$moderpass) {$wrforumm="$modername|$moderpass|$tektime|"; setcookie("wrforumm", $wrforumm, time()+18000); Header("Location: admin.php"); exit;}
exit("Ваши данные <B>ОШИБОЧНЫ</B>!</center>");

} else { // если нету данных, то выводим ФОРМУ ввода пароля

echo '<html><head><META HTTP-EQUIV="Pragma" CONTENT="no-cache"><META HTTP-EQUIV="Cache-Control" CONTENT="no-cache"><META content="text/html; charset=windows-1251" http-equiv=Content-Type><style>input, textarea {font-family:Verdana; font-size:12px; text-decoration:none; color:#000000; cursor:default; background-color:#FFFFFF; border-style:solid; border-width:1px; border-color:#000000;}</style></head><body>
<BR><BR><BR><center>
<table border=#C0C0C0 border=1 cellpadding=3 cellspacing=0 bordercolor=#959595>
<form action="admin.php" method=POST name=pswrd>
<TR><TD bgcolor=#C0C0C0 align=center>Администрирование форума</TD></TR>
<TR><TD align=right>Введите логин: <input size=17 name=name value=""></TD></TR>
<TR><TD align=right>Введите пароль: <input type=password size=17 name=pass></TD></TR>
<TR><TD align=right>';

if ($antispam==TRUE) nospam(); // АНТИСПАМ !

print"<TR><TD align=center><input type=submit style='WIDTH: 120px; height:20px;' value='Войти'>
<SCRIPT language=JavaScript>document.pswrd.name.focus();</SCRIPT></TD></TR></table>
<BR><BR><center><small>Powered by <a href=\"http://www.wr-script.ru\" title=\"Скрипт форума\" class='copyright'>WR-Forum</a> Professional &copy; 1.9<br></small></center></body></html>";
exit;}

} // АВТОРИЗАЦИЯ ПРОЙДЕНА!

$gbc=$_COOKIE['wrforumm']; $gbc=explode("|", $gbc); $gbname=$gbc[0];$gbpass=$gbc[1];$gbtime=$gbc[2];
if ($gbname==$adminname) $ktotut="1"; else $ktotut="2"; // Кто вошёл: админ или модер?










// РАССЫЛКА сообщений участникам форума
if(isset($_GET['event'])) { if ($_GET['event']=="rassilochka") {
$name=replacer($_POST['name']);
$email=replacer($_POST['email']);
$userdata=replacer($_POST['userdata']); if (strlen($userdata)<5) exit("Вы не выбрали участника форума, которому отправялем сообщение!");
$dt=explode("|", $userdata); $username=$dt[1]; $useremail=$dt[2];
$msg=$_POST['msg'];

// Для Выбора схемы - раскоментируйте её и закоментируйте текущую символами //
$bdcolor="#79BBEF"; $fcolor="#00293E"; // Светлоголубой
//$bdcolor="#FF9A00"; $fcolor="#833C07"; // Оранжевый
//$bdcolor="#FFE51A"; $fcolor="#FF8000"; // Жёлто-оранжевый
//$bdcolor="#00E900"; $fcolor="#005300"; // Светло-зеленый
//$bdcolor="#FB5037"; $fcolor="#620000"; // Красный
//$bdcolor="#800080"; $fcolor="#350035"; // Сиреневенький
//$bdcolor="#007800"; $fcolor="#000000"; // Темно зеленый
//$bdcolor="#D2A500"; $fcolor="#4A3406"; // Золотой
//$bdcolor="#BCC0C0"; $fcolor="#646464"; // Серый
//$bdcolor="#FFA8FF"; $fcolor="#800080"; // Розовый

// ТАБЛИЦА стилей зарыта ЗДЕСЬ !!!
$shapka="<html>
<head>
<META http-equiv=Content-Type content='text/html; charset=windows-1251'>
<style>
BODY,TD {FONT-FAMILY: verdana,arial,helvetica; FONT-SIZE: 13px;}
.pismo {BORDER-BOTTOM:$bdcolor 1px solid;}
.pismo2 {BORDER-LEFT:$bdcolor 1px solid; BORDER-BOTTOM:$bdcolor 1px solid;}
.remtop {font-weight: bold; color: $fcolor; font-size:1.1em; padding:5px; border-top: 1px solid $fcolor; border-bottom: 1px solid $fcolor; background-color: $bdcolor;}
.remdata {font-weight: bold; margin:0; display:inline; font-size:0.9em; color: $fcolor;}
input,textarea {font-family: Verdana; font-size: 12px; text-decoration: none; color: #000000; cursor: default; background-color: #FFFFFF; border-style: solid; border-width: 1px; border-color: $bdcolor;}
</style>
</head>
<BODY leftMargin=0 topMargin=0 rightMargin=0 bottomMargin=0 marginheight=0 marginwidth=0>";

$host=$_SERVER["HTTP_HOST"]; $self=$_SERVER["PHP_SELF"]; $furl="http://$host$self";
$furl=str_replace("admin.php", "", $furl);

// Настройки для отправки писем
$headers=null;
$headers.="From: $name <$email>\n";
$headers.="X-Mailer: PHP/".phpversion()."\n";
$headers.="Content-Type: text/html; charset=windows-1251";

$msg=str_replace("\r\n", "<br>",$msg);
$msg=str_replace("%name", "<B>$username</B>",$msg);
$msg=str_replace("%fname", "<B>$fname</B>",$msg);
$msg=str_replace("%furllogin", "<B><a href='".$furl."tools.php?event=login'>".$furl."tools.php?event=login</a></B>",$msg);
$msg=str_replace("%furl", "<B><a href='$furl'>$furl</a></B>",$msg);

// Собираем всю информацию в теле письма
$allmsg="$shapka
<center>
<table cellpadding=5 cellspacing=0>
<TR><TD colspan=2><div class=remtop align=center>Сообщение c сайта \"<a href='$furl'>$furl</a>\"</div></TD></TR>
<TR><TD class=pismo><P class=remdata>Имя</P></TD><TD class=pismo2><B>$name<B></TD></TR>
<TR><TD class=pismo><P class=remdata>E-mail</P></TD><TD class=pismo2><a href='mailto:$email'>$email</a></td></tr>
<TR><TD class=pismo><P class=remdata>Дата отправки:</P></TD><TD class=pismo2>$date г. в $time</td></tr>
<TR><TD class=pismo><P class=remdata>Сообщение</P></TD><TD class=pismo2>$msg</td></tr>
</table>";

$printmsg="$allmsg 
<center><BR>Cообщение <B><font color=navy>успешно отправлено</font></B><BR><BR>
</body></html>";

$allmsg.="<BR><BR><BR>* Это сообщение отправлено с форума.</body></html>";

// Отправляем письмо майлеру на съедение ;-)
mail("$useremail", "Сообщение с сайта: $fname", $allmsg, $headers);

print "<script language='Javascript'>function reload() {location = \"admin.php?event=massmail\"}; setTimeout('reload()', 3000);</script>$printmsg"; exit;

}}








// АДМИН меняет пароль юзеру
if (isset($_GET['newuserpass'])) {
if (isset($_POST['newpass'])) {$newpass=replacer($_POST['newpass']); $email=replacer($_GET['email']);
$newpass=md5("$newpass"); // Шифруем пароль пользователя в МД5

// Ищем юзера с таким емайлом. Если есть - меняем
$email=strtolower($email); unset($fnomer); unset($ok); $oldpass="";
$lines=file("$datadir/usersdat.php"); $ui=count($lines); $i=$ui;
do {$i--; $rdt=explode("|",$lines[$i]); 
$rdt[3]=strtolower($rdt[3]);
if ($rdt[3]===$email) {$oldpass=$rdt[1]; $fnomer=$i; $name=$rdt[0];}
} while($i > 1);

if (isset($fnomer)) { // обновление строку юзера в БД
$i=$ui; $dt=explode("|", $lines[$fnomer]);
$txtdat=$lines[$fnomer];
$txtdat=str_replace("$name|$oldpass","$name|$newpass",$txtdat);
$fp=fopen("$datadir/usersdat.php","a+");
flock ($fp,LOCK_EX); 
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
for ($i=0;$i<=(sizeof($lines)-1);$i++) {if ($i==$fnomer) fputs($fp,"$txtdat"); else fputs($fp,$lines[$i]);}
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp); }

Header("Location: admin.php?event=userwho"); exit; }}




// Блок удаления ВСЕХ НЕАКТИВИРОВАННЫХ УЧАСТНИКОВ
if(isset($_GET['delalluser'])) { $records="<?die;?>\r\n";
$file=file("$datadir/usersdat.php"); $maxi=count($file)-1; $i=0;
$fp=fopen("$datadir/usersdat.php","w"); // удаляем строки с не активированными записями участников
flock ($fp,LOCK_EX);
do { $i++; $dt=explode("|",$file[$i]); 
if (strlen($dt[13])=="6" and ctype_digit($dt[13])) $records=$records; else $records.=$file[$i]; } while($i<$maxi);
ftruncate ($fp,0);
fputs($fp, $records);
flock ($fp,LOCK_UN);
fclose($fp);
Header("Location: admin.php?event=userwho"); exit; }



// Добавление IP-юзера в БАН
if (isset($_GET['badip'])) {
if (isset($_POST['ip'])) {$ip=$_POST['ip']; $badtext=$_POST['text'];}
if (isset($_GET['ip_get'])) {$ip=$_GET['ip_get']; $badtext="За добавление нежелательных сообщений на форум! ЗА СПАМ!!!";}
if (strlen($ip)<8) exit("Введите IP по формату X.X.X.X, где Х - число от 1 до 255! Сейчас запрос пуст или IP НЕ указан!");
$text="$ip|$badtext|"; $text=stripslashes($text); $text=htmlspecialchars($text); $text=str_replace("\r\n", "<br>", $text);
$fp=fopen("$datadir/bad_ip.dat","a+");
flock ($fp,LOCK_EX);
fputs($fp,"$text\r\n");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
Header("Location: admin.php?event=blockip"); exit; }



// Удаления юзера из БАНА
if (isset($_GET['delip'])) { $xd=$_GET['delip'];
$file=file("$datadir/bad_ip.dat"); $dt=explode("|",$file[$xd]); 
$fp=fopen("$datadir/bad_ip.dat","w");
flock ($fp,LOCK_EX);
for ($i=0;$i< sizeof($file);$i++) { if ($i==$xd) unset($file[$i]); }
fputs($fp, implode("",$file));
flock ($fp,LOCK_UN);
fclose($fp);
Header("Location: admin.php?event=blockip"); exit; }



// АКТИВАЦИЯ пользователя
if(isset($_GET['event'])) { if ($_GET['event']=="activate") {

$key=$_GET['key']; $email=$_GET['email']; $page=$_GET['page'];

// защиты от взлома по ключу и емайлу
if (strlen($key)<6 or strlen($key)>6 or !ctype_digit($key)) exit("$back. Вы ошиблись при вводе ключа. Ключ может содержать только 6 цифр.");
$email=stripslashes($email); $email=htmlspecialchars($email);
$email=str_replace("|","I",$email); $email=str_replace("\r\n","<br>",$email);
if (strlen($key)>30) exit("Ошибка при вводе емайла");

// Ищем юзера с таким емайлом и ключом. Если есть - меняем статус на пустое поле
$email=strtolower($email); unset($fnomer); unset($ok);
$lines=file("$datadir/usersdat.php"); $ui=count($lines); $i=$ui;
do {$i--; $rdt=explode("|",$lines[$i]); 
$rdt[3]=strtolower($rdt[3]);
if ($rdt[3]===$email and $rdt[13]===$key) {$name=$rdt[0]; $pass=$rdt[1]; $fnomer=$i;}
if ($rdt[3]===$email and $rdt[13]==="") $ok="1";
} while($i > 1);
if (isset($fnomer)) {
// обновление строки юзера в БД
$i=$ui; $dt=explode("|", $lines[$fnomer]);
$txtdat="$dt[0]|$dt[1]|$dt[2]|$dt[3]|$dt[4]|$dt[5]|$dt[6]|$dt[7]|$dt[8]|$dt[9]|$dt[10]|$dt[11]|$dt[12]||";
$fp=fopen("$datadir/usersdat.php","a+");
flock ($fp,LOCK_EX); 
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
for ($i=0;$i<=(sizeof($lines)-1);$i++) {if ($i==$fnomer) fputs($fp,"$txtdat\r\n"); else fputs($fp,$lines[$i]);}
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
}
if (!isset($fnomer) and !isset($ok)) exit("$back. Вы ошиблись в воде активационного ключа или емайла.</center>");
if (isset($ok)) $add="Запись активирована ранее"; else $add="$name, Пользователь успешно зарегистрирован.";

print"<html><head><link rel='stylesheet' href='$fskin/style.css' type='text/css'></head><body>
<script language='Javascript'>function reload() {location = \"admin.php?event=userwho&page=$page\"}; setTimeout('reload()', 2500);</script>
<table width=100% height=80%><tr><td><table border=1 cellpadding=10 cellspacing=0 bordercolor=#224488 align=center valign=center width=60%><tr><td><center>
Спасибо, <B>$add</B>.<BR><BR>Через несколько секунд Вы будете автоматически перемещены на страницу с участниками форума.<BR><BR>
<B><a href='admin.php?event=userwho&page=$page'>ДАЛЬШЕ >>></a></B></td></tr></table></td></tr></table></center></body></html>";
exit;

}
}






// Блок ПЕРЕСЧЁТА кол-ва тем и сообщений
if(isset($_GET['event'])) { if ($_GET['event'] =="revolushion") {
$lines=file("$datadir/mainforum.dat"); $countmf=count($lines)-1; $i="-1";$u=$countmf-1;$k="0";

do {$i++; $dt=explode("|", $lines[$i]);
if (!isset($dt[12])) {$dt[12]=""; $dt[11]="";}

if ($dt[1]!="razdel") { $fid=$dt[0];
if ((is_file("$datadir/topic$fid.dat")) && (sizeof("$datadir/topic$fid.dat")>0)) {
$fl=file("$datadir/topic$fid.dat"); $kolvotem=count($fl); $kolvomsg="0";
for ($itf=0; $itf<$kolvotem; $itf++) {
$forumdt=explode("|",$fl[$itf]);
$id=$forumdt[7]; $tema=$forumdt[3];
 if ((!ctype_digit($id)) or (strlen($id)!=7)) print"- В теме с названием '<B>$tema</B>': <a href='index.php?id=$fid'>index.php?id=<B>$fid</B></a> ' есть ошибка: <font color=red>Потерян идентификатор, то есть потеряна тема</font><br>";
 else { 
  if (is_file("$datadir/$id.dat")) {
  $msgfile=file("$datadir/$id.dat"); $countmsg=count($msgfile); $kolvomsg=$kolvomsg+$countmsg;
  } else print"- Проблема с темой с названием '<B>$tema</B>': <a href='index.php?id=$id'>index.php?id=<B>$id</B></a> - <font color=red>отсутствует файл с темой (видимо была удалена некорректно)!</font><br>";
 }
} // for

if ($kolvotem=="0") $dt[8]="";
$lines[$i]="$dt[0]|$dt[1]|$dt[2]|$dt[3]|$kolvotem|$kolvomsg|$dt[6]|$dt[7]|$dt[8]|$dt[9]|$dt[10]|$dt[11]|$dt[12]|\r\n";
}

else {$kolvotem="0"; $kolvomsg="0"; $lines[$i]="$dt[0]|$dt[1]|$dt[2]|$dt[3]|$kolvotem|$kolvomsg|$dt[6]|$dt[7]|$dt[8]||$dt[10]|$dt[11]|$dt[12]|\r\n";}
}
else $lines[$i]="$dt[0]|$dt[1]|$dt[2]|\r\n";

} while($i < $countmf);

// сохраняем обновлённые данные о кол-ве тем и сообщений в файле
$file=file("$datadir/mainforum.dat");
$fp=fopen("$datadir/mainforum.dat","w");
flock ($fp,LOCK_EX); 
for ($i=0;$i< sizeof($file);$i++) fputs($fp,$lines[$i]);
flock ($fp,LOCK_UN);
fclose($fp);

print "<center><BR><BR><BR>Всё успешно пересчитано.</center><script language='Javascript'><!--
function reload() {location = \"admin.php\"}; setTimeout('reload()', 3000);
--></script>";
exit; }}







// Блок удаления УЧАСТНИКА ФОРУМА
if (isset($_GET['usersdelete'])) { $usersdelete=$_GET['usersdelete'];

$first=$_POST['first']; $last=$_POST['last']; $page=$_GET['page']; $delnum=null; $i=0;

// Сравнимаем кол-во строк в файле ЮЗЕРОВ и их СТАТИСТИКУ
if (count(file("$datadir/usersdat.php")) != count(file("$datadir/userstat.dat"))) exit("Статистика участников повреждена! Запустите блок: '<a href='admin.php?newstatistik'>Пересчитать статистику участников</a>',<br> а затем уже можно будет удалять участников!");

do {$dd="del$first"; if (isset($_POST["$dd"])) { $delnum[$i]=$first; $i++;} $first++; } while ($first<=$last);
$itogodel=count($delnum); $newi=0; 
if ($delnum=="") exit("Сделайте выбор хотябы одного участника!");
$file=file("$datadir/usersdat.php"); $itogo=sizeof($file); $lines=null; $delyes="0";
for ($i=0; $i<$itogo; $i++) { // цикл по файлу с данными
for ($p=0; $p<$itogodel; $p++) {if ($i==$delnum[$p]) $delyes=1;} // цикл по строкам для удаления
// если нет метки на удаление записи - формируем новую строку массива, иначе - нет
if ($delyes!=1) {$lines[$newi]=$file[$i]; $newi++;} else $delyes="0"; }

// пишем новый массив в файл
$newitogo=count($lines); 
$fp=fopen("$datadir/usersdat.php","w");
flock ($fp,LOCK_EX);
// если всех юзеров удаляем, тогда ничего туда ВПУТИТЬ :-))
if (isset($lines[0])) { for ($i=0; $i<$newitogo; $i++) fputs($fp,$lines[$i]); } else fputs($fp,"");
flock ($fp,LOCK_UN);
fclose($fp);

// Удаляем инфу о юзере из блока статистики - ДОРАБОТАТЬ блок!!!!
// сейчас делаю просто удалить ту запись, которая соответствует номеру
// но в идеале нужно проверять всю статистику и собирать файл
// заново - чтобы исключить любые ошибки

$file=file("$datadir/userstat.dat"); $itogo=sizeof($file); $lines=null; $delyes="0"; $newi=0;
for ($i=0; $i<$itogo; $i++) { // цикл по файлу с данными
for ($p=0; $p<$itogodel; $p++) {if ($i==$delnum[$p]) $delyes=1;} // цикл по строкам для удаления
// если нет метки на удаление записи - формируем новую строку массива, иначе - нет
if ($delyes!=1) {$lines[$newi]=$file[$i]; $newi++;} else $delyes="0"; }

// пишем новый массив в файл
$newitogo=count($lines); 
$fp=fopen("$datadir/userstat.dat","w");
flock ($fp,LOCK_EX);
// если статистику всех юзеров удаляем, тогда ничего туда ВПУТИТЬ :-))
if (isset($lines[0])) {for ($i=0; $i<$newitogo; $i++) fputs($fp,$lines[$i]);} else fputs($fp,"");
flock ($fp,LOCK_UN);
fclose($fp);

Header("Location: admin.php?event=userwho&page=$page"); exit; } 







// Блок ПЕРЕСЧЁТА СТАТИСТИКИ участников

if(isset($_GET['newstatistik'])) {


$lines=null; $ok=null;
// 1. Открываем и считываем в память файл с юзерами
$ulines=file("$datadir/usersdat.php"); $ui=count($ulines);

// 2. Открываем файл статистики
$slines=file("$datadir/userstat.dat"); $si=count($slines)-1;

// Цикл по кол-ву юзеров в базе
for ($i=1;$i<$ui;$i++) {
$udt=explode("|", $ulines[$i]);
if ($i<=$si) $sdt=explode("|",$slines[$i]); else $sdt[0]="";

if ($udt[0]==$sdt[0]) {$udt[0]=str_replace("\r\n","",$udt[0]); $ok=1; if (isset($sdt[1]) and isset($sdt[2]) and isset($sdt[3]) and isset($sdt[4])) {$lines[$i]="$slines[$i]";} else {$lines[$i]="$udt[0]|0|0|0|0|||||\r\n";}} // если имя=имя - значит данные верны

// Цикл в файле статистики - поиск строку текущего юзера
if ($ok!="1") {

for ($j=1;$j<$si;$j++) {
$sdt=explode("|", $slines[$j]);
if ($udt[0]==$sdt[0]) {$ok=1; $lines[$i]=$slines[$j]; }// если имя=имя - значит данные верны
}

if ($ok!="1") $lines[$i]="$udt[0]|0|0|0|0|||||\r\n"; // создаём юзера с нулевой статистикой
}
$ok=null; $ii=count($lines);}

$fp=fopen("$datadir/userstat.dat","a+");
flock ($fp,LOCK_EX); 
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
fputs($fp,"ИМЯ_ЮЗЕРА|Тем|Сообщений|Репутация|Предупреждения Х/5|Когда последний раз меняли рейтинг в UNIX формате|||\r\n");
for ($i=1;$i<=$ii;$i++) fputs($fp,"$lines[$i]");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

Header("Location: admin.php?event=userwho"); exit; }







// Блок изменения СТАТУСА участника
if(isset($_GET['newstatus'])) { if ($_GET['newstatus'] !="") { $newstatus=$_GET['newstatus']-1; $status=$_POST['status'];
if (isset($_GET['page'])) $page=$_GET['page']; else $page=1;
if (strlen($status)<3) exit("новый статус участника <B> < 3 символов </B> - это не серьёзно!");
$status=htmlspecialchars($status); $status=stripslashes($status);
$status=str_replace("|"," ",$status); $status=str_replace("\r\n","<br>",$status);
$lines=file("$datadir/usersdat.php"); $i=count($lines);
$dt=explode("|", $lines[$newstatus]);
$record="$dt[0]|$dt[1]|$dt[2]|$dt[3]|$dt[4]|$dt[5]|$dt[6]|$dt[7]|$dt[8]|$dt[9]|$dt[10]|$dt[11]|$dt[12]|$status|";
$fp=fopen("$datadir/usersdat.php","a+");
flock ($fp,LOCK_EX); 
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
for ($i=0;$i<=(sizeof($lines)-1);$i++) { if ($i==$newstatus) fputs($fp,"$record\r\n"); else fputs($fp,$lines[$i]); }
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
Header("Location: admin.php?event=userwho&page=$page"); exit; } }




// Блок изменения РЕЙТИНГА участника
if(isset($_GET['newreiting'])) { if ($_GET['newreiting'] !="") { $newreiting=$_GET['newreiting']-1; $reiting=$_POST['reiting'];
if (isset($_GET['page'])) $page=$_GET['page']; else $page=1;
$reiting=htmlspecialchars($reiting); $reiting=stripslashes($reiting);
$reiting=str_replace("|"," ",$reiting); $reiting=str_replace("\r\n","<br>",$reiting);
$lines=file("$datadir/usersdat.php"); $i=count($lines);
$dt=explode("|", $lines[$newreiting]);
$txtdat="$dt[0]|$dt[1]|$reiting|$dt[3]|$dt[4]|$dt[5]|$dt[6]|$dt[7]|$dt[8]|$dt[9]|$dt[10]|$dt[11]|$dt[12]|$dt[13]|";

$fp=fopen("$datadir/usersdat.php","a+");
flock ($fp,LOCK_EX); 
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
for ($i=0;$i<=(sizeof($lines)-1);$i++) { if ($i==$newreiting) fputs($fp,"$txtdat\r\n"); else fputs($fp,$lines[$i]); }
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
Header("Location: admin.php?event=userwho&page=$page"); exit; } }




// изменяем РЕПУТАЦИЮ юзера
if(isset($_GET['newrepa'])) {
if (isset($_GET['page'])) $page=$_GET['page']; else $page=1;
$text=$_POST['repa']; $usernum=$_POST['usernum']-1;
$text=htmlspecialchars($text); $text=stripslashes($text);
$text=str_replace("|"," ",$text); $repa=str_replace("\r\n","<br>",$text);

$lines=file("$datadir/userstat.dat");
$dt=explode("|", $lines[$usernum]);
$txtdat="$dt[0]|$dt[1]|$dt[2]|$repa|$dt[4]|$dt[5]|$dt[6]|$dt[7]|||";
$fp=fopen("$datadir/userstat.dat","a+");
flock ($fp,LOCK_EX); 
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
for ($i=0;$i<=(sizeof($lines)-1);$i++) { if ($i==$usernum) fputs($fp,"$txtdat\r\n"); else fputs($fp,$lines[$i]); }
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
Header("Location: admin.php?event=userwho&page=$page"); exit; }





// Блок удаления файла, прикреплённого в сообщении

if(isset($_GET['deletefoto'])) { $deletefoto=replacer($_GET['deletefoto']);
$fid=replacer($_GET['fid']); $id=replacer($_GET['id']);
if (is_file("$filedir/$deletefoto")) unlink ("$filedir/$deletefoto"); // удаляем файл 
Header("Location: admin.php?fid=$fid&id=$id"); exit;}





// Добавляем/снимаем ШТРАФЫ ЮЗЕРУ
if(isset($_GET['userstatus'])) {
if (isset($_GET['page'])) $page=$_GET['page']; else $page=1;
$text=$_POST['submit']; $status=$_POST['status']; $usernum=$_POST['usernum']-1;
$text=htmlspecialchars($text); $text=stripslashes($text);
$text=str_replace("|"," ",$text); $submit=str_replace("\r\n","<br>",$text);
if (!ctype_digit($status)) $status=0;
$status=$status+$submit; // корректируем статус (+1 или -1)
// 0 <= СТАТУС <= 5 (БОЛЬШЕ ЛИБО РАВЕН НУЛЮ, НО МЕНЬШЕ ЛИБО РАВЕН ПЯТИ)
if($status<0 or $status>5) exit("$back статус пользователя БОЛЬШЕ ЛИБО РАВЕН НУЛЮ, НО МЕНЬШЕ ЛИБО РАВЕН ПЯТИ!");
$lines=file("$datadir/userstat.dat");
if (!isset($lines[$usernum])) exit("ошибка! Нет такого пользователя в файле статистики!"); // если нет такой строка в файле статистики
$dt=explode("|", $lines[$usernum]); 
// В версии 1.8.2 ещё было 5 полей в строке файла userstat.dat. 
// Защищаемся от ошибки - вводим пустые поля
if (!isset($dt[6])) $dt[6]="";
if (!isset($dt[7])) $dt[7]="";
$dt[6]=str_replace("\r\n","",$dt[6]); $dt[7]=str_replace("\r\n","",$dt[7]);
$txtdat="$dt[0]|$dt[1]|$dt[2]|$dt[3]|$status|$dt[5]|$dt[6]|$dt[7]|||";
$fp=fopen("$datadir/userstat.dat","a+");
flock ($fp,LOCK_EX); 
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
for ($i=0;$i<=(sizeof($lines)-1);$i++) { if ($i==$usernum) fputs($fp,"$txtdat\r\n"); else fputs($fp,$lines[$i]); }
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
Header("Location: admin.php?event=userwho&page=$page"); exit; }









// ДОБАВЛЕНИЕ НОВОГО ГОЛОСОВАНИЯ
if (isset($_GET['event'])) { if ($_GET['event']=="voteadd") { 

$id=replacer($_GET['id']); $toper=replacer($_POST['toper']); // получаем данные из формы
$i=1; $itgo=0; $text="$toper||\r\n";

do {
 $otv=replacer($_POST["otv$i"]); $otv=str_replace("|","I",$otv); $otv=str_replace("\r\n","<br>",$otv);
 $kolvo=replacer($_POST["kolvo$i"]); $kolvo=str_replace("|","I",$kolvo); $kolvo=str_replace("\r\n","<br>",$kolvo);
 if (strlen($otv)>0) {$itgo++; $text.="$otv|$kolvo|\r\n";}
 $i++;
} while ($i<10);

if ($itgo<1) exit("Должен быть хотябы ОДИН вариант ответа!");

// создаём файл с голосованием
$fp=fopen("$datadir/$id-vote.dat","w");
flock ($fp,LOCK_EX);
fputs($fp,"$text");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
@chmod("$datadir/$id-vote.dat", 0644);

// создаём файл для записи IPшников голосовавших
$fp=fopen("$datadir/$id-ip.dat","w");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
@chmod("$datadir/$id-ip.dat", 0644);

Header("Location: admin.php?id=$id"); exit; }} // КОНЕЦ добавления нового голосования










// Блок Добавления/Редактирования/Удаления ГОЛОСОВАНИЯ

// в процессе написания - дописать!!!!
if(isset($_GET['vote'])) { $vote=$_GET['vote'];
$fid=$_GET['fid']; $id=$_GET['id'];
if ($vote=="delete") { // Выбрано - УДАЛЕНИЕ
if (is_file("$datadir/$id-vote.dat")) {unlink ("$datadir/$id-vote.dat"); unlink ("$datadir/$id-ip.dat");}} // удаляем файлы с голосованием

if ($vote=="change") { } // Выбрано - РЕДАКТИРОВАНИЕ

if ($vote=="add") {
if (is_file("$datadir/$id-vote.dat")) exit("$back. Голосование уже добавлено в теме. Более одного голосования добавлять нельзя!");

} // Выбрано - ДОБАВЛЕНИЕ

if ($vote=="addsave") { } // Сохранение после блока добавления или редактирования
Header("Location: admin.php?fid=$fid&id=$id"); exit;}












// Блок ПЕРЕМЕЩЕНИЯ ВВЕРХ/ВНИЗ РАЗДЕЛА или ТОПИКА
if(isset($_GET['movetopic'])) { if ($_GET['movetopic'] !="") {
$move1=$_GET['movetopic']; $where=$_GET['where']; 
if ($where=="0") $where="-1";
$move2=$move1-$where;
$file=file("$datadir/mainforum.dat"); $imax=sizeof($file);
if (($move2>=$imax) or ($move2<"0")) exit(" НИЗЯ туда двигать!");
$data1=$file[$move1]; $data2=$file[$move2];

$fp=fopen("$datadir/mainforum.dat","a+");
flock ($fp,LOCK_EX); 
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА 
// меняем местами два соседних раздела
for ($i=0; $i<$imax; $i++) {if ($move1==$i) fputs($fp,$data2); else {if ($move2==$i) fputs($fp,$data1); else fputs($fp,$file[$i]);}}
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
Header("Location: admin.php"); exit; }}




// Блок УДАЛЕНИЯ выбранного РАЗДЕЛА или ФОРУМА +++
if(isset($_GET['fxd'])) {
$id=replacer($_GET['fxd']); if ($id=="" or strlen($id)>3) exit("Ошибка, выбирите рубрику для удаления, либо ошибка скрипта!");

// считываем все файлы в папке data попорядку, удалем те, которые начинаются на $id,
// (файлы с темами, голосованием -vote, IP-шниками голосования -ip, topic$id - в темами)
if ($handle=opendir($datadir)) {
while (($file = readdir($handle)) !== false)
if (!is_dir($file)) { 
$tema=substr($file,0,3);
if($tema==$id) unlink ("$datadir/$file");
if($file=="topic$id.dat") unlink ("$datadir/topic$id.dat");
} closedir($handle); } else echo'Ошибка!';

// удаляем строку, соответствующую теме в файле со всеми темами
$file=file("$datadir/mainforum.dat");
$fp=fopen("$datadir/mainforum.dat","w");
flock ($fp,LOCK_EX);
for ($i=0;$i< sizeof($file);$i++) {$dt=explode("|",$file[$i]); if ($dt[0]==$id) unset($file[$i]);}
fputs($fp, implode("",$file));
flock ($fp,LOCK_UN);
fclose($fp);
Header("Location: admin.php"); exit; }





// Блок УДАЛЕНИЯ выбранного ПОСЛЕДНЕГО СООБЩЕНИЯ +++
if(isset($_GET['lxd'])) {
$id=replacer($_GET['lxd']); if ($id=="" or strlen($id)!=7) exit("Ошибка, выбирете сообщение для удаления, либо ошибка скрипта!");
// считываем файл news.dat и удаляем строку, соответствующую сообщению в файле
$file=file("$datadir/news.dat");
$fp=fopen("$datadir/news.dat","w");
flock ($fp,LOCK_EX);
for ($i=0;$i< sizeof($file);$i++) {$dt=explode("|",$file[$i]); if ($dt[1]==$id) unset($file[$i]);}
fputs($fp, implode("",$file));
flock ($fp,LOCK_UN);
fclose($fp);
Header("Location: admin.php"); exit; }






// БЛОК ПЕРЕНУМЕРАЦИИ ТЕМЫ +--
if (isset($_GET['rename'])) { if ($_GET['rename'] !="") {

$fid=$_GET['id']; $id_old=$_GET['rename']; $page=$_GET['page'];

// ID темы хранится в самой теме, в рубрике, в 10-ке последних, на главной в последней теме
// везде нужно исправить!!! везде!
// 1. Считываем рубрикатор, генерируем новый ID темы

// БЛОК ГЕНЕРИРУЕТ СЛЕДУЮЩИЙ ПО ПОРЯДКУ НОМЕР ТЕМЫ, начиная просмотр с 1000
// считываем весь файл с темами в память
$id=1000; $id="$fid$id";
$allid=null; $records=file("$datadir/topic$fid.dat"); $imax=count($records); $i=$imax;
if ($i > 0) { do {$i--; $rd=explode("|",$records[$i]); $allid[$i]=$rd[7]; } while($i>0);
//natcasesort($allid); // сортируем по возрастанию
do $id++; while(in_array($id,$allid) or is_file("$datadir/$id.dat"));
} else $id=$fid."1000";

// Считываем содержимое РУБРИКИ и делаем замену |ID старый| на новый по всему файлу
$rec=file_get_contents("$datadir/topic$fid.dat");
$rec=str_replace("|$id_old|","|$id|",$rec); // Делаем замену |ID старый| на новый по всему файлу
$fp=fopen("$datadir/topic$fid.dat","w+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
fputs($fp,"$rec");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

rename("$datadir/$id_old.dat", "$datadir/$id.dat"); // Переименовываем файл
$rec=file_get_contents("$datadir/$id.dat"); // Считываем содержимое
$rec=str_replace("|$id_old|","|$id|",$rec); // Делаем замену |ID старый| на новый по всему файлу

$fp=fopen("$datadir/$id.dat","w+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
fputs($fp,"$rec");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

// 4. Считываем содержимое ГЛАВНОЙ и делаем замену |ID старый| на новый по всему файлу
$rec=file_get_contents("$datadir/mainforum.dat"); // Считываем содержимое
$rec=str_replace("|$id_old|","|$id|",$rec); // Заменяем |ID старый| на новый по всему файлу
$fp=fopen("$datadir/mainforum.dat","w+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
fputs($fp,"$rec");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

// 5. Считываем содержимое ПОСЛЕДНИХ 20 тем и делаем замену |ID старый| на новый по всему файлу
$rec=file_get_contents("$datadir/news.dat"); // Считываем содержимое
$rec=str_replace("|$id_old|","|$id|",$rec); // Заменяем |ID старый| на новый по всему файлу
$fp=fopen("$datadir/news.dat","w+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
fputs($fp,"$rec");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

// короче в цикле сделать! В начале цикл от 1 до 4-х, потом из массива имён файлов выбирать
// нужный

Header("Location: admin.php?id=$fid&page=$page"); exit; } }









// Блок удаления выбранной ТЕМЫ
if (isset($_GET['xd'])) { if ($_GET['xd'] !="") {
if (isset($_GET['page'])) $page=$_GET['page']; else $page="0";
$xd=$_GET['xd']; $id=$_GET['id']; $fid=substr($id,0,3);
$file=file("$datadir/topic$fid.dat");

$minmsg=1; $delf=null; if (isset($file[$xd])) {
$dt=explode("|", $file[$xd]);
$delf = str_replace("\r\n", "", $dt[7]);
$mlines=file("$datadir/$delf.dat"); $minmsg=count($mlines);
unlink ("$datadir/$delf.dat");} // удаляем файл с темой
if (is_file("$datadir/$delf-vote.dat")) unlink("$datadir/$delf-vote.dat"); // удаляем файл с ГОЛОСОВАНИЕМ
if (is_file("$datadir/$delf-ip.dat")) unlink("$datadir/$delf-ip.dat"); // удаляем файл с голосовавшими IP

// удаляем строку, соответствующую теме в файле с текущими темами
$fp=fopen("$datadir/topic$fid.dat","w");
$kolvotem=sizeof($file)-1; // кол-во тем для уточнения на главной
flock ($fp,LOCK_EX); 
for ($i=0;$i< sizeof($file);$i++) {if ($i==$xd) unset($file[$i]);}
fputs($fp, implode("",$file));
flock ($fp,LOCK_UN);
fclose($fp);


// Блок вычитает 1-цу из кол-ва тем и вычитает кол-во сообщений
$lines=file("$datadir/mainforum.dat"); $i=count($lines);
// находим по fid номер строки
for ($ii=0;$ii< sizeof($lines);$ii++) {$kdt=explode("|",$lines[$ii]); 
if ($kdt[0]==$fid) $mnumer=$ii;}
$dt=explode("|",$lines[$mnumer]);
$dt[5]=$dt[5]-$minmsg;
if ($kolvotem=="0") $dt[5]="0";
if ($dt[5]<0) $dt[5]="0";
if ($dt[4]<0) $dt[4]="0";
// если удаляемая тема стоит на главной как последняя, то удаляем её с главной
if ($dt[3]==$delf or $dt[5]==0) {$dt[6]="";$dt[7]="";$dt[8]="";$dt[9]="";$dt[10]="";}
$text="$dt[0]|$dt[1]|$dt[2]|$dt[3]|$kolvotem|$dt[5]|$dt[6]|$dt[7]|$dt[8]|$dt[9]|$dt[10]|$dt[11]|$dt[12]||";
$file=file("$datadir/mainforum.dat");
$fp=fopen("$datadir/mainforum.dat","a+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);
for ($ii=0;$ii< sizeof($file);$ii++) { if ($mnumer!=$ii) fputs($fp,$file[$ii]); else fputs($fp,"$text\r\n"); }
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);


// удаляем объявление из 10-КИ последних
$file=file("$datadir/news.dat");
$fp=fopen("$datadir/news.dat","w");
flock ($fp,LOCK_EX);
for ($i=0; $i< sizeof($file); $i++) { $dt=explode("|",$file[$i]); if ($dt[1]==$id) unset($file[$i]); }
fputs($fp, implode("",$file));
flock ($fp,LOCK_UN);
fclose($fp);


Header("Location: admin.php?id=$fid&page=$page"); exit; } }





// Блок УДАЛЕНИЯ выбранного СООБЩЕНИЯ
if (isset($_GET['topicxd'])) { if ($_GET['topicxd'] !="") {
$id=$_GET['id']; $fid=substr($id,0,3); $topicxd=$_GET['topicxd']-1;
if (isset($_GET['page'])) $page=$_GET['page']; else $page="1";
$file=file("$datadir/$id.dat");
if (count($file)==1) exit("В ТЕМЕ должно остаться хотябы <B>одно сообщение!</B>");
$fp=fopen("$datadir/$id.dat","w");
flock ($fp,LOCK_EX);
for ($i=0;$i< sizeof($file);$i++) { if ($i==$topicxd) unset($file[$i]); }
fputs($fp, implode("",$file));
flock ($fp,LOCK_UN);
fclose($fp);
$topicxd--;

$file=file("$datadir/$id.dat");
//переписываем автора последнего сообщения в теме
$dt=explode("|",$file[count($file)-1]); $avtor=$dt[0]; $data=$dt[5]; $time=$dt[6];


// Блок вычитает 1-цу из кол-ва сообщений на главной
$lines = file("$datadir/mainforum.dat"); $i=count($lines);
// находим по fid номер строки
for ($ii=0;$ii< sizeof($lines);$ii++) { $kdt=explode("|",$lines[$ii]); if ($kdt[0]==$fid) $mnumer=$ii; }
$dt=explode("|",$lines[$mnumer]);
$dt[5]--; if ($dt[5]<0) $dt[5]="0";
$text="$dt[0]|$dt[1]|$dt[2]|$dt[3]|$dt[4]|$dt[5]|$avtor|$data|$time|$dt[9]|$dt[10]|$dt[11]||$dt[12]||";
$file=file("$datadir/mainforum.dat");
$fp=fopen("$datadir/mainforum.dat","a+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);
for ($ii=0;$ii< sizeof($file);$ii++) { if ($mnumer!=$ii) fputs($fp,$file[$ii]); else fputs($fp,"$text\r\n"); }
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
Header("Location: admin.php?id=$id&page=$page#m$topicxd"); exit; } }





// Добавление ФОРУМА / РАЗДЕЛА +++
if(isset($_GET['event'])) { if ($_GET['event'] =="addmainforum") {
$ftype=$_POST['ftype']; $zag=$_POST['zag']; $msg=$_POST['msg']; $id="101";
if ($zag=="") exit("$back <B>и введите заголовок!</B>");

// пробегаем по файлу с номерами разделов/топиков - ищем наибольшее и добавляем +1
if (is_file("$datadir/mainforum.dat")) { $lines=file("$datadir/mainforum.dat"); 
$imax=count($lines); $i=0;
do {$dt=explode("|", $lines[$i]); if ($id<$dt[0]) {$id=$dt[0];} $i++; } while($i<$imax);
$id++; }
if ($id<101) $id=101; if ($id>999) exit("Номер не может быть более 999");
$zag=str_replace("|","I",$zag); $msg=str_replace("|","I",$msg);
if ($ftype=="") $record="$id|$zag|$msg||0|0||$date|$time||||||"; else $record="$id|$ftype|$zag|";
$record=replacer($record);

// создаём пустой файл с рубриками
if ($ftype=="") { $fp=fopen("$datadir/topic$id.dat","a+");
flock ($fp,LOCK_EX); fputs($fp,""); fflush ($fp); flock ($fp,LOCK_UN); fclose($fp); }

// запись данных на главную страницу
$fp=fopen("$datadir/mainforum.dat","a+");
flock ($fp,LOCK_EX); fputs($fp,"$record\r\n"); fflush ($fp); flock ($fp,LOCK_UN); fclose($fp);
Header("Location: admin.php"); exit; }






// Блок СОРТИРОВКИ УЧАСТНИКОВ
if(isset($_GET['event'])) { if ($_GET['event'] =="sortusers") { $kaksort=$_POST['kaksort']; $lines="";

// Считываем оба файла в память
$dat="$datadir/usersdat.php"; $dlines=file("$dat"); $di=count($dlines);
$stat="$datadir/userstat.dat"; $slines=file("$stat"); $si=count($slines);

$msguser=1000; // общее кол-во оставленных сообщений - надо считать!!!!

if ($di!=$si) exit("$back - Необходимо Пересчитать статистику участников!!! Файл стистики повреждён!!!");

for ($i=1;$i<$di;$i++) {
$dt=explode("|",$dlines[$i]);
$st=explode("|",$slines[$i]);

if ($dt[0]!=$st[0]) exit("$back необходимо Пересчитать статистику участников!!! Файл стистики повреждён!!!");
/* kaksort
1 - Имени $dt[0]
2 - Кол-ву сообщений $st[2]
3 - Кол-ву звёзд dt[2]
4 - Репутации $st[3]
5 - Дате регистрации $dt[4]
6 - Активности $dt[4]/$st[2] */
// при склеивании на первое место ставим нужный параметр
if ($kaksort==1) {$name=strtolower($dt[0]); $lines[$i]="$name|";}
if ($kaksort==2) {$msg="0".+9999-$st[2]; $lines[$i]="$msg|";}
if ($kaksort==3) {$msg="0".+99-$dt[2]; $lines[$i]="$msg|";}
if ($kaksort==4) {$msg="0".+9000-$st[3]; $lines[$i]="$msg|";}

if ($kaksort>4) {
$akt=explode(".",$dt[4]); $tekdt=time();
$datereg=mktime(0,0,0,$akt[1],$akt[0],$akt[2]);
$aktiv=round(($tekdt-$datereg)/86400);
$aktiv=round(100*$msguser/$aktiv)/100;
if ($kaksort==5) $lines[$i]="$datereg|";
if ($kaksort==6) $lines[$i]="$aktiv|"; }

// Склеиваем два файла в одну переменную
$lines[$i].="$dt[0]|$dt[1]|$dt[2]|$dt[3]|$dt[4]|$dt[5]|$dt[6]|$dt[7]|$dt[8]|$dt[9]|$dt[10]|$dt[11]|$dt[12]|$dt[13]|$st[1]|$st[2]|$st[3]|$st[4]|$st[5]|||\r\n";

} // конец FOR

// сортируем массив
setlocale(LC_ALL,'ru_RU.CP1251'); // ! РАЗРЕШАЕМ РАБОТУ ФУНКЦИЙ, работающих с регистором и с РУССКИМИ БУКВАМИ
function prcmp ($a, $b) {if ($a==$b) return 0; if ($a<$b) return -1; return 1;} // Функция сортировки
usort($lines,"prcmp"); // сортируем дни по возрастанию

// разделяем на два массива и по очереди их сохраняем
$dlines=null; $dlines="<?die;?>\r\n"; $slines=null; $slines="ИМЯ_ЮЗЕРА|Тем|Сообщений|Репутация|Предупреждения Х/5|Когда последний раз меняли рейтинг в UNIX формате|||\r\n";

for ($i=0;$i<$di-1;$i++) {
$nt=explode("|",$lines[$i]);
$dlines.="$nt[1]|$nt[2]|$nt[3]|$nt[4]|$nt[5]|$nt[6]|$nt[7]|$nt[8]|$nt[9]|$nt[10]|$nt[11]|$nt[12]|$nt[13]|$nt[14]|||\r\n";
$slines.="$nt[1]|$nt[15]|$nt[16]|$nt[17]|$nt[18]|$nt[19]|||\r\n";
}

// запись данных
$fp=fopen("$datadir/usersdat.php","a+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);
fputs($fp,"$dlines");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

$fp=fopen("$datadir/userstat.dat","a+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);
fputs($fp,"$slines");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

Header("Location: admin.php?event=userwho"); exit; }}





// Редактирование ФОРУМА / РАЗДЕЛА
if ($_GET['event'] =="frdmainforum") {
$nextnum=$_POST['nextnum'];
$frd=$_POST['frd'];
$ftype=$_POST['ftype'];
$zag=$_POST['zag'];
if ($zag=="") exit("$back <B>и введите заголовок!</B>");
$zag=str_replace("|","I",$zag);

if ($ftype == "") { $addmax=$_POST['addmax']; $zvezdmax=$_POST['zvezdmax'];
$msg=$_POST['msg'];$idtemka=$_POST['idtemka'];$kt=$_POST['kt'];$km=$_POST['km'];$namem=$_POST['namem'];$datem=$_POST['datem'];$timem=$_POST['timem'];$temka=$_POST['temka'];$timetk=$_POST['timetk'];
$msg=str_replace("|","I",$msg); $msg=str_replace("\r\n", "<br>", $msg);
$txtmf="$nextnum|$zag|$msg|$idtemka|$kt|$km|$namem|$datem|$timem|$timetk|$temka|$addmax|$zvezdmax||";}
else $txtmf="$nextnum|$ftype|$zag|";

$txtmf=htmlspecialchars($txtmf); $txtmf=stripslashes($txtmf); $txtmf=str_replace("\r\n","<br>",$txtmf);

$file=file("$datadir/mainforum.dat");
$fp=fopen("$datadir/mainforum.dat","a+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА 
for ($i=0;$i< sizeof($file);$i++) { if ($frd!=$i) fputs($fp,$file[$i]); else fputs($fp,"$txtmf\r\n"); }
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
Header("Location: admin.php"); exit; }




if ($_GET['event']=="rdtema") { // Выбрано редактирование ТЕМЫ
$rd=replacer($_POST['rd']); $id=$rd;// - уникальный номер темы, которую необходимо заменить
$changefid=$_POST['changefid'];
if (isset($_GET['page'])) $page=$_GET['page']; else $page="0";
$oldzag=replacer($_POST['oldzag']); // старое название темы (до переименования)
$name=$_POST['name']; $who=$_POST['who']; $email=$_POST['email'];
$zag=$_POST['zag']; $msg=$_POST['msg']; $datem=$_POST['datem'];
$timem=$_POST['timem']; $fid=substr($rd,0,3);
$timetk=$_POST['timetk']; $status=$_POST['status']; $goto=$_POST['goto'];


if ($goto==1) $goto="admin.php?id=$changefid"; else $goto="admin.php?id=$fid&page=$page";

if ($zag=="") exit("$back <B>и введите ТЕМУ, она пустая!</B>");
$text="$name|$who|$email|$zag|$msg|$datem|$timem|$rd|$status|$timetk|||||";
$text=replacer($text); $text=str_replace("&lt;br&gt;","<br>",$text);

// БЛОК объединения тем
// I. в topic$temaplus.dat нужно удалить строку с этой темой
$temaplus=replacer($_POST['temaplus']); $temakuda=replacer($_POST['temakuda']);
if (strlen($temaplus)>1 and is_file("$datadir/$temaplus.dat")) {
$file=file("$datadir/topic$fid.dat");
$fp=fopen("$datadir/topic$fid.dat","a+");
flock ($fp,LOCK_EX); 
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
for ($i=0;$i<sizeof($file);$i++) { $rdt=explode("|",$file[$i]); if ($rdt[7]!="$temaplus") fputs($fp,$file[$i]); else $starzag=$rdt[3];}
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

// II. соединяем файлы вместе
$record1=file_get_contents("$datadir/$rd.dat"); // Считываем содержимое
$record2=file_get_contents("$datadir/$temaplus.dat"); // Считываем содержимое
if ($temakuda==TRUE) $records="$record2$record1"; else $records="$record1$record2";
$records=str_replace("|$temaplus|","|$rd|",$records);
$records=str_replace("|$starzag|","|$zag|",$records); // Менем название темы во всём файле
$fp=fopen("$datadir/$rd.dat","a+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ
unlink("$datadir/$temaplus.dat"); //удаляем файл
fputs($fp,$records);
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
} // КОНЕЦ БЛОКа объединения тем


if ($changefid==$fid) { // Если рубрика остаётся тамже
$file=file("$datadir/topic$fid.dat");
$fp=fopen("$datadir/topic$fid.dat","a+");
flock ($fp,LOCK_EX); 
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА 
for ($i=0;$i<sizeof($file);$i++) { $kdt=explode("|",$file[$i]);
if ($rd==$kdt[7]) fputs($fp,"$text\r\n"); else fputs($fp,$file[$i]); }
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

} else { // если меняем рубрику теме

// $fid - текущий, а $changefid - это новый фид топика.

// !. Нужно же новое имя файлу и теме придумать, проверяем свободно ли?
$newid=substr($id,3,4); $newid="$changefid$newid";
if (file_exists("$datadir/$newid.dat")) { // генерируем имя файлу с темой - СТАРЫЙ механизм
do $newid=mt_rand(1000,9999); while (file_exists("$datadir/$changefid$newid.dat"));
$newid="$changefid$newid";}
$text=str_replace("|$id|","|$newid|",$text); // меняем имя файлу

// 1. создаём копию темы в новом топике
touch("$datadir/topic$changefid.dat");
$file=file("$datadir/topic$changefid.dat");
$kolvotem1=sizeof($file)+1; // кол-во тем для уточнения на главной
$fp=fopen("$datadir/topic$changefid.dat","a+");
flock ($fp,LOCK_EX);
fputs($fp,"$text\r\n");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

// 2. удаляем тему в текущем топике

touch("$datadir/topic$fid.dat");
$file=file("$datadir/topic$fid.dat");
$fp=fopen("$datadir/topic$fid.dat","w+");
$kolvotem2=sizeof($file)-1; // кол-во тем для уточнения на главной
flock ($fp,LOCK_EX); 
for ($i=0;$i<sizeof($file);$i++) {$kdt=explode("|",$file[$i]); if ($rd==$kdt[7]) unset($file[$i]);}
fputs($fp, implode("",$file));
flock ($fp,LOCK_UN);
fclose($fp);

// 3. запускаем пересчёт по типу как в доске объявлений

// ДОДЕЛАТЬ в следующей версии!!!
// СЛЕДУЮЩИЕ два блока объединить в один. Сделать переход по массиву,
// корректирование и копирование данных в новый массив
// и последующая его запись в файл mainforum.dat

// Блок вычитает 1-цу из кол-ва тем и вычитает кол-во сообщений
$file=file("$datadir/$id.dat"); $minmsg=count($file);
$lines=file("$datadir/mainforum.dat"); $i=count($lines);
// находим по $changefid номер строки

for ($ii=0;$ii< sizeof($lines);$ii++) { $kdt=explode("|",$lines[$ii]); if ($kdt[0]==$fid) $mnumer=$ii; }
$dt=explode("|",$lines[$mnumer]);
$dt[5]=$dt[5]-$minmsg;
if ($kolvotem2=="0") $dt[5]="0";
if ($dt[5]<0) $dt[5]="0";
if ($dt[4]<0) $dt[4]="0";
// если удаляемая тема стоит на главной как последняя, то удаляем её с главной
if ($dt[3]==$id or $dt[5]==0) {$dt[6]="";$dt[7]="";$dt[8]="";$dt[9]="";$dt[10]="";}
$text="$dt[0]|$dt[1]|$dt[2]|$dt[3]|$kolvotem2|$dt[5]|$dt[6]|$dt[7]|$dt[8]|$dt[9]|$dt[10]|$dt[11]|$dt[12]||";
$file=file("$datadir/mainforum.dat");
$fp=fopen("$datadir/mainforum.dat","a+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);
for ($ii=0;$ii< sizeof($file);$ii++)
{ if ($mnumer!=$ii) fputs($fp,$file[$ii]); else fputs($fp,"$text\r\n"); }
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

// Блок прибавляет 1-цу к кол-ву тем и добавляет кол-во сообщений
for ($ii=0;$ii< sizeof($lines);$ii++) { $kdt=explode("|",$lines[$ii]); if ($kdt[0]==$changefid) $mnumer=$ii; }
$dt=explode("|",$lines[$mnumer]);
$dt[5]=$dt[5]+$minmsg;
if ($kolvotem1=="0") $dt[5]="0";
if ($dt[5]<0) $dt[5]="0";
if ($dt[4]<0) $dt[4]="0";
// если удаляемая тема стоит на главной как последняя, то удаляем её с главной
if ($dt[3]==$id or $dt[5]==0) {$dt[6]="";$dt[7]="";$dt[8]="";$dt[9]="";$dt[10]="";}
$text="$dt[0]|$dt[1]|$dt[2]|$dt[3]|$kolvotem1|$dt[5]|$dt[6]|$dt[7]|$dt[8]|$dt[9]|$dt[10]|$dt[11]||$dt[12]||";
$file=file("$datadir/mainforum.dat");
$fp=fopen("$datadir/mainforum.dat","a+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);
for ($ii=0;$ii< sizeof($file);$ii++)
{ if ($mnumer!=$ii) fputs($fp,$file[$ii]); else fputs($fp,"$text\r\n"); }
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

// 4. смотрим news.dat. Если есть удаляем нафиг (удаляем тему из 10-КИ последних)

$file=file("$datadir/news.dat");
$fp=fopen("$datadir/news.dat","w");
flock ($fp,LOCK_EX);
for ($i=0; $i< sizeof($file); $i++) {
$dt=explode("|",$file[$i]); if ($dt[1]==$id) unset($file[$i]); }
fputs($fp, implode("",$file));
flock ($fp,LOCK_UN);
fclose($fp);

// 5. Переименовываем файл $id.dat на $newid.dat и меняем в нём ID на новый!
$records=file_get_contents("$datadir/$id.dat"); // Считываем содержимое
$fp=fopen("$datadir/$id.dat","a+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ
$records=str_replace("|$id|","|$newid|",$records);
//print"$records \r\r\n |$id| \r\r\n |$newid|";
fputs($fp,$records);
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
rename("$datadir/$id.dat", "$datadir/$newid.dat");
Header("Location: $goto"); exit; }


// ЗАМЕНЯЕМ СТАРОЕ НАЗВАНИЕ РУБРИКИ НА НОВОЕ ПО ВСЕМУ ФАЙЛУ
$records=file_get_contents("$datadir/$id.dat"); // Считываем содержимое
$fp=fopen("$datadir/$id.dat","a+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ
$records=str_replace("|$oldzag|","|$zag|",$records);
fputs($fp,$records);
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
// если нужно заVIPить тему - прибавляем её ещё 2 года к сегодняшнему дню
if ($_POST['viptema']==="1") { $viptime=strtotime("+2 year"); touch("$datadir/$id.dat",$viptime);}
Header("Location: $goto"); exit; }


} // if $event==rdtema












// ДОБАВЛЕНИЕ ТЕМЫ или ОТВЕТА - ШАГ 1
if(isset($_GET['event'])) {
if (($_GET['event']=="addtopic") or ($_GET['event']=="addanswer")) {
if (isset($_POST['name'])) $name=$_POST['name'];
$name=trim($name); // Вырезает ПРОБЕЛьные символы 
$zag=$_POST['zag']; $msg=$_POST['msg'];
if (isset($_POST['who'])) $who=$_POST['who']; else $who="";
if (isset($_POST['email'])) $email=$_POST['email']; else $email="";
if (isset($_POST['page'])) $page=$_POST['page'];
if ($_GET['event']=="addanswer") $id=$_GET['id']; $fid=substr($id,0,3);
$in=0; $maxzd=$_POST['maxzd']; if (!ctype_digit($maxzd) or strlen($maxzd)>2) exit("<B>$back. Попытка взлома. Хакерам здесь не место.</B>");

// защита по топику fid
if (!ctype_digit($fid) or strlen($fid)>3) exit("<B>$back. Попытка взлома. Хакерам здесь не место.</B>");

// проходим по всем разделам и топикам - ищем запращиваемый
// на тот случай, если mainforum.dat - пуст, подключаем резервную копию

$realbase="1"; if (is_file("$datadir/mainforum.dat")) $mainlines=file("$datadir/mainforum.dat");
if (!isset($mainlines)) $datasize=0; else $datasize=sizeof($mainlines);
if ($datasize<=0) {if (is_file("$datadir/copy.dat")) {$realbase="0"; $mainlines=file("$datadir/copy.dat"); $datasize=sizeof($mainlines);}}
if ($datasize<=0) exit("$back. Проблемы с Базой данных - обратитесь к администратору");
$i=count($mainlines);

$realfid=null;
do {$i--; $dt=explode("|", $mainlines[$i]);
if ($dt[0]==$fid) {$realfid=$i; if ($dt[1]=="razdel") exit("$back. Попытка взлома.");} // присваиваем $realfid - № п/п строки
} while($i>0);

if (!isset($realfid)) exit("$back. Ошибка с номером рубрики. Она не существует в базе.");

$dt=explode("|",$mainlines[$realfid]);
if (is_file("$datadir/topic$fid.dat")) {$tlines=file("$datadir/topic$fid.dat"); $tc=count($tlines)-2; $i=$tc+2; $ok=null;
// нужно пробежаться по топику, найти тему. Если есть - нормуль, нету - значит добавление сообщений ЗАПРЕЩЕНО!


if ($_GET['event']=="addanswer") {
do {$i--; $tdt=explode("|",$tlines[$i]);
//print"$tdt[7]==$id<br>";
if ($tdt[7]=="$id") {$ok=1; if ($tdt[8]=="closed") exit("$back тема закрыта и добавление сообщений запрещено!"); }
} while($i>0);
if ($ok!=1) exit("$back тема закрыта и добавление сообщений запрещено!"); }

} else $tc="2";
if ($dt[11]>0 and $tc>=$dt[11]) exit("$back. Превышено ограничение на кол-во допустимых тем в данной рубрике! Не более <B>$dt[11]</B> тем!");

// проверка Логина/Пароля юзера. Может он хакер, тогда облом ему

// Этап 1
if (isset($_COOKIE['wrfcookies'])) {
    $wrfc=$_COOKIE['wrfcookies']; $wrfc=htmlspecialchars($wrfc); $wrfc=stripslashes($wrfc);
    $wrfc=explode("|", $wrfc); $wrfname=$wrfc[0]; $wrfpass=$wrfc[1];
} else {unset($wrfname); unset($wrfpass);}

// Этап 2
if ($who=="да") {
if (isset($wrfname) & isset($wrfpass)) {
$lines=file("$datadir/usersdat.php"); $i=count($lines);
do {$i--; $rdt=explode("|", $lines[$i]);
   if (isset($rdt[1])) { $realname=strtolower($rdt[0]);
   if (strtolower($wrfname)===$realname & $wrfpass===$rdt[1]) $ok="$i"; }
} while($i > "1");
if (!isset($ok)) {setcookie("wrfcookies","",time()); exit("Ошибка при работе с КУКИ! <font color=red><B>Вы не сможете оставить сообщение, попробуйте подать его как гость.</B></font> Ваш логин и пароль не найдены в базе данных, попробуйте зайти на форум вновь. Если ошибка повторяется - обратитесь к администратору форума.");}
}}

if (!isset($name) || strlen($name) > $maxname || strlen($name) <1) exit("$back Ваше <B>ИМЯ пустое, или превышает $maxname</B> символов!</B></center>");
if (strlen(ltrim($zag))<3 || strlen($zag) > 200) exit("$back Слишком короткое название темы или <B>название превышает $maxzag</B> символов!</B></center>");
if (strlen(ltrim($msg))<2 || strlen($msg) > 10000) exit("$back Ваше <B>сообщение короткое или превышает $maxmsg</B> символов.</B></center>");
if (!preg_match('/^([0-9a-zA-Z]([-.w]*[0-9a-zA-Z])*@([0-9a-zA-Z][-w]*[0-9a-zA-Z].)+[a-zA-Z]{2,9})$/si',$email) and strlen($email)>30 and $email!="") exit("$back и введите корректный E-mail адрес!</B></center>");

// генерируем имя файлу с темой
if ($_GET['event'] =="addtopic") {$add=null; $z=null; do {$id=mt_rand(1000,9999); if ($fid<10) $add="0"; if (!is_file("$datadir/$add$fid$id.dat") and strlen($id)==4) {$z++;} } while ($z<1); $id="$add$fid$id";}
if ((!ctype_digit($id)) or (strlen($id)>15)) exit("<B>$back. Попытка взлома. $id должно быть числом. Хакерам здесь не место.</B>");
if (strlen(ltrim($zag))<3) exit("$back ! Ошибка в вводе данных заголовка!");

$tektime=time();
$name=wordwrap($name,30,' ',1); // разрываем длинные строки
$zag==wordwrap($zag,30,' ',1);
$msg=wordwrap($msg,110,' ',1);

$name=str_replace("|","I",$name);
$who=str_replace("|","&#124;",$who);
$email=str_replace("|","&#124;",$email);
$zag=str_replace("|","&#124;",$zag);
$msg=str_replace("|","&#124;",$msg);

$smname=$name; if (strlen($name)>18) {$smname=substr($name,0,18); $smname.="..";}
$smzag=$zag; if (strlen($zag)>24) {$smzag=substr($zag,0,24); $smzag.="..";}

$ip=$_SERVER['REMOTE_ADDR']; // определяем IP юзера
$text="$name|$who|$email|$zag|$msg|$date|$time|$id||$tektime|$smname|$smzag|||||$ip||||";
$text=replacer($text);
$exd=explode("|",$text); 
$name=$exd[0]; $zag=$exd[3]; $smname=$exd[10]; $smzag=$exd[11]; $smmsg=$exd[4];


if(isset($_GET['topicrd'])) { // Выбрано редактирование СООБЩЕНИЯ
$topicrd=replacer($_GET['topicrd']); // номер ячейки, которую необходимо заменить
$oldmsg=replacer($_POST['oldmsg']);
$oldmsg=str_replace("\r\n","<br>",$oldmsg);
$oldmsg=str_replace("|","&#124;",$oldmsg);
$oldmsg=str_replace(":kovichka:", "'",$oldmsg); // РАЗшифровываем символ '
$fdate=replacer($_POST['fdate']); $ftime=replacer($_POST['ftime']);
$msg=replacer($msg);
$file=file("$datadir/$id.dat");
$fs=count($file)-1; $i="-1";
$timetek=time(); $timefile=filemtime("$datadir/$id.dat"); 
$timer=$timetek-$timefile; // узнаем сколько прошло времени (в секундах) 
$records=file_get_contents("$datadir/$id.dat");
$records=str_replace("|$oldmsg|$fdate|$ftime|","|$msg|$fdate|$ftime|",$records); // Делаем замену |старое сообщение|ДАТА|ВРЕМЯ| на новое

//print"$oldmsg\r\r\n<br><br><br><br>$msg\r\r\n<br><br><br>$records"; exit; // РАСКОМЕНТИРОВАТЬ ЕСЛИ СООБЩЕНИЯ НЕ РЕДАКТИРУЮТСЯ!

$fp=fopen("$datadir/$id.dat","a+");
flock ($fp,LOCK_EX); 
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА 
fputs($fp,$records);
//do {$i++; if ($i==$topicrd) fputs($fp,"$text\r\n"); else fputs($fp,$file[$i]); } while($i < $fs);
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
if ($timer<0) {$viptime=strtotime("+2 year"); touch("$datadir/$fid$id.dat",$viptime);}
Header("Location: admin.php?id=$id&page=$page"); exit; }


print"<html><head><link rel='stylesheet' href='$fskin/style.css' type='text/css'></head><body>";
// ЕСЛИ введена команда АП, то меняем дату создания файла и тема самая первая будет
if ($_GET['event'] =="addanswer") { // при ОТВЕТе В ТЕМЕ

if (is_file("$datadir/$id.dat")) {$linesn=file("$datadir/$id.dat"); $in=count($linesn)-1;}

// Проверяем, давно ли реактивировали тему
$timetek=time(); $timefile=filemtime("$datadir/$id.dat"); 
$timer=$timetek-$timefile; // узнаем сколько прошло времени (в секундах) 
// $timer<10 - 10 секунд защита от антифлуда
if ($smmsg=="ап!") {
if ($timer<10 and $timer>0) exit("$back тема была активна менее $timer секунд назад.");
touch("$datadir/$id.dat");
print "<script language='Javascript'>function reload() {location = \"admin.php?id=$id&page=$page#m$in\"}; setTimeout('reload()', 1500);</script>
<table width=100% height=80%><tr><td><table border=1 cellpadding=10 cellspacing=0 bordercolor=#224488 align=center valign=center width=60%><tr><td><center>
Спасибо, <B>$name</B>, тема реактивирована.<BR><BR>Через несколько секунд Вы будете автоматически перемещены в текущую тему <BR><B>$zag</B>.<BR><BR>
<B><a href='admin.php?id=$id&page=$page#m$in'>ДАЛЬШЕ >>></a></B></td></tr></table></td></tr></table></center></body></html>";
exit; }

if ($timer<10 and $timer>0) exit("$back тема была активна менее $timer секунд назад.");
}


$razdelname="";
if ($realbase=="1" and $maxzd<1) { // Если подключена рабочая база, а не копия
$lines=file("$datadir/mainforum.dat");
$dt=explode("|", $lines[$realfid]); $dt[5]++;
if ($_GET['event']=="addtopic") $dt[4]++;

// НЕ менять 4-е строки пусть как написано так и будет!
if (!isset($dt[11])) $dt[11]="100"; $dt[11]=str_replace("
", "<br>", $dt[11]);
if (!isset($dt[12])) $dt[12]=""; $dt[12]=str_replace("
", "<br>", $dt[12]);
$txtdat="$dt[0]|$dt[1]|$dt[2]|$id|$dt[4]|$dt[5]|$smname|$date|$time|$tektime|$smzag|$dt[11]|$dt[12]||";
$razdelname=$dt[1];
// запись данных на главную страницу
$fp=fopen("$datadir/mainforum.dat","a+");
flock ($fp,LOCK_EX);
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА
for ($i=0;$i<=(sizeof($lines)-1);$i++) { if ($i==$realfid) fputs($fp,"$txtdat\r\n"); else fputs($fp,$lines[$i]); }
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
} // if ($realbase==1)

if ($newmess=="1" and $maxzd<1) { // запись в отдельный файл нового сообщения
if (is_file("$datadir/topic$fid.dat")) $nlines=count(file("$datadir/topic$fid.dat")); else $nlines=1;

if (is_file("$datadir/$id.dat")) $nlines2=count(file("$datadir/$id.dat"))+1; else $nlines2=1;

$newmessfile="$datadir/news.dat";
$newlines=file("$newmessfile"); $ni=count($newlines)-1; $i2=0; $newlineexit="";

$nmsg=substr($msg,0,150); // образаем сообщение до 150 символов
$ntext="$fid|$id|$date|$time|$smname|$zag|$nmsg...|$nlines|$nlines2|$razdelname|$who||||";
$ntext=str_replace("
", "<br>", $ntext);

// Блок проверяет, есть ли уже новое сообщение в этой теме. Если есть - отсеивает. На выходе - массив без этой строки.
for ($i=0;$i<=$ni;$i++)
{ $ndt=explode("|",$newlines[$i]);
if (isset($ndt[1])) {if ($id!=$ndt[1]) {$newlineexit.="$newlines[$i]"; $i2++;}}
}
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
fclose($fp); }

$file=file($newmessfile);$i=count($file);
if ($i>="15") {
$fp=fopen($newmessfile,"w");
flock ($fp,LOCK_EX);
unset($file[0]);
fputs($fp, implode("",$file));
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
}
}
}
} // if ($newmess==1)


// БЛОК добавляет +1 к репе и +1 к сообщению или +1 к кол-ву тем, созданных юзером

if (isset($_COOKIE['wrfcookies']) and (isset($ok))) {

$ufile="$datadir/userstat.dat"; $ulines=file("$ufile"); $ui=count($ulines)-1; $ulinenew="";

// Ищем юзера по имени в файле userstat.dat
for ($i=0;$i<=$ui;$i++) {$udt=explode("|",$ulines[$i]);
if ($udt[0]==$wrfname) {
$udt[3]++; $udt[2]++; if ($_GET['event']=="addtopic") $udt[1]++;
$ulines[$i]="$udt[0]|$udt[1]|$udt[2]|$udt[3]|$udt[4]|$udt[5]||||\r\n";}
$ulinenew.="$ulines[$i]";}
// Пишем данные в файл
$fp=fopen("$ufile","w");
flock ($fp,LOCK_EX);
fputs($fp,"$ulinenew");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);

} // if isset($ok)



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

print "<script language='Javascript'>function reload() {location = \"admin.php?id=$id\"}; setTimeout('reload()', 1500);</script>
<table width=100% height=80%><tr><td><table border=1 cellpadding=10 cellspacing=0 bordercolor=#224488 align=center valign=center width=60%><tr><td><center>
Спасибо, <B>$name</B>, за добавление темы!<BR><BR>Через несколько секунд Вы будете автоматически перемещены в созданную тему.<BR><BR>
<B><a href='admin.php?id=$id'>ДАЛЬШЕ >>></a></B></td></tr></table></td></tr></table></center></body></html>";
exit;
}


if ($_GET['event'] =="addanswer") { //ОТВЕТ В ТЕМЕ - запись данных
$timetek=time(); $timefile=filemtime("$datadir/$id.dat"); 
$timer=$timetek-$timefile; // узнаем сколько прошло времени (в секундах) 
$fp=fopen("$datadir/$id.dat","a+");
flock ($fp,LOCK_EX);
fputs($fp,"$text\r\n");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
if ($timer<0) {$viptime=strtotime("+2 year"); touch("$datadir/$id.dat",$viptime);}

$in=$in+2; $page=ceil($in/$qq); // расчитываем верную страницу и номер сообщения

print "<script language='Javascript'>function reload() {location = \"admin.php?id=$id&page=$page#m$in\"}; setTimeout('reload()', 1500);</script>
<table width=100% height=80%><tr><td><table border=1 cellpadding=10 cellspacing=0 bordercolor=#224488 align=center valign=center width=60%><tr><td><center>
Спасибо, <B>$name</B>, Ваш ответ успешно добавлен.<BR><BR>Через несколько секунд Вы будете автоматически перемещены в текущую тему <BR><B>$zag</B>.<BR><BR>
<B><a href='admin.php?id=$id&page=$page#m$in'>ДАЛЬШЕ >>></a></B></td></tr></table></td></tr></table></center></body></html>";
exit;
}











// Сделать копию БД
if ($_GET['event']=="makecopy") {
if (is_file("$datadir/mainforum.dat")) $lines=file("$datadir/mainforum.dat");
if (!isset($lines)) $datasize=0; else $datasize=sizeof($lines);
if ($datasize<=0) exit("Проблемы с Базой данных - база повреждена. Размер = 0!");
if (copy("$datadir/mainforum.dat", "$datadir/copy.dat")) exit("<center><BR>Копия база данных создана.<BR><BR><h3>$back</h3></center>"); else exit("Ошибка создания копии БАЗЫ Данных. Попробуйте создать вручную файл copy.dat в папке $datadir и выставить ему права на ЗАПИСЬ - 666 или полные права 777 и повторите операцию создания копии!"); }

// Восстановить из копии БД
if ($_GET['event']=="restore") {
if (is_file("$datadir/copy.dat")) $lines=file("$datadir/copy.dat");
if (!isset($lines)) $datasize=0; else $datasize=sizeof($lines);
if ($datasize<=0) exit("Проблемы с копией базы данных - она повреждена. Восстановление невозможно!");
if (copy("$datadir/copy.dat", "$datadir/mainforum.dat")) exit("<center><BR>БД восстановлена из копии.<BR><BR><h3>$back</h3></center>"); else exit("Ошибка восстановления из копии БАЗЫ Данных. Попробуйте вручную файлам copy.dat и mainforum.dat в папке $datadir выставить права на ЗАПИСЬ - 666 или полные права 777 и повторите операцию восстановления!"); }



// КОНФИГУРИРОВАНИЕ форума, шаг 2: сохранение данных
if ($_GET['event']=="config") {

// обработка полей пароль админа/модератора
if (strlen($_POST['newpassword'])<1 or strlen($_POST['newmoderpass'])<1) exit("$back разрешается длина пароля МИНИМУМ 1 символ!");
if ($_POST['newpassword']!="скрыт") {$pass=trim($_POST['newpassword']); $_POST['password']=md5("$pass+$skey");}
if ($_POST['newmoderpass']!="скрыт") {$pass=trim($_POST['newmoderpass']); $_POST['moderpass']=md5("$pass+$skey");}

// защита от дурака. Дожились, уже в админке защиту приходится ставить...
$fd=stripslashes($_POST['fdesription']); $fd=str_replace("\\","/",$fd); $fd=str_replace("?>","? >",$fd); $fd=str_replace("\"","'",$fd); $fdesription=str_replace("\r\n","<br>",$fd);

mt_srand(time()+(double)microtime()*1000000); $rand_key=mt_rand(1000,9999); // Генерируем случайное число для цифрозащиты

$gmttime=($_POST['deltahour'] * 60 * 60); // Считаем смещение

$newsmiles=$_POST['newsmiles'];

$i=count($newsmiles); $smiles="array(";
for($k=0; $k<$i; $k=$k+2) {
  $j=$k+1; $s1=replacer($newsmiles[$k]); $s2=replacer($newsmiles[$j]);
  $smiles.="\"$s1\", \"$s2\""; if ($k!=($i-2)) $smiles.=",";
} $smiles.=");";

$_POST['fname']=replacer($_POST['fname']);

$configdata="<? // WR-forum v 1.9.9  //  20.07.12 г.  //  Miha-ingener@yandex.ru\r\n".
"$"."fname=\"".$_POST['fname']."\"; // Название форума показывается в теге TITLE и заголовке\r\n".
"$"."fdesription=\"".$fdesription."\"; // Краткое описание форума\r\n".
"$"."adminname=\"".$_POST['adminname']."\"; // Логин администратора\r\n".
"$"."password=\"".$_POST['password']."\"; // Пароль администратора защифрован md5()\r\n".
"$"."modername=\"".$_POST['modername']."\"; // Логин модератора\r\n".
"$"."moderpass=\"".$_POST['moderpass']."\"; // Пароль модератора защифрован md5()\r\n".
"$"."adminemail=\"".$_POST['newadminemail']."\"; // Е-майл администратора\r\n".
"$"."stop=\"".$_POST['stop']."\"; // ОТКЛЮЧИТЬ добавление тем/сообщений\r\n".
"$"."antimat=\"".$_POST['antimat']."\"; // включить АНТИМАТ да/нет - 1/0\r\n".
"$"."random_name=\"".$_POST['random_name']."\"; // При загрузке файла генерировать ему имя случайным образом?\r\n".
"$"."repaaddfile=\"".$_POST['repaaddfile']."\"; // Сколько очков репутации добавлять при загрузке файла?\r\n".
"$"."repaaddmsg=\"".$_POST['repaaddmsg']."\"; // Сколько очков репутации добавлять за добавление сообщения?\r\n".
"$"."repaaddtem=\"".$_POST['repaaddtem']."\"; // Сколько очков репутации добавлять за добавлении темы?\r\n".
"$"."sendmail=\"".$_POST['sendmail']."\"; // Включить отправку сообщений? 1/0\r\n".
"$"."sendadmin=\"".$_POST['sendadmin']."\"; // Мылить админу сообщения о вновь зарегистрированных пользователях? 1/0\r\n".
"$"."statistika=\"".$_POST['statistika']."\"; // Показывать статистику на главной странице? 1/0\r\n".
"$"."antispam=\"".$_POST['antispam']."\"; // Задействовать АНТИСПАМ\r\n".
"$"."max_key=\"".$_POST['max_key']."\"; // Кол-во символов в коде ЦИФРОЗАЩИТЫ\r\n".
"$"."rand_key=\"".$rand_key."\"; // Случайное число для цифрозащиты\r\n".
"$"."newmess=\"".$_POST['newmess']."\"; // Создавать файл с новыми сообщениями форума?\r\n".
"$"."guest=\"".$_POST['newguest']."\"; // Как называем не зарег-ся пользователей\r\n".
"$"."users=\"".$_POST['newusers']."\"; // Как называем зарег-ся\r\n".
"$"."cangutema=\"".$_POST['cangutema']."\"; // Разрешить гостям создавать темы? 1/0\r\n".
"$"."cangumsg=\"".$_POST['cangumsg']."\"; // Разрешить гостям оставлять сообщения? 1/0\r\n".
"$"."useactkey=\"".$_POST['useactkey']."\"; // Требовать активации через емайл при регистрации? 1/0\r\n".
"$"."maxname=\"".$_POST['newmaxname']."\"; // Максимальное кол-во символов в имени\r\n".
"$"."maxzag=\"".$_POST['maxzag']."\"; // Масимальный кол-во символов в заголовке темы\r\n".
"$"."maxmsg=\"".$_POST['newmaxmsg']."\"; // Максимальное количество символов в сообщении\r\n".
"$"."qqmain=\"".$_POST['newqqmain']."\"; // Кол-во отображаемых тем на страницу (15)\r\n".
"$"."qq=\"".$_POST['newqq']."\"; // Кол-во отображаемых сообщений на каждой странице (10)\r\n".
"$"."uq=\"".$_POST['uq']."\"; // По сколько человек выводить список участников\r\n".
"$"."specblok1=\"".$_POST['specblok1']."\"; // Включить БЛОК 15-и самых обсуждаемых тем?\r\n".
"$"."specblok2=\"".$_POST['specblok2']."\"; // Включить БЛОК 10 самых активных пользователей?\r\n".
"$"."nosssilki=\"".$_POST['nosssilki']."\"; // Запретить гостям добавлять сообщения со ссылками?\r\n".
"$"."liteurl=\"".$_POST['liteurl']."\";// Подсвечивать УРЛ? 1/0\r\n".
"$"."max_file_size=\"".$_POST['max_file_size']."\"; // Максимальный размер аватара в байтах\r\n".
"$"."datadir=\"".$_POST['datadir']."\"; // Папка с данными форума\r\n".
"$"."smile=\"".$_POST['smile']."\";// Включить/отключить графические смайлы\r\n".
"$"."canupfile=\"".$_POST['canupfile']."\"; // Разрешить загрузку фото 0 - нет, 1 - только зарегистрированным\r\n".
"$"."filedir=\"".$_POST['filedir']."\"; // Каталог куда будет закачан файл\r\n".
"$"."max_upfile_size=\"".$_POST['max_upfile_size']."\"; // максимальный размер файла в байтах\r\n".
"$"."fskin=\"".$_POST['fskin']."\"; // Текущий скин форума\r\n".
"$"."back=\"<html><head><meta http-equiv='Content-Type' content='text/html; charset=windows-1251'><meta http-equiv='Content-Language' content='ru'></head><body><center>Вернитесь <a href='javascript:history.back(1)'><B>назад</B></a>\"; // Удобная строка\r\n".
"$"."smiles=".$smiles."// СМАЙЛИКИ (имя файла, символ для вставки, -//-)\r\n".
"$"."date=date(\"d.m.Y\", time()+$gmttime); // число.месяц.год\r\n".
"$"."deltahour=\"".$_POST['deltahour']."\"; // Учитываем кол-во часов со смещением относительно хостинга по формуле: ЧЧ * 3600\r\n".
"$"."time=date(\"H:i:s\",time()+$gmttime); // часы:минуты:секунды\r\n?>";
$file=file("config.php");
$fp=fopen("config.php","a+");
flock ($fp,LOCK_EX); 
ftruncate ($fp,0);//УДАЛЯЕМ СОДЕРЖИМОЕ ФАЙЛА 
fputs($fp,$configdata);
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
Header("Location: admin.php?event=configure"); exit;}


} // конец if isset($event)




// шапка для всех страниц форума

if (isset($_COOKIE['wrfcookies'])) {
$wrfc=$_COOKIE['wrfcookies']; $wrfc = explode("|", $wrfc);
$wrfname=$wrfc[0];$wrfpass=$wrfc[1];$wrftime1=$wrfc[2];$wrftime2=$wrfc[3];
if (time()>($wrftime1+50)) { $tektime=time();
$wrfcookies="$wrfc[0]|$wrfc[1]|$tektime|$wrftime1|";
setcookie("wrfcookies", $wrfcookies, time()+1728000);
$wrfc=$_COOKIE['wrfcookies']; $wrfc = explode("|", $wrfc);
$wrfname=$wrfc[0];$wrfpass=$wrfc[1];$wrftime1=$wrfc[2];$wrftime2=$wrfc[3]; }}

if (is_file("$datadir/mainforum.dat")) $mainlines=file("$datadir/mainforum.dat");
if (!isset($mainlines)) $datasize=0; else $datasize=sizeof($mainlines);
if ($datasize<=0) {if (is_file("$datadir/copy.dat")) {$mainlines=file("$datadir/copy.dat"); $datasize=sizeof($mainlines);}}
if ($datasize<=0) exit("<center><h3>Файл РУБРИК несуществует! создайте рубрики!</h3>");









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
$maxikpage=ceil($imax/$qqmain); }

} // if (isset($_GET['id']))





 



// печатаем ВЕРХУШКУ форума если есть файл
?>
<html>
<head>
<title>Админка :: <?print"$frtname $frname $fname";?></title>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Cache-Control" CONTENT="no-cache">
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<meta http-equiv="Content-Language" content="ru">
<meta name="description" content="<? print"$fdesription - $fname";?>">
<meta http-equiv="keywords" content="<? print"$frtname $frname $fname";?>">
<meta name="Resource-type" content="document">
<meta name="document-state" content="dynamic">
<meta name="Robots" content="index,follow">
<link rel="stylesheet" href="<?=$fskin?>/style.css" type="text/css">
<SCRIPT language=JavaScript>
<!--
function x () {return;}
function FocusText() {
 document.REPLIER.msg.focus();
 document.REPLIER.msg.select();
 return true; }
function DoSmilie(addSmilie) {
 var revisedMessage;
 var currentMessage=document.REPLIER.msg.value;
 revisedMessage=currentMessage+addSmilie;
 document.REPLIER.msg.value=revisedMessage;
 document.REPLIER.msg.focus();
 return;
}
function DoPrompt(action) { var revisedMessage; var currentMessage=document.REPLIER.msg.value; }
//-->
</SCRIPT>
</head>

<body bgcolor="#E5E5E5" text="#000000" link="#006699" vlink="#5493B4" bottomMargin=0 leftMargin=0 topMargin=0 rightMargin=0 marginheight="0" marginwidth="0">

<table width=100% cellspacing=0 cellpadding=10 align=center><tr><td class=bodyline>
<table width=100% cellspacing=0 cellpadding=0>
<tr>
<td><a href="index.php"><img src="<?=$fskin?>/wr-logo.gif" border=0 alt="<?=$fname?>" vspace="1" /></a>
<br><div align=center>Вы вошли как <B><font color=red><?if ($ktotut==1) echo'Администратор'; else echo'Модератор';?></font></B></td>
<td align="center" valign="middle"><span class="maintitle"><a href=admin.php><h3><font color=red>Панель администрирования<br></font> <?=$fname?></h3></a></span>
<table width=80%><TR><TD align=center><span class="gen"><?=$fdesription?><br><br></span></TD></TR></TABLE>
<table cellspacing=0 cellpadding=2><tr><td align=center valign=middle>
<a href='admin.php?event=makecopy' class=mainmenu><img src="<?=$fskin?>/buttons_spacer.gif">Сделать копию БД</a> 
<a href='admin.php?event=restore' class=mainmenu><img src="<?=$fskin?>/buttons_spacer.gif">Восстановить из копии</a> 
<a href='admin.php?event=userwho' class=mainmenu><img src="<?=$fskin?>/buttons_spacer.gif">Участники</a>
<a href='admin.php?event=blockip' class=mainmenu><img src="<?=$fskin?>/buttons_spacer.gif">IP-Блокировка</a>
<a href='admin.php?event=massmail' class=mainmenu><img src="<?=$fskin?>/buttons_spacer.gif">Рассылка сообщений участникам</a>
<a href='admin.php?event=revolushion' class=mainmenu><img src="<?=$fskin?>/buttons_spacer.gif">Пересчитать</a>
<a href='admin.php?newstatistik' class=mainmenu><img src="<?=$fskin?>/buttons_spacer.gif">Пересчитать статистику участников</a>

<? if ($ktotut==1) print"<a href='admin.php?event=configure' class=mainmenu><img src='$fskin/buttons_spacer.gif' width='12' height='13' border='0' alt='' hspace='3' />Настройки</a>";
print"<a href='admin.php?event=clearcooke' class=mainmenu><img src='$fskin/buttons_spacer.gif' width='12' height='13' border='0' alt='Поиск' hspace='3'>Выход из админки</a>";

// читаем файл с именами пользователей в память чтобы показать последнего
$userlines=file("$datadir/usersdat.php");
$ui=count($userlines)-1;
$tdt = explode("|", $userlines[$ui]);

if (is_file("$datadir/copy.dat")) {
if (count(file("$datadir/copy.dat"))<1) $a2="<font color=red size=+1>НО файл копии ПУСТ! Срочно пересоздайте!</font><br> (смотрите права доступа, если эо сообщение повторяется)"; else $a2="";
$a1=round((time()-filemtime("$datadir/copy.dat"))/86400); if ($a1<1) $a1="сегодня</font>, это есть гуд!"; else $a1.="</font> дней назад.";
$add="<br><B><center>Копия была создана <font color=red size=+1>".$a1." $a2</B>"; if ($a1>90) $add.="Да уж, больше 3-х месяцев ниодной копии не делали. Испытываете судьбу? Делайте БЕГОМ!"; if ($a1>10) $add.="Вы что! СРОЧНО делайте копию! А вдруг сбой? Как будете данные восстанавливать?!!"; if ($a1>5) $add.="Пора делать копию. Берегите свои нервы. Чтобы быть спокойным при сбое ;-)"; $add.="</center>";} else $add="";

print"</span>
</td></tr></table>
</td></tr></table>
$add<table width=100% cellspacing=0 cellpadding=2>
<tr><td><span class=gensmall>Сегодня: $date - $time</td></tr></table>";






// выводим ГЛАВНУЮ СТРАНИЦУ ФОРУМА
if (!isset($_GET['event'])) {

if (!isset($_GET['id'])) {
echo'
<table width=100% cellpadding=2 cellspacing=1 class=forumline>
<tr><th width=60% colspan=2 class=thCornerL height=25 nowrap=nowrap>Форумы</th>
<th width=10% class=thTop nowrap=nowrap>Тем/Макс.</th>
<th width=7% class=thCornerR nowrap=nowrap>Ответов</th>
<th width=28% class=thCornerR nowrap=nowrap>Обновление</th></tr>';

// Выводим qq сообщений на текущей странице

$addform="<form action='admin.php?event=addmainforum' method=post name=REPLIER1><table width=100% cellpadding=4 cellspacing=1 class=forumline><tr> <td class=catHead colspan=2 height=28><span class=cattitle>Добавление Раздела / Форума</span></td></tr><tr><td class=row1 align=right><b><span class=gensmall>Тип добавляемого пункта</span></b></td><td class=row1><input type=radio name=ftype value='razdel'> Раздел &nbsp;&nbsp;<input type=radio name=ftype value=''checked> Форум</tr></td><tr><td class=row1 align=right valign=top><span class=gensmall><B>Заголовок</B></td><td class=row1 align=left valign=middle><input type=text class=post value='' name=zag size=70></td></tr><tr><td class=row1 align=right valign=top><span class=gensmall>Описание</td><td class=row1 align=left valign=middle><textarea cols=100 rows=6 size=500 class=post name=msg></textarea></td></tr><tr><td class=row1 colspan=2><center><input type=submit class=mainoption value='     Добавить     '></td></span></tr></table></form>";

if (!is_file("$datadir/mainforum.dat")) exit("<h3>Восстановите БД из копии. Файл mainforum.dat несуществует или добавьте форум/раздел.</h3>$addform"); 

$lines = file("$datadir/mainforum.dat"); $datasize = sizeof($lines);

if ($datasize==0) exit("<h3>Файл mainforum.dat пуст - добавьте форум или раздел.</h3>$addform");

$i=count($lines);
$n="0"; $a1="-1"; $u=$i-1;
$fid="0"; $itogotem="0"; $itogomsg="0";

do {$a1++; $dt = explode("|", $lines[$a1]);
$fid=$dt[0];


echo'<tr height=30><td class=row1>';

if ($ktotut==1) { // только админ может управлять разделами
print"<table><TR>
<td width=10 bgcolor=#A6D2FF><B><a href='admin.php?movetopic=$a1&where=1' title='переместить ВВЕРХ'>Вв</a></B></td>
<td width=10 bgcolor=#DEB369><B><a href='admin.php?movetopic=$a1&where=0' title='переместить ВНИЗ'>Нз</a></B></td>
<td width=10 bgcolor=#22FF44><B><a href='admin.php?frd=$a1' title='РЕДАКТИРОВАТЬ'>.P.</a></B></td>
<td width=10 bgcolor=#FF2244><B><a href='admin.php?fxd=$dt[0]' title='УДАЛИТЬ' onclick=\"return confirm('Будет удалён раздел и ВСЕ ТЕМЫ В НЁМ! Удалить? Уверены?')\" >.X.</a></B></td>
</tr></table>"; }

echo'</td>';

// определяем тип: форум или заголовок
if ($dt[1]=="razdel") print "<td class=catLeft colspan=1><span class=cattitle><center>$dt[2]</td><td class=rowpic colspan=4 align=right>&nbsp;</td></tr>";

else {

if (is_file("$datadir/$dt[3].dat")) { $msgsize=sizeof(file("$datadir/$dt[3].dat")); // считаем кол-во страниц в файле
if ($msgsize>$qq) $page=ceil($msgsize/$qq); else $page=1; } else {$msgsize=""; $page=1;}

if ($dt[7]==$date) $dt[7]="сегодня";
$maxzvezd=null; if (isset($dt[12])) { if ($dt[12]>0) {$maxzvezd="*Доступна участникам, имеющим <font color=red><B>$dt[12]</B> звезд";
$dt[4]=""; $dt[5]="";
if ($dt[12]==1) $maxzvezd.="у";
if ($dt[12]==2 or $dt[12]==3 or $dt[12]==4) $maxzvezd.="ы"; $maxzvezd.=" минимум</font>";}}

print "
<td width=60% class=row1 valign=middle><span class=forumlink><a href=\"admin.php?id=$fid\">$dt[1]</a> $maxzvezd<BR></span><small>$dt[2]</small></td>
<td width=7% class=row2 align=center><small>$dt[4] / $dt[11]</small></td>
<td width=7% class=row2 align=center valign=middle><small>$dt[5]</small></td>
<td width=28% class=row2 valign=middle><span class=gensmall>
тема: <a href=\"admin.php?id=$dt[3]&page=$page#m$msgsize\">$dt[10]</a><BR>
автор: <B>$dt[6]</B><BR>
дата: <B>$dt[7]</B> - $dt[8]</span></td></tr>";

$itogotem=$itogotem+$dt[4]; $itogomsg=$itogomsg+$dt[5]; }
} while($a1 < $u);
echo'</table><BR>';

// Выбрано редактирование ФОРУМА
if (isset($_GET['frd'])) { if ($_GET['frd'] !="") { $frd=$_GET['frd'];
$lines = file("$datadir/mainforum.dat");
$dt = explode("|", $lines[$frd]);
if (isset($dt[11])) { if ($dt[11]>0) $addmax=$dt[11]; else $addmax="100"; }
if (isset($dt[12])) {if ($dt[12]<=0) $dt[12]="0";}
$dt[2]=str_replace("<br>","\r\n",$dt[2]);
print "<form action='admin.php?event=frdmainforum' method=post name=REPLIER1><table width=100% cellpadding=4 cellspacing=1 class=forumline><tr> <td class=catHead colspan=2 height=28><span class=cattitle>Редактирование Раздела / Форума</span></td></tr>
<tr><td class=row1 align=right>Тип редактируемого пункта</td><td class=row1><input type=hidden name=nextnum value='$dt[0]'>";
if ($dt[1]=="razdel") print "<input type=hidden name=ftype value='razdel'>Раздел</tr></td><tr><td class=row1 align=right valign=top><span class=gensmall><B>Заголовок</B></td><td class=row1 align=left valign=middle><input type=text value='$dt[2]' name=zag size=70></td></tr>";
else {print "
<input type=hidden name=ftype value=''>Форум</tr></td><tr><td class=row1 align=right valign=top><B>Заголовок</B></td><td class=row1 align=left valign=middle><input class=post type=text value='$dt[1]' name=zag size=70></td></tr>
<tr><td class=row1 align=right valign=top>Описание</td><td class=row1 align=left valign=middle><textarea cols=80 rows=6 class=post size=500 name=msg>$dt[2]</textarea>
<input type=hidden name=idtemka value='$dt[3]'>
<input type=hidden name=kt value='$dt[4]'>
<input type=hidden name=km value='$dt[5]'>
<input type=hidden name=namem value='$dt[6]'>
<input type=hidden name=datem value='$dt[7]'>
<input type=hidden name=timem value='$dt[8]'>
<input type=hidden name=timetk value='$dt[9]'>
<input type=hidden name=temka value='$dt[10]'>
</td></tr>
<TR><TD align=right class=row1>Максимальное кол-во тем в форуме</TD><TD class=row1><input type=text class=post name=addmax value='$addmax'></TD></TR>
<input type=hidden name=zvezdmax value='$dt[12]'>
<TR><TD align=right class=row1>Заблокировать по звёздам</TD><TD class=row1><input type=text class=post size=5 maxlength=1 name=zvezdmax value='$dt[12]'>
(ТОЛЬКО участники с указанным кол-вом звёзд могут обсуждать этот форум)</TD></TR>";}

print"<tr><td colspan=2 class=row1><input type=hidden name=frd value='$frd'><SCRIPT language=JavaScript>document.REPLIER1.zag.focus();</SCRIPT><center><input type=submit class=mainoption value='     Изменить     '></td></span></tr></table></form><BR>";
} } // Конец редактирования ФОРУМА

else { if ($ktotut==1) print "$addform"; }


if ($statistika==TRUE) {
print"<table width=100% cellpadding=3 cellspacing=1 class=forumline><tr><td class=catHead colspan=2 height=28><span class=cattitle>Статистика</span></td></tr><tr>
<td class=row1 align=center valign=middle rowspan=2><img src=\"$fskin/whosonline.gif\"></td>
<td class=row1 align=left width=95%><span class=gensmall>Сообщений: <b>$itogomsg</b><br>Тем: <b>$itogotem</b><br>Всего зарегистрировано участников: <b><a href=\"tools.php?event=who\">$ui</a></b><br>Последним зарегистрировался: <a href=\"admin.php?event=profile&pname=$tdt[0]\">$tdt[0]</a></span></td>
</tr></table>"; 

// СТАТИСТИКА -= Последние сообщения с форума =-

if (is_file("$datadir/news.dat")) { $newmessfile="$datadir/news.dat";
$lines=file($newmessfile); $i=count($lines); //if ($i>10) $i=10; (РАСКОМЕНТИРУЙ - ВОТ ГДЕ СИЛА!!! ;-))
if ($i>1) {
echo('<br><table width=100% cellpadding=0 cellspacing=1 class=forumline><tr><td class=catHead colspan=3 height=28><span class=cattitle>Последние сообщения</span></td></tr>
<tr><td rowspan=20 class=row1 align=center valign=middle rowspan=2><img src="'.$fskin.'/whosonline.gif"></td>');

$a1=$i-1;$u="-1"; // выводим данные по возрастанию или убыванию
do {$dt=explode("|", $lines[$a1]); $a1--;

if (isset($dt[1])) { // Если строчка потерялась в скрипте (пустая строка) - то просто её НЕ выводим
$dt[6]=htmlspecialchars($dt[6]);
$dt[6]=str_replace("[b] "," ",$dt[6]);
$dt[6]=str_replace("[/b]"," ",$dt[6]);
$dt[6]=str_replace("[RB] "," ",$dt[6]);
$dt[6]=str_replace("[/RB]"," ",$dt[6]);
$dt[6]=str_replace("[Code] "," ",$dt[6]);
$dt[6]=str_replace("[/Code]"," ",$dt[6]);
$dt[6]=str_replace("[Quote] "," ",$dt[6]);
$dt[6]=str_replace("[/Quote]"," ",$dt[6]);
$dt[6]=str_replace("<br>","\r\n", $dt[6]);
$dt[6]=str_replace("'","`", $dt[6]);
$dt[2]=str_replace(".201",".1", $dt[2]);
$dt[2]=substr($dt[2],0,8);
$dt[3]=substr($dt[3],0,5);
if ($dt[8]>$qq) $page=ceil($dt[8]/$qq); else $page=1; // Считаем страницу

if ($dt[10]=="да") {$codename=urlencode($dt[4]); $name="<B><a href='admin.php?event=profile&pname=$codename'>$dt[4]</a></B>";} else $name="гость $dt[4]";

print"
<td class=row1><table><tr><td width=10 bgcolor=#FF2244><B><a href='admin.php?lxd=$dt[1]' title='УДАЛИТЬ' onclick=\"return confirm('Будет удалена ссылка на сообщение! Удалить? Уверены?')\" >.X.</a></B></td></tr></table></td>
<td class=row1 width=95% align=left><span class=gensmall>
$dt[2] - $dt[3]: <B><a href='admin.php?id=$dt[0]'>$dt[9]</a></B> -> <B><a href='admin.php?id=$dt[1]&page=$page#m$dt[8]' title='$dt[6] \r\n\r\n Отправлено $dt[3], $dt[2] г.'>$dt[5]</a></B> - $name.</td></tr>";
} // если строчка потерялась
$a11=$u; $u11=$a1;
} while($a11 < $u11);
echo'</span></td></tr></table>';}

} // Конец блока последних сообщений
}

} // конец главной страницы





// Общая переменная!
if (isset($_GET['id'])) {

if (strlen($_GET['id'])==3) { $fid=replacer($_GET['id']); $id=replacer($_GET['id']); }
else $id=replacer($_GET['id']);



if (strlen($id)==3) { // выводим страницу С ТЕМАМИ выбранной РУБРИКИ

$maxzd=null; // Уточняем статус по кол-ву ЗВЁЗД в теме
$imax=count($mainlines);
do {$imax--; $ddt=explode("|", $mainlines[$imax]); if ($ddt[0]==$fid) $maxzd=$ddt[12]; } while($imax>"0");
if (!ctype_digit($maxzd)) $maxzd=0;

print "
<table><tr><td><span class=nav>&nbsp;&nbsp;&nbsp;<a href=admin.php class=nav>$fname</a> -> <a href=admin.php?id=$fid class=nav>$frname</a></span></td></tr></table>
<table width=100% cellpadding=2 cellspacing=1 class=forumline><tr>
<th width=3% class=thCornerL height=25 nowrap=nowrap>X/P</th>
<th width=57% colspan=2 class=thCornerL height=25 nowrap=nowrap>Тема</th>
<th width=10% class=thTop nowrap=nowrap>Cообщений</th>
<th width=12% class=thCornerR nowrap=nowrap>Автор</th>
<th width=18% class=thCornerR nowrap=nowrap>Обновления</th></tr>";

$addbutton="<table width=100%><tr><td align=left valign=middle><span class=nav><a href=\"admin.php?id=$fid&newtema=add\"><img src='$fskin/newthread.gif' border=0></a>&nbsp;</span></td>";


// определяем есть ли информация в файле с данными
if (is_file("$datadir/topic$fid.dat"))
{
$msglines=file("$datadir/topic$fid.dat");
if (count($msglines)>0) {

if (count($msglines)>$maxtem-1) $addbutton="<table width=100%><TR><TD>Количество допустимых тем в рубрике исчерпано.";

// Выводим qqmain сообщений на текущей странице
$lines=file("$datadir/topic$fid.dat");
$i=count($lines); $maxi=count($lines)-1; $n="0";


// БЛОК СОРТИРОВКИ: последние ответы ВВЕРХУ (по времени создания файла с темой)!
if ($maxi>0) {
do {$i--; $dt=explode("|",$lines[$i]);
   $filename="$dt[7].dat"; if (is_file("$datadir/$filename")) $ftime=filemtime("$datadir/$filename"); else $ftime="";
   $newlines[$i]="$ftime|$dt[7]|$i|";
} while($i > 0);
usort($newlines,"prcmp");
// $newlines - массив с данными: ДАТА | ИМЯ_ФАЙЛА_С_ТЕМОЙ | № п/п |
// $lines - массив со всеми темами выбранной рубрики
$i=$maxi;
do {$i--; $dtn=explode("|", $newlines[$i]);
  $numtp="$dtn[2]"; $lines[$i]="$lines[$numtp]";
} while($i > 0);
} // if $maxi>0
// КОНЕЦ блока сортировки

// Исключаем ошибку вызова несуществующей страницы
if (!isset($_GET['page'])) $page=1; else { $page=$_GET['page']; if (!ctype_digit($page)) $page=1; if ($page<1) $page=1; }


// Показываем QQ ТЕМ
$fm=$maxi-$qq*($page-1); if ($fm<"0") $fm=$qq;
$lm=$fm-$qq; if ($lm<"0") $lm="-1";

$timetek=time();

do {$dt=explode("|", $lines[$fm]);

// нужно для определения темы на VIP-статус
if (is_file("$datadir/$dt[7].dat")) $ftime=filemtime("$datadir/$dt[7].dat"); else $ftime="";
$timer=$timetek-$ftime; // узнаем сколько прошло времени (в секундах) 

$fm--; $num=$fm+2; $numid=$fm+1;

$filename=$dt[7]; if (is_file("$datadir/$filename.dat")) { // если файл с темой существует - то показать тему
$msgsize=sizeof(file("$datadir/$filename.dat"));

// --------- Выделяем новые сообщения
$linetmp=file("$datadir/$filename.dat"); if (sizeof($linetmp)!=0) {
$pos=$msgsize-1; $dtt=explode("|", $linetmp[$pos]);
$foldicon="folder.gif";
// Если последнее сообщение в форуме произошло раньше посещения - значит раздел форума - новый
if (isset($wrfname)) {if (isset($dtt[9])) {if ($dtt[9]>$wrftime2) $foldicon="foldernew.gif";}}
if (strlen($dt[8])>1 and $dt[8]=="closed") {if ($msgsize<"20") $foldicon="close.gif"; else $foldicon="closed.gif"; }} else $foldicon="foldernew.gif";
// --------- Конец

print "<tr height=50>
<td width=3% class=row1><table><tr><td width=10 bgcolor=#22FF44><B><a href='admin.php?id=$id&rd=$dt[7]&page=$page' title='РЕДАКТИРОВАТЬ'>.P.</a></B></td></tr><tr><td width=10 bgcolor=#FF2244><B><a href='admin.php?id=$fid&xd=$numid&id=$dt[7]&page=$page' title='УДАЛИТЬ' onclick=\"return confirm('Будет удалена ТЕМА со всеми сообщениями! Удалить? Уверены?')\" >.X.</a></B></td>

</tr><tr><td width=10 bgcolor=#F58405><B><a href='admin.php?rename=$dt[7]&id=$fid&page=$page' title='ПЕРЕНУМЕРОВАТЬ!'>.Н.</a></B></td>

</tr></table></td>
<td width=3% class=row1 align=center valign=middle><img src=\"$fskin/$foldicon\" border=0></td>
<td width=57% class=row1 valign=middle><span class=forumlink><b>";

if ($timer<0) echo'<font color=red>VIP </font>';

print"<a href=\"admin.php?id=$dt[7]\">$dt[3]</a>";

if ($msgsize>$qq) { // ВЫВОДИМ СПИСОК ДОСТУПНЫХ СТРАНИЦ ТЕМЫ
$maxpaget=ceil($msgsize/$qq); $addpage="";
echo'</b></span><small>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div style="padding:6px;" class=pgbutt>Страницы: ';
if ($maxpaget<=5) $f1=$maxpaget; else $f1=5;
for($i=1; $i<=$f1; $i++) {if ($i!=1) $addpage="&page=$i"; print"<a href=admin.php?id=$dt[7]$addpage>$i</a> &nbsp;";}
if ($maxpaget>5) print "... <a href=admin.php?id=$dt[7]&page=$maxpaget>$maxpaget</a>"; }

print"</div></td><td class=row2 align=center>$msgsize</td><td class=row2><span class=gensmall>";

$codename=urlencode($dt[0]);
if ($dt[1]=="да") print "<a href='admin.php?event=profile&pname=$codename':$dt[2]>$dt[0]</a><BR><small>$users</small>"; else print"$dt[0]<BR><small>$guest</small>";


if ($msgsize>=2) {$linesdat=file("$datadir/$filename.dat"); $dtdat=explode("|", $linesdat[$msgsize-1]);
if (strlen($linesdat[$msgsize-1])>10) {$dt[0]=$dtdat[0]; $dt[1]=$dtdat[1]; $dt[2]=$dtdat[2]; $dt[5]=$dtdat[5]; $dt[6]=$dtdat[6];}} // защита if (strlen...) только если файл есть и имеет верный формат - выводим

$dt[6]=substr($dt[6],0,-3);
if ($dt[5]===$date) $dt[5]="<B>сегодня</B>";
print "</span></td><td width=15% height=50 class=row2 align=left valign=middle nowrap=nowrap><span class=gensmall>&nbsp;
автор: $dt[0]<BR>&nbsp;
дата: $dt[5]<BR>&nbsp;
время: $dt[6]</font>
</td></tr>";
} //if (is_file)

} while($lm < $fm);


// формируем переменную $pageinfo - со СПИСКОМ СТРАНИЦ
$pageinfo=""; $addpage=""; $maxpage=ceil(($maxi+1)/$qq); if ($page>$maxpage) $page=$maxpage;
$pageinfo.="<div style='padding:6px;' align=right class=pgbutt>Страницы: &nbsp;";
if ($page>3 and $maxpage>5) $pageinfo.="<a href=admin.php?id=$fid>1</a> ... ";
$f1=$page+2; $f2=abs($page-2); if ($f2=="0") $f2=1; if ($page>=$maxpage-1) $f1=$maxpage;
if ($maxpage<=5) {$f1=$maxpage; $f2=1;}
for($i=$f2; $i<=$f1; $i++) { if ($page==$i) $pageinfo.="<B>$i</B> &nbsp;"; 
else {if ($i!=1) $addpage="&page=$i"; $pageinfo.="<a href=admin.php?id=$fid$addpage>$i</a> &nbsp;";} }
if ($page<=$maxpage-3 and $maxpage>5) $pageinfo.="... <a href=admin.php?id=$fid&page=$maxpage>$maxpage</a>";
$pageinfo.='</div>';

print "</table>$pageinfo";
}}


















// ------------ Выбрано редактирование ТЕМЫ
if (isset($_GET['rd'])) { if ($_GET['rd'] !="") { $rd=replacer($_GET['rd']); $i="-1";

// Бежим по массиву тем и ищем ту тему, которую вызвали на редактирование
do {$i++; $dt=explode("|",$lines[$i]);
if ($dt[7]===$rd) $i=$maxi; // ЕСЛИ нашли тему, значит завершаем цикл и дальше работаем со строкой
} while($i < $maxi);

$moddate=filemtime("$datadir/$dt[7].dat"); $tektime=time();
if ($moddate<$tektime) {$vt1="checked"; $vt2="";} else {$vt2="checked"; $vt1="";}
if ($dt[8]=="closed") {$ct2="checked"; $ct1="";} else {$ct1="checked"; $ct2="";}

print "<form action='admin.php?event=rdtema&page=$page' method=post name=REPLIER1><table cellpadding=4 cellspacing=1 width=100% class=forumline><tr> <td class=catHead colspan=2 height=28><span class=cattitle>Редактирование Темы</span></td></tr>
<tr><td class=row1 align=right valign=top>Название темы</td>
<td class=row1 align=left valign=top><input type=text class=post value='$dt[3]' name=zag size=70>
<input type=radio name=status value=''$ct1/> <font color=blue><B>открыта</B></font>&nbsp;&nbsp; <input type=radio name=status value='closed'$ct2/> <font color=red><B>закрыта</B></font>
<input type=hidden name=rd value='$rd'>
<input type=hidden name=name value='$dt[0]'>
<input type=hidden name=who value='$dt[1]'>
<input type=hidden name=email value='$dt[2]'>
<input type=hidden name=oldzag value='$dt[3]'>
<input type=hidden name=msg value=\"$dt[4]\"><!-- кавычки в коде только двойные!-->
<input type=hidden name=datem value='$dt[5]'>
<input type=hidden name=timem value='$dt[6]'>
<input type=hidden name=timetk value='$dt[9]'></TD></TR>

<TR><TD class=row1 align=right>Объединить с другой темой?
</td><td class=row1>";

if ($maxi>0) { // БЫСТРЫЙ ПЕРЕХОД к теме
echo '<select name="temaplus"><option value="">Хотите объединить? Выберите тему!</option>';
$ii=$maxi+1; $cn=0; $i=0;
do {$dtt=explode("|", $lines[$i]); 
if ($dt[7]!=$dtt[7]) print" <option value='$dtt[7]'> - $dtt[3]</option>";
$i++;} while($i<$ii);
echo'</optgroup></select>
<input type=radio name=temakuda value="0"checked/> <font color=gray><B>в конец темы</B></font>&nbsp;&nbsp; <input type=radio name=temakuda value="1"/> <font color=black><B>В начало темы</B></font>
'; } // if($maxi>0)

print"</td></tr>
<tr><td class=row1 align=right valign=top>Переместить в другой раздел ?</TD><TD class=row1>
<select style='width=440' name='changefid'>
<option selected value='$fid'>Нет. Оставить в текущем</option><br><br>";

$mainlines=file("$datadir/mainforum.dat");
$mainsize=sizeof($mainlines); if($mainsize<1) exit("$back файл данных повреждён или у вас всего одна рубрика!");
$ii=count($mainlines); $cn=0; $i=0;
do {$mdt=explode("|", $mainlines[$i]);
if ($mdt[1]=="razdel") {if ($cn!=0) {echo'</optgroup>'; $cn=0;} $cn++; print"<optgroup label='$mdt[2]'>";} else {print" <option value='$mdt[0]' >|-$mdt[1]</option>";}
$i++; } while($i <$ii);
$s2=""; $s1="checked"; // поменяйте и будет по умолчанию переход в новую рубрику
print"</optgroup></select>

<input type=radio name=viptema value='0'$vt1/> <font color=gray><B>обычная тема</B></font>&nbsp;&nbsp; <input type=radio name=viptema value='1'$vt2/> <font color=black><B>VIP-тема</B></font>

</TD></TR><tr><td class=row1 align=right valign=top>После переноса вернуться в какой раздел ?</TD><TD class=row1>
<input type=radio name=goto value='0'$s1> в текущую рубрику &nbsp;&nbsp; <input type=radio name=goto value='1'$s2> туда куда переносим тему
</td></tr><tr><td colspan=2 class=row1>
<SCRIPT language=JavaScript>document.REPLIER1.zag.focus();</SCRIPT><center><input type=submit class=mainoption value='     Изменить     '></td></span></tr></table></form>";
}

} else {

echo '<table width=100% cellpadding=4 cellspacing=1 class=forumline><tr> <td class=catHead colspan=2 height=28><span class=cattitle>Добавление темы</span></td></tr>
<tr><td class=row1 align=right valign=top rowspan=2><span class=gensmall>';

if (!isset($wrfname)) echo'<B>Имя</B> и E-mail<BR>';

print "<B>Заголовок темы</B><BR><B>Сообщение</B></td><td class=row1 align=left valign=middle rowspan=2>
<form action=\"admin.php?event=addtopic&id=$fid\" method=post name=REPLIER>";
if (isset($wrfname)) {print "<input type=hidden name=name value='$wrfname' class=post><input type=hidden name=who value='да'>";}
else {echo '<input type=text value="" name=name size=23 class=post> <input type=text value="" name=email size=24 class=post><br>';}
print "
<input type=hidden name=maxzd value=$maxzd>
<input type=text class=post value='' name=zag size=50><br>
<textarea cols=100 rows=6 size=500 name=msg class=post></textarea><BR>
<BR><input type=submit class=mainoption value='     Добавить     '></td></form>
<SCRIPT language=JavaScript>document.REPLIER.msg.focus();</SCRIPT>
</span></tr></table><BR>";
}
// --------------

}








if (strlen($id)==7) { // выводим СООБЩЕНИЕ в текущей теме

// определяем есть ли информация в файле с данными
if (!is_file("$datadir/$id.dat")) exit("<BR><BR>$back. Извините, но такой темы на форуме не существует.<BR> Скорее всего её удалил администратор.");
$lines=file("$datadir/$id.dat"); $mitogo=count($lines); $i=$mitogo; $maxi=$i-1;

if ($mitogo>0) { $tblstyle="row1"; $printvote=null;

// Считываем СТАТИСТИКУ ВСЕХ УЧАСТНИКОВ
if (is_file("$datadir/userstat.dat")) {$ufile="$datadir/userstat.dat"; $ulines=file("$ufile"); $ui=count($ulines)-1;}

// Ищем тему в topicХХ.dat - проверяем не закрыта ли тема?
$msglines=file("$datadir/topic$fid.dat"); $mg=count($msglines); $closed="no";
do {$mg--; $mt=explode("|",$msglines[$mg]);
if ($mt[7]==$id and $mt[8]=="closed") $closed="yes";
} while($mg > "0");

$maxzd=null; // Уточняем статус по кол-ву ЗВЁЗД в теме
$imax=count($mainlines);
do {$imax--; $ddt=explode("|", $mainlines[$imax]); if ($ddt[0]==$fid) $maxzd=$ddt[12]; } while($imax>"0");
if (!ctype_digit($maxzd)) $maxzd=0;

// Исключаем ошибку вызова несуществующей страницы
if (!isset($_GET['page'])) $page=1; else {$page=$_GET['page']; if (!ctype_digit($page)) $page=1; if ($page<1) $page=1;}

// формируем переменную $pageinfo - со СПИСКОМ СТРАНИЦ
$pageinfo=""; $addpage=""; $maxpage=ceil(($maxi+1)/$qq); if ($page>$maxpage) $page=$maxpage;
$pageinfo.="<div align=center style='padding:6px;' class=pgbutt>Страницы: &nbsp;";
if ($page>3 and $maxpage>5) $pageinfo.="<a href=admin.php?id=$id>1</a> ... ";
$f1=$page+2; $f2=abs($page-2); if ($f2=="0") $f2=1; if ($page>=$maxpage-1) $f1=$maxpage;
if ($maxpage<=5) {$f1=$maxpage; $f2=1;}
for($i=$f2; $i<=$f1; $i++) { if ($page==$i) $pageinfo.="<B>$i</B> &nbsp;"; 
else {if ($i!=1) $addpage="&page=$i"; $pageinfo.="<a href=admin.php?id=$id$addpage>$i</a> &nbsp;";} }
if ($page<=$maxpage-3 and $maxpage>5) $pageinfo.="... <a href=admin.php?id=$id&page=$maxpage>$maxpage</a>";
$pageinfo.='</div>';

print"$pageinfo";

$fm=$qq*($page-1); if ($fm>$maxi) $fm=$maxi-$qq;
$lm=$fm+$qq; if ($lm>$maxi) $lm=$maxi+1;

do {$dt=explode("|", $lines[$fm]);

$fm++; $num=$maxi-$fm+2; $status=""; unset($youwr);

if (strlen($lines[$fm-1])>5) { // Если строчка потерялась в скрипте (пустая строка) - то просто её НЕ выводим

$msg=str_replace("[b]","<b>", $dt[4]);
$msg=str_replace("[/b]","</b>",$msg);
$msg=str_replace("[RB]","<font color=red><B>",$msg);
$msg=str_replace("[/RB]","</B></font>",$msg);
$msg=preg_replace("#\[Quote\]\s*(.*?)\s*\[/Quote\]#is","<br><B><U>Цитата:</U></B><table width=80% border=0 cellpadding=5 cellspacing=1 style='padding: 5px; margin: 1px'><tr><td class=quote>$1</td></tr></table>",$msg);
$msg=preg_replace("#\[Code\]\s*(.*?)\s*\[/Code\]#is"," <br><B><U>PHP код:</U></B><table width=80% border=0 cellpadding=5 cellspacing=1 style='padding: 5px; margin: 1px'><tr><td class=code >$1</td></tr></table>",$msg);

if ($smile==TRUE) {$i=count($smiles)-1; // заменяем текстовые смайлики на графические если разрешено
for($k=0; $k<$i; $k=$k+2) {$j=$k+1; $msg=str_replace("$smiles[$j]","<img src='smile/$smiles[$k].gif' border=0>",$msg);}}

$msg=str_replace("&lt;br&gt;","<br>",$msg);
$msg=preg_replace('#\[img(.*?)\](.+?)\[/img\]#','<img src="$2" border="0" $1>',$msg);

// Если разрешена публикация УРЛов
if ($liteurl==TRUE) $msg = preg_replace ("/([\s>\]]+)(http|https|ftp|goper):\/\/([a-zA-Z0-9\.\?&=\;\-\/_]+)([\W\s<\[]+)/", "\\1<a href=\"\\2://\\3\" target=\"_blank\">\\2://\\3</a>\\4", $msg);

// считываем в память данные по пользователю
if ($dt[1]=="да") {
$userlines=file("$datadir/usersdat.php"); $usercount=count($userlines); $ui=$usercount-1;
$iu=$usercount;
do {$iu--; $du=explode("|", $userlines[$iu]); if ($du[0]==$dt[0])
{ if (isset($du[12])) {$status=$du[13]; $reiting=$du[2]; $youavatar=$du[12]; $email=$du[3]; $icq=$du[7]; $site=$du[8]; $userpn=$iu;} $youwr=preg_replace("#(\[url=([^\]]+)\](.*?)\[/url\])|(http://(www.)?[0-9a-z\.-]+\.[a-z]{2,6}[0-9a-z/\?=&\._-]*)#","<a href=\"$4\" >$4</a> ",$du[11]);}
} while($iu > "0");
}

if ($tblstyle=="row1") $tblstyle="row2"; else $tblstyle="row1";

if (!isset($m1)) {
print "<table><tr><td><span class=nav>&nbsp;&nbsp;&nbsp;<a href=admin.php class=nav>$fname</a> <a href=admin.php?id=$fid class=nav>$frname</a> <a href='admin.php?id=$dt[7]' class=nav><strong>$dt[3]</strong></a></span></td></tr></table>";

echo'<table class=forumline width=100% cellspacing=1 cellpadding=3><tr>
<th class=thLeft width=150 height=26 nowrap=nowrap>Автор</th>
<th class=thRight nowrap=nowrap>Сообщение</th>'; $m1="1"; }

print"</tr><tr height=150><td class=$tblstyle valign=top><span class=name><BR><center>";


// Проверяем: это гость?
if (!isset($youwr)) {if (strlen($dt[2])>5) print "$dt[0] "; else print"$dt[0] ";
$kuda=$fm-1; print" <a href='javascript:%20x()' onclick=\"DoSmilie('[b]$dt[0][/b], ');\" class=nav>".chr(149)."</a><BR><br>
<form name='m$fm' method='post' action='tools.php?event=mailto' target='email' onclick=\"window.open('tools.php?event=mailto','email','width=600,height=350,left=100,top=100');return true;\"><input type=hidden name='email' value='$dt[2]'><input type=hidden name='name' value='$dt[0]'><input type=hidden name='id' value=''>
<input type=image src='$fskin/ico_pm.gif' alt='личное сообщение'></form><BR><small>$guest</small>";}


else {
$codename=urlencode($dt[0]);
print "<a name='m$fm' href='admin.php?event=profile&pname=$codename' class=nav>$dt[0]</a> <a href='javascript:%20x()' onclick=\"DoSmilie('[b]$dt[0][/b], ');\" class=nav>".chr(149)."</a><BR><BR><small>";
if (strlen($status)>2 & $dt[1]=="да" & isset($youwr)) print "$status"; else print"$users";
if (isset($reiting)) {if ($reiting>0) {echo'<BR>'; if (is_file("$fskin/star.gif")) {for ($ri=0;$ri<$reiting;$ri++) {print"<img src='$fskin/star.gif' border=0>";} } }}

if (isset($youavatar)) { if (is_file("avatars/$youavatar")) $avpr="$youavatar"; else $avpr="noavatar.gif";
print "<BR><BR><img src='avatars/$avpr'><BR> <!--
<a href='admin.php?event=profile&pname=$dt[0]'><img src='$fskin/profile.gif' alt='Профиль' border=0></a>
<a href='$site'><img src='$fskin/www.gif' alt='www' border=0></a><BR>
<a href='$icq'><img src='$fskin/icq.gif' alt='ICQ' border=0></a>
<form name='m$fm' method='post' action='tools.php?event=mailto' target='email' onclick=\"window.open('tools.php?event=mailto','email','width=600,height=350,left=100,top=100');return true;\"><input type=hidden name='email' value='$dt[3]'><input type=hidden name='name' value='$dt[0]'><input type=hidden name='id' value=''>
<input type=image src='$fskin/ico_pm.gif' alt='личное сообщение'></form>
-->";}
} // isset($youwr)

if (isset($youwr) and is_file("$datadir/userstat.dat")) { // ТОЛЬКО участники видят всю репутацию! ;-)
if (isset($ulines[$userpn])) {
if (strlen($ulines[$userpn])>5) {
$ddu=explode("|",$ulines[$userpn]);
print"</small></span><br>
<div style='PADDING-LEFT: 17px' align=left class=gensmall>Тем создано: $ddu[1]<br>
Сообщений: $ddu[2]<br>
Репутация: $ddu[3] <A href='#' onclick=\"window.open('tools.php?event=repa&name=$dt[0]&who=$userpn','repa','width=500,height=500,left=100,top=100')\">-+</A><br>
Предупреждения: $ddu[4]<br></span>"; }}}

if (!isset($dt[16])) $dt[16]=""; // временно, для совместимости с другими версиями
print "
<br><br>IP: $dt[16] <br><a href='admin.php?badip&ip_get=$dt[16]'><B><font color=red>БАН по IP</font></B></a><br>
</span></td><td class=$tblstyle width=100% height=28 valign=top><table width=100% height=100%><tr valign=center><td><span class=postbody>$msg</span>";


// БЛОК ГОЛОСОВАНИЯ - если есть то выводим !!!
if ($fm==1 and is_file("$datadir/$id-vote.dat")) { // БЛОК ПЕЧАТАЕМ ОДИН РАЗ
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
<TD align=right><table><tr><td width=10 bgcolor=#FF2244><B><a href='admin.php?fid=$fid&id=$id&vote=delete' title='УДАЛИТЬ ГОЛОСОВАНИЕ'>.X.</a></B></td></tr></table></TD><TR>
</TD></TR></TABLE>"; }} // КОНЕЦ БЛОКА ГОЛОСОВАНИЯ


echo'</td></tr><TR><TD>';

// Если ПРИКРЕПЛЁН ФАЙЛ к сообщению - то показываем значёк и ссылку на него или картинку
if (isset($dt[12])) { if ($dt[12]!="" and is_file("$filedir/$dt[13]")) {
$fsize=round($dt[14]/10.24)/100; print"<fieldset style=\"width:30%; color:#008000\"><legend>Прикреплён файл:</legend><a href='admin.php?fid=$fid&id=$id&deletefoto=$dt[13]'><font color=red><B>удалить фото</b></font></a><br><br>";
if (preg_match("/.(jpg|jpeg|bmp|gif|png)+$/is",$dt[13]))
print"<img border=0 src='$filedir/$dt[13]'>"; else 
print"<img border=0 src='$fskin/ico_file.gif'> <a href='$filedir/$dt[13]'>$dt[13]</a> ($fsize Кб.)</fieldset>"; }}

// печатаем подпись участника
if (isset($youwr)) {if (strlen($youwr)>3) {print "<tr><td valign=bottom><span class=postbody>--------------------------------------------------<BR><small>$youwr</small>";}}

print"</td></tr></table></td></tr><tr>
<td class=row3 valign=middle align=center ><span class=postdetails>
<table><tr><td width=10 bgcolor=#22FF44><B><a href='admin.php?id=$id&topicrd=$fm&page=$page#m$lm' title='РЕДАКТИРОВАТЬ'>.P.</a></B></td><td width=10 bgcolor=#FF2244><B><a href='admin.php?id=$id&topicxd=$fm&page=$page' title='УДАЛИТЬ'>.X.</a></B></td></tr></table>
<I>Сообщение # <B>$fm.</B></I></span></td>
<td class=row3 width=100% height=28 nowrap=nowrap><span class=postdetails>Отправлено: <b>$dt[5]</b> - $dt[6]</span></td>
</tr><tr><td class=spaceRow colspan=2 height=1><img src=\"$fskin/spacer.gif\" width=1 height=1></td>";

} // если строчка потерялась

} while($fm < $lm);

print"</tr></table> $pageinfo </span></td></tr></table>";



// Выбрана метка .P. - редактирование сообщения
if (isset($_GET['topicrd'])) { // выводим сообщение в форму
$topicrd=$_GET['topicrd']-1;
$lines=file("$datadir/$id.dat");
$dt=explode("|", $lines[$topicrd]);
$dt[4]=str_replace("<br>", "\r\n", $dt[4]);
$oldmsg=str_replace("'", ":kovichka:",$dt[4]); // шифруем символ '
print "
<form action=\"admin.php?event=addanswer&id=$id&topicrd=$topicrd\" method=post name=REPLIER>
<table cellpadding=3 cellspacing=1 width=100% class=forumline>
<tr><th class=thHead colspan=2 height=25><b>Сообщение</b></th></tr>
<tr><td class=row1 width=22% height=25><span class=gen><b>Имя
</b></span></td>
<td class=row2 width=78%> <span class=genmed>
<input type=hidden name=oldmsg value='$oldmsg'>
<input type=text value='$dt[0]' name=name size=20>&nbsp;
E-mail <input type=text value='$dt[2]' name=email size=26>&nbsp; 
<input type=hidden name=who value='$dt[1]'>Участник ? <B>$dt[1]"; 
if (strlen($dt[1])<1) echo'нет';

} else {

print "</B><form action=\"admin.php?event=addanswer&id=$id\" method=post name=REPLIER>
<input type=hidden name=maxzd value=$maxzd>
<input type=hidden name=id value='$dt[7]'>
<input type=hidden name=page value=$page>
<input type=hidden name=zag value=\"$dt[3]\">

<table cellpadding=3 cellspacing=1 width=100% class=forumline>
<tr><th class=thHead colspan=2 height=25><b>Сообщение</b></th></tr>
<tr><td class=row1 width=22% height=25><span class=gen><b>Имя ";


if (!isset($wrfname)) echo'и E-mail<BR>';

echo'</b></span></td><td class=row2 width=78%> <span class=genmed>';

if (!isset($wrfname)) echo'<input type=text name=name size=28 class=post> <input type=text name=email size=30 class=post>';
else print "<b>$wrfname</b><input type=hidden name=name value='$wrfname'><input type=hidden name=who value='да'>";
}


echo'</span></td></tr><tr>
<td class=row1 valign=top><span class=genmed><b>Сообщение</b><br><br>Для вставки имени, кликните на точку рядом с ним.<br><br>Смайлики:<br>
<table align=center width=100 height=70><tr><td valign=top>';

if ($smile==TRUE) {$i=count($smiles)-1;
for($k=0; $k<$i; $k=$k+2) {$j=$k+1; print"<A href='javascript:%20x()' onclick=\"DoSmilie(' $smiles[$j]');\"><img src='smile/$smiles[$k].gif' border=0></a> ";} }
print"<A href='javascript:%20x()' onclick=\"DoSmilie('[RB]  [/RB] ');\"><font color=red><B>RB</b></font></a>
<a name='add' href='#add' onclick=\"window.open('tools.php?event=moresmiles','smiles','width=250,height=300,left=50,top=150,toolbar=0,status=0,border=0,scrollbars=1')\">Ещё смайлы</a>
</tr></td></table></span></td>
<td class=row2 valign=top><span class=gen><table width=450><tr valign=middle><td><span class=genmed>
<input type=button class=button value=' B ' style='font-weight:bold; width: 30px' onclick=\"DoSmilie(' [b]  [/b] ');\">&nbsp;
<input type=button class=button value=' RB ' style='font-weight:bold; color:red' onclick=\"DoSmilie('[RB] [/RB]');\">&nbsp;
<INPUT type=button class=button value='Цитировать выделенное' style='width: 170px' onclick='REPLIER.msg.value += \"[Quote]\"+(window.getSelection?window.getSelection():document.selection.createRange().text)+\"[/Quote]\"'>&nbsp;
<input type=button class=button value=' Код ' onclick=\"DoSmilie(' [Code]  [/Code] ');\">&nbsp;
<input type=button class=button value=' IMG ' style='font-weight:bold; color:navy' onclick=\"DoSmilie('[img][/img]');\">&nbsp;
</span></td></tr><tr>";

if (isset($_GET['topicrd']))
{
print "
<td colspan=9><span class=gen><textarea name=msg cols=103 rows=10 class=post>$dt[4]</textarea></span></td>
<input type=hidden name=maxzd value=$maxzd>
<input type=hidden name=who value=$dt[1]>
<input type=hidden name=id value=$dt[7]>
<input type=hidden name=zag value=\"$dt[3]\">
<input type=hidden name=fdate value=$dt[5]>
<input type=hidden name=ftime value=$dt[6]>
<input type=hidden name=fnomer value=$topicrd>
<input type=hidden name=timetk value=$dt[9]>
<input type=hidden name=page value=$page>
</tr></table></span></td></tr>
<tr><td class=catBottom colspan=2 align=center height=28><input type=submit tabindex=5 class=mainoption value='Изменить и сохранить'>&nbsp;&nbsp;&nbsp;<input type=reset tabindex=6 class=mainoption value=' Очистить '></td>
</tr></table></form>";

} else {

echo'<td colspan=9><span class=gen><textarea name=msg cols=103 rows=10 class=post></textarea></span></td>
</tr></table></span></td></tr><tr>
<td class=catBottom colspan=2 align=center height=28><input type=submit tabindex=5 class=mainoption value=" Отправить ">&nbsp;&nbsp;&nbsp;<input type=reset tabindex=6 class=mainoption value=" Очистить "></td>
</tr></table></form>';


$newvote="<br>
<center><table align=center border=0 class=forumline><form action='admin.php?event=voteadd&id=$id' method=POST name=VOTE>
<tr><th class=thHead colspan=3 height=25><b>Cоздание/редактирование голосования</b></th></tr>";
$i=0; $j=1; do {

// Считываем имеющееся голосование
if (isset($vlines[$i])) $vdt=explode("|",$vlines[$i]); else {$vdt[0]=""; $vdt[1]="0";}

if ($i==0) {$newvote.="<tr><td class=row1><B>Название голосования:</B></td><td class=row1 colspan=2><input maxlength=100 type=text value='$vdt[0]' name=toper size=70 class=post></tr></td>";
} else {$newvote.="<TR>
<TD class=row$j align=right>$i ответ:</TD><TD class=row$j><input type=text value='$vdt[0]' maxlength=70 name='otv$i' class=post size=63></B></TD>
<TD class=row$j><input type=text value='$vdt[1]' name='kolvo$i' class=post maxlength=4 size=4></TD></TR>";}
$i++; $j++; if ($j>2) $j=1;
} while($i<11);
$newvote.="
<TR><td class=catBottom colspan=3 align=center height=28><input type=hidden name=golositogo value='$i'><input class=mainoption type=submit value='Создать голосование'></TD></TR></table></form>
<br><div align=center>* оставьте поля пустыми, если хотите создать голосование с меньшим кол-вом ответов </div></center>";

if ($page=="1") echo $newvote;
}}

} // else if event !=""
}
} // if (isset($_GET['id'])) - если есть $id





if (isset($_GET['event'])) {


// КОНФИГУРИРОВАНИЕ форума - выбор настроек
if ($_GET['event']=="configure") {

if (!isset($specblok1)) $specblok1="0";// временно так как ввёл новые переменные в config.php
if (!isset($specblok2)) $specblok2="0";// --//--
if (!isset($nosssilki)) $nosssilki="0";// --//--

if ($ktotut!=1) {exit("$back! Модераторам запрещено изменять настройки форума! Если нужно сменить пароль - обращайтесь к админу!");}

if ($sendmail==TRUE) {$s1="checked"; $s2="";} else {$s2="checked"; $s1="";}
if ($sendadmin==TRUE) {$sa1="checked"; $sa2="";} else {$sa2="checked"; $sa1="";}
if ($statistika==TRUE) {$st1="checked"; $st2="";} else {$st2="checked"; $st1="";}
if ($antispam==TRUE) {$as1="checked"; $as2="";} else {$as2="checked"; $as1="";}
if ($newmess==TRUE) {$n1="checked"; $n2="";} else {$n2="checked"; $n1="";}
if ($cangutema==TRUE) {$ct1="checked"; $ct2="";} else {$ct2="checked"; $ct1="";}
if ($cangumsg==TRUE) {$cm1="checked"; $cm2="";} else {$cm2="checked"; $cm1="";}
if ($useactkey==TRUE) {$u1="checked"; $u2="";} else {$u2="checked"; $u1="";}
if ($liteurl==TRUE) {$lu1="checked"; $lu2="";} else {$lu2="checked"; $lu1="";}
if ($nosssilki==TRUE) {$ns1="checked"; $ns2="";} else {$ns2="checked"; $ns1="";}
if ($canupfile==TRUE) {$cs1="checked"; $cs2="";} else {$cs2="checked"; $cs1="";}
if ($smile==TRUE) {$sm1="checked"; $sm2="";} else {$sm2="checked"; $sm1="";}

if ($specblok1==TRUE) {$sb1="checked"; $sb2="";} else {$sb2="checked"; $sb1="";}
if ($specblok2==TRUE) {$bs1="checked"; $bs2="";} else {$bs2="checked"; $bs1="";}

if ($stop==TRUE) {$sp1="checked"; $sp2="";} else {$sp2="checked"; $sp1="";}
if ($antimat==TRUE) {$am1="checked"; $am2="";} else {$am2="checked"; $am1="";}
if ($random_name==TRUE) {$rn1="checked"; $rn2="";} else {$rn2="checked"; $rn1="";}

print "<center><B>Конфигурирование</b></font>
<form action=admin.php?event=config method=post name=REPLIER>
<table width=900 cellpadding=2 cellspacing=1 class=forumline><tr> 
<th class=thCornerL height=25 nowrap=nowrap>Параметр</th>
<th class=thTop nowrap=nowrap>Значение</th></tr>
<tr><td class=row1>Название форума</td><td class=row1><input type=text value='$fname' name=fname class=post maxlength=50 size=50></tr></td>
<tr><td class=row2 valign=top>Описание<BR><B><small>использовать HTML-теги ЗАПРЕЩЕНО!</small></td><td class=row2><textarea cols=55 rows=6 size=700 class=post name=fdesription>$fdesription</textarea></tr></td>
<tr><td class=row1>Е-майл администратора</td><td class=row1><input type=text value='$adminemail' class=post name=newadminemail maxlength=40 size=25></tr></td>

<tr><td class=row2>Логин и пароль администратора (данные входа в админ.панель со 100% набором прав)*</td><td class=row1>Логин: <input name=adminname type=text value='$adminname'> Пароль: <input name=password type=hidden value='$password'><input class=post type=text value='скрыт' maxlength=10 name=newpassword size=15></td></tr>
<tr><td class=row2>Логин и пароль модератора (частичный набор прав)*</td><td class=row1>Логин: <input name=modername type=text value='$modername'> Пароль: <input name=moderpass type=hidden value='$moderpass'><input class=post type=text value='скрыт' maxlength=10 name=newmoderpass size=15></td></tr>

<tr><td class=row1><FONT COLOR=RED>Блокировка форума: отключить работу форума на добавление тем/сообщений?</FONT></td><td class=row1><input type=radio name=stop value=\"1\"$sp1/><B><font color=red> ДА </font>&nbsp;&nbsp; <input type=radio name=stop value=\"0\"$sp2/> <font color=gren>НЕТ</font></B></tr></td>
<tr><td class=row1>При загрузке файла генерировать ему имя случайным образом?</td><td class=row1><input type=radio name=random_name value=\"1\"$rn1/> да&nbsp;&nbsp; <input type=radio name=random_name value=\"0\"$rn2/> нет</tr></td>
<tr><td class=row1>Сколько очков репутации добавлять при <B>загрузке файла</B>, <B>добавление сообщения</B>, <B>добавлении темы</B>?</td><td class=row1><input type=text value='$repaaddfile' class=post name=repaaddfile maxlength=2 size=6> &nbsp; :: &nbsp; <input type=text value='$repaaddmsg' class=post name=repaaddmsg maxlength=2 size=6> &nbsp; :: &nbsp; <input type=text value='$repaaddtem' class=post name=repaaddtem maxlength=2 size=6></tr></td>

<tr><td class=row2>Включить отправку сообщений?</td><td class=row1><input type=radio name=sendmail value=\"1\"$s1/> да&nbsp;&nbsp; <input type=radio name=sendmail value=\"0\"$s2/> нет</tr></td>
<tr><td class=row1>Мылить админу сообщения о вновь зарегистрированных пользователях?</td><td class=row1><input type=radio name=sendadmin value=\"1\"$sa1/> да&nbsp;&nbsp; <input type=radio name=sendadmin value=\"0\"$sa2/> нет</tr></td>
<tr><td class=row2>Макс. длина имени / заголовка темы / сообщения</td><td class=row1><input type=text value='$maxname' class=post name=newmaxname maxlength=2 size=10> &nbsp;&nbsp; .:. &nbsp;&nbsp; <input type=text value='$maxzag' class=post name=maxzag maxlength=2 size=10> &nbsp;&nbsp; .:. &nbsp;&nbsp; <input type=text value='$maxmsg' class=post maxlength=4 name=newmaxmsg size=10></tr></td>
<tr><td class=row1>Задействовать АНТИМАТ?</td><td class=row1><input type=radio name=antimat value=\"1\"$am1/> да&nbsp;&nbsp; <input type=radio name=antimat value=\"0\"$am2/> нет</tr></td>
<tr><td class=row1>Задействовать АНТИСПАМ / длина кода</td><td class=row2><input type=radio name=antispam value=\"1\"$as1/> да&nbsp;&nbsp; <input type=radio name=antispam value=\"0\"$as2/> нет &nbsp;&nbsp; .:. &nbsp;&nbsp; <input type=text class=post value='$max_key' name=max_key size=4 maxlength=1> (от 1 до 9) цифр</td></tr>
<tr><td class=row2>Тем / Cообщений / Участников на страницу</td><td class=row2><input type=text value='$qqmain' class=post maxlength=2 name=newqqmain size=11> &nbsp; .:. &nbsp; <input type=text value='$qq' class=post maxlength=2 name=newqq size=11> &nbsp; .:. &nbsp; <input type=text value='$uq' maxlength=2 class=post name=uq size=11></tr></td>
<tr><td class=row1>Как называть участников НЕ зареганых / зареганых</td><td class=row2><input type=text value='$guest' class=post maxlength=25 name=newguest size=22> &nbsp;/ &nbsp;<input type=text value='$users' class=post maxlength=25 name=newusers size=22></tr></td>
<tr><td class=row2>Требовать активации через емайл при регистрации?</td><td class=row1><input type=radio name=useactkey value=\"1\"$u1/> да&nbsp;&nbsp; <input type=radio name=useactkey value=\"0\"$u2/> нет</tr></td>
<tr><td class=row1>Создавать темы / Оставлять сообщения гостям можно?</td><td class=row1>Т: <input type=radio name=cangutema value=\"1\"$ct1/> да&nbsp;&nbsp; <input type=radio name=cangutema value=\"0\"$ct2/> нет .:. С: <input type=radio name=cangumsg value=\"1\"$cm1/> да&nbsp;&nbsp; <input type=radio name=cangumsg value=\"0\"$cm2/> нет </tr></td>

<tr><td class=row1>Запретить гостям добавлять сообщения со ссылками?</td><td class=row1><input type=radio name=nosssilki value=\"1\"$ns1/> да&nbsp;&nbsp; <input type=radio name=nosssilki value=\"0\"$ns2/> нет</td></tr>
<tr><td class=row2>Делать ссылки в тексте <B>активными</B>?</td><td class=row1><input type=radio name=liteurl value=\"1\"$lu1/> да&nbsp;&nbsp; <input type=radio name=liteurl value=\"0\"$lu2/> нет</td></tr>

<tr><td class=row1>Включить / отключить графическеие смайлы?</td><td class=row1><input type=radio name=smile value=\"1\"$sm1/> включить &nbsp;&nbsp; <input type=radio name=smile value=\"0\"$sm2/> отключить</td></tr>
<tr><td class=row1>Смещение GMT относительно времени хостинга</td><td class=row1><input class=post type=text value='$deltahour' maxlength=2 name=deltahour size=15> (GMT + XX часов)</td></tr>

<tr><td class=row1>Включить БЛОК 15-и самых обсуждаемых тем?</td><td class=row1><input type=radio name=specblok1 value=\"1\"$sb1/> да&nbsp;&nbsp; <input type=radio name=specblok1 value=\"0\"$sb2/> нет</td></tr>
<tr><td class=row1>Включить БЛОК 10 самых активных пользователей?</td><td class=row1><input type=radio name=specblok2 value=\"1\"$bs1/> да&nbsp;&nbsp; <input type=radio name=specblok2 value=\"0\"$bs2/> нет</td></tr>
<tr><td class=row2>Показывать статистику на главной странице?</td><td class=row1><input type=radio name=statistika value=\"1\"$st1/> да&nbsp;&nbsp; <input type=radio name=statistika value=\"0\"$st2/> нет. (дни рождения, кол-во тем/сообщений, последние сообщ.)</tr></td>
<tr><td class=row1>Создавать файл с новыми сообщениями?</td><td class=row1><input type=radio name=newmess value=\"1\"$n1/> да&nbsp;&nbsp; <input type=radio name=newmess value=\"0\"$n2/> нет</tr></td>

<tr><td class=row2>Папка с данными форума</td><td class=row1><input type=text value='$datadir' class=post maxlength=20 name='datadir' size=10> &nbsp;&nbsp; По умолчанию - <B>./data</B></td></tr>
<tr><td class=row1>Максимальный размер аватара в байтах</td><td class=row1><input type=text value='$max_file_size' class=post maxlength=6 name='max_file_size' size=10></td></tr>
<tr><td class=row1>Разрешить загрузку файлов</td><td class=row2><input type=radio name=canupfile value=\"1\"$cs1/> да, только зарегистрированным &nbsp;&nbsp; <input type=radio name=canupfile value=\"0\"$cs2/> нет </td></tr>
<tr><td class=row2>Папка для загрузки файлов</td><td class=row1><input type=text value='$filedir' class=post maxlength=20 name='filedir' size=10> &nbsp;&nbsp; По умолчанию - <B>./load</B></td></tr>
<tr><td class=row1>Максимальный размер файла в байтах</td><td class=row1><input type=text value='$max_upfile_size' class=post maxlength=7 name='max_upfile_size' size=10></td></tr>

<tr><td class=row2>Скин форума</td><td class=row1><select class=input name=fskin>";

$path = '.'; // Путь до папки. '.' - текущая папка
if ($handle = opendir($path)) {
while (($file = readdir($handle)) !== false)
if (is_dir($file)) { 
$stroka=stristr($file, "images"); if (strlen($stroka)>"6") 
{print "$stroka - str $file <BR>";
$tskin=str_replace("images-", "Скин - ", $file);
if ($fskin==$file) $marker="selected"; else $marker="";
print"<option $marker value=\"$file\">$tskin</option>";}
}
closedir($handle); } else echo'Ошибка!';

echo'</select></td></tr>

<tr><td class=row1>Смайлы (изображение и код)<br> - меняйте как хотите ***</td><td class=row1><table width=300><TR><TD>';
if (isset($smiles) and $smile==TRUE) {$i=count($smiles);
for($k=0; $k<$i; $k=$k+2) {
$j=$k+1; if ($k!=($i-1) and is_file("smile/$smiles[$k].gif"))
print"<img src='smile/$smiles[$k].gif' border=0> <input type=hidden name=newsmiles[$k] value='$smiles[$k]'><input type=text value='$smiles[$j]' maxlength=15 name=newsmiles[$j] size=5> "; } }


echo'</td></tr></table>
</td></tr><tr><td class=row1 colspan=2><BR><center><input type=submit class=mainoption value="Сохранить конфигурацию"></form></td></tr></table>
<center><br>* Если хотите изменить пароль - сотрите слово <B>"скрыт"</B> и введите новый пароль.<br> Рекомендую использовать только английские буквы и/или цифры. У некоторых хостеров есть проблемы<br> с регинальнальными настройками и пароль, набранный на другом языке может сохранится некорректно.<br>';
}






// ПРОСМОТР ВСЕХ УЧАСТНИКОВ форума
if ($_GET['event']=="userwho") {
$t1="row1"; $t2="row2"; $error=0;
$userlines=file("$datadir/usersdat.php");
$ui=count($userlines)-1; $maxi=$ui; $first=0; $last=$ui+1;

$statlines=file("$datadir/userstat.dat"); $si=count($statlines)-1;

$bada="<center><font color=red><B>В файле статистики имеются ошибки! ПЕРЕСЧИТАЙТЕ статистику участников!!!</B></font></center><br>";

if ($si!=$ui) print"$bada";

if (isset($_GET['page'])) $page=$_GET['page']; else $page="1";
if (!ctype_digit($page)) $page=1; // защита
if ($page=="0") $page="1"; else $page=abs($page); 
$maxpage=ceil(($ui+1)/$uq); if ($page>$maxpage) $page=$maxpage;

// формируем переменную $pageinfo - со СПИСКОМ СТРАНИЦ
$pageinfo=""; $addpage=""; $maxpage=ceil(($maxi+1)/$uq); if ($page>$maxpage) $page=$maxpage;
$pageinfo.="<div style='padding:6px;' class=pgbutt>Страницы: &nbsp;";
if ($page>3 and $maxpage>5) $pageinfo.="<a href=admin.php?event=userwho>1</a> ... ";
$f1=$page+2; $f2=abs($page-2); if ($f2=="0") $f2=1; if ($page>=$maxpage-1) $f1=$maxpage;
if ($maxpage<=5) {$f1=$maxpage; $f2=1;}
for($i=$f2; $i<=$f1; $i++) { if ($page==$i) $pageinfo.="<B>$i</B> &nbsp;"; 
else {if ($i!=1) $addpage="&page=$i"; $pageinfo.="<a href=admin.php?event=userwho&page=$i>$i</a> &nbsp;";} }
if ($page<=$maxpage-3 and $maxpage>5) $pageinfo.="... <a href=admin.php?event=userwho&page=$maxpage>$maxpage</a>";
$pageinfo.='</div>';

$i=1+$uq*($page-1); if ($i>$ui) $i=$ui-$uq;
$lm=$i+$uq; if ($lm>$ui) $lm=$ui+1;

print"$pageinfo";
echo'<table width=100% valign=top cellpadding=0 cellspacing=0><TR><TD>

<table valign=top width=100% cellpadding=0 cellspacing=0 class=forumline><tr> 
<th class=thCornerL height=25 nowrap=nowrap>№</th>
<th class=thCornerL width=110>Имя</th>
<th class=thCornerR>Пол</th>
<th class=thTop>Дата рег-ии</th>
<th class=thTop>Емайл / Сменить пароль</th>
<th class=thTop>Тем</th>
<th class=thTop>Сообщ.</th>
<th class=thTop>Репутация</th>
<th class=thTop>Штрафы</th>
<th class=thTop>Статус / Изменить</th>
<th class=thTop>Звёзд</th></tr>';

$delblok="<FORM action='admin.php?usersdelete=$last&page=$page' method=POST name=delform>
<td colspan=8 class=$t1>
<table valign=top cellpadding=0 cellspacing=0 class=forumline width=25><th class=thCornerL>.X.</th>";

do {$tdt=explode("|",$userlines[$i]); $i++; $npp=$i-1;

if (isset($statlines[$i-1])) {$sdt=explode("|",$statlines[$i-1]);} else {$sdt[0]=""; $sdt[1]="-"; $sdt[2]="-"; $sdt[3]="-"; $sdt[4]="-";}
// Проверяем, если файл статистики повреждён - пишем сообщение о необходимости восстановить его
if ($sdt[0]!=$tdt[0]) {$error++; $sdt[1]="-"; $sdt[2]="-"; $sdt[3]="-"; $sdt[4]="-";}
if ($tdt[6]=="мужчина") $tdt[6]="<font color=blue>М</font>"; else $tdt[6]="<font color=red>Ж</font>";
if (strlen($tdt[13])<2) $tdt[13]=$users;

$delblok.="<TR height=35><td width=10 bgcolor=#FF6C6C><input type=checkbox name='del$npp' value=''"; if (isset($_GET['chekall'])) {$delblok.='CHECKED';} $delblok.="></td></TR>";
print"<tr height=35>
<td class=$t1>$npp</td>
<td class=$t1><B><a href=\"admin.php?event=profile&pname=$tdt[0]\">$tdt[0]</a></td>";
if (strlen($tdt[13])=="6" and ctype_digit($tdt[13])) {
print"<td class=$t1 colspan=9><B>[<a href='admin.php?event=activate&email=$tdt[3]&key=$tdt[13]&page=$page'>Активировать</a>]. Учётная запись не активирована с $tdt[4]. </B>
(емайл: <B>$tdt[3]</B> ключ: <B>$tdt[13]</B>)"; 
} else {

//ИМЯ_ЮЗЕРА|Тем|Сообщений|Репутация|Предупреждения Х/5|Когда последний раз меняли рейтинг в UNIX формате|||

$tdt[4]=str_replace(".20",".",$tdt[4]);
print"</td><td class=$t1 align=center><B>$tdt[6]</b></td><td class=$t1 align=center>$tdt[4]</td><td class=$t1><a href=\"mailto:$tdt[3]\">$tdt[3]</a>
<form action='admin.php?newuserpass&email=$tdt[3]' method=post><input type=text class=post name=newpass value='' size=7 maxlength=20><input type=submit name=submit value='Сменить пароль' class=mainoption></td></form>
<td class=$t1>$sdt[1]</TD>
<td class=$t1>$sdt[2]</TD>
<td class=$t1><form action='admin.php?newrepa&page=$page' method=post><input type=text class=post name=repa value='$sdt[3]' size=3 maxlength=4><input type=hidden name=usernum value='$i'><input type=submit name=submit value='OK' class=mainoption></td></form>
<td class=$t1 width=88 align=center><form action='admin.php?userstatus&page=$page' method=post><input type=hidden name=usernum value='$i'><input type=hidden name=status value='$sdt[4]'><input type=submit name=submit value='-1' style='width: 30px'>&nbsp; <B>$sdt[4]</B>&nbsp; <input type=submit name=submit value='+1' style='width: 30px'></TD></form>
<td class=$t1><form action='admin.php?newstatus=$i&page=$page' method=post><input type=text class=post name=status value='$tdt[13]' size=16 maxlength=20><input type=submit name=submit value='OK' class=mainoption></td></form>
<td class=$t1><form action='admin.php?newreiting=$i&page=$page' method=post><input type=text class=post name=reiting value='$tdt[2]' size=1 maxlength=1><input type=submit name=submit value='OK' class=mainoption></td></form>
</tr>";}

$t3=$t2; $t2=$t1; $t1=$t3;
} while ($i<$lm);

print"</table>
</TD><TD rowspan=20>


$delblok</table></TR></TABLE><br>
<div align=right><input type=hidden name=first value='$first'><input type=hidden name=last value='$last'><INPUT type=submit class=mainoption value='УДАЛИТЬ выбранных пользователей'></FORM>
&nbsp; <FORM action='admin.php?event=userwho&page=$page&chekall' method=POST name=delform><INPUT class=mainoption type=submit value='Пометить ВСЕХ'></FORM>
&nbsp; <FORM action='admin.php?event=userwho&page=$page' method=POST name=delform><INPUT class=mainoption type=submit value='СНЯТЬ пометку'></FORM></div>";

print "$pageinfo
<div align=right>Всего зарегистрировано участников - <B>$ui</B></div>
</TD></TR></TABLE><br>

Пересортировать участников по: 
<form action='admin.php?event=sortusers' method=post name=REPLIER>
<SELECT name=kaksort>
<OPTION selected value=1>Имени</OPTION>
<OPTION value=2>Кол-ву сообщений</OPTION>
<OPTION value=3>Кол-ву звёзд</OPTION>
<OPTION value=4>Репутации</OPTION>
<OPTION value=5>Дате регистрации</OPTION>
<OPTION value=6>Активности **</OPTION></SELECT>
<input type=submit class=mainoption value='     Пересортировать     '> &nbsp; (сортировать лучше когда с форумом никто из участников не работает)
<br><br>";


if ($error>0) print"$bada";

echo'

<B>Удалить тех, чьи учётные записи НЕ АКТИВИРОВАНЫ: <a href="?delalluser=yes" title="УДАЛИТЬ" onclick="return confirm(\'Будут удалены ВСЕ НЕ АКТИВИРОВАННЫЕ УЧЁТНЫЕ ЗАПИСИ! Удалить? Уверены?\')">Удалить</a>. После удаления нажмите "Пересчитать статистику участников".</B><br><br>

* Тем - Скрипт считает кол-во тем, созданных участником с момента регистрации/восстановления файла статистики после сбоя<br><br>
Сообщений - сколько участник оставил сообщений<br><br>
Репутация - "Авторитетность" пользователя. Максимум 9999 ед. Автоматически увеличивается на 1 при добавлении сообщения/темы<br><br>

Система штрафов ещё отлаживается. Будет доступна в следующей версии!!!
- ШТРАФЫ:<br>
0 - юзер может всё;<br>
1 - юзеру антифлуд увеличиваем до 60 секунд;<br>
2 - юзер не имеет права менять репу другим;<br>
3 - юзеру запрещаем создавать темы;<br>
4 - блокируем доступ к ответу в темах - только просмотр;<br>
5 - БАН на 1 месяц!<br><br>
** Активность - кол-во сообщений в сутки разделённая на кол-во дней с момента регистрации;
<br><BR>';
}
}




if (isset($_GET['event'])) { if ($_GET['event']=="blockip") { // - БЛОКИРОВКА по IP

$itogo=0;
if (is_file("$datadir/bad_ip.dat")) { $lines=file("$datadir/bad_ip.dat"); $i=count($lines); $itogo=$i;
if ($i>0) {

echo'<table width=100% border=0 cellpadding=1 cellspacing=0><TR><TD>
<table border=0 width=100% cellpadding=2 cellspacing=1 class=forumline><tr> 
<th class=thCornerL width=50 height=25 nowrap=nowrap>.X.</th>
<th class=thCornerL width=150>IP</th>
<th class=thCornerL >Формулировка</th>
</tr>';

do {$i--; $idt=explode("|", $lines[$i]);
  print"<TR bgcolor=#F7F7F7><td width=10 align=center><table><tr><td width=10 bgcolor=#FF2244><B><a href='admin.php?delip=$i'>.X.</a></B></td></tr></table></td><td>$idt[0]</td><td>$idt[1]</td></tr>";
} while($i > "0");
} else echo'<br><br><H2 align=center>Заблокированные IP-адреса отсутствуют</H2><br>';
} else echo'<br><br><H2 align=center>Заблокированные IP-адреса отсутствуют</H2><br>';
exit("</table><br><CENTER><form action='admin.php?badip' method=POST>
Добавь IP НЕдруга! &nbsp; <input type=text style='FONT-SIZE: 14px; WIDTH: 110px' maxlength=15 name=ip> Формулировка: <input type=text style='FONT-SIZE: 14px; WIDTH: 200px' maxlength=50 name=text> 
<input type=submit value=' добавить '></form><br><br>*вводите IP аккуратно, не ставьте лишних ноликов и всякий пробелов.
<br><BR>Всего заБАНено пользователей - <B>$itogo</B><BR><BR></td></tr></table>");}}














if (isset($_GET['event'])) {
if ($_GET['event'] =="profile") { // РЕДАКТИРОВАНИЕ ПРОФИЛЯ юзера

// функция используется для отображения аватаров
function get_dir($path = './', $mask = '*.php', $mode = GLOB_NOSORT) {
 if ( version_compare( phpversion(), '4.3.0', '>=' ) ) {if ( chdir($path) ) {$temp = glob($mask,$mode); return $temp;}}
return false;}

if (!isset($_GET['pname'])) exit("Попытка взлома.");
$pname=urldecode($_GET['pname']); // РАСКОДИРУЕМ имя пользователя, пришедшее из GET-запроса.
$lines=file("$datadir/usersdat.php");
$i = count($lines); $use="0";
do {$i--; $rdt=explode("|", $lines[$i]);

if (isset($rdt[1])) { // Если нет потерянных строк в скрипте (пустая строка)
if (strlen($rdt[13])=="6" and ctype_digit($rdt[13])) $rdt[13]="<B><font color=red>ожидание активации</font></B>";
if ($pname===$rdt[0]) {

$ufile="$datadir/userstat.dat"; $ulines=file("$ufile"); $ui=count($ulines)-1; $msgitogo=0;
for ($i=0;$i<=$ui;$i++) {$udt=explode("|",$ulines[$i]); $msgitogo=$msgitogo+$udt[2]; if ($udt[0]==$rdt[0]) {$msguser=$udt[2];}}
$msgaktiv=round(10000*$msguser/$msgitogo)/100;

$akt=explode(".",$rdt[4]);
$aktiv=mktime(0,0,0,$akt[1],$akt[0],$akt[2]); 
$tekdt=time(); $aktiv=round(($tekdt-$aktiv)/86400);
$aktiv=round(100*$msguser/$aktiv)/100;
if (strlen($rdt[13])<2) $rdt[13]=$users;

print "<center><br>
<table cellpadding=3 cellspacing=1 width=100% class=forumline>
<tr><th class=thHead colspan=2 height=25 valign=middle>Регистрационные данные ПОЛЬЗОВАТЕЛЯ $pname</th></tr>
<tr><td class=row2 colspan=2><span class=gensmall>Поля отмеченные * обязательны к заполнению, если не указано обратное</span></td></tr>
<tr><td class=row1 width=35%><span class=gen>Имя участника:</span></td><td class=row2><span class=nav>$rdt[0]</span></td></tr>
<tr><td class=row1><span class=gen>Дата регистрации:</span></td><td class=row2><span class=gen>$rdt[4]</td></tr>
<tr><td class=row1><span class=gen>Пол:</span><br></td><td class=row2><span class=gen>$rdt[6]</span><input type=hidden value='$rdt[6]' name=pol></td></tr>
<tr><td class=row1><span class=gen>Отправить личное сообщение на e-mail: </span><br></td><td class=row2><form method='post' action='tools.php?event=mailto' target='email' onclick=\"window.open('tools.php?event=mailto','email','width=600,height=350,left=100,top=100');return true;\"><input type=hidden name='email' value='$rdt[3]'><input type=hidden name='name' value='$rdt[0]'><input type=hidden name='id' value=''><input type=image src='$fskin/ico_pm.gif' alt='личное сообщение'></form></td></tr>
<tr><td class=row1><span class=gen>Написать персональное сообщение (сюда на форум):</span><br></td><td class=row2><form action='pm.php?id=$rdt[0]' method=POST name=citata><input type=image border=0 src='data-pm/pm.gif' alt='Отправить ПЕРСОНАЛЬНОЕ СООБЩЕНИЕ'></form></span></td></tr>
<tr><td class=row1><span class=gen>Активность:</span></td><td class=row2><span class=gen>Всего сообщений: <B>$msguser</b> [<B>$msgaktiv%</B> от общего числа / <B>$aktiv</B> сообщений в сутки]</span></td></tr>
<tr><td class=row1><span class=gen>Статус:</span></td><td class=row2><span class=gen>$rdt[13]</span></td></tr>

<form action='tools.php?event=reregist' name=creator method=post enctype=multipart/form-data>
<tr><td class=row1><span class=gen>Сменить пароль: *</span></td><td class=row2><input class=inputmenu type=text value='скрыт' maxlength=10 name=newpassword size=15><input type=hidden class=inputmenu value='$rdt[1]' name=pass>(если хотите сменить, то введите новый пароль, иначе оставьте как есть!)</td></tr>
<tr><td class=row1><span class=gen>Адрес e-mail: *</span><br><span class=gensmall>Введите существующий электронный адрес! Форум защищён от роботов-спамеров.</span></td><td class=row2><input type=text class=post style='width: 200px' value='$rdt[3]' name=email size=25 maxlength=50></td></tr>
<tr><td class=row1><span class=gen>День варенья:</span><br><span class=gensmall>Введите день рождения в формате: ДД.ММ.ГГГГГ, если не секрет.</span></td><td class=row2><input type=text name=dayx value='$rdt[5]' class=post style='width: 100px' size=10 maxlength=18></td></tr>
<tr><td class=row1><span class=gen>Номер в ICQ:</span><br><span class=gensmall>Введите номер ICQ, если он у Вас есть.</span></td><td class=row2><input type=text value='$rdt[7]' name=icq class=post style='width: 100px' size=10 maxlength=10></td></tr>
<tr><td class=row1><span class=gen>Домашняя страничка:</span><br></td><td class=row2><input type=text value='$rdt[8]' class=post style='width: 200px' name=www size=25 maxlength=70 value='http://' /></td></tr>
<tr><td class=row1><span class=gen>Откуда:</span><br><span class=gensmall>Введите место жительства (Страна, Область, Город).</span></td><td class=row2><input type=text class=post style='width: 250px' value='$rdt[9]' name=about size=25 maxlength=70></td></tr>
<tr><td class=row1><span class=gen>Интересы:</span><br><span class=gensmall>Вы можете написать о ваших интересах</span></td><td class=row2><input type=text class=post style='width: 300px' value='$rdt[10]' name=work size=35 maxlength=70></td></tr>
<tr><td class=row1><span class=gen>Подпись:</span><br><span class=gensmall>Введите Вашу подпись, не используйте HTML</span></td><td class=row2><input type=text class=post style='width: 400px' value='$rdt[11]' name=write size=35 maxlength=70></td></tr>
<tr><td class=row1><span class=gen>Аватар:</span><br><span class=gensmall></span></td><td class=row2>";
if (!is_file("avatars/$rdt[12]")) print"<img src='./avatars/noavatar.gif'>"; else print"<img src='./avatars/$rdt[12]'>";
print "<input type=hidden name=name value='$rdt[0]'><input type=hidden name=oldpass value='$rdt[1]'>
<input type=hidden name=file value=''><input type=hidden name=avatar value='$rdt[12]'>
</td></tr><tr><td class=catBottom colspan=2 align=center height=28><input type=submit name=submit value='Сохранить изменения' class=mainoption /></td>
</tr></table></form>"; $use="1"; $i=1;
}
} // if
} while($i > "1");

if ($use!="1") { // в БД такого ЮЗЕРА НЕТ
echo'<center><table width=600 height=300 class=forumline><tr><th class=thHead height=25 valign=middle>Пользователь НЕ ЗАРЕГИСТРИРОВАН</th></tr>
<tr><td class=row1 align=center><B>Уважаемый администратор!</B><BR><BR>Извините, но участник с таким - <B>логином на форуме не зарегистрирован.</B><BR><BR>
Скорее всего, <B>он был уже удалён или Вы перешли по ошибочной ссылке.</B>.<BR><BR>
<B>Посмотреть других участников</B> можно <B><a href="admin.php?event=who">здесь</a>.</B><br><br></TD></TR></TABLE>'; }
}
} // if (isset($_GET['event'])) {


















// МАССОВАЯ рассылка информации УЧАСТНИКам форума
if (isset($_GET['event'])) { if ($_GET['event']=="massmail") {

/*
echo'<table width=100% border=0 cellpadding=1 cellspacing=0><TR><TD>
<table border=0 width=100% cellpadding=2 cellspacing=1 class=forumline><tr> 
<th class=thCornerL height=25 nowrap=nowrap>№</th>
<th class=thCornerL width=110>Метка кому отправлять</th>
<th class=thCornerL width=110>Имя</th>
<th class=thTop>Емайл</th>
<th class=thTop>Тем</th>
<th class=thTop>Сообщений</th>
<th class=thTop>Репутация</th>
<th class=thTop>Статус / Изменить</th></tr></table>';
*/

print"<center><TABLE class=forumline cellPadding=2 cellSpacing=1 width=775>
<br><br><FORM action='admin.php?event=rassilochka' method=post>
<TBODY><TR><TD class=thTop align=middle colSpan=2>Введите параметры текста <B>отправляемого пользователю</B></TD></TR>

<TR bgColor=#ffffff><TD>&nbsp; Имя отправителя:<FONT color=#ff0000>*</FONT> <INPUT name=name value='Администратор форума ' style='FONT-SIZE: 14px; WIDTH: 240px'>
и E-mail:<FONT color=#ff0000>*</FONT> <INPUT name=email value='$adminemail' style='FONT-SIZE: 14px; WIDTH: 320px'></TD></TR>

<TR bgColor=#ffffff><TD>Получатель: &nbsp; НИК:<FONT color=#ff0000>*</FONT> и E-mail:<FONT color=#ff0000>*</FONT>";

echo'<SELECT name=userdata class=maxiinput><option value="">Выберите участника</option>\r\n';

// Блок считывает всех пользователей из файла
if (is_file("$datadir/usersdat.php")) $lines=file("$datadir/usersdat.php");
if (!isset($lines)) $datasize=0; else $datasize=sizeof($lines);
if ($datasize<=0) exit("$back. Проблемы с Базой пользователей, файл данных пуст.");
$imax=count($lines); $i="1";
do {$dt=explode("|", $lines[$i]);
print "<OPTION value=\"$i|$dt[0]|$dt[3]|\">$i - $dt[0] &lt;$dt[3]&gt;</OPTION>\r\n";
$i++; } while($i < $imax);

echo'</optgroup></SELECT></TD></TR>
<TR bgColor=#ffffff><TD>&nbsp; Сообщение:<FONT color=#ff0000>*</FONT><br>
<TEXTAREA name=msg style="FONT-SIZE: 14px; HEIGHT: 440px; WIDTH: 765px">
Здравствуйте, %name!\r\n
Вы являетесь зарегистрированным участником форума
%fname, установленного по адресу:
%furl.

Как администратор форума хочу Вам сообщить следующую новость:

СЮДА ВПИШИТЕ НОВОСТЬ, например:
- У нас на форуме с сегодняшнего дня идёт оживлённая дискуссия на тему________;
- У нас на форуме выложены ссылки на бесплатные фото супермоделей;
- На форуме выложены свежие ссылки на скрипт по php :-) в темах таких-то... и т.д. и т.п. 
Фантазируйте, короче ;-)

Зайти на наш форум Вы всегда можете по ссылке:
%furllogin
----------
С Уважением, администратор форума ВАСЯ ПУПКИН (здесь впишите своё имя/ник)
</TEXTAREA></TD></TR>
<TR><TD bgColor=#FFFFFF colspan=2><center><INPUT type=submit value=Отправить></TD></TR></TBODY></TABLE></FORM>
<br><br></center>

* Используйте макроподстановку:<br>
<LI><B>%name</B> - имя участника форума;</LI>
<LI><B>%fname</B> - название форума;</LI>
<LI><B>%furl</B> - URL-адрес форума;</LI>
<LI><B>%furllogin</B> - URL-адрес страницы входа;</LI>
'; }}



?><br>
<center><font size=-2><small>Powered by <a href="http://www.wr-script.ru" title="Скрипт форума" class="copyright">WR-Forum</a> Professional &copy; 1.9.9<br></small></font></center>
</body>
</html>
