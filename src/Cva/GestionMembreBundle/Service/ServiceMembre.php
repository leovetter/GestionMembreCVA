<?php

namespace Cva\GestionMembreBundle\Service;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Cva\GestionMembreBundle\Form\EtudiantType;
use Cva\GestionMembreBundle\Entity\Etudiant;

class ServiceMembre {

	private $em;

	function __construct(\Doctrine\ORM\EntityManager $em) {
		
		 $this->em = $em;
	}

	
	public function GetAllEtudiant() {
	
		$repository = $this->em->getRepository('CvaGestionMembreBundle:Etudiant');
		$etudiants = $repository->findAll();
		return $etudiants;
	}

	public function GetAllProduitDispo() {
	
		$repository = $this->em->getRepository('CvaGestionMembreBundle:Produit');
	
		$query = $repository->createQueryBuilder('p')
	    ->where('p.disponibilite=:disponibilite')
	    ->setParameter('disponibilite', 'OUI')
	    ->getQuery();
		$produits = $query->getResult();

	return $produits;
	}
	
	public function GetPaiementEtudiant($idEtudiant) {	
		$repository = $this->em->getRepository('CvaGestionMembreBundle:Paiement');	
		return $repository->findBy(array('idEtudiant' => $idEtudiant));
	}
	
	
	public function GetEtudiant($name = "",$firstName = "",$numEtudiant = "",$mail = "", $idDebut = 0, $limiteScale = 15) {	
		//$repository = $this->getDoctrine()->getEntityManager();
		$recherche=array();

		return $this->em
			->createQuery('SELECT e FROM CvaGestionMembreBundle:Etudiant e WHERE e.name LIKE :name AND e.firstName LIKE :firstName AND e.numEtudiant LIKE :numEtudiant AND e.mail LIKE :mail AND e.id > :idDebut')
			->setParameter('name', $name.'%')
			->setParameter('firstName', $firstName.'%')
			->setParameter('numEtudiant', $numEtudiant.'%')
			->setParameter('mail', $mail.'%')
			->setParameter('idDebut', $idDebut)
			->setMaxResults($limiteScale)
			->getResult();
	}
	
	public function GetEtudiantById($id) {	
		$repository = $this->em->getRepository('CvaGestionMembreBundle:Etudiant');
		return $repository->findOneById($id);
	}
		
	public function GetUserById($id) {	
		$repository = $this->em->getRepository('CvaGestionMembreBundle:User');
		return $repository->findOneById($id);
	}
	
	public function GetProduitById($id) {	
		$repository = $this->em->getRepository('CvaGestionMembreBundle:Produit');
		return $repository->findOneById($id);
	}
	
	public function GetPaiementById($id) {	
		$repository = $this->em->getRepository('CvaGestionMembreBundle:Paiement');
		return $repository->findOneById($id);
	}

	public function GetEtudiantByProduit($idProd, $annee = null)
	{
		//On recupere les paiements des Etudiant ayant achete ce produit
		$repository = $this->em->getRepository('CvaGestionMembreBundle:Paiement');
		$query = $repository->createQueryBuilder('p') 
			->where(':idProd MEMBER OF p.produits')
			->setParameter('idProd', $idProd)
			->getQuery();
		$paiements = $query->getResult();
		
		
		
		// $em = $this->getDoctrine()->getManager();
		// $query = $em->createQuery('SELECT p.idEtudiant FROM Cva\GestionMembreBundle\Entity\Paiement p WHERE :idProd MEMBER OF p.produits');
		// $query->setParameter('idProd', $idProd);
		// $ids = $query->getResult();
		
		//On recupere les etudiants associés
		$etudiant=array();

		foreach ($paiements as &$id) {
			$etud=$this->GetEtudiantById($id->getIdEtudiant());
			if($annee==null)
			{
				$etudiant[]=$etud;
			}
			elseif($etud->getAnnee()==$annee)
			{
				$etudiant[]=$etud;
			}
		}
		return $etudiant;
	
	}

	public function EtudiantAlreadyGotProduct($idEtudiant, $produit)
	{
		$paiements = $this->GetPaiementEtudiant($idEtudiant);
		foreach( $paiements as &$paie )
		{
			if((in_array($produit, $paie->getProduits()->toArray())))
			{
				return true;
			}
		}
		return false;
	}
	
	public function GetEtudiantByAnnee($annee)
	{
		$repository = $this->em->getRepository('CvaGestionMembreBundle:Etudiant');
		return $repository->findByAnnee($annee);	
	}
	
	public function GetDetailsByIdEtudiant($idEtudiant)
	{
		$repository = $this->em->getRepository('CvaGestionMembreBundle:DetailsWEI');
		return $repository->findOneBy(array('idEtudiant' => $idEtudiant));
	}
	
	public function GetBizuthWEIAvecDetails($prod = null)
	{
		if ($prod==null)
		{
			$bizuths = $this->GetEtudiantByAnnee(1);
		}
		else
		{
			$bizuths = $this->GetEtudiantByProduit($prod,1);
		}
		$details=array();
		$repository = $this->em->getRepository('CvaGestionMembreBundle:DetailsWEI');
		
		foreach ($bizuths as &$biz)
		{
			//On récupère le bus et le bung du bizuth
			$bus="";
			$bungalow="";
			$allproducts=array();
			if($this->GetDetailsByIdEtudiant($biz->getId())<>null)
			{
				$bus= $repository->findOneByIdEtudiant($biz)->GetBus();
				$bungalow= $repository->findOneByIdEtudiant($biz)->GetBungalow();
			}
			
			//On récupère les produits achetés par le bizuth
			
			$paiements=$this->GetPaiementEtudiant($biz->getId());

			if($paiements<>null)
			{
				foreach ($paiements as &$paie)
				{
					$allproducts[]=$paie->getProduits();
				}
			}
			
			//On met tout dans le tableau
			$details[]=array("bizuth" => $biz,"bus" => $bus, "bung" => $bungalow, "prods" => $allproducts);
		}
		return $details;
	}
	
	
	
}
