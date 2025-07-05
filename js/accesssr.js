// This script will be loaded with 'defer', so it will execute after the DOM is parsed
// and before the DOMContentLoaded event fires for other scripts.

try {
    const userEmail = localStorage.getItem('userEmail');
    const authorizedEmails = ['26002ishan@gmail.com', 'anandakw076@gmail.com',
    'ds4803707@gmail.com'];

    if (userEmail && authorizedEmails.includes(userEmail)) {
        const linkMappings = {
            '#chalithaya': '/lessons/SPEED REVISION/', 
'#automobile':'/speed_revision/et/automobile/',
'#tharala':'/speed_revision/et/තරල/',
'#drawing':'/speed_revision/et/drawing/watch-video',
'#kandanka':'/lessons/SPEED REVISION/',
        };

        console.log('User email authorized, attempting to update links.'); // For debugging

        for (const [originalHref, newHref] of Object.entries(linkMappings)) {
            const linkElement = document.querySelector(`a[href="${originalHref}"]`);
            if (linkElement) {
                console.log(`Updating link for ${originalHref} to ${newHref}`); // For debugging
                linkElement.setAttribute('href', newHref);
                linkElement.style.cursor = 'pointer'; // Ensure cursor is appropriate for a clickable link

                // The notification script, which runs on DOMContentLoaded,
                // will now correctly see these links as updated and not '#'.
            } else {
                console.log(`Link with href ${originalHref} not found.`); // For debugging
            }
        }
    } else {
        if (!userEmail) {
            console.log('User email not found in localStorage.'); // For debugging
        } else {
            console.log('User email not authorized for special links.'); // For debugging
        }
    }
} catch (error) {
    console.error("Error in user-specific link modification (userSpecificLinks.js):", error);
}
