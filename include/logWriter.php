<?
	$gv_logWriter_f = "";
	
	if ( $vMode == "FILE" ) { // 파일 로그를 남기는 모드면 파일 생성
		 $exist = "../LOG/".date("YmdHis").".log";
     $gv_logWriter_f = @fopen($exist, "w");
	}

	/**************
	 * 파일로 로그를 남기느냐, html형식으로 보여주느냐
	 * 화면에서 직접 들어온다면 table형식으로 표시될테고
	 * 백그라운드로 프로그램 작성되면 로그파일이 만들어진다.
	 * $pTag : 화면보기 모드일때 태그, $pFile : 파일 저장시
	 **************/
	function fn_echo($pTag, $pFile) {
		global $vMode;
		if ( $vMode == "FILE" ) {
			global $gv_logWriter_f;
      @fwrite($gv_logWriter_f, $pFile); 
		} else if ( $vMode == "4" ) {
		} else {
			echo $pTag;
		}
	}
?>