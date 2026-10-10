<?php

declare(strict_types=1);

use App\Modules\Skill\Http\Controllers\SkillCallController;
use App\Modules\Skill\Http\Controllers\SkillController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/v1/skills')->group(function (): void {
    Route::get('/',    [SkillController::class, 'index']);
    Route::get('/{id}', [SkillController::class, 'show'])->whereNumber('id');
    Route::post('/{id}/call', SkillCallController::class)->whereNumber('id');
});
