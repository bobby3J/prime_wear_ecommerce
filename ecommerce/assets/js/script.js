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

const categories = [
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
  cartCount: "/ecommerce/shared/api/cart/count.php"
};

const authState = {
  customer: null
};

async function fetchJson(url, options = {}) {
  // Shared fetch helper used by auth/cart flows.
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

function getProfileIcon() {
  return document.querySelector(".profile-trigger i");
}

function getProfileLink() {
  return document.querySelector(".profile-trigger");
}

function getCartCountBadge() {
  return document.getElementById("cartCountBadge");
}

function setCartCount(count) {
  const badge = getCartCountBadge();
  if (!badge) return;
  badge.textContent = String(count);
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
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password })
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
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name, email, password })
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

async function addToCart(productId) {
  // Enforces authenticated-only cart usage in UI before API call.
  if (!authState.customer) {
    await renderLoginModal("login");
    return;
  }

  await fetchJson(API.cartAdd, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      product_id: productId,
      quantity: 1
    })
  });

  await refreshCartCount();
  document.dispatchEvent(new CustomEvent("cart:changed"));
}

function initCategorySidebar() {
  const categoryList = document.querySelector(".category-list");
  if (!categoryList) return;
  categoryList.innerHTML = categories.map(renderCategory).join("");
}

function bindGlobalEvents() {
  const profileLink = getProfileLink();
  if (profileLink) {
    profileLink.addEventListener("click", handleProfileClick);
  }

  document.body.addEventListener("click", async (event) => {
    const categoryLink = event.target.closest("[data-category]");
    if (categoryLink) {
      event.preventDefault();
      const item = decodeURIComponent(categoryLink.dataset.category || "");
      if (window.showProducts) {
        window.showProducts({ q: item });
      }
      return;
    }

    const addToCartButton = event.target.closest("[data-add-to-cart]");
    if (addToCartButton) {
      // Product card -> cart add endpoint.
      event.preventDefault();
      const productId = Number(addToCartButton.dataset.productId || 0);
      if (productId > 0) {
        try {
          await addToCart(productId);
        } catch (error) {
          alert(error.message);
        }
      }
    }
  });
}

document.addEventListener("DOMContentLoaded", async () => {
  initCategorySidebar();
  bindGlobalEvents();
  await refreshAuthState();
});

document.addEventListener("cart:changed", () => {
  refreshCartCount();
});
