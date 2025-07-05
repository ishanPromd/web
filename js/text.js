const text = "Solo Dev";
const typingSpeed = 150;
const pauseTime = 1500;
const textElement = document.getElementById("text");

let isTyping = true;
let charIndex = 0;

function updateText() {
  if (isTyping) {
    charIndex++;
    textElement.textContent = text.slice(0, charIndex);
    
    if (charIndex >= text.length) {
      isTyping = false;
      setTimeout(updateText, pauseTime);
      return;
    }
  } else {
    charIndex--;
    textElement.textContent = text.slice(0, charIndex);
    
    if (charIndex <= 0) {
      isTyping = true;
      setTimeout(updateText, pauseTime);
      return;
    }
  }
  
  setTimeout(updateText, typingSpeed);
}

// Start the animation
setTimeout(updateText, typingSpeed);