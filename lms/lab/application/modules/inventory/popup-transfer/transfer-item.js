let dataTable;
let searchData;

function resetTransferState() {
  searchData = null;
  dataTable = null;
}

async function openPopupTransfer() {
  var header = [
    UI_Language_String("inventory/module", "table no"),
    UI_Language_String("inventory/components/module", "search centers"),
    UI_Language_String("inventory/module", "table in store"),
    UI_Language_String("inventory/module", "table need"),
  ];
  const popupContent = UI_Element_Create("inventory/popup-transfer/popup", {
    title: UI_Language_String("inventory/module", "transfer-popup-title"),
    "btn-request": UI_Language_String("inventory/module", "button-request"),
  });

  const edtCenter = UI_Element_Find(popupContent, "edt-center");
  edtCenter.placeholder = "Search center";
  edtCenter.onchange = onSearchCenter;
  const btnRequest = UI_Element_Find(popupContent, "btn-request");
  btnRequest.onclick = onRequest;
  btnRequest.style.width = "auto";
  btnRequest.style.minWidth = "96px";

  // CREATE RESULT TABLE
  const tableContainer = UI_Element_Find(popupContent, "table-result");
  dataTable = UI_Table("standard", { fixed: true });
  tableContainer.appendChild(dataTable);

  const headerRow = UI_Table_Row(dataTable);
  header.forEach((item) => {
    const cell = UI_Table_Cell(headerRow, { type: "header" });
    cell.innerHTML = item;
  });

  updateTableData("");

  const popup = await UI_Popup_Create(
    {
      content: popupContent,
    },
    [],
    "",
    {
      open: true,
      escape: async () => {
        resetTransferState();
        UI_Popup_Close(popup);
      },
    }
  );
}

async function onSearchCenter(e) {
  searchData = [];
  const { items, selectedItemIdx, center } = Core_State_Get("inventory", [
    "warehouse",
  ]);
  const centers = Core_Config("centers");
  const searchValue = removeAccents(e.currentTarget.value.toLowerCase());
  if (!searchValue) return;
  const searchResult = Object.keys(centers).filter(
    (key) =>
      centers[key]["name"].toLowerCase().includes(searchValue) && key !== center
  );

  if (searchResult && searchResult.length > 0) {
    searchData = await Core_Api("Inventory_Load_byCenter", {
      centers: searchResult,
      options: {
        item_code: items[selectedItemIdx]["item_code"],
      },
    });
  }
  updateTableData(searchData);
}

function updateTableData(data) {
  const rows = dataTable.rows.length;
  for (let i = 1; i < rows; i++) {
    dataTable.deleteRow(i);
  }
  if (data && data.length > 0) {
    const centers = Core_Config("centers");
    data.forEach((item, index) => {
      const { available, center_code } = item;
      if (available > 0) {
        const row = UI_Table_Row(dataTable);
        const noCell = UI_Table_Cell(row);
        noCell.innerHTML = index + 1;
        const nameCell = UI_Table_Cell(row);
        nameCell.innerHTML = centers[center_code]["name"] || "";
        const instoreCell = UI_Table_Cell(row);
        instoreCell.innerHTML = available;
        const needCell = UI_Table_Cell(row);
        const edtNeedNumber = UI_Element_Create("core/control-edit-plain");
        edtNeedNumber.style.width = "80px";
        edtNeedNumber.type = "number";
        edtNeedNumber.min = 0;
        edtNeedNumber.onchange = (e) => onChangeQuantity(index, e);
        needCell.appendChild(edtNeedNumber);
      }
    });
  } else {
    const row = UI_Table_Row(dataTable);
    const messageCell = UI_Table_Cell(row);
    messageCell.colSpan = 4;
    messageCell.innerText = UI_Language_String(
      "inventory/module",
      "table no item"
    );
  }
}

function onChangeQuantity(index, e) {
  const { available } = searchData[index];
  const quantity = e.target.value;
  if (quantity > available) {
    showAlertMessage(
      UI_Language_String("inventory/module", "alert not enough quantity"),
      "error"
    );
  }
  searchData[index]["needNum"] = Number(quantity);
}

async function onRequest() {
  if (!searchData || searchData.length == 0) {
    showAlertMessage(
      UI_Language_String("inventory/module", "alert search center"),
      "info"
    );
    return;
  }

  // verfiy the amount of quantity for each center
  for (let item of searchData) {
    const { needNum, available } = item;
    if (needNum > available) {
      showAlertMessage(
        UI_Language_String("inventory/module", "alert not enough quantity"),
        "error"
      );
      return;
    }
  }

  const isAccept = await UI_Popup_Confirm(
    UI_Language_String("inventory/module", "popup confirm request"),
    UI_Language_String("inventory/module", "popup confirm text request")
  );
  if (!isAccept) return;

  const items = searchData.filter((item) => item["needNum"]);
  if (items) {
    const { center } = Core_State_Get("inventory", "warehouse");
    const data = items.map(({ item_code, center_code, needNum }) => ({
      item_code,
      from_center: center,
      to_center: center_code,
      quantity: needNum,
    }));

    const res = await Core_Api("Inventory_Create_Transfer_Requests", {
      data,
    });

    if (res && res.length) {
      showAlertMessage(
        UI_Language_String("inventory/module", "alert request") +
          " " +
          UI_Language_String("inventory/module", "alert success")
      );
    }
  }
}
