<?
/*

$a = new DateTime('20170212000000'); // 20120101 ���� ���˵� �ߵ�
echo $a;
$������ = new DateTime('2012-10-11');

// $���� �� DateInterval ��ü. var_dump() ���� ���� ���� ��.
$����    = date_diff($������, $������);

echo $����->days; // 284
*/

echo strtotime("-40 days");
echo "/";
echo strtotime("20170212000000");

?>