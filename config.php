<?php



class config
{
    private static $pdo = null;

    // Twilio Configuration
  const TWILIO_SID = 'AC590b372a4ac402a6b2dbbb7feb0bd259';
  const TWILIO_TOKEN = '61b8d108e5415272e1a6b99670425a30';
  const TWILIO_FROM_NUMBER = '+18165754826';
  
  // Admin Configuration
  const ADMIN_FALLBACK_PHONE = '+21651981250'; 




  public static function getConnexion()

  {

    if (!isset(self::$pdo)) {

      try {

        self::$pdo = new PDO(

          'mysql:host=localhost;dbname=user',

          'root',

          '',

          [

            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC

          ]

        );

      } catch (Exception $e) {

        die('Erreur: ' . $e->getMessage());

      }

    }

    return self::$pdo;

  }

}