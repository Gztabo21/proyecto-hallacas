<?php
class AuthController {
    public static function login(): void {
        if (Auth::check()) { redirect('dashboard'); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify()) { flash('error','CSRF inválido'); redirect('login'); }
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            if (Auth::attempt($email, $password)) {
                redirect('dashboard');
            } else {
                flash('error','Credenciales inválidas');
                redirect('login');
            }
        }
        include BASE_PATH . '/app/views/login.php';
    }
    public static function logout(): void {
        Auth::logout();
        redirect('login');
    }
}
