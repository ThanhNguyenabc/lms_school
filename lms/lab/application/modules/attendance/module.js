// -----------------------------------------------------------------------------------------------//Attendance_Courses
//                                                                                                //
//                                   A T T E N D A N C E                                          //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//

async function Attendance_OnLoad(module, data)
{
  var page = await Attendance_Courses_Display();
  
  var module = UI_Element_Find(module, "module-page"); 
  module.innerHTML = "";
  module.appendChild(page);
}


async function Attendance_OnShow(module, data)
{
}


async function Attendance_OnUnload()
{
}


// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                          M O R E                                               //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


async function Attendance_Courses_Display(options = {})
{
 var display = UI_Element_Create("attendance/display");
 Core_State_Set("admin", ["attendance","display"], display);
 
 // ALL CENTERS
 var centers = Array_Organize_ByField(Centers_Available(), "id");
 
 // STORED SEARCH, IF ANY
 var search  = Core_State_Get("admin",["attendance", "search"], {});
 
 // DETERMINE AND STORE CENTERS AVAILABLE TO ME
 Core_State_Set("admin", ["attendance","centers"], centers);
  
  
 // CENTERS SELECT
 var select = UI_Element_Find(display, "search-centers");
 Document_Select_OptionsFromObjects(select, centers, "name", false);
 
 if(typeof search["centers"] != "undefined") Document_Select_SelectByValue(select, search["centers"]);
 

 
 // STATUS
 var select = UI_Element_Find(display, "search-status"); 
 Document_Select_AddOption(select, UI_Language_String("courses", "search any"), "");
 Document_Select_AddOption(select, "---", "").disabled = true;
 UI_Select_FromDatapage(select, "courses/course-status");
 
 if(typeof search["status"] != "undefined") select.value = search["status"];
 
 
 
 
 // ALL PROGRAMS
 var programs = Core_Config("programs");
 
 // PROGRAM SELECT
 var select = UI_Element_Find(display, "search-program");
 Document_Select_AddOption(select, UI_Language_String("courses", "search any"), "");
 Document_Select_AddOption(select, "---", "").disabled = true;
 Document_Select_OptionsFromObjects(select, programs, "name", false);
 
 if(typeof search["program"] != "undefined") select.value = search["program"];
 
 select.onchange = 
 function(event)
 {
  var display   = Core_State_Get("admin",["attendance", "display"]);
  var element  = event.currentTarget;
  var program  = element.value;
  
  // ALL LEVELS
  var programs = Core_Config("programs", "");
  var levels   = Safe_Get(programs, [program, "levels"], "").split(",");
  
  // LEVEL SELECT
  var select = UI_Element_Find(display, "search-level");
  Document_Select_Clear(select);
  Document_Select_AddOption(select, UI_Language_String("courses", "search any"), "");
  Document_Select_AddOption(select, "---", false).disabled = true;
  Document_Select_OptionsFromValues(select, levels, levels);
  
  //if(typeof search["level"] != "undefined") select.value = search["level"];
 }
 
 
 
 // DATE MODES
 var select   = UI_Element_Find(display, "search-datemode");
 UI_Select_FromDatapage(select, "courses/search-date-modes");
 select.value = "ends-after";
 
 select.onchange = 
 function(event)
 {
  var element = event.currentTarget;
  var display = Core_State_Get("admin", ["attendance","display"]);
  
  var input   = UI_Element_Find(display, "search-datefrom");
  if(element.value == "ongoing")
  {
   input.style.display = "none";
  }
  else 
  {
   input.style.display = "flex";
  }	  
 }
 
 
 // DATE 
 var input = UI_Element_Find(display, "search-datefrom");
 if(typeof search["date"] != "undefined") input.value = Date_To_Input(search["date"]); else input.value = Date_To_Input(Date_Now());
 
 
 // TIMEFROM TIMETO
 var select_from = UI_Element_Find(display, "search-timefrom");
 var select_to   = UI_Element_Find(display, "search-timeto");
 var time_start  = Time_To_Minutes(Core_Config(["operations", "timetable", "time-opening"], "01:00"));
 var time_end    = Time_To_Minutes(Core_Config(["operations", "timetable", "time-closing"], "24:00"));
 var time        = time_start;
 while(time <= time_end)
 {
  var text = Time_From_Minutes(time);
  
  Document_Select_AddOption(select_from, text, time);
  Document_Select_AddOption(select_to,   text, time);
  
  time = time + 30;
 }
 
 select_from.value    = time_start;
 select_to.value      = time_end;
 
	
 // DAYS 
 var select = UI_Element_Find(display, "search-days");
 var locale = UI_Language_Current(true);
 for(var day = 1; day<=7; day++)
 {
  var text = Date_Weekday_Name(day, "long", locale);
  Document_Select_AddOption(select, text, day);
 }
 

 // BUTTON SEARCH
 UI_Element_Find(display, "button-course-search").onclick = Attendance_Courses_Search;

 return display;
}




async function Attendance_Display(element)
{
  var course_id = Document_Element_GetData(element, "course_id", {});

  var course = Document_Element_GetObject(element,"course");
  var program = course.program;
  var level = course.level;
 
  var programs = Core_Config("programs");
  var lessons = Safe_Get(programs,[program,level],"");
  lessons = lessons.split(",");
  var lessonTimes = {};

  var seats = await Core_Api("Class_Seats_ListByLessonName",{course_id:course_id,lessons:lessons});
  var attendances = {};
  for (const key in seats) {
    var seat = seats[key];
    if(typeof attendances[seat.student_id] == "undefined") attendances[seat.student_id] = {};
    attendances[seat.student_id][seat.lesson_id] = seat
    lessonTimes[seat.lesson_id] = seat.date_start 
  }

  var studentIds = Object.keys(attendances);
  var students = await Core_Api("Users_Read",{ids:studentIds, fields:"firstname,lastname,midname,nickname",options:{array:false}})

  const table = UI_Table("standard", { fixed: true });
  table.classList.add("border-rounded");
  table.style.backgroundColor = "var(--color-medium)";

  const headerRow = UI_Table_Row(table);
  headerRow.style.cssText = "position: sticky;z-index: 11;";
  headerRow.style.top = 0;

  const cellStudentHeader = UI_Table_Cell(headerRow, { type: "header" });
  cellStudentHeader.style.cssText = "position: sticky; left: 0; z-index: 1;border-top-left-radius: 6px; min-width:200px;"
  cellStudentHeader.innerHTML = UI_Language_String("grades/module","student name");
  cellStudentHeader.rowSpan = 1;

  // ATTENDANCE
  var select = UI_Element_Create("attendance/attendance-select");

  lessons.forEach(lessonId => {
    var cellHeader = UI_Table_Cell(headerRow,{type:"header"});
    cellHeader.innerHTML = lessonId + "<br/>" +  "(" + Date_Get(lessonTimes[lessonId],["year","month","day"]) + ")";
  });

  for (const studentId in attendances) {
      var studentName = (students[studentId]["lastname"] || "") + " " + (students[studentId]["midname"] || "") + " " + (students[studentId]["firstname"] || "") + ( students[studentId]["nickname"] != null ?  "(" + (students[studentId]["nickname"]) + ")" : "" );
      var attendancesLessons = attendances[studentId];
      var row = UI_Table_Row(table);
      var cellStudentName = UI_Table_Cell(row);
      cellStudentName.innerHTML = studentName;
      cellStudentName.style.cssText = "position: sticky; left: 0; z-index: 1;";
      for (const lessonId in attendancesLessons) {
          const seat = attendancesLessons[lessonId];
          var selectCell = select.cloneNode(true);
          var icon = UI_Element_Find(selectCell,seat.attendance);
          if(typeof icon != "undefined") icon.style.display = "block";

          var cell = UI_Table_Cell(row);
          cell.appendChild(selectCell);
      }
  }

  var container       = UI_Element_Find("attendance-display");
  container.innerHTML = "";
  container.appendChild(table);

  container.parentElement.style.visibility = "visible";
}




async function Attendance_Courses_Search(reload = true)
{
 var module = Core_State_Get("admin", ["attendance","display"]);
 
 if(reload)
 {
  // SEARCH
  var centers   = [UI_Element_Find(module, "search-centers").value]                 || false;
  var program   = UI_Element_Find(module, "search-program").value                   || false;
  var level     = UI_Element_Find(module, "search-level").value                     || false;
  var status    = UI_Element_Find(module, "search-status").value                    || false;
  var date      = Date_From_Input(UI_Element_Find(module, "search-datefrom").value) || false;
  var date_mode = UI_Element_Find("search-datemode").value;
  var loadmore = Core_State_Get("admin",["attendance","loadmore"],false);
  var offset = Core_State_Get("admin",["attendance","offset"],0);
  if(!loadmore) Core_State_Set("admin",["attendance","offset"],0);
  
 
  var search    = {centers, program, level, date, date_mode, status, order:"date_start DESC", options: {limit:100, offset:offset}};
  Core_State_Set("admin",["attendance","search"], search);
 
  var courses   = await Core_Api("Courses_List", search);
  Core_State_Set("admin",["attendance","data"], courses);
 
 }
 else
 { 
  var courses = Core_State_Get("admin",["attendance","data"], []);
 }
 
 
 // FILTER THE RESULTS BY DATES/TIMES DATA
 var days      = Document_Input_Get(UI_Element_Find(module, "search-days"));
 var time_from = parseInt(UI_Element_Find(module, "search-timefrom").value);
 var time_to   = parseInt(UI_Element_Find(module, "search-timeto").value);

 var filtered = [];
  
 for(var course of courses)
 {  
  var validate = true;
  
  var schedule = course["schedule"] || [];
  for(var item of schedule)
  {
   // CHECK DAY
   if(days.length > 0 && !days.includes(item["day"])) 
   {
    validate = false;
	break;
   }
	
   // CHECK TIME
   if(!Numbers_Between(parseInt(item["time"]), time_from, time_to))
   {
    console.log("discarded for time " + item["time"] + " not between " + time_from + " and " + time_to);
    validate = false;
	break;
   }
  }
  
  if(validate) filtered.push(course);
 }
 
 var courses = filtered;
 
 await Attendance_Courses_Search_Display(courses);
}




async function Attendance_Courses_Search_Display(courses)
{
 var module = Core_State_Get("admin", ["attendance","display"]);
 
 // ASSEMBLE RESULTS  
 var list = UI_List_Items(courses, ["style-outlined-accented", "outline-inner"], Attendance_Display, undefined,
 function(course)
 { 
  var locale   = UI_Language_Current(true);
  var centers  = Core_Config("centers");
  var programs = Core_Config("programs");
 
  var data              = {};
  
  data["name"]          = course["name"] || "#" + course["id"];
  
  data["date"]          = UI_Language_Date(course["date_start"], "monthdayyear-compact");
  data["center"]        = Safe_Get(centers,  [course["center_id"], "name"], course["center_id"]);
  data["program"]       = Safe_Get(programs, [course["program"], "name"], course["program"]) || UI_Language_String("courses/course-display", "no program");
  data["level"]         = course["level"] || UI_Language_String("courses/course-display", "no level");
  
  data["seats_taken"]   = course["seats"]["taken"] || 0;
  data["seats_total"]   = course["seats"]["total"] || 0;
  
  data["classes_taken"] = course["classes"]["taken"] || 0;
  data["classes_total"] = course["classes"]["total"] || 0;
  
  
  var days  = [];
  for(var item of course["schedule"] || [])
  {
   var day  = Date_Weekday_Name(item["day"], "short", locale);
   var time = Time_From_Minutes(item["time"]);
   
   days.push(day + " " + time);
  }
  data["days"] = days.join(", ");
  
  var element     = UI_Element_Create("courses/course-item", data);
  Document_Element_SetObject(element, "course", course);
  Document_Element_SetData(element, "course_id", course["id"]);
  
  return element;
 });

 Core_State_Set("admin", ["attendance","courses-list"], list);
 
 // ADD LOAD MORE LISTENER
 const handleInfiniteScroll = async (e) => {
   var element = e.target;
   var endOfElement = element.clientHeight + element.scrollTop >= element.scrollHeight - 100 ;
   var loadmore = Core_State_Get("admin", ["attendance","loadmore"], false);
   if (endOfElement && !loadmore) {
     console.log("load more courses");
     Core_State_Set("admin", ["attendance","loadmore"], true);

     var offset =  Core_State_Get("admin", ["attendance","offset"],0);
     Core_State_Set("admin", ["attendance","offset"],offset + 100);

     await Attendance_Courses_Search(true);
     
     Core_State_Set("admin", ["attendance","loadmore"], false);
   }
 };

 if(courses.length > 0) list.onscroll = handleInfiniteScroll
 
 // DISPLAY RESULTS
 var container = UI_Element_Find(module, "courses-list"); 
 if(Core_State_Get("admin",["attendance", "loadmore"], false))
 {
  if(courses.length > 0)
  {
    list.children.forEach(element => {
      container.children[0].appendChild(element);
    });

    container.style.visibility = "visible";
  }
  // REMOVE LISTENER SCROLL
  var coursesData = Core_State_Get("admin",["attendance", "data"], []);
  if(coursesData.length == 0) container.children[0].onscroll = null;
 }
 else
 {
  if(courses.length > 0)
  {
    container.innerHTML  = "";
    container.appendChild(list);

    container.style.visibility = "visible";
  }
  else
  {
    container.style.visibility = "hidden";
    container.innerHTML  = "";
  }
 }
 

 // CLEAR COURSE DISPLAY
 UI_Element_Find(module, "attendance-display").innerHTML = ""; 
 UI_Element_Find(module, "attendance-detail").style.visibility = "hidden";
}