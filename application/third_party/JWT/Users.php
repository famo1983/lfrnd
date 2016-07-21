<?php
class Users{
 
  public $logs;
  public $JWToken;

  function __construct(){
     
     $this->logs = new Logs();
     $this->JWToken = new JWToken();
  }
  function getDevices($userID){
    if(!empty($userID)){
      $sql = "select device_id, device_name, device_type, device_status from mmtvTF.user_devices where userID='".$userID."' AND device_status = '1' order by device_status DESC";
      $res = $this->db->Q($sql);
    	$rows = $this->db->NumRows($res);
      if($rows > 0){
            $a = array();
            while($row = $this->db->R($res)){
              $b = array();
              foreach ($row as $key => $value) {
                  if(!is_int($key) && $key == 'device_type'){
                     if(stripos($value,'Windows') !== false){
                       $row[$key] = 'Windows';
                     }elseif(stripos($value,'Android') !== false){
                       $row[$key] = 'Android';
                     }elseif(stripos($value,'Ubuntu') !== false){
                       $row[$key] = 'Ubuntu';
                     }
                  }
                  if(is_int($key)) {
                      unset($row[$key]);
                  }else{
                    $b[$key]=$row[$key];
                  }
              }
              $a[] = $b;
            }
            return $a;
      }else{
          return FALSE;
      }
    }else{
      return FALSE;
    }
  }

  function deleteDevice($device, $userID){
    $sql = "UPDATE mmtvTF.user_devices SET device_status='0' WHERE device_id='".$device."' AND userID='".$userID."'";
    $res = $this->db->Q($sql);
    if($res){
      return TRUE;
    }else{
      return FALSE;
    }

  }

  function addDevice($deviceName, $deviceId, $userID, $deviceType){

    $devices = $this->getDevices($userID);
    $getUas = $this->getStatus('account', $userID);
    $getPackage = $this->getPackageDetails($userID);
    //var_dump($getPackage['allowedDevices']);
    if(sizeof($devices) > (int)$getPackage['allowedDevices'] || $getUas != '1'){
      return false;
    }
    if(is_array($devices)){
      foreach ($devices as $row) {
        foreach ($row as $key => $value) {
          if($key == 'device_id' && $value == $deviceId){
            return false;
          }
        }
      }
    }

    $sql = "INSERT INTO mmtvTF.user_devices (userID,device_id,device_name,device_type) VALUES ('".$userID."','".$deviceId."','".$deviceName."','".$deviceType."')";
    $insert=$this->db->Q($sql);
    if($insert){
      return TRUE;
    }
    else{
      return false;
    }
  }

  function getPackageDetails($userID){
      if(!empty($userID)){
        $sql = "SELECT
                    `users`.`userID`
                    , `packages`.`package_SKU`
                    , `packages`.`package_total_allowed_devices` AS `allowedDevices`
                FROM
                    `mmtvTF`.`users`
                    INNER JOIN `mmtvTF`.`user_subscriptions`
                        ON (`users`.`userID` = `user_subscriptions`.`userID`)
                    INNER JOIN `mmtvTF`.`packages`
                        ON (`user_subscriptions`.`package_id` = `packages`.`package_id`)
                WHERE (`users`.`userID` ='".$userID."'
                    AND `packages`.`package_status` ='1')";
        $res = $this->db->Q($sql);
      	$rows = $this->db->NumRows($res);
        if($rows > 0){
          $a = array();
          while($row = $this->db->R($res)){
            foreach ($row as $key => $value) {
                if (is_int($key)) {
                    unset($row[$key]);
                }else{
                  $a[$key]=$row[$key];
                }
            }
          }
          return $a;
        }else{
          return false;
        }
      }else{
        return false;
      }
    }

  function checkEmail($args, $deviceId){
	$sql = "SELECT * FROM mmtvTF.users where user_email = '".$args."'";
	$res = $this->db->Q($sql);
	$rows = $this->db->NumRows($res);
    if($rows > 0){
  		$out = array();
  		$out['status'] = 'NewTrue';
  	  if($row=$this->db->R($res))
  		{
        $userID = $row['userID'];
        $devices = $this->getDevices($userID);
        $getUas = $this->getStatus('account', $userID);
        $getUcs = $this->getStatus('confirm', $userID);

        $out['fname'] = $row['user_firstName'];
        $out['lname'] = $row['user_lastName'];

        if(!empty($row['user_password'])){
          $out['p'] = TRUE;//Password not set
        }else{
          $out['p'] = FALSE;//Password not set
        }
        $out['userID'] = $userID;
        $out['deviceId'] = $deviceId;

        if($getUas == '1'){
          $out['AS'] = $getUas;
          $out['CS'] = $getUcs;
          $getPackage = $this->getPackageDetails($userID);
          if(is_array($getPackage)){
          $out['pack'] = $getPackage;
            if(is_array($devices)){
              /*$out['D'] = $devices;*/
              if(sizeof($devices) >= $getPackage['allowedDevices']){
                $da = FALSE;
              }else{
                $da = TRUE;
              }
            }else{
              $da = FALSE;
            }
            $out['DA'] = $da;//Devices allowed or not.
          }else{
            $out['pack'] = False;
          }
        }else{
          $out['AS'] = $getUas;//Account status
          $out['CS'] = $getUcs;//Confirmed status
        }

        $token = $this->JWToken->issueToken($out);
        $out['token'] = $token;
  		}
    }else{
      	$sqlu = "SELECT * FROM mangomobiletv.user_join AS uj,mangomobiletv.account_info AS ai WHERE email = '$args' AND username=userid";
    		$resu = $this->db->Q($sqlu);
    		$rowsu = $this->db->NumRows($resu);
    		if($rowsu > 0){
    		  	$out = array();
    			$out['status'] = 'OldTrue';
    			$i = 0;$j=1;$k=2;
    		  if($row=$this->db->R($resu))
    			{
          				$out[$i]['fname'] = $row['firstname'];
          				$out[$i]['lname'] = $row['lastname'];
          				$out[$i]['phone'] = $row['phone'];
          				$out[$i]['phone2'] = $row['ship_phone1'];

          				$out[$j]['addrs1'] = $row['bill_address'];
          				$out[$j]['addrs2'] = $row['bill_address2'];
          				$out[$j]['zipcode'] = $row['bill_zipcode'];
          				$out[$j]['city'] = $row['bill_city'];
          				$out[$j]['state'] = $row['bill_state'];
          				$out[$j]['country'] = $row['bill_country'];

          				$out[$k]['OA_active'] = $row['active'];
          				$out[$k]['OA_status'] = $row['enable_account'];
          				$out[$k]['OA_trialflag'] = $row['trial_flag'];
          				$out[$k]['OA_planid'] = $row['planid'];
          				$out[$k]['OA_accountid'] = $row['accountid'];
          				$out[$k]['OA_userid'] = $row['userid'];
          				$out[$k]['OA_subscriptionid'] = $row['subscriptionid']; //Authorize SubID
          			}
    		}else{
    		  return false;
    		}
    }
    return $out;
  }

  function login($data, $type){
	if($data !=''){
		if($type == 'DroidDID'){
      $sql = "SELECT
                    `users`.`user_confirmStatus` AS `user_confirmed`
                    , `user_subscriptions`.`user_subscription_status` AS `subscribed`
                    , `packages`.`package_SKU` AS `package_id`
                    , `users`.`user_accountStatus` AS `account_status`
                    , `packages`.`package_total_allowed_devices` AS `deviceLimit`
                    , COUNT(`user_devices`.`id`) AS `userDevices`
                    , `users`.`userID` AS `userID`
                    , `users`.`user_email` AS `email`
                FROM
                    `mmtvTF`.`users`
                    INNER JOIN `mmtvTF`.`user_devices`
                        ON (`users`.`userID` = `user_devices`.`userID`)
                    INNER JOIN `mmtvTF`.`user_subscriptions`
                        ON (`user_devices`.`userID` = `user_subscriptions`.`userID`)
                    INNER JOIN `mmtvTF`.`packages`
                        ON (`user_subscriptions`.`package_id` = `packages`.`package_id`)
                WHERE (`user_devices`.`device_id` ='".$data['deviceId']."')";

                $result=$this->db->Q($sql);
                if (!$result) {
                    $new = array();
                    $new['success'] = "false";
                    $new['message'] = "User not registered";
                    $n = array("deviceId" => $deviceId);
                    $token = $this->JWToken->issueToken($n);
                    $new['token'] = $token;
                    return $new;
                    //return false;
                }

                $row = mysql_fetch_array($result);

                $deviceId = $data['deviceId'];
                $row['userDevices'] = sizeof($this->getDevices($row['userID']));

                if($row['deviceLimit'] == NULL || $row['email'] == NULL){
                  $new = array();
                  $new['success'] = "false";
                  $new['message'] = "User not registered";
                  $n = array("deviceId" => $deviceId);
                  $token = $this->JWToken->issueToken($n);
                  $new['token'] = $token;
                  return $new;
                }
                $userAddedDevices = sizeof($this->getDevices($row['userID']));
                if($row['userDevices'] > $row['deviceLimit']){
                  $new = array();
                  $new['success'] = "false";
                  $secret = 'bWFuZ29Nb2JpbFRW';
                  //$new['user_id'] = MD5($secret+ MD5($row['userID']));
                  $new['user_id'] = $row['userID'];

                  $new['message'] = 'Max no. of devices limit reaced.';
                  foreach ($row as $key => $value) {
                      if (is_int($key)) {
                          unset($row[$key]);
                      }
                  }
                  $token = $this->JWToken->issueToken($deviceId);
                  $new['token'] = $token;
                  foreach ($row as $key => $value) {
                      if($key == 'deviceId' || $key == 'userID' || $key == 'deviceLimit' || $key=='email' || $key == 'userDevices'){
                        unset($row[$key]);
                      }
                  }
                  return $new;
                }else{
                  $row['success'] = "true";
                  $row['deviceId'] = $data['deviceId'];
                  $secret = 'bWFuZ29Nb2JpbFRW';
                  $row['user_id'] = $row['userID'];

                  foreach ($row as $key => $value) {
                      if (is_int($key)) {
                          unset($row[$key]);
                      }elseif($key == 'deviceLimit' || $key=='email' || $key == 'userDevices'){
                          unset($row[$key]);
                        }
                  }
                  $token = $this->JWToken->issueToken($row);
                  $row['token'] = $token;
                  foreach ($row as $key => $value) {
                      if($key == 'deviceId' || $key == 'userID' || $key == 'deviceLimit' || $key=='email' || $key == 'userDevices'){
                        unset($row[$key]);
                      }
                  }
                  //var_dump($row);
                  return $row;
                }

		}elseif($type == 'iOSUID'){
			return $data['user_id'];
		}elseif($type == 'WEB'){

     $sql = "SELECT
                `users`.`user_confirmStatus` AS `user_confirmed`
                , `user_subscriptions`.`user_subscription_status` AS `subscribed`
                , `packages`.`package_SKU` AS `package_id`
                , `users`.`user_accountStatus` AS `account_status`
                , `packages`.`package_total_allowed_devices` AS `deviceLimit`
                , COUNT(`user_devices`.`id`) AS `userDevices`
                , `users`.`userID` AS `userID`
            FROM
                `mmtvTF`.`users`
                INNER JOIN `mmtvTF`.`user_devices`
                    ON (`users`.`userID` = `user_devices`.`userID`)
                INNER JOIN `mmtvTF`.`user_subscriptions`
                    ON (`user_devices`.`userID` = `user_subscriptions`.`userID`)
                INNER JOIN `mmtvTF`.`packages`
                    ON (`user_subscriptions`.`package_id` = `packages`.`package_id`)
            WHERE (`users`.`user_email` ='".$data['email']."'
                AND `users`.`user_password` ='".$data['password']."' AND `user_devices`.`device_status` = '1')";

        $result=$this->db->Q($sql);
        if (!$result) {
            return false;
        }

        $row = mysql_fetch_array($result);
        if($row['userDevices'] > $row['deviceLimit']){
          return false;
        }else{
          if(!empty($data['IP'])){
            $row['IP'] = $data['IP'];
          }
          $secret = 'bWFuZ29Nb2JpbFRW';
          foreach ($row as $key => $value) {
              if (is_int($key)) {
                  unset($row[$key]);
              }elseif($key == 'deviceLimit' || $key=='email' || $key == 'userDevices'){
                  unset($row[$key]);
                }
          }

          $token = $this->JWToken->issueToken($row);
          $row['token'] = $token;

          foreach ($row as $key => $value) {
            if($key == 'deviceId' || $key == 'deviceLimit' || $key=='email' || $key == 'userDevices'){
              unset($row[$key]);
            }
          }
          $_SESSION["loggedIN"] = TRUE;
          return $row;
        }
		}
	}else{
		return false;
	}
  }
  function updateDetails($details, $device){

    if($details['userID'] !=''){
      $userId = $details['userID'];
      $accountStatus = $this->getStatus('account', $userId);

      if(!$accountStatus && $accountStatus != '1'){
        $add = "user_accountStatus = '1'";
      }else{
        $add = "";
      }
    }else{
      $userId = $this->getUID($details['email']);
    }

    $sql = "UPDATE mmtvTF.users SET user_firstName='".$details['fname']."',user_lastName='".$details['lname']."',user_password='".md5($details['password'])."',user_mobile='".$details['phone']."',".$add." WHERE userID='".$userId."'";

    $insert=$this->db->Q($sql);
    $a = array();
    if($insert){
      if(!$accountStatus && $accountStatus != '1'){
        $addDevice = $this->registerDevice($details['userID'], $device);
        if($addDevice){
          $a['device']= 'Added device.';
        }else{
          $a['device']= 'Unable to add device.';
        }

        $addSub = $this->registerSubscription($details['userID'],' ');
        if($addSub){
          $time = strtotime($addSub);
          $a['subEnd'] = date("D, d M Y", $time);
        }

        if($details['lname'] != ''){
            $a['name']= $details['fname'].' '.$details['lname'];
        }else{
            $a['name']= $details['fname'];
        }
        //Send Welcome Mail
        $view = new \Slim\Views\Twig();
        $view->setData(array('fname'=>$details['fname'],'subend' =>$a['subEnd']));
        $view->setTemplatesDirectory($this->app->config('templates.path'));
        $email_content = $view->render('email/welcome.php');
        $message = array(
            'html' => $email_content,
            'text' => 'Dear '.$details['fname'].'
                        Your subscription to Mango Mobile TV has been confirmed.
                        You are our newest member and you’ve arrived at the destination for the best cinema and entertainment content – available, anytime, anywhere & on any device.
                        It’s great to welcome you aboard. You can start by exploring through our library and lining up your favourite videos to watch. We’ll keep adding fresh titles in many more languages.
                        Thanks for signing up with MMTV and we wish you lots of great entertainment experiences.
                        Your trial period expires on : '.$a['subEnd'] .'

                        Reply to this email for any feedback, questions or requests, we’d love to hear from you.
                        Mango Mobile TV : Indian Cinema AnyTime. AnyWhere. AnyDevice',
            'subject' => 'Welcome to Mango Mobile TV - Premium Indian Cinema',
            'from_email' => 'support@mangomobiletv.com',
            'from_name' => 'Mango Mobile TV',
            'to' => array(
                array(
                    'email' => $details['email'],
                    'type' => 'to'
                )
            ),
            'headers' => array('Reply-To' => 'support@mangomobiletv.com')
          );
        $a['status']= TRUE;
        $sendMail = $this->mail->sendJ($message);
      }else{
        //Send Details update mail
        $token = $this->JWToken->issueToken($details);
        $userData = base64_encode(json_encode($details));
        $tok = $token.'/'.$userData;

        $view = new \Slim\Views\Twig();
        $view->setData(array('token'=>$tok));
        $view->setTemplatesDirectory($this->app->config('templates.path'));
        $email_content = $view->render('email/updateDetails.php');
        $message = array(
            'html' => $email_content,
            'text' => 'Dear user,
                      We have received a request to update your Mango Mobile TV account details & password.
                      If you did not ask to change your details/password, use below link to reset password.
                      Reset password now : https://apps.mangomobiletv.com/V2/user/auth/forgot/'.$tok.'
                      Link is valid for another 24 hours only.

                      Mango Mobile TV : Indian Cinema AnyTime. AnyWhere. AnyDevice.',
            'subject' => 'Mango Mobile TV user acount details updated.',
            'from_email' => 'support@mangomobiletv.com',
            'from_name' => 'Mango Mobile TV',
            'to' => array(
                array(
                    'email' => $details['email'],
                    'type' => 'to'
                )
            ),
            'headers' => array('Reply-To' => 'support@mangomobiletv.com')
          );
        $sendMail = $this->mail->sendJ($message);
        $a['status']= TRUE;
      }
    }else{
      $a['status']= FALSE;
    }
    return $a;
  }
  function setStatus($type, $user, $value){
    if(!empty($type) || !empty($user) || !empty($value)){
      //var_dump($user);
      if(!empty($user) && !is_int($user)){
        $u = 'user_email';
      }else{
        $u = 'userID';
      }

      if($type == 'account'){
        $status = 'user_accountStatus';
      }elseif($type == 'confirm'){
        $status = 'user_confirmStatus';
      }else{
        return false;
      }

      $sql = "UPDATE mmtvTF.users SET ".$status."='".$value."' WHERE userID='".$user."'";
      $res=$this->db->Q($sql);
      if($res){
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }

  function getStatus($type, $user){
    if(!empty($type) || !empty($user)){
      if(!empty($user) && strpos('@', $user) !== false){
        $u = 'user_email';
      }else{
        $u = 'userID';
      }
      if($type == 'account'){
        $status = 'user_accountStatus';
      }elseif($type == 'confirm'){
        $status = 'user_confirmStatus';
      }
      $sql = "Select ".$status." from mmtvTF.users where ".$u."='".$user."'";
      $res=$this->db->Q($sql);
      $num_rows = $this->db->NumRows($res);

      if($num_rows<1)
       {
        return false;
       }else{
        if($row=$this->db->R($res))
        {
          return $row[$status];
        }else{
          return false;
        }
       }

    }else{
      return false;
    }
  }

  function registerDetails($details, $device){
    //return $details;
    if($details['userID'] !=''){
      $sql = "UPDATE mmtvTF.users SET user_firstName='".$details['fname']."',user_lastName='".$details['lname']."',user_password='".$details['password']."',user_mobile='".$details['phone']."',user_accountStatus=1 WHERE userID='".$details['userID']."'";
    }elseif($details['email'] !=''){
      $sql = "UPDATE mmtvTF.users SET user_firstName='".$details['fname']."',user_lastName='".$details['lname']."',user_password='".$details['password']."',user_mobile='".$details['phone']."',user_accountStatus=1 WHERE user_email='".$details['email']."'";
    }else{
      $a['status']= False;
    }

    $insert=$this->db->Q($sql);
    $a = array();
    if($insert){

      $addDevice = $this->registerDevice($details['userID'], $device);
      if($addDevice){
        $a['device']= 'Added device.';
      }else{
        $a['device']= 'Unable to add device.';
      }

      $addSub = $this->registerSubscription($details['userID'],' ');
      if($addSub){
        $time = strtotime($addSub);
        $a['subEnd'] = date("D, d M Y", $time);
      }

      if($details['lname'] != ''){
          $a['name']= $details['fname'].' '.$details['lname'];
      }else{
          $a['name']= $details['fname'];
      }

      $view = new \Slim\Views\Twig();
      $view->setData(array('fname'=>$details['fname'],'subend' =>$a['subEnd']));
      $view->setTemplatesDirectory($this->app->config('templates.path'));
      $email_content = $view->render('email/welcome.php');
      $message = array(
          'html' => $email_content,
          'text' => 'Dear '.$details['fname'].'
                      Your subscription to Mango Mobile TV has been confirmed.
                      You are our newest member and you’ve arrived at the destination for the best cinema and entertainment content – available, anytime, anywhere & on any device.
                      It’s great to welcome you aboard. You can start by exploring through our library and lining up your favourite videos to watch. We’ll keep adding fresh titles in many more languages.
                      Thanks for signing up with MMTV and we wish you lots of great entertainment experiences.
                      Your trial period expires on : '.$a['subEnd'] .'

                      Reply to this email for any feedback, questions or requests, we’d love to hear from you.
                      Mango Mobile TV : Indian Cinema AnyTime. AnyWhere. AnyDevice',
          'subject' => 'Welcome to Mango Mobile TV - Premium Indian Cinema',
          'from_email' => 'support@mangomobiletv.com',
          'from_name' => 'Mango Mobile TV',
          'to' => array(
              array(
                  'email' => $details['email'],
                  'type' => 'to'
              )
          ),
          'headers' => array('Reply-To' => 'support@mangomobiletv.com')
        );
      $a['status']= TRUE;
      $sendMail = $this->mail->sendJ($message);
    }else{
      $a['status']= FALSE;
    }

    return $a;
  }

  function registerSubscription($userID, $package){
    if($package == ''){
      $subPackage = 0;
    }else{
      $subPackage = $package;
    }

    $now = date('Y-m-d H:i:s');
    $date = new DateTime($now);
    $date->add(new DateInterval('P15D'));
    $endDate = $date->format('Y-m-d H:i:s');

    //insert into mmtvTF.user_subscriptions (userID,tr_id,package_id,user_subscription_start_date,user_subscription_end_date,user_subscription_status,user_authorize_sub_id)VALUES()
    $sql = "INSERT INTO mmtvTF.user_subscriptions(userID,tr_id,package_id,user_subscription_start_date,user_subscription_end_date,user_subscription_status)VALUES ('".$userID."','0','".$subPackage."','".$now."','".$endDate."','1')";
    $insert=$this->db->Q($sql);
    //var_dump($insert);
    if($insert){
      return $endDate;
    }
    else{
      return false;
    }
  }

  function registerDevice($userID, $device){
    if($userID !=''){
      $sql = "INSERT INTO mmtvTF.user_devices (userID,device_id,device_type) VALUES ('".$userID."', '".$device['deviceId']."','".$device['deviceType']."')";
      $insert=$this->db->Q($sql);
      if($insert){
        return true;
      }
      else{
        return false;
      }
    }else{
      return false;
    }
  }

  function getlatestUID(){
	$sql = 'SELECT userID FROM mmtvTF.users ORDER BY userID DESC LIMIT 0, 1';
	$res=$this->db->Q($sql);
  $num_rows = $this->db->NumRows($res);

	if($num_rows<1)
	 {
		return false;
	 }else{
		if($row=$this->db->R($res))
		{
			return $row['userID'];
		}else{
			return false;
		}
	 }
	}

  function getUID($email){
  $sql = "SELECT userID FROM mmtvTF.users where user_email='".$email."'";
  $res=$this->db->Q($sql);
  $num_rows = $this->db->NumRows($res);

  if($num_rows<1)
   {
    return false;
   }else{
    if($row=$this->db->R($res))
    {
      return $row['userID'];
    }else{
      return false;
    }
   }
  }
  function getUser($userID){
  $sql = "SELECT userID, user_firstName as Fname, user_lastName as Lname, user_email as Email, user_mobile as Mobile FROM mmtvTF.users where userID='".$userID."'";
  $res=$this->db->Q($sql);
  $num_rows = $this->db->NumRows($res);

  if($num_rows<1){
    return false;
   }else{
    if($row=$this->db->R($res)){
      return $row;
    }else{
      return false;
    }
   }
  }

  function registerEmail($email, $deviceId){
  	$uid = $this->getlatestUID()+1;
    $userData = array();
    $userData['email'] = $email;
    $userData['userID'] = $uid;
    if(!empty($deviceId)){
        $userData['deviceId'] = $deviceId;
    }

  	$sql = "INSERT INTO mmtvTF.users (user_email, userID) VALUES ('$email', '$uid')";
  	$insert=$this->db->Q($sql);
  	$a = array();
    $token = $this->JWToken->issueToken($userData);
    $usermetaBase = base64_encode(json_encode($userData));
  	if($insert){
      $sendMail = $this->confirmationMail($uid, $email, $deviceId);
  		$a['userID']= $uid;
  		$a['email']= $email;
  		return $a;
  	}
  	else{
  		return false;
  	}
  }

  function insertConfirmCode($userID,$code,$token){
    $tid = $this->JWToken->getTokenId($token);
    $sql = "INSERT INTO mmtvTF.user_confirmations (userID, code, token_id) VALUES ('$userID', '$code', '$tid')";
    $insert=$this->db->Q($sql);
    if($insert){
      return TRUE;
    }
    else{
      return false;
    }
  }
  function updateConfirmCode($userID,$code){
    $sql = "UPDATE mmtvTF.user_confirmations SET status='1' WHERE userID='".$userID."' AND code='".$code."'";
    $insert=$this->db->Q($sql);
    if($insert){
      return true;
    }else{
      return false;
    }
  }
  function checkConfirmCode($userID,$code){
    $getUcs = $this->getStatus('confirm',$userID);

    if($getUcs != '0'){
      return false;
    }

    $sql = "SELECT status FROM mmtvTF.user_confirmations where userID='".$userID."' AND code='".$code."'";
    $res=$this->db->Q($sql);
    $num_rows = $this->db->NumRows($res);

    if($num_rows<1)
     {
      return false;
     }else{
      if($row=$this->db->R($res))
      {
        $status = $row['status'];
        if($status == '0'){
          $update = $this->updateConfirmCode($userID,$code);
          if($update){
            $CU = $this->confirmUser($userID,'');
            if($CU){
              return $CU;
            }else{
              return false;
            }
          }else{
            return false;
          }
        }else{

        }
      }else{
        return false;
      }
     }
  }

  function confirmationMail($userID, $email, $deviceId){
    $getUas = $this->getStatus('account',$userID);
    if($getUas != '1'){

      $code = 'MMTV'.substr(str_shuffle(str_repeat("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4)), 0, 4);

      $this->setStatus('confirm',$userID, 0);
      $userData = array();
      $userData['email'] = $email;
      $userData['userID'] = $userID;
      $userData['deviceId'] = $deviceId;
      $userData['CCode'] = $code;

      $token = $this->JWToken->issueToken($userData);
      if($token !=''){
        $this->insertConfirmCode($userID, $code, $token);
      }
      $usermetaBase = base64_encode(json_encode($userData));

      $view = new \Slim\Views\Twig();
      $view->setData(array('token' => $token,'usermeta' => $usermetaBase,'code' => $code));
      $view->setTemplatesDirectory($this->app->config('templates.path'));
      $email_content = $view->render('email/confirm.html');
      $message = array(
          'html' => $email_content,
          'text' => 'Dear User,
                    Please use below link to activate your free account and enjoy Ad-free Indian Cinema.
                    Confirm now : https://apps.mangomobiletv.com/V2/user/auth/confirmUser/'.$token.'/'.$usermetaBase.'
                    Confirmation Code : '.$code.'
                    Mango Mobile TV : Indian Cinema AnyTime. AnyWhere. AnyDevice.',
          'subject' => 'Mango Mobile TV account confirmation. Code: '.$code,
          'from_email' => 'support@mangomobiletv.com',
          'from_name' => 'Mango Mobile TV',
          'to' => array(
              array(
                  'email' => $email,
                  'type' => 'to'
              )
          ),
          'headers' => array('Reply-To' => 'support@mangomobiletv.com')
        );
      $sendMail = $this->mail->sendJ($message);

      $a['userID']= $userID;
      $a['email']= $email;
      return $a;
    }else{
      return false;
    }
  }

  function confirmUser($userID, $code){
    $sql = "UPDATE mmtvTF.users SET user_confirmStatus=1 WHERE userID='".$userID."'";
    $insert=$this->db->Q($sql);

    if($insert){
      if(!empty($code)){
        $updateCode = $this->updateConfirmCode($userID, $code);
      }

      $sql = "SELECT  `user_email` AS `email`, `user_firstName` AS `fname`, `user_lastName` AS `lname`
                  , `user_password` AS `password`, `user_mobile` AS `phone`, `userID`
              FROM  `mmtvTF`.`users`   WHERE (`userID` ='".$userID."')";

      $res=$this->db->Q($sql);
      $a = array();
      if($row=$this->db->R($res))
      {
        $a['AS'] = $this->getStatus('account', $userID);
        $a['CS'] = '1';
        if($row['password'] == '' || $row['fname'] == '' || $row['lname'] == '' || $row['phone'] == ''){
          $a['status']='Empty';
          $row['userID'] = $userID;
          foreach($row as $key => $value){
            if(is_int($key) || $key == 'password'){
              unset($row[$key]);
            }
          }
          $a[] = $row;
          $a['reg']['email'] = $row['email'];
          $a['reg']['userID'] = $userID;
          return $a;
        }else{
          return TRUE;
        }
      }else{
        return FALSE;
      }
      return TRUE;
    }else{
      return FALSE;
    }
  }

  function resetPass($email){
    $uid = $this->getUID($email);
    if(!$uid){
      return false;
    }
    $data = array();
    $data['email'] = $email;
    $data['userID'] = $uid;

    $token = $this->JWToken->issueToken($data);
    $userData = base64_encode(json_encode($data));
    $tok = $token.'/'.$userData;
  	$sql = "INSERT INTO mmtvTF.user_passResets (userID, token) VALUES ('$uid', '$tok')";
  	$insert=$this->db->Q($sql);
  	$a = array();

  	if($insert){
      $view = new \Slim\Views\Twig();
      $view->setData(array('token'=>$tok));
      $view->setTemplatesDirectory($this->app->config('templates.path'));
      $email_content = $view->render('email/resetPass.php');
      $message = array(
          'html' => $email_content,
          'text' => 'Dear user,
                    We have received a request to update your mango mobile tv account password.
                    Please follow below link, to reset your password now.
                    If you did not ask to change your password, please ignore this mail.<br>Your account remains unchanged.
                    Reset password now : https://apps.mangomobiletv.com/V2/user/auth/forgot/'.$tok.'
                    Link is valid for another 24 hours only.

                    Mango Mobile TV : Indian Cinema AnyTime. AnyWhere. AnyDevice.',
          'subject' => 'Reset Password',
          'from_email' => 'support@mangomobiletv.com',
          'from_name' => 'Mango Mobile TV',
          'to' => array(
              array(
                  'email' => $email,
                  'type' => 'to'
              )
          ),
          'headers' => array('Reply-To' => 'support@mangomobiletv.com')
        );
      $sendMail = $this->mail->sendJ($message);
      //print_R($sendMail);
      return 'true';
  	}
  	else{
  		return 'false';
  	}
  }

  function updatePass($body, $token){
    $uid = $this->getUID($body['email']);
    if(!$uid){
      return false;
    }
    $data = array();
    $data['email'] = $body['email'];
    $data['userID'] = $uid;
    $token = $this->JWToken->issueToken($data);
    $userData = base64_encode(json_encode($data));
    $tok = $token.'/'.$userData;

    $sqlu = "INSERT INTO mmtvTF.user_passResets (userID, token) VALUES ('$uid', '$tok')";
  	$insertu=$this->db->Q($sqlu);

    $sql = "UPDATE mmtvTF.users SET user_password='".$body['password']."' WHERE user_email='".$body['email']."'";
    $insert=$this->db->Q($sql);
    $a = array();
    if($insert){
      $view = new \Slim\Views\Twig();
      $view->setData(array('token'=>$tok));
      $view->setTemplatesDirectory($this->app->config('templates.path'));
      $email_content = $view->render('email/resetPass.php');
      $message = array(
        'html' => $email_content,
        'text' => 'Dear user,
                    Your mango mobile tv account password has been successfully updated.
                    If you did not ask to change your password, Please follow below link to reset your password.
                    Reset password now : https://apps.mangomobiletv.com/V2/user/auth/forgot/'.$tok.'
                    Link is valid for another 24 hours only.

                    Mango Mobile TV : Indian Cinema AnyTime. AnyWhere. AnyDevice.',
          'subject' => 'Password updated',
          'from_email' => 'support@mangomobiletv.com',
          'from_name' => 'Mango Mobile TV',
          'to' => array(
              array(
                  'email' => $body['email'],
                  'type' => 'to'
              )
          ),
          'headers' => array('Reply-To' => 'support@mangomobiletv.com')
        );
      $sendMail = $this->mail->sendJ($message);
      $sqlU = "UPDATE mmtvTF.user_passResets SET status='1' WHERE userID='".$uid."'";
      $insertU=$this->db->Q($sqlU);
      return 'true';
    }
    else{
      return 'false';
    }
  }

  function checkApp($args){
  	if($args != ''){
  			return $this->JWToken->issueToken($args);
  	}else{
  		return false;
  	}
  }

  function getAddress($userID){
    $AS = $this->getStatus('account', $userID);
    if(!empty($userID) && $AS != '0'){
      $sql = "SELECT
                  `billing_id` AS `BID`
                  , `userID` AS `userID`
                  , `billing_address1` AS `A1`
                  , `billing_address2` AS `A2`
                  , `billing_city` AS `City`
                  , `billing_state` AS `State`
                  , `billing_country` AS `Country`
                  , `billing_pincode` AS `PinCode`
              FROM
                  `mmtvTF`.`user_billaddress`
              WHERE (`userID` ='".$userID."')";
      $res=$this->db->Q($sql);
      $num_rows = $this->db->NumRows($res);
      if($num_rows<1){
        return false;
       }else{
          if($row=$this->db->R($res)){
            return $row;
          }else{
            return false;
          }
       }
    }else{
      return false;
    }
  }
  function addAddress($userID){

  }
  function updateAddress($userID){

  }
}
