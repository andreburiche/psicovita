/**
 * Editor de foto de perfil (recorte + personalização) para Alpine.js.
 */
export function createAvatarEditorData(config = {}) {
    const viewportSize = 240;
    const outputSize = 512;

    return {
        shape: config.shape ?? 'circle',
        ring: config.ring ?? 'violet',
        filter: config.filter ?? 'none',
        zoom: 1,
        panX: 0,
        panY: 0,
        imageSrc: null,
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

        init() {
            if (this.imageSrc) {
                this.$nextTick(() => this.recalculateMinZoom());
            }
        },

        get previewSrc() {
            if (this.removeAvatar) {
                return null;
            }

            return this.imageSrc || this.storedUrl;
        },

        get hasPreview() {
            return !!this.previewSrc;
        },

        get canEditCrop() {
            return !!this.imageSrc && !this.removeAvatar;
        },

        get imageTransform() {
            return {
                transform: `translate(-50%, -50%) translate(${this.panX}px, ${this.panY}px) scale(${this.zoom})`,
            };
        },

        onFileChange(event) {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = () => {
                this.imageSrc = reader.result;
                this.removeAvatar = false;
                this.panX = 0;
                this.panY = 0;
                this.$nextTick(() => this.recalculateMinZoom());
            };
            reader.readAsDataURL(file);
        },

        recalculateMinZoom() {
            const img = this.$refs.sourceImg;
            if (!img?.naturalWidth) {
                return;
            }

            this.minZoom = Math.max(
                viewportSize / img.naturalWidth,
                viewportSize / img.naturalHeight,
            );
            this.zoom = this.clampZoom(this.zoom);
        },

        clampZoom(value) {
            const min = this.minZoom || 1;
            const max = 3;

            return Math.min(max, Math.max(min, Number(value) || min));
        },

        onImageLoaded() {
            this.recalculateMinZoom();
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
        },

        onDrag(event) {
            if (!this.isDragging) {
                return;
            }

            this.panX = this.panStartX + (event.clientX - this.dragStartX);
            this.panY = this.panStartY + (event.clientY - this.dragStartY);
        },

        endDrag() {
            this.isDragging = false;
        },

        markRemove() {
            this.removeAvatar = true;
            this.imageSrc = null;
            if (this.$refs.filePicker) {
                this.$refs.filePicker.value = '';
            }
            if (this.$refs.avatarInput) {
                this.$refs.avatarInput.value = '';
            }
        },

        undoRemove() {
            this.removeAvatar = false;
        },

        async prepareSubmit(event) {
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

                    const displayWidth = img.naturalWidth * this.zoom;
                    const displayHeight = img.naturalHeight * this.zoom;
                    const offsetX = (viewportSize / 2) - (displayWidth / 2) + this.panX;
                    const offsetY = (viewportSize / 2) - (displayHeight / 2) + this.panY;

                    const sx = Math.max(0, -offsetX / this.zoom);
                    const sy = Math.max(0, -offsetY / this.zoom);
                    const sw = viewportSize / this.zoom;
                    const sh = viewportSize / this.zoom;

                    ctx.drawImage(img, sx, sy, sw, sh, 0, 0, outputSize, outputSize);
                    canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.92);
                };
                img.onerror = () => resolve(null);
                img.src = this.imageSrc;
            });
        },
    };
}
