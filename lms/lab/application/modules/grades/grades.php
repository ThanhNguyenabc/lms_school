<?php

function Grades_Student_Test($course_id, $class_id, $test_id, $student_id, $score, $max_score, $weight, $teacher_id = -1)
{
  $db = Core_Database_Open();
 
  if($teacher_id == -1) $teacher_id = $_SESSION["user"]["id"];

  $now = Date_Now();

  //CHECK EXIST SCORE
  $test = SQL_Query("SELECT id,score FROM student_test WHERE course_id = $course_id AND class_id = $class_id AND test_id = $test_id AND student_id = $student_id",$db);
  if(count($test))
  {
    // UPDATE
    $id = $test[0]["id"];
    SQL_Query("UPDATE student_test SET score = $score, modify_by = $teacher_id, modify_time = $now WHERE id = $id",$db);
  }
  else SQL_Query("INSERT INTO student_test(course_id,class_id,test_id,student_id,score,modify_by,modify_time) VALUE($course_id,$class_id,$test_id,$student_id,$score,$teacher_id,$now)",$db);
  
  // CALCULATE TOTAL SCORE FOR EACH PROGRAM TYPE
  $courses = SQL_Query("SELECT * FROM courses WHERE id = $course_id",$db);
  $course = $courses[0];
  $program = $course["program"];

  $programs = Ini_File_Read("partners/default/programs.cfg");
  $programType = $programs[$program]["type"];

  // GET ALL SCORE OF STUDENT
  $studentTests = SQL_Query("SELECT course_test.* ,student_test.id as student_test_id, student_test.score FROM course_test LEFT JOIN student_test ON student_test.test_id = course_test.id AND student_test.student_id = $student_id WHERE course_test.course_id = $course_id",$db); 
  $total = 0;
  $update = false;
  $updateId = -1;
  $createId = -1;
 
  switch ($programType) {
    case 'sat':
      $updateType = "";
      $updateName = "";

      $testBestScore = ["Maths" => 0, "Verbal (English)" => 0];
      $testBest = [];

      $improvementScore = ["Maths" => 0, "Verbal (English)" => 0];

      foreach ($studentTests as $key => $test) {
        $data[$test["test_type"]][$test["name"]] = $test;
        if($test["id"] == $test_id && $test["test_type"] != "Homework") { $updateType = $test["test_type"]; $updateName = $test["name"];}
        if((int)$test["score"] > (int)$testBestScore[$test["name"]])
        {
          $testBest[$test["name"]] = $test;
          $testBestScore[$test["name"]] = $test["score"];
        }
      }
      
      if($updateType != "")
      {
        $totalUpdate = (int)$data[$updateType]["Maths"]["score"] + (int)$data[$updateType]["Verbal (English)"]["score"];

        // UPDATE TOTAL SCORE
        if($data[$updateType. " total"][$updateType. " total"]["student_test_id"])
        {
          SQL_Query("UPDATE student_test SET score = $totalUpdate, modify_by = $teacher_id, modify_time = $now WHERE id = ".$data[$updateType. " total"][$updateType. " total"]["student_test_id"],$db);
        }
        else
        {
          $testId = $data[$updateType. " total"][$updateType. " total"]["id"];
          SQL_Query("INSERT INTO student_test(course_id,test_id,student_id,score,modify_by,modify_time) VALUE($course_id,$testId,$student_id,$totalUpdate,$teacher_id,$now)",$db);
        }
        
        $total = (int)$testBestScore["Maths"] + (int)$testBestScore["Verbal (English)"];

        // UPDATE BEST SCORE
        if($data["Best Scores"]["Best " . $updateName]["score"] != $testBestScore[$updateName])
        {
          $bestScore = $testBestScore[$updateName];
          
          if($data["Best Scores"]["Best " .$updateName]["student_test_id"])
          {
            SQL_Query("UPDATE student_test SET score = $bestScore, modify_by = $teacher_id, modify_time = $now WHERE id = ".$data["Best Scores"]["Best " .$updateName]["student_test_id"],$db);
          }
          else
          {
            $testId = $data["Best Scores"]["Best " .$updateName]["id"];
            SQL_Query("INSERT INTO student_test(course_id,test_id,student_id,score,modify_by,modify_time) VALUE($course_id,$testId,$student_id,$bestScore,$teacher_id,$now)",$db);    
          }

          // SET CREATEID OR UPDATEID
          if($data["course_total"]["Final SAT score"]["student_test_id"])
          {
            $update = true;
            $updateId = $data["course_total"]["Final SAT score"]["student_test_id"];
          }
          else $createId = $data["course_total"]["Final SAT score"]["id"];

        }

        // UPDATE IMPROVEMENT SCORE
        $improvementScores[$updateName] = (int)$testBestScore[$updateName] - (int)$data["Diagnostic Test"][$updateName]["score"];

        if($data["Score Improvement"][$updateName . " Improvement"]["score"] < $improvementScores[$updateName] || !$data["Score Improvement"][$updateName . " Improvement"]["score"])
        {
          $improvementScore = $improvementScores[$updateName];
          if($data["Score Improvement"][$updateName . " Improvement"]["student_test_id"])
          {
            SQL_Query("UPDATE student_test SET score = $improvementScore, modify_by = $teacher_id, modify_time = $now WHERE id = ".$data["Score Improvement"][$updateName . " Improvement"]["student_test_id"],$db);
          }
          else
          {
            $testId = $data["Score Improvement"][$updateName . " Improvement"]["id"];
            SQL_Query("INSERT INTO student_test(course_id,test_id,student_id,score,modify_by,modify_time) VALUE($course_id,$testId,$student_id,$improvementScore,$teacher_id,$now)",$db);    
          }

          //UPDATE TOTAL IMPROVEMENT
          $totalImpovement = (int)$testBestScore["Maths"] - (int)$data["Diagnostic Test"]["Maths"]["score"] + (int)$testBestScore["Verbal (English)"] - (int)$data["Diagnostic Test"]["Verbal (English)"]["score"];
          if($data["Score Improvement"]["Score Improvement total"]["student_test_id"])
          {
            SQL_Query("UPDATE student_test SET score = $totalImpovement, modify_by = $teacher_id, modify_time = $now WHERE id = ".$data["Score Improvement"]["Score Improvement total"]["student_test_id"],$db);
          }
          else
          {
            $testId = $data["Score Improvement"]["Score Improvement total"]["id"];
            SQL_Query("INSERT INTO student_test(course_id,test_id,student_id,score,modify_by,modify_time) VALUE($course_id,$testId,$student_id,$totalImpovement,$teacher_id,$now)",$db);    
          }
        }
      }
      $result = [
        "test_id" => $test_id, 
        "student_id" => $student_id, 
        "score" => $score, 
        "teacher_id" => $teacher_id, 
        "total_score" => $total,
        "best_math" => (int)$testBestScore["Maths"],
        "best_english" => (int)$testBestScore["Verbal (English)"],
        "improvement_math" => (int)$testBestScore["Maths"] - (int)$data["Diagnostic Test"]["Maths"]["score"],
        "improvement_english" => (int)$testBestScore["Verbal (English)"] - (int)$data["Diagnostic Test"]["Verbal (English)"]["score"],
        "improvement_total" => (int)$testBestScore["Maths"] - (int)$data["Diagnostic Test"]["Maths"]["score"] + (int)$testBestScore["Verbal (English)"] - (int)$data["Diagnostic Test"]["Verbal (English)"]["score"],
        "update_total" => $totalUpdate
      ];
      break;

    case "ielts":
      $updateType = "";
      $updateName = "";

      foreach ($studentTests as $key => $test) {
        $data[$test["test_type"]][$test["name"]] = $test;
        if($test["id"] == $test_id && ($test["test_type"] ==  "eomt" || $test["test_type"] ==  "mmt") ) 
        {
           $updateType = $test["test_type"]; 
           $updateName = $test["name"];
        }
      }

      if($updateType != "")
      {
        // UPDATE TOTAL
        $updateTotal = 0;
        foreach ($data[$updateType] as $key => $value) {
          if($value["score"]) $updateTotal += (double)$value["score"];
        }
        $updateTotal = round($updateTotal/4,2);
        $updateTotal = Grades_Round($updateTotal);

        if(isset($data["$updateType total"]["$updateType total"]))
        {
          if($data["$updateType total"]["$updateType total"]["student_test_id"])
          {
            SQL_Query("UPDATE student_test SET score = $updateTotal, modify_by = $teacher_id, modify_time = $now WHERE id = ".$data["$updateType total"]["$updateType total"]["student_test_id"],$db);
          }
          else
          {
            $testTotalId = $data["$updateType total"]["$updateType total"]["id"];
            SQL_Query("INSERT INTO student_test(course_id,test_id,student_id,score,modify_by,modify_time) VALUE($course_id,$testTotalId,$student_id,$updateTotal,$teacher_id,$now)",$db);
          }
        }

        // SET UPDATEID OR CREATEID
        if($data["course_total"]["course total"]["student_test_id"])
        {
          $update  = true;
          $updateId = $data["course_total"]["course total"]["student_test_id"];
          foreach ($data["eomt"] as $key => $value) {
            if($value["score"]) $total += (double)$value["score"];
          }
          $total = round($total/4,2);
          $total = Grades_Round($total);
        }
        else $createId = $data["course_total"]["course total"]["id"];
      }
      $result = ["test_id" => $test_id, "student_id" => $student_id, "score" => $score, "teacher_id" => $teacher_id, "total_score" => $total, "update_total" => $updateTotal];
      break;
    
    default:
      // HYBRID
      foreach ($studentTests as $key => $test) {
        if($test["score"])
        {
          if($test["test_type"] == "course_total")
          {
            $update = true;
            $updateId = $test["student_test_id"];
          } 
          else $total += $test["score"] * $test["weight"] / $test["max_score"];
        }
        else if($test["test_type"] = "course_total") $createId = $test["id"];
      }
      $result = ["test_id" => $test_id, "student_id" => $student_id, "score" => $score, "teacher_id" => $teacher_id, "total_score" => $total];
      break;
  }

  $total = round($total,2);
  //CREATE OR UPDATE COURSE TOTAL
  if($update)
  {
     SQL_Query("UPDATE student_test SET score = $total, modify_by = $teacher_id, modify_time = $now WHERE id = $updateId",$db);
  }
  else
  {
    if($createId > 0) SQL_Query("INSERT INTO student_test(course_id,test_id,student_id,score,modify_by,modify_time) VALUE($course_id,$createId,$student_id,$total,$teacher_id,$now)",$db); 
  }

  SQL_Close($db);

  return $result;
}


function Grades_List_ByCourse($course_id)
{
  $db = Core_Database_Open();

  $courseTest = SQL_Query("SELECT * FROM course_test WHERE course_id = $course_id",$db);
  $studentTest = SQL_Query("SELECT * FROM student_test WHERE course_id = $course_id",$db);

  SQL_Close($db);

  return ["course_test" => $courseTest, "students" => $studentTest];
}


function Grades_Homework_Projects_Update($table,$id,$field)
{
  $db = Core_Database_Open();

  switch ($table) {
    case 'classes_seats':
      $query = "SELECT class_id, course_id, student_id, assessment FROM $table WHERE id = $id";
      $rows  = SQL_Query($query, $db);
      $seat  = $rows[0];
      
      $courseId = $seat["course_id"];
      $studentId = $seat["student_id"];
      $assessment = json_decode($seat["assessment"], true);

      $courseTests = SQL_Query("SELECT id, class_id, max_score, weight FROM course_test WHERE course_id = $courseId AND name = 'homework'",$db);
      
      if(count($courseTests))
      {
        $testId = $courseTests[0]["id"];
        $maxScore = $courseTests[0]["max_score"];
        $weight = $courseTests[0]["weight"];
        $classId = $courseTests[0]["class_id"];
        $studentSeats = SQL_Query("SELECT assessment FROM $table WHERE student_id = $studentId AND course_id = $courseId",$db);
        $hwScore = 0;
        $hwIndex = 0;
        foreach ($studentSeats as $key => $seat) {
          if($seat["assessment"] != null) 
          {
            $data = json_decode($seat["assessment"], true);
            if(isset($data[$field]))
            {
              $hwScore += (int) $data[$field];
              $hwIndex += 1;
            }
          }
        }
        SQL_Close($db);
        
        if($hwIndex > 0)
        {
          $averageHW  = round($hwScore/$hwIndex);
          Grades_Student_Test($courseId,$classId,$testId,$studentId,$averageHW,$maxScore,$weight);
        }
      }
      
      break;
    
    case "projects_students":
      $query = "SELECT ps.student_id, p.class_id, ps.assessment FROM $table ps JOIN projects p ON p.id = ps.project_id WHERE ps.id = $id";
      $rows = SQL_Query($query,$db);
      $studentProject = $rows[0];

      $studentId = $studentProject["student_id"];
      $classId = $studentProject["class_id"];
      $assessment = json_decode($studentProject["assessment"], true);

      $courseTests = SQL_Query("SELECT id, course_id, max_score, weight FROM course_test WHERE class_id = $classId ",$db);
      if(count($courseTests))
      {
        $testId = $courseTests[0]["id"];
        $maxScore = $courseTests[0]["max_score"];
        $weight = $courseTests[0]["weight"];
        $courseId = $courseTests[0]["course_id"];

        $config = parse_ini_file(__DIR__."/grades-project.dat",true);
        $total = 0;

        foreach ($assessment as $key => $value) {
          $infoSkill = Ini_File_Read("content/skills/".$key."/info.dat");
          if($infoSkill)
          {
            if(isset($infoSkill["lv ". $value]["score"])) $score = $infoSkill["lv ". $value]["score"];
            else $score = $value;

            $weightSkill = $config[$key]["weight"];
            $assessmentScore = $config[$key]["assessment-score"];

            $total += (int)$score / (int)$assessmentScore * (int)$weightSkill;
          }
        }
       
        if($total) Grades_Student_Test($courseId,$classId,$testId,$studentId,$total,$maxScore,$weight);
      }
      break;
  }

  SQL_Close($db);
}

function Grades_Round($grade)
{
  $check = $grade * 100 % 100;
  if($check >= 75) return ceil($grade);
  else if($check >= 25) return floor($grade) + 0.5;
  else return floor($grade);
}
?>