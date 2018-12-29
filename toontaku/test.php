<?
/*

$a = new DateTime('20170212000000'); // 20120101 같은 포맷도 잘됨
echo $a;
$종료일 = new DateTime('2012-10-11');

// $차이 는 DateInterval 객체. var_dump() 찍어보면 대충 감이 옴.
$차이    = date_diff($시작일, $종료일);

echo $차이->days; // 284
*/

echo strtotime("-40 days");
echo "/";
echo strtotime("20170212000000");

?>