const API = {
  me: "/ecommerce/shared/api/auth/me.php",
  cartGet: "/ecommerce/shared/api/cart/get.php",
  checkoutStatus: "/ecommerce/shared/api/checkout/status.php",
  checkoutPay: "/ecommerce/shared/api/checkout/pay.php"
};

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

function setPaymentStatus(message, type = "muted") {
  const target = document.getElementById("paymentStatus");
  if (!target) return;
  const classMap = {
    muted: "text-muted",
    success: "text-success",
    error: "text-danger",
    info: "text-primary"
  };
  target.className = classMap[type] || classMap.muted;
  target.textContent = message;
}

function renderSummary(cart) {
  const tableBody = document.getElementById("paymentItemsBody");
  const summary = document.getElementById("paymentSummary");
  if (!tableBody || !summary) return;

  tableBody.innerHTML = cart.items.map((item) => `
    <tr>
      <td>
        <div class="d-flex align-items-center gap-2">
          ${item.image_url ? `<img src="${item.image_url}" alt="${item.name}" width="50" class="rounded">` : ""}
          <span>${item.name}</span>
        </div>
      </td>
      <td>${formatCurrency(item.price)}</td>
      <td>${item.quantity}</td>
      <td>${formatCurrency(item.line_total)}</td>
    </tr>
  `).join("");

  summary.innerHTML = `
    <div class="row g-2">
      <div class="col-md-4">
        <div class="p-3 rounded border bg-light h-100">
          <div class="small text-muted"><i class="fa-solid fa-box me-1 text-info"></i>Total Items</div>
          <div class="fs-5 fw-bold">${cart.total_items}</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-3 rounded border bg-light h-100">
          <div class="small text-muted"><i class="fa-solid fa-layer-group me-1 text-info"></i>Quantity</div>
          <div class="fs-5 fw-bold">${cart.total_quantity}</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-3 rounded border bg-light h-100">
          <div class="small text-muted"><i class="fa-solid fa-coins me-1 text-warning"></i>Sub Total</div>
          <div class="fs-5 fw-bold text-dark">${formatCurrency(cart.sub_total)}</div>
        </div>
      </div>
    </div>
  `;

  const payBtn = document.getElementById("paymentPayBtn");
  if (payBtn) {
    payBtn.innerHTML = `<i class="fa-solid fa-lock me-2"></i>Pay Now (${formatCurrency(cart.sub_total)})`;
  }
}

function renderDelivery(delivery) {
  document.getElementById("paymentDeliveryName").textContent = delivery?.name || "-";
  document.getElementById("paymentDeliveryPhone").textContent = delivery?.phone || "-";
  document.getElementById("paymentDeliveryStreet").textContent = delivery?.street_address || "-";
}

async function ensureAuthenticatedCustomer() {
  const data = await fetchJson(API.me, { method: "GET" });
  if (!data.authenticated) {
    window.location.href = "/ecommerce/index.php?page=home";
    return false;
  }
  return true;
}

async function preparePageData() {
  const cart = await fetchJson(API.cartGet, { method: "GET" });
  if (!Array.isArray(cart.items) || cart.items.length === 0) {
    window.location.href = "/ecommerce/index.php?page=cart&checkout=empty";
    return false;
  }

  const status = await fetchJson(API.checkoutStatus, { method: "GET" });
  if (!status.confirmed) {
    window.location.href = "/ecommerce/index.php?page=checkout&needs_confirmation=1";
    return false;
  }

  cartState = cart;
  renderSummary(cart);
  renderDelivery(status.delivery || null);

  const payBtn = document.getElementById("paymentPayBtn");
  if (payBtn) {
    payBtn.disabled = false;
  }

  return true;
}

async function submitPayment() {
  if (!cartState || !Array.isArray(cartState.items) || cartState.items.length === 0) {
    throw new Error("Your cart is empty.");
  }

  const method = document.getElementById("paymentMethod")?.value || "";
  const simulateResult = document.getElementById("paymentSimulateResult")?.value || "";
  const transactionRef = document.getElementById("paymentTransactionRef")?.value || "";

  const result = await fetchJson(API.checkoutPay, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      method,
      simulate_result: simulateResult,
      transaction_ref: transactionRef
    })
  });

  const orderNumber = result?.order?.order_number || "N/A";
  const paymentStatus = (result?.payment?.status || "pending").toLowerCase();
  const statusType = paymentStatus === "failed" ? "error" : "success";

  setPaymentStatus(
    `Payment submitted. Order ${orderNumber} created with status: ${paymentStatus}. Redirecting to cart...`,
    statusType
  );
  showNotification(`Order ${orderNumber} created. Payment status: ${paymentStatus}.`, statusType);

  window.setTimeout(() => {
    window.location.href = "/ecommerce/index.php?page=cart&paid=1";
  }, 900);
}

document.addEventListener("DOMContentLoaded", async () => {
  try {
    const ok = await ensureAuthenticatedCustomer();
    if (!ok) return;

    await preparePageData();
  } catch (error) {
    setPaymentStatus(error.message, "error");
    showNotification(error.message, "error");
  }
});

document.addEventListener("click", async (event) => {
  const payBtn = event.target.closest("#paymentPayBtn");
  if (!payBtn) return;

  try {
    payBtn.disabled = true;
    await submitPayment();
  } catch (error) {
    setPaymentStatus(error.message, "error");
    showNotification(error.message, "error");
    payBtn.disabled = false;
  }
});
