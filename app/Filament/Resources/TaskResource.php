<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\Feedback;
use App\Models\Task;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;


class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'id', 'assigned_by', 'assigned_to'];
    }


    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'assigned_by' => $record->assigned_by,
            'assigned_to' => $record->assignedBy->name,
            'priority' => $record->priority,
            'assigned_to' => $record->assignedTo->name,
        ];
    }


    public static function form(Form $form): Form
    {

        $user = Auth::user();

        function access($user, $record)
        {
            if ($user->role === 'admin') {
                return false;
            } elseif ($user->role === 'dhead') {
                $assignBy = $record->assigned_by;
                // dd($assignBy);
                $assignByUser = User::find($assignBy);

                return $assignBy && $assignByUser->role === 'admin';
            } else {
                return $user->role === 'employee';
            }
        }
        return $form
            ->schema([
                Section::make('Task details')->schema([
                    TextInput::make('title')->required()->readOnly(function ($record) use ($user) {
                        return access($user, $record);
                    }),
                    Textarea::make('description')->required()->readOnly(function ($record) use ($user) {
                        return access($user, $record);
                    }),
                ])->columns(1),


                Section::make('Task status')->schema([

                    Select::make('status')->required()->native(false)->options([
                        'pending' => 'pending',
                        'ongoing' => 'ongoing',
                        'completed' => 'completed',
                        'paused' => 'paused'
                    ]),

                    Select::make('category')->required()->native(false)->options([
                        'work' => 'work',
                        'personal' => 'personal',
                        'urgent' => 'urgent',
                    ])->disabled(function ($record) use ($user) {
                        return access($user, $record);
                    }),
                    Select::make('priority')->required()->native(false)->options([
                        'high' => 'high',
                        'medium' => 'medium',
                        'low' => 'low',
                    ])->disabled(function ($record) use ($user) {
                        return access($user, $record);
                    }),

                    TextInput::make('assigned_by')->readOnly()->default(
                        auth()->user()->id
                    ),
                    Select::make('assigned_to')->required()->options(function () {
                        $currentUser = auth()->user();
                        $departmentId = $currentUser->department_id;
                        $usersInSameDepartment = User::where('department_id', $departmentId)->get();
                        $options = [];

                        foreach ($usersInSameDepartment as $user) {
                            $options[$user->id] = $user->id;
                        }

                        return $options;
                    })->live()->afterStateUpdated(function (Set $set, $state) {
                        $user = User::find($state);
                        if ($user) {
                            $set('department_id',  $user->department_id);
                        }
                    })->visible(function () {
                        return Auth::user()->role === 'dhead';
                    }),
                    TextInput::make('assigned_to')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            $user = User::find($state);
                            if ($user) {
                                $set('department_id',  $user->department_id);
                            }
                        })->readOnly(function () use ($user) {
                            return $user->role === 'employee';
                        }),
                    TextInput::make('department_id')
                        ->required()->readOnly()
                ])->columns(3)
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table

            ->query(function () {
                $user = Auth::user();
                if ($user->role === 'admin') {
                    return Task::query();
                } elseif ($user->role === 'dhead') {
                    return Task::query()->where('assigned_to', $user->id)->orWhere('assigned_by', $user->id);
                } elseif ($user->role === 'employee') {
                    return Task::query()->where('assigned_to', $user->id);
                }
            })
            ->columns([
                TextColumn::make('id')->copyable()
                    ->copyMessage('Task ID copied')
                    ->copyMessageDuration(1500),


                TextColumn::make('title')->searchable(),

                TextColumn::make('priority')->badge()->color(fn (string $state): string => match ($state) {
                    'low' => 'gray',
                    'medium' => 'warning',
                    'high' => 'danger',
                }),
                TextColumn::make('category')->badge()->color(fn (string $state): string => match ($state) {
                    'personal' => 'gray',
                    'work' => 'warning',
                    'urgent' => 'danger',
                }),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'paused' => 'gray',
                    'ongoing' => 'warning',
                    'pending' => 'danger',
                    'completed' => 'success',
                }),


                TextColumn::make('assignedBy.name'),
                TextColumn::make('assignedTo.name'),
                TextColumn::make('department.name'),
            ])


            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('feedback')->icon('heroicon-o-chat-bubble-left-right')
                    ->slideOver()
                    ->form(function (Form $form, $record) {
                        return $form

                            ->schema([
                                TextInput::make('task_id')->readOnly()->default(
                                    $record->id
                                ),
                                TextInput::make('user_id')->readOnly()->default(
                                    Auth::user()->id
                                ),
                                TextInput::make('message'),
                            ]);
                    })->action(function (array $data) {
                        $feedback = new Feedback();
                        $feedback->task_id = $data['task_id'];
                        $feedback->user_id = $data['user_id'];
                        $feedback->message = $data['message'];
                        $feedback->save();
                        Notification::make()->title('Feedback submitted successfully')->success()->send();
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
