// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                      B O O K   P A G E                                         //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//

async function Bookpage_OnLoad(module, data)
{
}



async function Bookpage_OnShow(module, data)
{
 var lesson = Client_Location_Parameter("lesson");
 
 var page   = await Lesson_Bookpage_Render(lesson);
 
 module.appendChild(page);
 
 // IF PRINT SIZE REQUIRED...
 if(Client_Location_Parameter("print"))
 {
  Document_CSS_UnsetClass(module, "content-centered");
  module.style.alignItems = "center"; 
  module.style.overflow   = "auto";
 }
 else
 // IF PRINT SIZE IS NOT REQUIRED, RESIZE TO SCREEN
 {
  Document_Element_FitContent(page);
  Bookpage_Adjust(page);
 }

 if(Client_Location_Parameter("edit")){
   await Bookpage_Render_Canvas(module,page);
 }
 
 console.log(page);
}




async function Bookpage_OnUnload()
{
}





// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                          M O R E                                               //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//

async function Bookpage_Render_Canvas(module,page)
{
  // CUSTOM page
  Document_CSS_UnsetClass(module, "content-centered");

  page.classList.remove("container-row");
  page.firstElementChild.style.zoom = null;
  page.lastElementChild.style.zoom = null;

  var dialog = UI_Element_Find(page,"dialogue");
  if(dialog){
     var lines = UI_Element_Find(dialog,"lines");
     var height = dialog.clientHeight;
     var width = dialog.clientWidth;
     if(lines.children.length > 0){
      var scale = lines.children[0].style.zoom;
      if(Client_Location_Parameter("scaleDialogue"))  scale = Client_Location_Parameter("scaleDialogue");
      lines.style.transform = "scale(" + scale + ")";
      lines.style.transformOrigin = "top left";
      lines.style.overflow = "unset";
      lines.style.height = height/scale;
      lines.style.width = width/scale;
      dialog.style.width = width/scale;
      // REMOVE ZOOM
      for (let index = 0; index < lines.children.length; index++) {
         var element = lines.children[index];
         element.style.zoom = null;
         element.lastElementChild.style.width = "100%";
         var fontsize = window.getComputedStyle(element.lastElementChild.lastElementChild, null).getPropertyValue('font-size');
         element.lastElementChild.lastElementChild.style.fontSize = fontsize*scale + 'px';
      }
     }
  }

  var vocabulary = UI_Element_Find(page,"vocabulary");
  if(vocabulary){
     var lines = UI_Element_Find(vocabulary,"lines");
     var height = vocabulary.clientHeight;
     if(lines.children.length > 0){
      var scale = lines.children[0].style.zoom;
      if(Client_Location_Parameter("scaleVocabulary"))  scale = Client_Location_Parameter("scaleVocabulary");
      lines.style.transform = "scale(" + scale + ")";
      lines.style.transformOrigin = "top left";
      lines.style.overflow = "unset";
      lines.style.height = height/scale;
      // REMOVE ZOOM
      for (let index = 0; index < lines.children.length; index++) {
         var element = lines.children[index];
         element.style.zoom = null;
      }
     }
  }

  
  await html2canvas(page).then(async function(canvas)
  {
    page.style.display = "none";
    Bookpage_Canvas_Create(module,canvas);
});
}

function Bookpage_Canvas_Create(module,canvas)
{
   canvas.addEventListener("mousemove", Bookpage_Edit_Pointer); 

   var whiteboard = UI_Element_Create("bookpage/bookpage-whiteboard");
   whiteboard.onmousedown = Bookpage_Whiteboard_Draw_Start;
   whiteboard.onmouseup   = Bookpage_Whiteboard_Draw_End;
   whiteboard.onmousemove =  Bookpage_Whiteboard_Draw_Draw;
   whiteboard.ondragstart =  Bookpage_Event_DragStart; 
   whiteboard.appendChild(canvas);
   module.appendChild(whiteboard);

   var rightContainer = UI_Element_Create("bookpage/bookpage-whiteboard-rightmenu");
   module.appendChild(rightContainer);

   Core_State_Set("bookpage","canvas",canvas);
   Core_State_Set("bookpage","whiteboard",whiteboard);
   
   Document_Element_SetObject(whiteboard, "canvas", canvas);
   Document_Element_SetObject(whiteboard, "handle", canvas.getContext("2d"));

   Bookpage_Whiteboard_SetPen(whiteboard);

   var container = UI_Element_Find(whiteboard, "tools");
   var pens      = Core_Data_Page("bookpage/pens");
   for(var id in pens)
   {
    var pen                   = pens[id];
    
    var tip                   = UI_Element_Create("whiteboard/tip-" + pen["type"]);
    tip.style.position        = "relative";
    tip.style.width           = "16px";
    tip.style.backgroundColor = RgbaToRgb(pen["color"]);
    tip.style.outline         = "solid 2px";
    tip.style.outlineColor    = "var(--color-dark)";
    tip.title                 = UI_Language_Object(pen);
    
    Document_CSS_SetClass(tip, "style-clickable");
    Document_CSS_SetClass(tip, "style-selectable");
  
    Document_Element_SetData(tip, "name", id);
    Document_Element_SetData(tip, "uid", id);
    Document_Element_SetObject(tip, "whiteboard", whiteboard);

    // ADD ICON
    if(typeof pen["icon"] != "undefined" && pen["icon"] != "") 
    {
      var icon = document.createElement("i");
      icon.classList.add(...pen["icon"].split(" "));
      tip.appendChild(icon);
      tip.style.outline = null;
      tip.style.alignItems = "center";
    }
    
    tip.onclick = 
    function(event)
    {
     var element    = event.currentTarget;
     var name       = Document_Element_GetData(element, "name");
     var whiteboard = Document_Element_GetObject(element, "whiteboard");
     Media_Audio_Play(Resources_URL("sounds/click.mp3"));
     Document_Element_Animate(element, "rubberBand 0.5s");
     Bookpage_Whiteboard_SetPen(whiteboard, name);
    }
    
    container.appendChild(tip);
   }

   // SET RIGHTMENNU
   var resetButton = UI_Element_Find(rightContainer,"reset");
   resetButton.onclick = Bookpage_Canvas_Reset;
   var undoButton = UI_Element_Find(rightContainer,"undo");
   undoButton.onclick = Bookpage_Canvas_Undo;
   var redoButton = UI_Element_Find(rightContainer,"redo");
   redoButton.onclick = Bookpage_Canvas_Redo;
   var downloadButton = UI_Element_Find(rightContainer,"download");
   downloadButton.onclick = Bookpage_Canvas_Download;
   // SAVE CANVAS
   Bookpage_Canvas_Save();
}


function Bookpage_Whiteboard_Draw_End(event)
{

 var whiteboard = event.currentTarget;
 var status = Document_Element_GetData(whiteboard, "status");

 switch (status) {
  case "draw":
    Bookpage_Canvas_Save();
    break;

  case "dragging":
    var element      = Core_State_Get("bookpage","current-input",null);
    if(element)
    Bookpage_ElementChanged(element);
    break;
  case "create-input":
    var element      = Core_State_Get("bookpage","current-input",null);
    if(element) element.focus();
    break;
  default:
    break;
 }
 Document_Element_SetData(whiteboard, "status", "");

}


function Bookpage_Edit_Pointer(event)
{  
 var layer   = Core_State_Get("bookpage","whiteboard",null);
 var pointer = UI_Element_Find(layer, "overlay").firstElementChild;
 var size    = Document_Element_Size(pointer);
 var target  = event.currentTarget;
 var rect    = target.getBoundingClientRect();

 var left    = (event.clientX  - rect.left + target.offsetLeft - (size["width"]/2))  + "px";
 var top     = (event.clientY  - rect.top - (size["height"]/2)) + "px";
 
 pointer.style.left = left;
 pointer.style.top  = top;
}




function Bookpage_Whiteboard_Draw_Start(event)
{
  console.log("mouse down")
 var elementSelected = Core_State_Get("bookpage","current-input",null);
 var whiteboard = event.currentTarget;
 var canvas = Document_Element_GetObject(whiteboard, "canvas");
 var target = event.target;
 if(target != canvas) 
 {
  if(target.tagName == "INPUT") Core_State_Set("bookpage","current-input",target);
 }
 else
 {    
  if(elementSelected)
  {
    console.log(elementSelected)
    var handle =  Document_Element_GetObject(whiteboard, "handle");
    var pointer  = {};
    pointer["x"] = parseFloat(elementSelected.style.left) - canvas.offsetLeft + whiteboard.offsetLeft;
    pointer["y"] = parseFloat(elementSelected.style.top) - canvas.offsetTop + whiteboard.offsetTop + 20;
    handle.font = elementSelected.style.fontSize + "  Arial"; 
    handle.fillStyle = elementSelected.style.color;
    var arrValue = elementSelected.value.split(/\n/g);
    for (let index = 0; index < arrValue.length; index++) {
      const element = arrValue[index];
      handle.fillText(element,pointer["x"],pointer["y"] + parseFloat(elementSelected.style.fontSize)*index);
    }
    var detail = Document_Element_GetObject(elementSelected,"details");
    detail.remove();
    elementSelected.remove();
    Core_State_Set("bookpage","current-input",null);
    Bookpage_Canvas_Save();
  } 
  else 
  {
    var pointOverlay = UI_Element_Find(whiteboard, "overlay").firstElementChild;
    var pointer  = {};
 
    pointer["x"] = parseFloat(pointOverlay.style.left) - canvas.offsetLeft + whiteboard.offsetLeft;
    pointer["y"] = parseFloat(pointOverlay.style.top) - canvas.offsetTop + whiteboard.offsetTop + parseFloat(pointOverlay.style.width) / 2;
 
    Document_Element_SetData(whiteboard, "status", "draw");
    Document_Element_SetObject(whiteboard, "pointer", pointer);
 
    var pen     = Document_Element_GetObject(whiteboard, "pen");
    if(pen["id"] == "text")
    {
       var input = UI_Element_Create("bookpage/bookpage-whiteboard-input");
       input.style.left = pointOverlay.style.left;
       input.style.top = pointOverlay.style.top
       
       whiteboard.appendChild(input);
       Core_State_Set("bookpage","current-input",input);
       Document_Element_SetData(whiteboard, "status", "create-input");
       Bookpage_Input_Detail(input)
    } 
  }    
 }
}
 




function  Bookpage_Whiteboard_Draw_Draw(event)
{  
 var whiteboard = event.currentTarget || event.srcElement;
 if(!whiteboard)
 {
  return;
 }
  
 var status = Document_Element_GetData(whiteboard, "status");
 var canvas = Document_Element_GetObject(whiteboard, "canvas");
 var pointOverlay = UI_Element_Find(whiteboard,"overlay").firstElementChild;

 switch(status)
 {
  case "draw":
   
	var handle  = Document_Element_GetObject(whiteboard, "handle");
	var pointer = Document_Element_GetObject(whiteboard, "pointer");
   var pen     = Document_Element_GetObject(whiteboard, "pen");
     
	handle.beginPath();
	handle.strokeStyle = pen["color"];
	handle.lineWidth   = pen["size"];
	handle.moveTo(pointer["x"], pointer["y"]);
 
  pointer["x"] = parseFloat(pointOverlay.style.left) - canvas.offsetLeft + whiteboard.offsetLeft;
  pointer["y"] = parseFloat(pointOverlay.style.top) - canvas.offsetTop + whiteboard.offsetTop + parseFloat(pointOverlay.style.width) / 2;
 
	handle.lineTo(pointer["x"], pointer["y"]);
	handle.stroke();

  break;
  case "dragging":
   var element      = Core_State_Get("bookpage","current-input");
   //console.log(element);
   if(element)
   Bookpage_ElementChanged(element);
   break;
 }

}



function Bookpage_Whiteboard_SetPen(whiteboard, set = {id:"default", type:"round", size:1, color:"white", mode:"destination-over"})
{
 if(typeof set == "string")
 {
  // SET OUTLINE
  var penId = set;
  var elementPen = UI_Element_Find(whiteboard,penId);
  elementPen.style.outline         = "solid 4px";
  elementPen.style.outlineColor    = "var(--color-wheel-2)";

  var pens = Core_Data_Page("bookpage/pens");
  var set  = pens[penId] || {};
  set.id = penId;
  set.element = elementPen;
 }
	
 var pen = Document_Element_GetObject(whiteboard, "pen") || {};
 var element = Safe_Get(pen,"element",null);
 if(element) 
 {
  // RESET OUTLINE
  element.style.outline         = "solid 2px";
  element.style.outlineColor    = "var(--color-dark)";
  if(pen.icon != "") element.style.outline = null;
 }
 Object.assign(pen, set);
 
 Document_Element_SetObject(whiteboard, "pen", pen);
 
 var tip = UI_Element_Create("whiteboard/tip-" + pen["type"]);
 Document_Element_SetObject(whiteboard, "tip", tip);
 tip.style.width           = pen["size"] + "px";
 tip.style.backgroundColor = pen["color"];
 
 var overlay       = UI_Element_Find(whiteboard, "overlay");
 overlay.innerHTML = "";
 overlay.appendChild(tip);
 
 var handle     = Document_Element_GetObject(whiteboard, "handle");
 handle.lineCap = pen["type"];
 
 handle.globalCompositeOperation = pen["mode"];
}


function Bookpage_Event_Disable()
{
   var whiteboard = Core_State_Get("bookpage","whiteboard");
   whiteboard.onmousedown = null;
   whiteboard.onmouseup   = null;
   whiteboard.onmousemove =  null;
}

function Bookpage_Event_Enable()
{
   var whiteboard = Core_State_Get("bookpage","whiteboard");
   whiteboard.onmousedown = Bookpage_Whiteboard_Draw_Start;
   whiteboard.onmouseup   = Bookpage_Whiteboard_Draw_End;
   whiteboard.onmousemove =  Bookpage_Whiteboard_Draw_Draw;
}


function Bookpage_Event_DragStart(event)
{
 event.preventDefault();
 var whiteboard = Core_State_Get("bookpage","whiteboard");
 var element = event.target;

 Core_State_Set("bookpage","current-input",element);
 Document_Element_SetData(whiteboard, "status", "dragging");
}



function Bookpage_ElementChanged(element)
{
 if(!element) return;
 var whiteboard = Core_State_Get("bookpage","whiteboard");
 var pointOverlay = UI_Element_Find(whiteboard, "overlay").firstElementChild;
 
 element.style.left = pointOverlay.style.left;
 element.style.top = pointOverlay.style.top;
 element["changed"] = true;
}

function Bookpage_Canvas_Save()
{
   var whiteboard = Core_State_Get("bookpage","whiteboard");
   var canvas = Document_Element_GetObject(whiteboard, "canvas");
   var handle = canvas.getContext("2d");
   var img = handle.getImageData(0, 0, canvas.width, canvas.height);
   var history = Core_State_Get("bookpage","history",{list : [], index : 0});
   if(history.list.length > history.index) history.list.splice(history.index, history.list.length - history.index);
   history.list.push(img);
   history.index += 1;
   Core_State_Set("bookpage","history",history);
}


function Bookpage_Canvas_Undo()
{
   var history = Core_State_Get("bookpage","history");
   var index = history.index - 1;
   if(index >= 1){
      var img = history.list[index - 1];
      var whiteboard = Core_State_Get("bookpage","whiteboard");
      var handle = Document_Element_GetObject(whiteboard, "handle");
      handle.putImageData(img, 0, 0);
      Core_State_Set("bookpage",["history","index"],index);
   }
}



function Bookpage_Canvas_Redo()
{
   var history = Core_State_Get("bookpage","history");
   var index = history.index + 1;
   if(index <= history.list.length ){
      var img = history.list[index - 1];
      var whiteboard = Core_State_Get("bookpage","whiteboard");
      var handle = Document_Element_GetObject(whiteboard, "handle");
      handle.putImageData(img, 0, 0);
      Core_State_Set("bookpage",["history","index"],index);
   }
}


function Bookpage_Canvas_Download()
{
   var canvas = Core_State_Get("bookpage","canvas");
   var lesson = Client_Location_Parameter("lesson");
   var opt = {
      filename: lesson
   }
   html2pdf().set(opt).from(canvas).save();
}


function Bookpage_Canvas_Reset()
{
  var history = Core_State_Get("bookpage","history");
  var img = history.list[0];
  var whiteboard = Core_State_Get("bookpage","whiteboard");
  var handle = Document_Element_GetObject(whiteboard, "handle");
  handle.putImageData(img, 0, 0);
  Core_State_Set("bookpage","history",{list : [img], index : 1});
  Bookpage_Whiteboard_SetPen(whiteboard);
}


function Bookpage_Input_Detail(input)
{
  var whiteboard = Core_State_Get("bookpage","whiteboard");
  var rightContainer = UI_Element_Find(whiteboard.parentElement,"right-menu");
  var detail = UI_Element_Create("bookpage/bookpage-input-information");

  var fontSize = UI_Element_Find(detail,"font-size");
  fontSize.value = parseFloat(input.style.fontSize);
  fontSize.onchange = (e) => {
    var element = e.target;
    input.style.fontSize = element.value;
  }

  var color = UI_Element_Find(detail,"text-color");
  color.value = input.style.color;
  color.onchange = (e) => {
    var element = e.target;
    input.style.color = element.value;
  }

  var saveButton = UI_Element_Find(detail,"save-button");
  Document_Element_SetObject(saveButton, "input", input);
  saveButton.onclick = (e) => {
    var element = e.target;
    var input = Document_Element_GetObject(element, "input");
    var whiteboard = Core_State_Get("bookpage","whiteboard");
    var canvas = Document_Element_GetObject(whiteboard, "canvas");
    var handle = Document_Element_GetObject(whiteboard, "handle");
    
    var pointer  = {};
    pointer["x"] = parseFloat(input.style.left) - canvas.offsetLeft + whiteboard.offsetLeft;
    pointer["y"] = parseFloat(input.style.top) - canvas.offsetTop + whiteboard.offsetTop + 20;
    
    handle.font = input.style.fontSize + "  Arial"; 
    handle.fillStyle = input.style.color;
    var arrValue = input.value.split(/\n/g);
    for (let index = 0; index < arrValue.length; index++) {
      const element = arrValue[index];
      handle.fillText(element,pointer["x"],pointer["y"] + parseFloat(input.style.fontSize)*index);
    }
    var detail = Document_Element_GetObject(input,"details");
    detail.remove();
    
    input.remove();
    Core_State_Set("bookpage","current-input",null);
    Bookpage_Canvas_Save();
  }

  rightContainer.appendChild(detail);
  Document_Element_SetObject(input, "details", detail);
}