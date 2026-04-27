function getQueryParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}

function riskClass(level) {
    return 'risk-' + (level || 'Unknown');
}

// ... (keep your existing getQueryParam and riskClass functions)

function renderProducts(products) {
    const container = document.getElementById('product-container');
    const resultCount = document.getElementById('resultCount');
    container.innerHTML = '';
    resultCount.textContent = `Showing ${products.length} results`;

    products.forEach(product => {
        const card = document.createElement('div');
        card.className = 'product-card';
        
        card.onclick = () => {
            window.location.href = `details.html?id=${product.barcode}`;
        };

        const imgPlaceholder = `https://picsum.photos/seed/${product.barcode}/200/200`;

        card.innerHTML = `
            <div class="product-image-container">
                <img class="product-image" 
                     src="${product.image}" 
                     alt="${product.name}"
                     onerror="this.src='${imgPlaceholder}'">
            </div>
            <div class="product-info">
                <p class="product-brand">${product.brand}</p>
                <h3 class="product-name">${product.name}</h3>
                <div class="risk-tag risk-${product.riskLevel}">
                    ${product.riskLevel} Risk
                </div>
                <div class="nutrition-preview">
                    <span>${product.nutrition.calories} kcal</span> • 
                    <span>${product.nutrition.sugar_g}g sugar</span>
                </div>
                <button class="btn-view-details">Analyze Ingredients</button>
            </div>
        `;
        container.appendChild(card);
    });
}

// Keep your existing filtering and DOMContentLoaded logic below...
// ... (rest of your category logic)
document.addEventListener('DOMContentLoaded', () => {
    const catParam = getQueryParam('cat');
    const titleEl = document.getElementById('categoryTitle');
    const searchEl = document.getElementById('productSearch');
    const sortEl = document.getElementById('sortProducts');
    const riskCheckboxes = document.querySelectorAll('.filter-risk');

    if (!catParam) {
        titleEl.textContent = 'Browse Products';
        return;
    }

    const category = decodeURIComponent(catParam);
    titleEl.textContent = category;
    const categoryData = window.products && window.products[category];

    if (!categoryData) {
        document.getElementById('product-container').innerHTML = '<p style="padding:20px;">No products found.</p>';
        return;
    }

    // --- NEW CORE FILTER LOGIC ---
    function applyFilters() {
        const query = searchEl.value.toLowerCase();
        
        // Get array of checked risk levels (e.g., ["Low", "High"])
        const selectedRisks = Array.from(riskCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        let filtered = categoryData.filter(p => {
            const matchesSearch = p.name.toLowerCase().includes(query) || p.brand.toLowerCase().includes(query);
            
            // If no checkboxes are checked, show everything. 
            // If some are checked, only show those that match.
            const matchesRisk = selectedRisks.length === 0 || selectedRisks.includes(p.riskLevel);
            
            return matchesSearch && matchesRisk;
        });

        // Handle Sorting
        const sortBy = sortEl.value;
        if (sortBy === 'risk-low') {
            const order = { 'Low': 1, 'Medium': 2, 'High': 3 };
            filtered.sort((a, b) => order[a.riskLevel] - order[b.riskLevel]);
        } else if (sortBy === 'calories') {
            filtered.sort((a, b) => a.nutrition.calories - b.nutrition.calories);
        }

        renderProducts(filtered);
    }

    // --- EVENT LISTENERS ---

    // 1. Listen for typing in search
    searchEl.addEventListener('input', applyFilters);

    // 2. Listen for checkbox changes
    riskCheckboxes.forEach(cb => {
        cb.addEventListener('change', applyFilters);
    });

    // 3. Listen for sort changes
    sortEl.addEventListener('change', applyFilters);

    // Initial Render
    renderProducts(categoryData);
});