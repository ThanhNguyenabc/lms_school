// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                     B O O K P A G E                                            //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


async function Activity_Bookpage_Run(source, display, config)
{	
 var lesson = Path_Filename(Path_Folder(source));
 var page               = await Lesson_Bookpage_Render(lesson);
 if(!page.lastElementChild) page     = Bookpage_Create_Empty_Page();
 page.style.height      = "90vh";
 page.style.aspectRatio = 1.4;
 page.style.perspective = "100vh";
 page.style.position = "absolute";
 page.style.top = "37px";
 page.style.zIndex = 100;
 page.lastElementChild.style.backfaceVisibility = "hidden";
 page.lastElementChild.style.transformOrigin = "left";
 page.lastElementChild.style.transition = "transform 0.25s";
 page.firstElementChild.style.backfaceVisibility = "hidden";
 page.firstElementChild.style.transformOrigin = "right";
 page.firstElementChild.style.transition = "transform 0.25s";
 page.dataset.uid = lesson;

 Core_State_Set("activity",["bookpage","current-page"],page);

 var frame   = await UI_Frame_Flexi(Resources_URL("images/frames/book-classic"), 32);
 Document_CSS_SetClass(frame, "border-rounded");
 var content = UI_Element_Find(frame, "content");
 content.style.overflow = "unset";
 content.style.height = "90vh";
 content.style.width = "64vw";
 display.innerHTML = "";
 display.appendChild(frame);

 content.appendChild(page);

 Document_Element_FitContent(page, 0.01);
 Bookpage_Adjust(page);
 
 // CHANGE WIDTH CONTENT AFTER LOAD PAGE
 var pageWidth = page.clientWidth;
 content.style.width = pageWidth + "px";
 // FIX TOP PAGE AFTER LOAD
 page.style.top = display.offsetTop + 32 + "px";
 Document_Element_SetData(content,"top", display.offsetTop + 32 + "px");
 var right = UI_Element_Find(frame, "right");
 right.classList.add("style-clickable");
 right.onclick = Activity_Bookpage_FlipBook_ControlNext

 var left = UI_Element_Find(frame, "left");
 left.classList.add("style-clickable");
 left.onclick = Activity_Bookpage_FlipBook_ControlPrev

 var top = content.parentElement.previousSibling.children[0];
 var topInner = UI_Element_Create("activities/bookpage-top");
 topInner.style.display = "inline-flex";
 top.appendChild(topInner);
 var currentPageButton = UI_Element_Find(top,"current");
 currentPageButton.onclick = Activity_Bookpage_ResetSlide;

 //EDIT FEATURE BUTTON
 var button = document.createElement("div");
 button.style.cssText = "display:inline-flex;float:right;margin-right:5px";
 button.classList.add("style-clickable","fa-solid","fa-pen-clip");
 button.onclick = async () => {
  //CHECK CURRENT PAGE
  var page = Core_State_Get("activity", ["bookpage","current-page"]);
  var lesson_id = Core_State_Get("course", "current-lesson");
  if(page.dataset.uid != lesson_id) return;
  var scale = "";
  var page = Core_State_Get("activity",["bookpage","current-page"],null);
  if(page){
    if(UI_Element_Find(page,"vocabulary") && UI_Element_Find(page,"vocabulary").firstElementChild.firstElementChild != null) scale += "&scaleVocabulary=" + UI_Element_Find(page,"vocabulary").firstElementChild.firstElementChild.style.zoom;
    if(UI_Element_Find(page,"dialogue") && UI_Element_Find(page,"dialogue").firstElementChild.firstElementChild != null) scale += "&scaleDialogue=" + UI_Element_Find(page,"dialogue").firstElementChild.firstElementChild.style.zoom;
  }
  window.open("?framework=null&module=bookpage&lesson=" + lesson_id + "&edit=true" + scale, "_", "popup, width="+ screen.width + ", height=" + screen.height + ", fullscreen=yes");
}
 top.appendChild(button);

 await Activity_Bookpage_Load_Flip_Book(content,lesson)
}




async function Activity_Bookpage_Finish(player)
{
 var popup = Core_State_Get("activity", ["popup"]); 
 await UI_Popup_Close(popup); 
}



async function Activity_Bookpage_Load_Flip_Book(content,lesson)
{
  var currentPage = Core_State_Get("activity",["bookpage","current-page"]);
  var prevLesson = Lesson_Get_From_Config(lesson,"prev");
  
  if(prevLesson)
  {
     var prevPage               = await Lesson_Bookpage_Render(prevLesson);
     if(!prevPage.lastElementChild) prevPage     = Bookpage_Create_Empty_Page();
     if(prevPage.lastElementChild){
       prevPage.style.height      = "90vh";
       prevPage.style.aspectRatio = 1.4;
       prevPage.lastElementChild.style.backfaceVisibility = "hidden";
       prevPage.lastElementChild.style.transformOrigin = "left";
       prevPage.lastElementChild.style.transition = "transform 0.25s";
       prevPage.lastElementChild.style.transform =  "rotateY(-180deg)";
       prevPage.firstElementChild.style.backfaceVisibility = "hidden";
       prevPage.firstElementChild.style.transformOrigin = "right";
       prevPage.firstElementChild.style.transition = "transform 0.25s";
     }
     prevPage.style.perspective = "100vh";
     prevPage.dataset.uid = prevLesson;
     prevPage.style.position = "absolute";
     prevPage.style.top = content.dataset.top;
     prevPage.style.zIndex= 10;
     Core_State_Set("activity",["bookpage","prev-page"],prevPage);
     content.insertBefore(prevPage,currentPage);
     Document_Element_FitContent(prevPage, 0.01);
     Bookpage_Adjust(prevPage);
  }

  var nextLesson = Lesson_Get_From_Config(lesson,"next");
  if(nextLesson)
  {
     var nextPage               = await Lesson_Bookpage_Render(nextLesson);
     if(!nextPage.lastElementChild) nextPage     = Bookpage_Create_Empty_Page();
     if(nextPage.lastElementChild)
     {
       nextPage.style.height      = "90vh";
       nextPage.style.aspectRatio = 1.4;
       nextPage.lastElementChild.style.backfaceVisibility = "hidden";
       nextPage.lastElementChild.style.transformOrigin = "left";
       nextPage.lastElementChild.style.transition = "transform 0.25s";
       nextPage.firstElementChild.style.backfaceVisibility = "hidden";
       nextPage.firstElementChild.style.transformOrigin = "right";
       nextPage.firstElementChild.style.transition = "transform 0.25s";
       nextPage.firstElementChild.style.transform =  "rotateY(180deg)";
     }
     nextPage.style.perspective = "100vh";
     nextPage.dataset.uid = nextLesson;
     nextPage.style.position = "absolute";
     nextPage.style.top = content.dataset.top;
     nextPage.style.zIndex = 10;
     Core_State_Set("activity",["bookpage","next-page"],nextPage);
     content.appendChild(nextPage);
     Document_Element_FitContent(nextPage, 0.01);
     Bookpage_Adjust(nextPage);
  }

  // var pageRenderd = await Multi_Lesson_Bookpage_Render(lesson,30);
  // var lessons = Object.keys(pageRenderd);
  // var lessonNext = [];
  // var lessonPre  = [];
  // var checkLessonNext = 0;
  // for (let index = 0; index < lessons.length; index++) {
  //   const les = lessons[index];
  //   if(les == lesson || les == lesson.toUpperCase()) checkLessonNext = 1;
  //   else{
  //     if(checkLessonNext == 1) lessonNext.push(les);
  //     else lessonPre.unshift(les);
  //   }
  // }
  // for (let index = 0; index < lessonPre.length; index++) {
  //   const lessonPrev = lessonPre[index];
  //   var prePage = pageRenderd[lessonPrev];
  //   if(lessonPrev != prevLesson) await Activity_Bookpage_FlipBook_Render(content,prePage,lessonPrev,"pre");
  // } 
  // for (let index = 0; index < lessonNext.length; index++) {
  //   const lesNext = lessonNext[index];
  //   var pageNext = pageRenderd[lesNext];
  //   if(lesNext != nextLesson) await Activity_Bookpage_FlipBook_Render(content,pageNext,lesNext,"next");
  // } 
}



function Activity_Bookpage_FlipBook_ControlNext()
{
  Activity_Bookpage_FlipBook_Disable_Control();
  var currentPage = Core_State_Get("activity",["bookpage","current-page"]);
  var nextPage = Core_State_Get("activity",["bookpage","next-page"]);
  if(nextPage){
   nextPage.style.zIndex = 200;
   nextPage.firstElementChild.style.transform =  "rotateY(0deg)";
   currentPage.lastElementChild.style.transform =  "rotateY(-180deg)";
   setTimeout(async () => {
     currentPage.style.zIndex = 10;
     nextPage.style.zIndex = 100;
     if(nextPage.nextSibling)
     {
      nextPage.nextSibling.firstElementChild.style.transform =  "rotateY(180deg)";
      Core_State_Set("activity",["bookpage","next-page"],nextPage.nextSibling);
     }
     else{
      // CHECK LESSON EXIST
      let currentLesson = nextPage.dataset.uid;
      var lessons = Object.keys(Core_State_Get("course",["class-data","book-data"])["lessons"] || {});
      var checkLessonNext = 0;
      for (let index = 0; index < lessons.length; index++) {
        const les = lessons[index];
        if(les == currentLesson || les == currentLesson.toUpperCase()) {
          if(index < lessons.length - 1) checkLessonNext = index + 1;
        }
      }
      if(checkLessonNext)
      {
        var newPage = await Lesson_Bookpage_Render(lessons[checkLessonNext]);
        var newPageRendered = await Activity_Bookpage_FlipBook_Render(nextPage.parentElement,newPage,lessons[checkLessonNext],"next");
        newPageRendered.firstElementChild.style.transform =  "rotateY(180deg)";
        Core_State_Set("activity",["bookpage","next-page"],newPageRendered);
      } 
      else {
        Core_State_Set("activity",["bookpage","next-page"],null);
      }
     }
     Activity_Bookpage_FlipBook_Enable_Control();
   }, 250);
   
   Core_State_Set("activity",["bookpage","current-page"],nextPage);
   Core_State_Set("activity",["bookpage","prev-page"],currentPage);
  }
  else Activity_Bookpage_FlipBook_Enable_Control();
}



function Activity_Bookpage_FlipBook_ControlPrev()
{
  Activity_Bookpage_FlipBook_Disable_Control();
  var currentPage = Core_State_Get("activity",["bookpage","current-page"]);
  var prevPage = Core_State_Get("activity",["bookpage","prev-page"]);
  if(prevPage){
   prevPage.style.zIndex = 200;
   prevPage.lastElementChild.style.transform =  "rotateY(0deg)";
   currentPage.firstElementChild.style.transform =  "rotateY(180deg)";
   setTimeout(async () => {
     currentPage.style.zIndex = 10;
     prevPage.style.zIndex = 100;
     if(!prevPage.previousSibling)
     {
      // CHECK LESSON EXIST
      let currentLesson = prevPage.dataset.uid;
      var lessons = Object.keys(Core_State_Get("course",["class-data","book-data"])["lessons"] || {});
      var checkLessonPre = -1;
      for (let index = 0; index < lessons.length; index++) {
        const les = lessons[index];
        if(les == currentLesson || les == currentLesson.toUpperCase()) {
          if(index > 0) checkLessonPre = index - 1;
        }
      }
      if(checkLessonPre >= 0)
      {
        var newPage =await Lesson_Bookpage_Render(lessons[checkLessonPre]);
        var newPageRendered = await Activity_Bookpage_FlipBook_Render(prevPage.parentElement,newPage,lessons[checkLessonPre],"pre");
        Core_State_Set("activity",["bookpage","prev-page"],newPageRendered);
      } 
      else{
        Core_State_Set("activity",["bookpage","prev-page"],null);
      }
     }
     else{
      Core_State_Set("activity",["bookpage","prev-page"],prevPage.previousSibling);
     }
     Activity_Bookpage_FlipBook_Enable_Control();
   },250);
   Core_State_Set("activity",["bookpage","current-page"],prevPage);
   Core_State_Set("activity",["bookpage","next-page"],currentPage);
  }
  else Activity_Bookpage_FlipBook_Enable_Control();
}



async function Activity_Bookpage_FlipBook_Render(content,page,lesson,mode)
{
  var newPage = page;
  if(!newPage.lastElementChild)
  {
    newPage = Bookpage_Create_Empty_Page();
  }

  newPage.style.height      = "90vh";
  newPage.style.aspectRatio = 1.4;
  newPage.style.perspective = "100vh";
  newPage.dataset.uid = lesson;
  newPage.style.position = "absolute";
  newPage.style.top = content.dataset.top;
  newPage.style.zIndex= 10;

  newPage.lastElementChild.style.backfaceVisibility = "hidden";
  newPage.lastElementChild.style.transformOrigin = "left";
  newPage.lastElementChild.style.transition = "transform 0.25s";

  newPage.firstElementChild.style.backfaceVisibility = "hidden";
  newPage.firstElementChild.style.transformOrigin = "right";
  newPage.firstElementChild.style.transition = "transform 0.25s";

  switch (mode) {
    case "pre":
      newPage.lastElementChild.style.transform =  "rotateY(-180deg)";
      content.insertBefore(newPage,content.children[0]); 
      break;
  
    default:
      newPage.firstElementChild.style.transform =  "rotateY(180deg)";
      content.appendChild(newPage);
      break;
  }

  Document_Element_FitContent(newPage, 0.01);
  Bookpage_Adjust(newPage); 
  return newPage;
}

function  Activity_Bookpage_FlipBook_Disable_Control(){
 var popup = Core_State_Get("activity","popup");
 var right = UI_Element_Find(popup, "right");
 right.onclick = null;

 var left = UI_Element_Find(popup, "left");
 left.onclick = null;
}

function  Activity_Bookpage_FlipBook_Enable_Control(){
  var popup = Core_State_Get("activity","popup");
  var right = UI_Element_Find(popup, "right");
  right.onclick = Activity_Bookpage_FlipBook_ControlNext;
  
  var left = UI_Element_Find(popup, "left");
  left.onclick = Activity_Bookpage_FlipBook_ControlPrev;
}

function Activity_Bookpage_ResetSlide(){

  var content = Core_State_Get("activity",["bookpage","current-page"]).parentElement;
  //RESET LESSON
  var defaultLesson = Core_State_Get("course","current-lesson");
  var prevLesson = Lesson_Get_From_Config(defaultLesson,"prev");
  var nextLesson = Lesson_Get_From_Config(defaultLesson,"next");

  var resetLesson = UI_Element_Find(content,defaultLesson);
  var resetPreLesson = UI_Element_Find(content,prevLesson);
  var resetNextLesson = UI_Element_Find(content,nextLesson);

  Core_State_Set("activity",["bookpage","current-page"],resetLesson);
  Core_State_Set("activity",["bookpage","prev-page"],resetPreLesson);
  Core_State_Set("activity",["bookpage","next-page"],resetNextLesson);

  //RESET TRANSITION / Z-INDEX
  var pre = true;
  for (let index = 0; index < content.children.length; index++) {
    var element = content.children[index];
    element.firstElementChild.style.transform =  null;
    element.lastElementChild.style.transform =  null;
    element.style.zIndex = 10;
    console.log(element.dataset.uid, defaultLesson);
    if(element.dataset.uid == defaultLesson)
    {
      pre = false;
      element.style.zIndex = 100;
    }  
    else if(pre){
      element.lastElementChild.style.transform =  "rotateY(-180deg)";
    } else{
      element.firstElementChild.style.transform =  "rotateY(180deg)";
    }
  }
}
