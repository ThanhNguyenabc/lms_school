async function Inventory_Warehouse() {
  const inventoryContainer = Core_State_Get("inventory", "container");

  const warehousePage = UI_Element_Find(
    inventoryContainer,
    "warehouse-container"
  );
  Core_State_Set("inventory", ["warehouse", "container"], warehousePage);

  const itemsContainer = UI_Element_Create(
    "inventory/components/inventory-list"
  );

  const itemDetailContainer = UI_Element_Create(
    "inventory/components/inventory-detail",
    {
      btnAction: UI_Language_String(
        "inventory/module",
        "button request transfer"
      ),
    }
  );

  UI_Element_Find(itemDetailContainer, "btn-action").onclick =
    openPopupTransfer;

  UI_Element_Find(itemDetailContainer, "btn-export").style.display = "none";

  let groupPrograms = Core_State_Get("inventory", "groupPrograms");

  if (!groupPrograms) {
    groupPrograms = await generateProgramGroup();
  }
  const searchElement = createSearchElement("warehouse", {
    onSearch: (e) => {btnClicked(e,"warehouse"); showWareHouseItems();},
    programData: groupPrograms,
  });

  const actions = UI_Element_Create("inventory/warehouse/search-actions", {
    btnHistory: UI_Language_String("inventory/module", "button history"),
    btnAccept: UI_Language_String("inventory/module", "button request"),
  });

  UI_Element_Find(actions, "btnAccept").onclick = (e) => {
    btnClicked(e,"warehouse");
    itemDetailContainer.style.display = "none";
    Inventory_Warehouse_Waiting();
  };
  UI_Element_Find(actions, "btnHistory").onclick = (e) => {
    btnClicked(e,"warehouse");
    itemDetailContainer.style.display = "none";

    Inventory_Warehouse_History();
  };

  const exportButton = UI_Element_Create("core/button-small-stretch",{text:UI_Language_String("inventory/components/module","button export")});
  exportButton.onclick = (e) => { exportExecl(e);}
  Document_Element_SetObject(exportButton, "state", "warehouse");
  
  const loadDataButton = UI_Element_Create("core/button-small-stretch",{text:UI_Language_String("inventory/components/module","button load D365")});
  loadDataButton.onclick = async (e) => {
    var centerMapping = Core_Config("centers-mapping");
    var center = UI_Element_Find(searchElement,"search-centers").value;
    var warehouseCode = centerMapping[center]["warehourse-code"];
    var div = document.createElement("div");
    div.classList.add("loader");
    div.style.cssText = " font-size: 4px; position: absolute; margin-left: 200px";
    loadDataButton.appendChild(div);
    
    var updateStatus = await Core_Api("Inventory_Create_Update_FromD365&filter=true&center="+ warehouseCode);
    if(updateStatus)
    {
      loadDataButton.removeChild(loadDataButton.firstElementChild);
      showWareHouseItems();
      showAlertMessage("Data has been updated");
    }
    else showAlertMessage("No Data has been updated!!");
  }
  // ADD ACTIONS
  const actionsContainer = UI_Element_Find(searchElement, "action-container");
  actionsContainer.appendChild(actions);
  actionsContainer.appendChild(exportButton);
  actionsContainer.appendChild(loadDataButton);

  warehousePage.appendChild(searchElement);
  warehousePage.appendChild(itemsContainer);
  warehousePage.appendChild(itemDetailContainer);
}

async function showWareHouseItems() {
  const { container, center, group, program, level } = Core_State_Get(
    "inventory",
    ["warehouse"]
  );

  Core_State_Set("inventory", "inventory-table-header",[
    UI_Language_String("inventory/module", "table no"),
    UI_Language_String("inventory/module", "table item code"),
    UI_Language_String("inventory/module", "table item name"),
    UI_Language_String("inventory/module", "table in store"),
    UI_Language_String("inventory/module", "table in order"),
  ]);
  
  UI_Element_Find(container, "item-detail").style.display = "flex";
  UI_Element_Find(container, "item-detail").style.visibility = "hidden";

  const data = await Core_Api("Inventory_Load_byCenter", {
    centers: [center],
    options: {
      group: group,
      program: program,
      level: level,
    },
  });

  Core_State_Set("inventory", ["warehouse", "items"], data);

  const itemsContainer = UI_Element_Find(container, "item-list-container");
  itemsContainer.style.visibility = "visible";
  itemsContainer.innerHTML = "";

  const table = UI_Table("standard", { fixed: true });
  itemsContainer.appendChild(table);
  const headerRow = UI_Table_Row(table);
  var header = Core_State_Get("inventory", "inventory-table-header");
  header.forEach((item) => {
    const cell = UI_Table_Cell(headerRow, { type: "header" });
    cell.innerHTML = item;
  });

  if (data && data.length > 0) {
    for (let i = 0; i < data.length; i++) {
      const { item_name = "", available = "", item_code, on_order } = data[i];
      const row = UI_Table_Row(table, { selectable: true }, () => {
        onClickItem(i);
      });
      const noCell = UI_Table_Cell(row);
      noCell.innerHTML = i + 1;
      const itemCodeCell = UI_Table_Cell(row);
      itemCodeCell.innerHTML = item_code;
      const bookNameCell = UI_Table_Cell(row);
      bookNameCell.innerHTML = item_name;
      const instoreCell = UI_Table_Cell(row);
      instoreCell.innerHTML = available;
      const inOrderCell = UI_Table_Cell(row);
      inOrderCell.innerHTML = on_order;
    }
  } else {
    const row = UI_Table_Row(table);
    const messageCell = UI_Table_Cell(row);
    messageCell.colSpan = 5;
    messageCell.style.height = "300px";
    messageCell.innerText =  UI_Language_String("inventory/module", "table no item");
  }
}

function onClickItem(index) {
  Core_State_Set("inventory", ["warehouse", "selectedItemIdx"], index);
  const item = Core_State_Get("inventory", ["warehouse", "items"])[index];
  const { available, item_name, item_code, on_order } = item || {};

  const warehouseContainer = Core_State_Get("inventory", [
    "warehouse",
    "container",
  ]);

  const detailContainer = UI_Element_Find(warehouseContainer, "item-detail");
  detailContainer.style.visibility = "visible";

  const itemInfoContainer = UI_Element_Find(detailContainer, "item-info");
  itemInfoContainer.innerHTML = "";

  [
    {
      title: UI_Language_String("inventory/module", "table item code"),
      value: item_code,
    },
    {
      title: UI_Language_String("inventory/module", "table item name"),
      value: item_name,
    },
    {
      title: UI_Language_String("inventory/module", "table in store"),
      value: available || "_",
    },
    {
      title: UI_Language_String("inventory/module", "table in order"),
      value: on_order || "_",
    },
  ].forEach((item) => {
    const nameItem = UI_Element_Create(
      "inventory/warehouse/inventory-list-item",
      {
        label: item.title,
        desc: item.value,
      }
    );
    itemInfoContainer.appendChild(nameItem);
  });
}
