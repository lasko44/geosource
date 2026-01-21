<?php

namespace App\Nova\Tools;

use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Tool;

class Documentation extends Tool
{
    /**
     * Perform any tasks that need to happen when the tool is booted.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Build the menu that renders the navigation links for the tool.
     */
    public function menu(Request $request): mixed
    {
        return MenuSection::make('Developer')
            ->path('/documentation')
            ->icon('book-open');
    }
}
