const ALERT_TYPES = {
  success: {
    className: "alert-success",
    title: "Info",
  },
  error: {
    className: "alert-error",
    title: "Error",
  },
  info: {
    className: "alert-info",
    title: "Info",
  },
};
let alert;

async function Inventory_OnLoad(module, data) {
  // INITIAL DATA
  Core_State_Set("inventory", "groups", data);
  generateProgramGroup().then((res) =>
    Core_State_Set("inventory", "groupPrograms", res)
  );

  // INIT PAGES
  const warehousePage = UI_Element_Create("inventory/warehouse/warehouse");
  const adminPage = UI_Element_Create("inventory/admin/admin");
  const assignmentPage = UI_Element_Create("inventory/assignment/assignment");

  const container = UI_Element_Find(module, "inventory-container");
  Core_State_Set("inventory", "container", container);
  container.appendChild(warehousePage);
  container.appendChild(adminPage);
  container.appendChild(assignmentPage);

  const pages = [
    {
      key: "warehouse",
      page: warehousePage,
      isLoaded: false,
    },
    {
      key: "assignment",
      page: assignmentPage,
      isLoaded: false,
    },
    {
      key: "admin",
      page: adminPage,
      isLoaded: false,
    },
  ];

  // CREATE MENU HEADER
  var menus = Core_Data_Page("inventory/menus");

  var items = {};
  for (const key in menus) {
    items[key] = {};
    items[key]["text"] = UI_Language_Object(menus[key]);
    items[key]["icons"] = [];
    items[key]["onclick"] = () => {
      pages.forEach((item) => {
        if (item["key"] == key) {
          item["page"].style.display = "flex";
          if (!item["isLoaded"]) {
            Safe_Function(
              "Inventory_" + String_Capitalize_Initial(key),
              function () {}
            )();
            item["isLoaded"] = true;
          }
        } else {
          item["page"].style.display = "none";
        }
      });
    };
  }

  var header = UI_Header("inventory-menu", items, {
    selectfirst: true,
    css: "color-noted",
    template: "standard",
  });

  UI_Element_Find(module, "module-header").appendChild(header);

  // INIT ALERT
  alert = UI_Element_Create("inventory/components/inventory-alert");
  document.body.appendChild(alert);
}

async function Inventory_OnUnload() {
  document.body.removeChild(alert);
  alert = null;
}

function showAlertMessage(message, type = "success") {
  const { className, title } = ALERT_TYPES[type];
  alert.innerHTML = `<strong>${UI_Language_String(
    "inventory/module",
    "alert " + type
  )}! ${message}</strong>`;
  alert.classList.add(className);
  alert.style.opacity = 1;

  setTimeout(() => {
    alert.classList.remove(className);
    alert.style.opacity = 0;
  }, 6000);
}

async function UpdateRequestStatus(id, status) {
  const { error, data } = await Core_Api("Inventory_Update_Transfer_Request", {
    id: id,
    fields: {
      status: status,
    },
  });

  if (data) {
    showAlertMessage("Update Status Successfuly");
    showTransferTable();
  }

  return { error, data };
}

function removeAccents(str) {
  return str
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d")
    .replace(/Đ/g, "D");
}

function createSearchElement(
  stateKey,
  {
    btnTitle = "",
    onSearch,
    showDate = false,
    showCenter = true,
    programData = null,
  }
) {
  const searchContainer = UI_Element_Create(
    "inventory/components/inventory-search",
    {
      "btn-search":
        btnTitle || UI_Language_String("inventory", "button search"),
    }
  );

  // CENTERS
  const centersElement = UI_Element_Find(searchContainer, "search-centers");
  if (showCenter) {
    const centers = Core_Config("centers");
    Document_Select_OptionsFromObjects(centersElement, centers, "name", false);
    Core_State_Set("inventory", [stateKey, "center"], centersElement.value);
    centersElement.onchange = (e) => {
      Core_State_Set("inventory", [stateKey, "center"], e.target.value);
    };
  } else {
    centersElement.parentElement.style.display = "none";
  }

  const programElement = UI_Element_Find(searchContainer, "search-program");

  // PROGRAM
  const programs = programData || Core_Config("programs");
  Document_Select_AddOption(
    programElement,
    UI_Language_String("inventory", "search any"),
    ""
  );
  Document_Select_AddOption(programElement, "---", "").disabled = true;

  Document_Select_OptionsFromObjects(programElement, programs, "name", false);
  Core_State_Set("inventory", [stateKey, "program"], programElement.value);
  programElement.onchange = (e) => {
    const value = e.target.value;
    Core_State_Set("inventory", [stateKey, "program"], value);
    displayLevels(searchContainer, [stateKey], value, programs);
  };

  // LEVELS
  displayLevels(searchContainer, [stateKey], programElement.value, programs);

  // ITEM GROUP
  const groups = Core_State_Get("inventory", "groups");
  const groupsElement = UI_Element_Find(searchContainer, "search-groups");
  Document_Select_AddOption(
    groupsElement,
    UI_Language_String("inventory", "search any"),
    ""
  );
  Document_Select_AddOption(groupsElement, "---", "").disabled = true;
  Document_Select_OptionsFromObjects(
    groupsElement,
    groups,
    "item_group",
    false
  );
  Core_State_Set("inventory", [stateKey, "group"], groupsElement.value);
  groupsElement.onchange = (e) =>
    Core_State_Set("inventory", [stateKey, "group"], e.target.value);

  // DATE
  if (showDate) {
    UI_Element_Find(searchContainer, "date-container").style.display = "flex";
    const inputDate = UI_Element_Find(searchContainer, "search-datefrom");
    inputDate.value = Date_To_Input(Date_Now());
    Core_State_Set("inventory", [stateKey, "date"], inputDate.value);
    inputDate.onchange = (event) =>
      Core_State_Set(
        "inventory",
        [stateKey, "date"],
        event.currentTarget.value
      );

    // DATE MODE
    const selectDateMode = UI_Element_Find(searchContainer, "search-datemode");
    UI_Select_FromDatapage(selectDateMode, "courses/search-date-modes");
    selectDateMode.value = "ends-after";
    Core_State_Set("inventory", [stateKey, "date_mode"], selectDateMode.value);
    selectDateMode.onchange = (event) => {
      const value = event.currentTarget.value;
      inputDate.style.display = value == "ongoing" ? "none" : "flex";
      Core_State_Set("inventory", [stateKey, "date_mode"], value);
    };
  }

  UI_Element_Find(searchContainer, "btnSearch").onclick = onSearch;

  return searchContainer;
}

function displayLevels(parent, state = [], programKey, programData = null) {
  const levelsElement = UI_Element_Find(parent, "search-levels");
  Document_Select_Clear(levelsElement);
  Document_Select_AddOption(
    levelsElement,
    UI_Language_String("inventory", "search any"),
    ""
  );
  Document_Select_AddOption(levelsElement, "---", "-").disabled = true;
  Core_State_Set("inventory", [...state, "level"], levelsElement.value);
  levelsElement.onchange = (e) => {
    let value = e.target.value;
    if (value.includes("-CAM")) {
      value = value.replaceAll("-CAM", "");
    }
    Core_State_Set("inventory", [...state, "level"], value);
  };

  if (programKey && programKey.length > 0) {
    const levelStr =
      programData?.[programKey]?.["levels"] ||
      Core_Config(["programs", programKey, "levels"]);

    const levels =
      levelStr?.split(",")?.reduce(
        (result, item) => ({
          ...result,
          [item]: {
            name: item,
          },
        }),
        {}
      ) || {};
    Document_Select_OptionsFromObjects(levelsElement, levels, "name", false);
  }

  return levelsElement;
}

async function generateProgramGroup() {
  const programs = Core_Config("programs");

  const programGroup = Object.keys(programs)?.reduce((result, key) => {
    const program = programs[key]["program"];
    let levels = programs[key]["levels"] || "";
    if (result[program]) return result;
    levels = levels.replaceAll("-CAM", "");
    return {
      ...result,
      [program]: {
        name: program,
        program,
        levels,
      },
    };
  }, {});

  return programGroup;
}

async function exportExecl(e) {
  const XLSX = await import(
    "https://cdn.sheetjs.com/xlsx-0.20.0/package/xlsx.mjs"
  );
  const state = Document_Element_GetObject(e.target, "state");
  const items = Core_State_Get("inventory", [state, "items"]) ?? [];
  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.json_to_sheet(items);
  XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
  XLSX.writeFile(wb, "LMS_data_export.xlsx");
}

function btnClicked(e, state) {
  var elementBefore = Core_State_Get("inventory", [state, "search-selected"]);
  if (typeof elementBefore !== "undefined") {
    elementBefore.style.backgroundColor = "var(--color-dark)";
    elementBefore.style.color = null;
  }

  var element = e.target;
  Core_State_Set("inventory", [state, "search-selected"], element);
  element.style.backgroundColor = "var(--color-accented)";
  element.style.color = "white";
}

function reloadGroupsData() {
  const groups = Core_State_Get("inventory", "groups");
  var selectgroups = document.querySelectorAll("[data-uid='search-groups']");
  console.log(selectgroups);
  for (let index = 0; index < selectgroups.length; index++) {
    var element = selectgroups[index];
    Document_Select_Clear(element);
    Document_Select_AddOption(
      element,
      UI_Language_String("inventory", "search any"),
      ""
    );
    Document_Select_AddOption(element, "---", "").disabled = true;
    Document_Select_OptionsFromObjects(element, groups, "item_group", false);
    //CHECK AND RESET SELECTED VALUE
    let check = element.parentElement.parentElement.parentElement.parentElement;
    switch (check.dataset.uid) {
      case "warehouse-container":
        element.value = Core_State_Get("inventory", ["warehouse", "group"], "");
        break;
      case "inventory-admin":
        element.value = Core_State_Get("inventory", ["admin", "group"], "");
        break;
      case "assignment-container":
        element.value = Core_State_Get(
          "inventory",
          ["assignment", "group"],
          ""
        );
        break;
      default:
        break;
    }
  }
}

function createLoading() {
  const loading = document.createElement("div");
  loading.classList.add("loader");
  loading.style = "font-size: 4px; margin-left: 25px; margin-right: 10px";
  return loading;
}

async function runWithLoading(button, callback) {
  const loading = createLoading();
  let state = "";
  if (state == "requesting") return;

  let data;
  try {
    // Show loading
    button.classList.add("style-disabled");
    button.appendChild(loading);

    state = "requesting";

    if (callback) {
      data = await callback();
    }
    // Remove loading
    state = "done";
    button.classList.remove("style-disabled");
    button.removeChild(loading);

    return data;
  } catch (error) {
    button.removeChild(loading);
    state = "done";
  }

  return null;
}
