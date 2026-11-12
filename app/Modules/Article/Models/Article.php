<?php

declare(strict_types=1);

namespace App\Modules\Article\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string $content
 * @property string $category
 */
class Article extends Model
{
    protected $fillable = [
        'title', 'content', 'category',
    ];
}
