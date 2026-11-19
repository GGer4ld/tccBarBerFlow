
<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';

exigirAdministrador();

$database = new Database();
$db = $database->conectar();

$erro = '';
$sucesso = '';

/*
|--------------------------------------------------------------------------
| FUNÇÃO PARA EXCLUIR UMA LOGO ANTIGA
|--------------------------------------------------------------------------
*/

function excluirLogoAntiga($caminho)
{
    if (
        !empty($caminho) &&
        strpos($caminho, 'uploads/logos/') === 0
    ) {
        $arquivo = basename($caminho);
        $caminhoCompleto = __DIR__ . '/../../uploads/logos/' . $arquivo;

        if (is_file($caminhoCompleto)) {
            unlink($caminhoCompleto);
        }
    }
}

/*
|--------------------------------------------------------------------------
| BUSCAR CONFIGURAÇÃO
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id_configuracao,
            nome_barbearia,
            telefone,
            whatsapp,
            email,
            endereco,
            horario_abertura,
            horario_fechamento,
            percentual_comissao_padrao,
            alerta_estoque_ativo,
            tema,
            logo,
            atualizado_em
        FROM configuracoes
        ORDER BY id_configuracao ASC
        LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->execute();

$configuracao = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| VALORES PADRÃO
|--------------------------------------------------------------------------
*/

$id_configuracao = $configuracao['id_configuracao'] ?? '';
$nome_barbearia = $configuracao['nome_barbearia'] ?? 'BarberFlow';
$telefone = $configuracao['telefone'] ?? '';
$whatsapp = $configuracao['whatsapp'] ?? '';
$email = $configuracao['email'] ?? '';
$endereco = $configuracao['endereco'] ?? '';
$horario_abertura = $configuracao['horario_abertura'] ?? '';
$horario_fechamento = $configuracao['horario_fechamento'] ?? '';

$percentual_comissao_padrao =
    $configuracao['percentual_comissao_padrao'] ?? '40.00';

$alerta_estoque_ativo =
    $configuracao['alerta_estoque_ativo'] ?? 'Sim';

$tema = $configuracao['tema'] ?? 'Claro';
$logo = $configuracao['logo'] ?? '';

$logoAnterior = $logo;
$novoArquivoLogo = '';

/*
|--------------------------------------------------------------------------
| PROCESSAR FORMULÁRIO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome_barbearia = trim($_POST['nome_barbearia'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');

    $horario_abertura = $_POST['horario_abertura'] ?? '';
    $horario_fechamento = $_POST['horario_fechamento'] ?? '';

    $percentual_comissao_padrao =
        $_POST['percentual_comissao_padrao'] ?? '40.00';

    $alerta_estoque_ativo =
        $_POST['alerta_estoque_ativo'] ?? 'Sim';

    $tema = $_POST['tema'] ?? 'Claro';

    $removerLogo = isset($_POST['remover_logo']);

    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÕES
    |--------------------------------------------------------------------------
    */

    if ($nome_barbearia === '') {

        $erro = 'Informe o nome da barbearia.';

    } elseif (
        $email !== '' &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $erro = 'Informe um endereço de e-mail válido.';

    } elseif (
        !is_numeric($percentual_comissao_padrao) ||
        $percentual_comissao_padrao < 0 ||
        $percentual_comissao_padrao > 100
    ) {

        $erro = 'O percentual de comissão deve estar entre 0 e 100.';

    } elseif (
        $horario_abertura !== '' &&
        $horario_fechamento !== '' &&
        $horario_fechamento <= $horario_abertura
    ) {

        $erro = 'O horário de fechamento deve ser maior que o horário de abertura.';

    } elseif (
        !in_array($alerta_estoque_ativo, ['Sim', 'Não'], true)
    ) {

        $erro = 'Configuração de alerta de estoque inválida.';

    } elseif (
        !in_array($tema, ['Claro', 'Escuro'], true)
    ) {

        $erro = 'Tema selecionado inválido.';

    }

    /*
    |--------------------------------------------------------------------------
    | PROCESSAR UPLOAD DA LOGO
    |--------------------------------------------------------------------------
    */

    if ($erro === '' && !$removerLogo) {

        if (
            isset($_FILES['logo']) &&
            $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            $arquivoLogo = $_FILES['logo'];

            if ($arquivoLogo['error'] !== UPLOAD_ERR_OK) {

                $erro = 'Não foi possível enviar a imagem. Tente novamente.';

            } elseif ($arquivoLogo['size'] > 2 * 1024 * 1024) {

                $erro = 'A logo deve ter no máximo 2 MB.';

            } else {

                $tiposPermitidos = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $tipoImagem = $finfo->file($arquivoLogo['tmp_name']);

                if (!isset($tiposPermitidos[$tipoImagem])) {

                    $erro = 'Formato inválido. Envie uma imagem JPG, PNG ou WebP.';

                } elseif (getimagesize($arquivoLogo['tmp_name']) === false) {

                    $erro = 'O arquivo enviado não é uma imagem válida.';

                } else {

                    $pastaLogo = __DIR__ . '/../../uploads/logos/';

                    if (
                        !is_dir($pastaLogo) &&
                        !mkdir($pastaLogo, 0755, true)
                    ) {

                        $erro = 'Não foi possível criar a pasta de imagens.';

                    } else {

                        $nomeArquivo =
                            bin2hex(random_bytes(16)) .
                            '.' .
                            $tiposPermitidos[$tipoImagem];

                        $destinoLogo = $pastaLogo . $nomeArquivo;

                        if (
                            move_uploaded_file(
                                $arquivoLogo['tmp_name'],
                                $destinoLogo
                            )
                        ) {

                            $novoArquivoLogo = 'uploads/logos/' . $nomeArquivo;
                            $logo = $novoArquivoLogo;

                        } else {

                            $erro = 'Não foi possível salvar a imagem enviada.';
                        }
                    }
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | REMOVER LOGO
    |--------------------------------------------------------------------------
    */

    if ($erro === '' && $removerLogo) {
        $logo = '';
    }

    /*
    |--------------------------------------------------------------------------
    | SALVAR CONFIGURAÇÕES NO BANCO
    |--------------------------------------------------------------------------
    */

    if ($erro === '') {

        try {

            if ($id_configuracao) {

                $sql = "UPDATE configuracoes SET
                            nome_barbearia = :nome_barbearia,
                            telefone = :telefone,
                            whatsapp = :whatsapp,
                            email = :email,
                            endereco = :endereco,
                            horario_abertura = :horario_abertura,
                            horario_fechamento = :horario_fechamento,
                            percentual_comissao_padrao = :percentual_comissao_padrao,
                            alerta_estoque_ativo = :alerta_estoque_ativo,
                            tema = :tema,
                            logo = :logo
                        WHERE id_configuracao = :id_configuracao";

            } else {

                $sql = "INSERT INTO configuracoes (
                            nome_barbearia,
                            telefone,
                            whatsapp,
                            email,
                            endereco,
                            horario_abertura,
                            horario_fechamento,
                            percentual_comissao_padrao,
                            alerta_estoque_ativo,
                            tema,
                            logo
                        ) VALUES (
                            :nome_barbearia,
                            :telefone,
                            :whatsapp,
                            :email,
                            :endereco,
                            :horario_abertura,
                            :horario_fechamento,
                            :percentual_comissao_padrao,
                            :alerta_estoque_ativo,
                            :tema,
                            :logo
                        )";
            }

            $stmt = $db->prepare($sql);

            $stmt->bindValue(':nome_barbearia', $nome_barbearia);

            $stmt->bindValue(
                ':telefone',
                $telefone !== '' ? $telefone : null
            );

            $stmt->bindValue(
                ':whatsapp',
                $whatsapp !== '' ? $whatsapp : null
            );

            $stmt->bindValue(
                ':email',
                $email !== '' ? $email : null
            );

            $stmt->bindValue(
                ':endereco',
                $endereco !== '' ? $endereco : null
            );

            $stmt->bindValue(
                ':horario_abertura',
                $horario_abertura !== '' ? $horario_abertura : null
            );

            $stmt->bindValue(
                ':horario_fechamento',
                $horario_fechamento !== '' ? $horario_fechamento : null
            );

            $stmt->bindValue(
                ':percentual_comissao_padrao',
                $percentual_comissao_padrao
            );

            $stmt->bindValue(
                ':alerta_estoque_ativo',
                $alerta_estoque_ativo
            );

            $stmt->bindValue(':tema', $tema);

            $stmt->bindValue(
                ':logo',
                $logo !== '' ? $logo : null
            );

            if ($id_configuracao) {
                $stmt->bindValue(
                    ':id_configuracao',
                    $id_configuracao,
                    PDO::PARAM_INT
                );
            }

            $stmt->execute();

            if (!$id_configuracao) {
                $id_configuracao = $db->lastInsertId();
            }

            /*
            |--------------------------------------------------------------------------
            | EXCLUIR ARQUIVO ANTIGO APÓS SALVAR NO BANCO
            |--------------------------------------------------------------------------
            */

            if (
                $logoAnterior !== '' &&
                ($removerLogo || $novoArquivoLogo !== '')
            ) {
                excluirLogoAntiga($logoAnterior);
            }

            $logoAnterior = $logo;
            $novoArquivoLogo = '';

            $sucesso = $removerLogo
                ? 'Logo removida e configurações salvas com sucesso.'
                : 'Configurações salvas com sucesso.';

        } catch (PDOException $e) {

            if ($novoArquivoLogo !== '') {
                excluirLogoAntiga($novoArquivoLogo);
            }

            $erro = 'Ocorreu um erro ao salvar as configurações.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ATUALIZAR EXIBIÇÃO DA LOGO APÓS ERRO
    |--------------------------------------------------------------------------
    */

    if ($erro !== '' && $novoArquivoLogo !== '') {
        excluirLogoAntiga($novoArquivoLogo);
        $logo = $logoAnterior;
    }
}

?>


<?php include __DIR__ . '/../../includes/header.php'; ?>


<style>
/* =====================================
   CONFIGURAÇÕES - BARBERFLOW
===================================== */

.configuracoes-page {
    padding: 30px;
    background: #F4F7FB;
}

.configuracoes-page .page-header {
    margin-bottom: 28px;
}

.configuracoes-page .page-header h1 {
    font-size: 27px;
    font-weight: 700;
    color: #172554;
    margin-bottom: 7px;
}

.configuracoes-page .page-header p {
    color: #64748B;
    font-size: 14px;
}

/* Cartões */

.configuracoes-page .configuracao-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 22px;
    box-shadow: 0 3px 12px rgba(15, 23, 42, 0.035);
    transition: box-shadow .2s ease;
}

.configuracoes-page .configuracao-card:hover {
    box-shadow: 0 7px 20px rgba(15, 23, 42, 0.06);
}

.configuracoes-page .card-header {
    padding: 0 0 19px;
    margin-bottom: 22px;
    border-bottom: 1px solid #E2E8F0;
}

.configuracoes-page .card-header h2 {
    color: #1E293B;
    font-size: 17px;
    font-weight: 700;
    margin-bottom: 6px;
}

.configuracoes-page .card-header span {
    color: #64748B;
    font-size: 13px;
}

/* Campos */

.configuracoes-page .form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
}

.configuracoes-page .form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 0;
}

.configuracoes-page .form-group-full {
    grid-column: 1 / -1;
}

.configuracoes-page .form-group label {
    color: #334155;
    font-size: 13px;
    font-weight: 600;
}

.configuracoes-page .form-group input,
.configuracoes-page .form-group select {
    width: 100%;
    min-height: 45px;
    padding: 11px 13px;
    border: 1px solid #CBD5E1;
    border-radius: 9px;
    background: #FFFFFF;
    color: #1E293B;
    font-size: 14px;
    box-sizing: border-box;
    transition: border-color .2s, box-shadow .2s;
}

.configuracoes-page .form-group input:focus,
.configuracoes-page .form-group select:focus {
    outline: none;
    border-color: #2563EB;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}

.configuracoes-page .form-group small {
    color: #64748B;
    font-size: 12px;
    line-height: 1.5;
}

/* Botões */

.configuracoes-page .form-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 12px;
    padding: 20px 0 5px;
    margin-top: 5px;
    border-top: 1px solid #E2E8F0;
}

.configuracoes-page .form-actions .btn {
    min-height: 43px;
    padding: 11px 18px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
}

/* Mensagens */

.configuracoes-page .alert {
    margin-bottom: 20px;
    border-radius: 10px;
}

/* Responsividade */

@media (max-width: 768px) {
    .configuracoes-page {
        padding: 20px;
    }

    .configuracoes-page .configuracao-card {
        padding: 19px;
    }

    .configuracoes-page .form-grid {
        grid-template-columns: 1fr;
        gap: 17px;
    }

    .configuracoes-page .form-group-full {
        grid-column: auto;
    }
}

@media (max-width: 480px) {
    .configuracoes-page {
        padding: 15px;
    }

    .configuracoes-page .form-actions {
        flex-direction: column-reverse;
        align-items: stretch;
    }

    .configuracoes-page .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}


/* Pré-visualização da logo */

.configuracoes-page .logo-preview-container {
    min-height: 150px;
    max-width: 350px;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #F8FAFC;
    border: 1px dashed #CBD5E1;
    border-radius: 12px;
}

.configuracoes-page .logo-preview-container img {
    display: block;
    max-width: 100%;
    max-height: 140px;
    object-fit: contain;
}

.configuracoes-page .logo-preview-container span {
    color: #94A3B8;
    font-size: 13px;
    text-align: center;
}

.configuracoes-page .logo-preview-container span i {
    display: block;
    font-size: 30px;
    margin-bottom: 10px;
}


</style>


<div class="app">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="main">

        <?php include __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content configuracoes-page">

            <div class="page-header">

                <div>

                    <h1>Configurações</h1>

                    <p>
                        Gerencie as configurações gerais da barbearia.
                    </p>

                </div>

            </div>


            <?php if ($erro !== ''): ?>

                <div class="alert alert-danger">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?= htmlspecialchars($erro) ?>

                </div>

            <?php endif; ?>


            <?php if ($sucesso !== ''): ?>

                <div class="alert alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    <?= htmlspecialchars($sucesso) ?>

                </div>

            <?php endif; ?>


            <form method="POST" enctype="multipart/form-data">


                <!-- DADOS DA BARBEARIA -->

                <div class="card configuracao-card">

                    <div class="card-header">

                        <div>

                            <h2>Dados da barbearia</h2>

                            <span>
                                Informações principais do estabelecimento.
                            </span>

                        </div>

                    </div>


                    <div class="form-grid">


                        <div class="form-group">

                            <label for="nome_barbearia">
                                Nome da barbearia *
                            </label>

                            <input
                                type="text"
                                id="nome_barbearia"
                                name="nome_barbearia"
                                value="<?= htmlspecialchars($nome_barbearia) ?>"
                                maxlength="100"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="telefone">
                                Telefone
                            </label>

                            <input
                                type="text"
                                id="telefone"
                                name="telefone"
                                value="<?= htmlspecialchars($telefone) ?>"
                                maxlength="20"
                                placeholder="(00) 0000-0000"
                            >

                        </div>


                        <div class="form-group">

                            <label for="whatsapp">
                                WhatsApp
                            </label>

                            <input
                                type="text"
                                id="whatsapp"
                                name="whatsapp"
                                value="<?= htmlspecialchars($whatsapp) ?>"
                                maxlength="20"
                                placeholder="(00) 00000-0000"
                            >

                        </div>


                        <div class="form-group">

                            <label for="email">
                                E-mail
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($email) ?>"
                                maxlength="100"
                                placeholder="contato@barbearia.com"
                            >

                        </div>


                        <div class="form-group form-group-full">

                            <label for="endereco">
                                Endereço
                            </label>

                            <input
                                type="text"
                                id="endereco"
                                name="endereco"
                                value="<?= htmlspecialchars($endereco) ?>"
                                maxlength="255"
                                placeholder="Rua, número, bairro e cidade"
                            >

                        </div>

                    </div>

                </div>


<!-- LOGO DA BARBEARIA -->

<div class="card configuracao-card">

    <div class="card-header">
        <div>
            <h2>Logo da barbearia</h2>
            <span>
                Envie a imagem que será utilizada na identidade visual do sistema.
            </span>
        </div>
    </div>

    <div class="form-grid">

        <div class="form-group form-group-full">

            <label for="logo">
                Selecionar imagem
            </label>

            <input
                type="file"
                id="logo"
                name="logo"
                accept="image/jpeg,image/png,image/webp"
            >

            <small>
                Formatos aceitos: JPG, PNG e WebP. Tamanho máximo: 2 MB.
            </small>

        </div>

        <div class="form-group form-group-full">

            <label>Pré-visualização da logo</label>

            <div class="logo-preview-container">

                <img
                    id="logo-preview"
                    src="<?= !empty($logo)
                        ? '../../' . htmlspecialchars($logo)
                        : '' ?>"
                    alt="Pré-visualização da logo"
                    style="<?= !empty($logo)
                        ? ''
                        : 'display: none;' ?>"
                >

                <span
                    id="logo-placeholder"
                    style="<?= !empty($logo)
                        ? 'display: none;'
                        : '' ?>"
                >
                    <i class="fa-solid fa-image"></i>
                    Nenhuma logo cadastrada
                </span>

            </div>

        </div>

        <?php if (!empty($logo)): ?>

            <div class="form-group form-group-full">

                <button
                    type="submit"
                    name="remover_logo"
                    value="1"
                    class="btn btn-secondary"
                    onclick="return confirm('Deseja realmente remover a logo atual?');"
                >
                    <i class="fa-solid fa-trash"></i>
                    Remover logo atual
                </button>

            </div>

        <?php endif; ?>

    </div>

</div>


                <!-- HORÁRIO -->

                <div class="card configuracao-card">

                    <div class="card-header">

                        <div>

                            <h2>Horário de funcionamento</h2>

                            <span>
                                Defina o horário de atendimento da barbearia.
                            </span>

                        </div>

                    </div>


                    <div class="form-grid">


                        <div class="form-group">

                            <label for="horario_abertura">
                                Horário de abertura
                            </label>

                            <input
                                type="time"
                                id="horario_abertura"
                                name="horario_abertura"
                                value="<?= htmlspecialchars($horario_abertura) ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="horario_fechamento">
                                Horário de fechamento
                            </label>

                            <input
                                type="time"
                                id="horario_fechamento"
                                name="horario_fechamento"
                                value="<?= htmlspecialchars($horario_fechamento) ?>"
                            >

                        </div>

                    </div>

                </div>


                <!-- FINANCEIRO -->

                <div class="card configuracao-card">

                    <div class="card-header">

                        <div>

                            <h2>Configurações financeiras</h2>

                            <span>
                                Defina o percentual de comissão padrão.
                            </span>

                        </div>

                    </div>


                    <div class="form-grid">


                        <div class="form-group">

                            <label for="percentual_comissao_padrao">
                                Comissão padrão (%)
                            </label>

                            <input
                                type="number"
                                id="percentual_comissao_padrao"
                                name="percentual_comissao_padrao"
                                value="<?= htmlspecialchars($percentual_comissao_padrao) ?>"
                                min="0"
                                max="100"
                                step="0.01"
                            >

                            <small>
                                Esse percentual será utilizado como padrão
                                quando aplicável.
                            </small>

                        </div>

                    </div>

                </div>


                <!-- ESTOQUE E APARÊNCIA -->
                
                <div class="card configuracao-card">

                    <div class="card-header">

                        <div>

                            <h2>Preferências do sistema</h2>

                            <span>
                                Configure alertas e aparência do sistema.
                            </span>

                        </div>

                    </div>


                    <div class="form-grid">


                        <div class="form-group">

                            <label for="alerta_estoque_ativo">
                                Alertas de estoque
                            </label>

                            <select
                                id="alerta_estoque_ativo"
                                name="alerta_estoque_ativo"
                            >

                                <option
                                    value="Sim"
                                    <?= $alerta_estoque_ativo === 'Sim'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Ativados
                                </option>

                                <option
                                    value="Não"
                                    <?= $alerta_estoque_ativo === 'Não'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Desativados
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="tema">
                                Tema do sistema
                            </label>

                            <select
                                id="tema"
                                name="tema"
                            >

                                <option
                                    value="Claro"
                                    <?= $tema === 'Claro'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Claro
                                </option>

                                <option
                                    value="Escuro"
                                    <?= $tema === 'Escuro'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Escuro
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <!-- AÇÕES -->

                <div class="form-actions">

                    <button
                        type="reset"
                        class="btn btn-secondary"
                    >

                        <i class="fa-solid fa-rotate-left"></i>

                        Restaurar

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fa-solid fa-floppy-disk"></i>

                        Salvar Configurações

                    </button>

                </div>


            </form>

        </main>

    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const campoLogo = document.getElementById('logo');
    const preview = document.getElementById('logo-preview');
    const placeholder = document.getElementById('logo-placeholder');

    if (!campoLogo) {
        return;
    }

    campoLogo.addEventListener('change', function () {

        const arquivo = this.files[0];

        if (!arquivo) {
            return;
        }

        const tiposPermitidos = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!tiposPermitidos.includes(arquivo.type)) {
            alert('Selecione uma imagem JPG, PNG ou WebP.');
            this.value = '';
            return;
        }

        if (arquivo.size > 2 * 1024 * 1024) {
            alert('A imagem deve ter no máximo 2 MB.');
            this.value = '';
            return;
        }

        const leitor = new FileReader();

        leitor.onload = function (evento) {
            preview.src = evento.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        };

        leitor.readAsDataURL(arquivo);
    });

});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
