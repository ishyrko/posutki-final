'use client';

import { useEffect, useRef } from 'react';
import { ExternalLink } from 'lucide-react';
import type { VideoEmbedInfo } from '@/features/properties/lib/videoEmbed';

declare global {
    interface Window {
        tiktokEmbed?: {
            lib?: {
                render: (element?: HTMLElement | null) => void;
            };
        };
    }
}

const TIKTOK_EMBED_SCRIPT = 'https://www.tiktok.com/embed.js';

let tiktokEmbedScriptPromise: Promise<void> | null = null;

function loadTikTokEmbedScript(): Promise<void> {
    if (typeof window === 'undefined') {
        return Promise.resolve();
    }

    if (tiktokEmbedScriptPromise) {
        return tiktokEmbedScriptPromise;
    }

    tiktokEmbedScriptPromise = new Promise((resolve, reject) => {
        const existing = document.querySelector<HTMLScriptElement>(`script[src="${TIKTOK_EMBED_SCRIPT}"]`);
        if (existing) {
            if (existing.dataset.loaded === 'true') {
                resolve();
                return;
            }
            existing.addEventListener('load', () => resolve(), { once: true });
            existing.addEventListener('error', () => reject(new Error('TikTok embed script failed')), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = TIKTOK_EMBED_SCRIPT;
        script.async = true;
        script.onload = () => {
            script.dataset.loaded = 'true';
            resolve();
        };
        script.onerror = () => reject(new Error('TikTok embed script failed'));
        document.body.appendChild(script);
    });

    return tiktokEmbedScriptPromise;
}

function TikTokPlayer({ embed }: { embed: VideoEmbedInfo }) {
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        let cancelled = false;

        void loadTikTokEmbedScript()
            .then(() => {
                if (cancelled) return;
                window.tiktokEmbed?.lib?.render(containerRef.current);
            })
            .catch(() => {
                // Fallback link remains visible inside blockquote.
            });

        return () => {
            cancelled = true;
        };
    }, [embed.originalUrl, embed.videoId]);

    return (
        <div ref={containerRef} className="flex justify-center">
            <blockquote
                className="tiktok-embed"
                cite={embed.originalUrl}
                data-video-id={embed.videoId}
                style={{ maxWidth: 605, minWidth: 325 }}
            >
                <section>
                    <a
                        href={embed.originalUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-sm text-primary hover:underline"
                    >
                        Смотреть в TikTok
                    </a>
                </section>
            </blockquote>
        </div>
    );
}

export function PropertyVideoPlayer({ embed }: { embed: VideoEmbedInfo }) {
    if (embed.platform === 'youtube' && embed.embedUrl) {
        return (
            <div className="relative w-full overflow-hidden rounded-2xl border border-border/50 bg-black aspect-video">
                <iframe
                    src={embed.embedUrl}
                    title="Видео объявления"
                    className="absolute inset-0 h-full w-full"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowFullScreen
                />
            </div>
        );
    }

    return (
        <div className="space-y-3">
            <div className="overflow-hidden rounded-2xl border border-border/50 bg-muted/30 p-4">
                <TikTokPlayer embed={embed} />
            </div>
            <a
                href={embed.originalUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-1.5 text-sm text-primary hover:underline"
            >
                <ExternalLink className="h-4 w-4" />
                Открыть видео в TikTok
            </a>
        </div>
    );
}
