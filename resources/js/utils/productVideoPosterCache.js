/** In-memory cache: video URL → JPEG data URL (first frame). */
const posterBySrc = new Map();

export function getVideoPoster(src) {
    return src ? posterBySrc.get(src) || null : null;
}

export function setVideoPoster(src, dataUrl) {
    if (src && dataUrl) {
        posterBySrc.set(src, dataUrl);
    }
}
