/**
 * Capture the first visible frame of a self-hosted video as a JPEG data URL (poster/thumbnail).
 */

const EMBED_VIDEO_PROVIDERS = [5, 10, 15];

export function isEmbedVideoProvider(provider) {
    return EMBED_VIDEO_PROVIDERS.includes(Number(provider));
}

export function isSelfHostedVideo(video) {
    return video?.link && !isEmbedVideoProvider(video.video_provider);
}

export function captureVideoThumbnail(videoUrl, seekSeconds = 0.12) {
    return captureVideoFrames(videoUrl, 1, [seekSeconds]).then((frames) => {
        if (!frames.length) {
            throw new Error('No frames captured');
        }
        return frames[0].dataUrl;
    });
}

function seekVideoTo(video, time) {
    return new Promise((resolve, reject) => {
        const onSeeked = () => {
            video.removeEventListener('seeked', onSeeked);
            video.removeEventListener('error', onError);
            resolve();
        };
        const onError = () => {
            video.removeEventListener('seeked', onSeeked);
            video.removeEventListener('error', onError);
            reject(new Error('Video seek failed'));
        };

        video.addEventListener('seeked', onSeeked, { once: true });
        video.addEventListener('error', onError, { once: true });
        video.currentTime = time;
    });
}

function drawVideoFrame(video) {
    const w = video.videoWidth;
    const h = video.videoHeight;
    if (!w || !h) {
        throw new Error('No video dimensions');
    }

    const canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, w, h);

    return canvas.toDataURL('image/jpeg', 0.82);
}

function loadVideoElement(videoUrl) {
    return new Promise((resolve, reject) => {
        if (!videoUrl || typeof document === 'undefined') {
            reject(new Error('Invalid video URL'));
            return;
        }

        const video = document.createElement('video');
        video.muted = true;
        video.playsInline = true;
        video.preload = 'auto';
        video.setAttribute('playsinline', '');
        video.setAttribute('webkit-playsinline', '');

        const cleanup = () => {
            video.pause();
            video.removeAttribute('src');
            video.load();
        };

        video.addEventListener('loadeddata', () => resolve(video), { once: true });
        video.addEventListener('error', () => {
            cleanup();
            reject(new Error('Video load failed'));
        }, { once: true });

        video.src = videoUrl;
        video.load();
    });
}

/**
 * Capture multiple frames from a video at evenly spaced timestamps.
 */
export function captureVideoFrames(videoUrl, frameCount = 8, customTimes = null) {
    return new Promise(async (resolve, reject) => {
        let video;

        try {
            video = await loadVideoElement(videoUrl);
            const duration = video.duration;
            const count = Math.max(1, Math.min(frameCount, 12));
            const times = customTimes && customTimes.length
                ? customTimes
                : Array.from({ length: count }, (_, index) => {
                    if (!duration || !isFinite(duration) || duration <= 0) {
                        return Math.max(0.05, index * 0.25);
                    }
                    return Math.max(0.05, (duration * (index + 1)) / (count + 1));
                });

            const frames = [];
            for (const time of times) {
                await seekVideoTo(video, time);
                frames.push({
                    time,
                    dataUrl: drawVideoFrame(video),
                });
            }

            video.pause();
            video.removeAttribute('src');
            video.load();
            resolve(frames);
        } catch (err) {
            if (video) {
                video.pause();
                video.removeAttribute('src');
                video.load();
            }
            reject(err);
        }
    });
}
