(function ($) {
    'use strict';

    let canvas = null;
    let ctx = null;
    let selectedColor = '#FF0000';
    let canvasData = [];
    let isDrawing = false;

    function initCanvas() {
        canvas = document.getElementById('pqc-canvas');
        if (!canvas) return;

        ctx = canvas.getContext('2d');

        // Canvas-Daten laden
        loadCanvasData();

        // Event-Listener
        setupEventListeners();

        // Erste Farbe auswählen
        $('.pqc-color-btn').first().addClass('active');
        selectedColor = $('.pqc-color-btn').first().data('color');
    }

    function setupEventListeners() {
        // Canvas-Klicks
        $(canvas).on('click', function (e) {
            const rect = canvas.getBoundingClientRect();
            const x = Math.floor(e.clientX - rect.left);
            const y = Math.floor(e.clientY - rect.top);

            paintPixel(x, y);
        });

        // Touch-Support für Mobile
        $(canvas).on('touchstart', function (e) {
            e.preventDefault();
            const touch = e.originalEvent.touches[0];
            const rect = canvas.getBoundingClientRect();
            const x = Math.floor(touch.clientX - rect.left);
            const y = Math.floor(touch.clientY - rect.top);

            paintPixel(x, y);
        });

        // Farbauswahl
        $('.pqc-color-btn').on('click', function () {
            $('.pqc-color-btn').removeClass('active');
            $(this).addClass('active');
            selectedColor = $(this).data('color');
        });

        // Zoom/Pan für Mobile
        if (isMobile()) {
            setupMobileControls();
        }
    }

    function paintPixel(x, y) {
        // Pixel verfügbar?
        if (!PQC.Session.usePixel()) {
            showNoPixelsMessage();
            return;
        }

        // Pixel auf Canvas zeichnen
        ctx.fillStyle = selectedColor;
        ctx.fillRect(x, y, 1, 1);

        // In lokalen Daten speichern
        const existingIndex = canvasData.findIndex(p => p.x === x && p.y === y);
        if (existingIndex !== -1) {
            canvasData[existingIndex].color = selectedColor;
        } else {
            canvasData.push({ x: x, y: y, color: selectedColor });
        }

        // An Server senden
        savePixelToServer(x, y, selectedColor);

        // Feedback
        showPaintFeedback(x, y);
    }

    function savePixelToServer(x, y, color) {
        $.ajax({
            url: pqc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pqc_save_pixel',
                nonce: pqc_ajax.nonce,
                x: x,
                y: y,
                color: color,
                session_id: getSessionId()
            },
            success: function (response) {
                if (!response.success) {
                    console.error('Fehler beim Speichern:', response.data);
                }
            },
            error: function () {
                console.error('AJAX-Fehler beim Speichern des Pixels');
            }
        });
    }

    function loadCanvasData() {
        if (typeof pqc_canvas !== 'undefined' && pqc_canvas.canvas_data) {
            canvasData = pqc_canvas.canvas_data;
            drawCanvas();
        } else {
            // Daten vom Server laden
            $.ajax({
                url: pqc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'pqc_get_canvas_data'
                },
                success: function (response) {
                    if (response.success) {
                        canvasData = response.data;
                        drawCanvas();
                    }
                }
            });
        }
    }

    function drawCanvas() {
        // Canvas leeren
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Alle Pixel zeichnen
        canvasData.forEach(function (pixel) {
            ctx.fillStyle = pixel.color;
            ctx.fillRect(pixel.x, pixel.y, 1, 1);
        });
    }

    function showPaintFeedback(x, y) {
        // Kurzes Aufblitzen an der gemalten Stelle
        const originalColor = selectedColor;
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(x, y, 1, 1);

        setTimeout(function () {
            ctx.fillStyle = originalColor;
            ctx.fillRect(x, y, 1, 1);
        }, 100);
    }

    function showNoPixelsMessage() {
        const message = $('<div>')
            .text('Keine Pixel verfügbar! Sammle mehr Pixel auf anderen Seiten.')
            .css({
                position: 'fixed',
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%)',
                background: '#f44336',
                color: 'white',
                padding: '20px',
                borderRadius: '5px',
                zIndex: 10001,
                textAlign: 'center'
            });

        $('body').append(message);
        message.fadeIn(300).delay(3000).fadeOut(300, function () {
            $(this).remove();
        });
    }

    function setupMobileControls() {
        // Pinch-to-zoom und Pan-Funktionalität
        let scale = 1;
        let originX = 0;
        let originY = 0;

        $(canvas).on('touchmove', function (e) {
            e.preventDefault();
        });

        // Einfache Zoom-Buttons für Mobile
        const controls = $('<div class="pqc-mobile-controls">')
            .css({
                position: 'absolute',
                bottom: '10px',
                right: '10px',
                display: 'flex',
                gap: '10px'
            });

        const zoomIn = $('<button>+</button>').css({
            width: '40px',
            height: '40px',
            fontSize: '20px'
        });

        const zoomOut = $('<button>-</button>').css({
            width: '40px',
            height: '40px',
            fontSize: '20px'
        });

        controls.append(zoomIn, zoomOut);
        $('.pqc-canvas-wrapper').css('position', 'relative').append(controls);

        zoomIn.on('click', function () {
            scale = Math.min(scale * 1.2, 5);
            $(canvas).css('transform', `scale(${scale})`);
        });

        zoomOut.on('click', function () {
            scale = Math.max(scale / 1.2, 0.5);
            $(canvas).css('transform', `scale(${scale})`);
        });
    }

    function isMobile() {
        return window.innerWidth <= 768;
    }

    function getSessionId() {
        let sessionId = localStorage.getItem('pqc_session_id');
        if (!sessionId) {
            sessionId = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('pqc_session_id', sessionId);
        }
        return sessionId;
    }

    // Initialisierung
    $(document).ready(function () {
        initCanvas();
    });

})(jQuery);