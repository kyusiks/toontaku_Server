<?
/*************************************************
	shoc(��) class

	�Էµ� url�� �ҽ��� �о��
	���ϴ� �κ��� ���� �����ϴ� Ŭ����
	Snoopy class�� �Բ� ����Ѵ�.
	
	1. �Էµ� url�� �ҽ��� �о�´�.
	2. �Էµ� ���Ͽ� ���� �ش� ������ �����´�.
	3. ���� �������� �ѹ��� �о�ü� �ֵ��� �Ѵ�.
*************************************************/
include "Snoopy.class.php";
header("Content-Type:text/html;charset=UTF-8"); // utf-8 ����

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

	function setUrl($pUrl) { // �о�� ����Ʈ �ּҸ� �۷ι� ������ ����
		if ( $pUrl != $this->gv_url ) {
			$this->fn_shocClear(); // ���� �ּҿ� �ٸ���� �ʱ�ȭ �� ����
			$this->gv_url = $pUrl;
		} else {
		}
	}

	function setRange($pRangeStart, $pRangeEnd) { // readIt �� ������ gv_readData�� ���ڿ��� �ڸ���� ��.
		$this->gv_rangeStart = $pRangeStart;
		$this->gv_rangeEnd = $pRangeEnd;
	}

	function findIt($pStartWords, $pEndWords) {
		$vUrl = $this->gv_url;
		$vData = $this->gv_readData;
		if ( empty($vData) ) { // ��ȯ�ڷᰡ ���ٸ�
			if ( empty($vUrl) ) { // ���õ� url�� ������ ����
		  	exit("������. ���õ� url�� ����"); 
			} else {  // url�� �����س��ٸ� �о����
				$this->readIt($vUrl);
				$vData = $this->gv_readData;
			}
		}

		// $pStartWords, $pEndWords �� �ٰŷ� ���Խ� ����
		$rex = "/".$this->fn_addSlashes($pStartWords)."(.*?)".$this->fn_addSlashes($pEndWords)."/";
//echo($rex);
		ini_set('memory_limit','256M');
		preg_match_all($rex, $vData, $output);
		return $output; // �迭�� ����
	}
	/******************
	 * pUrl �ּ��� ������ ������ �ܾ�´�.
	 *
	 * * �߰�
	 * Ư�� ������ �������� �ִ�.(gv_rangeStart ������� gv_rangeEnd ����)
	 * �̰�� readIt()�� �����ϱ����� setRange($pRangeStart, $pRangeEnd)�� �����صξ���Ѵ�.
	 ******************/
	function readIt($pUrl) {
		$snoopy = new snoopy; 
		$vReferer = split("/", $pUrl);
		if ( sizeof($vReferer) >= 2 ) $snoopy->referer=$vReferer[0]."//".$vReferer[2]; // ���� ���� ������ �ڵ� ��ȸ ks20151120 olleh ������.

		//$snoopy->proxy_host = "187.110.91.154"; // ks20180304 ���̹��� ������.
		//$snoopy->proxy_port = "53281"; // http://www.freeproxylists.net/ ���⼭ ������ �����϶�.

		$snoopy->proxy_host = "211.245.62.111"; // ks20180304 ���̹��� ������.
		$snoopy->proxy_port = "80"; // http://www.freeproxylists.net/ ���⼭ ������ �����϶�.




		$snoopy->fetch($pUrl);
		$vRange = $snoopy->results;
		
		if ( !empty($this->gv_rangeStart) // Ư�� ������ �ܾ���Ѵٸ� �۾�
		  && !empty($this->gv_rangeEnd  ) ) {
			$pieceOfRange1 = explode($this->gv_rangeStart, $vRange);
			$pieceOfRange2 = explode($this->gv_rangeEnd, $pieceOfRange1[1]);
			if ( !empty($pieceOfRange2[0]) ) $vRange = $pieceOfRange2[0];
		}

		$this->gv_readData = $vRange;
		$this->gv_url = $pUrl;
	}

	function fn_addSlashes($pStr) { // Ư�����ڿ� �������� ǥ��
		if ( strpos($pStr, "regex:") !== false ) {
			return substr($pStr, 6); // ���Խ��̸� ������ ���ϰ� ����
		}
		
		$vBefore = array("<", ">", "/", "?", "{", "}", "(", ")", ":", ";", "[", "]");
		$vAfter  = array("\<", "\>", "\/", "\?", "\{", "\}", "\(", "\)", "\:", "\;", "\[", "\]");
		$vEscape = str_replace($vBefore, $vAfter, addslashes($pStr)); // Ư������ ���ܸ� ���ܽ�Ű�� ���� �ѹ��� ���÷��̽�. Ư�����ھտ� \�� �ִ°� ���ܽ�Ŵ
		return str_replace("\\\\\\", "", $vEscape);
	}

	function fn_iconv($pOri, $pTon) { // iconv('utf-8', 'euc-kr', $snoopy->results);
		$this->gv_readData = iconv($pOri, $pTon, $this->gv_readData);
		return $this->gv_readData;
	}
}
?>