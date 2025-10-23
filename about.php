<?php

include 'config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>About</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/testimonials.css">
   <style>
      /* Constrain reviewed product image size in testimonials */
      .testimonials .testimonial-content {
         display: flex;
         align-items: center;
         gap: 16px;
      }
      .testimonials .testimonial-product-image {
         flex: 0 0 auto;
      }
      .testimonials .testimonial-product-image img {
         width: 110px;
         height: 110px;
         object-fit: cover;
         display: block;
         border-radius: 8px;
      }
   </style>

</head>
<body>
   
<?php include 'header.php'; ?>

<div class="heading">
   <h3>About us</h3>
   <p> <a href="home.php">Home</a> / About </p>
</div>

<section class="about">

   <div class="flex">

      <div class="image">
         <img src="images/about-img.jpg" alt="">
      </div>

      <div class="content">
         <h3>why choose us?</h3>
         <p>We offer high-quality, stylish, and comfortable footwear for every occasion. Our shoes are designed for durability, fashion, and the perfect fit to match your lifestyle.</p>
         <p>We prioritize customer satisfaction with affordable prices, excellent service, and a seamless shopping experience. Step into comfort and style—where every pair is made for you!</p>
         <a href="contact.php" class="btn">contact us</a>
      </div>

   </div>

</section>



<!-- customer testimonials section -->
<section class="testimonials">
   <h1 class="title">Our <span>Customer</span> Experiences</h1>
   
   <div class="testimonial-slider">
      <div class="testimonial-container" id="testimonialContainer">
         <?php
            // First get all approved review IDs
            $review_ids_query = mysqli_query($conn, "SELECT id FROM `reviews` WHERE status = 'approved' ORDER BY created_at DESC LIMIT 5");
            $review_ids = [];
            
            if(mysqli_num_rows($review_ids_query) > 0) {
                while($row = mysqli_fetch_assoc($review_ids_query)) {
                    $review_ids[] = $row['id'];
                }
            }
            
            // Debug information
            echo "<!-- Found " . count($review_ids) . " approved review IDs -->";
            
            $testimonials = [];
            $testimonial_count = 0;
            
            // Now get the full details for each review
            if(!empty($review_ids)) {
                foreach($review_ids as $review_id) {
                    $review_query = mysqli_query($conn, "SELECT r.*, u.name as user_name, p.name as product_name, p.image as product_image, p.id as product_id 
                                                     FROM `reviews` r 
                                                     JOIN `users` u ON r.user_id = u.id 
                                                     JOIN `products` p ON r.product_id = p.id 
                                                     WHERE r.id = '$review_id'");
                    
                    if(mysqli_num_rows($review_query) > 0) {
                        $testimonials[] = mysqli_fetch_assoc($review_query);
                        $testimonial_count++;
                    }
                }
            }
            
            // Debug more information
            echo "<!-- Retrieved " . count($testimonials) . " testimonials -->";
            
            if(count($testimonials) > 0) {
               foreach($testimonials as $fetch_testimonial){
                  $testimonial_count++;
         ?>
         <div class="testimonial-card">
            <div class="testimonial-content">
               <div class="testimonial-product-image">
                  <a href="product.php?id=<?php echo $fetch_testimonial['product_id']; ?>">
                     <?php if(!empty($fetch_testimonial['product_image']) && file_exists('uploaded_img/'.$fetch_testimonial['product_image'])): ?>
                        <img src="uploaded_img/<?php echo $fetch_testimonial['product_image']; ?>" alt="<?php echo $fetch_testimonial['product_name']; ?>">
                     <?php else: ?>
                        <img src="images/about-img.jpg" alt="<?php echo $fetch_testimonial['product_name']; ?>">
                     <?php endif; ?>
                  </a>
               </div>
               <div class="testimonial-details">
                  <div class="testimonial-text">
                     <?php echo $fetch_testimonial['review_text']; ?>
                  </div>
                  <div class="testimonial-rating">
                     <?php
                        for($i = 1; $i <= 5; $i++){
                           if($i <= $fetch_testimonial['rating']){
                              echo '<i class="fas fa-star"></i>';
                           } else {
                              echo '<i class="far fa-star"></i>';
                           }
                        }
                     ?>
                  </div>
                  <div class="testimonial-author">
                     <div class="testimonial-name"><?php echo $fetch_testimonial['user_name']; ?></div>
                     <div class="testimonial-product">on <a href="product.php?id=<?php echo $fetch_testimonial['product_id']; ?>"><?php echo $fetch_testimonial['product_name']; ?></a></div>
                  </div>
               </div>
            </div>
         </div>
         <?php
               }
            } else {
               echo '<div class="testimonial-card"><div class="testimonial-content"><p>No testimonials yet.</p></div></div>';
               $testimonial_count = 1;
            }
         ?>
      </div>
      
      <?php if($testimonial_count > 1): ?>
      <div class="testimonial-arrow prev" onclick="moveTestimonial(-1)"><i class="fas fa-chevron-left"></i></div>
      <div class="testimonial-arrow next" onclick="moveTestimonial(1)"><i class="fas fa-chevron-right"></i></div>
      
      <div class="testimonial-controls">
         <?php for($i = 0; $i < $testimonial_count; $i++): ?>
            <div class="testimonial-dot <?php echo ($i === 0) ? 'active' : ''; ?>" onclick="goToTestimonial(<?php echo $i; ?>)"></div>
         <?php endfor; ?>
      </div>
      <?php endif; ?>
   </div>
</section>

<?php include 'footer.php'; ?>

<!-- custom js file link  -->
<script src="js/script.js"></script>

<script>
// Testimonial slider functionality
let currentTestimonial = 0;
const testimonialContainer = document.getElementById('testimonialContainer');
const testimonialCards = document.querySelectorAll('.testimonial-card');
const dots = document.querySelectorAll('.testimonial-dot');
const totalTestimonials = testimonialCards.length;

function updateTestimonialPosition() {
    testimonialContainer.style.transform = `translateX(-${currentTestimonial * 100}%)`;
    
    // Update active dot
    dots.forEach((dot, index) => {
        if (index === currentTestimonial) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

function moveTestimonial(direction) {
    currentTestimonial += direction;
    
    // Handle wrapping
    if (currentTestimonial < 0) {
        currentTestimonial = totalTestimonials - 1;
    } else if (currentTestimonial >= totalTestimonials) {
        currentTestimonial = 0;
    }
    
    updateTestimonialPosition();
}

function goToTestimonial(index) {
    currentTestimonial = index;
    updateTestimonialPosition();
}

// Auto-rotate testimonials every 5 seconds
let testimonialInterval = setInterval(() => moveTestimonial(1), 5000);

// Pause auto-rotation when hovering over testimonials
const testimonialSlider = document.querySelector('.testimonial-slider');
if (testimonialSlider) {
    testimonialSlider.addEventListener('mouseenter', () => {
        clearInterval(testimonialInterval);
    });
    
    testimonialSlider.addEventListener('mouseleave', () => {
        testimonialInterval = setInterval(() => moveTestimonial(1), 5000);
    });
}
</script>

</body>
</html>