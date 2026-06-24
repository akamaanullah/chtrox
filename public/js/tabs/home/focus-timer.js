(function () {
    var sessionAbort = null;
    var STORAGE_SECONDS = 'chatrox_focus_seconds';
    var STORAGE_STARTED = 'chatrox_focus_started_at';

    function initFocusTimer() {
        if (sessionAbort) {
            sessionAbort.abort();
        }
        sessionAbort = new AbortController();
        var signal = sessionAbort.signal;

        var display = document.getElementById('focusTimerValue');
        var startBtn = document.getElementById('focusTimerStart');
        var resetBtn = document.getElementById('focusTimerReset');

        if (!display || !startBtn || !resetBtn) return;

        var seconds = parseInt(localStorage.getItem(STORAGE_SECONDS) || '0', 10) || 0;
        var startedAt = parseInt(localStorage.getItem(STORAGE_STARTED) || '0', 10) || 0;
        var intervalId = null;
        var running = false;

        function formatTime(totalSeconds) {
            var m = Math.floor(totalSeconds / 60);
            var s = totalSeconds % 60;
            return m + ':' + String(s).padStart(2, '0');
        }

        function persistState() {
            localStorage.setItem(STORAGE_SECONDS, String(seconds));
            if (running && startedAt) {
                localStorage.setItem(STORAGE_STARTED, String(startedAt));
            } else {
                localStorage.removeItem(STORAGE_STARTED);
            }
        }

        function syncElapsed() {
            if (!running || !startedAt) return;
            var elapsed = Math.floor((Date.now() - startedAt) / 1000);
            if (elapsed > 0) {
                seconds += elapsed;
                startedAt = Date.now();
                persistState();
            }
        }

        function updateDisplay() {
            syncElapsed();
            display.textContent = formatTime(seconds);
        }

        function pause() {
            syncElapsed();
            running = false;
            clearInterval(intervalId);
            intervalId = null;
            startedAt = 0;
            startBtn.textContent = 'START';
            persistState();
        }

        function start() {
            if (running) return;
            running = true;
            startedAt = Date.now();
            startBtn.textContent = 'PAUSE';
            intervalId = setInterval(function () {
                updateDisplay();
            }, 1000);
            persistState();
        }

        function reset() {
            pause();
            seconds = 0;
            localStorage.removeItem(STORAGE_SECONDS);
            localStorage.removeItem(STORAGE_STARTED);
            updateDisplay();
        }

        if (startedAt > 0) {
            var bootElapsed = Math.floor((Date.now() - startedAt) / 1000);
            if (bootElapsed > 0) {
                seconds += bootElapsed;
            }
            start();
        }

        startBtn.addEventListener('click', function () {
            if (running) pause();
            else start();
        }, { signal: signal });

        resetBtn.addEventListener('click', reset, { signal: signal });

        signal.addEventListener('abort', function () {
            syncElapsed();
            pause();
        });

        updateDisplay();
    }

    document.addEventListener('chatrox:page_load', initFocusTimer);
})();
