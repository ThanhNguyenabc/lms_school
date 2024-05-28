async function Grades_List_Display(teacher_id, managed = false)
{
 // IF SPECIFIC TEACHER AND TEACHER NOT CURRENT USER, LOAD IT
 if(teacher_id && teacher_id != User_Id()) 
 { 
  var user = await Core_Api("User_Read", {user_id:teacher_id, options:{fields:"id,firstname,lastname"}});
 }
 else
 {
  var user = await Core_User();
 }
 
 
 // CREATE DISPLAY
 var display = UI_Element_Create("grades/list-display");
 Core_State_Set("grades", "list-display", display);
 
 
 // IF LIST OF MANAGED TEACHERS, SHOW SELECTOR
 if(managed)
 {  
  // IF IT'S NOT A LIST BUT JUST A "TRUE" FLAG, BUILD THE LIST BY CALLING API
  if(typeof managed != "object")
  {
   var managed = await Core_Api("Users_List_ByManager", {manager_id:user["id"], fields:"id,firstname,lastname"});
  }

  var selector = UI_Element_Find(display, "selector");
  var select   = UI_Element_Find(selector, "teachers");
  
  // ADD MAIN USER TO THE LIST
  var name = [user["firstname"] || "", user["lastname"] || ""].join(" ").trim();
  Document_Select_AddOption(select, name, user["id"]);
  
  for(var teacher of managed)
  {
   var name = [teacher["firstname"] || "", teacher["lastname"] || ""].join(" ").trim();
   Document_Select_AddOption(select, name, teacher["id"]);
  }
  
  select.value = user["id"];
  
  select.onchange = 
  function()
  {
   Grades_List_Teacher(select.value);
  }
  
  selector.style.display = "flex";
 }
 
 await Grades_List_Teacher(user["id"]);


 return display;
}



async function Grades_List_Teacher(teacher_id)
{
 var display = Core_State_Get("grades", "list-display");
 // HIDE DETAIL VIEW
 UI_Element_Find(display, "detail").style.visibility = "hidden";


 // GET CLASS ASSESSMENT DATA
 var period     = parseInt(User_Config("admin-days", -30));
 var date_from  = Date_Portion(Date_Add_Days(Date_Now(), period), "no-seconds");
 var date_to    = Date_Portion(Date_Now(), "no-seconds");
 var data       = await Core_Api("Course_List_ByTeacher", {teacher_id, date_from, date_to});

 var gradesConfig = Core_Config("grades");
 var dataCustom = [];
 // CUSTOM DATA
 for(var item of data) 
 {
  var gradesLeson = Safe_Get(gradesConfig,[item["program"],item["level"]],"");
  if(gradesLeson != "")
  {
    gradesLeson = gradesLeson.split(";");
    var classes = item["classes"];
    gradesLeson.forEach(lesson => {
      lesson = lesson.split("__"); 
      var classFind = classes.find((element) =>  element["lesson_id"] == lesson[0]);
      if(typeof classFind != "undefined")
      {
        classFind["lesson"] = classFind["lesson_id"];
        classFind["type_name"] = lesson[1];
        classFind["course_id"] = item["id"];
        classFind["course_name"] = item["name"];
        classFind["program"] = item["program"];
        classFind["level"] = item["level"];
        if(classFind["date_start"] >= date_from && classFind["date_start"] <= date_to)
        {
          dataCustom.push(classFind);
        } 
      }
    });
  } 
 }

 for(var item of dataCustom) 
 {
  item["period"] = Date_Format_Period(Date_Now(), item["date_start"], {locale:UI_Language_Current(true), conversational:true});
 }
 
 // SORT BY DATE AND CLASS ID
 dataCustom.sort(
 function(a, b)
 {
  if(a["date_start"] < b["date_start"]) return 1;
  else
  if(a["date_start"] > b["date_start"]) return -1;
  else
  {
   if(a["id"] < b["id"]) return 1;
   else
   if(a["id"] > b["id"]) return -1;
   else   
   return 0;
  }
 });
 
 
 // ASSEMBLE DISPLAY
 var list = UI_List_Items(dataCustom, ["style-outlined-accented", "outline-inner"], Grades_List_Seat, {style:"vertical", overflow:true, sections:"period"},
 
 // ITEMS
 function(item)
 {  
  var locale      = UI_Language_Current(true);
  var info        = {};
  info["date"]    = Date_Format(item["date_start"], locale, "date-shortmonth-weekday-noyear") + ", " + Date_Format(item["date_start"], locale, "time-only");
  info["course"]  = item["course_name"];
  info["type"]    = item["type_name"];
 
  var element = UI_Element_Create("grades/list-item", info);  
  Document_Element_SetObject(element, "item", item);
  
  return element; 
 },
 
 // SECTIONS
 function(section, item)
 {
  var element = UI_Element_Create("assessment/list-section", {period:section.toUpperCase()});  
  return element;
 });
 


 // DISPLAY LIST 
 var container       = UI_Element_Find(display, "list");
 container.innerHTML = "";
 container.appendChild(list);
}




async function Grades_List_Seat(element)
{
 var item            = Document_Element_GetObject(element, "item");
 Core_State_Set("grades", "current-selected", item);

 var display         = Core_State_Get("grades", "list-display");
  
 var page            = await Grades_Assess_Display(item);
 Core_State_Set("grades", "page", page);

 var container       = UI_Element_Find(display, "detail-table");
 container.innerHTML = "";
 container.appendChild(page);

 var headerRowSub = UI_Element_Find(page,"header-sub");
 headerRowSub.style.top = headerRowSub.previousSibling.offsetHeight + 1;
 
 container.parentElement.style.visibility = "visible";
}




async function Grades_Assess_Display(item){

  var data = await Core_Api("Grades_List_ByCourse",{course_id:item["course_id"]});

  var dataCustomStudent = {};
  for (const key in data["students"]) {
    const studentTest = data["students"][key];
    if(typeof dataCustomStudent[studentTest["test_id"]] == "undefined") dataCustomStudent[studentTest["test_id"]] = {};
    dataCustomStudent[studentTest["test_id"]][studentTest["student_id"]] = studentTest;
  }

  var dataCustomTest = {};
  for (const key in data["course_test"]) {
    var test = data["course_test"][key];
    if(typeof dataCustomTest[test["test_type"]] == "undefined") dataCustomTest[test["test_type"]] = {};
    dataCustomTest[test["test_type"]][test["name"]] = {
      testId : test["id"],
      testType : test["test_type"],
      testName : test["name"],
      classId: test["class_id"],
      maxScore: test["max_score"],
      weight: test["weight"],
      students: dataCustomStudent[test["id"]]
    }
  }

  var programType = Grades_Program_Type(item["program"]);

  const table = UI_Table("standard", { fixed: true });
  table.classList.add("border-rounded");

  const headerRow = UI_Table_Row(table);
  headerRow.style.cssText = "position: sticky;z-index: 11;";
  headerRow.style.top = 0;

  const cellStudentHeader = UI_Table_Cell(headerRow, { type: "header" });
  cellStudentHeader.style.cssText = "position: sticky; left: 0; z-index: 1;border-top-left-radius: 6px; min-width:200px;"
  cellStudentHeader.innerHTML = UI_Language_String("grades/module","student name");
  cellStudentHeader.rowSpan = 2;

  const headerRowSub = UI_Table_Row(table);
  headerRowSub.dataset.uid = "header-sub";
  headerRowSub.style.cssText = "position: sticky;z-index: 10;";
  headerRowSub.style.top = 0;

  for (const testType in dataCustomTest) {
    const tests= dataCustomTest[testType];
    var type = testType;
    if(testType != "course_total" && !testType.toLowerCase().includes("total"))
    {
      let typeName;
      let typeNameNumber = "";
      if(Boolean(type.match(/\d/)))
      {
        let index = type.match(/\d/)["index"];
        typeName = type.slice(0,index);
        typeName = UI_Language_String("grades/module",(typeName.trim()).toLowerCase());
        typeNameNumber = " " + (type.slice(index)).trim();
      }
      else{
        typeName = UI_Language_String("grades/module",(type.trim()).toLowerCase());
      }
      const cellHeader = UI_Table_Cell(headerRow, { type: "header" });
      cellHeader.innerHTML = typeName + typeNameNumber;
      cellHeader.colSpan = Object.keys(tests).length;
    
      for (const testName in tests) {
        const test = tests[testName];
        let name = testName;
        let nameParse ;
        let nameNumber = "";
        if(Boolean(name.match(/\d/)) && programType != "sat")
        {
          let index = name.match(/\d/)["index"];
          nameParse = name.slice(0,index);
          nameParse = UI_Language_String("grades/module",(nameParse.trim()).toLowerCase());
          nameNumber = " " + (name.slice(index)).trim();
        }
        else{
          nameParse = UI_Language_String("grades/module",(name.trim()).toLowerCase());
        }
        const cellSub = UI_Table_Cell(headerRowSub, { type: "header" });
        cellSub.innerHTML = nameParse + nameNumber + Grades_Header_Tail(item,test["maxScore"],test["weight"]) ;
        cellSub.style.minWidth = "150px";
      }
    }
    else{
      const cellHeaderTotal = UI_Table_Cell(headerRow, { type: "header" });
      cellHeaderTotal.style.cssText = "border-top-right-radius: 6px;"
      cellHeaderTotal.innerHTML = UI_Language_String("grades/module", Object.keys(tests)[0].trim().toLowerCase())  + Grades_Header_Tail(item,Object.values(tests)[0]["maxScore"],test["weight"]);
      cellHeaderTotal.rowSpan   = 2;
      cellHeaderTotal.style.minWidth = "150px";
    }
  }

  var gradesConfig = Core_Config("grades");
  var gradesLeson = Safe_Get(gradesConfig,[item["program"],item["level"]],"");
  var seatsCheck = [];

  if(gradesLeson != "")
  {
    gradesLeson = gradesLeson.split(";");
    var lessons = []
    gradesLeson.forEach(lesson => {
      lesson = lesson.split("__");;
      lessons.push(lesson[0]);
    });

    var seats = await Core_Api("Class_Seats_ListByLessonName",{course_id:item["course_id"],lessons:lessons});
    seats.forEach(seat => {
      if(typeof seatsCheck[seat["class_id"]] == "undefined") seatsCheck[seat["class_id"]] = {}
      seatsCheck[seat["class_id"]][seat["student_id"]] = seat;
    });
  }
  
  var students = await Core_Api("Users_List_ByCourse",{course_id:item["course_id"]});

  students.forEach(user => {
    const row = UI_Table_Row(table);

    var cellStudentName =  UI_Table_Cell(row);
    cellStudentName.innerHTML = (user["lastname"]?? "")  + " " + (user["midname"] ?? "") + " " + (user["firstname"]?? "") ;
    cellStudentName.style.cssText = "position: sticky; left: 0; z-index: 1";

    for (const testType in dataCustomTest) {
      const tests= dataCustomTest[testType];
      for (const testName in tests) {
        const test = tests[testName];

        var cell =  UI_Table_Cell(row);
        if((typeof seatsCheck[test["classId"]] != "undefined" && typeof seatsCheck[test["classId"]][user["id"]] != "undefined") || testType == "course_total")
        {
          var input = UI_Element_Create("grades/grades-input");
          input.dataset.uid =  user["id"] + "__" + testName;
          input.onchange = Grades_Assess_Store
          input.min = 0;
          input.max = test["maxScore"];

          if(typeof test["students"] != "undefined" && typeof test["students"][user["id"]] != "undefined")
          {
            input.value = test["students"][user["id"]]["score"];
            Document_Element_SetObject(input, "studentTestId", test["students"][user["id"]]["id"]);
            Document_Element_SetObject(input, "defaultValue", test["students"][user["id"]]["score"]);
          } 
          // || parseInt(item["date_start"]) < parseInt(seatsCheck[test["classId"]][user["id"]]["date_start"]) 
          if(testType == "course_total" || testName.toLowerCase().includes("project") || testName.toLowerCase().includes("homework"))
          {
            //cell.classList.add("style-disabled");
            //input.classList.add("style-disabled");
            input.readOnly = true;
            input.style.cursor = "no-drop";
            input.style.fontWeight = "bolder";
          }

          switch (programType) {
            case "sat":
              var testTypeLower = testType.toLowerCase();
              if(testTypeLower.includes("best scores") || testTypeLower.includes("score improvement") || testName.toLowerCase().includes("total"))
              {
                //cell.classList.add("style-disabled");
                //input.classList.add("style-disabled");
                input.readOnly = true;
                input.style.cursor = "no-drop";
                input.style.fontWeight = "bolder";
              }
              break;
          
            case "ielts":
              if(testName.toLowerCase().includes("total"))
              {
                //cell.classList.add("style-disabled");
                //input.classList.add("style-disabled");
                input.readOnly = true;
                input.style.cursor = "no-drop";
                input.style.fontWeight = "bolder";
              }
              break;
            default:
              break;
          }

          // CHECK ROLE ALLOW EDIT
          var check =  User_Config("admin-edit-allow", "false")
          if(check == "false")
          {
            cell.classList.add("style-disabled");
            input.classList.add("style-disabled");
          } 

          Document_Element_SetObject(input, "courseId", item["course_id"]);
          Document_Element_SetObject(input, "classId", test["classId"]);
          Document_Element_SetObject(input, "testId", test["testId"]);
          Document_Element_SetObject(input, "testType", test["testType"]);
          Document_Element_SetObject(input, "testName", test["testName"]);
          Document_Element_SetObject(input, "maxScore", test["maxScore"]);
          Document_Element_SetObject(input, "percentage", test["weight"]);
          Document_Element_SetObject(input, "student", user["id"]);

          cell.appendChild(input);
        }
      }
    }
  });
  
  return table;
}


async function Grades_Assess_Store(e)
{
  var input = e.target;
  console.log(input);
  var maxScore = Document_Element_GetObject(input, "maxScore");
  var courseId = Document_Element_GetObject(input, "courseId");
  var classId = Document_Element_GetObject(input, "classId");
  var testId = Document_Element_GetObject(input, "testId");
  var weight = Document_Element_GetObject(input, "percentage");
  var studentId = Document_Element_GetObject(input, "student");
  var testType = Document_Element_GetObject(input, "testType");
  var defaultValue = Document_Element_GetObject(input, "defaultValue",null);
  var value = input.value;
  var selectedItem = Core_State_Get("grades", "current-selected");
  
  if(parseFloat(value) <= parseFloat(maxScore)) 
  {
    var data = await Core_Api("Grades_Student_Test", {course_id:courseId, class_id:classId, test_id:testId, student_id:studentId, score:value, max_score:maxScore, weight:weight}); 
    var page = Core_State_Get("grades", "page");
    
    switch (Grades_Program_Type(selectedItem["program"])) {
      case "sat":
        var dataUid = studentId + "__Final SAT score";

        var bestEnglish = UI_Element_Find(page,studentId + "__Best Verbal (English)");
        bestEnglish.value = data["best_english"];

        var bestMath = UI_Element_Find(page,studentId + "__Best Maths");
        bestMath.value = data["best_math"];

        var improvementMath = UI_Element_Find(page,studentId + "__Maths Improvement");
        improvementMath.value = data["improvement_math"];

        var improvementEnglish = UI_Element_Find(page,studentId + "__Verbal (English) Improvement");
        improvementEnglish.value = data["improvement_english"];

        var improvementTotal = UI_Element_Find(page,studentId + "__Score Improvement total");
        improvementTotal.value = data["improvement_total"];

        if(data["update_total"])
        {
          var updateTotal = UI_Element_Find(page,studentId + "__" + testType + " total");
          updateTotal.value = data["update_total"];
        }
        break;

      case "ielts":
        var dataUid = studentId + "__course total";
        if(data["update_total"])
        {
          var bestEnglish = UI_Element_Find(page,studentId + "__" + testType + " total");
          bestEnglish.value = data["update_total"];
        }
        break;

      default:
        var dataUid = studentId + "__course total";
        break;
    }
    var totalInput = UI_Element_Find(page,dataUid);
    totalInput.value = data["total_score"];
  }
  else{
    input.value = defaultValue;
  }
  
}


function Grades_Header_Tail(item,maxScore,weight)
{
  var programType = Grades_Program_Type(item["program"]);
  switch (programType) {
    case "sat":
      return "<i> (" + maxScore  + ")</i>"
    case "ielts":
      if(weight) return "<i> (" + maxScore  + "/" + weight + "%)</i>";
      else return "<i> (" + maxScore  + ")</i>"
    default:
      return "<i> (" + maxScore  + "/" + weight + "%)</i>"
  }
}


function Grades_Program_Type(program)
{
  var programs = Core_Config("programs");
  var programType = programs[program]["type"];
  return programType;
}