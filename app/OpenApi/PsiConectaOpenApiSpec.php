<?php

namespace App\OpenApi;

final class PsiConectaOpenApiSpec
{
    /**
     * @return array<string, mixed>
     */
    public static function document(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'PsiConecta API',
                'version' => '1.0.0',
                'description' => 'API REST v1 para profissionais (multi-tenant por profissional). Autenticação: Bearer token (Laravel Sanctum). Habilidades opcionais no login: `*`, `api:read`, `api:write`.',
            ],
            'servers' => [
                ['url' => '/api', 'description' => 'Base relativa'],
            ],
            'tags' => [
                ['name' => 'Sistema'],
                ['name' => 'Auth'],
                ['name' => 'Resumo'],
                ['name' => 'Pacientes'],
                ['name' => 'Sessões'],
                ['name' => 'Pagamentos'],
                ['name' => 'Prontuário'],
                ['name' => 'Integrações'],
            ],
            'paths' => [
                '/v1/health' => [
                    'get' => [
                        'tags' => ['Sistema'],
                        'summary' => 'Health check',
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                ],
                '/v1/openapi.json' => [
                    'get' => [
                        'tags' => ['Sistema'],
                        'summary' => 'Este documento OpenAPI (JSON)',
                        'responses' => ['200' => ['description' => 'Especificação OpenAPI']],
                    ],
                ],
                '/v1/auth/login' => [
                    'post' => [
                        'tags' => ['Auth'],
                        'summary' => 'Obter token',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['email', 'password'],
                                        'properties' => [
                                            'email' => ['type' => 'string', 'format' => 'email'],
                                            'password' => ['type' => 'string'],
                                            'device_name' => ['type' => 'string'],
                                            'abilities' => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string', 'enum' => ['*', 'api:read', 'api:write']],
                                                'description' => 'Opcional. Padrão: `*`.',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Token emitido'],
                            '403' => ['description' => 'Não profissional'],
                            '422' => ['description' => 'Validação'],
                        ],
                    ],
                ],
                '/v1/auth/logout' => [
                    'post' => [
                        'tags' => ['Auth'],
                        'summary' => 'Revogar token atual',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['200' => ['description' => 'Revogado']],
                    ],
                ],
                '/v1/summary' => [
                    'get' => [
                        'tags' => ['Resumo'],
                        'summary' => 'Indicadores do painel',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['200' => ['description' => 'JSON de resumo']],
                    ],
                ],
                '/v1/patients' => [
                    'get' => [
                        'tags' => ['Pacientes'],
                        'summary' => 'Listar pacientes',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['200' => ['description' => 'Lista paginada']],
                    ],
                    'post' => [
                        'tags' => ['Pacientes'],
                        'summary' => 'Criar paciente',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['201' => ['description' => 'Criado'], '422' => ['description' => 'Validação']],
                    ],
                ],
                '/v1/patients/{id}' => [
                    'get' => [
                        'tags' => ['Pacientes'],
                        'summary' => 'Detalhe do paciente',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => ['200' => ['description' => 'OK'], '404' => ['description' => 'Não encontrado']],
                    ],
                    'put' => [
                        'tags' => ['Pacientes'],
                        'summary' => 'Atualizar paciente',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => ['200' => ['description' => 'OK'], '422' => ['description' => 'Validação']],
                    ],
                    'delete' => [
                        'tags' => ['Pacientes'],
                        'summary' => 'Remover paciente',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => ['204' => ['description' => 'Sem conteúdo']],
                    ],
                ],
                '/v1/therapy-sessions' => [
                    'get' => [
                        'tags' => ['Sessões'],
                        'summary' => 'Listar sessões',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['200' => ['description' => 'Lista paginada']],
                    ],
                    'post' => [
                        'tags' => ['Sessões'],
                        'summary' => 'Agendar sessão',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['201' => ['description' => 'Criado'], '422' => ['description' => 'Validação / conflito']],
                    ],
                ],
                '/v1/therapy-sessions/{id}' => [
                    'get' => [
                        'tags' => ['Sessões'],
                        'summary' => 'Detalhe da sessão',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                    'put' => [
                        'tags' => ['Sessões'],
                        'summary' => 'Atualizar sessão',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                    'delete' => [
                        'tags' => ['Sessões'],
                        'summary' => 'Cancelar / remover sessão',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => ['204' => ['description' => 'Sem conteúdo']],
                    ],
                ],
                '/v1/payments' => [
                    'get' => [
                        'tags' => ['Pagamentos'],
                        'summary' => 'Listar pagamentos',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['200' => ['description' => 'Lista paginada']],
                    ],
                    'post' => [
                        'tags' => ['Pagamentos'],
                        'summary' => 'Registrar pagamento',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['201' => ['description' => 'Criado']],
                    ],
                ],
                '/v1/payments/{id}' => [
                    'get' => [
                        'tags' => ['Pagamentos'],
                        'summary' => 'Detalhe do pagamento',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                    'put' => [
                        'tags' => ['Pagamentos'],
                        'summary' => 'Atualizar pagamento',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                    'delete' => [
                        'tags' => ['Pagamentos'],
                        'summary' => 'Remover pagamento',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => ['204' => ['description' => 'Sem conteúdo']],
                    ],
                ],
                '/v1/clinical-records' => [
                    'get' => [
                        'tags' => ['Prontuário'],
                        'summary' => 'Listar registros clínicos',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['200' => ['description' => 'Lista paginada']],
                    ],
                    'post' => [
                        'tags' => ['Prontuário'],
                        'summary' => 'Criar registro',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['201' => ['description' => 'Criado']],
                    ],
                ],
                '/v1/clinical-records/{id}' => [
                    'get' => [
                        'tags' => ['Prontuário'],
                        'summary' => 'Detalhe do registro',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                    'put' => [
                        'tags' => ['Prontuário'],
                        'summary' => 'Atualizar registro',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                    'delete' => [
                        'tags' => ['Prontuário'],
                        'summary' => 'Remover registro',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => ['204' => ['description' => 'Sem conteúdo']],
                    ],
                ],
                '/v1/integrations/whatsapp/webhook' => [
                    'get' => [
                        'tags' => ['Integrações'],
                        'summary' => 'Verificação webhook WhatsApp (Meta)',
                        'parameters' => [
                            ['name' => 'hub.mode', 'in' => 'query', 'schema' => ['type' => 'string']],
                            ['name' => 'hub.verify_token', 'in' => 'query', 'schema' => ['type' => 'string']],
                            ['name' => 'hub.challenge', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ],
                        'responses' => ['200' => ['description' => 'Challenge em texto plano'], '403' => ['description' => 'Token inválido']],
                    ],
                    'post' => [
                        'tags' => ['Integrações'],
                        'summary' => 'Eventos WhatsApp (Meta Cloud API)',
                        'responses' => ['200' => ['description' => 'Recebido'], '503' => ['description' => 'Integração desativada']],
                    ],
                ],
                '/v1/integrations/evolution/webhook' => [
                    'post' => [
                        'tags' => ['Integrações'],
                        'summary' => 'Webhook Evolution API (messages.upsert)',
                        'description' => 'Recebe mensagens inbound da Evolution API. Requer WHATSAPP_DRIVER=evolution. Token opcional via header X-Webhook-Token ou query token.',
                        'parameters' => [
                            ['name' => 'X-Webhook-Token', 'in' => 'header', 'required' => false, 'schema' => ['type' => 'string']],
                            ['name' => 'token', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'event' => ['type' => 'string', 'example' => 'messages.upsert'],
                                            'instance' => ['type' => 'string'],
                                            'data' => ['type' => 'object'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Evento processado',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'received' => ['type' => 'boolean'],
                                                'ingested' => ['type' => 'integer'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '403' => ['description' => 'Token de webhook inválido'],
                            '503' => ['description' => 'Integração desactivada ou driver incorrecto'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Sanctum',
                    ],
                ],
            ],
        ];
    }
}
