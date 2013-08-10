<?php

namespace Cva\GestionMembreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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

class GestionMembreController extends Controller
{
	//La redirection depuis /
	public function cacaAction(Request $request)
	{
		return $this->redirect($this->generateUrl('cva_gestion_membre_adherent'));
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
					$this->get('session')->getFlashBag()->add('notice', 'Profil modifi�');
				}
				else if($oldPassword == $user->getPassword())
				{
					$user->setUsername($form->get('username')->getData());
					$newPassword = $encoder->encodePassword($form->get('newPassword')->getData() , $user->getSalt());
					$user->setPassword($newPassword);
					$em->persist($user);
					$em->flush();
					$this->get('session')->getFlashBag()->add('notice', 'Profil modifi�');
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
				$this->get('session')->getFlashBag()->add('notice', 'Etudiant ajouté');
				return $this->redirect('paiement?id=' . $etudiant->getId());
			}
		}

		return $this->render('CvaGestionMembreBundle::AjoutAdherent.html.twig', array('form' => $form->createView(),));
	}

	public function paiementAction(Request $request)
	{
		$em = $this->getDoctrine()->getEntityManager();
		$produits = $this->get('cva_gestion_membre')->GetAllProduitDispo();
		$paiement = new Paiement();
		$paiementType = new PaiementType($produits);
		$form = $this->createForm($paiementType, $paiement);

		if($request->isMethod('POST'))
		{
			$form->bind($request);

			if ($form->isValid()) 
			{
				foreach($produits as $prod)
				{
					foreach($form->get('Produits')->getData() as $desc)
					{
						if (strcmp($desc, $prod->getDescription()) == 0)
						{
							//On v�rifie que l'�tudiant ne poss�de pas d�j� le produit
							if($this->get('cva_gestion_membre')->EtudiantAlreadyGotProduct($request->request->get('id'),$prod)==true)
							{
								$this->get('session')->getFlashBag()->add('warning', 'Cet etudiant possede deja ce produit');
								return $this->redirect($this->generateUrl('cva_gestion_membre_adherent'));
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
				$this->get('session')->getFlashBag()->add('notice', 'Paiement effectué');
				return $this->redirect($this->generateUrl('cva_gestion_membre_ajoutAdherent'));
			}
		}
		return $this->render('CvaGestionMembreBundle::paiement.html.twig', array('form' => $form->createView(), 'id' => $request->query->get('id')));
	}
	
	public function editPaiementAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();		
		$paiementsEtud = $this->get('cva_gestion_membre')->GetPaiementEtudiant($request->query->get('id'));
		$produits = $this->get('cva_gestion_membre')->GetAllProduitDispo();
		$paiement = new Paiement();
		$paiementType = new PaiementType($produits);
		$form = $this->createForm($paiementType, $paiement);
		
		if($request->isMethod('POST'))
		{
			$form->bind($request);
			if ($form->isValid()) 
			{
				$em->persist($paiement);
				$em->flush();
				$this->get('session')->getFlashBag()->add('notice', 'Paiement modifié');
				return $this->redirect($this->generateUrl('cva_gestion_membre_adherent'));
			}
		}
		
		return $this->render('CvaGestionMembreBundle::paiement.html.twig', array('form' => $form->createView(), 'id' => $request->query->get('id'), 'paiementsEtud' => $paiementsEtud));
    }
	
	public function deletePaiementAction(Request $request) {
	
		$em = $this->getDoctrine()->getManager();		
		$paiement = $this->get('cva_gestion_membre')->GetPaiementById($request->query->get('idPaiement'));
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
		return $this->redirect($this->generateUrl('cva_gestion_membre_adherent'));
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
				$this->get('session')->getFlashBag()->add('notice', 'Etudiant modifié');
				return $this->redirect($this->generateUrl('cva_gestion_membre_adherent'));
			}
		}
		
		return $this->render('CvaGestionMembreBundle::editetudiant.html.twig', array('form' => $form->createView(), 'id' => $request->query->get('id')));
    }
	
	public function deleteAdherentAction(Request $request) {
	
		$em = $this->getDoctrine()->getManager();		
		$adh = $this->get('cva_gestion_membre')->GetEtudiantById($request->query->get('id'));
		$paiements= $this->get('cva_gestion_membre')->GetPaiementEtudiant($request->query->get('id'));
	
		foreach ($paiements as &$value) {
		$em->remove($value);
		}
		
		$em->remove($adh);
		$em->flush();
		
		$this->get('session')->getFlashBag()->add('notice', 'Adherent supprimé');
		return $this->redirect($this->generateUrl('cva_gestion_membre_adherent'));
	}
	
	public function detailetudiantAction(Request $request)
    {
		$etudiants = $this->get('cva_gestion_membre')->GetEtudiant('', '', $request->query->get('etudiant'), '');
		$etudiant = $etudiants[0];
		$paiements = $this->get('cva_gestion_membre')->GetPaiementEtudiant($etudiant->getId());
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
		return $this->render('CvaGestionMembreBundle::getetudiant.html.twig', array('etudiants' => $etudiants, 'produits' => $produits));
    }
	
	public function adherentAction(Request $request)
    {
		$produit = $this->get('cva_gestion_membre')->GetAllProduitDispo();
			
		if ($request->query->get('prod')==null)
		{
			$adherent = $this->get('cva_gestion_membre')->GetAllEtudiant();	
		}
		else
		{
			$adherent = $this->get('cva_gestion_membre')->GetEtudiantByProduit($request->query->get('prod'));
		}
		return $this->render('CvaGestionMembreBundle::rechercheAdherent.html.twig', array('adherent' => $adherent, 'produit' => $produit) );
    }
	
	public function tableAdherentAction(Request $request)
    {
		$produit = $this->get('cva_gestion_membre')->GetAllProduitDispo();
			
		if ($request->query->get('prod')==null)
		{
			$adherent = $this->get('cva_gestion_membre')->GetAllEtudiant();	
		}
		else
		{
			$adherent = $this->get('cva_gestion_membre')->GetEtudiantByProduit($request->query->get('prod'));
		}
		return $this->render('CvaGestionMembreBundle::tableAdherent.html.twig', array('adherent' => $adherent, 'produit' => $produit) );
    }
	
	//WEI
	public function ajoutBizuthWEIAction(Request $request)
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
				$this->get('session')->getFlashBag()->add('notice', 'Bizuth WEI ajoute');
				return $this->redirect('paiement?id=' . $etudiant->getId());		}
		}

		return $this->render('CvaGestionMembreBundle::ajoutBizuthWei.html.twig', array('form' => $form->createView(),));
	}
	
	public function rechercheBizuthWEIAction(Request $request)
    {
		$produit = $this->get('cva_gestion_membre')->GetAllProduitDispo();
			
		if ($request->query->get('prod')==null)
		{
			$adherent = $this->get('cva_gestion_membre')->GetBizuthWEIAvecDetails();
		}
		else
		{
			$adherent = $this->get('cva_gestion_membre')->GetBizuthWEIAvecDetails($request->query->get('prod'));
		}

		return $this->render('CvaGestionMembreBundle::rechercheBizuthWEI.html.twig', array('adherent' => $adherent, 'produit' => $produit) );
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

					$this->get('session')->getFlashBag()->add('notice', 'Utilisateur ajouté');
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
				// $factory = $this->get('security.encoder_factory');
						
				// $encoder = $factory->getEncoder($user);
				// $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
				// $user->setPassword($password);
			
				$em->persist($user);
				$em->flush();
				$this->get('session')->getFlashBag()->add('notice', 'Utilisateur modifié');
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
		
		$this->get('session')->getFlashBag()->add('notice', 'Utilisateur supprimé');
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
					$this->get('session')->getFlashBag()->add('notice', 'Produit ajouté');
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
		
		$this->get('session')->getFlashBag()->add('notice', 'Produit supprimé');
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
				$this->get('session')->getFlashBag()->add('notice', 'Produit modifié');
				return $this->redirect($this->generateUrl('cva_gestion_membre_editProduit'));
			}
		}
		
		return $this->render('CvaGestionMembreBundle::editProduit.html.twig', array('form' => $form->createView(), 'id' => $request->query->get('id')));
    }
	
    //Others
	
    public function rechercheetudiantAction(Request $request)
    {
		$etudiants = $this->get('cva_gestion_membre')->GetEtudiant($request->query->get('name'), $request->query->get('firstName'), $request->query->get('numEtudiant'), $request->query->get('mail'), $request->query->get('debut'));
		return $this->render('CvaGestionMembreBundle::resAjaxEtudiants.html.twig', array('etudiants' => $etudiants));
    }
		
	 public function loginAction()
	  {
		// Si le visiteur est d�j� identifi�, on le redirige vers l'accueil
		if ($this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
		  return $this->redirect($this->generateUrl('cva_gestion_membre_adherent'));
		}
	 
		$request = $this->getRequest();
		$session = $request->getSession();
	 
		// On v�rifie s'il y a des erreurs d'une pr�c�dente soumission du formulaire
		if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
		  $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
		} else {
		  $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
		  $session->remove(SecurityContext::AUTHENTICATION_ERROR);
		}
	 
		return $this->render('CvaGestionMembreBundle::index.html.twig', array(
		  // Valeur du pr�c�dent nom d'utilisateur entr� par l'internaute
		  'last_username' => $session->get(SecurityContext::LAST_USERNAME),
		  'error'         => $error,
		));
	  }
}
