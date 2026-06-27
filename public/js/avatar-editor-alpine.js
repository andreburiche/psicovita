/**
 * Editor de foto de perfil — Alpine via CDN (sem bundle Vite).
 * Manter alinhado a resources/js/avatar-editor.js
 */
(function () {
    function createAvatarEditorData(config) {
        config = config || {};
        var viewportSize = 240;
        var outputSize = 512;

        return {
            shape: config.shape || 'circle',
            ring: config.ring || 'violet',
            filter: config.filter || 'none',
            zoom: 1,
            panX: 0,
            panY: 0,
            imageSrc: null,
            removeAvatar: false,
            hasStoredAvatar: config.hasStoredAvatar || false,
            storedUrl: config.storedUrl || null,
            isDragging: false,
            dragStartX: 0,
            dragStartY: 0,
            panStartX: 0,
            panStartY: 0,
            viewportSize: viewportSize,
            outputSize: outputSize,
            minZoom: 1,

            init: function () {
                if (this.imageSrc) {
                    var self = this;
                    this.$nextTick(function () {
                        self.recalculateMinZoom();
                    });
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
                    transform: 'translate(-50%, -50%) translate(' + this.panX + 'px, ' + this.panY + 'px) scale(' + this.zoom + ')',
                };
            },

            onFileChange: function (event) {
                var file = event.target.files && event.target.files[0];
                if (!file) {
                    return;
                }

                var self = this;
                var reader = new FileReader();
                reader.onload = function () {
                    self.imageSrc = reader.result;
                    self.removeAvatar = false;
                    self.panX = 0;
                    self.panY = 0;
                    self.$nextTick(function () {
                        self.recalculateMinZoom();
                    });
                };
                reader.readAsDataURL(file);
            },

            recalculateMinZoom: function () {
                var img = this.$refs.sourceImg;
                if (!img || !img.naturalWidth) {
                    return;
                }

                this.minZoom = Math.max(
                    viewportSize / img.naturalWidth,
                    viewportSize / img.naturalHeight,
                );
                this.zoom = Math.max(this.minZoom, this.zoom);
            },

            onImageLoaded: function () {
                this.recalculateMinZoom();
            },

            startDrag: function (event) {
                if (!this.canEditCrop) {
                    return;
                }

                this.isDragging = true;
                this.dragStartX = event.clientX;
                this.dragStartY = event.clientY;
                this.panStartX = this.panX;
                this.panStartY = this.panY;
            },

            onDrag: function (event) {
                if (!this.isDragging) {
                    return;
                }

                this.panX = this.panStartX + (event.clientX - this.dragStartX);
                this.panY = this.panStartY + (event.clientY - this.dragStartY);
            },

            endDrag: function () {
                this.isDragging = false;
            },

            markRemove: function () {
                this.removeAvatar = true;
                this.imageSrc = null;
                if (this.$refs.filePicker) {
                    this.$refs.filePicker.value = '';
                }
                if (this.$refs.avatarInput) {
                    this.$refs.avatarInput.value = '';
                }
            },

            undoRemove: function () {
                this.removeAvatar = false;
            },

            prepareSubmit: function (event) {
                var self = this;

                if (!this.imageSrc || this.removeAvatar) {
                    return;
                }

                event.preventDefault();

                this.exportBlob().then(function (blob) {
                    if (!blob || !self.$refs.avatarInput) {
                        event.target.submit();
                        return;
                    }

                    var file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
                    var transfer = new DataTransfer();
                    transfer.items.add(file);
                    self.$refs.avatarInput.files = transfer.files;
                    event.target.submit();
                });
            },

            exportBlob: function () {
                var self = this;

                return new Promise(function (resolve) {
                    var img = new Image();
                    img.onload = function () {
                        var canvas = document.createElement('canvas');
                        canvas.width = outputSize;
                        canvas.height = outputSize;
                        var ctx = canvas.getContext('2d');
                        if (!ctx) {
                            resolve(null);
                            return;
                        }

                        var displayWidth = img.naturalWidth * self.zoom;
                        var displayHeight = img.naturalHeight * self.zoom;
                        var offsetX = (viewportSize / 2) - (displayWidth / 2) + self.panX;
                        var offsetY = (viewportSize / 2) - (displayHeight / 2) + self.panY;

                        var sx = Math.max(0, -offsetX / self.zoom);
                        var sy = Math.max(0, -offsetY / self.zoom);
                        var sw = viewportSize / self.zoom;
                        var sh = viewportSize / self.zoom;

                        ctx.drawImage(img, sx, sy, sw, sh, 0, 0, outputSize, outputSize);
                        canvas.toBlob(function (blob) {
                            resolve(blob);
                        }, 'image/jpeg', 0.92);
                    };
                    img.onerror = function () {
                        resolve(null);
                    };
                    img.src = self.imageSrc;
                });
            },
        };
    }

    document.addEventListener('alpine:init', function () {
        if (window.__psiconectaAvatarEditorRegistered) {
            return;
        }
        window.__psiconectaAvatarEditorRegistered = true;
        window.Alpine.data('avatarEditor', createAvatarEditorData);
    });
})();
