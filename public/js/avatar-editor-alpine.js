/**
 * Editor de foto de perfil — Alpine via CDN.
 * Manter alinhado a resources/js/avatar-editor.js
 */
(function () {
function createAvatarEditorData(config = {}) {
    const viewportSize = 280;
    const outputSize = 512;
    const maxFileBytes = 2 * 1024 * 1024;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    return {
        shape: config.shape ?? 'circle',
        ring: config.ring ?? 'violet',
        filter: config.filter ?? 'none',
        zoom: 1,
        panX: 0,
        panY: 0,
        rotation: 0,
        imageSrc: null,
        previewSrc: null,
        previewBroken: false,
        fileError: null,
        removeAvatar: false,
        hasStoredAvatar: config.hasStoredAvatar ?? false,
        storedUrl: config.storedUrl ?? null,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,
        panStartX: 0,
        panStartY: 0,
        viewportSize,
        outputSize,
        minZoom: 1,
        maxZoom: 3,
        previewTimer: null,
        objectUrl: null,

        init() {
            this.refreshPreviewSrc();
            this.$watch('shape', () => this.scheduleLivePreview());
            this.$watch('zoom', () => this.scheduleLivePreview());
            this.$watch('panX', () => this.scheduleLivePreview());
            this.$watch('panY', () => this.scheduleLivePreview());
            this.$watch('rotation', () => this.scheduleLivePreview());
            this.$watch('filter', () => this.scheduleLivePreview());
        },

        get hasPreview() {
            return !!this.previewSrc && !this.previewBroken && !this.removeAvatar;
        },

        get canEditCrop() {
            return !!this.imageSrc && !this.removeAvatar;
        },

        get imageTransform() {
            return {
                transform: `translate(-50%, -50%) translate(${this.panX}px, ${this.panY}px) rotate(${this.rotation}deg) scale(${this.zoom})`,
            };
        },

        get cropMaskClass() {
            if (this.shape === 'circle') {
                return 'rounded-full';
            }
            if (this.shape === 'rounded') {
                return 'rounded-2xl';
            }

            return 'rounded-none';
        },

        refreshPreviewSrc() {
            if (this.removeAvatar) {
                this.previewSrc = null;
                this.previewBroken = false;

                return;
            }

            if (this.imageSrc) {
                this.scheduleLivePreview(true);

                return;
            }

            this.previewSrc = this.storedUrl;
            this.previewBroken = false;
        },

        onPreviewError() {
            this.previewBroken = true;
        },

        onFileChange(event) {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }

            this.fileError = null;

            if (!allowedTypes.includes(file.type)) {
                this.fileError = 'Use uma imagem JPEG, PNG ou WebP.';
                event.target.value = '';

                return;
            }

            if (file.size > maxFileBytes) {
                this.fileError = 'A imagem deve ter no máximo 2 MB.';
                event.target.value = '';

                return;
            }

            if (this.objectUrl) {
                URL.revokeObjectURL(this.objectUrl);
                this.objectUrl = null;
            }

            const reader = new FileReader();
            reader.onload = () => {
                this.imageSrc = reader.result;
                this.removeAvatar = false;
                this.previewBroken = false;
                this.panX = 0;
                this.panY = 0;
                this.rotation = 0;
                this.zoom = 1;
                this.previewSrc = reader.result;
                this.$nextTick(() => {
                    this.recalculateMinZoom();
                    this.scheduleLivePreview(true);
                });
            };
            reader.onerror = () => {
                this.fileError = 'Não foi possível ler a imagem. Tente outro ficheiro.';
            };
            reader.readAsDataURL(file);
        },

        recalculateMinZoom() {
            const img = this.$refs.sourceImg;
            if (!img?.naturalWidth) {
                return;
            }

            const cover = Math.max(
                viewportSize / img.naturalWidth,
                viewportSize / img.naturalHeight,
            );

            this.minZoom = cover;
            this.maxZoom = Math.max(cover * 4, cover + 0.5);
            this.zoom = this.clampZoom(Math.max(this.zoom, cover));
            this.clampPan();
        },

        clampZoom(value) {
            const min = this.minZoom || 1;
            const max = this.maxZoom || 3;

            return Math.min(max, Math.max(min, Number(value) || min));
        },

        clampPan() {
            const img = this.$refs.sourceImg;
            if (!img?.naturalWidth) {
                return;
            }

            const displayW = img.naturalWidth * this.zoom;
            const displayH = img.naturalHeight * this.zoom;
            const maxX = Math.max(0, (displayW - viewportSize) / 2);
            const maxY = Math.max(0, (displayH - viewportSize) / 2);

            this.panX = Math.min(maxX, Math.max(-maxX, this.panX));
            this.panY = Math.min(maxY, Math.max(-maxY, this.panY));
        },

        onImageLoaded() {
            this.recalculateMinZoom();
            this.scheduleLivePreview(true);
        },

        onZoomInput() {
            this.zoom = this.clampZoom(this.zoom);
            this.clampPan();
            this.scheduleLivePreview();
        },

        nudgeZoom(delta) {
            this.zoom = this.clampZoom(this.zoom + delta);
            this.clampPan();
            this.scheduleLivePreview();
        },

        rotateBy(degrees) {
            this.rotation = (this.rotation + degrees) % 360;
            this.$nextTick(() => {
                this.recalculateMinZoom();
                this.scheduleLivePreview(true);
            });
        },

        resetFrame() {
            this.panX = 0;
            this.panY = 0;
            this.rotation = 0;
            this.$nextTick(() => {
                this.recalculateMinZoom();
                this.scheduleLivePreview(true);
            });
        },

        startDrag(event) {
            if (!this.canEditCrop) {
                return;
            }

            this.isDragging = true;
            this.dragStartX = event.clientX;
            this.dragStartY = event.clientY;
            this.panStartX = this.panX;
            this.panStartY = this.panY;
            event.currentTarget?.setPointerCapture?.(event.pointerId);
        },

        onDrag(event) {
            if (!this.isDragging) {
                return;
            }

            this.panX = this.panStartX + (event.clientX - this.dragStartX);
            this.panY = this.panStartY + (event.clientY - this.dragStartY);
            this.clampPan();
        },

        endDrag() {
            this.isDragging = false;
            this.scheduleLivePreview();
        },

        onWheel(event) {
            if (!this.canEditCrop) {
                return;
            }

            event.preventDefault();
            const delta = event.deltaY > 0 ? -0.08 : 0.08;
            this.nudgeZoom(delta);
        },

        markRemove() {
            this.removeAvatar = true;
            this.imageSrc = null;
            this.previewSrc = null;
            this.previewBroken = false;
            this.fileError = null;
            if (this.objectUrl) {
                URL.revokeObjectURL(this.objectUrl);
                this.objectUrl = null;
            }
            if (this.$refs.filePicker) {
                this.$refs.filePicker.value = '';
            }
            if (this.$refs.avatarInput) {
                this.$refs.avatarInput.value = '';
            }
        },

        undoRemove() {
            this.removeAvatar = false;
            this.refreshPreviewSrc();
        },

        scheduleLivePreview(immediate = false) {
            if (this.previewTimer) {
                clearTimeout(this.previewTimer);
                this.previewTimer = null;
            }

            if (!this.imageSrc || this.removeAvatar) {
                return;
            }

            if (immediate) {
                this.updateLivePreview();

                return;
            }

            this.previewTimer = setTimeout(() => this.updateLivePreview(), 80);
        },

        async updateLivePreview() {
            const blob = await this.exportBlob();
            if (!blob) {
                this.previewSrc = this.imageSrc;
                this.previewBroken = false;

                return;
            }

            if (this.objectUrl) {
                URL.revokeObjectURL(this.objectUrl);
            }

            this.objectUrl = URL.createObjectURL(blob);
            this.previewSrc = this.objectUrl;
            this.previewBroken = false;
        },

        async prepareSubmit(event) {
            const logoInput = event.target.querySelector?.('#institution_logo');
            if (logoInput?.files?.[0] && logoInput.files[0].size > 8 * 1024 * 1024) {
                event.preventDefault();
                this.fileError = 'A logo da instituição deve ter no máximo 8 MB (campo separado da foto de perfil).';
                logoInput.focus();

                return;
            }

            if (!this.imageSrc || this.removeAvatar) {
                return;
            }

            event.preventDefault();

            const blob = await this.exportBlob();
            if (!blob || !this.$refs.avatarInput) {
                event.target.submit();

                return;
            }

            const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
            const transfer = new DataTransfer();
            transfer.items.add(file);
            this.$refs.avatarInput.files = transfer.files;

            // Evita reenviar um ficheiro grande residual no picker sem name.
            if (this.$refs.filePicker) {
                this.$refs.filePicker.value = '';
            }

            event.target.submit();
        },

        exportBlob() {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = outputSize;
                    canvas.height = outputSize;
                    const ctx = canvas.getContext('2d');
                    if (!ctx) {
                        resolve(null);

                        return;
                    }

                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, outputSize, outputSize);

                    const scale = outputSize / viewportSize;
                    ctx.save();
                    ctx.translate(outputSize / 2, outputSize / 2);
                    ctx.translate(this.panX * scale, this.panY * scale);
                    ctx.rotate((this.rotation * Math.PI) / 180);
                    ctx.scale(this.zoom * scale, this.zoom * scale);
                    ctx.drawImage(img, -img.naturalWidth / 2, -img.naturalHeight / 2);
                    ctx.restore();

                    this.applyCanvasFilter(ctx, outputSize);

                    canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.92);
                };
                img.onerror = () => resolve(null);
                img.src = this.imageSrc;
            });
        },

        applyCanvasFilter(ctx, size) {
            if (this.filter === 'none') {
                return;
            }

            const imageData = ctx.getImageData(0, 0, size, size);
            const data = imageData.data;

            for (let i = 0; i < data.length; i += 4) {
                let r = data[i];
                let g = data[i + 1];
                let b = data[i + 2];

                if (this.filter === 'grayscale') {
                    const gray = 0.299 * r + 0.587 * g + 0.114 * b;
                    r = g = b = gray;
                } else if (this.filter === 'warm') {
                    r = Math.min(255, r * 1.08 + 12);
                    g = Math.min(255, g * 1.02);
                    b = Math.max(0, b * 0.92);
                } else if (this.filter === 'cool') {
                    r = Math.max(0, r * 0.95);
                    g = Math.min(255, g * 1.02);
                    b = Math.min(255, b * 1.1 + 8);
                }

                data[i] = r;
                data[i + 1] = g;
                data[i + 2] = b;
            }

            ctx.putImageData(imageData, 0, 0);
        },
    };
}


    document.addEventListener('alpine:init', function () {
        if (window.__psiconectaAvatarEditorRegistered) return;
        window.__psiconectaAvatarEditorRegistered = true;
        Alpine.data('avatarEditor', createAvatarEditorData);
    });
})();
