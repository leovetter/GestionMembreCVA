<?php
// src/Cva/GestionMembreBundle/Form/Type/RoleType.php
namespace Cva\GestionMembreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
		->add('name', 'text')
		->add('canAddOneNember', 'checkbox')
		->add('canGetEtudiant', 'text')
		->add('canGetAllEtudiant', 'text')
		->add('canDeleteEtudiant', 'checkbox')
		->add('canGetHistorique', 'checkbox')
		->add('canEditHistorique', 'checkbox')
		->add('canAddUser', 'checkbox')
		->add('canDeleteUser', 'checkbox')
		->add('canEditUser', 'checkbox')
		->add('canEditRole', 'checkbox')
		->add('canCreateRole', 'checkbox');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('data_class' => 'Cva\GestionMembreBundle\Entity\Role'));
    }

    public function getName()
    {
        return 'role';
    }
}
