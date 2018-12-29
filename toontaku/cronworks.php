<?
	include "../include/db_ok.php";

	fn_sql("DELETE FROM TB_LOG01 WHERE TIMESTAMP  < DATE_FORMAT(ADDDATE(NOW(), -14),'%Y%m%d%H%i%s')"); // 로그저장 2주
	fn_sql("DELETE FROM TB_WT005 WHERE LST_UPD_DH < DATE_FORMAT(ADDDATE(NOW(), -60),'%Y%m%d%H%i%s')"); // 내가보는 웹툰 저장 2달

 	$result = fn_sql(
	"SELECT '24H' AS GUBUN, COUNT(1) AS CNT FROM TB_WT006
 WHERE LST_UPD_DH > DATE_FORMAT(SUBTIME(NOW(), '24:00'), '%Y%m%d%H%i%s') UNION ALL
SELECT '72H' AS GUBUN, COUNT(1) AS CNT FROM TB_WT006
 WHERE LST_UPD_DH > DATE_FORMAT(SUBTIME(NOW(), '72:00'), '%Y%m%d%H%i%s') UNION ALL
SELECT '일주일' AS GUBUN, COUNT(1) AS CNT FROM TB_WT006
 WHERE LST_UPD_DH > DATE_FORMAT(SUBTIME(NOW(), '168:00'), '%Y%m%d%H%i%s')");

	$msg1 = $_SERVER["HTTP_HOST"]."\n방문자 통계\n";
	while ( $row = mysqli_fetch_array($result) ) {
		$msg1 .= $row['GUBUN']." : ".$row['CNT']."\n";
	}

 
	$result = fn_sql(
	" SELECT SITE
     , MAX(LST_UPD_DH) AS LST_UPD_DH
     , TIMESTAMPDIFF(HOUR, MAX(LST_UPD_DH),NOW()) AS LST_UPD_DH1
     , MAX(FST_INS_DH) AS FST_INS_DH 
     , datediff (NOW(), MAX(FST_INS_DH)) AS FST_INS_DH1
  FROM TB_WT001
 WHERE SITE != ''
 GROUP BY SITE
 ORDER BY LST_UPD_DH DESC");

	$msg1 .= "\nUPDATE (업뎃/추가)\n";
	while ( $row = mysqli_fetch_array($result) ) {
		$msg1 .= $row['SITE']." : ".$row['LST_UPD_DH1']."시간전 / ".$row['FST_INS_DH1']."일전\n";
	}
	
	
		
	//define('TOKEN_KEY','114864593:AAFoLCPVdfLV5TIu2ZO_IvECAIT7eKUbX-c/');
	define('TOKEN_KEY','164152133:AAF6fmt-vGDcGkjTtWtrbaM6EuhbtfQ4I_U/');
	define('BASE_URL', 'https://api.telegram.org/bot'.TOKEN_KEY);

	$rest = curl_init();
	curl_setopt($rest, CURLOPT_URL, BASE_URL."sendMessage?chat_id=159284966&text=".urlencode($msg1));
	curl_setopt($rest, CURLOPT_RETURNTRANSFER, true);
	$Result = curl_exec($rest);
	curl_close($rest);

?>