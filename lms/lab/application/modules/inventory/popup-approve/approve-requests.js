async function openPopupRequest() {
  const popupContent = UI_Element_Create("inventory/popup-approve", {
    title: UI_Language_String("inventory/module", "approve-popup-title"),
  });
  const headers = UI_Element_Find(popupContent, "headers");
  const itemList = UI_Element_Find(popupContent, "requests");
  const table = UI_Element_Find(popupContent, "table");
  const tbody = UI_Element_Find(popupContent, "tbody");
  const approvePopup = await UI_Popup_Create(
    {
      content: popupContent,
    },
    [],
    ""
  );

  const data =
    (await Core_Api("Inventory_Get_Requests", {
      centercode: Core_State_Get("inventory", "center"),
    })) || [];

  if (data && data.length > 0) {
    headers.style.visibility = "visible";
    data.forEach((item, index) => {
      const element = createListItem(item, index);
      tbody.appendChild(element);
    });
  } else {
    itemList.innerHTML = "No item requests";
    itemList.style.marginTop = "100px";
  }
}

function createListItem(item, index) {
  const itemView = UI_Element_Create("inventory/request-list-item", {
    no: index + 1,
    id: item["id"],
    name: item["item_name"],
    itemcode: item["item_code"],
    fromcenter: item["from_center"],
    status: item["status"],
    quantity: item["quantity"],
    btnAccept: "Accept",
    btnReject: "Reject",
  });

  // const btnAccept = UI_Element_Find(itemView, "btn-accept");
  // btnAccept.onclick = onClickAccept;

  // const btnReject = UI_Element_Find(itemView, "btn-reject");
  // btnReject.onclick = onClickReject;
  return itemView;
}

async function onClickReject(event) {
  const id = event.target.parentElement["id"];
  const { error } = await onUpdateStatus(id, "rejected");

  if (error) {
    showAlertMessage(error, "error");
    return;
  }

  showAlertMessage("Reject successfully");
  const statusElement = UI_Element_Find(event.target.parentElement, "status");
  statusElement.innerHTML = "rejected";
}

async function onClickAccept(event) {
  const id = event.target.parentElement["id"];

  const { error } = await onUpdateStatus(id, "accepted");
  if (error) {
    showAlertMessage(error, "error");
    return;
  }

  showAlertMessage("Accpet successfully");
  const statusElement = UI_Element_Find(event.target.parentElement, "status");
  statusElement.innerHTML = "accepted";
}

async function onUpdateStatus(id, status) {
  return await Core_Api("Inventory_Update_Transfer_Request", {
    id,
    fields: {
      status,
    },
  });
}
