import { renderProductCard } from "./components/ProductCard.js";

/*
Products listing loader used by home page.
API source: /shared/api/products.php
*/

export async function fetchProducts(params = {}) {

    const url = new URL('../../shared/api/products.php', import.meta.url);

    if (params.category) url.searchParams.set('category', params.category);
    if (params.q) url.searchParams.set('q', params.q);

    const response = await fetch(url);
    const json = await response.json();

    return json?.data?.items || [];
}

export async function showProducts(params = {}) {

    const container = document.querySelector(".products-row");
    if (!container) return;

    container.innerHTML = `
        <div class="col-12 text-center py-4">
            <div class="spinner-border"></div>
        </div>
    `;

    const products = await fetchProducts(params);

    if (products.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info text-center">No products found.</div>
            </div>
        `;
        return;
    }

    container.innerHTML = products
        .map(renderProductCard)
        .join('');
}

// Load all products on first page load
document.addEventListener("DOMContentLoaded", () => {
    const params = new URLSearchParams(window.location.search);
    const q = params.get("q") || "";
    if (q) {
        showProducts({ q });
        return;
    }
    showProducts();
});

window.showProducts = showProducts;
