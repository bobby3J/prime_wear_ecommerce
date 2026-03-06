const API = {
  me: "/ecommerce/shared/api/auth/me.php",
  cartGet: "/ecommerce/shared/api/cart/get.php",
  checkoutStatus: "/ecommerce/shared/api/checkout/status.php",
  checkoutPay: "/ecommerce/shared/api/checkout/pay.php"
};

let cartState = null;
let collectionDestinations = {};
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

function getPaymentMethodLabel(method) {
  const normalized = String(method || "")
    .trim()
    .toLowerCase()
    .replace(/-/g, "_")
    .replace(/\s+/g, "_");

  const labels = {
    momo: "MTN MoMo",
    mtn_momo: "MTN MoMo",
    mobile_money: "MTN MoMo",
    bank: "Bank",
    card: "Bank",
    telecel_cash: "Telecel Cash",
    vodafone_cash: "Telecel Cash",
    cash_on_delivery: "Cash On Delivery",
    cod: "Cash On Delivery"
  };

  return labels[normalized] || method || "Payment";
}

function toLocalGhanaMobile(value) {
  const digits = String(value || "").replace(/\D/g, "");
  if (digits.startsWith("233") && digits.length === 12) {
    return `0${digits.slice(3)}`;
  }
  return digits;
}

function getPayerPhoneUiConfig(method) {
  const normalized = String(method || "").trim().toLowerCase();
  if (normalized === "mtn_momo") {
    return {
      label: "Payer Number (MTN MoMo)",
      placeholder: "e.g. 024xxxxxxx",
      hint: "Use MTN number (starts with 024, 054, 055, or 059).",
      allowedPrefixes: ["024", "054", "055", "059"]
    };
  }
  if (normalized === "telecel_cash") {
    return {
      label: "Payer Number (Telecel Cash)",
      placeholder: "e.g. 020xxxxxxx or 050xxxxxxx",
      hint: "Use Telecel number (starts with 020 or 050).",
      allowedPrefixes: ["020", "050"]
    };
  }
  return {
    label: "Payer Number",
    placeholder: "e.g. 024xxxxxxx",
    hint: "Required for MTN MoMo and Telecel Cash prompt flow.",
    allowedPrefixes: []
  };
}

function validatePayerPhoneForMethod(method, payerPhone) {
  const normalizedMethod = String(method || "").trim().toLowerCase();
  if (normalizedMethod !== "mtn_momo" && normalizedMethod !== "telecel_cash") {
    return;
  }

  const local = toLocalGhanaMobile(payerPhone);
  if (!/^0\d{9}$/.test(local)) {
    throw new Error("Enter a valid Ghana mobile number (10 digits).");
  }

  const config = getPayerPhoneUiConfig(normalizedMethod);
  const isAllowed = config.allowedPrefixes.some((prefix) => local.startsWith(prefix));
  if (!isAllowed) {
    throw new Error(config.hint);
  }
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
    <div class="row g-2 payment-summary-grid">
      <div class="col-md-4">
        <div class="p-3 rounded border bg-light h-100 payment-metric">
          <div class="small text-muted payment-metric-label">Total Items</div>
          <div class="fs-5 fw-bold payment-metric-value">${cart.total_items}</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-3 rounded border bg-light h-100 payment-metric">
          <div class="small text-muted payment-metric-label">Quantity</div>
          <div class="fs-5 fw-bold payment-metric-value">${cart.total_quantity}</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-3 rounded border bg-light h-100 payment-metric">
          <div class="small text-muted payment-metric-label">Sub Total</div>
          <div class="fs-5 fw-bold text-dark payment-metric-value">${formatCurrency(cart.sub_total)}</div>
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

function renderCollectionDestination(method) {
  const box = document.getElementById("paymentCollectionDestination");
  if (!box) return;

  const normalizedMethod = String(method || "").trim().toLowerCase();
  const payerInput = document.getElementById("paymentPayerPhone");
  const payerWrap = document.getElementById("paymentPayerPhoneWrap");
  const payerLabel = document.getElementById("paymentPayerPhoneLabel");
  const payerHint = document.getElementById("paymentPayerPhoneHint");
  const isMomoMethod = normalizedMethod === "mtn_momo" || normalizedMethod === "telecel_cash";
  const payerConfig = getPayerPhoneUiConfig(normalizedMethod);
  if (payerWrap) {
    payerWrap.classList.toggle("d-none", !isMomoMethod);
  }
  if (payerInput) {
    payerInput.placeholder = payerConfig.placeholder;
    payerInput.required = isMomoMethod;
    payerInput.disabled = !isMomoMethod;
    if (!isMomoMethod) {
      payerInput.value = "";
    }
  }
  if (payerLabel) {
    payerLabel.textContent = payerConfig.label;
  }
  if (payerHint) {
    payerHint.textContent = payerConfig.hint;
  }
  syncProviderChips(normalizedMethod);

  if (normalizedMethod === "cash_on_delivery") {
    box.innerHTML = `<i class="fa-solid fa-truck me-2"></i>No upfront transfer required. You will pay on delivery.`;
    return;
  }

  const destination = collectionDestinations[normalizedMethod] || null;
  if (!destination) {
    box.innerHTML = `<i class="fa-solid fa-building-columns me-2"></i>Business collection account is configured in gateway settings.`;
    return;
  }

  if (destination.type === "mobile_money") {
    const network = destination.network ? destination.network.toUpperCase() : "MOBILE MONEY";
    const number = destination.number || "configured on gateway";
    const name = destination.name || "";
    box.innerHTML = `<i class="fa-solid fa-mobile-screen-button me-2"></i>${network} collection number: <strong>${number}</strong>${name ? ` (${name})` : ""}`;
    return;
  }

  if (destination.type === "bank") {
    const bankName = destination.bank_name || "configured bank";
    const accountNumber = destination.account_number || "configured account";
    const accountName = destination.account_name || "";
    box.innerHTML = `<i class="fa-solid fa-building-columns me-2"></i>Bank collection: <strong>${bankName}</strong>, A/C <strong>${accountNumber}</strong>${accountName ? ` (${accountName})` : ""}`;
    return;
  }

  box.innerHTML = `<i class="fa-solid fa-building-columns me-2"></i>Business collection account is configured in gateway settings.`;
}

function syncProviderChips(method) {
  const chips = document.querySelectorAll("[data-provider-chip]");
  chips.forEach((chip) => {
    const provider = String(chip.getAttribute("data-provider-chip") || "").toLowerCase();
    const isActive = provider === method;
    chip.classList.toggle("is-active", isActive);
    chip.setAttribute("aria-pressed", isActive ? "true" : "false");
  });
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
  collectionDestinations = status.collection_destinations || {};
  renderSummary(cart);
  renderDelivery(status.delivery || null);
  renderCollectionDestination(document.getElementById("paymentMethod")?.value || "");

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
  const transactionRef = document.getElementById("paymentTransactionRef")?.value || "";
  const payerPhone = document.getElementById("paymentPayerPhone")?.value?.trim() || "";

  if ((method === "mtn_momo" || method === "telecel_cash") && !payerPhone) {
    throw new Error("Enter the payer mobile money number to receive PIN prompt.");
  }
  validatePayerPhoneForMethod(method, payerPhone);

  const result = await fetchJson(API.checkoutPay, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      method,
      transaction_ref: transactionRef,
      payer_phone: payerPhone
    })
  });

  const orderNumber = result?.order?.order_number || "N/A";
  const paymentStatus = (result?.payment?.status || "pending").toLowerCase();
  const paymentMethod = result?.payment?.method || method;
  const paymentMethodLabel = getPaymentMethodLabel(paymentMethod);
  const statusType = paymentStatus === "failed" ? "error" : (paymentStatus === "successful" ? "success" : "info");
  const customerNotice = result?.customer_notice || "";
  const checkoutUrl = result?.gateway?.checkout_url || "";
  const requiresRedirect = Boolean(result?.gateway?.requires_redirect && checkoutUrl);
  const destination = result?.collection_destination || null;

  setPaymentStatus(
    `${paymentMethodLabel} payment submitted. Order ${orderNumber} created with status: ${paymentStatus}.`,
    statusType
  );
  showNotification(
    `Order ${orderNumber} created. Method: ${paymentMethodLabel}. Payment status: ${paymentStatus}.`,
    statusType
  );

  if (customerNotice) {
    showNotification(customerNotice, "warning");
  }

  if (destination && destination.type) {
    renderCollectionDestination(method);
  }

  if (requiresRedirect) {
    setPaymentStatus(
      `${paymentMethodLabel} initiated. Redirecting to secure payment page...`,
      "info"
    );
    window.setTimeout(() => {
      window.location.href = checkoutUrl;
    }, 700);
    return;
  }

  window.setTimeout(() => {
    const query = paymentStatus === "successful" ? "paid=1" : `payment=${encodeURIComponent(paymentStatus)}`;
    window.location.href = `/ecommerce/index.php?page=cart&${query}`;
  }, 900);
}

document.addEventListener("DOMContentLoaded", async () => {
  try {
    const paymentMethod = document.getElementById("paymentMethod");
    if (paymentMethod) {
      paymentMethod.addEventListener("change", () => {
        renderCollectionDestination(paymentMethod.value);
      });
    }

    document.querySelectorAll("[data-provider-chip]").forEach((chip) => {
      chip.addEventListener("click", () => {
        if (!paymentMethod) return;
        const selected = String(chip.getAttribute("data-provider-chip") || "");
        paymentMethod.value = selected;
        renderCollectionDestination(selected);
      });
    });

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
