<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;

use App\Form\EditUserPasswordType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    /**
     * this controller allow us edit user profil
     *
     * @param User $user
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */

    #[Security("is_granted('ROLE_USER') and user === choosenUser")]
    #[Route('/utilisateur/edition/{id}', name: 'user.edit', methods: ['GET', 'POST'])]
    public function edit(
        User $choosenUser,
        Request $request,
        EntityManagerInterface $manager,
        UserPasswordHasherInterface $hasher
    ): Response {

        $form = $this->createForm(UserType::class, $choosenUser);

        $form->handleRequest($request);
    if($form -> isSubmitted() && $form->isValid()) {
        if($hasher->isPasswordValid($choosenUser, $form->getData()->getPlainPassword())) {
            $user = $form->getData();
            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'sucess',
                'Les information de votre compte on bien été modifées.'
            );

            return $this->redirectToRoute('recipe.index');
        } else {
            $this->addFlash(
                'warning',
                "Le mot de passe renseigné est incorrect."
            );
        }

    }
            return $this->render('pages/user/edit.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        /**
         * Function to edit mdp
         *
         * @param User $user
         * @param Request $request
         * @param UserPasswordHasherInterface $hasher
         * @param EntityManagerInterface $manager
         * @return Response
         */
        #[Security("is_granted('ROLE_USER') and user === choosenUser")]
        #[Route('/utilisateur/edition-mot-de-passe/{id}', 'user.edit.password', methods: ['GET', 'POST'])]
        public function editPassword(User $choosenUser,
                                        Request $request,
                                        UserPasswordHasherInterface $hasher,
                                        EntityManagerInterface $manager):Response
        {
            if(!$this->getUser()) {
                return $this->redirectToRoute('security.login');
            }
    
            if($this->getUser() !== $choosenUser) {
                return $this->redirectToRoute('recipe.index');
            }

            $form = $this->createForm(EditUserPasswordType::class);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                if($hasher->isPasswordValid($choosenUser, $form->getData()['plainPassword']))
                {
                    $choosenUser->setUpdatedAt(new \DateTimeImmutable());
                    $choosenUser->setPlainPassword(

                            $form->getData()['newPassword']

                    );

                    $this->addFlash(
                        'sucess',
                        "Le mot de passe a été modifié."
                    );

                    $manager->persist($choosenUser);
                    $manager->flush();

                    return $this->redirectToRoute('recipe.index');
                }else{
                    $this->addFlash(
                        'warning',
                        "Le mot de passe renseigné est incorrect."
                    );
                }
            }

            return $this->render('pages/user/edit_password.html.twig',[
                'form' => $form->createView()
            ]);
        }
}
