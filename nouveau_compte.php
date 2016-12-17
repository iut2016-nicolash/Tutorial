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
//***** Variables **
$nom_saisi = NULL;
$prenom_saisi = NULL;
$email_saisi = NULL;
$mdp_saisi = NULL;
$alert_email = "Email :";
//** Variables *****
//
//
if (isset($_POST['boutonValider'])) { //on valide la création d'un compte
    //
    //***** On récupère le contenu de tous les champs **************************
    $nom_saisi = $_POST['nom'];
    $prenom_saisi = $_POST['prenom'];
    $email_saisi = $_POST['email'];
    $mdp_saisi = $_POST['mdp'];
    //************************** On récupère le contenu de tous les champs *****
    //
    //
    //***** Vérifie la validité de l'email *************************************

    $sth = $bdd->prepare("SELECT email FROM utilisateurs");
    $sth->execute();

    $tab_email = $sth->fetchAll(PDO::FETCH_ASSOC); //contient tous les email de la bdd

    foreach ($tab_email as $value) { //on compare les emails 1 par 1
        $email = $value['email'];

        if ($email == $email_saisi) { //si l'email est déjà dans la bdd
            $email_valide = FALSE;
            break; //on arrête de boucler et $email reste à FALSE
        } else { //Si email n'est pas encore utilisé on peut créer le compte
            $email_valide = TRUE;
        }
    }
    //***** Mode debug ***
    //print_r($tab_email);
    //********************
    //************************************* Vérifie la validité de l'email *****

    if ($email_valide == TRUE) { //Création du compte
        //
        //***** Création du sid (pour les cookies) *****************************
        //On génère une valeur aléatoire basé sur l'heure actuelle et l'email
        $sid = md5(time() . $email);

        //************************************************ Création du sid *****
        //
        //***** Création du cookie *********************************************
        $temps_cookie = 10; //Temps par défaut du cookie (en sec)
        //On cré un cookie nommé "iut2016_nicolas_herbez"
        //Ou on réinitialise sa valeur s'il est déjà présent
        setcookie('iut2016_nicolas_herbez', $sid, time() + $temps_cookie, null, null, false, true);

        //********************************************* Création du cookie *****

        $sth = $bdd->prepare("INSERT INTO utilisateurs (nom, prenom, email, mdp, sid, temps_cookie) "
                . "VALUES (:nom, :prenom, :email, :mdp, :sid, :temps_cookie)");
        $sth->bindvalue(':nom', $nom_saisi, PDO::PARAM_STR);
        $sth->bindvalue(':prenom', $prenom_saisi, PDO::PARAM_STR);
        $sth->bindvalue(':email', $email_saisi, PDO::PARAM_STR);
        $sth->bindvalue(':mdp', $mdp_saisi, PDO::PARAM_STR);
        $sth->bindvalue(':sid', $sid, PDO::PARAM_STR);
        $sth->bindvalue(':temps_cookie', $temps_cookie, PDO::PARAM_INT);
        $sth->execute();

        $_SESSION['connexion'] = TRUE;
        $_SESSION['nouveau_compte'] = TRUE;
        $_SESSION['id_utilisateur'] = $bdd->lastInsertId();

        //Quelques sec d'attente pour la création du cookie et
        //pour que le sid soit bien enregistré dans la base
        sleep(3);

        header("location: index.php");
        exit();
    }
    //
    else { //Notification email déjà existant
        $alert_email = "<div style= color:red;>Email déjà existant !</div>";
    }
}

$smarty->assign(array(
    "nom" => $nom_saisi,
    "prenom" => $prenom_saisi,
    "email" => $email_saisi,
    "mdp" => $mdp_saisi,
    "alert_email" => $alert_email
));

$smarty->display('nouveau_compte.tpl');
$smarty->display('bouton_annuler.tpl');

include_once 'includes/footer.inc.php';
