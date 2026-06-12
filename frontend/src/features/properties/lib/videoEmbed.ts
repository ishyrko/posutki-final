export type VideoEmbedPlatform = 'youtube' | 'tiktok';

export interface VideoEmbedInfo {
    platform: VideoEmbedPlatform;
    embedUrl: string;
}

function parseYoutubeVideoId(url: URL): string | null {
    const host = url.hostname.replace(/^www\./, '');

    if (host === 'youtu.be') {
        const id = url.pathname.split('/').filter(Boolean)[0];
        return id ?? null;
    }

    if (host === 'youtube.com' || host === 'm.youtube.com') {
        const fromQuery = url.searchParams.get('v');
        if (fromQuery) {
            return fromQuery;
        }

        const pathParts = url.pathname.split('/').filter(Boolean);
        const embedIndex = pathParts.indexOf('embed');
        if (embedIndex >= 0 && pathParts[embedIndex + 1]) {
            return pathParts[embedIndex + 1];
        }

        const shortsIndex = pathParts.indexOf('shorts');
        if (shortsIndex >= 0 && pathParts[shortsIndex + 1]) {
            return pathParts[shortsIndex + 1];
        }
    }

    return null;
}

function parseTiktokVideoId(url: URL): string | null {
    const host = url.hostname.replace(/^www\./, '');
    if (host !== 'tiktok.com' && host !== 'vm.tiktok.com') {
        return null;
    }

    const match = url.pathname.match(/\/video\/(\d+)/);
    return match?.[1] ?? null;
}

export function getVideoEmbedInfo(rawUrl?: string | null): VideoEmbedInfo | null {
    if (!rawUrl?.trim()) {
        return null;
    }

    let url: URL;
    try {
        url = new URL(rawUrl.trim());
    } catch {
        return null;
    }

    const youtubeId = parseYoutubeVideoId(url);
    if (youtubeId) {
        return {
            platform: 'youtube',
            embedUrl: `https://www.youtube.com/embed/${youtubeId}`,
        };
    }

    const tiktokId = parseTiktokVideoId(url);
    if (tiktokId) {
        return {
            platform: 'tiktok',
            embedUrl: `https://www.tiktok.com/embed/v2/${tiktokId}`,
        };
    }

    return null;
}
