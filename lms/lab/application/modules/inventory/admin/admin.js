async function Inventory_Admin() {
  Core_State_Set("inventory", "inventory-table-header", [
    UI_Language_String("inventory/module", "table no"),
    UI_Language_String("inventory/module", "table item code"),
    UI_Language_String("inventory/module", "table item name"),
    UI_Language_String("inventory/components/module", "search group"),
    UI_Language_String("inventory/components/module", "search program"),
    UI_Language_String("inventory/components/module", "search level"),
  ]);

  var module = Core_State_Get("inventory", "container");
  const container = UI_Element_Find(module, "inventory-admin");

  const itemDetailContainer = UI_Element_Create(
    "inventory/components/inventory-detail",
    {
      btnAction: UI_Language_String("inventory/module", "button save"),
    }
  );

  // Init search UI
  const groupPrograms = Core_State_Get("inventory", "groupPrograms");
  const searchElement = createSearchElement("admin", {
    showCenter: false,
    showProgram: false,
    onSearch: (e) => { btnClicked(e,"admin");  showItemsAdminTable(); },
    programData: groupPrograms,
  });

  const exportButton = UI_Element_Create("core/button-small-stretch",{text:UI_Language_String("inventory/components/module","button export")});
  Document_Element_SetObject(exportButton, "state", "admin");
  exportButton.onclick = (e) => { exportExecl(e);}
  UI_Element_Find(searchElement, "action-container").appendChild(exportButton);

  const loadDataButton = UI_Element_Create("core/button-small-stretch",{text:UI_Language_String("inventory/components/module","button load")});
  loadDataButton.style.justifyContent = "space-around";
  loadDataButton.onclick = async function(){
    var div = document.createElement("div");
    div.classList.add("loader");
    div.style.cssText = " font-size: 4px; position: absolute; margin-left: 200px";
    loadDataButton.appendChild(div);
    
    var updateStatus = await Core_Api("Inventory_Items_Create_Update_FromD365&filter=true");
    if(updateStatus)
    {
      loadDataButton.removeChild(loadDataButton.firstElementChild);
      //UPDATE GROUP DATA
      var groups = await Core_Api("Inventory_Get_Groups_Item");
      Core_State_Set("inventory", "groups", groups);
      reloadGroupsData()
      showItemsAdminTable();
      showAlertMessage("Data has been updated");
    }
    else showAlertMessage("No Data has been updated!!");
  }
  UI_Element_Find(searchElement, "action-container").appendChild(loadDataButton);
  
  container.appendChild(searchElement);

  // Init List Item
  const itemListContainer = UI_Element_Create(
    "inventory/components/inventory-list"
  );
  container.appendChild(itemListContainer);

  container.appendChild(itemDetailContainer);
  UI_Element_Find(itemDetailContainer, "btn-action").onclick = updateItemInfo;
  UI_Element_Find(itemDetailContainer, "btn-export").style.display = "none";
}

async function showItemsAdminTable() {
  var module = Core_State_Get("inventory", "container");
  const container = UI_Element_Find(module, "inventory-admin");
  UI_Element_Find(container, "item-detail").style.visibility = "hidden";

  const itemListContainer = UI_Element_Find(container, "item-list-container");
  itemListContainer.style.visibility = "visible";
  itemListContainer.innerHTML = "";
  const itemlist = document.createElement("div");
  itemlist.classList.add("container-column");
  itemlist.style.width = "100%";
  itemlist.style.overflow = "hidden auto";
  itemListContainer.appendChild(itemlist);
  const table = UI_Table("standard", { fixed: true });
  itemlist.appendChild(table);

  const headerRow = UI_Table_Row(table);
  headerRow.style.position = "sticky";
  headerRow.style.top = 0;

  // CREATE TABLE HEADER
  var header = Core_State_Get("inventory", "inventory-table-header");
  header.forEach((item) => {
    const cell = UI_Table_Cell(headerRow, { type: "header" });
    cell.innerHTML = item;
  });

    const data =
    (await Core_Api("Inventory_Items_Search", {
      search: {
        group: Core_State_Get("inventory", ["admin", "group"]),
        program: Core_State_Get("inventory", ["admin", "program"]),
        level: Core_State_Get("inventory", ["admin", "level"]),
      },
    })) || [];

  Core_State_Set("inventory", ["admin", "items"], data);
  Core_State_Set("inventory", ["admin", "selectedItemIndex"], null);

  if (data && data.length > 0) {
    for (let i = 0; i < data.length; i++) {
      const { item_name = "", available = "", item_code = "" ,item_group = "", unit = "", program = "", level = ""} = data[i];
      const row = UI_Table_Row(table, { selectable: true }, () =>
        itemAdminDetail(i)
      );
      const noCell = UI_Table_Cell(row);
      noCell.innerHTML = i + 1;

      var itemCodeCell = UI_Table_Cell(row);
      itemCodeCell.innerHTML = item_code;

      var itemNameCell = UI_Table_Cell(row);
      itemNameCell.innerHTML = item_name;

      var groupCell = UI_Table_Cell(row);
      groupCell.innerHTML = item_group;

      var programCell = UI_Table_Cell(row);
      programCell.innerHTML = program;

      var levelCell = UI_Table_Cell(row);
      levelCell.innerHTML = level;
    }
  } else {
    const row = UI_Table_Row(table);
    const messageCell = UI_Table_Cell(row);
    messageCell.colSpan = header.length;
    messageCell.style.height = "300px";
    messageCell.innerText = "No items";
  }
}

function itemAdminDetail(index) {
  Core_State_Set("inventory", ["admin", "selectedItemIndex"], index);
  const item = Core_State_Get("inventory", ["admin", "items"])[index];
  const { item_code, item_name, item_group, program, level } = item || {};

  var module = Core_State_Get("inventory", "container");
  const container = UI_Element_Find(module, "inventory-admin");
  const itemDetailContainer = UI_Element_Find(container, "item-detail");
  itemDetailContainer.style.visibility = "visible";
  const itemInfoContainer = UI_Element_Find(itemDetailContainer, "item-info");
  itemInfoContainer.innerHTML = "";

  [
    {
      value: item_code,
      name: "code",
    },
    {
      value: item_name,
      name: "name",
    },
    {
      value: item_group,
      name: "group",
    },
    {
      value: program,
      name: "program",
    },
    {
      value: level,
      name: "level",
    },
  ].forEach((item) => {
    Core_State_Set(
      "inventory",
      ["admin", "item-detail", item.name],
      item.value
    );
  });

  // ITEM CODE
  var code = UI_Element_Create("core/control-box-plain");
  code.innerHTML = item_code;

  codedetail = UI_Element_Create("inventory/components/item-detail", {
    label: UI_Language_String("inventory/module", "table item code"),
  });
  codedetail.appendChild(code);
  itemInfoContainer.appendChild(codedetail);

  // ITEM NAME
  var name = UI_Element_Create("core/control-edit-plain");
  name.value = item_name;
  name.onchange = (e) => {
    Core_State_Set(
      "inventory",
      ["admin", "item-detail", "name"],
      e.target.value
    );
  };
  namedetail = UI_Element_Create("inventory/components/item-detail", {
    label: UI_Language_String("inventory/module", "table item name"),
  });
  namedetail.appendChild(name);
  itemInfoContainer.appendChild(namedetail);

  // SELECT GROUP
  var group = UI_Element_Create("core/control-dropdown-plain");
  Document_Select_OptionsFromObjects(
    group,
    Core_State_Get("inventory", "groups"),
    "item_group",
    false
  );
  group.dataset.uid = "item-info-group";
  group.value = item_group;
  group.onchange = (e) => {
    Core_State_Set(
      "inventory",
      ["admin", "item-detail", "group"],
      e.target.value
    );
  };
  groupdetail = UI_Element_Create("inventory/components/item-detail", {
    label:  UI_Language_String("inventory/module", "change group"),
  });
  groupdetail.appendChild(group);
  itemInfoContainer.appendChild(groupdetail);

  // PROGRAM
  const programs = Core_State_Get("inventory", "groupPrograms");
  const programElement = UI_Element_Create("core/control-dropdown-plain");
  programElement.dataset.uid = "search-program";
  Document_Select_AddOption(
    programElement,
    UI_Language_String("inventory", "search any"),
    "Any"
  );
  Document_Select_AddOption(programElement, "---", "").disabled = true;
  Document_Select_OptionsFromObjects(programElement, programs, "name", false);
  programElement.value = program ?? "Any";

  Core_State_Set(
    "inventory",
    ["admin", "item-detail", "program"],
    programElement.value
  );
  programElement.onchange = (e) => {
    Core_State_Set(
      "inventory",
      ["admin", "item-detail", "program"],
      e.target.value
    );
    displayLevels(
      itemInfoContainer,
      ["admin", "item-detail"],
      e.target.value,
      programs
    );
  };

  const programContainer = UI_Element_Create(
    "inventory/components/item-detail",
    {
      label: UI_Language_String("inventory/module", "change program"),
    }
  );
  programContainer.appendChild(programElement);
  itemInfoContainer.appendChild(programContainer);

  // LEVELS
  const levelElement = UI_Element_Create("core/control-dropdown-plain");
  levelElement.dataset.uid = "search-levels";
  const levelContainer = UI_Element_Create("inventory/components/item-detail", {
    label:  UI_Language_String("inventory/components/module", "search level"),
  });
  levelContainer.appendChild(levelElement);
  itemInfoContainer.appendChild(levelContainer);

  displayLevels(
    levelContainer,
    ["admin", "item-detail"],
    programElement.value,
    programs
  );
  levelElement.value = level ?? "";
}

async function updateItemInfo() {
  const isAccept = await UI_Popup_Confirm(
    UI_Language_String("inventory/module", "update information"),
    UI_Language_String("inventory/module", "update confirm text"),
  );
  if (!isAccept) return;

  const itemDetail = Core_State_Get("inventory", ["admin", "item-detail"]);

  res = await Core_Api("Inventory_Item_Update", {
    item: {
      item_code: itemDetail.code,
      item_group: itemDetail.group,
      item_name: itemDetail.name,
      program: itemDetail.program,
      level: itemDetail.level,
    },
  });
  if (res) {
    showAlertMessage("Update Item SuccessFuly !");
    showItemsAdminTable();
    var module = Core_State_Get("inventory", "container");
    const container = UI_Element_Find(module, "inventory-admin");
    UI_Element_Find(container, "item-detail").style.visibility = "visible";
  } else {
    showAlertMessage("Operation error !!!");
  }
}

