<?PHP

function Timeoff_Field_Type($type)
{
  switch ($type) {
    case 'user':
      return "user_id";
      break;
    
    default:
      return "center_id";
      break;
  }
}

function Timeoff_Parse_Value($type,$value)
{
  $center = 'NULL';
  $user_id = 'NULL';
 
  $field = Timeoff_Field_Type($type);
 
  $db = Core_Database_Open();
 
  if($field == "user_id")
  {
    $centerData = SQL_Query("SELECT center FROM users WHERE id = $value",$db);
    $center = SQL_Format($centerData[0]["center"],$db);
    $user_id = $value;
  } 
  else $center = SQL_Format($value,$db);
  SQL_Close($db);
  return [$center,$user_id];
}

function Timeoff_Read($type, $value, $utc = false)
{
  $db = Core_Database_Open();

  $field = Timeoff_Field_Type($type);
  if($field == "center_id") $value = SQL_Format($value,$db);
  
  $data = SQL_Query("SELECT * FROM timeoff WHERE $field = $value",$db);

  // REMOVE TIMEOFF OLDER 1 MONTH
  $today   = Date_Now();
  
  foreach($data as $item)
  {
   // FILTER OUT DATES OLDER THAN ABOUT 1 MONTH
   if(Date_Distance_Days($today, $item["date_to"]) > -30)
   {}
   else{
    SQL_Query("DELETE FROM timeoff WHERE id = ".$item["id"],$db);
   }
  }
 
  SQL_Close($db);
  
  // CONVERT FROM UTC
  if(!$utc)
  {
    foreach($data as &$item)
    {
    $item["date_from"] = User_Date($item["date_from"], "out");
    $item["date_to"]   = User_Date($item["date_to"],  "out");
    }
  }
 
 
 return $data;
}


function Timeoff_Add($type, $value, $item)
{
  // CONVERT TO UTC
  $date_from = User_Date($item["date_from"], "in");
  $date_to   = User_Date($item["date_to"],   "in");

  list($center,$user_id) = Timeoff_Parse_Value($type,$value);

  $db = Core_Database_Open();
  //CHECK EXIST
  $check = SQL_Query("SELECT id FROM timeoff WHERE date_from = $date_from AND date_to = $date_to AND user_id = $user_id AND center_id = $center",$db);
  
  if(!count($check))
  SQL_Query("INSERT INTO timeoff(center_id,user_id,date_from,date_to) VALUES($center,$user_id,$date_from,$date_to)",$db);

  SQL_Close($db);
}



function Timeoff_AddBatch($type, $value, $items)
{
 $values = []; 

 list($center,$user_id) = Timeoff_Parse_Value($type,$value);

 $db = Core_Database_Open();

 // CONVERT TO UTC
 foreach($items as &$item)
 {
  $date_from = User_Date($item["date_from"], "in");
  $date_to   = User_Date($item["date_to"],   "in");
  $values[] = "($center,$user_id,$date_from,$date_to)";
 }
 
 $values = implode(",",$values);
 
 SQL_Query("INSERT INTO timeoff(center_id,user_id,date_from,date_to) VALUES $values",$db);

 SQL_Close($db);
}



function Timeoff_RemoveBatch($type, $value, $markers)
{
  // CONVERT TO UTC
  $date_from = User_Date($markers["date_from"], "in");
  $date_to   = User_Date($markers["date_to"],   "in");

  list($center,$user_id) = Timeoff_Parse_Value($type,$value);

  $db = Core_Database_Open();

  SQL_Query("DELETE FROM timeoff WHERE center_id = $center AND user_id = $user_id AND date_from = $date_from AND date_to = $date_to",$db);

  SQL_Close($db);
}




function Timeoff_Update_Notes($id, $notes)
{
  $db = Core_Database_Open();
  $notes =SQL_Format($notes,$db);
  SQL_Query("UPDATE timeoff SET notes = $notes WHERE id = $id",$db);
  SQL_Close($db);
}



function Timeoff_Delete($id)
{
  $db = Core_Database_Open();
  SQL_Query("DELETE FROM timeoff WHERE id = $id",$db);
  SQL_Close($db);
}



?>