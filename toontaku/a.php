<?
/*
	TB_WT001 웹툰 정보
	TB_WT002 사이트 설정
	TB_WT003 웹툰당 회차정보
	TB_WT004 시스템 설정
	TB_WT005 내가보는웹툰 목록 저장
	TB_WT006 사용자 정보
	TB_LOG01 디버깅로그

  트래픽 감소를 위해 컬럼 단축
	A	CID
	B	COMP_YN
	C	ID_SEQ
	D	IMG_VIEWER
	E	LINK_CODE
	F	LST_UPD_DH / 이 컬럼은 LST_UPD_DH - '$vLstUpdDh' AS LST_UPD_DH 이렇게 보낸다. 트래픽 감소를 위해.(로컬에서 vParam값을 더할것이다.)
	G	MAX_NO
	H	NAME
	I	SEL_MODE
	J	SET_CONT
	K	SET_ID
	L	SET_VALUE
	M	SITE
	N	SORT
	O	THUMB_COMN
	P	THUMB_NAIL
	Q	USE_YN
	R	CNT
	S	ARTIST 작가명 ks20151125
	T	SELL_YN 유료여부 ks20151125
	U	ORG_UPD_DH 웹서버의 업데이트 일자 ks20151125
*/
	include "../include/db_ok.php";

	$vMode     = $_REQUEST["pMode"]; // 모드
	$vAppVer   = addslashes($_REQUEST["pAppVer"]); // 앱버전
	$vParam    = addslashes($_REQUEST["pParam"]); // 파라메터
	$vLstUpdDh = addslashes($_REQUEST["pLstUpdDh"]); // 최종업데이트
	$vMyNm     = fn_giveMeMyName($_REQUEST["pMyNm"]); // 기기고유값

	$vLog = true;
	if ( $vLog == true ) logDB($vMyNm, "$vMode,$vAppVer,$vLstUpdDh,$vParam");

	if ( $vAppVer == "" ) {
		// 파라메터 정리 ks20150112
		if ( $vLstUpdDh == "" ) $vLstUpdDh = $vParam; // 구버전 호완용
		if ( strpos($vParam, "|") !== false ) { 
			logDB($vMyNm, "old version [$vLstUpdDh][$vParam]");
			$vParamArr = explode("|", $vParam);
			$vLstUpdDh = $vParamArr[0];
			$vParam = $vParamArr[1];
			if ( $vMode == "3" ) {
				$vLstUpdDh = $vParamArr[1];
				$vParam = $vParamArr[0];
			}
		}
	}

	if ( $vMode == "V0" ) {  // 내구독목록에 있는 툰만 업데이트
		fn_mode_V0();
	} else if ( $vMode == "V1" ) { // 사이트별 웹툰 목록 update // 사이트별 추가/변경 웹툰	
		fn_mode_V1();
	} else if ( $vMode == "V2" ) { // 웹툰인서트,완결업데이트에 관한전부
		fn_mode_V2();
	} else if ( $vMode == "2"  ) { // 사이트 목록
		fn_mode_2();
	} else if ( $vMode == "3"  ) { // 웹툰 회차 콤보 $cid	
		fn_mode_3();
	} else if ( $vMode == "4"  ) { // 세팅항목
		fn_mode_4();
	} else if ( $vMode == "6"  ) { // 추천목록
		fn_mode_6();
	} else if ( $vMode == "VB" ) { // 백업목록조회 ks20151225
		fn_mode_VB();
	} else if ( $vMode == "backUpMakingXml" ) {
		fn_mode_backUpMakingXml();
	}
	// 이하 모드는 버전업에서 안쓴다.
	else if ( $vMode == "1" ) fn_mode_1(); // 사이트별 웹툰 목록 update
	else if ( $vMode == "1-1" ) fn_mode_1_1(); // 사이트별 웹툰 목록 insert. use_yn,comp_yn이 바뀐경우도 발동
	mysqli_close($conn); // 디비 접속 끊기
	////////////////////
	// 끝.
	////////////////////
	
	function fn_mode_V0() { // 내구독목록에 있는 툰만 업데이트
		global $conn,$vMode,$vMyNm,$vParam,$vLstUpdDh,$vAppVer;

		// 내가 보는 웹툰 업데이트 ks20141206
		$vIdSeqLstViewNo = split("_" ,$vParam); // ID_SEQ/ID_SEQ/ID_SEQ/_LST_VIEW_NO/LST_VIEW_NO/LST_VIEW_NO/... 
		$vIdSeq = split("/" , $vIdSeqLstViewNo[0]);

		if ( sizeof($vIdSeq) == 0 ) return;
		$vMyNm1 = fn_divMyNmArr($vMyNm);
		$vLstViewNo = split("/" , $vIdSeqLstViewNo[1]);
		$vIdSeqRep = "'".str_replace("/", "','", $vIdSeqLstViewNo[0])."'"; // ID_SEQ/ID_SEQ/ID_SEQ/... 

		$result = mysqli_query($conn,
"SELECT ID_SEQ, MAX_NO, THUMB_NAIL, LST_UPD_DH - '$vLstUpdDh' AS LST_UPD_DH
   FROM TB_WT001 A WHERE EXISTS (SELECT 1 FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ)
	  AND LST_UPD_DH + 0 > '$vLstUpdDh' + 0 AND NAME != ''
	  AND ID_SEQ IN ($vIdSeqRep) ");

		while ( $row = mysqli_fetch_array($result) ) {
			echo "<C>".$row['ID_SEQ']."</C>";
			echo "<G>".$row['MAX_NO']."</G>";
			echo "<P>".fn_setCDATA($row['THUMB_NAIL'])."</P>";
			echo "<F>".$row['LST_UPD_DH']."</F>";
		}

		// 유료!!! vip면 내가 보는 웹툰 동기화 ks20151211
		if ( 1 != 1 ) { 
			$vQuery = " SELECT '' AS ID_SEQ, '' AS LST_VIEW_NO ";
			for ( $i = 0; $i < sizeof($vIdSeq); $i++ ) {
				if ( $vIdSeq[$i] == "" ) continue;
				$vQuery .= " UNION ALL SELECT '$vIdSeq[$i]', '$vLstViewNo[$i]' ";
			}

			$result = mysqli_query($conn,
"SELECT A.ID_SEQ, A.LST_VIEW_NO, '' AS THUMB_NAIL, '' AS LST_UPD_DH
   FROM TB_WT005 A, ($vQuery) B
  WHERE A.ID_SEQ = B.ID_SEQ AND A.LST_VIEW_NO != B.LST_VIEW_NO
    AND B.LST_VIEW_NO != '-1'
    AND A.USER_SEQ = (SELECT USER_SEQ FROM TB_WT006 WHERE MY_NM = '$vMyNm1[0]' AND MY_NM_SEQ = '$vMyNm1[1]') " );
// AND B.LST_VIEW_NO != '-1' 구독 추가하고 한번도 안본상태는 제외한다.
			while ( $row = mysqli_fetch_array($result) ) {
				echo "<C>".$row['ID_SEQ']."</C>";
				echo "<G>".$row['LST_VIEW_NO']."</G>";
				echo "<P>".$row['THUMB_NAIL']."</P>";
				echo "<F>".$row['LST_UPD_DH']."</F>";
			}
		}

		// 내가 보는 웹툰 업데이트 ks20141206
		$vQuery = "INSERT INTO TB_WT005 (USER_SEQ, ID_SEQ, LST_VIEW_NO, FST_INS_DH, LST_UPD_DH) VALUE ";
		for ( $i = 0; $i < sizeof($vIdSeq); $i++ ) {
			if ( $vIdSeq[$i] == "" ) continue;
			if ( $i > 0 ) $vQuery .= ", ";
			$vQuery .= " ( (SELECT USER_SEQ FROM TB_WT006 WHERE MY_NM = '$vMyNm1[0]' AND MY_NM_SEQ = '$vMyNm1[1]'), '$vIdSeq[$i]', '$vLstViewNo[$i]', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') ) ";
		}
		$vQuery .= " ON DUPLICATE KEY UPDATE LST_VIEW_NO = VALUES(LST_VIEW_NO), USE_YN = '', LST_UPD_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') ";
		mysqli_query($conn, $vQuery);
		// 내가 보는 웹툰 업데이트 ks20141206  */
	}

	function fn_mode_V1() { // 사이트별 웹툰 목록 update // 사이트별 추가/변경 웹툰
		global $conn,$vMode,$vMyNm,$vParam,$vLstUpdDh,$vAppVer;
		//$vParamArr = explode("|", $vParam); // LST_UPD_DH|SITE 순서로 온다.
		$result = mysqli_query($conn,
"SELECT ID_SEQ, MAX_NO, THUMB_NAIL, LST_UPD_DH - '$vLstUpdDh' AS LST_UPD_DH
	  , CID, NAME, COMP_YN, USE_YN, ARTIST
   FROM TB_WT001 A WHERE EXISTS (SELECT 1 FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ AND USE_YN != 'N')
	AND FST_INS_DH + 0 > '$vLstUpdDh' + 0 AND NAME != ''
	AND SITE = '$vParam'
  UNION ALL
 SELECT ID_SEQ, MAX_NO, THUMB_NAIL, LST_UPD_DH - '$vLstUpdDh' AS LST_UPD_DH
	  , '' AS CID, '' AS NAME, '' AS COMP_YN, '' AS USE_YN, '' AS ARTIST
   FROM TB_WT001 A WHERE EXISTS (SELECT 1 FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ)
	AND LST_UPD_DH + 0 > '$vLstUpdDh' + 0 AND NAME != ''
	AND SITE NOT IN ('daum_l', 'naver_b', 'lezhin')
	AND SITE = '$vParam'
  UNION ALL
 SELECT ID_SEQ, MAX_NO, THUMB_NAIL, LST_UPD_DH - '$vLstUpdDh' AS LST_UPD_DH
	  , '' AS CID, '' AS NAME, '' AS COMP_YN, '' AS USE_YN, '' AS ARTIST
   FROM TB_WT001 A WHERE EXISTS (SELECT 1 FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ)
	AND LST_UPD_DH + 0 > '$vLstUpdDh' + 0 AND NAME != ''
    AND LST_UPD_DH > DATE_FORMAT(SUBTIME(NOW(), '24:00'), '%Y%m%d%H%i%s')
	AND SITE IN ('daum_l', 'naver_b', 'lezhin')
	AND SITE = '$vParam'  ");
		// 1쿼리. 추가/변경된 웹툰목록
		// 2쿼리. 메이저웹툰 업데이트. CID등은 트레픽 제거를 위해 빈칸.
		// 3쿼리. 마이너웹툰 업데이트. (관리웹툰이 1000건 이상.)
		// 마이너웹툰은 수가 많다. 24시간분만 보여주려면 위 쿼리 추가. new 표시를 위해.
		// AND LST_UPD_DH > DATE_FORMAT(SUBTIME(NOW(), '24:00'), '%Y%m%d%H%i%s')
		// ks20151125 "daum", "naver", "nate", "kakao", "naver_b", "olleh", "tstore", "ttale", "lezhin", "foxtoon", "foxtoon_d"
		while ( $row = mysqli_fetch_array($result) ) {
			echo "<C>".$row['ID_SEQ']."</C>";
			echo "<G>".$row['MAX_NO']."</G>";
			echo "<P>".fn_setCDATA($row['THUMB_NAIL'])."</P>";
			echo "<F>".$row['LST_UPD_DH']."</F>";
			echo "<S>".fn_setCDATA($row['ARTIST'])."</S>";

			echo "<A>".$row['CID']."</A>";
			echo "<H>".fn_setCDATA($row['NAME'])."</H>";
			echo "<B>".$row['COMP_YN']."</B>";
			echo "<Q>".$row['USE_YN']."</Q>";
		}
	}
	
	function fn_mode_V2() { // 웹툰인서트,완결업데이트에 관한전부
		global $conn,$vMode,$vMyNm,$vParam,$vLstUpdDh,$vAppVer;
		$result = mysqli_query($conn,
"SELECT ID_SEQ, MAX_NO, THUMB_NAIL, FST_INS_DH - '$vLstUpdDh' AS LST_UPD_DH
	  , CID, NAME, COMP_YN, USE_YN, SITE, ARTIST
   FROM TB_WT001 A WHERE EXISTS (SELECT * FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ)
	AND FST_INS_DH + 0 > '$vLstUpdDh' + 0 AND NAME != '' ");

		while ( $row = mysqli_fetch_array($result) ) {
			echo "<C>".$row['ID_SEQ']."</C>";
			echo "<G>".$row['MAX_NO']."</G>";
			echo "<P>".fn_setCDATA($row['THUMB_NAIL'])."</P>";
			echo "<F>".$row['LST_UPD_DH']."</F>";
			echo "<A>".$row['CID']."</A>";
			echo "<H>".fn_setCDATA($row['NAME'])."</H>";
			echo "<B>".$row['COMP_YN']."</B>";
			echo "<Q>".$row['USE_YN']."</Q>";
			echo "<M>".$row['SITE']."</M>";
			echo "<S>".fn_setCDATA($row['ARTIST'])."</S>";
		}
	}
	
	function fn_mode_2() { // 사이트 목록
		global $conn,$vMode,$vMyNm,$vParam,$vLstUpdDh,$vAppVer;
		$result = mysqli_query($conn, 
"SELECT SITE, NAME, SORT, IMG_VIEWER, THUMB_COMN, LST_UPD_DH - '$vLstUpdDh' AS LST_UPD_DH, USE_YN
	  , ( SELECT COUNT(1) FROM TB_WT001 B WHERE A.SITE = B.SITE AND B.USE_YN != 'N' ) AS CNT
   FROM TB_WT002 A
  WHERE LST_UPD_DH + 0 > '$vLstUpdDh' + 0");

		while ( $row = mysqli_fetch_array($result) ) {
			echo "<M>".$row['SITE']."</M>";
			echo "<N>".$row['SORT']."</N>";
			echo "<H>".fn_setCDATA($row['NAME'])."</H>";
			echo "<D>".fn_setCDATA($row['IMG_VIEWER'])."</D>";
			echo "<O>".fn_setCDATA($row['THUMB_COMN'])."</O>";
			echo "<F>".$row['LST_UPD_DH']."</F>";
			echo "<Q>".$row['USE_YN']."</Q>";
			echo "<R>".$row['CNT']."</R>";
			
			echo "<LOCAL_LST_UPD_DH>20160601000000</LOCAL_LST_UPD_DH>"; // TODO 사이트업데이트할때 이게 없으면 망한다. 연구하자 ks20160728

		}
	}
	
	function fn_mode_3() { // 웹툰 회차 콤보 $cid
		global $conn,$vMode,$vMyNm,$vParam,$vLstUpdDh,$vAppVer;
		$result = mysqli_query($conn,
"SELECT LINK_CODE, TITLE, THUMB_NAIL, SORT, LST_UPD_DH - '$vLstUpdDh' AS LST_UPD_DH
      , USE_YN, SELL_YN, ORG_UPD_DH
   FROM TB_WT003 WHERE ID_SEQ = '$vParam'
    AND LST_UPD_DH + 0 > '$vLstUpdDh' + 0
  ORDER BY ORG_UPD_DH DESC, LINK_CODE + 0 DESC"); 

		while ( $row = mysqli_fetch_array($result) ) {
			echo "<E>".$row['LINK_CODE']."</E>";
			echo "<H>".fn_setCDATA($row['TITLE'])."</H>";
			echo "<P>".fn_setCDATA($row['THUMB_NAIL'])."</P>";
			echo "<F>".$row['LST_UPD_DH']."</F>";
			echo "<Q>".$row['USE_YN']."</Q>";
			echo "<T>".$row['SELL_YN']."</T>";
			echo "<U>".$row['ORG_UPD_DH']."</U>";
		}

		/* / 통계용 저장 ks20141116
		$vMyNm1 = fn_divMyNmArr($vMyNm);
		mysqli_query($conn, "INSERT INTO TB_WT005 (USER_SEQ, ID_SEQ, LST_UPD_DH) VALUES ((SELECT USER_SEQ FROM TB_WT006 WHERE MY_NM = '$vMyNm1[0]' AND MY_NM_SEQ = '$vMyNm1[1]'), '$vParam', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')) 
			                       ON DUPLICATE KEY UPDATE USER_SEQ=(SELECT USER_SEQ FROM TB_WT006 WHERE MY_NM = '$vMyNm1[0]' AND MY_NM_SEQ = '$vMyNm1[1]'), ID_SEQ = '$vParam', LST_UPD_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')"); */
	}
	
	function fn_mode_4() { // 세팅항목
		global $conn,$vMode,$vMyNm,$vParam,$vLstUpdDh,$vAppVer;
		$result = mysqli_query($conn, 
"SELECT SET_ID, SET_NM, SEL_MODE, SET_VALUE, SET_CONT, SORT, LST_UPD_DH - '$vLstUpdDh' AS LST_UPD_DH, USE_YN 
   FROM TB_WT004 WHERE LST_UPD_DH + 0 > '$vLstUpdDh' + 0 "); 

		while ( $row = mysqli_fetch_array($result) ) {
			echo "<K>".$row['SET_ID']."</K>";
			echo "<H>".$row['SET_NM']."</H>";
			echo "<I>".$row['SEL_MODE']."</I>";
			echo "<L>".fn_setCDATA($row['SET_VALUE'])."</L>";
			echo "<J>".fn_setCDATA($row['SET_CONT'])."</J>";
			echo "<N>".$row['SORT']."</N>";
			echo "<F>".$row['LST_UPD_DH']."</F>";
			echo "<Q>".$row['USE_YN']."</Q>";
		}

		if ( $_REQUEST["pMyNm"] != $vMyNm ) { // 로컬에 MY_NM이 없으면 한줄 추가. 기기 식별값 주기위함.
			echo "<K>MY_NM</K><H></H><I></I>";
			echo "<L>".$vMyNm."</L>";
			echo "<J></J><N></N><F></F><Q></Q>";
		}

		// 통계용 저장. 접속했다 전해라 ks20151206
		$vMyNm1 = fn_divMyNmArr($vMyNm);
		mysqli_query($conn, "UPDATE TB_WT006 SET CNT = CNT + 1, APP_VER = IFNULL('$vAppVer', APP_VER), LST_UPD_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), FST_INS_DH = CASE WHEN FST_INS_DH = '' THEN DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') ELSE FST_INS_DH END WHERE MY_NM = '$vMyNm1[0]' AND MY_NM_SEQ = '$vMyNm1[1]' ");
	}
	
	function fn_mode_6() { // 추천목록
		global $conn,$vMode,$vMyNm,$vParam,$vLstUpdDh,$vAppVer;
		$result = mysqli_query($conn, 
"SELECT ID_SEQ, COUNT(1) AS CNT FROM TB_WT005
WHERE USE_YN != 'N' GROUP BY ID_SEQ ORDER BY 2 DESC LIMIT 0, 51 "); // 구독자 많은순 50.
  
/*		$result = mysqli_query($conn, 
"SELECT ID_SEQ, COUNT(1) AS CNT FROM TB_WT005
  WHERE LST_UPD_DH > DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 60 DAY), '%Y%m%d%H%i%s') AND MY_NM != ''
  GROUP BY ID_SEQ ORDER BY 2 DESC LIMIT 0, 51 "); // 최근 14일간. 인기 10선. */
		while ( $row = mysqli_fetch_array($result) ) {
			echo "<C>".$row['ID_SEQ']."</C>";
			echo "<R>".$row['CNT']."</R>";
		}
		//TODO 랜덤추천, 인기추천, 구독추천, 마이너추천등 만들자. ks20151207
	}
	
	function fn_mode_VB() { // 백업목록 조회 ks20151225
		global $conn,$vMode,$vMyNm,$vParam,$vLstUpdDh,$vAppVer;
		
		$vMyNm1 = fn_divMyNmArr($vParam);
		
		$result = mysqli_query($conn, 
" SELECT A.ID_SEQ, A.LST_VIEW_NO FROM TB_WT005 A, TB_WT006 B
WHERE A.USER_SEQ = B.USER_SEQ AND B.MY_NM = '$vMyNm1[0]' AND B.MY_NM_SEQ = '$vMyNm1[1]' AND A.USE_YN != 'N' ");
		while ( $row = mysqli_fetch_array($result) ) {
			echo "<C>".$row['ID_SEQ']."</C>";
			//echo "<G>-1</G>";
			echo "<LST_VIEW_NO>".$row['LST_VIEW_NO']."</LST_VIEW_NO>"; // FOR 프리미엄
		}
	}
	
	function fn_mode_backUpMakingXml() {
		global $conn,$vMode,$vMyNm,$vParam,$vLstUpdDh,$vAppVer;
		// 배포용 XML 만들기. 프로그램에선 안쓴다. 배포전 수동 기동하라. xml파일을 저장하여 assets 폴더에 풍덩
		$vSql11= "SELECT CID, ID_SEQ, SITE, NAME, COMP_YN, USE_YN, THUMB_NAIL, MAX_NO, LST_UPD_DH, ARTIST "
		       . "  FROM TB_WT001 WHERE ID_SEQ IN (SELECT DISTINCT ID_SEQ FROM TB_WT003 WHERE USE_YN != 'N') "
		       . "   AND NAME != '' AND USE_YN != 'N' ";
		$vSql2 = "SELECT SITE, NAME, SORT, IMG_VIEWER, THUMB_COMN, LST_UPD_DH "
				."     , ( SELECT COUNT(1) FROM TB_WT001 B WHERE A.SITE = B.SITE AND B.USE_YN != 'N') AS CNT "
				."     , ( SELECT MAX(B.LST_UPD_DH) FROM TB_WT001 B, TB_WT003 C "
				."          WHERE A.SITE = B.SITE AND B.ID_SEQ = C.ID_SEQ "
				."            AND B.USE_YN != 'N' AND C.USE_YN != 'N' ) AS LOCAL_LST_UPD_DH " // LOCAL_LST_UPD_DH 은 로컬에서 업데이트받는 기준 시간. 내가보는웹툰의 업데이트 시간이 엉망이 되는 바람에 추가.
				."  FROM TB_WT002 A WHERE USE_YN != 'N' ";
		$vSql4 = "SELECT SET_ID, SET_NM, SEL_MODE, SET_VALUE, SET_CONT, SORT, LST_UPD_DH FROM TB_WT004 WHERE USE_YN != 'N' ORDER BY SORT";
		$vSql3 = 
"SELECT A.ID_SEQ, LINK_CODE, TITLE, THUMB_NAIL, SORT, LST_UPD_DH, USE_YN, SELL_YN, ORG_UPD_DH FROM TB_WT003 A
      ,(SELECT DISTINCT ID_SEQ FROM ( SELECT ID_SEQ FROM  ( SELECT ID_SEQ, COUNT(1) AS CNT FROM TB_WT005
				                       WHERE LST_UPD_DH > DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 60 DAY), '%Y%m%d%H%i%s')
				                       GROUP BY ID_SEQ ORDER BY 2 DESC LIMIT 0, 51 ) AA
				            UNION ALL SELECT ID_SEQ FROM TB_WT003 GROUP BY ID_SEQ HAVING COUNT(1) > 300 ) BB ) B
  WHERE A.ID_SEQ = B.ID_SEQ ORDER BY ID_SEQ, ORG_UPD_DH DESC  "; // 회차가 300건 이상인 웹툰과, 인기웹툰 50건의 TB_WT003을 백업한다. ks20151201
		  /*
	TODO 검토	  
SELECT ID_SEQ, COUNT(1) AS CNT FROM TB_WT005
WHERE LST_UPD_DH > DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 60 DAY), '%Y%m%d%H%i%s')
AND USE_YN != 'N' GROUP BY ID_SEQ ORDER BY 2 DESC
*/
		/*$vSql3 = "SELECT ID_SEQ, LINK_CODE, TITLE, THUMB_NAIL, SORT, LST_UPD_DH, USE_YN, SELL_YN, ORG_UPD_DH FROM TB_WT003 "
                ." WHERE ID_SEQ IN ( SELECT ID_SEQ FROM ( SELECT ID_SEQ, COUNT(1) AS CNT FROM TB_WT003 GROUP BY ID_SEQ ) A WHERE CNT > 300 ) "
		        ." ORDER BY ID_SEQ, LINK_CODE + 0 DESC"; //TODO 내가보는웹툰 추가 리스트를 만들어서 추가해보자 ks20150202
/*
select CNT, SITE,NAME,ARTIST, COMP_YN 
, (SELECT COUNT(1) FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ GROUP BY ID_SEQ) AS CCC
from TB_WT001 A,(
SELECT id_seq,count(1) CNT FROM `TB_WT005` group by id_Seq ) B
WHERE A.ID_SEQ =B.ID_sEQ
*/
		$result = mysqli_query($conn, $vSql11); 
		$exist = "../LOG/xml_list_V2.xml";
		echo "<a href = '$exist'>$exist</a><br>";
		$f = @fopen($exist, "w");
		while ( $row = mysqli_fetch_array($result) ) {
			@fwrite($f, "<A>".$row['CID']."</A>");
			@fwrite($f, "<C>".$row['ID_SEQ']."</C>");
			@fwrite($f, "<M>".$row['SITE']."</M>");
			@fwrite($f, "<H>".fn_setCDATA($row['NAME'])."</H>");
			@fwrite($f, "<B>".$row['COMP_YN']."</B>");
			@fwrite($f, "<P>".fn_setCDATA($row['THUMB_NAIL'])."</P>");
			@fwrite($f, "<G>".$row['MAX_NO']."</G>");
			@fwrite($f, "<F>".$row['LST_UPD_DH']."</F>");
			@fwrite($f, "<S>".fn_setCDATA($row['ARTIST'])."</S>");
		}
		@fclose($f); 

		$result = mysqli_query($conn, $vSql2); 
		$exist = "../LOG/xml_list_2.xml";
		echo "<a href = '$exist'>$exist</a><br>";
		$f = @fopen($exist, "w");
		while ( $row = mysqli_fetch_array($result) ) {
			@fwrite($f, "<M>".$row['SITE']."</M>");
			@fwrite($f, "<N>".$row['SORT']."</N>");
			@fwrite($f, "<H>".fn_setCDATA($row['NAME'])."</H>");
			@fwrite($f, "<D>".fn_setCDATA($row['IMG_VIEWER'])."</D>");
			@fwrite($f, "<O>".fn_setCDATA($row['THUMB_COMN'])."</O>");
			@fwrite($f, "<R>".$row['CNT']."</R>");
			@fwrite($f, "<F>".$row['LST_UPD_DH']."</F>");
			@fwrite($f, "<LOCAL_LST_UPD_DH>".$row['LOCAL_LST_UPD_DH']."</LOCAL_LST_UPD_DH>");
		}
		@fclose($f); 

		$result = mysqli_query($conn, $vSql4); 
		$exist = "../LOG/xml_list_4.xml";
		echo "<a href = '$exist'>$exist</a><br>";
		$f = @fopen($exist, "w");
		while ( $row = mysqli_fetch_array($result) ) {
			@fwrite($f, "<K>".$row['SET_ID']."</K>");
			@fwrite($f, "<H>".$row['SET_NM']."</H>");
			@fwrite($f, "<I>".$row['SEL_MODE']."</I>");
			@fwrite($f, "<L>".fn_setCDATA($row['SET_VALUE'])."</L>");
			@fwrite($f, "<J>".fn_setCDATA($row['SET_CONT'])."</J>");
			@fwrite($f, "<N>".$row['SORT']."</N>");
			@fwrite($f, "<F>".$row['LST_UPD_DH']."</F>");
		}
		@fclose($f); 

		$result = mysqli_query($conn, $vSql3); 
		$exist = "../LOG/xml_list_3.xml";
		echo "<a href = '$exist'>$exist</a><br>";
		$f = @fopen($exist, "w");
		while ( $row = mysqli_fetch_array($result) ) {
			@fwrite($f, "<C>".$row['ID_SEQ']."</C>");
			@fwrite($f, "<E>".$row['LINK_CODE']."</E>");
			@fwrite($f, "<H>".fn_setCDATA($row['TITLE'])."</H>");
			@fwrite($f, "<P>".fn_setCDATA($row['THUMB_NAIL'])."</P>");
			@fwrite($f, "<F>".$row['LST_UPD_DH']."</F>");
			@fwrite($f, "<T>".$row['SELL_YN']."</T>");
			@fwrite($f, "<U>".$row['ORG_UPD_DH']."</U>");
		}
		@fclose($f);
	}
	

	// 이하 모드는 버전업에서 안쓴다.
	function fn_mode_1() { // 사이트별 웹툰 목록 update
		global $conn,$vMode,$vMyNm,$vParam,$vLstUpdDh,$vAppVer;
		$result = mysqli_query($conn,
		"SELECT ID_SEQ, MAX_NO, THUMB_NAIL, LST_UPD_DH - '$vLstUpdDh' AS LST_UPD_DH
		   FROM TB_WT001 WHERE ID_SEQ IN (SELECT DISTINCT ID_SEQ FROM TB_WT003)
		    AND LST_UPD_DH + 0 > '$vLstUpdDh' + 0 AND NAME != ''
		    AND SITE IN ('naver', 'daum', 'nate', 'kakao')
      UNION ALL
     SELECT ID_SEQ, MAX_NO, THUMB_NAIL, LST_UPD_DH - '$vLstUpdDh' AS LST_UPD_DH
		   FROM TB_WT001 WHERE ID_SEQ IN (SELECT DISTINCT ID_SEQ FROM TB_WT003)
		    AND LST_UPD_DH + 0 > '$vLstUpdDh' + 0 AND NAME != ''
		    AND SITE NOT IN ('naver', 'daum', 'nate', 'kakao')
		    AND LST_UPD_DH > DATE_FORMAT(SUBTIME(NOW(), '24:00'), '%Y%m%d%H%i%s') " ); // 마이너 웹툰은 최근 24시간분. new 표시를 위해.

		while ( $row = mysqli_fetch_array($result) ) {
			echo "<C>".$row['ID_SEQ']."</C>";
			echo "<G>".$row['MAX_NO']."</G>";
			echo "<P>".fn_setCDATA($row['THUMB_NAIL'])."</P>";
			echo "<F>".$row['LST_UPD_DH']."</F>";
		}
	}
	
	function fn_mode_1_1() { // 사이트별 웹툰 목록 insert. use_yn,comp_yn이 바뀐경우도 발동
		global $conn,$vMode,$vMyNm,$vParam,$vLstUpdDh,$vAppVer;
		$result = mysqli_query($conn,
		"SELECT CID, ID_SEQ, SITE, NAME, COMP_YN, USE_YN, THUMB_NAIL, MAX_NO, LST_UPD_DH - $vLstUpdDh AS LST_UPD_DH
		   FROM TB_WT001 WHERE ID_SEQ IN (SELECT DISTINCT ID_SEQ FROM TB_WT003 WHERE USE_YN != 'N')
		    AND FST_INS_DH + 0 > '$vLstUpdDh' + 0 AND NAME != '' AND USE_YN != 'N' ");

		while ( $row = mysqli_fetch_array($result) ) {
			echo "<A>".$row['CID']."</A>";
			echo "<C>".$row['ID_SEQ']."</C>";
			echo "<M>".$row['SITE']."</M>";
			echo "<H>".fn_setCDATA($row['NAME'])."</H>";
			echo "<B>".$row['COMP_YN']."</B>";
			echo "<Q>".$row['USE_YN']."</Q>";
			echo "<P>".fn_setCDATA($row['THUMB_NAIL'])."</P>";
			echo "<G>".$row['MAX_NO']."</G>";
			echo "<F>".$row['LST_UPD_DH']."</F>";
		}
	}

	function fn_giveMeMyName($pMyNm) {
		global $conn,$vMode,$vMyNm,$vParam,$vLstUpdDh,$vAppVer;

		// 기존 이름이 있고 myNmSeq 가지고 있으면 리턴
		if ( strpos($pMyNm, "_") !== false ) return $pMyNm;
		
		/*if ( strpos($pMyNm, "_") !== false ) { // return $pMyNm;
			$vMyNm1 = split("_" ,$pMyNm);
			return $vMyNm1[0];
		}*/

		if ( $vMode != "4" ) return "Temp_9";
		
		//if ( strlen($pMyNm) != 12 && strlen($pMyNm) != 0 ) return $pMyNm; // 구버전(12글자), 처음(0글자)인 경우가 아니면 그냥 vMyNm리턴

		$result = mysqli_query($conn,
" SELECT MY_NM, MY_NM_SEQ, LST_UPD_DH, SORT FROM (
 (SELECT MY_NM, MY_NM_SEQ, LST_UPD_DH, 1 AS SORT FROM TB_WT006 WHERE MY_NM = '$pMyNm' AND MY_NM_SEQ = 0 AND APP_VER = '')
  UNION ALL
 (SELECT MY_NM, (SELECT MAX(MY_NM_SEQ) FROM TB_WT006 WHERE MY_NM = A.MY_NM) + 1, '' AS LST_UPD_DH, 2 AS SORT FROM TB_WT006 A WHERE MY_NM = '$pMyNm')
  UNION ALL
 (SELECT MY_NM, MY_NM_SEQ, LST_UPD_DH, 3 AS SORT FROM TB_WT006 WHERE LST_UPD_DH = '')
  UNION ALL
 (SELECT MY_NM, MY_NM_SEQ + 1 AS MY_NM_SEQ, LST_UPD_DH, 4 AS SORT FROM TB_WT006 ) ) A
  ORDER BY SORT, MY_NM_SEQ, RAND(), LST_UPD_DH LIMIT 1 ");
// 1쿼리 구버전 사용자는 MY_NM, MY_NM_SEQ=0 으로 세팅. 
// 2쿼리 1쿼리중 FST_INS_DH가 이미 있으면 MY_NM_SEQ 를 + 1 해서 이름 제작
// 3쿼리 pMyNm이 빈값으로 들어온 상황. 사용한 이름이 없는 건 검색.(이미 소진됨)
// 4쿼리 모두 사용한 이름이면 MY_NM_SEQ 버전이 낮은것(사용빈도가 적은것) 중 최근 변경일자가 가장 옛날인건 1개를 선택.
		$vMyNm1 = "";
		$vMyNmSeq1 = "";

		while ( $row = mysqli_fetch_array($result) ) {
			$vMyNm1 = $row['MY_NM'];
			$vMyNmSeq1 = $row['MY_NM_SEQ'];
		} // 결과가 1줄인 쿼리다.

		if ( $vMyNm1 == "" ) { // 자원이 없다면 새로 딴다. ks20151205 myNmSeq 도입으로 아래 로직이 작동하는 일은 없다.
			$keychars = "abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			for ( $i = 0; $i < 12; $i++ ) {
				$vMyNm1 .= substr($keychars, rand(1, strlen($keychars) ), 1);
			}
			$vMyNmSeq1 = "100";
		}

		if ( $vAppVer == "" ) $vAppVer = "_"; // 초기 사용자 세팅을 위한. ks20151230

		// 사용자 정보 저장 ks20151206
		mysqli_query($conn,
"INSERT INTO TB_WT006 (MY_NM, MY_NM_SEQ, APP_VER, FST_INS_DH, LST_UPD_DH, USER_SEQ) VALUES 
('$vMyNm1', '$vMyNmSeq1', '$vAppVer', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), (SELECT MAX(USER_SEQ)+1 FROM TB_WT006 C) ) 
	 ON DUPLICATE KEY UPDATE APP_VER = VALUES(APP_VER), FST_INS_DH = CASE WHEN FST_INS_DH = '' THEN DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') ELSE FST_INS_DH END, LST_UPD_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')");
		return $vMyNm1."_".$vMyNmSeq1;
	}

	// myname_0 을 rtn[0] = myname; rtn[1] = 0; 으로 리턴한다. ks20151206
	function fn_divMyNmArr($pMyNm) {
		if ( strpos($pMyNm, "_") !== false ) $pMyNm .= "_0"; // 구버전 호환용.
		return split("_" ,$pMyNm);
	}

	function fn_setCDATA($pStr) {
		return ( $pStr == "" )? "" : "<![CDATA[$pStr]]>";
	}

	// 콜한 프로그램 버전($vAppVer)이 지정한 프로그램 버전($pVer) 이상이면 true ks20151213
	function fn_overThen($pVer) {
		global $conn,$vMode,$vMyNm,$vParam,$vLstUpdDh,$vAppVer;
		if ( $vAppVer == $pVer ) return true;
		if ( $vAppVer == "" && $pVer != "" ) return false;
		$v1 = split("\." ,$vAppVer);
		$v2 = split("\." ,$pVer);

		for ( $i = 0; $i < sizeof($v1); $i++ ) {
			if ( (int)$v1[$i] > (int)$v2[$i] ) return true;
		}
		return false;
	}
/* TB_WT006의 USER_SEQ 업데이트문.q
UPDATE TB_WT006 A, (SELECT @num:=0) B
SET USER_SEQ =  0+ (@num:=@num+1)

-- 방문자 카운트
SELECT '24H' AS GUBUN, COUNT(1) AS CNT FROM TB_WT006
 WHERE LST_UPD_DH > DATE_FORMAT(SUBTIME(NOW(), '24:00'), '%Y%m%d%H%i%s') UNION ALL
SELECT '72H' AS GUBUN, COUNT(1) AS CNT FROM TB_WT006
 WHERE LST_UPD_DH > DATE_FORMAT(SUBTIME(NOW(), '72:00'), '%Y%m%d%H%i%s') UNION ALL
SELECT '168H' AS GUBUN, COUNT(1) AS CNT FROM TB_WT006
 WHERE LST_UPD_DH > DATE_FORMAT(SUBTIME(NOW(), '168:00'), '%Y%m%d%H%i%s')
*/
?>