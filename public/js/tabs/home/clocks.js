function updateClocks() {
    const timeZones = {
        'pk': 'Asia/Karachi',
        'hou': 'America/Chicago',
        'ny': 'America/New_York',
        'phx': 'America/Phoenix'
    };

    if (!window.clockRotations) {
        window.clockRotations = {};
    }

    for (const [id, zone] of Object.entries(timeZones)) {
        try {
            const now = new Date();
            const formatter = new Intl.DateTimeFormat('en-US', {
                timeZone: zone,
                hour: 'numeric',
                minute: 'numeric',
                second: 'numeric',
                hour12: false
            });
            const parts = formatter.formatToParts(now);
            const h = parseInt(parts.find(p => p.type === 'hour').value, 10);
            const m = parseInt(parts.find(p => p.type === 'minute').value, 10);
            const s = parseInt(parts.find(p => p.type === 'second').value, 10);

            const digitalEl = document.getElementById(`${id}-digital`);
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

            const state = window.clockRotations[id];
            if (state.lastSecond !== s) {
                let delta = s - state.lastSecond;
                if (delta < 0) {
                    delta += 60;
                }
                state.secRotation += delta * 6;
                state.lastSecond = s;
            }

            const secDeg = state.secRotation;
            const minDeg = (m / 60) * 360 + (s / 60) * 6;
            const hrDeg = ((h % 12) / 12) * 360 + (m / 60) * 30;

            const hrHand = document.getElementById(`${id}-hour`);
            const minHand = document.getElementById(`${id}-min`);
            const secHand = document.getElementById(`${id}-sec`);

            if (hrHand) hrHand.style.transform = `rotate(${hrDeg}deg)`;
            if (minHand) minHand.style.transform = `rotate(${minDeg}deg)`;
            if (secHand) secHand.style.transform = `rotate(${secDeg}deg)`;

        } catch (e) {
            console.error(`Error updating clock for ${id}:`, e);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateClocks();
    setInterval(updateClocks, 1000);
});
