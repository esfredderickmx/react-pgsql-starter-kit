<?php

namespace App\Exceptions;

use App\Enums\Frontend\EmphasisVariant;
use App\Enums\Frontend\ResponseStyle;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

use function back;

class AppException extends Exception
{
    public function __construct(string $message, protected ResponseStyle $style = ResponseStyle::ALERT, protected EmphasisVariant $variant = EmphasisVariant::AFFIRMATIVE)
    {
        parent::__construct($message, 400);
    }

    public function render(Request $request): RedirectResponse
    {
        Inertia::notify($this->message, $this->style, $this->variant);

        return back();
    }
}
