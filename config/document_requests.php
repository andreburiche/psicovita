<?php

return [
    'suggested_documents' => [
        'Histórico escolar',
        'Relatório pedagógico',
        'Laudo médico',
        'Relatório de outro profissional de saúde',
        'Atestado',
        'Prontuário externo',
        'Declaração da empresa',
    ],

    'permissions' => [
        'solicitacoes.visualizar',
        'solicitacoes.criar',
        'solicitacoes.editar',
        'solicitacoes.excluir',
        'solicitacoes.baixar_pdf',
        'solicitacoes.anexar',
        'solicitacoes.enviar_email',
    ],

    'max_upload_kb' => 10240,
    'allowed_mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
];
