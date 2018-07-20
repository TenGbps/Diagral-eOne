<?PHP
 // --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- //
 // Diagral e-ONE php client by TenGbps                                 //
 // Version 1.0                                                         //
 // Config                                                              //
 // --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- //
 $username      = "dev@diagral.fr";  // MyDiagral Account Email
 $password      = "SuperPassword";   // MyDiagral Account Password
 $masterCode    = "0000";            // Your Pin Code
 $systemIndex   = 0;                 // Alarme index

 // --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- //
 // Code                                                                //
 // --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- //
 $sessionId     = 0;
 $diagralId     = 0;
 $systemId      = 0;
 $transmitterId = 0;
 $ttmSessionId  = 0;

 echo "Log in...\n";
 $post = '{"username":"'.$username.'","password":"'.$password.'"}';
 if($data = doRequest("/authenticate/login", $post)) {
  if(isset($data["sessionId"])) {
   $sessionId = $data["sessionId"];
   //echo "sessId: $sessionId\n";
  } else {
   echo "Session fail !\n";
  }
 } else {
  echo "Fail logon !\n";
 }

 if($sessionId != 0) {
  echo "Get systems...\n";
  $post = '{"sessionId":"'.$sessionId.'"}';
  if($data = doRequest("/configuration/getSystems", $post)) {
   if(isset($data["diagralId"])) {
    $diagralId = $data["diagralId"];
    $systemId  = $data["systems"][$systemIndex]["id"];
    //echo "diagralId: $diagralId, systemId: $systemId\n";
   } else {
    echo "Fail diagralId !\n";
   }
  } else {
   echo "Fail get systems !\n";
  }
 }

 if($systemId != 0) {
  echo "Get configuration...\n";
  $post = '{"systemId":'.$systemId.',"role":1,"sessionId":"'.$sessionId.'"}';
  if($data = doRequest("/configuration/getConfiguration", $post)) {
   if(isset($data["transmitterId"])) {
    $transmitterId = $data["transmitterId"];
    //echo "transmitterId: $transmitterId\n";
   } else {
    echo "Fail transmitterId !";
   }
  } else {
   echo "Fail get configuration\n";
  }
 }

 if($transmitterId != 0) {
  echo "Get last ttm session id\n";
  $post = '{"masterCode":"'.$masterCode.'","transmitterId":"'.$transmitterId.'","systemId":'.$systemId.',"role":1,"sessionId":"'.$sessionId.'"}';
  if($data = doRequest("/authenticate/getLastTtmSessionId", $post, true)) {
   if(strlen($data) == 32) {
    $ttmSessionId = $data;
    //echo "ttmSessionId: $ttmSessionId\n";
   } else {
    echo "Fail ttmSessionId !\n";
   }
  } else {
   echo "Fail getLastTtmSessionId !\n";
  }
 }

 if($ttmSessionId == 0) {
  echo "Do connect...\n";
  $post = '{"masterCode":"'.$masterCode.'","transmitterId":"'.$transmitterId.'","systemId":'.$systemId.',"role":1,"sessionId":"'.$sessionId.'"}';
  if($data = doRequest("/authenticate/connect", $post, true)) {
   if(isset($data["ttmSessionId"])) {
    $ttmSessionId = $data["ttmSessionId"];
    //echo "ttmSessionId: $ttmSessionId\n";
   } else {
    echo "Fail ttmSessionId !\n";
   }
  } else {
   echo "Fail connect !\n";
  }
 }


 if($ttmSessionId != 0) {
  echo "Put on group 1,";
  $post = '{"systemState":"group","group":[1],"currentGroup":[],"nbGroups":"4","sessionId":"'.$sessionId.'","ttmSessionId":"'.$ttmSessionId.'"}';
  if($data = doRequest("/action/stateCommand", $post)) {
   if($data["commandStatus"] == "CMD_OK") {
    echo " stateCommand OK\n";
   } else {
    echo "Fail stateCommand !\n";
   }
  }
 }

 sleep(5);

 if($ttmSessionId != 0) {
  echo "Put on system,";
  $post = '{"systemState":"on","group":[],"currentGroup":[],"nbGroups":"4","sessionId":"'.$sessionId.'","ttmSessionId":"'.$ttmSessionId.'"}';
  if($data = doRequest("/action/stateCommand", $post)) {
   if($data["commandStatus"] == "CMD_OK") {
    echo " stateCommand OK\n";
   } else {
    echo "Fail stateCommand !\n";
   }
  }
 }

 sleep(5);

 if($ttmSessionId != 0) {
  echo "Put off system";
  $post = '{"systemState":"off","group":[],"currentGroup":[],"nbGroups":"4","sessionId":"'.$sessionId.'","ttmSessionId":"'.$ttmSessionId.'"}';
  if($data = doRequest("/action/stateCommand", $post)) {
   if($data["commandStatus"] == "CMD_OK") {
    echo " stateCommand OK\n";
   } else {
    echo "Fail stateCommand !\n";
   }
  }
 }

 function doRequest($endpoint, $data, $rawout = false) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, "https://appv3.tt-monitor.com/topaze".$endpoint);
  curl_setopt($curl, CURLOPT_TIMEOUT,        5);
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST,  "POST");
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS,     $data);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array(
   "User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 10_2 like Mac OS X) AppleWebKit/602.3.12 (KHTML, like Gecko) Version/10.0 Mobile/14C92 Safari/602.1",
   "Accept: application/json, text/plain, */*",
   "Accept-Encoding: deflate",
   "X-App-Version: 1.5.0",
   "X-Vendor: diagral",
   "Content-Type: application/json;charset=UTF-8",
   "Content-Length: ".strlen($data),
   "Connection: Close",
  ));
  $result = curl_exec($curl);
  $rtcode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  //var_dump($result);
  curl_close($curl);
  if($rtcode == 200) {
   if($rawout == true) {
    return $result;
   } else {
    return json_decode($result, true);
   }
  } else {
   return false;
  }
 }
 // --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- //
?>
