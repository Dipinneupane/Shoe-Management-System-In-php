let userBox = document.querySelector('.header .header-2 .user-box');

document.querySelector('#user-btn').onclick = () =>{
   userBox.classList.toggle('active');
   navbar.classList.remove('active');
}

let navbar = document.querySelector('.header .header-2 .navbar');

document.querySelector('#menu-btn').onclick = () =>{
   navbar.classList.toggle('active');
   userBox.classList.remove('active');
}

window.onscroll = () =>{
   userBox.classList.remove('active');
   navbar.classList.remove('active');

   if(window.scrollY > 60){
      document.querySelector('.header .header-2').classList.add('active');
   }else{
      document.querySelector('.header .header-2').classList.remove('active');
   }
}

// Cart quantity control functions
function incrementQuantity(element) {
   const input = element.previousElementSibling;
   const currentValue = parseInt(input.value);
   input.value = currentValue + 1;
}

function decrementQuantity(element) {
   const input = element.nextElementSibling;
   const currentValue = parseInt(input.value);
   if (currentValue > 1) {
      input.value = currentValue - 1;
   }
}

// Scroll to top button functionality
const scrollTopBtn = document.querySelector('.scroll-top');
if (scrollTopBtn) {
   window.addEventListener('scroll', () => {
      if (window.pageYOffset > 300) {
         scrollTopBtn.classList.add('active');
      } else {
         scrollTopBtn.classList.remove('active');
      }
   });

   scrollTopBtn.addEventListener('click', () => {
      window.scrollTo({
         top: 0,
         behavior: 'smooth'
      });
   });
}