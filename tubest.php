<? // WR-forum v 1.9.9  //  06.03.12 �.  //  Miha-ingener@yandex.ru

if ($specblok1==TRUE) { // ���������� ���� 15-� ����� ����������� ���

function prcmp2 ($a, $b) {if ($a==$b) return 0; if ($a>$b) return -1; return 1;} // ������� ����������

if (!isset($datadir)) exit;
$timetek=time(); $timefile=0;
if(is_file("$datadir/best.dat")) $timefile=filemtime("$datadir/best.dat"); // ��������� ���� �������� ����� best.dat
$timer=$timetek-$timefile; // ������ ������� ������ ������� (� ��������) 

if ($timer>86400) { // ���� ������� 24-� ����� �����, �� ��������� ���� �� ������ ����� ����������� ���, 

// ������ �������� �����: ������ �� ���������� � ����� ������ � ��������� �����
$i=0;

if ($handle=opendir($datadir)) { while (($file=readdir($handle))!==FALSE)
if (!is_dir($file)) { // ���� �� ����������
$id=str_replace(".dat","",$file);
if (ctype_digit($id)) // ���� ����
  {$lines=file("$datadir/$file");
   $itogo=count($lines);
   if ($itogo<10) $itogo="0$itogo";
   if ($itogo<100) $itogo="0$itogo";
   $massiv[$i]="$itogo|$file|";
   $i++;
} } }
usort($massiv,"prcmp2"); // ��������� ��� �� �����������

$i=0; $maxi=15; if ($maxi>count($massiv)) $maxi=count($massiv);

$text="<br><table width=100% cellpadding=3 cellspacing=1 class=forumline>
<tr><td class=catHead colspan=2 height=28><span class=cattitle>����� ����������� ���� ($maxi)</span></td></tr><tr>
<td class=row1 align=center valign=middle rowspan=2><img src=\"$fskin/whosonline.gif\"></td>
<td class=row1 align=left width=95%><span class=gensmall>";

do {
$dt=explode("|",$massiv[$i]);
$lines=file("$datadir/$dt[1]"); $max=$dt[0]-1;
$tdt=explode("|",$lines[$max]);
if ($dt[0]>$qq) $page=ceil($dt[0]/$qq); else $page=1;
if ($page!=1) $addpage="&page=$page"; else $addpage="";
$tdt[5]=str_replace('.20','.',$tdt[5]); $tdt[6]=substr($tdt[6],0,5);
$text.="$tdt[5] $tdt[6] [<B>$dt[0] �����.</B>] <B><a href='index.php?id=$tdt[7]$addpage#m$dt[0]'>$tdt[3]</a></B> ��������� - $tdt[0]<BR>\r\n";
$i++; } while($i<$maxi);
$text.="</span><br>
* ���� ������������ $date � $time</td></tr></table>";

// ��������� ������ � ���� best.dat
$fp=fopen("$datadir/best.dat","w");
flock ($fp,LOCK_EX);
fputs($fp,"$text");
fflush ($fp);
flock ($fp,LOCK_UN);
fclose($fp);
// ����� �������� �����

$msg=file_get_contents("$datadir/best.dat"); print"$msg";

} else { // ����� ������� ������ �� ����� �����

$msg=file_get_contents("$datadir/best.dat"); // ��������� ���������� ����� best.dat � ������ � ������� ���
print"$msg";
} // if ($timer>86400)

} // if ($specblok1==TRUE)




if ($specblok2==TRUE) { // ���������� ���� 10 ����� �������� �������������


// ������ ������ ���������� �� ���������� ���������

//���_�����|���|���������|���������|�������������� �/5|����� ��������� ��� ������ ������� � UNIX �������|||
$lines=file("$datadir/userstat.dat"); $i=count($lines); $maxi=$i;

if ($i>3) { // ���� ���������������� ����� 2-� ����������

do {$i--; $dt=explode("|",$lines[$i]);
if ($dt[2]<10) $dt[2]="0$dt[2]"; if ($dt[2]<100) $dt[2]="0$dt[2]"; if ($dt[2]<1000) $dt[2]="0$dt[2]";
if ($dt[3]<10) $dt[3]="0$dt[3]"; if ($dt[3]<100) $dt[3]="0$dt[3]"; if ($dt[3]<1000) $dt[3]="0$dt[3]";
$record1[$i]="$dt[2]|$dt[1]|$dt[0]|";
$record2[$i]="$dt[3]|$dt[1]|$dt[0]|";
} while($i>0);
sort($record1); sort($record2);
//print"<PRE>"; print_r($record);

$text="<br><table width=100% cellpadding=3 cellspacing=1 class=forumline>
<tr><td class=catHead colspan=2 height=28><span class=cattitle>����� �������� ��������� (15)</span></td></tr><tr>
<td class=row1 align=center valign=middle rowspan=2><img src=\"$fskin/whosonline.gif\"></td>
<td class=row1 align=left width=95%><span class=gensmall>
<table width=500><TR><TD>�� ���������� ���������:<br><br>";
$text2="</TD><TD>�� ���������:<br><br>";
if ($maxi>15) { // ���� � ��� ������ 15 �������������
$max=$maxi-15; $k=$maxi;
do {$k--; $dt=explode("|",$record1[$k]);
$dtt=explode("|",$record2[$k]);
$codename2=urlencode($dtt[2]);
$codename=urlencode($dt[2]);
if (!isset($wrfname)) $text2.="<B>$dtt[2]</B>"; else $text2.="<B><a href='tools.php?event=profile&pname=$codename2'>$dtt[2]</a></B>";
$text2.=" - $dtt[0]<br>";

if (!isset($wrfname)) $text.="<B>$dt[2]</B>"; else $text.="<B><a href='tools.php?event=profile&pname=$codename'>$dt[2]</a></B>";
$text.=" - $dt[0]<br>";
} while($k>$max); // if-�
} // if ($maxi>15)

print"$text $text2
</td></tr></table>
</td></tr></table>";

} // if ($i>3)

} // if ($specblok2==TRUE)

?>
