



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

   if(window.innerWidth < 1200){
      sideBar.classList.remove('active');
      body.classList.remove('active');
   }
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

var target_mili_sec = new Date("sep 20, 2025 23:30:0").getTime();
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

document.addEventListener("DOMContentLoaded", function() {
   // Fetch data from tr.php
   fetch('tr.php')
      .then(response => response.json()) // Assuming PHP returns JSON
      .then(data => {
         console.log("Data from tr.php:", data);
         // Use data to update the UI (e.g., populate a div)
         // Example: document.getElementById('output').innerHTML = data.message;
      })
      .catch(error => console.error("Error fetching data:", error));
});