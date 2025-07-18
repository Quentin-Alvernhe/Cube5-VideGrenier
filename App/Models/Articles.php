<?php

namespace App\Models;

use Core\Model;
use App\Core;
use DateTime;
use Exception;
use App\Utility;

/**
 * Articles Model
 */
class Articles extends Model {

    /**
     *Récupère tous les articles triés selon un filtre donné (popularité ou date).
     */
    public static function getAll($filter) {
        $db = static::getDB();

        $query = 'SELECT * FROM articles ';

        //Applique un tri si un filtre est spécifié
        switch ($filter){
            case 'views':
                $query .= ' ORDER BY articles.views DESC'; //trie par vue
                break;
            case 'data':
                $query .= ' ORDER BY articles.published_date DESC'; // trie par date
                break;
            case '':
                break;
        }

        $stmt = $db->query($query);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     *Récupére un article précis par son ID + infos de l'utilisateur
     */
    public static function getOne($id) {
        $db = static::getDB();

        $stmt = $db->prepare('
        SELECT articles.*, users.username, users.email 
        FROM articles
        INNER JOIN users ON articles.user_id = users.id
        WHERE articles.id = ? 
        LIMIT 1
    ');

        $stmt->execute([$id]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     *Incrémente le compteur de vues d'un article
     */
    public static function addOneView($id) {
        $db = static::getDB();

        $stmt = $db->prepare('
            UPDATE articles 
            SET articles.views = articles.views + 1
            WHERE articles.id = ?');

        $stmt->execute([$id]);
    }

    /**
     *Récupère tous les articles d'un utilisateur donnée (par id utilisateur)
     */
    public static function getByUser($id) {
        $db = static::getDB();

        $stmt = $db->prepare('
            SELECT *, articles.id as id FROM articles
            LEFT JOIN users ON articles.user_id = users.id
            WHERE articles.user_id = ?');

        $stmt->execute([$id]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     *Récupère une liste de 10 articles récents
     */
    public static function getSuggest() {
        $db = static::getDB();

        $stmt = $db->prepare('
            SELECT *, articles.id as id FROM articles
            INNER JOIN users ON articles.user_id = users.id
            ORDER BY published_date DESC LIMIT 10');

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     *Sauvegarde un nouvel article dans la base
     */
    public static function save($data) {
        $db = static::getDB();

        $stmt = $db->prepare('INSERT INTO articles(name, description, user_id, published_date) VALUES (:name, :description, :user_id,:published_date)');

        $published_date =  new DateTime();
        $published_date = $published_date->format('Y-m-d');
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':published_date', $published_date);
        $stmt->bindParam(':user_id', $data['user_id']);

        $stmt->execute();

        return $db->lastInsertId(); //retourne l'id généré
    }

    /**
    *Associe une image à l'article
     **/
    public static function attachPicture($articleId, $pictureName){
        $db = static::getDB();

        $stmt = $db->prepare('UPDATE articles SET picture = :picture WHERE articles.id = :articleid');

        $stmt->bindParam(':picture', $pictureName);
        $stmt->bindParam(':articleid', $articleId);


        $stmt->execute();
    }

    /**
     * Supprime l'article d'ID donné de la base.(par id)
     */
    public static function delete($id)
    {
        $db = static::getDB();
        $sql = "DELETE FROM articles WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
    *Retourne l'accès brut à la place (hérité de model)
     **/
    public static function getDatabase() {
        return parent::getDB();
    }

}
