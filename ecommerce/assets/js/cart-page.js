const API = {
  me: "/ecommerce/shared/api/auth/me.php",
  cartGet: "/ecommerce/shared/api/cart/get.php",
  cartUpdate: "/ecommerce/shared/api/cart/update.php",
  cartRemove: "/ecommerce/shared/api/cart/remove.php"
};

/*
Cart page controller.
Flow:
1) Ensure authenticated customer.
2) Load and render cart snapshot.
3) Handle update/remove actions.
4) Continue checkout to dedicated checkout page.
*/

let cartState = null;
const NOTIFICATION_CONTAINER_ID = "appNotifications";
const NOTIFICATION_VISIBLE_MS = 2800;

async function fetchJson(url, options = {}) {
  const response = await fetch(url, {
    credentials: "same-origin",
    ...options
  });
  const json = await response.json();
  if (!json.success) {
    throw new Error(json.message || "Request failed.");
  }
  return json.data;
}

function formatCurrency(value) {
  return `GH\u20B5${Number(value).toFixed(2)}`;
}

function getNotificationContainer() {
  let container = document.getElementById(NOTIFICATION_CONTAINER_ID);
  if (container) return container;

  container = document.createElement("div");
  container.id = NOTIFICATION_CONTAINER_ID;
  container.className = "position-fixed top-0 start-50 translate-middle-x p-3";
  container.style.zIndex = "2500";
  container.style.width = "min(92vw, 460px)";
  container.style.pointerEvents = "none";
  document.body.appendChild(container);
  return container;
}

function showNotification(message, type = "info") {
  const classMap = {
    success: "alert-success",
    error: "alert-danger",
    warning: "alert-warning",
    info: "alert-primary"
  };

  const container = getNotificationContainer();
  const alert = document.createElement("div");
  alert.className = `alert ${classMap[type] || classMap.info} shadow-sm mb-2 py-2 px-3 d-flex justify-content-between align-items-start`;
  alert.style.pointerEvents = "auto";
  alert.role = "alert";

  const messageNode = document.createElement("span");
  messageNode.className = "me-2";
  messageNode.textContent = message;

  const closeButton = document.createElement("button");
  closeButton.type = "button";
  closeButton.className = "btn-close ms-2 flex-shrink-0";
  closeButton.setAttribute("aria-label", "Close notification");
  closeButton.addEventListener("click", () => {
    alert.remove();
  });

  alert.append(messageNode, closeButton);
  container.appendChild(alert);

  window.setTimeout(() => {
    alert.classList.add("fade");
    alert.style.opacity = "0";
    window.setTimeout(() => alert.remove(), 180);
  }, NOTIFICATION_VISIBLE_MS);
}

function renderCart(cart) {
  const tableBody = document.getElementById("cartItemsBody");
  const summary = document.getElementById("cartSummary");
  if (!tableBody || !summary) return;

  if (!cart.items.length) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-muted py-4">Your cart is empty.</td>
      </tr>
    `;
  } else {
    tableBody.innerHTML = cart.items.map((item) => `
      <tr>
        <td>
          <div class="d-flex align-items-center gap-2">
            ${item.image_url ? `<img src="${item.image_url}" alt="${item.name}" width="50" class="rounded">` : ""}
            <span>${item.name}</span>
          </div>
        </td>
        <td>${formatCurrency(item.price)}</td>
        <td>
          <input
            type="number"
            min="1"
            value="${item.quantity}"
            data-qty-input
            data-item-id="${item.id}"
            class="form-control form-control-sm"
            style="max-width:90px"
          >
        </td>
        <td>${formatCurrency(item.line_total)}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" data-update-item data-item-id="${item.id}">
            Update
          </button>
        </td>
        <td>
          <button class="btn btn-sm btn-outline-danger" data-remove-item data-item-id="${item.id}">
            Remove
          </button>
        </td>
      </tr>
    `).join("");
  }

  summary.innerHTML = `
    <div class="row g-2 mb-3">
      <div class="col-6">
        <div class="p-3 rounded border bg-light h-100">
          <div class="small text-muted"><i class="fa-solid fa-box me-1 text-info"></i>Total Items</div>
          <div class="fs-5 fw-bold">${cart.total_items}</div>
        </div>
      </div>
      <div class="col-6">
        <div class="p-3 rounded border bg-light h-100">
          <div class="small text-muted"><i class="fa-solid fa-layer-group me-1 text-info"></i>Quantity</div>
          <div class="fs-5 fw-bold">${cart.total_quantity}</div>
        </div>
      </div>
    </div>
    <div class="p-3 rounded border bg-light mb-3 d-flex justify-content-between align-items-center">
      <span class="text-muted"><i class="fa-solid fa-coins me-1 text-warning"></i>Sub Total</span>
      <strong class="text-dark fs-5">${formatCurrency(cart.sub_total)}</strong>
    </div>
    <div class="d-flex justify-content-center">
      <button class="btn btn-primary px-4" id="cartCheckoutBtn" type="button" ${cart.items.length ? "" : "disabled"}>
        <i class="fa-solid fa-credit-card me-2"></i>Checkout (${formatCurrency(cart.sub_total)})
      </button>
    </div>
  `;
}

async function loadCart() {
  const data = await fetchJson(API.cartGet, { method: "GET" });
  cartState = data;
  renderCart(data);
}

async function ensureAuthenticatedCustomer() {
  const data = await fetchJson(API.me, { method: "GET" });
  if (!data.authenticated) {
    window.location.href = "/ecommerce/index.php?page=home";
    return false;
  }
  return true;
}

function showEntryNotifications() {
  const params = new URLSearchParams(window.location.search);
  const paid = params.get("paid");
  const checkout = params.get("checkout");

  if (paid === "1") {
    showNotification("Payment completed and order created successfully.", "success");
  }
  if (checkout === "empty") {
    showNotification("Your cart is empty. Add products before checkout.", "warning");
  }

  if (paid || checkout) {
    const cleanUrl = new URL(window.location.href);
    cleanUrl.searchParams.delete("paid");
    cleanUrl.searchParams.delete("checkout");
    window.history.replaceState({}, "", cleanUrl.toString());
  }
}

document.addEventListener("DOMContentLoaded", async () => {
  try {
    const ok = await ensureAuthenticatedCustomer();
    if (!ok) return;

    await loadCart();
    showEntryNotifications();
  } catch (error) {
    const tableBody = document.getElementById("cartItemsBody");
    if (tableBody) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="6" class="text-center text-danger py-4">${error.message}</td>
        </tr>
      `;
    }
  }
});

document.addEventListener("click", async (event) => {
  const updateBtn = event.target.closest("[data-update-item]");
  if (updateBtn) { 
    const itemId = Number(updateBtn.dataset.itemId || 0);
    const input = document.querySelector(`[data-qty-input][data-item-id="${itemId}"]`);
    const quantity = Number(input?.value || 0);

    try {
      await fetchJson(API.cartUpdate, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ item_id: itemId, quantity })
      });
      await loadCart();
      showNotification("Cart item updated.", "success");
      document.dispatchEvent(new CustomEvent("cart:changed"));
    } catch (error) {
      showNotification(error.message, "error");
    }
    return;
  }

  const removeBtn = event.target.closest("[data-remove-item]");
  if (removeBtn) {
    const itemId = Number(removeBtn.dataset.itemId || 0);
    try {
      await fetchJson(API.cartRemove, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ item_id: itemId })
      });
      await loadCart();
      showNotification("Item removed from cart.", "success");
      document.dispatchEvent(new CustomEvent("cart:changed"));
    } catch (error) {
      showNotification(error.message, "error");
    }
    return;
  }

  const checkoutBtn = event.target.closest("#cartCheckoutBtn");
  if (checkoutBtn) {
    if (!cartState || !Array.isArray(cartState.items) || !cartState.items.length) {
      showNotification("Your cart is empty. Add products before checkout.", "warning");
      return;
    }
    window.location.href = "/ecommerce/index.php?page=checkout";
  }
});
