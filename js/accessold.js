// This script will be loaded with 'defer', so it will execute after the DOM is parsed
// and before the DOMContentLoaded event fires for other scripts.

try {
    const userEmail = localStorage.getItem('userEmail');
    const authorizedEmails = ['26002ishan@gmail.com', 'ishanstc123@gmail.com'];

    if (userEmail && authorizedEmails.includes(userEmail)) {
        const linkMappings = {
            '#chalithaya': '/lessons/SPEED REVISION/', // Using lowercase and no spaces for URLs is common practice
            '#chalithyaet': '#',
            '#electrical': '#',
'#automobile':'/speed_revision/et/automobilesr/'
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
