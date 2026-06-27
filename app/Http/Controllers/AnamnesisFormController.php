<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnamnesisFormRequest;
use App\Http\Requests\UpdateAnamnesisFormRequest;
use App\Models\AnamnesisForm;
use App\Support\FieldTypeDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnamnesisFormController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(AnamnesisForm::class, 'anamnesis_form');
    }

    public function index(): View
    {
        $forms = AnamnesisForm::query()
            ->where('professional_id', auth()->id())
            ->withCount('questions')
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('anamnesis-forms.index', compact('forms'));
    }

    public function create(): View
    {
        $defaultRow = [
            'label' => '',
            'field_key' => '',
            'field_type' => 'text',
            'required' => false,
            'mask' => null,
            'validation_rules' => [],
            'sort_order' => 0,
        ];

        return view('anamnesis-forms.create', [
            'fieldDefaultsJson' => FieldTypeDefaults::jsonForBuilder(),
            'initialQuestions' => old('questions', [$defaultRow]),
        ]);
    }

    public function show(AnamnesisForm $anamnesisForm): View
    {
        $anamnesisForm->load('questions');

        return view('anamnesis-forms.show', ['form' => $anamnesisForm]);
    }

    public function store(StoreAnamnesisFormRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request) {
            $form = AnamnesisForm::query()->create([
                'professional_id' => $request->user()->clinicalPracticeId(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
            ]);

            $this->syncQuestions($form, $data['questions'] ?? []);
        });

        return redirect()
            ->route('anamnesis-forms.index')
            ->with('status', __('Modelo de anamnese criado.'));
    }

    public function edit(AnamnesisForm $anamnesisForm): View
    {
        $anamnesisForm->load('questions');

        $mapped = $anamnesisForm->questions->map(fn ($q) => [
            'label' => $q->label,
            'field_key' => $q->field_key,
            'field_type' => $q->field_type,
            'required' => $q->required,
            'mask' => $q->mask,
            'validation_rules' => $q->validation_rules ?? [],
            'sort_order' => $q->sort_order,
        ])->values()->all();

        return view('anamnesis-forms.edit', [
            'form' => $anamnesisForm,
            'fieldDefaultsJson' => FieldTypeDefaults::jsonForBuilder(),
            'initialQuestions' => old('questions', $mapped),
        ]);
    }

    public function update(UpdateAnamnesisFormRequest $request, AnamnesisForm $anamnesisForm): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($anamnesisForm, $data) {
            $anamnesisForm->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
            ]);

            $anamnesisForm->questions()->delete();
            $this->syncQuestions($anamnesisForm, $data['questions'] ?? []);
        });

        return redirect()
            ->route('anamnesis-forms.index')
            ->with('status', __('Modelo atualizado.'));
    }

    public function destroy(AnamnesisForm $anamnesisForm): RedirectResponse
    {
        $anamnesisForm->delete();

        return redirect()
            ->route('anamnesis-forms.index')
            ->with('status', __('Modelo removido.'));
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    private function syncQuestions(AnamnesisForm $form, array $questions): void
    {
        foreach ($questions as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            FieldTypeDefaults::applyDefaultsToRow($row);

            $form->questions()->create([
                'label' => $row['label'],
                'field_key' => $row['field_key'],
                'field_type' => $row['field_type'],
                'sort_order' => (int) ($row['sort_order'] ?? $index),
                'required' => filter_var($row['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'mask' => $row['mask'] ?? null,
                'validation_rules' => array_values($row['validation_rules'] ?? []),
                'meta' => $row['meta'] ?? null,
            ]);
        }
    }
}
