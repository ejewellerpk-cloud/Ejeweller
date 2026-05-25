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

        let settled = false;
        const finish = (fn) => {
            if (settled) {
                return;
            }
            settled = true;
            video.pause();
            video.removeAttribute('src');
            video.load();
            fn();
        };

        const drawFrame = () => {
            try {
                const w = video.videoWidth;
                const h = video.videoHeight;
                if (!w || !h) {
                    finish(() => reject(new Error('No video dimensions')));
                    return;
                }
                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, w, h);
                const dataUrl = canvas.toDataURL('image/jpeg', 0.82);
                finish(() => resolve(dataUrl));
            } catch (err) {
                finish(() => reject(err));
            }
        };

        video.addEventListener('loadeddata', () => {
            const duration = video.duration;
            if (duration && isFinite(duration) && duration > seekSeconds) {
                video.currentTime = seekSeconds;
            } else {
                drawFrame();
            }
        }, { once: true });

        video.addEventListener('seeked', drawFrame, { once: true });

        video.addEventListener('error', () => {
            finish(() => reject(new Error('Video load failed')));
        }, { once: true });

        video.src = videoUrl;
        video.load();
    });
}
