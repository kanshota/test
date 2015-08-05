<?php
require_once 'config.php';

/**
 * APIへの接続情報の保持、各種メソッドの実装
 * getInstance()でインスタンスを取得して使用
 *
 * @author S.Kan
 */
class ApiManager {

	/** 自己インスタンス */
	private static $_instance;

	/** エンドポイントURL */
	private $_endpoint;

	/** アクセスキー */
	private $_accesskey;
	
	/** 秘密鍵 */
	private $_secret;
	
	/** 

	/** コンストラクタ */
	public function __construct() {
		$this->_endpoint = ENDPOINT;
		$this->_accesskey = ACCESSKEY;
		$this->_secret = SECRET;
		
	}

	/** singletonインスタンスの取得 */
	public static function getInstance() {
		//自己インスタンスの生成
		if( !isset(self::$_instance) ){
			self::$_instance = new ApiManager();
		}
		
		return self::$_instance;
	}
	
	
	/** APIリクエスト実行 */
	public function executeRequest($command,$params = array(),$json = false) {
	
		array_push($params,array('key' => 'command', 'value' => $command));
		array_push($params,array('key' => 'apikey', 'value' => $this->_accesskey));
		if( $json ){
			array_push($params,array('key' => 'response', 'value' => 'json'));
		}
		
		$signature = $this->builtSignature($params);
		
		$url = $this->_endpoint;
		$url.= "/client/api?";
		
		foreach($params as $p){
		
			$url.= $p['key'] . "=" . $p['value'];
			$url.="&";
		
		}
		
		$url.= "signature=" . $signature;
		
		$resstr = file_get_contents($url);
		
		return $resstr;
	
	}
	
	/** テスト用 */
	public function printSignature() {
	
		$p = array(
				array('key' => 'command', 'value' => 'listVirtualMachines'),
				array('key' => 'apikey', 'value' => $this->_accesskey)
			);
	
		$signature = $this->builtSignature($p);

		$url = $this->_endpoint . "/client/api?command=listVirtualMachines&apikey=" . $this->_accesskey . "&signature=" . $signature;
		print $url;
		$str = file_get_contents($url);
		
		print $str;
	
	}


	/** 署名作成 */
	private function builtSignature($params) {
	
		$parr = array();
		
		foreach($params as $p){
			$t = array();
			$t['key'] = strtolower($p['key']);
			$t['value'] = strtolower(urlencode($p['value']));
			
			array_push($parr,$t);
		}
		
		usort($parr, array($this,'compare_by_key'));
		
		$tstr = "";
		
		$num = count($parr);
		$i=0;
		foreach($parr as $a){
		
			$tstr .= $a['key'].'='.$a['value'];
			$i++;
			
			if( $i < $num ){
				$tstr .= '&';
			}
		}
		
		return urlencode(base64_encode(hash_hmac('sha1',$tstr,$this->_secret,true)));
	
	}
	
	
	/** ソート用比較関数 */
	private function compare_by_key($item1,$item2){
		$key1 = $item1['key'];
		$key2 = $item2['key'];
		if ( $key1 < $key2 ) return -1;
		if ( $key1 > $key2 ) return 1;
		$value1 = $item1['value'];
		$value2 = $item2['value'];
		if ( $value1 < $value2 ) return -1;
		if ( $value1 > $value2 ) return 1;
		return 0;
	}
	
	
	/**
	 * clone()の抑制
	 *
	 * @throws RuntimeException
	 */
	public final function __clone() {
		throw new RuntimeException('Clone() is not allowd against ' . get_class($this));
	}

}




?>