// ============================================================
// FILE: public/assets/js/search.js
// PURPOSE: Live search functionality
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // DOM REFERENCES
    // ============================================
    
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    const searchForm = document.querySelector('.search-bar form');
    
    // ============================================
    // LIVE SEARCH (Autocomplete)
    // ============================================
    
    let searchTimeout = null;
    let currentQuery = '';
    
    if (searchInput) {
        // Create dropdown container for suggestions
        const suggestionsDropdown = document.createElement('div');
        suggestionsDropdown.className = 'search-suggestions';
        suggestionsDropdown.style.display = 'none';
        searchInput.parentElement.appendChild(suggestionsDropdown);
        
        // Input event listener
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            currentQuery = query;
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                suggestionsDropdown.style.display = 'none';
                return;
            }
            
            // Debounce: Wait 300ms before searching
            searchTimeout = setTimeout(function() {
                fetchSuggestions(query);
            }, 300);
        });
        
        // Keyboard navigation for suggestions
        searchInput.addEventListener('keydown', function(e) {
            const items = suggestionsDropdown.querySelectorAll('.suggestion-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (items.length > 0) {
                    const current = suggestionsDropdown.querySelector('.suggestion-item.active');
                    if (current) {
                        current.classList.remove('active');
                        const next = current.nextElementSibling || items[0];
                        next.classList.add('active');
                    } else {
                        items[0].classList.add('active');
                    }
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const items = suggestionsDropdown.querySelectorAll('.suggestion-item');
                if (items.length > 0) {
                    const current = suggestionsDropdown.querySelector('.suggestion-item.active');
                    if (current) {
                        current.classList.remove('active');
                        const prev = current.previousElementSibling || items[items.length - 1];
                        prev.classList.add('active');
                    } else {
                        items[items.length - 1].classList.add('active');
                    }
                }
            } else if (e.key === 'Enter') {
                const active = suggestionsDropdown.querySelector('.suggestion-item.active');
                if (active) {
                    e.preventDefault();
                    const url = active.getAttribute('data-url');
                    if (url) {
                        window.location.href = url;
                    } else {
                        const value = active.getAttribute('data-value');
                        if (value) {
                            searchInput.value = value;
                            suggestionsDropdown.style.display = 'none';
                            searchForm.submit();
                        }
                    }
                }
            } else if (e.key === 'Escape') {
                suggestionsDropdown.style.display = 'none';
            }
        });
        
        // Close suggestions on blur
        searchInput.addEventListener('blur', function() {
            setTimeout(function() {
                suggestionsDropdown.style.display = 'none';
            }, 200);
        });
        
        // ============================================
        // FETCH SUGGESTIONS
        // ============================================
        
        function fetchSuggestions(query) {
            fetch('search.php?suggest=1&q=' + encodeURIComponent(query) + '&ajax=1')
                .then(response => response.json())
                .then(data => {
                    displaySuggestions(data.suggestions, query);
                })
                .catch(error => {
                    console.error('Error fetching suggestions:', error);
                });
        }
        
        function displaySuggestions(suggestions, query) {
            const dropdown = suggestionsDropdown;
            
            if (!suggestions || suggestions.length === 0) {
                dropdown.style.display = 'none';
                return;
            }
            
            let html = '';
            
            // Group suggestions by type
            const products = suggestions.filter(s => s.type === 'product');
            const categories = suggestions.filter(s => s.type === 'category');
            
            if (products.length > 0) {
                html += '<div class="suggestion-group">';
                html += '<div class="suggestion-group-label">Products</div>';
                products.forEach(function(item) {
                    html += `
                        <a href="${item.url || 'product_details.php?id=' + item.id}" 
                           class="suggestion-item" 
                           data-value="${item.name}">
                            <span class="suggestion-icon">
                                <i class="fas fa-box"></i>
                            </span>
                            <span class="suggestion-text">${item.name}</span>
                            ${item.price ? `<span class="suggestion-price">$${item.price}</span>` : ''}
                        </a>
                    `;
                });
                html += '</div>';
            }
            
            if (categories.length > 0) {
                html += '<div class="suggestion-group">';
                html += '<div class="suggestion-group-label">Categories</div>';
                categories.forEach(function(item) {
                    html += `
                        <a href="products.php?category=${item.id}" 
                           class="suggestion-item" 
                           data-value="${item.name}">
                            <span class="suggestion-icon">
                                <i class="fas fa-tag"></i>
                            </span>
                            <span class="suggestion-text">${item.name}</span>
                        </a>
                    `;
                });
                html += '</div>';
            }
            
            // Add "View all results" link
            html += `
                <a href="search.php?q=${encodeURIComponent(query)}" class="suggestion-view-all">
                    <i class="fas fa-search"></i> View all results for "${query}"
                </a>
            `;
            
            dropdown.innerHTML = html;
            dropdown.style.display = 'block';
            
            // Position the dropdown
            const rect = searchInput.getBoundingClientRect();
            dropdown.style.top = rect.bottom + 'px';
            dropdown.style.left = rect.left + 'px';
            dropdown.style.width = rect.width + 'px';
        }
    }
    
    // ============================================
    // FILTER UPDATE (Search page)
    // ============================================
    
    window.updateSearch = function() {
        const category = document.getElementById('categoryFilter')?.value || '';
        const sort = document.getElementById('sortFilter')?.value || '';
        const query = currentQuery || '';
        
        let url = 'search.php?q=' + encodeURIComponent(query);
        if (category) url += '&category=' + category;
        if (sort) url += '&sort=' + sort;
        
        window.location.href = url;
    };
    
    // ============================================
    // LIVE SEARCH RESULTS (Real-time on search page)
    // ============================================
    
    if (window.location.pathname.includes('search.php')) {
        const searchPageInput = document.querySelector('.search-page .search-input');
        if (searchPageInput) {
            let resultTimeout = null;
            
            searchPageInput.addEventListener('input', function() {
                clearTimeout(resultTimeout);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    return;
                }
                
                resultTimeout = setTimeout(function() {
                    fetchLiveResults(query);
                }, 500);
            });
        }
    }
    
    function fetchLiveResults(query) {
        const resultsContainer = document.getElementById('searchResults');
        if (!resultsContainer) return;
        
        resultsContainer.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
        
        fetch('search.php?q=' + encodeURIComponent(query) + '&ajax=1')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayLiveResults(data, resultsContainer);
                } else {
                    resultsContainer.innerHTML = '<p>Error searching. Please try again.</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultsContainer.innerHTML = '<p>Error searching. Please try again.</p>';
            });
    }
    
    function displayLiveResults(data, container) {
        if (data.total === 0) {
            container.innerHTML = `
                <div class="no-results">
                    <div class="no-results-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h2>No products found</h2>
                    <p>No products match "<strong>${data.query}</strong>".</p>
                </div>
            `;
            return;
        }
        
        let html = `
            <div class="products-grid">
                <div class="result-info">
                    Found ${data.total} product(s)
                </div>
        `;
        
        data.results.forEach(function(product) {
            html += `
                <div class="product-card">
                    <div class="product-image">
                        <a href="${product.url}">
                            <img src="${product.image}" alt="${product.name}" loading="lazy">
                        </a>
                        <span class="badge ${product.stock > 0 ? 'in-stock' : 'out-of-stock'}">
                            ${product.stock > 0 ? 'In Stock' : 'Out of Stock'}
                        </span>
                    </div>
                    <div class="product-info">
                        <span class="product-category">
                            <i class="fas fa-tag"></i> ${product.category}
                        </span>
                        <h3 class="product-name">
                            <a href="${product.url}">${product.name}</a>
                        </h3>
                        <div class="product-price">
                            <span class="current-price">$${product.price}</span>
                        </div>
                        <div class="product-stock">
                            <span class="stock-indicator ${product.stock > 0 ? 'in-stock' : 'out-of-stock'}"></span>
                            ${product.stock > 0 ? product.stock + ' available' : 'Sold out'}
                        </div>
                        ${product.stock > 0 ? `
                            <button class="btn btn-primary add-to-cart" 
                                    data-id="${product.id}"
                                    data-name="${product.name}"
                                    data-price="${product.price}">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        ` : `
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-times-circle"></i> Out of Stock
                            </button>
                        `}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    // ============================================
    // SEARCH SUGGESTIONS STYLES
    // ============================================
    
    const searchStyles = document.createElement('style');
    searchStyles.textContent = `
        .search-suggestions {
            position: fixed;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            z-index: 9999;
            max-height: 400px;
            overflow-y: auto;
            padding: 8px 0;
            border: 1px solid #eef2f7;
        }
        
        .suggestion-group {
            padding: 4px 0;
        }
        
        .suggestion-group-label {
            padding: 4px 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }
        
        .suggestion-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            text-decoration: none;
            color: #1a1a2e;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        
        .suggestion-item:hover,
        .suggestion-item.active {
            background: #f8fafc;
        }
        
        .suggestion-icon {
            width: 32px;
            height: 32px;
            background: #f0f4ff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2C3E8F;
            font-size: 14px;
        }
        
        .suggestion-text {
            flex: 1;
            font-size: 14px;
        }
        
        .suggestion-price {
            font-size: 13px;
            font-weight: 600;
            color: #2C3E8F;
        }
        
        .suggestion-view-all {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            color: #2C3E8F;
            font-weight: 600;
            text-decoration: none;
            border-top: 1px solid #f0f0f0;
            transition: background 0.2s ease;
        }
        
        .suggestion-view-all:hover {
            background: #f8fafc;
        }
        
        .loading-spinner {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .loading-spinner i {
            font-size: 24px;
            margin-right: 8px;
        }
        
        .result-info {
            grid-column: 1 / -1;
            padding: 8px 0;
            font-size: 14px;
            color: #6c757d;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: 12px;
        }
    `;
    document.head.appendChild(searchStyles);
    
    console.log('✅ Search functionality loaded successfully!');
});