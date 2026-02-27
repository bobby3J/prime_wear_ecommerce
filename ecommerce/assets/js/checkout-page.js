const API = {
  me: "/ecommerce/shared/api/auth/me.php",
  cartGet: "/ecommerce/shared/api/cart/get.php",
  checkoutConfirm: "/ecommerce/shared/api/checkout/confirm.php",
  checkoutStatus: "/ecommerce/shared/api/checkout/status.php"
};

let cartState = null;
let reviewDraft = null;
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

function setCheckoutStatus(message, type = "muted") {
  const target = document.getElementById("checkoutStatus");
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

function getDeliveryDraft() {
  return {
    name: (document.getElementById("checkoutName")?.value || "").trim(),
    phone: (document.getElementById("checkoutPhone")?.value || "").trim(),
    streetAddress: (document.getElementById("checkoutStreetAddress")?.value || "").trim()
  };
}

function validateDeliveryDraft(draft) {
  if (draft.name.length < 2 || draft.name.length > 120) {
    throw new Error("Full name must be between 2 and 120 characters.");
  }
  if (!/^\+?[0-9][0-9\s\-]{6,19}$/.test(draft.phone)) {
    throw new Error("Enter a valid phone number.");
  }
  if (draft.streetAddress.length < 5 || draft.streetAddress.length > 255) {
    throw new Error("Street address must be between 5 and 255 characters.");
  }
}

function fillDeliveryForm(delivery) {
  if (!delivery) return;

  const nameInput = document.getElementById("checkoutName");
  const phoneInput = document.getElementById("checkoutPhone");
  const addressInput = document.getElementById("checkoutStreetAddress");

  if (nameInput && !nameInput.value) nameInput.value = delivery.name || "";
  if (phoneInput && !phoneInput.value) phoneInput.value = delivery.phone || "";
  if (addressInput && !addressInput.value) addressInput.value = delivery.street_address || "";
}

function populateReviewModal(draft) {
  if (!cartState) return;

  const nameEl = document.getElementById("checkoutReviewName");
  const phoneEl = document.getElementById("checkoutReviewPhone");
  const streetEl = document.getElementById("checkoutReviewStreet");
  const itemsEl = document.getElementById("checkoutReviewItems");
  const qtyEl = document.getElementById("checkoutReviewQty");
  const totalEl = document.getElementById("checkoutReviewTotal");
  const rowsEl = document.getElementById("checkoutReviewItemsBody");
  const confirmBtn = document.getElementById("checkoutModalConfirmBtn");

  if (nameEl) nameEl.textContent = draft.name;
  if (phoneEl) phoneEl.textContent = draft.phone;
  if (streetEl) streetEl.textContent = draft.streetAddress;
  if (itemsEl) itemsEl.textContent = String(cartState.total_items);
  if (qtyEl) qtyEl.textContent = String(cartState.total_quantity);
  if (totalEl) totalEl.textContent = formatCurrency(cartState.sub_total);

  if (rowsEl) {
    rowsEl.innerHTML = cartState.items.map((item) => `
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
  }

  if (confirmBtn) {
    confirmBtn.textContent = `Confirm & Continue to Payment (${formatCurrency(cartState.sub_total)})`;
  }
}

function openReviewModal() {
  const modalEl = document.getElementById("checkoutReviewModal");
  if (!modalEl) return;

  if (window.bootstrap?.Modal) {
    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    return;
  }

  modalEl.classList.add("show");
  modalEl.style.display = "block";
  modalEl.removeAttribute("aria-hidden");
  modalEl.setAttribute("aria-modal", "true");
}

function closeReviewModal() {
  const modalEl = document.getElementById("checkoutReviewModal");
  if (!modalEl) return;

  if (window.bootstrap?.Modal) {
    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.hide();
    return;
  }

  modalEl.classList.remove("show");
  modalEl.style.display = "none";
  modalEl.setAttribute("aria-hidden", "true");
  modalEl.removeAttribute("aria-modal");
}

function updateReviewButtonLabel() {
  const reviewBtn = document.getElementById("checkoutReviewBtn");
  if (!reviewBtn || !cartState) return;

  reviewBtn.textContent = `Review Order Summary (${formatCurrency(cartState.sub_total)})`;
}

function markDraftAsChanged() {
  reviewDraft = null;

  const toPaymentBtn = document.getElementById("checkoutToPaymentBtn");
  if (toPaymentBtn) {
    toPaymentBtn.classList.add("d-none");
  }

  setCheckoutStatus("Details updated. Review popup again before final confirmation.", "info");
}

function bindDraftChangeListeners() {
  const fields = [
    document.getElementById("checkoutName"),
    document.getElementById("checkoutPhone"),
    document.getElementById("checkoutStreetAddress")
  ].filter(Boolean);

  fields.forEach((field) => {
    field.addEventListener("input", markDraftAsChanged);
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

async function loadCart() {
  const cart = await fetchJson(API.cartGet, { method: "GET" });
  if (!Array.isArray(cart.items) || cart.items.length === 0) {
    window.location.href = "/ecommerce/index.php?page=cart&checkout=empty";
    return null;
  }

  cartState = cart;
  updateReviewButtonLabel();
  return cart;
}

async function loadCheckoutStatus() {
  const status = await fetchJson(API.checkoutStatus, { method: "GET" });
  fillDeliveryForm(status.delivery || null);

  if (status.confirmed) {
    const toPaymentBtn = document.getElementById("checkoutToPaymentBtn");
    if (toPaymentBtn) {
      toPaymentBtn.classList.remove("d-none");
    }

    setCheckoutStatus("Delivery details already confirmed. You can continue to payment, or edit and reconfirm.", "info");
  }
}

function showEntryNotifications() {
  const params = new URLSearchParams(window.location.search);
  if (params.get("needs_confirmation") === "1") {
    showNotification("Confirm delivery details before accessing payment.", "warning");
  }

  if (params.get("needs_confirmation")) {
    const cleanUrl = new URL(window.location.href);
    cleanUrl.searchParams.delete("needs_confirmation");
    window.history.replaceState({}, "", cleanUrl.toString());
  }
}

function reviewOrder() {
  if (!cartState || !Array.isArray(cartState.items) || cartState.items.length === 0) {
    throw new Error("Your cart is empty.");
  }

  const draft = getDeliveryDraft();
  validateDeliveryDraft(draft);

  reviewDraft = draft;
  populateReviewModal(draft);
  openReviewModal();
  setCheckoutStatus("Review popup opened. Confirm from the popup when ready.", "info");
}

async function confirmCheckout() {
  if (!reviewDraft) {
    throw new Error("Open the review popup and review details before final confirmation.");
  }

  validateDeliveryDraft(reviewDraft);

  await fetchJson(API.checkoutConfirm, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      name: reviewDraft.name,
      phone: reviewDraft.phone,
      street_address: reviewDraft.streetAddress
    })
  });

  closeReviewModal();

  const toPaymentBtn = document.getElementById("checkoutToPaymentBtn");
  if (toPaymentBtn) {
    toPaymentBtn.classList.remove("d-none");
  }

  setCheckoutStatus("Delivery details confirmed. Redirecting to payment...", "success");
  showNotification("Checkout confirmed. Moving to payment.", "success");

  window.setTimeout(() => {
    window.location.href = "/ecommerce/index.php?page=payment";
  }, 500);
}

document.addEventListener("DOMContentLoaded", async () => {
  try {
    const ok = await ensureAuthenticatedCustomer();
    if (!ok) return;

    showEntryNotifications();
    const cart = await loadCart();
    if (!cart) return;

    bindDraftChangeListeners();
    await loadCheckoutStatus();
  } catch (error) {
    setCheckoutStatus(error.message, "error");
    showNotification(error.message, "error");
  }
});

document.addEventListener("click", async (event) => {
  const reviewBtn = event.target.closest("#checkoutReviewBtn");
  if (reviewBtn) {
    try {
      reviewOrder();
    } catch (error) {
      setCheckoutStatus(error.message, "error");
      showNotification(error.message, "error");
    }
    return;
  }

  const modalConfirmBtn = event.target.closest("#checkoutModalConfirmBtn");
  if (!modalConfirmBtn) return;

  try {
    modalConfirmBtn.disabled = true;
    await confirmCheckout();
  } catch (error) {
    setCheckoutStatus(error.message, "error");
    showNotification(error.message, "error");
  } finally {
    modalConfirmBtn.disabled = false;
  }
});
