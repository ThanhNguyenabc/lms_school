// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                        P R O J E C T                                           //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//

async function Classroom_Manage_DisplayReminder()
{
 var reviewElement = UI_Element_Find("review");
 Document_Element_Animate(reviewElement, "flash 1.5s 4");
 var class_id       = Core_State_Get("classroom", ["class", "info", "id"]);
  
 // IF A REMINDER HASN'T BEEN ALLOCATED YET, DO IT 
 var courseObj     = Core_State_Get("course",["current-course"]);
 
 var user = Safe_Get(application, ["user"], {});
 if([Core_State_Get("classroom", ["class", "info", "teacher_id"]),Core_State_Get("classroom", ["class", "info", "assistant_id"]),Core_State_Get("classroom", ["class", "info", "ta1_id"]),Core_State_Get("classroom", ["class", "info", "ta2_id"]),Core_State_Get("classroom", ["class", "info", "ta3_id"])].includes(user.id)){
    var userid = user.id;
    reminder_id       = await Core_Api("Reminder_Create", {course:courseObj, teacher_id:userid});
    window.open("https://docs.google.com/document/d/"+reminder_id, "_blank");
 }
 else{
    var reminder_id = Core_State_Get("course",["current-course", "reminder_id"]);
    var teacher_id = Core_State_Get("classroom", ["class", "info", "teacher_id"]);
    if(!reminder_id)
    {
      reminder_id       = await Core_Api("Reminder_Create", {course:courseObj, teacher_id});
      Core_State_Set("course",["current-course","reminder_id"], reminder_id);
    }

    var page = UI_Element_Create("classroom/document-container");
    page.src = "https://docs.google.com/document/d/" + reminder_id + "/?usp=sharing&embedded=true&rm=demo";
    var container       = UI_Element_Find("presentation-panel-right");
    container.innerHTML = "";
    container.appendChild(page);
 }
}

async function Course_Class_DisplayReminderFile(content, state)
{
    var course_id = Core_State_Get("course", ["class-data", "class", "course_id"]);
    var courseObj = await Core_Api("Courses_Read",{id:course_id,fields:"reminder_id"});
    var reminder_id = courseObj.reminder_id;

    var page = UI_Element_Create("course/review-file-container");
    page.src = "https://docs.google.com/document/d/" + reminder_id + "/?usp=sharing&embedded=true&rm=demo";

    content.innerHTML = "";
    content.appendChild(page);
}