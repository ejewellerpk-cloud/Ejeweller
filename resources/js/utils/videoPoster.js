import { isEmbedVideoProvider } from './videoThumbnail';

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

export function isEmbedVideo(video) {
    return Boolean(video?.link && isEmbedVideoProvider(video.video_provider));
}

export function formatProductCardVideoLink(video) {
    if (!video?.link) {
        return '';
    }

    let link = video.link;
    const provider = Number(video.video_provider);

    if (provider === 5) {
        const ytId = getYouTubeId(link);
        if (ytId) {
            if (!link.includes('/embed/')) {
                link = `https://www.youtube.com/embed/${ytId}`;
            }
            return `${link}?autoplay=1&mute=1&loop=1&playlist=${ytId}&controls=0&showinfo=0&modestbranding=1&enablejsapi=1&playsinline=1`;
        }
    }

    if (provider === 10) {
        return `${link}${link.includes('?') ? '&' : '?'}autoplay=1&loop=1&muted=1&background=1&controls=0&playsinline=1`;
    }

    if (provider === 15) {
        return `${link}${link.includes('?') ? '&' : '?'}autoplay=1&mute=1&loop=1&controls=0&playsinline=1`;
    }

    return link;
}
