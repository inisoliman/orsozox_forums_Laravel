/**
 * YouTube Lite Embed - High Performance JS
 * Only loads YouTube iframe on click
 */

document.addEventListener('DOMContentLoaded', function () {
    // Event delegation for clicks on YouTube Lite containers
    document.addEventListener('click', function (e) {
        const container = e.target.closest('.yt-lite');

        // If clicking the play button or thumbnail area
        if (container && !e.target.closest('.yt-footer a')) {
            loadYoutubeVideo(container);
        }
    });

    /**
     * Replaces the preview card with the actual YouTube Iframe
     */
    function loadYoutubeVideo(container) {
        const videoId = container.getAttribute('data-id');
        if (!videoId || container.querySelector('iframe')) return;

        // Preconnect to YouTube for faster response
        const link1 = document.createElement('link');
        link1.rel = 'preconnect';
        link1.href = 'https://www.youtube.com';
        document.head.appendChild(link1);

        const link2 = document.createElement('link');
        link2.rel = 'preconnect';
        link2.href = 'https://www.google.com';
        document.head.appendChild(link2);

        // Clear children
        container.innerHTML = '';

        // Create Iframe
        const iframe = document.createElement('iframe');
        iframe.setAttribute('src', `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`);
        iframe.setAttribute('frameborder', '0');
        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
        iframe.setAttribute('allowfullscreen', 'true');
        iframe.setAttribute('loading', 'lazy');

        container.appendChild(iframe);
    }
});
