<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class EmailTemplate extends Resource
{
    public static string $model = \App\Models\EmailTemplate::class;

    public static $title = 'name';

    public static $search = ['key', 'name'];

    public static function label(): string
    {
        return 'Email Templates';
    }

    public static function singularLabel(): string
    {
        return 'Email Template';
    }

    /**
     * Templates are created by the seeder and keyed by code paths. Admins
     * edit copy; they do not add or remove template rows.
     */
    public static function authorizedToCreate(Request $request): bool
    {
        return false;
    }

    public function authorizedToDelete(Request $request): bool
    {
        return false;
    }

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Name')
                ->sortable()
                ->readonly()
                ->help('A friendly name for admins. Not sent in emails.'),

            Text::make('Key')
                ->readonly()
                ->help('The code key used to look this template up. Immutable.'),

            Textarea::make('Description')
                ->readonly()
                ->alwaysShow()
                ->help('When this email is sent.'),

            Panel::make('Available variables', [
                Code::make('Available variables', 'available_variables')
                    ->json()
                    ->readonly()
                    ->help('Use these in the subject or body with the syntax {{ variable_name }}.'),
            ]),

            Panel::make('English content', [
                Text::make('Subject (EN)', 'subject_en')->rules('required', 'max:255'),
                Markdown::make('Body (EN)', 'body_en')
                    ->rules('required')
                    ->alwaysShow()
                    ->help('Use the toolbar for formatting. Variables use <code>{{ variable_name }}</code> and are listed in the panel above.'),
            ]),

            Panel::make('Urdu content', [
                Text::make('Subject (UR)', 'subject_ur')->rules('required', 'max:255'),
                Markdown::make('Body (UR)', 'body_ur')
                    ->rules('required')
                    ->alwaysShow()
                    ->help('Use the toolbar for formatting. Variables use <code>{{ variable_name }}</code> and are listed in the panel above.'),
            ]),
        ];
    }
}
