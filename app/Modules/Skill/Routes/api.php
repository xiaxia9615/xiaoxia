<?php

declare(strict_types=1);

use App\Modules\Skill\Http\Controllers\SkillCallController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/v1')->group(function (): void {
    Route::post('/skills/{id}/call', SkillCallController::class);
});
