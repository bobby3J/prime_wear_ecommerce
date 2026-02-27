// ecommerce/assets/js/components/CategoryCard.js

export function renderCategory(category) {
  const items = category.items
    .map((item) => {
      const itemLabel = typeof item === "string"
        ? item
        : (item.label || item.name || "");
      const itemValue = typeof item === "string"
        ? item
        : (item.value || item.slug || itemLabel);
      const categoryId = typeof item === "object" && item
        ? Number(item.categoryId || item.id || 0)
        : 0;
      const categoryIdAttr = categoryId > 0
        ? ` data-category-id="${categoryId}"`
        : "";

      return `
      <li>
        <a href="#" 
           class="text-dark text-decoration-none category-link"
           data-category="${encodeURIComponent(itemValue)}"${categoryIdAttr}>
          ${itemLabel}
        </a>
      </li>
    `;
    })
    .join('');

  return `
    <li class="mb-2">
      <a class="d-flex align-items-center justify-content-between text-dark text-decoration-none fw-semibold category-toggle"
         data-bs-toggle="collapse" href="#${category.id}" role="button">
        <span><i class="${category.icon} me-2 text-info"></i> ${category.name}</span>
        <i class="fa-solid fa-chevron-down small"></i>
      </a>
      <ul class="collapse ms-4 mt-2 small" id="${category.id}">
        ${items}
      </ul>
    </li>
  `;
}

