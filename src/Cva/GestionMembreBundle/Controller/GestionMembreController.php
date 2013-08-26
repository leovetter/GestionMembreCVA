<?php

namespace Cva\GestionMembreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Cva\GestionMembreBundle\Form\EtudiantType;
use Cva\GestionMembreBundle\Form\resetPasswordType;
use Cva\GestionMembreBundle\Form\ConnexionType;
use Cva\GestionMembreBundle\Form\ProduitType;
use Cva\GestionMembreBundle\Form\PaiementType;
use Cva\GestionMembreBundle\Form\UserType;
use Cva\GestionMembreBundle\Form\DetailsWEIType;
use Cva\GestionMembreBundle\Entity\Etudiant;
use Cva\GestionMembreBundle\Entity\Produit;
use Cva\GestionMembreBundle\Entity\Paiement;
use Cva\GestionMembreBundle\Entity\User;
use Cva\GestionMembreBundle\Entity\DetailsWEI;
use Symfony\Component\HttpFoundation\Request;
use \DateTime;

class GestionMembreController extends Controller
{
	//La redirection depuis /
	public function cacaAction(Request $request)
	{
		return $this->redirect($this->generateUrl('cva_gestion_membre_adherent'));
	}

	public function exportCSVAction(Request $request)
	{
		$response = new Response();
		$response->setContent($request->request->get('csvText'));
		$response->headers->set('Content-Type','application/force-download');
		$response->headers->set('Content-disposition','filename="export.csv"');
		
		return $response;
	}

	public function profilAction(Request $request)
    	{
		$user = $this->get('security.context')->getToken()->getUser();
		$form = $this->createForm(new resetPasswordType());
		if($request->isMethod('POST'))
		{
			$form->bind($request);

			if ($form->isValid()) 
			{	
				$factory = $this->get('security.encoder_factory');
				$encoder = $factory->getEncoder($user);
				$oldPassword = $encoder->encodePassword($form->get('oldPassword')->getData() , $user->getSalt());
				$em = $this->getDoctrine()->getManager();

				if(is_null($form->get('oldPassword')->getData()))
				{
					$user->setUsername($form->get('username')->getData());
					$em->persist($user);
					$em->flush();
					$this->get('session')->getFlashBag()->add('notice', 'Profil modifie');
				}
				else if($oldPassword == $user->getPassword())
				{
					$user->setUsername($form->get('username')->getData());
					$newPassword = $encoder->encodePassword($form->get('newPassword')->getData() , $user->getSalt());
					$user->setPassword($newPassword);
					$em->persist($user);
					$em->flush();
					$this->get('session')->getFlashBag()->add('notice', 'Profil modifie');
				}
				else if ($oldPassword !== $user->getPassword())
				{
					$this->get('session')->getFlashBag()->add('warning', 'Le mot de passe renseigne ne correspond pas a votre mot de passe');
					return $this->render('CvaGestionMembreBundle::profil.html.twig', array('form' => $form->createView(),));
				}		
				
			}
		}
		
		if (!$form->isBound())
		{
			$form->setData(array('username' => $user->getUsername()));
		}
		
		return $this->render('CvaGestionMembreBundle::profil.html.twig', array('form' => $form->createView(),));
    	}

	//Adherents
	
	 public function ajoutAdherentAction(Request $request)
    	{
		$em = $this->getDoctrine()->getManager();
		$etudiant = new Etudiant();
		$form = $this->createForm(new EtudiantType(), $etudiant);

		if($request->isMethod('POST'))
		{
			$form->bind($request);

			if ($form->isValid()) 
			{
				$em->persist($etudiant);
				$em->flush();
				$this->get('session')->getFlashBag()->add('notice', 'Etudiant ajoutÃ©');
				return $this->redirect('paiement?id=' . $etudiant->getId());
			}
		}

		return $this->render('CvaGestionMembreBundle::AjoutAdherent.html.twig', array('form' => $form->createView(),));
	}

	public function paiementAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$produits = $this->get('cva_gestion_membre')->GetAllProduitDispo();
		$paiement = new Paiement();
		$paiementType = new PaiementType($produits);
		$form = $this->createForm($paiementType, $paiement);

		if($request->isMethod('POST'))
		{
			$form->bind($request);

			if ($form->isValid()) 
			{

				if(sizeof($form->get('Produits')->getData()) == 0) {

					$this->get('session')->getFlashBag()->add('warning', 'Vous devez choisir au moins un produit');
					return $this->redirect($this->generateUrl('cva_gestion_membre_editPaiement', array('id' => $request->request->get('id'))));
				} 
				foreach($produits as $prod)
				{
					foreach($form->get('Produits')->getData() as $desc)
					{
						if (strcmp($desc, $prod->getDescription()) == 0)
						{
							//On vérifie que l'étudiant ne possède pas déjà le produit
							if($this->get('cva_gestion_membre')->EtudiantAlreadyGotProduct($request->request->get('id'),$prod)==true)
							{
								$this->get('session')->getFlashBag()->add('warning', 'Cet etudiant possede deja ce produit');
								return $this->redirect($this->generateUrl('cva_gestion_membre_editPaiement', array('id' => $request->request->get('id'))));
							}
							$paiement->addProduit($prod);
						}
					}
				}
				$etudiant = $this->get('cva_gestion_membre')->GetEtudiantById($request->request->get('id'));
				$paiement->setIdEtudiant($etudiant);
				$paiement->setUpdated();
				$em->persist($paiement);
				$em->flush();
				$this->get('session')->getFlashBag()->add('notice', 'Paiement effectuÃ©');
				if($request->request->get('from'))
					return $this->redirect($this->generateUrl('cva_gestion_membre_ajoutBizuthWEI'));				

return $this->redirect($this->generateUrl('cva_gestion_membre_ajoutAdherent'));
			}
		}
		return $this->render('CvaGestionMembreBundle::paiement.html.twig', array('from' => $request->request->get('from'),'form' => $form->createView(), 'id' => $request->query->get('id')));
	}
	
	public function editPaiementAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();		
		$paiementsEtud = $this->get('cva_gestion_membre')->GetPaiementEtudiant($request->query->get('id'));
		$produits = $this->get('cva_gestion_membre')->GetAllProduitDispo();
		$paiement = new Paiement();
		$paiementType = new PaiementType($produits);
		$form = $this->createForm($paiementType, $paiement);
		
		/*if($request->isMethod('POST'))
		{
			$form->bind($request);
			if ($form->isValid()) 
			{
				$em->persist($paiement);
				$em->flush();
				$this->get('session')->getFlashBag()->add('notice', 'Paiement modifiÃ©');
				return $this->redirect($this->generateUrl('cva_gestion_membre_adherent'));
			}
		}*/
		
		return $this->render('CvaGestionMembreBundle::paiement.html.twig', array('form' => $form->createView(), 'id' => $request->query->get('id'), 'paiementsEtud' => $paiementsEtud));
    }
	
	public function deletePaiementAction(Request $request) {
		
		$idEtu = $request->query->get('idEtu');
		$em = $this->getDoctrine()->getManager();		
		$paiement = $this->get('cva_gestion_membre')->GetPaiementById($request->query->get('idPaiement'));
//die(var_dump($paiement));
		if(sizeof($paiement->getProduits())==1)
		{
			$em->remove($paiement);
		}
		else if(sizeof($paiement->getProduits())>1)
		{
			$produit = $this->get('cva_gestion_membre')->GetProduitById($request->query->get('idProduit'));
			$paiement->removeProduit($produit);
		}
		$em->flush();
		$this->get('session')->getFlashBag()->add('notice', 'Modification enregistree');
		return $this->redirect($this->generateUrl('cva_gestion_membre_editPaiement',array('id'=>$idEtu)));
	}
	
	public function editEtudiantAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();		
		$etudiant = $this->get('cva_gestion_membre')->GetEtudiantById($request->query->get('id'));
		
		$form = $this->createForm(new EtudiantType(), $etudiant);
		
		if($request->isMethod('POST'))
		{
			$form->bind($request);
			if ($form->isValid()) 
			{
				$em->persist($etudiant);
				$em->flush();
				$this->get('session')->getFlashBag()->add('notice', 'Etudiant modifiÃ©');
				return $this->redirect($this->generateUrl('cva_gestion_membre_adherent'));
			}
		}
		
		return $this->render('CvaGestionMembreBundle::editetudiant.html.twig', array('form' => $form->createView(), 'id' => $request->query->get('id')));
    }
	
	public function deleteAdherentAction(Request $request) {
	
		$em = $this->getDoctrine()->getManager();		
		$adh = $this->get('cva_gestion_membre')->GetEtudiantById($request->query->get('id'));
		$paiements= $this->get('cva_gestion_membre')->GetPaiementEtudiant($request->query->get('id'));
		$details = $this->get('cva_gestion_membre')->GetDetailsByIdEtudiant($request->query->get('id'));
	
		foreach ($paiements as &$value) {
		$em->remove($value);
		}
		if($details){
		$em->remove($details);
		}
		
		$em->remove($adh);
		$em->flush();
		
		$this->get('session')->getFlashBag()->add('notice', 'Adherent supprimÃ©');
		return $this->redirect($this->generateUrl('cva_gestion_membre_adherent'));
	}

	public function voirDetailsAction(Request $request)
	{
		$idEtu = $request->query->get('idEtu');

		if(isset($idEtu))
		{
			$etudiantRecherche = $this->get('cva_gestion_membre')->GetEtudiantById($idEtu);

			$paiements = $this->get('cva_gestion_membre')->GetPaiementEtudiant($etudiantRecherche->getId());
			if ($paiements)
			{
				foreach ($paiements as $paiement)
				{
					foreach ($paiement->getProduits() as $prod)
					{
						$produits[] = $prod;
					}
				}
			}
			else
			{
				$produits = new Produit();
				$produits->setDescription("Aucun");
			}
			
			return $this->render('CvaGestionMembreBundle::voirDetails.html.twig', array('etu' => $etudiantRecherche, 'produits' => $produits));
		}
	}
	
	public function adherentAction(Request $request)
    {

			$allAdherents = $this->get('cva_gestion_membre')->GetAllEtudiant();
if(count($allAdherents)==0)
$adherent=array();
			foreach($allAdherents as $i => $adh)
			{
				//On récupère les paiements et les produits de cet adherent
				$paiements = $this->get('cva_gestion_membre')->GetPaiementEtudiant($adh->getId());
				if ($paiements)
				{
					foreach ($paiements as $paiement)
					{
						foreach ($paiement->getProduits() as $prod)
						{
							$produits[] = $prod;
						}
					}
				}
				else
				{
					
					$produits = array();
					
				}

				$adherent[$i]['etudiant'] = $adh;
				$adherent[$i]['produit'] = $produits;
			}
	
		
		return $this->render('CvaGestionMembreBundle::rechercheAdherent.html.twig', array('adherent' => $adherent) );
    }
	
	//WEI
	public function ajoutBizuthWEIAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$etudiant = new Etudiant();
		//Les bizuths sont au PC ;)
		$etudiant->setDepartement('PC');

		//En théorie ils sont dans l'année de leurs 18 ans
		$anneeCourante = getdate();
		$anneeMaj = $anneeCourante['year']-18;

		$etudiant->setBirthday(new DateTime($anneeMaj."-01-24"));		

		$form = $this->createForm(new EtudiantType(), $etudiant);

		if($request->isMethod('POST'))
		{
			$form->bind($request);

			if ($form->isValid()) 
			{
				$em->persist($etudiant);
				$em->flush();
				$this->get('session')->getFlashBag()->add('notice', 'Bizuth WEI ajoute');
				return $this->redirect('paiement?id=' . $etudiant->getId().'&from=wei');		}
		}

		return $this->render('CvaGestionMembreBundle::ajoutBizuthWei.html.twig', array('form' => $form->createView(),));
	}
	
	public function rechercheBizuthWEIAction(Request $request)
    {
			
		if ($request->query->get('prod')==null)
		{
			$adherent = $this->get('cva_gestion_membre')->GetBizuthWEIAvecDetails();
		}


		return $this->render('CvaGestionMembreBundle::rechercheBizuthWEI.html.twig', array('adherent' => $adherent) );
    }
	
	public function ajoutDetailsWEIAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();

		if($request->isMethod('POST'))
		{
			$id = $_POST['id'];
		}
		else
		{
			$id = $_GET['id'];
		}
		
		$detailsWEI = $this->get('cva_gestion_membre')->GetDetailsByIdEtudiant($id);
		if($detailsWEI==NULL)
		{ 
			$detailsWEI = new DetailsWEI(); 
		}
		
		$form = $this->createForm(new DetailsWEIType(), $detailsWEI);

		if($request->isMethod('POST'))
		{
			$form->bind($request);
			
			if ($form->isValid()) 
			{
				$bizuth=$this->get('cva_gestion_membre')->GetEtudiantById($request->request->get('id'));
				$detailsWEI->setIdEtudiant($bizuth);
				$em->persist($detailsWEI);
				$em->flush();
				$this->get('session')->getFlashBag()->add('notice', 'Details enregistres');
				return $this->redirect('rechercheBizuthWEI');
			}
		}

		return $this->render('CvaGestionMembreBundle::ajoutDetailsWEI.html.twig', array('form' => $form->createView(),'id' => $request->query->get('id')));
	}
	//Utilisateurs
	public function addUserAction(Request $request)
    {
		
			$em = $this->getDoctrine()->getManager();
			$user = new User();
			$form = $this->createForm(new UserType(), $user);
			
			if($request->isMethod('POST'))
			{
				$form->bind($request);

				if ($form->isValid()) 
				{
						$factory = $this->get('security.encoder_factory');
						
						$encoder = $factory->getEncoder($user);
						$password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
						$user->setPassword($password);
						$em->persist($user);
						$em->flush();

					$this->get('session')->getFlashBag()->add('notice', 'Utilisateur ajoutÃ©');
					return $this->redirect($this->generateUrl('cva_gestion_membre_addUser'));
				}
			}
			
			
			return $this->render('CvaGestionMembreBundle::ajoutUser.html.twig', array('form' => $form->createView(),));
		
    }
	
	public function editUserAction(Request $request)
    {
	$repository = $this->getDoctrine()->getRepository('CvaGestionMembreBundle:User');
	$myName=$this->get('security.context')->getToken()->getUser()->getUserName();
	$superAdmin = "a:1:{i:0;s:16:\"ROLE_SUPER_ADMIN\";}";
	
	$query = $repository->createQueryBuilder('u')
    ->where('u.username <> :myName AND u.roles<> :superAdmin')
    ->setParameter('myName', $myName)
	->setParameter('superAdmin', $superAdmin)
    ->getQuery();
		
	$users = $query->getResult();
	
	return $this->render('CvaGestionMembreBundle::editUser.html.twig', array('user' => $users) );
    }
	
	public function modifUserAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();		
		$user = $this->get('cva_gestion_membre')->GetUserById($request->query->get('id'));
		
		$form = $this->createForm(new UserType(array()), $user);
		
		if($request->isMethod('POST'))
		{
			$form->bind($request);
			if ($form->isValid()) 
			{
				 $factory = $this->get('security.encoder_factory');
						
				 $encoder = $factory->getEncoder($user);
				 $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
				 $user->setPassword($password);
			
				$em->persist($user);
				$em->flush();
				$this->get('session')->getFlashBag()->add('notice', 'Utilisateur modifiÃ©');
				return $this->redirect($this->generateUrl('cva_gestion_membre_modifUser'));
			}
		}
		
		return $this->render('CvaGestionMembreBundle::modificationUser.html.twig', array('form' => $form->createView(), 'id' => $request->query->get('id')));
    }
	
	public function deleteUserAction(Request $request) {
	
		$em = $this->getDoctrine()->getManager();		
		$user = $this->get('cva_gestion_membre')->GetUserById($request->query->get('id'));
		
		$em->remove($user);
		$em->flush();
		
		$this->get('session')->getFlashBag()->add('notice', 'Utilisateur supprimÃ©');
		return $this->redirect($this->generateUrl('cva_gestion_membre_editUser'));
	}
	
	//Produits
	public function addProduitAction(Request $request)
    {
		
			$em = $this->getDoctrine()->getManager();
			$produit = new Produit();
			
			$produit->setDisponibilite('OUI');
			$form = $this->createForm(new ProduitType(), $produit);
			
			if($request->isMethod('POST'))
			{
				$form->bind($request);


				if ($form->isValid()) 
				{
						$em->persist($produit);
						$em->flush();
					$this->get('session')->getFlashBag()->add('notice', 'Produit ajoutÃ©');
					return $this->redirect($this->generateUrl('cva_gestion_membre_addProduit'));
				}
			}
			
			
			return $this->render('CvaGestionMembreBundle::ajoutProduit.html.twig', array('form' => $form->createView(),));
		
    }
	
	public function deleteProduitAction(Request $request) {
	
		$em = $this->getDoctrine()->getManager();		
		$product = $this->get('cva_gestion_membre')->GetProduitById($request->query->get('id'));
		
		$em->remove($product);
		$em->flush();
		
		$this->get('session')->getFlashBag()->add('notice', 'Produit supprimÃ©');
		return $this->redirect($this->generateUrl('cva_gestion_membre_tableauProduits'));
	}
	
	public function tableauProduitsAction(Request $request)
    {
	$repository = $this->getDoctrine()->getRepository('CvaGestionMembreBundle:Produit');
			
	$products = $repository->findAll();
	
	return $this->render('CvaGestionMembreBundle::tableauProduits.html.twig', array('produit' => $products) );
    }

	public function editProduitAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();		
		$product = $this->get('cva_gestion_membre')->GetProduitById($request->query->get('id'));
		
		$form = $this->createForm(new ProduitType(array()), $product);
		
		if($request->isMethod('POST'))
		{
			$form->bind($request);
			if ($form->isValid()) 
			{			
				$em->persist($product);
				$em->flush();
				$this->get('session')->getFlashBag()->add('notice', 'Produit modifiÃ©');
				return $this->redirect($this->generateUrl('cva_gestion_membre_editProduit'));
			}
		}
		
		return $this->render('CvaGestionMembreBundle::editProduit.html.twig', array('form' => $form->createView(), 'id' => $request->query->get('id')));
    }
	
    //Others
		
	 public function loginAction()
	  {
		// Si le visiteur est déjà identifié, on le redirige vers l'accueil
		if ($this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
		  return $this->redirect($this->generateUrl('cva_gestion_membre_adherent'));
		}
	 
		$request = $this->getRequest();
		$session = $request->getSession();
	 
		// On vérifie s'il y a des erreurs d'une précédente soumission du formulaire
		if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
		  $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
		} else {
		  $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
		  $session->remove(SecurityContext::AUTHENTICATION_ERROR);
		}
	 
		return $this->render('CvaGestionMembreBundle::index.html.twig', array(
		  // Valeur du précédent nom d'utilisateur entré par l'internaute
		  'last_username' => $session->get(SecurityContext::LAST_USERNAME),
		  'error'         => $error,
		));
	  }

	public function statsAction()
	{
		//Ventes Mois
		//$ventesMois = count($this->get('cva_gestion_membre')->VentesMoisCourant());	
		//die(var_dump($ventesMois));	
		
		//Stats par produits
		$prods = $this->get('cva_gestion_membre')->GetAllProduitDispo();
		$ventesProds = array();
		$ventesAnnee = array();

		foreach($prods as $prod)
		{
			$desc = $prod->getDescription();
			$ventes = count($this->get('cva_gestion_membre')->GetEtudiantByProduit($prod->getId()));
			$venteProds[]=array('desc' => $desc,'vendus' => $ventes);
		}

		//Stats par années
		$annees = array(1,2,3,4,5,'3CYCLE','Personnel','Autre');
		foreach($annees as $annee)
		{
			$ventes = count($this->get('cva_gestion_membre')->GetEtudiantByAnnee($annee));
			$venteAnnee[]=array('annee' => $annee,'vendus' => $ventes);
		}

		//Stats par depart
		$departs = array('PC','GEN','GCU','GI','GMC','GMD','GMPP','GE','IF','TC','BB','BIM','SGM');
		foreach($departs as $depart)
		{
			$ventes = count($this->get('cva_gestion_membre')->GetEtudiantByDepartement($depart));
			$venteDepart[]=array('depart' => $depart,'vendus' => $ventes);
		}
		//die(var_dump($venteDepart));
		return $this->render('CvaGestionMembreBundle::stats.html.twig',array('venteProds' => $venteProds, 'venteAnnee' => $venteAnnee, 'venteDepart' => $venteDepart));
	}
}
