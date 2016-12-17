<?php

session_start(); //pour transporter les données de variables de pages en pages,
//car toutes les données sont supprimées en fin de script (fin de page)

require_once ("libs/Smarty.class.php");
require_once ("settings/bdd.inc.php");
require_once ("fonctions/fonctions.inc.php");

include_once 'includes/header.inc.php';

$smarty = new Smarty();

$smarty->setTemplateDir('templates/');
$smarty->setCompileDir('templates_c/');
//$smarty->setConfigDir('/web/www.example.com/guestbook/configs/');
//$smarty->setCacheDir('/web/www.example.com/guestbook/cache/');
//
//
//***** Mode debug Smarty ******************************************************
//$smarty->debugging = true;
//****************************************************** Mode debug Smarty *****
//
//
//
//la structure if (en cas d'actions la page article)
//la structure else (lorsqu'on arrive sur la page article)
//
//***** Structure if ***********************************************************
if (isset($_POST['Ajouter']) OR isset($_POST['Modifier'])) {

    $date_ajout = date("Y-m-d"); //on enregistre la date du système dans une variable
    $_POST['date'] = $date_ajout; //on ajoute la date à la liste de POST
    //
    //
    //si la case est cochée on met publie à 1
    //condition simple
    if (isset($_POST['publie'])) { //isset = est-ce qu'il existe?
        $_POST['publie'] = 1;
    } else {
        $_POST['publie'] = 0;
    }
    //ou condition ternaire (écriture plus simple)
    //$_POST['publie'] = isset($_POST['publie']) ? 1 : 0;


    if ($_FILES['image']['error'] == 0) { //obligation de mettre une image
        if (isset($_POST['Ajouter'])) {
            $id_utilisateur = $_POST['id_utilisateur'];
            $sth = $bdd->prepare("INSERT INTO articles (titre, texte, publie, date, id_utilisateur) VALUES (:titre, :texte, :publie, :date, :id_utilisateur)"); //préparation de la requête.
            $sth->bindvalue(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        } elseif (isset($_POST['Modifier'])) {
            $sth = $bdd->prepare("UPDATE articles SET titre = :titre, texte = :texte, publie = :publie, date = :date WHERE id=:id_article");
            $sth->bindvalue(':id_article', $_POST['id_article'], PDO::PARAM_INT); //la variable :id ne prendra qu'un nbre
        }

        //on prépare les variables et on les sécurises
        $sth->bindvalue(':titre', $_POST['titre'], PDO::PARAM_STR); //la variable :titre ne prendra qu'une chaîne de carac
        $sth->bindvalue(':texte', $_POST['texte'], PDO::PARAM_STR); //la variable :texte ne prendra qu'une chaîne de carac
        $sth->bindvalue(':publie', $_POST['publie'], PDO::PARAM_INT); //la variable :publie ne prendra qu'un nbre
        $sth->bindvalue(':date', $_POST['date'], PDO::PARAM_STR); //la variable :date ne prendra qu'une chaîne de carac

        $sth->execute();

        //***** Mode debug ******
        //print_r($_POST);
        //***********************
        //
        //***** Nom de l'image *************************************************
        //L'image portera le nom de l'Id de l'article
        if (isset($_POST['Ajouter'])) {
            $id = $bdd->lastInsertId(); //ici le dernier Id de la table
        } elseif (isset($_POST['Modifier'])) {
            $id = $_POST['id'];
        }

        move_uploaded_file($_FILES['image']['tmp_name'], dirname(__FILE__) . "/img/$id.jpg");

        //************************************************* Nom de l'image *****
        //
        //***** Sessions pour notifications *****
        if (isset($_POST['Ajouter'])) {
            $_SESSION['ajout_article'] = TRUE;
        } elseif (isset($_POST['Modifier'])) {
            $_SESSION['modifier_article'] = TRUE;
        }
        //***** Sessions pour notifications *****
        //
        //redirection vers une autre page
        header("location: index.php");
        exit();
    } else {
        echo "Erreur de chargement de l'image!";
        exit();
    }
}
//*********************************************************** Structure if *****
//
//
//
//***** Structure else *********************************************************
else {
    $id_article = NULL;
    $titre = "";
    $texte = "";
    $bouton = "Ajouter";
    $id_utilisateur = NULL;

    if (isset($_GET['modifier'])) {
        $id_article = $_GET['modifier'];
        $sth = $bdd->prepare("SELECT id, titre, texte, DATE_FORMAT(date, '%d,%M,%Y') as date_fr 
								FROM articles 
								WHERE id = :id_article");
        $sth->bindvalue(':id_article', $id_article, PDO::PARAM_INT);
        $sth->execute();

        $tab_articles = $sth->fetchAll(PDO::FETCH_ASSOC);

        $titre = ($tab_articles[0]['titre']);
        $texte = ($tab_articles[0]['texte']);
        //récupérer l'image ?
        $bouton = "Modifier";

        //***** Mode debug ******
        //print_r($tab_articles);
        //***********************
    }if (isset($_GET['rediger'])) { //transporte l'id_utilisateur
        $id_utilisateur = $_GET['rediger'];
    }

    //***** Variables *******************
    $smarty->assign(array(
        "id_article" => $id_article,
        "titre" => $titre,
        "texte" => $texte,
        "bouton" => $bouton,
        "id_utilisateur" => $id_utilisateur
    ));
    //******************* Variables *****


    $smarty->display('article.tpl');
    $smarty->display('bouton_annuler.tpl');
}
//********************************************************* Structure else *****

include_once 'includes/footer.inc.php';
