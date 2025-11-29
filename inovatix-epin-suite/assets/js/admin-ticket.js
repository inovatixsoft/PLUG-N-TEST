(function ($) {
    'use strict';

    if (typeof IESTicketMonitor === 'undefined') {
        return;
    }

    const settings = IESTicketMonitor;
    const audio = Audio('/assets/audio/notify.mp3');
    const storageKey = 'iesTicketLastSeen';
    const initialId = parseInt(settings.latestId, 10) || 0;
    let lastSeen = parseInt(window.localStorage.getItem(storageKey), 10);

    if (!lastSeen || lastSeen < initialId) {
        lastSeen = initialId;
        window.localStorage.setItem(storageKey, lastSeen);
    }

    function checkLatest() {
        $.post(
            settings.ajaxUrl,
            {
                action: 'ies_ticket_latest',
                _ajax_nonce: settings.nonce,
            }
        )
            .done(function (response) {
                if (!response || !response.success || !response.data) {
                    return;
                }

                const latest = parseInt(response.data.latest_id, 10) || 0;

                if (latest > lastSeen) {
                    lastSeen = latest;
                    window.localStorage.setItem(storageKey, lastSeen);

                    try {
                        audio.currentTime = 0;
                        audio.play();
                    } catch (err) {
                        // Ignore playback errors (e.g., browser autoplay policy)
                    }
                }
            });
    }

    const interval = parseInt(settings.interval, 10) || 30000;
    setInterval(checkLatest, interval);
})(jQuery);