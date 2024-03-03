<?php
namespace Freedom\Controllers;

use Freedom\Models\User;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController extends CDController {

    /**
     * Create a new instance
     */
    public function __construct(ContainerInterface $container)
    {
        return parent::__construct($container);
    }
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return parent::__invoke($request, $response, $args);
    }

    public function about(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "about");
        $this->view->Set("src/templates/about.php");
        return $this->buffer_response($request, $response, $args);
    }

    public function contact(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "contact_us");
        $this->view->Set("src/templates/contact_us.php");
        return $this->buffer_response($request, $response, $args);
    }

    public function home(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "home");
        $this->view->Set("src/templates/home.php");
        return $this->buffer_response($request, $response, $args);
    }
    public function register(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "register");
        $this->view->Set("src/templates/register_form.php");
        return $this->buffer_response($request, $response, $args);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "register");
        $this->model = new User();
        $this->model->Connect($this->container);
        $this->model->Copy($args);
        if ($this->model->Validate())
        {
            $this->model->Create();
            $this->container->set("active_page", "home");
            $this->view->Set("src/templates/home.php");
        }
        return $this->buffer_response($request, $response, $args);
    }
    public function login(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "login");
        $this->view->Set("src/templates/login_form.php");

        return $this->buffer_response($request, $response, $args);
    }
    public function logout(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->get('session')->End();
        $this->container->set("active_page", "home");
        $this->view->Set("src/templates/home.php");

        return $this->buffer_response($request, $response, $args);
    }
    public function authenticate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->view->Set("src/templates/login_form.php");

        $data = $request->getParsedBody();
        $user_name = (isset($data['user_name'])) ? $data['user_name'] : null;
        $password = (isset($data['password'])) ? $data['password'] : null;

        $this->model = new User();
        $this->model->Connect($this->container);
        if ($this->model->authenticate($user_name, $password))
        {
            $this->container->set("active_page", "profile");
            $this->view->Set("src/templates/profile.php");
        }

        return $this->buffer_response($request, $response, $args);
    }

    public function forgot_password(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "forgot_password");
        $this->view->Set("src/templates/home.php");
        return $this->buffer_response($request, $response, $args);
    }
    public function new_password(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "new_password");
        $this->view->Set("src/templates/home.php");
        return $this->buffer_response($request, $response, $args);
    }
    public function reset_create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "reset_create");
        $this->view->Set("src/templates/home.php");
        return $this->buffer_response($request, $response, $args);
    }
    public function reset_store(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "reset_store");
        $this->view->Set("src/templates/home.php");
        return $this->buffer_response($request, $response, $args);
    }
}
