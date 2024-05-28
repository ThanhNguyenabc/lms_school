async function showPopupConfirm(title, content, popupData = {}) {
  // Initial UI
  const popupContent = UI_Element_Create(
    "inventory/popup-confirm/popup-confirm",
    {
      title: title,
    }
  );
  UI_Element_Find(popupContent, "content").innerText = content;

  const errorMessage = UI_Element_Find(popupContent, "errorMessage");

  const loading = document.createElement("div");
  loading.classList.add("loader");
  loading.style = "font-size: 4px; margin-left: 25px; margin-right: 10px";

  const continueBtn = UI_Element_Create("core/button-small-plain", {
    text: UI_Language_String("inventory/module", "button continue"),
  });
  UI_Element_Find(popupContent, "buttonContainer").appendChild(continueBtn);

  const popup = await UI_Popup_Create({ content: popupContent }, [], "");

  let state;
  // Register click events
  continueBtn.onclick = async () => {
    try {
      if (state == "requesting") return;

      // Show loading
      continueBtn.classList.add("style-disabled");
      continueBtn.appendChild(loading);

      state = "requesting";
      const { id, status } = popupData;
      const { error, data } = await UpdateRequestStatus(id, status);

      // Remove loading
      state = "done";
      continueBtn.classList.remove("style-disabled");
      continueBtn.removeChild(loading);

      if (data) {
        UI_Popup_Close(popup);
        return;
      }

      if (error) {
        errorMessage.innerText = error;
        continueBtn.innerText = "Try Again";
      }
    } catch (error) {
      continueBtn.removeChild(loading);
      state = "done";
    }
  };
}

async function showPopupConfirmX(title, content, options = {}) {
  // Initial UI
  const popupContent = UI_Element_Create(
    "inventory/popup-confirm/popup-confirm",
    {
      title: title,
    }
  );
  UI_Element_Find(popupContent, "content").innerText = content;

  const errorMessage = UI_Element_Find(popupContent, "errorMessage");

  const loading = document.createElement("div");
  loading.classList.add("loader");
  loading.style = "font-size: 4px; margin-left: 25px; margin-right: 10px";

  const continueBtn = UI_Element_Create("core/button-small-plain", {
    text: UI_Language_String("inventory/module", "button continue"),
  });
  UI_Element_Find(popupContent, "buttonContainer").appendChild(continueBtn);

  const popup = await UI_Popup_Create({ content: popupContent }, [], "");

  const { onFailure, action, onSuccess } = options;

  let state;
  // Register click events
  continueBtn.onclick = async () => {
    try {
      if (state == "requesting") return;

      // Show loading
      continueBtn.classList.add("style-disabled");
      continueBtn.appendChild(loading);

      state = "requesting";

      const { error, data } = await action();
      // Remove loading
      state = "done";
      continueBtn.classList.remove("style-disabled");
      continueBtn.removeChild(loading);

      if (data) {
        onSuccess && onSuccess(data);
      } else if (error) {
        errorMessage.innerText = onFailure ? onFailure(error) : error;
        continueBtn.innerText = "Try Again";
      }
    } catch (error) {
      // continueBtn.hasChildNodes() && continueBtn.removeChild(loading);
      state = "done";
    }
  };

  return { popup, continueBtn , messageText: errorMessage };
}
