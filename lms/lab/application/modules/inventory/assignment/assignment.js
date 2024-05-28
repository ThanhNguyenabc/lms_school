let previousCourseItem;

async function Inventory_Assignment() {
  const inventoryContainer = Core_State_Get("inventory", "container");

  const assignmentPage = UI_Element_Find(
    inventoryContainer,
    "assignment-container"
  );
  Core_State_Set("inventory", ["assignment", "container"], assignmentPage);

  // ADD SEARCH VIEW
  const searchElement = createSearchElement("assignment", {
    onSearch: (e) => {
      btnClicked(e, "assignment");
      onAssign();
    },
    showDate: true,
    btnTitle: UI_Language_String("inventory/module", "button-assign"),
  });
  const btnTakeback = UI_Element_Create("core/button-small-stretch", {
    text: UI_Language_String("inventory/module", "button take back"),
  });
  btnTakeback.onclick = (e) => {
    btnClicked(e, "assignment");
    onTakeBack();
  };
  UI_Element_Find(searchElement, "action-container").appendChild(btnTakeback);

  // ADD LIST VIEW
  const courseListContainer = UI_Element_Create(
    "inventory/components/inventory-list"
  );
  courseListContainer.dataset.uid = "course-list-container";
  courseListContainer.style.width = "300px";
  courseListContainer.style.flex = "";

  const itemsListContainer = UI_Element_Create(
    "inventory/components/inventory-list"
  );

  const detailContainer = UI_Element_Create(
    "inventory/components/inventory-detail",
    {
      btnAction: UI_Language_String("inventory/module", "button save"),
    }
  );
  detailContainer.style.flex = "1";
  UI_Element_Find(detailContainer, "btn-action").onclick = onSaveData;

  assignmentPage.appendChild(searchElement);
  assignmentPage.appendChild(courseListContainer);
  assignmentPage.appendChild(itemsListContainer);
  assignmentPage.appendChild(detailContainer);
}

async function onTakeBack() {
  Core_State_Set("inventory", ["assignment", "action"], "take-back");
  clearUI();
  const courses = await fetchCourses();
  onDisplayCourses(courses, {
    onClickCourse: async () => {
      const data = await fetchUsersAndItems().then((res) => {
        const assignedUser = res["items_users"]?.reduce((result, item) => {
          const key = `${item["user_id"]}`;
          if (result[key]) {
            result[key] = {
              ...result[key],
              [item["item_code"]]: item,
            };
          } else {
            result[key] = {
              [item["item_code"]]: item,
            };
          }

          return result;
        }, {});
        return {
          items: res["items"],
          users: res["users"],
          assignedUser: assignedUser,
        };
      });
      Core_State_Set("inventory", ["assignment", "items_users"], data);
      showStudentsForReturning();
    },
  });
}

async function onAssign() {
  Core_State_Set("inventory", ["assignment", "action"], "assign");
  clearUI();
  const courses = await fetchCourses();
  onDisplayCourses(courses, {
    onClickCourse: async () => {
      const response = await fetchUsersAndItems();

      const assignedUser = response["items_users"]?.reduce((result, item) => {
        const key = `${item["item_code"]}-${item["user_id"]}`;
        result[key] = item;
        return result;
      }, {});

      const data = {
        items: response["items"],
        users: response["users"],
        assignedUser: assignedUser,
      };
      Core_State_Set("inventory", ["assignment", "items_users"], data);
      showItemsForAssigning();
    },
  });
}

async function onSaveData(e) {
  let {
    action,
    selectItemIdx,
    selectStudents,
    center,
    items_users,
    takebackItems,
    takebackStudent,
  } = Core_State_Get("inventory", ["assignment"]);

  switch (action) {
    case "take-back": {
      if (!takebackStudent || !takebackItems || takebackItems.length == 0) {
        showAlertMessage(
          UI_Language_String("inventory/module", "alert message please", {
            text: UI_Language_String("inventory/module", "table take back"),
          }),
          "info"
        );
        return;
      }

      const isAccept = await UI_Popup_Confirm(
        UI_Language_String("inventory/module", "popup confirm", {
          text: UI_Language_String("inventory/module", "popup take back"),
        }),
        UI_Language_String("inventory/module", "popup confirm text", {
          text: UI_Language_String("inventory/module", "popup take back"),
        })
      );
      if (!isAccept) return;

      const { data, error } = await Core_Api("Inventory_TakeBack", {
        item_codes: takebackItems,
        userid: takebackStudent["id"],
        center_code: center,
      });

      if (error) {
        showAlertMessage(error, "error");
      } else {
        showAlertMessage(
          UI_Language_String("inventory/module", "table take back") +
            " " +
            UI_Language_String("inventory/module", "alert success")
        );
      }
      takebackItems.forEach((item) => {
        delete items_users["assignedUser"][takebackStudent["id"]][item];
      });
      Core_State_Set("inventory", ["assignment", "items_users"], items_users);
      showItemsForReturning();
      return;
    }
    case "assign": {
      if (!selectItemIdx || !selectStudents || selectStudents.length == 0) {
        showAlertMessage(
          UI_Language_String("inventory/module", "alert message please", {
            text: UI_Language_String("inventory/module", "table assign"),
          }),
          "info"
        );
        return;
      }

      const currentItem = items_users["items"]?.[selectItemIdx];

      const { popup, continueBtn, messageText } = await showPopupConfirmX(
        UI_Language_String("inventory/module", "table assign"),
        UI_Language_String("inventory/module", "popup confirm text", {
          text: UI_Language_String("inventory/module", "popup assign"),
        }),
        {
          action: async () => {
            return await Core_Api("Inventory_Assign_ItemStudents", {
              item_code: currentItem?.["item_code"],
              users: selectStudents.map((userId) => ({
                id: userId,
                isAssigned: true,
              })),
              center_code: center,
            });
          },
          onFailure: (error) => {
            console.log(error);
            return error;
          },
          onSuccess: (data) => {
            var {
              newQuantity,
              assignedStudents = [],
              unassignedStudents = [],
              message,
            } = data;

            if (assignedStudents.length > 0) {
              selectStudents.forEach((userId) => {
                if (unassignedStudents.indexOf(userId) < 0) {
                  items_users["assignedUser"][
                    `${currentItem["item_code"]}-${userId}`
                  ] = {
                    user_id: userId,
                    item_code: currentItem["item_code"],
                  };
                }
              });

              currentItem["available"] = newQuantity;
              items_users["items"][selectItemIdx] = currentItem;

              Core_State_Set(
                "inventory",
                ["assignment", "items_users"],
                items_users
              );

              showItemsForAssigning();
              showStudentsForAssigning();

              if (unassignedStudents.length == 0) {
                showAlertMessage(
                  UI_Language_String("inventory/module", "table assign") +
                    " " +
                    UI_Language_String("inventory/module", "alert success")
                );
                UI_Popup_Close(popup);
              } else {
                const users = Core_State_Get("inventory", [
                  "assignment",
                  "items_users",
                  "users",
                ]);

                const failureUsers = users.reduce((result, item) => {
                  if (unassignedStudents.includes(item.id)) result.push(item);
                  return result;
                }, []);

                let userStr = failureUsers
                  .map(
                    ({ id, firstname, lastname }) =>
                      `<li>[${id} - ${firstname}${lastname}]</li>`
                  )
                  .join(" ");

                messageText.innerHTML = `Having some errors with these students: <br/><ul style="text-align:left">${userStr}</ul>`;
                continueBtn.innerText = "Try Again";
                selectStudents = unassignedStudents;
              }
              return;
            }

            // Can not assgin to any student
            if (unassignedStudents.length > 0 && assignedStudents.length == 0) {
              messageText.innerHTML = `All students can not be assigned: <br/><p style="text-align:left">Error: ${message}</p>`;
              continueBtn.innerText = "Try Again";
              return;
            }
          },
        }
      );
      return;
    }
  }
}

function clearUI() {
  const { container } = Core_State_Get("inventory", ["assignment"]);
  UI_Element_Find(container, "item-list-container").style.visibility = "hidden";
  const detailContainer = UI_Element_Find(container, "item-detail");
  detailContainer.style.visibility = "hidden";
  UI_Element_Find(detailContainer, "btn-action").classList.remove(
    "style-disabled"
  );
}

async function fetchCourses() {
  const { center, level, program, date, date_mode } = Core_State_Get(
    "inventory",
    ["assignment"]
  );
  const courses = await Core_Api("Courses_List", {
    centers: [center],
    level,
    program,
    order: "date_start DESC",
    status: false,
    date_mode,
    date: Date_From_Input(date),
  });
  return courses;
}

async function fetchUsersAndItems() {
  const { selectedCourse } = Core_State_Get("inventory", ["assignment"]);
  const { level, program: courseProgram, id } = selectedCourse;
  const programConfigs = Core_Config("programs");
  const group = Core_State_Get("inventory", ["assignment", "group"], "");
  const data = await Core_Api("Inventory_Assignment_Items_Users", {
    group: group,
    level,
    program: programConfigs[courseProgram]?.["program"] || "",
    course_id: id,
  });
  return data;
}

async function onDisplayCourses(courses, { onClickCourse = null } = {}) {
  const { container } = Core_State_Get("inventory", ["assignment"]);

  const courseListContainer = UI_Element_Find(
    container,
    "course-list-container"
  );
  courseListContainer.innerHTML = "";
  if (courses && courses.length > 0) {
    courseListContainer.style.visibility = "visible";
  } else {
    courseListContainer.style.visibility = "hidden";
    return;
  }

  const locale = UI_Language_Current(true);
  const centers = Core_Config("centers");
  const programs = Core_Config("programs");

  courses.forEach((course) => {
    const days = [];
    course["schedule"]?.forEach((item) => {
      const day = Date_Weekday_Name(item["day"], "short", locale);
      const time = Time_From_Minutes(item["time"]);
      days.push(day + " " + time);
    });

    const courseItem = UI_Element_Create("inventory/components/course-item", {
      name: course["name"] || "#" + course["id"],
      date: UI_Language_Date(course["date_start"], "monthdayyear-compact"),
      center: Safe_Get(
        centers,
        [course["center_id"], "name"],
        course["center_id"]
      ),
      days: days.join(", "),
      program:
        Safe_Get(programs, [course["program"], "name"], course["program"]) ||
        "no program",
      level: course["level"] || "no level",
      seats_taken: course["seats"]["taken"] || 0,
      seats_total: course["seats"]["total"] || 0,
      classes_taken: course["classes"]["taken"] || 0,
      classes_total: course["classes"]["total"] || 0,
    });
    Document_Element_SetData(courseItem, "course_id", course["id"]);
    courseItem.onclick = (event) => {
      if (previousCourseItem) {
        previousCourseItem.classList.toggle("style-outlined-accented");
      }
      courseItem.classList.toggle("style-outlined-accented");
      previousCourseItem = courseItem;
      Core_State_Set("inventory", ["assignment", "selectedCourse"], course);
      UI_Element_Find(container, "item-detail").style.visibility = "hidden";
      onClickCourse && onClickCourse(course["id"]);
    };
    courseListContainer.appendChild(courseItem);
  });
}

async function showItemsForAssigning() {
  const { container, items_users } = Core_State_Get("inventory", [
    "assignment",
  ]);
  Core_State_Set("inventory", "inventory-table-header", [
    UI_Language_String("inventory/module", "table item code"),
    UI_Language_String("inventory/module", "table item name"),
    UI_Language_String("inventory/module", "table in store"),
  ]);
  const data = items_users["items"] || [];

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
      const { item_name = "", available = "", item_code } = data[i];
      const row = UI_Table_Row(table, { selectable: true }, onItemClick);
      row.id = i;
      row.dataset.data = data[i];
      const itemCodeCell = UI_Table_Cell(row);
      itemCodeCell.innerHTML = item_code;
      const bookNameCell = UI_Table_Cell(row);
      bookNameCell.innerHTML = item_name;
      const instoreCell = UI_Table_Cell(row);
      instoreCell.innerHTML = available;
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

async function showItemsForReturning() {
  const { container, items_users, takebackStudent } = Core_State_Get(
    "inventory",
    ["assignment"]
  );
  Core_State_Set("inventory", "inventory-table-header", [
    UI_Language_String("inventory/module", "table item code"),
    UI_Language_String("inventory/module", "table item name"),
    UI_Language_String("inventory/module", "table take back"),
  ]);
  const data = items_users["items"] || [];
  const assignedUser = items_users["assignedUser"];

  const detailContainer = UI_Element_Find(container, "item-detail");
  const actionContainer = UI_Element_Find(detailContainer, "actions-detail");
  const inforElement = UI_Element_Find(detailContainer, "item-info");

  detailContainer.style.visibility = "visible";
  inforElement.innerHTML = "";

  const table = UI_Table("standard", { fixed: true });
  inforElement.appendChild(table);

  const headerRow = UI_Table_Row(table);
  var header = Core_State_Get("inventory", "inventory-table-header");
  header.forEach((item) => {
    const cell = UI_Table_Cell(headerRow, { type: "header" });
    cell.innerHTML = item;
  });

  const selectedItems = [];
  if (takebackStudent && data && data.length > 0) {
    for (let i = 0; i < data.length; i++) {
      const { item_name = "", item_code } = data[i];
      if (assignedUser[`${takebackStudent["id"]}`]?.[item_code]) {
        selectedItems.push(item_code);
        const row = UI_Table_Row(table, { selectable: false });
        row.id = item_code;
        row.dataset.data = data[i];
        const itemCodeCell = UI_Table_Cell(row);
        itemCodeCell.innerHTML = item_code;
        const bookNameCell = UI_Table_Cell(row);
        bookNameCell.innerHTML = item_name;
        const checkBoxCell = UI_Table_Cell(row);
        const checkbox = UI_Element_Create(
          "inventory/components/input-checkbox"
        );
        checkbox.onchange = (event) => {
          const value = event.target.checked;
          const itemCode = event.target.parentElement.parentElement.id;
          let selectItems =
            Core_State_Get("inventory", ["assignment", "takebackItems"]) || [];
          if (value) {
            selectItems.push(itemCode);
          } else {
            selectItems = selectItems.filter((item) => item != itemCode);
          }
          Core_State_Set(
            "inventory",
            ["assignment", "takebackItems"],
            selectItems
          );
        };
        checkBoxCell.appendChild(checkbox);
      }
    }
  }

  if (selectedItems.length == 0) {
    const row = UI_Table_Row(table);
    const messageCell = UI_Table_Cell(row);
    messageCell.colSpan = header.length;
    messageCell.style.height = "300px";
    messageCell.innerText = UI_Language_String(
      "inventory/module",
      "table no item"
    );

    // hide buttons
    actionContainer.style.display = "none";
  } else {
    // show buttons
    actionContainer.style.display = "flex";
  }
}

function onItemClick(event) {
  const idx = event.target.parentElement.id;
  Core_State_Set("inventory", ["assignment", "selectItemIdx"], idx);

  showStudentsForAssigning();
}

async function showStudentsForReturning() {
  const { container, items_users } = Core_State_Get("inventory", [
    "assignment",
  ]);
  const students = items_users["users"] || [];

  const itemListContainer = UI_Element_Find(container, "item-list-container");
  itemListContainer.innerHTML = "";
  itemListContainer.style.visibility = "visible";

  const table = createStudentTable(students, {
    onClickRow: (student, index) => {
      Core_State_Set("inventory", ["assignment", "takebackStudent"], student);
      showItemsForReturning();
    },
  });

  itemListContainer.appendChild(table);
}

async function showStudentsForAssigning() {
  Core_State_Set("inventory", ["assignment", "selectStudents"], []);

  const { container, items_users, selectItemIdx } = Core_State_Get(
    "inventory",
    ["assignment"]
  );
  const currentItem = items_users["items"][selectItemIdx];
  const students = items_users["users"];

  const detailContainer = UI_Element_Find(container, "item-detail");
  detailContainer.style.visibility = "visible";
  const inforElement = UI_Element_Find(detailContainer, "item-info");
  inforElement.innerHTML = "";

  const table = createStudentTable(students, {
    showCheckBox: true,
    onHandleCheckBox: (checkbox, item) => {
      if (
        items_users["assignedUser"]?.[
          `${currentItem["item_code"]}-${item["id"]}`
        ]
      ) {
        checkbox.checked = true;
        checkbox.disabled = true;
      }
      if (currentItem["available"] == 0) checkbox.disabled = true;
    },
  });
  inforElement.appendChild(table);

  const actionContainer = UI_Element_Find(detailContainer, "actions-detail");
  actionContainer.style.display =
    students && students.length > 0 ? "flex" : "none";

  if (currentItem["available"] == 0) {
    UI_Element_Find(actionContainer, "btn-action").classList.add(
      "style-disabled"
    );
  } else {
    UI_Element_Find(actionContainer, "btn-action").classList.remove(
      "style-disabled"
    );
  }
}

function createStudentTable(
  students,
  { showCheckBox = false, onHandleCheckBox, onClickRow } = {}
) {
  const table = UI_Table("standard", { fixed: true });

  const headerRow = UI_Table_Row(table);
  headerRow.style.position = "sticky";
  headerRow.style.top = 0;
  // CREATE TABLE HEADER
  var header = [
    UI_Language_String("inventory/module", "table no"),
    UI_Language_String("inventory/module", "table student"),
  ];
  header.forEach((item) => {
    const cell = UI_Table_Cell(headerRow, { type: "header" });
    cell.innerHTML = item;
  });

  if (showCheckBox) {
    let checkall = UI_Element_Create("inventory/components/input-checkbox");
    checkall.onchange = onCheckAllStudent;
    UI_Table_Cell(headerRow, { type: "header" }).appendChild(checkall);
  }

  if (students && students.length > 0) {
    for (let i = 0; i < students.length; i++) {
      const { firstname, lastname, id } = students[i];
      const row = UI_Table_Row(table, { selectable: onClickRow || false }, () =>
        onClickRow(students[i], i)
      );
      row.id = id;
      const noCell = UI_Table_Cell(row);
      noCell.innerHTML = i + 1;
      const nameCell = UI_Table_Cell(row);
      nameCell.innerHTML = `${firstname || ""} ${lastname || ""}`;

      if (showCheckBox) {
        const checkBoxCell = UI_Table_Cell(row);
        const checkbox = UI_Element_Create(
          "inventory/components/input-checkbox"
        );
        onHandleCheckBox && onHandleCheckBox(checkbox, students[i]);
        checkbox.onchange = onCheckStudent;
        checkBoxCell.appendChild(checkbox);
      }
    }
  } else {
    const row = UI_Table_Row(table);
    const messageCell = UI_Table_Cell(row);
    messageCell.colSpan = 3;
    messageCell.style.height = "300px";
    messageCell.innerText = "No students";
  }
  return table;
}

function onCheckStudent(event) {
  const value = event.target.checked;
  const id = event.target.parentElement.parentElement.id;
  let selectStudents =
    Core_State_Get("inventory", ["assignment", "selectStudents"]) || [];
  if (value) {
    selectStudents.push(id);
  } else {
    selectStudents = selectStudents.filter((item) => item != id);
  }
  Core_State_Set("inventory", ["assignment", "selectStudents"], selectStudents);
}

function onCheckAllStudent(e) {
  const value = e.target.checked;
  const table = e.target.parentElement.parentElement.parentElement;
  var listcheckbox = table.querySelectorAll("input[type=checkbox]");
  var firstcheck = 1;
  listcheckbox.forEach((element) => {
    if (firstcheck) {
      firstcheck = 0;
    } else if (value && !element.checked) element.click();
    else if (!value && element.checked) element.click();
  });
}
