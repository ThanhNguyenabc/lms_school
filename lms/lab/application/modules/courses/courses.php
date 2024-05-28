<?PHP

function Courses_Load()
{
}



function Courses_Read($id, $fields = "*", $info = false, $options = [])
{
 // QUERY 
 $db = Core_Database_Open();
 
 $query = "SELECT $fields FROM courses WHERE id = $id";
 $data  = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 $course = $data[0];
 
 // DECODE JSON FIELDS
 $decode = ["schedule", "staff", "students", "classes", "config"];
 Array_Fields_JSONParse($course, $decode);
 foreach($decode as $field) if(!$course[$field]) $course[$field] = [];
 
 // CLASSES DATES AND TIMES
 if(!$options["utc"]) User_Date_Process($course["classes"], "date_", "out"); 
 
 // LOAD STUDENTS & STAFF INFO?
 foreach(["students", "staff"] as $section)
 {
  
 if($info && ($info["staff"] || $info["all"]))
  { 
   $ids   = array_values($course[$section]); 
   $users = Users_Read($ids);
  
   $keys = array_keys($course[$section]);
   foreach($keys as $key)
   {
    $id         = $course[$section][$key];
    
    $user       = [];
    $user["id"] = $key;
	  $userinfo   = $users[$id] ?? [];
    $user       = array_merge($user, $userinfo); 
	 
    $course[$section][$key] = $user;
   }
  }

 }
 
 
 

 return $course;
}



function Courses_Update_Field($id, $field, $value, $json = false)
{
 $db = Core_Database_Open();
 
 if($json) $value = json_encode($value);
 $value  = SQL_Format($value, $db);

 $query = "UPDATE courses SET $field = $value WHERE id = $id";
 $data  = SQL_Query($query, $db);
 
 SQL_Close($db);
}



function Courses_Ongoing($centers, $fields = "id, name")
{
 $date    = Date_Format_As(Date_Now(), "date-only");
 
 $db      = Core_Database_Open();
 
 $centers = SQL_Format_IN($centers, $db);
 
 $query   = "SELECT $fields FROM courses WHERE center_id IN ($centers) AND (date_end >= $date) ORDER BY date_start";
 $data    = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 return $data;
}



function Courses_List($status = false, $centers = false, $program = false, $level = false, $date = false, $date_mode = "ongoing", $order = "date_start", $options = [])
{
 $conditions = [];
  
  
 // STATUS
 if($status)
 {
  array_push($conditions, "status = '$status'");
 }
  
 // NAME
 if(isset($options["name"]) && $options["name"])
 {
  array_push($conditions, "name LIKE '%".$options["name"]."%'");
 }
  
 // CENTERS
 if($centers)
 {
  foreach($centers as &$center) $center = "'$center'";
  
  $centers = implode(",", $centers);
  
  array_push($conditions, "center_id IN ($centers)");
 }
 
 
 // PROGRAM
 if($program)
 {    
  array_push($conditions, "program = '$program'");
 }
 
 
 // LEVEL
 if($level)
 {    
  array_push($conditions, "level = '$level'");
 }
 
 
 // DATE
 $date = Date_Format_As($date, "date-only");
 
 switch($date_mode)
 {
  case "ongoing":
	$now = Date_Format_As(Date_Now(), "date-only");
	array_push($conditions, "date_start <= $date");
	array_push($conditions, "date_end >= $date");
  break;
  
  case "starts-after":
	array_push($conditions, "date_start >= $date");
  break;
  
  case "ends-after":
	array_push($conditions, "date_end >= $date");
  break;
  
  case "ends-before":
	array_push($conditions, "date_end <= $date");
  break;
 }
 
 $limit = " LIMIT 100";
 if(isset($options["limit"]))
 $limit = " LIMIT " . $options["limit"];
 if(isset($options["offset"]))
 $limit .= " OFFSET " . $options["offset"];
 
 $conditions = implode(" AND ", $conditions);
 
 
 // QUERY 
 $db = Core_Database_Open();
 
 $query = "SELECT * FROM courses WHERE $conditions ORDER BY $order $limit";
 $data  = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 // DECODE JSON FIELDS
 Array_Items_JSONParse($data, ["schedule", "staff", "students", "classes", "config"]);
 
 
 // CALCULATE SEATS LEFT
 foreach($data as &$item) 
 {
  $seats = [];
  $seats["taken"] = count($item["students"] ?? []);
  $seats["total"] = $item["seats"] ?? 0;
  
  unset($item["students"]);
  $item["seats"] = $seats;
 }
 
 
 // CALCULATE CLASSES LEFT
 $now = Date_Format_As(Date_Now(), "date-only");
 foreach($data as &$item) 
 {
  $all   = $item["classes"] ?? [];
  $taken = 0;
  
  foreach($all as $class)
  {
   $date = $class["date"];
   
   if($date < $now)
   {
	$taken = $taken + 1;
   }
   else 
   {
	break;
   }
  }
  
  $classes          = [];
  $classes["taken"] = $taken;
  $classes["total"] = count($all);
  
  $item["classes"]  = $classes;
 }
 
 
 
 return $data;
}

function Courses_Update_Classes($id, $classes)
{
 $db = Core_Database_Open();
 User_Date_Process($classes, "date_", "in");

 $course_date_start = $classes[0]["date_end"];
 $course_date_end = $classes[count($classes)-1]["date_end"];
 
 $classes  = json_encode($classes);
 $query = "UPDATE courses SET classes = '$classes' , date_end = $course_date_end, date_start = $course_date_start WHERE id = $id";
 $data  = SQL_Query($query, $db);
 SQL_Close($db);
}

function Courses_New($data)
{
 $db = Core_Database_Open();
 
 $insert = SQL_Fields_Insert($data, $db);
 $query  = "INSERT INTO courses $insert";
 $id     = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 return $id;
}




function Courses_Rollout($id, $center, $classes, $options = [])
{
 $db     = Core_Database_Open();

 $center = SQL_Format($center, $db);
 if(!$options["utc"]) User_Date_Process($classes, "date_", "in");
 
 
 $students   = SQL_Query("SELECT student_id, lesson_ids FROM registration WHERE course_id = $id", $db);

 
 // FIRST, ROLLBACK
 $pastClasses = Courses_Rollback($id); 
 
 
 $db->beginTransaction();
 foreach($classes as &$class)
 {
  $date_start        = $class["date_start"];
  $duration          = $class["duration"];
  $date_end          = $class["date_end"];

  $teacher_id        = $class["teacher_id"] ?? "NULL";
  $ta1               = $class["ta1_id"] ?? "NULL";
  $ta2               = $class["ta2_id"] ?? "NULL";
  $ta3               = $class["ta3_id"] ?? "NULL";
  $classroom_id      = $class["classroom_id"] ?? "NULL";
  
  $teacher_type      = SQL_Format($class["teacher_type"], $db);
  $lesson_id         = SQL_Format($class["lesson_id"], $db);
   
  $class["id"]       = SQL_Query("INSERT INTO classes (center_id, date_start, date_end, duration, lesson_id, teacher_type, course_id, teacher_id, ta1_id, ta2_id, ta3_id, classroom_id) VALUES ($center, $date_start, $date_end, $duration, $lesson_id, $teacher_type, $id, $teacher_id, $ta1, $ta2, $ta3, $classroom_id)", $db); 

  foreach($students as $student) {
    $lessons = $student["lesson_ids"];
    if(strpos($lessons, $class["lesson_id"]) !== false) {
      SQL_Query("INSERT INTO classes_seats (class_id, student_id, course_id) VALUES({$class["id"]}, {$student["student_id"]}, $id)", $db);
    }
  }
 }
 $db->commit();
 
 $date_end = $classes[count($classes)-1]["date_end"];
 
 $classes  = json_encode($classes);
 
 SQL_Query("UPDATE courses SET classes = '$classes', date_end = $date_end WHERE id = $id", $db); 

 
 // REMOVE ALL PAST CLASSES AFTER CREATING NEW CLASSES
 if($pastClasses) {
  $ids = array_column($pastClasses, "id");
  $ids = SQL_Format_IN($ids, $db);
  SQL_Query("DELETE FROM classes WHERE id IN ($ids)", $db); 
  SQL_Query("DELETE FROM classes_seats WHERE class_id IN ($ids)", $db); 
 }

 SQL_Close($db);


 $classes = json_decode($classes);
 foreach ($classes as $key => $value) {
  $classes[$key] = (array)$value;
 }
 if(!$options["utc"]) User_Date_Process($classes, "date_", "out");
 
 //CREATE TEST
 Courses_Test_Create($id);

 json_encode($classes);
 return $classes;
}



function Courses_Rollback($id)
{  
 $db = Core_Database_Open();
 
 
 // COLLECT CLASSES IDS FROM TODAY ONWARD
 $now      = Date_Format_As(Date_Now(), "date-only") . "0000"; 
 $classes  = SQL_Query("SELECT id FROM classes WHERE course_id = $id AND date_start >= $now", $db);
 
 $ids      = array_column($classes, "id");
 $ids      = SQL_Format_IN($ids, $db);
 
 if($ids) 
 {	 
  // KEEP ONLY CLASSES FROM TODAY AND BEFORE
  SQL_Query("DELETE FROM classes WHERE id IN ($ids)", $db); 
  SQL_Query("DELETE FROM classes_seats WHERE class_id IN ($ids)", $db); 
 }
 
 
 // UPDATE COURSE CLASSES JSON BY KEEPING ONLY CLASSES FROM TODAY AND BEFORE
 $courses = SQL_Query("SELECT classes FROM courses WHERE id = $id", $db);
 $course  = $courses[0];
 $classes = $course["classes"];
 $classes = json_decode($classes, true);
 
 $now  = Date_Format_As(Date_Now(), "date-only");
 $past = [];
 foreach($classes as $class)
 {
  if(Date_Format_As($class["date_start"], "date-only") < $now)
  {
   array_push($past, $class);
  }
 }
 $classes = $past;

 // UPDATE CLASSES AND ERASE STAFF
 $classes = json_encode($classes);
 $classes = SQL_Format($classes, $db);
 SQL_Query("UPDATE courses SET classes = $classes WHERE id = $id", $db); 
 
 SQL_Close($db);
 return $past;
}




function Courses_Classes_List($id, $date_from = "197001010000", $date_to = "210001012359", $fields = "id", $options = [])
{
 if(!$options["utc"])
 {
  $date_from = User_Date($date_from, "in");
  $date_to   = User_Date($date_to,   "in");
 }
	
 $db      = Core_Database_Open();
 $classes = SQL_Query("SELECT $fields FROM classes WHERE course_id = $id AND date_start BETWEEN $date_from AND $date_to", $db);   
 SQL_Close($db);
 
 return $classes;
}




function Courses_Staff_Set($classes, $field, $user_id)
{
 $now = Date_Now();
 
 Classes_Batch_SetField($classes, $field, $user_id, ["from" => $now]);
}




function Courses_Staff_Unset($id, $field, $user_id)
{
 $now     = Date_Now();
 $db      = Core_Database_Open();
 
 $query   = "UPDATE classes SET $field = NULL WHERE course_id = $id AND $field = $user_id AND date_start > $now";
 SQL_Query($query, $db);
 
 SQL_Close($db);
}





function Courses_Rooms_Available($id)
{
 // COLLECT DATES FOR ALL LESSONS OF THIS COURSE
 $db      = Core_Database_Open();
 $rows    = SQL_Query("SELECT center_id, classes FROM courses WHERE id = $id", $db);
 SQL_Close($db);
 
 // CENTER AND CLASSES TO CHECK
 $center  = $rows[0]["center_id"];
 $classes = json_decode($rows[0]["classes"], true);
 
 // CONVERT CLASS DATES TO UTC
 User_Date_Process($classes, "date_", "in");
 
 // REMOVE CLASSES IN THE PAST
 $now      = Date_Now();
 $filtered = [];
 foreach($classes as $class)
 {
  if($class["date_start"] > $now) array_push($filtered, $class);
 }
 
 // SEE WHAT ROOMS ARE AVAILABLE THROUGHOUT THOSE DATES
 $rooms = Center_Rooms_Available($center, $filtered, []);

 return $rooms;
}



function Courses_Students_Add($id, $student_id, $date_from = false, $amount = 0, $options = [])
{
 $db = Core_Database_Open();
  
 // GET STUDENTS AND CLASSES
 $rows   = SQL_Query("SELECT students, seats FROM courses WHERE id = $id", $db);
 $course = $rows[0];
 
 $students = $course["students"];
 $students = json_decode($students, true) ?? [];
 
 // CHECK COURSE SEATS LIMIT
 if($options["checkseats"])
 {
  if(count(array_keys($students)) >= $course["seats"])
  {
   SQL_Close($db);  
   return "no seats";
  }	  
 }	 
 
 
 // IF DATE_FROM NOT SET, THEN IT'S TODAY
 if(!$date_from)
 {
  $date_from = Date_Format_As(Date_Now(), "date-only") . "2359";
 }
 
 
 // GET IDS FOR COURSE CLASSES FROM DATE_FROM ONWARD
 $classes = Courses_Classes_List($id, $date_from, "210001012359", "id, lesson_id");
 $ids     = array_column($classes, "id");
 $ids     = SQL_Format_IN($ids, $db);
 
 // UPDATE STUDENTS OBJECT
 if(!in_array($student_id, $students))  {
  array_push($students, $student_id);
  $students = json_encode($students);
  $students = SQL_Format($students, $db);
  SQL_Query("UPDATE courses SET students = $students WHERE id = $id", $db);
 }


 // UPDATE REGISTRATION FOR EACH STUDENT
 $lesson_ids = array_column($classes, "lesson_id") ?: [];

 $registration = SQL_Query("SELECT lesson_ids FROM registration WHERE course_id = $id AND student_id = $student_id LIMIT 1", $db);
 $past_lessons = json_decode($registration[0]["lesson_ids"], true) ?: [];
 $combined_lessons = array_unique([...$past_lessons, ...$lesson_ids]);
 if($combined_lessons) {
  $combined_lessons = array_values($combined_lessons);
  sort($combined_lessons);
  $combined_lessons = SQL_Format(json_encode($combined_lessons), $db);
  SQL_Query("INSERT INTO registration (course_id, student_id, lesson_ids) VALUES($id, $student_id, $combined_lessons) ON DUPLICATE KEY UPDATE lesson_ids = $combined_lessons", $db);
 }


 // SANITIZATION: DELETE EXISTING STUDENT SEATS FOR THIS COURSE FROM DATE_FROM ONWARD
 SQL_Query("DELETE FROM classes_seats WHERE (student_id = $student_id AND class_id IN ($ids))", $db);
 
 
 // CREATE SEATS FOR ALL CLASSES FROM DATE_FROM ONWARD
 $ids  = array_column($classes, "id");
 
 $db->beginTransaction();
 $seats = [];
 foreach($ids as $class_id)
 {  
  $seat_id = SQL_Query("INSERT INTO classes_seats (class_id, student_id, course_id) VALUES($class_id, $student_id, $id)", $db);
  array_push($seats, $seat_id);
  
  // IF AN AMOUNT IS SET AND WE MATCHED IT, STOP ADDING
  if($amount && count($seats) >= $amount) break;
 }
 $db->commit();
 
 SQL_Close($db);
 
 return $seats;
}


function Courses_Students_Remove($id, $student_id, $seats = false)
{ 
 $db = Core_Database_Open();
  
 // GET STUDENTS AND CLASSES
 $rows   = SQL_Query("SELECT students, classes FROM courses WHERE id = $id", $db);
 $course = $rows[0];
 
 // REMOVE SPECIFIC STUDENT FROM STUDENTS
 $students = $course["students"];
 $students = json_decode($students, true);
 Array_Item_Delete($students, $student_id);
 $students = json_encode($students);
 $students = SQL_Format($students, $db);
 SQL_Query("UPDATE courses SET students = $students WHERE id = $id", $db);
 
 
 // REMOVE SEATS FOR ALL CLASSES FROM TOMORROW ONWARD
 $classes = $course["classes"];
 $classes = json_decode($classes, true);
 $today   = Date_Format_As(Date_Now(), "date-only");

 $past_lessons = [];
 $seatIds = []; 
 foreach($classes as $class)
 {
  if(Date_Format_As($class["date_start"], "date-only") > $today)
  {
   array_push($seatIds, $class["id"]);
  } else {
    array_push($past_lessons, $class["lesson_id"]);
  } 
 }
 
 // DELETE ALL CLASS SEAT IF EXISITNG
 if($seatIds) {
  $seatIds = SQL_Format_IN($seatIds, $db);
  SQL_Query("DELETE FROM classes_seats WHERE student_id = $student_id AND class_id IN ($seatIds)", $db);
 }

 // UPDATE PAST LESSON ON REGISTRATION TABLE  
 $registration = SQL_Query("SELECT lesson_ids FROM registration WHERE course_id = $id AND student_id = $student_id LIMIT 1", $db);
 if($registration) {
  $lesson_ids = json_decode($registration[0]["lesson_ids"]) ?: [];

  if(!$past_lessons || $past_lessons && $lesson_ids && $past_lessons[0] !== $lesson_ids[0]) {
    SQL_Query("DELETE FROM registration WHERE course_id = $id AND student_id = $student_id", $db);
  } else {
    $past_lessons = SQL_Format(json_encode($past_lessons), $db);
    SQL_Query("UPDATE registration SET lesson_ids = $past_lessons WHERE course_id = $id AND student_id = $student_id", $db);
  }
 }

 SQL_Close($db);
}




function Courses_Delete($id)
{ 
 // ROLLBACK ALLOCATED CLASSES, IF ANY
 Courses_Rollback($id);
 
 // DELETE COURSE FROM DATABASE
 $db = Core_Database_Open();
 
 $query  = "DELETE FROM courses WHERE id = $id";
 SQL_Query($query, $db);
 
 SQL_Close($db);
}


function Courses_Lock_New($data)
{
 $db = Core_Database_Open();
 $select  = "SELECT id FROM courses_lock WHERE user_id = ".$data["user_id"];
 $log     = SQL_Query($select, $db);
 if($log) $data["id"] = $log[0]["id"];
 $insert = SQL_Fields_Insert($data, $db);
 $query  = "INSERT INTO courses_lock $insert ON DUPLICATE KEY UPDATE last_update = ".$data["last_update"].", course_id = ".$data["course_id"];
 $id     = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 return $id;
}



function Course_Edit_Available($id)
{
 $db = Core_Database_Open();
 $select  = "SELECT user_id FROM courses_lock WHERE course_id = ".$id;
 $log     = SQL_Query($select, $db);
 
 if($log)
 {
  $query  = "SELECT id,firstname, lastname, midname FROM users WHERE id = ". $log[0]["user_id"]; 
  $rows   = SQL_Query($query, $db);
  $user   = $rows[0];
  SQL_Close($db);
  return $user;
 } 
 else{
  SQL_Close($db);
  return 0;
 } 
}



function Courses_Lock_Delete($user_id = -1)
{ 
 if($user_id == -1) $user_id = $_SESSION["user"]["id"];
 
 // DELETE COURSE LOG FROM DATABASE
 $db = Core_Database_Open();
 
 $query  = "DELETE FROM courses_lock WHERE user_id = $user_id";
 SQL_Query($query, $db);
 
 SQL_Close($db);
}

function Courses_Check_Exist($search = [])
{ 
  $search = (array)$search;
  $db = Core_Database_Open();
  
  $conditions = [];

  foreach ($search as $key => $value) {
    if($key == "date_start" || $key == "date_end" || $key == "seats")
      array_push($conditions,"(".$key." = ".$value.")");
    else
      array_push($conditions,"(".$key." = '".$value."')");
  }

  $conditions = implode(" AND ", $conditions);

  $query = trim("SELECT id, classes, lesson_duration, date_start, date_end FROM courses WHERE $conditions");

  $rows  = SQL_Query($query, $db);
  SQL_Close($db);

  if(!$rows) return [];

  $course = $rows[0];
  if($course["classes"]) {
    Array_Fields_JSONParse($course, ["classes"]);
    User_Date_Process($course["classes"], "date_", "out"); 
  }

  return $course;
}

function Courses_Update($course)
{
 $db = Core_Database_Open();
 $id = $course["id"];
 $courseinfo = SQL_Query("SELECT * FROM courses WHERE id = $id", $db);
 if($courseinfo){
  try {
    $data = [];
    $query = "UPDATE courses SET ";
    foreach ($course as $field => $value) {
      if($field != "id" && $courseinfo[$field] != $value){
        if(gettype($value) == "array") $value = json_encode($value);
        $value  = SQL_Format($value, $db); 
        array_push($data," $field = $value ");
      }
    }
    $data = implode(" , ",$data);
    $query = $query .$data . " WHERE id = $id";
    SQL_Query($query, $db);
    SQL_Close($db);
  } catch (\Throwable $th) {
    var_dump($th->getMessage());
    return false;
  }
 }
 return true;
}


function Courses_MultipleStudents_Add($id, $students = [], $amount = 0, $options = [])
{
 $db = Core_Database_Open();
  	 
 $seats = [];
 foreach ($students as $key => $student) { 
    // GET STUDENTS AND CLASSES
    $rows   = SQL_Query("SELECT students, seats FROM courses WHERE id = $id", $db);
    $course = $rows[0];

    $coursestudents = $course["students"];
    $coursestudents = json_decode($coursestudents, true) ?? [];

    // CHECK COURSE SEATS LIMIT
    if($options["checkseats"])
    {
      if(count(array_keys($coursestudents)) >= $course["seats"])
      {
        SQL_Close($db);  
        return "no seats for student:".$student["student_id"];
      }	  
    }
    
    $student  =(array)$student;
    $date_from = $student["date_from"];
    $student_id = $student["student_id"];

    // IF DATE_FROM NOT SET, THEN IT'S TODAY
    if(!$date_from)
    {
      $date_from = Date_Format_As(Date_Now(), "date-only") . "2359";
    }

    // GET IDS FOR COURSE CLASSES FROM DATE_FROM ONWARD
    $classes = Courses_Classes_List($id, $date_from, "210001012359", "id, lesson_id");
    $ids     = array_column($classes, "id");
    $ids     = SQL_Format_IN($ids, $db);

    // UPDATE STUDENTS OBJECT
    if(!in_array($student_id, $coursestudents))  {
      array_push($coursestudents, $student_id);
      $coursestudents = json_encode($coursestudents);
      $coursestudents = SQL_Format($coursestudents, $db);
      SQL_Query("UPDATE courses SET students = $coursestudents WHERE id = $id", $db);
    }

    // UPDATE REGISTRATION FOR EACH STUDENT
    $lesson_ids = array_column($classes, "lesson_id") ?: [];

    $registration = SQL_Query("SELECT lesson_ids FROM registration WHERE course_id = $id AND student_id = $student_id LIMIT 1", $db);
    $past_lessons = json_decode($registration[0]["lesson_ids"], true) ?: [];
    $combined_lessons = array_unique([...$past_lessons, ...$lesson_ids]);
    if($combined_lessons) {
      $combined_lessons = array_values($combined_lessons);
      sort($combined_lessons);
      $combined_lessons = SQL_Format(json_encode($combined_lessons), $db);
      if($past_lessons){
        SQL_Query("UPDATE registration SET lesson_ids = $combined_lessons WHERE course_id = $id AND student_id = $student_id", $db);
      }else
        SQL_Query("INSERT INTO registration (course_id, student_id, lesson_ids) VALUES($id, $student_id, $combined_lessons)", $db);
    }

    // SANITIZATION: DELETE EXISTING STUDENT SEATS FOR THIS COURSE FROM DATE_FROM ONWARD
    SQL_Query("DELETE FROM classes_seats WHERE (student_id = $student_id AND class_id IN ($ids))", $db);
    
    // CREATE SEATS FOR ALL CLASSES FROM DATE_FROM ONWARD
    $ids  = array_column($classes, "id");
    $db->beginTransaction();
    $seats[$student_id] = [];
    foreach($ids as $class_id)
    {  
      $seat_id = SQL_Query("INSERT INTO classes_seats (class_id, student_id, course_id) VALUES($class_id, $student_id, $id)", $db);
      array_push($seats[$student_id], $seat_id);
      
      // IF AN AMOUNT IS SET AND WE MATCHED IT, STOP ADDING
      if($amount && count($seats[$student_id]) >= $amount) break;
    }
    $db->commit();
 }
 SQL_Close($db);
 return true;
}

function Course_List_ByTeacher($teacher_id, $date_from, $date_to, $options = [])
{
  $db = Core_Database_Open();
  $teacherCondition = "staff LIKE '%\"$teacher_id\"%'";
  $date_from_custom = substr($date_from,0,8);
  $date_to_custom = substr($date_to,0,8);

  $query = "SELECT * FROM courses WHERE $teacherCondition AND ((date_start BETWEEN $date_from_custom AND $date_to_custom) OR (date_end BETWEEN $date_from AND $date_to) OR (date_start < $date_from_custom AND date_end > $date_to)) ORDER BY date_start";
  
  $data  = SQL_Query($query, $db);
 
  foreach ($data as $key => &$value) {
    // DECODE JSON FIELDS
    $decode = ["schedule", "staff", "students", "classes", "config"];
    Array_Fields_JSONParse($value, $decode);
    // CLASSES DATES AND TIMES
    if(!$options["utc"]) User_Date_Process($value["classes"], "date_", "out"); 
  }
 
  SQL_Close($db);
  return $data;
}

function Courses_Test_Create($course_id)
{
  $db = Core_Database_Open();
  try {
  $courses = SQL_Query("SELECT * FROM courses WHERE id = $course_id",$db);
  $course = $courses[0];
  $program = $course["program"];
  $level = $course["level"];
  
  $classes = json_decode($course["classes"]);

  $grades = parse_ini_file("partners/default/grades.cfg",true);
  $programType = $grades[$program]["type"];
  $lessonTest = $grades[$program][$level];
  $lessonTest = explode(";",$lessonTest);
  
  SQL_Transaction_Begin($db);

  // GET ALL EXIST COURSE TEST
  $dataCheck = [];
  $courseTests = SQL_Query("SELECT id, class_id, test_type, name FROM course_test WHERE course_id = $course_id",$db);
  foreach ($courseTests as $key => $value) {
   $dataCheck[$value["id"]] = $value["test_type"] . "__" . $value["name"];
  }
  $studentTests = SQL_Query("SELECT id,test_id FROM student_test WHERE course_id = $course_id",$db);

  $maxScoreTotal = 0;

  // DELETE EXIST COURSE TEST
  SQL_Query("DELETE FROM course_test WHERE course_id = $course_id",$db);
  foreach ($lessonTest as $key => $lessonText) 
  {
    $lessons = explode("__",$lessonText);
    $lesson_id = $lessons[0];
    
    $classKey = array_search($lesson_id,array_column($classes,"lesson_id"));
    $classId = $classes[$classKey]->id;
    
    $lessonInfo = parse_ini_file("content/lessons/$lesson_id/info.dat",true);
    $test = $lessonInfo["test"];
    $testType = $test["type"];
    $testType = SQL_Format($testType,$db);
   
    $skillCount = 0;
    $testKeys = array_keys($test);
    
    for ($i=0; $i < count($testKeys) ; $i++) { 
      if(str_contains($testKeys[$i],"skill")) $skillCount++;
    }
  
    $maxScoreTest = 0;
    $maxWeight = 0;
    for ($i=0; $i < $skillCount; $i++) { 
      $name = $test["skill 0$i"];
      $name = SQL_Format($name,$db);
      $maxScore = $test["max score 0$i"];
      $maxScoreTest += (int)$test["max score 0$i"];
      $weight = $test["percentage 0$i"];
      $maxWeight += (int)$test["percentage 0$i"];
      $id = SQL_Query("INSERT INTO course_test(course_id,class_id,test_type,name,max_score,weight) VALUE($course_id,$classId,$testType,$name,$maxScore,$weight)",$db);
      
      //UPDATE EXIST STUDENT TEST
      if(count($studentTests))
      {
        foreach ($studentTests as $key => $value) {
          if($dataCheck[$value["test_id"]] == $test["type"] . "__" . $test["skill 0$i"])
          {
            $studentTestId = $value["id"];
            SQL_Query("UPDATE student_test SET class_id = $classId , test_id = $id WHERE id = $studentTestId",$db);
          }
        }
      }

      // SET TOTAL SCORE
      switch ($programType) {
        case 'sat':
          if($test["type"] == "Best Scores") $maxScoreTotal += (int)$maxScore;
          break;
        case 'ielts': 
          if($test["type"] == "eomt") $maxScoreTotal += (int)$maxScore/100*$weight;
          break;
      }
    }

    // ADD STRETCH COLUMN WITH PROGRAM TYPE
    switch ($programType) {
      case 'sat':
        if($test["type"] != "Best Scores" && $test["type"] != "Homework")
        {
          $name = $test["type"] . " total";
          $name = SQL_Format($name,$db);
          $id = SQL_Query("INSERT INTO course_test(course_id,class_id,test_type,name,max_score,weight) VALUE($course_id,$classId,$name,$name,$maxScoreTest,0)",$db);
          //UPDATE EXIST STUDENT TEST
          if(count($studentTests))
          {
            foreach ($studentTests as $key => $value) {
              if($dataCheck[$value["test_id"]] == $test["type"] . "__" . $test["type"] . " total")
              {
                $studentTestId = $value["id"];
                SQL_Query("UPDATE student_test SET class_id = $classId , test_id = $id WHERE id = $studentTestId",$db);
              }
            }
          }
        } 
        break;
      case 'ielts':
        // CREATE OR UPDATE MODULE TEST TOTAL
        if($test["type"] == "eomt" || $test["type"] == "mmt")
        {
          $name = $test["type"] . " total";
          $name = SQL_Format($name,$db);
          $id = SQL_Query("INSERT INTO course_test(course_id,class_id,test_type,name,max_score,weight) VALUE($course_id,$classId,$name,$name,$maxScoreTest/4,$maxWeight)",$db);
          //UPDATE EXIST STUDENT TEST
          if(count($studentTests))
          {
            foreach ($studentTests as $key => $value) {
              if($dataCheck[$value["test_id"]] == $test["type"]. " total" . "__" . $test["type"] . " total")
              {
                $studentTestId = $value["id"];
                SQL_Query("UPDATE student_test SET class_id = $classId , test_id = $id WHERE id = $studentTestId",$db);
              }
            }
          }
        }
        break;
    }
  }

  // ADD COURSE TOTAL COLUMN
  switch ($programType) {
    case 'sat':
      $testName = "Final SAT score";
      break;
    
    default:
      $testName = "course total";
      break;
  }
  if($maxScoreTotal == 0) $maxScoreTotal = 100;
  $idTotal = SQL_Query("INSERT INTO course_test(course_id,test_type,name,max_score,weight) VALUE($course_id,'course_total','$testName',$maxScoreTotal,100)",$db);
  foreach ($studentTests as $key => $value) {
    if($dataCheck[$value["test_id"]] == 'course_total__'.$testName)
    {
      $studentTestId = $value["id"];
      SQL_Query("UPDATE student_test SET test_id = $idTotal WHERE id = $studentTestId",$db);
    }
  }
  SQL_Transaction_Commit($db);
  } catch (\Throwable $th) 
  {
    SQL_Transaction_Rollback($db);
    Users_Write_Log("Courses_Test_Create($course_id)",["message" => $th->getMessage(),"trace" => $th->getTrace()]);
  }
  SQL_Close($db);
}
?>