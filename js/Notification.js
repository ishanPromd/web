document.addEventListener('DOMContentLoaded', function() {

    // --- Configuration for Notifications ---
    const NOTIFICATION_CONFIG = {
        displayDuration: 6000,
        transitionDuration: 300, // Duration for animations (adjusted for smoothness, was 200ms)
        animationEasing: 'cubic-bezier(0.23, 1, 0.32, 1)',

        outTransformY: 30, 
        outScale: 0.1,
        
        inInitialTransformY: -50, 
        inInitialScale: 0.8, 
        inOvershootScale: 1.05,
        finalScale: 1,
        finalTransformY: 0,

        defaultIconClass: 'fas fa-info-circle',
        newLessonIconClass: 'fas fa-star',
        defaultIconColor: '#4a6cf7',
        newLessonIconColor: '#FFD700',

        newLessonHighlightBoxShadow: '0 0 10px rgba(255, 215, 0, 0.8), 0 0 2px rgba(255, 215, 0, 0.4)',
        newLessonHighlightDuration: 10000
    };

    const unavailableLinks = document.querySelectorAll(
        'a[href="#"], a[href="#electrical"], a[href="#chalithaya"], a[href="#chalithyaet"], ' +
        'a[href="#automobile"], a[href="#python"], a[href="#database"], a[href="#php"], ' +
        'a[href="#networking"], a[href="#sql"], a[href="#os"], a[href="#v"], a[href="#ravidu"]'
    );

    let notificationElement = null;
    let hideNotificationTimeoutId = null;
    let isNotificationTransitioning = false;

    function _applyContentAndAnimateIn(message, lessonName, isNewLesson) {
        if (!notificationElement) {
            notificationElement = document.createElement('div');
            notificationElement.id = 'custom-notification';
            notificationElement.setAttribute('aria-live', 'polite');
            notificationElement.style.cssText = `
                position: fixed; top: 20px; left: 50%; padding: 12px 18px;
                background-color: white; color: #333; border-radius: 8px;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.08);
                z-index: 9999; font-size: 13px; font-weight: normal; /* Changed from 500 to normal */ display: flex;
                align-items: center; min-width: 280px; max-width: 400px; cursor: default;
                opacity: 0;
                transform: translateX(-50%) translateY(${NOTIFICATION_CONFIG.inInitialTransformY}px) scale(${NOTIFICATION_CONFIG.inInitialScale});
                transition: opacity ${NOTIFICATION_CONFIG.transitionDuration}ms ${NOTIFICATION_CONFIG.animationEasing}, 
                            transform ${NOTIFICATION_CONFIG.transitionDuration}ms ${NOTIFICATION_CONFIG.animationEasing};
            `;
            const contentContainer = document.createElement('div');
            contentContainer.className = 'notification-content';
            contentContainer.style.cssText = `display: flex; align-items: center; width: 100%;`;
            const iconElement = document.createElement('span');
            iconElement.className = 'notification-icon';
            iconElement.innerHTML = `<i class="${NOTIFICATION_CONFIG.defaultIconClass}"></i>`;
            iconElement.style.cssText = `margin-right: 12px; font-size: 18px; color: ${NOTIFICATION_CONFIG.defaultIconColor};`;
            const messageElementSpan = document.createElement('span'); // Renamed to avoid conflict with global messageElement if any
            messageElementSpan.className = 'notification-message';
            messageElementSpan.style.flex = '1';
            contentContainer.appendChild(iconElement);
            contentContainer.appendChild(messageElementSpan);
            notificationElement.appendChild(contentContainer);
            document.body.appendChild(notificationElement);
        }

        if (hideNotificationTimeoutId) {
            clearTimeout(hideNotificationTimeoutId);
            hideNotificationTimeoutId = null;
        }

        const iconElem = notificationElement.querySelector('.notification-icon i'); // Renamed to avoid conflict
        const messageElem = notificationElement.querySelector('.notification-message'); // Renamed to avoid conflict

        if (isNewLesson) {
            iconElem.className = NOTIFICATION_CONFIG.newLessonIconClass;
            iconElem.style.color = NOTIFICATION_CONFIG.newLessonIconColor;
        } else {
            iconElem.className = NOTIFICATION_CONFIG.defaultIconClass;
            iconElem.style.color = NOTIFICATION_CONFIG.defaultIconColor;
        }

        if (lessonName) {
            // Highlighted lessonName remains font-weight: 600 as per existing logic
            messageElem.innerHTML = message.replace(`"${lessonName}"`, `<span style="color: #ff3333; font-weight: 600;">"${lessonName}"</span>`);
        } else {
            messageElem.textContent = message;
        }

        notificationElement.style.display = 'flex';
        notificationElement.style.transition = 'none';
        notificationElement.style.opacity = '0';
        notificationElement.style.transform = `translateX(-50%) translateY(${NOTIFICATION_CONFIG.inInitialTransformY}px) scale(${NOTIFICATION_CONFIG.inInitialScale})`;
        
        void notificationElement.offsetWidth; 
        
        notificationElement.style.transition = `opacity ${NOTIFICATION_CONFIG.transitionDuration}ms ${NOTIFICATION_CONFIG.animationEasing}, 
                                                transform ${NOTIFICATION_CONFIG.transitionDuration}ms ${NOTIFICATION_CONFIG.animationEasing}`;
        
        isNotificationTransitioning = true;
        notificationElement.style.opacity = '1';
        notificationElement.style.transform = `translateX(-50%) translateY(${NOTIFICATION_CONFIG.finalTransformY}px) scale(${NOTIFICATION_CONFIG.inOvershootScale})`;

        setTimeout(() => {
            if(notificationElement) {
                notificationElement.style.transform = `translateX(-50%) translateY(${NOTIFICATION_CONFIG.finalTransformY}px) scale(${NOTIFICATION_CONFIG.finalScale})`;
            }
        }, NOTIFICATION_CONFIG.transitionDuration * 0.7);

        setTimeout(() => {
            isNotificationTransitioning = false;
        }, NOTIFICATION_CONFIG.transitionDuration);

        hideNotificationTimeoutId = setTimeout(() => {
            if (!notificationElement || isNotificationTransitioning) {
                return; 
            }
            isNotificationTransitioning = true;
            notificationElement.style.opacity = '0';
            notificationElement.style.transform = `translateX(-50%) translateY(${NOTIFICATION_CONFIG.outTransformY}px) scale(${NOTIFICATION_CONFIG.outScale})`;
            
            setTimeout(() => {
                if (notificationElement && notificationElement.style.opacity === '0') {
                    notificationElement.style.display = 'none';
                }
                isNotificationTransitioning = false;
            }, NOTIFICATION_CONFIG.transitionDuration);
        }, NOTIFICATION_CONFIG.displayDuration + NOTIFICATION_CONFIG.transitionDuration);
    }

    function showNotification(message, lessonName = null, isNewLesson = false) {
        if (isNotificationTransitioning) {
            setTimeout(() => {
                showNotification(message, lessonName, isNewLesson);
            }, NOTIFICATION_CONFIG.transitionDuration / 3); 
            return;
        }

        const isVisible = notificationElement && 
                          notificationElement.style.display !== 'none' && 
                          parseFloat(notificationElement.style.opacity) > 0;

        if (hideNotificationTimeoutId) {
            clearTimeout(hideNotificationTimeoutId);
            hideNotificationTimeoutId = null; 
        }

        if (isVisible) {
            isNotificationTransitioning = true; 

            notificationElement.style.opacity = '0';
            notificationElement.style.transform = `translateX(-50%) translateY(${NOTIFICATION_CONFIG.outTransformY}px) scale(${NOTIFICATION_CONFIG.outScale})`;

            setTimeout(() => {
                _applyContentAndAnimateIn(message, lessonName, isNewLesson);
            }, NOTIFICATION_CONFIG.transitionDuration);
        } else {
            _applyContentAndAnimateIn(message, lessonName, isNewLesson);
        }
    }

    unavailableLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const spanElement = this.querySelector('span');
            let lessonNameStr = spanElement ? spanElement.textContent.trim() : ''; // Renamed
            lessonNameStr = lessonNameStr.replace(/[\u00A0\s]+/g, ' ').trim();
            if (!lessonNameStr) {
                showNotification('This content is currently unavailable.');
            } else {
                showNotification(`Request declined: "${lessonNameStr}" || To get access, contact the website owner`, lessonNameStr);
            }
        });
    });

    unavailableLinks.forEach(link => {
        link.style.cursor = 'help';
        const iconElem = link.querySelector('i'); // Renamed
        const spanElem = link.querySelector('span'); // Renamed
        if (iconElem && spanElem && spanElem.textContent.trim()) {
            iconElem.style.opacity = '0.7';
        }
    });

    function handleInitialNotification() {
        const hasSeenWelcome = sessionStorage.getItem('hasSeenWelcome');
        if (!hasSeenWelcome) {
            const userEmail = localStorage.getItem('userEmail');
            let notificationMsg = 'New lesson added.'; // Renamed
            if (userEmail === 'ds4803707@gmail.com') {
                notificationMsg = 'උකහා ගන්නෙ නැතුව ලබන සතියෙ වත් ක්ලාස් යමන් පකො 🖕.';
            } else if (userEmail === 'anandakw076@gmail.com') {
                notificationMsg = 'උකහා ගන්නෙ නැතුව ලබන සතියෙ වත් ක්ලාස් යමන්  .';
            } else if (userEmail === '26002ishan@gmail.com') {
                notificationMsg = 'පාඩම් කරපාන් 😒💔.';
            } else {
                notificationMsg = 'Speed Revision (පදාර්තයේ යාන්ත්‍රික ගුණ Day 2 Added )!';
            }
            showNotification(notificationMsg, null, true);
            sessionStorage.setItem('hasSeenWelcome', 'true');
            const lessonLinkToHighlight = document.querySelector('a[href="/Documents/2023SFT/2023SFT_682d3b241bab84.56356745.pdf"]');
            if (lessonLinkToHighlight) {
                lessonLinkToHighlight.style.transition = 'all 0.5s ease';
                if (!Array.from(unavailableLinks).includes(lessonLinkToHighlight)) {
                    lessonLinkToHighlight.style.boxShadow = NOTIFICATION_CONFIG.newLessonHighlightBoxShadow;
                    setTimeout(() => {
                        lessonLinkToHighlight.style.boxShadow = 'none';
                    }, NOTIFICATION_CONFIG.newLessonHighlightDuration);
                }
            }
        }
    }

    handleInitialNotification();
});
