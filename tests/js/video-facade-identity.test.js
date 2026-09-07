/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import {
    buildVideoFacadeMarkup,
    resolveVideoFacadeIdentity,
    styleObjectToAttribute,
} from '../../resources/js/editor/video-poster.js';
import { parseFacadeElementAsVideo } from '../../resources/js/editor/editor-video.js';

function mockVideoModel({
    id = 'iabc123',
    style = 'border-radius: 24px; overflow: hidden;',
    classes = ['w-full', 'aspect-video', 'vb-video-host'],
    provider = 'yt',
    videoId = 'dQw4w9WgXcQ',
    autoplay = false,
    poster = '',
} = {}) {
    const attrs = {
        ...(id ? { id } : {}),
        ...(style ? { style } : {}),
    };

    return {
        getAttributes: () => ({ ...attrs }),
        getId: () => id,
        getClasses: () => [...classes],
        getStyle: () => ({}),
        get: (key) => {
            if (key === 'provider') {
                return provider;
            }

            if (key === 'videoId') {
                return videoId;
            }

            if (key === 'autoplay') {
                return autoplay;
            }

            if (key === 'poster') {
                return poster;
            }

            if (key === 'src') {
                return `https://www.youtube.com/embed/${videoId}`;
            }

            return undefined;
        },
    };
}

describe('video facade identity (id + style)', () => {
    it('styleObjectToAttribute joins declarations', () => {
        expect(styleObjectToAttribute({
            'border-radius': '24px',
            overflow: 'hidden',
        })).toBe('border-radius: 24px; overflow: hidden');
    });

    it('resolveVideoFacadeIdentity reads attrs.id and attrs.style', () => {
        const model = mockVideoModel();

        expect(resolveVideoFacadeIdentity(model)).toEqual({
            id: 'iabc123',
            style: 'border-radius: 24px; overflow: hidden;',
        });
    });

    it('resolveVideoFacadeIdentity falls back to getId and inline getStyle', () => {
        const model = {
            getAttributes: () => ({}),
            getId: () => 'from-get-id',
            getStyle: ({ inline } = {}) => (
                inline
                    ? { 'border-radius': '12px' }
                    : {}
            ),
        };

        expect(resolveVideoFacadeIdentity(model)).toEqual({
            id: 'from-get-id',
            style: 'border-radius: 12px',
        });
    });

    it('buildVideoFacadeMarkup emits id and style for export (counter contract)', () => {
        const html = buildVideoFacadeMarkup(
            mockVideoModel(),
            'https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1',
        );

        expect(html).toContain('id="iabc123"');
        expect(html).toContain('style="border-radius: 24px; overflow: hidden;"');
        expect(html).toContain('data-vb-video-facade');
        expect(html).toContain('data-vb-embed-src=');
    });

    it('buildVideoFacadeMarkup can omit identity for nested canvas facades', () => {
        const html = buildVideoFacadeMarkup(
            mockVideoModel(),
            'https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1',
            { persistIdentity: false },
        );

        expect(html).not.toContain(' id="');
        expect(html).not.toContain(' style="');
        expect(html).toContain('data-vb-video-facade');
    });
});

describe('parseFacadeElementAsVideo', () => {
    it('restores id and style attributes from exported facade markup', () => {
        const html = buildVideoFacadeMarkup(
            mockVideoModel({
                id: 'ivideo99',
                style: 'border-radius: 24px;',
            }),
            'https://www.youtube.com/embed/abc123?autoplay=1',
        );

        const attrs = Object.fromEntries(
            [...html.matchAll(/([a-zA-Z0-9:-]+)="([^"]*)"/g)].map((match) => [match[1], match[2]]),
        );

        const element = {
            getAttribute: (name) => {
                if (Object.hasOwn(attrs, name)) {
                    return attrs[name];
                }

                // Boolean attrs (e.g. data-vb-video-facade) have no ="…" in export.
                if (new RegExp(`(?:^|\\s)${name}(?:\\s|>|$)`).test(html)) {
                    return '';
                }

                return null;
            },
        };

        const parsed = parseFacadeElementAsVideo(element);

        expect(parsed).not.toBe(false);
        expect(parsed.attributes).toEqual({
            id: 'ivideo99',
            style: 'border-radius: 24px;',
        });
        expect(parsed.provider).toBe('yt');
        expect(parsed.videoId).toBe('abc123');
        expect(parsed.autoplay).toBe(false);
    });

    it('returns false for non-facade nodes', () => {
        expect(parseFacadeElementAsVideo({
            getAttribute: () => null,
        })).toBe(false);
    });
});
