<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Models\Cities;
use \Core\View;
use Exception;

/**
 * API controller
 */
class Api extends \Core\Controller
{

    /**
     * Affiche la liste des articles / produits pour la page d'accueil
     *
     * @throws Exception
     */
    public function ProductsAction()
    {
        $query = $_GET['sort'];

        $articles = Articles::getAll($query);

        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        echo json_encode($articles);
    }

    /**
     * Recherche dans la liste des villes
     *
     * @throws Exception
     */
    public function CitiesAction(){

        $cities = Cities::search($_GET['query']);

        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        echo json_encode($cities);
    }

    public function SearchAction() {
        $query = trim($_GET['q'] ?? '');

        header('Content-Type: application/json');

        if (empty($query)) {
            echo json_encode([]);
            return;
        }

        $db = Articles::getDatabase();
        $stmt = $db->prepare('SELECT id, name, description, picture, views FROM articles WHERE name LIKE :query ORDER BY name ASC LIMIT 10');
        $stmt->execute([':query' => "%$query%"]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo json_encode($results);
    }

}
