/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import {
    ensureHtml5AutoplayMute,
    isEmbedVideoProvider,
    wrapPublishedEmbedVideoHtml,
} from '../../resources/js/editor/editor-video.js';

describe('isEmbedVideoProvider', () => {
    it('treats youtube / vimeo as embeds', () => {
        expect(isEmbedVideoProvider('yt')).toBe(true);
        expect(isEmbedVideoProvider('ytnc')).toBe(true);
        expect(isEmbedVideoProvider('vi')).toBe(true);
    });

    it('treats HTML5 file source as local', () => {
        expect(isEmbedVideoProvider('so')).toBe(false);
        expect(isEmbedVideoProvider('')).toBe(false);
        expect(isEmbedVideoProvider(null)).toBe(false);
    });
});

describe('ensureHtml5AutoplayMute', () => {
    it('forces muted when autoplay is on for local video', () => {
        const attrs = {};
        const state = { provider: 'so', autoplay: true, muted: false };
        const component = {
            is: (type) => type === 'video',
            get: (key) => state[key],
            set: (key, value) => {
                state[key] = value;
            },
            addAttributes: (next) => Object.assign(attrs, next),
        };

        expect(ensureHtml5AutoplayMute(component)).toBe(true);
        expect(state.muted).toBe(true);
        expect(attrs.muted).toBe(true);
        expect(attrs.playsinline).toBe(true);
    });

    it('does nothing when autoplay is off', () => {
        const state = { provider: 'so', autoplay: false, muted: false };
        const component = {
            is: (type) => type === 'video',
            get: (key) => state[key],
            set: (key, value) => {
                state[key] = value;
            },
            addAttributes: () => {},
        };

        expect(ensureHtml5AutoplayMute(component)).toBe(false);
        expect(state.muted).toBe(false);
    });
});

describe('wrapPublishedEmbedVideoHtml', () => {
    it('wraps bare iframe in .vb-video-host for front/editor DOM parity', () => {
        const component = {
            getClasses: () => ['w-full', 'aspect-video', 'rounded', 'vb-video-host'],
            getId: () => 'vid1',
            getStyle: () => ({ width: '100%', height: 'auto' }),
        };

        const html = wrapPublishedEmbedVideoHtml(
            component,
            '<iframe src="https://www.youtube-nocookie.com/embed/abc" class="w-full aspect-video vb-video-host" id="vid1" style="width:100%"></iframe>',
        );

        expect(html).toContain('class="vb-video-host w-full aspect-video rounded"');
        expect(html).toContain('id="vid1"');
        expect(html).toMatch(/<div[^>]+>[\s\S]*<iframe/);
        expect(html).not.toMatch(/<iframe[^>]*\bid=/);
        expect(html).not.toMatch(/<iframe[^>]*vb-video-host/);
    });

    it('leaves non-iframe markup untouched', () => {
        expect(wrapPublishedEmbedVideoHtml({}, '<video src="a.mp4"></video>')).toBe(
            '<video src="a.mp4"></video>',
        );
    });
});
