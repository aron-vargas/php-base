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
        $this->view->active_page = "about";
        $this->view->template = "src/templates/about.php";
        return $this->buffer_response($request, $response, $args);
    }

    public function contact(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->view->active_page = "contact_us";
        $this->view->template = "src/templates/contact_us.php";
        return $this->buffer_response($request, $response, $args);
    }

    public function home(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->view->active_page = "home";
        $this->view->template = "src/templates/home.php";
        return $this->buffer_response($request, $response, $args);
    }
    public function register(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->view->active_page = "register";
        $this->view->template = "src/templates/register_form.php";
        return $this->buffer_response($request, $response, $args);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->view->active_page = "register";
        $this->view->model = new User();
        $this->view->model->Connect($this->container);
        $this->view->model->Copy($args);
        if ($this->view->model->Validate())
        {
            $this->view->model->Create();
            $this->view->active_page = "home";
            $this->view->template = "src/templates/home.php";
        }
        return $this->buffer_response($request, $response, $args);
    }
    public function login(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->view->active_page = "login";
        $this->view->template = "src/templates/login_form.php";

        return $this->buffer_response($request, $response, $args);
    }
    public function logout(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->get('session')->End();
        $this->view->active_page = "home";
        $this->view->template = "src/templates/home.php";

        return $this->buffer_response($request, $response, $args);
    }
    public function authenticate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->view->template = "src/templates/login_form.php";

        $data = $request->getParsedBody();
        $user_name = (isset($data['user_name'])) ? $data['user_name'] : null;
        $password = (isset($data['password'])) ? $data['password'] : null;

        $this->model = new User();
        $this->model->Connect($this->container);
        if ($this->model->authenticate($user_name, $password))
        {
            //$this->container->set("active_page", "profile");
            $this->view->template = "src/templates/profile.php";
        }

        return $this->buffer_response($request, $response, $args);
    }

    public function forgot_password(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->view->active_page = "forgot_password";
        $this->view->template = "src/templates/forgot_password.php";

        return $this->buffer_response($request, $response, $args);
    }
    public function new_password(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->view->active_page = "new_password";
        $this->view->template = "src/templates/new_password.php";
        return $this->buffer_response($request, $response, $args);
    }
    public function reset_create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->view->active_page = "reset_create";
        $this->view->template = "src/templates/reset_create.php";
        return $this->buffer_response($request, $response, $args);
    }
    public function reset_store(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->view->active_page = "reset_store";
        $this->view->template = "src/templates/reset_store.php";
        return $this->buffer_response($request, $response, $args);
    }
}
