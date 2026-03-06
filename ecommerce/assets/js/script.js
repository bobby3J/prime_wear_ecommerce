import { renderCategory } from "./components/CategoryCard.js";

/*
Storefront global controller.
Primary responsibilities:
1) Load auth session state and toggle profile icon behavior.
2) Open login/register modal fragments and submit auth requests.
3) Add product to cart from product cards.
4) Keep cart count badge synchronized.
5) Wire category sidebar interactions.
*/

const fallbackCategories = [
  {
    id: "menCategory",
    name: "Men's Collection",
    icon: "fa-solid fa-person",
    items: ["Boxers", "Singlets", "Socks"]
  },
  {
    id: "ladiesCategory",
    name: "Ladies' Collection",
    icon: "fa-solid fa-person-dress",
    items: ["Nightwear", "Panties", "Active wears"]
  },
  {
    id: "couplesUnisex",
    name: "Couples & Unisex",
    icon: "fa-solid fa-people-arrows",
    items: ["Matching sets", "T-Shirts", "Unisex sleepwear"]
  }
];

const API = {
  me: "/ecommerce/shared/api/auth/me.php",
  login: "/ecommerce/shared/api/auth/login.php",
  register: "/ecommerce/shared/api/auth/register.php",
  logout: "/ecommerce/shared/api/auth/logout.php",
  cartAdd: "/ecommerce/shared/api/cart/add.php",
  cartCount: "/ecommerce/shared/api/cart/count.php",
  categories: "/ecommerce/shared/api/categories.php"
};

const authState = {
  customer: null
};
const NOTIFICATION_CONTAINER_ID = "appNotifications";
const NOTIFICATION_VISIBLE_MS = 2800;

async function fetchJson(url, options = {}) {
  // Single request helper for storefront v1:
  // - keeps credentials behavior in one place
  // - auto-handles JSON body + Content-Type
  // - keeps call sites focused on endpoint + payload
  const {
    method = "GET",
    headers = {},
    body,
    ...rest
  } = options;

  const requestOptions = {
    credentials: "same-origin",
    method,
    headers: { ...headers },
    ...rest
  };

  if (typeof body !== "undefined") {
    if (body instanceof FormData) {
      requestOptions.body = body;
    } else if (typeof body === "string") {
      requestOptions.body = body;
      if (!requestOptions.headers["Content-Type"]) {
        requestOptions.headers["Content-Type"] = "application/json";
      }
    } else {
      requestOptions.body = JSON.stringify(body);
      if (!requestOptions.headers["Content-Type"]) {
        requestOptions.headers["Content-Type"] = "application/json";
      }
    }
  }

  const response = await fetch(url, requestOptions);
  const json = await response.json();
  if (!response.ok || !json.success) {
    throw new Error(json.message || "Request failed.");
  }
  return json.data;
}

function getProfileIcon() {
  return document.querySelector(".profile-trigger i");
}

function getProfileLink() {
  return document.querySelector(".profile-trigger");
}

function getCartLink() {
  return document.querySelector('a[href="/ecommerce/index.php?page=cart"]');
}

function getCartCountBadge() {
  return document.getElementById("cartCountBadge");
}

function setCartCount(count) {
  const badge = getCartCountBadge();
  if (!badge) return;
  badge.textContent = String(count);
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

function buildSidebarCategories(rawCategories = []) {
  if (!Array.isArray(rawCategories) || rawCategories.length === 0) {
    return fallbackCategories;
  }

  const groups = {
    men: {
      id: "menCategory",
      name: "Men's Collection",
      icon: "fa-solid fa-person",
      items: []
    },
    ladies: {
      id: "ladiesCategory",
      name: "Ladies' Collection",
      icon: "fa-solid fa-person-dress",
      items: []
    },
    unisex: {
      id: "couplesUnisex",
      name: "Couples & Unisex",
      icon: "fa-solid fa-people-arrows",
      items: []
    }
  };

  rawCategories.forEach((category) => {
    const groupKey = ["men", "ladies", "unisex"].includes(category.group)
      ? category.group
      : "unisex";

    groups[groupKey].items.push({
      label: category.name,
      value: category.slug || category.name,
      categoryId: Number(category.id || 0)
    });
  });

  return [groups.men, groups.ladies, groups.unisex];
}

async function fetchStoreCategories() {
  try {
    const data = await fetchJson(API.categories, { method: "GET" });
    return Array.isArray(data.items) ? data.items : [];
  } catch {
    return [];
  }
}

async function refreshCartCount() {
  try {
    const data = await fetchJson(API.cartCount, { method: "GET" });
    setCartCount(Number(data.count || 0));
  } catch {
    setCartCount(0);
  }
}

function hideLoginModal() {
  const overlay = document.getElementById("loginModalOverlay");
  const inner = document.getElementById("loginModalInner");
  if (!overlay || !inner) return;

  overlay.style.display = "none";
  inner.innerHTML = "";
  document.body.style.overflow = "";
}

function showError(form, message) {
  let errorNode = form.querySelector("[data-auth-error]");
  if (!errorNode) {
    errorNode = document.createElement("div");
    errorNode.className = "alert alert-danger mt-3 mb-0";
    errorNode.setAttribute("data-auth-error", "1");
    form.appendChild(errorNode);
  }
  errorNode.textContent = message;
}

async function submitLogin(form) {
  const email = form.querySelector("#email")?.value || "";
  const password = form.querySelector("#password")?.value || "";

  try {
    await fetchJson(API.login, {
      method: "POST",
      body: { email, password }
    });
    await refreshAuthState();
    hideLoginModal();
  } catch (error) {
    showError(form, error.message);
  }
}

async function submitRegister(form) {
  const name = form.querySelector("#reg_name")?.value || "";
  const email = form.querySelector("#reg_email")?.value || "";
  const password = form.querySelector("#reg_password")?.value || "";
  const confirmPassword = form.querySelector("#reg_password_confirm")?.value || "";

  if (password !== confirmPassword) {
    showError(form, "Passwords do not match.");
    return;
  }

  try {
    await fetchJson(API.register, {
      method: "POST",
      body: { name, email, password }
    });
    await refreshAuthState();
    hideLoginModal();
  } catch (error) {
    showError(form, error.message);
  }
}

async function renderLoginModal(page) {
  const overlay = document.getElementById("loginModalOverlay");
  const inner = document.getElementById("loginModalInner");
  if (!overlay || !inner) return;

  const response = await fetch(`/ecommerce/pages/${page}.php`);
  const html = await response.text();

  inner.innerHTML = html;
  overlay.style.display = "flex";
  document.body.style.overflow = "hidden";

  const closeBtn = inner.querySelector(".btn-close");
  if (closeBtn) {
    closeBtn.addEventListener("click", hideLoginModal);
  }

  const loginForm = inner.querySelector("#loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", (event) => {
      event.preventDefault();
      submitLogin(loginForm);
    });
  }

  const registerForm = inner.querySelector("#registerForm");
  if (registerForm) {
    registerForm.addEventListener("submit", (event) => {
      event.preventDefault();
      submitRegister(registerForm);
    });
  }

  const switchToRegister = inner.querySelector("#switchToRegister");
  if (switchToRegister) {
    switchToRegister.addEventListener("click", (event) => {
      event.preventDefault();
      renderLoginModal("register");
    });
  }

  const switchToLogin = inner.querySelector("#switchToLogin");
  if (switchToLogin) {
    switchToLogin.addEventListener("click", (event) => {
      event.preventDefault();
      renderLoginModal("login");
    });
  }
}

function updateProfileIcon() {
  // Icon state reflects current auth state.
  const link = getProfileLink();
  const icon = getProfileIcon();
  if (!link || !icon) return;

  if (authState.customer) {
    icon.classList.remove("fa-user");
    icon.classList.add("fa-right-from-bracket");
    link.title = "Logout";
  } else {
    icon.classList.remove("fa-right-from-bracket");
    icon.classList.add("fa-user");
    link.title = "Login";
  }
}

async function refreshAuthState() {
  // Reads current session customer from backend.
  try {
    const data = await fetchJson(API.me, { method: "GET" });
    authState.customer = data.customer || null;
  } catch {
    authState.customer = null;
  }
  updateProfileIcon();
  await refreshCartCount();
}

async function handleProfileClick(event) {
  // Logged-in click = logout, logged-out click = open login modal.
  event.preventDefault();
  if (authState.customer) {
    try {
      await fetchJson(API.logout, { method: "POST" });
      authState.customer = null;
      updateProfileIcon();
      await refreshCartCount();
    } catch {
      // Keep UI stable on logout failure.
    }
    return;
  }

  renderLoginModal("login");
}

async function handleCartClick(event) {
  if (authState.customer) {
    return;
  }

  event.preventDefault();
  await renderLoginModal("login");
}

async function addToCart(productId, productName = "") {
  // Enforces authenticated-only cart usage in UI before API call.
  if (!authState.customer) {
    await renderLoginModal("login");
    return;
  }

  await fetchJson(API.cartAdd, {
    method: "POST",
    body: {
      product_id: productId,
      quantity: 1
    }
  });

  await refreshCartCount();
  showNotification(
    productName ? `${productName} added to cart.` : "Item added to cart.",
    "success"
  );
  document.dispatchEvent(new CustomEvent("cart:changed"));
}

async function initCategorySidebar() {
  const categoryList = document.querySelector(".category-list");
  if (!categoryList) return;

  const storeCategories = await fetchStoreCategories();
  const sidebarCategories = buildSidebarCategories(storeCategories);
  categoryList.innerHTML = sidebarCategories.map(renderCategory).join("");
}

function bindGlobalEvents() {
  const profileLink = getProfileLink();
  if (profileLink) {
    profileLink.addEventListener("click", handleProfileClick);
  }

  const cartLink = getCartLink();
  if (cartLink) {
    cartLink.addEventListener("click", (event) => {
      handleCartClick(event).catch(() => {
        showNotification("Please login to view your cart.", "warning");
      });
    });
  }

  document.body.addEventListener("click", async (event) => {
    const categoryLink = event.target.closest("[data-category]");
    if (categoryLink) {
      event.preventDefault();
      const item = decodeURIComponent(categoryLink.dataset.category || "");
      const categoryId = Number(categoryLink.dataset.categoryId || 0);

      if (window.showProducts) {
        window.showProducts({
          category: item,
          categoryId
        });
      } else {
        const url = new URL("/ecommerce/index.php", window.location.origin);
        url.searchParams.set("page", "home");
        if (categoryId > 0) {
          url.searchParams.set("category_id", String(categoryId));
        }
        if (item) {
          url.searchParams.set("category", item);
        }
        window.location.href = url.toString();
      }
      return;
    }

    const addToCartButton = event.target.closest("[data-add-to-cart]");
    if (addToCartButton) {
      // Product card -> cart add endpoint.
      event.preventDefault();
      const productId = Number(addToCartButton.dataset.productId || 0);
      const productName = addToCartButton
        .closest(".card")
        ?.querySelector(".card-title")
        ?.textContent
        ?.trim();
      if (productId > 0) {
        try {
          await addToCart(productId, productName || "");
        } catch (error) {
          showNotification(error.message, "error");
        }
      }
    }
  });
}

document.addEventListener("DOMContentLoaded", async () => {
  await initCategorySidebar();
  bindGlobalEvents();
  await refreshAuthState();
});

document.addEventListener("cart:changed", () => {
  refreshCartCount();
});
