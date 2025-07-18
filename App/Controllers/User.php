<?php

namespace App\Controllers;

use App\Config;
use App\Model\UserRegister;
use App\Models\Articles;
use App\Utility\Hash;
use App\Utility\Session;
use \Core\View;
use Exception;
use http\Env\Request;
use http\Exception\InvalidArgumentException;
use PHPMailer\PHPMailer\PHPMailer;

class User extends \Core\Controller
{
    /**
    *Page de connexion utilisateur
     **/

    public function loginAction()
    {
        // On stocke un message d’erreur éventuel ici
        $error = null;


        if (isset($_POST['submit'])) {
            $f = $_POST;

            //connexion de l'utilisateur
            $ok = $this->login($f, isset($f['remember']) && $f['remember'] === 'on');

            if ($ok) {
                // Si OK, on redirige vers le compte
                header('Location: /account');
                exit;
            } else {
                // Sinon on prépare un message d’erreur
                $error = 'Email ou mot de passe incorrect.';
            }
        }

        // Passe la variable $error à la vue
        View::renderTemplate('User/login.html', ['error' => $error]);
    }

    /**
    *Inscription d'un nouvel utilisateur
     **/
    public function registerAction()
    {
        $error = null;

        if (isset($_POST['submit'])) {
            $f = $_POST;

            // 1) Verifie la securité du mot de passe
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $f['password'])) {
                $error = 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
                View::renderTemplate('User/register.html', ['error' => $error]);
                return;
            }

            // 2) Vérifie la correspondance des mots de passe
            if ($f['password'] !== $f['password_confirmation']) {
                $error = 'Les deux mots de passe ne correspondent pas.';
                View::renderTemplate('User/register.html', ['error' => $error]);
                return;
            }

            // 3) Vérifie si l’email est déjà utilisé
            $existing = \App\Models\User::getByLogin($f['email']);
            if ($existing) {
                $error = 'Cet email est déjà enregistré.';
                View::renderTemplate('User/register.html', ['error' => $error]);
                return;
            }

            // 4) Création du compte
            $userID = $this->register($f);
            if (!$userID) {
                $error = 'Une erreur est survenue, impossible de créer le compte.';
                View::renderTemplate('User/register.html', ['error' => $error]);
                return;
            }

            // 5) Connexion automatique
            $loginOk = $this->login([
                'email'    => $f['email'],
                'password' => $f['password']
            ]);

            if ($loginOk) {
                header('Location: /account');
                exit;
            } else {
                $error = 'Compte créé mais échec de la connexion automatique.';
            }
        }

        View::renderTemplate('User/register.html', ['error' => $error]);
    }

    /**
    *Affiche la page de compte de l'utilisateur
     **/
    public function accountAction()
    {
        if (!isset($_SESSION['user'])) {
            if (isset($_COOKIE['remember_token'])) {
                // Vérifier le token avec la BDD (exemple simple)
                $user = \App\Models\User::getByRememberToken($_COOKIE['remember_token']);
                 if ($user) {
                     $_SESSION['user'] = [
                         'id' => $user['id'],
                         'username' => $user['username'],
                     ];
                 } else {
                     header('Location: /login');
                     exit;
                 }
                // Pour le test simple sans BDD :
                //$_SESSION['user'] = [
                //    'id' => 1,
                //    'username' => 'TestUser',
                //];
            } else {
                header('Location: /login');
                exit;
            }
        }
        $articles = Articles::getByUser($_SESSION['user']['id']);

        View::renderTemplate('User/account.html', [
            'articles' => $articles
        ]);
    }

    /**
    *Méthode privée pour enregistrer un utilisateur
     **/
    private function register($data)
    {
        // Validation du mot de passe (longueur, maj, min, chiffre, spécial)
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $data['password'])) {
            return false; // on laisse la fonction appeler l’erreur plus haut
        }

        // si confirmation fournie et ne matche pas, on émet un warning PHP
        if (isset($data['password_confirmation']) && $data['password'] !== $data['password_confirmation']) {
            trigger_error('Les mots de passe ne correspondent pas', E_USER_WARNING);
        }

        try {
            $salt = Hash::generateSalt(32);
            $userID = \App\Models\User::createUser([
                "email"    => $data['email'],
                "username" => $data['username'],
                "password" => Hash::generate($data['password'], $salt),
                "salt"     => $salt
            ]);
            return $userID;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
    *Connexion d'un utilisateur
     **/
    private function login($data, $rememberMe = false){
        try {
            if(!isset($data['email'])){
                throw new Exception('TODO');
            }

            $user = \App\Models\User::getByLogin($data['email']);

            if (Hash::generate($data['password'], $user['salt']) !== $user['password']) {
                return false;
            }

            $_SESSION['user'] = array(
                'id' => $user['id'],
                'username' => $user['username'],
            );

            if ($rememberMe) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + 86400, "/");
            }

            return true;

        } catch (Exception $ex) {
           error_log('Erreur login : ' . $ex->getMessage());
           return false;
        }
    }

    /**
    *Déconnexion de l'utilisateur
     **/
    public function logoutAction() {

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        setcookie('remember_token', '', time() - 3600, "/");

        header ('Location: /');

        return true;
    }

    // Affiche le formulaire de demande de reset
    public function forgotPasswordAction() {
        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'];
            $user = \App\Models\User::getByLogin($email);

            if (!$user) {
                $error = "Aucun compte avec cet email.";
            } else {
                $code = rand(100000, 999999);
                $_SESSION['reset'] = [
                    'email' => $email,
                    'code' => $code,
                    'time' => time()
                ];


                // PHPMailer pour envoyer le code
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'videgreniercontactcesi@gmail.com';
                    $mail->Password   = 'lwer cwtj rppz eidf';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;

                    $mail->setFrom('videgreniercontactcesi@gmail.com', 'VideGrenier');
                    $mail->addAddress($email);
                    $mail->CharSet = 'UTF-8';
                    $mail->Subject = 'Réinitialisation de votre mot de passe';
                    $mail->Body    = "Voici votre code de vérification : $code";

                    $mail->send();

                    header("Location: /reset-code");
                    exit;
                } catch (Exception $e) {
                    $error = "Erreur lors de l’envoi de l’email : " . $mail->ErrorInfo;
                }
            }
        }

        View::renderTemplate("User/forgot.html", [
            'error' => $error,
            'success' => $success
        ]);
    }

    /**
    *Renvoie d'un code si l'utilisateur le redemande
     **/
    public function resendCodeAction() {
        if (!isset($_SESSION['reset']['email'])) {
            header('Location: /forgot');
            exit;
        }

        $email = $_SESSION['reset']['email'];
        $code = rand(100000, 999999);
        $_SESSION['reset']['code'] = $code;
        $_SESSION['reset']['time'] = time();

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'videgreniercontactcesi@gmail.com';
            $mail->Password   = 'lwer cwtj rppz eidf';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->CharSet = 'UTF-8';
            $mail->setFrom('videgreniercontactcesi@gmail.com', 'VideGrenier');
            $mail->addAddress($email);
            $mail->Subject = 'Nouveau code de vérification';
            $mail->Body    = "Voici votre nouveau code de vérification : $code";

            $mail->send();

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            // Optionnel : log erreur
        }

        $_SESSION['flash_success'] = "Un nouveau code vous a été envoyé par email.";
        // Redirige vers le formulaire
        header('Location: /reset-code');
        exit;
    }

// Vérifie le code
    public function resetCodeAction() {
        $error = null;

        if (!isset($_SESSION['reset'])) {
            header("Location: /forgot");
            exit;
        }

        // Expiration après 5 minutes
        if (time() - $_SESSION['reset']['time'] > 300) {
            unset($_SESSION['reset']);
            $error = "Code expiré. <a href='/forgot'>Clique ici pour en demander un nouveau</a>.";
            View::renderTemplate("User/reset_code.html", [
                'error' => $error
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = $_POST['code'];

            if ($code != $_SESSION['reset']['code']) {
                $error = "Code invalide.";
            } else {
                $_SESSION['reset']['verified'] = true;
                header("Location: /reset-password");
                exit;
            }
        }

        $success = null;
        if (isset($_SESSION['flash_success'])) {
            $success = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }

        View::renderTemplate("User/reset_code.html", [
            'error' => $error,
            'success' => $success
        ]);
    }

// Change le mot de passe
    public function resetPasswordAction() {
        $error = null;

        if (!isset($_SESSION['reset']) || !$_SESSION['reset']['verified']) {
            header("Location: /forgot");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pass = $_POST['password'];
            $confirm = $_POST['password_confirmation'];

            if ($pass !== $confirm) {
                $error = "Les mots de passe ne correspondent pas.";
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $pass)) {
                $error = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
            } else {
                $email = $_SESSION['reset']['email'];
                $user = \App\Models\User::getByLogin($email);
                $salt = \App\Utility\Hash::generateSalt(32);
                $hashed = \App\Utility\Hash::generate($pass, $salt);

                $db = \App\Models\User::getConnexion();
                $stmt = $db->prepare("UPDATE users SET password = :pwd, salt = :salt WHERE email = :email");
                $stmt->execute([
                    ':pwd' => $hashed,
                    ':salt' => $salt,
                    ':email' => $email
                ]);

                unset($_SESSION['reset']);
                header("Location: /login");
                exit;
            }
        }

        View::renderTemplate("User/reset_password.html", [
            'error' => $error
        ]);
    }

}
