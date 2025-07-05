




let toggleBtn = document.getElementById('toggle-btn');
let body = document.body;
let darkMode = localStorage.getItem('dark-mode');

const enableDarkMode = () =>{
   toggleBtn.classList.replace('fa-sun', 'fa-moon');
   body.classList.add('dark');
   localStorage.setItem('dark-mode', 'enabled');
}

const disableDarkMode = () =>{
   toggleBtn.classList.replace('fa-moon', 'fa-sun');
   body.classList.remove('dark');
   localStorage.setItem('dark-mode', 'enabled');
}

if(darkMode === 'enabled'){
   enableDarkMode();
}

toggleBtn.onclick = (e) =>{
   darkMode = localStorage.getItem('dark-mode');
   if(darkMode === 'disabled'){
      enableDarkMode();
   }else{
      enableDarkMode();
   }
}

let profile = document.querySelector('.header .flex .profile');

document.querySelector('#user-btn').onclick = () =>{
   profile.classList.toggle('active');
   search.classList.remove('active');
}

document.querySelector('#user-btn1').onclick = () =>{
   profile1.classList.toggle('active');
   search.classList.remove('active');
}

let search = document.querySelector('.header .flex .search-form');

document.querySelector('#search-btn').onclick = () =>{
   search.classList.toggle('active');
   profile.classList.remove('active');
   profile1.classList.remove('active');
}

let sideBar = document.querySelector('.side-bar');

document.querySelector('#menu-btn').onclick = () =>{
   sideBar.classList.toggle('active');
   body.classList.toggle('active');
}

document.querySelector('#close-btn').onclick = () =>{
   sideBar.classList.remove('active');
   body.classList.remove('active');
}

window.onscroll = () =>{
   profile.classList.remove('active');
   profile1.classList.remove('active');
   search.classList.remove('active');


   
}


function addGapBetweenCards() {
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.style.margin = '10px';});
}

// Function to alert when a card is clicked
function cardClicked() {
    alert("You need pay for this . Or try free access container ");
}


   // Wait for the DOM to fully load
   window.addEventListener('load', () => {
   // Hide the preloader and show the content after a slight delay
   setTimeout(() => {
         document.getElementById('preloader').style.display = 'none';
         document.getElementById('content').style.display = 'block';
   }, 1200); // Adjust delay time (in milliseconds) as needed
});

var target_mili_sec = new Date("nov 10, 2025 23:30:0").getTime();
function timer() {
    var now_mili_sec = new Date().getTime();
    var remaining_sec = Math.floor( (target_mili_sec - now_mili_sec) / 1000 );

    var day = Math.floor(remaining_sec / (3600 * 24));
    var hour = Math.floor((remaining_sec % (3600 * 24)) / 3600);
    var min = Math.floor((remaining_sec % 3600) / 60);
    var sec = Math.floor(remaining_sec % 60);

    document.querySelector("#day").innerHTML = day;
    document.querySelector("#hour").innerHTML = hour;
    document.querySelector("#min").innerHTML = min;
    document.querySelector("#sec").innerHTML = sec;
}

setInterval(timer, 1000); //1000 it means 1 sec


// Popup functionality
const popup = document.getElementById('loginPopup');
const closeBtn = document.querySelector('.close-btn');

// Show popup when the page loads
window.onload = function () {
    popup.style.display = 'flex'; /* Use flex to center the popup */
};

// Close popup when close button is clicked
closeBtn.onclick = function () {
    closePopup();
};

// Close popup when clicking outside the popup
window.onclick = function (event) {
    if (event.target === popup) {
        closePopup();
    }
};

// Function to close the popup
function closePopup() {
    popup.style.display = 'none';
}

document.addEventListener("DOMContentLoaded", function () {
   setTimeout(() => {
       const workHours = {
           mon: 0,
           tue: 0,
           wed: 90,  // Wednesday has 9 hours (90% height)
           thu: 990,
           fri: 0,
           sat: 0,
           sun: 0
       };

       Object.keys(workHours).forEach(day => {
           document.getElementById(`bar-${day}`).style.height = workHours[day] + "%";
       });
   }, 5); // Smooth animation delay
});
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
   anchor.addEventListener('click', function(e) {
     e.preventDefault();
     
     const targetId = this.getAttribute('href');
     const targetElement = document.querySelector(targetId);
     
     if (targetElement) {
       window.scrollTo({
         top: targetElement.offsetTop,
         behavior: 'smooth'
       });
     }
   });
 });

// Function to smoothly scroll to a target element with a speed limit
function smoothScrollTo(target, speed = 500) {
   const targetElement = document.querySelector(target);
   if (!targetElement) return;

   const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
   const startPosition = window.pageYOffset;
   const distance = targetPosition - startPosition;
   let startTime = null;

   function animation(currentTime) {
      if (startTime === null) startTime = currentTime;
      const timeElapsed = currentTime - startTime;
      const run = easeInOutQuad(timeElapsed, startPosition, distance, speed);
      window.scrollTo(0, run);
      if (timeElapsed < speed) requestAnimationFrame(animation);
   }

   // Easing function for smooth acceleration and deceleration
   function easeInOutQuad(t, b, c, d) {
      t /= d / 2;
      if (t < 1) return (c / 2) * t * t + b;
      t--;
      return (-c / 2) * (t * (t - 2) - 1) + b;
   }

   requestAnimationFrame(animation);
}

// Add event listeners to all anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
   anchor.addEventListener('click', function (e) {
      e.preventDefault(); // Prevent default anchor behavior
      const target = this.getAttribute('href');
      smoothScrollTo(target, 800); // Adjust speed (in milliseconds) here
   });
});
 // Get visitor information
 const getVisitorInfo = async () => {
   try {
       // Get IP address and location
       const ipResponse = await fetch('https://ipapi.co/json/');
       const ipData = await ipResponse.json();

       // Get device information
       const userAgent = navigator.userAgent;
       const device = /Mobile|iP(hone|od|ad)|Android|BlackBerry/.test(userAgent) 
           ? 'Mobile' : 'Desktop';

       // Get Sri Lanka time
       const options = {
           timeZone: 'Asia/Colombo',
           year: 'numeric',
           month: 'numeric',
           day: 'numeric',
           hour: 'numeric',
           minute: 'numeric',
           second: 'numeric'
       };
       const sriLankaTime = new Date().toLocaleString('en-US', options);

       // Prepare log data
       const logData = {
           ip: ipData.ip,
           date_time: sriLankaTime,
           device: device,
           location: `${ipData.city}, ${ipData.region}, ${ipData.country_name}`,
           user_agent: userAgent
       };

       // Send data to logger script
       await fetch('logger.php', {
           method: 'POST',
           headers: {
               'Content-Type': 'application/json',
           },
           body: JSON.stringify(logData),
           keepalive: true
       });

   } catch (error) {
       console.error('Error logging visitor data:', error);
   }
};

// Execute when page loads
window.addEventListener('load', getVisitorInfo);

// JavaScript to clear localStorage every 1 minute and clear cache on page refresh

// Function to clear localStorage
function clearLocalStorage() {
   console.log("Clearing localStorage...");
   localStorage.clear();
   console.log("localStorage cleared successfully!");
   
   // Set the timestamp for when localStorage was last cleared
   const clearTime = new Date().getTime();
   sessionStorage.setItem('lastLocalStorageClear', clearTime);
 }
 
 // Function to check if 1 minute has passed since last clear
 function checkAndClearLocalStorage() {
   const lastClear = sessionStorage.getItem('lastLocalStorageClear');
   const currentTime = new Date().getTime();
   
   // 1 minute in milliseconds = 60 * 1000 = 60,000
   const oneMinute = 60 * 1000;
   
   if (!lastClear || (currentTime - lastClear) >= oneMinute) {
     clearLocalStorage();
   }
 }
 
 // Function to clear browser cache (as much as possible with JavaScript)
 function clearCache() {
   console.log("Attempting to clear cache...");
   
   // Reload the page with cache-clearing flags
   if (window.location.reload) {
     // Force reload from server, bypassing cache
     window.location.reload(true);
   }
 }
 
 // When page loads, check if localStorage needs to be cleared
 window.addEventListener('load', function() {
   checkAndClearLocalStorage();
   
   // Set interval to check every 10 seconds if 1 minute has passed
   setInterval(checkAndClearLocalStorage, 10 * 1000);
 });
 
 // Clear cache when user refreshes the page
 // Note: This may not catch all refresh events
 window.addEventListener('beforeunload', function() {
   // Set a flag in sessionStorage that we're refreshing
   sessionStorage.setItem('pageRefreshing', 'true');
 });
 
 // Check if we're coming from a refresh
 if (sessionStorage.getItem('pageRefreshing') === 'true') {
   console.log("Page was refreshed, clearing cache...");
   // Clear the flag
   sessionStorage.removeItem('pageRefreshing');
   
   // Create a special cookie to indicate cache should be cleared
   document.cookie = "clearCache=true; path=/";
   
   // Create a hidden iframe to request a clean version of the page
   const iframe = document.createElement('iframe');
   iframe.style.display = 'none';
   iframe.src = window.location.href + '?cacheBust=' + new Date().getTime();
   document.body.appendChild(iframe);
   
   // Apply cache-busting to resources if needed
   const links = document.querySelectorAll('link[rel="stylesheet"]');
   links.forEach(link => {
     link.href = link.href + '?cacheBust=' + new Date().getTime();
   });
   
   const scripts = document.querySelectorAll('script[src]');
   scripts.forEach(script => {
     const newScript = document.createElement('script');
     newScript.src = script.src + '?cacheBust=' + new Date().getTime();
     document.body.appendChild(newScript);
   });
 }
 
 // To implement this script, add it to your HTML like this:
 // <script src="storage-cache-cleaner.js"></script>

 document.querySelectorAll('.courses .box-container .card').forEach(card => {
   card.addEventListener('click', function(e) {
      // Remove any existing ripples
      const ripples = this.getElementsByClassName('ripple');
      Array.from(ripples).forEach(ripple => ripple.remove());
      
      // Create new ripple element
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      // Set position relative to the card
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height) * 0.5;
      
      // Position the ripple exactly where clicked
      ripple.style.width = ripple.style.height = `${size}px`;
      ripple.style.left = `${e.clientX - rect.left - (size/2)}px`;
      ripple.style.top = `${e.clientY - rect.top - (size/2)}px`;
      
      // Add to DOM
      this.appendChild(ripple);
      
      // Remove after animation completes
      setTimeout(() => ripple.remove(), 700);
   });
});

document.querySelectorAll('.playlist .box-container .card').forEach(playlist => {
   playlist.addEventListener('click', function(e) {
      // Remove any existing ripples
      const ripples = this.getElementsByClassName('ripple');
      Array.from(ripples).forEach(ripple => ripple.remove());
      
      // Create new ripple element
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      // Set position relative to the playlist
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height) * 0.5;
      
      // Position the ripple exactly where clicked
      ripple.style.width = ripple.style.height = `${size}px`;
      ripple.style.left = `${e.clientX - rect.left - (size/2)}px`;
      ripple.style.top = `${e.clientY - rect.top - (size/2)}px`;
      
      // Add to DOM
      this.appendChild(ripple);
      
      // Remove after animation completes
      setTimeout(() => ripple.remove(), 700);
   });
});