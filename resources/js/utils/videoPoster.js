export function getYouTubeId(url) {
    if (!url) {
        return null;
    }

    const match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|shorts\/))([\w-]{11})/);
    return match ? match[1] : null;
}

export function getVideoPoster(video, fallbackImage = '') {
    if (!video) {
        return fallbackImage;
    }

    if (video.thumbnail) {
        return video.thumbnail;
    }

    if (Number(video.video_provider) === 5) {
        const videoId = getYouTubeId(video.link);
        if (videoId) {
            return `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
        }
    }

    return fallbackImage;
}

export function shouldUseVideoPosterSlide(video) {
    if (!video) {
        return false;
    }

    return !!video.thumbnail || Number(video.video_provider) === 5;
}
