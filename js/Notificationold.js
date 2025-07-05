// Script to handle navigation and show custom messages for unavailable lessons

document.addEventListener('DOMContentLoaded', function() {
  // Find all links with href="#", "#electrical", "#chalithaya", or "#chalithyaet"
  const unavailableLinks = document.querySelectorAll('a[href="#"], a[href="#electrical"], a[href="#chalithaya"], a[href="#chalithyaet"], a[href="#automobile"]');
  
  // Create a professional notification function
  function showNotification(message, lessonName = null, isNewLesson = false) {
    // Create notification element if it doesn't exist
    let notification = document.getElementById('custom-notification');
    if (!notification) {
      notification = document.createElement('div');
      notification.id = 'custom-notification';
      
      // Create inner container for notification content
      const contentContainer = document.createElement('div');
      contentContainer.className = 'notification-content';
      
      // Create info icon
      const iconElement = document.createElement('span');
      iconElement.className = 'notification-icon';
      iconElement.innerHTML = '<i class="fas fa-info-circle"></i>';
      
      // Create message container
      const messageElement = document.createElement('span');
      messageElement.className = 'notification-message';
      
      // Append elements to notification
      contentContainer.appendChild(iconElement);
      contentContainer.appendChild(messageElement);
      notification.appendChild(contentContainer);
      
      // Apply styles programmatically for better maintainability
      notification.style.position = 'fixed';
      notification.style.top = '20px';
      notification.style.left = '50%';
      notification.style.transform = 'translateX(-50%)';
      notification.style.padding = '12px 16px';
      notification.style.backgroundColor = 'white';
      notification.style.color = '#333';
      notification.style.borderRadius = '6px';
      notification.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(0, 0, 0, 0.05)';
      notification.style.zIndex = '9999';
      notification.style.fontSize = '14px';
      notification.style.fontWeight = '400';
      notification.style.opacity = '0';
      notification.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
      notification.style.transform = 'translateX(-50%) translateY(-8px)'; // Initial position for animation
      notification.style.display = 'flex';
      notification.style.alignItems = 'center';
      notification.style.minWidth = '280px';
      notification.style.maxWidth = '400px';
      
      // Style for the content container
      contentContainer.style.display = 'flex';
      contentContainer.style.alignItems = 'center';
      contentContainer.style.width = '100%';
      
      // Style for the icon
      iconElement.style.marginRight = '10px';
      iconElement.style.fontSize = '16px';
      
      // Style for the message
      messageElement.style.flex = '1';
      
      // Add to DOM
      document.body.appendChild(notification);
    }
    
    // Change icon for new lesson notification
    const iconElement = notification.querySelector('.notification-icon i');
    if (isNewLesson) {
      iconElement.className = 'fas fa-star'; // Ensure Font Awesome class is correct
      iconElement.style.color = '#FFD700'; // Gold color for new lessons
    } else {
      iconElement.className = 'fas fa-info-circle'; // Ensure Font Awesome class is correct
      iconElement.style.color = '#4a6cf7'; // Default blue color
    }
    
    // Set notification message
    const messageElement = notification.querySelector('.notification-message');
    
    if (lessonName) {
      // Create message with red lesson name
      messageElement.innerHTML = message.replace(`"${lessonName}"`, `"<span style="color: #ff3333; font-weight: 500;">${lessonName}</span>"`);
    } else {
      messageElement.textContent = message;
    }
    
    // Show notification with animation
    notification.style.display = 'flex'; // Ensure it's visible before animation
    // Force a reflow before changing opacity and transform for the transition to apply correctly
    void notification.offsetWidth; 
    notification.style.opacity = '1';
    notification.style.transform = 'translateX(-50%) translateY(0)';
    
    // Hide notification after a delay
    setTimeout(() => {
      notification.style.opacity = '0';
      notification.style.transform = 'translateX(-50%) translateY(-8px)';
      // Set display to none after transition finishes to prevent interaction with an invisible element
      setTimeout(() => {
        if(notification.style.opacity === '0') { // Check if it's still meant to be hidden
          notification.style.display = 'none';
        }
      }, 300); // This timeout should match the transition duration
    }, 3400); // Total visible time
  }
  
  // Add click event listeners to all unavailable links
  unavailableLinks.forEach(link => {
    link.addEventListener('click', function(event) {
      event.preventDefault();
      
      // Get the link text to show which lesson is unavailable
      const spanElement = this.querySelector('span');
      let lessonName = spanElement ? spanElement.textContent.trim() : '';
      
      // Remove any non-breaking spaces or special characters from the lesson name
      const emptyPlaceholders = [
        '\u00A0 \u00A0\u00A0 \u00A0 \u00A0\u00A0\u00A0\u00A0\u00A0 \u00A0\u00A0\u00A0',
        '\u00A0 \u00A0\u00A0 \u00A0 \u00A0\u00A0 \u00A0\u00A0\u00A0\u00A0\u00A0 \u00A0\u00A0\u00A0\u00A0 \u00A0\u00A0\u00A0\u00A0\u00A0 \u00A0\u00A0',
        '\u00A0 \u00A0\u00A0 \u00A0 \u00A0\u00A0\u00A0\u00A0 \u00A0\u00A0\u00A0\u00A0\u00A0 \u00A0\u00A0',
        ''
      ];
      
      if (emptyPlaceholders.includes(lessonName)) {
        // If it's just a placeholder or non-breaking spaces, use a generic message
        showNotification('Itz empty. 🥵');
      } else if (!lessonName && this.textContent.trim()) {
        // Fallback if span is not found but link has text
        lessonName = this.textContent.trim();
        showNotification(`Request declined :- "${lessonName}". To get access, contact the website owner`, lessonName);
      } else if (lessonName) {
        // Show which specific lesson is not available yet with red lessonName
        showNotification(`Request declined :- "${lessonName}". To get access, contact the website owner`, lessonName);
      } else {
        // Ultimate fallback if no name can be found
        showNotification('This content is currently unavailable. To get access, contact the website owner');
      }
    });
  });
  
  // Optional: Add hover effect to indicate these links are different
  unavailableLinks.forEach(link => {
    link.style.cursor = 'help';
    
    // Optional: Add visual cue that these are not yet active
    const iconElement = link.querySelector('i');
    const spanElement = link.querySelector('span');
    // Check if spanElement exists and its textContent does not include a non-breaking space (simple check for placeholder)
    if (iconElement && spanElement && !spanElement.textContent.includes('\u00A0')) {
      // For lessons with actual names (not placeholders)
      iconElement.style.opacity = '0.7';
    }
  });
  
  // Show "New lessons added" notification on first page load
  const hasSeenWelcome = sessionStorage.getItem('hasSeenWelcome');
  if (!hasSeenWelcome) {
    // Get the latest added lessons (you can modify this to show actual new lessons)
    const newLessons = [
      // Example: { name: "ව්‍යවසායකත්වය", path: "#v" }, 
      // For this example, we'll use the hardcoded message from your script
      // Make sure the path "#v" exists if you want to highlight it.
      // If "/lessons/jeiwan/playlist" is a new lesson, ensure that path is correct.
    ];
    
    // Show notification for new lessons - using your specific message
    showNotification(`New lessons added ව්‍යවසායකත්වය . Teachers > Amila D saSamarasinghe > ව්‍යවසායකත්වය`, null, true);
    
    // Set flag in session storage so it only shows once per session
    sessionStorage.setItem('hasSeenWelcome', 'true');
    
    // Highlight the new lessons with a subtle glow effect
    // You need to define which lessons are considered "new" for highlighting
    // For example, if "ව්‍යවසායකත්වය" link has href="#v"
    const lessonLinkToHighlight = document.querySelector('a[href="#v"]'); 
    if (lessonLinkToHighlight) {
        lessonLinkToHighlight.style.transition = 'all 0.5s ease';
        // Check if the link isn't already marked as unavailable by the above logic
        if (!unavailableLinks || !Array.from(unavailableLinks).includes(lessonLinkToHighlight)) {
            lessonLinkToHighlight.style.boxShadow = '0 0 8px rgba(255, 215, 0, 0.7)';
            
            // Remove the highlight effect after 15 seconds
            setTimeout(() => {
              lessonLinkToHighlight.style.boxShadow = 'none';
            }, 15000);
        }
    }
  }
});
