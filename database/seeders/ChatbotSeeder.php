<?php

namespace Database\Seeders;

use App\Models\ChatbotFlow;
use App\Models\ChatbotIntent;
use App\Models\ChatbotResponse;
use App\Models\SupportQueue;
use Illuminate\Database\Seeder;

class ChatbotSeeder extends Seeder
{
    public function run(): void
    {
        $queues = [
            ['slug' => 'rh', 'name' => 'Recursos Humanos', 'sort_order' => 10],
            ['slug' => 'ti', 'name' => 'Tecnologia', 'sort_order' => 20],
            ['slug' => 'ouvidoria', 'name' => 'Ouvidoria', 'sort_order' => 30],
            ['slug' => 'assistencia', 'name' => 'Assistência Social', 'sort_order' => 40],
            ['slug' => 'admin', 'name' => 'Administrativo', 'sort_order' => 50],
            ['slug' => 'clinico', 'name' => 'Apoio Clínico', 'sort_order' => 60],
        ];

        foreach ($queues as $queue) {
            SupportQueue::query()->updateOrCreate(
                ['slug' => $queue['slug']],
                [
                    'name' => $queue['name'],
                    'sort_order' => $queue['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        $flow = ChatbotFlow::query()->updateOrCreate(
            ['slug' => 'support-default'],
            ['name' => 'Atendimento geral', 'is_active' => true],
        );

        $intents = [
            [
                'slug' => 'update_profile',
                'label' => 'Atualizar cadastro',
                'phrases' => ['atualizar cadastro', 'atualizar meu cadastro', 'mudar meus dados', 'alterar cadastro'],
                'action' => 'handoff',
                'queue' => 'admin',
                'response' => 'Vou encaminhar para o setor administrativo. Protocolo :protocol.',
                'priority' => 80,
            ],
            [
                'slug' => 'benefit_issue',
                'label' => 'Benefício',
                'phrases' => ['beneficio', 'benefício', 'nao recebi', 'não recebi', 'pagamento atrasado'],
                'action' => 'handoff',
                'queue' => 'assistencia',
                'response' => 'Entendi. Encaminho para Assistência Social. Protocolo :protocol.',
                'priority' => 90,
            ],
            [
                'slug' => 'technical_support',
                'label' => 'Suporte técnico',
                'phrases' => ['login', 'senha', 'erro', 'nao consigo entrar', 'não consigo entrar', 'suporte tecnico'],
                'action' => 'handoff',
                'queue' => 'ti',
                'response' => 'Vou encaminhar para o suporte técnico. Protocolo :protocol.',
                'priority' => 70,
            ],
            [
                'slug' => 'complaint',
                'label' => 'Ouvidoria',
                'phrases' => ['reclamacao', 'reclamação', 'ouvidoria', 'insatisfeito'],
                'action' => 'handoff',
                'queue' => 'ouvidoria',
                'response' => 'Registei o seu contacto na Ouvidoria. Protocolo :protocol.',
                'priority' => 85,
            ],
            [
                'slug' => 'human_agent',
                'label' => 'Falar com atendente',
                'phrases' => ['atendente', 'humano', 'pessoa', 'falar com alguem', 'falar com alguém'],
                'action' => 'handoff',
                'queue' => 'admin',
                'response' => 'Vou pedir que um atendente assuma esta conversa. Protocolo :protocol.',
                'priority' => 100,
            ],
            [
                'slug' => 'schedule_appointment',
                'label' => 'Agendar atendimento na clínica',
                'phrases' => ['agendar', 'marcar consulta', 'marcar atendimento', 'agenda', 'primeira consulta', 'agendar com a clinica'],
                'action' => 'handoff',
                'queue' => 'admin',
                'response' => 'Vou encaminhar para agendamento. Protocolo :protocol.',
                'priority' => 95,
            ],
            [
                'slug' => 'talk_to_professional',
                'label' => 'Falar com profissional',
                'phrases' => ['falar com profissional', 'meu psicologo', 'meu psicólogo', 'terapeuta', 'profissional'],
                'action' => 'handoff',
                'queue' => 'clinico',
                'response' => 'Vou encaminhar para o apoio clínico. Protocolo :protocol.',
                'priority' => 92,
            ],
            [
                'slug' => 'professional_contact',
                'label' => 'Sou profissional — falar com a clínica',
                'phrases' => ['sou profissional', 'sou psicologo', 'sou psicólogo', 'cadastro profissional'],
                'action' => 'handoff',
                'queue' => 'rh',
                'response' => 'Vou encaminhar para Recursos Humanos. Protocolo :protocol.',
                'priority' => 88,
            ],
            [
                'slug' => 'greeting',
                'label' => 'Saudação',
                'phrases' => ['ola', 'olá', 'bom dia', 'boa tarde', 'oi'],
                'action' => 'reply',
                'queue' => null,
                'response' => 'Olá! Escolha uma das opções abaixo.',
                'priority' => 10,
            ],
        ];

        foreach ($intents as $data) {
            $queueId = $data['queue']
                ? SupportQueue::query()->where('slug', $data['queue'])->value('id')
                : null;

            $intent = ChatbotIntent::query()->updateOrCreate(
                [
                    'chatbot_flow_id' => $flow->id,
                    'slug' => $data['slug'],
                ],
                [
                    'label' => $data['label'],
                    'training_phrases' => $data['phrases'],
                    'route_action' => $data['action'],
                    'target_queue_id' => $queueId,
                    'priority' => $data['priority'],
                    'is_active' => true,
                ],
            );

            ChatbotResponse::query()->updateOrCreate(
                [
                    'chatbot_intent_id' => $intent->id,
                    'locale' => 'pt_BR',
                ],
                [
                    'body_template' => $data['response'],
                    'quick_replies' => ['Cadastro', 'Benefício', 'Suporte técnico', 'Atendente'],
                ],
            );
        }
    }
}
