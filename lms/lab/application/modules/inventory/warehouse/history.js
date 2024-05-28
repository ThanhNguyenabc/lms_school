function Inventory_Warehouse_History() {
  Core_State_Set("inventory", "inventory-table-header", [
    UI_Language_String("inventory/module", "table no"),
    UI_Language_String("inventory/components/module", "search centers"),
    UI_Language_String("inventory/module", "table item code"),
    UI_Language_String("inventory/module", "table item name"),
    UI_Language_String("inventory/module", "table in store"),
    UI_Language_String("inventory/module", "table request"),
    UI_Language_String("inventory/module", "table status"),
  ]);
  Core_State_Set("inventory", ["warehouse", "center-options"], "from");
  //HIDE DETAIL
  if (typeof itemDetailContainer !== "undefined")
    itemDetailContainer.style.display = "none";
  showTransferTable();
}

async function showTransferTable() {
  var module = Core_State_Get("inventory", "container");
  var itemListContainer = UI_Element_Find(module, "item-list-container");
  itemListContainer.style.visibility = "visible";
  itemListContainer.style.display = "flex";
  itemListContainer.innerHTML = "";
  const table = UI_Table("standard", { fixed: true });
  itemListContainer.appendChild(table);
  const headerRow = UI_Table_Row(table);

  // CREATE TABLE HEADER
  var header = Core_State_Get("inventory", "inventory-table-header");
  header.forEach((item) => {
    const cell = UI_Table_Cell(headerRow, { type: "header" });
    cell.innerHTML = item;
  });

  var options = Core_State_Get("inventory", ["warehouse", "center-options"]);
  const data =
    (await Core_Api("Inventory_Request_Search", {
      center_code: Core_State_Get("inventory", ["warehouse", "center"]),
      item_group: Core_State_Get("inventory", ["warehouse", "group"]),
      program: Core_State_Get("inventory", ["warehouse", "program"]),
      level: Core_State_Get("inventory", ["warehouse", "level"]),
      options: options,
    })) || [];

  if (data && data.length > 0) {
    for (let i = 0; i < data.length; i++) {
      //CHECK IS HISTORY OR REQUEST PENDING
      if (
        options == "from" ||
        (options == "to" && data[i].status == "pending")
      ) {
        const {
          id = "",
          item_name = "",
          available = "",
          item_code = "",
          from_center = "",
          to_center = "",
          status = "",
          quantity = "",
        } = data[i];
        const row = UI_Table_Row(table);
        const noCell = UI_Table_Cell(row);
        noCell.innerHTML = i + 1;
        var centerCell = UI_Table_Cell(row);
        if (options == "from") {
          centerCell.innerHTML = to_center;
        } else centerCell.innerHTML = from_center;
        var itemCodeCell = UI_Table_Cell(row);
        itemCodeCell.innerHTML = item_code;
        var itemNameCell = UI_Table_Cell(row);
        itemNameCell.innerHTML = item_name;
        var instore = UI_Table_Cell(row);
        instore.innerHTML = available;
        var request = UI_Table_Cell(row);
        request.innerHTML = quantity;
        var statusCell = UI_Table_Cell(row);
        var statusContainer = UI_Element_Create(
          "inventory/components/request-status"
        );
        if (status == "pending") {
          if (options == "from") {
            var textStatus = UI_Element_Create("core/text-caption-medium", {
              text: "Requesting",
            });
            statusContainer.appendChild(textStatus);
            var btnCancel = UI_Element_Create("core/button-small-stretch", {
              text: UI_Language_String("inventory/module", "button-cancel"),
            });
            btnCancel.dataset.id = id;
            btnCancel.onclick = CancelRequest;
            statusContainer.appendChild(btnCancel);
            statusCell.appendChild(statusContainer);
          } else {
            var btnAccept = UI_Element_Create("core/button-small-stretch", {
              text: UI_Language_String("inventory/module", "button-accept"),
            });
            btnAccept.dataset.id = id;
            btnAccept.dataset.quantity = quantity;
            btnAccept.onclick = AcceptRequest;
            statusContainer.appendChild(btnAccept);
            var btnReject = UI_Element_Create("core/button-small-stretch", {
              text: UI_Language_String("inventory/module", "button-reject"),
            });
            btnReject.dataset.id = id;
            btnReject.onclick = RejectRequest;
            statusContainer.appendChild(btnReject);
            statusCell.appendChild(statusContainer);
          }
        } else statusCell.innerHTML = status;
      }
    }
  } else {
    const row = UI_Table_Row(table);
    const messageCell = UI_Table_Cell(row);
    messageCell.colSpan = header.length;
    messageCell.style.height = "300px";
    messageCell.innerText = UI_Language_String(
      "inventory/module",
      "table no item"
    );
  }
}

async function CancelRequest(e) {
  Core_State_Set("inventory", ["warehouse", "status-update"], "canceled");
  await showPopupConfirm(
    UI_Language_String("inventory/module", "popup confirm", {
      text: UI_Language_String("inventory/module", "button-cancel"),
    }),
    UI_Language_String("inventory/module", "popup confirm text", {
      text: UI_Language_String("inventory/module", "button-cancel"),
    }),
    {
      id: e.target.dataset.id,
      status: "canceled",
    }
  );
}
