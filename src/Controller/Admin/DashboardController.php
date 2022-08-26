<?php

namespace App\Controller\Admin;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Topic;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator
    )
    {
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin', name: 'admin')]
    public function index():Response
    {
//        return parent::index();
        /*$url = $this->adminUrlGenerator
            ->setController(QuestionCrudController::class)
            ->generateUrl();
        return $this->redirect($url);*/

        return $this->render('my-dashboard.html.twig');

    }

    public function configureDashboard():Dashboard
    {
        return Dashboard::new()
            ->setFaviconPath('favicon.svg')
            ->setTitle('Easy Admin For me');
    }

    public function configureMenuItems():iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-dashboard');
//        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Questions', 'fas fa-question-circle', Question::class);
        yield MenuItem::linkToCrud('Answers', 'fas fa-comments', Answer::class);
        yield MenuItem::linkToCrud('Topics', 'fas fa-folder', Topic::class);
        yield MenuItem::linkToCrud('Users', 'fas fa-users', User::class);
//        yield MenuItem::linkToRoute('HomePage', 'fas fa-home', 'app_homepage');
        yield MenuItem::linkToUrl('HomePage', 'fas fa-home', $this->generateUrl('app_homepage'));

        /*return [
            MenuItem::linkToRoute('The Label', 'fa ...', 'admin'),
            MenuItem::linkToDashboard('Dashboard', 'fa fa-home'),
            MenuItem::linkToCrud('The Label', 'fas fa-list', Question::class),
        ];*/

    }

    public function configureActions():Actions
    {
        return parent::configureActions()
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureUserMenu(UserInterface $user):UserMenu
    {
        if(!$user instanceof User){
            throw new \Exception('wrong user');
        }
        return parent::configureUserMenu($user)
            ->setAvatarUrl($user->getAvatarUrl())
            ->setMenuItems([
                MenuItem::linkToUrl('My Profile', 'fas fa-user', $this->generateUrl(
                    'app_profile_show'
                ))
                           ]);
    }


}
