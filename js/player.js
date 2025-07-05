document.addEventListener('DOMContentLoaded', () => {
            const player = new Plyr('#player', {
                youtube: {
                    noCookie: false,
                    controls: 0,
                    disablekb: 1
                },
                clickToPlay: true,
                hideControls: false
            });

            // Center click handler
            document.querySelector('.center-click-layer').addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const clickX = e.clientX - rect.left;
                const clickY = e.clientY - rect.top;
                
                if (Math.abs(clickX - centerX) < 1500 && Math.abs(clickY - centerY) < 1500) {
                    player.togglePlay();
                }
            });

            // Double security for title area
            document.querySelector('.title-blocker').addEventListener('click', e => e.stopPropagation());
            document.querySelector('.share-blocker').addEventListener('click', e => e.stopPropagation());
        });