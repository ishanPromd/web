// profile.js - User profile and authentication management
// ... (previous comments remain the same) ...
// Modified: Dashboard greeting uses Sri Lanka Time Zone (Asia/Colombo).
// Modified: Preloader logic waits for window.load (all assets) and updates text.
// Modified: Dashboard greeting ONLY applied on index.html / home.html / root path.
// Modified: Added listeners to fix admin panel collapsed position on load/resize.
// Modified: Added logging for dashboard greeting page path check.
// *** FIX: checkLogin now uses email lists from localStorage for admin/verified checks ***
// *** FIX: Corrected constant name in initializeClientSideEmailLists for Allowed Emails ***

// --- Global Flag for Redirect State ---
window.isRedirecting = false;

// --- Constants for Client-Side Email List Simulation ---
const LOCAL_STORAGE_ADMIN_EMAILS_KEY = 'clientAdminEmailsList';
const LOCAL_STORAGE_VERIFIED_EMAILS_KEY = 'clientVerifiedEmailsList';
const LOCAL_STORAGE_ALLOWED_EMAILS_KEY = 'clientAllowedEmailsList';

// --- Default Email Lists (for localStorage initialization) ---
// Use lowercase for consistent checking
const DEFAULT_ADMIN_EMAILS = [ 'educationslgreat@gmail.com', '26002ishan@gmail.com' ,
'chamodi2006@gmail.com',
'vihangalakshan675@gmail.com',
'ds4803707@gmail.com'].map(email => email.toLowerCase());
const DEFAULT_VERIFIED_EMAILS = [ '26002ishan@gmail.com', 'gimhanatharun5@gmail.com', 'weerasinghem512@gmail.com', 'banukasomathilaka2006@gmail.com', 'ds4803707@gmail.com', 'educationslgreat@gmail.com', 'anjanatennakoon10@gmail.com', 'smaduwantha881@gmail.com',
'anandakw076@gmail.com' ].map(email => email.toLowerCase());
const DEFAULT_ALLOWED_EMAILS = [ 'test@example.com', 'another@example.com' ].map(email => email.toLowerCase());

// --- Constants for Admin Panel Positioning ---
const ADMIN_PANEL_COLLAPSED_HEIGHT = 55; // Make constants accessible for repositioning
const ADMIN_PANEL_PADDING = 20;


// --- Initialization for Client-Side Email Lists in LocalStorage ---
function initializeClientSideEmailLists() {
    console.log("Initializing client-side email lists in localStorage if needed...");
    try {
        const listsToInit = [
            { key: LOCAL_STORAGE_ADMIN_EMAILS_KEY, defaults: DEFAULT_ADMIN_EMAILS, name: "Admin" },
            { key: LOCAL_STORAGE_VERIFIED_EMAILS_KEY, defaults: DEFAULT_VERIFIED_EMAILS, name: "Verified" },
            // *** FIXED: Changed DEFAULT_ALLOWED_EMAILS_LOGIN to DEFAULT_ALLOWED_EMAILS ***
            { key: LOCAL_STORAGE_ALLOWED_EMAILS_KEY, defaults: DEFAULT_ALLOWED_EMAILS, name: "Allowed" }
        ];
        listsToInit.forEach(list => {
            if (localStorage.getItem(list.key) === null) {
                localStorage.setItem(list.key, JSON.stringify(list.defaults));
                console.log(`Initialized ${list.name} emails in localStorage:`, list.defaults);
            }
        });
    } catch (error) {
        console.error("Error initializing client-side email lists in localStorage:", error);
     
    }
}

// --- Email List Management (CLIENT-SIDE SIMULATION using localStorage) ---
function getLocalStorageKeyForList(listType) {
    switch (listType.toLowerCase()) {
        case 'admin': return LOCAL_STORAGE_ADMIN_EMAILS_KEY;
        case 'verified': return LOCAL_STORAGE_VERIFIED_EMAILS_KEY;
        case 'allowed': return LOCAL_STORAGE_ALLOWED_EMAILS_KEY;
        default:
            console.error(`Unknown list type: ${listType}`);
            return null;
    }
}

// Modified to be async as localStorage access *could* theoretically be slow,
// and it prepares for potential future async operations (like real fetch).
async function getServerEmailList(listType) {
    const storageKey = getLocalStorageKeyForList(listType);
    if (!storageKey) return []; // Return empty array if key is invalid
    try {
        const storedList = localStorage.getItem(storageKey);
        // Ensure we return an array even if localStorage is corrupted or empty
        const parsedList = storedList ? JSON.parse(storedList) : [];
        return Array.isArray(parsedList) ? parsedList : [];
    } catch (error) {
        console.error(`Error fetching/parsing ${listType} email list from localStorage:`, error);
        showNotification(`Failed to load ${listType} emails. Check console.`, 'error');
        // Attempt to recover by resetting to default if parsing fails? Or just return empty?
        // Let's return empty for now to avoid overwriting potentially valid but temporarily unparsable data.
        // If this becomes a recurring issue, consider resetting here.
        // localStorage.removeItem(storageKey); // Optional: Clear corrupted data
        // initializeClientSideEmailLists(); // Optional: Re-initialize
        return []; // Return empty array on error
    }
}

async function addServerEmail(listType, email) {
    const storageKey = getLocalStorageKeyForList(listType);
    if (!storageKey) {
        showNotification(`Cannot add email: Unknown list type '${listType}'.`, 'error');
        return false;
    }
    const normalizedEmail = email.toLowerCase().trim();
    if (!normalizedEmail || !normalizedEmail.includes('@')) {
        showNotification("Invalid email format.", "error");
        return false;
    }
    try {
        let emails = await getServerEmailList(listType); // Use await here
        if (!emails.includes(normalizedEmail)) {
            emails.push(normalizedEmail);
            emails.sort(); // Keep the list sorted
            localStorage.setItem(storageKey, JSON.stringify(emails));
            console.log(`Added ${normalizedEmail} to ${listType} list.`);
            showNotification(`Added ${email} to ${listType} list.`, 'success');
            return true;
        } else {
            showNotification(`${email} is already in the ${listType} list.`, 'info');
            return false;
        }
    } catch (error) {
        console.error(`Error adding ${normalizedEmail} to ${listType} list:`, error);
        showNotification(`Failed to add ${email}. Check console.`, 'error');
        return false;
    }
}

async function removeServerEmail(listType, email) {
    const storageKey = getLocalStorageKeyForList(listType);
    if (!storageKey) {
        showNotification(`Cannot remove email: Unknown list type '${listType}'.`, 'error');
        return false;
    }
    const normalizedEmail = email.toLowerCase();
    try {
        let emails = await getServerEmailList(listType); // Use await here
        const initialLength = emails.length;
        emails = emails.filter(e => e !== normalizedEmail);
        if (emails.length < initialLength) {
            localStorage.setItem(storageKey, JSON.stringify(emails));
            console.log(`Removed ${normalizedEmail} from ${listType} list.`);
            showNotification(`Removed ${email} from ${listType} list.`, 'success');
            return true;
        } else {
            showNotification(`${email} not found in the ${listType} list.`, 'info');
            return false;
        }
    } catch (error) {
        console.error(`Error removing ${normalizedEmail} from ${listType} list:`, error);
        showNotification(`Failed to remove ${email}. Check console.`, 'error');
        return false;
    }
}


// Check if the user is logged in - *** MODIFIED TO BE ASYNC AND USE LOCALSTORAGE LISTS ***
async function checkLogin() { // <--- Made async
    console.log("checkLogin: Starting user check.");
    const loggedIn = localStorage.getItem('loggedIn');
    const userEmail = localStorage.getItem('userEmail');
    const userName = localStorage.getItem('userName');
    const userPicture = localStorage.getItem('userPicture');

    if (!loggedIn || !userName || !userEmail || !userPicture) {
        console.log("checkLogin: User not logged in or missing data. Redirecting to login.");
        window.isRedirecting = true; // Set flag
        const preloaderText = document.getElementById('preloader-text');
        if (preloaderText) {
            preloaderText.textContent = 'Redirecting to login...';
        }
        // Short delay allows preloader text update to render
        setTimeout(() => {
            window.location.href = '/login.html?error=not_logged_in';
        }, 50); // Reduced delay slightly
        return; // Stop execution
    }

    // --- User is logged in, proceed with setup ---
    console.log(`checkLogin: User ${userName} (${userEmail}) is logged in.`);

    // 1. Ensure client-side lists are initialized if they don't exist
    initializeClientSideEmailLists();

    // 2. Fetch the *current* email lists from localStorage
    console.log("checkLogin: Fetching current Admin and Verified email lists from localStorage.");
    // Use await because getServerEmailList is now async
    const currentAdminEmails = await getServerEmailList('admin');
    const currentVerifiedEmails = await getServerEmailList('verified');
    console.log("checkLogin: Fetched Admin Emails from localStorage:", currentAdminEmails);
    console.log("checkLogin: Fetched Verified Emails from localStorage:", currentVerifiedEmails);

    // 3. Determine admin status using the fetched list
    // Fallback to default only if localStorage fetch somehow failed AND returned null/undefined (getServerEmailList now returns [])
    const adminListToCheck = currentAdminEmails.length > 0 ? currentAdminEmails : DEFAULT_ADMIN_EMAILS;
    const isAdmin = adminListToCheck.includes(userEmail.toLowerCase());
    console.log(`checkLogin: Is admin (based on localStorage list)? ${isAdmin}`);

    if (isAdmin) {
        console.log("checkLogin: User is admin.");
        if (!sessionStorage.getItem('adminNotificationShown')) {
            showNotification("You're now web admin", 'info');
            sessionStorage.setItem('adminNotificationShown', 'true');
            console.log("checkLogin: Admin welcome notification shown for this session.");
        }
        console.log("checkLogin: Applying dark theme for admin.");
        applyDarkTheme(); // Ensure dark theme styles are applied if user is admin
    }

    console.log("checkLogin: Updating profile elements.");
    // 4. Update profile elements, using the fetched verified list for the badge
    const verifiedListToCheck = currentVerifiedEmails.length > 0 ? currentVerifiedEmails : DEFAULT_VERIFIED_EMAILS;
    document.querySelectorAll('.profile').forEach((profile, index) => {
        const imageElement = profile.querySelector('.image');
        if (imageElement) {
            imageElement.src = userPicture;
            imageElement.alt = userName;
        }
        const nameElement = profile.querySelector('.name');
        if (nameElement) {
            nameElement.textContent = userName;
            // Check against the fetched verified list
            if (verifiedListToCheck.includes(userEmail.toLowerCase())) {
                if (!nameElement.querySelector('.fa-circle-check')) {
                    const verificationBadge = document.createElement('i');
                    verificationBadge.className = 'fa-solid fa-circle-check';
                    // Badge color still depends on whether they are currently determined to be an admin
                    const badgeColor = isAdmin ? 'var(--main-color, #007bff)' : 'white';
                    verificationBadge.style.cssText = `color: ${badgeColor}; margin-left: 5px; font-size: 0.9em; vertical-align: middle;`;
                    nameElement.appendChild(verificationBadge);
                    console.log(`checkLogin: Added ${isAdmin ? 'admin' : 'non-admin'} verification badge (based on localStorage verified list).`);
                }
            } else {
                // Remove badge if they are NOT in the fetched verified list
                const existingBadge = nameElement.querySelector('.fa-circle-check');
                if (existingBadge) {
                    existingBadge.remove();
                    console.log("checkLogin: Removed verification badge as user is not in localStorage verified list.");
                }
            }
        }
        // Update role text (only if NOT admin)
        if (profile.closest('.side-bar')) {
             const roleElement = profile.querySelector('p.role');
             // If the user is NOT admin (based on localStorage check), set role to 'student' if element exists
             if (!isAdmin && roleElement) {
                 roleElement.textContent = 'student';
             }
             // If the user IS admin, the admin badge logic below will replace this anyway
        }
        console.log(`checkLogin: Updated profile section ${index + 1}.`);
    });

    // 5. Add admin-specific features *only if* isAdmin is true based on localStorage check
    if (isAdmin) {
        console.log("checkLogin: Adding admin features (badge/panel).");
        addAdminFeatures(); // This function handles adding the badge and panel
    }

    console.log("checkLogin: Updating dashboard greeting (conditionally).");
    updateDashboardGreeting(userName); // Update greeting based on page and Sri Lanka time

    console.log("checkLogin: Saving user activity.");
    saveUserActivity(); // Log activity

    console.log("checkLogin: Adding sign-out button if applicable.");
    addSignOutButton(); // Ensure sign-out is available

    console.log("checkLogin: Check complete. Waiting for window.load to hide preloader.");
}


// Apply dark theme styles & styles for new elements
// No changes needed here
function applyDarkTheme() {
    if (document.getElementById('admin-dark-theme-styles')) return;
    const darkThemeStyles = document.createElement('style');
    darkThemeStyles.id = 'admin-dark-theme-styles';
    darkThemeStyles.textContent = `
        /* Styles for Admin Badge (Dark Theme) */
        .admin-badge { background-color: #454547 !important; color: #d4d4d4 !important; border: 1px solid #2d2d3d !important; padding: 5px 10px; border-radius: 4px; font-size: 1.4rem; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; margin-top: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s ease !important; }
        .admin-badge:hover { background-color: #2d2d3d !important; transform: translateY(-2px) !important; box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important; }
        /* Styles for Sign Out Button (Dark Theme) */
        .sign-out-btn { background-color: #333333 !important; color: #d4d4d4 !important; border: 1px solid #2d2d3d !important; padding: 8px 12px; border-radius: 7px; cursor: pointer; margin-top: 10px; font-size: 1.4rem; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease !important; width: 100%; justify-content: center; font-weight: 500; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .sign-out-btn:hover { background-color: #d32f2f !important; border-color: #d32f2f !important; color: #ffffff !important; transform: translateY(-2px) !important; box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important; }
        /* Styles for Admin Control Panel Email Sections (Dark Theme) */
        .admin-panel-section { margin-top: 15px; padding-top: 15px; border-top: 1px solid #3a3a4a; }
        .admin-panel-content > .admin-panel-section:first-child { margin-top: 0; padding-top: 0; border-top: none; }
        .admin-panel-section h5 { font-size: 1.4rem; font-weight: 600; color: #ccc; margin-bottom: 10px; }
        .admin-panel-section .email-list { border: 1px solid #444; border-radius: 5px; max-height: 150px; margin-bottom: 10px; font-size: 1.3rem; padding: 0; list-style: none; overflow-y: auto; background-color: #333341; }
        .admin-panel-section .email-list li { padding: 5px 10px; border-bottom: 1px solid #3a3a4a; color: #ccc; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .admin-panel-section .email-list li:last-child { border-bottom: none; }
        .admin-panel-section .email-list li span { margin-right: 8px; word-break: break-all; flex-grow: 1; }
        .admin-panel-section .email-list li button { background: #444452; border: 1px solid #555; color: #ff8a8a; border-radius: 4px; cursor: pointer; font-weight: bold; line-height: 1; padding: 1px 6px; font-size: 1.3rem; transition: background-color 0.2s; flex-shrink: 0; }
        .admin-panel-section .email-list li button:hover { background-color: #555562; }
        .admin-panel-section .email-list li button:disabled { opacity: 0.5; cursor: not-allowed; }
        .admin-panel-section .email-add-form { display: flex; gap: 5px; margin-top: 8px; }
        .admin-panel-section .email-add-form input[type="email"] { flex-grow: 1; background-color: #333341; color: #e0e0e0; border: 1px solid #444; border-radius: 4px; padding: 6px 8px; font-size: 1.3rem; }
        .admin-panel-section .email-add-form button { background-color: #2a9d47; color: white; border: none; border-radius: 4px; padding: 6px 10px; font-size: 1.3rem; cursor: pointer; transition: background-color 0.2s; flex-shrink: 0; }
        .admin-panel-section .email-add-form button:hover { background-color: #23813a; }
        .admin-panel-section .email-add-form button:disabled { background-color: #777; cursor: not-allowed; }
        .admin-panel-section hr.section-separator { border: none; border-top: 1px dashed #3a3a4a; margin: 15px 0; }
        /* Styles for Box Toggles Section (Dark Theme) - Includes FIX */
        .box-toggles-section .toggle-container { display: flex; align-items: center; justify-content: space-between; padding: 6px 0; gap: 10px; }
        .box-toggles-section .master-toggle { padding: 8px 0; border-bottom: 1px dashed #3a3a4a; margin-bottom: 8px; }
        .box-toggles-section .toggle-container > label:not(.switch) { flex-grow: 1; margin-right: 0; font-size: 1.3rem; cursor: pointer; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .box-toggles-section .master-toggle label { font-weight: 500; }
        .box-toggles-section .switch { flex-shrink: 0; }
        .box-toggles-section p { font-size: 12px; color: #a0a0a0; text-align: center; margin: 10px 0; }
    `;
    document.head.appendChild(darkThemeStyles);
}

// Update dashboard greeting using Sri Lanka Time Zone, ONLY on specific pages
// No changes needed here
function updateDashboardGreeting(userName) {
    console.log("updateDashboardGreeting: Checking page for greeting.");
    const currentPath = window.location.pathname;
    console.log("updateDashboardGreeting: Current Pathname:", currentPath);
    const isGreetingPage = currentPath.endsWith('/dashboard.php') || currentPath.endsWith('/dashboard') || currentPath === '/';

    if (!isGreetingPage) {
        console.log(`updateDashboardGreeting: Skipping greeting update on page: ${currentPath}`);
        return;
    }

    console.log("updateDashboardGreeting: Page is eligible. Determining greeting for", userName);
    let timeOfDayGreeting = 'Hello';
    let sriLankaHour = -1;

    try {
        const options = { timeZone: 'Asia/Colombo', hour: 'numeric', hour12: false };
        const formatter = new Intl.DateTimeFormat([], options);
        const now = new Date();
        sriLankaHour = parseInt(formatter.format(now), 10);
        console.log(`updateDashboardGreeting: Current hour in Sri Lanka (Asia/Colombo): ${sriLankaHour}`);

        if (sriLankaHour >= 5 && sriLankaHour < 12) { timeOfDayGreeting = 'Good Morning'; }
        else if (sriLankaHour >= 12 && sriLankaHour < 17) { timeOfDayGreeting = 'Good Afternoon'; }
        else if (sriLankaHour >= 17 && sriLankaHour < 22) { timeOfDayGreeting = 'Good Evening'; }
        else { timeOfDayGreeting = 'Good Night'; }
    } catch (error) {
        console.error("updateDashboardGreeting: Error getting Sri Lanka time:", error);
        timeOfDayGreeting = 'Hello';
    }

    const fullGreeting = `Hi, ${userName} ${timeOfDayGreeting}!`;
    console.log("updateDashboardGreeting: Determined greeting:", fullGreeting);
    let headingElement = null;
    const selectorsToTry = [
        '#content h1.heading', '#content h1', 'main h1.heading', 'main h1',
        'section.dashboard h1', 'section.home-grid h1.heading', '.home-grid h1',
        'section.courses > h1.heading', 'body h1'
    ];
    console.log("updateDashboardGreeting: Trying selectors:", selectorsToTry);

    for (const selector of selectorsToTry) {
        headingElement = document.querySelector(selector);
        if (headingElement) {
            console.log(`updateDashboardGreeting: Found heading element using selector: '${selector}'`);
            break;
        }
    }

    if (headingElement) {
        headingElement.textContent = fullGreeting;
        console.log(`updateDashboardGreeting: Updated heading text to: "${fullGreeting}"`);
    } else {
        console.warn("updateDashboardGreeting: Could not find a suitable heading element to update with the greeting on this page. Please check HTML structure and selectorsToTry array.");
    }
}

// Add admin features (badge, panel)
// No changes needed here - it's called conditionally by the modified checkLogin
function addAdminFeatures() {
    console.log("addAdminFeatures: Adding admin badge and features.");
    // Add admin badge to sidebar profile
    const sidebarProfile = document.querySelector('.side-bar .profile');
    if (sidebarProfile) {
        const roleParagraph = sidebarProfile.querySelector('p.role');
        if (roleParagraph) {
            console.log("addAdminFeatures: Replacing sidebar role paragraph with admin badge.");
            const adminBadge = document.createElement('div');
            adminBadge.className = 'admin-badge';
            adminBadge.innerHTML = '<i class="fas fa-shield-alt"></i> Website Admin';
            roleParagraph.parentNode.replaceChild(adminBadge, roleParagraph);
        } else if (!sidebarProfile.querySelector('.admin-badge')) {
            // Add badge if role paragraph doesn't exist and badge isn't already there
            console.log("addAdminFeatures: Adding sidebar admin badge directly (no role p found).");
            const adminBadge = document.createElement('div');
            adminBadge.className = 'admin-badge';
            adminBadge.innerHTML = '<i class="fas fa-shield-alt"></i> Website Admin';
            const nameEl = sidebarProfile.querySelector('.name');
            // Insert after name if possible, otherwise just append
            if (nameEl && nameEl.nextSibling) {
                 nameEl.parentNode.insertBefore(adminBadge, nameEl.nextSibling);
            } else if (nameEl) {
                 nameEl.parentNode.appendChild(adminBadge);
            }
             else {
                sidebarProfile.appendChild(adminBadge);
            }
        }
    } else {
        console.log("addAdminFeatures: Sidebar profile not found.");
    }

    // Add admin badge to header profile (if it exists and doesn't have one)
    const headerProfile = document.querySelector('header .profile');
    if (headerProfile && !headerProfile.querySelector('.admin-badge') && !headerProfile.querySelector('p.role')) {
        console.log("addAdminFeatures: Adding header admin badge.");
        const adminBadge = document.createElement('div');
        adminBadge.className = 'admin-badge';
        adminBadge.innerHTML = '<i class="fas fa-shield-alt"></i> Website Admin';
        const nameElement = headerProfile.querySelector('.name');
        // Insert after name if possible, otherwise just append
        if (nameElement && nameElement.nextSibling) {
             nameElement.parentNode.insertBefore(adminBadge, nameElement.nextSibling);
        } else if (nameElement) {
             nameElement.parentNode.appendChild(adminBadge);
        }
         else {
            headerProfile.appendChild(adminBadge);
        }
    }

    // Create admin control panel if it doesn't exist
    if (!document.querySelector('.admin-control-panel')) {
        console.log("addAdminFeatures: Creating admin control panel.");
        createAdminControlPanel(); // Create the panel
    } else {
        console.log("addAdminFeatures: Admin control panel already exists.");
    }
}

// Get box name for visibility toggles
// No changes needed here
function getBoxName(box, index) {
    let boxName = '';
    // Try finding a specific title element first
    const titleElement = box.querySelector('.title, .box-title, .card-title'); // Added common classes
    if (titleElement) {
        boxName = titleElement.textContent.trim();
    } else {
        // Try finding any heading element
        const headingElements = box.querySelectorAll('h1, h2, h3, h4, h5, h6, .heading');
        if (headingElements.length > 0) {
            boxName = headingElements[0].textContent.trim();
        }
        // Fallback: Use class names (more robust filtering)
        if (!boxName) {
            const classNames = Array.from(box.classList).filter(cn =>
                !['box', 'card', 'container', 'box-container', 'flex', 'grid', 'swiper-slide'].includes(cn) && // Filter common layout classes
                !cn.startsWith('col-') && !cn.startsWith('span-') // Filter grid classes
            );
            if (classNames.length > 0) {
                // Capitalize the first significant class name found
                boxName = classNames[0].replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            }
        }
    }
    // Final fallback if absolutely nothing is found
    if (!boxName || boxName.length === 0) {
        if (box.querySelector('.timer')) { // Specific check for timer box
            boxName = 'A/L Timer';
        } else {
            boxName = `Card ${index + 1}`; // Generic fallback
        }
    }
    return boxName;
}


// Create the entire Admin Control Panel
// No changes needed here
function createAdminControlPanel() {
    // Uses constants ADMIN_PANEL_COLLAPSED_HEIGHT and ADMIN_PANEL_PADDING defined globally
    let isPanelCollapsed = true; // Local state for collapse button logic
    const EXPANDED_PANEL_MAX_HEIGHT_VH = 60;
    const adminControlPanel = document.createElement('div');
    adminControlPanel.className = 'admin-control-panel';
    adminControlPanel.dataset.lastTop = ''; // Store last expanded top position

    // Calculate initial positions
    const initialCollapsedTop = window.innerHeight - ADMIN_PANEL_COLLAPSED_HEIGHT - ADMIN_PANEL_PADDING;
    // Default expanded top tries to center the expanded view, but respects padding
    const defaultExpandedTop = Math.max(ADMIN_PANEL_PADDING, window.innerHeight - (window.innerHeight * (EXPANDED_PANEL_MAX_HEIGHT_VH / 100)) - ADMIN_PANEL_PADDING);
    adminControlPanel.dataset.lastTop = defaultExpandedTop; // Set initial default expanded top

    // Initial styling for collapsed state
    adminControlPanel.style.cssText = `
        position: fixed;
        top: ${initialCollapsedTop}px; /* Start collapsed at bottom */
        max-height: ${ADMIN_PANEL_COLLAPSED_HEIGHT}px; /* Start collapsed height */
        left: ${ADMIN_PANEL_PADDING}px;
        right: auto; /* Let left and width control position */
        bottom: auto;
        background: #1e1e2d; /* Dark background */
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        padding: 0 15px 15px 15px; /* No top padding initially, added by handle */
        z-index: 1000;
        display: flex;
        flex-direction: column;
        border: 1px solid #2d2d3d; /* Darker border */
        color: #e6e6e6; /* Light text */
        width: 300px; /* Fixed width */
        max-width: calc(100vw - ${ADMIN_PANEL_PADDING*2}px); /* Prevent overflow on small screens */
        transition: top 0.3s ease-out, max-height 0.3s ease-out, opacity 0.3s ease-out;
        overflow: hidden; /* Crucial for collapsing */
        opacity: 1; /* Start visible */
    `;

    // Drag Handle
    const dragHandle = document.createElement('div');
    dragHandle.style.cssText = `
        width: calc(100% + 30px); /* Span full width including padding */
        margin: 0 -15px 5px -15px; /* Overlap padding */
        height: 20px;
        cursor: grab;
        background-color: #2d2d3d; /* Slightly darker handle */
        border-top-left-radius: 8px; /* Match panel radius */
        border-top-right-radius: 8px;
        border-bottom: 1px solid #3a3a4a; /* Separator line */
        flex-shrink: 0; /* Prevent handle from shrinking */
    `;
    adminControlPanel.appendChild(dragHandle);

    // Panel Header (Title + Collapse Button)
    const panelHeader = document.createElement('div');
    panelHeader.style.cssText = `
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #2d2d3d;
        padding-bottom: 10px;
        width: 100%;
        flex-shrink: 0; /* Prevent header from shrinking */
    `;
    const panelTitle = document.createElement('h4');
    panelTitle.textContent = 'Admin Controls';
    panelTitle.style.cssText = `margin: 0; font-size: 1.5rem;`;
    const collapseBtn = document.createElement('button');
    collapseBtn.innerHTML = '<i class="fas fa-chevron-up"></i>'; // Start with 'expand' icon
    collapseBtn.title = 'Expand Panel';
    collapseBtn.style.cssText = `background: none; border: none; color: #aaa; font-size: 1.6rem; cursor: pointer; padding: 0 5px;`;
    panelHeader.appendChild(panelTitle);
    panelHeader.appendChild(collapseBtn);
    adminControlPanel.appendChild(panelHeader);

    // Panel Content Area (Scrollable)
    const panelContent = document.createElement('div');
    panelContent.className = 'admin-panel-content';
    panelContent.style.cssText = `
        overflow-y: auto; /* Enable vertical scroll */
        /* Calculate max height for content based on expanded panel height and header/padding */
        max-height: calc(${EXPANDED_PANEL_MAX_HEIGHT_VH}vh - ${ADMIN_PANEL_COLLAPSED_HEIGHT}px - 10px); /* Estimate header/padding */
        width: 100%;
        display: none; /* Start hidden */
        transition: opacity 0.3s ease-out;
        opacity: 0; /* Start faded out */
        padding-right: 5px; /* Space for scrollbar */
        pointer-events: none; /* Prevent interaction when hidden */
        margin-top: 10px; /* Space below header */
    `;

    // Populate Content Area
    createBoxTogglesSection(panelContent); // Add Box Toggles
    const separator1 = document.createElement('hr'); // Add separator
    separator1.className = 'section-separator';
    panelContent.appendChild(separator1);
    // Add Email Management sections
    createEmailManagementSection(panelContent, 'Allowed Emails', 'allowed');
    createEmailManagementSection(panelContent, 'Verified Emails', 'verified');
    createEmailManagementSection(panelContent, 'Admin Emails', 'admin');

    adminControlPanel.appendChild(panelContent); // Add content to panel

    // Collapse Button Logic
    collapseBtn.addEventListener('click', function() {
        isPanelCollapsed = !isPanelCollapsed; // Toggle local state

        if (isPanelCollapsed) {
            // --- Collapse Panel ---
            // Store the current top position *before* collapsing, if it looks like a valid expanded position
            const currentTop = adminControlPanel.offsetTop;
            if (!adminControlPanel.dataset.lastTop || currentTop < (window.innerHeight - ADMIN_PANEL_COLLAPSED_HEIGHT - ADMIN_PANEL_PADDING - 10 /* tolerance */ )) {
                adminControlPanel.dataset.lastTop = currentTop; // Store last valid expanded top
            }
            const targetTop = window.innerHeight - ADMIN_PANEL_COLLAPSED_HEIGHT - ADMIN_PANEL_PADDING; // Recalculate target bottom position
            adminControlPanel.style.top = `${targetTop}px`;
            adminControlPanel.style.maxHeight = `${ADMIN_PANEL_COLLAPSED_HEIGHT}px`;
            panelContent.style.opacity = '0';
            panelContent.style.pointerEvents = 'none';
            // Hide content completely after fade out transition
            setTimeout(() => { if(isPanelCollapsed) panelContent.style.display = 'none'; }, 300); // Match transition duration
            collapseBtn.innerHTML = '<i class="fas fa-chevron-up"></i>'; // Show 'expand' icon
            collapseBtn.title = 'Expand Panel';
        } else {
            // --- Expand Panel ---
            // Retrieve the last known expanded top position, or use default
            let restoreTop = adminControlPanel.dataset.lastTop ? parseInt(adminControlPanel.dataset.lastTop, 10) : defaultExpandedTop;
            // Ensure the restore position is within valid viewport bounds
            const panelCurrentHeightEstimate = window.innerHeight * (EXPANDED_PANEL_MAX_HEIGHT_VH / 100);
            const minTopPossible = ADMIN_PANEL_PADDING;
            const maxTopPossible = Math.max(ADMIN_PANEL_PADDING, window.innerHeight - panelCurrentHeightEstimate - ADMIN_PANEL_PADDING); // Ensure panel doesn't go off bottom
            restoreTop = Math.max(minTopPossible, Math.min(restoreTop, maxTopPossible)); // Clamp value

            adminControlPanel.style.top = `${restoreTop}px`; // Move to restored/calculated position
            adminControlPanel.style.maxHeight = `${EXPANDED_PANEL_MAX_HEIGHT_VH}vh`; // Expand height
            panelContent.style.display = 'block'; // Make content visible
            // Use rAF to ensure display:block is applied before starting opacity transition
            requestAnimationFrame(() => {
                panelContent.style.opacity = '1';
                panelContent.style.pointerEvents = 'auto'; // Make interactable
            });
            collapseBtn.innerHTML = '<i class="fas fa-chevron-down"></i>'; // Show 'collapse' icon
            collapseBtn.title = 'Collapse Panel';
        }
    });

    document.body.appendChild(adminControlPanel);
    console.log("createAdminControlPanel: Admin control panel added to body (initially collapsed).");
    makeDraggable(adminControlPanel, dragHandle); // Make it draggable using the handle
    updateCollapsedPanelPosition(); // Ensure correct initial collapsed position respecting saved state if applicable
}


// Creates just the Box Toggles Section within the Admin Panel
// No changes needed here
function createBoxTogglesSection(container) {
    const section = document.createElement('div');
    section.className = 'admin-panel-section box-toggles-section';
    const sectionTitle = document.createElement('h5');
    sectionTitle.textContent = 'Page Element Visibility';
    section.appendChild(sectionTitle);

    // Find controllable elements - more robust selector? Maybe target specific sections?
    // Using '.card' within '.courses' section seems specific enough based on context.
    const boxes = document.querySelectorAll('section.courses .card'); // Or 'section.home-grid .box' etc.
    const boxIds = {}; // Keep track of valid boxes found

    console.log(`createBoxTogglesSection: Found ${boxes.length} controllable elements (.card in section.courses).`);

    // Ensure boxes have unique IDs and apply initial visibility state
    boxes.forEach((box, index) => {
        if (!box.id || !box.id.startsWith('controllable-card-')) {
            box.id = `controllable-card-${index + 1}`; // Assign a predictable ID if missing
        }
        boxIds[box.id] = true; // Mark this box as controllable

        const savedState = getBoxState(box.id); // Get state from localStorage
        box.style.display = savedState ? 'block' : 'none'; // Apply state

        // Initialize state in localStorage if it's the first time seeing this box
        if (savedState === null) { // 'null' indicates not found in storage
            saveBoxState(box.id, true); // Default to visible
        }
    });

    if (boxes.length > 0) {
        // --- Master Toggle ---
        const masterToggleContainer = document.createElement('div');
        masterToggleContainer.className = 'toggle-container master-toggle';
        const masterToggleLabel = document.createElement('label');
        masterToggleLabel.textContent = 'Toggle All Cards';
        const masterToggleSwitch = createToggleSwitch();
        const masterToggleInput = masterToggleSwitch.querySelector('input');
        // Set initial state based on all individual boxes being checked
        masterToggleInput.checked = Array.from(boxes).every(box => getBoxState(box.id));

        masterToggleInput.addEventListener('change', function() {
            const newState = this.checked;
            boxes.forEach(box => {
                if (boxIds[box.id]) { // Only affect controllable boxes found earlier
                    box.style.display = newState ? 'block' : 'none';
                    saveBoxState(box.id, newState);
                    // Update corresponding individual toggle switch
                    const individualInput = section.querySelector(`#toggle-${box.id}`);
                    if (individualInput) individualInput.checked = newState;
                }
            });
            showNotification(`All cards set to ${newState ? 'visible' : 'hidden'}`, 'info');
        });

        masterToggleContainer.appendChild(masterToggleLabel);
        masterToggleContainer.appendChild(masterToggleSwitch);
        section.appendChild(masterToggleContainer);

        // --- Individual Toggles ---
        boxes.forEach((box, index) => {
            if (!boxIds[box.id]) return; // Skip if not marked as controllable

            const boxToggleContainer = document.createElement('div');
            boxToggleContainer.className = 'toggle-container box-toggle';

            let boxTitle = getBoxName(box, index); // Get a user-friendly name
            const fullTitle = boxTitle; // Store full title for tooltip
            if (boxTitle.length > 25) { // Truncate long titles for display
                boxTitle = boxTitle.substring(0, 22) + '...';
            }

            const boxToggleLabel = document.createElement('label');
            boxToggleLabel.textContent = boxTitle;
            boxToggleLabel.htmlFor = `toggle-${box.id}`; // Link label to input
            boxToggleLabel.title = fullTitle; // Show full title on hover

            const boxToggleSwitch = createToggleSwitch();
            const boxToggleInput = boxToggleSwitch.querySelector('input');
            boxToggleInput.id = `toggle-${box.id}`; // Unique ID for the input
            boxToggleInput.checked = getBoxState(box.id); // Set initial state

            boxToggleInput.addEventListener('change', function() {
                const isVisible = this.checked;
                box.style.display = isVisible ? 'block' : 'none';
                saveBoxState(box.id, isVisible);
                showNotification(`${fullTitle} card is now ${isVisible ? 'visible' : 'hidden'}`, isVisible ? 'success' : 'info');

                // Update master toggle if all individuals now match its state
                const allCheckedNow = Array.from(section.querySelectorAll('.box-toggle input')).every(input => input.checked);
                const masterInput = section.querySelector('.master-toggle input');
                if (masterInput && masterInput.checked !== allCheckedNow) {
                    masterInput.checked = allCheckedNow;
                }
            });

            boxToggleContainer.appendChild(boxToggleLabel);
            boxToggleContainer.appendChild(boxToggleSwitch);
            section.appendChild(boxToggleContainer);
        });
    } else {
        const noBoxesMessage = document.createElement('p');
        noBoxesMessage.textContent = 'No controllable cards (.card) found in section.courses.';
        section.appendChild(noBoxesMessage);
    }
    container.appendChild(section);
}


// Creates Email List Management Section within the Admin Panel
// No changes needed here - uses async getServerEmailList now
function createEmailManagementSection(container, title, listType) {
    const section = document.createElement('div');
    section.className = 'admin-panel-section email-management-section';

    const sectionTitle = document.createElement('h5');
    sectionTitle.textContent = title;
    section.appendChild(sectionTitle);

    const emailListElement = document.createElement('ul');
    emailListElement.className = 'email-list';
    section.appendChild(emailListElement);

    // Function to fetch and render the email list
    const renderEmailList = async () => {
        emailListElement.innerHTML = '<li style="font-style: italic; color: #888;">Loading...</li>'; // Loading indicator
        emailListElement.style.opacity = '0.7';
        try {
            const emails = await getServerEmailList(listType); // Use await
            emailListElement.innerHTML = ''; // Clear loading/previous content
            emailListElement.style.opacity = '1';
            if (emails.length === 0) {
                emailListElement.innerHTML = `<li style="font-style: italic; color: #888;">No emails listed.</li>`;
            } else {
                emails.forEach(email => {
                    const li = document.createElement('li');
                    const emailSpan = document.createElement('span');
                    emailSpan.textContent = email;
                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.innerHTML = '&times;'; // Use '×' symbol for delete
                    deleteBtn.title = `Remove ${email}`;
                    deleteBtn.onclick = async () => {
                        // Confirmation dialog
                        if (!confirm(`Are you sure you want to remove "${email}" from the ${listType} list?`)) {
                            return;
                        }
                        // Disable button and show loading state
                        deleteBtn.disabled = true;
                        deleteBtn.innerHTML = '...';
                        li.style.opacity = '0.5'; // Dim the list item

                        const success = await removeServerEmail(listType, email); // Use await

                        if (success) {
                            li.remove(); // Remove item from UI on success
                            // No need to re-render whole list, just remove the element
                        } else {
                            // Re-enable button and restore opacity on failure
                            deleteBtn.disabled = false;
                            deleteBtn.innerHTML = '&times;';
                            li.style.opacity = '1';
                            // Notification is shown by removeServerEmail on failure
                        }
                    };
                    li.appendChild(emailSpan);
                    li.appendChild(deleteBtn);
                    emailListElement.appendChild(li);
                });
            }
        } catch (error) {
            // Display error in the list area itself
            emailListElement.innerHTML = `<li style="color: #f46a6a;">Error loading list. Check console.</li>`;
            emailListElement.style.opacity = '1';
        }
    };

    // Add Email Form
    const addEmailForm = document.createElement('form');
    addEmailForm.className = 'email-add-form';
    addEmailForm.addEventListener('submit', async (e) => {
        e.preventDefault(); // Prevent default form submission
        const emailInput = addEmailForm.querySelector('input[type="email"]');
        const addButton = addEmailForm.querySelector('button');
        const newEmail = emailInput.value.trim();

        if (newEmail) {
            // Disable form elements during submission
            addButton.disabled = true;
            addButton.textContent = '...';
            emailInput.disabled = true;

            const success = await addServerEmail(listType, newEmail); // Use await

            if (success) {
                emailInput.value = ''; // Clear input on success
                renderEmailList(); // Re-render the list to include the new email
            }
            // Re-enable form elements regardless of success/failure
            addButton.disabled = false;
            addButton.textContent = 'Add';
            emailInput.disabled = false;
            emailInput.focus(); // Focus input for easy next entry
        } else {
            showNotification('Please enter an email address.', 'error');
        }
    });

    const emailInput = document.createElement('input');
    emailInput.type = 'email';
    emailInput.placeholder = 'Add email address';
    emailInput.required = true;

    const addButton = document.createElement('button');
    addButton.type = 'submit';
    addButton.textContent = 'Add';

    addEmailForm.appendChild(emailInput);
    addEmailForm.appendChild(addButton);
    section.appendChild(addEmailForm);

    renderEmailList(); // Initial rendering of the list
    container.appendChild(section);
}

// Create toggle switch element (reusable UI component)
// No changes needed here
function createToggleSwitch() {
    const toggleSwitch = document.createElement('label');
    toggleSwitch.className = 'switch';
    toggleSwitch.style.cssText = `
        position: relative;
        display: inline-block;
        width: 46px; /* Width of the switch */
        height: 24px; /* Height of the switch */
        flex-shrink: 0; /* Prevent shrinking in flex layouts */
    `;

    const toggleInput = document.createElement('input');
    toggleInput.type = 'checkbox';
    toggleInput.style.cssText = `
        opacity: 0; /* Hide the actual checkbox */
        width: 0;
        height: 0;
    `;

    const toggleSlider = document.createElement('span');
    toggleSlider.className = 'slider round'; // Use 'round' for rounded slider/knob
    toggleSlider.style.cssText = `
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #555; /* Default background (off state) */
        transition: .4s; /* Smooth transition for background and knob */
        border-radius: 24px; /* Make the slider track rounded */
    `;

    // Inject styles for the switch knob and checked state if not already present
    if (!document.querySelector('style#toggle-styles')) {
        const style = document.createElement('style');
        style.id = 'toggle-styles';
        style.textContent = `
            .switch .slider:before { /* The sliding knob */
                position: absolute;
                content: "";
                height: 18px; /* Knob height */
                width: 18px; /* Knob width */
                left: 3px; /* Padding from left */
                bottom: 3px; /* Padding from bottom */
                background-color: white;
                transition: .4s; /* Smooth sliding animation */
                border-radius: 50%; /* Make the knob circular */
            }
            .switch input:checked + .slider {
                background-color: #4a6cf7; /* Background color when checked (on state) */
            }
            .switch input:focus + .slider {
                box-shadow: 0 0 1px #4a6cf7; /* Optional focus outline */
            }
            .switch input:checked + .slider:before {
                transform: translateX(22px); /* Move knob to the right when checked */
            }
        `;
        document.head.appendChild(style);
    }

    toggleSwitch.appendChild(toggleInput);
    toggleSwitch.appendChild(toggleSlider);
    return toggleSwitch;
}

// Make element draggable within viewport bounds
// No changes needed here
function makeDraggable(element, handle) {
    let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    const dragHandle = handle || element; // Use provided handle or the element itself

    element.style.position = 'fixed'; // Ensure element is fixed for positioning
    dragHandle.style.cursor = 'grab'; // Set initial cursor
    const PADDING = ADMIN_PANEL_PADDING; // Use global constant for boundary checks

    dragHandle.onmousedown = dragMouseDown;

    function dragMouseDown(e) {
        e = e || window.event;
        // Prevent drag on right-click or middle-click
        if (e.button !== 0) return;
        e.preventDefault(); // Prevent text selection during drag

        // Get the initial mouse cursor position
        pos3 = e.clientX;
        pos4 = e.clientY;

        // Set up event listeners for drag stop and movement
        document.onmouseup = closeDragElement;
        document.onmousemove = elementDrag;

        // Update styles during drag
        dragHandle.style.cursor = 'grabbing';
        element.style.userSelect = 'none'; // Prevent text selection on the element
        element.style.zIndex = '1001'; // Bring element to front during drag
    }

    function elementDrag(e) {
        e = e || window.event;
        e.preventDefault();

        // Calculate the new cursor position
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;

        // Calculate the element's new position
        let newTop = element.offsetTop - pos2;
        let newLeft = element.offsetLeft - pos1;

        // Boundary checks - keep element within viewport minus padding
        newTop = Math.max(PADDING, Math.min(newTop, window.innerHeight - element.offsetHeight - PADDING));
        newLeft = Math.max(PADDING, Math.min(newLeft, window.innerWidth - element.offsetWidth - PADDING));

        // Set the element's new position
        element.style.top = newTop + "px";
        element.style.left = newLeft + "px";
        element.style.right = 'auto'; // Ensure left/top positioning takes precedence
        element.style.bottom = 'auto';
    }

    function closeDragElement() {
        // Stop listening for mouse movements and release
        document.onmouseup = null;
        document.onmousemove = null;

        // Reset styles after drag
        dragHandle.style.cursor = 'grab';
        element.style.userSelect = ''; // Re-allow text selection
        element.style.zIndex = '1000'; // Reset z-index

        // Store the final top position in the dataset (used by collapse/expand logic)
        element.dataset.lastTop = element.offsetTop;
        // Save the final position to localStorage
        saveControlPanelPosition(element.offsetLeft, element.offsetTop);
        console.log(`makeDraggable: Drag ended. Stored lastTop: ${element.dataset.lastTop}. Saved position (${element.offsetLeft}, ${element.offsetTop})`);
    }

    // --- Restore Position on Load ---
    const savedPosition = getControlPanelPosition();
    if (savedPosition && typeof savedPosition.left === 'number' && typeof savedPosition.top === 'number') {
        console.log(`makeDraggable: Restoring saved position (${savedPosition.left}, ${savedPosition.top})`);

        // IMPORTANT: Apply boundary checks to the *restored* position as well
        // The window size might have changed since the position was saved
        let restoredTop = Math.max(PADDING, Math.min(savedPosition.top, window.innerHeight - element.offsetHeight - PADDING));
        let restoredLeft = Math.max(PADDING, Math.min(savedPosition.left, window.innerWidth - element.offsetWidth - PADDING));

        element.style.top = restoredTop + 'px';
        element.style.left = restoredLeft + 'px';
        element.style.right = 'auto';
        element.style.bottom = 'auto';

        // Update the dataset's lastTop with the *applied* restored position
        element.dataset.lastTop = restoredTop;
        console.log(`makeDraggable: Restored position applied and clamped. Stored lastTop: ${restoredTop}. Initial visual state is set by createAdminControlPanel.`);
    } else {
        console.log("makeDraggable: No saved position found or invalid data. Using default positions set by createAdminControlPanel.");
        // Initial position is already set by createAdminControlPanel based on collapsed state logic
    }
}


// Save/Retrieve Admin Control Panel position to/from localStorage
// No changes needed here
function saveControlPanelPosition(left, top) {
    try {
        // Basic validation before saving
        if (typeof left === 'number' && typeof top === 'number' && !isNaN(left) && !isNaN(top)) {
            localStorage.setItem('adminPanelPosition', JSON.stringify({ left, top }));
        } else {
            console.warn("saveControlPanelPosition: Attempted to save invalid position:", { left, top });
        }
    } catch (e) {
        console.error("Error saving panel position to localStorage:", e);
    }
}
function getControlPanelPosition() {
    const position = localStorage.getItem('adminPanelPosition');
    if (!position) return null; // No saved position
    try {
        const parsed = JSON.parse(position);
        // Validate data structure and types after parsing
        if (parsed && typeof parsed.left === 'number' && typeof parsed.top === 'number') {
            return parsed;
        } else {
            console.warn("Error parsing panel position: Invalid data format.", parsed);
            localStorage.removeItem('adminPanelPosition'); // Remove invalid data
            return null;
        }
    } catch (e) {
        console.error("Error parsing panel position from localStorage:", e);
        localStorage.removeItem('adminPanelPosition'); // Remove corrupted data
        return null;
    }
}


// Save/Retrieve visibility state of a specific box/card to/from localStorage
// No changes needed here
function saveBoxState(boxId, isVisible) {
    try {
        const states = JSON.parse(localStorage.getItem('boxStates') || '{}');
        states[boxId] = !!isVisible; // Ensure boolean value is stored
        localStorage.setItem('boxStates', JSON.stringify(states));
    } catch (e) {
        console.error("Error saving box state to localStorage:", e);
        // Potentially show a user notification here if saving fails repeatedly
    }
}
function getBoxState(boxId) {
    try {
        const states = JSON.parse(localStorage.getItem('boxStates') || '{}');
        // Return the stored state if it exists, otherwise return true (default visible)
        // Use 'null' to indicate "not found" which helps differentiate from explicitly false
        return boxId in states ? states[boxId] : null;
    } catch (e) {
        console.error("Error retrieving box state from localStorage:", e);
        // If storage is corrupted, default to visible
        return true; // Default to visible on error
    }
}

// Show a temporary notification message
// No changes needed here
function showNotification(message, type = 'info') {
    let n = document.getElementById('custom-notification');
    if (!n) {
        n = document.createElement('div');
        n.id = 'custom-notification';
        // Base styles - ensure it's positioned correctly and looks decent
        n.style.cssText = `
            position: fixed;
            top: 20px; /* Position from top */
            left: 50%; /* Center horizontally */
            transform: translateX(-50%) translateY(-50px); /* Start off-screen above */
            background-color: #2a2a38; /* Dark background */
            color: #e0e0e0; /* Light text */
            padding: 12px 20px;
            border-radius: 6px;
            z-index: 1001; /* Above most elements, but maybe below modals */
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
            font-size: 14px;
            border: 1px solid #3a3a4a; /* Default border */
            opacity: 0; /* Start hidden */
            transition: opacity 0.3s ease, transform 0.3s ease; /* Smooth transitions */
            pointer-events: none; /* Don't block clicks when hidden/fading */
            max-width: 90%; /* Prevent overly wide notifications */
            text-align: left; /* Ensure text aligns left */
        `;
        document.body.appendChild(n);
    }

    // Determine icon and border color based on type
    let icon = 'fas fa-info-circle', iColor = '#58a6ff', bColor = '#58a6ff'; // Default: info
    if (type === 'success') {
        icon = 'fas fa-check-circle';
        iColor = '#4caf50'; // Green
        bColor = '#4caf50';
    } else if (type === 'error') {
        icon = 'fas fa-times-circle';
        iColor = '#f44336'; // Red
        bColor = '#f44336';
    } else if (type === 'warning') { // Added warning type
        icon = 'fas fa-exclamation-triangle';
        iColor = '#ff9800'; // Orange
        bColor = '#ff9800';
    }
    // else 'info' uses the defaults

    n.style.borderColor = bColor; // Set border color based on type
    // Set content with icon and message
    n.innerHTML = `
        <i class="${icon}" style="color: ${iColor}; font-size: 1.1em; flex-shrink: 0;"></i>
        <span style="line-height: 1.4;">${message}</span>
    `;

    // Clear any existing timer for this notification element
    if (n.timerId) {
        clearTimeout(n.timerId);
    }

    // Animate the notification into view
    requestAnimationFrame(() => { // Use rAF for smoother animation start
        n.style.opacity = '1';
        n.style.transform = 'translateX(-50%) translateY(0)'; // Slide down to position
        n.style.pointerEvents = 'auto'; // Make it clickable/hoverable if needed
    });

    // Set a timer to automatically hide the notification
    n.timerId = setTimeout(() => {
        n.style.opacity = '0';
        n.style.transform = 'translateX(-50%) translateY(-10px)'; // Slide up slightly on fade out
        n.style.pointerEvents = 'none'; // Make non-interactive again
        // Optional: Remove the element after fade out? Generally better to reuse it.
        // setTimeout(() => { if (n.parentNode) n.parentNode.removeChild(n); }, 300);
    }, 3000); // Hide after 3 seconds
}


// Save user activity (attempts server log, falls back to localStorage)
// No changes needed here
function saveUserActivity() {
    const userName = localStorage.getItem('userName');
    const userEmail = localStorage.getItem('userEmail');

    if (!userName || !userEmail) {
        console.warn("saveUserActivity: Skipping log, user name or email missing from localStorage.");
        return;
    }

    const currentDateTime = new Date().toISOString(); // Standard ISO 8601 format
    const currentPath = window.location.pathname; // Get the current page path
    const logEntry = `${userName},${userEmail},${currentDateTime},${currentPath}\n`; // CSV format

    console.log("saveUserActivity: Attempting to log via fetch to /save-login.php...");

    // Use fetch API to send data to the server-side script
    fetch('/save-login.php', {
        method: 'POST',
        headers: {
            // Standard header for form data
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        // Encode the data correctly for the body
        body: 'data=' + encodeURIComponent(logEntry)
    })
    .then(response => {
        if (!response.ok) {
            // Throw an error if the server response is not successful (e.g., 404, 500)
            throw new Error(`Server responded with status ${response.status}`);
        }
        // Return the response text (might be empty or a confirmation message)
        return response.text();
    })
    .then(result => {
        // Log success if server communication worked
        console.log('saveUserActivity: Activity logged via server script:', result);
    })
    .catch(error => {
        // Log error if fetch failed (network issue, server error, etc.)
        console.error('saveUserActivity: Error logging activity via server script:', error);
        // Fallback to saving in localStorage if server logging fails
        saveToLocalStorage(userName, userEmail, currentDateTime, currentPath);
    });
}
function saveToLocalStorage(userName, userEmail, dateTime, filePath) {
    const data = { userName, userEmail, dateTime, filePath };
    try {
        let log = JSON.parse(localStorage.getItem('userActivityLog') || '[]');
        const MAX_LOG_ENTRIES = 100; // Limit the size of the log in localStorage

        // Rotate log if it exceeds the maximum size
        if (log.length >= MAX_LOG_ENTRIES) {
            log = log.slice(log.length - MAX_LOG_ENTRIES + 1); // Keep the latest entries
        }

        log.push(data); // Add the new entry
        localStorage.setItem('userActivityLog', JSON.stringify(log));
        console.log('saveUserActivity (Fallback): Saved user activity to localStorage.');
    } catch (e) {
        console.error("Error saving activity to localStorage:", e);
        // Consider clearing the log if it's corrupted and causing errors
        // localStorage.removeItem('userActivityLog');
    }
}


// Sign out function: clear data and redirect
// No changes needed here
function signOut() {
    console.log("signOut: Initiating sign out process.");
    window.isRedirecting = true; // Set global flag to prevent preloader hiding

    // Attempt to save final activity before clearing data
    saveUserActivity();

    // Show preloader with "Signing out..." message
    const preloader = document.getElementById('preloader');
    const preloaderText = document.getElementById('preloader-text');
    if (preloader && (!preloader.style.display || preloader.style.display === 'none')) {
        // Ensure preloader is visible if it was hidden
        preloader.style.display = ''; // Use default display value
        preloader.style.opacity = '1'; // Make sure it's fully opaque
    }
    if (preloaderText) {
        preloaderText.textContent = 'Signing out...'; // Update text
    }

    // Clear user-specific data from storage
    localStorage.removeItem('loggedIn');
    localStorage.removeItem('userEmail');
    localStorage.removeItem('userName');
    localStorage.removeItem('userPicture');
    // Also clear potentially sensitive or session-specific admin settings
    localStorage.removeItem('adminPanelPosition'); // Reset panel position
    localStorage.removeItem('boxStates'); // Reset box visibility states
    sessionStorage.removeItem('adminNotificationShown'); // Reset admin welcome notification flag

    console.log("signOut: Relevant local and session storage cleared.");

    // Redirect to login page after a short delay to allow UI updates/logging
    setTimeout(() => {
        window.location.href = '/login_new.html'; // Redirect to login page
    }, 150); // Short delay (e.g., 150ms)
}


// Add sign out button dynamically to header profile
// No changes needed here
function addSignOutButton() {
    // Target the profile section specifically within the header, excluding the sidebar
    const headerProfile = document.querySelector('header .profile:not(.side-bar .profile)'); // More specific selector

    if (!headerProfile) {
        console.log("addSignOutButton: Header profile section not found. Skipping button addition.");
        return;
    }

    // Find the intended container for buttons within the header profile
    const buttonContainer = headerProfile.querySelector('.flex-btn');

    if (!buttonContainer) {
        console.error("addSignOutButton: '.flex-btn' container not found within the header profile.");
        // Fallback: Append directly to header profile if flex-btn is missing, but check if button exists first
        if (!headerProfile.querySelector(':scope > .sign-out-btn')) { // Use :scope to check direct children
             console.warn("addSignOutButton: Appending sign-out button directly to header profile as fallback.");
             const btn = document.createElement('button');
             btn.className = 'sign-out-btn'; // Use the class for styling
             btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Sign Out';
             btn.title = "Sign Out";
             btn.addEventListener('click', signOut);
             // Apply some basic inline styles if needed, but rely on CSS primarily
             btn.style.cssText = 'margin-top: 10px; width: auto; padding: 5px 10px;'; // Example minimal style
             headerProfile.appendChild(btn);
        }
        return; // Exit after fallback attempt or if button already exists
    }

    // Check if the sign-out button already exists within the flex-btn container
    if (!buttonContainer.querySelector('.sign-out-btn')) {
        console.log("addSignOutButton: Adding sign-out button to header profile's .flex-btn container.");
        const btn = document.createElement('button');
        btn.className = 'sign-out-btn'; // Use the class for consistent styling
        btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Sign Out'; // Icon + Text
        btn.title = "Sign Out"; // Tooltip
        btn.addEventListener('click', signOut); // Attach the sign out function
        buttonContainer.appendChild(btn); // Add the button to the container
    } else {
        console.log("addSignOutButton: Sign-out button already exists in header profile's .flex-btn.");
    }

    // Defensive check: Remove sign-out button if erroneously added to sidebar profile
    // (Assuming sign-out should *only* be in the header profile's button container)
    const sidebarBtn = document.querySelector('.side-bar .profile .sign-out-btn');
    if (sidebarBtn) {
        console.log("addSignOutButton: Removing potentially duplicate sign-out button from sidebar profile.");
        sidebarBtn.remove();
    }
}


// --- Admin Panel Repositioning Logic ---
// No changes needed here
function updateCollapsedPanelPosition() {
    const adminControlPanel = document.querySelector('.admin-control-panel');
    if (!adminControlPanel) return; // Exit if panel doesn't exist

    // Determine if the panel is currently collapsed
    // Check max-height style, allowing for small floating point variations
    const currentMaxHeight = parseFloat(adminControlPanel.style.maxHeight);
    const isCollapsed = currentMaxHeight <= ADMIN_PANEL_COLLAPSED_HEIGHT + 1;

    if (isCollapsed) {
        console.log("Updating collapsed panel position due to load/resize.");
        // Recalculate the target top position based on current window height
        const targetTop = window.innerHeight - ADMIN_PANEL_COLLAPSED_HEIGHT - ADMIN_PANEL_PADDING;
        adminControlPanel.style.top = `${targetTop}px`;

        // Also ensure left position remains within bounds (important if window width changed significantly)
        const currentLeft = parseFloat(adminControlPanel.style.left);
        const maxLeft = window.innerWidth - adminControlPanel.offsetWidth - ADMIN_PANEL_PADDING;
        // Clamp the left position between the left padding and the calculated maxLeft
        adminControlPanel.style.left = `${Math.max(ADMIN_PANEL_PADDING, Math.min(currentLeft, maxLeft))}px`;
    }
    // If the panel is expanded, its position is handled by the drag logic or expand logic,
    // so we don't need to reposition it here on resize unless specific behavior is desired.
}


// --- Preloader Logic (Waits for window.load) ---
// No changes needed here

// Run initial checks and setup when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOMContentLoaded: Event fired.");
    const preloader = document.getElementById('preloader');
    const preloaderText = document.getElementById('preloader-text');

    if (preloader) {
        console.log("DOMContentLoaded: Preloader found.");
        if(preloaderText) {
            // Only update text if not already in a redirect state
            if (!window.isRedirecting) {
                console.log("DOMContentLoaded: Updating preloader text.");
                preloaderText.textContent = 'Loading Assets...'; // Indicate waiting for assets
            }
        } else {
            console.warn("DOMContentLoaded: Preloader text element (#preloader-text) not found.");
        }
    } else {
        console.warn("DOMContentLoaded: Preloader element (#preloader) not found.");
    }

    console.log("DOMContentLoaded: Running checkLogin.");
    // Run user auth and initial UI setup.
    // IMPORTANT: checkLogin is now async. We don't strictly need to `await` it here
    // because its primary side effects (UI updates, redirects) will happen
    // asynchronously anyway. The preloader hiding logic in window.load will
    // wait for everything.
    checkLogin().catch(error => {
        // Catch potential errors during the async checkLogin process
        console.error("Error during initial checkLogin:", error);
        // Maybe redirect to login or show an error message?
        const preloaderText = document.getElementById('preloader-text');
        if (preloaderText) preloaderText.textContent = 'Error during login check.';
        // Consider redirecting after a delay if auth fails critically
        // setTimeout(() => { window.location.href = '/login.html?error=check_failed'; }, 2000);
    });
});

// Wait for all page resources (images, scripts, styles) to load before hiding preloader
window.addEventListener('load', function() {
    console.log("window.load: All page assets loaded.");

    // --- Check if redirection has been initiated (e.g., by checkLogin or signOut) ---
    if (window.isRedirecting) {
        console.log('window.load: Redirecting, skipping preloader hide.');
        return; // Do not hide preloader if redirection is in progress
    }

    // Reposition collapsed admin panel after load ensures layout stability before reveal
    updateCollapsedPanelPosition();

    const preloader = document.getElementById('preloader');
    const preloaderText = document.getElementById('preloader-text');
    const content = document.getElementById('content'); // Assuming main content container has id="content"

    if (preloader) {
        console.log("window.load: Preloader found.");
        if(preloaderText) {
            preloaderText.textContent = 'Finalizing...'; // Brief "finalizing" message
        }

        // Short delay before starting fade-out allows "Finalizing..." text to be seen
        setTimeout(() => {
            preloader.style.opacity = '0';
            preloader.style.pointerEvents = 'none'; // Disable interaction during fade

            // Hide preloader completely after fade-out transition completes
            setTimeout(() => {
                preloader.style.display = 'none';
                console.log("window.load: Preloader hidden.");
            }, 300); // Should match CSS transition duration for opacity

            // Fade in the main content if it exists
            if (content) {
                content.style.opacity = '0'; // Ensure it starts transparent
                content.style.display = 'block'; // Or 'grid', 'flex' etc. - make it take up space
                // Use requestAnimationFrame for smoother transition start
                requestAnimationFrame(() => {
                    content.style.transition = 'opacity 0.5s ease-in'; // Define fade-in transition
                    content.style.opacity = '1'; // Start fade-in
                    console.log("window.load: Content shown.");
                });
            } else {
                console.warn("window.load: Main content element (#content) not found for fade-in.");
            }
        }, 150); // Delay before starting fade out

    } else {
        // If preloader element wasn't found for some reason, ensure content is visible
        console.warn("window.load: Preloader element (#preloader) not found at load event.");
        if (content && (content.style.display === 'none' || content.style.opacity === '0')) {
             content.style.display = 'block'; // Or appropriate display value
             content.style.opacity = '1';
             console.log("window.load: Preloader missing, ensuring content is visible.");
        }
    }
});

// --- Add listener for admin panel repositioning on resize ---
window.addEventListener('resize', updateCollapsedPanelPosition);


// --- Add CSS for Preloader Fade Effects (Ensure this CSS exists) ---
function addPreloaderStyles() {
     if (document.getElementById('preloader-dynamic-styles')) return;
     const style = document.createElement('style');
     style.id = 'preloader-dynamic-styles';
     // Make sure your #preloader CSS has `transition: opacity 0.3s ease-out;`
     // Make sure your #content CSS allows opacity changes and starts hidden if needed
     style.textContent = `
        #preloader {
            /* Ensure transition is defined for smooth fade-out */
            transition: opacity 0.3s ease-out;
            /* Ensure it's visible initially if controlled by JS */
            opacity: 1;
            pointer-events: auto; /* Allow interaction initially if needed */
        }
        #content {
            /* Start hidden/transparent for fade-in effect */
            opacity: 0;
            /* display property might be set to 'none' initially via HTML/CSS,
               JS will change it to 'block' or similar before fading in */
            /* transition property is added dynamically via JS in window.load */
        }
     `;
     /*
     // Uncomment and include basic spinner/text styles if not in your main CSS
     style.textContent += `
        #preloader {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.8); z-index: 1100;
        }
        #preloader .spinner {
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-left-color: #ffffff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
         }
        @keyframes spin { to { transform: rotate(360deg); } }
        #preloader #preloader-text {
             color: #e0e0e0;
             font-size: 1.2em;
             font-family: sans-serif;
        }
     `;
     */
     document.head.appendChild(style);
}
addPreloaderStyles(); // Call this function to inject the styles needed for transitions
