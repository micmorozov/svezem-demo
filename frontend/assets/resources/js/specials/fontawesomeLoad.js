document.addEventListener('DOMContentLoaded', function () {
    let link = document.createElement('link');
    link.href = 'https://use.fontawesome.com/releases/v5.8.2/css/all.css';
    link.type = 'text/css';
    link.rel = 'stylesheet';

    console.log(link);

    document.head.appendChild(link);
});