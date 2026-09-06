document.addEventListener('DOMContentLoaded', function () {
    const heroScreen = document.getElementById('heroScreen');
    const helpScreen = document.getElementById('helpScreen');

    if (!heroScreen || !helpScreen) {
        return;
    }

    let userInteracted = false;
    ['click', 'touchstart', 'scroll', 'keydown'].forEach(eventName => {
        document.addEventListener(eventName, function () {
            userInteracted = true;
        }, { once: true });
    });

    setTimeout(function () {
        if (userInteracted) {
            return;
        }

        heroScreen.classList.remove('active');
        heroScreen.classList.add('slide-away');
        helpScreen.classList.add('active');
    }, 4000);
});
