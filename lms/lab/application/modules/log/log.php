<?php

function Users_Write_Log($event,$data,$options = [])
{
  try {
    $user_id = $_SESSION["user"]["id"] ?? 0;
    $db     = Core_Database_Open();

    $now = Date_Now();
    $data["time"] = $now;
    if(isset($data["response"]) && gettype($data["response"]) == "string") $data["response"] = json_decode($data["response"]);
    $session =  session_id() ? 'SESSION-'.session_id() : "NULL";
    if(isset($_REQUEST["nologin"])) $session = 'SESSION-nologin';
    if(isset($_REQUEST["loginkey"])) $session = 'SESSION-'.$_REQUEST["loginkey"];
    $id = Users_Log_CheckSession($session);
    
    $dataFormat = json_encode([$data]);
    $dataFormat = SQL_Format($dataFormat,$db);
    $sessionFormat = SQL_Format($session,$db);
    if(isset($options["use-event"]))
    {
      $event = SQL_Format($event,$db);
      SQL_Query("INSERT INTO users_log(user_id,date,event,data) VALUES($user_id,$now,$event,$dataFormat)",$db);
    }
    else
    if($id)
    {
      Users_Log_UpdateData($id, $event, $session, $data);
    }
    else 
    {
      SQL_Query("INSERT INTO users_log(user_id,date,event,data) VALUES($user_id,$now,$sessionFormat,$dataFormat)",$db);
    }
  } catch (\Throwable $th) {
    $thFormat = SQL_Format(json_encode(["message"=> $th->getMessage(), "trace" => $th->getTrace()]),$db);
    if(strlen($thFormat) < 65535)
    SQL_Query("INSERT INTO users_log(user_id,date,event,data) VALUES($user_id,$now,'error_log',$thFormat)",$db);
    else {
      $th = SQL_Format(json_encode(["message" => $th->getMessage()]),$db);
      SQL_Query("INSERT INTO users_log(user_id,date,event,data) VALUES($user_id,$now,'error_log', $th)",$db);
    }
  }
  SQL_Close($db);
}

function Users_Log_CheckSession($session)
{
  $db     = Core_Database_Open();
  $user_id = $_SESSION["user"]["id"] ?? 0;
  $now    = Date_Format_As(Date_Now(), "date-only");
  $data   = SQL_Query("SELECT id from users_log where event = '$session' AND user_id = $user_id AND date >= {$now}0000  AND date <= {$now}2359 ORDER BY id desc",$db);
  SQL_Close($db);
  if(count($data)) return $data[0]["id"];
  return false;
}

function Users_Log_UpdateData($id, $event, $session, $newdata) {
  try {
    $db     = Core_Database_Open();
    $user_id = $_SESSION["user"]["id"] ?? 0;
    $now    = Date_Now();
    $dataquery   =  SQL_Query("SELECT data FROM users_log WHERE id = $id",$db);
    $data   = json_decode($dataquery[0]["data"]) ?? [];
    array_push($data,(object)$newdata);
    $data   = json_encode($data);
    $dataFormat = SQL_Format($data,$db);
    if(strlen($dataFormat) < 65535)
      SQL_Query("UPDATE users_log SET data = $dataFormat WHERE id = $id",$db);
    else 
    {
      $newdataFormat = json_encode([$newdata]);
      $newdataFormat = SQL_Format($newdataFormat,$db);
      $sessionFormat = SQL_Format($session,$db);
      SQL_Query("INSERT INTO users_log(user_id,date,event,data) VALUES($user_id,$now,$sessionFormat,$newdataFormat)",$db);
    }
    SQL_Close($db);
  } catch (\Throwable $th) {
    $th = SQL_Format(json_encode(["message"=> $th->getMessage(), "trace" => $th->getTrace()]),$db);
    SQL_Query("INSERT INTO users_log(date,event,data) VALUES($now,'error_log',$th)",$db);
  }
  
}


function Users_Log_Create_Partitions(){
  // CREATE NEW PARTITION FOR NEXT MONTH
  $db    = Core_Database_Open();
  $now   = Date_Now();
  $month = substr($now,4,2);
  $year  = substr($now,0,4);

  if($month == 12) {$nextmonth = "01";$year = (int)$year + 1;}
  else if((int)$month + 1 > 9) $nextmonth = (int)$month + 1 ;
        else $nextmonth =  '0'.((int)$month + 1);
  
  if($nextmonth == 12) $next2month = ((int)((int)($year) + 1 .'01'))*100*100*100;
  else  $next2month = ((int)($year.$nextmonth) + 1)*100*100*100;


  $partitionMonthName = "MONTH_".$year.$nextmonth;
  $partitionMonth = SQL_Query("SELECT PARTITION_NAME FROM INFORMATION_SCHEMA.PARTITIONS WHERE TABLE_NAME= 'users_log' AND PARTITION_NAME = '$partitionMonthName'",$db);
  if(!count($partitionMonth))
  SQL_Query("ALTER TABLE users_log ADD PARTITION (PARTITION $partitionMonthName VALUES LESS THAN ($next2month))",$db);
}

function Users_Login_Write_Log($event,$data,$options = [])
{
    $user_id = $_SESSION["user"]["id"] ?? 0;
	$user_email = $_SESSION["user"]["email"] ?? '';
    $db     = Core_Database_Open();

    $now = Date_Now();
    $data["time"] = $now;
    $session =  session_id() ? 'SESSION-'.session_id() : "NULL";
    
	$user_email = SQL_Format($user_email,$db);
	$ip = SQL_Format($data["ip"],$db);
    $dataFormat = json_encode([$data]);
    $dataFormat = SQL_Format($dataFormat,$db);
    $sessionFormat = SQL_Format($session,$db);
    $id="abc";
    if(isset($options["use-event"]))
    {
      $event = SQL_Format($event,$db);
      $id = SQL_Query("INSERT INTO users_login_log(user_id,email,date,event,ip,data) VALUES($user_id,$user_email,$now,$event,$ip,$dataFormat)",$db);
    }
    else
    {
      $id = SQL_Query("INSERT INTO users_login_log(user_id,email,date,event,ip,data) VALUES($user_id,$user_email,$now,$sessionFormat,$ip,$dataFormat)",$db);
    }
  SQL_Close($db);
}
?>