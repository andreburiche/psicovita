<?php

namespace App\Enums;

enum UserProfessionalFunction: string
{
    case Psychologist = 'psychologist';
    case ClinicalPsychologist = 'clinical_psychologist';
    case Psychotherapist = 'psychotherapist';
    case Psychiatrist = 'psychiatrist';
    case Neuropsychologist = 'neuropsychologist';
    case Psychopedagogue = 'psychopedagogue';
    case OccupationalTherapist = 'occupational_therapist';
    case SpeechTherapist = 'speech_therapist';
    case SocialWorker = 'social_worker';
    case EducationalCounselor = 'educational_counselor';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Psychologist => __('Psicólogo(a)'),
            self::ClinicalPsychologist => __('Psicólogo(a) clínico(a)'),
            self::Psychotherapist => __('Psicoterapeuta'),
            self::Psychiatrist => __('Psiquiatra'),
            self::Neuropsychologist => __('Neuropsicólogo(a)'),
            self::Psychopedagogue => __('Psicopedagogo(a)'),
            self::OccupationalTherapist => __('Terapeuta ocupacional'),
            self::SpeechTherapist => __('Fonoaudiólogo(a)'),
            self::SocialWorker => __('Assistente social'),
            self::EducationalCounselor => __('Orientador(a) educacional'),
            self::Other => __('Outro'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public static function tryFromValue(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom($value);
    }
}
