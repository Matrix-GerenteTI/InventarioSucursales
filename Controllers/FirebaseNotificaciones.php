<?php

require_once dirname(__FILE__)."/../vendor/autoload.php";

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use  Kreait\Firebase\Messaging\Notification;
use  Kreait\Firebase\Messaging\CloudMessage;
$serviceAccount = ServiceAccount::fromJsonFile( dirname(__FILE__)."/../notificacionesentradas-firebase-adminsdk-468ty-6c178f7b15.json");

$firebase = ( new Factory )
        ->withServiceAccount( $serviceAccount )
        ->create();

$mensajes = $firebase->getMessaging();

$creaNotificacion = Notification::fromArray([
        'title' => "Mensaje del Servidor",
        'body' => "...",
        'click_action' => "CONFIRMACION_ACTIVITY"
    ]);

$contendio = [
    'saludo' => "Hola",
    "cantidad" => "3"
];

$tokenDispositivo = "eToLGsQWXzA:APA91bGCZF55609WQVX6Mo6E7WfKmZHNaQYwLhLTYD_ITCol1G9fnclwEW5_hqKp4MgttN5UZz7eAkV3gheZ4M0LH8j3tSUxG8vwrVSsFD_qp3VK4_UiAGOvKvVNtZoRx7VOkPEBGmgS";
$mensaje  = CloudMessage::withTarget('token', $tokenDispositivo)
                ->withNotification( $creaNotificacion )
                ->withData( $contendio );

$mensajes->send( $mensaje );