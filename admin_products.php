<?php

include 'config.php';

// Only start session if one doesn't already exist
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:login.php');
};

if(isset($_POST['add_product'])){

   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $price = $_POST['price'];
   $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 0;
   $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';
   $category = isset($_POST['category']) ? mysqli_real_escape_string($conn, $_POST['category']) : '';
   $brand = isset($_POST['brand']) ? mysqli_real_escape_string($conn, $_POST['brand']) : '';
   $type = isset($_POST['type']) ? mysqli_real_escape_string($conn, $_POST['type']) : '';
   $sizes = isset($_POST['sizes']) ? mysqli_real_escape_string($conn, $_POST['sizes']) : '';
   $image = $_FILES['image']['name'];
   $image_size = $_FILES['image']['size'];
   $image_tmp_name = $_FILES['image']['tmp_name'];
   $image_folder = 'uploaded_img/'.$image;

   $select_product_name = mysqli_query($conn, "SELECT name FROM `products` WHERE name = '$name'") or die('query failed');

   if(mysqli_num_rows($select_product_name) > 0){
      $message[] = 'product name already added';
   }else{
      if($image_size > 2000000){
         $message[] = 'image size is too large (max 2MB)';
      }else{
         $add_product_query = mysqli_query($conn, "INSERT INTO `products`(name, price, quantity, description, category, brand, type, sizes, image) VALUES('$name', '$price', '$quantity', '$description', '$category', '$brand', '$type', '$sizes', '$image')") or die('query failed');

         if($add_product_query){
            move_uploaded_file($image_tmp_name, $image_folder);
            
            // Get the ID of the newly added product
            $product_id = mysqli_insert_id($conn);
            
            
            
            $message[] = 'product added successfully!';
         }else{
            $message[] = 'product could not be added!';
         }
      }
   }
}

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   $delete_image_query = mysqli_query($conn, "SELECT image FROM `products` WHERE id = '$delete_id'") or die('query failed');
   $fetch_delete_image = mysqli_fetch_assoc($delete_image_query);
    $image_path = 'uploaded_img/'.$fetch_delete_image['image'];
    if (file_exists($image_path) && is_file($image_path)) {
        unlink($image_path);
    }
    
    mysqli_query($conn, "DELETE FROM `products` WHERE id = '$delete_id'") or die('query failed');
   header('location:admin_products.php');
}

if(isset($_POST['update_product'])){

   $update_p_id = $_POST['update_p_id'];
   $update_name = mysqli_real_escape_string($conn, $_POST['update_name']);
   $update_price = $_POST['update_price'];
   $update_quantity = isset($_POST['update_quantity']) ? $_POST['update_quantity'] : 0;
   $update_description = isset($_POST['update_description']) ? mysqli_real_escape_string($conn, $_POST['update_description']) : '';
   $update_category = isset($_POST['update_category']) ? mysqli_real_escape_string($conn, $_POST['update_category']) : '';
   $update_brand = isset($_POST['update_brand']) ? mysqli_real_escape_string($conn, $_POST['update_brand']) : '';
   $update_type = isset($_POST['update_type']) ? mysqli_real_escape_string($conn, $_POST['update_type']) : '';
   $update_sizes = isset($_POST['update_sizes']) ? mysqli_real_escape_string($conn, $_POST['update_sizes']) : '';

   mysqli_query($conn, "UPDATE `products` SET 
      name = '$update_name', 
      price = '$update_price', 
      quantity = '$update_quantity', 
      description = '$update_description', 
      category = '$update_category', 
      brand = '$update_brand', 
      type = '$update_type',
      sizes = '$update_sizes'
      WHERE id = '$update_p_id'") or die('query failed');

   $update_image = $_FILES['update_image']['name'];
   $update_image_tmp_name = $_FILES['update_image']['tmp_name'];
   $update_image_size = $_FILES['update_image']['size'];
   $update_folder = 'uploaded_img/'.$update_image;
   $update_old_image = $_POST['update_old_image'];

   if(!empty($update_image)){
      if($update_image_size > 2000000){
         $message[] = 'image file size is too large (max 2MB)';
      }else{
         mysqli_query($conn, "UPDATE `products` SET image = '$update_image' WHERE id = '$update_p_id'") or die('query failed');
         move_uploaded_file($update_image_tmp_name, $update_folder);
         unlink('uploaded_img/'.$update_old_image);
      }
   }

   $message[] = 'Product updated successfully!';
   header('location:admin_products.php');

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Products</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom admin css file link  -->
   <link rel="stylesheet" href="css/admin_style.css">
   <link rel="stylesheet" href="css/admin_style_sizes.css">

</head>
<body>
   
<?php include 'admin_header.php'; ?>

<!-- product CRUD section starts  -->

<section class="admin-section products-section">
   <div class="section-header">
      <h1 class="title">Product Management</h1>
      <div class="section-actions">
         <button type="button" id="addProductBtn" class="action-button primary-button">
            <i class="fas fa-plus"></i> Add New Product
         </button>
      </div>
   </div>

   <div id="addProductForm" class="form-container">
      <div class="form-header">
         <h3><i class="fas fa-plus-circle"></i> Add New Product</h3>
         <button type="button" id="closeAddForm" class="close-form">&times;</button>
      </div>
      <form action="" method="post" enctype="multipart/form-data" class="product-form">
         <div class="form-row">
            <div class="form-column">
               <div class="form-group">
                  <label for="productName">Product Name</label>
                  <input type="text" id="productName" name="name" placeholder="Enter product name" required>
               </div>
               
               <div class="form-group">
                  <label for="productPrice">Price (Rs)</label>
                  <input type="number" id="productPrice" name="price" min="0" placeholder="Enter product price" required>
               </div>
               
               <div class="form-group">
                  <label for="productQuantity">Quantity in Stock</label>
                  <input type="number" id="productQuantity" name="quantity" min="0" placeholder="Enter available quantity">
               </div>
               
               <div class="form-group">
                  <label for="productDescription">Description</label>
                  <textarea id="productDescription" name="description" placeholder="Enter product description"></textarea>
               </div>
            </div>
            
            <div class="form-column">
               <div class="form-group file-input-group">
                  <label for="productImage">Product Image</label>
                  <div class="file-input-container">
                     <input type="file" id="productImage" name="image" accept="image/jpg, image/jpeg, image/png" required>
                     <div class="file-input-button">
                        <i class="fas fa-cloud-upload-alt"></i> Choose Image
                     </div>
                     <div class="file-name" id="fileName">No file chosen</div>
                  </div>
                  <small>Accepted formats: JPG, JPEG, PNG. Max size: 2MB</small>
               </div>
               
               <div class="form-group">
                  <label for="productCategory">Category</label>
                  <select id="productCategory" name="category">
                     <option value="">Select a category</option>
                     <option value="men">Men's Shoes</option>
                     <option value="women">Women's Shoes</option>
                     <option value="kids">Kids' Shoes</option>
                     <option value="sports">Sports Shoes</option>
                     <option value="casual">Casual Shoes</option>
                     <option value="formal">Formal Shoes</option>
                  </select>
               </div>
               
               <div class="form-group">
                  <label for="productBrand">Brand</label>
                  <select id="productBrand" name="brand" required>
                     <option value="">Select a brand</option>
                     <option value="Nike">Nike</option>
                     <option value="Adidas">Adidas</option>
                     <option value="Goldstar">Goldstar</option>
                     <option value="Puma">Puma</option>
                     <option value="Reebok">Reebok</option>
                     <option value="Bata">Bata</option>
                     <option value="Converse">Converse</option>
                     <option value="Other">Other</option>
                  </select>
               </div>
               
               <div class="form-group">
                  <label for="productType">Type</label>
                  <select id="productType" name="type" required>
                     <option value="">Select type</option>
                     <option value="sports">Sports</option>
                     <option value="sneakers">Sneakers</option>
                     <option value="casual">Casual</option>
                     <option value="formal">Formal</option>
                     <option value="boots">Boots</option>
                     <option value="sandals">Sandals</option>
                     <option value="slippers">Slippers</option>
                     <option value="other">Other</option>
                  </select>
               </div>
               
               <div class="form-group">
                  <label for="productSizes">Sizes</label>
                  <input type="text" id="productSizes" name="sizes" placeholder="e.g. 40,41,42" pattern="^\d+(,\s*\d+)*$" required oninvalid="this.setCustomValidity('Enter sizes as numbers separated by commas, e.g. 40,41,42')" oninput="this.setCustomValidity('')">
                  <small>Enter available sizes separated by commas (e.g. 40, 41, 42)</small>
               </div>
            </div>
         </div>
         

         <div class="form-actions">
            <button type="reset" class="secondary-button">Reset</button>
            <button type="submit" name="add_product" class="primary-button">Add Product</button>
         </div>
      </form>
   </div>
</section>

<!-- product CRUD section ends -->

<!-- show products  -->

<section class="products-display">
   <div class="section-header">
      <h2 class="section-title">Product Inventory</h2>
      <div class="section-actions">
         <div class="search-container">
            <input type="text" id="productSearch" placeholder="Search products..." onkeyup="searchProducts()">
            <i class="fas fa-search"></i>
         </div>
      </div>
   </div>

   <div class="products-grid">
      <?php
         $select_products = mysqli_query($conn, "SELECT * FROM `products` ORDER BY id DESC") or die('query failed');
         if(mysqli_num_rows($select_products) > 0){
            while($fetch_products = mysqli_fetch_assoc($select_products)){
      ?>
      <div class="product-card">
         <div class="product-image">
            <img src="uploaded_img/<?php echo $fetch_products['image']; ?>" alt="<?php echo $fetch_products['name']; ?>">
            <div class="product-actions">
               <button type="button" class="action-btn edit-btn" onclick="location.href='admin_products.php?update=<?php echo $fetch_products['id']; ?>'">
                  <i class="fas fa-edit"></i>
               </button>
               <button type="button" class="action-btn delete-btn" onclick="confirmDelete(<?php echo $fetch_products['id']; ?>)">
                  <i class="fas fa-trash-alt"></i>
               </button>
            </div>
         </div>
         <div class="product-details">
            <h3 class="product-name"><?php echo $fetch_products['name']; ?></h3>
            <div class="product-brand-type">
               <span class="product-brand"><strong>Brand:</strong> <?php echo $fetch_products['brand']; ?></span> |
               <span class="product-type"><strong>Type:</strong> <?php echo $fetch_products['type']; ?></span> |
               <span class="product-sizes"><strong>Sizes:</strong> <?php echo $fetch_products['sizes']; ?></span>
            </div>
            <div class="product-meta">
               <div class="product-price">Rs<?php echo $fetch_products['price']; ?>/-</div>
               <div class="product-stock">
                  <?php if(isset($fetch_products['quantity']) && $fetch_products['quantity'] > 0): ?>
                     <span class="in-stock"><i class="fas fa-check-circle"></i> In Stock (<?php echo $fetch_products['quantity']; ?>)</span>
                  <?php else: ?>
                     <span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>
                  <?php endif; ?>
               </div>
            </div>
            <div class="product-description">
               <?php if(isset($fetch_products['description']) && !empty($fetch_products['description'])): ?>
                  <?php echo substr($fetch_products['description'], 0, 100); ?><?php echo (strlen($fetch_products['description']) > 100) ? '...' : ''; ?>
               <?php else: ?>
                  <span class="no-description">No description available</span>
               <?php endif; ?>
            </div>
         </div>
      </div>
      <?php
         }
      } else {
      ?>
      <div class="empty-state">
         <div class="empty-icon"><i class="fas fa-box-open"></i></div>
         <h3>No Products Found</h3>
         <p>You haven't added any products yet. Click the "Add New Product" button to get started.</p>
      </div>
      <?php
      }
      ?>
   </div>
</section>

<!-- Edit Product Modal -->
<div class="modal" id="editProductModal">
   <?php
      if(isset($_GET['update'])){
         $update_id = $_GET['update'];
         $update_query = mysqli_query($conn, "SELECT * FROM `products` WHERE id = '$update_id'") or die('query failed');
         if(mysqli_num_rows($update_query) > 0){
            while($fetch_update = mysqli_fetch_assoc($update_query)){
   ?>
   <div class="modal-content edit-product-form">
      <div class="modal-header">
         <h2><i class="fas fa-edit"></i> Edit Product</h2>
         <span class="close" id="close-update">&times;</span>
      </div>
      <div class="modal-body">
         <form action="" method="post" enctype="multipart/form-data" class="product-form">
            <input type="hidden" name="update_p_id" value="<?php echo $fetch_update['id']; ?>">
            <input type="hidden" name="update_old_image" value="<?php echo $fetch_update['image']; ?>">
            
            <div class="form-row">
               <div class="form-column">
                  <div class="form-group">
                     <label for="updateName">Product Name</label>
                     <input type="text" id="updateName" name="update_name" value="<?php echo $fetch_update['name']; ?>" required placeholder="Enter product name">
                  </div>
                  
                  <div class="form-group">
                     <label for="updatePrice">Price (Rs)</label>
                     <input type="number" id="updatePrice" name="update_price" value="<?php echo $fetch_update['price']; ?>" min="0" required placeholder="Enter product price">
                  </div>
                  
                  <div class="form-group">
                     <label for="updateQuantity">Quantity in Stock</label>
                     <input type="number" id="updateQuantity" name="update_quantity" value="<?php echo isset($fetch_update['quantity']) ? $fetch_update['quantity'] : '0'; ?>" min="0" placeholder="Enter available quantity">
                  </div>
                  
                  <div class="form-group">
                     <label for="updateDescription">Description</label>
                     <textarea id="updateDescription" name="update_description" placeholder="Enter product description"><?php echo isset($fetch_update['description']) ? $fetch_update['description'] : ''; ?></textarea>
                  </div>
               </div>
               
               <div class="form-column">
                  <div class="current-image-container">
                     <label>Current Image</label>
                     <div class="current-image">
                        <img src="uploaded_img/<?php echo $fetch_update['image']; ?>" alt="<?php echo $fetch_update['name']; ?>">
                     </div>
                  </div>
                  
                  <div class="form-group file-input-group">
                     <label for="updateImage">Change Image (Optional)</label>
                     <div class="file-input-container">
                        <input type="file" id="updateImage" name="update_image" accept="image/jpg, image/jpeg, image/png">
                        <div class="file-input-button">
                           <i class="fas fa-cloud-upload-alt"></i> Choose New Image
                        </div>
                        <div class="file-name" id="updateFileName">No file chosen</div>
                     </div>
                     <small>Accepted formats: JPG, JPEG, PNG. Max size: 2MB</small>
                  </div>
                  
                  <div class="form-group">
                     <label for="updateCategory">Category</label>
                     <select id="updateCategory" name="update_category">
                        <option value="">Select a category</option>
                        <option value="men" <?php echo (isset($fetch_update['category']) && $fetch_update['category'] == 'men') ? 'selected' : ''; ?>>Men's Shoes</option>
                        <option value="women" <?php echo (isset($fetch_update['category']) && $fetch_update['category'] == 'women') ? 'selected' : ''; ?>>Women's Shoes</option>
                        <option value="kids" <?php echo (isset($fetch_update['category']) && $fetch_update['category'] == 'kids') ? 'selected' : ''; ?>>Kids' Shoes</option>
                        <option value="sports" <?php echo (isset($fetch_update['category']) && $fetch_update['category'] == 'sports') ? 'selected' : ''; ?>>Sports Shoes</option>
                        <option value="casual" <?php echo (isset($fetch_update['category']) && $fetch_update['category'] == 'casual') ? 'selected' : ''; ?>>Casual Shoes</option>
                        <option value="formal" <?php echo (isset($fetch_update['category']) && $fetch_update['category'] == 'formal') ? 'selected' : ''; ?>>Formal Shoes</option>
                     </select>
                  </div>
                  
                  <div class="form-group">
                     <label for="updateBrand">Brand</label>
                     <input type="text" id="updateBrand" name="update_brand" class="form-control" value="<?php echo isset($fetch_update['brand']) ? htmlspecialchars($fetch_update['brand']) : ''; ?>" placeholder="Enter brand name">
                  </div>
                  
                  <div class="form-group">
                     <label for="updateType">Type</label>
                     <select id="updateType" name="update_type" class="form-control">
                        <option value="">Select type</option>
                        <option value="sports" <?php echo (isset($fetch_update['type']) && $fetch_update['type'] == 'sports') ? 'selected' : ''; ?>>Sports</option>
                        <option value="sneakers" <?php echo (isset($fetch_update['type']) && $fetch_update['type'] == 'sneakers') ? 'selected' : ''; ?>>Sneakers</option>
                        <option value="casual" <?php echo (isset($fetch_update['type']) && $fetch_update['type'] == 'casual') ? 'selected' : ''; ?>>Casual</option>
                        <option value="formal" <?php echo (isset($fetch_update['type']) && $fetch_update['type'] == 'formal') ? 'selected' : ''; ?>>Formal</option>
                        <option value="boots" <?php echo (isset($fetch_update['type']) && $fetch_update['type'] == 'boots') ? 'selected' : ''; ?>>Boots</option>
                        <option value="sandals" <?php echo (isset($fetch_update['type']) && $fetch_update['type'] == 'sandals') ? 'selected' : ''; ?>>Sandals</option>
                        <option value="slippers" <?php echo (isset($fetch_update['type']) && $fetch_update['type'] == 'slippers') ? 'selected' : ''; ?>>Slippers</option>
                        <option value="other" <?php echo (isset($fetch_update['type']) && $fetch_update['type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                     </select>
                  </div>
                  
                  <div class="form-group">
                     <label for="updateGender">Gender</label>
                     <select id="updateGender" name="update_gender" class="form-control">
                        <option value="">Select gender</option>
                        <option value="men" <?php echo (isset($fetch_update['gender']) && $fetch_update['gender'] == 'men') ? 'selected' : ''; ?>>Men</option>
                        <option value="women" <?php echo (isset($fetch_update['gender']) && $fetch_update['gender'] == 'women') ? 'selected' : ''; ?>>Women</option>
                        <option value="unisex" <?php echo (isset($fetch_update['gender']) && $fetch_update['gender'] == 'unisex') ? 'selected' : ''; ?>>Unisex</option>
                        <option value="kids" <?php echo (isset($fetch_update['gender']) && $fetch_update['gender'] == 'kids') ? 'selected' : ''; ?>>Kids</option>
                     </select>
                  </div>
                  
                  <div class="form-group">
                     <label for="updateSizes">Available Sizes</label>
                     <input type="text" id="updateSizes" name="update_sizes" 
                            value="<?php echo isset($fetch_update['sizes']) ? htmlspecialchars($fetch_update['sizes']) : ''; ?>" 
                            placeholder="e.g. 40,41,42" 
                            class="form-control"
                            pattern="^\d+(,\s*\d+)*$" 
                            title="Enter sizes as numbers separated by commas (e.g. 40,41,42)">
                     <small class="form-text text-muted">Enter sizes separated by commas (e.g. 40, 41, 42)</small>
                  </div>
               </div>
            </div>
               </div>
            </div>
            
            <div class="form-actions" style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
               <button type="button" id="cancel-update" class="btn btn-secondary">Cancel</button>
               <button type="submit" name="update_product" class="btn btn-primary">
                  <i class="fas fa-save"></i> Update Product
               </button>
            </div>
         </form>
      </div>
   </div>
   <?php
         }
      }
   }
   ?>
</div>

<script>
   // Show the modal if update parameter is present
   <?php if(isset($_GET['update'])): ?>
   document.getElementById('editProductModal').style.display = 'block';
   <?php endif; ?>
</script>







<!-- custom admin js file link  -->
<script src="js/admin_script.js"></script>

<script>
// Size input management for add product form
document.addEventListener('DOMContentLoaded', function() {
    const addSizeBtn = document.getElementById('add-size-btn');
    const sizeInputsContainer = document.getElementById('size-inputs-container');
    let sizeCounter = 1;
    
    if(addSizeBtn && sizeInputsContainer) {
        addSizeBtn.addEventListener('click', function() {
            sizeCounter++;
            
            // Create a new row for size inputs
            const newRow = document.createElement('div');
            newRow.className = 'size-input-row';
            
            // Size input
            const sizeGroup = document.createElement('div');
            sizeGroup.className = 'size-input-group';
            
            const sizeLabel = document.createElement('label');
            sizeLabel.setAttribute('for', 'size' + sizeCounter);
            sizeLabel.textContent = 'Size';
            
            const sizeInput = document.createElement('input');
            sizeInput.setAttribute('type', 'text');
            sizeInput.setAttribute('id', 'size' + sizeCounter);
            sizeInput.setAttribute('name', 'sizes[]');
            sizeInput.setAttribute('placeholder', 'e.g. 40');
            
            sizeGroup.appendChild(sizeLabel);
            sizeGroup.appendChild(sizeInput);
            
            // Stock input
            const stockGroup = document.createElement('div');
            stockGroup.className = 'size-input-group';
            
            const stockLabel = document.createElement('label');
            stockLabel.setAttribute('for', 'stock' + sizeCounter);
            stockLabel.textContent = 'Stock';
            
            const stockInput = document.createElement('input');
            stockInput.setAttribute('type', 'number');
            stockInput.setAttribute('id', 'stock' + sizeCounter);
            stockInput.setAttribute('name', 'stocks[]');
            stockInput.setAttribute('min', '0');
            stockInput.setAttribute('placeholder', 'Quantity');
            
            stockGroup.appendChild(stockLabel);
            stockGroup.appendChild(stockInput);
            
            // Remove button
            const removeBtn = document.createElement('button');
            removeBtn.setAttribute('type', 'button');
            removeBtn.className = 'remove-size-btn';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            
            removeBtn.addEventListener('click', function() {
                sizeInputsContainer.removeChild(newRow);
            });
            
            // Add all elements to the row
            newRow.appendChild(sizeGroup);
            newRow.appendChild(stockGroup);
            newRow.appendChild(removeBtn);
            
            // Add the row to the container
            sizeInputsContainer.appendChild(newRow);
        });
        
        // Make the first row's remove button visible if there are more rows
        const showFirstRemoveBtn = function() {
            const rows = sizeInputsContainer.querySelectorAll('.size-input-row');
            const firstRowRemoveBtn = rows[0].querySelector('.remove-size-btn');
            
            if(rows.length > 1) {
                firstRowRemoveBtn.style.visibility = 'visible';
                firstRowRemoveBtn.addEventListener('click', function() {
                    sizeInputsContainer.removeChild(rows[0]);
                    showFirstRemoveBtn();
                });
            } else {
                firstRowRemoveBtn.style.visibility = 'hidden';
            }
        };
        
        // Initialize the first row's remove button
        showFirstRemoveBtn();
    }
});
</script>

</body>
</html>