<?
	// $vParam : 웹툰의 아이디가 들어오고
	// $vMode : FILE / CRONTAB / UPD(클라이언트에서 던지는 업데이트) 으로 들어온다.
	// 1. DB에 저장된 웹툰의 새로운 회차 확인. 
	// 2. 다음웹툰리그 삭제건 확인(후 삭제)

	$vMode = $_REQUEST["pMode"];
	$vMyNm = $_REQUEST["pMyNm"];
	$vParam = $_REQUEST["pParam"];

	ini_set('max_execution_time', 6000);

	if ( $vMode == "FILE" || $vMode == "CRONTAB" ) { // 파일 로그를 남기는 모드면 파일 생성 || 자동머신이 돌려줌
		$exist = "../LOG/updateLog.".date("YmdHis");
		$f = @fopen($exist, "w");
	} else if ( $vMode == "UPD" ) { // 클라이언트에서 보내는 업데이트 싸인(특정웹툰)
	} else if ( $vMode == "UPD_MYLIST" ) { // ID_SEQ/ID_SEQ/ID_SEQ/...
	} else if ( $vMode == "UPD_ALL" ) { // 전체업데이트
	} else {
		if ( $vParam == "" ) {
			fn_echo("ERR! vParam Missing", "ERR! vParam Missing");
			exit();
		}
	}

	include "../include/db_ok.php";
	include "../include/shoc.class.php";

	logDB($vMyNm, "$vMode / $vParam");

	$result =
"SELECT A.CID, A.NAME, A.COMP_YN, B.NAME AS SITE_NAME, A.ID_SEQ
      , A.SITE, A.LST_UPD_DH
      , B.THUMB_COMN
	  , IFNULL( (SELECT 'Y' FROM TB_WT001 AA WHERE EXISTS (SELECT 1 FROM TB_WT003 WHERE ID_SEQ = AA.ID_SEQ) AND AA.ID_SEQ = A.ID_SEQ ), 'N') AS TB_WT003_YN
   FROM TB_WT001 A INNER JOIN TB_WT002 B
  WHERE A.SITE = B.SITE AND A.USE_YN != 'N' AND B.USE_YN != 'N' ";

	if ( $vMode == "CRONTAB" ) { // 크론탭머신(자동업데이트머신)모드이면 간략하게 추려서 업데이트
		/******* 
	   * 현재 https://mywebcron-com.loopiasecure.com/ 사이트에서 kyusiks.kr 구글계정으로 크론탭 돌리고 있다. / 안돈다.
	   * http://kyusiks.dothome.co.kr/toontaku/updateBot.php?vMode=CRONTAB
		 *******/
		$result = $result
//."    AND A.LST_CHK_DH < DATE_FORMAT(SUBTIME(NOW(), '03:59'), '%Y%m%d%H%i%s') " // 최근 업데이트 체크 후 4시간이 지난건만 대상(매시간 체크하는건 성능 우려가 생긴다)
//."    AND A.LST_UPD_DH < DATE_FORMAT(SUBTIME(NOW(), '19:59'), '%Y%m%d%H%i%s') " // 최근 업데이트 성공부터 20시간 후의 건만 대상(보통 업데이트 후 하루이상 텀이 필요하다)
//."    AND A.LST_UPD_DH != '' " // LST_UPD_DH가 없는건 = 새로 INSERT 된 웹툰
."    AND A.COMP_YN != 'Y' " // 완결건은 체크하지 않는다. 누군가 수동으로 한다면 업데이트 되겠지.
."  ORDER BY A.LST_CHK_DH, A.LST_UPD_DH, A.SITE";

	} else if ( $vMode == "UPD" ) {
		$result = $result." AND A.ID_SEQ = '$vParam'"; // ALL이면 모든 카툰 업데이트

	} else if ( $vMode == "UPD_ALL" ) {
		$result = $result
."    AND A.COMP_YN != 'Y' " // 완결건은 체크하지 않는다. 누군가 수동으로 한다면 업데이트 되겠지.
."  ORDER BY A.LST_CHK_DH, A.LST_UPD_DH, A.SITE ";

	} else if ( $vMode == "UPD_MYLIST" ) {
		$vIdSeqRep = "'".str_replace("/", "','", $vParam)."'";

		$result = $result
."    AND A.LST_CHK_DH < DATE_FORMAT(SUBTIME(NOW(), '00:10'), '%Y%m%d%H%i%s') " // 최근 업데이트 체크 후 10분 이후건만 대상. 테스트할때 자꾸 껏다켰다해서
."    AND A.ID_SEQ IN ($vIdSeqRep) "
."    AND A.COMP_YN != 'Y' " // 완결건은 체크하지 않는다. 누군가 수동으로 한다면 업데이트 되겠지.
."  ORDER BY A.LST_CHK_DH, A.LST_UPD_DH, A.SITE";

	} else if ( $vMode == "TEST" ) { // ks20151124
		$result = $result." AND A.SITE = '$vParam' ORDER BY A.LST_CHK_DH, A.LST_UPD_DH, A.SITE LIMIT 0, 10"; // 해당 사이트의 웹툰 10개정도만 업데이트 돌려본다.

	} else {
		$vSiteList = array( "daum", "naver", "nate", "kakao", "naver_b"
		                  , "daum_l", "olleh", "tstore", "lezhin" // , "ttale"
		                  , "foxtoon", "foxtoon_d", "daum_s", "battlec", "battlec_d"
		                  , "comiccube" );

		if ( in_array($vParam, $vSiteList) ) { // vParam이 사이트로 들어오면 ks20151128
			$result = $result." AND A.SITE = '$vParam' ORDER BY A.LST_CHK_DH, A.LST_UPD_DH, A.SITE ";
		} else if ( $vParam == "ALL" ) { // 전체 업데이트. 조건없이 모든건을 업데이트한다.
			$result = $result."    AND A.COMP_YN != 'Y' AND A.SITE != 'lezhin' AND A.SITE != 'daum_l' "
."  ORDER BY A.LST_CHK_DH, A.LST_UPD_DH, A.SITE ";
		} else { // 특정 cid만 업데이트 하는거면 위 조건 무시
			$result = $result." AND A.ID_SEQ = '$vParam'"; // ALL이면 모든 카툰 업데이트
		}
	}

	$vQuery = mysqli_query($conn, $result);
	fn_echo("<link href='./wtb.css' rel='stylesheet'/><body><table border=3><tr>", "");

	$gv_no = "";
	$gv_cid = "";
	$gv_loopCnt = 9999; // 무한루프 방지. 최대 몇번 루프할거냐. 0이면 무한.

	$gv_minorChkCntMax = 0; // 마이너 웹툰은 n건만 체크한다. 건이 많아서 성능 우려. 0이면 무한.
	$gv_minorChkCnt = 0; // 마이너 웹툰은 n건만 체크한다. 건이 많아서 성능 우려. 0이면 무한.
	$gv_successCnt = 0; // 업데이트된 툰 갯수 ks20141030

	$vJson = array("daum", "daum_l", "olleh", "tstore", "lezhin", "daum_s", "comiccube");

	while ( $row = mysqli_fetch_array($vQuery) ) {
		$gv_idSeq   = $row['ID_SEQ'];
		$vSite      = $row['SITE'];
		$gv_cid     = $row['CID'];
		$vUrl       = "";
		$vRangeC    = array();
		$vNextPageC = array();
		$vNameC     = array();
		$vThumbC    = array();
		$vLinkCodeC = array();
		$vWebUpdDhC = array(); // 웹 서버의 업데이트 일시
		$vSellYnC   = array(); // 유료여부 ks20151122
		$vInsertCnt = 0;
		$vPageNo    = 1;
		$vNextTF    = true;
		$shoc       = new shoc; // 페이지를 읽어오기 위해서 쇽 클래스 호출

		if ( $vSite == "daum" ) { // 다음웹툰 ks20151123 192 / tomorrow / 어제 오늘 그리고 내일
			$vUrl       = "http://webtoon.daum.net/data/pc/webtoon/view/^cid";
		} else if ( $vSite == "naver" ) { // 네이버웹툰 ks20151123 85190 / 20853 / 마음의소리
			$vUrl       = "http://comic.naver.com/webtoon/list.nhn?titleId=^cid&page=^nextPage";
			$vRangeC    = array("<!-- 리스트 -->", "<!-- side menu -->");
			$vNextPageC = array("<a href=\"/webtoon/list.nhn?titleId=^cid&amp;page=", "\"   class=\"next\">");
			$vNameC     = array("',event)\">", "<");
			$vThumbC    = array("	<img src=\"", "\" title=\"");
			$vLinkCodeC = array("this,'lst.title','^cid','", "'");
			$vWebUpdDhC = array("<td class=\"num\">", "<"); // 2015.11.19
			$vSellYnC   = array();
		} else if ( $vSite == "nate" ) { // 네이트 연재중 ks20151123 45647 / 64389 / 엄마는 외국인 시즌2
			$vUrl       = "http://comics.nate.com/webtoon/list.php?btno=^cid&page=^nextPage";
			$vNextPageC = array("<a href=\"/webtoon/list.php?btno=^cid&page=", "&category=1&order=cnt_view\" class=\"next\">");
			$vNameC     = array("<span class=\"tel_title\">", "</span>");
			$vLinkCodeC = array("<a href=\"/webtoon/detail.php?btno=^cid&bsno=", "&category=1\">");
			$vWebUpdDhC = array("<span class=\"tel_date\">", "<"); // 2015.11.19
			$vSellYnC   = array();
		} else if ( $vSite == "olleh" ) { // 올레마켓 ks20151123 웹툰 87959 / 15 / 냄새를 보는 소녀
			$vUrl       = "http://webtoon.olleh.com/api/work/getTimesListByWork.kt?webtoonseq=^cid";
		} else if ( $vSite == "daum_l" ) { // 다음웹툰리그 ks20151123 827 / 8574 / 스프린터
			$vUrl       = "http://webtoon.daum.net/data/pc/leaguetoon/view/^cid?page_no=^nextPage"; 
		} else if ( $vSite == "naver_b" ) { // 네이버베스트도전 ks20151123 5725 / 567749 / 해프닝
			$vUrl       = "http://m.comic.naver.com/bestChallenge/list.nhn?titleId=^cid&page=^nextPage";
			$vRangeC    = array("likeit_wrap\">", "</html>");
			$vNextPageC = array("lst.next','','');\" href=\"/bestChallenge/list.nhn?titleId=^cid&", "\" class=\"next\" id=\"nextButton");
			$vNameC     = array("<span class=\"toon_name\"><strong><span>", "</span></strong></span>");
			$vThumbC    = array("><span class=\"im_inbr\"><img src=\"", "\" width=\"71\" height=\"42\" alt=\"\" />");
			$vLinkCodeC = array("&no=", "&");
			$vWebUpdDhC = array("<span class=\"if1\">", "<"); // 15.11.19
			$vSellYnC   = array();
		} else if ( $vSite == "kakao" ) { // 카카오웹툰 ks20151117 86211 / 46609100 / 옹동스
			$vUrl       = "http://page.kakao.com/home/^cid?page=^nextPage"; // 페이지업데이트됨 ks20141013 ks20150317 ks20160321 ks20160610
			$vRangeC    = array("<div class=\"contentWrp\">", "<div class=\"homePagination\">");
			$vNameC     = array("alt=\"", "\" width");
			$vThumbC    = array("src=\"http://dn-img-page.kakao.com/download/resource?kid=", "\"");
			$vLinkCodeC = array("data-productId=\"", "\"");
			$vWebUpdDhC = array("<span class=\"date\">", "<"); // 2015.06.09
			$vSellYnC   = array();
		} else if ( $vSite == "tstore" ) { // T스토어 웹툰 ks20151123 : 88060 / H900719170 / 헬로우 블랙잭
			$vUrl       = "http://m.onestore.co.kr/mobilepoc/webtoon/webtoonListMore.omp?prodId=^cid&currentPage=^nextPage";
			
		} else if ( $vSite == "lezhin" ) { // 레진코믹스 웹툰 ks20151124 : 88349 / bad_boss / 나쁜 상사
			$vUrl       = "http://www.lezhin.com/comic/^cid"; // #scheduled(#printed #completed을 포함한다)
			$vRangeC    = array(",all:", ",purchased:[");
		} else if ( $vSite == "foxtoon" ) { // 폭스툰 ks20151123 ks20150829 : 145400 / fantasychildren / 환상남매
			$vUrl       = "http://www.foxtoon.com/comic/^cid";
			$vRangeC    = array("contents", "ranking comic");
			$vNameC     = array("<div class=\"title\">", "</div>");
			$vLinkCodeC = array("img data-original=\"http://cdn.foxtoon.com/comics/^cid/episode/", "/");
			$vWebUpdDhC = array("regex:<div class=\"head_info\">(?:\s*)<div class=\"date\">(?:\s*)", "regex:(?:\s*)<\/div>"); // ks20160705 ks20150621
			$vSellYnC   = array("regex:<div class=\"coin \">(?:\s*)", "regex:\s*<\/div>\s*<\/div>");
		} else if ( $vSite == "foxtoon_d" ) { // 폭스툰 도전 ks20151119 : 172431 / 38 / 용사의시험
			$vUrl       = "http://www.foxtoon.com/challenge/comic/^cid/?p=^nextPage";
			$vRangeC    = array("comic_item_list challenge", "comic_detail_pagination");
			$vNameC     = array("<div class=\"title\">", "</div>");
			$vLinkCodeC = array("img data-original=\"http://cdn.foxtoon.com/challenge_comics/^cid/episode/", "/");
			$vWebUpdDhC = array("regex:<div class=\"head_info\">(?:\s*)<div class=\"date\">(?:\s*)", "regex:(?:\s*)<\/div>"); // ks20160705 ks20150621
			$vSellYnC   = array();
		} else if ( $vSite == "daum_s" ) { // 다음스포츠 웹툰 ks20151208 : 91896 / joncarter / 존카터의 스포툰
			$vUrl       = "http://1boon.kakao.com/^cid.json?callback=sportsCartoonListCallback&pagesize=16&page=^nextPage";
			$vRangeC    = array("; sportsCartoonListCallback(", ");");
		} else if ( $vSite == "battlec" ) { // 배틀코믹스 ks20160706
			$vUrl       = "http://www.battlecomics.co.kr/webtoons/^cid";
			$vNameC     = array("regex:content__title'>(?:\s*)<span>", "<");
			$vThumbC    = array("data-src-small='http://images.battlecomics.co.kr/webtoon/^cid/episode/^no/thumbnail/thumbnail-webtoonid_^cid-episodeid_^no-w_280-h_200-t_", "'");
			$vLinkCodeC = array("data-index-episode='", "'");
			$vWebUpdDhC = array("regex:info__updated-at'>(?:\s*)<span>", "<");
			
		} else if ( $vSite == "battlec_d" ) { // 배틀코믹스 도전만화 ks20160706
			$vUrl       = "http://www.battlecomics.co.kr/challenge/webtoons/^cid";
			$vNameC     = array("regex:content__title'>(?:\s*)<span>", "<");
			$vThumbC    = array("data-src-small='http://images.battlecomics.co.kr/challenge_webtoon/^cid/episode/", "'");
			$vLinkCodeC = array("data-index-episode='", "'");
			$vWebUpdDhC = array("regex:info__updated-at'>(?:\s*)<span>", "<");

		} else if ( $vSite == "comiccube" ) { // 코믹큐브 ks20160707
			$vUrl       = "http://www.bookcube.com/toon/data/_webtoon_split_list.asp?webtoon_num=^cid&pageNum=^nextPage&pagesize=20"; 
			
		} else if ( $vSite == "ttale" ) { // 티테일 웹툰 ks20160708 ks20151124 : 172909 / 125 / 봄의 소리
			return; // 티테일 서비스종료로 인한 삭제 ks20160808
			$vUrl       = "http://blog.ttale.com/^cid?&p=^nextPage";
			$vRangeC    = array("leray_area", "right_area");
			$vNameC     = array("regex:>(?:|\n*|\s*)<p>", "<");
			$vThumbC    = array("regex:storage\/product_", "\"");
			$vLinkCodeC = array("^cid/", "\" style=\"display");
			$vWebUpdDhC = array("<span class=\"ddaate\">", "<"); // 15.09.16
		} else {
			return;
		}

		if ( $vSite == 'naver_b' || $vSite == 'daum_l' || $vSite == "foxtoon_d" ) {
			if ( $gv_minorChkCntMax != 0 && $gv_minorChkCnt > $gv_minorChkCntMax ) continue; // 마이너 체크는 n개만.
			$gv_minorChkCnt++;
		}

		fn_echo("</tr><tr><td>".$row['SITE']."</td><td>".$row['NAME']."</td><td>".$row['ID_SEQ']."</td><td>".$row['CID']."</td>", $row['SITE_NAME']."|".$row['NAME']);

		while ( $vNextTF ) {
			/**************
			 * 페이지를 긁어와서 적당히 파싱 시작
			 **************/
//echo(fn_vReplace($vUrl));
			$shoc->setUrl(fn_vReplace($vUrl)); // 업데이트를 확인할 리스트 url 셋

			/**************
			 * 2. 긁어온 사이트에서 내용 긁을 범위만 지정한다. (생략사이트들도 많음)
			 **************/
			if ( sizeof($vRangeC) >= 1 ) $shoc->setRange($vRangeC[0], $vRangeC[1]);

			/************** TODO 이건 다시보자
			 * 다음웹툰리그의 삭제 여부를 판단한다.ks20141228
			 * 사용자가 삭제하기때문에. 확인하는 방법이 이것뿐이라...
			 *************
			if ( $vSite == 'daum_l' ) {
				$daumLFin = $shoc->findIt(fn_vReplace("<title>"), fn_vReplace("</title>"));
				if ( $daumLFin[1][0] == "Daum - 원하시는 페이지를 찾을 수가 없습니다." ) {
					fn_echo("<td>삭제된듯</td>", "삭제된듯");
					mysqli_query($conn, "UPDATE TB_WT001 SET USE_YN = 'N', FST_INS_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), LST_UPD_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), LST_CHK_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') WHERE ID_SEQ = '".$row['ID_SEQ']."'");
					return 0;
				}
			}*/

			$vTitle = array();
			$vThumb = array();
			$vLinkCode = array();
			$vWebUpdDh = array(); // 서버에서 업데이트된 날짜 (order 활용에 유용할듯) ks20151120
			$vSellYn = array(); // 유료 유무 ks20151120

			if ( in_array($vSite, $vJson) ) { // json 형식의 경우 ks20151113
				$shoc->findIt("","");
				$decode = json_decode($shoc->gv_readData, true);

				if ( $vSite == "daum" ) {
					for ( $i = 0; $i < sizeof( $decode['data']['webtoon']['webtoonEpisodes']); $i++ ) {
						array_push($vTitle   , $decode['data']['webtoon']['webtoonEpisodes'][$i]['title']);
						array_push($vLinkCode, $decode['data']['webtoon']['webtoonEpisodes'][$i]['id']);
						array_push($vThumb   , $decode['data']['webtoon']['webtoonEpisodes'][$i]['thumbnailImage']['url']);
						array_push($vWebUpdDh, $decode['data']['webtoon']['webtoonEpisodes'][$i]['dateCreated']); // 업데이트일자 "20151117110200"
						array_push($vSellYn  , ($decode['data']['webtoon']['webtoonEpisodes'][$i]['serviceType'] == "free")? "":"Y"); // '':"free", Y:"pay_group"
						//echo("<br>".$vTitle[$i]."/".$vThumb[$i]."/".$vLinkCode[$i]."/".$vWebUpdDh[$i]."/".$vSellYn[$i]."/".$vUseYn[$i]);
					}
					$vNextTF = false; // 한페이지만 읽는다.
				} else if ( $vSite == "daum_l" ) {
					for ( $i = 0; $i < sizeof( $decode['data']['leaguetoon']['leaguetoonEpisodes']); $i++ ) {
						array_push($vTitle   , $decode['data']['leaguetoon']['leaguetoonEpisodes'][$i]['title']);
						array_push($vLinkCode, $decode['data']['leaguetoon']['leaguetoonEpisodes'][$i]['id']);
						array_push($vThumb   , $decode['data']['leaguetoon']['leaguetoonEpisodes'][$i]['image']['url']);
						array_push($vWebUpdDh, $decode['data']['leaguetoon']['leaguetoonEpisodes'][$i]['dateCreated']); // 업데이트일자 "20150918013726"
						array_push($vSellYn  , ""); // 전부 무료
					}

					if ( $decode['page']['no'] * $decode['page']['size'] < $decode['page']['totalItemCount'] ) {
						$vNextTF = true;
					} else { // page수 * 한페이지당 읽어오는수size 가 총 아이템 갯수totalItemCount 보다 작으면 루프
						$vNextTF = false;
					}

					if ( sizeof($vTitle) == 0 ) { // 삭제처리 ks20151201
						if ( $decode['result']['status'] == "404" ) {
							fn_delToon($gv_idSeq);
							fn_echo("<td>삭제</td>", "|삭제 $gv_idSeq");
						}
					}
				} else if ( $vSite == "olleh" ) { // ks20151120
					for ( $i = 0; $i < sizeof( $decode['timesList']); $i++ ) {
						array_push($vTitle   , $decode['timesList'][$i]['timestitle']);
						array_push($vLinkCode, $decode['timesList'][$i]['timesseq']);
						array_push($vThumb   , $decode['timesList'][$i]['thumbpath']);
						array_push($vWebUpdDh, $decode['timesList'][$i]['startdt']); // 업데이트일자
						array_push($vSellYn  , ($decode['timesList'][$i]['sellyn'] == "Y"? "Y":"")); // 유료여부
					}
					$vNextTF = false; // 한페이지만 읽는다.
				} else if ( $vSite == "tstore" ) { // ks20151121
					for ( $i = 0; $i < sizeof( $decode['webtoonList']); $i++ ) {
						array_push($vTitle   , $decode['webtoonList'][$i]['prodNm']);
						array_push($vLinkCode, $decode['webtoonList'][$i]['prodId']);
						array_push($vThumb   , $decode['webtoonList'][$i]['filePos']);
						array_push($vWebUpdDh, substr(str_replace("T", "", $decode['webtoonList'][$i]['updateDate']), 0, 14)); // 업데이트일자 "20130104T044120+0900"
						array_push($vSellYn  , ""); // 전부 무료인걸로 판단되나 추가 확인이 필요함. prodAmt, discountPrice
					}
					if ( sizeof($decode['webtoonList']) == 0 ) $vNextTF = false;
				} else if ( $vSite == "lezhin" ) { // ks20151121
					for ( $i = 0; $i < sizeof( $decode); $i++ ) {
						array_push($vTitle   , $decode[$i]['display']['title']);
						array_push($vLinkCode, $decode[$i]['name']);
						array_push($vThumb   , ""); // vLinkCode 와 cid로 조합하기때문에 필요없다. 
						array_push($vWebUpdDh, date("YmdHis", substr($decode[$i]['publishedAt'], 0, -3)) + $decode[$i]['seq'] ); // 업데이트일자 "1412866800000". 일시가 모두 같은 웹툰은 seq를 더해 소트.
						array_push($vSellYn  , (empty($decode[$i]['freedAt'])? "Y": ""));
					}
					$vNextTF = false; // 한페이지만 읽는다.
				} else if ( $vSite == "daum_s" ) { // ks20151208
					for ( $i = 0; $i < sizeof( $decode['data']); $i++ ) {
						array_push($vTitle   , $decode['data'][$i]['title']);
						array_push($vLinkCode, $decode['data'][$i]['publishKey']);
						array_push($vThumb   , $decode['data'][$i]['img']);
						array_push($vWebUpdDh, date("YmdHis", substr($decode['data'][$i]['publishTime'], 0, -3)) ); // 업데이트일자 "1449554394969".
						array_push($vSellYn  , "");
						//echo("<br>".$vTitle[$i]."/".$vThumb[$i]."/".$vLinkCode[$i]."/".$vWebUpdDh[$i]."/".$vSellYn[$i]."/".$vUseYn[$i]);
					}

					if ( $decode['pagingInfo']['hasNext'] == "true" ) {
						$vNextTF = true;
					} else {
						$vNextTF = false;
					}
				} else if ( $vSite == "comiccube" ) { // 코믹큐브 ks20160707
					for ( $i = 0; $i < sizeof( $decode['list']); $i++ ) {
						array_push($vTitle   , $decode['list'][$i]['title']);
						array_push($vLinkCode, $decode['list'][$i]['split_num']);
						array_push($vThumb   , ""); // vLinkCode 와 cid로 조합하기때문에 필요없다. 
						array_push($vWebUpdDh, str_replace("-", "", $decode['list'][$i]['publish_date'])."000000"); // 업데이트일자 "2016-07-07"
						array_push($vSellYn  , ($decode['list'][$i]['free_yn'] == 1 ? "":"Y") );
					}
					if ( $decode['pageNum'] == $decode['pagecount'] ) $vNextTF = false;
				}
			} else { // 일반 크롤링
				// 제목, 링크코드, 썸네일, 웹업데이트일시, 유료여부를 읽어온다.
				$vTitle    = $shoc->findIt(fn_vReplace($vNameC[0])    , fn_vReplace($vNameC[1]));
				$vLinkCode = $shoc->findIt(fn_vReplace($vLinkCodeC[0]), fn_vReplace($vLinkCodeC[1]));
				if ( sizeof($vThumbC)   >= 1 ) $vThumb    = $shoc->findIt(fn_vReplace($vThumbC[0]), fn_vReplace($vThumbC[1]));
				if ( sizeof($vWebUpdDhC)>= 1 ) $vWebUpdDh = $shoc->findIt($vWebUpdDhC[0], $vWebUpdDhC[1]);
				if ( sizeof($vSellYnC)  >= 1 ) $vSellYn   = $shoc->findIt($vSellYnC[0], $vSellYnC[1]);

				if ( sizeof($vTitle)    >= 1 ) $vTitle    = $vTitle[1];
				if ( sizeof($vLinkCode) >= 1 ) $vLinkCode = $vLinkCode[1];
				if ( sizeof($vThumb)    >= 1 ) $vThumb    = $vThumb[1];
				if ( sizeof($vWebUpdDh) >= 1 ) $vWebUpdDh = $vWebUpdDh[1];
				if ( sizeof($vSellYn)   >= 1 ) $vSellYn   = $vSellYn[1];

				for ( $i = 0; $i < sizeof($vWebUpdDh); $i++ ) {
					if ( $vSite == "foxtoon" ) {
						$vSellYn[$i] = (strpos($vSellYn[$i], "무료") === false)? "Y" : ""; // 판매여부에 [무료] 문자열이 없으면 유료
					} else if ( $vSite == "battlec" ) {
						$gv_no = $vLinkCode[$i]; // $gv_no 세팅 하여 각각 돌려서 입력
						$vThumbEct = $shoc->findIt(fn_vReplace($vThumbC[0]), fn_vReplace($vThumbC[1]));
						$vThumb[$i] = $vThumbEct[1][0];
					}
					$vWebUpdDh[$i] = fn_dateFormat($vWebUpdDh[$i]);
				}
				
				if ( $vSite == "foxtoon" ) $vNextTF = false;
			}

//for ( $i = 0; $i < sizeof($vTitle); $i++ ) {echo("<br>".$vTitle[$i]."/".$vThumb[$i]."/".$vLinkCode[$i]."/".$vWebUpdDh[$i]."/".$vSellYn[$i]);}
//echo("<pre>".$shoc->gv_readData."</pre>");
			// 셋은 항상 배열 크기가 같아야함. 아니면 에러
			if ( sizeof($vTitle) != sizeof($vLinkCode) 
			  || ( sizeof($vTitle) != sizeof($vThumb) && sizeof($vThumbC) >= 1 ) )  {
				fn_echo("<td>ERR!. 배열 크기 문제 vTitle:vLinkCode:vThumb = ".sizeof($vTitle).":".sizeof($vLinkCode).":".sizeof($vThumb)."</td>", "|ERR!. 배열 크기 문제 vTitle:vLinkCode:vThumb = ".sizeof($vTitle).":".sizeof($vLinkCode).":".sizeof($vThumb));
				break;
			}

			if ( sizeof($vTitle) == 0 ) {
				if ( $vSite == 'kakao' || $vSite == 'tstore' || $vSite == 'foxtoon_d' || $vSite == 'ttale' ) { // 이 사이트는 0일때 업데이트 종료. 이게 정상.
				} else if ( $vSite == 'naver_b' ) { // 이친구는 웹툰이 없어진걸로 판단 ks20151201
					fn_delToon($gv_idSeq);
					fn_echo("<td>삭제</td>", "|삭제 $gv_idSeq");
				} else {
					fn_echo("<td>ERR!. vTitle 배열 0</td>", "|ERR!. vTitle 배열 0");
					// TODO daum의 경우 이건 웹툰 삭제 또는 성인물. 웹툰을 지울까 하는데 과연 그래도 될지(오류로 배열이 0잡히면 멀쩡한 웹툰이 지워지니까)
					// nate도 그렇다. 얼럿. 백히스트리스크립트
					// kakao 완결건은 업데이트 안된다.
				}
				break;
			}

			/**************
			 * 다음 페이지를 읽어야 하는지 판단한다.
			 **************/
			if ( $vNextTF && sizeof($vNextPageC) >= 1 ) { // 다음페이지를 읽어야 하는 상태에서 다음페이지 존재여부를 확인하는 세팅이 있다면
				$vNextPage = $shoc->findIt(fn_vReplace($vNextPageC[0]), fn_vReplace($vNextPageC[1]));
				if ( empty($vNextPage[1][0]) ) $vNextTF = false; // 다음페이지의 주소 바인드. 배열이지만 0번째만 보면 되므로.
			} // $vNextPage[1][0]엔 다음 페이지 문자열이 들어있을 확률이 있으나, 그냥 존재하기만 하면 다음페이지로 가는것으로 코딩됐다.

			/**************
			 * 5. 쿼리 제작후 돌린다. 상태에 따라 업데이트 인서트 skip 처리한다.
			 **************/
			// 웹툰 제목 여부에 따라 쿼리를 만들어라. 조인을 위한 덤프테이블 작성
			$vDumpDual = "SELECT '' AS LINK_CODE, '' AS TITLE, '' AS THUMB_NAIL, '' AS SELL_YN, '' AS ORG_UPD_DH FROM DUAL"; // union all을 없애기위해 dump추가
			for ( $i = 0; $i < sizeof($vTitle); $i++ ) {
				if ( $vSite == "daum" && $vSellYn[$i] == "Y" ) {
					if ( strtotime("-40 days") < strtotime($vWebUpdDh[$i]) ) { // 최근 40일내의 유료면 pass
						continue; // 다음웹툰 유료는 제외한다. 정렬문제. ks20170212
					}
				}
				$gv_no = $vLinkCode[$i];
				$vDumpDual .= " UNION ALL SELECT TRIM('".addslashes($vLinkCode[$i])."') AS LINK_CODE, '".addslashes($vTitle[$i])."' AS TITLE, '"
				             .str_replace(fn_vReplace($row['THUMB_COMN']), "", $vThumb[$i])."' AS THUMB_NAIL, '"
							 .addslashes($vSellYn[$i])."' AS SELL_YN, '".addslashes($vWebUpdDh[$i])."' AS ORG_UPD_DH FROM DUAL";
			}

			/**************
			 * 링크코드를 추출. 비교를 위해 양쪽 모두를 생성
			 **************/
			$result = 
"SELECT DISTINCT
	    A.LINK_CODE, B.LINK_CODE AS ORG_LINK_CODE
	  , A.TITLE, B.TITLE AS ORG_TITLE
	  , A.THUMB_NAIL, B.THUMB_NAIL AS ORG_THUMB_NAIL
	  , A.SELL_YN, B.SELL_YN AS ORG_SELL_YN
	  , A.ORG_UPD_DH, B.ORG_UPD_DH AS ORG_UPD_DH_ORG
  FROM (".$vDumpDual.") A LEFT JOIN TB_WT003 B ON B.ID_SEQ = '$gv_idSeq' AND A.LINK_CODE = B.LINK_CODE ";
			//$vQuery1 = mysqli_query($conn, $result) or die("Error: ".mysqli_error($conn)) ; // DB에 없는 링크코드
			$vQuery1 = mysqli_query($conn, $result); // DB에 없는 링크코드

			$vInsertDataCnt = 0;
			/**************
			 * DB에 없는 링크코드만 인서트 작업 한다.
			 **************/

			while ( $row1 = mysqli_fetch_array($vQuery1) ) {
				if ( $row1['LINK_CODE'] == "" ) continue;
				
//echo ($row1['LINK_CODE']."/".$row1['LINK_CODE']."/".$row1['TITLE']."/".$row1['ORG_TITLE']."/".$row1['THUMB_NAIL']."/".$row1['ORG_THUMB_NAIL']
//.$row1['SELL_YN']."/".$row1['ORG_SELL_YN']."/".$row1['ORG_UPD_DH']."/".$row1['ORG_UPD_DH_ORG'] ."<Br>");

				if ( $row1['ORG_LINK_CODE'] == "" ) { // 빈값이면 인서트
					$vInsertDataCnt++;
					$vInsertQuery = 
"INSERT INTO TB_WT003 ( ID_SEQ, LINK_CODE, TITLE, THUMB_NAIL, SELL_YN, ORG_UPD_DH, LST_UPD_DH ) VALUES (
'$gv_idSeq', '".$row1['LINK_CODE']."', '".addslashes($row1['TITLE'])."', '".$row1['THUMB_NAIL']."', '".$row1['SELL_YN']."'
, IFNULL('".$row1['ORG_UPD_DH']."', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')), DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'))"; 

					mysqli_query($conn, $vInsertQuery);
				} else { // 아니면 기존값과 다른점이 있는지 체크하여 업데이트하거나 무시
					if ( $row1['TITLE']      != $row1['ORG_TITLE'] 
					  || $row1['THUMB_NAIL'] != $row1['ORG_THUMB_NAIL'] 
					  || $row1['SELL_YN']    != $row1['ORG_SELL_YN'] 
					  || $row1['ORG_UPD_DH'] != $row1['ORG_UPD_DH_ORG'] ) { // 업데이트하라
						$vInsertDataCnt++;
						$vInsertQuery = 
"UPDATE TB_WT003
    SET TITLE      = IFNULL('".$row1['TITLE']."', TITLE)
	  , THUMB_NAIL = IFNULL('".$row1['THUMB_NAIL']."', THUMB_NAIL)
	  , SELL_YN    = IFNULL('".$row1['SELL_YN']."', SELL_YN)
	  , ORG_UPD_DH = IFNULL('".$row1['ORG_UPD_DH']."', ORG_UPD_DH)
	  , LST_UPD_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')
  WHERE ID_SEQ = '$gv_idSeq' AND LINK_CODE = '".$row1['LINK_CODE']."'";
						mysqli_query($conn, $vInsertQuery);
					} else { // 무시하라
					}
				}
			}

			$vInsertCnt += $vInsertDataCnt; // ID_SEQ당 인서트 갯수 += 이번 루프 인서트 갯수
			$vPageNo++; // 아랫단 명령 위치 중요하다.
			if ( $gv_loopCnt != 0 && $vPageNo > $gv_loopCnt ) break; // 무한루프 방지. gv_loopCnt페이지까지만 루프돌린다.
			if ( $vSite == 'kakao' ) continue; // 최근웹툰이 가장뒷페이지에 있으므로 무조건 다음페이지 ks20150426
			if ( $vInsertDataCnt < sizeof($row1) || $vInsertDataCnt == 0 ) break; // 전체 페이지가 업데이트 된게 아니라면 그만. ks20151123
		}

		fn_echo("<td>$vInsertCnt</td>\n", "");

		if ( $vInsertCnt > 0 ) { // 인서트된 건이 있으면 , MAX_NO를 저장, 최종 수정시간 업데이트
			$vMaxUpdate = 
"UPDATE TB_WT001 A
    SET MAX_NO     = IFNULL((SELECT LINK_CODE FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ AND USE_YN != 'N' ORDER BY ORG_UPD_DH DESC, LINK_CODE DESC LIMIT 1), 0)
      , THUMB_NAIL = (SELECT THUMB_NAIL FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ ORDER BY ORG_UPD_DH DESC, LINK_CODE DESC LIMIT 1)
      , LST_UPD_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')
      , LST_CHK_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') ";
			if ( $row['COMP_YN'] == "Y" ) { // 완료건인데 업데이트가 되었다면 완료 상태를 N으로 바꾼다.
				$vMaxUpdate .= "    , COMP_YN = 'N', FST_INS_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') ";
			}
			if ( $row['TB_WT003_YN'] == "N" ) { // TB_WT003에 최초 등록이라면
				$vMaxUpdate .= "    , FST_INS_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') ";
			}

			$vMaxUpdate .= "     WHERE ID_SEQ = '$gv_idSeq'";

			mysqli_query($conn, $vMaxUpdate);
			$gv_successCnt++;
			if ( $vMode == "UPD" ) echo "<UPD>Y</UPD>"; // 클라이언트로 리턴. 업데이트건이 있다Y. 트래픽 이유로 주석처리.
		} else { // 업데이트 실패시 최종 체크시간 업데이트
			$vMaxUpdate = "UPDATE TB_WT001 SET LST_CHK_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') WHERE ID_SEQ = '$gv_idSeq'";
			mysqli_query($conn, $vMaxUpdate);
			if ( $vMode == "UPD" ) echo "<UPD>N</UPD>";
		}
		fn_echo("", "|".$vInsertCnt."\n");
	}
	fn_echo("</table></body>", "");

	if ( $vMode == "UPD_ALL" || $vMode == "UPD_MYLIST" ) echo "<UPD>$gv_successCnt</UPD>"; // ks20141030
	if ( $vMode == "FILE" || $vMode == "CRONTAB" ) @fclose($f); 
	mysqli_close($conn); // 디비 접속 끊기
	//////// 끝 ////////


	function fn_delToon($pIdSeq) {
		global $conn;
		$vDeleteQuery = 
"UPDATE TB_WT001 SET USE_YN = 'N'
      , FST_INS_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')
      , LST_UPD_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')
  WHERE ID_SEQ = '$pIdSeq'";
		mysqli_query($conn, $vDeleteQuery);
	}

	function fn_vReplace($pText) { // 변수들에 변수값을 치환
		global $gv_cid;
		global $gv_no;
		global $vPageNo;
		$vBefore = array("^cid", "^no", "^nextPage", "\$cid", "\$no", "\$nextPage");
		$vAfter  = array($gv_cid, $gv_no, $vPageNo, $gv_cid, $gv_no, $vPageNo);
		return str_replace($vBefore, $vAfter, $pText);
	}
	
	function fn_dateFormat($pDate) { // 2015.11.19 || 2015.11.19 || 15.11.19 || 2015-11-19 || 15.09.16
		$vDate = str_replace(array(".","-"," "), array("","",""), $pDate); // 11.02.13 2013-03-03 등 기호삭제
		if ( strlen($vDate) == 6 ) {
			$vDate = ((substr($vDate, 0, 2) >= "70")? "19" : "20").$vDate."000000"; // 130213 을 20130213000000 으로 변경.
		} else if ( strlen($vDate) == 8 ) {
			$vDate = $vDate."000000";
		}
		return $vDate;
	}

	// 파일로 로그를 남기느냐, html형식으로 보여주느냐
	// 화면에서 직접 들어온다면 table형식으로 표시될테고
	// 백그라운드로 프로그램 작성되면 로그파일이 만들어진다.
	// $pTag : 화면보기 모드일때 태그, $pFile : 파일 저장시
	function fn_echo($pTag, $pFile) {
		global $vMode;
		if ( $vMode == "FILE" || $vMode == "CRONTAB" ) {
			global $f;
			@fwrite($f, $pFile); 
		} else if ( $vMode == "UPD" ) { // 클라이언트에서 보내는 업데이트 싸인(특정웹툰)
		} else if ( $vMode == "UPD_MYLIST" ) { // ID_SEQ/ID_SEQ/ID_SEQ/...
		} else if ( $vMode == "UPD_ALL" ) { // 전체업데이트
		} else {
			global $vParam;
			echo $pTag;
			//if ( $vParam == "ALL" ) echo $pTag;
		}
	}

/* 자료 정정 쿼리. ks20150127
	UPDATE TB_WT001 A
    SET MAX_NO =                                                                              IFNULL((SELECT CASE LINK_CODE WHEN LINK_CODE + 0 THEN MAX(LINK_CODE + 0) ELSE MAX(LINK_CODE) END FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ), 0)
      , THUMB_NAIL = (SELECT THUMB_NAIL FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ AND LINK_CODE = IFNULL((SELECT CASE LINK_CODE WHEN LINK_CODE + 0 THEN MAX(LINK_CODE + 0) ELSE MAX(LINK_CODE) END FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ), 0))
      , LST_UPD_DH = (SELECT LST_UPD_DH FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ AND LINK_CODE = IFNULL((SELECT CASE LINK_CODE WHEN LINK_CODE + 0 THEN MAX(LINK_CODE + 0) ELSE MAX(LINK_CODE) END FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ), 0))
      , LST_CHK_DH = (SELECT LST_UPD_DH FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ AND LINK_CODE = IFNULL((SELECT CASE LINK_CODE WHEN LINK_CODE + 0 THEN MAX(LINK_CODE + 0) ELSE MAX(LINK_CODE) END FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ), 0))
  WHERE              (SELECT LST_UPD_DH FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ AND LINK_CODE = IFNULL((SELECT CASE LINK_CODE WHEN LINK_CODE + 0 THEN MAX(LINK_CODE + 0) ELSE MAX(LINK_CODE) END FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ), 0)) LIKE '20150126%'
*/
/* 두달간 업데이트 없는 연재 웹툰 종료처리 ks20150420
UPDATE TB_WT001 SET COMP_YN = 'Y', FST_INS_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), LST_UPD_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), LST_CHK_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') WHERE USE_YN != 'N' AND COMP_YN != 'Y' AND LST_UPD_DH < DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 MONTH), '%Y%m%d%H%i%s') AND SITE NOT IN ('daum_l', 'naver_b')
*/
/* 사이트별 최근 업데이스 시간(프로그램 미작동 여부 판별) ks20151122
SELECT SITE
     , MAX(LST_UPD_DH) AS LST_UPD_DH
     , MAX(FST_INS_DH) AS FST_INS_DH 
     , MAX(LST_CHK_DH) AS LST_CHK_DH 
  FROM TB_WT001
 GROUP BY SITE
 ORDER BY LST_UPD_DH ASC
 
 SELECT SITE
     , MAX(LST_UPD_DH) AS LST_UPD_DH
     , TIMESTAMPDIFF(HOUR,NOW(), MAX(LST_UPD_DH)) AS LST_UPD_DH
     , TIMEDIFF(NOW(), MAX(LST_UPD_DH)) AS LST_UPD_DH
     , MAX(FST_INS_DH) AS FST_INS_DH 
     , datediff (NOW(), MAX(FST_INS_DH)) AS FST_INS_DH 
     , TIMEDIFF(NOW(), MAX(FST_INS_DH)) AS FST_INS_DH 
     , MAX(LST_CHK_DH) AS LST_CHK_DH 
     , datediff (NOW(), MAX(LST_CHK_DH)) AS LST_CHK_DH 
     , TIMEDIFF(NOW(), MAX(LST_CHK_DH)) AS LST_CHK_DH 
  FROM TB_WT001
 WHERE SITE != ''
 GROUP BY SITE
 ORDER BY LST_UPD_DH DESC

*/

/* 제공중인 웹툰 갯수 ks20151122
SELECT COUNT(A.SITE), A.SITE
  FROM TB_WT001 A, TB_WT002 B
 WHERE EXISTS (SELECT * FROM TB_WT003 WHERE ID_SEQ = A.ID_SEQ)
   AND A.SITE = B.SITE
 GROUP BY A.SITE
*/