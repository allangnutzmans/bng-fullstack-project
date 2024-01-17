<div class="container-fluid mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-6 col-sm-8 col-10">
            <div class="card p-4">

                <div class="d-flex align-items-center justify-content-center my-4">
                    <img src="assets/images/logo_64.png" class="img-fluid me-3">
                    <h2><strong><?= APP_NAME ?></strong></h2>
                </div>

                <div class="row justify-content-center">
                    <div class="col-8">

                        <form action="?mt=main&mt=definePasswordSubmit" method="post" novalidate>

                            <input type="hidden" name="purl" value="<?= $purl ?>">
                            <input type="hidden" name="id" value="<?= aes_encrypt($id) ?>">

                            <p class="mb-3">Para concluir seu cadastro defina sua <strong> password</strong>.</p>

                            <div class="mb-3">
                                <label for="text_password" class="form-label">Password</label>
                                <input type="password" name="text_password" id="text_password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="text_repeat_password" class="form-label">Repetir password</label>
                                <input type="password" name="text_repeat_password" id="text_repeat_password" class="form-control" required>
                            </div>
                            
                            <div class="mb-3 text-center">
                                <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-key me-2"></i>Definir password</button>
                            </div>

                            <?php if(isset($validation_errors)):?>
                                <div class="alert alert-danger p-2 text-center">

                                    <?php foreach ($validation_errors as $error): ?>
                                        <li><?= $error ?></li>
                                    <?php endforeach;?>
                                </div>
                            <?php endif;?>


                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>