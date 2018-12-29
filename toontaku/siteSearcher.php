<?
	// 다음(daum)과 다음웹툰리그(daum_l)에 대한 신규 웹툰 검색
	//$pParam = "daum"; // 다음 웹툰 새 웹툰 찾기
	//$pParam = "daum_l"; // 다음 웹툰 리그 새 웹툰 찾기
	//$pParam = "naver"; // 네이버 웹툰 새 웹툰 찾기
	//$pParam = "naver_b"; // 네이버 베도 새 웹툰 찾기
	//봉인 - $vSite = "naver_d"; // 네이버 도전만화 새 웹툰 찾기 -- 이건 모바일 지원을 안해서 안올리는걸로하자. 봉인.
	// http://kyusiks.dothome.co.kr/toontaku/siteSearcher.php?pParam=daum
	//$pParam = "nate";
	//$pParam = "kakao"; // 카카오 웹툰 추가 ks20140619
	//$pParam = "olleh"; // 올레마켓 웹툰 추가 ks20150110
	//$pParam = "tworld"; // 티스토어 웹툰 추가 ks20150110
	//$pParam = "ttale"; // 티테일 추가 ks20150215 삭제 ks20160808
	//$pParam = "lezhin"; // 리진코믹스 ks20150215
	//$pParam = "foxtoon"; // 폭스툰 ks20150829
	//$pParam = "foxtoon_d"; // 폭스툰 도전 ks20151119
	//$pParam = "battlec"; // 배틀코믹스 ks20160706
	//$pParam = "battlec_d"; // 배틀코믹스 도전만화 ks20160706	
	//$pParam = "comiccube"; // 코믹큐브 ks20160707	

	/******* 
   * 현재 https://mywebcron-com.loopiasecure.com/ 사이트에서 kyusiks.kr 구글계정으로 크론탭 돌리고 있다.
   * http://kyusiks.dothome.co.kr/toontaku/siteSearcher.php?pParam=ALL&vMode=FILE
   * http://kyusiks.dothome.co.kr/toontaku/siteSearcher.php?pParam=daum_l&vMode=FILE
	 *******/
//exit();
	include "../include/db_ok.php";
	include "../include/shoc.class.php";

	$pMode  = $_REQUEST["pMode" ];
	$pParam = $_REQUEST["pParam"];
	$vMyNm  = $_REQUEST["pMyNm" ];
	$vToTelegramMsg = "";

	if ( $pMode == "FILE" ) { // 파일 로그를 남기는 모드면 파일 생성
		$exist = "../LOG/siteSearcher_$pParam.".date("YmdHis");
		$f = @fopen($exist, "w");

	} else if ( $pMode == "SITE_SEARCHER" ) { // 앱에서 누군가가 하루 한번씩 호출. ks20150119
		$vNumber = substr(date("d"), -1); // 날짜의 뒷숫자.
		if ( $vNumber == "1" || $vNumber == "6" ) { // 1900
			$vSiteList = array("daum", "nate", "battlec", "foxtoon_d"); // , "ttale"
		} else if ( $vNumber == "2" || $vNumber == "7" ) { //
			$vSiteList = array("naver", "kakao", "comiccube", "battlec_d");
		} else if ( $vNumber == "3" || $vNumber == "8" ) {
			$vSiteList = array("daum_l");
		} else if ( $vNumber == "4" || $vNumber == "9" ) {
			$vSiteList = array("foxtoon","naver_b");
		} else if ( $vNumber == "5" || $vNumber == "0" ) {
			$vSiteList = array("olleh", "tstore", "lezhin");
		}
	}

	if ( $pParam == "ALL" ) {  // , "daum_l" 이건 많아서 뺐다. foxtoon추가ks20150829
		$vSiteList = array( "daum", "naver", "nate", "kakao", "naver_b"
		                  , "olleh", "tstore", "lezhin", "foxtoon" // , "ttale"
		                  , "foxtoon_d", "battlec", "battlec_d", "comiccube");
	} else if ( $pParam != "") {
		$vSiteList = array($pParam);
	}

	if ( count($vSiteList) == 0 ) {
		logDB($vMyNm, "siteSearcher/$pParam/$pMode/$vMode/에러");
		return;
	}

	logDB($vMyNm, "siteSearcher/$pParam/$pMode/$vMode");

	fn_echo("<link href='./wtb.css' rel='stylesheet'/><body><table border=3>", "");

	for ( $i = 0; $i < count($vSiteList); $i++ ) {
		fn_reading($vSiteList[$i]);
		fn_echo("</table><table border=3>", "");
	}

	fn_echo("</table></body>", "");
	if ( $pMode == "FILE" ) @fclose($f); 
	mysqli_close($conn); // 디비 접속 끊기
/*
	if ( $vToTelegramMsg != "" ) {
		$vToTelegramMsg = "http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."\n".$vToTelegramMsg;
		$url = "http://anglab.dothome.co.kr/toontaku/test.php?room_id=159284966&msg=".urlencode($vToTelegramMsg);
	  $rest = curl_init();
	  curl_setopt($rest, CURLOPT_URL, $url);
	  curl_setopt($rest, CURLOPT_POST, false);
	  curl_setopt($rest, CURLOPT_RETURNTRANSFER, true);
	  curl_exec($rest);
	  curl_close($rest);
	}
  */
	//////// 끝 ////////


	function fn_reading($vSite) {
		global $conn;
		$vJson = array("daum", "daum_l", "olleh", "tstore", "lezhin", "piki");
		$vUrlArry = array();
		
		$vUrl = "";
		$vCntIns = 0;
		$vCntUpd = 0;
		$vCntCid = 0;
		$vCidC      = array(); // 웹툰ID 범위
		$vFinishYnC = array(); // 완결여부 범위
		$vNameC     = array(); // 웹툰제목 범위
		$vRangeC    = array(); // 크롤링할 범위
		$vArtistC   = array(); // 작가 범위

		if ( $vSite == "daum" ) { // ks20151117
			$vUrl     = "http://webtoon.daum.net/data/pc/webtoon/"; // 다음 웹툰
			$vUrlArry = array("", "list_serialized/mon", "list_serialized/tue", "list_serialized/wed", "list_serialized/thu", "list_serialized/fri", "list_serialized/sat", "list_serialized/sun", "list_serialized/free", "list_finished");
		} else if ( $vSite == "daum_l" ) { // ks20151117 ks20151114 다음 웹툰 리그는 매이저, 마이너 두개
			//$vUrl = "http://webtoon.daum.net/data/pc/leaguetoon/list?level=minor&page_size=200&page_no="; // 다음 웹툰 리그 마이너... 6673갠데...
			$vUrl     = "http://webtoon.daum.net/data/pc/leaguetoon/list?level=major&page_size=200&page_no="; // 뒤에 하드코딩된 주소가 있으니 유의할것.
		} else if ( $vSite == "olleh" ) { // 올레마켓 웹툰 ks20151120 $snoopy->referer 때문에 힘들었다. ks20150110
			$vUrl     = "http://webtoon.olleh.com/api/work/getWorkList.kt";
		} else if ( $vSite == "tstore" ) { // T스토어 웹툰 ks20151118 ks20150122
			$vUrl     = "http://m.onestore.co.kr/mobilepoc/webtoon/weekdayListDetail.omp?weekday=";
		} else if ( $vSite == "lezhin" ) { // 레진코믹스 웹툰 ks20151119 ks20150215
			$vUrl     = "http://www.lezhin.com/#scheduled"; // #scheduled(#printed #completed을 포함한다)
			$vRangeC  = array(".ComicHomePage.init({comics:", ",novels:");

		} else if ( $vSite == "naver" ) { // ks20151117
			$vUrl     = "http://m.comic.naver.com/webtoon/weekday.nhn?order=TitleName"; // 네이버 웹툰
			$vUrlArry = array("", "mon", "tue", "wed", "thu", "fri", "sat", "sun");
			$vCidC    = array("titleId=","&");
			$vFinishYnC = array();
			$vNameC   = array("toon_name\"><strong><span>", "<");
			$vArtistC = array("class=\"sub_info\">", "<");
		} else if ( $vSite == "naver_b" ) { // ks20151117
			$vUrl     = "http://m.comic.naver.com/bestChallenge/genre.nhn?genre=all&order=TitleName&page="; // 네이버 베도
			$vCidC    = array("titleId=","\"");
			$vFinishYnC = array("regex:\/>(?:.*?\n.*?\n.*?\n.*?\n.*?\n)", "regex:(\n.*?\n.*?)\s<\/span>");
			$vNameC   = array("<strong><span>", "<");
			$vArtistC = array("class=\"sub_info\">", "<");
		} else if ( $vSite == "nate" ) { // 네이트 연재중 ks20151117
			$vUrl     = "http://comics.nate.com/webtoon/index.php?mode=genre"; // 네이트 웹툰(연재중)
			$vRangeC  = array("<!-- 장르별 웹툰 -->", "<!-- // 장르별 웹툰 -->");
			$vCidC    = array("btno=","\" title");
			$vNameC   = array("title=\"", "\" class");
			$vArtistC = array("wtl_author\">", "<");
		} else if ( $vSite == "kakao" ) { // 카카오웹툰 ks20151117
			$vUrl     = "http://page.kakao.com/main/ajaxCallWeeklyList?day=0&categoryUid=10&subCategoryUid=1000"; // 페이지업데이트됨 ks20141013 ks20150317 ks20160321 ks20170212
			$vCidC    = array("<a href=\"/home/", "?");
			$vNameC   = array("icon_count.png\" alt=\"", "\"");
			$vArtistC = array("• ", "\n");
		} else if ( $vSite == "foxtoon" ) { // 폭스툰 ks20151119 ks20150829
			$vUrl     = "http://www.foxtoon.com/comic";
			$vRangeC  = array("comic_schedule_table", "footer");
			$vCidC    = array("comic/", "\"");
			$vNameC   = array("title=\"", "\"");
			$vArtistC = array("<div class=\"author\">", "<");
		} else if ( $vSite == "foxtoon_d" ) { // 폭스툰 도전 ks20151119
			$vUrl     = "http://www.foxtoon.com/challenge/comic/?genre=0&p=";
			$vRangeC  = array("section_content", "comic_pagination");
			$vCidC    = array("comic/", "\"");
			$vNameC   = array("title=\"", "\"");
			$vArtistC = array("class=\"author\">", "<");
		} else if ( $vSite == "daum_s" ) { // 다음스포츠 ks20151208
			$vUrl     = "http://sports.media.daum.net/sports/cartoon/";
			$vRangeC  = array("<div class=\"cartoon_list\">", "3.14.15.1/dist/lib/clounge.min.js");
			$vCidC    = array("<a href=\"/sports/cartoon/", "\"");
			$vNameC   = array("<strong class=\"tit_g\">", "<");
			$vArtistC = array("<strong class=\"tit_g\">", "<");

		} else if ( $vSite == "battlec" ) { // 배틀코믹스 ks20160706
			$vUrl     = "http://www.battlecomics.co.kr/webtoons";
			$vRangeC  = array("id='week-table'", "<span>랜덤</span>"); // id='complete'  popular
			$vCidC    = array("<a href=\"/webtoons/", "\"");
			$vNameC   = array("<h4 class='content__title'>", "<");
			$vArtistC = array("<small class='content__author'>", "<");

		} else if ( $vSite == "battlec_d" ) { // 배틀코믹스 도전만화 ks20160706
			$vUrl     = "http://www.battlecomics.co.kr/challenge/webtoons?order=recent&page=";
			$vRangeC  = array("webtoons__ordering", "pagination");
			$vCidC    = array("<a href=\"/challenge/webtoons/", "\"");
			$vNameC   = array("regex:webtoon-card__name'>(?:\s*)<span>", "<");
			$vArtistC = array("regex:webtoon-card__author'>(?:\s*)<span>", "<");

		} else if ( $vSite == "comiccube" ) { // 코믹큐브 ks20160707
			$vUrl     = "http://www.bookcube.com/toon/data/_weekday_list.asp?page=webtoon&sort=up&pageNum=1&day_num="; // 뒷쪽에서 주소가 바뀐다.
			//$vUrl     = "http://www.bookcube.com/toon/data/_complete_list.asp?page=webtoon&sort=up&pageNum=1&temp="; // temp는 뒤에 변수($vPage) 무마용
			$vCidC    = array("webtoon_num=", "\"");
			$vNameC   = array("<p>", "<");
			$vArtistC = array("<p class=\"dayInfo\">", "<");

		} else if ( $vSite == "ttale" ) { // 티테일 ks20160708 웹툰 ks20151119 ks20150215
			return; // 티테일 서비스 종료로 인한 삭제 ks20160808
			$vUrl     = "http://ttale.com/AllWebtoon?&p=";
			$vCidC    = array("blog.ttale.com:80/./", "\"");
			$vCidC    = array("regex:\.\/((.*?)\/webtoon\/","regex:)\"");
			$vNameC   = array("margin-bottom:5px;\">", "regex:(?:|\s)<"); // 가장뒤의 엔터 포함
 			$vArtistC = array("argin-bottom:4px;\">", "<");
		} else {
			return;
		}

		$shoc = new shoc; // 페이지를 읽어오기 위해서 쇽 클래스 호출
		$vPageNo = 1;
		$vNextTF = true;
		$vAntiInfinityLoop = 0; // 무한루프방지. sizeof($cid) == 0 인 경우가 20 루프 이상 되면 강제 종료
		$vAntiInfinityBeforeUrl = ""; // 무한루프방지. 이전 주소를 저장해서, 지금 주소와 같은지 판단한다.

		while ( $vNextTF ) {
			if ( $vAntiInfinityLoop > 100 ) $vNextTF = false; // 배열을 읽어오지 못하는 경우가 100번이면 루프 종료.(무한루프방지)

			/**************
			 * 1. 각 사이트별로 읽을 페이지 파라메터 세팅
			 **************/
			if ( $vSite == "daum" ) {
				$vUrlCombi = $vUrl.$vUrlArry[$vPageNo]; // 요일별,완결자료씩.
			} else if ( $vSite == "naver" ) {
				if ( $vPageNo >= 8 ) { // 완료건
					$vUrlCombi = $vUrl."&week=fin&page=".($vPageNo-7); // vPageNo 가 8이므로 1부터 시작하기위해 7을 뺐다
				} else { // 요일별
					$vUrlCombi = $vUrl."&week=".$vUrlArry[$vPageNo]."&page=1"; // 요일별로는 1페이지밖에없다.
				}
			//} else if ( $vSite == "naver_b" ) {
			//	$vUrlCombi = $vUrl."&page=".$vPageNo; // 매 페이지 호출
			} else if ( $vSite == "foxtoon_d" || $vSite == "daum_l" || $vSite == "battlec_d"
			         || $vSite == "comiccube" || $vSite == "tstore" || $vSite == "ttale"
			         || $vSite == "naver_b" ) {
				$vUrlCombi = $vUrl.$vPageNo;
			} else {
				$vUrlCombi = $vUrl;
			}

			$shoc->setUrl($vUrlCombi);

			/**************
			 * 2. 긁어온 사이트에서 내용 긁을 범위만 지정한다. (생략사이트들도 많음)
			 **************/
			if ( sizeof($vRangeC) >= 1 ) $shoc->setRange($vRangeC[0], $vRangeC[1]);

			/**************
			 * 3. cid, 완결여부, 웹툰제목을 바인딩한다. 웹툰제목은 바인딩 안될수도있다. 열외하고 진행 가능.
			 **************/
			$vCid      = array();
			$vName     = array();
			$vFinishYn = array();
			$vArtist   = array();
			if ( in_array($vSite, $vJson) ) { // json 형식의 경우 ks20151113
				$shoc->findIt("","");
				
				$decode = json_decode($shoc->gv_readData, true);
				if ( $vSite == "daum" ) {
					for ( $i = 0; $i < sizeof($decode['data']); $i++ ) {
						array_push($vCid     , $decode['data'][$i]['nickname']);
						array_push($vName    , $decode['data'][$i]['title']);
						array_push($vFinishYn, ($decode['data'][$i]['finishYn'] == "N")? "" : "Y"); // vFinishYn 이 Y,N,A으로 들어와서. Y,A는 Y로저장
						array_push($vArtist  , fn_jsonArrayJoin($decode['data'][$i]['cartoon']['artists'], 'name'));
						//echo("<br>".$vFinishYn[$i]."/".$vCid[$i]."/".$vName[$i]."/".$vArtist[$i]);
					}
				} else if ( $vSite == "daum_l" ) {
					for ( $i = 0; $i < sizeof($decode['data']); $i++ ) {
						array_push($vCid     , $decode['data'][$i]['id']);
						array_push($vName    , $decode['data'][$i]['title']);
						array_push($vFinishYn, $decode['data'][$i]['finishYn']);
						array_push($vArtist  , fn_jsonArrayJoin($decode['data'][$i]['cartoon']['artists'], 'name'));
					}
				} else if ( $vSite == "tstore" ) {
					for ( $i = 0; $i < sizeof($decode['webtoonVO']['webtoonList']); $i++ ) {
						array_push($vCid     , $decode['webtoonVO']['webtoonList'][$i]['channelId']);
						array_push($vName    , $decode['webtoonVO']['webtoonList'][$i]['prodNm']);
						array_push($vFinishYn, $decode['webtoonVO']['webtoonList'][$i]['etcYn']);
						array_push($vArtist  , $decode['webtoonVO']['webtoonList'][$i]['artistNm']);
					}
				} else if ( $vSite == "lezhin" ) {
					for ( $i = 0; $i < sizeof($decode); $i++ ) {
						array_push($vCid     , $decode[$i]['alias']);
						array_push($vName    , $decode[$i]['display']['title']);
						array_push($vFinishYn, ($decode[$i]['state'] == "completed")? "Y" : ""); // state가 completed면 완결. scheduled연재
						array_push($vArtist  , fn_jsonArrayJoin($decode[$i]['artists'], 'name'));
					}
				} else if ( $vSite == "olleh" ) {
					for ( $i = 0; $i < sizeof($decode['workList']); $i++ ) {
						array_push($vCid     , $decode['workList'][$i]['webtoonseq']);
						array_push($vName    , $decode['workList'][$i]['webtoonnm']);
						array_push($vFinishYn, $decode['workList'][$i]['endyn']);
						array_push($vArtist  , $decode['workList'][$i]['authornm1']);
					}
				}
			} else { // 일반 크롤링
				$vCid = $shoc->findIt($vCidC[0], $vCidC[1]);
				$vName = $shoc->findIt($vNameC[0], $vNameC[1]);
				if ( sizeof($vFinishYnC) >= 1 ) $vFinishYn = $shoc->findIt($vFinishYnC[0], $vFinishYnC[1]);
				if ( sizeof($vArtistC)   >= 1 ) $vArtist   = $shoc->findIt($vArtistC[0], $vArtistC[1]);

				$vCid = $vCid[1];
				$vName = $vName[1];
				if ( sizeof($vFinishYnC) >= 1 ) $vFinishYn = $vFinishYn[1];
				if ( sizeof($vArtistC)   >= 1 ) $vArtist   = $vArtist[1];
			}
//echo($shoc->gv_readData);
//for ( $i = 0; $i < sizeof($vCid); $i++ ) {	echo("<br>".$vFinishYn[$i]."/".$vCid[$i]."/".$vName[$i]."/".$vArtist[$i]);}

			// 항상 두 배열 크기가 같아야함. 아니면 에러
			if ( sizeof($vFinishYnC) >= 1 && sizeof($vCid) != sizeof($vFinishYn) ) {
				fn_echo("<tr><td>에러Fin</td><td>".sizeof($vCid)."</td><td>".sizeof($vFinishYn)."</td><td>".$vPageNo."</td><td></td></tr>", "에러남 : ".sizeof($vCid)."/".sizeof($vFinishYn)."/".$vPageNo);
				$vPageNo++;
				$vAntiInfinityLoop++;
				continue;
			}
			if ( sizeof($vCid) != sizeof($vName) ) {
				fn_echo("<tr><td>에러Name</td><td>".sizeof($vCid)."</td><td>".sizeof($vName)."</td><td>".$vPageNo."</td><td></td></tr>", "에러남 : ".sizeof($vCid)."/".sizeof($vName)."/".$vPageNo);
				$vPageNo++;
				$vAntiInfinityLoop++;
				exit;
				continue;
			}

			/**************
			 * 4. 가공이 필요한 경우 가공하라
			 * $vNextTF 를 세팅하라.
			 **************/
			if ( $vSite == "daum" ) {
				if ( sizeof($vUrlArry) - 1 == $vPageNo ) $vNextTF = false;
			} else if ( $vSite == "daum_l" ) {
				if ( sizeof($vCid) == 0 ) {
					if ( $vUrl == "http://webtoon.daum.net/data/pc/leaguetoon/list?level=major&page_size=200&page_no=" ) {
						$vUrl = "http://webtoon.daum.net/data/pc/leaguetoon/list?level=minor&page_size=200&page_no="; // 다음 웹툰 리그 마이너... 6673갠데...
						$vPageNo = 1;
						continue; // 두번째 주소로 1페이지부터 다시 루프.
					} else {
						$vNextTF = false;
					}
				}
			} else if ( $vSite == "naver" ) {
				if ( $vPageNo < 8 ) {
					for ( $i = 0; $i < sizeof($vCid); $i++ ) {
						$vFinishYn[$i] = "";
					}
					$vNextTF = true;
				} else {
					for ( $i = 0; $i < sizeof($vCid); $i++ ) {
						$vFinishYn[$i] = "Y";
					}
					$vNextTF = ( strpos($shoc->gv_readData, "nextButton\">다음") !== false );
				}
			} else if ( $vSite == "naver_b" ) { //  || $vSite == "naver_d"
				for ( $i = 0; $i < sizeof($vCid); $i++ ) {
					$vFinishYn[$i] = ( strpos($vFinishYn[$i], "end") !== false )? "Y" : "";
				} // vFinishYn 문자열에 <span class="end"></span>이게 포함되어있으면 완결이다.
	
				// 다음페이지 라는 단어가 소스에 있으면 다음 페이지 로딩 ok
				$vNextTF = ( strpos($shoc->gv_readData, "nextButton\">다음") !== false );
	
			} else if ( $vSite == "nate" ) {
				if ( $vPageNo == 1 ) { // 네이트는 1페이지는 연재중인것만
					$vNextTF = true;
					$vUrl    = "http://comics.nate.com/webtoon/finish.php?category=2"; // 네이트 웹툰(완결)
					$vRangeC = array("<!-- 장르별 웹툰 -->", "<!-- // 장르별 완결 웹툰 -->");
					$vCidC   = array("series/","_L");
					$vNameC  = array("_L.gif\" alt=\"", "\"");
				} else { // 2페이지는 완료인걸로 주소를 바꾼다.
					for ( $i = 0; $i < sizeof($vCid); $i++ ) {
						$vFinishYn[$i] = "Y"; // 연재완료건만 있다.
					}
					$vNextTF = false; // 네이트는 한번의 페이지 로딩으로 끝
				}
			} else if ( $vSite == "kakao" ) {
				$vNextTF = false; // 카카오는 한번의 페이지 로딩으로 끝
			} else if ( $vSite == "ttale" ) {
				if ( sizeof($vCid) == 0 ) $vNextTF = false;
			} else if ( $vSite == "foxtoon" ) {
				$vNextTF = false;
			} else if ( $vSite == "foxtoon_d" ) {
				if ( sizeof($vCid) == 0 ) $vNextTF = false;
			} else if ( $vSite == "lezhin" ) {
				$vNextTF = false; // 한페이지
			} else if ( $vSite == "olleh" ) {
				$vNextTF = false; // 한번의 페이지 로딩으로 끝
			} else if ( $vSite == "daum_s" ) {
				$vNextTF = false; // 한번의 페이지 로딩으로 끝
			} else if ( $vSite == "battlec" ) { // 배틀코믹스. url은 같지만 $vPagge   
				if ( $vPageNo == 1 ) { // 1페이지는 연재중인것만
					$vNextTF = true;
					$vUrl     = "http://www.battlecomics.co.kr/webtoons#complete";
					$vRangeC  = array("id='complete'", "id='popular'");
					$vNameC   = array("regex:webtoon-card__name'>(?:\s*)<span>", "<");
					$vArtistC = array("regex:webtoon-card__author'>(?:\s*)<span>", "<");
				} else { // 2페이지는 완료인걸로 주소를 바꾼다.
					for ( $i = 0; $i < sizeof($vCid); $i++ ) {
						$vFinishYn[$i] = "Y"; // 연재완료건만 있다.
					}
					$vNextTF = false; // 네이트는 한번의 페이지 로딩으로 끝
				}
			} else if ( $vSite == "battlec_d" ) {
				if ( sizeof($vCid) == 0 ) $vNextTF = false;
			} else if ( $vSite == "comiccube" ) {
				if ( $vPageNo == 8 ) { // 1~8페이지는 연재중. 다음 로딩은 완료 url			
					$vUrl     = "http://www.bookcube.com/toon/data/_complete_list.asp?page=webtoon&sort=up&pageNum=1&temp="; // temp는 뒤에 변수($vPage) 무마용
				} else if ( $vPageNo >= 9 ) { // 완료 1페이지 로딩 후 종료
					for ( $i = 0; $i < sizeof($vCid); $i++ ) {
						$vFinishYn[$i] = "Y"; // 9페이지는 모두 완료. 
					}
					$vNextTF = false;
				}
			}

			/**************
			 * 5. 쿼리 제작후 돌린다. 상태에 따라 업데이트 인서트 skip 처리한다.
			 **************/
			// 웹툰 제목 여부에 따라 쿼리를 만들어라. 조인을 위한 덤프테이블 작성
			$vDumpDual = "SELECT '' AS CID, '' AS COMP_YN, '' AS NAME, '' AS ARTIST FROM DUAL"; // union all을 없애기위해 dump추가
			for ( $i = 0; $i < sizeof($vCid); $i++ ) {
//echo("<br>".$vFinishYn[$i]."/".$vCid[$i]."/".$vName[$i]."/".$vArtist[$i]);
				$vDumpDual .= " UNION ALL SELECT TRIM('".addslashes($vCid[$i])."') AS CID, '".addslashes($vFinishYn[$i])."' AS COMP_YN, '".addslashes($vName[$i])."' AS NAME, '".addslashes($vArtist[$i])."' AS ARTIST FROM DUAL";
			}

			if ( $vSite == "daum_l" ) { // ks20151229 다음웹툰리그는 10000번대 미만 건들이 0014, 14 이런식으로 중복 검색되어 아래와 같이 코딩(0014만 살아남음) CASE WHEN LENGTH(CID) < 4 THEN LPAD(CID, 4, '0') ELSE CID END CID
				$vDumpDual = "SELECT 0 AS CID, '' AS COMP_YN, '' AS NAME, '' AS ARTIST FROM DUAL"; // union all을 없애기위해 dump추가
				for ( $i = 0; $i < sizeof($vCid); $i++ ) {
					$vDumpDual .= " UNION ALL SELECT CASE WHEN LENGTH(TRIM('".addslashes($vCid[$i])."')) < 4 THEN LPAD(TRIM('".addslashes($vCid[$i])."'), 4,'0') ELSE TRIM('".addslashes($vCid[$i])."') END CID, '".addslashes($vFinishYn[$i])."' AS COMP_YN, '".addslashes($vName[$i])."' AS NAME, '".addslashes($vArtist[$i])."' AS ARTIST FROM DUAL";
				}
			}

			// 중복은 알아서 잡아준다.
			$result = "SELECT DISTINCT A.NAME, B.NAME AS NAME_ORG, A.CID, CASE A.COMP_YN WHEN 'Y' THEN 'Y' ELSE '' END AS C1, CASE B.COMP_YN WHEN 'Y' THEN 'Y' ELSE '' END AS C2, TRIM(B.CID) AS CID2, ID_SEQ, A.ARTIST, B.ARTIST AS ARTIST_ORG FROM (".$vDumpDual.") AS A LEFT JOIN TB_WT001 B ON TRIM(B.CID) = A.CID AND B.USE_YN != 'N' AND B.SITE = '$vSite'";
			$vQuery = mysqli_query($conn, $result);
			while ( $row = mysqli_fetch_array($vQuery) ) { // 업데이트 대상건 루프
				if ( $row['CID' ] == "" ) continue;
				if ( $row['NAME'] == "" ) continue; // ks20151201

				if ( $row['CID'] != $row['CID2'] ) { // CID 자체가 등록되어있지 않으면 인서트
					fn_insert($row, $vSite);
					$vCntIns++; // 인서트 카운터
					fn_echo("<tr><td>".$row['NAME']."</td><td>".$row['CID']."<br>".$row['ARTIST']."</td><td>".$row['C2']."<br>".$row['C1']."</td><td>ins</td></tr>", "인\n");
				} else {
					if ( $row['C1'] != $row['C2']  // 완료여부가 일치하지 않으면 완료여부만 업데이트
					  || $row['ARTIST'] != $row['ARTIST_ORG'] ) {
						fn_update($row, $row['C1']);
						$vCntUpd++; // 업데이트 카운터
						fn_echo("<tr><td>".$row['NAME']."</td><td>".$row['CID']."<br>".$row['ARTIST']."</td><td>".$row['C2']."<br>".$row['C1']."</td><td>upd</td></tr>", "업\n");
					} else {
						// 이름은 업데이트 안한다.
						fn_updChkTime($row['ID_SEQ']);
					}
				}
				$vCntCid++; // 전체 검토 웹툰 갯수 카운터 ks20151124
			}

			if ( $vSite == "kakao" || $vSite == "foxtoon" ) { // 카카오는 완결건 검색이 없다. 위의 대상에서 빠진게 완결건. // foxtoon추가ks20150829 || $vSite == "ttale" 
				$result = "SELECT A.ID_SEQ, A.NAME, A.CID, 'Y' AS C1 FROM TB_WT001 A LEFT JOIN ($vDumpDual) B ON A.CID = B.CID WHERE B.CID IS NULL AND A.SITE = '$vSite' AND A.COMP_YN != 'Y' ";
				$vQuery = mysqli_query($conn, $result);

				while ( $row = mysqli_fetch_array($vQuery) ) { // 업데이트 대상건 루프
					fn_update($row, "Y");
					fn_echo("<tr><td>".$row['NAME']."</td><td>".$row['CID']."<br>".$row['ARTIST']."</td><td>".$row['C2']."<br>".$row['C1']."</td><td>upd</td></tr>", "업\n");
				}
			}
			$vPageNo++;
//echo(sizeof($vCid)." : ".$vUrlCombi."<br>");
			/**************
			 * 6. 무한루프 방지 로직.
			 **************/
			if ( sizeof($vCid) == 0 ) $vAntiInfinityLoop++;
			if ( $vAntiInfinityBeforeUrl == $vUrlCombi ) $vNextTF = false; // 이전에 읽은 페이지를 다시 읽는 경우는 없다.
			$vAntiInfinityBeforeUrl = $vUrlCombi;
		}
		$vCntUnChk = 0;
		if ( $vSite == "naver" ) { // 최종적으로 체크한 웹툰 이외의 것들을 없앤다. 
			$vCntUnChk = fn_updUnChkToons($vSite, "C"); // 검색 안된것들 완결처리
		}
		
		
		fn_echo("<tr><td>$vSite</td><td>INS[$vCntIns] UPD[$vCntUpd] ALL[$vCntCid] UNCHK[$vCntUnChk]</td><td></td><td></td></tr>", "$vSite : INS[$vCntIns] UPD[$vCntUpd] ALL[$vCntCid] UNCHK[$vCntUnChk]");
		
		global $vToTelegramMsg;
		$vToTelegramMsg .= "\n[$vSite] : INS[$vCntIns] UPD[$vCntUpd] ALL[$vCntCid] UNCHK[$vCntUnChk]";
	}

	/**************
	 * CID없는 웹툰 인서트
	 **************/
	function fn_insert($row, $vSite) {
		global $conn;
		mysqli_query($conn, 
"INSERT INTO TB_WT001 ( 
	CID, COMP_YN, SITE, NAME, ARTIST, ID_SEQ, FST_INS_DH, LST_UPD_DH, LST_CHK_DH
 ) VALUES (
   '".$row['CID']."', '".$row['C1']."', '$vSite', '".addslashes($row['NAME'])."', '".addslashes($row['ARTIST'])."'
 , IFNULL((SELECT MIN(ID_SEQ) FROM TB_WT001 A WHERE NAME = ''),(SELECT MAX(A.ID_SEQ) + 1 FROM TB_WT001 A))
 , DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'))
 ON DUPLICATE KEY 
 UPDATE CID = '".$row['CID']."', MAX_NO = '', THUMB_NAIL = '', COMP_YN = '".$row['C1']."', USE_YN = ''
, SITE = '$vSite', NAME = '".addslashes($row['NAME'])."', ARTIST = '".addslashes($row['ARTIST'])."'
, FST_INS_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), LST_UPD_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), LST_CHK_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') ");
	}

	/**************
	 * 완결여부, 작품명, 아티스트 변경툰 업데이트
	 **************/
	function fn_update($row, $pYn) {
		global $conn;
		mysqli_query($conn, 
"UPDATE TB_WT001
    SET COMP_YN = '$pYn'
	  , NAME = IFNULL('".addslashes($row['NAME'])."', NAME)
	  , ARTIST = IFNULL('".addslashes($row['ARTIST'])."', ARTIST)
	  , FST_INS_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')
	  , LST_UPD_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')
	  , LST_CHK_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')
  WHERE ID_SEQ = '".$row['ID_SEQ']."'");
	}

	/**************
	 * 그냥 체크만 했다.
	 **************/
	function fn_updChkTime($pIdSeq) {
		global $conn;
		mysqli_query($conn, "UPDATE TB_WT001 SET LST_CHK_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') WHERE ID_SEQ = '$pIdSeq'");
	}

	/**************
	 * 체크되지 않은 웹툰들을 완결/삭제 처리한다.
	 **************/
	function fn_updUnChkToons($pSite, $pCompDel) {
		global $conn; // LST_CHK_DH 가 최근 24시간내에 없으면 (=지금 체그 안됐으면) C완결처리하거나 D삭제처리
		if ( $pCompDel == "C" ) { // 완결처리
			mysqli_query($conn, "UPDATE TB_WT001 SET COMP_YN = 'Y', LST_UPD_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') WHERE SITE = '$pSite' AND LST_CHK_DH < DATE_FORMAT(SUBTIME(NOW(), '00:10'), '%Y%m%d%H%i%s') AND COMP_YN != 'Y' ");
			//mysqli_query($conn, "UPDATE TB_WT001 SET COMP_YN = 'Y', LST_UPD_DH = CASE WHEN COMP_YN = 'Y' THEN LST_UPD_DH ELSE DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') END WHERE SITE = '$pSite' AND LST_CHK_DH < DATE_FORMAT(SUBTIME(NOW(), '00:10'), '%Y%m%d%H%i%s')");
		} else if ( $pCompDel == "D" ) { // 삭제처리
			mysqli_query($conn, "UPDATE TB_WT001 SET USE_YN = 'N', LST_UPD_DH = DATE_FORMAT(NOW(), '%Y%m%d%H%i%s') WHERE SITE = '$pSite' AND LST_CHK_DH < DATE_FORMAT(SUBTIME(NOW(), '00:10'), '%Y%m%d%H%i%s') AND USE_YN != 'N' ");
		}
		return mysqli_affected_rows($conn);
	}

	/**************
	 * 파일로 로그를 남기느냐, html형식으로 보여주느냐
	 * 화면에서 직접 들어온다면 table형식으로 표시될테고
	 * 백그라운드로 프로그램 작성되면 로그파일이 만들어진다.
	 * $pTag : 화면보기 모드일때 태그, $pFile : 파일 저장시
	 **************/
	function fn_echo($pTag, $pFile) {
		global $pMode;
		if ( $pMode == "FILE" ) {
			global $f;
      @fwrite($f, $pFile); 
		} else if ( $pMode == "4" ) {
		} else if ( $pMode == "SITE_SEARCHER" ) {
		} else {
			echo $pTag;
		}
	}

	function fn_jsonArrayJoin($pArr, $pColId) {
		$vTemp = array();
		for ( $i = 0; $i < sizeof($pArr); $i++ ) {
			array_push($vTemp, $pArr[$i][$pColId]);
		}
		$vTemp = array_unique($vTemp);
		return implode(",", $vTemp);
	}

/*
$hostname=$_SERVER["HTTP_HOST"]; //도메인명(호스트)명을 구합니다.
$uri= $REQUEST_URI; //uri를 구합니다.
$query_string=getenv("QUERY_STRING"); // Get값으로 넘어온 값들을 구합니다.
$phpself=$_SERVER["PHP_SELF"]; //현재 실행되고 있는 페이지의 url을 구합니다. 
$basename=basename($_SERVER["PHP_SELF"]); //현재 실행되고 있는 페이지명만 구합니다.
$ip = gethostbynamel(php_uname('n'));

echo$hostname."<br>";
echo$uri."<br>";
echo$query_string."<br>";
echo$phpself."<br>";
echo$basename."<br>";
echo "$ip[0]"; // ip 출력

kyusiks.dothome.co.kr
/toontaku/siteSearcher.php?pParam=tstore
pParam=tstore
/toontaku/siteSearcher.php
siteSearcher.php
112.175.184.65
*/
?>