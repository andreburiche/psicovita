<?php

namespace App\Support;

final class Permissions
{
    public const SOLICITACOES_VISUALIZAR = 'solicitacoes.visualizar';

    public const SOLICITACOES_CRIAR = 'solicitacoes.criar';

    public const SOLICITACOES_EDITAR = 'solicitacoes.editar';

    public const SOLICITACOES_EXCLUIR = 'solicitacoes.excluir';

    public const SOLICITACOES_BAIXAR_PDF = 'solicitacoes.baixar_pdf';

    public const SOLICITACOES_ANEXAR = 'solicitacoes.anexar';

    public const SOLICITACOES_ENVIAR_EMAIL = 'solicitacoes.enviar_email';

    /** @return list<string> */
    public static function documentRequestPermissions(): array
    {
        return config('document_requests.permissions', []);
    }
}
