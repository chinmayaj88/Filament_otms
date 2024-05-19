<?php

namespace App\Filament\Pages;

use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;


class EditProfile extends Page implements HasForms
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.edit-profile';

    protected static bool $shouldRegisterNavigation = false;


    public ?array $profileData = [];
    public ?array $passwordData = [];

    public function mount(): void
    {
        $this->form->fill();
    }


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User details')->schema([
                    TextInput::make('name')->required()->default(auth()->user()->name),
                    TextInput::make('email')->required()->default(auth()->user()->email)->unique(ignoreRecord: true),
                    TextInput::make('password'),
                    FileUpload::make('avatar')
                        ->directory('form-attachments')
                        ->image()
                        ->imageEditor()
                        // ->minSize(512)
                        ->maxSize(7000)->default(auth()->user()->avatar),
                ])->columns(3)
            ])->model($this->getUser())
            ->statePath('profileData');
    }


    protected function getUser(): Authenticatable & Model
    {
        $user = Filament::auth()->user();
        if (!$user instanceof Model) {
            throw new Exception('The authenticated user object must be an Eloquent model to allow the profile page to update it.');
        }
        return $user;
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
        $data = $this->form->getState();
        // dd($data);
        $user = $this->getUser();
        $user->name = $data['name'];
        $user->avatar = $data['avatar'];

        if ($data['email'] !== $user->email) {

            $existingUser = User::where('email', $data['email'])->first();
            if ($existingUser && $existingUser->id !== $user->id) {

                Notification::make()
                    ->danger()
                    ->title('Email already exists')
                    ->send();
                return;
            }
            $user->email = $data['email'];
        }

        if (!empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }

        $user->save();
        session()->flush();
        redirect("/admin/login");


        Notification::make()
            ->success()
            ->title('User saved successfully')
            ->send();
        try {
        } catch (Halt $ex) {
            return;
        }
    }
}
