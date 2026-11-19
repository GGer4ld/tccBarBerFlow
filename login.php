<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Login | BarberFlow</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="assets/css/style.css">

    <link
        rel="stylesheet"
        href="assets/css/login.css">

    <style>
        .cadastro-login a {
            color: #7C3AED;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .cadastro-login a:hover {
            color: #6D28D9;
            text-decoration: underline;
        }


        .sucesso-login {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 20px;

            padding: 12px 15px;

            border-radius: 8px;

            background: #DCFCE7;

            color: #15803D;

            font-size: 13px;

        }


        /* ==================================================
           CAMPO DE SENHA
           ================================================== */

        .senha-wrapper {

            position: relative;

            width: 100%;

        }

        .senha-wrapper input {

            padding-right: 45px;

        }

        .toggle-senha {

            position: absolute;

            top: 50%;

            right: 10px;

            transform: translateY(-50%);

            width: 32px !important;
            height: 32px !important;

            margin: 0 !important;
            padding: 0 !important;

            display: flex;
            align-items: center;
            justify-content: center;

            background: transparent !important;

            color: #475569 !important;

            font-size: 16px;

            border: none;

            border-radius: 6px;

            cursor: pointer;

            transition: color 0.2s ease;

        }

        .toggle-senha:hover {

            color: #2563EB;

        }

        .toggle-senha {
            display: none;
        }

        .login-social-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0;
            color: #94A3B8;
            font-size: 13px;
        }

        .login-social-divider::before,
        .login-social-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #E2E8F0;
        }

        .login-google-button {
            width: 100%;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #FFFFFF;
            color: #334155;
            border: 1px solid #CBD5E1;
            border-radius: 10px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            transition: .2s ease;
            box-sizing: border-box;
        }

        .login-google-button:hover {
            background: #F8FAFC;
            border-color: #94A3B8;
            box-shadow: 0 3px 10px rgba(15, 23, 42, .08);
        }

        .login-google-button i {
            font-size: 17px;
            color: #4285F4;
        }
    </style>

</head>

<body class="login-page">

    <div class="login-container">

        <div class="login-left">

            <h1>BarberFlow</h1>

            <p>
                Sistema profissional de gerenciamento para barbearias.
            </p>

        </div>


        <div class="login-right">

            <?php if (isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso'): ?>

                <div class="sucesso-login">

                    <i class="fa-solid fa-circle-check"></i>

                    Cadastro realizado com sucesso! Agora você pode entrar na sua conta.

                </div>

            <?php endif; ?>


            <?php if (isset($_GET['erro'])): ?>

                <div class="erro-login">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    E-mail ou senha inválidos.

                </div>

            <?php endif; ?>


            <form
                class="login-card"
                action="app/controllers/LoginController.php"
                method="POST">

                <h2>Entrar</h2>


                <div class="input-group">

                    <label>E-mail</label>

                    <input
                        type="email"
                        name="email"
                        placeholder="Digite seu e-mail"
                        required>

                </div>


                <div class="input-group">

                    <label>Senha</label>

                    <div class="senha-wrapper">

                        <input
                            type="password"
                            name="senha"
                            id="senha"
                            placeholder="Digite sua senha"
                            required>

                        <button
                            type="button"
                            class="toggle-senha"
                            id="toggleSenha"
                            aria-label="Mostrar senha">

                            <i class="fa-solid fa-eye"></i>

                        </button>

                    </div>


                    <?php if (isset($_GET['senha_incorreta']) && $_GET['senha_incorreta'] === '1'): ?>
                        <div class="esqueci-senha">
                            <a href="recuperar-senha.php">
                                <i class="fa-solid fa-key"></i>
                                Esqueci minha senha
                            </a>
                        </div>
                    <?php endif; ?>

                </div>


                <button type="submit">Entrar</button>

                <div class="login-social-divider">
                    <span>ou continue com</span>
                </div>

                <a
                    href="app/controllers/GoogleLoginController.php"
                    class="login-google-button">
                    <i class="fa-brands fa-google"></i>
                    <span>Continuar com Google</span>
                </a>


                <div class="cadastro-login">

                    <span>
                        Ainda não possui uma conta?
                    </span>

                    <a href="cadastro.php">
                        Cadastre-se
                    </a>

                </div>

            </form>

        </div>

    </div>


    <script>
        const toggleSenha = document.getElementById('toggleSenha');
        const senha = document.getElementById('senha');

        function atualizarOlho() {
            if (senha.value.length > 0) {
                toggleSenha.style.display = 'flex';
            } else {
                toggleSenha.style.display = 'none';

                senha.type = 'password';
                toggleSenha.innerHTML = '<i class="fa-solid fa-eye"></i>';
                toggleSenha.setAttribute('aria-label', 'Mostrar senha');
            }
        }

        senha.addEventListener('input', atualizarOlho);

        toggleSenha.addEventListener('click', function() {
            if (senha.type === 'password') {
                senha.type = 'text';
                toggleSenha.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
                toggleSenha.setAttribute('aria-label', 'Ocultar senha');
            } else {
                senha.type = 'password';
                toggleSenha.innerHTML = '<i class="fa-solid fa-eye"></i>';
                toggleSenha.setAttribute('aria-label', 'Mostrar senha');
            }
        });

        atualizarOlho();
    </script>

</body>

</html>