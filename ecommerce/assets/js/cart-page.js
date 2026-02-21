const API = {
  me: "/ecommerce/shared/api/auth/me.php",
  cartGet: "/ecommerce/shared/api/cart/get.php",
  cartUpdate: "/ecommerce/shared/api/cart/update.php",
  cartRemove: "/ecommerce/shared/api/cart/remove.php",
  checkoutConfirm: "/ecommerce/shared/api/checkout/confirm.php",
  checkoutPay: "/ecommerce/shared/api/checkout/pay.php"
};

/*
Cart + Checkout controller.
Flow:
1) Ensure authenticated customer.
2) Load cart snapshot and render.
3) Require checkout confirmation (name/phone/address) before payment.
4) Simulated payment creates order/order_items/order_delivery_details/payment.
5) Refresh cart and sync navbar badge.
*/

let cartState = null;
let checkoutConfirmed = false;

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
  return `$${Number(value).toFixed(2)}`;
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

function togglePaymentControls(enabled) {
  const method = document.getElementById("checkoutPaymentMethod");
  const simulate = document.getElementById("checkoutSimulateResult");
  const txRef = document.getElementById("checkoutTransactionRef");
  const payBtn = document.getElementById("checkoutPayBtn");
  [method, simulate, txRef, payBtn].forEach((el) => {
    if (el) el.disabled = !enabled;
  });
}

function resetCheckoutConfirmation(reasonMessage = "Confirm details to unlock payment.") {
  checkoutConfirmed = false;
  togglePaymentControls(false);
  const hint = document.getElementById("checkoutConfirmHint");
  if (hint) hint.textContent = reasonMessage;
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
    <div><strong>Total Items:</strong> ${cart.total_items}</div>
    <div><strong>Total Quantity:</strong> ${cart.total_quantity}</div>
    <div><strong>Sub Total:</strong> ${formatCurrency(cart.sub_total)}</div>
  `;
}

async function loadCart() {
  const data = await fetchJson(API.cartGet, { method: "GET" });
  cartState = data;
  renderCart(data);

  if (!data.items.length) {
    resetCheckoutConfirmation("Cart is empty. Add products to start checkout.");
    setCheckoutStatus("", "muted");
  }
}

async function ensureAuthenticatedCustomer() {
  const data = await fetchJson(API.me, { method: "GET" });
  if (!data.authenticated) {
    window.location.href = "/ecommerce/index.php?page=home";
    return false;
  }
  return true;
}

async function confirmCheckoutDetails() {
  const name = document.getElementById("checkoutName")?.value || "";
  const phone = document.getElementById("checkoutPhone")?.value || "";
  const streetAddress = document.getElementById("checkoutStreetAddress")?.value || "";

  if (!cartState || !cartState.items?.length) {
    throw new Error("Your cart is empty.");
  }

  const result = await fetchJson(API.checkoutConfirm, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      name,
      phone,
      street_address: streetAddress
    })
  });

  checkoutConfirmed = true;
  togglePaymentControls(true);
  const hint = document.getElementById("checkoutConfirmHint");
  if (hint) {
    hint.textContent = "Details confirmed. You can now proceed with payment.";
  }
  setCheckoutStatus("Checkout details confirmed successfully.", "success");
  return result;
}

async function payCheckout() {
  if (!checkoutConfirmed) {
    throw new Error("Confirm delivery details before payment.");
  }

  const method = document.getElementById("checkoutPaymentMethod")?.value || "";
  const simulateResult = document.getElementById("checkoutSimulateResult")?.value || "";
  const transactionRef = document.getElementById("checkoutTransactionRef")?.value || "";

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
  const paymentStatus = result?.payment?.status || "pending";

  setCheckoutStatus(
    `Payment submitted. Order ${orderNumber} created with payment status: ${paymentStatus}.`,
    paymentStatus === "failed" ? "error" : "success"
  );

  await loadCart();
  resetCheckoutConfirmation("Payment complete. Confirm details again for next checkout.");
  document.dispatchEvent(new CustomEvent("cart:changed"));
}

document.addEventListener("DOMContentLoaded", async () => {
  try {
    const ok = await ensureAuthenticatedCustomer();
    if (!ok) return;

    await loadCart();
    resetCheckoutConfirmation("Confirm details to unlock payment.");
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
      resetCheckoutConfirmation("Cart updated. Reconfirm details before payment.");
      setCheckoutStatus("Cart updated. Please reconfirm delivery details.", "info");
      document.dispatchEvent(new CustomEvent("cart:changed"));
    } catch (error) {
      alert(error.message);
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
      resetCheckoutConfirmation("Cart changed. Reconfirm details before payment.");
      setCheckoutStatus("Cart changed. Please reconfirm delivery details.", "info");
      document.dispatchEvent(new CustomEvent("cart:changed"));
    } catch (error) {
      alert(error.message);
    }
    return;
  }

  const confirmBtn = event.target.closest("#checkoutConfirmBtn");
  if (confirmBtn) {
    try {
      confirmBtn.disabled = true;
      await confirmCheckoutDetails();
    } catch (error) {
      setCheckoutStatus(error.message, "error");
    } finally {
      confirmBtn.disabled = false;
    }
    return;
  }

  const payBtn = event.target.closest("#checkoutPayBtn");
  if (payBtn) {
    try {
      payBtn.disabled = true;
      await payCheckout();
    } catch (error) {
      setCheckoutStatus(error.message, "error");
    } finally {
      payBtn.disabled = false;
    }
  }
});
