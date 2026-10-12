<?php

declare(strict_types=1);

require_once __DIR__ . '/../../components/head.php';

/*
|--------------------------------------------------------------------------
| Permissão
|--------------------------------------------------------------------------
*/

requireStaff();

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

/*
|--------------------------------------------------------------------------
| Solicitação
|--------------------------------------------------------------------------
*/

$requestId = filter_input(
    INPUT_POST,
    'request_id',
    FILTER_VALIDATE_INT
);

if (
    $requestId === false ||
    $requestId === null ||
    $requestId <= 0
) {

    flash(
        'error',
        'Solicitação inválida.'
    );

    redirect('/admin');
}

/*
|--------------------------------------------------------------------------
| Rejeitar
|--------------------------------------------------------------------------
*/

try {

    beginWriteTransaction($usersPdo);
    validateDatabaseActor($usersPdo,(int)userId(),true);

    /*
    |--------------------------------------------------------------------------
    | Buscar solicitação
    |--------------------------------------------------------------------------
    */

    $statement = $usersPdo->prepare(
        '
        SELECT
            id,
            name,
            r6_nickname,
            r6_rank,
            status
        FROM access_requests
        WHERE id = :id
        LIMIT 1
        '
    );

    $statement->execute([
        ':id' => $requestId,
    ]);

    $request = $statement->fetch();

    if (!$request) {

        throw new RuntimeException(
            'Solicitação não encontrada.'
        );
    }

    if ($request['status'] !== 'pending') {

        throw new RuntimeException(
            'Esta solicitação já foi analisada.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Atualizar solicitação
    |--------------------------------------------------------------------------
    */

    $statement = $usersPdo->prepare(
        '
        UPDATE access_requests
        SET
            status = :status,
            reviewed_by = :reviewed_by,
            reviewed_at = CURRENT_TIMESTAMP
        WHERE id = :id
        AND status = :current_status
        '
    );

    $statement->execute([
        ':status' =>
            'rejected',

        ':reviewed_by' =>
            userId(),

        ':id' =>
            $requestId,

        ':current_status' =>
            'pending',
    ]);

    if ($statement->rowCount() !== 1) {

        throw new RuntimeException(
            'Não foi possível concluir a rejeição.'
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

    $statement->execute([
        ':type' =>
            'access_rejected',

        ':description' =>
            'Solicitação de acesso rejeitada.',

        ':user_id' =>
            null,

        ':staff_user_id' =>
            userId(),

        ':additional_data' =>
            json_encode(
                [
                    'request_id' =>
                        $requestId,

                    'r6_nickname' =>
                        $request['r6_nickname'],

                    'r6_rank' =>
                        $request['r6_rank'],
                ],
                JSON_UNESCAPED_UNICODE
            ),
    ]);

    /*
    |--------------------------------------------------------------------------
    | Finalizar
    |--------------------------------------------------------------------------
    */

    $usersPdo->commit();

    flash(
        'success',
        'Solicitação de '
        . $request['r6_nickname']
        . ' rejeitada.'
    );

    redirect('/admin');

} catch (Throwable $exception) {

    if ($usersPdo->inTransaction()) {
        $usersPdo->rollBack();
    }

    flash(
        'error',
        $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'Não foi possível rejeitar a solicitação.'
    );

    redirect('/admin');
}
