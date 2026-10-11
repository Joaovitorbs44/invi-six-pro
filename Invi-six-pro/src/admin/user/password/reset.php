<?php

declare(strict_types=1);

require_once __DIR__
    . '/../../../components/head.php';

/*
|--------------------------------------------------------------------------
| Somente Admin
|--------------------------------------------------------------------------
*/

requireAdmin();

/*
|--------------------------------------------------------------------------
| Somente POST
|--------------------------------------------------------------------------
*/

if (!isPost()) {

    http_response_code(405);

    header('Allow: POST');

    exit('Método não permitido.');
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

verifyCsrf();
require_once __DIR__ . '/../../../includes/rate-limit.php';
enforceRateLimit('staff-password-reset:' . userId(), 15, 600, false);

/*
|--------------------------------------------------------------------------
| Dados
|--------------------------------------------------------------------------
*/

$userId = filter_input(
    INPUT_POST,
    'user_id',
    FILTER_VALIDATE_INT
);

$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

/*
|--------------------------------------------------------------------------
| Validação
|--------------------------------------------------------------------------
*/

if (
    $userId === false ||
    $userId === null ||
    $userId <= 0
) {

    flash(
        'error',
        'Usuário inválido.'
    );

    redirect('/admin');
}

if (mb_strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {

    flash(
        'error',
        'Use pelo menos 8 caracteres e no máximo 72 bytes na senha.'
    );

    redirect('/admin');
}

/*
|--------------------------------------------------------------------------
| Buscar usuário
|--------------------------------------------------------------------------
*/

$statement = $usersPdo->prepare(
    '
    SELECT
        id,
        name,
        r6_nickname,
        role,
        status
    FROM users
    WHERE id = :id
    LIMIT 1
    '
);

$statement->execute([
    ':id' => $userId,
]);

$user = $statement->fetch();

if (!$user) {

    flash(
        'error',
        'Usuário não encontrado.'
    );

    redirect('/admin');
}

/*
|--------------------------------------------------------------------------
| Hash
|--------------------------------------------------------------------------
*/

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

if ($passwordHash === false) {

    flash(
        'error',
        'Não foi possível gerar a nova senha.'
    );

    redirect('/admin');
}

/*
|--------------------------------------------------------------------------
| Atualização
|--------------------------------------------------------------------------
*/

try {

    beginWriteTransaction($usersPdo);
    validateDatabaseAdmin($usersPdo,(int)userId());

    /*
    |--------------------------------------------------------------------------
    | Atualizar senha
    |--------------------------------------------------------------------------
    */

    $statement = $usersPdo->prepare(
        '
        UPDATE users
        SET
            password_hash = :password_hash,
            session_version = session_version + 1,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
        '
    );

    $statement->execute([
        ':password_hash' =>
            $passwordHash,

        ':id' =>
            $userId,
    ]);

    if ($statement->rowCount() !== 1) {

        throw new RuntimeException(
            'Não foi possível redefinir a senha.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Log
    |--------------------------------------------------------------------------
    */

    $statement = $usersPdo->prepare(
        '
        INSERT INTO account_logs (
            type,
            description,
            user_id,
            staff_user_id,
            additional_data
        )
        VALUES (
            :type,
            :description,
            :user_id,
            :staff_user_id,
            :additional_data
        )
        '
    );

    $additionalData = json_encode(
        [
            'r6_nickname' =>
                $user['r6_nickname'],
        ],
        JSON_UNESCAPED_UNICODE
    );

    $statement->execute([
        ':type' =>
            'password_reset',

        ':description' =>
            'Senha do usuário redefinida pelo administrador.',

        ':user_id' =>
            $userId,

        ':staff_user_id' =>
            userId(),

        ':additional_data' =>
            $additionalData,
    ]);

    $usersPdo->commit();

    flash(
        'success',
        'Senha de '
        . $user['r6_nickname']
        . ' redefinida com sucesso.'
    );

    redirect('/admin');

} catch (Throwable $exception) {

    if ($usersPdo->inTransaction()) {
        $usersPdo->rollBack();
    }

    flash(
        'error',
        'Não foi possível redefinir a senha.'
    );

    redirect('/admin');
}
