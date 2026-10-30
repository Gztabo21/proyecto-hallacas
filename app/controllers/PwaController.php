<?php
class PwaController {
    public static function settingsStore(): void {
        require_role('admin');
        if (!csrf_verify()) {
            flash('error', 'CSRF inválido');
            redirect('dashboard');
        }
        set_setting('pwa_enabled', !empty($_POST['pwa_enabled']) ? '1' : '0');
        flash('success', !empty($_POST['pwa_enabled']) ? 'PWA activada' : 'PWA desactivada');
        redirect('dashboard');
    }
}
