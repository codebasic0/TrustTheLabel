function getQueryParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}

function renderProducts(products) {
    const container = document.getElementById('product-container');
    const resultCount = document.getElementById('resultCount');
    container.innerHTML = '';
    resultCount.textContent = `Showing ${products.length} result${products.length !== 1 ? 's' : ''}`;

    if (products.length === 0) {
        container.innerHTML = '<p style="padding:20px;color:#7a8499;">No products match your filters.</p>';
        return;
    }

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

function showLoading() {
    const container = document.getElementById('product-container');
    container.innerHTML = '<p style="padding:20px;color:#7a8499;">Loading products...</p>';
}

function showError(msg) {
    const container = document.getElementById('product-container');
    container.innerHTML = `<p style="padding:20px;color:#ff4f4f;">${msg}</p>`;
}

document.addEventListener('DOMContentLoaded', async () => {
    const catParam = getQueryParam('cat');
    const titleEl = document.getElementById('categoryTitle');
    const searchEl = document.getElementById('productSearch');
    const sortEl = document.getElementById('sortProducts');
    const riskCheckboxes = document.querySelectorAll('.filter-risk');
    const resultCount = document.getElementById('resultCount');

    showLoading();

    let allData = {};
    try {
        const res = await fetch('api.php?action=all');
        if (!res.ok) throw new Error('Server error');
        allData = await res.json();
    } catch (e) {
        showError('Could not load products. Make sure api.php is running.');
        return;
    }

    if (!catParam) {
        titleEl.textContent = 'Browse Products';
        const allProducts = Object.values(allData).flat();
        renderProducts(allProducts);

        searchEl.addEventListener('input', () => {
            const q = searchEl.value.toLowerCase();
            renderProducts(Object.values(allData).flat().filter(p =>
                p.name.toLowerCase().includes(q) || p.brand.toLowerCase().includes(q)
            ));
        });
        return;
    }

    const category = decodeURIComponent(catParam);
    titleEl.textContent = category;
    const categoryData = allData[category];

    if (!categoryData || categoryData.length === 0) {
        showError('No products found in this category.');
        return;
    }

    function applyFilters() {
        const query = searchEl.value.toLowerCase();
        const selectedRisks = Array.from(riskCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        let filtered = categoryData.filter(p => {
            const matchesSearch = p.name.toLowerCase().includes(query) || p.brand.toLowerCase().includes(query);
            const matchesRisk = selectedRisks.length === 0 || selectedRisks.includes(p.riskLevel);
            return matchesSearch && matchesRisk;
        });

        const sortBy = sortEl.value;
        if (sortBy === 'risk-low') {
            const order = { 'Low': 1, 'Medium': 2, 'High': 3 };
            filtered.sort((a, b) => (order[a.riskLevel] || 9) - (order[b.riskLevel] || 9));
        } else if (sortBy === 'calories') {
            filtered.sort((a, b) => a.nutrition.calories - b.nutrition.calories);
        }

        renderProducts(filtered);
    }

    searchEl.addEventListener('input', applyFilters);
    riskCheckboxes.forEach(cb => cb.addEventListener('change', applyFilters));
    sortEl.addEventListener('change', applyFilters);

    renderProducts(categoryData);
});