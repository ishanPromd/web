<?php
echo <<<HTML_OUTPUT
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Dashboard</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">

   <link rel="stylesheet" href="/css/style.css?v=1.2.4">
     <link rel="stylesheet" href="/css/style.css?t=202505132130001">
   <link rel="stylesheet" href="css/bot.css">
   <link rel="stylesheet" href="css/timer.css">
   <link rel="stylesheet" href="css/text.css">
   <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
   <style>
       /* Styles for player blockers, notification, etc. (kept as provided) */
       .container { position: relative; overflow: hidden; border-radius: 10px; }
       .player-wrapper { position: relative; }
       .title-blocker { position: absolute; top: 0; left: 0; width: 30%; height: 15%; z-index: 2; pointer-events: auto; cursor: default; }
       .share-blocker { position: absolute; top: 0; right: 0; width: 15%; height: 15%; z-index: 2; pointer-events: auto; cursor: default; }
       .center-click-layer { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; cursor: pointer; }
       iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; z-index: 0; }
       .plyr__controls { z-index: 2 !important; pointer-events: all !important; }
       a[href="#"] { position: relative; transition: all 0.25s ease; }
       a[href="#"]:not(:has(span:empty)) i { opacity: 0.7; }
       a[href="#"]:hover { background-color: rgba(0, 0, 0, 0.03); }
       #custom-notification { display: flex; align-items: center; opacity: 0; transform: translateY(-8px); transition: opacity 0.3s ease, transform 0.3s ease; pointer-events: none; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; max-width: 90vw; }
       .notification-content { display: flex; align-items: center; width: 100%; }
       .notification-icon { margin-right: 10px; color: #4a6cf7; display: flex; align-items: center; justify-content: center; }
       .notification-message { flex: 1; line-height: 1.4; }
       @media (max-width: 768px) { #custom-notification { max-width: 85vw; padding: 12px 14px; font-size: 13px; } }
   </style>
   <link rel="icon" type="image/x-icon" href="https://i.imghippo.com/files/ao9417UdU.png">

</head>
<body>
    <main class="content-blur"> <header class="header">
            <section class="flex">
                <div class="icons"> <div id="menu-btn" class="fas fa-bars"></div> </div>
                <form action="search.html" method="post" class="search-form">
                    <input type="text" name="search_box" required placeholder="search Lessons..." maxlength="100">
                    <button type="submit" class="fas fa-search"></button>
                </form>
                <div class="icons">
                    <div id="search-btn" class="fas fa-search"></div>
                    <div id="user-btn1" class="fa-regular fa-clock" ></div> <div id="toggle-btn" class="fas fa-sun"></div>
                    <div id="user-btn" class="fas fa-user"></div>
                </div>
                <div class="profile">
                    <img class="image" alt=""> <h3 class="name"></h3> <div class="flex-btn"> </div>
                </div>
            </section>
        </header>

        <div class="side-bar">
            <div id="close-btn"> <i class="fas fa-times"></i> </div>
            <div class="profile">
                <img class="image"> <br> <h3 class="name"></h3> <p class="role">student</p> </div>
            <nav class="navbar">
                <a href="/home.html"><i class="fas fa-home"></i><span>Dashboard</span></a>
                <a href="/teachers/teachers.html"><i class="fas fa-chalkboard-user"></i><span>My Lessons</span></a>
                <a href="/progress.html"><i class="fas fa-question"></i><span> My Progress</span></a>
                <a href="/weekplan.html"><i class="fa-solid fa-calendar"></i><span> Week Plan</span></a>
                <a href="/paper/sft/playlist.html"> <i class="fa-solid fa-address-book"></i><span> Papers</span></a>
            </nav>
            <div class="typing-container">
                <div class="animation-container"> <div id="text"></div> <span class="cursor"></span> </div>
            </div>
        </div>

        <section class="home-grid">
            <h1 class="heading">Dashboard</h1> <div class="box-container">
                <div class="box"> <h3 class="title">SFT</h3>
                    <div class="flex">
                        <a href="/lessons/jeiwanu/playlist"><i class="fa-solid fa-atom"></i><span>ජෛවාණු </span></a>
                        <a href="/lessons/kandanka-jamithiya/playlist"><i class="fa-solid fa-chart-pie"></i><span>ඛණ්ඩාංක ජ්‍යාමිතිය </span></a>
                        <a href="/lessons/thapaya/playlist"><i class="fa-solid fa-fire-flame-curved"></i></i><span>තාපය</span></a>
                        <a href="/lessons/thapa-rasayanaya/playlist"><i class="fa-solid fa-fire"></i><span>තාප රසායනය </span></a>
                        <a href="/lessons/padarthaya/playlist"><i class="fa-solid fa-glass-water"></i><span>පදාර්ථයේ යාන්ත්‍රික ගුණ</span></a>
                        <a href="/lessons/Calithaya/playlist"><i class="fa-solid fa-person-running"></i><span>චලිතය</span></a>
                        <a href="/lessons/Mulika_ganithaya/playlist"><i class="fa-solid fa-calculator"></i><span>මූලික ගණිතය</span></a>
                        <a href="/lessons/balaya/playlist"><i class="fa-solid fa-bolt-lightning"></i><span>බලය </span></a>
                        <a href="/lessons/Trikonamitiya/playlist"><i class="fa-solid fa-triangle-exclamation"></i><span>ත්‍රිකෝණමිතිය</span></a>
                        <a href="/lessons/seyila/playlist"><i class="fa-solid fa-egg"></i><span>සෛලීය</span></a>
                        <a href="/lessons/minum/playlist"><i class="fa-solid fa-pen-ruler"></i><span>මිනුම් උපකරණ</span></a>
                        <a href="#"><span><i class="fa-brands fa-steam-symbol"></i> යාන්ත්‍රික ශක්තිය</span></a>
                        <a href="#"><span><i class="fa-solid fa-plug-circle-bolt"></i> විද්‍යුතය</span></a>
                        <a href="#"><span>  &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;</span></a>
                    </div>
                </div>

                <div class="box"> <h3 class="title">ICT </h3>
                    <div class="flex">
                         <a href="#python"><i class="fa-brands fa-python"></i><span>Python</span></a>
                         <a href="#sql"><i class="fa-solid fa-database"></i><span>SQL</span></a>
                         <a href="#database"><i class="fa-solid fa-database"></i><span>Database</span></a>
                         <a href="#networking"><span><i class="fa-solid fa-network-wired"></i>  Networking</span></a>
                         <a href="#php"><span> <i class="fa-brands fa-php"></i> PHP</span></a>
                         <a href="#os"><span>  <i class="fa-brands fa-windows"></i>Operating System</span></a>
                         <a href="/ict/ict/Logic_gate/playlist"><span>  <i class="fa-solid fa-microchip"></i>Logic Gates</span></a>
            
                         <a href="#"><span>  <i class="fa-solid fa-guarani-sign"></i> Number System</span></a>
                         <a href="#"><span>  <i class="fa-brands fa-html5"></i> HTML</span></a>
                         <a href="#"><span>  <i class="fa-brands fa-css3-alt"></i> Css</span></a>
                         <a href="#"><span>  &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;</span></a>
                         <a href="#"><span>  &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;</span></a>
                         <a href="#"><span>  &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;</span></a>
                    </div>
                </div>

                <div class="box"> <h3 class="title">ET </h3>
                    <div class="flex">
                         <a href="/lessons/et/chalithaya/playlist"><i class="fa-solid fa-person-running"></i><span>චලිතය</span></a>
                         <a href="/lessons/et/Electrical/playlist"><i class="fa-solid fa-lightbulb"></i><span>Electrical</span></a>
                         <a href="/lessons/et/කසළ අපවහනය/playlist"><i class="fa-solid fa-trash"></i><span>  කසළ අපවහනය</span></a>
                         <a href="/lessons/et/drawing/playlist"><i class="fa-solid fa-compass-drafting"></i><span>Drawing</span></a>
                         <a href="/lessons/et/සම්මත_ඒකක/playlist"><i class="fa-solid fa-scale-balanced"></i><span>සම්මත_ඒකක</span></a>
                         <a href="/lessons/et/civil/"><span> <i class="fa-solid fa-people-roof"></i>Civil</span></a>
                         <a href="/lessons/et/automobile/"><span> <i class="fa-solid fa-car"></i> Automobile</span></a>
                         <a href="/lessons/et/safety/"><span> <i class="fa-solid fa-shield"></i> Safety</span></a>
                         <a href="/lessons/et/tds/index"><span><i class="fa-solid fa-table"></i> TDS ( ඇස්තමේන්තුකරණය )</span></a>
                         <a href="/lessons/et/introduction/watch-video"><span> <i class="fa-solid fa-question"></i> හැදින්වීම</span></a>
                         <a href="#v"><span> <i class="fa-solid fa-business-time"></i> ව්‍යවසායකත්වය</span></a>
                         <a href="#"><span>  &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;</span></a>
                    </div>
                </div>

                <div class="box"> <h3 class="title">ET Speed Revision (&nbsp; <i class="fa-solid fa-user"></i> &nbsp; Limited Access )</h3>
                    <div class="flex">
                         <a href="#chalithyaet"><i class="fa-solid fa-person-running"></i><span>චලිතය</span></a>
                         <a href="#electrical"><i class="fa-solid fa-lightbulb"></i><span>Electrical</span></a>
                         <a href="#drawing"><i class="fa-solid fa-compass-drafting"></i><span>Drawing</span></a>
                         <a href="#automobile"><span> <i class="fa-solid fa-car"></i> Automobile</span></a>
                         
                         <a href="/Payments.html"><span><i class="fa-solid fa-table"></i> TDS ( ඇස්තමේන්තුකරණය )</span></a>
                         <a href="#tharala"><span><i class="fa-solid fa-water"></i>  තරල යන්ත්‍ර</span></a>
                        <a href="#"><span>  </span></a>
                      
                    </div>
                </div>

                <div class="box"> <h3 class="title">SFT Speed Revision (&nbsp; <i class="fa-solid fa-user"></i> &nbsp;Limited Access )</h3>
                    <div class="flex">
                         <a href="#chalithaya"><i class="fa-solid fa-person-running"></i> <span>චලිතය</span></a>
                         <a href="#chalithaya"><span> <i class="fa-solid fa-glass-water"></i> පදාර්ථයේ යාන්ත්‍රක ගුණ</span></a>
                                  <a href="#kandanka"><i class="fa-solid fa-chart-pie"></i><span>ඛණ්ඩාංක ජ්‍යාමිතිය </span></a>
 <a href="#"><span>  &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;</span></a>
                         <a href="#"><span>  &nbsp; &nbsp;&nbsp; &nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;</span></a>
                      
                    </div>
                </div>
 <div class="box"> <h1 class="heading1">DAYS LEFT (A/L)</h1> <div class="timer"> <div class="sub_timer"> <h1 id="day" class="digit">00</h1> <p class="digit_name">Days</p> </div>
                        <div class="sub_timer"> <h1 id="hour" class="digit">00</h1> <p class="digit_name">Hours</p> </div>
                        <div class="sub_timer"> <h1 id="min" class="digit">00</h1> <p class="digit_name">Minutes</p> </div>
                        <div class="sub_timer"> <h1 id="sec" class="digit">00</h1> <p class="digit_name">Seconds</p> </div>
                    </div>
</div>

<div class="box"> <h3 class="title"> A/L papers (SFT)  </h3>
                    <div class="flex">
                         
                         <a href="/Documents/Juneweek/Juneweek_6840a7ec6067f.png"><i class="fa-solid fa-file-pdf"></i><span>2023 A/L</span></a>
                         
                         
                         <a href="/Documents/2022SFT/2022SFT_682d3bad823521.81666427.pdf"><span><i class="fa-solid fa-file-pdf"></i> 2022 A/L </span></a>
                         
                        <a href="/Documents/2021SFT/2021SFT_682d3bf4954356.22973438.pdf"><span><i class="fa-solid fa-file-pdf"></i> 2021 A/L </span></a>
                       
                         <a href="/Documents/2020SFT/2020SFT_682d3c28d1ea38.33803866.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2020 A/L </span></a>
                          <a href="/Documents/2019ET2/2019ET2_682d4398f3ee53.97883166.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2019 A/L </span></a>

<a href="/Documents/2018SFT/2018SFT_682d3f977ed386.81312845.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2018 A/L </span></a>
<a href="/Documents/2017SFT/2017SFT_682d3da0df6551.97235768.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2017 A/L </span></a>
<a href="/Documents/2016SFT/2016SFT_682d3f563a32c0.35312347.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2016 A/L </span></a>

                    </div>
                </div>




<div class="box"> <h3 class="title"> A/L ORIGINAL papers (ICT)  </h3>
                    <div class="flex">
                         
                         <a href="/Documents/Juneweek/Juneweek_6840a7ec6067f.png"><i class="fa-solid fa-file-pdf"></i><span>2023 A/L</span></a>
                         
                         
                         <a href="/Documents/2022ICT/2022ICT_682d345c46c50.pdf"><span><i class="fa-solid fa-file-pdf"></i> 2022 A/L </span></a>
                         
                        <a href="/Documents/2021ICT/2021ICT_682d34abcd736.pdf"><span><i class="fa-solid fa-file-pdf"></i> 2021 A/L </span></a>
                       
                         <a href="/Documents/2020ICT/2020ICT_682d3559816fa.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2020 A/L </span></a>
                          <a href="/Documents/2019AL/2019AL_682d359a44558.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2019 A/L </span></a>

<a href="/Documents/2018AL/2018AL_682d35e561132.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2018 A/L </span></a>
<a href="/Documents/2017AL/2017AL_682d360d06a5d.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2017 A/L </span></a>
<a href="/Documents/2016ICT/2016ICT_682d3633b9be7.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2016 A/L </span></a>

                    </div>
                </div>


<div class="box"> <h3 class="title"> A/L papers (ET)  </h3>
                    <div class="flex">
                         
                         <a href="/Documents/2023ET/2023ET_682d40f5595004.99857569.pdf"><i class="fa-solid fa-file-pdf"></i><span>2023 A/L</span></a>
                         
                         
                         <a href="/Documents/2022ET/2022ET_682d41897bafc2.10702251.pdf"><span><i class="fa-solid fa-file-pdf"></i> 2022 A/L </span></a>
                         
                        <a href="/Documents/2021ET/2021ET_682d41b40d4997.20835797.pdf"><span><i class="fa-solid fa-file-pdf"></i> 2021 A/L </span></a>
                       
                         <a href="/Documents/2020ET/2020ET_682d41e25e8494.54403596.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2020 A/L </span></a>
                          <a href="/Documents/2019ET/2019ET_682d4248416f79.48172271.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2019 A/L </span></a>

<a href="/Documents/2018ET/2018ET_682d4273e7ea80.05297561.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2018 A/L </span></a>
<a href="/Documents/2017ET/2017ET_682d42a502ac70.92096615.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2017 A/L </span></a>
<a href="/Documents/2016ET/2016ET_682d42cd522d54.39854341.pdf"><span> <i class="fa-solid fa-file-pdf"></i> 2016 A/L </span></a>

                    </div>
                </div>
 <div class="box"><div class="box1">
            <h1 class="heading1">Week Plan (June)</h1>
            <img  src="/Documents/Weekplan/Weekplan_6846099aeb0d35.51539116.jpg" width="100%" autoplay="" muted="" loop="" playsinline=""></video>
        </div></div>


               
                </div>

            </div> </section> <br><br> </main>
    <br><br><br><br>
    </div>

    <script src="/js/bot.js"></script>
    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    <script src="/js/player.js"></script>
    <script src="/js/text.js"></script>

    <script src="/js/sw.js"></script>

    <script src="js/script.js?v=1.1.0"></script> 
    <script src="js/script.js?t=202505122130001"></script>

    <script src="/js/profile.js?v=1.2.1"></script> 
    <script src="/js/profile.js?t=202505101331211"></script>

    <script src="/js/Notification.js?v=1.5.3"></script> 
    <script src="/js/Notification.js?t=20250521153200"></script>

<script src="/js/access.js?v=1.1.6"></script> 
    <script src="/js/access.js?t=20250519143100"></script>

<script src="/js/accesssr.js?v=1.1.6"></script> 
    <script src="/js/accesssr.js?t=20250520133100"></script>

     
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Notification script for unavailable links
        const unavailableLinks = document.querySelectorAll('a[href="#"]'); // This will now correctly find only links that are still "#"
        
        function showNotification(message, lessonName = null, isNewLesson = false) { 
            // Your existing notification logic...
            // For example:
            const notification = document.getElementById('custom-notification');
            if (notification) { // Check if notification element exists
                const messageElement = notification.querySelector('.notification-message');
                if (messageElement) {
                     messageElement.textContent = message;
                }
                notification.style.opacity = '1';
                notification.style.transform = 'translateY(0)';
                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateY(-8px)';
                }, 5000);
            } else {
                console.warn("Custom notification element not found. Message:", message); // Fallback if element is missing
            }
            // console.log("Notification:", message, lessonName, isNewLesson); // Original placeholder
        }

        unavailableLinks.forEach(link => {
            // This listener will only be added to links that are still href="#" 
            // after userSpecificLinks.js has run.
            link.addEventListener('click', function(event) {
                event.preventDefault(); 
                const lessonNameSpan = link.querySelector('span');
                const lessonName = lessonNameSpan ? lessonNameSpan.textContent.trim() : 'this lesson';
                showNotification(\`The lesson "\${lessonName}" is currently unavailable. We are working on it. Please check back later!\`);
            });
            link.style.cursor = 'help'; // Apply help cursor only to genuinely unavailable links
        });

        const hasSeenWelcome = sessionStorage.getItem('hasSeenWelcome');
        if (!hasSeenWelcome) {
            // ...welcome notification logic...
            showNotification("Welcome to the Dashboard! Explore your lessons and track your progress.", null, true);
            sessionStorage.setItem('hasSeenWelcome', 'true');
            // console.log("Welcome notification would show here."); // Original placeholder
        }

        // --- USER-SPECIFIC LINK MODIFICATION LOGIC HAS BEEN MOVED TO js/userSpecificLinks.js ---
        // --- Make sure that file is created and linked correctly with 'defer' ---

    });
</script>

</body>
</html>
HTML_OUTPUT;
// You can add more PHP code here if needed, or omit the closing PHP tag if this is the end of the file.
?>
