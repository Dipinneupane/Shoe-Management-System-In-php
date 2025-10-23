<?php

include 'config.php';

// Only start session if one doesn't already exist
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
}

if(isset($_POST['update_cart'])){
   $cart_ids = explode(',', $_POST['cart_ids']);
   $cart_quantity = (int)$_POST['cart_quantity'];
   
   // Update all items in this group
   $ids_placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
   $types = str_repeat('i', count($cart_ids));
   $params = array_merge([$cart_quantity], $cart_ids);
   
   // First, update one item with the new quantity
   $stmt = $conn->prepare("UPDATE `cart` SET quantity = ? WHERE id = ?");
   $stmt->bind_param('ii', $cart_quantity, $cart_ids[0]);
   $stmt->execute();
   
   // Delete the rest of the items in this group
   if(count($cart_ids) > 1) {
      $placeholders = implode(',', array_fill(0, count($cart_ids) - 1, '?'));
      $delete_ids = array_slice($cart_ids, 1);
      $stmt = $conn->prepare("DELETE FROM `cart` WHERE id IN ($placeholders)");
      $stmt->bind_param(str_repeat('i', count($delete_ids)), ...$delete_ids);
      $stmt->execute();
   }
   
   $message[] = 'Cart quantity updated!';
   header('location:cart.php');
   exit();
}

if(isset($_GET['delete'])){
   $delete_ids = explode(',', $_GET['delete']);
   $placeholders = implode(',', array_fill(0, count($delete_ids), '?'));
   $stmt = $conn->prepare("DELETE FROM `cart` WHERE id IN ($placeholders)");
   $stmt->bind_param(str_repeat('i', count($delete_ids)), ...$delete_ids);
   $stmt->execute();
   header('location:cart.php');
   exit();
}

if(isset($_GET['delete_all'])){
   mysqli_query($conn, "DELETE FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
   header('location:cart.php');
   exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Cart</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'header.php'; ?>

<div class="heading">
   <h3>Shopping cart</h3>
   <p> <a href="home.php">Home</a> / Cart </p>
</div>

<section class="shopping-cart">

   <h1 class="title">Products Added</h1>

   <div class="box-container">
      <?php
         $grand_total = 0;
         // First, get all cart items and group them by product name and size
         $cart_items = [];
         $select_cart = mysqli_query($conn, "SELECT c.*, p.brand, p.type FROM `cart` c 
                                          LEFT JOIN `products` p ON c.name = p.name 
                                          WHERE c.user_id = '$user_id'") or die('query failed');
         
         if(mysqli_num_rows($select_cart) > 0){
            // Group items by product name and size
            while($fetch_cart = mysqli_fetch_assoc($select_cart)){
               $key = $fetch_cart['name'] . '_' . ($fetch_cart['size'] ?? 'no_size');
               if(isset($cart_items[$key])) {
                  // If product with same name and size exists, update quantity and subtotal
                  $cart_items[$key]['quantity'] += $fetch_cart['quantity'];
                  $cart_items[$key]['subtotal'] += $fetch_cart['quantity'] * $fetch_cart['price'];
                  $cart_items[$key]['ids'][] = $fetch_cart['id']; // Store all IDs for this group
               } else {
                  // New product/size combination
                  $cart_items[$key] = [
                     'id' => $fetch_cart['id'],
                     'ids' => [$fetch_cart['id']],
                     'name' => $fetch_cart['name'],
                     'price' => $fetch_cart['price'],
                     'quantity' => $fetch_cart['quantity'],
                     'size' => $fetch_cart['size'] ?? null,
                     'image' => $fetch_cart['image'],
                     'brand' => $fetch_cart['brand'] ?? null,
                     'type' => $fetch_cart['type'] ?? null,
                     'subtotal' => $fetch_cart['quantity'] * $fetch_cart['price']
                  ];
               }
            }
            
            // Now display the grouped items
            foreach($cart_items as $item_key => $item){   
      ?>
      <div class="box">
         <a href="cart.php?delete=<?php echo implode(',', $item['ids']); ?>" class="fas fa-times" 
            onclick="return confirm('Remove all <?php echo addslashes($item['quantity']); ?> items of <?php echo addslashes($item['name']); ?><?php echo !empty($item['size']) ? ' (Size: ' . $item['size'] . ')' : ''; ?> from cart?');"></a>
         <div class="image-container">
            <img src="uploaded_img/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
         </div>
         <div class="content">
            <div class="name"><?php echo htmlspecialchars($item['name']); ?></div>
            <div class="product-details">
                <?php if(!empty($item['brand'])): ?>
                    <div class="product-detail"><strong>Brand:</strong> <?php echo htmlspecialchars($item['brand']); ?></div>
                <?php endif; ?>
                
                <?php if(!empty($item['type'])): ?>
                    <div class="product-detail"><strong>Type:</strong> <?php echo htmlspecialchars($item['type']); ?></div>
                <?php endif; ?>
                
                <?php if(!empty($item['size'])): ?>
                    <div class="product-detail"><strong>Size:</strong> <?php echo htmlspecialchars($item['size']); ?></div>
                <?php endif; ?>
                
                <div class="product-detail"><strong>Quantity:</strong> <?php echo $item['quantity']; ?></div>
            <?php if(!empty($item['size'])): ?>
            <div class="product-detail"><strong>Size:</strong> <?php echo htmlspecialchars($item['size']); ?></div>
            <?php endif; ?>
            </div>
            <div class="price">Rs<?php echo number_format($item['price']); ?>/- each</div>
            <form action="" method="post">
               <input type="hidden" name="cart_ids" value="<?php echo implode(',', $item['ids']); ?>">
               <div class="quantity-control">
                  <span class="quantity-btn" onclick="decrementQuantity(this)"><i class="fas fa-minus"></i></span>
                  <input type="number" min="1" name="cart_quantity" value="<?php echo $item['quantity']; ?>">
                  <span class="quantity-btn" onclick="incrementQuantity(this)"><i class="fas fa-plus"></i></span>
                  <input type="submit" name="update_cart" value="Update" class="option-btn">
               </div>
            </form>
            <div class="sub-total">Subtotal: <span>Rs<?php echo number_format($item['subtotal']); ?>/-</span></div>
         </div>
      </div>
      <?php
      $grand_total += $item['subtotal'];
         }
      }else{
         echo '<p class="empty">your cart is empty</p>';
      }
      ?>
   </div>

   <div class="cart-total">
      <h3>Order Summary</h3>
      <p>Grand Total: <span>Rs<?php echo number_format($grand_total); ?>/-</span></p>
      <div class="flex">
         <a href="shop.php" class="option-btn"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
         <a href="checkout.php" class="btn <?php echo ($grand_total > 1)?'':'disabled'; ?>">Proceed to Checkout <i class="fas fa-arrow-right"></i></a>
      </div>
      <?php if($grand_total > 1): ?>
      <div style="margin-top: 2rem; text-align:center;">
         <a href="cart.php?delete_all" class="delete-btn" onclick="return confirm('Are you sure you want to empty your cart?');"><i class="fas fa-trash"></i> Empty Cart</a>
      </div>
      <?php endif; ?>
   </div>

</section>








<?php include 'footer.php'; ?>

<!-- custom js file link  -->
<script src="js/script.js"></script>

</body>
</html>