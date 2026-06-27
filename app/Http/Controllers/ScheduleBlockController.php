<?php

namespace App\Http\Controllers;

use App\Models\ScheduleBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleBlockController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ScheduleBlock::class, 'schedule_block');
    }

    public function index(Request $request): View
    {
        $blocks = ScheduleBlock::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->orderByDesc('block_date')
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        return view('schedule-blocks.index', compact('blocks'));
    }

    public function create(): View
    {
        return view('schedule-blocks.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedBlock($request);
        $validated['professional_id'] = $request->user()->clinicalPracticeId();

        ScheduleBlock::query()->create($validated);

        return redirect()
            ->route('schedule-blocks.index')
            ->with('status', 'Bloqueio criado.');
    }

    public function edit(ScheduleBlock $scheduleBlock): View
    {
        return view('schedule-blocks.edit', ['block' => $scheduleBlock]);
    }

    public function update(Request $request, ScheduleBlock $scheduleBlock): RedirectResponse
    {
        $scheduleBlock->update($this->validatedBlock($request));

        return redirect()
            ->route('schedule-blocks.index')
            ->with('status', 'Bloqueio atualizado.');
    }

    public function destroy(ScheduleBlock $scheduleBlock): RedirectResponse
    {
        $scheduleBlock->delete();

        return redirect()
            ->route('schedule-blocks.index')
            ->with('status', 'Bloqueio removido.');
    }

    /**
     * @return array{block_date: string, start_time: string, end_time: string, reason: string|null}
     */
    private function validatedBlock(Request $request): array
    {
        $validated = $request->validate([
            'block_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $start = strlen($validated['start_time']) === 5 ? $validated['start_time'].':00' : $validated['start_time'];
        $end = strlen($validated['end_time']) === 5 ? $validated['end_time'].':00' : $validated['end_time'];

        if ($start >= $end) {
            throw ValidationException::withMessages([
                'end_time' => __('O horário final deve ser após o inicial.'),
            ]);
        }

        return [
            'block_date' => $validated['block_date'],
            'start_time' => $start,
            'end_time' => $end,
            'reason' => $validated['reason'] ?? null,
        ];
    }
}
