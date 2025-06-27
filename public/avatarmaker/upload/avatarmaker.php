<?php
/**
 * AvatarMaker 3.x By InochiTeam
 *
 * @updated       17/10/2018 (3.2.1 Elsie)
 */
session_start(); // Démarrer la session PHP
require_once("avatarmaker.class.php");


$avatarMaker = new HT_AvatarMaker();


$userId = $_SESSION['_sf2_attributes']['avatar_user_id'] ?? null;

if (!$userId) {
    die('Erreur : Aucun utilisateur trouvé.');
}

// Construire le bon chemin absolu pour enregistrer l'avatar
$saveFolder = realpath(__DIR__ . '/../../images/avatars') . '/';

$avatarName = "avatar_" . $userId . ".png"; // Renommer en fonction de l'utilisateur

// Vérifier si le dossier existe, sinon le créer
if (!is_dir($saveFolder)) {
    mkdir($saveFolder, 0775, true);
}



/*
 * Send the configuration file in case of a GET request
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if( isset( $_GET['md5'] ) )
    {
        $avatarMaker->setAvatarMd5( $_GET['md5'] );
        $avatarMaker->renderAvatar($saveFolder . $avatarName );
        die();
    }

    if( isset( $_GET['avm_items'] ) )
    {
        $avatarMaker->setAvatarGET();
        $avatarMaker->renderAvatar( $saveFolder . $avatarName );
        die();
    }

	$avatarMaker->outAppConfig( );
}


/*
 * Load the avatar structure sent as json payload and render the avatar accordingly
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $avatarMaker->setAvatar( );
    $avatarMaker->renderAvatar( $saveFolder. $avatarName);
}
