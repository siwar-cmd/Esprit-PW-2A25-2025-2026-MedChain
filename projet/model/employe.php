<?php

class Employe{



 private $nom;

 private $prenom;

 private $salaire;

 private $anneeEmbauche;



 public function setNom($nom){

$this->nom=$nom;

 }

 public function setPrenom($prenom){

$this->prenom=$prenom;

 }

 public function setSalaire($salaire){

$this->salaire=$salaire;

 }

 public function setAnneEmbauche($anneeEmbauche){

$this->anneeEmbauche=$anneeEmbauche;

 }



 public function getNom(){

  return $this->nom;

 }



 public function getPrenom(){

  return $this->prenom;

 }

 public function getSalaire(){

  return $this->salaire;

 }

 public function getAnneEmbauche(){

  return $this->anneeEmbauche;

 }









// public function __construct($nom,$prenom,$salaire,$anneeEmbauche){

// $this->nom=$nom;

// $this->prenom=$prenom;

// $this->salaire=$salaire;

// $this->anneeEmbauche=$anneeEmbauche;

// }

// function saisirInfor($nom,$prenom,$salaire,$anneeEmbauche){

// $this->nom=$nom;

// $this->prenom=$prenom;

// $this->salaire=$salaire;

// $this->anneeEmbauche=$anneeEmbauche;



// }

function afficherInfor(){

  echo "le nom est :"."<br>".$this->nom."<br>"."le prenom est:".$this->prenom."<br>"

  ."le salaire est:".$this->salaire."<br>".

  "l'année d'embauche est:".$this->anneeEmbauche."<br>";

 

}

public function __construct($nom,$prenom,$salaire,$anneeEmbauche){

$this->nom=$nom;

 $this->prenom=$prenom;

 $this->salaire=$salaire;

 $this->anneeEmbauche=$anneeEmbauche;

 

}

// public function __destruct(){

//  echo "l'objet a été détruit";

// }







}

class Manager extends Employe{



public $bonus;



public function __construct($nom,$prenom,$salaire,$anneeEmbauche,$bonus){

 parent::__construct($nom,$prenom,$salaire,$anneeEmbauche);

 $this->bonus=$bonus; 

}



public function afficherInfor(){

  parent::afficherInfor();

  echo "le bonus est ".$this->bonus;

}



public function calculSalaireFinale(){

  echo "le salaire finale est".($this->getSalaire()+$this->bonus);

}

  

}

























// class Manager extends Employe{

//  public $bonus;



//  public function __construct($nom,$prenom,$salaire,$anneeEmbauche,$bonus){

//    parent::__construct($nom,$prenom,$salaire,$anneeEmbauche);

//  $this->bonus=$bonus;

//  }



//  public function afficherInfor(){

//    parent::afficherInfor();

//    echo "le bonus est ".$this->bonus."<br>";

//  }

//  public function salairefinale(){

//    echo "le salaire finale est".($this->getSalaire()+$this->bonus);

//  }

//  public function __destruct(){

//    echo "l'objet a été bien détruit";

//  }

// }





// $employe1=new Employe("ben flen","flen",1000,2015);//objet

// // $employe1->saisirInfor("ben flen","flen",1000,2015);



// $employe1->afficherInfor();





// // echo $manager->getNom();



// echo $employe1->getNom();





// $manager=new Manager("ben salah","salah",2000,2020,700);

// $manager->setNom("Ahmedd");

// $manager->afficherInfor();

// $manager->calculSalaireFinale();







?>

Éditeur de texte de publication
