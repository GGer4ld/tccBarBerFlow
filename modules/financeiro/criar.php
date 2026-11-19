<?php

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../app/config/database.php';
exigirAdministrador();


$database = new Database();
$db = $database->conectar();



if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $sql = "INSERT INTO lancamentos_financeiros
    (
        tipo,
        categoria,
        descricao,
        valor
    )

    VALUES
    (
        :tipo,
        :categoria,
        :descricao,
        :valor
    )";


    $stmt = $db->prepare($sql);


    $stmt->bindValue(
        ':tipo',
        $_POST['tipo']
    );


    $stmt->bindValue(
        ':categoria',
        $_POST['categoria']
    );


    $stmt->bindValue(
        ':descricao',
        $_POST['descricao']
    );


    $stmt->bindValue(
        ':valor',
        $_POST['valor']
    );


    $stmt->execute();


    header(
        "Location: index.php"
    );

    exit;
}


?>


<?php include __DIR__ . '/../../includes/header.php'; ?>


<div class="app">


    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>


    <div class="main">


        <?php include __DIR__ . '/../../includes/navbar.php'; ?>


        <main class="content">


            <div class="page-header">

                <div>

                    <h1>
                        Novo lançamento
                    </h1>

                    <p>
                        Cadastre uma entrada ou saída financeira.
                    </p>

                </div>


                <a href="index.php" class="btn btn-secondary">

                    <i class="fa-solid fa-arrow-left"></i>

                    Voltar

                </a>


            </div>




            <div class="card">


                <div class="card-header">

                    <h2>
                        Dados financeiros
                    </h2>

                </div>



                <form method="POST">



                    <div class="form-grid">


                        <div class="form-group">

                            <label>
                                Tipo
                            </label>


                            <select name="tipo" required>

                                <option value="Entrada">
                                    Entrada
                                </option>


                                <option value="Saída">
                                    Saída
                                </option>


                            </select>


                        </div>



                        <div class="form-group">


                            <label>
                                Categoria
                            </label>


                            <input
                                type="text"
                                name="categoria"
                                placeholder="Ex: Corte, Produto, Aluguel"
                                required>


                        </div>


                    </div>




                    <div class="form-group">


                        <label>
                            Descrição
                        </label>


                        <textarea
                            name="descricao"
                            rows="4"
                            placeholder="Descrição do lançamento">
</textarea>


                    </div>





                    <div class="form-group">


                        <label>
                            Valor
                        </label>


                        <input
                            type="number"
                            step="0.01"
                            name="valor"
                            placeholder="0,00"
                            required>


                    </div>





                    <div class="form-actions">


                        <button
                            type="submit"
                            class="btn btn-primary">


                            <i class="fa-solid fa-save"></i>

                            Salvar lançamento


                        </button>



                    </div>



                </form>


            </div>



        </main>


    </div>


</div>



<?php include __DIR__ . '/../../includes/footer.php'; ?>