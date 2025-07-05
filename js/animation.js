
// --- Configuration ---

// List of BANNED emails (KEEP HARDCODED FOR SECURITY)
const BANNED_EMAILS = ['euroi@gmail.com',
'']; // anjanatennakoon10@gmail.com is already listed here

// Default Allowed Emails (used ONLY if localStorage is empty on login page)
// Ensure this matches the defaults in profile.js if that initializes the list too
const DEFAULT_ALLOWED_EMAILS_LOGIN = [
    'educationsladmin@gmail.com', 'ishanstc123@gmail.com', '26002ishan@gmail.com',
    'chutta595@gmail.com', 'gimhanatharun5@gmail.com',
    'duleeshanethmina2006@gmail.com', 'binuramethmin2006@gmail.com',
    'supundissanayeka263@gmail.com', 'naveendil2006@gmail.com', 'weerasinghem512@gmail.com',
    'kavindyawickramasinghekavindya@gmail.com', 'kavindiyawickramasinghe0@gmail.com',
    'banukasomathilaka2006@gmail.com', 'chathuranga071@gmail.com', 'ds4803707@gmail.com',
    'ravinduyasasthi16@gmail.com', 'educationslgreat@gmail.com', 'smaduwantha881@gmail.com',
    'anandakw076@gmail.com',
   'sudarshanapradeep783@gmail.com',
   'vihagalakshan2006@gmail.com',
   'sewminadilanka10@gmail.com',
   'anjanatennakoon10@gmail.com'
];

// --- Helper Functions ---

// Helper to get allowed emails on login page
function getAllowedEmailsForLogin() {
    const key = 'allowedEmailsList'; // same key as profile.js
    try {
        const storedList = localStorage.getItem(key);
        if (storedList) {
            // the list from localStorage if it exists
            return JSON.parse(storedList);
        } else {
            // If not found on login page, use the hardcoded defaults above
            console.log("Login Page: Allowed emails not found in localStorage, using defaults defined in this script.");
            // You could optionally save the defaults back to localStorage here,
            // but profile.js likely handles the main initialization.
            // localStorage.setItem(key, JSON.stringify(DEFAULT_ALLOWED_EMAILS_LOGIN));
            return DEFAULT_ALLOWED_EMAILS_LOGIN;
        }
    } catch (e) {
        console.error("Login Page: Error reading allowed emails from localStorage:", e);
        return DEFAULT_ALLOWED_EMAILS_LOGIN; // Fallback to defaults on error
    }
}

// --- Google Sign-In Handling ---

function handleCredentialResponse(response) {
   try {
      const responsePayload = JSON.parse(atob(response.credential.split('.')[1]));
      const userEmail = responsePayload.email.toLowerCase();

      // --- Banned Check (Uses hardcoded BANNED_EMAILS list) ---
      // This check happens immediately upon successful Google Auth response
      if (BANNED_EMAILS.includes(userEmail)) {
         console.warn(`Login attempt blocked for banned email: ${userEmail}`);
         window.location.href = 'banded.html'; // Ensure 'banded.html' exists or change target
         return; // Stop processing
      }

      // --- Allowed Check (Uses localStorage via helper function) ---
      // This check happens ONLY if the email is NOT in the BANNED_EMAILS list
      const allowedEmails = getAllowedEmailsForLogin(); // <<< *** Reads from localStorage ***
      if (!allowedEmails.includes(userEmail)) {         // <<< *** Checks localStorage list ***
         console.warn(`Login attempt denied for non-allowed email: ${userEmail}`);
         const alertElement = document.createElement('div');
         alertElement.style.backgroundColor = '#ef4444';
         alertElement.style.color = 'white';
         alertElement.style.padding = '1rem';
         alertElement.style.borderRadius = '8px';
         alertElement.style.marginBottom = '1rem';
         alertElement.style.textAlign = 'center';
         alertElement.textContent = 'Access denied. Your email is not registered. Please contact the administrator.';
         alertElement.className = 'login-alert'; // Assign class for potential removal

         const formContainer = document.querySelector('.form-container'); // Adjust if selector is different
         if (formContainer) {
             // Remove any existing alerts first
             const existingAlerts = formContainer.querySelectorAll('.login-alert');
             existingAlerts.forEach(alert => alert.remove());
             // Prepend the new alert
             formContainer.prepend(alertElement);
         } else {
             console.error("Cannot display denial message: '.form-container' not found.");
         }

         google.accounts.id.disableAutoSelect(); // Don't auto-select this denied account next time
         // Ensure potentially stored invalid login info is cleared
         localStorage.removeItem('loggedIn');
         localStorage.removeItem('userEmail');
         localStorage.removeItem('userName');
         localStorage.removeItem('userPicture');

         // Optional: Reload page after delay or just show message
         // setTimeout(() => window.location.reload(), 3000);
         return; // Stop processing
      }

      // --- Allow Valid User ---
      console.log(`Login successful for allowed email: ${userEmail}`);
      localStorage.setItem('loggedIn', 'true');
      localStorage.setItem('userName', responsePayload.name);
      localStorage.setItem('userEmail', userEmail);
      localStorage.setItem('userPicture', responsePayload.picture);
      localStorage.setItem('lastLogin', new Date().toISOString());
      // updateUI(); // Call UI update if needed on the login page itself *before* redirect

      showSuccessMessage("Login successful! Redirecting..."); // Provide feedback

      // Redirect to dashboard page after slight delay
      setTimeout(() => {
          // *** IMPORTANT: Make sure '/home.html' is the correct path to your main dashboard page ***
          window.location.href = '/home.html';
      }, 1500); // Adjust delay if needed

   } catch (error) {
      console.error('Login error during handleCredentialResponse:', error);
      showErrorMessage('Authentication failed due to an error. Please try again.');
   }
}

function initializeGoogleSignIn() {
   // Check if Google Identity Services library is loaded
   if (window.google && window.google.accounts && window.google.accounts.id) {
      try {
         console.log("Initializing Google Sign-In...");
         window.google.accounts.id.initialize({
            // --- Google Cloud Client ID ---
            client_id: '544000326327-fap2n9ebapq8iv7opagmaajddci5n5rp.apps.googleusercontent.com',
            callback: handleCredentialResponse // Function to handle the response after sign-in
         });

         // Render the Google Sign-In button
         const googleSignInButtonDiv = document.getElementById('gSignIn');
         if (googleSignInButtonDiv) {
             // --- Google Sign-In Button Rendering Options ---
             window.google.accounts.id.renderButton(
                googleSignInButtonDiv,
                {
                   type: 'standard',
                   theme: 'outline',
                   size: 'large',
                   shape: 'pill',
                   text: 'signin_with', // Or 'continue_with', etc.
                   logo_alignment: 'center', // Or 'left'
                   width: '100%' // Adjust as needed, or remove for default width
                }
             );
             console.log("Google Sign-In button rendered.");
         } else {
             console.error("Google Sign-In button container ('#gSignIn') not found.");
             // Optionally display the fallback button if the container is missing
             const fallbackButton = document.querySelector('.google-btn');
             if (fallbackButton) fallbackButton.style.display = 'flex';
         }


         // Optionally prompt users automatically if they were previously signed in
         // Consider UX implications - might be annoying if they explicitly signed out.
         // if (localStorage.getItem('userEmail')) { // Check if user was previously logged in
         //    window.google.accounts.id.prompt();
         // }

         // Hide the fallback button if Google button rendered successfully
         const fallbackButton = document.querySelector('.google-btn');
         if (fallbackButton) fallbackButton.style.display = 'none';

      } catch (e) {
         console.error("Error initializing Google Sign-In:", e);
         // Show the fallback button in case of error
         const fallbackButton = document.querySelector('.google-btn');
         if (fallbackButton) fallbackButton.style.display = 'flex';
         showErrorMessage("Could not initialize Google Sign-In. Please check your connection or try the fallback.");
      }
   } else {
      console.log("Google Identity Services API not loaded yet, showing fallback button.");
      // Show the fallback button if Google API isn't ready
      const fallbackButton = document.querySelector('.google-btn');
      if (fallbackButton) fallbackButton.style.display = 'flex';
      // Optionally show a message indicating reliance on fallback
   }
}

// Function to manually trigger sign-in (e.g., for fallback button)
function tryGoogleSignIn() {
   const fallbackButton = document.querySelector('.google-btn');
   if (fallbackButton) fallbackButton.style.display = 'none'; // Hide fallback
   console.log("Attempting to initialize Google Sign-In via fallback...");
   initializeGoogleSignIn(); // Try initializing again
   // You might also directly call prompt() here if initialize was successful but didn't show button
   // window.google.accounts.id.prompt();
}

// --- UI Update Functions ---

// Shows error/success messages (ensure container exists)
function showErrorMessage(message) {
    showMessage(message, '#ef4444'); // Red for error
}

function showSuccessMessage(message) {
    showMessage(message, '#34c38f'); // Green for success
}

function showMessage(message, backgroundColor) {
   const alertElement = document.createElement('div');
   alertElement.style.backgroundColor = backgroundColor;
   alertElement.style.color = 'white';
   alertElement.style.padding = '1rem';
   alertElement.style.borderRadius = '8px';
   alertElement.style.marginBottom = '1rem';
   alertElement.style.textAlign = 'center';
   alertElement.textContent = message;
   alertElement.className = 'login-alert'; // Use class for easier management

   const formContainer = document.querySelector('.form-container'); // Adjust selector if needed
   if(formContainer) {
       // Remove any existing alerts first
       const existingAlerts = formContainer.querySelectorAll('.login-alert');
       existingAlerts.forEach(alert => alert.remove());
       // Add the new message
       formContainer.prepend(alertElement);
   } else {
       console.error("Cannot display message: '.form-container' not found.");
       // Fallback alert if container is missing
       alert(message);
   }
}


// Updates UI elements (e.g., user info display) based on login state
// NOTE: This function might be more relevant on pages *after* login,
// unless you show user info on the login page itself.
function updateUI() {
   const loggedIn = localStorage.getItem('loggedIn') === 'true';
   const userInfoDiv = document.getElementById('userInfo');
   const googleSignInDiv = document.getElementById('gSignIn');
   const userImage = document.getElementById('userImage');
   const userNameSpan = document.getElementById('userName');

   // Elements for the sidebar (check if they exist on the current page)
   const sidebarName = document.querySelector('.side-bar .name');
   const sidebarRole = document.querySelector('.side-bar .role');
   const sidebarImage = document.querySelector('.side-bar .image');

   if (loggedIn) {
      // Show user info, hide sign-in button (if these elements exist)
      if (userInfoDiv) userInfoDiv.style.display = 'block';
      if (googleSignInDiv) googleSignInDiv.style.display = 'none'; // Hide Google Sign-In button

      const picture = localStorage.getItem('userPicture');
      const name = localStorage.getItem('userName');
      const email = localStorage.getItem('userEmail');

      if (userImage) userImage.src = picture || '/images/default-avatar.png'; // Provide a default avatar path
      if (userNameSpan) userNameSpan.textContent = name || 'User';

      // Update sidebar profile elements (if they exist)
      if (sidebarName) sidebarName.textContent = name || 'User';
      if (sidebarRole) sidebarRole.textContent = email || ''; // Display email in role field? Adjust as needed.
      if (sidebarImage) sidebarImage.src = picture || '/images/default-avatar.png';

   } else {
      // Hide user info, show sign-in button (if these elements exist)
      if (userInfoDiv) userInfoDiv.style.display = 'none';
      if (googleSignInDiv) googleSignInDiv.style.display = 'flex'; // Show Google Sign-In button

      // Update sidebar for logged out state (if sidebar elements exist)
      if (sidebarName) sidebarName.textContent = '! Login Required';
      if (sidebarRole) sidebarRole.textContent = 'Please sign in';
      if (sidebarImage) sidebarImage.src = '/images/default-avatar.png'; // Show default avatar
   }
}

// --- Sign Out ---

function signOut() {
   console.log("Signing out...");
   if (window.google && window.google.accounts && window.google.accounts.id) {
       google.accounts.id.disableAutoSelect(); // Prevent auto sign-in next time
   }
   // Clear all relevant session/user data
   localStorage.clear(); // Clears everything - be careful if you store other non-auth data
   // Or selectively remove items:
   // localStorage.removeItem('loggedIn');
   // localStorage.removeItem('userEmail');
   // localStorage.removeItem('userName');
   // localStorage.removeItem('userPicture');
   // localStorage.removeItem('lastLogin');
   // localStorage.removeItem('lastActivity');
   // Potentially clear admin/verified/allowed lists too if desired on sign out
   // localStorage.removeItem('adminEmailsList');
   // localStorage.removeItem('verifiedEmailsList');
   // localStorage.removeItem('allowedEmailsList');

   // Reload the page or redirect to login
   window.location.reload(); // Reloads current page, likely showing login state
   // Or redirect explicitly: window.location.href = '/login.html';
}

// --- Sidebar Functionality ---

function setupSidebar() {
   // Only setup if sidebar elements exist on the page
   const menuBtn = document.getElementById('menu-btn');
   const closeBtn = document.getElementById('close-btn');
   const sideBar = document.querySelector('.side-bar');
   const overlay = document.querySelector('.overlay'); // Assuming an overlay div exists

   if (!menuBtn || !closeBtn || !sideBar || !overlay) {
       // console.log("Sidebar elements not found on this page. Skipping setup.");
       return; // Don't proceed if elements are missing
   }

   console.log("Setting up sidebar toggles...");

   menuBtn.addEventListener('click', () => {
      sideBar.classList.add('active');
      overlay.classList.add('active');
      document.body.style.overflow = 'hidden'; // Prevent background scrolling when sidebar is open
   });

   const closeSidebar = () => {
      sideBar.classList.remove('active');
      overlay.classList.remove('active');
      document.body.style.overflow = ''; // Restore scrolling
   };

   closeBtn.addEventListener('click', closeSidebar);
   overlay.addEventListener('click', closeSidebar);
}

// --- Session Timeout ---

function checkSessionTimeout() {
   const lastActivity = localStorage.getItem('lastActivity');
   if (lastActivity && localStorage.getItem('loggedIn') === 'true') {
      const inactiveTime = Date.now() - new Date(lastActivity).getTime();
      const TIMEOUT_DURATION = 15 * 60 * 1000; // 15 minutes in milliseconds

      if (inactiveTime > TIMEOUT_DURATION) {
         console.log("Session timed out due to inactivity.");
         showErrorMessage('Session expired due to inactivity. Please sign in again.');
         // Sign out after showing the message
         setTimeout(signOut, 2000); // Delay sign out slightly so user sees message
      }
   }
}

// Update last activity timestamp on user interaction
function updateLastActivity() {
   if (localStorage.getItem('loggedIn') === 'true') {
      localStorage.setItem('lastActivity', new Date().toISOString());
   }
}

// --- Theme Toggling ---

function setupThemeToggle() {
    const toggleBtn = document.getElementById('toggle-btn');
    if (!toggleBtn) {
        // console.log("Theme toggle button not found. Skipping setup.");
        return;
    }

    console.log("Setting up theme toggle...");
    toggleBtn.addEventListener('click', function() {
       document.body.classList.toggle('light-mode');
       const isDarkMode = !document.body.classList.contains('light-mode');
       localStorage.setItem('darkMode', isDarkMode.toString());
       // Update icon based on new theme state
       this.className = isDarkMode ? 'fas fa-sun' : 'fas fa-moon';
       console.log(`Theme toggled to: ${isDarkMode ? 'Dark' : 'Light'}`);
       // You might need to call functions here that re-style elements based on theme if CSS isn't sufficient
       // e.g., applyAdminDarkStyles() from profile.js might need linking or re-applying
    });
}

function applySavedThemePreference() {
    const toggleBtn = document.getElementById('toggle-btn');
    const isDarkModeSaved = localStorage.getItem('darkMode') !== 'false'; // Default to dark if not 'false'

    if (!isDarkModeSaved) { // If light mode is saved
        document.body.classList.add('light-mode');
        if (toggleBtn) toggleBtn.className = 'fas fa-moon';
    } else { // If dark mode is saved or no preference (default dark)
        document.body.classList.remove('light-mode');
        if (toggleBtn) toggleBtn.className = 'fas fa-sun';
    }
    console.log(`Applied saved theme: ${isDarkModeSaved ? 'Dark' : 'Light'}`);
}

// --- Initialization ---

// Run initialization logic when the page finishes loading
window.onload = function() {
   console.log("Window loaded. Running initial setup...");

   // --- START: Added check for specific banned email in localStorage on page load ---
   const storedEmail = localStorage.getItem('userEmail');

   // Check if a user email is stored and if it matches the specific banned email
   if (storedEmail && storedEmail.toLowerCase() === ' ') {
       console.warn(`Stored email '${storedEmail}' is banned. Redirecting to banded.html.`);
       // Optionally, clear localStorage related to this banned user before redirecting
       // localStorage.removeItem('loggedIn');
       // localStorage.removeItem('userEmail');
       // localStorage.removeItem('userName');
       // localStorage.removeItem('userPicture');
       window.location.href = 'banded.html';
       return; // Stop executing the rest of the onload logic
   }
   // --- END: Added check for specific banned email in localStorage on page load ---

   applySavedThemePreference(); // Apply theme first
   updateUI(); // Update UI based on login state (might be minimal on login page)
   setupSidebar(); // Setup sidebar toggles if elements exist
   setupThemeToggle(); // Setup theme toggle button if exists

   // Check session timeout periodically (e.g., every minute)
   // Run check immediately on load too
   checkSessionTimeout();
   setInterval(checkSessionTimeout, 60 * 1000); // Check every 60 seconds

   // Set up activity tracking listeners to update lastActivity timestamp
   ['click', 'keypress', 'scroll', 'mousemove'].forEach(eventType => {
      // Use throttling/debouncing here if performance becomes an issue
      document.addEventListener(eventType, updateLastActivity, { passive: true });
   });
   // Update activity on load as well
   updateLastActivity();

   // Attempt to initialize Google Sign-In after a short delay
   // This gives the Google API script potentially more time to load
   setTimeout(() => {
      initializeGoogleSignIn();
   }, 300); // Adjust delay if needed (e.g., 500ms)

   console.log("Initial setup complete.");
};
