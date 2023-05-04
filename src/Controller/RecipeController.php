<?php

namespace App\Controller;

use App\Entity\Mark;
use App\Entity\Recipe;
use App\Form\MarkType;
use App\Form\RecipeType;
use App\Repository\MarkRepository;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RecipeController extends AbstractController
{

    /**
     * This controller disply all recipes
     *
     * @param IngredientRepository $repository
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @return Response
     */

    #[Route('/recette', name: 'recipe.index', methods:['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        RecipeRepository $repository,
        Request $request,
        PaginatorInterface $paginator): Response
    {

        $recipes = $paginator->paginate(
            $repository->findBy(['user' => $this->getUser()]),
            $request->query->getInt('page', 1),
            10
        );
        return $this->render('pages/recipe/index.html.twig', [
            'recipes' => $recipes,
        ]);
    }

    #[Route('/recette/publique', 'recipe.index.publique', methods:['GET'])]
    public function indexPublic(PaginatorInterface $paginator,
                                    RecipeRepository $repository,
                                    Request $request
                                ):Response
    {
        $recipes = $paginator->paginate(
            $repository->findPublicRecipe(null),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('pages/recipe/index_public.html.twig', [
            'recipes' => $recipes
        ]);
    }


/**
 * This controller allow us to create a new recipe
 *
 * @param Request $request
 * @param EntityManagerInterface $manager
 * @return Response
 */


    #[IsGranted('ROLE_USER')]
    #[Route('/recette/creation', 'recipe.new', methods: ['POST', 'GET'])]
    public function new(Request $request, EntityManagerInterface $manager) : Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);

        $form->handleRequest($request);
        if($form->isSubmitted()&& $form->isValid()){
            $recipe = $form->getData();
            $recipe->setUser($this->getUser());

            $manager->persist($recipe);
            $manager->flush();

            return $this->redirectToRoute('recipe.index');

            $this->addFlash(
                'sucess',
                'Votre recette a été créé avec succes !'
            );
        }

        return $this->render('pages/recipe/new.html.twig',[
            'form' => $form->createView()
    ]);
    }

        //Debut edit //
    /**
     * Function to edit Ingredient
     *
     * @param Recipe $recipe
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */

    #[Security("is_granted('ROLE_USER') and user === recipe.getUser()")]
    #[Route('/recette/edition/{id}', 'recipe.edit', methods: ['GET', 'POST'])]
    public function edit(Recipe $recipe,
                            Request $request,
                            EntityManagerInterface $manager) : Response
    {
        $form = $this->createForm(RecipeType::class, $recipe);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $recipe = $form->getData();

            $manager->persist($recipe);
            $manager->flush();

            $this->addFlash(
                'sucess',
                'Votre recette a été modifié avec succes !'
            );

            return $this->redirectToRoute('recipe.index');

        }

        return $this->render('pages/recipe/edit.html.twig',[
            'form' => $form->createView()
        ]);
    }
    /**
     * Function to supp recette
     *
     * @param EntityManagerInterface $manager
     * @param Recipe $recipe
     * @return Response
     */

    #[Security("is_granted('ROLE_USER') and user === recipe.getUser()")]
    #[Route('/recipe/suppresion/{id}', 'recipe.delete', methods: ['GET'])]
    public function delete(EntityManagerInterface $manager,
                                            Recipe $recipe) : Response
    {
        if (!$recipe){
            $this->addFlash(
                'warning',
                "La recette na pas était trouver!"
            );

            return $this->redirectToRoute('recette.index');
        }

        $manager->remove($recipe);
        $manager->flush();

        

        $this->addFlash(
            'sucess',
            'Votre recette a été supprimer avec succes !'
        );

        return $this->redirectToRoute('recipe.index');
    }


    /**
     * This controller allow us to see a recipe if this one is public
     *
     * @param Recipe $recipe
     * @return Response
     */
    #[Security("is_granted('ROLE_USER') and recipe.getIsPublic() === true || user === recipe.getUser()")]
    #[Route('/recette/{id}', 'recipe.show', methods:['GET', 'POST'])]
    public function show(Recipe $recipe,
                            Request $request,
                            MarkRepository $markRepository,
                            EntityManagerInterface $manager) : Response
    {
        $mark = new Mark();
        $form = $this->createForm(MarkType::class, $mark);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $mark->setUser($this->getUser())
                    ->setRecipe($recipe);

            $existingMark = $markRepository->findOneBy([
                'user' => $this->getUser(),
                'recipe' => $recipe
            ]);

            if (!$existingMark) 
            {
                $manager->persist($mark);

            }else{
                $existingMark->setMark(
                                $form->getData()->getMark()
                );
            }
            $manager->flush();

            $this->addFlash(
                "sucess",
                'Votre note a bien été prise en compte'
            );

            return $this->redirectToRoute('recipe.show', ['id' => $recipe->getId()]);

        }

        return $this->render('pages/recipe/show.html.twig',[
            'recipe' => $recipe,
            'form' => $form->createView()
        ]);
    }

}
