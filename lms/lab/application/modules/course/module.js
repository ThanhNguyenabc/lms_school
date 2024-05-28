// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                         C O U R S E                                            //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


async function Course_OnShow(module, data)
{
}





async function Course_OnUnload()
{
}





async function Course_OnLoad(module, data)
{
 Core_State_Set("course", "page", "classes");
 Core_State_Set("course", "module-body", module);
 
 var items = {};
 for(var period of ["past", "last month", "last week" ,"this week", "this month","next week", "next month", "future"])
 {
  var item        = {};
  item["icons"]   = [];
  item["text"]    = UI_Language_String("course/periods", period);
  item["period"]  = Core_Data_Value("course/periods", period, "period");
  item["onclick"] = Course_Classes_Display;
  
  items[period]  = item;
 }
 
 var header = UI_Header("classes", items, {selectfirst:false, css:"color-noted", template:"big"});
 
 
 // SWITCH TO "THIS WEEK" VIEW BY DEFAULT, BUT NOT IF ANOTHER MODULE SENT US HERE TO VIEW A SPECIFIC SEAT
 if(!Module_Parameter_Get("viewseat")) UI_Header_Set(header, "this week", true);

 UI_Element_Find(module, "classes-header").appendChild(header);
}





async function Course_Classes_Display(item)
{
 var module = Core_State_Get("course", "module-body");
 
 // HIDE CLASS DETAIL
 var container              = UI_Element_Find(module, "class-detail");
 container.style.visibility = "hidden";
 container.innerHTML        = "";
 
 var period = item["period"];
 var range  = Date_Range(period);
 var seats  = await Core_Api("Class_Seats_ListByStudent", {date_from:range["from"], date_to:range["to"], options:{lesson:true, marknext:true}}); console.log(seats); 

 //var ids       = Array_From_Fields(classes, "lesson_id");
 //var info      = await Core_Api("Lessons_Info", {lessons:ids, sections:["info", "title"]});
 //Array_Integrate_AddFromObject(classes, info)
 
 // FIND UPCOMING CLASS, IF ANY
 var upcoming  = false;
 for(var seat of seats) if(seat["next"])
 {
  upcoming = seat;
  break;
 }
 
 // ASSEMBLE DISPLAY
 var list = UI_List_Items(seats, ["style-outlined-accented"], Course_Class_Display, {style:"vertical", overflow:true, sections:false, animate:true, selected:upcoming, highlight:"style-outlined-alert"},
 // ITEMS
 function(seat)
 {  
  var element = UI_Element_Create("course/lesson-thumbnail");
    
  // COVER
  var picture    = UI_Element_Find(element, "picture");
  var source     = "content/lessons/" + seat["lesson_id"] + "/cover.png";
  var fallback = Resources_URL("images/cover-lesson.jpg");
  Document_Image_Load(picture, [source, fallback]);
  
  // TITLE
  var title      = Safe_Get(seat, ["lesson", "title"], {});
  title          = UI_Language_Object(title);
  UI_Element_Find(element, "title").innerHTML = title;
  
  // DATE
  var date       = Safe_Get(seat, ["date_start"]);
  date           = UI_Language_Date(date, "date-time-compact");
  UI_Element_Find(element, "date").innerHTML = date;
  
  // ATTENDANCE
  var attendance = UI_Element_Find(element, "attendance-" + seat["attendance"]);
  if(attendance) attendance.style.display = "flex";
  
  Document_Element_SetObject(element, "seat", seat);
  return element;
 });
 
 
 // DISPLAY
 var container = UI_Element_Find(module, "classes-list"); 
 container.innerHTML = "";
 container.appendChild(list);
}



async function Course_Class_Display(element, seat_id)
{
 if(element)
 {
  var seat    = Document_Element_GetObject(element, "seat");
  var seat_id = seat["id"];
 }
    
 // DISPLAY CLASS DETAIL INSIDE RIGHT PANEL
 var container              = UI_Element_Find("class-detail");
 await Course_Class(container, seat_id);
 container.style.visibility = "visible";
 
 
 Core_State_Set("course", "page", "class");

 //SET DATA BOOK PAGE
 var currentLesson =  Core_State_Get("course", "current-lesson",null);
 var arrLesson  = Lesson_Get_From_Config(currentLesson,"lesson");
 var data = {};
 data["lessons"]    = await Core_Api("Lessons_Read", {sources:arrLesson["lesson"], data:{all:true}});
 data["vocabulary"] = {};
 for (const key in data["lessons"]) {
    const element = data["lessons"][key];
    data["vocabulary"][key] = element["vocabulary"];
 }
 data["bookpage"]   = await Bookpage_Read_Mutiple(arrLesson["lesson"]);
 data["dialogue"]   = await Dialogue_Read_Mutiple(arrLesson["lesson"]);
 Core_State_Set("course", ["class-data", "book-data"],data);
}