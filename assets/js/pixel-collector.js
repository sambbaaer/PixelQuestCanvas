(function ($) {
    'use strict';

    let pixelsCollected = 0;
    let sessionPixels = 0;

    function initPixelCollector() {
        if (typeof pqc_collector === 'undefined' || !pqc_collector.pixel_data) {
            return;
        }

        sessionPixels = PQC.Session.getPixelCount();

        // Pixel-Icons generieren und platzieren
        pqc_collector.pixel_data.forEach(function (pixelData, index) {
            setTimeout(function () {
                createPixelIcon(pixelData);
            }, pixelData.delay);
        });

        // Pixel-Counter anzeigen
        showPixelCounter();
    }

    function createPixelIcon(pixelData) {
        if (PQC.Session.isIconsHidden()) {
            // Pixel trotzdem sammeln, auch wenn Icons versteckt sind
            collectPixel();
            return;
        }

        const icon = $('<div>')
            .addClass('pqc-pixel-icon')
            .attr('id', pixelData.id)
            .css({
                position: 'absolute',
                top: pixelData.position.top,
                left: pixelData.position.left,
                width: '16px',
                height: '16px',
                background: `url(${pqc_collector.icon_url}) no-repeat center`,
                backgroundSize: 'contain',
                cursor: 'pointer',
                zIndex: 9998,
                opacity: 0,
                transform: 'scale(0.5)',
                transition: 'all 0.3s ease',
                pointerEvents: 'auto'
            });

        $('#pqc-pixel-container').append(icon);

        // Fade-in Animation
        setTimeout(function () {
            icon.css({
                opacity: 1,
                transform: 'scale(1)'
            });
        }, 100);

        // Pulsing-Animation
        setInterval(function () {
            if (icon.length) {
                icon.animate({ transform: 'scale(1.1)' }, 500)
                    .animate({ transform: 'scale(1)' }, 500);
            }
        }, 2000);

        // Klick-Event (automatisches Sammeln nach 5 Sekunden)
        setTimeout(function () {
            if (icon.length) {
                collectPixelIcon(icon);
            }
        }, 5000);

        // Manuelles Klicken
        icon.on('click', function () {
            collectPixelIcon($(this));
        });
    }

    function collectPixelIcon(icon) {
        // Sammel-Animation
        icon.css({
            transform: 'scale(1.5)',
            opacity: 0.8
        });

        setTimeout(function () {
            icon.animate({
                top: '20px',
                right: '20px',
                transform: 'scale(0)',
                opacity: 0
            }, 500, function () {
                icon.remove();
                collectPixel();
            });
        }, 200);
    }

    function collectPixel() {
        pixelsCollected++;

        // Session-Limit prüfen
        const maxPixels = parseInt(pqc_ajax.settings.max_session_pixels);
        if (sessionPixels >= maxPixels) {
            showLimitReachedMessage();
            return;
        }

        sessionPixels = PQC.Session.addPixels(1);

        // Sammel-Feedback
        showCollectFeedback();

        // Sound abspielen (falls verfügbar)
        if (pqc_collector.collect_sound) {
            playCollectSound();
        }
    }

    function showCollectFeedback() {
        // Counter kurz hervorheben
        $('#pqc-counter-value').css({
            transform: 'scale(1.2)',
            color: '#4CAF50'
        });

        setTimeout(function () {
            $('#pqc-counter-value').css({
                transform: 'scale(1)',
                color: 'white'
            });
        }, 300);

        // Kleine +1 Animation
        const feedback = $('<div>+1</div>')
            .css({
                position: 'fixed',
                top: '60px',
                right: '30px',
                color: '#4CAF50',
                fontSize: '18px',
                fontWeight: 'bold',
                zIndex: 10001,
                opacity: 0
            });

        $('body').append(feedback);
        feedback.animate({
            top: '40px',
            opacity: 1
        }, 200).delay(800).animate({
            top: '20px',
            opacity: 0
        }, 200, function () {
            feedback.remove();
        });
    }

    function showPixelCounter() {
        $('#pqc-pixel-counter').fadeIn(300);
    }

    function showLimitReachedMessage() {
        const message = $('<div>')
            .addClass('pqc-limit-message')
            .text('Session-Limit erreicht! Pixel können auf dem Canvas verwendet werden.')
            .css({
                position: 'fixed',
                top: '100px',
                right: '20px',
                background: '#ff9800',
                color: 'white',
                padding: '15px',
                borderRadius: '5px',
                zIndex: 10001,
                maxWidth: '300px'
            });

        $('body').append(message);
        message.fadeIn(300).delay(4000).fadeOut(300, function () {
            $(this).remove();
        });
    }

    function playCollectSound() {
        try {
            const audio = new Audio(pqc_collector.collect_sound);
            audio.volume = 0.3;
            audio.play().catch(function () {
                // Ignoriere Audio-Fehler (Autoplay-Policy)
            });
        } catch (e) {
            // Ignoriere Audio-Fehler
        }
    }

    // Initialisierung
    $(document).ready(function () {
        initPixelCollector();
    });

})(jQuery);
