(function () {
    const display = document.getElementById('focusTimerValue');
    const startBtn = document.getElementById('focusTimerStart');
    const resetBtn = document.getElementById('focusTimerReset');

    if (!display || !startBtn || !resetBtn) return;

    let seconds = 0;
    let intervalId = null;
    let running = false;

    function formatTime(totalSeconds) {
        const m = Math.floor(totalSeconds / 60);
        const s = totalSeconds % 60;
        return m + ':' + String(s).padStart(2, '0');
    }

    function updateDisplay() {
        display.textContent = formatTime(seconds);
    }

    function start() {
        if (running) return;
        running = true;
        startBtn.textContent = 'PAUSE';
        intervalId = setInterval(function () {
            seconds += 1;
            updateDisplay();
        }, 1000);
    }

    function pause() {
        running = false;
        clearInterval(intervalId);
        intervalId = null;
        startBtn.textContent = 'START';
    }

    function reset() {
        pause();
        seconds = 0;
        updateDisplay();
    }

    startBtn.addEventListener('click', function () {
        if (running) pause();
        else start();
    });

    resetBtn.addEventListener('click', reset);

    updateDisplay();
})();
