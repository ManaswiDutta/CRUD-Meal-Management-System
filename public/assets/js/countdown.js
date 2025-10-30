
// Countdown from 5 to 1, then redirect
document.addEventListener('DOMContentLoaded', function () {
    var redirectTo = "<?= $redirect_page ?>";
    var seconds = 5;
    var el = document.getElementById('redirect-count');
    if (!el) {
        // fallback: create element if it doesn't exist
        el = document.createElement('span');
        el.id = 'redirect-count';
        el.textContent = seconds;
        var p = document.querySelector('.redirect-text');
        if (p) p.appendChild(el);
    } else {
        el.textContent = seconds;
    }

    var interval = setInterval(function () {
        seconds -= 1;
        if (seconds <= 0) {
            clearInterval(interval);
            window.location.href = redirectTo;
        } else {
            el.textContent = seconds;
        }
    }, 1000);
});
