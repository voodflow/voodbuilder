/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import { resolveAssetManagerCopy, shouldUseMediaCompanionBrowser } from '../../resources/js/editor/editor-assets.js';

describe('resolveAssetManagerCopy', () => {
    it('uses image copy for poster / cover pickers', () => {
        const copy = resolveAssetManagerCopy('image', {
            assetManagerImageTitle: 'Select image',
            assetManagerImageAdd: 'Add image',
            assetManagerImageInput: 'https://…/image.jpg',
            assetManagerImageUpload: 'Drop files here or click to upload',
            assetManagerVideoTitle: 'Select video',
            assetManagerVideoAdd: 'Add video',
            assetManagerVideoInput: 'https://…/video.mp4',
            assetManagerVideoUpload: 'Drop a video here or click to upload',
        });

        expect(copy.modalTitle).toBe('Select image');
        expect(copy.addButton).toBe('Add image');
        expect(copy.inputPlh).toBe('https://…/image.jpg');
        expect(copy.uploadTitle).toBe('Drop files here or click to upload');
    });

    it('uses video copy only for video file pickers', () => {
        const copy = resolveAssetManagerCopy('video', {
            assetManagerVideoTitle: 'Select video',
            assetManagerVideoAdd: 'Add video',
            assetManagerVideoInput: 'https://…/video.mp4',
            assetManagerVideoUpload: 'Drop a video here or click to upload',
        });

        expect(copy.modalTitle).toBe('Select video');
        expect(copy.addButton).toBe('Add video');
        expect(copy.inputPlh).toContain('video.mp4');
        expect(copy.uploadTitle).toContain('video');
    });
});

describe('shouldUseMediaCompanionBrowser', () => {
    it('requires both library and galleries URLs (Media companion)', () => {
        expect(shouldUseMediaCompanionBrowser('/voodbuilder/editor/media', null)).toBe(false);
        expect(shouldUseMediaCompanionBrowser('/voodbuilder/editor/media', '')).toBe(false);
        expect(shouldUseMediaCompanionBrowser('', '/voodbuilder/editor/media/galleries')).toBe(false);
        expect(shouldUseMediaCompanionBrowser(
            '/voodbuilder/editor/media',
            '/voodbuilder/editor/media/galleries',
        )).toBe(true);
    });
});
