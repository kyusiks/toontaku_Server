<?
/*************************************************
	shoc(쇽) class

	입력된 url의 소스를 읽어와
	원하는 부분의 값을 리턴하는 클래스
	Snoopy class와 함께 사용한다.
	
	1. 입력된 url의 소스를 읽어온다.
	2. 입력된 패턴에 따라 해당 내용을 가져온다.
	3. 여러 페이지를 한번에 읽어올수 있도록 한다.
*************************************************/
include "Snoopy.class.php";
header("Content-Type:text/html;charset=UTF-8"); // utf-8 선언

class shoc {
	/**** Public variables ****/
	var $gv_url = "";
	var $gv_readData = "";
	var $gv_rangeStart = "";
	var $gv_rangeEnd = "";
	/**** Public variables ****/
	function fn_shocClear() {
		$this->gv_url = "";
		$this->gv_readData = "";
		$this->gv_rangeStart = "";
		$this->gv_rangeEnd = "";
	}

	function setUrl($pUrl) { // 읽어올 사이트 주소를 글로벌 변수에 저장
		if ( $pUrl != $this->gv_url ) {
			$this->fn_shocClear(); // 이전 주소와 다를경우 초기화 후 세팅
			$this->gv_url = $pUrl;
		} else {
		}
	}

	function setRange($pRangeStart, $pRangeEnd) { // readIt 시 가져온 gv_readData의 문자열을 자를경우 셋.
		$this->gv_rangeStart = $pRangeStart;
		$this->gv_rangeEnd = $pRangeEnd;
	}

	function findIt($pStartWords, $pEndWords) {
		$vUrl = $this->gv_url;
		$vData = $this->gv_readData;
		if ( empty($vData) ) { // 변환자료가 없다면
			if ( empty($vUrl) ) { // 세팅된 url이 없으면 에러
		  	exit("에러남. 세팅된 url이 없음"); 
			} else {  // url을 세팅해놨다면 읽어오기
				$this->readIt($vUrl);
				$vData = $this->gv_readData;
			}
		}

		// $pStartWords, $pEndWords 를 근거로 정규식 제작
		$rex = "/".$this->fn_addSlashes($pStartWords)."(.*?)".$this->fn_addSlashes($pEndWords)."/";
//echo($rex);
		ini_set('memory_limit','256M');
		preg_match_all($rex, $vData, $output);
		return $output; // 배열로 리턴
	}
	/******************
	 * pUrl 주소의 페이지 내용을 긁어온다.
	 *
	 * * 추가
	 * 특정 범위만 긁을수도 있다.(gv_rangeStart 여기부터 gv_rangeEnd 까지)
	 * 이경우 readIt()이 동작하기전에 setRange($pRangeStart, $pRangeEnd)를 설정해두어야한다.
	 ******************/
	function readIt($pUrl) {
		$snoopy = new snoopy; 
		$vReferer = split("/", $pUrl);
		if ( sizeof($vReferer) >= 2 ) $snoopy->referer=$vReferer[0]."//".$vReferer[2]; // 접속 막은 도메인 자동 우회 ks20151120 olleh 때문에.

		//$snoopy->proxy_host = "187.110.91.154"; // ks20180304 네이버가 막혔다.
		//$snoopy->proxy_port = "53281"; // http://www.freeproxylists.net/ 여기서 접속을 교란하라.

		$snoopy->proxy_host = "211.245.62.111"; // ks20180304 네이버가 막혔다.
		$snoopy->proxy_port = "80"; // http://www.freeproxylists.net/ 여기서 접속을 교란하라.




		$snoopy->fetch($pUrl);
		$vRange = $snoopy->results;
		
		if ( !empty($this->gv_rangeStart) // 특정 범위만 긁어야한다면 작업
		  && !empty($this->gv_rangeEnd  ) ) {
			$pieceOfRange1 = explode($this->gv_rangeStart, $vRange);
			$pieceOfRange2 = explode($this->gv_rangeEnd, $pieceOfRange1[1]);
			if ( !empty($pieceOfRange2[0]) ) $vRange = $pieceOfRange2[0];
		}

		$this->gv_readData = $vRange;
		$this->gv_url = $pUrl;
	}

	function fn_addSlashes($pStr) { // 특수문자에 역슬레쉬 표시
		if ( strpos($pStr, "regex:") !== false ) {
			return substr($pStr, 6); // 정규식이면 슬레시 안하고 리턴
		}
		
		$vBefore = array("<", ">", "/", "?", "{", "}", "(", ")", ":", ";", "[", "]");
		$vAfter  = array("\<", "\>", "\/", "\?", "\{", "\}", "\(", "\)", "\:", "\;", "\[", "\]");
		$vEscape = str_replace($vBefore, $vAfter, addslashes($pStr)); // 특수문자 열외를 열외시키기 위해 한번더 리플레이스. 특수문자앞에 \가 있는건 열외시킴
		return str_replace("\\\\\\", "", $vEscape);
	}

	function fn_iconv($pOri, $pTon) { // iconv('utf-8', 'euc-kr', $snoopy->results);
		$this->gv_readData = iconv($pOri, $pTon, $this->gv_readData);
		return $this->gv_readData;
	}
}
?>