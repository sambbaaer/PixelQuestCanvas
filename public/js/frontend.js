(function ($) {
    'use strict';

    window.PQC = window.PQC || {};

    // Session-Management
    PQC.Session = {
        getPixelCount: function () {
            return parseInt(localStorage.getItem('pqc_session_pixels') || '0');
        },

        setPixelCount: function (count) {
            localStorage.setItem('pqc_session_pixels', count.toString());
            this.updateDisplays();
        },

        addPixels: function (amount) {
            const current = this.getPixelCount();
            const newCount = Math.min(current + amount, pqc_ajax.settings.max_session_pixels);
            this.setPixelCount(newCount);
            return newCount;
        },

        usePixel: function () {
            const current = this.getPixelCount();
            if (current > 0) {
                this.setPixelCount(current - 1);
                return true;
            }
            return false;
        },

        updateDisplays: function () {
            const count = this.getPixelCount();
            $('.pqc-pixel-count').each(function () {
                const format = $(this).data('format') || '{count}';
                $(this).text(format.replace('{count}', count));
            });
            $('#pqc-available-pixels').text(count);
            $('#pqc-counter-value').text(count);
        },

        isIconsHidden: function () {
            return localStorage.getItem('pqc_hide_icons') === 'true';
        },

        toggleIcons: function () {
            const hidden = this.isIconsHidden();
            localStorage.setItem('pqc_hide_icons', (!hidden).toString());

            if (hidden) {
                $('.pqc-pixel-icon').show();
                $('#pqc-toggle-pixels').text($('#pqc-toggle-pixels').data('text-hide'));
            } else {
                $('.pqc-pixel-icon').hide();
                $('#pqc-toggle-pixels').text($('#pqc-toggle-pixels').data('text-show'));
            }
        }
    };

    // Streak-System
    PQC.Streak = {
        getVisitedPages: function () {
            const pages = localStorage.getItem('pqc_visited_pages');
            return pages ? JSON.parse(pages) : [];
        },

        addVisitedPage: function (pageId) {
            let pages = this.getVisitedPages();
            if (!pages.includes(pageId)) {
                pages.push(pageId);
                localStorage.setItem('pqc_visited_pages', JSON.stringify(pages));
                this.checkStreakBonus(pages.length);
            }
        },

        checkStreakBonus: function (pageCount) {
            let bonus = 0;
            if (pageCount >= 3) bonus += 1;
            if (pageCount >= 5) bonus += 1;
            if (pageCount >= 7) bonus += 1;

            if (bonus > 0) {
                PQC.Session.addPixels(bonus);
                this.showStreakNotification(pageCount, bonus);
            }
        },

        showStreakNotification: function (pages, bonus) {
            const notification = $('<div class="pqc-streak-notification">')
                .html(`<strong>Streak Bonus!</strong><br>${pages} Seiten besucht<br>+${bonus} Pixel erhalten`)
                .css({
                    position: 'fixed',
                    top: '50%',
                    left: '50%',
                    transform: 'translate(-50%, -50%)',
                    background: 'linear-gradient(45deg, #ff6b6b, #ffd93d)',
                    color: 'white',
                    padding: '20px',
                    borderRadius: '10px',
                    boxShadow: '0 4px 20px rgba(0,0,0,0.3)',
                    zIndex: 10001,
                    textAlign: 'center',
                    fontSize: '16px',
                    fontWeight: 'bold'
                });

            $('body').append(notification);
            notification.fadeIn(300).delay(3000).fadeOut(300, function () {
                $(this).remove();
            });
        }
    };

    // Hauptinitialisierung
    $(document).ready(function () {
        // Session initialisieren
        PQC.Session.updateDisplays();

        // Aktuelle Seite zu besuchten Seiten hinzufügen
        if (typeof pqc_ajax !== 'undefined' && pqc_ajax.current_post_id) {
            PQC.Streak.addVisitedPage(pqc_ajax.current_post_id);
        }

        // Toggle-Button für Pixel-Icons
        $(document).on('click', '#pqc-toggle-pixels', function () {
            PQC.Session.toggleIcons();
        });

        // Icons initial verstecken falls gesetzt
        if (PQC.Session.isIconsHidden()) {
            $('.pqc-pixel-icon').hide();
            $('#pqc-toggle-pixels').text($('#pqc-toggle-pixels').data('text-show'));
        }
    });

})(jQuery);