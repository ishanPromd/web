 // List of allowed and banned emails (all lowercase)
const ALLOWED_EMAILS = ['educationsladmin@gmail.com', 'ishanstc123@gmail.com', '26002ishan@gmail.com',
'chutta595@gmail.com' ,'rmbeatsyt@gmail.com',
'gimhanatharun5@gmail.com',
'anjanatennakoon10@gmail.com',
'duleeshanethmina2006@gmail.com',
'binuramethmin2006@gmail.com',
'supundissanayeka263@gmail.com',
'naveendil2006@gmail.com',
'weerasinghem512@gmail.com',
'kavindyawickramasinghekavindya@gmail.com',
'kavindiyawickramasinghe0@gmail.com',
'banukasomathilaka2006@gmail.com',
'chathuranga071@gmail.com',
'ds4803707@gmail.com',
'ravinduyasasthi16@gmail.com',
'educationslgreat@gmail.com','smaduwantha881@gmail.com',
'anandakw076@gmail.com'
]; 
const BANNED_EMAILS = ['ishanstc@gmail.com', 'euroi@gmail.com'];

function handleCredentialResponse(response) {
   try {
      const responsePayload = JSON.parse(atob(response.credential.split('.')[1]));
      const userEmail = responsePayload.email.toLowerCase();

      // Immediate redirect for banned emails
      if (BANNED_EMAILS.includes(userEmail)) {
         window.location.href = 'banded.html';
         return;
      }

      // Check if email is allowed
      if (!ALLOWED_EMAILS.includes(userEmail)) {
         const alertElement = document.createElement('div');
         alertElement.style.backgroundColor = '#ef4444';
         alertElement.style.color = 'white';
         alertElement.style.padding = '1rem';
         alertElement.style.borderRadius = '8px';
         alertElement.style.marginBottom = '1rem';
         alertElement.style.textAlign = 'center';
         alertElement.textContent = 'Access denied. Please contact admin to register your email.';
         
         // Remove any existing alerts first
         const existingAlerts = document.querySelectorAll('.form-container > div');
         existingAlerts.forEach(alert => alert.remove());
         
         document.querySelector('.form-container').prepend(alertElement);
         
         google.accounts.id.disableAutoSelect();
         localStorage.removeItem('loggedIn');
         localStorage.removeItem('userEmail');
         setTimeout(() => window.location.reload(), 3000);
         return;
      }

      // Allow valid users
      localStorage.setItem('loggedIn', 'true');
      localStorage.setItem('userName', responsePayload.name);
      localStorage.setItem('userEmail', userEmail);
      localStorage.setItem('userPicture', responsePayload.picture);
      localStorage.setItem('lastLogin', new Date().toISOString());
      updateUI();
      
      // Redirect to dashboard after slight delay
      setTimeout(() => {
         window.location.href = '/index.html';
      }, 1000);
   } catch (error) {
      console.error('Login error:', error);
      showErrorMessage('Authentication failed. Please try again.');
   }
}

function showErrorMessage(message) {
   const alertElement = document.createElement('div');
   alertElement.style.backgroundColor = '#ef4444';
   alertElement.style.color = 'white';
   alertElement.style.padding = '1rem';
   alertElement.style.borderRadius = '8px';
   alertElement.style.marginBottom = '1rem';
   alertElement.style.textAlign = 'center';
   alertElement.textContent = message;
   
   // Remove any existing alerts first
   const existingAlerts = document.querySelectorAll('.form-container > div');
   existingAlerts.forEach(alert => alert.remove());
   
   document.querySelector('.form-container').prepend(alertElement);
}

function updateUI() {
   if (localStorage.getItem('loggedIn') === 'true') {
      document.getElementById('userInfo').style.display = 'block';
      document.getElementById('gSignIn').style.display = 'none';
      document.getElementById('userImage').src = localStorage.getItem('userPicture') || '/images/default-avatar.png';
      document.getElementById('userName').textContent = localStorage.getItem('userName') || 'User';
      
      // Update sidebar profile if it exists
      const sidebarName = document.querySelector('.side-bar .name');
      const sidebarRole = document.querySelector('.side-bar .role');
      const sidebarImage = document.querySelector('.side-bar .image');
      
      if (sidebarName) sidebarName.textContent = localStorage.getItem('userName') || 'User';
      if (sidebarRole) sidebarRole.textContent = localStorage.getItem('userEmail') || '';
      if (sidebarImage) sidebarImage.src = localStorage.getItem('userPicture') || '/images/default-avatar.png';
   } else {
      document.getElementById('userInfo').style.display = 'none';
      document.getElementById('gSignIn').style.display = 'flex';
      
      // Update sidebar for logged out state
      const sidebarName = document.querySelector('.side-bar .name');
      const sidebarRole = document.querySelector('.side-bar .role');
      
      if (sidebarName) sidebarName.textContent = '! Login Required';
      if (sidebarRole) sidebarRole.textContent = 'Please sign in to continue';
   }
}

function signOut() {
   google.accounts.id.disableAutoSelect();
   localStorage.clear();
   window.location.reload();
}

function tryGoogleSignIn() {
   document.querySelector('.google-btn').style.display = 'none';
   initializeGoogleSignIn();
}

function initializeGoogleSignIn() {
   if (window.google && window.google.accounts) {
      try {
         window.google.accounts.id.initialize({
            client_id: '544000326327-fap2n9ebapq8iv7opagmaajddci5n5rp.apps.googleusercontent.com',
            callback: handleCredentialResponse
         });
         
         window.google.accounts.id.renderButton(
            document.getElementById('gSignIn'),
            { 
               type: 'standard',
               theme: 'outline',
               size: 'large',
               shape: 'pill',
               text: 'signin_with',
               logo_alignment: 'center',
               width: '100%'
            }
         );
         
         // Auto select account if previously logged in (for convenience)
         if (localStorage.getItem('userEmail')) {
            window.google.accounts.id.prompt();
         }
         
         document.querySelector('.google-btn').style.display = 'none';
      } catch (e) {
         console.error("Error initializing Google Sign-In:", e);
         document.querySelector('.google-btn').style.display = 'flex';
      }
   } else {
      console.log("Google API not loaded yet, showing fallback button");
      document.querySelector('.google-btn').style.display = 'flex';
   }
}

// Sidebar toggle functionality
function setupSidebar() {
   const menuBtn = document.getElementById('menu-btn');
   const closeBtn = document.getElementById('close-btn');
   const sideBar = document.querySelector('.side-bar');
   const overlay = document.querySelector('.overlay');
   
   if (menuBtn) {
      menuBtn.addEventListener('click', () => {
         sideBar.classList.add('active');
         overlay.classList.add('active');
      });
   }
   
   if (closeBtn) {
      closeBtn.addEventListener('click', () => {
         sideBar.classList.remove('active');
         overlay.classList.remove('active');
      });
   }
   
   if (overlay) {
      overlay.addEventListener('click', () => {
         sideBar.classList.remove('active');
         overlay.classList.remove('active');
      });
   }
}

// Check session timeout (15 minutes of inactivity)
function checkSessionTimeout() {
   const lastActivity = localStorage.getItem('lastActivity');
   if (lastActivity) {
      const inactiveTime = Date.now() - new Date(lastActivity).getTime();
      // If inactive for more than 15 minutes (900000 ms)
      if (inactiveTime > 900000 && localStorage.getItem('loggedIn') === 'true') {
         signOut();
         showErrorMessage('Session expired due to inactivity. Please sign in again.');
      }
   }
}

// Update last activity timestamp
function updateLastActivity() {
   if (localStorage.getItem('loggedIn') === 'true') {
      localStorage.setItem('lastActivity', new Date().toISOString());
   }
}

// Initialize everything when the page loads
window.onload = function() {
   updateUI();
   setupSidebar();
   checkSessionTimeout();
   
   // Set up activity tracking
   ['click', 'keypress', 'scroll', 'mousemove'].forEach(eventType => {
      document.addEventListener(eventType, updateLastActivity, { passive: true });
   });
   
   // Check for Google API and initialize sign-in
   setTimeout(() => {
      initializeGoogleSignIn();
   }, 300);
};

// Toggle dark/light mode
document.getElementById('toggle-btn')?.addEventListener('click', function() {
   document.body.classList.toggle('light-mode');
   const isDarkMode = !document.body.classList.contains('light-mode');
   localStorage.setItem('darkMode', isDarkMode.toString());
   this.className = isDarkMode ? 'fas fa-sun' : 'fas fa-moon';
});

// Apply saved theme preference
if (localStorage.getItem('darkMode') === 'false') {
   document.body.classList.add('light-mode');
   document.getElementById('toggle-btn').className = 'fas fa-moon';
}