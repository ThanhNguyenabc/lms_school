<?php

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                   A S S E S S M E N T                                          //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Assessment_Seat_Data($seat_id, $options = [])
{
 $data = [];
 
 // READ SEAT COMPLETE WITH STUDENT INFO
 $seat         = Class_Seat_Read($seat_id, ["users" => true]);
 $data["seat"] = $seat;
 
 
 // GET LESSON AND TEACHER IDs FROM CLASS
 $class_id   = $seat["class_id"];
 
 $fields     = Core_Database_ReadTable("classes", $class_id, ["teacher_id", "lesson_id"]);
 $teacher_id = $fields["teacher_id"];
 $lesson_id  = $fields["lesson_id"];
 
 $data["teacher_id"] = $teacher_id;
 $data["lesson_id"]  = $lesson_id;
 
 // READ LESSON COMPLETE WITH OUTCOMES
 $data["lesson"] = Lesson_Read($lesson_id, ["outcomes" => true]);
 
 
 // OPTIONAL DATA
 
 
 // PREPARATION (PRECLASS ACTIVITIES' SCORES)
 if(isset($options["preparation"]))
 {
  // READ ALL ACTIVITIES THE STUDENT COMPLETED FOR THIS SEAT'S LESSON
  $activities = Activities_Results_ReadBySource($seat["student_id"], $lesson_id, false);
  
  // DERIVE ACTIVITY TYPE BY SOURCE
  foreach($activities as &$activity)
  {
   $activity["type"] = Storage_Path_GetFilename($activity["source"]);
  }
  
  // CATALOG BY TYPE
  $activities = Array_Catalog_ByField($activities, "type");
  
  // GET MOST RECENT SCORE FOR EACH ACTIVITY TYPE
  $scores = [];
  $types  = array_keys($activities);
  foreach($types as $type)
  {
   $activities[$type] = array_reverse($activities[$type]);
   $last              = $activities[$type][0];
   $scores[$type]     = $last["score"];
  }
  
  $data["preparation"] = $scores;
 }
 
 
 // PERFORMANCE FROM PAST CLASSES
 if(isset($options["performance"]))
 {
  $performance = [];
  
  $n     = $options["performance"];
  $seats = Class_Seats_ListByStudent(student_id:$seat["student_id"], date_to:Date_Now(), options:["last" => true, "limit" =>$n, "assessment" => true]);
  $seats = array_reverse($seats);
  
  foreach($seats as &$seat)
  {
   $array = $seat["assessment"] ?? [0];
   $score = array_sum($array) / count($array);
   
   array_push($performance, $score);
  }
  
  $data["performance"] = $performance;
 }
 
 
 return $data;
}






function Assessment_Outcome_Store($table, $id, $field, $value, $return = false)
{
 $db    = Core_Database_Open();
 
 // GET CURRENT JSON
 $query = "SELECT assessment FROM $table WHERE id = $id";
 $rows  = SQL_Query($query, $db);
 $seat  = $rows[0];

 // DECODE OR CREATE IF NECESSARY, UPDATE
 if(!isset($seat["assessment"])) 
 {
  $data = [];
 }
 else
 {
  $data = json_decode($seat["assessment"], true);
 }
 
 $data[$field] = $value;


 // RE-ENCODE AND STORE BACK
 $assessment = json_encode($data);
 $assessment = SQL_Format($assessment, $db);
 $query      = "UPDATE $table SET assessment = $assessment WHERE id = $id";
 SQL_Query($query, $db);
 
 // UPDATE HOMEWORK SCORE 
 if($field == "hw-000" || $table == "projects_students")
 {
  Grades_Homework_Projects_Update($table,$id,$field);
 }
 // RETURN THE WHOLE UPDATED OBJECT ONLY IF REQUESTED
 if($return) return $data;
}



function Assessment_Status($teacher_id = -1, $date_from = "190001010000", $date_to = "290001010000", $options = [])
{
 if($teacher_id == -1) $teacher_id = $_SESSION["user"]["id"];
 
 // FORCE DATE_TO TO NOW
 $date_to = Date_Now(); 

 // GET ALL CLASSES FOR THE GIVEN TEACHER IN THE GIVEN DATE RANGE
 $classes = Classes_List_ByTeacher($teacher_id, $date_from, $date_to, "id, lesson_id, center_id, date_start");
 
 if(count($classes) == 0) return [];
 

 // COLLECT INFO FOR ALL LESSONS
 $ids     = array_column($classes, "lesson_id");
 $ids     = array_unique($ids);
 $lessons = [];
 foreach($ids as $id)
 {
  $lessons[$id]                = Lesson_Read($id, "base-info");
  $lessons[$id]["assessables"] = Lesson_Assessables($id);
 }
 

 // GET ALL SEATS FROM THE LIST OF CLASSES
 $db    = Core_Database_Open();
 $list  = array_column($classes, "id");
 $list  = SQL_Values_List($list);
 
 $query = "SELECT id, class_id, student_id, attendance, assessment FROM classes_seats WHERE class_id IN ($list)";
 $seats = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 
 // PROCESS SEATS
 $classes = Array_Catalog_ByField($classes, "id", true);
 foreach($seats as &$seat) 
 {
  // FIND CLASS AND LESSON DATA FOR THIS SEAT
  $class  = $classes[$seat["class_id"]];
  $lesson = $lessons[$class["lesson_id"]];
  $center = $lessons[$class["center_id"]];
  
  $seat["date_start"] = $class["date_start"];
  $seat["lesson_id"]  = $class["lesson_id"];
  $seat["center_id"]  = $class["center_id"];

  
  // DECODE SEAT ASSESSMENT
  $seat["assessment"] = json_decode($seat["assessment"], true);
  
  
  // CHECK WHAT'S MISSING
  $missing = [];
  
  $categories = array_keys($lesson["assessables"]);
  foreach($categories as $category)
  {
   $done = false;
   
   foreach($lesson["assessables"][$category] as $key)
   {
	if(isset($seat["assessment"][$key]))
	{
     $done = true;
	 break;
	}		
   }
   
   if(!$done) array_push($missing, $category);
  }
  
  if(!$seat["attendance"]) array_push($missing, "attendance");
  
  $seat["todo"] = $missing;
  
  // DISCARD ASSESSMENT
  //unset($seat["assessment"]);
 }
 
 
 if($options["incomplete"])
 {
  $filtered = [];
  foreach($seats as &$seat) if(count($seat["todo"]) > 0) array_push($filtered, $seat);
  
  $seats = $filtered;
 }
 
 
 // ADD STUDENTS NAME AND LAST NAME TO SEATS
 Users_Integrate($seats, "student_id", "id,firstname,lastname,nickname", $container = "student");
 
 
 // ADJUST DATES
 if(!$options["utc"]) User_Date_Process($seats, "date_", "out");
 
 
 return $seats;
}


function Sync_PlacementTest_Data()
{
  $db    = Core_Database_Open();
  $dataPOST = trim(file_get_contents('php://input'));
  $xmlData = simplexml_load_string($dataPOST);
  $json = json_encode($xmlData);
  $array = json_decode($json,true);

  $testId = $array["ID"] ?? -1;
  if($testId)
  {
    $placementTests = SQL_Query("SELECT * FROM placement_tests WHERE placement_test_id = $testId", $db);
    if(count($placementTests))
    {
      
      $studentTest = $placementTests[0];

      // GET LESSON NAME
      $classId = $studentTest["class_id"];
      $classes = SQL_Query("SELECT lesson_id FROM classes WHERE id = $classId", $db);
      $source = $classes[0]["lesson_id"];
      $lesson = parse_ini_file("content/lessons/$source/info.dat", true);

      // READ CORE SKILLS
      $keys                  = array_values($lesson["core skills"] ?? []) ?: array_keys($skills["core skills"] ?? []) ?: [];
      $lesson["core skills"] = [];
        
      foreach($keys as $key)
      {
        $lesson["core skills"][$key] = Outcome_Read("content/skills/$key");   
      }


      // READ OUTCOMES
      $ids                = array_values($lesson["outcomes"] ?? []);
      $lesson["outcomes"] = [];

      foreach($ids as $id)
      {
        $outcome                 = Outcome_Read("content/outcomes/$id");
        $lesson["outcomes"][$id] = $outcome;
      }

      $behavior = parse_ini_file("application/modules/assessment/behavior.dat",true);

      $comment = [];
      $studentCode = $studentTest["student_code"];

      $seats = SQL_Query("SELECT classes_seats.feedback, classes_seats.behavior, classes_seats.assessment FROM classes_seats JOIN users ON classes_seats.student_id = users.id WHERE classes_seats.class_id = $classId AND users.staffcode = '$studentCode'", $db);
      $assessment = json_decode($seats[0]["assessment"], true);

      $hours = $lesson["outcomes"]["pt-002"]["lv ".$assessment["pt-002"]]["hours"];
      $levelIdeal = $lesson["outcomes"]["pt-001"]["lv ".$assessment["pt-001"]]["erp"];
      $module = $lesson["outcomes"]["pt-001"]["lv ".$assessment["pt-001"]]["module"];

      // GET PROGRAM
      $programs = parse_ini_file("partners/default/programs.cfg",true);
      $programer = "";
      $level = $levelIdeal . "-" . $module;
      foreach ($programs as $key => $program) {
        foreach ($program as $keyP => $value) {
          if($keyP == $level){ $programer = $program["program"]; break; }
        }
        if($programer != "") break;
      }

      $programer_mapping = [
        "Jumpstart" => "EY-K",
        "Super Juniors" => "EY-J",
        "Smart Teens" => "EY-S",
        "Global English" => "EA-GE",
        "Jumpstart Online" => "EY-KO",
        "Super Juniors Online" => "EY-JO",
        "Smart Teens Online" => "EY-SO",
        "Global English Online" => "EA-GO"
      ];

      $programe = $programer_mapping[$programer];


      foreach ($assessment as $key => $value) {
        if(isset($lesson["core skills"][$key])) $comment[$lesson["core skills"][$key]["info"]["en"]] = $lesson["core skills"][$key]["lv $value"]["en"];
      }

      $comment["behavior"] = $behavior[(int)$seats[0]["behavior"]]["en"];
      $comment["feedback"] = $seats[0]["feedback"];

      $description = "";
      foreach ($comment as $key => $value) {
        $description .= "(" . $key . "):\n" . $value . ".\n";
      }

      $xml = '<?xml version="1.0" encoding="utf-8"?>
      <Result>
        <getStatus>1</getStatus>
        <Message>successful</Message>
        <PTID>%placement_test_id%</PTID>
        <Programme>%programme%</Programme>
        <LevelID>%level%</LevelID>
        <Module>%module%</Module>
        <IdealHours>%hour%</IdealHours>
        <SpeakingScore>0</SpeakingScore>
        <Description>%description%</Description>
        <Description_JS></Description_JS>
      </Result>';
      $xml = str_replace("%placement_test_id%",$studentTest["placement_test_id"],$xml);
      $xml = str_replace("%programme%",$programe,$xml);
      $xml = str_replace("%level%",$levelIdeal,$xml);
      $xml = str_replace("%module%",$module,$xml);
      $xml = str_replace("%hour%",$hours,$xml);
      $xml = str_replace("%description%",$description,$xml);

      header('Content-Type: text/xml');
      print_r($xml);
      
    }
  }
  SQL_Close($db);
  die;
}


?>