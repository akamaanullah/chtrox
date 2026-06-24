(function () {
    var clockIntervalId = null;

    function updateClocks() {
        var timeZones = {
            pk: 'Asia/Karachi',
            hou: 'America/Chicago',
            ny: 'America/New_York',
            phx: 'America/Phoenix'
        };

        if (!window.clockRotations) {
            window.clockRotations = {};
        }

        Object.keys(timeZones).forEach(function (id) {
            var zone = timeZones[id];
            try {
                var now = new Date();
                var formatter = new Intl.DateTimeFormat('en-US', {
                    timeZone: zone,
                    hour: 'numeric',
                    minute: 'numeric',
                    second: 'numeric',
                    hour12: false
                });
                var parts = formatter.formatToParts(now);
                var h = parseInt(parts.find(function (p) { return p.type === 'hour'; }).value, 10);
                var m = parseInt(parts.find(function (p) { return p.type === 'minute'; }).value, 10);
                var s = parseInt(parts.find(function (p) { return p.type === 'second'; }).value, 10);

                var digitalEl = document.getElementById(id + '-digital');
                if (digitalEl) {
                    digitalEl.textContent = now.toLocaleString('en-US', {
                        timeZone: zone,
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    });
                }

                if (!window.clockRotations[id]) {
                    window.clockRotations[id] = {
                        secRotation: s * 6,
                        lastSecond: s
                    };
                }

                var state = window.clockRotations[id];
                if (state.lastSecond !== s) {
                    var delta = s - state.lastSecond;
                    if (delta < 0) {
                        delta += 60;
                    }
                    state.secRotation += delta * 6;
                    state.lastSecond = s;
                }

                var secDeg = state.secRotation;
                var minDeg = (m / 60) * 360 + (s / 60) * 6;
                var hrDeg = ((h % 12) / 12) * 360 + (m / 60) * 30;

                var hrHand = document.getElementById(id + '-hour');
                var minHand = document.getElementById(id + '-min');
                var secHand = document.getElementById(id + '-sec');

                if (hrHand) hrHand.style.transform = 'rotate(' + hrDeg + 'deg)';
                if (minHand) minHand.style.transform = 'rotate(' + minDeg + 'deg)';
                if (secHand) secHand.style.transform = 'rotate(' + secDeg + 'deg)';
            } catch (e) {
                console.error('Error updating clock for ' + id + ':', e);
            }
        });
    }

    function initClocks() {
        if (!document.getElementById('pk-digital')) return;

        if (clockIntervalId) {
            clearInterval(clockIntervalId);
        }

        updateClocks();
        clockIntervalId = setInterval(updateClocks, 1000);
    }

    document.addEventListener('chatrox:page_load', initClocks);
})();
