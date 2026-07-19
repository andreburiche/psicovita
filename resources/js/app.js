import './bootstrap';

import Alpine from 'alpinejs';
import { initInputMasks } from './masks';
import { initFieldValidation } from './field-validation';
import { initCepLookup } from './cep-lookup';
import { createAnamnesisBuilderData } from './anamnesis-builder-data';
import { createConfirmDialogData } from './confirm-dialog';
import { createAppShellData } from './app-shell';
import { createAvatarEditorData } from './avatar-editor';
import { createInactivityGuardData } from './inactivity-guard';

document.addEventListener('alpine:init', () => {
    if (! window.__psiconectaAppShellRegistered) {
        window.__psiconectaAppShellRegistered = true;
        Alpine.data('appShell', createAppShellData);
    }

    if (! window.__psiconectaAvatarEditorRegistered) {
        window.__psiconectaAvatarEditorRegistered = true;
        Alpine.data('avatarEditor', createAvatarEditorData);
    }

    if (! window.__psiconectaInactivityGuardRegistered) {
        window.__psiconectaInactivityGuardRegistered = true;
        Alpine.data('inactivityGuard', createInactivityGuardData);
    }

    if (! window.__psiconectaConfirmDialogRegistered) {
        window.__psiconectaConfirmDialogRegistered = true;
        Alpine.data('confirmDialog', createConfirmDialogData);
    }

    if (window.__psiconectaAnamnesisBuilderRegistered) {
        return;
    }
    window.__psiconectaAnamnesisBuilderRegistered = true;
    Alpine.data('anamnesisBuilder', createAnamnesisBuilderData);
});

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initInputMasks();
    initFieldValidation();
    initCepLookup();
});
