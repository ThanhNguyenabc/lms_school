
// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                 D O W N L O A D   B O O K                                      //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//

function Editor_Downloadbook()
{
    // GET SUBMODULE BODY
    var submodule = Module_Page_Body();

    Core_State_Set("editor", ["downloadbook","display"],submodule);

    var download = UI_Element_Find(submodule, "book-download");
    download.style.display = "none";
    download.onclick = async () =>{
        var download = UI_Element_Find(submodule, "book-download");
        download.classList.add("loader");
        download.style.fontSize = "4px";
        download.style.marginTop = "10px";
        download.style.marginRight = "10px";
        var container = UI_Element_Find(submodule, "book-display");
        var program = UI_Element_Find(submodule, "search-program").value || false;
        var level = UI_Element_Find(submodule, "search-level").value || false;
        var flag = 1;
        var index = 0;
        while (flag) {
            flag = 0;
            var element = container.children[index] || null;
            if(element)
            {
                var element = Editor_Downloadbook_ConfigScale(element);
                var opt = {
                    filename:  program.toUpperCase() + '_' + level.toUpperCase() + '_BOOK(' + element.dataset.displayid +').pdf',
                }
                // CHECK ZOOM PAGE
                var cloneElement = element.cloneNode(true);
                if(document.body.style.zoom != null)
                {
                  cloneElement.style.transformOrigin = "top left";
                  cloneElement.style.marginLeft = (document.body.offsetWidth - document.body.clientWidth)/2;
                  if(document.body.style.zoom == 2 && parseFloat(document.body.clientWidth)  <= 1588)
                  {
                    cloneElement.style.marginLeft = parseFloat(cloneElement.style.marginLeft) + (1588 - parseFloat(document.body.clientWidth))/4;
                  }
                }
                await html2pdf().from(cloneElement).set(opt).save().then(function(){
                    flag = 1;
                    index ++ ;
                });
            }
        }
        download.classList.remove("loader");
        download.style.fontSize = null;
        download.style.marginTop = null;
        download.style.marginRight = null;
    }

    var search  = Core_State_Get("editor", ["downloadbook","search"], {});

    // ALL PROGRAMS
    var programs = Core_Config("programs");

    // PROGRAM SELECT
    var select = UI_Element_Find(submodule, "search-program");
    Document_Select_AddOption(select, UI_Language_String("courses", "search any"), "");
    Document_Select_AddOption(select, "---", "").disabled = true;
    Document_Select_OptionsFromObjects(select, programs, "name", false);
    
    if(typeof search["program"] != "undefined") select.value = search["program"];
    
    select.onchange = 
    function(event)
    {
        var display   = Core_State_Get("editor", ["downloadbook","display"]);
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
        
        if(typeof search["level"] != "undefined") select.value = search["level"];
    }

    var search = UI_Element_Find(submodule, "button-book-search");
    search.onclick = Editor_Downloadbook_Display;
}


async function Editor_Downloadbook_Display()
{
    var programConfig = Core_Config("programs");
    var submodule = Core_State_Get("editor", ["downloadbook","display"]);
    var program = UI_Element_Find(submodule, "search-program").value || false;
    var level = UI_Element_Find(submodule, "search-level").value || false;
    var download = UI_Element_Find(submodule, "book-download");
    download.style.display = "none";

    if(program && level)
    {
        var container = UI_Element_Find(submodule, "book-display");
        container.innerHTML = "";

        var loader = UI_Element_Create("editor/downloadbook-loader");
        container.appendChild(loader);

        var lessons = programConfig[program][level];
        var firstlesson = lessons.split(",")[0];
        var pagesRender = await Multi_Lesson_Bookpage_Render(firstlesson,lessons.split(",").length);
        var arrLesson  = Lesson_Get_From_Config(firstlesson,"lesson");
        var bookpages = await Core_Api("Bookpage_Read_Mutiple", {sources: arrLesson["lesson"] });
        
        container.innerHTML = "";
        var div ;
        var countPage = 0;
        var id = 0;
        for (const lessonName in pagesRender) {
          if(typeof bookpages[lessonName]["page"] != "undefined")
          {
            if(countPage == 0)
            {
            div = document.createElement("div");
            div.innerHTML = ""; 
            div.dataset.displayid = id;
            container.appendChild(div);
            } 
            countPage ++;

            var page = pagesRender[lessonName];
            if(page.firstElementChild){
                page.classList.remove("container-row");
                page.dataset.uid = lessonName;
                div.appendChild(page); 
                Bookpage_Adjust(page);
            }

            if(countPage == 20 )
            {
                countPage = 0;
                id++;
            }
          }
        } 
        download.style.display = "flex";
    }
    
}

function Editor_Downloadbook_ConfigScale(elements)
{
    for (let index = 0; index < elements.children.length; index++) 
    {
        var element = elements.children[index];

        if(element.dataset.scaled) continue;
        else
        {
            element.dataset.scaled = true;

            var vocabulary = UI_Element_Find(element,"vocabulary");
            var dialogue = UI_Element_Find(element,"dialogue");

            if(vocabulary)
            {
                if(typeof vocabulary.children[0].children[0] != "undefined")
                {
                        var scale = vocabulary.children[0].children[0].style.zoom || 1;
                        var width = vocabulary.clientWidth;
                        var height = vocabulary.clientHeight;
                        vocabulary.style.width = width/scale;
                        vocabulary.children[0].style.transform = "scale(" + scale + ")";
                        vocabulary.children[0].style.width = width/scale; 
                        vocabulary.children[0].style.height = height/scale; 
                        vocabulary.children[0].style.transformOrigin = "left top";
                        for (let index = 0; index < vocabulary.children[0].children.length; index++) 
                        {
                            var elementVocab = vocabulary.children[0].children[index];
                            elementVocab.style.zoom = null;
                        }
                }
            } 

            if(dialogue)
            {
                var lines = dialogue.children[0] || null;
                if(typeof lines.children[0] != "undefined")
                {
                    var scale = lines.children[0].style.zoom || 1;
                    var width = dialogue.clientWidth;
                    var height = dialogue.clientHeight;
                    dialogue.style.width = width/scale;
                    lines.style.alignItems = "stretch";
                    lines.style.transform = "scale(" + scale + ")";
                    lines.style.width = width/scale; 
                    lines.style.height = height/scale;  
                    lines.style.transformOrigin = "left top";
                    for (let index = 0; index < lines.children.length; index++) 
                    {
                        var elementDialogue = lines.children[index];
                        elementDialogue.style.zoom = null;
                        elementDialogue.lastElementChild.style.width = "100%";
                        var fontsize = window.getComputedStyle(elementDialogue.lastElementChild.lastElementChild, null).getPropertyValue('font-size');
                        elementDialogue.lastElementChild.lastElementChild.style.fontSize = fontsize*scale + 'px';
                    }
                }
            } 
        }
       
    }
    return elements;
}