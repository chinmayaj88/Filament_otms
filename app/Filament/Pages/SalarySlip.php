<?php

namespace App\Filament\Pages;

use App\Models\SalarySlip as ModelsSalarySlip;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Blade;

class SalarySlip extends Page implements HasForms, HasTable, HasActions
{
    use InteractsWithForms;
    use InteractsWithTable;


    public ?array $data = [];
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.salary-slip';

    protected static ?string $title = 'Salary Slip';


    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('salary details')->schema([
                    TextInput::make('user_id')->required(),
                    TextInput::make('issued_by')->default(auth()->user()->id)->required(),
                    TextInput::make('ctc')->numeric()->inputMode('decimal')->required()->live(),
                    TextInput::make('bonus')->numeric()->inputMode('decimal')->required()->live(),
                    TextInput::make('tax')->numeric()->inputMode('decimal')->required()->live()->afterStateUpdated(function (Set $set, $state, Get $get) {
                        $ctc = $get('ctc');
                        $bonus = $get('bonus');
                        $tax = $state;

                        $InHand = ($ctc + $bonus) - $tax;
                        $set('in_hand', $InHand);
                    }),
                    TextInput::make('in_hand')->numeric()->inputMode('decimal')->required()->readOnly(true)
                ])->columns(3)
            ])->statePath('data');
    }

    protected function getFormActions(): array
    {
        $btns = [
            Action::make('save')->submit('save'),
        ];
        return $btns;
    }




    public function save()
    {
        try {
            $data = $this->form->getState();
            $salary = new ModelsSalarySlip();
            $salary->user_id = $data['user_id'];
            $salary->issued_by = $data['issued_by'];
            $salary->ctc = $data['ctc'];
            $salary->bonus = $data['bonus'];
            $salary->tax = $data['tax'];
            $salary->in_hand = $data['in_hand'];
            $salary->save();
            Notification::make()
                ->success()
                ->title('salary saved successfully')
                ->send();
        } catch (Halt $ex) {
            return;
        }
    }

    public function table(Table $table): Table
    {

        return $table
            ->query(ModelsSalarySlip::query())
            ->columns([
                TextColumn::make('user_id'),
                TextColumn::make('user_id'),
                TextColumn::make('issued_by'),
                TextColumn::make('ctc'),
                TextColumn::make('bonus'),
                TextColumn::make('tax')
            ])
            ->actions([
                TableAction::make('pdf')
                    ->label('PDF')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-on-square-stack')
                    ->action(function ($record) {
                        return response()->streamDownload(function () use ($record) {
                            echo Pdf::loadHtml(
                                Blade::render('Dpdf', ['record' => $record])
                            )->stream();
                        }, $record->id . '.pdf');
                    }),
            ])
            ->filters([
                // ...
            ])
            ->bulkActions([]);
    }
}
