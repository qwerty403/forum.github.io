<? // WR-forum v 1.9.9  //  06.03.12 г.  //  Miha-ingener@yandex.ru
   // Модуль персональных сообщений (сообщений хранящихся внутри форума)

include "config.php";

$maxpmmsg="100000"; // Максимальное количество символов в персональном сообщении
$datapmdir="data-pm"; // Папка с данными перональных сообщений
$date=date("d.mmmmm.y"); $time=date("H:i");

$shapka="<html><head><META content='text/html; charset=windows-1251' http-equiv=Content-Type><title>Отправка / Просмотр ПС</title></head><body>";

$name=""; $flag="0";// Очищаем переменные

if (isset($_COOKIE['wrfcookies'])) { // ищем В КУКАХ wrfcookies чтобы вывести ИМЯ
$wrfc=$_COOKIE['wrfcookies']; $wrfc=htmlspecialchars($wrfc); $wrfc=stripslashes($wrfc); 
$wrfc=explode("|", $wrfc); $wrfname=$wrfc[0]; $wrfpass=$wrfc[1]; $name=urlencode($wrfname); $name=strtolower($name);

// ФАЗА 2: Сверяем логин и пароль в базе с тем, что у нас хранится в КУКАХ
$lines=file("$datadir/usersdat.php"); $maxi=count($lines); $i="1";
do {$dt=explode("|", $lines[$i]); $i++;
$dt[0]=strtolower($dt[0]); $pass=$wrfpass;
if ($dt[0]===$name and $dt[1]===$pass) {$flag=1; $i=$maxi;} // ЕСЛИ нашли юзера, значит последний раз цикл работает
} while($i < $maxi);
} else echo'$shapka Система внутренних сообщений работает только для ЗАРЕГИСТРИРОВАННЫХ ПОЛЬЗОВАТЕЛЕЙ!'; // ЕСЛИ есть КУКИ

if ($flag===1) { // ПОЛЬЗОВАТЕЛЬ ПРОШЁЛ КОНТРОЛЬ ДОСТОВЕРНОСТИ УЧЁТНОЙ ЗАПИСИ

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


if (isset($_GET['id'])) { // Если есть ИМЯ ПОЛЬЗОВАТЕЛЯ
$id=replacer($_GET['id']);
$id=strtolower($id); // переводим все ники в нижний регистр
if (is_file("$datapmdir/$id.dat")) { $linesn=file("$datapmdir/$id.dat"); $in=count($linesn); if ($in > 500) exit("$back <B>Максимальное количество сообщение достигнуто/B>! Удалите свои сообщения или попросите пользователя, которому Вы отправляете сообщение почистить свой ящик!</center>"); }



if(isset($_GET['deletemsg'])) { // Блок УДАЛЕНИЯ выбранного СООБЩЕНИЯ
$num=replacer($_GET['deletemsg']); if ($num=="" or strlen($num)<5) exit("$shapka Ошибка, выбирите сообщение для удаления, либо ошибка скрипта!");
if (is_file("$datapmdir/$id.dat")) {
$file=file("$datapmdir/$id.dat");
$fp=fopen("$datapmdir/$id.dat","w");
flock ($fp,LOCK_EX);
for ($i=0;$i< sizeof($file);$i++) {$dt=explode("|",$file[$i]); if ($dt[2]==$num) unset($file[$i]);}
fputs($fp, implode("",$file));
flock ($fp,LOCK_UN);
fclose($fp); } // if is_file
Header("Location: pm.php?readpm&id=$id"); exit; }



if (isset($_GET['alldelete'])) { // ОЧИСТКА ЯЩИКА
if ($id==$name & is_file("$datapmdir/$id.dat")) { unlink ("$datapmdir/$id.dat"); print"$shapka <p align=center><b>Личные сообщения удалены!<br>Вы можете перейти на главную страницу форума <a href='index.php'>по этой ссылке</a></b></p>";}
else exit("$shapka $back У ВАС отсутствуют сообщения! Ваша папка ПУСТА."); }



if (isset($_GET['sendpm'])) { // ОТПРАВКА СООБЩЕНИЯ
print"$shapka <center><br><br><table width=33%><tr><td align=center><B>Сообщение отправлено!<br></B><br> Вы можете закрыть это окно!
Либо перейти в папку с Вашими входящими письмами по этой ссылке:<br><a href='pm.php?readpm&id=$name'>pm.php?readpm&id=$name</a>.
<br>Либо перейти на главную страницу форума <a href='index.php'>по этой ссылке</a>
</td></tr></table>"; exit; }



if (isset($_GET['readpm'])) { // ПРОСМОТР СООБЩЕНИЙ

if (is_file("$datapmdir/$id.dat") & $id===strtolower($name)) {
$rlines=file("$datapmdir/$id.dat"); $ri=count($rlines); $key="0";
if ($ri>0) { // Если файл не пуст

print"<body link=#48629D vlink=#48629D alink=#7688B6><div align='center'>
<table width='70%' cellpadding=3 cellspacing=5><tr bgcolor=#E2F1FC><td colspan=3><p align='center'><b>Личные сообщения [Входящие: $ri шт.]</b></td></tr>
<tr><td align=center width=150><b>Отправитель</b></td><td align=center><b>Сообщение</b></td><td><B>.X.</B></td></tr>";

do {$ri--; $edt=explode("|",$rlines[$ri]);
$data=date("d.m.y H:i",$edt[2]);
$edt[1]=replacer($edt[1]); $edt[0]=replacer($edt[0]);
$edt[1]=str_replace("&lt;br&gt;",'<BR>',$edt[1]);

if ($key==0) {$cvet="#E2F1FC"; $key=1;} else {$cvet="#F1F9FE"; $key=0;}
print"<tr bgcolor=$cvet><td height=70 align=center valign=top>
<p align=left><b><a href='tools.php?event=profile&pname=$edt[0]'><font color='#000000'>$edt[0]</a></font></b><br><br>
<font color=#777777 size=-1><b>$data</b></font></td>
<td align=justify valign=top><p align=justify><font face=verdana size=2 color=#000000>&nbsp;$edt[1]</font>
<div align=right><a href='pm.php?id=$edt[0]'>Написать ответ</a></div></td>
<td width=10 bgcolor=#FFD2D9><B>
<a href='pm.php?id=$id&deletemsg=$edt[2]' title='УДАЛИТЬ' onclick=\"return confirm('Сообщение будет удалено. Удалить? Уверены?')\" >.X.</a>
</TD></tr>";
} while($ri>0);
echo"</table><br><B><a href='pm.php?alldelete&id=$name' onclick=\"return confirm('Будут удалены ВСЕ СООБЩЕНИЯ! Удалить? Уверены?')\">Удалить все сообщения</a></B>";
} else {echo'<center>Ваша папка ПУСТА</center>';} // if ($ri>0)
} } // isset($readpm)




if(isset($_GET['savepm'])) { // СОХРАНЕНИЕ сообщения

// Считываем всех пользователей, ищем того, которому адресовано сообщение
$lines=file("$datadir/userstat.dat"); $maxi=count($lines); $i="0";
do {$dt=explode("|", $lines[$i]); $i++;
$dt[0]=strtolower($dt[0]);
//$id==strtolower($id);
if ($dt[0]===$id) { // ЕСЛИ нашли юзера, которому адресовано сообщение, то пишем его
$i=$maxi; // Нашли, значит последний раз цикл работает
$msg=replacer($_POST['msg']); $msg=str_replace("|","I",$msg);
if ($msg=="" || strlen($msg)>$maxpmmsg) exit("$shapka $back Ваше <B>сообщение пустое или превышает $maxpmmsg символов.</B></center>");
$day=time(); $text="$name|$msg|$day|";
$fp=fopen("$datapmdir/$id.dat","a+");
flock ($fp,LOCK_EX);
fputs($fp,"$text\r\n");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
} // if ($dt[0]===$id) если нашли юзера
} while($i < $maxi);

exit("<script language='Javascript'>function reload() {location = \"pm.php?sendpm&id=$id\"}; setTimeout('reload()', 800);</script>");
}




if ($name!=$id) { // Если нет никакого вывода и ЧТОБЫ самому себе письма не отправлять!
print"$shapka <FORM action='pm.php?savepm&id=$id' method=post name=addForm><center>
<table width='700' cellpadding=3 cellspacing=5><tr bgcolor=#E2F1FC><td colspan=3 align=center><h3>Отправка персонального сообщения для $id*</h3></td></tr>
<TR bgcolor=#F1F9FE><td><b>Отправитель:</b></td><td><b>Вы</b></td></tr>
<tr bgcolor=#E2F1FC><td><b>Кому:</b></td><td><b>$id</b></td></tr>
<tr bgcolor=#F1F9FE><td><b>Текст сообщения:</b></td><td><textarea name=msg rows='15' cols='60'></textarea></td></tr>
</table>
<br><input type='submit' value='Отправить сообщение'></form><br><br>
* Сообщение будет сохранено здесь. на форуме. Когда пользователь $id<br>
зайдёт на форум, то увидит новое сообщение в своём ящике.";}


} else echo'Отсутствует обязательный ключ id=ИМЯ_ПОЛЬЗОВАТЕЛЯ_КОМУ_БУДЕТ_ОТПРАВЛЕНО_СООБЩЕНИЕ'; //if (isset($_GET['id']))
} // if ($flag==1)

?>
</body></html>
