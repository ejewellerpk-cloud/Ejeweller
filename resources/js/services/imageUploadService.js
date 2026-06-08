import axios from 'axios';

export async function assetToFile(asset) {
    const response = await fetch(asset.url);
    const blob = await response.blob();
    const filename = asset.filename || asset.originalName || 'image.jpg';
    return new File([blob], filename, { type: blob.type || 'image/jpeg' });
}

export async function importImageFromUrl(url, folder = 'uploads') {
    const response = await axios.post('admin/media-library/from-url', { url, folder });
    return response.data;
}
