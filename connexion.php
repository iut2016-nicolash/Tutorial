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
$echec_connexion = " ";

if (isset($_POST['boutonValiderConnexion'])) {

    $sth = $bdd->prepare("SELECT * FROM utilisateurs WHERE email=:email AND mdp=:mdp");
    $sth->bindvalue(':email', $_POST['email'], PDO::PARAM_STR);
    $sth->bindvalue(':mdp', $_POST['mdp'], PDO::PARAM_STR);
    $sth->execute();

    $count = $sth->rowCount(); //=0 si rien trouvé ou =1 si une association login/mdp a été trouvée.

    if ($count == 1) { //Association login/mdp OK
        $tab_utilisateur = $sth->fetchAll(PDO::FETCH_ASSOC);

        $_SESSION['connexion'] = TRUE;
        $_SESSION['id_utilisateur'] = $tab_utilisateur['0']['id'];

        //***** Création du cookie *********************************************
        //On cré un cookie nommé "iut2016_nicolas_herbez"
        //Ou on réinitialise sa valeur s'il est déjà présent
        setcookie('iut2016_nicolas_herbez', $tab_utilisateur['0']['sid'], time() + $tab_utilisateur['0']['temps_cookie'], null, null, false, true);

        //********************************************* Création du cookie *****

        header("location: index.php");
        exit();
    } else {
        $echec_connexion = "Aucun utilisateur trouvé !";
    }
}

$smarty->assign("echec_connexion", $echec_connexion);

$smarty->display('connexion.tpl');
$smarty->display('bouton_annuler.tpl');

include_once 'includes/footer.inc.php';
