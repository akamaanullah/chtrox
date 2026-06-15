function updateClocks() {
    const timeZones = {
        'pk': 'Asia/Karachi',
        'hou': 'America/Chicago',
        'ny': 'America/New_York',
        'phx': 'America/Phoenix'
    };

    for (const [id, zone] of Object.entries(timeZones)) {
        try {
            const now = new Date();
            // Get parts for analog calculation and digital display
            const formatter = new Intl.DateTimeFormat('en-US', {
                timeZone: zone,
                hour: 'numeric',
                minute: 'numeric',
                second: 'numeric',
                hour12: false
            });
            const parts = formatter.formatToParts(now);
            const h = parseInt(parts.find(p => p.type === 'hour').value);
            const m = parseInt(parts.find(p => p.type === 'minute').value);
            const s = parseInt(parts.find(p => p.type === 'second').value);

            // Digital Update (12h format)
            const digitalEl = document.getElementById(`${id}-digital`);
            if (digitalEl) {
                const digitalText = now.toLocaleString('en-US', {
                    timeZone: zone,
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
                digitalEl.textContent = digitalText;
            }

            // Analog Update
            const secDeg = (s / 60) * 360;
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

// Initial call
document.addEventListener('DOMContentLoaded', () => {
    updateClocks();
    setInterval(updateClocks, 1000);
});
