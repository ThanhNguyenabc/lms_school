function Inventory_Warehouse_Waiting() {
  Core_State_Set("inventory", "inventory-table-header", [
    UI_Language_String("inventory/module", "table no"),
    UI_Language_String("inventory/components/module", "search centers"),
    UI_Language_String("inventory/module", "table item code"),
    UI_Language_String("inventory/module", "table item name"),
    UI_Language_String("inventory/module", "table in store"),
    UI_Language_String("inventory/module", "table request"),
    UI_Language_String("inventory/module", "table status"),
  ]);
  Core_State_Set("inventory", ["warehouse", "center-options"], "to");

  showTransferTable();

  //HIDE DETAIL
  if (typeof itemDetailContainer !== "undefined")
    itemDetailContainer.style.display = "none";
}

async function AcceptRequest(e) {
  await showPopupConfirm(
    UI_Language_String("inventory/module", "popup confirm", {
      text: UI_Language_String("inventory/module", "button-accept"),
    }),
    UI_Language_String("inventory/module", "popup confirm text", {
      text: UI_Language_String("inventory/module", "button-accept"),
    }),
    {
      id: e.target.dataset.id,
      status: "accepted",
    }
  );
}

async function RejectRequest(e) {
  await showPopupConfirm(
    UI_Language_String("inventory/module", "popup confirm", {
      text: UI_Language_String("inventory/module", "button-reject"),
    }),
    UI_Language_String("inventory/module", "popup confirm text", {
      text: UI_Language_String("inventory/module", "button-reject"),
    }),
    {
      id: e.target.dataset.id,
      status: "rejected",
    }
  );
}
