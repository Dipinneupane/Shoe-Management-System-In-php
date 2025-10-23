let navbar = document.querySelector('.header .navbar');
let accountBox = document.querySelector('.header .account-box');

document.querySelector('#menu-btn').onclick = () =>{
   navbar.classList.toggle('active');
   accountBox.classList.remove('active');
};

document.querySelector('#user-btn').onclick = () =>{
   accountBox.classList.toggle('active');
   navbar.classList.remove('active');
};

window.onscroll = () =>{
   navbar.classList.remove('active');
   accountBox.classList.remove('active');
};

// Admin Orders Page Functions
function searchOrders() {
  const input = document.getElementById('orderSearch');
  const filter = input.value.toUpperCase();
  const orderItems = document.querySelectorAll('.order-item');

  orderItems.forEach(item => {
    const customerName = item.querySelector('.customer-name').textContent;
    const customerEmail = item.querySelector('.customer-contact').textContent;
    const orderId = item.querySelector('.order-id').textContent;
    
    if (
      customerName.toUpperCase().indexOf(filter) > -1 ||
      customerEmail.toUpperCase().indexOf(filter) > -1 ||
      orderId.toUpperCase().indexOf(filter) > -1
    ) {
      item.style.display = '';
    } else {
      item.style.display = 'none';
    }
  });
}

function filterOrders() {
  const filterValue = document.getElementById('statusFilter').value;
  const orderItems = document.querySelectorAll('.order-item');

  orderItems.forEach(item => {
    if (filterValue === 'all' || item.getAttribute('data-status') === filterValue) {
      item.style.display = '';
    } else {
      item.style.display = 'none';
    }
  });
}

// Order Details Modal
function viewOrderDetails(orderId) {
  const modal = document.getElementById('orderDetailsModal');
  const modalContent = document.getElementById('orderDetailsContent');
  
  // In a real application, you would fetch the order details from the server
  // For now, we'll just display a placeholder message
  modalContent.innerHTML = `
    <div class="order-detail-header">
      <h3>Order #${orderId}</h3>
    </div>
    <div class="order-detail-content">
      <p>Loading order details for order #${orderId}...</p>
      <p>In a production environment, this would fetch detailed information about this order from the server.</p>
    </div>
  `;
  
  modal.style.display = 'block';
}

// Close Modal
const modal = document.getElementById('orderDetailsModal');
if (modal) {
  const closeBtn = modal.querySelector('.close');
  if (closeBtn) {
    closeBtn.onclick = function() {
      modal.style.display = 'none';
    }
  }

  // Close modal when clicking outside the content
  window.onclick = function(event) {
    if (event.target === modal) {
      modal.style.display = 'none';
    }
  }
}

// Toggle dropdown menus
document.addEventListener('DOMContentLoaded', function() {
  const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
  
  dropdownToggles.forEach(toggle => {
    toggle.addEventListener('click', function() {
      const dropdown = this.nextElementSibling;
      
      // Close all other dropdowns
      document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (menu !== dropdown) {
          menu.classList.remove('active');
        }
      });
      
      // Toggle current dropdown
      dropdown.classList.toggle('active');
    });
  });
  
  // Close dropdowns when clicking outside
  document.addEventListener('click', function(event) {
    if (!event.target.closest('.action-dropdown')) {
      document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.classList.remove('active');
      });
    }
  });
});

// Product Management Page Functions

// Add Product Form Display
const addProductBtn = document.getElementById('addProductBtn');
const addProductForm = document.getElementById('addProductForm');
const closeAddForm = document.getElementById('closeAddForm');

if (addProductBtn && addProductForm) {
  addProductBtn.addEventListener('click', function() {
    addProductForm.classList.add('active');
  });
  
  if (closeAddForm) {
    closeAddForm.addEventListener('click', function() {
      addProductForm.classList.remove('active');
    });
  }
}

// Edit Product Modal
const closeUpdate = document.getElementById('close-update');
const cancelUpdate = document.getElementById('cancel-update');
const editProductModal = document.getElementById('editProductModal');

if (closeUpdate && editProductModal) {
  closeUpdate.onclick = function() {
    editProductModal.style.display = 'none';
    window.location.href = 'admin_products.php';
  }
}

if (cancelUpdate && editProductModal) {
  cancelUpdate.onclick = function() {
    editProductModal.style.display = 'none';
    window.location.href = 'admin_products.php';
  }
}

// Close modal when clicking outside
window.onclick = function(event) {
  if (event.target === editProductModal) {
    editProductModal.style.display = 'none';
    window.location.href = 'admin_products.php';
  }
}

// File Input Display
const productImage = document.getElementById('productImage');
const fileName = document.getElementById('fileName');
const updateImage = document.getElementById('updateImage');
const updateFileName = document.getElementById('updateFileName');

if (productImage && fileName) {
  productImage.addEventListener('change', function() {
    if (this.files.length > 0) {
      fileName.textContent = this.files[0].name;
    } else {
      fileName.textContent = 'No file chosen';
    }
  });
}

if (updateImage && updateFileName) {
  updateImage.addEventListener('change', function() {
    if (this.files.length > 0) {
      updateFileName.textContent = this.files[0].name;
    } else {
      updateFileName.textContent = 'No file chosen';
    }
  });
}

// Search Products
function searchProducts() {
  const input = document.getElementById('productSearch');
  const filter = input.value.toUpperCase();
  const productCards = document.querySelectorAll('.product-card');

  productCards.forEach(card => {
    const productName = card.querySelector('.product-name').textContent;
    const productDescription = card.querySelector('.product-description').textContent;
    
    if (
      productName.toUpperCase().indexOf(filter) > -1 ||
      productDescription.toUpperCase().indexOf(filter) > -1
    ) {
      card.style.display = '';
    } else {
      card.style.display = 'none';
    }
  });
}

// Confirm Delete
function confirmDelete(productId) {
  if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
    window.location.href = `admin_products.php?delete=${productId}`;
  }
}