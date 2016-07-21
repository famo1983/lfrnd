<?php
use \Firebase\JWT\JWT;
class JWToken{
	private $secretKey = 'LIKEfrnd!@#$';
	private $issuingDomain = 'likefrnd.com';
	
	
	
	//private $token;
	public function __construct($token=null){
		
		
		
	}
	public function issueToken($args){
		//$tokenId    = base64_encode(mcrypt_create_iv(32));
		$issuedAt   = time();
		$notBefore  = $issuedAt + 5;             //Adding 10 seconds
		$expire     = $notBefore + (24*60*60);            // Adding 600 seconds
		$serverName = $this->issuingDomain; // Retrieve the server name from config file

		/*
		 * Create the token as an array
		 */
		$data = [
			'iat'  => $issuedAt,         // Issued at: time when the token was generated
			//'jti'  => $tokenId,        // Json Token Id: an unique identifier for the token
			'iss'  => $serverName,       // Issuer
			'nbf'  => $notBefore,        // Not before
			'exp'  => $expire,           // Expire
			'data' => $args
		];
		$token = JWT::encode($data, $this->secretKey);
		if($token){
			$b = array();
			
			return $token;
		}else{
			return false;
		}
	}

	

  public function authenticate($token)
	{
		try{
			

			JWT::$leeway = 5;
			return JWT::decode($token, $this->secretKey, array('HS256'));
		}catch(Exception $e){
			//return $e;
			return false;
		}
	}

	public function checkApp($args){
		if($args != ''){
				return $this->issueToken($args);
		}else{
			return false;
		}
	}

	public function getTokenId($token){
		$sql = "SELECT token_id FROM mmtvTF.token_logs where auth_token like '%".$token."%'";
	  $res=$this->db->Q($sql);
	  $num_rows = $this->db->NumRows($res);

	  if($num_rows<1)
	   {
	    return false;
	   }else{
	    if($row=$this->db->R($res))
	    {
	      return $row['token_id'];
	    }else{
	      return false;
	    }
	   }
	}

	public function getKey($key, $payload)
	{
		if(is_array($payload)){
			if(array_key_exists($key, $payload)){
				return $payload[$key];
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	public function getUserPayload($token)
	{
		try{
			JWT::$leeway = 5;
			$check = JWT::decode($token, $this->secretKey, array('HS256'));
			$unencodedData = (array) $check;
			return json_encode($unencodedData['data']);
		}catch(Exception $e){
			echo false;
		}

	}
	public function getAppPayload($token)
	{
		try{
			JWT::$leeway = 5;
			$check = JWT::decode($token, $this->secretKey, array('HS256'));
			$unencodedData = (array) $check;
			unset($unencodedData['data']);
			return json_encode($unencodedData);
		}catch(Exception $e){
			echo false;
		}

	}
}
