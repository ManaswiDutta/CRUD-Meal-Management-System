let countdownElement = document.getElementById('countdown');
let count = 5;
let redirectUrl = "google.com"

function countdown() {
    if (count > 0 ){
        countdownElement.innerHTML = count;
    } else {
        countdownElement.innerHTML = 'redirecting...'
        clearInterval(intervalId);
        setTimeout(() =>{
            window.location.href = redirectUrl;
        }, 1000};
    }
    count--;

}

let intervalId = setInterval(countdown, 1000)