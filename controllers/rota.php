<?php

  require_once '../models/Pet.php';
  require_once '../models/Usuario.php';
  require_once '../models/Anuncio.php';
  require_once '../models/Imagem.php';
  require_once '../models/Transacao.php';
  require_once '../DAOs/ImagemDAO.php';
  require_once './AnuncioController.php';
  require_once './UsuarioControlador.php';
  require_once './TransacaoController.php';

  $acao = $_GET['acao'];

  switch ($acao) {
    case 'salvarPet':
      $raca = filter_input(INPUT_POST, 'raca', FILTER_SANITIZE_SPECIAL_CHARS);
      $cor = filter_input(INPUT_POST, 'cor', FILTER_SANITIZE_SPECIAL_CHARS);
      $sexo = filter_input(INPUT_POST, 'sexo', FILTER_SANITIZE_SPECIAL_CHARS);
      $categoria = filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_SPECIAL_CHARS);
      $dataNascimento = filter_input(INPUT_POST, 'dataNascimento', FILTER_SANITIZE_SPECIAL_CHARS);


      $numeroDeImagens = count($_FILES['imagem']['name']);
      $imagens = [];

      if (isset($_FILES['imagem'])) {

        for ($i = 0; $i < $numeroDeImagens; $i++) {
          $imagem = new Imagem();

          $ext = strtolower(substr($_FILES['imagem']['name'][$i], -4));
          echo $ext;
          $new_name = (date("Y.m.d-H.i.s"). rand()) . $ext;
          $dir = '../views/images/';
          move_uploaded_file($_FILES['imagem']['tmp_name'][$i], $dir . $new_name);

          $imagem->setCaminho($dir);
          $imagem->setNome($new_name);

          array_push($imagens, $imagem);
        }

      }
      session_start();                    
      $objUsuario = $_SESSION['usuario'];

      $usuario = new Usuario();
      $usuario->setId($objUsuario->id);
      $usuario->setNome($objUsuario->nome);
      $usuario->setTelefone($objUsuario->telefone);
      $usuario->setEmail($objUsuario->email);


      $pet = new Pet();
      $pet->setRaca($raca);
      $pet->setCor($cor);
      $pet->setSexo($sexo);
      $pet->setCategoria($categoria);
      $pet->setImagem($imagens);
      $pet->setNascimento($dataNascimento);

      $anuncio = new Anuncio();
      $anuncio->setPet($pet);
      $anuncio->setUsuario($usuario);

      try {
        AnuncioController::salvar($anuncio);
        header('Location: ../views/index.php');
      } catch (PDOException $th) {
        throw $th;
      }

      break;
    case 'cadastrarUsuario':
      $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
      $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS);
      $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS);
      $senha = filter_input(INPUT_POST, 'senha', FILTER_SANITIZE_SPECIAL_CHARS);

      $usuario = new Usuario();

      $usuario->setNome($nome);
      $usuario->setTelefone($telefone);
      $usuario->setEmail($email);
      $usuario->setSenha($senha);

      try {
        $usuarioSalvo = UsuarioControlador::salvar($usuario);
        session_start();
        $_SESSION['usuario'] = $usuarioSalvo;
        header('Location: ../views/index.php');
      } catch (PDOException $erro) {
        throw $erro;
      }

      break;
    case 'autenticar':

      $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS);
      $senha = filter_input(INPUT_POST, 'senha', FILTER_SANITIZE_SPECIAL_CHARS);

      $usuario = new Usuario();

      $usuario->setEmail($email);
      $usuario->setSenha($senha);

      try {
        $autenticacao = UsuarioControlador::autenticar($usuario);

        if (!$autenticacao) {
          header('Location: ../views/login.html');
        } else {
          session_start();
          $_SESSION['usuario'] = $autenticacao;
          header('Location: ../views/index.php');
        }

      } catch (PDOException $erro) {
        throw $erro;
      }


      break;
  case 'listarTodos':
      $categoria = filter_input(INPUT_GET, 'categoria', FILTER_SANITIZE_SPECIAL_CHARS);
      session_start();
      $lista;
      if ($categoria !== NULL) {
        $lista = AnuncioController::anuncioPorCategoria($categoria);
      } else {
        $lista = AnuncioController::listarTodos();
      }
      
      
      
      $_SESSION['listagemGeral'] = $lista;
      header('Location: ../views/index.php');
      break;

    case 'mostrarAnuncio':
      $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);

      $anuncio = AnuncioController::anuncioPorId($id);
      $transacoes = TransacaoController::transacaoPorId($id);
      if ($anuncio !== false) {
        session_start();
        $_SESSION['anuncioPorId'] = $anuncio;
        $_SESSION['transacoes'] = $transacoes;
      }


      header('Location: ../views/paginaTransacao.php?anuncio='.$id);
      break;
    case 'deslogar':
    
      session_start();
      session_destroy();
      header('Location: ../views/index.php');
      break;
    case 'adotarPet':
      session_start();
      $anuncio = $_SESSION['anuncioPorId'];
      $usuarioComprador = $_SESSION['usuario'];

      $transacao = new Transacao;
      $transacao->setIdAnuncio($anuncio->getId());
      $transacao->setComprador($usuarioComprador->id);


      if (isset($_SESSION['transacao']) !== $transacao) {
        try {
          TransacaoController::iniciarTransacao($transacao);
          $_SESSION['transacao'] = $transacao;
        } catch (PDOException $erro) {
          throw $erro;
        }

      }
      header('Location: ../views/index.php');
      // redirect to sucess page or fail page
      break;
    case 'finalizarTransacao':
      $idAnuncio = filter_input(INPUT_GET, 'anuncio', FILTER_SANITIZE_SPECIAL_CHARS);
      AnuncioController::finalizarAnuncio($idAnuncio);
      header('Location: ../views/index.php');
      break;
    default:
      # code...
      break;
  }

?>