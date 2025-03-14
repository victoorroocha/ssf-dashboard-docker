<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;
use Application\Service\OracleService;
use Application\Repository\UsuarioRepository;
use Laminas\View\Model\JsonModel;
use Laminas\Db\Sql\Sql;
use Laminas\Session\Container;
use Laminas\Permissions\Acl\Acl;
use Laminas\Crypt\Password\Bcrypt;


class UsuarioController extends BaseController
{
    private $pgAdapter;
    private $oracleService;
    private $usuarioRepository;

    public function __construct(Adapter $pgAdapter, OracleService $oracleService = null, UsuarioRepository $usuarioRepository = null, Acl $acl)
    {
        parent::__construct($acl); // Chama o construtor da classe base
        $this->pgAdapter = $pgAdapter;
        $this->oracleService = $oracleService;
        $this->usuarioRepository = $usuarioRepository;
    }

    public function perfilUsuarioAction()
    {
        $session = new Container('auth');

        if (!isset($session->user)) {
            // Redireciona o usuário para o login caso não esteja autenticado
            return $this->redirect()->toRoute('login');
        }
        unset($session->user['senha']);
        return new ViewModel([
            'sessao' => $session->user
        ]);
    }
    public function atualizaPerfilAction()
    {
        // Verifica se a requisição é do tipo POST
        if (!$this->getRequest()->isPost()) {
            return new JsonModel([
                'success' => false,
                'message' => 'Método não permitido.',
            ]);
        }

        // Obtém os dados enviados pelo formulário
        $data = $this->params()->fromPost();

        // Valida os dados (exemplo básico)
        if (empty($data['id']) || empty($data['nome']) || empty($data['role'])) {
            return new JsonModel([
                'success' => false,
                'message' => 'Dados inválidos.',
            ]);
        }

        try {
            // Atualiza os dados do usuário no banco de dados
            $this->usuarioRepository->atualizarUsuario($data);

            return new JsonModel([
                'success' => true,
                'message' => 'Dados atualizados com sucesso!',
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'success' => false,
                'message' => 'Erro ao atualizar os dados: ' . $e->getMessage(),
            ]);
        }
    }
    

    public function gestaoUsuarioAction()
    {
        $session = new Container('auth');

        if (!isset($session->user)) {
            // Redireciona o usuário para o login caso não esteja autenticado
            return $this->redirect()->toRoute('login');
        }

        return new ViewModel();
    }
    public function listUsuariosAction()
    {

        try {
            // Obtém os parâmetros de paginação e filtros
            $skip = $this->params()->fromQuery('skip', 0);
            $take = $this->params()->fromQuery('take', 500);
            $sort = $this->params()->fromQuery('sort', null);

            // Busca os usuários no repositório
            $usuarios = $this->usuarioRepository->listarUsuarios($skip, $take, $sort);

            return new JsonModel([
                'success' => true,
                'data' => $usuarios['data'],
                'totalCount' => $usuarios['totalCount'],
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'success' => false,
                'message' => 'Erro ao listar usuários: ' . $e->getMessage(),
            ]);
        }
    }

    public function addOrUpdateUsuarioAction()
    {
        // Verifica se a requisição é do tipo POST ou PUT
        if (!$this->getRequest()->isPost() && !$this->getRequest()->isPut()) {
            return new JsonModel([
                'success' => false,
                'message' => 'Método não permitido.',
            ]);
        }

        // Obtém os dados enviados
        $data = json_decode($this->getRequest()->getContent(), true);

        try {
            // Verifica se é uma requisição PUT (atualização)
            if ($this->getRequest()->isPut()) {
                // Atualiza o usuário existente
                $this->usuarioRepository->atualizarUsuario($data);
                $message = 'Usuário atualizado com sucesso!';
            } else {
                // Insere um novo usuário (requisição POST)
                $this->usuarioRepository->inserirUsuario($data);
                $message = 'Usuário adicionado com sucesso!';
            }

            return new JsonModel([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'success' => false,
                'message' => 'Erro ao salvar usuário: ' . $e->getMessage(),
            ]);
        }
    }
    public function excluirUsuarioAction()
    {
        // Verifica se a requisição é do tipo DELETE
        if (!$this->getRequest()->isDelete()) {
            return new JsonModel([
                'success' => false,
                'message' => 'Método não permitido.',
            ]);
        }

        // Obtém os dados enviados no corpo da requisição
        $data = json_decode($this->getRequest()->getContent(), true);

        try {
            // Exclui o usuário
            $this->usuarioRepository->excluirUsuario($data['id']);

            return new JsonModel([
                'success' => true,
                'message' => 'Usuário excluído com sucesso!',
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'success' => false,
                'message' => 'Erro ao excluir usuário: ' . $e->getMessage(),
            ]);
        }
    }
}