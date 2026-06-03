<?php

namespace Afterburner\Subscriptions\Livewire\Admin\TeamTrials;

use Afterburner\Subscriptions\Actions\ClearTeamTrial;
use Afterburner\Subscriptions\Actions\SetTeamTrial;
use Afterburner\Subscriptions\Support\SubscriptionStatus;
use Afterburner\Subscriptions\Support\SubscriptionSummary;
use Afterburner\Subscriptions\Support\TeamTrialAdmin;
use Afterburner\Subscriptions\Support\TeamTrialDisplay;
use Afterburner\Subscriptions\Support\TeamTrialManagement;
use Afterburner\Subscriptions\Support\TeamTrialSchedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $teamSearch = '';

    public ?int $selectedTeamId = null;

    public ?int $presetDays = null;

    public bool $useCustomDate = false;

    public ?string $customEndsAt = null;

    public string $extendMode = TeamTrialSchedule::EXTEND_FROM_TODAY;

    protected $queryString = [
        'selectedTeamId' => ['except' => null],
    ];

    public function mount(): void
    {
        $this->authorize('viewAny', TeamTrialManagement::class);

        if ($this->presetDays === null) {
            $presets = TeamTrialAdmin::dayPresets();
            $this->presetDays = $presets[0] ?? 30;
        }
    }

    public function updatedTeamSearch(): void
    {
        //
    }

    public function selectTeam(int $teamId): void
    {
        $this->selectedTeamId = $teamId;
        $this->teamSearch = '';
    }

    public function editTeam(int $teamId): void
    {
        $this->selectTeam($teamId);
    }

    public function applyTrial(): void
    {
        $team = $this->selectedTeam();

        $this->authorize('manageTeamTrial', $team);

        if ($this->presetDays !== null) {
            $this->presetDays = (int) $this->presetDays;
        }

        $presets = TeamTrialAdmin::dayPresets();
        $timezone = TeamTrialAdmin::teamTimezone($team);

        $this->validate([
            'selectedTeamId' => ['required', 'integer'],
            'extendMode' => ['required', Rule::in([
                TeamTrialSchedule::EXTEND_FROM_TODAY,
                TeamTrialSchedule::EXTEND_FROM_CURRENT_END,
            ])],
            'presetDays' => [Rule::requiredIf(! $this->useCustomDate), 'nullable', 'integer', Rule::in($presets)],
            'customEndsAt' => [
                Rule::requiredIf($this->useCustomDate),
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($timezone): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    if (Carbon::parse($value, $timezone)->endOfDay()->lte(now($timezone))) {
                        $fail('The trial end date must be after today in the team\'s timezone.');
                    }
                },
            ],
        ]);

        try {
            $endsAt = TeamTrialSchedule::resolveEndsAt(
                $team->trial_ends_at,
                $this->extendMode,
                $this->useCustomDate ? null : $this->presetDays,
                $this->useCustomDate ? $this->customEndsAt : null,
                $presets,
                $timezone,
            );
        } catch (InvalidArgumentException $exception) {
            $this->addError('presetDays', $exception->getMessage());

            return;
        }

        app(SetTeamTrial::class)($team, $endsAt);

        $team->refresh();

        session()->flash('flash', [
            'bannerStyle' => 'success',
            'banner' => 'Trial updated for '.$team->name.'.',
        ]);

        $this->resetPage('teamTrialsPage');
    }

    public function clearTrial(): void
    {
        $team = $this->selectedTeam();

        $this->authorize('manageTeamTrial', $team);

        app(ClearTeamTrial::class)($team);

        $team->refresh();

        session()->flash('flash', [
            'bannerStyle' => 'success',
            'banner' => 'Trial ended for '.$team->name.'.',
        ]);

        $this->resetPage('teamTrialsPage');
    }

    public function render()
    {
        $selectedTeam = $this->resolveSelectedTeam();
        $searchResults = TeamTrialAdmin::searchTeams($this->teamSearch);

        $activeTrials = TeamTrialAdmin::activeTrialsQuery()->paginate(10, pageName: 'teamTrialsPage');

        $summary = $selectedTeam ? SubscriptionSummary::forTeam($selectedTeam) : null;
        $hasPaidSubscription = $selectedTeam
            && method_exists($selectedTeam, 'subscribed')
            && $selectedTeam->subscribed();

        return view('afterburner-subscriptions::admin.team-trials.livewire.index', [
            'searchResults' => $searchResults,
            'selectedTeam' => $selectedTeam,
            'summary' => $summary,
            'statusLabel' => $selectedTeam ? SubscriptionStatus::forTeam($selectedTeam)->statusLabel() : null,
            'hasPaidSubscription' => $hasPaidSubscription,
            'activeTrials' => $activeTrials,
            'dayPresets' => TeamTrialAdmin::dayPresets(),
            'supportsTrials' => TeamTrialAdmin::teamSupportsTrials(),
            'minCustomTrialDate' => $selectedTeam
                ? TeamTrialDisplay::minCustomTrialDate($selectedTeam)
                : now()->addDay()->toDateString(),
        ]);
    }

    protected function resolveSelectedTeam(): ?Model
    {
        if ($this->selectedTeamId === null) {
            return null;
        }

        return TeamTrialAdmin::teamModelClass()::query()->find($this->selectedTeamId);
    }

    protected function selectedTeam(): Model
    {
        $team = $this->resolveSelectedTeam();

        if ($team === null) {
            abort(422, 'Select a team first.');
        }

        return $team;
    }
}
