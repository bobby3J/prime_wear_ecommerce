// /ecommerce/assets/js/components/ProductCard.js
// Card component used by product-fetch.js.
// Add-to-cart button emits data attributes consumed by assets/js/script.js.

export function renderProductCard(product) {
  const imageUrl = product.image_url || "./assets/images/shirt1.webp";
  const canAdd = Number(product.stock || 0) > 0;
  const buttonLabel = canAdd ? "Add to Cart" : "Out of Stock";
  const disabledAttr = canAdd ? "" : "disabled";

  return `
    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
      <div class="card shadow-sm w-100">
        <img src="${imageUrl}" 
             class="card-img-top" 
             alt="${product.title}" 
             style="object-fit: cover; height: 220px;">
        <div class="card-body d-flex flex-column">
          <h5 class="card-title">${product.title}</h5>
          <p class="card-text flex-grow-1">${product.description}</p>
          <div class="mt-auto d-flex justify-content-between align-items-center">
            <span class="fw-bold text-info">$${product.price.toFixed(2)}</span>
            <button
              class="btn btn-primary btn-sm"
              data-add-to-cart
              data-product-id="${product.id}"
              ${disabledAttr}
            >
              ${buttonLabel}
            </button>
          </div>
        </div>
      </div>
    </div>
  `;
}
