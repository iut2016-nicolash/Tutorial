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
//***** Cookie *****************************************************************
if (isset($_COOKIE['iut2016_nicolas_herbez'])) { //Si le cookie est valide (si le temps n'est pas encore écoulé)
    $sth = $bdd->prepare("SELECT id, email, sid, temps_cookie FROM utilisateurs");
    $sth->execute();

    $tab_sid = $sth->fetchALL(PDO::FETCH_ASSOC);

    foreach ($tab_sid as $value) { //On compare chaque cookie de la bdd
        $_SESSION['id_utilisateur'] = $value['id'];
        $email = $value['email'];
        $sid = $value['sid'];
        $temps_cookie = $value['temps_cookie'];

        if ($sid == $_COOKIE['iut2016_nicolas_herbez']) {
            //Le cookie est présent et sa valeur appartient à un utilisateur
            $cookie_valide = TRUE;
            break; //On sort de la boucle, $cookie_valide reste à TRUE
        } else {
            //Le cookie est présent mais sa valeur n'appartient à aucun utilisateur
            $cookie_valide = FALSE;
        }
    }
    if ($cookie_valide == TRUE) { //On a une concordance
        //Connexion automatique
        $_SESSION['connexion'] = TRUE;

        //***** Création du cookie *********************************************
        //On cré un cookie nommé "iut2016_nicolas_herbez"
        //Ou on réinitialise sa valeur s'il est déjà présent
        setcookie('iut2016_nicolas_herbez', $sid, time() + $temps_cookie, null, null, false, true);

        //********************************************* Création du cookie *****
    } else {
        $_SESSION['usurpation'] = TRUE;
    }
    //***** Mode debug ******
    //print_r($tab_sid);
    //print_r($_SESSION['id_utilisateur']);
    //***********************
} elseif (!isset($_COOKIE['iut2016_nicolas_herbez']) AND
        isset($_SESSION['connexion'])) { // Le cookie a expiré pendant une connexion
    unset($_SESSION['connexion']);
    $_SESSION['expiration_connexion'] = TRUE;
}
//***************************************************************** Cookie *****
//
//
//***** Notifications **********************************************************
$session = "Bienvenue sur mon blog !";
$connexion = "Connexion";
$redirection = "connexion.php"; //En mode non connecté
$lien_articles_non_publie = NULL;

if (isset($_SESSION['usurpation'])) { //si présente on affiche un message
    echo "<div style= text-align:center;><h2>Le piratage est passible de prison et d'amendes !</h2></div>";
    unset($_SESSION['usurpation']);
    exit();
}if (isset($_SESSION['ajout_article'])) { //si présente on affiche un message
    $session = "<strong>Félicitation, </strong>votre article a bien été ajouté !";
}if (isset($_SESSION['modifier_article'])) {
    $session = "<strong>Félicitation, </strong>votre article a bien été modifié !";
}if (isset($_SESSION['supprimer_OK'])) {
    $session = "<strong>Félicitation, </strong>votre article a bien été supprimé !";
}if (isset($_SESSION['connexion'])) {
    $id_utilisateur = $_SESSION['id_utilisateur'];

    //Enregistre nom, prénom et email utilisateur dans un tableau : $tab_utilisateur
    $sth = $bdd->prepare("SELECT nom, prenom, email FROM utilisateurs WHERE id = :id");
    $sth->bindvalue(':id', $id_utilisateur, PDO::PARAM_INT);
    $sth->execute();

    $tab_utilisateur = $sth->fetchAll(PDO::FETCH_ASSOC);
    //
    //***** Mode debug **********
    //echo "Id utilisateur = " . $id_utilisateur . "<br>";
    //print_r($tab_utilisateur);
    //***************************

    $nom = $tab_utilisateur['0']['nom'];
    $prenom = $tab_utilisateur['0']['prenom'];
    $email = $tab_utilisateur['0']['email'];

    $session = "Bonjour, <strong>" . $prenom . " " . $nom . "</strong>";
    $connexion = "Déconnecter";
    $redirection = "index.php?deconnecter";
    $lien_articles_non_publie = '<li><a href="index.php?publiesNOK=' . $id_utilisateur . '">Mes articles non publiés</a></li>';
}if (isset($_SESSION['expiration_connexion'])) {
    $session = "<strong>Votre session a expiré, veuillez vous connecter !</strong>";
}if (isset($_SESSION['nouveau_compte'])) {
    $session = "<strong>Félicitation, </strong>votre compte a bien été créé !";
}if (isset($_SESSION['deconnection'])) {
    $session = "Merci, à bientôt !";
}if (isset($_SESSION['demande_connexion'])) {
    $session = "<div style='color:red;'><strong>Vous devez être connecté pour tout "
            . "ajout, modification ou suppression !</strong></div>";
}

$smarty->assign("session", $session);
$smarty->assign("connexion", $connexion);
$smarty->assign("redirection", $redirection);
$smarty->assign("lien_articles_non_publie", $lien_articles_non_publie);

unset($_SESSION['ajout_article']); //on détruit les sessions
unset($_SESSION['modifier_article']);
unset($_SESSION['supprimer_OK']);
unset($_SESSION['nouveau_compte']);
unset($_SESSION['deconnection']);
unset($_SESSION['demande_connexion']);
unset($_SESSION['expiration_connexion']);
unset($_SESSION['id_utilisateur']);

$smarty->display('notifications.tpl');
//********************************************************** Notifications *****

$smarty->display('menu.tpl');

//***** Se déconnecter *********************************************************
if (isset($_GET['deconnecter'])) {
    unset($_SESSION['connexion']);
    $_SESSION['deconnection'] = TRUE;

    //Supprime le cookie (expiration = 0)
    if (isset($_COOKIE['iut2016_nicolas_herbez'])) {
        setcookie('iut2016_nicolas_herbez', $sid, time(), null, null, false, true);
    }

    //***** On génère un nouveau sid *******************************************
    //
    //On génère une valeur aléatoire basé sur l'heure actuelle et l'email
    $sid = md5(time() . $email);

    //Insertion du sid dans la table
    $sth = $bdd->prepare("UPDATE utilisateurs SET sid = :sid WHERE id = :id");
    $sth->bindvalue(':sid', $sid, PDO::PARAM_STR);
    $sth->bindvalue(':id', $id_utilisateur, PDO::PARAM_INT);
    $sth->execute();
    //******************************************* On génère un nouveau sid *****

    header("location: index.php");
    exit();
}
//********************************************************* Se déconnecter *****
//
//
//***** Ajouter un commentaire *************************************************
if (isset($_POST['Ajouter'])) { //Ajoute le commentaire
    $id_article = $_POST['id'];

    if (isset($_SESSION['connexion'])) {
        $date_ajout = date("Y-m-d"); //on enregistre la date du système dans une variable
        $_POST['date'] = $date_ajout; //on ajoute la date à la liste de POST

        $sth = $bdd->prepare("INSERT INTO commentaires (id_article, commentaire, date, id_utilisateur) "
                . "VALUES (:id_article, :commentaire, :date, :id_utilisateur)");
        $sth->bindvalue(':id_article', $id_article, PDO::PARAM_INT);
        $sth->bindvalue(':commentaire', $_POST['commentaire'], PDO::PARAM_STR);
        $sth->bindvalue(':date', $_POST['date'], PDO::PARAM_INT);
        $sth->bindvalue(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        $sth->execute();

        header("location: index.php?commentaire=$id_article");
        exit();
    } else {
        $_SESSION['demande_connexion'] = TRUE;

        header("location: index.php?commentaire=$id_article");
        exit();
    }
}
//************************************************* Ajouter un commentaire *****
//
//
//La structure if (actions sur la page index)
//La structure else (affiche tous les articles publiés)
//***** Structure if ***********************************************************
if (isset($_GET['rediger']) OR isset($_GET['modifier'])
        OR isset($_GET['supprimer']) OR isset($_GET['commentaire'])) {

    if (isset($_GET['rediger'])) {
        if (isset($_SESSION['connexion'])) {

            header("location: article.php?rediger='$id_utilisateur'");
            exit();
        } else {
            $_SESSION['demande_connexion'] = TRUE;

            header("location: index.php");
            exit();
        }
    }

    if (isset($_GET['modifier'])) {
        $id = $_GET['modifier'];
    }
    if (isset($_GET['supprimer'])) {
        $id = $_GET['supprimer'];
    }
    if (isset($_GET['commentaire'])) {
        $id = $_GET['commentaire'];
    }
    //***** Affiche l'article ciblé ********************************************
    //Enregistre l'article dans un tableau : $tab_article
    $sth = $bdd->prepare("SELECT id, titre, texte, DATE_FORMAT(date, '%d,%M,%Y') 
                        as date_fr, id_utilisateur FROM articles WHERE id = :id");

    $sth->bindvalue(':id', $id, PDO::PARAM_INT); //prépare et sécurise la variable
    //ici la variable :id ne prendra qu'un entier int

    $sth->execute();
    $tab_article = $sth->fetchAll(PDO::FETCH_ASSOC);
    //
    //***** Mode debug ******
    //print_r($tab_articles);
    //***********************

    $id = $tab_article['0']['id'];
    $titre = $tab_article['0']['titre'];
    $texte = $tab_article['0']['texte'];
    $date = $tab_article['0']['date_fr'];
    $id_utilisateur = $tab_article['0']['id_utilisateur'];

    $sth = $bdd->prepare("SELECT nom, prenom FROM utilisateurs WHERE id = :id");
    $sth->bindvalue(':id', $id_utilisateur, PDO::PARAM_INT);
    $sth->execute();

    $tab_utilisateur = $sth->fetchALL(PDO::FETCH_ASSOC);

    $nom = $tab_utilisateur['0']['nom'];
    $prenom = $tab_utilisateur['0']['prenom'];

    //***** Variables tpl **********
    $smarty->assign(array(
        "id" => $id,
        "titre" => $titre,
        "texte" => $texte,
        "date" => $date,
        "nom" => $nom,
        "prenom" => $prenom
    ));
    //********** Variables tpl *****
    //******************************************** Affiche l'article ciblé *****

    if (isset($_GET['modifier'])) { //Modifier l'article
        if (isset($_SESSION['connexion'])) {

            header("location: article.php?modifier={$id}");
            exit();
        } else {
            $_SESSION['demande_connexion'] = TRUE;

            header("location: index.php");
            exit();
        }
    }

    if (isset($_GET['supprimer'])) { //Confirmation de suppression de l'article
        $smarty->display('supprimer.tpl');
    }

    //***** Affiche les commentaires *******************************************
    if (isset($_GET['commentaire'])) {

        $smarty->display('article_select.tpl'); //affiche l'article sélectionné
        //
        //Enregistre tous les commentaires dans un tableau : $tab_commentaires
        $sth = $bdd->prepare("SELECT id, id_article, commentaire, DATE_FORMAT(date, '%d,%M,%Y') 
                        as date_fr, id_utilisateur FROM commentaires WHERE id_article = :id ORDER BY id DESC");
        $sth->bindvalue(':id', $id, PDO::PARAM_INT);
        $sth->execute();

        $tab_commentaires = $sth->fetchAll(PDO::FETCH_ASSOC);
        //
        //***** Mode debug **********
        //print_r($tab_commentaires);
        //***************************
        //
        //Une boucle "foreach" récupère tous les commentaires liés à l'article
        foreach ($tab_commentaires as $value) {
            $id_com = $value['id'];

            $sth = $bdd->prepare("SELECT nom, prenom FROM utilisateurs WHERE id = :id_utilisateur");
            $sth->bindvalue(':id_utilisateur', $value['id_utilisateur'], PDO::PARAM_INT);
            $sth->execute();

            $tab_nom_prenom = $sth->fetchAll(PDO::FETCH_ASSOC);

            $prenom = $tab_nom_prenom['0']['prenom'];
            $nom = $tab_nom_prenom['0']['nom'];
            $id_article = $value['id_article'];
            $commentaire = $value['commentaire'];
            $date = $value['date_fr'];
            //
            //
            //***** Variables *******************
            $smarty->assign(array(
                "id_com" => $id_com,
                "nom" => $nom,
                "prenom" => $prenom,
                "id_article" => $id_article,
                "commentaire" => $commentaire,
                "date" => $date
            ));
            //******************* Variables *****

            $smarty->display('commentaires.tpl');
        }

        $smarty->display('commentaires_ajout.tpl');
    }
    //******************************************* Affiche les commentaires *****
//
//
//
} elseif (isset($_GET['oui_YyxSx0Ae2k'])) { //suppression de l'article
    $id_sup = $_GET['oui_YyxSx0Ae2k'];

    if (isset($_SESSION['connexion'])) {
        $sth = $bdd->prepare("DELETE FROM articles WHERE id = :id");
        $sth->bindvalue(':id', $id_sup, PDO::PARAM_INT);
        $sth->execute();

        $sth = $bdd->prepare("DELETE FROM commentaires WHERE id_article = :id");
        $sth->bindvalue(':id', $id_sup, PDO::PARAM_INT);
        $sth->execute();

        $_SESSION['supprimer_OK'] = TRUE;

        unlink("img/$id_sup.jpg"); //supprime l'image associée à l'article

        header("location: index.php");
        exit();
    } else {
        $_SESSION['demande_connexion'] = TRUE;

        header("location: index.php?supprimer=$id_sup");
        exit();
    }
}
//********************************************************************* if *****
//
//
//***** Structure else *********************************************************
//Structure if (affiche les articles suite à une recherche)
//Structure else (affiche tous les articles présent dans la base)
else {
    //***** Calcul pagination **************************************************

    $sql = $bdd->prepare("SELECT COUNT(*) as nbArticles FROM articles WHERE publie = 1");
    $sql->execute();

    $tabArticles = $sql->fetchAll(PDO::FETCH_ASSOC);
    $totalArticles = $tabArticles[0]['nbArticles'];
    $nbreArticleParPage = 2;

    if (isset($_GET['numPage'])) {
        $numPage = $_GET['numPage'];
    } else {
        $numPage = 1;
    }
    $nbrePages = ceil($totalArticles / $nbreArticleParPage);

    $indexDepart = returnIndex($numPage, $nbreArticleParPage); //fonction returnIndex
    //************************************************** Calcul pagination *****
    //
    //
    //***** $tab_articles ******************************************************
    //On enregistre les articles dans tab_articles
    if (isset($_GET['recherche'])) {
        $recherche = $_GET['recherche'];

        $sth = $bdd->prepare("SELECT id, titre, texte, DATE_FORMAT(date, '%d,%M,%Y') 
                        as date_fr, id_utilisateur FROM articles WHERE (titre LIKE :recherche OR texte LIKE :recherche) 
                        AND publie = 1 ORDER BY id DESC");
        $sth->bindvalue(':recherche', "%$recherche%", PDO::PARAM_STR);
    } else {
        if (isset($_GET['publiesNOK'])) { //Récupère tous les articles non publiés de la base
            $sth = $bdd->prepare("SELECT id, titre, texte, DATE_FORMAT(date, '%d,%M,%Y') 
                        as date_fr, id_utilisateur FROM articles WHERE publie = 0 ORDER BY id DESC");
        } else { //Récupère tous les articles publiés de la base
            $sth = $bdd->prepare("SELECT id, titre, texte, DATE_FORMAT(date, '%d,%M,%Y') 
                        as date_fr, id_utilisateur FROM articles WHERE publie = 1  ORDER BY id DESC
                        LIMIT $indexDepart, $nbreArticleParPage");
        }
    }

    $sth->execute();

    $tab_articles = $sth->fetchAll(PDO::FETCH_ASSOC);
    //
    //***** Mode debug ******
    //print_r($tab_articles);
    //***********************
    //****************************************************** $tab_articles *****
    //
    //
    //***** Si $tab_articles est vide ******************************************
    if (empty($tab_articles)) {
        if (isset($_GET['recherche'])) {
            echo "<h3>Aucun résultat trouvé !</h3>";
        } else {
            if (isset($_GET['publiesNOK'])) {
                echo "<h3>Vous n'avez aucun article non publié !</h3>";
            } else {
                echo "<h2>Aucun article</h2><br/><h3>Soyez le premier à rédiger un article !</h3>";
            }
        }
    }
    //****************************************** Si $tab_articles est vide *****
    else {
        if (isset($_GET['recherche'])) {
            echo "<h4>Résultat(s) trouvé(s) :</h4>";
        } elseif (isset($_GET['publiesNOK'])) {
            echo "<h4>Vos articles non publié :</h4>";
        }
    }
    //
    //
    //***** Affichage des articles publiés *************************************
    //Une boucle "foreach" récupère tous les champs de chaque article
    foreach ($tab_articles as $value) {
        $id = $value['id'];
        $titre = $value['titre'];
        $texte = $value['texte'];
        $date = $value['date_fr'];
        $id_utilisateur = $value['id_utilisateur'];
        //
        //
        $sth = $bdd->prepare("SELECT nom, prenom FROM utilisateurs WHERE id = :id");
        $sth->bindvalue(':id', $id_utilisateur, PDO::PARAM_INT);
        $sth->execute();

        $tab_utilisateur = $sth->fetchALL(PDO::FETCH_ASSOC);

        $nom = $tab_utilisateur['0']['nom'];
        $prenom = $tab_utilisateur['0']['prenom'];

        //***** Variables tpl **************
        $smarty->assign(array(
            "id" => $id,
            "titre" => $titre,
            "texte" => $texte,
            "date" => $date,
            "nom" => $nom,
            "prenom" => $prenom
        ));
        //************** Variables tpl *****

        $smarty->display('index.tpl');
    }
    //************************************* Affichage des articles publiés *****
    //
    //
    //***** Affichage pagination ***********************************************
    if (isset($_GET['recherche']) OR isset($_GET['publiesNOK'])) {
        //pagination désactivée pour recherche et non publiés
    } else {
        echo '<div class="pagination"><ul>';

        for ($i = 1; $i <= $nbrePages; $i++) {
            if ($numPage == $i) {
                $ClassBouton = 'active';
            } else {
                $ClassBouton = '';
            }
            echo '<li class=' . $ClassBouton . '> <a href="index.php?numPage=' . $i . '">' . $i . '</a> </li>';
        }

        echo '</ul></div>';
        //*********************************************** Affichage pagination *****
    }
}
//******************************************************************* else *****

include_once 'includes/footer.inc.php';
