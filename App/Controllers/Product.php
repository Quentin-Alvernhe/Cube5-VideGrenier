<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Utility\Upload;
use \Core\View;

/**
 * Product controller
 */
class Product extends \Core\Controller
{

    /**
     * Affiche la page d'ajout
     * @return void
     */
    private $articles;
    private $upload;

    /**
    *Constructeur du contrôleur, permet d'injecter les dépendances (utile pour les tests)
     **/
    public function __construct($route_params = [], $articlesModel = null, $uploadUtil = null)
    {
        parent::__construct($route_params);

        // si l'objet est déjà un Articles, pas besoin d'en créer un
        $this->articles = is_object($articlesModel) ? $articlesModel : new \App\Models\Articles();
        $this->upload   = is_object($uploadUtil)    ? $uploadUtil    : new \App\Utility\Upload();
    }

    /**
    *Affiche le formulaire d'jout d'un article
     **/
    public function indexAction()
    {

        if(isset($_POST['submit'])) {

            try {
                $f = $_POST;

                //on ajoute l'id de l'utilisateur connecté dans les données
                $f['user_id'] = $_SESSION['user']['id'];
                //Sauvegarde de l'article dans la base de données
                $id = Articles::save($f);

                //gestion de l'upload d'image
                $pictureName = $this->upload->uploadFile($_FILES['picture'], $id);
                //Associe l'image uploadée à l'article
                $this->articles->attachPicture($id, $pictureName);

                //redirection vers article
                header('Location: /product/' . $id);
            } catch (\Exception $e){
                    var_dump($e);
            }
        }

        View::renderTemplate('Product/Add.html');
    }

    /**
     * Affiche la fiche d’un article donné (GET ou POST via AJAX pour contact)
     */
    public function showAction()
    {
        $id = $this->route_params['id']; //id récupéré depuis l'url
        $article = $this->articles->getOne($id); //récuperation de l'article
        if (!$article) {
            header("HTTP/1.0 404 Not Found");
            exit;
        }

        //verifie si l'utilisateur actuel est connecté
        $currentUserId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;

        //Vérifie si l'utilisateur connecté est le proprio de l'article
        $articleOwnerID = (int) $article['user_id'];
        $isOwner = ($currentUserId !== null && $currentUserId === $articleOwnerID);

        //si l'utilisateur soumet le formulaire de contact
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');

            //on récup et nettoie les champs du form
            $subject   = trim(isset($_POST['subject']) ? $_POST['subject'] : '');
            $fromEmail = filter_var(isset($_POST['fromEmail']) ? $_POST['fromEmail'] : '', FILTER_VALIDATE_EMAIL);
            $message   = trim(isset($_POST['message']) ? $_POST['message'] : '');

            //verifie que tous les champs sont bien remplis
            if (!$subject || !$fromEmail || !$message) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => 'Tous les champs sont requis.']);
                exit;
            }

            //Envoi via PHPMailer
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'videgreniercontactcesi@gmail.com';
                $mail->Password   = 'lwer cwtj rppz eidf';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom($fromEmail);
                $mail->addAddress($article['email']);
                $mail->Subject = $subject;
                $mail->Body    = $message;

                $mail->send();

                //réponse json
                echo json_encode(['status' => 'ok', 'message' => 'Message envoyé avec succès.']);
                exit;
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Erreur mailer: ' . $e->getMessage()]);
                exit;
            }
        }

        // GET normal
        $this->articles->addOneView($id); //ajoute une vue a l'article
        $suggestions = $this->articles->getSuggest(); //récupere les suggestions d'autres articles
        //affiche la vue de l'article
        View::renderTemplate('Product/Show.html', [
            'article'     => $article,
            'suggestions' => $suggestions,
            'isOwner'     => $isOwner,
        ]);
    }

    /**
    *Supprime un article (accessible uniquement par le propriétaire)
     **/
    public function deleteAction() {
        $id = $this->route_params['id'];
        $article = $this->articles->getOne($id);
        if (!$article) {
            header("HTTP/1.0 404 Not Found");
            exit('Annonce introuvable');
        }

        //verifie que l'utilisateur est connecté
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $currentUserId = (int) $_SESSION['user']['id'];
        $articleOwnerId = (int) $article['user_id'];
        //seul le proprio peut supprimer son article
        if ($currentUserId !== $articleOwnerId) {
            header("HTTP/1.0 403 Forbidden");
            exit('Vous n’êtes pas autorisé à supprimer cette annonce.');
        }

        //suppression de l'article
        $success = $this->articles->delete($id);

        if ($success) {
            header('Location: /account');
            exit;
        } else {
            exit("impossible de supprimer l'annonce, réessayez plus tard.");
        }
    }
}

// l'envoie au serveur STMP fait des bugs par moments d'envoie au serveur aucune erreur de notre coté c ok
// juste leur serveur de merde